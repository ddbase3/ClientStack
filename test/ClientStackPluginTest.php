<?php declare(strict_types=1);

namespace ClientStack\Test;

use PHPUnit\Framework\TestCase;
use ClientStack\ClientStackPlugin;
use ClientStack\Api\IAssetService;
use ClientStack\Service\DefaultAssetService;
use Base3\Api\IContainer;
use Base3\Test\Core\ContainerStub;

class ClientStackPluginTest extends TestCase {

	public function testGetNameReturnsExpectedValue(): void {
		$this->assertSame('clientstackplugin', ClientStackPlugin::getName());
	}

	public function testInitRegistersPluginAndAssetService(): void {
		$container = new ContainerStub();
		$plugin = new ClientStackPlugin($container);

		$plugin->init();

		$this->assertTrue($container->has(ClientStackPlugin::getName()));
		$this->assertSame($plugin, $container->get(ClientStackPlugin::getName()));
		$this->assertSame(IContainer::SHARED, $container->getFlags(ClientStackPlugin::getName()));

		$this->assertTrue($container->has(IAssetService::class));
		$this->assertSame(IContainer::SHARED, $container->getFlags(IAssetService::class));

		// ContainerStub resolves callables by executing them (like the real container would).
		$service1 = $container->get(IAssetService::class);
		$this->assertInstanceOf(DefaultAssetService::class, $service1);

		// SHARED => same instance
		$service2 = $container->get(IAssetService::class);
		$this->assertSame($service1, $service2);
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
