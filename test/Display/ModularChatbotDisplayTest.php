<?php declare(strict_types=1);

namespace ClientStack\Test\Display;

use Base3\Api\IAssetResolver;
use Base3\Api\IMvcView;
use ClientStack\Display\ModularChatbotDisplay;
use PHPUnit\Framework\TestCase;

class ModularChatbotDisplayTest extends TestCase {

	public function testGetNameReturnsExpectedValue(): void {
		$this->assertSame('modularchatbotdisplay', ModularChatbotDisplay::getName());
	}

	public function testGetOutputAssignsModuleConfiguration(): void {
		$view = new ModularChatbotFakeMvcView();
		$display = new ModularChatbotDisplay($view, new ModularChatbotFakeAssetResolver());
		$display->setData([
			'id' => 'chatbot-a',
			'service_url' => '/chatbot/service',
			'service' => 'chatbotservice',
			'turn_prepare_url' => '/chatbot/prepare',
			'speech_to_text_session_url' => '/chatbot/stt-session',
			'text_to_speech_url' => '/chatbot/tts',
			'use_voice' => false
		]);

		$output = $display->getOutput();

		$this->assertSame(DIR_PLUGIN . 'ClientStack', $view->getLastPath());
		$this->assertSame('Display/ModularChatbotDisplay.php', $view->getLastTemplate());
		$this->assertSame('chatbot-a', $view->getAssigned('id'));
		$this->assertSame('/chatbot/service', $view->getAssigned('serviceUrl'));
		$this->assertSame('chatbotservice', $view->getAssigned('serviceId'));
		$this->assertSame('/chatbot/prepare', $view->getAssigned('turnPrepareUrl'));
		$this->assertSame('/chatbot/stt-session', $view->getAssigned('speechToTextSessionUrl'));
		$this->assertSame('/chatbot/tts', $view->getAssigned('textToSpeechUrl'));
		$this->assertFalse($view->getAssigned('useVoice'));
		$this->assertSame(
			'/resolved/plugin/ClientStack/assets/modularchatbot/index.js',
			$view->getAssigned('moduleUrl')
		);
		$this->assertSame('FAKE_TEMPLATE_OUTPUT', $output);
	}

	public function testGetHelpReturnsExpectedText(): void {
		$display = new ModularChatbotDisplay(new ModularChatbotFakeMvcView(), new ModularChatbotFakeAssetResolver());

		$this->assertSame('Renders the modular native ES module Chatbot client.', $display->getHelp());
	}
}

class ModularChatbotFakeAssetResolver implements IAssetResolver {

	public function resolve(string $path): string {
		return '/resolved/' . $path;
	}
}

class ModularChatbotFakeMvcView implements IMvcView {

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
