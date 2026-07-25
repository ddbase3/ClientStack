<?php declare(strict_types=1);

namespace ClientStack\Test\Display;

use Base3\Api\IAssetResolver;
use Base3\Api\IMvcView;
use ClientStack\Display\ClassicChatbotDisplay;
use PHPUnit\Framework\TestCase;

class ClassicChatbotDisplayTest extends TestCase {

	public function testGetNameReturnsExpectedValue(): void {
		$this->assertSame('classicchatbotdisplay', ClassicChatbotDisplay::getName());
	}

	public function testGetOutputRendersClientStackTemplateAndAssets(): void {
		$view = new FakeMvcView();
		$display = new ClassicChatbotDisplay($view, new FakeAssetResolver());
		$display->setData([
			'service_url' => '/chatbot/service',
			'service' => 'chatbotservice',
			'use_voice' => false,
			'transport_mode' => 'rest'
		]);

		$output = $display->getOutput();

		$this->assertSame(DIR_PLUGIN . 'ClientStack', $view->getLastPath());
		$this->assertSame('Display/ClassicChatbotDisplay.php', $view->getLastTemplate());
		$this->assertSame('/chatbot/service', $view->getAssigned('service_url'));
		$this->assertSame('chatbotservice', $view->getAssigned('service'));
		$this->assertFalse($view->getAssigned('use_voice'));
		$this->assertSame('rest', $view->getAssigned('transport_mode'));
		$this->assertTrue($view->getAssigned('use_markdown'));
		$this->assertTrue($view->getAssigned('use_threads'));
		$this->assertSame('FAKE_TEMPLATE_OUTPUT', $output);

		$resolve = $view->getAssigned('resolve');
		$this->assertIsCallable($resolve);
		$this->assertSame(
			'/resolved/plugin/ClientStack/assets/classicchatbot/chatbot/chatbot.js',
			$resolve('plugin/ClientStack/assets/classicchatbot/chatbot/chatbot.js')
		);
	}

	public function testGetHelpReturnsExpectedText(): void {
		$display = new ClassicChatbotDisplay(new FakeMvcView(), new FakeAssetResolver());

		$this->assertSame('Renders the classic Chatbot browser client.', $display->getHelp());
	}
}

class FakeAssetResolver implements IAssetResolver {

	public function resolve(string $path): string {
		return '/resolved/' . $path;
	}
}

class FakeMvcView implements IMvcView {

	private ?string $lastPath = null;
	private ?string $lastTemplate = null;
	private array $assigned = [];

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

	public function loadBricks(string $set, string $language = ''): void {}

	public function getBricks(string $set): ?array {
		return null;
	}

	public function getLastPath(): ?string {
		return $this->lastPath;
	}

	public function getLastTemplate(): ?string {
		return $this->lastTemplate;
	}

	public function getAssigned(string $tag): mixed {
		return $this->assigned[$tag] ?? null;
	}
}
