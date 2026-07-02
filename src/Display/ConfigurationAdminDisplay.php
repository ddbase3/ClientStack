<?php declare(strict_types=1);

namespace ClientStack\Display;

use Base3\Api\IAssetResolver;
use Base3\Api\IDisplay;
use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\Configuration\Api\IConfiguration;
use Base3\LinkTarget\Api\ILinkTargetService;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class ConfigurationAdminDisplay implements IDisplay {

        public function __construct(
                private readonly IRequest $request,
                private readonly IMvcView $view,
                private readonly IAssetResolver $assetResolver,
                private readonly IConfiguration $configuration,
                private readonly ILinkTargetService $linkTargetService
        ) {}

        public static function getName(): string {
                return 'configurationadmindisplay';
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
                return 'Help of ConfigurationAdminDisplay';
        }

        private function handleHtml(): string {
                $this->view->setPath(DIR_PLUGIN . 'ClientStack');
                $this->view->setTemplate('Display/ConfigurationAdminDisplay.php');

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

                $this->view->assign(
                        'modularDialogCssUrl',
                        $this->assetResolver->resolve('plugin/ClientStack/assets/modulardialog/styles/modulardialog.css')
                );

                $this->view->assign(
                        'modularDialogJsUrl',
                        $this->assetResolver->resolve('plugin/ClientStack/assets/modulardialog/index.js')
                );

                $this->view->assign('typeOptions', $this->getTypeOptions());

                return $this->view->loadTemplate();
        }

        private function handleJson(bool $final = false): string {
                try {
                        $response = $this->buildJsonResponse();
                }
                catch(Throwable $e) {
                        $response = [
                                'ok' => false,
                                'error' => 'Configuration request failed.',
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

                if($request['mode'] === 'reload') {
                        return $this->buildReloadResponse();
                }

                return $this->buildPageResponse($request);
        }

        /**
         * @param array<string, mixed> $payload
         * @return array<string, mixed>
         */
        private function normalizeRequest(array $payload): array {
                $mode = 'page';
                $allowedModes = ['page', 'record', 'save', 'delete', 'reload'];

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
                $allowedKeys = ['group', 'key', 'type', 'value_preview'];

                $sort = [
                        'key' => 'group',
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

                $key = isset($first['key']) ? (string) $first['key'] : 'group';
                if(!in_array($key, $allowedKeys, true)) {
                        $key = 'group';
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
                        'group' => '',
                        'key' => '',
                        'type' => '',
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
                $identity = $this->decodeId($id);

                if($identity === null) {
                        return [
                                'ok' => false,
                                'mode' => 'record',
                                'found' => false,
                                'error' => 'Invalid configuration id.',
                                'record' => null,
                        ];
                }

                $row = $this->loadRow($identity['group'], $identity['key']);

                if($row === null) {
                        return [
                                'ok' => false,
                                'mode' => 'record',
                                'found' => false,
                                'error' => 'Configuration value not found.',
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
                $oldGroup = $this->readString($payload, 'oldGroup');
                $oldKey = $this->readString($payload, 'oldKey');
                $group = $this->readString($payload, 'group');
                $key = $this->readString($payload, 'key');
                $type = $this->normalizeValueType($this->readString($payload, 'type'));
                $rawValue = $payload['value'] ?? '';

                if($group === '') {
                        return $this->buildErrorResponse('Group must not be empty.', 'save');
                }

                if($key === '') {
                        return $this->buildErrorResponse('Key must not be empty.', 'save');
                }

                try {
                        $value = $this->decodeEditorValue($type, $rawValue);
                }
                catch(Throwable $e) {
                        return $this->buildErrorResponse($e->getMessage(), 'save');
                }

                $isRename = $oldGroup !== '' && $oldKey !== '' && ($oldGroup !== $group || $oldKey !== $key);

                if($isRename && $this->configuration->hasValue($group, $key)) {
                        return $this->buildErrorResponse('Target configuration value already exists.', 'save');
                }

                try {
                        $this->storeValue($group, $key, $value);

                        if($isRename) {
                                $this->configuration->removeValue($oldGroup, $oldKey);
                                $this->saveConfiguration();
                        }
                }
                catch(Throwable $e) {
                        return $this->buildErrorResponse('Failed to save configuration value: ' . $e->getMessage(), 'save');
                }

                $row = $this->loadRow($group, $key);

                return [
                        'ok' => true,
                        'mode' => 'save',
                        'action' => $isRename ? 'renamed and saved' : 'saved',
                        'record' => $row,
                ];
        }

        /**
         * @return array<string, mixed>
         */
        private function buildDeleteResponse(string $id): array {
                $identity = $this->decodeId($id);

                if($identity === null) {
                        return $this->buildErrorResponse('Invalid configuration id.', 'delete');
                }

                if(!$this->configuration->hasValue($identity['group'], $identity['key'])) {
                        return $this->buildErrorResponse('Configuration value not found.', 'delete');
                }

                try {
                        $this->configuration->removeValue($identity['group'], $identity['key']);
                        $this->saveConfiguration();
                }
                catch(Throwable $e) {
                        return $this->buildErrorResponse('Failed to delete configuration value: ' . $e->getMessage(), 'delete');
                }

                return [
                        'ok' => true,
                        'mode' => 'delete',
                        'action' => 'deleted',
                        'group' => $identity['group'],
                        'key' => $identity['key'],
                ];
        }

        /**
         * @return array<string, mixed>
         */
        private function buildReloadResponse(): array {
                try {
                        $this->configuration->reload();
                }
                catch(Throwable $e) {
                        return $this->buildErrorResponse('Failed to reload configuration: ' . $e->getMessage(), 'reload');
                }

                return [
                        'ok' => true,
                        'mode' => 'reload',
                        'action' => 'reloaded',
                ];
        }

        /**
         * @return array<int, array<string, mixed>>
         */
        private function loadRows(): array {
                $config = $this->configuration->get();

                if(!is_array($config)) {
                        return [];
                }

                $rows = [];

                foreach($config as $group => $entries) {
                        if(!is_string($group) && !is_int($group)) {
                                continue;
                        }

                        if(!is_array($entries)) {
                                continue;
                        }

                        foreach($entries as $key => $value) {
                                if(!is_string($key) && !is_int($key)) {
                                        continue;
                                }

                                $rows[] = $this->normalizeRow((string) $group, (string) $key, $value);
                        }
                }

                return $rows;
        }

        /**
         * @return array<string, mixed>|null
         */
        private function loadRow(string $group, string $key): ?array {
                if(!$this->configuration->hasValue($group, $key)) {
                        return null;
                }

                return $this->normalizeRow($group, $key, $this->configuration->getValue($group, $key));
        }

        /**
         * @return array<string, mixed>
         */
        private function normalizeRow(string $group, string $key, mixed $value): array {
                $type = $this->detectValueType($value);
                $valueText = $this->formatValueText($value, $type);
                $valuePreview = $this->shorten($valueText, 220);

                return [
                        'id' => $this->encodeId($group, $key),
                        'group' => $group,
                        'key' => $key,
                        'type' => $type,
                        'value_preview' => $valuePreview,
                        'value_text' => $valueText,
                        'value_edit' => $this->formatEditorValue($value, $type),
                        'is_array' => $type === 'array',
                        'is_scalar' => in_array($type, ['string', 'int', 'float', 'bool', 'null'], true),
                ];
        }

        /**
         * @param array<string, mixed> $a
         * @param array<string, mixed> $b
         * @param array<string, string> $sort
         */
        private function compareRows(array $a, array $b, array $sort): int {
                $key = $sort['key'] ?? 'group';
                $dir = $sort['dir'] ?? 'asc';

                $aValue = (string) ($a[$key] ?? '');
                $bValue = (string) ($b[$key] ?? '');

                $result = strnatcasecmp($aValue, $bValue);

                if($result === 0) {
                        $result = strnatcasecmp((string) ($a['group'] ?? ''), (string) ($b['group'] ?? ''));
                }

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
                                        (string) ($row['group'] ?? ''),
                                        (string) ($row['key'] ?? ''),
                                        (string) ($row['type'] ?? ''),
                                        (string) ($row['value_text'] ?? ''),
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
                if($filters['group'] !== '' && strpos($this->toLower((string) $row['group']), $this->toLower($filters['group'])) === false) {
                        return false;
                }

                if($filters['key'] !== '' && strpos($this->toLower((string) $row['key']), $this->toLower($filters['key'])) === false) {
                        return false;
                }

                if($filters['type'] !== '' && (string) $row['type'] !== $filters['type']) {
                        return false;
                }

                return true;
        }

        private function storeValue(string $group, string $key, mixed $value): void {
                try {
                        if($this->configuration->persistValue($group, $key, $value)) {
                                return;
                        }
                }
                catch(Throwable $e) {
                        // Fallback to the generic configuration API below.
                }

                $this->configuration->setValue($group, $key, $value);
                $this->saveConfiguration();
        }

        private function saveConfiguration(): bool {
                try {
                        return $this->configuration->saveIfDirty();
                }
                catch(Throwable $e) {
                        try {
                                return $this->configuration->trySave();
                        }
                        catch(Throwable $fallbackException) {
                                $this->configuration->save();
                                return true;
                        }
                }
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
                return ['string', 'int', 'float', 'bool', 'array', 'null'];
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

        private function encodeId(string $group, string $key): string {
                $json = json_encode(
                        [
                                'group' => $group,
                                'key' => $key,
                        ],
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );

                if(!is_string($json)) {
                        return '';
                }

                return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        }

        /**
         * @return array{group: string, key: string}|null
         */
        private function decodeId(string $id): ?array {
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

                $group = isset($decoded['group']) && is_scalar($decoded['group']) ? trim((string) $decoded['group']) : '';
                $key = isset($decoded['key']) && is_scalar($decoded['key']) ? trim((string) $decoded['key']) : '';

                if($group === '' || $key === '') {
                        return null;
                }

                return [
                        'group' => $group,
                        'key' => $key,
                ];
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
