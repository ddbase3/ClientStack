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

use Base3\UiFoundation\Settings\AbstractFeatureSettingsTab;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest;
use Base3\CoreFoundation\Usermanager\Permission;
use Throwable;

final class GlobalChatbotLauncherSettingsTab extends AbstractFeatureSettingsTab {

	public function getParentTabName(): ?string {
		return 'chatbotsettingstab';
	}

	public function getDisplayName(): ?string {
		return 'globalchatbotlauncherconfigdisplay';
	}

	public function getDisplayData(): mixed {
		return ['title' => $this->getLabel(), 'description' => $this->getDescription()];
	}

	public function isVisible(): bool {
		if (!parent::isVisible()) return false;
		try {
			return $this->authorization->decide(new AuthorizationRequest(
				$this->authorization->getDefaultAuthority(), Permission::for('system', 'admin')
			))->isAllowed();
		} catch (Throwable) {
			return false;
		}
	}

	public function getOrder(): int {
		return 20;
	}
}
