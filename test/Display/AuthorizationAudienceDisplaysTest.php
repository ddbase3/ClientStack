<?php declare(strict_types=1);

namespace ClientStack\Test\Display;

use Base3\Api\IAssetResolver;
use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\Authorization\Api\IAuthorizationService;
use Base3\Authorization\Dto\AuthorizationDecision;
use Base3\LinkTarget\Api\ILinkTargetService;
use Base3\Session\Api\ISession;
use ClientStack\Display\AuthorizationFeaturesDisplay;
use ClientStack\Display\AuthorizationGroupsDisplay;
use PHPUnit\Framework\TestCase;
use UiFoundation\Api\IAuthorizationAudienceAdministrationService;

final class AuthorizationAudienceDisplaysTest extends TestCase {

	public function testGroupDisplayDoesNotReadBackendWithoutSystemAdminDecision(): void {
		$authorization = $this->createMock(IAuthorizationService::class);
		$authorization->method('getDefaultAuthority')->willReturn('ilias');
		$authorization->method('decide')->willReturn(AuthorizationDecision::deny());
		$administration = $this->createMock(IAuthorizationAudienceAdministrationService::class);
		$administration->expects(self::never())->method('getGroupView');
		$display = new AuthorizationGroupsDisplay(
			$authorization,
			$administration,
			$this->createMock(IRequest::class),
			$this->createMock(ISession::class),
			$this->createMock(IMvcView::class),
			$this->createMock(ILinkTargetService::class),
			$this->createMock(IAssetResolver::class)
		);
		self::assertStringContainsString('Access denied', $display->getOutput());
	}

	public function testBadCsrfDoesNotModifyFeatureAudiences(): void {
		$authorization = $this->createMock(IAuthorizationService::class);
		$authorization->method('getDefaultAuthority')->willReturn('ilias');
		$authorization->method('decide')->willReturn(AuthorizationDecision::allow());
		$request = $this->createMock(IRequest::class);
		$request->method('post')->willReturnCallback(static function (string $key, $default = null) {
			return match ($key) {
				'base3_feature_action' => 'save',
				'base3_feature_csrf' => 'incorrect',
				'base3_feature_rules' => ['demo' => []],
				default => $default
			};
		});
		$session = $this->createMock(ISession::class);
		$session->method('get')->willReturn(str_repeat('a', 64));
		$administration = $this->createMock(IAuthorizationAudienceAdministrationService::class);
		$administration->expects(self::never())->method('replaceFeatureRules');
		$display = new AuthorizationFeaturesDisplay(
			$authorization, $administration, $request, $session,
			$this->createMock(IMvcView::class),
			$this->createMock(ILinkTargetService::class),
			$this->createMock(IAssetResolver::class)
		);
		self::assertStringContainsString('rejected', $display->getOutput());
	}

	public function testGroupMutationDelegatesToHostExactlyOnce(): void {
		$authorization = $this->createMock(IAuthorizationService::class);
		$authorization->method('getDefaultAuthority')->willReturn('ilias');
		$authorization->method('decide')->willReturn(AuthorizationDecision::allow());
		$request = $this->createMock(IRequest::class);
		$request->method('post')->willReturnCallback(static function (string $key, $default = null) {
			return match ($key) {
				'base3_group_action' => 'save',
				'base3_group_csrf' => str_repeat('b', 64),
				'base3_group_rules' => ['example' => []],
				'base3_default_group' => '',
				default => $default
			};
		});
		$session = $this->createMock(ISession::class);
		$session->method('get')->willReturn(str_repeat('b', 64));
		$session->method('started')->willReturn(true);
		$administration = $this->createMock(IAuthorizationAudienceAdministrationService::class);
		$administration->expects(self::once())->method('replaceGroupRules')->with(['example' => []], '');
		$administration->method('getGroupView')->willReturn(['entries' => [], 'defaultGroup' => '', 'criterionOptions' => [], 'roleOptions' => [], 'roleHint' => '']);
		$view = $this->createMock(IMvcView::class);
		$view->method('getBricks')->willReturn([]);
		$view->method('loadTemplate')->willReturn('rendered');
		$links = $this->createMock(ILinkTargetService::class);
		$links->method('getLink')->willReturn('/admin');
		$assets = $this->createMock(IAssetResolver::class);
		$assets->method('resolve')->willReturnCallback(static fn(string $path): string => '/assets/' . $path);
		$display = new AuthorizationGroupsDisplay($authorization, $administration, $request, $session, $view, $links, $assets);
		self::assertSame('rendered', $display->getOutput());
	}
}
