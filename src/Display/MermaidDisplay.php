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

namespace Base3\ClientStack\Display;

use Base3\CoreFoundation\Api\IAssetResolver;
use Base3\CoreFoundation\Api\IDisplay;
use Base3\CoreFoundation\Api\IMvcView;

final class MermaidDisplay implements IDisplay {

	private array $data = [];

	public function __construct(
		private readonly IMvcView $view,
		private readonly IAssetResolver $assetResolver
	) {}

	public static function getName(): string {
		return 'mermaiddisplay';
	}

	public function setData($data) {
		$this->data = is_array($data) ? $data : [];
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		if($final) {
			return '';
		}

		$this->view->setPath(dirname(__DIR__, 2));
		$this->view->setTemplate('Display/MermaidDisplay.php');
		$this->view->assign('mermaid', (string) ($this->data['mermaid'] ?? ''));
		$this->view->assign(
			'mermaidUrl',
			$this->assetResolver->resolve('plugin/ClientStack/assets/mermaid/mermaid.min.js')
		);

		return $this->view->loadTemplate();
	}

	public function getHelp(): string {
		return 'Renders Mermaid diagrams with the ClientStack browser bundle.';
	}
}
