<?php

namespace ClientStack\Api;

use ClientStack\Dto\LogicalAsset;

/**
 * Interface for managing and retrieving logical assets.
 */
interface IAssetService {

	/**
	 * Registers a new logical asset.
	 *
	 * @param LogicalAsset $asset The asset to register.
	 * @return void
	 */
	public function registerAsset(LogicalAsset $asset): void;

	/**
	 * Retrieves a registered asset by its name.
	 *
	 * @param string $name The name of the asset.
	 * @return LogicalAsset|null The asset if found, or null otherwise.
	 */
	public function getAsset(string $name): ?LogicalAsset;

	/**
	 * Returns an array of all registered asset keys (names).
	 *
	 * @return array An array of asset names.
	 */
	public function getAssetKeys(): array;

	/**
	 * Returns all assets marked as default.
	 *
	 * @return array An array of default LogicalAsset objects.
	 */
	public function getDefaultAssets(): array;

	/**
	 * Returns all registered logical assets.
	 *
	 * @return array An array of all LogicalAsset objects.
	 */
	public function getAllAssets(): array;
}

