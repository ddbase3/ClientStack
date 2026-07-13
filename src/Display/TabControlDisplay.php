<?php declare(strict_types=1);

namespace ClientStack\Display;

use Base3\Api\IClassMap;
use Base3\Api\IDisplay;
use Base3\Api\IMvcView;
use Base3\LinkTarget\Api\ILinkTargetService;

final class TabControlDisplay implements IDisplay {

	private const DISPLAY_DATA_PARAMETER = 'base3_display_data';

	private array $data = [];

	public function __construct(
		private readonly IClassMap $classmap,
		private readonly IMvcView $view,
		private readonly ILinkTargetService $linkTargetService
	) {}

	public static function getName(): string {
		return 'tabcontroldisplay';
	}

	public function setData($data) {
		$this->data = is_array($data) ? $data : [];
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		$displayInstances = [];
		$tabs = $this->normalizeTabs($this->data['tabs'] ?? [], $displayInstances);
		$activeDisplayName = $this->resolveActiveDisplayName(
			$tabs,
			isset($this->data['active']) && is_scalar($this->data['active'])
				? trim((string) $this->data['active'])
				: ''
		);

		$activeTabName = $this->resolveActiveTabName($tabs, $activeDisplayName);
		$activeDisplay = $this->getDisplayConfig($tabs, $activeDisplayName);
		$content = $this->renderDisplay(
			$displayInstances[$activeDisplayName] ?? null,
			$activeDisplay['data'] ?? null
		);

		$this->view->setPath(DIR_PLUGIN . 'ClientStack');
		$this->view->setTemplate('Display/TabControlDisplay.php');
		$this->view->assign('controlId', 'base3-tab-control-' . bin2hex(random_bytes(6)));
		$this->view->assign('tabs', $tabs);
		$this->view->assign('activeTab', $activeTabName);
		$this->view->assign('activeDisplay', $activeDisplayName);
		$this->view->assign('activeUrl', (string) ($activeDisplay['url'] ?? ''));
		$this->view->assign('content', $content);
		$this->view->assign(
			'emptyMessage',
			isset($this->data['empty_message']) && is_scalar($this->data['empty_message'])
				? (string) $this->data['empty_message']
				: 'No administration displays are available.'
		);

		return $this->view->loadTemplate();
	}

	public function getHelp(): string {
		return 'Renders a tab and subtab control for discoverable BASE3 displays.';
	}

	/**
	 * @param mixed $tabsData
	 * @param array<string, IDisplay> $displayInstances
	 * @return array<int, array<string, mixed>>
	 */
	private function normalizeTabs(mixed $tabsData, array &$displayInstances): array {
		if(!is_array($tabsData)) {
			return [];
		}

		$tabs = [];
		$usedTabNames = [];
		$usedDisplayNames = [];

		foreach($tabsData as $tabData) {
			if(!is_array($tabData)) {
				continue;
			}

			$tabName = $this->readName($tabData, 'name');
			if($tabName === '' || isset($usedTabNames[$tabName])) {
				continue;
			}

			$displays = [];
			$displayDataList = $tabData['displays'] ?? [];

			if(!is_array($displayDataList)) {
				continue;
			}

			foreach($displayDataList as $displayData) {
				if(!is_array($displayData)) {
					continue;
				}

				$displayName = $this->readName($displayData, 'name');
				if($displayName === '' || $displayName === self::getName() || isset($usedDisplayNames[$displayName])) {
					continue;
				}

				$display = $this->getDisplayInstance($displayName);
				if(!$display instanceof IDisplay) {
					continue;
				}

				$params = [];
				if(isset($displayData['params']) && is_array($displayData['params'])) {
					$params = $displayData['params'];
				}

				if(array_key_exists('data', $displayData) && $displayData['data'] !== null) {
					$encodedData = $this->encodeDisplayData($displayData['data']);
					if($encodedData !== '') {
						$params[self::DISPLAY_DATA_PARAMETER] = $encodedData;
					}
				}

				$displays[] = [
					'name' => $displayName,
					'label' => $this->readLabel($displayData, $displayName),
					'data' => $displayData['data'] ?? null,
					'url' => $this->linkTargetService->getLink(
						[
							'name' => $displayName,
							'out' => 'html',
						],
						$params
					),
				];

				$displayInstances[$displayName] = $display;
				$usedDisplayNames[$displayName] = true;
			}

			if(count($displays) === 0) {
				continue;
			}

			$tabs[] = [
				'name' => $tabName,
				'label' => $this->readLabel($tabData, $tabName),
				'displays' => $displays,
			];

			$usedTabNames[$tabName] = true;
		}

		return $tabs;
	}

	private function getDisplayInstance(string $name): ?IDisplay {
		$instance = $this->classmap->getInstanceByInterfaceName(IDisplay::class, $name);

		return $instance instanceof IDisplay ? $instance : null;
	}

	/**
	 * @param array<int, array<string, mixed>> $tabs
	 */
	private function resolveActiveDisplayName(array $tabs, string $requestedName): string {
		foreach($tabs as $tab) {
			foreach($tab['displays'] as $display) {
				if((string) $display['name'] === $requestedName) {
					return $requestedName;
				}
			}
		}

		if(isset($tabs[0]['displays'][0]['name'])) {
			return (string) $tabs[0]['displays'][0]['name'];
		}

		return '';
	}

	/**
	 * @param array<int, array<string, mixed>> $tabs
	 */
	private function resolveActiveTabName(array $tabs, string $activeDisplayName): string {
		foreach($tabs as $tab) {
			foreach($tab['displays'] as $display) {
				if((string) $display['name'] === $activeDisplayName) {
					return (string) $tab['name'];
				}
			}
		}

		return '';
	}

	/**
	 * @param array<int, array<string, mixed>> $tabs
	 */
	private function getDisplayConfig(array $tabs, string $displayName): ?array {
		foreach($tabs as $tab) {
			foreach($tab['displays'] as $display) {
				if((string) $display['name'] === $displayName) {
					return $display;
				}
			}
		}

		return null;
	}

	private function renderDisplay(?IDisplay $display, mixed $data): string {
		if(!$display instanceof IDisplay) {
			return '';
		}

		$display->setData($data);
		return $display->getOutput('html', false);
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private function readName(array $data, string $key): string {
		if(!isset($data[$key]) || !is_scalar($data[$key])) {
			return '';
		}

		return trim((string) $data[$key]);
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private function readLabel(array $data, string $fallback): string {
		if(!isset($data['label']) || !is_scalar($data['label'])) {
			return $fallback;
		}

		$label = trim((string) $data['label']);
		return $label !== '' ? $label : $fallback;
	}

	private function encodeDisplayData(mixed $data): string {
		$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if(!is_string($json)) {
			return '';
		}

		return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
	}
}
