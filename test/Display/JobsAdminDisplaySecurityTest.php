<?php declare(strict_types=1);

namespace ClientStack\Test\Display;

use Base3\Api\IClassMap;
use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\Authorization\Api\IAuthorizationService;
use Base3\Authorization\Dto\AuthorizationDecision;
use Base3\Configuration\Api\IConfiguration;
use Base3\LinkTarget\Api\ILinkTargetService;
use Base3\Session\Api\ISession;
use Base3\Translation\Api\ITranslation;
use ClientStack\Display\JobsAdminDisplay;
use PHPUnit\Framework\TestCase;

final class JobsAdminDisplaySecurityTest extends TestCase {

	private function createDisplay(IRequest $request, IConfiguration $configuration, ISession $session, bool $authorized = true): JobsAdminDisplay {
		$authorization = $this->createMock(IAuthorizationService::class);
		$authorization->method('getDefaultAuthority')->willReturn('base3');
		$authorization->method('decide')->willReturn($authorized ? AuthorizationDecision::allow() : AuthorizationDecision::deny());
		$translation = $this->createMock(ITranslation::class);
		$translation->method('translate')->willReturn('Request rejected');
		return new JobsAdminDisplay(
			$this->createMock(IClassMap::class),
			$this->createMock(IMvcView::class),
			$request,
			$configuration,
			$this->createMock(ILinkTargetService::class),
			$authorization,
			$session,
			$translation
		);
	}

	public function testUnauthenticatedAuthorizationDecisionBlocksJson(): void {
		$config = $this->createMock(IConfiguration::class);
		$config->expects(self::never())->method('persistValue');
		$request = $this->createMock(IRequest::class);
		$display = $this->createDisplay($request, $config, $this->createMock(ISession::class), false);
		self::assertSame('', $display->getOutput('json'));
		self::assertSame(403, http_response_code());
	}

	public function testGetCannotChangeJobConfigEvenWithAValidFeature(): void {
		$config = $this->createMock(IConfiguration::class);
		$config->expects(self::never())->method('persistValue');
		$config->expects(self::never())->method('setValue');
		$request = $this->createMock(IRequest::class);
		$request->method('get')->willReturnCallback(static fn(string $key, $default = null) => match ($key) {
			'action' => 'set_active',
			'job' => 'job-one',
			'value' => '1',
			default => $default
		});
		$request->method('server')->willReturn('GET');
		$display = $this->createDisplay($request, $config, $this->createMock(ISession::class));
		$json = json_decode($display->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame('error', $json['status']);
		self::assertSame(403, http_response_code());
	}

	public function testPostWithWrongCsrfCannotWriteConfig(): void {
		$config = $this->createMock(IConfiguration::class);
		$config->expects(self::never())->method('persistValue');
		$request = $this->createMock(IRequest::class);
		$request->method('get')->willReturn('');
		$request->method('post')->willReturnCallback(static fn(string $key, $default = null) => match ($key) {
			'action' => 'set_active',
			'job' => 'job-one',
			'value' => '1',
			'csrf' => 'incorrect',
			default => $default
		});
		$request->method('server')->willReturn('POST');
		$session = $this->createMock(ISession::class);
		$session->method('started')->willReturn(true);
		$session->method('get')->willReturn(str_repeat('a', 64));
		$display = $this->createDisplay($request, $config, $session);
		$json = json_decode($display->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame('error', $json['status']);
		self::assertSame(403, http_response_code());
	}

	public function testAuthorizedPostWithValidCsrfPersistsExactlyOnce(): void {
		$config = $this->createMock(IConfiguration::class);
		$config->expects(self::once())->method('persistValue')->with('job', 'job-one.active', '1')->willReturn(true);
		$request = $this->createMock(IRequest::class);
		$request->method('get')->willReturn('');
		$request->method('post')->willReturnCallback(static fn(string $key, $default = null) => match ($key) {
			'action' => 'set_active',
			'job' => 'job-one',
			'value' => '1',
			'csrf' => str_repeat('a', 64),
			default => $default
		});
		$request->method('server')->willReturn('POST');
		$session = $this->createMock(ISession::class);
		$session->method('started')->willReturn(true);
		$session->method('get')->willReturn(str_repeat('a', 64));
		$display = $this->createDisplay($request, $config, $session);
		$json = json_decode($display->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame('ok', $json['status']);
		self::assertSame('job-one', $json['data']['job']['jobKey']);
	}
}
