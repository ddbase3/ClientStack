<?php declare(strict_types=1);

namespace ClientStack\Test\Display;

use PHPUnit\Framework\TestCase;

final class AuthorizationGroupsDeleteControlTest extends TestCase {

	public function testOnlyManagedGroupHasDeleteButtonWithSeparateCsrfProtectedForm(): void {
		$view = new AuthorizationGroupsDeleteControlTestDouble1();

		$html = $view->render();
		self::assertSame(1, preg_match_all('/<button\b[^>]*\bdata-confirm-remove\b/s', $html));
		self::assertStringContainsString('form="base3-remove-group-1"', $html);
		self::assertStringContainsString('id="base3-remove-group-1"', $html);
		self::assertStringContainsString('name="base3_group_name" value="temporary_group"', $html);
		self::assertStringContainsString('name="base3_group_action" value="remove"', $html);
		self::assertStringContainsString('name="base3_group_csrf" value="test-csrf"', $html);
		self::assertStringNotContainsString('base3-remove-group-2', $html);
		self::assertStringContainsString('Delete BASE3 group temporary_group? This cannot be undone.', $html);
	}
}

require_once __DIR__ . '/AuthorizationGroupsDeleteControlTestDouble1.php';
