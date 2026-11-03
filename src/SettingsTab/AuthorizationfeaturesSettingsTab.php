<?php declare(strict_types=1);

namespace ClientStack\SettingsTab;

use UiFoundation\Settings\AbstractFeatureSettingsTab;

final class AuthorizationfeaturesSettingsTab extends AbstractFeatureSettingsTab {

	public function getParentTabName(): ?string {
		return 'base3settingstab';
	}

	public function getDisplayName(): ?string {
		return 'authorizationfeaturesdisplay';
	}

	public function getOrder(): int {
		return 90;
	}

	public function getDefaultAudience(): array {
		return ['group' => 'development'];
	}
}
