<?php declare(strict_types=1);

namespace ClientStack\FileManager;

use Base3\Api\IRequest;
use Base3\Translation\Api\ITranslation;
use InvalidArgumentException;
use ResourceFoundation\Api\IFileStorage;
use ResourceFoundation\Api\IManagedFileStorageService;
use RuntimeException;
use Throwable;

/** Host-independent HTTP implementation of the existing ClientStack FileManager protocol. */
final class FileManagerHttpService {

	public const DEFAULT_MAX_FILE_SIZE = 50 * 1024 * 1024;

	public function __construct(
		private readonly IManagedFileStorageService $storageService,
		private readonly FileUploadService $uploadService,
		private readonly IRequest $request,
		private readonly ITranslation $translation
	) {}

	/** @return array<string,string> Action URLs for the shipped HttpFileManagerAdapter. */
	public static function buildEndpoints(string $baseUrl, string $parameter = 'fm_action'): array {
		$actions = ['list', 'stat', 'mkdir', 'delete', 'rmdir', 'copy', 'move', 'uploadStart',
			'uploadChunk', 'uploadStatus', 'uploadFinish', 'uploadAbort', 'download'];
		$result = [];
		foreach ($actions as $action) {
			$result[$action] = $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?')
				. rawurlencode($parameter) . '=' . rawurlencode($action);
		}
		return $result;
	}

	/**
	 * Handles a request only AFTER the host has authorized actor, owner and operation.
	 * No client-supplied actor or owner identifier is trusted here.
	 *
	 * @return string JSON response body or downloaded file bytes.
	 */
	public function handle(string $action, string $ownerGroup, string $ownerName, string $mode, string $userId, int $maxFileSize = self::DEFAULT_MAX_FILE_SIZE): string {
		try {
			if ($action === 'download') return $this->download($ownerGroup, $ownerName, $mode);
			if (strtoupper((string)$this->request->server('REQUEST_METHOD', 'GET')) !== 'POST') {
				http_response_code(405);
				throw new InvalidArgumentException('POST required.');
			}
			$body = $this->request->getJsonBody();
			$payload = match ($action) {
				'list' => $this->list($ownerGroup, $ownerName, $mode, $body),
				'stat' => $this->stat($ownerGroup, $ownerName, $mode, $body),
				'mkdir' => $this->mkdir($ownerGroup, $ownerName, $mode, $body),
				'delete' => $this->delete($ownerGroup, $ownerName, $mode, $body),
				'rmdir' => $this->rmdir($ownerGroup, $ownerName, $mode, $body),
				'copy' => $this->copy($ownerGroup, $ownerName, $mode, $body),
				'move' => $this->move($ownerGroup, $ownerName, $mode, $body),
				'uploadStart' => $this->uploadService->start($ownerGroup, $ownerName, $mode, $userId, $maxFileSize, $body),
				'uploadChunk' => $this->uploadService->saveChunk($ownerGroup, $ownerName, $mode, $userId, $this->request->allPost(), $this->request->allFiles()),
				'uploadStatus' => $this->uploadService->status($ownerGroup, $ownerName, $mode, $userId, trim((string)($body['uploadId'] ?? ''))),
				'uploadFinish' => $this->uploadService->finish($ownerGroup, $ownerName, $mode, $userId, trim((string)($body['uploadId'] ?? ''))),
				'uploadAbort' => $this->uploadService->abort($ownerGroup, $ownerName, $mode, $userId, trim((string)($body['uploadId'] ?? ''))),
				default => throw new InvalidArgumentException('Unknown FileManager action.')
			};
		} catch (InvalidArgumentException $error) {
			if (http_response_code() < 400) http_response_code(400);
			$payload = ['message' => $this->translation->translate('FileManager', 'filemanager', 'invalid_request')];
		} catch (Throwable $error) {
			if (http_response_code() < 400) http_response_code(500);
			$payload = ['message' => $this->translation->translate('FileManager', 'filemanager', 'request_failed')];
		}
		if (!headers_sent()) {
			header('Content-Type: application/json; charset=utf-8');
			header('Cache-Control: no-store');
		}
		return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
	}

	private function list(string $group, string $name, string $mode, array $body): array {
		$path = self::normalizePath((string)($body['path'] ?? ''));
		$storage = $this->storageService->openOrCreate($group, $name, $mode);
		$items = [];
		foreach ($storage->list($path) as $item) {
			if (!is_array($item) || !isset($item['name'], $item['type'])) continue;
			$child = self::joinPath($path, (string)$item['name']);
			$items[] = self::descriptor($child, (string)$item['name'], $item);
		}
		return [
			'items' => $items,
			'path' => $path,
			'containerId' => hash('sha256', $group . "\0" . $name . "\0" . $mode)
		];
	}

	private function stat(string $group, string $name, string $mode, array $body): array {
		$path = self::normalizePath((string)($body['path'] ?? ''));
		$descriptor = self::statDescriptor($this->storageService->openOrCreate($group, $name, $mode), $path);
		if ($descriptor === null) {
			http_response_code(404);
			throw new RuntimeException('File or directory not found.');
		}
		return $descriptor;
	}

	private function mkdir(string $group, string $name, string $mode, array $body): array {
		$path = self::normalizePath((string)($body['path'] ?? ''));
		if ($path === '' || !$this->storageService->openOrCreate($group, $name, $mode)->mkdir($path)) {
			throw new RuntimeException('Directory could not be created.');
		}
		return ['success' => true];
	}

	private function delete(string $group, string $name, string $mode, array $body): array {
		$path = self::normalizePath((string)($body['path'] ?? ''));
		if ($path === '' || !$this->storageService->openOrCreate($group, $name, $mode)->delete($path)) {
			throw new RuntimeException('File could not be deleted.');
		}
		return ['success' => true];
	}

	private function rmdir(string $group, string $name, string $mode, array $body): array {
		$path = self::normalizePath((string)($body['path'] ?? ''));
		if ($path === '' || !$this->storageService->openOrCreate($group, $name, $mode)->rmdir($path)) {
			throw new RuntimeException('Directory could not be deleted.');
		}
		return ['success' => true];
	}

	private function copy(string $group, string $name, string $mode, array $body): array {
		$source = self::normalizePath((string)($body['sourcePath'] ?? ''));
		$target = self::normalizePath((string)($body['targetPath'] ?? ''));
		if ($source === '' || $target === '' || !$this->storageService->openOrCreate($group, $name, $mode)->copy($source, $target)) {
			throw new RuntimeException('File could not be copied.');
		}
		return ['success' => true];
	}

	private function move(string $group, string $name, string $mode, array $body): array {
		$source = self::normalizePath((string)($body['sourcePath'] ?? ''));
		$target = self::normalizePath((string)($body['targetPath'] ?? ''));
		if ($source === '' || $target === '' || !$this->storageService->openOrCreate($group, $name, $mode)->move($source, $target)) {
			throw new RuntimeException('File could not be moved.');
		}
		return ['success' => true];
	}

	private function download(string $group, string $name, string $mode): string {
		$path = self::normalizePath((string)$this->request->get('path', ''));
		if ($path === '') {
			http_response_code(404);
			return '';
		}
		$storage = $this->storageService->openOrCreate($group, $name, $mode);
		$stat = $storage->stat($path);
		if ($stat === null || (string)($stat['type'] ?? '') !== 'file') {
			http_response_code(404);
			return '';
		}
		$content = $storage->read($path);
		$filename = basename($path);
		$fallback = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'download';
		if (!headers_sent()) {
			header('Content-Type: application/octet-stream');
			header('Content-Length: ' . strlen($content));
			header('Content-Disposition: attachment; filename="' . $fallback . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
			header('X-Content-Type-Options: nosniff');
			header('Cache-Control: no-store');
		}
		return $content;
	}

	/** Validate untrusted virtual paths; never normalize traversal into an accepted path. */
	public static function normalizePath(string $path): string {
		$path = ltrim(str_replace('\\', '/', trim($path)), '/');
		if ($path === '' || $path === '.') return '';
		$parts = [];
		foreach (explode('/', $path) as $segment) {
			if ($segment === '' || $segment === '.') continue;
			if ($segment === '..' || str_contains($segment, "\0")) {
				throw new InvalidArgumentException('Invalid FileManager path.');
			}
			$parts[] = $segment;
		}
		return implode('/', $parts);
	}

	public static function joinPath(string $path, string $name): string {
		$base = self::normalizePath($path);
		$child = self::normalizePath($name);
		return $base === '' ? $child : ($child === '' ? $base : $base . '/' . $child);
	}

	public static function statDescriptor(IFileStorage $storage, string $path): ?array {
		$path = self::normalizePath($path);
		$data = $storage->stat($path);
		return $data === null ? null : self::descriptor($path, $path === '' ? '/' : basename($path), $data);
	}

	public static function descriptor(string $path, string $name, array $data): array {
		$type = (string)($data['type'] ?? '');
		if (!in_array($type, ['dir', 'file'], true)) throw new RuntimeException('Unsupported file storage type.');
		return [
			'id' => $path === '' ? '/' : $path,
			'name' => $name,
			'path' => $path,
			'kind' => $type === 'dir' ? 'directory' : 'file',
			'size' => $type === 'file' ? (int)($data['size'] ?? 0) : null,
			'mimeType' => (string)($data['mime_type'] ?? ''),
			'modifiedAt' => (string)($data['modified'] ?? '')
		];
	}
}
