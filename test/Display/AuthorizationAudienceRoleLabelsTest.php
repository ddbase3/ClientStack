<?php declare(strict_types=1);

namespace ClientStack\Test\Display;

use PHPUnit\Framework\TestCase;

final class AuthorizationAudienceRoleLabelsTest extends TestCase {

	private function render(string $display, array $rules, array $roles, array $criteria = [], array $translations = []): string {
		$view = new class($display, $rules, $roles, $criteria, $translations) {
			public array $_;
			private string $display;

			public function __construct(string $display, array $rules, array $roles, array $criteria, array $translations) {
				$this->display = $display;
				$this->_ = [
					'endpoint' => '/authorization', 'csrf' => 'test-token', 'translations' => $translations,
					'roleOptions' => $roles, 'criterionOptions' => $criteria, 'groups' => ['development'],
					'rows' => [[
						'name' => 'feature', 'label' => 'Feature', 'description' => 'Test',
						'permissions' => [], 'rules' => $rules
					]],
					'entries' => [[
						'name' => 'custom_group', 'source' => 'managed', 'description' => 'Test',
						'codeCriteria' => [], 'editable' => true, 'rows' => $rules
					]],
					'defaultGroup' => ''
				];
			}

			public function output(): string {
				ob_start();
				require __DIR__ . '/../../tpl/Display/' . $this->display . '.php';
				return (string)ob_get_clean();
			}
		};
		return $view->output();
	}

	public function testBothDisplaysPresentRoleNamesWhileRetainingOriginalValues(): void {
		foreach (['AuthorizationFeaturesDisplay', 'AuthorizationGroupsDisplay'] as $display) {
			$html = $this->render($display, [['kind' => 'role', 'value' => '2', 'config' => '{}']], [
				['id' => 2, 'label' => 'Administrator'],
				['id' => 4, 'label' => 'User']
			]);
			self::assertStringContainsString('data-audience-role required', $html);
			self::assertStringContainsString('value="2" selected>Administrator</option>', $html);
			self::assertStringContainsString('value="4">User</option>', $html);
			self::assertStringContainsString('data-audience-number disabled', $html);
			self::assertStringNotContainsString('<datalist', $html);
		}
	}

	public function testUnlistedSavedRoleIsNotDiscarded(): void {
		$html = $this->render('AuthorizationFeaturesDisplay', [['kind' => 'role', 'value' => '99', 'config' => '{}']], [
			['id' => 2, 'label' => 'Administrator']
		]);
		self::assertStringContainsString('value="99" selected', $html);
		self::assertStringContainsString('Role not in current catalog (ID 99)', $html);
	}

	public function testMissingHostCatalogRetainsExistingRoleIdInput(): void {
		$html = $this->render('AuthorizationGroupsDisplay', [['kind' => 'role', 'value' => '5', 'config' => '{}']], []);
		self::assertStringContainsString('data-audience-number required', $html);
		self::assertStringNotContainsString('class="base3-audience-field base3-audience-target base3-audience-role-target"', $html);
	}

	public function testDeleteGroupButtonOverridesHostButtonColors(): void {
		$styles = file_get_contents(__DIR__ . '/../../tpl/Display/AuthorizationAudienceStyles.php');
		self::assertIsString($styles);
		self::assertStringContainsString('background-color:#fff !important;', $styles);
		self::assertStringContainsString('color:#852525 !important;', $styles);
	}
	public function testAssignmentChoicesUseNeutralLabelsInAllLocalizedCatalogs(): void {
		foreach (glob(__DIR__ . '/../../lang/Display/*.ini') as $file) {
			$sections = parse_ini_file($file, true, INI_SCANNER_RAW);
			self::assertIsArray($sections);
			self::assertContains($sections['authorization_audience_editor']['assignment_role'], ['Role', 'Rolle']);
			self::assertContains($sections['authorization_audience_editor']['assignment_group'], ['Group', 'Gruppe']);
		}
	}

	public function testCriterionLabelsComeExclusivelyFromTheirSchemas(): void {
		$criteria = [
			['name' => 'foreignpluginaudience', 'schema' => ['title' => 'Eigentuemer-eigener Text', 'type' => 'object']],
			['name' => 'base3iliasrolecriterion', 'schema' => ['title' => 'ILIAS-Rolle', 'type' => 'object']],
			['name' => 'anothercriterion', 'schema' => ['type' => 'object']]
		];
		$html = $this->render('AuthorizationFeaturesDisplay', [
			['kind' => 'criterion', 'value' => 'base3iliasrolecriterion', 'config' => '{}']
		], [], $criteria, ['criterion_ilias_role' => 'Wrong cross-plugin translation']);
		self::assertStringContainsString('value="foreignpluginaudience">Eigentuemer-eigener Text</option>', $html);
		self::assertStringContainsString('value="base3iliasrolecriterion" selected>ILIAS-Rolle</option>', $html);
		self::assertStringContainsString('value="anothercriterion">anothercriterion</option>', $html);
		self::assertStringNotContainsString('Wrong cross-plugin translation', $html);
	}

	public function testNoCriterionSpecificTranslationKeysAreStoredInClientStack(): void {
		foreach (glob(__DIR__ . '/../../lang/Display/*.ini') as $file) {
			$sections = parse_ini_file($file, true, INI_SCANNER_RAW);
			self::assertIsArray($sections);
			$keys = array_keys($sections['authorization_audience_editor'] ?? []);
			foreach ($keys as $key) {
				self::assertFalse(str_starts_with($key, 'property_'), $file . ': ' . $key);
				self::assertFalse(str_starts_with($key, 'criterion_') && !in_array($key, ['criterion_config', 'criterion_placeholder'], true), $file . ': ' . $key);
			}
		}
		$editor = file_get_contents(__DIR__ . '/../../tpl/Display/AuthorizationAudienceEditor.php');
		$script = file_get_contents(__DIR__ . '/../../tpl/Display/AuthorizationAudienceScript.php');
		self::assertNotFalse($editor);
		self::assertNotFalse($script);
		self::assertStringNotContainsString('propertyLabels', $editor);
		self::assertStringNotContainsString('propertyLabels', $script);
		self::assertStringContainsString('definition.title', $script);
	}

	public function testNewGroupAndCriterionSelectionsShowExplicitPlaceholders(): void {
		$html = $this->render('AuthorizationFeaturesDisplay', [], [
			['id' => 2, 'label' => 'Administrator']
		], [
			['name' => 'qualitusemaildomaincriterion', 'schema' => ['title' => 'Qualitus email domain', 'type' => 'object', 'properties' => []]]
		]);
		self::assertStringContainsString('value="" disabled selected>Select a group</option>', $html);
		self::assertStringContainsString('value="" disabled selected>Select a criterion</option>', $html);
		self::assertStringContainsString('value="" disabled selected>Select a role</option>', $html);
	}

	public function testExistingGroupSelectionIsPreserved(): void {
		$html = $this->render('AuthorizationFeaturesDisplay', [
			['kind' => 'group', 'value' => 'development', 'config' => '{}']
		], []);
		$active = explode('<template data-audience-template>', $html, 2)[0];
		self::assertStringContainsString('data-audience-group required', $active);
		self::assertStringContainsString('value="development" selected>Development</option>', $active);
		self::assertStringContainsString('value="" disabled>Select a group</option>', $active);
	}

	public function testExistingCriterionRetainsNameAndUsesPluginSchemaTitle(): void {
		$criteria = [
			['name' => 'qualitusemaildomaincriterion', 'schema' => ['title' => 'Qualitus email domain', 'type' => 'object', 'properties' => []]],
			['name' => 'base3anonymouscriterion', 'schema' => ['type' => 'object']]
		];
		$html = $this->render('AuthorizationGroupsDisplay', [
			['kind' => 'criterion', 'value' => 'qualitusemaildomaincriterion', 'config' => '{}']
		], [], $criteria);
		$active = explode('<template data-audience-template>', $html, 2)[0];
		self::assertStringContainsString('data-audience-criterion required', $active);
		self::assertStringContainsString('value="qualitusemaildomaincriterion" selected>Qualitus email domain</option>', $active);
		self::assertStringContainsString('value="" disabled>Select a criterion</option>', $active);
	}

	public function testGroupAndCriterionPlaceholdersTranslateInGerman(): void {
		$translations = parse_ini_file(__DIR__ . '/../../lang/Display/de.ini', true, INI_SCANNER_RAW);
		self::assertIsArray($translations);
		$html = $this->render('AuthorizationFeaturesDisplay', [], [], [], $translations['authorization_audience_editor']);
		self::assertStringContainsString('Gruppe auswählen', $html);
		self::assertStringContainsString('Kriterium auswählen', $html);
	}

	public function testExpressionConfigRetainsExactSavedJson(): void {
		$expression = '{"any":[{"group":"development"}]}';
		$html = $this->render('AuthorizationGroupsDisplay', [
			['kind' => 'expression', 'value' => '', 'config' => $expression]
		], []);
		self::assertStringContainsString('data-kind="expression"', $html);
		self::assertStringContainsString(htmlspecialchars($expression, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), $html);
		self::assertStringContainsString('data-audience-number disabled', $html);
	}

}
