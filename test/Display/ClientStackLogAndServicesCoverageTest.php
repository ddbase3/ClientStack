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

use Base3\CoreFoundation\Api\IContainer;
use Base3\CoreFoundation\Api\IMvcView;
use Base3\CoreFoundation\Api\IRequest;
use Base3\CoreFoundation\Authorization\Api\IAuthorizationService;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationDecision;
use Base3\CoreFoundation\Configuration\Api\IConfiguration;
use Base3\CoreFoundation\LinkTarget\Api\ILinkTargetService;
use Base3\CoreFoundation\Logger\Api\ILogger;
use Base3\ClientStack\Display\LogAdminDisplay;
use Base3\ClientStack\Display\ServicesAdminDisplay;
use PHPUnit\Framework\TestCase;

final class ClientStackLogAndServicesCoverageTest extends TestCase {

	protected function setUp(): void { http_response_code(200); }
	protected function tearDown(): void { http_response_code(200); }

	private function authorization(bool $allow): IAuthorizationService {
		$auth = $this->createStub(IAuthorizationService::class);
		$auth->method('getDefaultAuthority')->willReturn('base3');
		$auth->method('decide')->willReturn($allow ? AuthorizationDecision::allow() : AuthorizationDecision::deny());
		return $auth;
	}

	private function view(array &$values): IMvcView {
		$view = $this->createStub(IMvcView::class);
		$view->method('assign')->willReturnCallback(static function(string $key, mixed $value) use (&$values): void {
			$values[$key] = $value;
		});
		$view->method('loadTemplate')->willReturn('view-ok');
		return $view;
	}

	private function logDisplay(bool $allowed, IRequest $request, ILogger $logger, array &$values): LogAdminDisplay {
		$link = $this->createStub(ILinkTargetService::class);
		$link->method('getLink')->willReturn('/base3/logs?out=json');
		return new LogAdminDisplay($request, $this->view($values),
			$this->createStub(IConfiguration::class), $logger, $link, $this->authorization($allowed));
	}

	public function testServicesDisplayRejectsFeatureDenialBeforeLookingUpServices(): void {
		$container = $this->createMock(IContainer::class);
		$container->expects(self::never())->method('get');
		$view = $this->createMock(IMvcView::class);
		$view->expects(self::never())->method('loadTemplate');
		$display = new ServicesAdminDisplay($container, $view, $this->authorization(false));
		self::assertSame('servicesadmindisplay', $display::getName());
		self::assertSame('', $display->getOutput());
		self::assertSame(403, http_response_code());
	}

	public function testServicesDisplayUsesOnlyRegisteredContainerInstances(): void {
		$values = [];
		$container = $this->createStub(IContainer::class);
		$service = new \stdClass();
		$container->method('get')->willReturnCallback(
			static fn(string $name): ?object => $name === IContainer::class ? $service : null
		);
		$display = new ServicesAdminDisplay($container, $this->view($values), $this->authorization(true));
		self::assertSame('view-ok', $display->getOutput('html'));
		self::assertSame([['interface' => IContainer::class, 'service' => \stdClass::class]], $values['services']);
		self::assertStringContainsString('ServicesAdminDisplay', $display->getHelp());
		$display->setData(['ignored' => true]);
	}

	public function testServicesDisplayHandlesEmptyContainer(): void {
		$values = [];
		$display = new ServicesAdminDisplay($this->createStub(IContainer::class), $this->view($values), $this->authorization(true));
		self::assertSame('view-ok', $display->getOutput());
		self::assertSame([], $values['services']);
	}

	public function testLogDisplayDeniesAccessBeforeReadingLogs(): void {
		$logger = $this->createMock(ILogger::class);
		$logger->expects(self::never())->method('getScopes');
		$values = [];
		$display = $this->logDisplay(false, $this->createStub(IRequest::class), $logger, $values);
		self::assertSame('logadmindisplay', $display::getName());
		self::assertSame('', $display->getOutput('json'));
		self::assertSame(403, http_response_code());
	}

	public function testLogDisplayHtmlUsesCanonicalLinkAndTemplate(): void {
		$values = [];
		$display = $this->logDisplay(true, $this->createStub(IRequest::class), $this->createStub(ILogger::class), $values);
		self::assertSame('view-ok', $display->getOutput());
		self::assertSame('/base3/logs?out=json', $values['endpoint']);
		self::assertStringContainsString('log viewer', $display->getHelp());
		$display->setData(['ignored' => true]);
	}

	public function testLogDisplayRejectsUnknownJsonAction(): void {
		$request = $this->createStub(IRequest::class);
		$request->method('get')->willReturnCallback(static fn(string $key): mixed => $key === 'action' ? 'delete' : null);
		$values = [];
		$display = $this->logDisplay(true, $request, $this->createStub(ILogger::class), $values);
		$response = json_decode($display->getOutput('JSON'), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame('error', $response['status']);
		self::assertStringContainsString('Unknown action', $response['message']);
	}

	public function testLogTailSortsScopesAndFallsBackToFirst(): void {
		$request = $this->createStub(IRequest::class);
		$request->method('get')->willReturnCallback(static fn(string $key): mixed => match ($key) {
			'action' => 'tail', 'scope' => 'not-known', 'num' => 0, default => null
		});
		$logger = $this->createMock(ILogger::class);
		$logger->method('getScopes')->willReturn(['zeta', 'alpha']);
		$logger->expects(self::once())->method('getLogs')->with('alpha', 1, true)
			->willReturn([['level' => 'ERROR', 'log' => 'oops']]);
		$values = [];
		$display = $this->logDisplay(true, $request, $logger, $values);
		$response = json_decode($display->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame('ok', $response['status']);
		self::assertSame('alpha', $response['data']['scope']);
		self::assertSame(['alpha', 'zeta'], $response['data']['scopes']);
		self::assertSame(1, $response['data']['num']);
		self::assertSame('error', $response['data']['logs'][0]['level']);
		self::assertSame('oops', $response['data']['logs'][0]['log']);
	}

	public function testLogTailClampsMaximumAndPreservesValidScope(): void {
		$request = $this->createStub(IRequest::class);
		$request->method('get')->willReturnCallback(static fn(string $key): mixed => match ($key) {
			'action' => 'tail', 'scope' => 'application', 'num' => 10000, default => null
		});
		$logger = $this->createMock(ILogger::class);
		$logger->method('getScopes')->willReturn(['application']);
		$logger->expects(self::once())->method('getLogs')->with('application', 500, true)->willReturn([]);
		$values = [];
		$response = json_decode($this->logDisplay(true, $request, $logger, $values)->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame(500, $response['data']['num']);
		self::assertSame('application', $response['data']['scope']);
	}

	public function testLogTailDoesNotReadLogsWithoutAvailableScopes(): void {
		$request = $this->createStub(IRequest::class);
		$request->method('get')->willReturn('tail');
		$logger = $this->createMock(ILogger::class);
		$logger->method('getScopes')->willReturn([]);
		$logger->expects(self::never())->method('getLogs');
		$values = [];
		$response = json_decode($this->logDisplay(true, $request, $logger, $values)->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame('', $response['data']['scope']);
		self::assertSame([], $response['data']['logs']);
	}

	public function testLogTailSuppressesProviderExceptionDetailsFromTopLevel(): void {
		$request = $this->createStub(IRequest::class);
		$request->method('get')->willReturn('tail');
		$logger = $this->createStub(ILogger::class);
		$logger->method('getScopes')->willReturnCallback(static fn(): array => throw new \RuntimeException('provider failed'));
		$values = [];
		$response = json_decode($this->logDisplay(true, $request, $logger, $values)->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame('error', $response['status']);
		self::assertStringContainsString('provider failed', $response['message']);
	}
}
