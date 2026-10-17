<?php declare(strict_types=1);

namespace ClientStack\Display;

use Base3\Api\IDisplay;
use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\LinkTarget\Api\ILinkTargetService;
use Base3\Settings\Api\ISettingsStore;
use Base3\Usermanager\Api\IUsermanager;
use Base3\Usermanager\Permission;
use ClientStack\GlobalChatbotLauncher\GlobalChatbotLauncherSettings;
use Throwable;

/**
 * BASE3 administration display for the host-independent chatbot launcher.
 */
final class GlobalChatbotLauncherConfigDisplay implements IDisplay {

	private array $data = [];

	public function __construct(
		private readonly IMvcView $view,
		private readonly IRequest $request,
		private readonly ISettingsStore $settingsStore,
		private readonly ILinkTargetService $linkTargetService,
		private readonly IUsermanager $usermanager
	) {}

	public static function getName(): string {
		return 'globalchatbotlauncherconfigdisplay';
	}

	public function getHelp(): string {
		return 'Configure the global chatbot launcher presentation independently of individual chatbot settings.';
	}

	public function setData($data) {
		$this->data = is_array($data) ? $data : [];
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		if (!$this->usermanager->can(Permission::for('system', 'admin'))) {
			http_response_code(403);
			return $out === 'json'
				? json_encode(['success' => false, 'message' => 'Access denied.'], JSON_THROW_ON_ERROR)
				: '<p role="alert">Access denied.</p>';
		}

		if ($out === 'json') {
			return $this->saveFromRequest();
		}
		if ($out !== 'html') return '';

		$this->view->setPath(dirname(__DIR__, 2));
		$this->view->setTemplate('Display/GlobalChatbotLauncherConfigDisplay.php');
		$this->view->assign('title', (string)($this->data['title'] ?? 'Global chatbot launcher'));
		$this->view->assign('description', (string)($this->data['description'] ?? 'Choose launcher window modes and navigation behaviour.'));
		$this->view->assign('settings', GlobalChatbotLauncherSettings::get($this->settingsStore));
		$this->view->assign('save_url', $this->linkTargetService->getLink(
			['name' => self::getName(), 'out' => 'json'],
			['action' => 'save']
		));
		return $this->view->loadTemplate();
	}

	private function saveFromRequest(): string {
		if ($this->request->request('action') !== 'save'
			|| $this->request->getContext() === IRequest::CONTEXT_WEB_GET) {
			http_response_code(405);
			return json_encode(['success' => false, 'message' => 'Invalid save request.'], JSON_THROW_ON_ERROR);
		}

		$submitted = $this->request->allPost();
		if ($submitted === []) $submitted = $this->request->getJsonBody();
		$values = [];
		foreach (GlobalChatbotLauncherSettings::DEFAULTS as $key => $default) {
			$values[$key] = $submitted[$key] ?? false;
		}

		try {
			$configuration = GlobalChatbotLauncherSettings::save($this->settingsStore, $values);
			return json_encode(['success' => true, 'settings' => $configuration], JSON_THROW_ON_ERROR);
		} catch (\InvalidArgumentException $e) {
			http_response_code(422);
			return json_encode(['success' => false, 'message' => $e->getMessage()], JSON_THROW_ON_ERROR);
		} catch (Throwable $e) {
			http_response_code(500);
			return json_encode(['success' => false, 'message' => 'Launcher configuration could not be saved.'], JSON_THROW_ON_ERROR);
		}
	}
}
