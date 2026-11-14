<?php

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

namespace Base3\ClientStack\Api;

use Base3\ClientStack\Dto\LogicalAsset;

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

