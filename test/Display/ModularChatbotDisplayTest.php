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
		$view = new ModularChatbotFakeMvcView([
			'showConversations' => 'Verläufe anzeigen'
		]);
		$display = new ModularChatbotDisplay($view, new ModularChatbotFakeAssetResolver());
		$display->setData([
			'id' => 'chatbot-a',
			'service_url' => '/chatbot/service',
			'service' => 'chatbotservice',
			'turn_prepare_url' => '/chatbot/prepare',
			'speech_to_text_session_url' => '/chatbot/stt-session',
			'text_to_speech_url' => '/chatbot/tts',
			'extensions' => [[
				'name' => 'custom-renderer',
				'module_url' => '/plugin/CustomChatbotExtension/CustomRendererPlugin.js',
				'export_name' => 'CustomRendererPlugin',
				'options' => ['mode' => 'compact']
			]],
			'use_voice' => false,
			'conversation_enabled' => true,
			'chat_history_enabled' => true,
			'chat_history_panel_mode' => 'open',
			'automatic_chat_titles' => true,
			'first_message_mode' => 'contextual_ai',
			'ai_notice_text' => 'AI can make mistakes.',
			'ai_notice_position' => 'below_composer',
			'dom_classes' => [
				'root' => ['customer-chatbot', 'theme-dark'],
				'main' => 'customer-chat',
				'composer' => ['customer-prompt']
			],
			'additional_stylesheet' => 'plugin/Customer/assets/css/chatbot.css',
			'message_icons' => [
				'user' => '',
				'assistant' => '',
				'thinking' => 'plugin/Customer/assets/icons/thinking.svg',
				'opening' => 'plugin/Customer/assets/icons/opening.svg'
			],
			'conversation_state_url' => '/chatbot/conversations/state',
			'conversation_create_url' => '/chatbot/conversations/create',
			'conversation_materialize_url' => '/chatbot/conversations/materialize',
			'conversation_activate_url' => '/chatbot/conversations/activate',
			'conversation_rename_url' => '/chatbot/conversations/rename',
			'conversation_delete_url' => '/chatbot/conversations/delete',
			'conversation_title_url' => '/chatbot/conversations/title',
			'strings' => [
				'thinking' => 'Custom thinking'
			]
		]);

		$output = $display->getOutput();

		$this->assertSame(DIR_PLUGIN . 'ClientStack', $view->getLastPath());
		$this->assertSame('ModularChatbotDisplay', $view->getLoadedBrickSet());
		$this->assertSame('Display/ModularChatbotDisplay.php', $view->getLastTemplate());
		$this->assertSame('chatbot-a', $view->getAssigned('id'));
		$this->assertSame('/chatbot/service', $view->getAssigned('serviceUrl'));
		$this->assertSame('chatbotservice', $view->getAssigned('serviceId'));
		$this->assertSame('/chatbot/prepare', $view->getAssigned('turnPrepareUrl'));
		$this->assertSame('/chatbot/stt-session', $view->getAssigned('speechToTextSessionUrl'));
		$this->assertSame('/chatbot/tts', $view->getAssigned('textToSpeechUrl'));
		$this->assertSame('custom-renderer', $view->getAssigned('extensions')[0]['name'] ?? null);
		$this->assertSame('CustomRendererPlugin', $view->getAssigned('extensions')[0]['exportName'] ?? null);
		$this->assertSame([], $view->getAssigned('extensionPluginOptions'));
		$this->assertFalse($view->getAssigned('useVoice'));
		$this->assertTrue($view->getAssigned('conversationEnabled'));
		$this->assertTrue($view->getAssigned('chatHistoryEnabled'));
		$this->assertSame('open', $view->getAssigned('chatHistoryPanelMode'));
		$this->assertTrue($view->getAssigned('automaticChatTitles'));
		$this->assertSame('contextual_ai', $view->getAssigned('firstMessageMode'));
		$this->assertSame('AI can make mistakes.', $view->getAssigned('aiNoticeText'));
		$this->assertSame('below_composer', $view->getAssigned('aiNoticePosition'));
		$this->assertSame([
			'root' => ['customer-chatbot', 'theme-dark'],
			'main' => 'customer-chat',
			'composer' => ['customer-prompt']
		], $view->getAssigned('domClasses'));
		$this->assertSame('/resolved/plugin/Customer/assets/css/chatbot.css', $view->getAssigned('additionalStylesheetUrl'));
		$this->assertSame('', $view->getAssigned('messageIcons')['user'] ?? null);
		$this->assertSame('/resolved/plugin/Customer/assets/icons/thinking.svg', $view->getAssigned('messageIcons')['thinking'] ?? null);
		$this->assertSame('/resolved/plugin/Customer/assets/icons/opening.svg', $view->getAssigned('messageIcons')['opening'] ?? null);
		$this->assertSame('/chatbot/conversations/state', $view->getAssigned('conversationStateUrl'));
		$this->assertSame('/chatbot/conversations/materialize', $view->getAssigned('conversationMaterializeUrl'));
		$this->assertSame('/chatbot/conversations/title', $view->getAssigned('conversationTitleUrl'));
		$this->assertSame('Verläufe anzeigen', $view->getAssigned('strings')['showConversations'] ?? null);
		$this->assertSame('Custom thinking', $view->getAssigned('strings')['thinking'] ?? null);
		$this->assertSame(
			'/resolved/plugin/ClientStack/assets/modularchatbot/index.js',
			$view->getAssigned('moduleUrl')
		);
		$this->assertSame(
			'/resolved/plugin/ClientStack/assets/modularchatbot/icons/edit.svg',
			$view->getAssigned('icons')['edit'] ?? null
		);
		$this->assertSame('/resolved/plugin/ClientStack/assets/modularchatbot/icons/info.svg', $view->getAssigned('icons')['info'] ?? null);
		$this->assertSame('FAKE_TEMPLATE_OUTPUT', $output);
	}


	public function testInvalidAiNoticePositionFallsBackToAboveComposer(): void {
		$view = new ModularChatbotFakeMvcView();
		$display = new ModularChatbotDisplay($view, new ModularChatbotFakeAssetResolver());
		$display->setData([
			'ai_notice_position' => 'sideways'
		]);

		$display->getOutput();

		$this->assertSame('above_composer', $view->getAssigned('aiNoticePosition'));
	}

	public function testClientUrlsAreDecodedBeforeTheyEnterJavaScriptConfiguration(): void {
		$view = new ModularChatbotFakeMvcView();
		$display = new ModularChatbotDisplay($view, new ModularChatbotFakeAssetResolver());
		$display->setData([
			'conversation_state_url' => '/service?name=state&amp;config_name=example',
			'conversation_create_url' => '/service?name=create&amp;config_name=example',
			'conversation_materialize_url' => '/service?name=materialize&amp;config_name=example'
		]);

		$display->getOutput();

		$this->assertSame(
			'/service?name=state&config_name=example',
			$view->getAssigned('conversationStateUrl')
		);
		$this->assertSame(
			'/service?name=create&config_name=example',
			$view->getAssigned('conversationCreateUrl')
		);
		$this->assertSame(
			'/service?name=materialize&config_name=example',
			$view->getAssigned('conversationMaterializeUrl')
		);
	}

	public function testTemplateUsesCanonicalOpeningMessageContract(): void {
		$template = file_get_contents(DIR_PLUGIN . 'ClientStack/tpl/Display/ModularChatbotDisplay.php');

		$this->assertIsString($template);
		$this->assertStringContainsString('data-chatbot-opening-message', $template);
		$this->assertStringNotContainsString('data-chatbot-base-prompt', $template);
	}

	public function testTemplateExposesDomClassConfigurationAndStableDetailTargets(): void {
		$template = file_get_contents(DIR_PLUGIN . 'ClientStack/tpl/Display/ModularChatbotDisplay.php');

		$this->assertIsString($template);
		$this->assertStringContainsString("'domClasses' => (object) \$this->_['domClasses']", $template);
		$this->assertStringContainsString('data-chatbot-actions', $template);
		$this->assertStringContainsString('data-chatbot-ai-notice', $template);
	}

	public function testTemplateInstallsResponseExtensionsBeforeConversationHydration(): void {
		$template = file_get_contents(DIR_PLUGIN . 'ClientStack/tpl/Display/ModularChatbotDisplay.php');

		$this->assertIsString($template);
		$extensionPosition = strpos($template, 'for(const definition of extensionDefinitions)');
		$conversationPosition = strpos($template, 'plugins.push(client.ConversationPlugin)');
		$this->assertIsInt($extensionPosition);
		$this->assertIsInt($conversationPosition);
		$this->assertLessThan($conversationPosition, $extensionPosition);
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
