<?php declare(strict_types=1);

namespace ClientStack\SettingsTab;

use UiFoundation\Settings\AbstractSettingsTab;

final class Base3SettingsTab extends AbstractSettingsTab {

	public function getOrder(): int {
		return 10;
	}
}
