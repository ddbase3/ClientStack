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

final class DiagnosticScopeTestCheck implements IDiagnosticCheck {

	public static function getName(): string { return 'diagnosticscopetestcheck'; }
	public function getScope(): string { return PrivacyDiagnosticScope::getName(); }
	public function run(): DiagnosticCheckResult { return new DiagnosticCheckResult(DiagnosticCheckResult::PASSED, 'passed'); }

}
