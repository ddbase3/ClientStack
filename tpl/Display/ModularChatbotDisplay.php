<?php
	$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
	$strings = $this->_['strings'];
	$clientConfig = [
		'serviceUrl' => $this->_['serviceUrl'],
		'serviceId' => $this->_['serviceId'],
		'turnPrepareUrl' => $this->_['turnPrepareUrl'],
		'configGroup' => $this->_['configGroup'],
		'configName' => $this->_['configName'],
		'transportMode' => $this->_['transportMode'],
		'messageIcons' => $this->_['messageIcons'],
		'strings' => $strings
	];
	$pluginConfig = [
		'markdown' => [
			'scriptUrl' => $this->_['markedUrl']
		],
		'reference' => [
			'mode' => $this->_['referenceMode'],
			'reference' => $this->_['reference'],
			'provider' => $this->_['referenceProvider']
		],
		'message-actions' => [
			'icons' => $this->_['icons']
		],
		'conversation' => [
			'enabled' => !empty($this->_['conversationEnabled']),
			'multiple' => !empty($this->_['chatHistoryEnabled']),
			'panelMode' => $this->_['chatHistoryPanelMode'],
			'automaticTitles' => !empty($this->_['automaticChatTitles']),
			'firstMessageMode' => $this->_['firstMessageMode'],
			'urls' => [
				'state' => $this->_['conversationStateUrl'],
				'create' => $this->_['conversationCreateUrl'],
				'materialize' => $this->_['conversationMaterializeUrl'],
				'activate' => $this->_['conversationActivateUrl'],
				'rename' => $this->_['conversationRenameUrl'],
				'delete' => $this->_['conversationDeleteUrl'],
				'title' => $this->_['conversationTitleUrl']
			],
			'icons' => [
				'list' => $this->_['icons']['list'],
				'plus' => $this->_['icons']['plus'],
				'edit' => $this->_['icons']['edit'],
				'delete' => $this->_['icons']['delete'],
				'save' => $this->_['icons']['check'],
				'close' => $this->_['icons']['close']
			],
			'strings' => $strings
		],
		'voice' => [
			'stt' => [
				'enabled' => true,
				'provider' => !empty($this->_['speechToTextSessionUrl']) ? 'backend' : 'browser',
				'sessionUrl' => $this->_['speechToTextSessionUrl']
			],
			'tts' => [
				'enabled' => true,
				'provider' => !empty($this->_['textToSpeechUrl']) ? 'backend' : 'browser',
				'speechUrl' => $this->_['textToSpeechUrl']
			],
			'dialog' => true,
			'lang' => $this->_['defaultLang'],
			'icons' => [
				'microphone' => $this->_['icons']['microphone'],
				'speaker' => $this->_['icons']['speaker'],
				'dialogue' => $this->_['icons']['dialogue']
			]
		]
	];
	$extensionPluginOptions = is_array($this->_['extensionPluginOptions'] ?? null)
		? $this->_['extensionPluginOptions']
		: [];
	$pluginConfig = array_replace_recursive($pluginConfig, $extensionPluginOptions);
	$extensions = is_array($this->_['extensions'] ?? null) ? $this->_['extensions'] : [];
	$id = htmlspecialchars($this->_['id'], ENT_QUOTES);
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($this->_['cssUrl'], ENT_QUOTES); ?>" />
<?php if(!empty($this->_['additionalStylesheetUrl'])) { ?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($this->_['additionalStylesheetUrl'], ENT_QUOTES); ?>" />
<?php } ?>

<section
	id="<?php echo $id; ?>"
	class="base3-chatbot"
	role="region"
	aria-label="<?php echo htmlspecialchars($strings['regionLabel'], ENT_QUOTES); ?>"
	data-chatbot-state="loading"
	data-ai-notice-position="<?php echo htmlspecialchars($this->_['aiNoticePosition'], ENT_QUOTES); ?>"
>
	<div class="base3-chatbot-conversation-backdrop" data-chatbot-conversation-backdrop hidden></div>

	<nav
		id="<?php echo $id; ?>-conversations"
		class="base3-chatbot-conversation-panel"
		data-chatbot-conversation-panel
		aria-label="<?php echo htmlspecialchars($strings['conversationNavigation'], ENT_QUOTES); ?>"
		aria-hidden="true"
		hidden
	>
		<div class="base3-chatbot-conversation-toolbar">
			<h2><?php echo htmlspecialchars($strings['conversationsHeading']); ?></h2>
			<button
				type="button"
				class="base3-chatbot-conversation-collapse"
				data-chatbot-conversation-collapse
				aria-label="<?php echo htmlspecialchars($strings['closeConversations'], ENT_QUOTES); ?>"
			>
				<img src="<?php echo htmlspecialchars($this->_['icons']['close'], ENT_QUOTES); ?>" alt="" aria-hidden="true" />
			</button>
		</div>
		<ul class="base3-chatbot-conversation-list" data-chatbot-conversation-list></ul>
	</nav>

	<h2
		class="base3-chatbot-opening-message base3-chatbot-initial-message"
		data-chatbot-opening-message
		hidden
	></h2>

	<div class="base3-chatbot-main" data-chatbot-main>
		<div
			class="base3-chatbot-messages is-empty"
			data-chatbot-messages
			role="log"
			aria-label="<?php echo htmlspecialchars($strings['chatLogLabel'], ENT_QUOTES); ?>"
			aria-relevant="additions"
			tabindex="0"
		></div>
		<div
			class="base3-chatbot-suggestions"
			data-chatbot-suggestions
			aria-label="<?php echo htmlspecialchars($strings['suggestionsLabel'], ENT_QUOTES); ?>"
		></div>
	</div>

	<aside
		class="base3-chatbot-canvas"
		data-chatbot-canvas
		aria-label="<?php echo htmlspecialchars($strings['canvasLabel'], ENT_QUOTES); ?>"
		aria-hidden="true"
		hidden
	>
		<header class="base3-chatbot-canvas-header">
			<div class="base3-chatbot-canvas-title" data-chatbot-canvas-title>
				<?php echo htmlspecialchars($strings['canvasTitle']); ?>
			</div>
			<button
				type="button"
				class="base3-chatbot-canvas-close"
				data-chatbot-canvas-close
				aria-label="<?php echo htmlspecialchars($strings['closeCanvas'], ENT_QUOTES); ?>"
			>×</button>
		</header>
		<div class="base3-chatbot-canvas-content" data-chatbot-canvas-content></div>
	</aside>

	<div class="base3-chatbot-composer" data-chatbot-composer>
		<label class="base3-chatbot-visually-hidden" for="<?php echo $id; ?>-input">
			<?php echo htmlspecialchars($strings['messageLabel']); ?>
		</label>
		<textarea
			id="<?php echo $id; ?>-input"
			class="base3-chatbot-input"
			data-chatbot-input
			name="prompt"
			rows="1"
			aria-label="<?php echo htmlspecialchars($strings['messageInputLabel'], ENT_QUOTES); ?>"
			aria-describedby="<?php echo $id; ?>-ai-notice"
		></textarea>

		<div class="base3-chatbot-composer-overlay" data-chatbot-slot="composer-overlay"></div>

		<div class="base3-chatbot-actions">
			<div class="base3-chatbot-actions-left">
				<div class="base3-chatbot-slot" data-chatbot-slot="composer-start"></div>
			</div>
			<div class="base3-chatbot-actions-right">
				<div class="base3-chatbot-slot" data-chatbot-slot="composer-end"></div>
				<button
					type="button"
					class="base3-chatbot-send"
					data-chatbot-send
					aria-label="<?php echo htmlspecialchars($strings['sendMessage'], ENT_QUOTES); ?>"
				>
					<img src="<?php echo htmlspecialchars($this->_['icons']['send'], ENT_QUOTES); ?>" alt="" aria-hidden="true" />
				</button>
			</div>
		</div>
	</div>

	<p id="<?php echo $id; ?>-ai-notice" class="base3-chatbot-ai-notice">
		<img class="base3-chatbot-ai-notice-icon" src="<?php echo htmlspecialchars($this->_['icons']['info'], ENT_QUOTES); ?>" alt="" aria-hidden="true" />
		<span><?php echo htmlspecialchars($this->_['aiNoticeText']); ?></span>
	</p>

	<div class="base3-chatbot-visually-hidden" data-chatbot-status role="status" aria-live="polite"></div>

	<dialog
		class="base3-chatbot-delete-dialog"
		data-chatbot-conversation-delete-dialog
		aria-labelledby="<?php echo $id; ?>-delete-dialog-title"
		aria-describedby="<?php echo $id; ?>-delete-dialog-text"
	>
		<h2 id="<?php echo $id; ?>-delete-dialog-title"><?php echo htmlspecialchars($strings['deleteDialogTitle']); ?></h2>
		<p id="<?php echo $id; ?>-delete-dialog-text" data-chatbot-conversation-delete-text></p>
		<div class="base3-chatbot-dialog-actions">
			<button type="button" class="base3-chatbot-button" data-chatbot-conversation-delete-cancel>
				<?php echo htmlspecialchars($strings['cancel']); ?>
			</button>
			<button type="button" class="base3-chatbot-button base3-chatbot-button-danger" data-chatbot-conversation-delete-confirm>
				<?php echo htmlspecialchars($strings['deleteConfirm']); ?>
			</button>
		</div>
	</dialog>
</section>

<script type="module">
	const root = document.getElementById(<?php echo json_encode($this->_['id'], $jsonFlags); ?>);
	const moduleUrl = <?php echo json_encode($this->_['moduleUrl'], $jsonFlags); ?>;
	const baseConfig = <?php echo json_encode($clientConfig, $jsonFlags); ?>;
	const pluginOptions = <?php echo json_encode($pluginConfig, $jsonFlags); ?>;
	const extensionDefinitions = <?php echo json_encode($extensions, $jsonFlags); ?>;

	try {
		const client = await import(moduleUrl);
		const plugins = [
			client.ReferencePlugin,
			client.AgentActivityPlugin,
			client.AgentInteractionPlugin,
			client.CanvasPlugin,
			client.SuggestionsPlugin
		];
<?php if(!empty($this->_['useMarkdown'])) { ?>
		plugins.push(client.MarkdownPlugin);
<?php } ?>
<?php if(!empty($this->_['useIcons'])) { ?>
		plugins.push(client.MessageActionsPlugin);
<?php } ?>
<?php if(!empty($this->_['useVoice'])) { ?>
		plugins.push(client.VoicePlugin);
<?php } ?>

		for(const definition of extensionDefinitions) {
			const extensionModule = await import(definition.moduleUrl);
			const extensionPlugin = extensionModule[definition.exportName];
			if(!extensionPlugin || typeof extensionPlugin !== 'object') {
				throw new Error(`Chatbot extension "${definition.name}" did not export "${definition.exportName}".`);
			}
			if(extensionPlugin.name !== definition.name) {
				throw new Error(`Chatbot extension "${definition.name}" exported plugin name "${extensionPlugin.name || ''}".`);
			}

			plugins.push(extensionPlugin);
			pluginOptions[definition.name] = {
				...(pluginOptions[definition.name] || {}),
				...(definition.options || {})
			};
		}
<?php if(!empty($this->_['conversationEnabled'])) { ?>
		if(client.ConversationPlugin) {
			plugins.push(client.ConversationPlugin);
		}
<?php } ?>

		await client.mountChatbot(root, {
			...baseConfig,
			plugins,
			pluginOptions
		});
	} catch(error) {
		root.dataset.chatbotState = 'error';
		const errorMessage = document.createElement('p');
		errorMessage.className = 'base3-chatbot-error';
		errorMessage.textContent = <?php echo json_encode($strings['initializationError'], $jsonFlags); ?>;
		root.querySelector('[data-chatbot-messages]').appendChild(errorMessage);
		console.error(error);
	}
</script>
