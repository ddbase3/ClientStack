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

use Base3\CoreFoundation\Api\IMvcView;
use Base3\CoreFoundation\Api\IRequest;
use Base3\CoreFoundation\Authorization\Api\IAuthorizationService;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationDecision;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest;
use Base3\CoreFoundation\LinkTarget\Api\ILinkTargetService;
use Base3\CoreFoundation\Usermanager\Api\IUsermanager;
use Base3\CoreFoundation\Usermanager\Permission;
use Base3\ClientStack\Display\UsermanagerDebugDisplay;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

final class ClientStackUsermanagerDebugCoverageTest extends TestCase {

	private function request(array $values = []): IRequest {
		$request = $this->createStub(IRequest::class);
		$request->method('request')->willReturnCallback(static fn(string $key, mixed $default = null): mixed => $values[$key] ?? $default);
		return $request;
	}

	private function usermanager(): IUsermanager {
		$user = $this->createStub(IUsermanager::class);
		$user->method('getUser')->willReturn(['id' => 7, 'userid' => 'tester']);
		$user->method('getGroups')->willReturn([['name' => 'authenticated']]);
		$user->method('getRoles')->willReturn([['name' => 'learner'], ['name' => 'learner'], ['name' => 'administrator']]);
		$user->method('getPermissions')->willReturn([Permission::for('ilias', 'read')]);
		$user->method('getAllPermissions')->willReturn([Permission::for('ilias', 'read'), Permission::for('ilias', 'write')]);
		$user->method('hasRole')->willReturn(true);
		return $user;
	}

	private function debug(
		?IUsermanager $usermanager = null, ?IRequest $request = null,
		?IMvcView $view = null, ?IAuthorizationService $authorization = null
	): UsermanagerDebugDisplay {
		if ($authorization === null) {
			$authorization = $this->createStub(IAuthorizationService::class);
			$authorization->method('getDefaultAuthority')->willReturn('ilias');
			$authorization->method('decide')->willReturn(AuthorizationDecision::allow());
		}
		$view ??= $this->createStub(IMvcView::class);
		$view->method('loadTemplate')->willReturn('debug-html');
		$links = $this->createStub(ILinkTargetService::class);
		$links->method('getLink')->willReturn('/debug?out=json');
		return new UsermanagerDebugDisplay($request ?? $this->request(), $view, $links, $usermanager ?? $this->usermanager(), $authorization);
	}

	private function invoke(UsermanagerDebugDisplay $display, string $method, mixed ...$args): mixed {
		return (new ReflectionMethod(UsermanagerDebugDisplay::class, $method))->invoke($display, ...$args);
	}

	protected function setUp(): void {
		http_response_code(200);
	}

	protected function tearDown(): void {
		http_response_code(200);
	}

	public function testNameHelpAndNoOpSetDataAreStable(): void {
		$display = $this->debug();
		self::assertSame('usermanagerdebugdisplay', UsermanagerDebugDisplay::getName());
		self::assertStringContainsString('groups', $display->getHelp());
		$display->setData(['ignored' => true]);
	}

	public function testFeatureDenialBlocksAllUsermanagerReads(): void {
		$user = $this->createMock(IUsermanager::class);
		$user->expects(self::never())->method('getUser');
		$auth = $this->createStub(IAuthorizationService::class);
		$auth->method('getDefaultAuthority')->willReturn('ilias');
		$auth->method('decide')->willReturn(AuthorizationDecision::deny());
		$display = $this->debug($user, authorization: $auth);
		self::assertSame('', $display->getOutput());
		self::assertSame(403, http_response_code());
	}

	public function testHtmlRendersUserCatalogAndChecksEachUniqueRoleOnce(): void {
		$user = $this->createMock(IUsermanager::class);
		$user->method('getUser')->willReturn(['id' => 7, 'userid' => 'tester']);
		$user->method('getGroups')->willReturn([['name' => 'authenticated']]);
		$user->method('getRoles')->willReturn([['name' => 'learner'], ['name' => 'learner'], ['name' => 'admin']]);
		$user->method('getPermissions')->willReturn([Permission::for('ilias', 'read')]);
		$user->method('getAllPermissions')->willReturn([Permission::for('ilias', 'write'), Permission::for('ilias', 'read')]);
		$user->expects(self::exactly(2))->method('hasRole')->willReturn(true);
		$assigned = [];
		$view = $this->createStub(IMvcView::class);
		$view->method('loadTemplate')->willReturn('debug-html');
		$view->method('assign')->willReturnCallback(static function(string $key, mixed $value) use (&$assigned): void {$assigned[$key] = $value;});
		$display = $this->debug($user, $this->request(), $view);
		self::assertSame('debug-html', $display->getOutput());
		self::assertSame(['read', 'write'], $assigned['operationOptions']);
		self::assertSame('ok', $assigned['summary']['status']);
		self::assertSame(2, $assigned['summary']['role_checks']);
		self::assertSame('7', $assigned['summary']['user_id']);
		self::assertSame('/debug?out=json', $assigned['endpoint']);
		self::assertSame('read', $assigned['defaultOperation']);
	}

	public function testJsonUnknownActionNeverQueriesAuthorizationOperations(): void {
		$user = $this->createMock(IUsermanager::class);
		$user->expects(self::never())->method('getAllPermissions');
		$data = json_decode($this->debug($user)->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame('error', $data['status']);
		self::assertStringContainsString('Unknown action', $data['message']);
	}

	public function testJsonSingleOperationChecksExactNativeResource(): void {
		$auth = $this->createStub(IAuthorizationService::class);
		$auth->method('getDefaultAuthority')->willReturn('ilias');
		$captured = [];
		$auth->method('decide')->willReturnCallback(static function(AuthorizationRequest $request) use (&$captured): AuthorizationDecision {
			$captured[] = [$request->authority, $request->permission->scope, $request->permission->permission, $request->resource?->id];
			return AuthorizationDecision::allow();
		});
		$display = $this->debug($this->usermanager(), $this->request([
			'action' => 'permission_probe', 'base3_um_scope' => 'ilias', 'base3_um_target' => '321', 'base3_um_operation' => 'write'
		]), authorization: $auth);
		$data = json_decode($display->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame('ok', $data['status']);
		self::assertSame(1, $data['data']['total']);
		self::assertSame(1, $data['data']['allowed']);
		self::assertSame(['ilias', 'ilias', 'write', 321], $captured[1]);
		self::assertSame('yes', $data['data']['rows'][0]['result']);
	}

	public function testJsonAllModeChecksKnownOperationsOnly(): void {
		$auth = $this->createStub(IAuthorizationService::class);
		$auth->method('getDefaultAuthority')->willReturn('ilias');
		$seen = [];
		$auth->method('decide')->willReturnCallback(static function(AuthorizationRequest $request) use (&$seen): AuthorizationDecision {
			$seen[] = $request->permission->permission;
			return ($request->permission->scope === 'feature' || $request->permission->permission === 'read') ? AuthorizationDecision::allow() : AuthorizationDecision::deny();
		});
		$display = $this->debug($this->usermanager(), $this->request([
			'action' => 'permission_probe', 'base3_um_scope' => 'ilias', 'base3_um_mode' => 'all'
		]), authorization: $auth);
		$json = $display->getOutput('json');
		$data = json_decode($json, true, 512, JSON_THROW_ON_ERROR)['data'];
		self::assertSame(2, $data['total']);
		self::assertSame(1, $data['allowed']);
		self::assertSame(['usermanagerdebugsettingstab', 'read', 'write'], $seen);
	}

	public function testProbeWithDeniedPermissionReturnsNoWithoutError(): void {
		$auth = $this->createStub(IAuthorizationService::class);
		$auth->method('getDefaultAuthority')->willReturn('ilias');
		$auth->method('decide')->willReturnCallback(static function(AuthorizationRequest $request): AuthorizationDecision {
			return $request->permission->scope === 'feature' ? AuthorizationDecision::allow() : AuthorizationDecision::deny();
		});
		$display = $this->debug($this->usermanager(), $this->request(['action' => 'permission_probe']), authorization: $auth);
		$data = json_decode($display->getOutput('json'), true, 512, JSON_THROW_ON_ERROR)['data'];
		self::assertSame('no', $data['rows'][0]['result']);
		self::assertSame(0, $data['errors']);
	}

	public function testProbeBackendErrorIsReportedAsOneErroredRow(): void {
		$auth = $this->createStub(IAuthorizationService::class);
		$auth->method('getDefaultAuthority')->willReturn('ilias');
		$auth->method('decide')->willReturnCallback(static function(AuthorizationRequest $request): AuthorizationDecision {
			if($request->permission->scope === 'feature') return AuthorizationDecision::allow();
			throw new RuntimeException('permission backend unavailable');
		});
		$display = $this->debug($this->usermanager(), $this->request(['action' => 'permission_probe']), authorization: $auth);
		$data = json_decode($display->getOutput('json'), true, 512, JSON_THROW_ON_ERROR)['data'];
		self::assertSame('error', $data['rows'][0]['status']);
		self::assertSame(1, $data['errors']);
	}

	public function testOperationOptionsDeduplicateFilterAndSort(): void {
		$display = $this->debug();
		$permissions = [
			['scope' => 'ilias', 'permission' => 'write'],
			['scope' => 'moodle', 'permission' => 'read'],
			['scope' => 'ilias', 'permission' => 'read'],
			['scope' => 'ilias', 'permission' => 'write'],
			['scope' => 'ilias', 'permission' => ' ']
		];
		self::assertSame(['read', 'write'], $this->invoke($display, 'getOperationOptions', $permissions, 'ilias'));
		self::assertSame([], $this->invoke($display, 'getOperationOptions', $permissions, 'wordpress'));
	}

	public function testDefaultIliasOperationsOnlyWhenNoCatalogEntries(): void {
		$operations = $this->invoke($this->debug(), 'getOperationOptions', [], 'ilias');
		self::assertSame(['copy', 'delete', 'edit_permission', 'read', 'visible', 'write'], $operations);
	}

	public function testNormalizeListHandlesNullSingleEntryAndAssociativeShape(): void {
		$display = $this->debug();
		self::assertSame([], $this->invoke($display, 'normalizeList', null));
		self::assertSame([['value' => 'word']], $this->invoke($display, 'normalizeList', 'word'));
		self::assertSame([['name' => 'editor']], $this->invoke($display, 'normalizeList', ['name' => 'editor']));
		self::assertSame([['value' => 8], ['value' => 9]], $this->invoke($display, 'normalizeList', [8, 9]));
	}

	public function testNormalizeItemPreservesObjectClassAndNestedValues(): void {
		$display = $this->debug();
		$data = $this->invoke($display, 'normalizeItem', Permission::for('ilias', 'read'));
		self::assertSame('ilias', $data['scope']);
		self::assertSame(Permission::class, $data['__class']);
		self::assertSame(['child' => ['name' => 'x']], $this->invoke($display, 'normalizeItem', ['child' => ['name' => 'x']]));
	}

	public function testGroupPermissionsByScopeSortsAndHandlesUnknownScopes(): void {
		$display = $this->debug();
		$groups = $this->invoke($display, 'groupPermissionsByScope', [
			['scope' => 'ilias', 'permission' => 'write'],
			['scope' => '', 'permission' => 'missing'],
			['scope' => 'ilias', 'permission' => 'read']
		]);
		self::assertSame(['ilias', 'unknown'], array_keys($groups));
		self::assertSame('read', $groups['ilias'][0]['permission']);
		self::assertSame('write', $groups['ilias'][1]['permission']);
	}

	public function testFormattersHandleBooleansNullScalarsAndItems(): void {
		$display = $this->debug();
		self::assertSame('', $this->invoke($display, 'formatValue', null));
		self::assertSame('true', $this->invoke($display, 'formatValue', true));
		self::assertSame('false', $this->invoke($display, 'formatValue', false));
		self::assertSame('42', $this->invoke($display, 'formatValue', 42));
		self::assertSame('read, editor', $this->invoke($display, 'formatList', [['permission' => 'read'], ['name' => 'editor']]));
	}

	public function testRequestScopeAndTargetAreNormalized(): void {
		$display = $this->debug(request: $this->request([
			'base3_um_scope' => '  moodle  ', 'base3_um_operation' => '  edit  ', 'base3_um_target' => ' 0007 ',
			'base3_um_mode' => 'all'
		]));
		self::assertSame('moodle', $this->invoke($display, 'getScope'));
		self::assertSame('edit', $this->invoke($display, 'getOperation'));
		self::assertSame(7, $this->invoke($display, 'getTarget'));
		self::assertSame('all', $this->invoke($display, 'getMode'));
	}

	public function testNonNumericResourceIdStaysString(): void {
		$display = $this->debug(request: $this->request(['base3_um_target' => 'course-ABC']));
		self::assertSame('course-ABC', $this->invoke($display, 'getTarget'));
	}

	public function testUsageStringQuotesAuthorityScopeAndOperationSafely(): void {
		$display = $this->debug();
		$usage = $this->invoke($display, 'buildUsage', "o'hare", 'read', 'course-1');
		self::assertStringContainsString("o\\'hare", $usage);
		self::assertStringContainsString("'course-1'", $usage);
		self::assertStringContainsString('ResourceReference', $usage);
	}

	public function testSummaryCountsRoleCheckErrorsButNoExtraPermissiveGrants(): void {
		$display = $this->debug();
		$summary = $this->invoke($display, 'getSummary',
			['ok' => false, 'value' => null], [], [], [], [], [['status' => 'error']]);
		self::assertSame('error', $summary['status']);
		self::assertSame(2, $summary['errors']);
		self::assertSame('error', $summary['user_status']);
	}
}
