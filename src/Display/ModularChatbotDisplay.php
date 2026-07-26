<?php declare(strict_types=1);

namespace ClientStack\Display;

use Base3\Api\IAssetResolver;
use Base3\Api\IMvcView;
use UiFoundation\Api\IChatbotDisplay;

final class ModularChatbotDisplay implements IChatbotDisplay {

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
			'use_threads' => true,
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
		$this->view->setTemplate('Display/ModularChatbotDisplay.php');
		$this->view->assign('id', $id);
		$this->view->assign('serviceUrl', (string)$config['service_url']);
		$this->view->assign('serviceId', (string)$config['service']);
		$this->view->assign('turnPrepareUrl', (string)$config['turn_prepare_url']);
		$this->view->assign('configGroup', (string)$config['config_group']);
		$this->view->assign('configName', (string)$config['config_name']);
		$this->view->assign('useMarkdown', (bool)$config['use_markdown']);
		$this->view->assign('useIcons', (bool)$config['use_icons']);
		$this->view->assign('useVoice', (bool)$config['use_voice']);
		$this->view->assign('useThreads', (bool)$config['use_threads']);
		$this->view->assign('transportMode', (string)$config['transport_mode']);
		$this->view->assign('referenceMode', (string)$config['reference_mode']);
		$this->view->assign('reference', is_array($config['reference']) ? $config['reference'] : []);
		$this->view->assign('referenceProvider', (string)$config['reference_provider']);
		$this->view->assign('defaultLang', (string)$config['default_lang']);
		$this->view->assign('speechToTextSessionUrl', (string)$config['speech_to_text_session_url']);
		$this->view->assign('textToSpeechUrl', (string)$config['text_to_speech_url']);
		$this->view->assign(
			'moduleUrl',
			$this->assetResolver->resolve('plugin/ClientStack/assets/modularchatbot/index.js')
		);
		$this->view->assign(
			'cssUrl',
			$this->assetResolver->resolve('plugin/ClientStack/assets/modularchatbot/styles/chatbot.css')
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
			'plus' => $this->resolveIcon('plus')
		]);

		return $this->view->loadTemplate();
	}

	public function getHelp(): string {
		return 'Renders the modular native ES module Chatbot client.';
	}

	private function resolveIcon(string $name): string {
		return $this->assetResolver->resolve(
			'plugin/ClientStack/assets/modularchatbot/icons/' . $name . '.svg'
		);
	}
}
