#!/usr/bin/env php
<?php declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Base3Framework/src/Settings/Api/ISettingsStore.php';
require_once dirname(__DIR__) . '/src/GlobalChatbotLauncher/GlobalChatbotLauncherSettings.php';

use Base3\Settings\Api\ISettingsStore;
use ClientStack\GlobalChatbotLauncher\GlobalChatbotLauncherSettings as Settings;

final class TestSettingsStore implements ISettingsStore {
	private array $groups = [];
	public bool $saved = false;
	public function get(string $group, string $name, array $default = []): array {
		return $this->groups[$group][$name] ?? $default;
	}
	public function set(string $group, string $name, array $settings): void {
		$this->groups[$group][$name] = $settings;
	}
	public function has(string $group, string $name): bool { return isset($this->groups[$group][$name]); }
	public function remove(string $group, string $name): void { unset($this->groups[$group][$name]); }
	public function getGroup(string $group): array { return $this->groups[$group] ?? []; }
	public function save(): void { $this->saved = true; }
	public function reload(): void {}
}

$store = new TestSettingsStore();
$defaults = Settings::get($store);
if ($defaults !== Settings::DEFAULTS) throw new RuntimeException('Incorrect defaults');
$values = ['mode_overlay_enabled' => '0', 'mode_modal_enabled' => '1', 'mode_docked_enabled' => 'on', 'unknown' => 'secret'];
$actual = Settings::save($store, $values);
if (!$store->saved || !Settings::get($store)['mode_docked_enabled'] || array_key_exists('unknown', $actual)) {
	throw new RuntimeException('Configuration persistence failed');
}
$empty = array_fill_keys(array_keys(Settings::DEFAULTS), false);
try {
	Settings::save($store, $empty);
	throw new RuntimeException('Accepted configuration without any mode');
} catch (InvalidArgumentException $e) {
	// Expected.
}
if (!Settings::get($store)['mode_modal_enabled']) throw new RuntimeException('Invalid configuration replaced the saved one');
echo "GlobalChatbotLauncherSettings smoke test passed.\n";
