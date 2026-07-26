<?php
	$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
	$clientConfig = [
		'serviceUrl' => $this->_['serviceUrl'],
		'serviceId' => $this->_['serviceId'],
		'turnPrepareUrl' => $this->_['turnPrepareUrl'],
		'configGroup' => $this->_['configGroup'],
		'configName' => $this->_['configName'],
		'transportMode' => $this->_['transportMode']
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
		'threads' => [
			'icons' => [
				'list' => $this->_['icons']['list'],
				'plus' => $this->_['icons']['plus']
			]
		],
		'voice' => [
			'stt' => true,
			'tts' => true,
			'dialog' => true,
			'lang' => $this->_['defaultLang'],
			'icons' => [
				'microphone' => $this->_['icons']['microphone'],
				'speaker' => $this->_['icons']['speaker'],
				'dialogue' => $this->_['icons']['dialogue']
			]
		]
	];
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($this->_['cssUrl'], ENT_QUOTES); ?>" />

<section
	id="<?php echo htmlspecialchars($this->_['id'], ENT_QUOTES); ?>"
	class="base3-chatbot"
	role="region"
	aria-label="Chatbot"
	data-chatbot-state="loading"
>
	<p class="base3-chatbot-base-prompt" data-chatbot-base-prompt></p>

	<div class="base3-chatbot-main" data-chatbot-main>
		<div
			class="base3-chatbot-messages is-empty"
			data-chatbot-messages
			role="log"
			aria-label="Chatverlauf"
			aria-relevant="additions"
			tabindex="0"
		></div>
		<div class="base3-chatbot-suggestions" data-chatbot-suggestions aria-label="Vorschläge"></div>
	</div>

	<aside
		class="base3-chatbot-canvas"
		data-chatbot-canvas
		aria-label="Canvas"
		aria-hidden="true"
		hidden
	>
		<header class="base3-chatbot-canvas-header">
			<div class="base3-chatbot-canvas-title" data-chatbot-canvas-title>Canvas</div>
			<button
				type="button"
				class="base3-chatbot-canvas-close"
				data-chatbot-canvas-close
				aria-label="Canvas schließen"
			>×</button>
		</header>
		<div class="base3-chatbot-canvas-content" data-chatbot-canvas-content></div>
	</aside>

	<div class="base3-chatbot-composer" data-chatbot-composer>
		<label class="base3-chatbot-visually-hidden" for="<?php echo htmlspecialchars($this->_['id'], ENT_QUOTES); ?>-input">
			Nachricht
		</label>
		<textarea
			id="<?php echo htmlspecialchars($this->_['id'], ENT_QUOTES); ?>-input"
			class="base3-chatbot-input"
			data-chatbot-input
			name="prompt"
			rows="1"
			aria-label="Nachricht eingeben"
		></textarea>

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
					aria-label="Nachricht senden"
			>
					<img src="<?php echo htmlspecialchars($this->_['icons']['send'], ENT_QUOTES); ?>" alt="" aria-hidden="true" />
				</button>
			</div>
		</div>
	</div>
</section>

<script type="module">
	const root = document.getElementById(<?php echo json_encode($this->_['id'], $jsonFlags); ?>);
	const moduleUrl = <?php echo json_encode($this->_['moduleUrl'], $jsonFlags); ?>;
	const baseConfig = <?php echo json_encode($clientConfig, $jsonFlags); ?>;
	const pluginOptions = <?php echo json_encode($pluginConfig, $jsonFlags); ?>;

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
<?php if(!empty($this->_['useThreads'])) { ?>
		plugins.push(client.ThreadsPlugin);
<?php } ?>
<?php if(!empty($this->_['useVoice'])) { ?>
		plugins.push(client.VoicePlugin);
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
		errorMessage.textContent = 'Der Chatbot konnte nicht initialisiert werden.';
		root.querySelector('[data-chatbot-messages]').appendChild(errorMessage);
		console.error(error);
	}
</script>
