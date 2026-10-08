<?php declare(strict_types=1);

namespace ClientStack\Test\Service;

use Base3\Api\IModuleRegistry;
use ClientStack\Dto\AssetFile;
use ClientStack\Dto\LogicalAsset;
use ClientStack\Service\DefaultAssetService;
use PHPUnit\Framework\TestCase;

class DefaultAssetServiceTest extends TestCase {

	private string $testModuleDir;
	private IModuleRegistry $moduleRegistry;

	protected function setUp(): void {
		$this->testModuleDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base3_clientstack_' . uniqid('', true);
		@mkdir($this->testModuleDir . '/local', 0777, true);

		$json = [
			'unittestasset' => [
				'default' => true,
				'files' => [
					['path' => '/assets/test/unit.js', 'type' => 'js', 'version' => '1.2.3'],
					['path' => '/assets/test/unit.css', 'type' => 'css']
				]
			]
		];

		file_put_contents($this->testModuleDir . '/local/assets.json', json_encode($json, JSON_PRETTY_PRINT));

		$modulePath = $this->testModuleDir;
		$this->moduleRegistry = new class($modulePath) implements IModuleRegistry {
			public function __construct(private readonly string $modulePath) {}
			public function getModuleNames(): array { return ['ZzClientStackTestPlugin']; }
			public function getModulePath(string $name): ?string { return $name === 'ZzClientStackTestPlugin' ? $this->modulePath : null; }
			public function requireModulePath(string $name): string {
				$path = $this->getModulePath($name);
				if ($path === null) throw new \RuntimeException('Module not found: ' . $name);
				return $path;
			}
		};
	}

	protected function tearDown(): void {
		@unlink($this->testModuleDir . '/local/assets.json');
		@rmdir($this->testModuleDir . '/local');
		@rmdir($this->testModuleDir);
	}

	public function testBuiltInAssetsAreRegistered(): void {
		$service = new DefaultAssetService($this->moduleRegistry);

		$this->assertNotNull($service->getAsset('assetloader'));
		$this->assertNotNull($service->getAsset('jquery'));
		$this->assertNotNull($service->getAsset('jqueryui'));
		$this->assertNotNull($service->getAsset('chart'));

		$keys = $service->getAssetKeys();
		$this->assertContains('assetloader', $keys);
		$this->assertContains('jquery', $keys);
	}

	public function testRegisterAssetAndGetAsset(): void {
		$service = new DefaultAssetService($this->moduleRegistry);

		$asset = new LogicalAsset('customasset', [
			new AssetFile('/assets/custom/custom.js', 'js')
		], false);

		$service->registerAsset($asset);

		$loaded = $service->getAsset('customasset');
		$this->assertInstanceOf(LogicalAsset::class, $loaded);
		$this->assertSame('customasset', $loaded->name);
	}

	public function testGetDefaultAssetsIncludesBuiltInsAndJsonAssets(): void {
		$service = new DefaultAssetService($this->moduleRegistry);

		$defaults = $service->getDefaultAssets();
		$this->assertNotEmpty($defaults);

		$defaultNames = array_map(fn($a) => $a->name, $defaults);
		$this->assertContains('assetloader', $defaultNames);
		$this->assertContains('jquery', $defaultNames);
		$this->assertContains('unittestasset', $defaultNames);
	}

	public function testLoadsPluginAssetsFromJson(): void {
		$service = new DefaultAssetService($this->moduleRegistry);

		$asset = $service->getAsset('unittestasset');
		$this->assertNotNull($asset);
		$this->assertSame('unittestasset', $asset->name);
		$this->assertTrue($asset->isDefault);
		$this->assertIsArray($asset->files);
		$this->assertCount(2, $asset->files);
	}
}
