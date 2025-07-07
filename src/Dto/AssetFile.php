<?php

namespace ClientStack\Dto;

/**
 * Represents a single file within a logical asset.
 */
class AssetFile {
	public function __construct(
		public string $path,
		public string $type = 'js'
	) {}
}

