<?php declare(strict_types=1);

namespace ClientStack\SettingsTab;

use UiFoundation\Settings\AbstractSettingsTab;

/** Shared category for independently installed BASE3 games. */
final class GamesSettingsTab extends AbstractSettingsTab {

	public function getOrder(): int {
		return 120;
	}
}
