<?php declare(strict_types=1);

namespace ClientStack\AdminDisplay;

use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\Configuration\Api\IConfiguration;
use Base3\Logger\Api\ILogger;
use UiFoundation\Api\IAdminDisplay;

final class LogAdminDisplay implements IAdminDisplay {

	private const DEFAULT_NUM = 100;

	public function __construct(
		private readonly IRequest $request,
		private readonly IMvcView $view,
		private readonly IConfiguration $config,
		private readonly ILogger $logger
	) {}

	public static function getName(): string {
		return 'logadmindisplay';
	}

	public function setData($data) {
		// no-op
	}

	public function getHelp(): string {
		return 'System log viewer (scope selector + auto-refresh).';
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		$out = strtolower((string)$out);

		if ($out === 'json') {
			return $this->handleJson();
		}

		return $this->handleHtml();
	}

	private function handleHtml(): string {
		// ClientStack plugin path
		$this->view->setPath(DIR_PLUGIN . 'ClientStack');
		$this->view->setTemplate('AdminDisplay/LogAdminDisplay.php');

		$baseEndpoint = (string)($this->config->get('base')['endpoint'] ?? '');
		$endpoint = $this->buildEndpointBase($baseEndpoint);

		$this->view->assign('endpoint', $endpoint);

		return $this->view->loadTemplate();
	}

	private function handleJson(): string {
		$action = (string)($this->request->get('action') ?? '');

		try {
			return match ($action) {
				'tail' => $this->jsonSuccess($this->loadTail()),
				default => $this->jsonError("Unknown action '$action'. Use: tail"),
			};
		} catch (\Throwable $e) {
			return $this->jsonError('Exception: ' . $e->getMessage());
		}
	}

	private function loadTail(): array {
		$scopes = $this->logger->getScopes();
		sort($scopes);

		$scope = (string)($this->request->get('scope') ?? '');

		if ($scope === '' || !in_array($scope, $scopes, true)) {
			$scope = $scopes[0] ?? '';
		}

		$num = (int)($this->request->get('num') ?? self::DEFAULT_NUM);
		$num = max(1, min(500, $num));

		$logs = [];
		if ($scope !== '') {
			$logs = $this->logger->getLogs($scope, $num, true);
		}

		// normalize + keep consistent keys
		$out = [];
		foreach ($logs as $row) {
			$out[] = [
				'timestamp' => (string)($row['timestamp'] ?? ''),
				'scope' => (string)($row['scope'] ?? $scope),
				'level' => strtolower((string)($row['level'] ?? 'info')),
				'log' => (string)($row['log'] ?? ''),
			];
		}

		return [
			'scope' => $scope,
			'scopes' => $scopes,
			'num' => $num,
			'logs' => $out,
		];
	}

	private function buildEndpointBase(string $baseEndpoint): string {
		$baseEndpoint = trim($baseEndpoint);

		if ($baseEndpoint === '') {
			$baseEndpoint = 'base3.php';
		}

		$sep = str_contains($baseEndpoint, '?') ? '&' : '?';

		return $baseEndpoint . $sep . 'name=' . rawurlencode(self::getName()) . '&out=json&action=';
	}

	private function jsonSuccess(array $data): string {
		return json_encode([
			'status' => 'ok',
			'timestamp' => gmdate('c'),
			'data' => $data
		], JSON_UNESCAPED_UNICODE);
	}

	private function jsonError(string $message): string {
		return json_encode([
			'status' => 'error',
			'timestamp' => gmdate('c'),
			'message' => $message
		], JSON_UNESCAPED_UNICODE);
	}
}
