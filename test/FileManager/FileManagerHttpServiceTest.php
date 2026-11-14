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

namespace Base3\ClientStack\Test\FileManager;

use Base3\ClientStack\FileManager\FileManagerHttpService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Base3\ResourceFoundation\Api\IFileStorage;

final class FileManagerHttpServiceTest extends TestCase {

	public function testEndpointNamesMatchHttpAdapter(): void {
		$endpoints = FileManagerHttpService::buildEndpoints('https://example.test/index.php?name=files');
		self::assertCount(13, $endpoints);
		self::assertSame('https://example.test/index.php?name=files&fm_action=uploadChunk', $endpoints['uploadChunk']);
		self::assertSame('https://example.test/index.php?name=files&fm_action=download', $endpoints['download']);
	}

	public function testUntrustedParentTraversalIsRejected(): void {
		$this->expectException(InvalidArgumentException::class);
		FileManagerHttpService::normalizePath('folder/../confidential.txt');
	}

	public function testStorageDescriptorMatchesClientContract(): void {
		$storage = $this->createMock(IFileStorage::class);
		$storage->expects(self::once())->method('stat')->with('file.txt')->willReturn([
			'type' => 'file', 'size' => 9, 'modified' => '2026-11-05T00:00:00Z'
		]);
		self::assertSame([
			'id' => 'file.txt', 'name' => 'file.txt', 'path' => 'file.txt',
			'kind' => 'file', 'size' => 9, 'mimeType' => '', 'modifiedAt' => '2026-11-05T00:00:00Z'
		], FileManagerHttpService::statDescriptor($storage, 'file.txt'));
	}
}
