<?php declare(strict_types=1);

namespace ClientStack\AdminDisplay;

use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\Configuration\Api\IConfiguration;
use UiFoundation\Api\IAdminDisplay;

final class AgentFlowAdminDisplay implements IAdminDisplay {

	public function __construct(
		private readonly IRequest $request,
		private readonly IMvcView $view,
		private readonly IConfiguration $config
	) {}

	public static function getName(): string {
		return 'agentflowadmindisplay';
	}

	public function setData($data) {
		// no-op
	}

	public function getHelp() {
		return 'Agent flow viewer: scans plugin local folders for JSON files whose filename contains "flow".';
	}

	public function getOutput($out = 'html') {
		$out = strtolower((string)$out);

		if ($out === 'json') {
			return $this->handleJson();
		}

		return $this->handleHtml();
	}

	private function handleHtml(): string {
		$this->view->setPath(DIR_PLUGIN . 'ClientStack');
		$this->view->setTemplate('AdminDisplay/AgentFlowAdminDisplay.php');

		$flowId = (string)($this->request->get('flow') ?? '');
		$flowView = $this->loadFlowView($flowId);

		$baseEndpoint = (string)($this->config->get('base')['endpoint'] ?? '');
		$selfUrl = $this->buildSelfUrl($baseEndpoint);
		$listEndpoint = $this->buildListEndpoint($baseEndpoint);

		$this->view->assign('self_url', $selfUrl);
		$this->view->assign('list_endpoint', $listEndpoint);
		$this->view->assign('flow', $flowView);

		return $this->view->loadTemplate();
	}

	private function handleJson(): string {
		$action = (string)($this->request->get('action') ?? '');

		try {
			return match ($action) {
				'list' => $this->jsonSuccess([
					'flows' => $this->scanFlows(),
				]),
				default => $this->jsonError("Unknown action '$action'. Use: list"),
			};
		} catch (\Throwable $e) {
			return $this->jsonError('Exception: ' . $e->getMessage());
		}
	}

	/**
	 * Scan strategy:
	 * - DIR_PLUGIN contains plugins as direct subfolders
	 * - Each plugin may have a "local" folder
	 * - Under "local" we scan exactly one additional folder level (e.g. local/Ai, local/Chatbot)
	 * - In those folders we collect JSON files whose basename contains "flow"
	 *
	 * @return array<int, array{id:string,label:string,plugin:string,relpath:string,abspath:string,mtime:int}>
	 */
	private function scanFlows(): array {
		$base = rtrim((string)DIR_PLUGIN, '/\\') . DIRECTORY_SEPARATOR;

		$plugins = glob($base . '*', GLOB_ONLYDIR) ?: [];
		$flows = [];

		foreach ($plugins as $pluginDir) {
			$pluginName = basename($pluginDir);

			$localDir = $pluginDir . DIRECTORY_SEPARATOR . 'local';
			if (!is_dir($localDir)) {
				continue;
			}

			$groups = glob($localDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];
			foreach ($groups as $groupDir) {
				$files = glob($groupDir . DIRECTORY_SEPARATOR . '*') ?: [];

				foreach ($files as $file) {
					if (!is_file($file)) {
						continue;
					}

					$baseName = basename($file);
					$lower = strtolower($baseName);

					if (!str_ends_with($lower, '.json')) {
						continue;
					}

					if (strpos($lower, 'flow') === false) {
						continue;
					}

					$rel = $this->relPath($file, $base);
					$id = $pluginName . '::' . $rel;

					$flows[] = [
						'id' => $id,
						'label' => $pluginName . ' / ' . $rel,
						'plugin' => $pluginName,
						'relpath' => $rel,
						'abspath' => $file,
						'mtime' => (int)@filemtime($file),
					];
				}
			}
		}

		usort($flows, function(array $a, array $b) {
			return ($b['mtime'] ?? 0) <=> ($a['mtime'] ?? 0);
		});

		return $flows;
	}

	private function loadFlowView(string $flowId): array {
		$flows = $this->scanFlows();

		$selected = null;
		foreach ($flows as $f) {
			if (($f['id'] ?? '') === $flowId) {
				$selected = $f;
				break;
			}
		}

		if (!$selected && count($flows) > 0) {
			$selected = $flows[0];
		}

		if (!$selected) {
			return [
				'selected' => null,
				'error' => 'No flows found.',
				'meta' => null,
				'nodes' => [],
				'resources' => [],
				'connections' => [],
				'node_index' => [],
				'resource_index' => [],
			];
		}

		$json = @file_get_contents((string)$selected['abspath']);
		if ($json === false) {
			return [
				'selected' => $this->publicFlowMeta($selected),
				'error' => 'Cannot read file.',
				'meta' => null,
				'nodes' => [],
				'resources' => [],
				'connections' => [],
				'node_index' => [],
				'resource_index' => [],
			];
		}

		$data = json_decode($json, true);
		if (!is_array($data)) {
			return [
				'selected' => $this->publicFlowMeta($selected),
				'error' => 'Invalid JSON.',
				'meta' => null,
				'nodes' => [],
				'resources' => [],
				'connections' => [],
				'node_index' => [],
				'resource_index' => [],
			];
		}

		$nodes = is_array($data['nodes'] ?? null) ? $data['nodes'] : [];
		$resources = is_array($data['resources'] ?? null) ? $data['resources'] : [];
		$conns = is_array($data['connections'] ?? null) ? $data['connections'] : [];

		$nodeIndex = $this->indexById($nodes);
		$resIndex = $this->indexById($resources);

		$meta = [
			'node_count' => count($nodes),
			'resource_count' => count($resources),
			'connection_count' => count($conns),
			'modified_at' => ($selected['mtime'] ?? 0)
				? gmdate('Y-m-d H:i:s', (int)$selected['mtime']) . ' UTC'
				: null,
		];

		return [
			'selected' => $this->publicFlowMeta($selected),
			'error' => null,
			'meta' => $meta,
			'nodes' => $nodes,
			'resources' => $resources,
			'connections' => $conns,
			'node_index' => $nodeIndex,
			'resource_index' => $resIndex,
		];
	}

	private function publicFlowMeta(array $f): array {
		return [
			'id' => (string)($f['id'] ?? ''),
			'label' => (string)($f['label'] ?? ''),
			'plugin' => (string)($f['plugin'] ?? ''),
			'relpath' => (string)($f['relpath'] ?? ''),
			'mtime' => (int)($f['mtime'] ?? 0),
		];
	}

	private function indexById(array $items): array {
		$idx = [];
		foreach ($items as $it) {
			if (!is_array($it)) {
				continue;
			}
			$id = (string)($it['id'] ?? '');
			if ($id !== '') {
				$idx[$id] = $it;
			}
		}
		return $idx;
	}

	private function relPath(string $path, string $base): string {
		$p = str_replace('\\', '/', $path);
		$b = rtrim(str_replace('\\', '/', $base), '/') . '/';

		if (str_starts_with($p, $b)) {
			return substr($p, strlen($b));
		}

		return basename($path);
	}

	private function buildSelfUrl(string $baseEndpoint): string {
		$baseEndpoint = trim($baseEndpoint);

		if ($baseEndpoint === '') {
			$baseEndpoint = 'base3.php';
		}

		$sep = str_contains($baseEndpoint, '?') ? '&' : '?';

		return $baseEndpoint . $sep . 'name=' . rawurlencode(self::getName());
	}

	private function buildListEndpoint(string $baseEndpoint): string {
		$baseEndpoint = trim($baseEndpoint);

		if ($baseEndpoint === '') {
			$baseEndpoint = 'base3.php';
		}

		$sep = str_contains($baseEndpoint, '?') ? '&' : '?';

		return $baseEndpoint
			. $sep . 'name=' . rawurlencode(self::getName())
			. '&out=json&action=list';
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
