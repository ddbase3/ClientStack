<?php declare(strict_types=1);

namespace ClientStack\Test\Display;

use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\Authorization\Api\IAuthorizationService;
use Base3\Authorization\Dto\AuthorizationDecision;
use Base3\Diagnostics\Api\IDiagnosticCheck;
use Base3\Diagnostics\Api\IDiagnosticRunner;
use Base3\Diagnostics\Dto\DiagnosticCheckResult;
use Base3\Diagnostics\Feature\DiagnosticReportsFeature;
use Base3\Diagnostics\Scope\PrivacyDiagnosticScope;
use Base3\Diagnostics\Scope\DependencyDiagnosticScope;
use Base3\LinkTarget\Api\ILinkTargetService;
use Base3\Session\Api\ISession;
use Base3\Translation\Api\ITranslation;
use ClientStack\Display\DiagnosticScopeDisplay;
use PHPUnit\Framework\TestCase;

final class DiagnosticScopeDisplayTest extends TestCase {

	public function testHtmlIncludesSummaryFiltersAndSequentialExecution(): void {
		$runner = $this->createMock(IDiagnosticRunner::class);
		$runner->method('getScopes')->willReturn([new PrivacyDiagnosticScope()]);
		$runner->method('getChecks')->willReturn([new DiagnosticScopeTestCheck()]);
		$request = $this->createMock(IRequest::class);
		$request->method('get')->willReturn(PrivacyDiagnosticScope::getName());
		$view = new DiagnosticScopeTestMvcView();
		$display = $this->createDisplay($runner, $request, $view);
		$html = $display->getOutput();
		$this->assertStringContainsString('data-diagnostic-summary="failed"', $html);
		$this->assertStringContainsString('<select id=', $html);
		$this->assertStringContainsString('<option value="error"', $html);
		$this->assertStringContainsString('data-diagnostic-search', $html);
		$this->assertStringContainsString('data-diagnostic-export disabled', $html);
		$this->assertStringContainsString('navigator.clipboard.writeText', $html);
		$this->assertStringContainsString('schemaVersion: 1', $html);
		$this->assertStringContainsString('completedAt = new Date().toISOString()', $html);
		$this->assertStringContainsString('for(const card of cards)', $html);
		$this->assertStringContainsString('diagnostic_check: card.dataset.diagnosticCheck', $html);
	}

	public function testOverviewListsAllAreasWithoutRunningAnyChecks(): void {
		$runner = $this->createMock(IDiagnosticRunner::class);
		$runner->method('getScopes')->willReturn([new PrivacyDiagnosticScope(), new DependencyDiagnosticScope()]);
		$runner->method('getChecks')->willReturnCallback(static fn(string $scope): array => $scope === PrivacyDiagnosticScope::getName()
			? [new DiagnosticScopeTestCheck()]
			: []);
		$runner->expects($this->never())->method('run');
		$request = $this->createMock(IRequest::class);
		$request->method('get')->willReturn('');
		$html = $this->createDisplay($runner, $request)->getOutput();
		self::assertStringContainsString('data-diagnostic-launch="privacydiagnosticscope"', $html);
		self::assertStringContainsString('data-diagnostic-launch="dependencydiagnosticscope"', $html);
		self::assertStringContainsString('data-diagnostic-results', $html);
		self::assertStringContainsString('data-diagnostic-check-template', $html);
		self::assertStringContainsString('if(initialScope) runArea(initialScope);', $html);
		self::assertStringContainsString('selectedArea.endpoint', $html);
		self::assertStringContainsString('areaRow.appendChild(results);', $html);
		self::assertStringContainsString('setExpanded(card, status !== "passed");', $html);
		self::assertStringContainsString('data-diagnostic-toggle aria-expanded="false"', $html);
		self::assertStringContainsString('data-diagnostic-measures', $html);
		self::assertStringContainsString('data-diagnostic-extra hidden', $html);
		self::assertStringNotContainsString('data-diagnostic-rerun', $html);
		self::assertStringContainsString('schemaVersion: 1', $html);
	}

	public function testEachScopeHasOneInlineExpandableResultPanelAndCompactCheckRows(): void {
		$runner = $this->createMock(IDiagnosticRunner::class);
		$runner->method('getScopes')->willReturn([new PrivacyDiagnosticScope(), new DependencyDiagnosticScope()]);
		$runner->method('getChecks')->willReturn([new DiagnosticScopeTestCheck()]);
		$request = $this->createMock(IRequest::class);
		$request->method('get')->willReturn('');
		$html = $this->createDisplay($runner, $request)->getOutput();
		self::assertSame(1, substr_count($html, 'data-diagnostic-results aria-label='));
		self::assertSame(2, substr_count($html, 'class="base3-diagnostic-area-row"'));
		self::assertStringContainsString('areaRow.appendChild(results);', $html);
		self::assertStringContainsString('aria-expanded="false"', $html);
		self::assertStringContainsString('data-diagnostic-summary-entry="passed" hidden', $html);
		self::assertStringContainsString('card.querySelector("[data-diagnostic-extra]").hidden = !expanded;', $html);
		self::assertStringContainsString('card.querySelector("[data-diagnostic-toggle]").setAttribute("aria-expanded", String(expanded));', $html);
		$measures = strpos($html, 'class="base3-diagnostic-measures" data-diagnostic-measures hidden');
		$extra = strpos($html, 'class="base3-diagnostic-check-extra" data-diagnostic-extra hidden');
		self::assertIsInt($measures);
		self::assertIsInt($extra);
		self::assertLessThan($extra, $measures);
	}

	public function testJsonWithoutScopeCannotExecuteChecks(): void {
		$runner = $this->createMock(IDiagnosticRunner::class);
		$runner->method('getScopes')->willReturn([new PrivacyDiagnosticScope()]);
		$runner->expects($this->never())->method('run');
		$request = $this->createMock(IRequest::class);
		$request->method('get')->willReturn('');
		$payload = json_decode($this->createDisplay($runner, $request)->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertArrayHasKey('error', $payload);
	}

	public function testJsonRunsOnlyTheRequestedCheckAndTranslatesTheResult(): void {
		$runner = $this->createMock(IDiagnosticRunner::class);
		$runner->method('getScopes')->willReturn([new PrivacyDiagnosticScope()]);
		$runner->expects($this->once())->method('run')
			->with(PrivacyDiagnosticScope::getName(), DiagnosticScopeTestCheck::getName())
			->willReturn(new DiagnosticCheckResult(DiagnosticCheckResult::FAILED, 'failed', ['count' => 2], 'remediation', ['overdue' => 2]));
		$request = $this->createMock(IRequest::class);
		$request->method('get')->willReturn(PrivacyDiagnosticScope::getName());
		$request->method('server')->willReturn('POST');
		$request->method('post')->willReturnCallback(static fn(string $key, mixed $default = null) => match($key) {
			'diagnostic_csrf' => str_repeat('a', 64),
			'diagnostic_check' => DiagnosticScopeTestCheck::getName(),
			default => $default
		});
		$result = json_decode($this->createDisplay($runner, $request)->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		$this->assertSame(DiagnosticCheckResult::FAILED, $result['status']);
		$this->assertSame('failed', $result['message']);
		$this->assertSame([['label' => 'overdue', 'value' => 2]], $result['details']);
	}

	public function testWrongCsrfDoesNotExecuteAnyCheck(): void {
		$runner = $this->createMock(IDiagnosticRunner::class);
		$runner->method('getScopes')->willReturn([new PrivacyDiagnosticScope()]);
		$runner->expects($this->never())->method('run');
		$request = $this->createMock(IRequest::class);
		$request->method('get')->willReturn(PrivacyDiagnosticScope::getName());
		$request->method('server')->willReturn('POST');
		$request->method('post')->willReturn('invalid');
		$json = json_decode($this->createDisplay($runner, $request)->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		$this->assertArrayHasKey('error', $json);
	}

	public function testFeatureDenialPreventsAjaxExecution(): void {
		$runner = $this->createMock(IDiagnosticRunner::class);
		$runner->expects($this->never())->method('run');
		$request = $this->createMock(IRequest::class);
		$request->method('get')->willReturn(PrivacyDiagnosticScope::getName());
		$request->method('server')->willReturn('POST');
		$response = $this->createDisplay($runner, $request, null, false)->getOutput('json');
		self::assertArrayHasKey('error', json_decode($response, true, 512, JSON_THROW_ON_ERROR));
	}

	private function createDisplay(IDiagnosticRunner $runner, IRequest $request, ?IMvcView $view = null, bool $featureAllowed = true): DiagnosticScopeDisplay {
		$authorization = $this->createMock(IAuthorizationService::class);
		$authorization->method('getDefaultAuthority')->willReturn('ilias');
		$authorization->method('decide')->willReturnCallback(static function(AuthorizationRequest $request) use ($featureAllowed): AuthorizationDecision {
			if($request->permission->scope === 'feature'
				&& $request->permission->permission === DiagnosticReportsFeature::getName()) {
				return $featureAllowed ? AuthorizationDecision::allow() : AuthorizationDecision::deny();
			}
			return AuthorizationDecision::allow();
		});
		$session = $this->createMock(ISession::class);
		$session->method('started')->willReturn(true);
		$session->method('get')->willReturn(str_repeat('a', 64));
		$translation = $this->createMock(ITranslation::class);
		$translation->method('translate')->willReturnCallback(static fn(string $set, string $section, string $key, string $fallback = '', array $replacements = []) => $fallback === '' ? $key : strtr($fallback, array_combine(array_map(static fn(string $k): string => '{' . $k . '}', array_keys($replacements)), array_map('strval', array_values($replacements)))));
		$links = $this->createMock(ILinkTargetService::class);
		$links->method('getLink')->willReturn('/ajax/diagnostics');
		return new DiagnosticScopeDisplay($runner, $authorization, $translation, $request, $session, $view ?? new DiagnosticScopeTestMvcView(), $links);
	}

}

final class DiagnosticScopeTestCheck implements IDiagnosticCheck {

	public static function getName(): string { return 'diagnosticscopetestcheck'; }
	public function getScope(): string { return PrivacyDiagnosticScope::getName(); }
	public function run(): DiagnosticCheckResult { return new DiagnosticCheckResult(DiagnosticCheckResult::PASSED, 'passed'); }

}

final class DiagnosticScopeTestMvcView implements IMvcView {

	public array $_ = [];
	private string $path = '';
	private string $template = '';

	public function setPath(string $path = '.'): void { $this->path = $path; }
	public function assign(string $key, $value): void { $this->_[$key] = $value; }
	public function setTemplate(string $template = 'default'): void { $this->template = $template; }
	public function loadTemplate(): string {
		ob_start();
		include $this->path . '/tpl/' . $this->template;
		return (string)ob_get_clean();
	}
	public function loadBricks(string $set, string $language = ''): void {}
	public function getBricks(string $set): ?array { return null; }

}
