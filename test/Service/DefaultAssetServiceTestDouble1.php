<?php declare(strict_types=1);

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

namespace Base3\ClientStack\Test\Service;

use Base3\CoreFoundation\Api\IModuleRegistry;
use Base3\ClientStack\Dto\AssetFile;
use Base3\ClientStack\Dto\LogicalAsset;
use Base3\ClientStack\Service\DefaultAssetService;
use PHPUnit\Framework\TestCase;

final class DefaultAssetServiceTestDouble1 implements IModuleRegistry {
			public function __construct(private readonly string $modulePath) {}
			public function getModuleNames(): array { return ['ZzClientStackTestPlugin']; }
			public function getModulePath(string $name): ?string { return $name === 'ZzClientStackTestPlugin' ? $this->modulePath : null; }
			public function requireModulePath(string $name): string {
				$path = $this->getModulePath($name);
				if ($path === null) throw new \RuntimeException('Module not found: ' . $name);
				return $path;
			}
		}
