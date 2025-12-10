<?php declare(strict_types=1);

namespace ClientStack\Test\Service;

use PHPUnit\Framework\TestCase;
use ClientStack\Service\DefaultAssetService;
use ClientStack\Dto\LogicalAsset;
use ClientStack\Dto\AssetFile;

class DefaultAssetServiceTest extends TestCase {

	private string $testPluginDir;

	protected function setUp(): void {
		// Create a temporary plugin with a local/assets.json so DefaultAssetService can discover it
		$this->testPluginDir = DIR_PLUGIN . 'ZzClientStackTestPlugin';

		@mkdir($this->testPluginDir . '/local', 0777, true);

		$json = [
			'unittestasset' => [
				'default' => true,
				'files' => [
					['path' => '/assets/test/unit.js', 'type' => 'js', 'version' => '1.2.3'],
					['path' => '/assets/test/unit.css', 'type' => 'css']
				]
			]
		];

		file_put_contents($this->testPluginDir . '/local/assets.json', json_encode($json, JSON_PRETTY_PRINT));
	}

	protected function tearDown(): void {
		// Best-effort cleanup
		@unlink($this->testPluginDir . '/local/assets.json');
		@rmdir($this->testPluginDir . '/local');
		@rmdir($this->testPluginDir);
	}

	public function testBuiltInAssetsAreRegistered(): void {
		$service = new DefaultAssetService();

		$this->assertNotNull($service->getAsset('assetloader'));
		$this->assertNotNull($service->getAsset('jquery'));
		$this->assertNotNull($service->getAsset('jqueryui'));
		$this->assertNotNull($service->getAsset('chart'));

		$keys = $service->getAssetKeys();
		$this->assertContains('assetloader', $keys);
		$this->assertContains('jquery', $keys);
	}

	public function testRegisterAssetAndGetAsset(): void {
		$service = new DefaultAssetService();

		$asset = new LogicalAsset('customasset', [
			new AssetFile('/assets/custom/custom.js', 'js')
		], false);

		$service->registerAsset($asset);

		$loaded = $service->getAsset('customasset');
		$this->assertInstanceOf(LogicalAsset::class, $loaded);
		$this->assertSame('customasset', $loaded->name);
	}

	public function testGetDefaultAssetsIncludesBuiltInsAndJsonAssets(): void {
		$service = new DefaultAssetService();

		$defaults = $service->getDefaultAssets();
		$this->assertNotEmpty($defaults);

		$defaultNames = array_map(fn($a) => $a->name, $defaults);

		// Built-in defaults
		$this->assertContains('assetloader', $defaultNames);
		$this->assertContains('jquery', $defaultNames);

		// From our temp plugin assets.json
		$this->assertContains('unittestasset', $defaultNames);
	}

	public function testLoadsPluginAssetsFromJson(): void {
		$service = new DefaultAssetService();

		$asset = $service->getAsset('unittestasset');
		$this->assertNotNull($asset);
		$this->assertSame('unittestasset', $asset->name);
		$this->assertTrue($asset->isDefault);

		$this->assertIsArray($asset->files);
		$this->assertCount(2, $asset->files);
	}

}
