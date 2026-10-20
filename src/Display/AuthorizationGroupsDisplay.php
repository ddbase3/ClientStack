<?php declare(strict_types=1);

namespace ClientStack\Display;

use Base3\Api\IDisplay;
use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\Authorization\Api\IAuthorizationService;
use Base3\Authorization\Dto\AuthorizationRequest;
use Base3\LinkTarget\Api\ILinkTargetService;
use Base3\Session\Api\ISession;
use Base3\Usermanager\Permission;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use UiFoundation\Api\IAuthorizationAudienceAdministrationService;

/** One host-neutral BASE3 groups administration display; host owns authorization and storage. */
final class AuthorizationGroupsDisplay implements IDisplay {

	private const CSRF_KEY = 'authorization.groups.csrf';

	public function __construct(
		private readonly IAuthorizationService $authorization,
		private readonly IAuthorizationAudienceAdministrationService $administration,
		private readonly IRequest $request,
		private readonly ISession $session,
		private readonly IMvcView $view,
		private readonly ILinkTargetService $links
	) {}

	public static function getName(): string { return 'authorizationgroupsdisplay'; }

	public function setData($data) {}

	public function getOutput(string $out = 'html', bool $final = false): string {
		if ($out !== 'html') throw new InvalidArgumentException('Administration supports HTML only.');
		if (!$this->authorization->decide(new AuthorizationRequest(
			$this->authorization->getDefaultAuthority(), Permission::for('system', 'admin')
		))->isAllowed()) {
			http_response_code(403);
			return '<p role="alert">Access denied.</p>';
		}
		try {
			$this->administration->assertCanAdminister();
			$action = $this->request->post('base3_group_action', '');
			$message = '';
			if ($action !== '') {
				$this->assertCsrf();
				switch ($action) {
					case 'save':
						$rows = $this->request->post('base3_group_rules', []);
						if (!is_array($rows)) throw new InvalidArgumentException('Invalid group rules.');
						$this->administration->replaceGroupRules($rows, trim((string)$this->request->post('base3_default_group', '')));
						$message = 'Group memberships saved.';
						break;
					case 'create':
						$this->administration->createGroup((string)$this->request->post('base3_group_name', ''), (string)$this->request->post('base3_group_description', ''));
						$message = 'BASE3 group created.';
						break;
					case 'remove':
						$this->administration->removeGroup((string)$this->request->post('base3_group_name', ''));
						$message = 'BASE3 group removed.';
						break;
					default:
						throw new InvalidArgumentException('Invalid BASE3 group action.');
				}
			}
			$data = $this->administration->getGroupView();
			$this->view->setPath(dirname(__DIR__, 2));
			$this->view->loadBricks('Display');
			$this->view->setTemplate('Display/AuthorizationGroupsDisplay.php');
			$this->view->assign('translations', array_merge(
				$this->view->getBricks('authorization_groups_display') ?? [],
				$this->view->getBricks('authorization_audience_editor') ?? []
			));
			foreach ($data as $name => $value) $this->view->assign($name, $value);
			$this->view->assign('csrf', $this->csrfToken());
			$this->view->assign('message', $message);
			$this->view->assign('endpoint', $this->links->getLink(['name' => self::getName(), 'out' => 'html']));
			return $this->view->loadTemplate();
		} catch (Throwable $error) {
			// The UI never discloses host authorization details or persisted audiences on failure.
			http_response_code(403);
			return '<p role="alert">Authorization administration is unavailable or the request was rejected.</p>';
		}
	}

	private function assertCsrf(): void {
		$expected = $this->session->get(self::CSRF_KEY);
		$provided = $this->request->post('base3_group_csrf', '');
		if (!is_string($expected) || !is_string($provided) || !hash_equals($expected, $provided)) {
			throw new InvalidArgumentException('Invalid authorization administration CSRF token.');
		}
	}

	private function csrfToken(): string {
		if (!$this->session->started()) throw new RuntimeException('An active session is required.');
		$token = $this->session->get(self::CSRF_KEY);
		if (!is_string($token) || strlen($token) !== 64) {
			$token = bin2hex(random_bytes(32));
			$this->session->set(self::CSRF_KEY, $token);
		}
		return $token;
	}
}
