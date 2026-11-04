<?php declare(strict_types=1);

namespace ClientStack\Test\GlobalChatbotLauncher;

use Base3\Settings\Api\ISettingsStore;
use ClientStack\GlobalChatbotLauncher\GlobalChatbotLauncherSettings;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class GlobalChatbotLauncherNormalizationCoverageTest extends TestCase {

	public function testNormalizationNeverExposesUnknownSettings(): void {
		$normalized = GlobalChatbotLauncherSettings::normalize(['untrusted_option' => 'secret']);
		self::assertSame(GlobalChatbotLauncherSettings::DEFAULTS, $normalized);
		self::assertArrayNotHasKey('untrusted_option', $normalized);
	}

	public function testOnlyStrictTruthValuesEnableAFlag(): void {
		foreach ([true, 1, '1', 'on'] as $trueValue) {
			$result = GlobalChatbotLauncherSettings::normalize(['mode_docked_enabled' => $trueValue]);
			self::assertTrue($result['mode_docked_enabled']);
		}
		foreach ([false, 0, '0', 'true', 'yes', 'ON', [], null, 2] as $falseValue) {
			$result = GlobalChatbotLauncherSettings::normalize(['mode_docked_enabled' => $falseValue]);
			self::assertFalse($result['mode_docked_enabled']);
		}
	}

	public function testExplicitOffOverridesEnabledDefaults(): void {
		$result = GlobalChatbotLauncherSettings::normalize([
			'mode_overlay_enabled' => false, 'mode_modal_enabled' => '0', 'mode_cover_enabled' => 'on'
		]);
		self::assertFalse($result['mode_overlay_enabled']);
		self::assertFalse($result['mode_modal_enabled']);
		self::assertTrue($result['mode_cover_enabled']);
	}

	public function testLoadingSettingsWhitelistsPersistedValues(): void {
		$store = $this->createMock(ISettingsStore::class);
		$store->expects(self::once())->method('get')
			->with(GlobalChatbotLauncherSettings::GROUP, GlobalChatbotLauncherSettings::NAME, [])
			->willReturn(['mode_docked_enabled' => 'on', 'token' => 'never-client-visible']);
		$result = GlobalChatbotLauncherSettings::get($store);
		self::assertTrue($result['mode_docked_enabled']);
		self::assertArrayNotHasKey('token', $result);
	}

	public function testSaveAcceptsFullscreenAsTheOnlyEnabledMode(): void {
		$values = array_fill_keys(array_keys(GlobalChatbotLauncherSettings::DEFAULTS), false);
		$values['mode_fullscreen_enabled'] = true;
		$store = $this->createMock(ISettingsStore::class);
		$store->expects(self::once())->method('set')->with(
			GlobalChatbotLauncherSettings::GROUP, GlobalChatbotLauncherSettings::NAME, $values
		);
		$store->expects(self::once())->method('save');
		self::assertSame($values, GlobalChatbotLauncherSettings::save($store, $values));
	}

	public function testSaveRejectsAllDisabledModesBeforePersisting(): void {
		$store = $this->createMock(ISettingsStore::class);
		$store->expects(self::never())->method('set');
		$store->expects(self::never())->method('save');
		$this->expectException(InvalidArgumentException::class);
		GlobalChatbotLauncherSettings::save($store, array_fill_keys(array_keys(GlobalChatbotLauncherSettings::DEFAULTS), false));
	}

	public function testSaveFiltersUntrustedKeysBeforeWriting(): void {
		$values = GlobalChatbotLauncherSettings::DEFAULTS;
		$values['untrusted'] = true;
		$store = $this->createMock(ISettingsStore::class);
		$store->expects(self::once())->method('set')->with(
			GlobalChatbotLauncherSettings::GROUP,
			GlobalChatbotLauncherSettings::NAME,
			GlobalChatbotLauncherSettings::DEFAULTS
		);
		$store->expects(self::once())->method('save');
		self::assertSame(GlobalChatbotLauncherSettings::DEFAULTS, GlobalChatbotLauncherSettings::save($store, $values));
	}
}
