<?php declare(strict_types=1);

namespace ClientStack\Display;

use Base3\Api\IAssetResolver;
use Base3\Api\IMvcView;
use UiFoundation\Api\IRichTextEditorDisplay;

final class CkEditorRichTextEditorDisplay implements IRichTextEditorDisplay {

	private array $data = [];

	public function __construct(
		private readonly IMvcView $view,
		private readonly IAssetResolver $assetResolver
	) {}

	public static function getName(): string {
		return 'ckeditorrichtexteditordisplay';
	}

	public function setData($data) {
		$this->data = is_array($data) ? $data : [];
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		$id = $this->readString('id');
		if($id === '') {
			$id = 'base3-rich-text-editor-' . bin2hex(random_bytes(6));
		}

		$name = $this->readString('name');
		$value = $this->readString('value');
		$additionalClass = $this->readString('class');
		$className = trim('base3-rich-text-editor base3-rich-text-editor-ckeditor-source ' . $additionalClass);
		$rows = $this->readInt('rows', 12, 2, 1000);

		$this->view->setPath(DIR_PLUGIN . 'ClientStack');
		$this->view->setTemplate('Display/CkEditorRichTextEditorDisplay.php');
		$this->view->assign('id', $id);
		$this->view->assign('name', $name);
		$this->view->assign('value', $value);
		$this->view->assign('className', $className);
		$this->view->assign('rows', $rows);
		$this->view->assign('minimumHeight', max(120, $rows * 24));
		$this->view->assign('placeholder', $this->readString('placeholder'));
		$this->view->assign('spellcheck', $this->readBool('spellcheck', true));
		$this->view->assign('readonly', $this->readBool('readonly', false));
		$this->view->assign('disabled', $this->readBool('disabled', false));
		$this->view->assign('ariaLabel', $this->readString('aria_label'));
		$this->view->assign(
			'ckeditorModuleUrl',
			$this->assetResolver->resolve('plugin/ClientStack/assets/ckeditor/ckeditor5.js')
		);
		$this->view->assign(
			'ckeditorCssUrl',
			$this->assetResolver->resolve('plugin/ClientStack/assets/ckeditor/ckeditor5.css')
		);

		return $this->view->loadTemplate();
	}

	public function getHelp(): string {
		return 'Renders CKEditor 5 with a synchronized native textarea fallback.';
	}

	private function readString(string $key): string {
		if(!isset($this->data[$key]) || !is_scalar($this->data[$key])) {
			return '';
		}

		return (string) $this->data[$key];
	}

	private function readInt(string $key, int $default, int $minimum, int $maximum): int {
		if(!isset($this->data[$key]) || !is_numeric($this->data[$key])) {
			return $default;
		}

		return max($minimum, min($maximum, (int) $this->data[$key]));
	}

	private function readBool(string $key, bool $default): bool {
		if(!array_key_exists($key, $this->data)) {
			return $default;
		}

		$value = $this->data[$key];
		if(is_bool($value)) {
			return $value;
		}

		if(is_int($value) || is_float($value)) {
			return (bool) $value;
		}

		if(is_string($value)) {
			$normalized = strtolower(trim($value));
			if(in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
				return true;
			}
			if(in_array($normalized, ['0', 'false', 'no', 'off', ''], true)) {
				return false;
			}
		}

		return $default;
	}
}
