<?php declare(strict_types=1);

namespace ClientStack\Display;

use Base3\Api\IDisplay;
use Base3\Api\IRequest;
use Base3\Authorization\Api\IAuthorizationService;
use Base3\Authorization\Dto\AuthorizationRequest;
use Base3\Session\Api\ISession;
use Base3\Usermanager\Api\IUsermanager;
use Base3\Usermanager\Permission;
use Base3\Usermanager\User;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use UiFoundation\Api\IAuthorizationAudienceAdministrationService;

/** Authorized, read-only user option source shared by the groups and features editors. */
final class AuthorizationAudienceUserSearchDisplay implements IDisplay {

	private const CSRF_KEYS = [
		'groups' => 'authorization.groups.csrf',
		'features' => 'authorization.features.csrf'
	];
	private const MAX_LIMIT = 30;
	private const MAX_OFFSET = 100000;
	private const MAX_QUERY_BYTES = 256;

	public function __construct(
		private readonly IAuthorizationService $authorization,
		private readonly IAuthorizationAudienceAdministrationService $administration,
		private readonly IUsermanager $usermanager,
		private readonly IRequest $request,
		private readonly ISession $session
	) {}

	/**
	 * Return the unique class-map display name for the authorized user option endpoint.
	 * @return string Lowercase unnamespaced component identifier
	 * @sideeffects None
	 */
	public static function getName(): string { return 'authorizationaudienceusersearchdisplay'; }

	/**
	 * Ignore external display configuration; request parameters and the trusted session
	 * are the only inputs to this endpoint.
	 * @param mixed $data Ignored; this endpoint is not configurable through display data
	 * @return void
	 * @sideeffects None
	 */
	public function setData($data) {}

	/**
	 * Return minimal native user identity options for an authorized audience administrator.
	 * The source endpoint accepts form-encoded POST requests only; a valid editor session
	 * CSRF token and both BASE3 and host administration decisions are mandatory.
	 * Never returns email addresses, roles, other user attributes or full directories.
	 *
	 * @param string $out Output format; only json is supported
	 * @param bool $final Output finalization hint; this endpoint is always self-contained
	 * @return string JSON page or single resolved option, or a generic error object
	 * @sideeffects Sends JSON/no-store headers and an HTTP status; reads native user identities
	 */
	public function getOutput(string $out = 'html', bool $final = false): string {
		if ($out !== 'json') {
			http_response_code(404);
			return '';
		}
		if (!headers_sent()) {
			header('Content-Type: application/json; charset=utf-8');
			header('Cache-Control: private, no-store, max-age=0');
			header('X-Content-Type-Options: nosniff');
		}
		try {
			if (!$this->authorization->decide(new AuthorizationRequest(
				$this->authorization->getDefaultAuthority(), Permission::for('system', 'admin')
			))->isAllowed()) {
				return $this->error(403, 'Access denied.');
			}
			$this->administration->assertCanAdminister();
		} catch (Throwable $error) {
			return $this->error(403, 'Access denied.');
		}
		if (strtoupper((string)$this->request->server('REQUEST_METHOD', '')) !== 'POST') {
			return $this->error(405, 'POST required.');
		}
		try {
			$this->assertCsrf();
		} catch (Throwable $error) {
			return $this->error(403, 'Access denied.');
		}
		try {
			$action = $this->request->post('action', '');
			if ($action === 'search') return $this->search();
			if ($action === 'resolve') return $this->resolve();
			return $this->error(422, 'Invalid user option request.');
		} catch (InvalidArgumentException $error) {
			return $this->error(422, 'Invalid user search parameters.');
		} catch (Throwable $error) {
			return $this->error(500, 'User lookup unavailable.');
		}
	}

	private function assertCsrf(): void {
		$scope = $this->request->post('scope', '');
		$provided = $this->request->post('csrf', '');
		if (!is_string($scope) || !isset(self::CSRF_KEYS[$scope]) || !is_string($provided)
			|| !$this->session->started()) {
			throw new RuntimeException('Unauthorized audience user request.');
		}
		$expected = $this->session->get(self::CSRF_KEYS[$scope]);
		if (!is_string($expected) || strlen($expected) !== 64 || !hash_equals($expected, $provided)) {
			throw new RuntimeException('Unauthorized audience user request.');
		}
	}

	private function search(): string {
		$query = $this->request->post('query', '');
		$limit = $this->readInteger('limit', 20, 1, self::MAX_LIMIT);
		$offset = $this->readInteger('offset', 0, 0, self::MAX_OFFSET);
		if (!is_string($query) || strlen($query) > self::MAX_QUERY_BYTES) {
			throw new InvalidArgumentException('Invalid search term.');
		}
		$query = trim($query);
		if ($query === '') return $this->json(['items' => [], 'hasMore' => false]);

		$users = $this->usermanager->searchUsers($query, $limit + 1, $offset);
		$items = [];
		foreach (array_slice($users, 0, $limit) as $user) {
			$option = self::project($user);
			if ($option !== null) $items[] = $option;
		}
		return $this->json(['items' => $items, 'hasMore' => count($users) > $limit]);
	}

	private function resolve(): string {
		$id = $this->request->post('id', '');
		if ((!is_string($id) && !is_int($id)) || !preg_match('/^[1-9][0-9]*$/D', (string)$id)
			|| strlen((string)$id) > 19 || filter_var((string)$id, FILTER_VALIDATE_INT) === false) {
			throw new InvalidArgumentException('Invalid user ID.');
		}
		$user = $this->usermanager->getUserById((string)$id);
		$option = self::project($user);
		if ($option !== null && $option['value'] !== (string)$id) $option = null;
		return $this->json(['item' => $option]);
	}

	private function readInteger(string $field, int $default, int $minimum, int $maximum): int {
		$value = $this->request->post($field, $default);
		if ((!is_string($value) && !is_int($value)) || !preg_match('/^[0-9]+$/D', (string)$value)) {
			throw new InvalidArgumentException('Invalid pagination value.');
		}
		$number = filter_var((string)$value, FILTER_VALIDATE_INT);
		if ($number === false || $number < $minimum || $number > $maximum) {
			throw new InvalidArgumentException('Pagination outside allowed range.');
		}
		return $number;
	}

	/** @return array{value:string,label:string}|null */
	private static function project(mixed $user): ?array {
		if (!$user instanceof User || (!is_string($user->id) && !is_int($user->id))) return null;
		$id = (string)$user->id;
		// The audience editor intentionally stores positive native numeric IDs.
		if (!preg_match('/^[1-9][0-9]*$/D', $id) || strlen($id) > 19) return null;
		$name = is_scalar($user->name) ? trim((string)$user->name) : '';
		$login = is_scalar($user->userid) ? trim((string)$user->userid) : '';
		$label = $name !== '' ? $name : ($login !== '' ? $login : 'User');
		if ($login !== '' && $login !== $label) $label .= ' (' . $login . ')';
		return ['value' => $id, 'label' => $label . ' [ID ' . $id . ']'];
	}

	/** @param array<string,mixed> $value */
	private function json(array $value): string {
		return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	private function error(int $status, string $message): string {
		http_response_code($status);
		return $this->json(['success' => false, 'message' => $message]);
	}
}
