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

namespace Base3\ClientStack\Dto;

use Base3\ClientStack\Dto\AssetFile;

/**
 * Represents a logical asset composed of one or more files.
 */
class LogicalAsset {
	public function __construct(
		public string $name,
		public array $files, // Array of AssetFile
		public bool $isDefault = false,
		public ?string $version = null
	) {}
}

