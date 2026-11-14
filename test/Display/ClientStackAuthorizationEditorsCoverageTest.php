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

use Base3\CoreFoundation\Api\IAssetResolver;
use Base3\CoreFoundation\Api\IMvcView;
use Base3\CoreFoundation\Api\IRequest;
use Base3\CoreFoundation\Authorization\Api\IAuthorizationService;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationDecision;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest;
use Base3\CoreFoundation\LinkTarget\Api\ILinkTargetService;
use Base3\CoreFoundation\Session\Api\ISession;
use Base3\ClientStack\Display\AuthorizationFeaturesDisplay;
use Base3\ClientStack\Display\AuthorizationGroupsDisplay;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Base3\UiFoundation\Api\IAuthorizationAudienceAdministrationService;

final class ClientStackAuthorizationEditorsCoverageTest extends TestCase {

	private function authorization(bool $feature = true, bool $admin = true, ?array &$decisions = null): IAuthorizationService {
		$authorization = $this->createStub(IAuthorizationService::class);
		$authorization->method('getDefaultAuthority')->willReturn('ilias');
		$index = 0;
		$authorization->method('decide')->willReturnCallback(static function(AuthorizationRequest $request) use (&$index, &$decisions, $feature, $admin): AuthorizationDecision {
			$decisions[] = [$request->authority, $request->permission->scope, $request->permission->permission];
			$index++;
			return ($index === 1 ? $feature : $admin) ? AuthorizationDecision::allow() : AuthorizationDecision::deny();
		});
		return $authorization;
	}

	private function request(array $values = []): IRequest {
		$request = $this->createStub(IRequest::class);
		$request->method('post')->willReturnCallback(static fn(string $name, mixed $default = null): mixed => $values[$name] ?? $default);
		return $request;
	}

	private function session(bool $started = true, string $token = ''): ISession {
		$session = $this->createStub(ISession::class);
		$session->method('started')->willReturn($started);
		$session->method('get')->willReturn($token);
		return $session;
	}

	private function view(?array &$assigned = null): IMvcView {
		$view = $this->createStub(IMvcView::class);
		$view->method('getBricks')->willReturn([]);
		$view->method('loadTemplate')->willReturn('rendered');
		$view->method('assign')->willReturnCallback(static function(string $key, mixed $value) use (&$assigned): void {
			$assigned[$key] = $value;
		});
		return $view;
	}

	private function features(
		IAuthorizationService $authorization, IAuthorizationAudienceAdministrationService $administration,
		?IRequest $request = null, ?ISession $session = null, ?IMvcView $view = null
	): AuthorizationFeaturesDisplay {
		$links = $this->createStub(ILinkTargetService::class);
		$links->method('getLink')->willReturn('/admin');
		$assets = $this->createStub(IAssetResolver::class);
		$assets->method('resolve')->willReturnCallback(static fn(string $path): string => '/assets/' . $path);
		return new AuthorizationFeaturesDisplay($authorization, $administration, $request ?? $this->request(),
			$session ?? $this->session(true, str_repeat('x', 64)), $view ?? $this->view(), $links, $assets);
	}

	private function createGroupsDisplay(
		IAuthorizationService $authorization, IAuthorizationAudienceAdministrationService $administration,
		?IRequest $request = null, ?ISession $session = null, ?IMvcView $view = null
	): AuthorizationGroupsDisplay {
		$links = $this->createStub(ILinkTargetService::class);
		$links->method('getLink')->willReturn('/admin');
		$assets = $this->createStub(IAssetResolver::class);
		$assets->method('resolve')->willReturnCallback(static fn(string $path): string => '/assets/' . $path);
		return new AuthorizationGroupsDisplay($authorization, $administration, $request ?? $this->request(),
			$session ?? $this->session(true, str_repeat('x', 64)), $view ?? $this->view(), $links, $assets);
	}

	protected function setUp(): void {
		http_response_code(200);
	}

	protected function tearDown(): void {
		http_response_code(200);
	}

	public function testFeatureDisplayDeniesBeforeAccessingHost(): void {
		$admin = $this->createMock(IAuthorizationAudienceAdministrationService::class);
		$admin->expects(self::never())->method('getFeatureView');
		$decisions = [];
		$display = $this->features($this->authorization(false, true, $decisions), $admin);
		self::assertSame('', $display->getOutput());
		self::assertSame([['ilias', 'feature', 'authorizationfeaturessettingstab']], $decisions);
		self::assertSame(403, http_response_code());
	}

	public function testGroupsDisplayDeniesBeforeAccessingHost(): void {
		$admin = $this->createMock(IAuthorizationAudienceAdministrationService::class);
		$admin->expects(self::never())->method('getGroupView');
		$decisions = [];
		$display = $this->createGroupsDisplay($this->authorization(false, true, $decisions), $admin);
		self::assertSame('', $display->getOutput());
		self::assertSame([['ilias', 'feature', 'authorizationgroupssettingstab']], $decisions);
	}

	public function testFeatureSystemAdminDecisionMustAllow(): void {
		$admin = $this->createMock(IAuthorizationAudienceAdministrationService::class);
		$admin->expects(self::never())->method('getFeatureView');
		$decisions = [];
		$display = $this->features($this->authorization(true, false, $decisions), $admin);
		self::assertStringContainsString('Access denied', $display->getOutput());
		self::assertSame(['ilias', 'system', 'admin'], $decisions[1]);
		self::assertSame(403, http_response_code());
	}

	public function testGroupSystemAdminDecisionMustAllow(): void {
		$admin = $this->createMock(IAuthorizationAudienceAdministrationService::class);
		$admin->expects(self::never())->method('getGroupView');
		$display = $this->createGroupsDisplay($this->authorization(true, false), $admin);
		self::assertStringContainsString('Access denied', $display->getOutput());
		self::assertSame(403, http_response_code());
	}

	public function testFeatureDisplayRejectsNonHtmlOutput(): void {
		$display = $this->features($this->authorization(), $this->createStub(IAuthorizationAudienceAdministrationService::class));
		$this->expectException(InvalidArgumentException::class);
		$display->getOutput('json');
	}

	public function testGroupDisplayRejectsNonHtmlOutput(): void {
		$display = $this->createGroupsDisplay($this->authorization(), $this->createStub(IAuthorizationAudienceAdministrationService::class));
		$this->expectException(InvalidArgumentException::class);
		$display->getOutput('json');
	}

	public function testFeatureHostDenialDoesNotRevealDetails(): void {
		$admin = $this->createStub(IAuthorizationAudienceAdministrationService::class);
		$admin->method('assertCanAdminister')->willReturnCallback(static function(): never {throw new RuntimeException('private host details');});
		$display = $this->features($this->authorization(), $admin);
		$text = $display->getOutput();
		self::assertStringContainsString('rejected', $text);
		self::assertStringNotContainsString('private host details', $text);
		self::assertSame(403, http_response_code());
	}

	public function testGroupHostDenialDoesNotRevealDetails(): void {
		$admin = $this->createStub(IAuthorizationAudienceAdministrationService::class);
		$admin->method('assertCanAdminister')->willReturnCallback(static function(): never {throw new RuntimeException('private host details');});
		$display = $this->createGroupsDisplay($this->authorization(), $admin);
		$text = $display->getOutput();
		self::assertStringContainsString('rejected', $text);
		self::assertStringNotContainsString('private host details', $text);
		self::assertSame(403, http_response_code());
	}

	public function testFeatureSaveDelegatesExactRowsOnceAndRenders(): void {
		$admin = $this->createMock(IAuthorizationAudienceAdministrationService::class);
		$rules = ['feature.demo' => ['op' => 'and', 'children' => []]];
		$admin->expects(self::once())->method('replaceFeatureRules')->with($rules);
		$admin->method('getFeatureView')->willReturn(['rows' => [], 'groups' => [], 'criterionOptions' => [], 'roleOptions' => [], 'roleHint' => '']);
		$assigned = [];
		$display = $this->features($this->authorization(), $admin, $this->request([
			'base3_feature_action' => 'save', 'base3_feature_rules' => $rules, 'base3_feature_csrf' => str_repeat('x', 64)
		]), null, $this->view($assigned));
		self::assertSame('rendered', $display->getOutput());
		self::assertSame('Feature audiences saved.', $assigned['message']);
		self::assertSame('features', $assigned['userSearchScope']);
		self::assertSame('/admin', $assigned['endpoint']);
		self::assertSame('/assets/plugin/ClientStack/assets/modularselect/index.js', $assigned['modularSelectModuleUrl']);
	}

	public function testFeatureInvalidRulesNeverReachHost(): void {
		$admin = $this->createMock(IAuthorizationAudienceAdministrationService::class);
		$admin->expects(self::never())->method('replaceFeatureRules');
		$display = $this->features($this->authorization(), $admin, $this->request([
			'base3_feature_action' => 'save', 'base3_feature_rules' => 'invalid', 'base3_feature_csrf' => str_repeat('x', 64)
		]));
		self::assertStringContainsString('rejected', $display->getOutput());
		self::assertSame(403, http_response_code());
	}

	public function testUnknownFeatureActionNeverMutatesHost(): void {
		$admin = $this->createMock(IAuthorizationAudienceAdministrationService::class);
		$admin->expects(self::never())->method('replaceFeatureRules');
		$display = $this->features($this->authorization(), $admin, $this->request([
			'base3_feature_action' => 'delete-all', 'base3_feature_csrf' => str_repeat('x', 64)
		]));
		self::assertStringContainsString('rejected', $display->getOutput());
	}

	public function testGroupCreationDelegatesExactNameAndDescription(): void {
		$admin = $this->createMock(IAuthorizationAudienceAdministrationService::class);
		$admin->expects(self::once())->method('createGroup')->with('research', 'Research team');
		$admin->method('getGroupView')->willReturn(['entries' => [], 'defaultGroup' => '', 'criterionOptions' => [], 'roleOptions' => [], 'roleHint' => '']);
		$assigned = [];
		$display = $this->createGroupsDisplay($this->authorization(), $admin, $this->request([
			'base3_group_action' => 'create', 'base3_group_name' => 'research',
			'base3_group_description' => 'Research team', 'base3_group_csrf' => str_repeat('x', 64)
		]), null, $this->view($assigned));
		self::assertSame('rendered', $display->getOutput());
		self::assertSame('BASE3 group created.', $assigned['message']);
		self::assertSame('groups', $assigned['userSearchScope']);
	}

	public function testGroupRemovalDelegatesExactName(): void {
		$admin = $this->createMock(IAuthorizationAudienceAdministrationService::class);
		$admin->expects(self::once())->method('removeGroup')->with('research');
		$admin->method('getGroupView')->willReturn([]);
		$assigned = [];
		$display = $this->createGroupsDisplay($this->authorization(), $admin, $this->request([
			'base3_group_action' => 'remove', 'base3_group_name' => 'research', 'base3_group_csrf' => str_repeat('x', 64)
		]), null, $this->view($assigned));
		self::assertSame('rendered', $display->getOutput());
		self::assertSame('BASE3 group removed.', $assigned['message']);
	}

	public function testGroupSaveNormalizesDefaultGroupAndPassesRules(): void {
		$admin = $this->createMock(IAuthorizationAudienceAdministrationService::class);
		$admin->expects(self::once())->method('replaceGroupRules')->with(['research' => []], 'authenticated');
		$admin->method('getGroupView')->willReturn([]);
		$display = $this->createGroupsDisplay($this->authorization(), $admin, $this->request([
			'base3_group_action' => 'save', 'base3_group_rules' => ['research' => []],
			'base3_default_group' => ' authenticated ', 'base3_group_csrf' => str_repeat('x', 64)
		]));
		self::assertSame('rendered', $display->getOutput());
	}

	public function testGroupSaveRejectsNonArrayRules(): void {
		$admin = $this->createMock(IAuthorizationAudienceAdministrationService::class);
		$admin->expects(self::never())->method('replaceGroupRules');
		$display = $this->createGroupsDisplay($this->authorization(), $admin, $this->request([
			'base3_group_action' => 'save', 'base3_group_rules' => '{broken}', 'base3_group_csrf' => str_repeat('x', 64)
		]));
		self::assertStringContainsString('rejected', $display->getOutput());
	}

	public function testInvalidGroupActionDoesNotMutateHost(): void {
		$admin = $this->createMock(IAuthorizationAudienceAdministrationService::class);
		$admin->expects(self::never())->method('removeGroup');
		$admin->expects(self::never())->method('createGroup');
		$display = $this->createGroupsDisplay($this->authorization(), $admin, $this->request([
			'base3_group_action' => 'restore', 'base3_group_csrf' => str_repeat('x', 64)
		]));
		self::assertStringContainsString('rejected', $display->getOutput());
	}

	public function testFeatureReadGeneratesCsrfTokenExactlyOnce(): void {
		$admin = $this->createStub(IAuthorizationAudienceAdministrationService::class);
		$admin->method('getFeatureView')->willReturn([]);
		$session = $this->createMock(ISession::class);
		$session->method('started')->willReturn(true);
		$session->method('get')->willReturn('short');
		$token = '';
		$session->expects(self::once())->method('set')->willReturnCallback(static function(string $key, mixed $value) use (&$token): void { $token = $value; });
		$display = $this->features($this->authorization(), $admin, null, $session);
		self::assertSame('rendered', $display->getOutput());
		self::assertSame(64, strlen($token));
	}

	public function testGroupReadRequiresSessionForCsrfToken(): void {
		$admin = $this->createStub(IAuthorizationAudienceAdministrationService::class);
		$admin->method('getGroupView')->willReturn([]);
		$display = $this->createGroupsDisplay($this->authorization(), $admin, null, $this->session(false));
		self::assertStringContainsString('rejected', $display->getOutput());
		self::assertSame(403, http_response_code());
	}
}
