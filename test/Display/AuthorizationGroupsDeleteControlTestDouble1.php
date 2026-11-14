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

final class AuthorizationGroupsDeleteControlTestDouble1 {
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
		}
