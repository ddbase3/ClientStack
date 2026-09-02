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
		'showConversations' => 'Show conversations',
		'newConversation' => 'Start new conversation',
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
		'conversationLoading' => 'Loading chats...',
		'chatLogLabel' => 'Chat history',
		'suggestionsLabel' => 'Suggestions',
		'canvasLabel' => 'Canvas',
		'canvasTitle' => 'Canvas',
		'closeCanvas' => 'Close canvas',
		'messageLabel' => 'Message',
		'messageInputLabel' => 'Enter message',
		'sendMessage' => 'Send message',
		'initializationError' => 'The chatbot could not be initialized.',
		'emptyResponse' => 'No visible response could be generated. Please try the request again.',
		'requestError' => 'A technical error occurred. The request could not be completed.',
		'technicalDetails' => 'Technical details',
		'thinking' => 'Preparing response',
		'interactionRequired' => 'Confirmation required',
		'interactionExpired' => 'Confirmation expired',
		'interactionApproved' => 'Approved',
		'interactionDenied' => 'Cancelled',
		'interactionSubmitted' => 'Change submitted',
		'interactionResolved' => 'Resolved',
		'interactionViaChat' => 'via chat message',
		'riskLevel' => 'Risk level: {level}',
		'riskLow' => 'Low',
		'riskMedium' => 'Medium',
		'riskHigh' => 'High',
		'approve' => 'Approve',
		'deny' => 'Cancel',
		'yesLabel' => 'Yes',
		'noLabel' => 'No',
		'agentStage' => 'Stage',
		'agentTool' => 'Tool',
		'agentFailed' => 'failed',
		'agentCompleted' => 'completed',
		'agentRunning' => 'running',
		'agentAwaitingApproval' => 'Awaiting approval',
		'agentAwaitingInput' => 'Awaiting input',
		'agentCached' => 'cached',
		'agentActivity' => 'Agent activity',
		'agentSteps' => 'Work steps',
		'agentParameters' => 'Parameters',
		'agentTurnId' => 'Turn ID',
		'agentLoop' => 'loop {iteration}',
		'agentAiIfNeeded' => 'AI if needed',
		'agentNoAi' => 'no AI',
		'agentDone' => 'done',
		'agentPreparing' => 'Preparing request',
		'agentCreatingResponse' => 'Creating response',
		'agentReviewingResult' => 'Reviewing result',
		'agentPlanning' => 'Planning approach',
		'agentPreparingContext' => 'Preparing context',
		'agentProcessingInformation' => 'Processing information',
		'agentPreparingNextStep' => 'Preparing next step',
		'agentProcessingRequest' => 'Processing request',
		'agentReviewingNextStep' => 'Reviewing next step',
		'agentRetrievingInformation' => 'Retrieving information',
		'copyResponse' => 'Copy response',
		'responseHelpful' => 'Response helpful',
		'responseNotHelpful' => 'Response not helpful',
		'listening' => 'Listening...',
		'startStopVoiceInput' => 'Start or stop voice input',
		'toggleVoiceOutput' => 'Turn voice output on or off',
		'toggleDialogMode' => 'Turn dialog mode on or off',
		'extensionLoading' => 'Creating content...'
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
			'use_icons' => true,
			'use_voice' => true,
			'use_dialog' => true,
			'speech_to_text_enabled' => true,
			'text_to_speech_enabled' => true,
			'conversation_enabled' => false,
			'chat_history_enabled' => false,
			'chat_history_panel_mode' => 'responsive',
			'automatic_chat_titles' => true,
			'first_message_mode' => 'none',
			'ai_notice_text' => '',
			'ai_notice_position' => 'above_composer',
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
			'text_to_speech_url' => '',
			'extensions' => [],
			'extension_plugin_options' => [],
			'dom_classes' => [],
			'additional_stylesheet' => '',
			'message_icons' => [],
			'strings' => []
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
		$this->view->assign('useIcons', (bool)$config['use_icons']);
		$this->view->assign('useVoice', (bool)$config['use_voice']);
		$this->view->assign('useDialog', (bool)$config['use_dialog']);
		$this->view->assign('speechToTextEnabled', (bool)$config['speech_to_text_enabled']);
		$this->view->assign('textToSpeechEnabled', (bool)$config['text_to_speech_enabled']);
		$this->view->assign('conversationEnabled', (bool)$config['conversation_enabled']);
		$this->view->assign('chatHistoryEnabled', (bool)$config['chat_history_enabled']);
		$this->view->assign('chatHistoryPanelMode', (string)$config['chat_history_panel_mode']);
		$this->view->assign('automaticChatTitles', (bool)$config['automatic_chat_titles']);
		$firstMessageMode = (string)$config['first_message_mode'];
		if(!in_array($firstMessageMode, ['none', 'random', 'contextual_ai'], true)) {
			$firstMessageMode = 'none';
		}
		$this->view->assign('firstMessageMode', $firstMessageMode);
		$this->view->assign('aiNoticeText', trim((string)$config['ai_notice_text']));
		$aiNoticePosition = (string)$config['ai_notice_position'];
		if(!in_array($aiNoticePosition, ['above_composer', 'below_composer'], true)) {
			$aiNoticePosition = 'above_composer';
		}
		$this->view->assign('aiNoticePosition', $aiNoticePosition);
		$this->view->assign('additionalStylesheetUrl', $this->resolveOptionalAsset((string)$config['additional_stylesheet']));
		$messageIcons = is_array($config['message_icons']) ? $config['message_icons'] : [];
		$this->view->assign('messageIcons', [
			'user' => $this->resolveOptionalAsset((string)($messageIcons['user'] ?? '')),
			'assistant' => $this->resolveOptionalAsset((string)($messageIcons['assistant'] ?? '')),
			'thinking' => $this->resolveOptionalAsset((string)($messageIcons['thinking'] ?? '')),
			'opening' => $this->resolveOptionalAsset((string)($messageIcons['opening'] ?? ''))
		]);
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
		$this->view->assign('extensions', $this->normalizeExtensions($config['extensions']));
		$this->view->assign(
			'extensionPluginOptions',
			is_array($config['extension_plugin_options']) ? $config['extension_plugin_options'] : []
		);
		$this->view->assign('domClasses', $config['dom_classes']);
		$this->view->assign(
			'strings',
			$this->getStrings(is_array($config['strings']) ? $config['strings'] : [])
		);
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
			'close' => $this->resolveIcon('close'),
			'info' => $this->resolveIcon('info')
		]);

		return $this->view->loadTemplate();
	}

	public function getHelp(): string {
		return 'Renders the modular native ES module Chatbot client.';
	}

	/** @return array<int,array<string,mixed>> */
	private function normalizeExtensions(mixed $extensions): array {
		if(!is_array($extensions)) {
			return [];
		}

		$result = [];
		foreach($extensions as $extension) {
			if(!is_array($extension)) {
				throw new \InvalidArgumentException('Chatbot extension definition must be an array.');
			}

			$name = trim((string)($extension['name'] ?? ''));
			$moduleUrl = $this->normalizeClientUrl((string)($extension['module_url'] ?? ''));
			$exportName = trim((string)($extension['export_name'] ?? ''));
			if(
				preg_match('/^[a-z0-9._-]+$/', $name) !== 1
				|| $moduleUrl === ''
				|| preg_match('/^[A-Za-z_$][A-Za-z0-9_$]*$/', $exportName) !== 1
			) {
				throw new \InvalidArgumentException('Chatbot extension definition is invalid.');
			}

			$options = $extension['options'] ?? [];
			if(!is_array($options)) {
				throw new \InvalidArgumentException('Chatbot extension options must be an array.');
			}

			$result[] = [
				'name' => $name,
				'moduleUrl' => $moduleUrl,
				'exportName' => $exportName,
				'options' => $options
			];
		}

		return $result;
	}

	/** @return array<string,string> */
	private function getStrings(array $overrides = []): array {
		$bricks = $this->view->getBricks('modularchatbot');
		$strings = self::DEFAULT_STRINGS;

		if(is_array($bricks)) {
			foreach($strings as $key => $default) {
				$value = $bricks[$key] ?? null;
				if(is_scalar($value) && trim((string)$value) !== '') {
					$strings[$key] = trim((string)$value);
				}
			}
		}

		foreach($overrides as $key => $value) {
			if(array_key_exists($key, $strings) && is_scalar($value) && trim((string)$value) !== '') {
				$strings[$key] = (string)$value;
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

	private function resolveOptionalAsset(string $logicalPath): string {
		$logicalPath = trim($logicalPath);
		return $logicalPath === '' ? '' : $this->assetResolver->resolve($logicalPath);
	}

	private function resolveIcon(string $name): string {
		return $this->resolveVersionedAsset(
			'plugin/ClientStack/assets/modularchatbot/icons/' . $name . '.svg'
		);
	}
}
