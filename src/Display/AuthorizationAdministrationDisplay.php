<?php declare(strict_types=1);

namespace ClientStack\Display;

use Base3\Api\IDisplay;
use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\Authorization\Api\IAuthorizationAdministrationService;
use Base3\Authorization\Api\IAuthorizationService;
use Base3\Authorization\Dto\AuthorizationGrant;
use Base3\Authorization\Dto\AuthorizationSubject;
use Base3\Authorization\Dto\ResourceReference;
use Base3\LinkTarget\Api\ILinkTargetService;
use Base3\Session\Api\ISession;
use Base3\Usermanager\Permission;
use InvalidArgumentException;
use Throwable;

/** Host-neutral native rights UI. Only the selected authoritative admin service interprets grants. */
final class AuthorizationAdministrationDisplay implements IDisplay {

	private const CSRF_KEY = 'authorization.native-grants.csrf';

	public function __construct(
		private readonly IAuthorizationService $authorization,
		private readonly IAuthorizationAdministrationService $administration,
		private readonly IRequest $request,
		private readonly ISession $session,
		private readonly IMvcView $view,
		private readonly ILinkTargetService $links
	) {}

	public static function getName(): string { return 'authorizationadministrationdisplay'; }

	public function setData($data) {}

	public function getOutput(string $out = 'html', bool $final = false): string {
		if ($out !== 'html') throw new InvalidArgumentException('Rights administration supports HTML only.');
		$authority = $this->input('authority', $this->authorization->getDefaultAuthority());
		$type = $this->input('resource_type', 'ref_id');
		$id = $this->input('resource_id', '');
		$message = '';
		$error = '';
		$permissions = [];
		$subjects = [];
		$grants = [];
		$capabilities = null;
		$resource = null;
		if ($id !== '') {
			try {
				$resource = new ResourceReference($authority, $type, $id);
				$capabilities = $this->administration->describe($authority, $resource);
				if (!$capabilities->canRead) throw new \RuntimeException('Native rights administration is not available for this resource.');
				$permissions = $this->administration->getPermissions($authority, $resource);
				$subjects = $this->administration->getSubjects($resource);
				if ($this->request->post('base3_rights_action', '') === 'replace') {
					$this->assertCsrf();
					if (!$capabilities->canWrite) throw new \RuntimeException('Native rights administration is read-only.');
					$selected = $this->subject($authority, $subjects);
					if (!$selected instanceof AuthorizationSubject || !$selected->editable) throw new InvalidArgumentException('Native subject cannot be edited.');
					$granted = $this->request->post('base3_permissions', []);
					if (!is_array($granted) || !array_is_list($granted)) throw new InvalidArgumentException('Invalid permissions.');
					$catalog = [];
					foreach ($permissions as $permission) $catalog[$permission->scope . '/' . $permission->permission] = $permission;
					$replacement = [];
					foreach ($granted as $key) {
						if (!is_string($key) || !isset($catalog[$key])) throw new InvalidArgumentException('Unknown native operation.');
						$replacement[$key] = $catalog[$key];
					}
					$this->administration->replaceGrants($resource, $selected->reference, array_values($replacement));
					$message = 'Native role grants saved.';
				}
				$grants = $this->administration->getGrants($resource);
			} catch (Throwable $exception) {
				// Never display host exception details or native grant metadata to unauthorized viewers.
				$error = 'Rights are unavailable or the requested change was rejected.';
				if ($this->request->post('base3_rights_action', '') === 'replace') http_response_code(403);
				$permissions = [];
				$subjects = [];
				$grants = [];
			}
		}
		$rows = [];
		foreach ($subjects as $subject) {
			if (!$subject instanceof AuthorizationSubject) continue;
			$direct = [];
			$inherited = [];
			foreach ($grants as $grant) {
				if (!$grant instanceof AuthorizationGrant || $grant->subject != $subject->reference) continue;
				$key = $grant->permission->scope . '/' . $grant->permission->permission;
				if ($grant->inherited === true) $inherited[] = $key;
				else $direct[] = $key;
			}
			$rows[] = ['subject' => $subject, 'direct' => array_unique($direct), 'inherited' => array_unique($inherited)];
		}
		$this->view->setPath(dirname(__DIR__, 2));
		$this->view->setTemplate('Display/AuthorizationAdministrationDisplay.php');
		foreach (['authority' => $authority, 'resource_type' => $type, 'resource_id' => $id,
			'permissions' => $permissions, 'rows' => $rows, 'capabilities' => $capabilities,
			'message' => $message, 'error' => $error,
			'csrf' => $this->csrfToken(),
			'endpoint' => $this->links->getLink(['name' => self::getName(), 'out' => 'html'])] as $key => $value) {
			$this->view->assign($key, $value);
		}
		return $this->view->loadTemplate();
	}

	private function input(string $name, string $default): string {
		$value = $this->request->request($name, $default);
		return is_string($value) || is_int($value) ? trim((string)$value) : '';
	}

	/** @param list<AuthorizationSubject> $subjects */
	private function subject(string $authority, array $subjects): ?AuthorizationSubject {
		$kind = $this->input('subject_kind', '');
		$id = $this->input('subject_id', '');
		foreach ($subjects as $candidate) {
			if ($candidate->reference->authority === $authority && $candidate->reference->kind === $kind && (string)$candidate->reference->id === $id) {
				return $candidate;
			}
		}
		return null;
	}

	private function assertCsrf(): void {
		$expected = $this->session->get(self::CSRF_KEY);
		$provided = $this->request->post('base3_rights_csrf', '');
		if (!is_string($expected) || !is_string($provided) || !hash_equals($expected, $provided)) {
			throw new InvalidArgumentException('Invalid rights administration CSRF token.');
		}
	}

	private function csrfToken(): string {
		if (!$this->session->started()) throw new \RuntimeException('An active session is required for rights administration.');
		$token = $this->session->get(self::CSRF_KEY);
		if (!is_string($token) || strlen($token) !== 64) {
			$token = bin2hex(random_bytes(32));
			$this->session->set(self::CSRF_KEY, $token);
		}
		return $token;
	}
}
