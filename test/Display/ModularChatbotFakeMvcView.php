<?php declare(strict_types=1);

namespace ClientStack\Test\Display;

use Base3\Api\IAssetResolver;
use Base3\Api\IMvcView;
use ClientStack\Display\ModularChatbotDisplay;
use PHPUnit\Framework\TestCase;

class ModularChatbotFakeMvcView implements IMvcView {

	private ?string $lastPath = null;
	private ?string $lastTemplate = null;
	private ?string $loadedBrickSet = null;
	private array $assigned = [];

	/** @param array<string,string> $bricks */
	public function __construct(
		private readonly array $bricks = []
	) {}

	public function setPath(string $path = '.'): void {
		$this->lastPath = $path;
	}

	public function assign(string $key, $value): void {
		$this->assigned[$key] = $value;
	}

	public function setTemplate(string $template = 'default'): void {
		$this->lastTemplate = $template;
	}

	public function loadTemplate(): string {
		return 'FAKE_TEMPLATE_OUTPUT';
	}

	public function loadBricks(string $set, string $language = ''): void {
		$this->loadedBrickSet = $set;
	}

	public function getBricks(string $set): ?array {
		return $set === 'modularchatbot' ? $this->bricks : null;
	}

	public function getLastPath(): ?string {
		return $this->lastPath;
	}

	public function getLastTemplate(): ?string {
		return $this->lastTemplate;
	}

	public function getLoadedBrickSet(): ?string {
		return $this->loadedBrickSet;
	}

	public function getAssigned(string $tag): mixed {
		return $this->assigned[$tag] ?? null;
	}
}
