<?php declare(strict_types=1);

namespace ClientStack\Display;

use Base3\Api\IClassMap;
use Base3\Api\IDisplay;
use Base3\Api\IMvcView;

final class CompositeDisplay implements IDisplay {

	private array $data = [];

	public function __construct(
		private readonly IClassMap $classmap,
		private readonly IMvcView $view
	) {}

	public static function getName(): string {
		return 'compositedisplay';
	}

	public function setData($data) {
		$this->data = is_array($data) ? $data : [];
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		$columns = $this->normalizeColumns($this->data['columns'] ?? 2);
		$items = $this->renderItems($this->data['items'] ?? [], $columns);

		$this->view->setPath(DIR_PLUGIN . 'ClientStack');
		$this->view->setTemplate('Display/CompositeDisplay.php');
		$this->view->assign('compositeId', 'base3-composite-display-' . bin2hex(random_bytes(6)));
		$this->view->assign('columns', $columns);
		$this->view->assign('items', $items);
		$this->view->assign(
			'emptyMessage',
			isset($this->data['empty_message']) && is_scalar($this->data['empty_message'])
				? (string) $this->data['empty_message']
				: 'No displays are available.'
		);

		return $this->view->loadTemplate();
	}

	public function getHelp(): string {
		return 'Composes multiple discoverable BASE3 displays in a responsive grid.';
	}

	/**
	 * @param mixed $itemsData
	 * @return array<int, array<string, mixed>>
	 */
	private function renderItems(mixed $itemsData, int $columns): array {
		if(!is_array($itemsData)) {
			return [];
		}

		$items = [];
		$usedIds = [];

		foreach($itemsData as $itemData) {
			if(!is_array($itemData)) {
				continue;
			}

			$id = $this->readName($itemData, 'id');
			if($id === '') {
				$id = $this->readName($itemData, 'name');
			}

			$displayName = $this->readName($itemData, 'display');
			if(
				$id === ''
				|| $displayName === ''
				|| $displayName === self::getName()
				|| isset($usedIds[$id])
			) {
				continue;
			}

			$display = $this->createDisplayInstance($displayName);
			if(!$display instanceof IDisplay) {
				continue;
			}

			$display->setData($itemData['data'] ?? null);
			$items[] = [
				'id' => $id,
				'display' => $displayName,
				'title' => $this->readText($itemData, 'title'),
				'span' => $this->normalizeSpan($itemData['span'] ?? 1, $columns),
				'content' => $display->getOutput('html', false),
			];

			$usedIds[$id] = true;
		}

		return $items;
	}

	private function createDisplayInstance(string $name): ?IDisplay {
		$instance = $this->classmap->getInstanceByInterfaceName(IDisplay::class, $name);
		return $instance instanceof IDisplay ? $instance : null;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private function readText(array $data, string $key): string {
		if(!isset($data[$key]) || !is_scalar($data[$key])) {
			return '';
		}

		return trim((string) $data[$key]);
	}

	private function normalizeColumns(mixed $columns): int {
		$value = is_numeric($columns) ? (int) $columns : 2;
		return max(1, min(4, $value));
	}

	private function normalizeSpan(mixed $span, int $columns): int {
		$value = is_numeric($span) ? (int) $span : 1;
		return max(1, min($columns, $value));
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
}
