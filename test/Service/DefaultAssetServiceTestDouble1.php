<?php declare(strict_types=1);

namespace ClientStack\Test\Service;

use Base3\Api\IModuleRegistry;
use ClientStack\Dto\AssetFile;
use ClientStack\Dto\LogicalAsset;
use ClientStack\Service\DefaultAssetService;
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
