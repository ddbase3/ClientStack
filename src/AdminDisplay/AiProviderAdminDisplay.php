<?php declare(strict_types=1);

namespace ClientStack\AdminDisplay;

use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\LinkTarget\Api\ILinkTargetService;
use Base3\Settings\Api\ISettingsStore;
use RuntimeException;
use UiFoundation\Api\IAdminDisplay;

final class AiProviderAdminDisplay implements IAdminDisplay {

	private const SETTINGS_GROUP = 'ai-provider';

	/**
	 * @var array<int, string>
	 */
	private const DRIVER_SUGGESTIONS = [
		'openai',
		'mistral',
		'openai-compatible',
	];

	/**
	 * @var array<int, string>
	 */
	private const KEYTYPE_SUGGESTIONS = [
		'env',
		'fixed',
	];

	public function __construct(
		private readonly IMvcView $view,
		private readonly IRequest $request,
		private readonly ILinkTargetService $linkTargetService,
		private readonly ISettingsStore $settingsStore
	) {}

	public static function getName(): string {
		return 'aiprovideradmindisplay';
	}

	public function getHelp(): string {
		return 'Configure AI service providers stored in settings group "ai-provider".';
	}

	public function setData($data) {
		// no-op
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		$out = strtolower((string)$out);

		if($out === 'json') {
			return $this->handleJson();
		}

		return $this->handleHtml();
	}

	private function handleHtml(): string {
		$this->view->setPath(DIR_PLUGIN . 'ClientStack');
		$this->view->setTemplate('AdminDisplay/AiProviderAdminDisplay.php');

		$instanceId = 'aiprovadm-' . uniqid();
		$driverListId = $instanceId . '-drivers';
		$keyTypeListId = $instanceId . '-keytypes';

		$this->view->assign('instanceId', $instanceId);
		$this->view->assign('endpoint', $this->buildEndpointBase());
		$this->view->assign('configGroup', self::SETTINGS_GROUP);
		$this->view->assign('driverSuggestions', self::DRIVER_SUGGESTIONS);
		$this->view->assign('keyTypeSuggestions', self::KEYTYPE_SUGGESTIONS);
		$this->view->assign('driverListId', $driverListId);
		$this->view->assign('keyTypeListId', $keyTypeListId);

		return $this->view->loadTemplate();
	}

	private function handleJson(): string {
		$action = strtolower(trim((string)$this->request->request('action', '')));

		try {
			return match($action) {
				'list' => $this->jsonSuccess([
					'group' => self::SETTINGS_GROUP,
					'providers' => $this->listProviders(),
				]),
				'save' => $this->jsonSuccess([
					'group' => self::SETTINGS_GROUP,
					'provider' => $this->saveProvider(),
				]),
				'remove' => $this->jsonSuccess([
					'group' => self::SETTINGS_GROUP,
					'name' => $this->removeProvider(),
				]),
				default => $this->jsonError("Unknown action '$action'. Use: list|save|remove"),
			};
		}
		catch(\Throwable $e) {
			return $this->jsonError('Exception: ' . $e->getMessage());
		}
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function listProviders(): array {
		$group = $this->settingsStore->getGroup(self::SETTINGS_GROUP);
		$rows = [];

		foreach($group as $name => $settings) {
			if(!is_string($name) || $name === '' || !is_array($settings)) {
				continue;
			}

			$rows[] = $this->normalizeProvider($name, $settings);
		}

		usort($rows, function(array $a, array $b): int {
			$aSort = trim((string)($a['label'] ?? ''));
			$bSort = trim((string)($b['label'] ?? ''));

			if($aSort === '') {
				$aSort = (string)($a['name'] ?? '');
			}

			if($bSort === '') {
				$bSort = (string)($b['name'] ?? '');
			}

			$cmp = strcasecmp($aSort, $bSort);
			if($cmp !== 0) {
				return $cmp;
			}

			return strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
		});

		return $rows;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function saveProvider(): array {
		$name = $this->normalizeKey((string)$this->request->request('name', ''));
		$label = trim((string)$this->request->request('label', ''));
		$driver = $this->normalizeToken((string)$this->request->request('driver', ''));
		$endpoint = trim((string)$this->request->request('endpoint', ''));
		$keyType = $this->normalizeToken((string)$this->request->request('keytype', ''));
		$keyValue = trim((string)$this->request->request('keyvalue', ''));
		$enabled = $this->normalizeBool($this->request->request('enabled', 0));

		if($name === '') {
			throw new RuntimeException('Missing settings name.');
		}

		if($label === '') {
			throw new RuntimeException('Missing label.');
		}

		if($driver === '') {
			throw new RuntimeException('Missing driver.');
		}

		if($endpoint === '') {
			throw new RuntimeException('Missing endpoint.');
		}

		if($keyType === '') {
			throw new RuntimeException('Missing key type.');
		}

		if($keyValue === '') {
			throw new RuntimeException('Missing key value.');
		}

		$settings = [
			'label' => $label,
			'driver' => $driver,
			'endpoint' => $endpoint,
			'keytype' => $keyType,
			'keyvalue' => $keyValue,
			'enabled' => $enabled,
		];

		$this->settingsStore->set(self::SETTINGS_GROUP, $name, $settings);
		$this->settingsStore->save();

		return $this->normalizeProvider($name, $settings);
	}

	private function removeProvider(): string {
		$name = $this->normalizeKey((string)$this->request->request('name', ''));

		if($name === '') {
			throw new RuntimeException('Missing settings name.');
		}

		$this->settingsStore->remove(self::SETTINGS_GROUP, $name);
		$this->settingsStore->save();

		return $name;
	}

	/**
	 * @param array<string, mixed> $settings
	 * @return array<string, mixed>
	 */
	private function normalizeProvider(string $name, array $settings): array {
		$label = trim((string)($settings['label'] ?? ''));
		$driver = $this->normalizeToken((string)($settings['driver'] ?? ''));
		$endpoint = trim((string)($settings['endpoint'] ?? ''));
		$keyType = $this->normalizeToken((string)($settings['keytype'] ?? ''));
		$keyValue = trim((string)($settings['keyvalue'] ?? ''));
		$enabled = $this->normalizeBool($settings['enabled'] ?? false);

		return [
			'name' => $name,
			'label' => $label,
			'driver' => $driver,
			'endpoint' => $endpoint,
			'keytype' => $keyType,
			'keyvalue' => $keyValue,
			'enabled' => $enabled,
		];
	}

	private function normalizeKey(string $s): string {
		$s = trim($s);
		$s = strtolower($s);
		$s = preg_replace('/[^a-z0-9._-]+/', '', $s) ?? '';
		return $s;
	}

	private function normalizeToken(string $s): string {
		return $this->normalizeKey($s);
	}

	private function normalizeBool(mixed $v): bool {
		if(is_bool($v)) {
			return $v;
		}

		$s = strtolower(trim((string)$v));
		return in_array($s, ['1', 'true', 'yes', 'on'], true);
	}

	private function buildEndpointBase(): string {
		return $this->linkTargetService->getLink(
			[
				'name' => self::getName(),
			],
			[
				'out' => 'json',
			]
		);
	}

	/**
	 * @param array<string, mixed> $data
	 * @return string
	 */
	private function jsonSuccess(array $data): string {
		return (string)json_encode([
			'status' => 'ok',
			'timestamp' => gmdate('c'),
			'data' => $data,
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	private function jsonError(string $message): string {
		return (string)json_encode([
			'status' => 'error',
			'timestamp' => gmdate('c'),
			'message' => $message,
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
}
