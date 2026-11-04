<?php declare(strict_types=1);

namespace ClientStack\Test\Display;

use Base3\Api\IAssetResolver;
use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\Authorization\Api\IAuthorizationAdministrationService;
use Base3\Authorization\Api\IAuthorizationService;
use Base3\Authorization\Dto\AuthorizationCapabilities;
use Base3\Authorization\Dto\AuthorizationDecision;
use Base3\Authorization\Dto\AuthorizationSubject;
use Base3\Authorization\Dto\SubjectReference;
use Base3\LinkTarget\Api\ILinkTargetService;
use Base3\Session\Api\ISession;
use ClientStack\Display\AuthorizationAdministrationDisplay;
use ClientStack\Display\AuthorizationFeaturesDisplay;
use ClientStack\Display\AuthorizationGroupsDisplay;
use PHPUnit\Framework\TestCase;
use UiFoundation\Api\IAuthorizationAudienceAdministrationService;

final class ClientStackAuthorizationRemainingCoverageTest extends TestCase {

	private const CSRF = 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc';

	protected function setUp(): void {
		http_response_code(200);
	}

	protected function tearDown(): void {
		http_response_code(200);
	}

	private function authorization(): IAuthorizationService {
		$service = $this->createStub(IAuthorizationService::class);
		$service->method('getDefaultAuthority')->willReturn('ilias');
		$service->method('decide')->willReturn(AuthorizationDecision::allow());
		return $service;
	}

	private function request(array $values = [], array $posted = []): IRequest {
		$request = $this->createStub(IRequest::class);
		$request->method('request')->willReturnCallback(
			static fn(string $name, mixed $default = null): mixed => $values[$name] ?? $default
		);
		$request->method('post')->willReturnCallback(
			static fn(string $name, mixed $default = null): mixed => $posted[$name] ?? $default
		);
		return $request;
	}

	private function makeView(array &$assigned): IMvcView {
		$view = $this->createStub(IMvcView::class);
		$view->method('getBricks')->willReturn([]);
		$view->method('assign')->willReturnCallback(static function(string $key, mixed $value) use (&$assigned): void {
			$assigned[$key] = $value;
		});
		$view->method('loadTemplate')->willReturn('authorization-html');
		return $view;
	}

	private function editorSession(string $token, ?array &$writes = null): ISession {
		$session = $this->createStub(ISession::class);
		$session->method('started')->willReturn(true);
		$session->method('get')->willReturn($token);
		$session->method('set')->willReturnCallback(static function(string $name, mixed $value) use (&$writes): void {
			$writes[] = [$name, $value];
		});
		return $session;
	}

	private function groupDisplay(IAuthorizationAudienceAdministrationService $admin, IRequest $request, ISession $session, array &$assigned): AuthorizationGroupsDisplay {
		$links = $this->createStub(ILinkTargetService::class);
		$links->method('getLink')->willReturn('/admin');
		$assets = $this->createStub(IAssetResolver::class);
		$assets->method('resolve')->willReturn('/asset');
		return new AuthorizationGroupsDisplay($this->authorization(), $admin, $request, $session, $this->makeView($assigned), $links, $assets);
	}

	public function testFeatureDisplayIgnoresExternallyInjectedData(): void {
		$admin = $this->createMock(IAuthorizationAudienceAdministrationService::class);
		$admin->expects(self::never())->method('replaceFeatureRules');
		$assigned = [];
		$links = $this->createStub(ILinkTargetService::class);
		$links->method('getLink')->willReturn('/admin');
		$assets = $this->createStub(IAssetResolver::class);
		$assets->method('resolve')->willReturn('/asset');
		$display = new AuthorizationFeaturesDisplay($this->authorization(), $admin, $this->request(),
			$this->editorSession(self::CSRF), $this->makeView($assigned), $links, $assets);
		self::assertSame('authorizationfeaturesdisplay', $display::getName());
		self::assertNull($display->setData(['base3_feature_action' => 'save', 'rules' => ['injected' => true]]));
		self::assertSame('authorization-html', $display->getOutput());
		self::assertSame('', $assigned['message']);
	}

	public function testGroupDisplayIgnoresInjectedDataAndCreatesFreshCsrfOnce(): void {
		$admin = $this->createMock(IAuthorizationAudienceAdministrationService::class);
		$admin->expects(self::never())->method('replaceGroupRules');
		$writes = [];
		$assigned = [];
		$display = $this->groupDisplay($admin, $this->request(), $this->editorSession('broken', $writes), $assigned);
		self::assertSame('authorizationgroupsdisplay', $display::getName());
		self::assertNull($display->setData(['base3_group_action' => 'remove', 'base3_group_name' => 'development']));
		self::assertSame('authorization-html', $display->getOutput());
		self::assertSame('', $assigned['message']);
		self::assertCount(1, $writes);
		self::assertSame('authorization.groups.csrf', $writes[0][0]);
		self::assertSame(64, strlen($writes[0][1]));
		self::assertSame($writes[0][1], $assigned['csrf']);
	}

	public function testGroupUpdateWithIncorrectCsrfDoesNotReachAdministration(): void {
		$admin = $this->createMock(IAuthorizationAudienceAdministrationService::class);
		$admin->expects(self::never())->method('replaceGroupRules');
		$admin->expects(self::never())->method('removeGroup');
		$assigned = [];
		$display = $this->groupDisplay($admin, $this->request([], [
			'base3_group_action' => 'save',
			'base3_group_csrf' => 'not-correct',
			'base3_group_rules' => [['name' => 'administration']]
		]), $this->editorSession(self::CSRF), $assigned);
		self::assertStringContainsString('rejected', $display->getOutput());
		self::assertSame(403, http_response_code());
	}

	public function testNativeRightsUnknownSubjectCannotReplaceHostGrants(): void {
		$admin = $this->createMock(IAuthorizationAdministrationService::class);
		$admin->method('describe')->willReturn(new AuthorizationCapabilities('ilias', true, true, false, ['role']));
		$admin->method('getPermissions')->willReturn([]);
		$admin->method('getSubjects')->willReturn([
			new AuthorizationSubject(new SubjectReference('ilias', 'role', 21), 'Teachers', true)
		]);
		$admin->method('getGrants')->willReturn([]);
		$admin->expects(self::never())->method('replaceGrants');
		$assigned = [];
		$links = $this->createStub(ILinkTargetService::class);
		$links->method('getLink')->willReturn('/rights');
		$display = new AuthorizationAdministrationDisplay($this->authorization(), $admin,
			$this->request([
				'resource_type' => 'ref_id', 'resource_id' => '123',
				'subject_kind' => 'role', 'subject_id' => '999'
			], [
				'base3_rights_action' => 'replace', 'base3_rights_csrf' => self::CSRF,
				'base3_permissions' => []
			]),
			$this->editorSession(self::CSRF), $this->makeView($assigned), $links);
		self::assertSame('authorization-html', $display->getOutput());
		self::assertSame(403, http_response_code());
		self::assertSame([], $assigned['rows']);
		self::assertStringContainsString('rejected', $assigned['error']);
	}

	public function testNativeRightsDifferentAuthorityDoesNotAuthorizeSelectedSubject(): void {
		$admin = $this->createMock(IAuthorizationAdministrationService::class);
		$admin->method('describe')->willReturn(new AuthorizationCapabilities('ilias', true, true, false, ['role']));
		$admin->method('getPermissions')->willReturn([]);
		$admin->method('getSubjects')->willReturn([
			new AuthorizationSubject(new SubjectReference('other', 'role', 21), 'Foreign', true)
		]);
		$admin->method('getGrants')->willReturn([]);
		$admin->expects(self::never())->method('replaceGrants');
		$assigned = [];
		$display = new AuthorizationAdministrationDisplay($this->authorization(), $admin,
			$this->request([
				'resource_id' => '123', 'subject_kind' => 'role', 'subject_id' => '21'
			], [
				'base3_rights_action' => 'replace', 'base3_rights_csrf' => self::CSRF
			]),
			$this->editorSession(self::CSRF), $this->makeView($assigned), $this->createStub(ILinkTargetService::class));
		self::assertSame('authorization-html', $display->getOutput());
		self::assertSame(403, http_response_code());
		self::assertSame([], $assigned['rows']);
	}
}
