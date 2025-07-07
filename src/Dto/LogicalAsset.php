<?php

namespace ClientStack\Dto;

use ClientStack\Dto\AssetFile;

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

