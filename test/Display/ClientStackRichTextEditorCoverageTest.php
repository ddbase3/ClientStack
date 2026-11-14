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
use Base3\ClientStack\Display\CkEditorRichTextEditorDisplay;
use Base3\ClientStack\Display\TextareaRichTextEditorDisplay;
use PHPUnit\Framework\TestCase;

final class ClientStackRichTextEditorCoverageTest extends TestCase {

	private function view(array &$variables): IMvcView {
		$view = $this->createStub(IMvcView::class);
		$view->method('assign')->willReturnCallback(
			static function (string $key, mixed $value) use (&$variables): void { $variables[$key] = $value; }
		);
		$view->method('loadTemplate')->willReturn('editor-html');
		return $view;
	}

	private function makeTextarea(array &$variables): TextareaRichTextEditorDisplay {
		return new TextareaRichTextEditorDisplay($this->view($variables));
	}

	private function makeCkEditor(array &$variables): CkEditorRichTextEditorDisplay {
		$assets = $this->createStub(IAssetResolver::class);
		$assets->method('resolve')->willReturnCallback(static fn(string $path): string => '/resolved/' . $path);
		return new CkEditorRichTextEditorDisplay($this->view($variables), $assets);
	}

	public function testTextareaDefaultOptionsAndGeneratedId(): void {
		$data = [];
		$editor = $this->makeTextarea($data);
		self::assertSame('textarearichtexteditordisplay', $editor::getName());
		self::assertSame('editor-html', $editor->getOutput());
		self::assertSame(12, $data['rows']);
		self::assertSame('', $data['name']);
		self::assertSame('', $data['value']);
		self::assertSame('', $data['ariaLabel']);
		self::assertTrue($data['spellcheck']);
		self::assertFalse($data['readonly']);
		self::assertFalse($data['disabled']);
		self::assertSame('base3-rich-text-editor base3-rich-text-editor-textarea', $data['className']);
		self::assertTrue(str_starts_with($data['id'], 'base3-rich-text-editor-'));
		self::assertSame(35, strlen($data['id']));
		self::assertStringContainsString('textarea', $editor->getHelp());
	}

	public function testTextareaRendersExplicitAccessibleFormFields(): void {
		$data = [];
		$editor = $this->makeTextarea($data);
		$editor->setData([
			'id' => 'post_body', 'name' => 'content', 'value' => '<strong>body</strong>',
			'class' => 'editor-large', 'rows' => '15', 'placeholder' => 'Write',
			'spellcheck' => 'off', 'readonly' => 'yes', 'disabled' => 1,
			'aria_label' => 'Main content'
		]);
		self::assertSame('editor-html', $editor->getOutput('html', true));
		self::assertSame('post_body', $data['id']);
		self::assertSame('content', $data['name']);
		self::assertSame('<strong>body</strong>', $data['value']);
		self::assertSame('Write', $data['placeholder']);
		self::assertSame('Main content', $data['ariaLabel']);
		self::assertSame(15, $data['rows']);
		self::assertFalse($data['spellcheck']);
		self::assertTrue($data['readonly']);
		self::assertTrue($data['disabled']);
		self::assertStringContainsString('editor-large', $data['className']);
	}

	public function testTextareaRowsAreClampedToSupportedLimits(): void {
		$data = [];
		$editor = $this->makeTextarea($data);
		foreach (['-100' => 2, '0' => 2, '1' => 2, '1001' => 1000, '500' => 500] as $rows => $expected) {
			$editor->setData(['rows' => $rows]);
			$editor->getOutput();
			self::assertSame($expected, $data['rows']);
		}
		$editor->setData(['rows' => ['invalid']]);
		$editor->getOutput();
		self::assertSame(12, $data['rows']);
	}

	public function testTextareaBooleanOptionsRespectRecognizedStringForms(): void {
		$data = [];
		$editor = $this->makeTextarea($data);
		foreach (['true', 'YES', ' On ', '1'] as $text) {
			$editor->setData(['readonly' => $text]);
			$editor->getOutput();
			self::assertTrue($data['readonly']);
		}
		foreach (['false', 'NO', ' off ', '0', ''] as $text) {
			$editor->setData(['spellcheck' => $text]);
			$editor->getOutput();
			self::assertFalse($data['spellcheck']);
		}
	}

	public function testTextareaUnsupportedBooleanValuesUseDefinedDefaults(): void {
		$data = [];
		$editor = $this->makeTextarea($data);
		$editor->setData(['readonly' => 'perhaps', 'spellcheck' => new \stdClass(), 'disabled' => ['true']]);
		$editor->getOutput();
		self::assertFalse($data['readonly']);
		self::assertTrue($data['spellcheck']);
		self::assertFalse($data['disabled']);
	}

	public function testTextareaNumericAndNullFieldsUseDocumentedConversions(): void {
		$data = [];
		$editor = $this->makeTextarea($data);
		$editor->setData(['id' => 42, 'name' => false, 'value' => 0.5, 'placeholder' => ['not scalar'], 'rows' => null,
			'readonly' => 1.0, 'disabled' => 0]);
		$editor->getOutput();
		self::assertSame('42', $data['id']);
		self::assertSame('', $data['name']);
		self::assertSame('0.5', $data['value']);
		self::assertSame('', $data['placeholder']);
		self::assertSame(12, $data['rows']);
		self::assertTrue($data['readonly']);
		self::assertFalse($data['disabled']);
	}

	public function testTextareaNonArrayInputClearsPreviousState(): void {
		$data = [];
		$editor = $this->makeTextarea($data);
		$editor->setData(['name' => 'old']);
		$editor->setData('invalid');
		$editor->getOutput();
		self::assertSame('', $data['name']);
	}

	public function testCkEditorDefaultOptionsAndAssets(): void {
		$data = [];
		$editor = $this->makeCkEditor($data);
		self::assertSame('ckeditorrichtexteditordisplay', $editor::getName());
		self::assertSame('editor-html', $editor->getOutput());
		self::assertSame(12, $data['rows']);
		self::assertSame(288, $data['minimumHeight']);
		self::assertTrue($data['spellcheck']);
		self::assertFalse($data['readonly']);
		self::assertSame('/resolved/plugin/ClientStack/assets/ckeditor/ckeditor5.js', $data['ckeditorModuleUrl']);
		self::assertSame('/resolved/plugin/ClientStack/assets/ckeditor/ckeditor5.css', $data['ckeditorCssUrl']);
		self::assertStringContainsString('CKEditor 5', $editor->getHelp());
	}

	public function testCkEditorMinimumHeightAndBoundaries(): void {
		$data = [];
		$editor = $this->makeCkEditor($data);
		$editor->setData(['rows' => 1, 'value' => 'Hello', 'readonly' => true]);
		$editor->getOutput();
		self::assertSame(2, $data['rows']);
		self::assertSame(120, $data['minimumHeight']);
		self::assertSame('Hello', $data['value']);
		self::assertTrue($data['readonly']);
		$editor->setData(['rows' => 9999, 'spellcheck' => false]);
		$editor->getOutput();
		self::assertSame(1000, $data['rows']);
		self::assertSame(24000, $data['minimumHeight']);
		self::assertFalse($data['spellcheck']);
	}

	public function testCkEditorBooleanAndScalarNormalization(): void {
		$data = [];
		$editor = $this->makeCkEditor($data);
		$editor->setData(['id' => 'source', 'name' => 15, 'class' => 'custom', 'rows' => 'bad',
			'spellcheck' => 'no', 'readonly' => ' true ', 'disabled' => 'maybe', 'aria_label' => ['bad']]);
		$editor->getOutput();
		self::assertSame('source', $data['id']);
		self::assertSame('15', $data['name']);
		self::assertSame(12, $data['rows']);
		self::assertFalse($data['spellcheck']);
		self::assertTrue($data['readonly']);
		self::assertFalse($data['disabled']);
		self::assertSame('', $data['ariaLabel']);
		self::assertStringContainsString('custom', $data['className']);
	}

	public function testCkEditorNonArrayInputClearsPreviousState(): void {
		$data = [];
		$editor = $this->makeCkEditor($data);
		$editor->setData(['name' => 'old']);
		$editor->setData(null);
		$editor->getOutput();
		self::assertSame('', $data['name']);
	}
}
