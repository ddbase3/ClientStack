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

namespace Base3\ClientStack\Test;

use Base3\CoreFoundation\Settings\Api\ISettingsStore;
use Base3\ClientStack\GlobalChatbotLauncher\GlobalChatbotLauncherSettings;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class GlobalChatbotLauncherSettingsTest extends TestCase {

	public function testDefaultsPersistenceAndInvalidUpdates(): void {
		$store = new GlobalChatbotLauncherSettingsTestDouble1();

		self::assertSame(GlobalChatbotLauncherSettings::DEFAULTS, GlobalChatbotLauncherSettings::get($store));

		$values = [
			'mode_overlay_enabled' => '0',
			'mode_modal_enabled' => '1',
			'mode_docked_enabled' => 'on',
			'unknown' => 'secret'
		];
		$actual = GlobalChatbotLauncherSettings::save($store, $values);
		self::assertTrue($store->saved);
		self::assertTrue(GlobalChatbotLauncherSettings::get($store)['mode_docked_enabled']);
		self::assertArrayNotHasKey('unknown', $actual);

		$invalid = array_fill_keys(array_keys(GlobalChatbotLauncherSettings::DEFAULTS), false);
		try {
			GlobalChatbotLauncherSettings::save($store, $invalid);
			self::fail('Configuration without a window mode was accepted.');
		} catch (InvalidArgumentException) {
			self::assertTrue(GlobalChatbotLauncherSettings::get($store)['mode_modal_enabled']);
		}
	}
}

require_once __DIR__ . '/GlobalChatbotLauncherSettingsTestDouble1.php';
