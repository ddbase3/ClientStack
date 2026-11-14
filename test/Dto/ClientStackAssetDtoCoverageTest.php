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

namespace Base3\ClientStack\Test\Dto;

use Base3\ClientStack\Dto\AssetFile;
use Base3\ClientStack\Dto\LogicalAsset;
use PHPUnit\Framework\TestCase;

final class ClientStackAssetDtoCoverageTest extends TestCase {

	public function testAssetFileHasDefaults(): void {
		$file = new AssetFile('/bundle.js');
		self::assertSame('/bundle.js', $file->path);
		self::assertSame('js', $file->type);
		self::assertNull($file->version);
	}

	public function testAssetFileRetainsProvidedFields(): void {
		$file = new AssetFile('/styles.css', 'css', '2026.11');
		self::assertSame('/styles.css', $file->path);
		self::assertSame('css', $file->type);
		self::assertSame('2026.11', $file->version);
	}

	public function testAssetFilePropertiesAreMutable(): void {
		$file = new AssetFile('/old.js');
		$file->path = '/new.js';
		$file->type = 'css';
		$file->version = '2';
		self::assertSame(['/new.js', 'css', '2'], [$file->path, $file->type, $file->version]);
	}

	public function testLogicalAssetRetainsEmptyFileCollectionAndDefaults(): void {
		$asset = new LogicalAsset('empty', []);
		self::assertSame('empty', $asset->name);
		self::assertSame([], $asset->files);
		self::assertFalse($asset->isDefault);
		self::assertNull($asset->version);
	}

	public function testLogicalAssetRetainsChildInstancesAndVersions(): void {
		$script = new AssetFile('/bundle.js', 'js', '5');
		$asset = new LogicalAsset('bundle', [$script], true, 'v5');
		self::assertSame([$script], $asset->files);
		self::assertTrue($asset->isDefault);
		self::assertSame('v5', $asset->version);
	}

	public function testLogicalAssetAllowsUpdatingDefaultsAndFiles(): void {
		$asset = new LogicalAsset('bundle', []);
		$file = new AssetFile('/new.css', 'css');
		$asset->files[] = $file;
		$asset->isDefault = true;
		self::assertSame([$file], $asset->files);
		self::assertTrue($asset->isDefault);
	}
}
