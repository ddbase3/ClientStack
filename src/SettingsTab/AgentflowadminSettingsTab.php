<?php declare(strict_types=1);

namespace ClientStack\SettingsTab;

use UiFoundation\Settings\AbstractFeatureSettingsTab;

final class AgentflowadminSettingsTab extends AbstractFeatureSettingsTab {

	public function getParentTabName(): ?string {
		return 'base3settingstab';
	}

	public function getDisplayName(): ?string {
		return 'agentflowadmindisplay';
	}

	public function getOrder(): int {
		return 110;
	}
}
