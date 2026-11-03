<?php declare(strict_types=1);

namespace ClientStack\SettingsTab;

use UiFoundation\Settings\AbstractSettingsTab;

final class GovernanceSettingsTab extends AbstractSettingsTab {

	public function getOrder(): int {
		return 20;
	}
}
