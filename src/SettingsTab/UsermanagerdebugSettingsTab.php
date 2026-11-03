<?php declare(strict_types=1);

namespace ClientStack\SettingsTab;

use UiFoundation\Settings\AbstractFeatureSettingsTab;

final class UsermanagerdebugSettingsTab extends AbstractFeatureSettingsTab {

	public function getParentTabName(): ?string {
		return 'base3settingstab';
	}

	public function getDisplayName(): ?string {
		return 'usermanagerdebugdisplay';
	}

	public function getOrder(): int {
		return 40;
	}
}
