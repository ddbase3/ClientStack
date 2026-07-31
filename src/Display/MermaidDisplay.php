<?php declare(strict_types=1);

namespace ClientStack\Display;

use Base3\Api\IAssetResolver;
use Base3\Api\IDisplay;
use Base3\Api\IMvcView;

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

		$this->view->setPath(DIR_PLUGIN . 'ClientStack');
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
