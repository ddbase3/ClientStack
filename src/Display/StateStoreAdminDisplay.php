<?php declare(strict_types=1);

namespace ClientStack\Display;

use Base3\Api\IAssetResolver;
use Base3\Api\IDisplay;
use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\Database\Api\IDatabase;
use Base3\LinkTarget\Api\ILinkTargetService;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class StateStoreAdminDisplay implements IDisplay {

	public function __construct(
		private readonly IRequest $request,
		private readonly IMvcView $view,
		private readonly IAssetResolver $assetResolver,
		private readonly IDatabase $database,
		private readonly ILinkTargetService $linkTargetService
	) {}

	public static function getName(): string {
		return 'statestoreadmindisplay';
	}

	public function setData($data) {
		// no-op
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		$out = strtolower((string) $out);

		if($out === 'json') {
			return $this->handleJson($final);
		}

		return $this->handleHtml();
	}

	public function getHelp(): string {
		return 'Help of StateStoreAdminDisplay';
	}

	private function handleHtml(): string {
		$this->view->setPath(DIR_PLUGIN . 'ClientStack');
		$this->view->setTemplate('Display/StateStoreAdminDisplay.php');

		$this->view->assign(
			'service',
			$this->linkTargetService->getLink(
				[
					'name' => self::getName(),
					'out' => 'json'
				]
			)
		);

		$this->view->assign(
			'modularGridCssUrl',
			$this->assetResolver->resolve('plugin/ClientStack/assets/modulargrid/styles/modulargrid.css')
		);

		$this->view->assign(
			'modularGridJsUrl',
			$this->assetResolver->resolve('plugin/ClientStack/assets/modulargrid/index.js')
		);

		$this->view->assign('typeOptions', $this->getTypeOptions());
		$this->view->assign('expiresStateOptions', $this->getExpiresStateOptions());

		return $this->view->loadTemplate();
	}

	private function handleJson(bool $final = false): string {
		try {
			$response = $this->buildJsonResponse();
		}
		catch(Throwable $e) {
			$response = [
				'ok' => false,
				'error' => 'State store request failed.',
				'details' => $e->getMessage(),
			];
		}

		if($final && !headers_sent()) {
			header('Content-Type: application/json; charset=utf-8');
		}

		return (string) json_encode(
			$response,
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function buildJsonResponse(): array {
		$payload = $this->request->getJsonBody();

		if(!is_array($payload)) {
			$payload = [];
		}

		$request = $this->normalizeRequest($payload);

		if($request['mode'] === 'record') {
			return $this->buildRecordResponse($request['id']);
		}

		if($request['mode'] === 'save') {
			return $this->buildSaveResponse($payload);
		}

		if($request['mode'] === 'delete') {
			return $this->buildDeleteResponse($request['id']);
		}

		return $this->buildPageResponse($request);
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>
	 */
	private function normalizeRequest(array $payload): array {
		$mode = 'page';
		$allowedModes = ['page', 'record', 'save', 'delete'];

		if(isset($payload['mode']) && is_string($payload['mode']) && in_array($payload['mode'], $allowedModes, true)) {
			$mode = $payload['mode'];
		}

		$page = isset($payload['page']) ? (int) $payload['page'] : 1;
		$page = max(1, $page);

		$pageSize = isset($payload['pageSize']) ? (int) $payload['pageSize'] : 50;
		$pageSize = max(1, min(250, $pageSize));

		$search = '';
		if(isset($payload['search']) && is_scalar($payload['search'])) {
			$search = trim((string) $payload['search']);
		}

		$id = '';
		if(isset($payload['id']) && is_scalar($payload['id'])) {
			$id = trim((string) $payload['id']);
		}

		return [
			'mode' => $mode,
			'page' => $page,
			'pageSize' => $pageSize,
			'search' => $search,
			'id' => $id,
			'sort' => $this->normalizeSort($payload['sort'] ?? null),
			'filters' => $this->normalizeFilters($payload['filters'] ?? null),
		];
	}

	/**
	 * @param mixed $sortPayload
	 * @return array<string, string>
	 */
	private function normalizeSort(mixed $sortPayload): array {
		$allowedKeys = ['key', 'type', 'value_preview', 'updated_at', 'expires_at', 'expires_state'];

		$sort = [
			'key' => 'key',
			'dir' => 'asc',
			'type' => 'string',
		];

		if(!is_array($sortPayload) || count($sortPayload) === 0) {
			return $sort;
		}

		$first = reset($sortPayload);

		if(!is_array($first)) {
			return $sort;
		}

		$key = isset($first['key']) ? (string) $first['key'] : 'key';
		if(!in_array($key, $allowedKeys, true)) {
			$key = 'key';
		}

		$dir = isset($first['dir']) ? strtolower((string) $first['dir']) : 'asc';
		$dir = $dir === 'desc' ? 'desc' : 'asc';

		return [
			'key' => $key,
			'dir' => $dir,
			'type' => 'string',
		];
	}

	/**
	 * @param mixed $filtersPayload
	 * @return array<string, string>
	 */
	private function normalizeFilters(mixed $filtersPayload): array {
		$result = [
			'key' => '',
			'type' => '',
			'expires_state' => '',
		];

		if(!is_array($filtersPayload)) {
			return $result;
		}

		foreach(array_keys($result) as $key) {
			if(isset($filtersPayload[$key]) && is_scalar($filtersPayload[$key])) {
				$result[$key] = trim((string) $filtersPayload[$key]);
			}
		}

		if($result['type'] !== '' && !in_array($result['type'], $this->getAllowedTypes(), true)) {
			$result['type'] = '';
		}

		if($result['expires_state'] !== '' && !in_array($result['expires_state'], $this->getAllowedExpiresStates(), true)) {
			$result['expires_state'] = '';
		}

		return $result;
	}

	/**
	 * @param array<string, mixed> $request
	 * @return array<string, mixed>
	 */
	private function buildPageResponse(array $request): array {
		$rows = $this->loadRows();
		$filteredRows = [];

		foreach($rows as $row) {
			if(!$this->matchesSearch($row, $request['search'])) {
				continue;
			}

			if(!$this->matchesFilters($row, $request['filters'])) {
				continue;
			}

			$filteredRows[] = $row;
		}

		usort(
			$filteredRows,
			fn(array $a, array $b) => $this->compareRows($a, $b, $request['sort'])
		);

		$total = count($filteredRows);
		$page = (int) $request['page'];
		$pageSize = (int) $request['pageSize'];
		$totalPages = $pageSize > 0 ? (int) ceil($total / $pageSize) : 0;
		$offset = max(0, ($page - 1) * $pageSize);
		$data = array_slice($filteredRows, $offset, $pageSize);

		return [
			'ok' => true,
			'mode' => 'page',
			'data' => array_values($data),
			'groups' => [],
			'page' => $page,
			'pageSize' => $pageSize,
			'total' => $total,
			'totalPages' => $totalPages,
			'hasMore' => ($offset + $pageSize) < $total,
			'nextCursor' => null,
			'appliedSearch' => $request['search'],
			'appliedSort' => [$request['sort']],
			'appliedFilters' => $request['filters'],
			'appliedGroup' => [],
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function buildRecordResponse(string $id): array {
		$key = $this->decodeId($id);

		if($key === null) {
			return [
				'ok' => false,
				'mode' => 'record',
				'found' => false,
				'error' => 'Invalid state store id.',
				'record' => null,
			];
		}

		$row = $this->loadRow($key);

		if($row === null) {
			return [
				'ok' => false,
				'mode' => 'record',
				'found' => false,
				'error' => 'State store entry not found.',
				'record' => null,
			];
		}

		return [
			'ok' => true,
			'mode' => 'record',
			'found' => true,
			'record' => $row,
		];
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>
	 */
	private function buildSaveResponse(array $payload): array {
		$key = $this->decodeId($this->readString($payload, 'id'));
		$type = $this->normalizeValueType($this->readString($payload, 'type'));
		$rawValue = $payload['value'] ?? '';

		if($key === null) {
			return $this->buildErrorResponse('Invalid state store id.', 'save');
		}

		if($this->loadRow($key) === null) {
			return $this->buildErrorResponse('State store entry not found.', 'save');
		}

		try {
			$value = $this->decodeEditorValue($type, $rawValue);
			$storedValue = $this->encodeStoredValue($type, $value);
		}
		catch(Throwable $e) {
			return $this->buildErrorResponse($e->getMessage(), 'save');
		}

		try {
			$this->updateValue($key, $storedValue);
		}
		catch(Throwable $e) {
			return $this->buildErrorResponse('Failed to save state store value: ' . $e->getMessage(), 'save');
		}

		$row = $this->loadRow($key);

		return [
			'ok' => true,
			'mode' => 'save',
			'action' => 'saved',
			'record' => $row,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function buildDeleteResponse(string $id): array {
		$key = $this->decodeId($id);

		if($key === null) {
			return $this->buildErrorResponse('Invalid state store id.', 'delete');
		}

		if($this->loadRow($key) === null) {
			return $this->buildErrorResponse('State store entry not found.', 'delete');
		}

		try {
			$this->deleteValue($key);
		}
		catch(Throwable $e) {
			return $this->buildErrorResponse('Failed to delete state store entry: ' . $e->getMessage(), 'delete');
		}

		return [
			'ok' => true,
			'mode' => 'delete',
			'action' => 'deleted',
			'key' => $key,
		];
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function loadRows(): array {
		$sql = "
			SELECT
				`key`,
				`value`,
				`updated_at`,
				`expires_at`
			FROM
				`base3_statestore`
		";

		$rows = $this->database->multiQuery($sql);

		if(!is_array($rows)) {
			return [];
		}

		$result = [];

		foreach($rows as $row) {
			if(!is_array($row)) {
				continue;
			}

			$result[] = $this->normalizeRow($row);
		}

		return $result;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function loadRow(string $key): ?array {
		$sql = "
			SELECT
				`key`,
				`value`,
				`updated_at`,
				`expires_at`
			FROM
				`base3_statestore`
			WHERE
				`key` = " . $this->quote($key) . "
			LIMIT 1
		";

		$row = $this->database->singleQuery($sql);

		if(!is_array($row)) {
			return null;
		}

		return $this->normalizeRow($row);
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	private function normalizeRow(array $row): array {
		$key = $this->readRowString($row, 'key');
		$rawValue = $this->readRowString($row, 'value');
		$updatedAt = $this->readRowString($row, 'updated_at');
		$expiresAt = $this->readRowString($row, 'expires_at');
		$decoded = $this->decodeStoredValue($rawValue);

		$type = $decoded['is_json'] ? $this->detectValueType($decoded['value']) : 'raw';
		$valueText = $decoded['is_json']
			? $this->formatValueText($decoded['value'], $type)
			: $rawValue;

		$valuePreview = $this->shorten($valueText, 220);
		$expiresState = $this->detectExpiresState($expiresAt);

		return [
			'id' => $this->encodeId($key),
			'key' => $key,
			'type' => $type,
			'value_preview' => $valuePreview,
			'value_text' => $valueText,
			'value_edit' => $decoded['is_json']
				? $this->formatEditorValue($decoded['value'], $type)
				: $rawValue,
			'stored_value_raw' => $rawValue,
			'is_json' => $decoded['is_json'],
			'is_array' => $type === 'array',
			'is_scalar' => in_array($type, ['string', 'int', 'float', 'bool', 'null'], true),
			'updated_at' => $updatedAt,
			'expires_at' => $expiresAt,
			'expires_state' => $expiresState,
			'expires_label' => $this->formatExpiresLabel($expiresAt, $expiresState),
		];
	}

	/**
	 * @return array{is_json: bool, value: mixed}
	 */
	private function decodeStoredValue(string $rawValue): array {
		$value = json_decode($rawValue, true);

		if(json_last_error() === JSON_ERROR_NONE) {
			return [
				'is_json' => true,
				'value' => $value,
			];
		}

		return [
			'is_json' => false,
			'value' => $rawValue,
		];
	}

	private function updateValue(string $key, string $storedValue): void {
		$sql = "
			UPDATE
				`base3_statestore`
			SET
				`value` = " . $this->quote($storedValue) . ",
				`updated_at` = NOW()
			WHERE
				`key` = " . $this->quote($key) . "
			LIMIT 1
		";

		$this->database->multiQuery($sql);
	}

	private function deleteValue(string $key): void {
		$sql = "
			DELETE FROM
				`base3_statestore`
			WHERE
				`key` = " . $this->quote($key) . "
			LIMIT 1
		";

		$this->database->multiQuery($sql);
	}

	/**
	 * @param array<string, mixed> $a
	 * @param array<string, mixed> $b
	 * @param array<string, string> $sort
	 */
	private function compareRows(array $a, array $b, array $sort): int {
		$key = $sort['key'] ?? 'key';
		$dir = $sort['dir'] ?? 'asc';

		$aValue = (string) ($a[$key] ?? '');
		$bValue = (string) ($b[$key] ?? '');

		$result = strnatcasecmp($aValue, $bValue);

		if($result === 0) {
			$result = strnatcasecmp((string) ($a['key'] ?? ''), (string) ($b['key'] ?? ''));
		}

		return $dir === 'desc' ? -$result : $result;
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private function matchesSearch(array $row, string $search): bool {
		if($search === '') {
			return true;
		}

		$needle = $this->toLower($search);
		$haystack = $this->toLower(
			implode(
				' ',
				[
					(string) ($row['key'] ?? ''),
					(string) ($row['type'] ?? ''),
					(string) ($row['value_text'] ?? ''),
					(string) ($row['stored_value_raw'] ?? ''),
					(string) ($row['updated_at'] ?? ''),
					(string) ($row['expires_at'] ?? ''),
					(string) ($row['expires_state'] ?? ''),
				]
			)
		);

		return strpos($haystack, $needle) !== false;
	}

	/**
	 * @param array<string, mixed> $row
	 * @param array<string, string> $filters
	 */
	private function matchesFilters(array $row, array $filters): bool {
		if($filters['key'] !== '' && strpos($this->toLower((string) $row['key']), $this->toLower($filters['key'])) === false) {
			return false;
		}

		if($filters['type'] !== '' && (string) $row['type'] !== $filters['type']) {
			return false;
		}

		if($filters['expires_state'] !== '' && (string) $row['expires_state'] !== $filters['expires_state']) {
			return false;
		}

		return true;
	}

	private function detectValueType(mixed $value): string {
		if($value === null) {
			return 'null';
		}

		if(is_bool($value)) {
			return 'bool';
		}

		if(is_int($value)) {
			return 'int';
		}

		if(is_float($value)) {
			return 'float';
		}

		if(is_array($value) || is_object($value)) {
			return 'array';
		}

		return 'string';
	}

	private function normalizeValueType(string $type): string {
		$type = strtolower(trim($type));

		if(!in_array($type, $this->getAllowedTypes(), true)) {
			return 'string';
		}

		return $type;
	}

	/**
	 * @return array<int, string>
	 */
	private function getAllowedTypes(): array {
		return ['string', 'int', 'float', 'bool', 'array', 'null', 'raw'];
	}

	/**
	 * @return array<int, string>
	 */
	private function getAllowedExpiresStates(): array {
		return ['persistent', 'active', 'expired'];
	}

	private function decodeEditorValue(string $type, mixed $value): mixed {
		if($type === 'null') {
			return null;
		}

		if($type === 'array') {
			return $this->decodeArrayEditorValue($value);
		}

		if(is_array($value) || is_object($value)) {
			$value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		if($type === 'raw') {
			return (string) $value;
		}

		$text = trim((string) $value);

		if($type === 'bool') {
			return $this->decodeBoolEditorValue($text);
		}

		if($type === 'int') {
			if($text === '' || preg_match('/^-?\d+$/', $text) !== 1) {
				throw new InvalidArgumentException('Integer values must contain a valid integer.');
			}

			return (int) $text;
		}

		if($type === 'float') {
			if($text === '' || !is_numeric($text)) {
				throw new InvalidArgumentException('Float values must contain a valid number.');
			}

			return (float) $text;
		}

		return (string) $value;
	}

	private function encodeStoredValue(string $type, mixed $value): string {
		if($type === 'raw') {
			return (string) $value;
		}

		$json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		if(!is_string($json)) {
			throw new RuntimeException('Failed to encode state store value as JSON.');
		}

		return $json;
	}

	private function decodeBoolEditorValue(string $value): bool {
		$value = strtolower(trim($value));

		if(in_array($value, ['1', 'true', 'yes', 'on'], true)) {
			return true;
		}

		if(in_array($value, ['0', 'false', 'no', 'off', ''], true)) {
			return false;
		}

		throw new InvalidArgumentException('Boolean values must be true/false, yes/no, on/off or 1/0.');
	}

	private function decodeArrayEditorValue(mixed $value): array {
		if(is_array($value)) {
			return $value;
		}

		$text = trim((string) $value);

		if($text === '') {
			return [];
		}

		$decoded = json_decode($text, true);

		if(json_last_error() !== JSON_ERROR_NONE) {
			throw new InvalidArgumentException('Array values must contain valid JSON: ' . json_last_error_msg());
		}

		if(!is_array($decoded)) {
			throw new InvalidArgumentException('Array JSON must decode to a PHP array.');
		}

		return $decoded;
	}

	private function formatValueText(mixed $value, string $type): string {
		if($type === 'array') {
			return $this->formatJson($value);
		}

		if($type === 'bool') {
			return $value ? 'true' : 'false';
		}

		if($type === 'null') {
			return 'null';
		}

		return (string) $value;
	}

	private function formatEditorValue(mixed $value, string $type): string {
		if($type === 'array') {
			return $this->formatJson($value);
		}

		if($type === 'bool') {
			return $value ? 'true' : 'false';
		}

		if($type === 'null') {
			return '';
		}

		return (string) $value;
	}

	private function formatJson(mixed $value): string {
		$json = json_encode(
			$value,
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
		);

		if(!is_string($json)) {
			throw new RuntimeException('Failed to encode JSON value.');
		}

		return $json;
	}

	private function detectExpiresState(string $expiresAt): string {
		if($expiresAt === '') {
			return 'persistent';
		}

		$timestamp = strtotime($expiresAt);

		if($timestamp !== false && $timestamp < time()) {
			return 'expired';
		}

		return 'active';
	}

	private function formatExpiresLabel(string $expiresAt, string $expiresState): string {
		if($expiresState === 'persistent') {
			return 'Never';
		}

		if($expiresState === 'expired') {
			return $expiresAt . ' (expired)';
		}

		return $expiresAt;
	}

	private function shorten(string $value, int $maxLength): string {
		if(function_exists('mb_strlen') && function_exists('mb_substr')) {
			if(mb_strlen($value) <= $maxLength) {
				return $value;
			}

			return mb_substr($value, 0, $maxLength - 1) . '…';
		}

		if(strlen($value) <= $maxLength) {
			return $value;
		}

		return substr($value, 0, $maxLength - 1) . '…';
	}

	private function encodeId(string $key): string {
		$json = json_encode(
			[
				'key' => $key,
			],
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		);

		if(!is_string($json)) {
			return '';
		}

		return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
	}

	private function decodeId(string $id): ?string {
		$id = trim($id);

		if($id === '') {
			return null;
		}

		$padding = strlen($id) % 4;
		if($padding > 0) {
			$id .= str_repeat('=', 4 - $padding);
		}

		$json = base64_decode(strtr($id, '-_', '+/'), true);

		if(!is_string($json)) {
			return null;
		}

		$decoded = json_decode($json, true);

		if(!is_array($decoded)) {
			return null;
		}

		$key = isset($decoded['key']) && is_scalar($decoded['key']) ? trim((string) $decoded['key']) : '';

		if($key === '') {
			return null;
		}

		return $key;
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	private function readString(array $payload, string $key): string {
		if(!isset($payload[$key]) || !is_scalar($payload[$key])) {
			return '';
		}

		return trim((string) $payload[$key]);
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private function readRowString(array $row, string $key): string {
		if(!isset($row[$key]) || $row[$key] === null) {
			return '';
		}

		if(is_scalar($row[$key])) {
			return (string) $row[$key];
		}

		return '';
	}

	private function quote(string $value): string {
		return "'" . $this->database->escape($value) . "'";
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	private function getTypeOptions(): array {
		return [
			[
				'value' => '',
				'label' => 'All types',
			],
			[
				'value' => 'string',
				'label' => 'String',
			],
			[
				'value' => 'int',
				'label' => 'Integer',
			],
			[
				'value' => 'float',
				'label' => 'Float',
			],
			[
				'value' => 'bool',
				'label' => 'Boolean',
			],
			[
				'value' => 'array',
				'label' => 'Array / JSON',
			],
			[
				'value' => 'null',
				'label' => 'Null',
			],
			[
				'value' => 'raw',
				'label' => 'Raw text',
			],
		];
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	private function getExpiresStateOptions(): array {
		return [
			[
				'value' => '',
				'label' => 'All expiration states',
			],
			[
				'value' => 'persistent',
				'label' => 'Persistent',
			],
			[
				'value' => 'active',
				'label' => 'Active expiration',
			],
			[
				'value' => 'expired',
				'label' => 'Expired',
			],
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function buildErrorResponse(string $message, string $mode): array {
		return [
			'ok' => false,
			'mode' => $mode,
			'error' => $message,
		];
	}

	private function toLower(string $value): string {
		if(function_exists('mb_strtolower')) {
			return mb_strtolower($value);
		}

		return strtolower($value);
	}

}
