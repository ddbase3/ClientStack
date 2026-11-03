<?php declare(strict_types=1);

namespace ClientStack\SettingsTab;

use Base3\Authorization\Api\IAuthorizationService;
use Base3\Authorization\Dto\AuthorizationRequest;
use Base3\Diagnostics\Feature\DiagnosticReportsFeature;
use Base3\Translation\Api\ITranslation;
use Base3\Usermanager\Permission;
use Throwable;
use UiFoundation\Settings\AbstractSettingsTab;

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
