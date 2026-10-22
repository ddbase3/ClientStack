<?php declare(strict_types=1);

namespace ClientStack\Display;

use Base3\Api\IDisplay;
use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\Authorization\Api\IAuthorizationService;
use Base3\Authorization\Dto\AuthorizationRequest;
use Base3\Diagnostics\Api\IDiagnosticRunner;
use Base3\Diagnostics\Dto\DiagnosticCheckResult;
use Base3\Diagnostics\Feature\DiagnosticReportsFeature;
use Base3\LinkTarget\Api\ILinkTargetService;
use Base3\Session\Api\ISession;
use Base3\Translation\Api\ITranslation;
use Base3\Usermanager\Permission;
use InvalidArgumentException;
use Throwable;

/** Generic, privilege-protected, sequential AJAX UI for a diagnostic scope. */
final class DiagnosticScopeDisplay implements IDisplay {

	private const CSRF_KEY = 'diagnostics.checks.csrf';
	private const SCOPE_PARAMETER = 'diagnostic_scope';

	private array $data = [];

	public function __construct(
		private readonly IDiagnosticRunner $runner,
		private readonly IAuthorizationService $authorization,
		private readonly ITranslation $translation,
		private readonly IRequest $request,
		private readonly ISession $session,
		private readonly IMvcView $view,
		private readonly ILinkTargetService $links
	) {}

	public static function getName(): string {
		return 'diagnosticscopedisplay';
	}

	public function setData($data) {
		$this->data = is_array($data) ? $data : [];
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		$out = strtolower($out);
		if(!in_array($out, ['html', 'json'], true)) {
			throw new InvalidArgumentException('Unsupported diagnostic output.');
		}
		if(!$this->isAuthorized()) {
			return $this->errorResponse(403, $out, $this->ui('forbidden', 'Access denied.'), $final);
		}

		$scope = $this->requestedScope();
		$selectedScope = null;
		foreach($this->runner->getScopes() as $candidate) {
			if($candidate::getName() === $scope) {
				$selectedScope = $candidate;
				break;
			}
		}
		if($selectedScope === null) {
			return $this->errorResponse(404, $out, $this->ui('scope_not_found', 'Diagnostic scope not found.'), $final);
		}

		if($out === 'json') {
			return $this->handleJson($scope, $final);
		}
		return $this->handleHtml($scope);
	}

	private function isAuthorized(): bool {
		$authority = $this->authorization->getDefaultAuthority();
		if(!$this->authorization->decide(new AuthorizationRequest(
			$authority,
			Permission::for('system', 'admin')
		))->isAllowed()) {
			return false;
		}
		return $this->authorization->decide(new AuthorizationRequest(
			$authority,
			Permission::for('feature', DiagnosticReportsFeature::getName())
		))->isAllowed();
	}

	private function requestedScope(): string {
		$value = $this->request->get(self::SCOPE_PARAMETER, '');
		if(is_string($value) && trim($value) !== '') {
			return trim($value);
		}
		$value = $this->data['scope'] ?? '';
		return is_string($value) ? trim($value) : '';
	}

	private function handleHtml(string $scope): string {
		$checks = [];
		foreach($this->runner->getChecks($scope) as $check) {
			$name = $check::getName();
			$checks[] = [
				'name' => $name,
				'title' => $this->translation->translate('DiagnosticChecks', $name, 'title', $name),
				'description' => $this->translation->translate('DiagnosticChecks', $name, 'description', '')
			];
		}

		$this->view->setPath(dirname(__DIR__, 2));
		$this->view->setTemplate('Display/DiagnosticScopeDisplay.php');
		$this->view->assign('id', 'base3-diagnostic-' . bin2hex(random_bytes(6)));
		$this->view->assign('scope', $scope);
		$this->view->assign('title', $this->translation->translate('DiagnosticScopes', $scope, 'title', $scope));
		$this->view->assign('description', $this->translation->translate('DiagnosticScopes', $scope, 'description', ''));
		$this->view->assign('checks', $checks);
		$this->view->assign('endpoint', $this->links->getLink(
			['name' => self::getName(), 'out' => 'json'],
			[self::SCOPE_PARAMETER => $scope]
		));
		$this->view->assign('csrf', $this->csrfToken());
		$keys = [
			'summary', 'total', 'passed', 'failed', 'warning', 'error', 'not_applicable',
			'pending', 'all', 'search', 'search_placeholder', 'run_again', 'checking',
			'finished', 'progress', 'remediation', 'no_checks', 'no_results',
			'request_failed', 'run_started', 'run_finished', 'filter_label', 'unknown',
			'export_json', 'export_copied', 'export_failed'
		];
		$labels = [];
		foreach($keys as $key) {
			$labels[$key] = $this->ui($key, $key);
		}
		$this->view->assign('labels', $labels);
		return $this->view->loadTemplate();
	}

	private function handleJson(string $scope, bool $final): string {
		if(strtoupper((string)$this->request->server('REQUEST_METHOD', '')) !== 'POST') {
			return $this->errorResponse(405, 'json', $this->ui('request_failed', 'Check could not be executed.'), $final);
		}
		$csrf = $this->request->post('diagnostic_csrf', '');
		$expected = $this->session->get(self::CSRF_KEY);
		if(!is_string($csrf) || !is_string($expected) || !hash_equals($expected, $csrf)) {
			return $this->errorResponse(403, 'json', $this->ui('request_failed', 'Check could not be executed.'), $final);
		}
		$name = $this->request->post('diagnostic_check', '');
		if(!is_string($name) || !preg_match('/^[a-z][a-z0-9]*$/D', $name)) {
			return $this->errorResponse(400, 'json', $this->ui('request_failed', 'Check could not be executed.'), $final);
		}

		try {
			$result = $this->runner->run($scope, $name);
		} catch(InvalidArgumentException $exception) {
			return $this->errorResponse(404, 'json', $this->ui('request_failed', 'Check could not be executed.'), $final);
		} catch(Throwable $exception) {
			$result = new DiagnosticCheckResult(DiagnosticCheckResult::ERROR, 'error');
		}

		$details = [];
		foreach($result->details as $key => $value) {
			$details[] = [
				'label' => $this->translation->translate('DiagnosticChecks', $name, 'detail_' . $key, $key),
				'value' => $value
			];
		}
		return $this->jsonResponse(200, [
			'status' => $result->status,
			'message' => $this->translation->translate('DiagnosticChecks', $name, $result->messageKey, $result->messageKey, $result->replacements),
			'remediation' => $result->remediationKey !== ''
				? $this->translation->translate('DiagnosticChecks', $name, $result->remediationKey, $result->remediationKey, $result->replacements)
				: '',
			'details' => $details
		], $final);
	}

	private function csrfToken(): string {
		if(!$this->session->started()) {
			throw new \RuntimeException('An active session is required for diagnostic checks.');
		}
		$token = $this->session->get(self::CSRF_KEY);
		if(!is_string($token) || strlen($token) !== 64) {
			$token = bin2hex(random_bytes(32));
			$this->session->set(self::CSRF_KEY, $token);
		}
		return $token;
	}

	private function ui(string $key, string $fallback): string {
		return $this->translation->translate('Diagnostics', 'ui', $key, $fallback);
	}

	private function errorResponse(int $status, string $format, string $message, bool $final): string {
		if($format === 'json') {
			return $this->jsonResponse($status, ['error' => $message], $final);
		}
		if($final) http_response_code($status);
		return '<p role="alert">' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
	}

	/** @param array<string,mixed> $data */
	private function jsonResponse(int $status, array $data, bool $final): string {
		if($final) {
			http_response_code($status);
			if(!headers_sent()) {
				header('Content-Type: application/json; charset=utf-8');
				header('Cache-Control: no-store');
			}
		}
		return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

}
