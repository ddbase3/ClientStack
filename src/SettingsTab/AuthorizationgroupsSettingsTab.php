<?php declare(strict_types=1);

namespace ClientStack\SettingsTab;

use UiFoundation\Settings\AbstractFeatureSettingsTab;

final class AuthorizationgroupsSettingsTab extends AbstractFeatureSettingsTab {

	public function getParentTabName(): ?string {
		return 'base3settingstab';
	}

	public function getDisplayName(): ?string {
		return 'authorizationgroupsdisplay';
	}

	public function getOrder(): int {
		return 80;
	}
}
