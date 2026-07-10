<?php declare(strict_types=1);

namespace ClientStack\Display;

use Base3\Api\IDisplay;
use Base3\Api\IMvcView;
use Base3\Usermanager\Api\IUsermanager;
use Base3\Usermanager\Permission;
use Base3\Usermanager\Role;
use Throwable;

final class UsermanagerDebugDisplay implements IDisplay {

	public function __construct(
		private readonly IMvcView $view,
		private readonly IUsermanager $usermanager
	) {}

	public static function getName(): string {
		return 'usermanagerdebugdisplay';
	}

	public function setData($data) {
		// no-op
	}

	public function getHelp(): string {
		return 'Shows the current BASE3 usermanager user, groups, roles and permissions.';
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		$userCall = $this->call('getUser', fn() => $this->usermanager->getUser());
		$groupsCall = $this->call('getGroups', fn() => $this->usermanager->getGroups());
		$rolesCall = $this->call('getRoles', fn() => $this->usermanager->getRoles());
		$permissionsCall = $this->call('getPermissions', fn() => $this->usermanager->getPermissions());

		$user = $this->normalizeItem($userCall['value']);
		$groups = $this->normalizeList($groupsCall['value']);
		$roles = $this->normalizeList($rolesCall['value']);
		$permissions = $this->normalizeList($permissionsCall['value']);
		$checks = $this->getChecks();

		$this->view->setPath(\DIR_PLUGIN . 'ClientStack');
		$this->view->setTemplate('Display/UsermanagerDebugDisplay.php');

		$this->view->assign('generatedAt', date('c'));
		$this->view->assign('usermanagerClass', get_class($this->usermanager));
		$this->view->assign('user', $user);
		$this->view->assign('groups', $groups);
		$this->view->assign('roles', $roles);
		$this->view->assign('permissions', $permissions);
		$this->view->assign('permissionScopes', $this->groupPermissionsByScope($permissions));
		$this->view->assign('checks', $checks);
		$this->view->assign('calls', [$userCall, $groupsCall, $rolesCall, $permissionsCall]);
		$this->view->assign('summary', $this->getSummary($userCall, $groups, $roles, $permissions, $checks));
		$this->view->assign('formatValue', fn($value) => $this->formatValue($value));
		$this->view->assign('formatList', fn($value) => $this->formatList($value));

		return $this->view->loadTemplate();
	}

	private function getChecks(): array {
		return [
			$this->checkRole('admin'),
			$this->checkRole('member'),
			$this->checkRole('visit'),
			$this->checkPermission('system', 'admin'),
			$this->checkPermission('entry', 'admin'),
			$this->checkPermission('entry', 'create'),
			$this->checkPermission('user', 'manage'),
			$this->checkPermission('group', 'manage'),
			$this->checkPermission('role', 'manage'),
		];
	}

	private function checkRole(string $role): array {
		$call = $this->call('hasRole(' . $role . ')', fn() => $this->usermanager->hasRole(Role::named($role)));
		$allowed = $call['ok'] && $call['value'] === true;

		return [
			'label' => 'Role: ' . $role,
			'source' => 'IUsermanager::hasRole(Role::named(...))',
			'status' => $call['ok'] ? ($allowed ? 'ok' : 'info') : 'error',
			'result' => $call['ok'] ? ($allowed ? 'yes' : 'no') : 'error',
			'details' => $call['ok'] ? '' : $call['error'],
		];
	}

	private function checkPermission(string $scope, string $permission): array {
		$call = $this->call('can(' . $scope . '/' . $permission . ')', fn() => $this->usermanager->can(Permission::for($scope, $permission)));
		$allowed = $call['ok'] && $call['value'] === true;

		return [
			'label' => 'Permission: ' . $scope . '/' . $permission,
			'source' => 'IUsermanager::can(Permission::for(...))',
			'status' => $call['ok'] ? ($allowed ? 'ok' : 'info') : 'error',
			'result' => $call['ok'] ? ($allowed ? 'yes' : 'no') : 'error',
			'details' => $call['ok'] ? '' : $call['error'],
		];
	}

	private function call(string $label, callable $callback): array {
		try {
			return [
				'label' => $label,
				'ok' => true,
				'status' => 'ok',
				'value' => $callback(),
				'error' => '',
			];
		} catch (Throwable $exception) {
			return [
				'label' => $label,
				'ok' => false,
				'status' => 'error',
				'value' => null,
				'error' => get_class($exception) . ': ' . $exception->getMessage(),
			];
		}
	}

	private function normalizeList($items): array {
		if ($items === null) {
			return [];
		}

		if (!is_array($items)) {
			return [$this->normalizeItem($items)];
		}

		if (!$this->isSequentialArray($items)) {
			return [$this->normalizeItem($items)];
		}

		$result = [];
		foreach ($items as $item) {
			$result[] = $this->normalizeItem($item);
		}

		return $result;
	}

	private function normalizeItem($item): array {
		if ($item === null) {
			return [];
		}

		if (is_object($item)) {
			$data = get_object_vars($item);
			$data['__class'] = get_class($item);
			return $this->normalizeArray($data);
		}

		if (is_array($item)) {
			return $this->normalizeArray($item);
		}

		return [
			'value' => $item,
		];
	}

	private function normalizeArray(array $data): array {
		$result = [];

		foreach ($data as $key => $value) {
			$result[(string)$key] = $this->normalizeValue($value);
		}

		return $result;
	}

	private function normalizeValue($value) {
		if (is_object($value)) {
			$data = get_object_vars($value);
			$data['__class'] = get_class($value);
			return $this->normalizeArray($data);
		}

		if (is_array($value)) {
			if ($this->isSequentialArray($value)) {
				$list = [];
				foreach ($value as $entry) {
					$list[] = $this->normalizeValue($entry);
				}
				return $list;
			}

			return $this->normalizeArray($value);
		}

		return $value;
	}

	private function isSequentialArray(array $array): bool {
		$index = 0;
		foreach (array_keys($array) as $key) {
			if ($key !== $index) {
				return false;
			}
			$index++;
		}

		return true;
	}

	private function groupPermissionsByScope(array $permissions): array {
		$groups = [];

		foreach ($permissions as $permission) {
			$scope = (string)($permission['scope'] ?? '');
			if ($scope === '') {
				$scope = 'unknown';
			}

			if (!array_key_exists($scope, $groups)) {
				$groups[$scope] = [];
			}

			$groups[$scope][] = $permission;
		}

		ksort($groups);

		foreach ($groups as $scope => $items) {
			usort($items, fn(array $a, array $b) => strcmp((string)($a['permission'] ?? ''), (string)($b['permission'] ?? '')));
			$groups[$scope] = $items;
		}

		return $groups;
	}

	private function getSummary(array $userCall, array $groups, array $roles, array $permissions, array $checks): array {
		$errors = $userCall['ok'] ? 0 : 1;
		foreach ($checks as $check) {
			if ((string)$check['status'] === 'error') {
				$errors++;
			}
		}

		return [
			'status' => $errors > 0 ? 'error' : 'ok',
			'user_status' => $userCall['ok'] ? 'ok' : 'error',
			'user_id' => (string)($this->normalizeItem($userCall['value'])['id'] ?? ''),
			'userid' => (string)($this->normalizeItem($userCall['value'])['userid'] ?? ''),
			'role' => (string)($this->normalizeItem($userCall['value'])['role'] ?? ''),
			'groups' => count($groups),
			'roles' => count($roles),
			'permissions' => count($permissions),
			'checks' => count($checks),
			'errors' => $errors,
		];
	}

	private function formatList($value): string {
		if ($value === null || $value === '') {
			return '';
		}

		if (!is_array($value)) {
			return (string)$value;
		}

		$parts = [];
		foreach ($value as $item) {
			if (is_array($item)) {
				$name = (string)($item['name'] ?? $item['permission'] ?? $item['scope'] ?? $item['id'] ?? '');
				if ($name !== '') {
					$parts[] = $name;
				}
				continue;
			}

			if (is_scalar($item)) {
				$parts[] = (string)$item;
			}
		}

		return implode(', ', $parts);
	}

	private function formatValue($value): string {
		if ($value === null) {
			return '';
		}

		if (is_bool($value)) {
			return $value ? 'true' : 'false';
		}

		if (is_scalar($value)) {
			return (string)$value;
		}

		$json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		return $json === false ? '' : $json;
	}
}
