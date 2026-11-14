<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of ClientStack for BASE3 Framework.
 *
 * ClientStack manages browser assets for modular BASE3 applications.
 * It provides reusable JavaScript and CSS library registrations.
 * Logical asset identifiers are resolved to deployment-specific URLs.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/ClientStack
 **********************************************************************/

namespace Base3\ClientStack\Test\Display;

use Base3\CoreFoundation\Api\IAssetResolver;
use Base3\CoreFoundation\Api\IMvcView;
use Base3\ClientStack\Display\ModularChatbotDisplay;
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
