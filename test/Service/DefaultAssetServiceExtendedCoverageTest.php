<?php declare(strict_types=1);

namespace ClientStack\Test\Service;

use Base3\Api\IModuleRegistry;
use ClientStack\Dto\AssetFile;
use ClientStack\Dto\LogicalAsset;
use ClientStack\Service\DefaultAssetService;
use PHPUnit\Framework\TestCase;

final class DefaultAssetServiceExtendedCoverageTest extends TestCase {

	private string $directory = '';

	protected function tearDown(): void {
		if ($this->directory !== '') {
			@unlink($this->directory . '/local/assets.json');
			@rmdir($this->directory . '/local');
			@rmdir($this->directory);
		}
	}

	private function service(?string $json = null, bool $missingModule = false): DefaultAssetService {
		$this->directory = sys_get_temp_dir() . '/clientstack-unit-' . bin2hex(random_bytes(8));
		mkdir($this->directory . '/local', 0700, true);
		if ($json !== null) file_put_contents($this->directory . '/local/assets.json', $json);
		$registry = $this->createStub(IModuleRegistry::class);
		$registry->method('getModuleNames')->willReturn(['unknown', 'owned']);
		$registry->method('getModulePath')->willReturnCallback(
			fn(string $module): ?string => $module === 'owned' && !$missingModule ? $this->directory : null
		);
		return new DefaultAssetService($registry);
	}

	public function testBaseAssetsAreRegisteredInFixedOrder(): void {
		$service = $this->service();
		self::assertSame(['assetloader', 'jquery', 'jqueryui', 'dbdesigner', 'jquerydatatable', 'chart'], $service->getAssetKeys());
		self::assertCount(6, $service->getAllAssets());
		self::assertSame(['assetloader', 'jquery'], array_keys($service->getDefaultAssets()));
	}

	public function testMissingModulePathAndMissingJsonAreIgnored(): void {
		$service = $this->service(null, true);
		self::assertCount(6, $service->getAllAssets());
		self::assertNull($service->getAsset('missing'));
	}

	public function testMalformedJsonDoesNotRegisterInvalidAssets(): void {
		$service = $this->service('{invalid');
		self::assertCount(6, $service->getAllAssets());
		self::assertNull($service->getAsset('custom'));
	}

	public function testValidJsonFilesRetainTypesVersionsAndDefaultMarker(): void {
		$service = $this->service(json_encode([
			'custom' => ['default' => true, 'files' => [
				['path' => '/custom.js', 'type' => 'js', 'version' => '5'],
				['path' => '/custom.css', 'type' => 'css']
			]]
		], JSON_THROW_ON_ERROR));
		$asset = $service->getAsset('custom');
		self::assertInstanceOf(LogicalAsset::class, $asset);
		self::assertTrue($asset->isDefault);
		self::assertSame('/custom.js', $asset->files[0]->path);
		self::assertSame('js', $asset->files[0]->type);
		self::assertSame('5', $asset->files[0]->version);
		self::assertSame('css', $asset->files[1]->type);
		self::assertNull($asset->files[1]->version);
		self::assertContains('custom', array_keys($service->getDefaultAssets()));
	}

	public function testJsonAssetMayDeliberatelyHaveNoFiles(): void {
		$service = $this->service(json_encode(['empty' => []], JSON_THROW_ON_ERROR));
		$asset = $service->getAsset('empty');
		self::assertInstanceOf(LogicalAsset::class, $asset);
		self::assertSame([], $asset->files);
		self::assertFalse($asset->isDefault);
	}

	public function testLaterRegistrationReplacesAnAssetWithoutDuplicateKeys(): void {
		$service = $this->service();
		$replacement = new LogicalAsset('jquery', [new AssetFile('/other.js')], false);
		$service->registerAsset($replacement);
		self::assertSame($replacement, $service->getAsset('jquery'));
		self::assertCount(6, $service->getAssetKeys());
		self::assertSame(['assetloader'], array_keys($service->getDefaultAssets()));
	}

	public function testJsonModuleCanOverrideBuiltInAsset(): void {
		$service = $this->service(json_encode([
			'jquery' => ['default' => false, 'files' => [['path' => '/new.js', 'type' => 'js']]]
		], JSON_THROW_ON_ERROR));
		self::assertSame('/new.js', $service->getAsset('jquery')->files[0]->path);
		self::assertCount(6, $service->getAllAssets());
		self::assertFalse($service->getAsset('jquery')->isDefault);
	}

	public function testGetAllAssetsIsAListOfActualObjects(): void {
		$service = $this->service();
		$registered = new LogicalAsset('later', [new AssetFile('/later.js')]);
		$service->registerAsset($registered);
		$all = $service->getAllAssets();
		self::assertCount(7, $all);
		self::assertSame($registered, $all[6]);
		self::assertSame('later', $service->getAssetKeys()[6]);
	}
}
