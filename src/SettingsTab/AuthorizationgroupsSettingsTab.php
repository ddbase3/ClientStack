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
