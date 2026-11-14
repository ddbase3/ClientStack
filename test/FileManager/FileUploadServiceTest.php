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

use Base3\ClientStack\FileManager\FileUploadService;
use FilesystemIterator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Base3\ResourceFoundation\Api\IFileStorage;
use Base3\ResourceFoundation\Api\IManagedFileStorageService;
use RuntimeException;

final class FileUploadServiceTest extends TestCase {

	private string $root;

	protected function setUp(): void {
		$this->root = sys_get_temp_dir() . '/base3-clientstack-upload-' . bin2hex(random_bytes(8));
	}

	protected function tearDown(): void {
		if (!is_dir($this->root)) return;
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ($iterator as $file) {
			$file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
		}
		rmdir($this->root);
	}

	public function testUploadPersistsThroughManagedStorageAndRemovesChunks(): void {
		$storage = $this->createMock(IFileStorage::class);
		$storage->expects(self::once())->method('write')->with('docs/report.txt', 'hello')->willReturn(true);
		$storage->expects(self::once())->method('stat')->with('docs/report.txt')->willReturn([
			'type' => 'file', 'size' => 5, 'modified' => '2026-11-05T00:00:00Z'
		]);
		$manager = $this->createMock(IManagedFileStorageService::class);
		$manager->expects(self::once())->method('openOrCreate')
			->with('chatbot', 'default', IManagedFileStorageService::MODE_COLLECTION)
			->willReturn($storage);
		$uploads = new FileUploadService($manager, $this->root . '/uploads');
		$start = $uploads->start('chatbot', 'default', IManagedFileStorageService::MODE_COLLECTION,
			'actor-1', 20, ['name' => 'report.txt', 'path' => 'docs', 'size' => 5, 'chunkSize' => 5, 'totalChunks' => 1]);
		$uploadId = $start['uploadId'];
		$chunkFile = $this->root . '/tempfile';
		file_put_contents($chunkFile, 'hello');
		$chunk = $uploads->saveChunk('chatbot', 'default', IManagedFileStorageService::MODE_COLLECTION,
			'actor-1', ['uploadId' => $uploadId, 'index' => '0'],
			['chunk' => ['error' => UPLOAD_ERR_OK, 'tmp_name' => $chunkFile]]);
		self::assertTrue($chunk['received']);
		self::assertSame([0], $uploads->status('chatbot', 'default', IManagedFileStorageService::MODE_COLLECTION,
			'actor-1', $uploadId)['uploadedChunks']);
		$descriptor = $uploads->finish('chatbot', 'default', IManagedFileStorageService::MODE_COLLECTION, 'actor-1', $uploadId);
		self::assertSame('docs/report.txt', $descriptor['path']);
		self::assertFalse(is_dir($this->root . '/uploads/' . hash('sha256', "chatbot\0default") . '/' . $uploadId));
	}

	public function testUploadCannotBeCompletedByDifferentActor(): void {
		$manager = $this->createMock(IManagedFileStorageService::class);
		$uploads = new FileUploadService($manager, $this->root . '/uploads');
		$start = $uploads->start('chatbot', 'default', IManagedFileStorageService::MODE_COLLECTION,
			'actor-1', 20, ['name' => 'report.txt', 'size' => 0, 'chunkSize' => 5, 'totalChunks' => 1]);
		$this->expectException(RuntimeException::class);
		$uploads->status('chatbot', 'default', IManagedFileStorageService::MODE_COLLECTION,
			'actor-2', $start['uploadId']);
	}

	public function testUploadCannotUseExecutableFilename(): void {
		$manager = $this->createMock(IManagedFileStorageService::class);
		$uploads = new FileUploadService($manager, $this->root . '/uploads');
		$this->expectException(InvalidArgumentException::class);
		$uploads->start('chatbot', 'default', IManagedFileStorageService::MODE_COLLECTION,
			'actor-1', 20, ['name' => 'backdoor.php', 'size' => 3, 'chunkSize' => 3, 'totalChunks' => 1]);
	}
}
