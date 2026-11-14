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

use Base3\CoreFoundation\Api\IMvcView;
use Base3\CoreFoundation\Api\IRequest;
use Base3\CoreFoundation\Authorization\Api\IAuthorizationService;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationDecision;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest;
use Base3\CoreFoundation\Diagnostics\Api\IDiagnosticRunner;
use Base3\CoreFoundation\Diagnostics\Dto\DiagnosticCheckResult;
use Base3\Core\Diagnostics\Scope\PrivacyDiagnosticScope;
use Base3\CoreFoundation\LinkTarget\Api\ILinkTargetService;
use Base3\CoreFoundation\Session\Api\ISession;
use Base3\CoreFoundation\Translation\Api\ITranslation;
use Base3\ClientStack\Display\DiagnosticScopeDisplay;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ClientStackDiagnosticFlowCoverageTest extends TestCase {

	private const CSRF = 'dddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd';

	private function request(array $query = [], array $posted = [], string $method = 'POST'): IRequest {
		$request = $this->createStub(IRequest::class);
		$request->method('get')->willReturnCallback(static fn(string $key, mixed $default = null): mixed => $query[$key] ?? $default);
		$request->method('post')->willReturnCallback(static fn(string $key, mixed $default = null): mixed => $posted[$key] ?? $default);
		$request->method('server')->willReturnCallback(static fn(string $key, mixed $default = null): mixed => $key === 'REQUEST_METHOD' ? $method : $default);
		return $request;
	}

	private function jsonRequest(array $posted = [], string $method = 'POST'): IRequest {
		return $this->request(['diagnostic_scope' => PrivacyDiagnosticScope::getName()], array_merge([
			'diagnostic_csrf' => self::CSRF, 'diagnostic_check' => 'privacytest'
		], $posted), $method);
	}

	private function diagnostic(
		IDiagnosticRunner $runner, ?IRequest $request = null, ?ISession $session = null,
		bool $adminAllowed = true, bool $featureAllowed = true, ?IMvcView $view = null
	): DiagnosticScopeDisplay {
		$authorization = $this->createStub(IAuthorizationService::class);
		$authorization->method('getDefaultAuthority')->willReturn('ilias');
		$authorization->method('decide')->willReturnCallback(static function(AuthorizationRequest $request) use ($adminAllowed, $featureAllowed): AuthorizationDecision {
			$allowed = $request->permission->scope === 'system' ? $adminAllowed : $featureAllowed;
			return $allowed ? AuthorizationDecision::allow() : AuthorizationDecision::deny();
		});
		$translation = $this->createStub(ITranslation::class);
		$translation->method('translate')->willReturnCallback(static fn(string $set, string $section, string $key, string $fallback = ''): string => $fallback !== '' ? $fallback : $key);
		$session ??= $this->createStub(ISession::class);
		$session->method('get')->willReturn(self::CSRF);
		$session->method('started')->willReturn(true);
		$view ??= $this->createStub(IMvcView::class);
		$view->method('loadTemplate')->willReturn('diagnostic-html');
		$links = $this->createStub(ILinkTargetService::class);
		$links->method('getLink')->willReturn('/diagnostic');
		return new DiagnosticScopeDisplay($runner, $authorization, $translation, $request ?? $this->jsonRequest(), $session, $view, $links);
	}

	private function runner(): IDiagnosticRunner {
		$runner = $this->createStub(IDiagnosticRunner::class);
		$runner->method('getScopes')->willReturn([new PrivacyDiagnosticScope()]);
		$runner->method('run')->willReturn(new DiagnosticCheckResult(DiagnosticCheckResult::PASSED, 'passed'));
		return $runner;
	}

	protected function setUp(): void {
		http_response_code(200);
	}

	protected function tearDown(): void {
		http_response_code(200);
	}

	public function testMetadataAndSetDataAreUsedForScopeWhenNoQueryIsProvided(): void {
		$runner = $this->runner();
		$display = $this->diagnostic($runner, $this->request([], ['diagnostic_csrf' => self::CSRF, 'diagnostic_check' => 'privacytest']));
		self::assertSame('diagnosticscopedisplay', DiagnosticScopeDisplay::getName());
		$display->setData(['scope' => ' ' . PrivacyDiagnosticScope::getName() . ' ']);
		$data = json_decode($display->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame('passed', $data['status']);
	}

	public function testQueryScopeTakesPrecedenceOverConfiguredScope(): void {
		$display = $this->diagnostic($this->runner(), $this->jsonRequest());
		$display->setData(['scope' => 'nonexistent']);
		self::assertSame('passed', json_decode($display->getOutput('json'), true, 512, JSON_THROW_ON_ERROR)['status']);
	}

	public function testUnsupportedOutputModeIsRejectedBeforeRunningDiagnostics(): void {
		$runner = $this->createMock(IDiagnosticRunner::class);
		$runner->expects(self::never())->method('run');
		$display = $this->diagnostic($runner);
		$this->expectException(InvalidArgumentException::class);
		$display->getOutput('xml');
	}

	public function testAdminDenialPreventsDiagnosticExecution(): void {
		$runner = $this->createMock(IDiagnosticRunner::class);
		$runner->expects(self::never())->method('run');
		$display = $this->diagnostic($runner, adminAllowed: false);
		self::assertArrayHasKey('error', json_decode($display->getOutput('json', true), true, 512, JSON_THROW_ON_ERROR));
		self::assertSame(403, http_response_code());
	}

	public function testFeatureDenialPreventsDiagnosticExecution(): void {
		$runner = $this->createMock(IDiagnosticRunner::class);
		$runner->expects(self::never())->method('run');
		$display = $this->diagnostic($runner, featureAllowed: false);
		self::assertArrayHasKey('error', json_decode($display->getOutput('json'), true, 512, JSON_THROW_ON_ERROR));
	}

	public function testUnknownScopeDoesNotRunChecks(): void {
		$runner = $this->createMock(IDiagnosticRunner::class);
		$runner->method('getScopes')->willReturn([new PrivacyDiagnosticScope()]);
		$runner->expects(self::never())->method('run');
		$display = $this->diagnostic($runner, $this->request(['diagnostic_scope' => 'other'], []));
		self::assertArrayHasKey('error', json_decode($display->getOutput('json', true), true, 512, JSON_THROW_ON_ERROR));
		self::assertSame(404, http_response_code());
	}

	public function testJsonRequiresPost(): void {
		$runner = $this->createMock(IDiagnosticRunner::class);
		$runner->method('getScopes')->willReturn([new PrivacyDiagnosticScope()]);
		$runner->expects(self::never())->method('run');
		$display = $this->diagnostic($runner, $this->jsonRequest(method: 'GET'));
		self::assertArrayHasKey('error', json_decode($display->getOutput('json', true), true, 512, JSON_THROW_ON_ERROR));
		self::assertSame(405, http_response_code());
	}

	public function testJsonRejectsMalformedCheckNameBeforeExecuting(): void {
		$runner = $this->createMock(IDiagnosticRunner::class);
		$runner->method('getScopes')->willReturn([new PrivacyDiagnosticScope()]);
		$runner->expects(self::never())->method('run');
		$display = $this->diagnostic($runner, $this->jsonRequest(['diagnostic_check' => 'not_valid!']));
		self::assertArrayHasKey('error', json_decode($display->getOutput('json', true), true, 512, JSON_THROW_ON_ERROR));
		self::assertSame(400, http_response_code());
	}

	public function testJsonRejectsNonStringCsrf(): void {
		$runner = $this->createMock(IDiagnosticRunner::class);
		$runner->method('getScopes')->willReturn([new PrivacyDiagnosticScope()]);
		$runner->expects(self::never())->method('run');
		$display = $this->diagnostic($runner, $this->jsonRequest(['diagnostic_csrf' => []]));
		self::assertArrayHasKey('error', json_decode($display->getOutput('json', true), true, 512, JSON_THROW_ON_ERROR));
		self::assertSame(403, http_response_code());
	}

	public function testMissingCheckReturns404WithoutExposingInternalNames(): void {
		$runner = $this->createStub(IDiagnosticRunner::class);
		$runner->method('getScopes')->willReturn([new PrivacyDiagnosticScope()]);
		$runner->method('run')->willReturnCallback(static function(): never { throw new InvalidArgumentException('internal check source name'); });
		$display = $this->diagnostic($runner);
		$text = $display->getOutput('json', true);
		self::assertSame(404, http_response_code());
		self::assertStringNotContainsString('internal check source', $text);
	}

	public function testRuntimeFailureReturnsSanitizedDiagnosticError(): void {
		$runner = $this->createStub(IDiagnosticRunner::class);
		$runner->method('getScopes')->willReturn([new PrivacyDiagnosticScope()]);
		$runner->method('run')->willReturnCallback(static function(): never { throw new RuntimeException('private connection string'); });
		$display = $this->diagnostic($runner);
		$data = json_decode($display->getOutput('json', true), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame(DiagnosticCheckResult::ERROR, $data['status']);
		self::assertSame('error', $data['message']);
		self::assertStringNotContainsString('private connection', json_encode($data, JSON_THROW_ON_ERROR));
		self::assertSame(200, http_response_code());
	}

	public function testSuccessfulCheckReturnsRemediationAndTranslatedDetails(): void {
		$runner = $this->createStub(IDiagnosticRunner::class);
		$runner->method('getScopes')->willReturn([new PrivacyDiagnosticScope()]);
		$runner->method('run')->willReturn(new DiagnosticCheckResult(DiagnosticCheckResult::WARNING,
			'needs_review', ['count' => 3], 'follow_up', ['count' => 3, 'threshold' => 2]));
		$display = $this->diagnostic($runner);
		$data = json_decode($display->getOutput('json', true), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame('warning', $data['status']);
		self::assertSame('follow_up', $data['remediation']);
		self::assertSame([['label' => 'count', 'value' => 3], ['label' => 'threshold', 'value' => 2]], $data['details']);
	}

	public function testHtmlOverviewPassesScopeAndTranslationLabels(): void {
		$runner = $this->runner();
		$assigned = [];
		$view = $this->createStub(IMvcView::class);
		$view->method('loadTemplate')->willReturn('diagnostic-html');
		$view->method('assign')->willReturnCallback(static function(string $key, mixed $value) use (&$assigned): void {$assigned[$key] = $value;});
		$display = $this->diagnostic($runner, $this->request(['diagnostic_scope' => PrivacyDiagnosticScope::getName()]), view: $view);
		self::assertSame('diagnostic-html', $display->getOutput('html'));
		self::assertSame(PrivacyDiagnosticScope::getName(), $assigned['initialScope']);
		self::assertSame(self::CSRF, $assigned['csrf']);
		self::assertSame('search_placeholder', $assigned['labels']['search_placeholder']);
		self::assertSame('/diagnostic', $assigned['scopes'][0]['endpoint']);
	}

	public function testHtmlAuthorizationFailureEscapesMessageForAccessibility(): void {
		$display = $this->diagnostic($this->runner(), adminAllowed: false);
		$text = $display->getOutput('html', true);
		self::assertStringContainsString('role="alert"', $text);
		self::assertSame(403, http_response_code());
	}
}
