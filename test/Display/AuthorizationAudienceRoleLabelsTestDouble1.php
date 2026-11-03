<?php declare(strict_types=1);

namespace ClientStack\Test\Display;

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
