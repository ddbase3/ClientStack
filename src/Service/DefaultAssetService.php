<?php

namespace ClientStack\Service;

use ClientStack\Api\IAssetService;
use ClientStack\Dto\LogicalAsset;
use ClientStack\Dto\AssetFile;
use Base3\Api\IAssetResolver;

/**
 * Default implementation of the asset service interface.
 * Handles registration and loading of logical assets.
 */
class DefaultAssetService implements IAssetService {
	private array $assets = [];

	public function __construct() {
		$this->registerBuiltInAssets();
		$this->loadPluginAssets();
	}

	/**
	 * Registers a logical asset.
	 *
	 * @param LogicalAsset $asset The asset to register.
	 * @return void
	 */
	public function registerAsset(LogicalAsset $asset): void {
		$this->assets[$asset->name] = $asset;
	}

	/**
	 * Retrieves an asset by name.
	 *
	 * @param string $name The name of the asset.
	 * @return LogicalAsset|null The asset, or null if not found.
	 */
	public function getAsset(string $name): ?LogicalAsset {
		return $this->assets[$name] ?? null;
	}

	/**
	 * Returns the names of all registered assets.
	 *
	 * @return array List of asset names.
	 */
	public function getAssetKeys(): array {
		return array_keys($this->assets);
	}

	/**
	 * Returns all assets marked as default.
	 *
	 * @return array List of default LogicalAsset objects.
	 */
	public function getDefaultAssets(): array {
		return array_filter($this->assets, fn($a) => $a->isDefault);
	}

	/**
	 * Returns all registered assets.
	 *
	 * @return array List of all LogicalAsset objects.
	 */
	public function getAllAssets(): array {
		return array_values($this->assets);
	}

	/**
	 * Registers core/built-in assets.
	 *
	 * @return void
	 */
	private function registerBuiltInAssets(): void {
		$this->registerAsset(new LogicalAsset('assetloader', [
			new AssetFile(DIR_PLUGIN . 'assets/assetloader/assetloader.min.js', 'js')
		], true));

		$this->registerAsset(new LogicalAsset('jquery', [
			new AssetFile(DIR_PLUGIN . 'assets/jquery/jquery-3.4.1.min.js', 'js')
		]));

		$this->registerAsset(new LogicalAsset('jqueryui', [
			new AssetFile(DIR_PLUGIN . 'assets/jqueryui/jquery-ui.min.js', 'js'),
			new AssetFile(DIR_PLUGIN . 'assets/jqueryui/jquery-ui.min.css', 'css')
		]));

		$this->registerAsset(new LogicalAsset('dbdesigner', [
			new AssetFile(DIR_PLUGIN . 'assets/dbdesigner/dbdesigner.js', 'js')
		]));

		$this->registerAsset(new LogicalAsset('jquerydatatable', [
			new AssetFile(DIR_PLUGIN . 'assets/jquerydatatable/jquerydatatable.js', 'js'),
			new AssetFile(DIR_PLUGIN . 'assets/jquerydatatable/jquerydatatable.css', 'css')
		]));

		$this->registerAsset(new LogicalAsset('chart', [
			new AssetFile(DIR_PLUGIN . 'assets/chart/chart.js', 'js')
		]));
	}

	/**
	 * Loads plugin-defined assets from JSON files.
	 *
	 * @return void
	 */
	private function loadPluginAssets(): void {
		foreach (glob(DIR_PLUGIN . '*/local/assets.json') as $jsonPath) {
			$this->registerAssetsFromJson($jsonPath);
		}
	}

	/**
	 * Registers assets defined in a JSON file.
	 *
	 * @param string $jsonPath Path to the JSON file.
	 * @return void
	 */
	private function registerAssetsFromJson(string $jsonPath): void {
		$data = json_decode(file_get_contents($jsonPath), true);

		foreach ($data as $name => $config) {
			$files = [];

			foreach ($config['files'] ?? [] as $file) {
				$files[] = new AssetFile(
					$file['path'],
					$file['type'],
					$file['version'] ?? null
				);
			}

			$this->registerAsset(new LogicalAsset(
				$name,
				$files,
				$config['default'] ?? false
			));
		}
	}
}

