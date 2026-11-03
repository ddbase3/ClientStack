<?php declare(strict_types=1);

namespace ClientStack\Test;

use Base3\Settings\Api\ISettingsStore;
use ClientStack\GlobalChatbotLauncher\GlobalChatbotLauncherSettings;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class GlobalChatbotLauncherSettingsTest extends TestCase {

	public function testDefaultsPersistenceAndInvalidUpdates(): void {
		$store = new class implements ISettingsStore {
			private array $groups = [];
			public bool $saved = false;

			public function get(string $group, string $name, array $default = []): array {
				return $this->groups[$group][$name] ?? $default;
			}

			public function set(string $group, string $name, array $settings): void {
				$this->groups[$group][$name] = $settings;
			}

			public function has(string $group, string $name): bool {
				return isset($this->groups[$group][$name]);
			}

			public function remove(string $group, string $name): void {
				unset($this->groups[$group][$name]);
			}

			public function getGroup(string $group): array {
				return $this->groups[$group] ?? [];
			}

			public function save(): void {
				$this->saved = true;
			}

			public function reload(): void {}
		};

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
