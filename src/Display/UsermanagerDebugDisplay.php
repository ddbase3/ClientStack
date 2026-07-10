<?php declare(strict_types=1);

namespace ClientStack\Display;

use Base3\Api\IDisplay;
use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\LinkTarget\Api\ILinkTargetService;
use Base3\Usermanager\Api\IUsermanager;
use Base3\Usermanager\Permission;
use Base3\Usermanager\Role;
use Throwable;

final class UsermanagerDebugDisplay implements IDisplay {

	private const ACTION_PERMISSION_PROBE = 'permission_probe';

	private const PARAM_ACTION = 'action';
	private const PARAM_SCOPE = 'base3_um_scope';
	private const PARAM_TARGET = 'base3_um_target';
	private const PARAM_OPERATION = 'base3_um_operation';
	private const PARAM_MODE = 'base3_um_mode';

	private const DEFAULT_SCOPE = 'ilias';
	private const DEFAULT_OPERATION = 'read';

	private const DEFAULT_ILIAS_OPERATIONS = [
		'visible',
		'read',
		'write',
		'edit_permission',
		'delete',
		'copy',
	];

	public function __construct(
		private readonly IRequest $request,
		private readonly IMvcView $view,
		private readonly ILinkTargetService $linkTargetService,
		private readonly IUsermanager $usermanager
	) {}

	public static function getName(): string {
		return 'usermanagerdebugdisplay';
	}

	public function setData($data) {
		// no-op
	}

	public function getHelp(): string {
		return 'Shows the current BASE3 usermanager user, groups, roles and target permissions.';
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		$out = strtolower($out);

		if ($out === 'json' || $this->requestValue(self::PARAM_ACTION) === self::ACTION_PERMISSION_PROBE) {
			return $this->handleJson();
		}

		return $this->handleHtml();
	}

	private function handleHtml(): string {
		$userCall = $this->call('getUser', fn() => $this->usermanager->getUser());
		$groupsCall = $this->call('getGroups', fn() => $this->usermanager->getGroups());
		$rolesCall = $this->call('getRoles', fn() => $this->usermanager->getRoles());
		$permissionsCall = $this->call('getPermissions', fn() => $this->usermanager->getPermissions());
		$allPermissionsCall = $this->call('getAllPermissions', fn() => $this->usermanager->getAllPermissions());

		$user = $this->normalizeItem($userCall['value']);
		$groups = $this->normalizeList($groupsCall['value']);
		$roles = $this->normalizeList($rolesCall['value']);
		$permissions = $this->normalizeList($permissionsCall['value']);
		$allPermissions = $this->normalizeList($allPermissionsCall['value']);
		$roleChecks = $this->getRoleChecks($roles);

		$scope = $this->getScope();
		$target = $this->getTarget();
		$operation = $this->getOperation();

		$this->view->setPath(\DIR_PLUGIN . 'ClientStack');
		$this->view->setTemplate('Display/UsermanagerDebugDisplay.php');

		$this->view->assign('generatedAt', date('c'));
		$this->view->assign('usermanagerClass', get_class($this->usermanager));
		$this->view->assign('endpoint', $this->buildPermissionProbeEndpoint());
		$this->view->assign('scopeParamName', self::PARAM_SCOPE);
		$this->view->assign('targetParamName', self::PARAM_TARGET);
		$this->view->assign('operationParamName', self::PARAM_OPERATION);
		$this->view->assign('modeParamName', self::PARAM_MODE);
		$this->view->assign('defaultScope', $scope);
		$this->view->assign('defaultTarget', $this->formatTargetForInput($target));
		$this->view->assign('defaultOperation', $operation);
		$this->view->assign('operationOptions', $this->getOperationOptions($allPermissions, $scope));
		$this->view->assign('initialUsage', $this->buildUsage($scope, $operation, $target));
		$this->view->assign('user', $user);
		$this->view->assign('groups', $groups);
		$this->view->assign('roles', $roles);
		$this->view->assign('permissions', $permissions);
		$this->view->assign('allPermissions', $allPermissions);
		$this->view->assign('permissionScopes', $this->groupPermissionsByScope($permissions));
		$this->view->assign('allPermissionScopes', $this->groupPermissionsByScope($allPermissions));
		$this->view->assign('roleChecks', $roleChecks);
		$this->view->assign('calls', [$userCall, $groupsCall, $rolesCall, $permissionsCall, $allPermissionsCall]);
		$this->view->assign('summary', $this->getSummary($userCall, $groups, $roles, $permissions, $allPermissions, $roleChecks));
		$this->view->assign('formatValue', fn($value) => $this->formatValue($value));
		$this->view->assign('formatList', fn($value) => $this->formatList($value));

		return $this->view->loadTemplate();
	}

	private function handleJson(): string {
		try {
			$action = $this->requestValue(self::PARAM_ACTION);

			if ($action !== self::ACTION_PERMISSION_PROBE) {
				return $this->jsonError("Unknown action '$action'. Use: " . self::ACTION_PERMISSION_PROBE);
			}

			$scope = $this->getScope();
			$target = $this->getTarget();
			$operation = $this->getOperation();
			$mode = $this->getMode();
			$allPermissionsCall = $this->call('getAllPermissions', fn() => $this->usermanager->getAllPermissions());
			$allPermissions = $this->normalizeList($allPermissionsCall['value']);
			$operations = $mode === 'all' ? $this->getOperationOptions($allPermissions, $scope) : [$operation];
			$rows = [];

			foreach ($operations as $operationName) {
				$rows[] = $this->checkPermission($scope, $operationName, $target);
			}

			return $this->jsonSuccess([
				'scope' => $scope,
				'target' => $target,
				'operation' => $operation,
				'mode' => $mode,
				'usage' => $this->buildUsage($scope, $operation, $target),
				'rows' => $rows,
				'total' => count($rows),
				'allowed' => count(array_filter($rows, fn(array $row) => (string)$row['result'] === 'yes')),
				'errors' => count(array_filter($rows, fn(array $row) => (string)$row['status'] === 'error')),
			]);
		} catch (Throwable $exception) {
			return $this->jsonError(get_class($exception) . ': ' . $exception->getMessage());
		}
	}

	private function getRoleChecks(array $roles): array {
		$checks = [];
		$seen = [];

		foreach ($roles as $role) {
			$name = trim((string)($role['name'] ?? ''));
			if ($name === '' || isset($seen[$name])) continue;

			$seen[$name] = true;
			$checks[] = $this->checkRole($name);
		}

		return $checks;
	}

	private function checkRole(string $role): array {
		$call = $this->call('hasRole(' . $role . ')', fn() => $this->usermanager->hasRole(Role::named($role)));
		$allowed = $call['ok'] && $call['value'] === true;

		return [
			'label' => 'Role: ' . $role,
			'source' => "\$this->usermanager->hasRole(Role::named('" . $this->escapePhpString($role) . "'))",
			'status' => $call['ok'] ? ($allowed ? 'ok' : 'info') : 'error',
			'result' => $call['ok'] ? ($allowed ? 'yes' : 'no') : 'error',
			'details' => $call['ok'] ? '' : $call['error'],
		];
	}

	private function checkPermission(string $scope, string $operation, int|string|null $target): array {
		$permission = Permission::for($scope, $operation, $target);
		$call = $this->call('can(' . $scope . '/' . $operation . ')', fn() => $this->usermanager->can($permission));
		$allowed = $call['ok'] && $call['value'] === true;

		return [
			'label' => $operation,
			'scope' => $scope,
			'target' => $target,
			'usage' => $this->buildUsage($scope, $operation, $target),
			'status' => $call['ok'] ? ($allowed ? 'ok' : 'info') : 'error',
			'result' => $call['ok'] ? ($allowed ? 'yes' : 'no') : 'error',
			'details' => $call['ok'] ? '' : $call['error'],
		];
	}

	private function getOperationOptions(array $allPermissions, string $scope): array {
		$operations = [];

		foreach ($allPermissions as $permission) {
			$currentScope = trim((string)($permission['scope'] ?? ''));
			$name = trim((string)($permission['permission'] ?? ''));

			if ($currentScope !== $scope || $name === '') continue;

			$operations[] = $name;
		}

		if (empty($operations) && $scope === 'ilias') {
			$operations = self::DEFAULT_ILIAS_OPERATIONS;
		}

		$operations = array_values(array_unique($operations));
		sort($operations);

		return $operations;
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

	private function getSummary(array $userCall, array $groups, array $roles, array $permissions, array $allPermissions, array $roleChecks): array {
		$errors = $userCall['ok'] ? 0 : 1;
		foreach ($roleChecks as $check) {
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
			'all_permissions' => count($allPermissions),
			'role_checks' => count($roleChecks),
			'errors' => $errors,
		];
	}

	private function formatList($value): string {
		if ($value === null || $value === '') {
			return '';
		}

		if (!is_array($value)) {
			return $this->formatValue($value);
		}

		$parts = [];
		foreach ($value as $item) {
			if (is_array($item)) {
				$parts[] = (string)($item['name'] ?? $item['permission'] ?? $item['id'] ?? $item['value'] ?? json_encode($item));
			} else {
				$parts[] = $this->formatValue($item);
			}
		}

		return implode(', ', array_filter($parts, fn($part) => $part !== ''));
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

		return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '';
	}

	private function getScope(): string {
		$scope = trim($this->requestValue(self::PARAM_SCOPE));

		return $scope !== '' ? $scope : self::DEFAULT_SCOPE;
	}

	private function getTarget(): int|string|null {
		$value = trim($this->requestValue(self::PARAM_TARGET));

		if ($value === '') {
			return null;
		}

		if (is_numeric($value)) {
			return (int)$value;
		}

		return $value;
	}

	private function getOperation(): string {
		$operation = trim($this->requestValue(self::PARAM_OPERATION));

		return $operation !== '' ? $operation : self::DEFAULT_OPERATION;
	}

	private function getMode(): string {
		$mode = trim($this->requestValue(self::PARAM_MODE));

		return $mode === 'all' ? 'all' : 'single';
	}

	private function requestValue(string $key): string {
		$value = $this->request->request($key);

		if (is_scalar($value)) {
			return trim((string)$value);
		}

		return '';
	}

	private function buildPermissionProbeEndpoint(): string {
		return $this->linkTargetService->getLink(
			[
				'name' => self::getName(),
				'out' => 'json'
			],
			[
				self::PARAM_ACTION => self::ACTION_PERMISSION_PROBE
			]
		);
	}

	private function buildUsage(string $scope, string $operation, int|string|null $target): string {
		return "\$allowed = \$this->usermanager->can(Permission::for("
			. $this->phpValue($scope)
			. ', '
			. $this->phpValue($operation)
			. ', '
			. $this->phpValue($target)
			. '));';
	}

	private function phpValue(int|string|null $value): string {
		if ($value === null) {
			return 'null';
		}

		if (is_int($value)) {
			return (string)$value;
		}

		return "'" . $this->escapePhpString($value) . "'";
	}

	private function escapePhpString(string $value): string {
		return str_replace(["\\", "'"], ["\\\\", "\\'"], $value);
	}

	private function formatTargetForInput(int|string|null $target): string {
		return $target === null ? '' : (string)$target;
	}

	private function jsonSuccess(array $data): string {
		if (!headers_sent()) {
			header('Content-Type: application/json; charset=utf-8');
		}

		return json_encode([
			'status' => 'ok',
			'timestamp' => gmdate('c'),
			'data' => $data,
		], JSON_UNESCAPED_UNICODE) ?: '{}';
	}

	private function jsonError(string $message): string {
		if (!headers_sent()) {
			header('Content-Type: application/json; charset=utf-8');
		}

		return json_encode([
			'status' => 'error',
			'timestamp' => gmdate('c'),
			'message' => $message,
		], JSON_UNESCAPED_UNICODE) ?: '{}';
	}
}
