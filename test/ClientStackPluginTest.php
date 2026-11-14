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

namespace Base3\ClientStack\Test;

use PHPUnit\Framework\TestCase;
use Base3\ClientStack\ClientStackPlugin;
use Base3\ClientStack\Api\IAssetService;
use Base3\ClientStack\Service\DefaultAssetService;
use Base3\CoreFoundation\Api\IContainer;
use Base3\CoreFoundation\Api\IModuleRegistry;
use Base3\UiFoundation\Api\IChatbotDisplay;
use Base3\Test\Core\ContainerStub;

class ClientStackPluginTest extends TestCase {

	public function testGetNameReturnsExpectedValue(): void {
		$this->assertSame('clientstackplugin', ClientStackPlugin::getName());
	}

	public function testInitRegistersPluginAndAssetService(): void {
		$container = new ContainerStub();
		$container->set(IModuleRegistry::class, $this->createStub(IModuleRegistry::class), IContainer::SHARED);
		$plugin = new ClientStackPlugin($container);

		$plugin->init();

		$this->assertTrue($container->has(ClientStackPlugin::getName()));
		$this->assertSame($plugin, $container->get(ClientStackPlugin::getName()));
		$this->assertSame(IContainer::SHARED, $container->getFlags(ClientStackPlugin::getName()));

		$this->assertTrue($container->has(IAssetService::class));
		$this->assertSame(IContainer::SHARED, $container->getFlags(IAssetService::class));

		$this->assertTrue($container->has(IChatbotDisplay::class));
		$this->assertSame(
			IContainer::SHARED | IContainer::NOOVERWRITE,
			$container->getFlags(IChatbotDisplay::class)
		);

		// ContainerStub resolves callables by executing them (like the real container would).
		$service1 = $container->get(IAssetService::class);
		$this->assertInstanceOf(DefaultAssetService::class, $service1);

		// SHARED => same instance
		$service2 = $container->get(IAssetService::class);
		$this->assertSame($service1, $service2);
	}

	public function testInitKeepsExistingChatbotDisplayBinding(): void {
		$container = new ContainerStub();
		$customDisplay = $this->createStub(IChatbotDisplay::class);
		$container->set(IChatbotDisplay::class, $customDisplay, IContainer::SHARED);

		$plugin = new ClientStackPlugin($container);
		$plugin->init();

		$this->assertSame($customDisplay, $container->get(IChatbotDisplay::class));
	}

	public function testCheckDependenciesReturnsOkWhenUiFoundationPluginInstalled(): void {
		$container = new ContainerStub();
		$container->set('uifoundationplugin', new \stdClass(), IContainer::SHARED);

		$plugin = new ClientStackPlugin($container);

		$this->assertSame([
			'uifoundationplugin_installed' => 'Ok'
		], $plugin->checkDependencies());
	}

	public function testCheckDependenciesReturnsNotInstalledWhenMissing(): void {
		$container = new ContainerStub();
		$plugin = new ClientStackPlugin($container);

		$this->assertSame([
			'uifoundationplugin_installed' => 'uifoundationplugin not installed'
		], $plugin->checkDependencies());
	}
}
