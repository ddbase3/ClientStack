<?php declare(strict_types=1);

namespace ClientStack\Display;

use Base3\Api\IAssetResolver;
use Base3\Api\IMvcView;
use UiFoundation\Api\IChatbotDisplay;

final class ModularChatbotDisplay implements IChatbotDisplay {

	private const DEFAULT_STRINGS = [
		'regionLabel' => 'Chatbot',
		'conversationNavigation' => 'Chat conversations',
		'conversationsHeading' => 'Chats',
		'closeConversations' => 'Close chat list',
		'showConversations' => 'Show chat list',
		'newConversation' => 'Start new chat',
		'renameConversation' => 'Rename chat',
		'deleteConversation' => 'Delete chat',
		'titleLabel' => 'Chat title',
		'saveTitle' => 'Save title',
		'cancel' => 'Cancel',
		'deleteDialogTitle' => 'Delete chat',
		'deleteQuestionPrefix' => 'Delete the chat “',
		'deleteQuestionSuffix' => '”?',
		'deleteConfirm' => 'Delete',
		'conversationLoaded' => 'Chat loaded.',
		'conversationCreated' => 'New chat created.',
		'conversationRenamed' => 'Chat renamed.',
		'conversationDeleted' => 'Chat deleted.',
		'busy' => 'The current request must finish first.',
		'requestFailed' => 'The chat request failed.',
		'conversationUnavailable' => 'Chat history is not available.',
		'conversationLoading' => 'Loading chats…',
		'chatLogLabel' => 'Chat history',
		'suggestionsLabel' => 'Suggestions',
		'canvasLabel' => 'Canvas',
		'canvasTitle' => 'Canvas',
		'closeCanvas' => 'Close canvas',
		'messageLabel' => 'Message',
		'messageInputLabel' => 'Enter message',
		'sendMessage' => 'Send message',
		'initializationError' => 'The chatbot could not be initialized.'
	];

	private array $data = [];

	public function __construct(
		private readonly IMvcView $view,
		private readonly IAssetResolver $assetResolver
	) {}

	public static function getName(): string {
		return 'modularchatbotdisplay';
	}

	public function setData($data) {
		$this->data = is_array($data) ? $data : [];
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		$config = array_merge([
			'id' => '',
			'service_url' => '',
			'service' => '',
			'turn_prepare_url' => '',
			'config_group' => '',
			'config_name' => '',
			'use_markdown' => true,
			'use_mathjax' => false,
			'use_icons' => true,
			'use_voice' => true,
			'conversation_enabled' => false,
			'chat_history_enabled' => false,
			'chat_history_panel_mode' => 'responsive',
			'automatic_chat_titles' => true,
			'ai_notice_text' => '',
			'conversation_state_url' => '',
			'conversation_create_url' => '',
			'conversation_materialize_url' => '',
			'conversation_activate_url' => '',
			'conversation_rename_url' => '',
			'conversation_delete_url' => '',
			'conversation_title_url' => '',
			'transport_mode' => 'auto',
			'reference_mode' => 'url',
			'reference' => [],
			'reference_provider' => '',
			'default_lang' => 'auto',
			'speech_to_text_session_url' => '',
			'text_to_speech_url' => ''
		], $this->data);

		$id = trim((string)$config['id']);
		if($id === '') {
			$id = 'base3-chatbot-' . bin2hex(random_bytes(6));
		}

		$this->view->setPath(DIR_PLUGIN . 'ClientStack');
		$this->view->loadBricks('ModularChatbotDisplay');
		$this->view->setTemplate('Display/ModularChatbotDisplay.php');
		$this->view->assign('id', $id);
		$this->view->assign('serviceUrl', $this->normalizeClientUrl((string)$config['service_url']));
		$this->view->assign('serviceId', (string)$config['service']);
		$this->view->assign('turnPrepareUrl', $this->normalizeClientUrl((string)$config['turn_prepare_url']));
		$this->view->assign('configGroup', (string)$config['config_group']);
		$this->view->assign('configName', (string)$config['config_name']);
		$this->view->assign('useMarkdown', (bool)$config['use_markdown']);
		$this->view->assign('useMathJax', (bool)$config['use_mathjax']);
		$this->view->assign('useIcons', (bool)$config['use_icons']);
		$this->view->assign('useVoice', (bool)$config['use_voice']);
		$this->view->assign('conversationEnabled', (bool)$config['conversation_enabled']);
		$this->view->assign('chatHistoryEnabled', (bool)$config['chat_history_enabled']);
		$this->view->assign('chatHistoryPanelMode', (string)$config['chat_history_panel_mode']);
		$this->view->assign('automaticChatTitles', (bool)$config['automatic_chat_titles']);
		$this->view->assign('aiNoticeText', trim((string)$config['ai_notice_text']));
		$this->view->assign('conversationStateUrl', $this->normalizeClientUrl((string)$config['conversation_state_url']));
		$this->view->assign('conversationCreateUrl', $this->normalizeClientUrl((string)$config['conversation_create_url']));
		$this->view->assign('conversationMaterializeUrl', $this->normalizeClientUrl((string)$config['conversation_materialize_url']));
		$this->view->assign('conversationActivateUrl', $this->normalizeClientUrl((string)$config['conversation_activate_url']));
		$this->view->assign('conversationRenameUrl', $this->normalizeClientUrl((string)$config['conversation_rename_url']));
		$this->view->assign('conversationDeleteUrl', $this->normalizeClientUrl((string)$config['conversation_delete_url']));
		$this->view->assign('conversationTitleUrl', $this->normalizeClientUrl((string)$config['conversation_title_url']));
		$this->view->assign('transportMode', (string)$config['transport_mode']);
		$this->view->assign('referenceMode', (string)$config['reference_mode']);
		$this->view->assign('reference', is_array($config['reference']) ? $config['reference'] : []);
		$this->view->assign('referenceProvider', (string)$config['reference_provider']);
		$this->view->assign('defaultLang', (string)$config['default_lang']);
		$this->view->assign('speechToTextSessionUrl', $this->normalizeClientUrl((string)$config['speech_to_text_session_url']));
		$this->view->assign('textToSpeechUrl', $this->normalizeClientUrl((string)$config['text_to_speech_url']));
		$this->view->assign('strings', $this->getStrings());
		$this->view->assign(
			'moduleUrl',
			$this->resolveVersionedAsset('plugin/ClientStack/assets/modularchatbot/index.js')
		);
		$this->view->assign(
			'cssUrl',
			$this->resolveVersionedAsset('plugin/ClientStack/assets/modularchatbot/styles/chatbot.css')
		);
		$this->view->assign(
			'markedUrl',
			$this->assetResolver->resolve('plugin/ClientStack/assets/marked/marked.js')
		);
		$this->view->assign(
			'mathJaxUrl',
			$this->assetResolver->resolve('plugin/ClientStack/assets/mathjax/tex-mml-chtml.js')
		);
		$this->view->assign('icons', [
			'send' => $this->resolveIcon('send'),
			'copy' => $this->resolveIcon('copy'),
			'check' => $this->resolveIcon('check'),
			'thumbsup' => $this->resolveIcon('thumbsup'),
			'thumbsupfill' => $this->resolveIcon('thumbsupfill'),
			'thumbsdown' => $this->resolveIcon('thumbsdown'),
			'thumbsdownfill' => $this->resolveIcon('thumbsdownfill'),
			'microphone' => $this->resolveIcon('microphone'),
			'speaker' => $this->resolveIcon('speaker'),
			'dialogue' => $this->resolveIcon('dialogue'),
			'list' => $this->resolveIcon('list'),
			'plus' => $this->resolveIcon('plus'),
			'edit' => $this->resolveIcon('edit'),
			'delete' => $this->resolveIcon('delete'),
			'close' => $this->resolveIcon('close')
		]);

		return $this->view->loadTemplate();
	}

	public function getHelp(): string {
		return 'Renders the modular native ES module Chatbot client.';
	}

	/** @return array<string,string> */
	private function getStrings(): array {
		$bricks = $this->view->getBricks('modularchatbot');
		if(!is_array($bricks)) {
			return self::DEFAULT_STRINGS;
		}

		$strings = self::DEFAULT_STRINGS;
		foreach($strings as $key => $default) {
			$value = $bricks[$key] ?? null;
			if(is_scalar($value) && trim((string)$value) !== '') {
				$strings[$key] = trim((string)$value);
			}
		}
		return $strings;
	}

	private function normalizeClientUrl(string $url): string {
		return html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}

	private function resolveVersionedAsset(string $logicalPath): string {
		$url = $this->assetResolver->resolve($logicalPath);
		$prefix = 'plugin/ClientStack/';
		if(!str_starts_with($logicalPath, $prefix)) {
			return $url;
		}

		$file = DIR_PLUGIN . 'ClientStack/' . substr($logicalPath, strlen($prefix));
		if(!is_file($file)) {
			return $url;
		}

		$hash = hash_file('sha256', $file);
		if(!is_string($hash) || $hash === '') {
			return $url;
		}

		return $url . (str_contains($url, '?') ? '&' : '?') . 'v=' . substr($hash, 0, 12);
	}

	private function resolveIcon(string $name): string {
		return $this->assetResolver->resolve(
			'plugin/ClientStack/assets/modularchatbot/icons/' . $name . '.svg'
		);
	}
}
