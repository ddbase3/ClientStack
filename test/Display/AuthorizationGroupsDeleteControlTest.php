<?php declare(strict_types=1);

namespace ClientStack\Test\Display;

use PHPUnit\Framework\TestCase;

final class AuthorizationGroupsDeleteControlTest extends TestCase {

	public function testOnlyManagedGroupHasDeleteButtonWithSeparateCsrfProtectedForm(): void {
		$view = new class {
			public array $_ = [
				'entries' => [
					[
						'name' => 'temporary_group',
						'description' => 'Temporary managed group',
						'source' => 'managed',
						'editable' => true,
						'rows' => [],
						'codeCriteria' => [],
						'expression' => []
					],
					[
						'name' => 'development',
						'description' => 'Code-owned group',
						'source' => 'code',
						'editable' => true,
						'rows' => [],
						'codeCriteria' => ['criterion' => 'fixed'],
						'expression' => []
					]
				],
				'defaultGroup' => '',
				'criterionOptions' => [],
				'roleOptions' => [],
				'translations' => [
					'remove_group' => 'Delete group',
					'remove_confirm' => 'Delete BASE3 group %s? This cannot be undone.'
				],
				'csrf' => 'test-csrf',
				'endpoint' => '/admin/groups'
			];

			public function render(): string {
				ob_start();
				require __DIR__ . '/../../tpl/Display/AuthorizationGroupsDisplay.php';
				return (string)ob_get_clean();
			}
		};

		$html = $view->render();
		self::assertSame(1, substr_count($html, 'data-confirm-remove'));
		self::assertStringContainsString('form="base3-remove-group-1"', $html);
		self::assertStringContainsString('id="base3-remove-group-1"', $html);
		self::assertStringContainsString('name="base3_group_name" value="temporary_group"', $html);
		self::assertStringContainsString('name="base3_group_action" value="remove"', $html);
		self::assertStringContainsString('name="base3_group_csrf" value="test-csrf"', $html);
		self::assertStringNotContainsString('base3-remove-group-2', $html);
		self::assertStringContainsString('Delete BASE3 group temporary_group? This cannot be undone.', $html);
	}
}
