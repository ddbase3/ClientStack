<?php declare(strict_types=1);

namespace ClientStack\SettingsTab;

use UiFoundation\Settings\AbstractFeatureSettingsTab;
use Base3\Authorization\Dto\AuthorizationRequest;
use Base3\Usermanager\Permission;
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
