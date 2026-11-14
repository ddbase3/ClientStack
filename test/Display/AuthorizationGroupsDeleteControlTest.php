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

namespace Base3\ClientStack\Test\Display;

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
