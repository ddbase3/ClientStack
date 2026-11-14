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

namespace Base3\ClientStack\Test\GlobalChatbotLauncher;

use Base3\CoreFoundation\Api\IAssetResolver;
use Base3\CoreFoundation\Api\IMvcView;
use Base3\CoreFoundation\Authorization\Api\IAuthorizationService;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationDecision;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest;
use Base3\CoreFoundation\Settings\Api\ISettingsStore;
use Base3\ClientStack\GlobalChatbotLauncher\GlobalChatbotLauncherBootstrap;
use PHPUnit\Framework\TestCase;

final class GlobalChatbotLauncherBootstrapTest extends TestCase {

	public function testEscapesUserSuppliedAppearanceAndUsesCanonicalClientStackAssets(): void {
		$assets = $this->createMock(IAssetResolver::class);
		$assets->method('resolve')->willReturnCallback(static fn(string $path): string => '/' . $path);
		$settings = $this->createMock(ISettingsStore::class);
		$settings->method('get')->willReturn(['mode_overlay_enabled' => true]);
		$view = $this->createMock(IMvcView::class);
		$view->method('getBricks')->willReturn(['launcher_label' => 'Chat', 'close_chatbot' => 'Close']);
		$renderer = new GlobalChatbotLauncherBootstrap($assets, $settings, $view);

		$html = $renderer->render('/render?x=1&other=2', 'chatbot', 'default', ['header_title' => '<script>alert(1)</script>']);
		self::assertStringContainsString('data-base3-global-chatbot-launcher', $html);
		self::assertStringContainsString('data-mode-overlay-enabled="1"', $html);
		self::assertStringContainsString('data-ajax-url="/render?x=1&amp;other=2"', $html);
		self::assertStringNotContainsString('<script>alert(1)</script>', $html);
		self::assertStringContainsString('plugin/ClientStack/assets/globalchatbotlauncher/index.js', $html);
	}
	public function testOnlyExplicitHostAllowEmitsLauncher(): void {
		$assets = $this->createStub(IAssetResolver::class);
		$assets->method('resolve')->willReturnCallback(static fn(string $path): string => '/' . $path);
		$settings = $this->createStub(ISettingsStore::class);
		$settings->method('get')->willReturn([]);
		$view = $this->createStub(IMvcView::class);
		$view->method('getBricks')->willReturn(['launcher_label' => 'Chat']);
		$bootstrap = new GlobalChatbotLauncherBootstrap($assets, $settings, $view);
		foreach ([AuthorizationDecision::deny(), AuthorizationDecision::indeterminate()] as $decision) {
			$authorization = $this->createStub(IAuthorizationService::class);
			$authorization->method('getDefaultAuthority')->willReturn('site');
			$authorization->method('decide')->willReturn($decision);
			self::assertSame('', $bootstrap->renderAuthorized($authorization, '/chat.html', 'chatbot', 'default'));
		}

		$authorization = $this->createMock(IAuthorizationService::class);
		$authorization->method('getDefaultAuthority')->willReturn('site');
		$authorization->expects(self::once())->method('decide')->willReturnCallback(static function(AuthorizationRequest $request): AuthorizationDecision {
			self::assertSame('site', $request->authority);
			self::assertSame('feature', $request->permission->scope);
			self::assertSame('globalchatbotlauncherfeature', $request->permission->permission);
			return AuthorizationDecision::allow();
		});
		self::assertStringContainsString('data-base3-global-chatbot-launcher',
			$bootstrap->renderAuthorized($authorization, '/chat.html', 'chatbot', 'default'));
	}

}
