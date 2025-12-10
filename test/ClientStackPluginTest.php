<?php declare(strict_types=1);

namespace ClientStack\Test;

use PHPUnit\Framework\TestCase;
use ClientStack\ClientStackPlugin;
use ClientStack\Api\IAssetService;
use ClientStack\Service\DefaultAssetService;
use Base3\Api\IContainer;

class ClientStackPluginTest extends TestCase {

	public function testGetNameReturnsExpectedValue(): void {
		$this->assertSame('clientstackplugin', ClientStackPlugin::getName());
	}

	public function testInitRegistersPluginAndAssetService(): void {
		$container = new FakeContainer();
		$plugin = new ClientStackPlugin($container);

		$plugin->init();

		$this->assertTrue($container->has(ClientStackPlugin::getName()));
		$this->assertSame($plugin, $container->get(ClientStackPlugin::getName()));
		$this->assertSame(IContainer::SHARED, $container->getFlags(ClientStackPlugin::getName()));

		$this->assertTrue($container->has(IAssetService::class));
		$this->assertSame(IContainer::SHARED, $container->getFlags(IAssetService::class));

		$factory = $container->get(IAssetService::class);
		$this->assertIsCallable($factory);

		$service = $factory();
		$this->assertInstanceOf(DefaultAssetService::class, $service);
	}

	public function testCheckDependenciesReturnsEmptyArray(): void {
		$container = new FakeContainer();
		$plugin = new ClientStackPlugin($container);

		$this->assertSame([], $plugin->checkDependencies());
	}

}

class FakeContainer implements IContainer {

	private array $items = [];
	private array $flags = [];

	public function getServiceList(): array {
		return array_keys($this->items);
	}

	public function set(string $name, $classDefinition, $flags = 0): IContainer {
		$this->items[$name] = $classDefinition;
		$this->flags[$name] = (int)$flags;
		return $this;
	}

	public function remove(string $name) {
		unset($this->items[$name], $this->flags[$name]);
	}

	public function has(string $name): bool {
		return array_key_exists($name, $this->items);
	}

	public function get(string $name) {
		return $this->items[$name] ?? null;
	}

	public function getFlags(string $name): ?int {
		return $this->flags[$name] ?? null;
	}

}
