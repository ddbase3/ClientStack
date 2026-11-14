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

use Base3\CoreFoundation\Api\IClassMap;
use Base3\CoreFoundation\Api\IDisplay;
use Base3\CoreFoundation\Api\IRequest;
use Base3\CoreFoundation\Authorization\Api\IAuthorizationService;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationDecision;
use Base3\CoreFoundation\LinkTarget\Api\ILinkTargetService;
use Base3\CoreFoundation\Translation\Api\ITranslation;
use Base3\CoreFoundation\Usermanager\Api\IUsermanager;
use Base3\CoreFoundation\Usermanager\User;
use Base3\ClientStack\Display\SettingsDisplay;
use Base3\ClientStack\SettingsTab\Base3SettingsTab;
use Base3\ClientStack\SettingsTab\UsermanagerdebugSettingsTab;
use PHPUnit\Framework\TestCase;

final class SettingsDisplayTest extends TestCase {

	private function createAuthorization(bool $allowed): IAuthorizationService {
		$authorization = $this->createStub(IAuthorizationService::class);
		$authorization->method('getDefaultAuthority')->willReturn('base3');
		$authorization->method('decide')->willReturn($allowed ? AuthorizationDecision::allow() : AuthorizationDecision::deny());
		return $authorization;
	}

	public function testAnonymousRequestCannotReadSettings(): void {
		$classmap = $this->createMock(IClassMap::class);
		$classmap->expects(self::never())->method('getInstancesByInterface');
		$users = $this->createStub(IUsermanager::class);
		$users->method('getUser')->willReturn(null);
		$translation = $this->createStub(ITranslation::class);
		$translation->method('translate')->willReturn('Sign in required');
		$display = new SettingsDisplay($classmap, $users, $translation,
			$this->createStub(IRequest::class), $this->createStub(ILinkTargetService::class));
		self::assertStringContainsString('Sign in required', $display->getOutput());
		self::assertSame(403, http_response_code());
	}

	public function testDirectFragmentIsDeniedWhenFeatureIsNotAllowed(): void {
		$translation = $this->createStub(ITranslation::class);
		$translation->method('translate')->willReturn('Settings');
		$classmap = $this->createMock(IClassMap::class);
		$classmap->method('getInstancesByInterface')->willReturn([
			new Base3SettingsTab($translation),
			new UsermanagerdebugSettingsTab($translation, $this->createAuthorization(false))
		]);
		$classmap->expects(self::never())->method('getInstanceByInterfaceName');
		$users = $this->createStub(IUsermanager::class);
		$users->method('getUser')->willReturn(new User());
		$request = $this->createStub(IRequest::class);
		$request->method('get')->willReturn('usermanagerdebugsettingstab');
		$display = new SettingsDisplay($classmap, $users, $translation,
			$request, $this->createStub(ILinkTargetService::class));
		self::assertSame('', $display->getOutput());
		self::assertSame(403, http_response_code());
	}

	public function testPermittedFragmentReceivesPluginOwnedDisplayAndStaticData(): void {
		$translation = $this->createStub(ITranslation::class);
		$translation->method('translate')->willReturn('Messaging');
		$feature = new UsermanagerdebugSettingsTab($translation, $this->createAuthorization(true));
		$target = $this->createMock(IDisplay::class);
		$target->expects(self::once())->method('setData')->with(null);
		$target->expects(self::once())->method('getOutput')->with('html', false)->willReturn('rendered template');
		$classmap = $this->createMock(IClassMap::class);
		$classmap->method('getInstancesByInterface')->willReturn([new Base3SettingsTab($translation), $feature]);
		$classmap->expects(self::once())->method('getInstanceByInterfaceName')
			->with(IDisplay::class, 'usermanagerdebugdisplay')->willReturn($target);
		$users = $this->createStub(IUsermanager::class);
		$users->method('getUser')->willReturn(new User());
		$request = $this->createStub(IRequest::class);
		$request->method('get')->willReturn($feature::getName());
		$links = $this->createStub(ILinkTargetService::class);
		$links->method('getLink')->willReturn('/settings');
		$display = new SettingsDisplay($classmap, $users, $translation, $request, $links);
		self::assertSame('rendered template', $display->getOutput());
	}
}
