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
use Base3\CoreFoundation\LinkTarget\Api\ILinkTargetService;
use Base3\CoreFoundation\Session\Api\ISession;
use Base3\ClientStack\Display\AuthorizationFeaturesDisplay;
use Base3\ClientStack\Display\AuthorizationGroupsDisplay;
use PHPUnit\Framework\TestCase;
use Base3\UiFoundation\Api\IAuthorizationAudienceAdministrationService;

final class AuthorizationAudienceDisplaysTest extends TestCase {

	public function testGroupDisplayDoesNotReadBackendWithoutSystemAdminDecision(): void {
		$authorization = $this->createStub(IAuthorizationService::class);
		$authorization->method('getDefaultAuthority')->willReturn('ilias');
		$authorization->method('decide')->willReturn(AuthorizationDecision::deny());
		$administration = $this->createMock(IAuthorizationAudienceAdministrationService::class);
		$administration->expects(self::never())->method('getGroupView');
		$display = new AuthorizationGroupsDisplay(
			$authorization,
			$administration,
			$this->createStub(IRequest::class),
			$this->createStub(ISession::class),
			$this->createStub(IMvcView::class),
			$this->createStub(ILinkTargetService::class),
			$this->createStub(IAssetResolver::class)
		);
		self::assertSame('', $display->getOutput());
		self::assertSame(403, http_response_code());
	}

	public function testBadCsrfDoesNotModifyFeatureAudiences(): void {
		$authorization = $this->createStub(IAuthorizationService::class);
		$authorization->method('getDefaultAuthority')->willReturn('ilias');
		$authorization->method('decide')->willReturn(AuthorizationDecision::allow());
		$request = $this->createStub(IRequest::class);
		$request->method('post')->willReturnCallback(static function (string $key, $default = null) {
			return match ($key) {
				'base3_feature_action' => 'save',
				'base3_feature_csrf' => 'incorrect',
				'base3_feature_rules' => ['demo' => []],
				default => $default
			};
		});
		$session = $this->createStub(ISession::class);
		$session->method('get')->willReturn(str_repeat('a', 64));
		$administration = $this->createMock(IAuthorizationAudienceAdministrationService::class);
		$administration->expects(self::never())->method('replaceFeatureRules');
		$display = new AuthorizationFeaturesDisplay(
			$authorization, $administration, $request, $session,
			$this->createStub(IMvcView::class),
			$this->createStub(ILinkTargetService::class),
			$this->createStub(IAssetResolver::class)
		);
		self::assertStringContainsString('rejected', $display->getOutput());
	}

	public function testGroupMutationDelegatesToHostExactlyOnce(): void {
		$authorization = $this->createStub(IAuthorizationService::class);
		$authorization->method('getDefaultAuthority')->willReturn('ilias');
		$authorization->method('decide')->willReturn(AuthorizationDecision::allow());
		$request = $this->createStub(IRequest::class);
		$request->method('post')->willReturnCallback(static function (string $key, $default = null) {
			return match ($key) {
				'base3_group_action' => 'save',
				'base3_group_csrf' => str_repeat('b', 64),
				'base3_group_rules' => ['example' => []],
				'base3_default_group' => '',
				default => $default
			};
		});
		$session = $this->createStub(ISession::class);
		$session->method('get')->willReturn(str_repeat('b', 64));
		$session->method('started')->willReturn(true);
		$administration = $this->createMock(IAuthorizationAudienceAdministrationService::class);
		$administration->expects(self::once())->method('replaceGroupRules')->with(['example' => []], '');
		$administration->method('getGroupView')->willReturn(['entries' => [], 'defaultGroup' => '', 'criterionOptions' => [], 'roleOptions' => [], 'roleHint' => '']);
		$view = $this->createStub(IMvcView::class);
		$view->method('getBricks')->willReturn([]);
		$view->method('loadTemplate')->willReturn('rendered');
		$links = $this->createStub(ILinkTargetService::class);
		$links->method('getLink')->willReturn('/admin');
		$assets = $this->createStub(IAssetResolver::class);
		$assets->method('resolve')->willReturnCallback(static fn(string $path): string => '/assets/' . $path);
		$display = new AuthorizationGroupsDisplay($authorization, $administration, $request, $session, $view, $links, $assets);
		self::assertSame('rendered', $display->getOutput());
	}
}
