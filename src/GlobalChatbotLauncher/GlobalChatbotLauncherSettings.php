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

namespace Base3\ClientStack\GlobalChatbotLauncher;

use Base3\CoreFoundation\Settings\Api\ISettingsStore;
use InvalidArgumentException;

/**
 * Shared settings for the global launcher presentation, not chatbot content.
 * The global chatbot identity and backend remain owned by the host project.
 */
final class GlobalChatbotLauncherSettings {

	public const GROUP = 'global-chatbot-launcher';
	public const NAME = 'default';

	public const DEFAULTS = [
		'mode_overlay_enabled' => true,
		'mode_modal_enabled' => true,
		'mode_docked_enabled' => false,
		'mode_cover_enabled' => false,
		'mode_fullscreen_enabled' => false,
		'resizable_overlay' => false,
		'resizable_modal' => false,
		'resizable_docked' => false,
		'remember_last_mode' => false,
		'reopen_overlay' => false,
		'reopen_docked' => false
	];

	/**
	 * Returns whitelisted, boolean presentation settings with stable defaults.
	 * Unknown persisted keys are intentionally not exposed to the client.
	 *
	 * @param ISettingsStore $store Canonical BASE3 settings store.
	 * @return array<string,bool> Complete presentation options.
	 */
	public static function get(ISettingsStore $store): array {
		return self::normalize($store->get(self::GROUP, self::NAME, []));
	}

	/**
	 * Validates and persists all configuration flags as a single dataset.
	 * At least one window mode must remain available.
	 *
	 * @param ISettingsStore $store Canonical BASE3 settings store.
	 * @param array<string,mixed> $values Form or migration values.
	 * @return array<string,bool> Saved configuration.
	 * @throws InvalidArgumentException When all five modes are disabled.
	 */
	public static function save(ISettingsStore $store, array $values): array {
		$configuration = self::normalize($values);
		$hasMode = false;
		foreach (['overlay', 'modal', 'docked', 'cover', 'fullscreen'] as $mode) {
			if ($configuration['mode_' . $mode . '_enabled']) {
				$hasMode = true;
				break;
			}
		}
		if (!$hasMode) {
			throw new InvalidArgumentException('At least one launcher window mode must be enabled.');
		}
		$store->set(self::GROUP, self::NAME, $configuration);
		$store->save();
		return $configuration;
	}

	/**
	 * @param array<string,mixed> $values User supplied or stored flags.
	 * @return array<string,bool> Whitelisted flags with defaults for missing keys.
	 */
	public static function normalize(array $values): array {
		$result = self::DEFAULTS;
		foreach (self::DEFAULTS as $key => $default) {
			if (!array_key_exists($key, $values)) continue;
			$value = $values[$key];
			$result[$key] = $value === true || $value === 1 || $value === '1' || $value === 'on';
		}
		return $result;
	}
}
