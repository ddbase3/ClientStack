<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of ClientStack for BASE3 Framework.
 *
 * ClientStack manages browser assets for modular BASE3 applications.
 * It provides reusable JavaScript and CSS library registrations.
 * Logical asset identifiers are resolved to deployment-specific URLs.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/ClientStack
 **********************************************************************/

namespace Base3\ClientStack\Test\Display;

use Base3\CoreFoundation\Api\IClassMap;
use Base3\CoreFoundation\Api\IMvcView;
use Base3\CoreFoundation\Api\IRequest;
use Base3\CoreFoundation\Authorization\Api\IAuthorizationService;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationDecision;
use Base3\CoreFoundation\Configuration\Api\IConfiguration;
use Base3\CoreFoundation\LinkTarget\Api\ILinkTargetService;
use Base3\CoreFoundation\Session\Api\ISession;
use Base3\CoreFoundation\Translation\Api\ITranslation;
use Base3\ClientStack\Display\JobsAdminDisplay;
use PHPUnit\Framework\TestCase;

final class JobsAdminDisplaySecurityTest extends TestCase {

	private function createDisplay(IRequest $request, IConfiguration $configuration, ISession $session, bool $authorized = true): JobsAdminDisplay {
		$authorization = $this->createStub(IAuthorizationService::class);
		$authorization->method('getDefaultAuthority')->willReturn('base3');
		$authorization->method('decide')->willReturn($authorized ? AuthorizationDecision::allow() : AuthorizationDecision::deny());
		$translation = $this->createStub(ITranslation::class);
		$translation->method('translate')->willReturn('Request rejected');
		return new JobsAdminDisplay(
			$this->createStub(IClassMap::class),
			$this->createStub(IMvcView::class),
			$request,
			$configuration,
			$this->createStub(ILinkTargetService::class),
			$authorization,
			$session,
			$translation
		);
	}

	public function testUnauthenticatedAuthorizationDecisionBlocksJson(): void {
		$config = $this->createMock(IConfiguration::class);
		$config->expects(self::never())->method('persistValue');
		$request = $this->createStub(IRequest::class);
		$display = $this->createDisplay($request, $config, $this->createStub(ISession::class), false);
		self::assertSame('', $display->getOutput('json'));
		self::assertSame(403, http_response_code());
	}

	public function testGetCannotChangeJobConfigEvenWithAValidFeature(): void {
		$config = $this->createMock(IConfiguration::class);
		$config->expects(self::never())->method('persistValue');
		$config->expects(self::never())->method('setValue');
		$request = $this->createStub(IRequest::class);
		$request->method('get')->willReturnCallback(static fn(string $key, $default = null) => match ($key) {
			'action' => 'set_active',
			'job' => 'job-one',
			'value' => '1',
			default => $default
		});
		$request->method('server')->willReturn('GET');
		$display = $this->createDisplay($request, $config, $this->createStub(ISession::class));
		$json = json_decode($display->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame('error', $json['status']);
		self::assertSame(403, http_response_code());
	}

	public function testPostWithWrongCsrfCannotWriteConfig(): void {
		$config = $this->createMock(IConfiguration::class);
		$config->expects(self::never())->method('persistValue');
		$request = $this->createStub(IRequest::class);
		$request->method('get')->willReturn('');
		$request->method('post')->willReturnCallback(static fn(string $key, $default = null) => match ($key) {
			'action' => 'set_active',
			'job' => 'job-one',
			'value' => '1',
			'csrf' => 'incorrect',
			default => $default
		});
		$request->method('server')->willReturn('POST');
		$session = $this->createStub(ISession::class);
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
		$request = $this->createStub(IRequest::class);
		$request->method('get')->willReturn('');
		$request->method('post')->willReturnCallback(static fn(string $key, $default = null) => match ($key) {
			'action' => 'set_active',
			'job' => 'job-one',
			'value' => '1',
			'csrf' => str_repeat('a', 64),
			default => $default
		});
		$request->method('server')->willReturn('POST');
		$session = $this->createStub(ISession::class);
		$session->method('started')->willReturn(true);
		$session->method('get')->willReturn(str_repeat('a', 64));
		$display = $this->createDisplay($request, $config, $session);
		$json = json_decode($display->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame('ok', $json['status']);
		self::assertSame('job-one', $json['data']['job']['jobKey']);
	}
}
