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

final class AuthorizationAudienceRoleLabelsTestDouble1 {
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
		}
