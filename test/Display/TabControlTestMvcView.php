<?php declare(strict_types=1);

namespace ClientStack\Test\Display;

use Base3\Api\IClassMap;
use Base3\Api\IDisplay;
use Base3\Api\IMvcView;
use Base3\LinkTarget\Api\ILinkTargetService;
use ClientStack\Display\TabControlDisplay;
use PHPUnit\Framework\TestCase;

final class TabControlTestMvcView implements IMvcView {

	public array $_ = [];
	private string $path = '';
	private string $template = '';

	public function setPath(string $path = '.'): void {
		$this->path = $path;
	}

	public function assign(string $key, $value): void {
		$this->_[$key] = $value;
	}

	public function setTemplate(string $template = 'default'): void {
		$this->template = $template;
	}

	public function loadTemplate(): string {
		ob_start();
		include $this->path . '/tpl/' . $this->template;
		return (string)ob_get_clean();
	}

	public function loadBricks(string $set, string $language = ''): void {}

	public function getBricks(string $set): ?array {
		return null;
	}

	public function getAssigned(string $key): mixed {
		return $this->_[$key] ?? null;
	}
}
