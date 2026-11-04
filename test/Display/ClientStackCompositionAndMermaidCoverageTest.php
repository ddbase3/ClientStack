<?php declare(strict_types=1);

namespace ClientStack\Test\Display;

use Base3\Api\IAssetResolver;
use Base3\Api\IClassMap;
use Base3\Api\IDisplay;
use Base3\Api\IMvcView;
use ClientStack\Display\CompositeDisplay;
use ClientStack\Display\MermaidDisplay;
use PHPUnit\Framework\TestCase;

final class ClientStackCompositionAndMermaidCoverageTest extends TestCase {

	private function view(array &$variables): IMvcView {
		$view = $this->createStub(IMvcView::class);
		$view->method('assign')->willReturnCallback(
			static function (string $key, mixed $value) use (&$variables): void { $variables[$key] = $value; }
		);
		$view->method('loadTemplate')->willReturn('rendered');
		return $view;
	}

	private function composite(array &$variables, ?IDisplay $child = null): CompositeDisplay {
		$classmap = $this->createStub(IClassMap::class);
		$classmap->method('getInstanceByInterfaceName')->willReturn($child);
		return new CompositeDisplay($classmap, $this->view($variables));
	}

	public function testMermaidSkipsRenderingInFinalResponse(): void {
		$view = $this->createMock(IMvcView::class);
		$view->expects(self::never())->method('loadTemplate');
		$assets = $this->createMock(IAssetResolver::class);
		$assets->expects(self::never())->method('resolve');
		$display = new MermaidDisplay($view, $assets);
		self::assertSame('mermaiddisplay', $display::getName());
		self::assertSame('', $display->getOutput('html', true));
	}

	public function testMermaidRendersUserDiagramAndResolvedBundledAsset(): void {
		$variables = [];
		$assets = $this->createMock(IAssetResolver::class);
		$assets->expects(self::once())->method('resolve')
			->with('plugin/ClientStack/assets/mermaid/mermaid.min.js')->willReturn('/assets/mermaid.js');
		$display = new MermaidDisplay($this->view($variables), $assets);
		$display->setData(['mermaid' => 'flowchart TD; A-->B']);
		self::assertSame('rendered', $display->getOutput());
		self::assertSame('flowchart TD; A-->B', $variables['mermaid']);
		self::assertSame('/assets/mermaid.js', $variables['mermaidUrl']);
		self::assertStringContainsString('Mermaid', $display->getHelp());
	}

	public function testMermaidNormalizesNonArrayAndNumericInput(): void {
		$variables = [];
		$assets = $this->createStub(IAssetResolver::class);
		$display = new MermaidDisplay($this->view($variables), $assets);
		$display->setData(['mermaid' => 123]);
		$display->getOutput();
		self::assertSame('123', $variables['mermaid']);
		$display->setData('not an array');
		$display->getOutput();
		self::assertSame('', $variables['mermaid']);
	}

	public function testCompositeWithoutItemsShowsConfiguredDefaults(): void {
		$variables = [];
		$display = $this->composite($variables);
		self::assertSame('compositedisplay', $display::getName());
		self::assertSame('rendered', $display->getOutput());
		self::assertSame([], $variables['items']);
		self::assertSame(2, $variables['columns']);
		self::assertSame('No displays are available.', $variables['emptyMessage']);
		self::assertTrue(str_starts_with($variables['compositeId'], 'base3-composite-display-'));
		self::assertStringContainsString('responsive grid', $display->getHelp());
	}

	public function testCompositeRendersChildOnceWithItsOwnData(): void {
		$variables = [];
		$child = $this->createMock(IDisplay::class);
		$child->expects(self::once())->method('setData')->with(['value' => 42]);
		$child->expects(self::once())->method('getOutput')->with('html', false)->willReturn('<p>ok</p>');
		$display = $this->composite($variables, $child);
		$display->setData(['columns' => 3, 'items' => [[
			'id' => 'task', 'display' => 'tasksdisplay', 'title' => '  Tasks ', 'span' => 2, 'data' => ['value' => 42]
		]]]);
		$display->getOutput();
		self::assertSame(3, $variables['columns']);
		self::assertSame([[
			'id' => 'task', 'display' => 'tasksdisplay', 'title' => 'Tasks', 'span' => 2, 'content' => '<p>ok</p>'
		]], $variables['items']);
	}

	public function testCompositeFallsBackToNameAndOmitsDuplicateIds(): void {
		$variables = [];
		$child = $this->createStub(IDisplay::class);
		$child->method('getOutput')->willReturn('content');
		$display = $this->composite($variables, $child);
		$display->setData(['items' => [
			['name' => 'one', 'display' => 'child'],
			['name' => 'one', 'display' => 'child'],
			['name' => 'two', 'display' => 'child']
		]]);
		$display->getOutput();
		self::assertSame(['one', 'two'], array_column($variables['items'], 'id'));
	}

	public function testCompositeRejectsIncompleteAndRecursiveEntries(): void {
		$variables = [];
		$child = $this->createStub(IDisplay::class);
		$display = $this->composite($variables, $child);
		$display->setData(['items' => [
			'wrong', [], ['id' => 3], ['display' => 'child'],
			['id' => 'recursive', 'display' => 'compositedisplay']
		]]);
		$display->getOutput();
		self::assertSame([], $variables['items']);
	}

	public function testCompositeFiltersMissingChildDisplays(): void {
		$variables = [];
		$display = $this->composite($variables);
		$display->setData(['items' => [['id' => 'removed', 'display' => 'missing']]]);
		$display->getOutput();
		self::assertSame([], $variables['items']);
	}

	public function testCompositeClampsColumnsAndSpans(): void {
		$variables = [];
		$child = $this->createStub(IDisplay::class);
		$display = $this->composite($variables, $child);
		foreach ([[-5, 1], [0, 1], ['not numeric', 2], [99, 4], [3, 3]] as [$input, $expected]) {
			$display->setData(['columns' => $input, 'items' => [[
				'id' => 'one', 'display' => 'child', 'span' => 99
			]]]);
			$display->getOutput();
			self::assertSame($expected, $variables['columns']);
			self::assertSame($expected, $variables['items'][0]['span']);
		}
	}

	public function testCompositeRejectsInvalidSpanAndUsesOne(): void {
		$variables = [];
		$child = $this->createStub(IDisplay::class);
		$display = $this->composite($variables, $child);
		$display->setData(['columns' => 4, 'items' => [
			['id' => 'a', 'display' => 'child', 'span' => 'invalid'],
			['id' => 'b', 'display' => 'child', 'span' => -99]
		]]);
		$display->getOutput();
		self::assertSame([1, 1], array_column($variables['items'], 'span'));
	}

	public function testCompositeCustomEmptyMessageAcceptsScalarsOnly(): void {
		$variables = [];
		$display = $this->composite($variables);
		$display->setData(['empty_message' => 123]);
		$display->getOutput();
		self::assertSame('123', $variables['emptyMessage']);
		$display->setData(['empty_message' => ['bad']]);
		$display->getOutput();
		self::assertSame('No displays are available.', $variables['emptyMessage']);
	}

	public function testCompositeInvalidTopLevelStateIsEmpty(): void {
		$variables = [];
		$display = $this->composite($variables);
		$display->setData(['items' => 'bad']);
		$display->setData(42);
		$display->getOutput();
		self::assertSame([], $variables['items']);
	}
}
