<?php declare(strict_types=1);

namespace ClientStack\Test\Display;

use PHPUnit\Framework\TestCase;

final class AuthorizationAudienceLayoutTest extends TestCase {

	public function testInactiveFieldsRemainHiddenWithinGridLayout(): void {
		$styles = file_get_contents(__DIR__ . '/../../tpl/Display/AuthorizationAudienceStyles.php');
		self::assertIsString($styles);
		self::assertStringContainsString('.base3-authorization-editor [hidden] {display:none !important;}', $styles);
		self::assertStringContainsString('display:grid;', $styles);
		self::assertStringContainsString('grid-template-areas:', $styles);
		self::assertStringContainsString('grid-area:config;', $styles);
	}

	public function testBothDisplaysUseTheSharedAudienceEditor(): void {
		foreach (['AuthorizationGroupsDisplay.php', 'AuthorizationFeaturesDisplay.php'] as $file) {
			$template = file_get_contents(__DIR__ . '/../../tpl/Display/' . $file);
			self::assertIsString($template);
			self::assertStringContainsString("require __DIR__ . '/AuthorizationAudienceEditor.php'", $template);
			self::assertStringContainsString("require __DIR__ . '/AuthorizationAudienceStyles.php'", $template);
		}
	}

	public function testBothEditorsRetainTheirOriginalFormFields(): void {
		$row = file_get_contents(__DIR__ . '/../../tpl/Display/AuthorizationAudienceRow.php');
		self::assertIsString($row);
		foreach (['[kind]', '[value]', '[config]'] as $field) {
			self::assertStringContainsString($field, $row);
		}
		self::assertStringContainsString('base3-audience-target', $row);
		self::assertStringContainsString('data-audience-json-label', $row);
	}

	public function testChangingKindRemovesOnlyOldCriterionControls(): void {
		$script = file_get_contents(__DIR__ . '/../../tpl/Display/AuthorizationAudienceScript.php');
		self::assertIsString($script);
		self::assertStringContainsString("row.querySelector('[data-audience-properties]').replaceChildren();", $script);
		self::assertStringContainsString("row.querySelector('[data-audience-schema]').textContent = '';", $script);
	}
	public function testAdvancedExpressionUsesTargetGridAreaOnEveryViewport(): void {
		$row = file_get_contents(__DIR__ . '/../../tpl/Display/AuthorizationAudienceRow.php');
		$styles = file_get_contents(__DIR__ . '/../../tpl/Display/AuthorizationAudienceStyles.php');
		$script = file_get_contents(__DIR__ . '/../../tpl/Display/AuthorizationAudienceScript.php');
		self::assertIsString($row);
		self::assertIsString($styles);
		self::assertIsString($script);
		self::assertStringContainsString('data-kind="<?php echo $esc($kind); ?>"', $row);
		self::assertStringContainsString('.base3-audience-row[data-kind="expression"] > .base3-audience-config {grid-area:target;}', $styles);
		self::assertStringContainsString('grid-template-areas:"kind target remove";', $styles);
		self::assertStringContainsString('grid-template-areas:"kind target" ". remove";', $styles);
		self::assertStringContainsString('grid-template-areas:"kind" "target" "remove";', $styles);
		self::assertStringContainsString('row.dataset.kind = kind;', $script);
	}

}

