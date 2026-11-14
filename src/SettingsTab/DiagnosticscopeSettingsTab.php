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

namespace Base3\ClientStack\SettingsTab;

use Base3\CoreFoundation\Authorization\Api\IAuthorizationService;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest;
use Base3\Core\Diagnostics\Feature\DiagnosticReportsFeature;
use Base3\CoreFoundation\Translation\Api\ITranslation;
use Base3\CoreFoundation\Usermanager\Permission;
use Throwable;
use Base3\UiFoundation\Settings\AbstractSettingsTab;

/** Reuses the Framework diagnostic feature; no competing feature definition. */
final class DiagnosticscopeSettingsTab extends AbstractSettingsTab {

	public function __construct(
		ITranslation $translation,
		private readonly IAuthorizationService $authorization
	) {
		parent::__construct($translation);
	}

	public function getParentTabName(): ?string {
		return 'governancesettingstab';
	}

	public function getDisplayName(): ?string {
		return 'diagnosticscopedisplay';
	}

	public function getOrder(): int {
		return 10;
	}

	public function isVisible(): bool {
		try {
			$authority = $this->authorization->getDefaultAuthority();
			return $this->authorization->decide(new AuthorizationRequest(
				$authority, Permission::for('system', 'admin')
			))->isAllowed() && $this->authorization->decide(new AuthorizationRequest(
				$authority, Permission::for('feature', DiagnosticReportsFeature::getName())
			))->isAllowed();
		} catch (Throwable) {
			return false;
		}
	}
}
