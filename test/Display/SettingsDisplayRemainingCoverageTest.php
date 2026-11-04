<?php declare(strict_types=1);

namespace ClientStack\Test\Display;

use Base3\Api\IClassMap;
use Base3\Api\IDisplay;
use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\Authorization\Api\IAuthorizationService;
use Base3\Authorization\Dto\AuthorizationDecision;
use Base3\LinkTarget\Api\ILinkTargetService;
use Base3\Translation\Api\ITranslation;
use Base3\Usermanager\Api\IUsermanager;
use Base3\Usermanager\User;
use ClientStack\Display\SettingsDisplay;
use ClientStack\Display\TabControlDisplay;
use ClientStack\SettingsTab\Base3SettingsTab;
use ClientStack\SettingsTab\UsermanagerdebugSettingsTab;
use PHPUnit\Framework\TestCase;
use UiFoundation\Api\ISettingsTab;

final class SettingsDisplayRemainingCoverageTest extends TestCase {

	protected function setUp(): void {
		http_response_code(200);
	}

	protected function tearDown(): void {
		http_response_code(200);
	}

	private function translation(): ITranslation {
		$translation = $this->createStub(ITranslation::class);
		$translation->method('translate')->willReturnCallback(
			static fn(string $set, string $section, string $key): string => $key
		);
		return $translation;
	}

	private function usermanager(): IUsermanager {
		$users = $this->createStub(IUsermanager::class);
		$users->method('getUser')->willReturn(new User());
		return $users;
	}

	private function authorizedEntry(ITranslation $translation): UsermanagerdebugSettingsTab {
		$authorization = $this->createStub(IAuthorizationService::class);
		$authorization->method('getDefaultAuthority')->willReturn('base3');
		$authorization->method('decide')->willReturn(AuthorizationDecision::allow());
		return new UsermanagerdebugSettingsTab($translation, $authorization);
	}

	public function testSettingsIdentityAndUntrustedDisplayDataHaveNoSideEffects(): void {
		$map = $this->createMock(IClassMap::class);
		$map->expects(self::never())->method('getInstancesByInterface');
		$display = new SettingsDisplay($map, $this->usermanager(), $this->translation(),
			$this->createStub(IRequest::class), $this->createStub(ILinkTargetService::class));
		self::assertSame('settingsdisplay', SettingsDisplay::getName());
		self::assertNull($display->setData(['entry' => 'injected', 'admin' => true]));
		self::assertSame('', $display->getOutput('json'));
		self::assertSame(404, http_response_code());
	}

	public function testMissingTabControlProducesServiceUnavailableInsteadOfUntrustedContent(): void {
		$map = $this->createStub(IClassMap::class);
		$map->method('getInstancesByInterface')->willReturn([]);
		$map->method('getInstanceByInterfaceName')->willReturn(null);
		$display = new SettingsDisplay($map, $this->usermanager(), $this->translation(),
			$this->createStub(IRequest::class), $this->createStub(ILinkTargetService::class));
		self::assertSame('', $display->getOutput());
		self::assertSame(503, http_response_code());
	}

	public function testMissingAuthorizedDisplayReturnsNotFoundWithoutRenderingOtherTabs(): void {
		$translation = $this->translation();
		$feature = $this->authorizedEntry($translation);
		$map = $this->createMock(IClassMap::class);
		$map->method('getInstancesByInterface')->willReturn([new Base3SettingsTab($translation), $feature]);
		$map->expects(self::once())->method('getInstanceByInterfaceName')
			->with(IDisplay::class, 'usermanagerdebugdisplay')->willReturn(null);
		$request = $this->createStub(IRequest::class);
		$request->method('get')->willReturn(UsermanagerdebugSettingsTab::getName());
		$display = new SettingsDisplay($map, $this->usermanager(), $translation, $request,
			$this->createStub(ILinkTargetService::class));
		self::assertSame('', $display->getOutput());
		self::assertSame(404, http_response_code());
	}

	public function testAvailableSettingsTabRendersThroughExistingTabControl(): void {
		$translation = $this->translation();
		$feature = $this->authorizedEntry($translation);
		$assigned = [];
		$view = $this->createStub(IMvcView::class);
		$view->method('getBricks')->willReturn(['empty_message' => 'Nothing to configure']);
		$view->method('assign')->willReturnCallback(static function(string $key, mixed $value) use (&$assigned): void {
			$assigned[$key] = $value;
		});
		$view->method('loadTemplate')->willReturn('settings-tabs-html');
		$links = $this->createStub(ILinkTargetService::class);
		$links->method('getLink')->willReturn('/settings');
		$map = $this->createStub(IClassMap::class);
		$map->method('getInstancesByInterface')->willReturn([new Base3SettingsTab($translation), $feature]);
		$tabControl = new TabControlDisplay($map, $view, $links);
		$child = $this->createStub(IDisplay::class);
		$child->method('getOutput')->willReturn('active-content');
		$map->method('getInstanceByInterfaceName')->willReturnCallback(
			static fn(string $type, string $name): mixed => match ($name) {
				'tabcontroldisplay' => $tabControl,
				'usermanagerdebugdisplay' => $child,
				default => null
			}
		);
		$display = new SettingsDisplay($map, $this->usermanager(), $translation,
			$this->createStub(IRequest::class), $links);
		self::assertSame('settings-tabs-html', $display->getOutput());
		self::assertSame(200, http_response_code());
		self::assertSame('usermanagerdebugsettingstab', $assigned['activeDisplay']);
		self::assertSame('active-content', $assigned['content']);
		self::assertSame('base3settingstab', $assigned['tabs'][0]['name']);
		self::assertSame('usermanagerdebugdisplay', $assigned['tabs'][0]['displays'][0]['display']);
		self::assertFalse($assigned['initialLoadViaAjax']);
	}

	public function testUnknownFragmentCannotBeUsedToAccessAHiddenComponent(): void {
		$map = $this->createMock(IClassMap::class);
		$map->method('getInstancesByInterface')->willReturn([]);
		$map->expects(self::never())->method('getInstanceByInterfaceName');
		$request = $this->createStub(IRequest::class);
		$request->method('get')->willReturn('not-registered');
		$display = new SettingsDisplay($map, $this->usermanager(), $this->translation(), $request,
			$this->createStub(ILinkTargetService::class));
		self::assertSame('', $display->getOutput());
		self::assertSame(403, http_response_code());
	}
}
