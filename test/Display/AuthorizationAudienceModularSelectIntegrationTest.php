<?php declare(strict_types=1);

namespace ClientStack\Test\Display;

use PHPUnit\Framework\TestCase;

/** Structural regression checks; safe to autoload during ILIAS build. */
final class AuthorizationAudienceModularSelectIntegrationTest extends TestCase {

	public function testBothDisplaysUseOneSharedEndpointAndAssetResolver(): void {
		foreach (['AuthorizationGroupsDisplay.php', 'AuthorizationFeaturesDisplay.php'] as $file) {
			$source = file_get_contents(__DIR__ . '/../../src/Display/' . $file);
			self::assertIsString($source);
			self::assertStringContainsString('AuthorizationAudienceUserSearchDisplay::getName()', $source);
			self::assertStringContainsString("'out' => 'json'", $source);
			self::assertStringContainsString('plugin/ClientStack/assets/modularselect/index.js', $source);
			self::assertStringContainsString('plugin/ClientStack/assets/modularselect/styles/modularselect.css', $source);
		}
	}

	public function testExistingAudienceFieldsAndNumericFallbackRemainUnchanged(): void {
		$row = file_get_contents(__DIR__ . '/../../tpl/Display/AuthorizationAudienceRow.php');
		self::assertIsString($row);
		self::assertStringContainsString('data-audience-number', $row);
		self::assertStringContainsString('data-audience-user-wrap hidden', $row);
		self::assertStringContainsString('data-audience-user-select', $row);
		self::assertStringContainsString('[value]', $row);
		self::assertStringContainsString('[kind]', $row);
		self::assertStringContainsString('[config]', $row);
	}

	public function testClientScriptHasAuthorizedPostSourceAndCleanup(): void {
		$script = file_get_contents(__DIR__ . '/../../tpl/Display/AuthorizationAudienceScript.php');
		self::assertIsString($script);
		self::assertStringContainsString("method: 'POST'", $script);
		self::assertStringContainsString("credentials: 'same-origin'", $script);
		self::assertStringContainsString("action,", $script);
		self::assertStringContainsString("import(url)", $script);
		self::assertStringContainsString("window.BASE3AudienceEditor", $script);
		self::assertStringContainsString("base3:tab-control:unload", $script);
		self::assertStringContainsString("state.instance.destroy()", $script);
		self::assertStringNotContainsString('getAllUsers(', $script);
	}

	public function testNativeEditorCssDoesNotOverrideTheEmbeddedSelectInput(): void {
		$styles = file_get_contents(__DIR__ . '/../../tpl/Display/AuthorizationAudienceStyles.php');
		self::assertIsString($styles);
		self::assertStringContainsString('.base3-audience-field > input:not([type="checkbox"])', $styles);
		self::assertStringNotContainsString('.base3-audience-field input:not([type="checkbox"])', $styles);
	}
}
