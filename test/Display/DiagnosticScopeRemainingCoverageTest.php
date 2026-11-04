<?php declare(strict_types=1);

namespace ClientStack\Test\Display;

use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\Authorization\Api\IAuthorizationService;
use Base3\Authorization\Dto\AuthorizationDecision;
use Base3\Diagnostics\Api\IDiagnosticRunner;
use Base3\LinkTarget\Api\ILinkTargetService;
use Base3\Session\Api\ISession;
use Base3\Translation\Api\ITranslation;
use ClientStack\Display\DiagnosticScopeDisplay;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DiagnosticScopeRemainingCoverageTest extends TestCase {

	protected function setUp(): void {
		http_response_code(200);
	}

	protected function tearDown(): void {
		http_response_code(200);
	}

	private function buildDiagnostic(ISession $session, bool $allowed = true, ?array &$assigned = null, mixed $scope = ''): DiagnosticScopeDisplay {
		$runner = $this->createStub(IDiagnosticRunner::class);
		$runner->method('getScopes')->willReturn([]);
		$authorization = $this->createStub(IAuthorizationService::class);
		$authorization->method('getDefaultAuthority')->willReturn('base3');
		$authorization->method('decide')->willReturn($allowed ? AuthorizationDecision::allow() : AuthorizationDecision::deny());
		$translation = $this->createStub(ITranslation::class);
		$translation->method('translate')->willReturnCallback(
			static fn(string $set, string $section, string $key, string $fallback = ''): string => $fallback === '' ? $key : $fallback
		);
		$request = $this->createStub(IRequest::class);
		$request->method('get')->willReturn($scope);
		$view = $this->createStub(IMvcView::class);
		$view->method('loadTemplate')->willReturn('scope-html');
		$view->method('assign')->willReturnCallback(static function(string $key, mixed $value) use (&$assigned): void {
			$assigned[$key] = $value;
		});
		$links = $this->createStub(ILinkTargetService::class);
		$links->method('getLink')->willReturn('/diagnostic');
		return new DiagnosticScopeDisplay($runner, $authorization, $translation, $request, $session, $view, $links);
	}

	public function testHtmlRenderingRenewsMalformedCsrfTokenExactlyOnce(): void {
		$written = [];
		$session = $this->createStub(ISession::class);
		$session->method('started')->willReturn(true);
		$session->method('get')->willReturn('invalid');
		$session->method('set')->willReturnCallback(static function(string $key, mixed $value) use (&$written): void {
			$written[] = [$key, $value];
		});
		$assigned = [];
		$display = $this->buildDiagnostic($session, assigned: $assigned);
		self::assertSame('scope-html', $display->getOutput());
		self::assertCount(1, $written);
		self::assertSame('diagnostics.checks.csrf', $written[0][0]);
		self::assertSame(64, strlen($written[0][1]));
		self::assertSame($written[0][1], $assigned['csrf']);
	}

	public function testHtmlRenderingRequiresAnActiveSessionForCsrf(): void {
		$session = $this->createMock(ISession::class);
		$session->method('started')->willReturn(false);
		$session->expects(self::never())->method('set');
		$display = $this->buildDiagnostic($session);
		$this->expectException(RuntimeException::class);
		$display->getOutput('html');
	}

	public function testNonFinalHtmlAuthorizationErrorDoesNotMutateResponseStatus(): void {
		$session = $this->createStub(ISession::class);
		$display = $this->buildDiagnostic($session, allowed: false);
		self::assertStringContainsString('role="alert"', $display->getOutput('html', false));
		self::assertSame(200, http_response_code());
	}

	public function testNonFinalJsonAuthorizationErrorDoesNotMutateResponseStatus(): void {
		$session = $this->createStub(ISession::class);
		$display = $this->buildDiagnostic($session, allowed: false);
		self::assertSame(['error' => 'Access denied.'], json_decode($display->getOutput('json', false), true, 512, JSON_THROW_ON_ERROR));
		self::assertSame(200, http_response_code());
	}

	public function testNonArrayDisplayConfigurationAndNonStringRequestScopeAreIgnored(): void {
		$session = $this->createStub(ISession::class);
		$session->method('started')->willReturn(true);
		$session->method('get')->willReturn(str_repeat('s', 64));
		$assigned = [];
		$display = $this->buildDiagnostic($session, assigned: $assigned, scope: ['not a string']);
		$display->setData(['scope' => 'previous']);
		$display->setData('invalid');
		self::assertSame('scope-html', $display->getOutput());
		self::assertSame('', $assigned['initialScope']);
	}
}
