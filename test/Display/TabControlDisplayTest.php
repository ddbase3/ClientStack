<?php declare(strict_types=1);

namespace ClientStack\Test\Display;

use Base3\Api\IClassMap;
use Base3\Api\IDisplay;
use Base3\Api\IMvcView;
use Base3\LinkTarget\Api\ILinkTargetService;
use ClientStack\Display\TabControlDisplay;
use PHPUnit\Framework\TestCase;

final class TabControlDisplayTest extends TestCase {

	public function testAjaxInitialLoadUsesTheConfiguredDisplayUrlWithoutRenderingTheDisplayOnTheServer(): void {
		$child = $this->createMock(IDisplay::class);
		$child->expects($this->never())->method('setData');
		$child->expects($this->never())->method('getOutput');

		$view = new TabControlTestMvcView();
		$control = $this->createControl($child, $view);
		$control->setData([
			'tabs' => $this->getTabs(),
			'initial_load_via_ajax' => true
		]);

		$html = $control->getOutput();

		$this->assertTrue($view->getAssigned('initialLoadViaAjax'));
		$this->assertSame('', $view->getAssigned('content'));
		$this->assertSame('chatbotconfigdisplay', $view->getAssigned('activeDisplay'));
		$this->assertSame('/ajax?name=chatbotconfigdisplay', $view->getAssigned('activeUrl'));
		$this->assertStringContainsString('data-base3-tab-initial-load="ajax"', $html);
		$this->assertStringContainsString('loadTarget(activeTarget, getUrl(initialLink));', $html);
	}

	public function testInitialServerRenderingRemainsTheDefault(): void {
		$child = $this->createMock(IDisplay::class);
		$child->expects($this->once())->method('setData')->with([
			'group' => 'uihk-chatbot',
			'name' => 'default'
		]);
		$child->expects($this->once())->method('getOutput')->with('html', false)->willReturn('RENDERED_DISPLAY');

		$view = new TabControlTestMvcView();
		$control = $this->createControl($child, $view);
		$control->setData(['tabs' => $this->getTabs()]);

		$html = $control->getOutput();

		$this->assertFalse($view->getAssigned('initialLoadViaAjax'));
		$this->assertSame('RENDERED_DISPLAY', $view->getAssigned('content'));
		$this->assertStringContainsString('data-base3-tab-initial-load="server"', $html);
		$this->assertStringContainsString('RENDERED_DISPLAY', $html);
	}


	public function testMissingDisplaysAndTheirEmptyGroupsAreOmittedWithoutError(): void {
		$existing = $this->createStub(IDisplay::class);
		$classmap = $this->createStub(IClassMap::class);
		$classmap->method('getInstanceByInterfaceName')->willReturnCallback(
			static fn(string $interface, string $name): ?IDisplay =>
				$name === 'chatbotconfigdisplay' ? $existing : null
		);
		$links = $this->createStub(ILinkTargetService::class);
		$links->method('getLink')->willReturn('/ajax?name=chatbotconfigdisplay');
		$view = new TabControlTestMvcView();
		$control = new TabControlDisplay($classmap, $view, $links);
		$control->setData(['tabs' => [
			[
				'name' => 'unavailable',
				'label' => 'Unavailable',
				'displays' => [['name' => 'notinstalleddisplay', 'label' => 'Not installed']]
			],
			$this->getTabs()[0]
		]]);
		$control->getOutput();

		$tabs = $view->getAssigned('tabs');
		self::assertCount(1, $tabs);
		self::assertSame('chatbot', $tabs[0]['name']);
		self::assertSame('chatbotconfigdisplay', $view->getAssigned('activeDisplay'));
	}

	private function createControl(IDisplay $child, IMvcView $view): TabControlDisplay {
		$classmap = $this->createStub(IClassMap::class);
		$classmap->method('getInstanceByInterfaceName')->willReturn($child);

		$linkTargetService = $this->createStub(ILinkTargetService::class);
		$linkTargetService->method('getLink')->willReturn('/ajax?name=chatbotconfigdisplay');

		return new TabControlDisplay($classmap, $view, $linkTargetService);
	}

	private function getTabs(): array {
		return [[
			'name' => 'chatbot',
			'label' => 'Chatbot',
			'displays' => [[
				'name' => 'chatbotconfigdisplay',
				'label' => 'Configuration',
				'data' => [
					'group' => 'uihk-chatbot',
					'name' => 'default'
				]
			]]
		]];
	}
}

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
