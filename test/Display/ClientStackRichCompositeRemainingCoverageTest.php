<?php declare(strict_types=1);

namespace ClientStack\Test\Display;

use Base3\Api\IAssetResolver;
use Base3\Api\IClassMap;
use Base3\Api\IDisplay;
use Base3\Api\IMvcView;
use ClientStack\Display\CkEditorRichTextEditorDisplay;
use ClientStack\Display\CompositeDisplay;
use ClientStack\Display\TextareaRichTextEditorDisplay;
use PHPUnit\Framework\TestCase;

final class ClientStackRichCompositeRemainingCoverageTest extends TestCase {

	private function makeView(array &$assigned): IMvcView {
		$view = $this->createStub(IMvcView::class);
		$view->method('assign')->willReturnCallback(static function(string $name, mixed $value) use (&$assigned): void {
			$assigned[$name] = $value;
		});
		$view->method('loadTemplate')->willReturn('display-html');
		return $view;
	}

	public function testCkEditorAcceptsNumericBooleanValuesWithoutConvertingThemToDefault(): void {
		$assigned = [];
		$assets = $this->createStub(IAssetResolver::class);
		$assets->method('resolve')->willReturn('/editor-asset');
		$editor = new CkEditorRichTextEditorDisplay($this->makeView($assigned), $assets);
		$editor->setData(['id' => 'editor', 'spellcheck' => 0, 'readonly' => 2, 'disabled' => 0.0]);
		self::assertSame('display-html', $editor->getOutput());
		self::assertFalse($assigned['spellcheck']);
		self::assertTrue($assigned['readonly']);
		self::assertFalse($assigned['disabled']);
	}

	public function testCkEditorNumericBooleansAlsoAcceptFloatingPointTruthyValues(): void {
		$assigned = [];
		$assets = $this->createStub(IAssetResolver::class);
		$assets->method('resolve')->willReturn('/editor-asset');
		$editor = new CkEditorRichTextEditorDisplay($this->makeView($assigned), $assets);
		$editor->setData(['id' => 'editor', 'spellcheck' => 0.5, 'disabled' => 5]);
		$editor->getOutput();
		self::assertTrue($assigned['spellcheck']);
		self::assertTrue($assigned['disabled']);
	}

	public function testTextareaAcceptsLiteralBooleanValuesWithoutFallback(): void {
		$assigned = [];
		$editor = new TextareaRichTextEditorDisplay($this->makeView($assigned));
		$editor->setData(['id' => 'editor', 'spellcheck' => false, 'readonly' => true, 'disabled' => false]);
		self::assertSame('display-html', $editor->getOutput());
		self::assertFalse($assigned['spellcheck']);
		self::assertTrue($assigned['readonly']);
		self::assertFalse($assigned['disabled']);
	}

	public function testCompositeRejectsNonArrayItemsWithoutLosingOtherConfiguration(): void {
		$assigned = [];
		$map = $this->createMock(IClassMap::class);
		$map->expects(self::never())->method('getInstanceByInterfaceName');
		$display = new CompositeDisplay($map, $this->makeView($assigned));
		foreach (['invalid', new \stdClass(), 14, null] as $items) {
			$display->setData(['items' => $items, 'columns' => 3]);
			self::assertSame('display-html', $display->getOutput());
			self::assertSame([], $assigned['items']);
			self::assertSame(3, $assigned['columns']);
		}
	}

	public function testCompositeEmptyTitleAndMissingDataHaveDefinedChildContracts(): void {
		$assigned = [];
		$child = $this->createMock(IDisplay::class);
		$child->expects(self::once())->method('setData')->with(null);
		$child->expects(self::once())->method('getOutput')->with('html', false)->willReturn('child-html');
		$map = $this->createStub(IClassMap::class);
		$map->method('getInstanceByInterfaceName')->willReturn($child);
		$display = new CompositeDisplay($map, $this->makeView($assigned));
		$display->setData(['items' => [[
			'id' => 'child', 'display' => 'childdisplay', 'title' => ['invalid'], 'span' => 'not-numeric'
		]]]);
		self::assertSame('display-html', $display->getOutput());
		self::assertSame([[
			'id' => 'child', 'display' => 'childdisplay', 'title' => '', 'span' => 1, 'content' => 'child-html'
		]], $assigned['items']);
	}

	public function testCompositeDoesNotTreatNonDisplaysAsDiscoverableChildren(): void {
		$assigned = [];
		$map = $this->createStub(IClassMap::class);
		$map->method('getInstanceByInterfaceName')->willReturn(new \stdClass());
		$display = new CompositeDisplay($map, $this->makeView($assigned));
		$display->setData(['items' => [['id' => 'child', 'display' => 'not-a-display']]]);
		self::assertSame('display-html', $display->getOutput());
		self::assertSame([], $assigned['items']);
	}
}
