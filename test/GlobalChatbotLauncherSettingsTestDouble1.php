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

final class GlobalChatbotLauncherSettingsTestDouble1 implements ISettingsStore  {
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
		}
