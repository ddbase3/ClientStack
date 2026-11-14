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
use Base3\CoreFoundation\Diagnostics\Api\IDiagnosticCheck;
use Base3\CoreFoundation\Diagnostics\Api\IDiagnosticRunner;
use Base3\CoreFoundation\Diagnostics\Dto\DiagnosticCheckResult;
use Base3\Core\Diagnostics\Feature\DiagnosticReportsFeature;
use Base3\Core\Diagnostics\Scope\PrivacyDiagnosticScope;
use Base3\Core\Diagnostics\Scope\DependencyDiagnosticScope;
use Base3\CoreFoundation\LinkTarget\Api\ILinkTargetService;
use Base3\CoreFoundation\Session\Api\ISession;
use Base3\CoreFoundation\Translation\Api\ITranslation;
use Base3\ClientStack\Display\DiagnosticScopeDisplay;
use PHPUnit\Framework\TestCase;

final class DiagnosticScopeTestCheck implements IDiagnosticCheck {

	public static function getName(): string { return 'diagnosticscopetestcheck'; }
	public function getScope(): string { return PrivacyDiagnosticScope::getName(); }
	public function run(): DiagnosticCheckResult { return new DiagnosticCheckResult(DiagnosticCheckResult::PASSED, 'passed'); }

}
