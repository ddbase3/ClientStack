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
use Base3\CoreFoundation\Authorization\Api\IAuthorizationAdministrationService;
use Base3\CoreFoundation\Authorization\Api\IAuthorizationService;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationCapabilities;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationDecision;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationGrant;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationSubject;
use Base3\CoreFoundation\Authorization\Dto\ResourceReference;
use Base3\CoreFoundation\Authorization\Dto\SubjectReference;
use Base3\CoreFoundation\LinkTarget\Api\ILinkTargetService;
use Base3\CoreFoundation\Session\Api\ISession;
use Base3\CoreFoundation\Usermanager\Permission;
use Base3\ClientStack\Display\AuthorizationAdministrationDisplay;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ClientStackNativeRightsCoverageTest extends TestCase {

	private const CSRF = 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc';

	private function request(array $values = [], array $posted = []): IRequest {
		$request = $this->createStub(IRequest::class);
		$request->method('request')->willReturnCallback(static fn(string $key, mixed $default = null): mixed => $values[$key] ?? $default);
		$request->method('post')->willReturnCallback(static fn(string $key, mixed $default = null): mixed => $posted[$key] ?? $default);
		return $request;
	}

	private function resourceRequest(array $otherValues = [], array $posted = []): IRequest {
		return $this->request(array_merge(['resource_id' => '123', 'authority' => 'ilias', 'resource_type' => 'ref_id'], $otherValues), $posted);
	}

	private function session(bool $active = true, string $token = self::CSRF): ISession {
		$session = $this->createStub(ISession::class);
		$session->method('started')->willReturn($active);
		$session->method('get')->willReturn($token);
		return $session;
	}

	private function view(?array &$assigned = null): IMvcView {
		$view = $this->createStub(IMvcView::class);
		$view->method('loadTemplate')->willReturn('rights-html');
		$view->method('assign')->willReturnCallback(static function(string $key, mixed $value) use (&$assigned): void {
			$assigned[$key] = $value;
		});
		return $view;
	}

	private function rights(
		IAuthorizationAdministrationService $administration, ?IRequest $request = null,
		?IMvcView $view = null, ?ISession $session = null, bool $featureAllowed = true
	): AuthorizationAdministrationDisplay {
		$authorization = $this->createStub(IAuthorizationService::class);
		$authorization->method('getDefaultAuthority')->willReturn('ilias');
		$authorization->method('decide')->willReturn($featureAllowed ? AuthorizationDecision::allow() : AuthorizationDecision::deny());
		$links = $this->createStub(ILinkTargetService::class);
		$links->method('getLink')->willReturn('/native-rights');
		return new AuthorizationAdministrationDisplay($authorization, $administration, $request ?? $this->request(),
			$session ?? $this->session(), $view ?? $this->view(), $links);
	}

	private function administration(bool $read = true, bool $write = true): IAuthorizationAdministrationService {
		$administration = $this->createStub(IAuthorizationAdministrationService::class);
		$administration->method('describe')->willReturn(new AuthorizationCapabilities('ilias', $read, $write, false, ['role']));
		$administration->method('getPermissions')->willReturn([Permission::for('object', 'read'), Permission::for('object', 'write')]);
		$administration->method('getSubjects')->willReturn([new AuthorizationSubject(new SubjectReference('ilias', 'role', 21), 'Teachers', true)]);
		$administration->method('getGrants')->willReturn([]);
		return $administration;
	}

	protected function setUp(): void {
		http_response_code(200);
	}

	protected function tearDown(): void {
		http_response_code(200);
	}

	public function testDisplayMetadataAndEmptySelectionDoNotReadHost(): void {
		$administration = $this->createMock(IAuthorizationAdministrationService::class);
		$administration->expects(self::never())->method('describe');
		$assigned = [];
		$display = $this->rights($administration, null, $this->view($assigned));
		self::assertSame('authorizationadministrationdisplay', AuthorizationAdministrationDisplay::getName());
		$display->setData(['ignored' => true]);
		self::assertSame('rights-html', $display->getOutput());
		self::assertSame('', $assigned['resource_id']);
		self::assertSame('ilias', $assigned['authority']);
		self::assertSame(self::CSRF, $assigned['csrf']);
		self::assertSame([], $assigned['rows']);
		self::assertSame('/native-rights', $assigned['endpoint']);
	}

	public function testFeatureDenialNeverInspectsNativeRights(): void {
		$administration = $this->createMock(IAuthorizationAdministrationService::class);
		$administration->expects(self::never())->method('describe');
		$display = $this->rights($administration, $this->resourceRequest(), featureAllowed: false);
		self::assertSame('', $display->getOutput());
		self::assertSame(403, http_response_code());
	}

	public function testOnlyHtmlOutputIsSupported(): void {
		$display = $this->rights($this->administration());
		$this->expectException(InvalidArgumentException::class);
		$display->getOutput('json');
	}

	public function testInvalidResourceIdInputDoesNotQueryBackend(): void {
		$administration = $this->createMock(IAuthorizationAdministrationService::class);
		$administration->expects(self::never())->method('describe');
		$assigned = [];
		$display = $this->rights($administration, $this->resourceRequest(['resource_id' => []]), $this->view($assigned));
		self::assertSame('rights-html', $display->getOutput());
		self::assertSame('', $assigned['resource_id']);
	}

	public function testResourceReadUsesNativeAuthorityAndResourceId(): void {
		$administration = $this->createStub(IAuthorizationAdministrationService::class);
		$seen = [];
		$administration->method('describe')->willReturnCallback(static function(string $authority, ?ResourceReference $resource) use (&$seen): AuthorizationCapabilities {
			$seen = [$authority, $resource->authority, $resource->type, $resource->id];
			return new AuthorizationCapabilities($authority, true, false, false, ['role']);
		});
		$administration->method('getPermissions')->willReturn([]);
		$administration->method('getSubjects')->willReturn([]);
		$administration->method('getGrants')->willReturn([]);
		$assigned = [];
		$display = $this->rights($administration, $this->resourceRequest(), $this->view($assigned));
		self::assertSame('rights-html', $display->getOutput());
		self::assertSame(['ilias', 'ilias', 'ref_id', '123'], $seen);
		self::assertSame('', $assigned['error']);
		self::assertFalse($assigned['capabilities']->canWrite);
	}

	public function testNoReadCapabilityDeniesGrantEnumeration(): void {
		$administration = $this->createMock(IAuthorizationAdministrationService::class);
		$administration->method('describe')->willReturn(new AuthorizationCapabilities('ilias', false, true, false, ['role']));
		$administration->expects(self::never())->method('getPermissions');
		$assigned = [];
		$display = $this->rights($administration, $this->resourceRequest(), $this->view($assigned));
		self::assertSame('rights-html', $display->getOutput());
		self::assertStringContainsString('unavailable', $assigned['error']);
		self::assertSame([], $assigned['permissions']);
	}

	public function testNativeBackendExceptionDoesNotLeakHostInformation(): void {
		$administration = $this->createStub(IAuthorizationAdministrationService::class);
		$administration->method('describe')->willReturnCallback(static function(): never {throw new RuntimeException('private ILIAS role data');});
		$assigned = [];
		$display = $this->rights($administration, $this->resourceRequest(), $this->view($assigned));
		self::assertSame('rights-html', $display->getOutput());
		self::assertStringNotContainsString('private ILIAS', $assigned['error']);
		self::assertSame([], $assigned['rows']);
	}

	public function testGrantsRemainSeparatedIntoDirectAndInheritedPermissions(): void {
		$subjectRef = new SubjectReference('ilias', 'role', 21);
		$subject = new AuthorizationSubject($subjectRef, 'Teachers', true);
		$resource = new ResourceReference('ilias', 'ref_id', '123');
		$administration = $this->createStub(IAuthorizationAdministrationService::class);
		$administration->method('describe')->willReturn(new AuthorizationCapabilities('ilias', true, false, true, ['role']));
		$administration->method('getPermissions')->willReturn([Permission::for('object', 'read')]);
		$administration->method('getSubjects')->willReturn([$subject]);
		$administration->method('getGrants')->willReturn([
			new AuthorizationGrant($subjectRef, Permission::for('object', 'read'), $resource, true, false),
			new AuthorizationGrant($subjectRef, Permission::for('object', 'read'), $resource, true, false),
			new AuthorizationGrant($subjectRef, Permission::for('object', 'write'), $resource, false, true),
			new AuthorizationGrant(new SubjectReference('ilias', 'role', 99), Permission::for('object', 'write'), $resource, true, false)
		]);
		$assigned = [];
		$display = $this->rights($administration, $this->resourceRequest(), $this->view($assigned));
		self::assertSame('rights-html', $display->getOutput());
		self::assertSame(['object/read'], array_values($assigned['rows'][0]['direct']));
		self::assertSame(['object/write'], $assigned['rows'][0]['inherited']);
		self::assertCount(1, $assigned['rows']);
	}

	public function testReplacingNativeGrantsForEditableSelectedRole(): void {
		$administration = $this->createMock(IAuthorizationAdministrationService::class);
		$administration->method('describe')->willReturn(new AuthorizationCapabilities('ilias', true, true, false, ['role']));
		$administration->method('getPermissions')->willReturn([Permission::for('object', 'read'), Permission::for('object', 'write')]);
		$administration->method('getSubjects')->willReturn([new AuthorizationSubject(new SubjectReference('ilias', 'role', 21), 'Teachers', true)]);
		$administration->method('getGrants')->willReturn([]);
		$captured = [];
		$administration->expects(self::once())->method('replaceGrants')->willReturnCallback(static function(ResourceReference $resource, SubjectReference $subject, array $permissions) use (&$captured): void {
			$captured = [$resource->id, $subject->kind, $subject->id, array_map(static fn(Permission $p): string => $p->scope . '/' . $p->permission, $permissions)];
		});
		$assigned = [];
		$display = $this->rights($administration, $this->resourceRequest([
			'subject_kind' => 'role', 'subject_id' => '21'
		], ['base3_rights_action' => 'replace', 'base3_rights_csrf' => self::CSRF,
			'base3_permissions' => ['object/read', 'object/read', 'object/write']]), $this->view($assigned));
		self::assertSame('rights-html', $display->getOutput());
		self::assertSame(['123', 'role', 21, ['object/read', 'object/write']], $captured);
		self::assertSame('Native role grants saved.', $assigned['message']);
	}

	public function testReplacementWithEmptyPermissionsRevokesOnlyDirectRoleGrants(): void {
		$administration = $this->createMock(IAuthorizationAdministrationService::class);
		$administration->method('describe')->willReturn(new AuthorizationCapabilities('ilias', true, true, false, ['role']));
		$administration->method('getPermissions')->willReturn([Permission::for('object', 'read')]);
		$administration->method('getSubjects')->willReturn([new AuthorizationSubject(new SubjectReference('ilias', 'role', 21), 'Teachers', true)]);
		$administration->method('getGrants')->willReturn([]);
		$captured = null;
		$administration->expects(self::once())->method('replaceGrants')->willReturnCallback(static function(ResourceReference $resource, SubjectReference $subject, array $permissions) use (&$captured): void {$captured = $permissions;});
		$display = $this->rights($administration, $this->resourceRequest([
			'subject_kind' => 'role', 'subject_id' => '21'
		], ['base3_rights_action' => 'replace', 'base3_rights_csrf' => self::CSRF, 'base3_permissions' => []]));
		self::assertSame('rights-html', $display->getOutput());
		self::assertSame([], $captured);
	}

	public function testCsrfFailurePreventsNativeMutation(): void {
		$administration = $this->createMock(IAuthorizationAdministrationService::class);
		$administration->method('describe')->willReturn(new AuthorizationCapabilities('ilias', true, true, false, ['role']));
		$administration->method('getPermissions')->willReturn([]);
		$administration->method('getSubjects')->willReturn([]);
		$administration->expects(self::never())->method('replaceGrants');
		$assigned = [];
		$display = $this->rights($administration, $this->resourceRequest([], [
			'base3_rights_action' => 'replace', 'base3_rights_csrf' => 'wrong'
		]), $this->view($assigned));
		self::assertSame('rights-html', $display->getOutput());
		self::assertSame(403, http_response_code());
		self::assertStringContainsString('rejected', $assigned['error']);
	}

	public function testReadOnlyNativeAuthorityNeverReplacesGrants(): void {
		$administration = $this->createMock(IAuthorizationAdministrationService::class);
		$administration->method('describe')->willReturn(new AuthorizationCapabilities('ilias', true, false, false, ['role']));
		$administration->method('getPermissions')->willReturn([]);
		$administration->method('getSubjects')->willReturn([]);
		$administration->expects(self::never())->method('replaceGrants');
		$display = $this->rights($administration, $this->resourceRequest([], [
			'base3_rights_action' => 'replace', 'base3_rights_csrf' => self::CSRF
		]));
		self::assertSame('rights-html', $display->getOutput());
		self::assertSame(403, http_response_code());
	}

	public function testUnknownNativePermissionIsRejected(): void {
		$administration = $this->createMock(IAuthorizationAdministrationService::class);
		$administration->method('describe')->willReturn(new AuthorizationCapabilities('ilias', true, true, false, ['role']));
		$administration->method('getPermissions')->willReturn([Permission::for('object', 'read')]);
		$administration->method('getSubjects')->willReturn([new AuthorizationSubject(new SubjectReference('ilias', 'role', 21), 'Teachers', true)]);
		$administration->expects(self::never())->method('replaceGrants');
		$display = $this->rights($administration, $this->resourceRequest([
			'subject_kind' => 'role', 'subject_id' => '21'
		], ['base3_rights_action' => 'replace', 'base3_rights_csrf' => self::CSRF,
			'base3_permissions' => ['object/delete_everything']]));
		self::assertSame('rights-html', $display->getOutput());
		self::assertSame(403, http_response_code());
	}

	public function testNonEditableNativeSubjectCannotBeChanged(): void {
		$administration = $this->createMock(IAuthorizationAdministrationService::class);
		$administration->method('describe')->willReturn(new AuthorizationCapabilities('ilias', true, true, false, ['role']));
		$administration->method('getPermissions')->willReturn([]);
		$administration->method('getSubjects')->willReturn([new AuthorizationSubject(new SubjectReference('ilias', 'role', 21), 'Teachers', false)]);
		$administration->expects(self::never())->method('replaceGrants');
		$display = $this->rights($administration, $this->resourceRequest([
			'subject_kind' => 'role', 'subject_id' => '21'
		], ['base3_rights_action' => 'replace', 'base3_rights_csrf' => self::CSRF,
			'base3_permissions' => []]));
		self::assertSame('rights-html', $display->getOutput());
		self::assertSame(403, http_response_code());
	}

	public function testInvalidPermissionListNeverCallsHostMutation(): void {
		$administration = $this->createMock(IAuthorizationAdministrationService::class);
		$administration->method('describe')->willReturn(new AuthorizationCapabilities('ilias', true, true, false, ['role']));
		$administration->method('getPermissions')->willReturn([]);
		$administration->method('getSubjects')->willReturn([new AuthorizationSubject(new SubjectReference('ilias', 'role', 21), 'Teachers', true)]);
		$administration->expects(self::never())->method('replaceGrants');
		$display = $this->rights($administration, $this->resourceRequest([
			'subject_kind' => 'role', 'subject_id' => '21'
		], ['base3_rights_action' => 'replace', 'base3_rights_csrf' => self::CSRF,
			'base3_permissions' => ['object/read' => true]]));
		self::assertSame('rights-html', $display->getOutput());
		self::assertSame(403, http_response_code());
	}

	public function testCsrfTokenIsGeneratedForMissingOrMalformedSessionValue(): void {
		$session = $this->createMock(ISession::class);
		$session->method('started')->willReturn(true);
		$session->method('get')->willReturn('short');
		$token = '';
		$session->expects(self::once())->method('set')->willReturnCallback(static function(string $key, mixed $value) use (&$token): void {$token = $value;});
		$display = $this->rights($this->createStub(IAuthorizationAdministrationService::class), null, null, $session);
		self::assertSame('rights-html', $display->getOutput());
		self::assertSame(64, strlen($token));
	}

	public function testRenderingRequiresActiveSession(): void {
		$display = $this->rights($this->createStub(IAuthorizationAdministrationService::class), null, null, $this->session(false));
		$this->expectException(RuntimeException::class);
		$display->getOutput();
	}
}
