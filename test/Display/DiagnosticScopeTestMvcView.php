<?php declare(strict_types=1);

namespace ClientStack\Test\Display;

use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\Authorization\Api\IAuthorizationService;
use Base3\Authorization\Dto\AuthorizationDecision;
use Base3\Diagnostics\Api\IDiagnosticCheck;
use Base3\Diagnostics\Api\IDiagnosticRunner;
use Base3\Diagnostics\Dto\DiagnosticCheckResult;
use Base3\Diagnostics\Feature\DiagnosticReportsFeature;
use Base3\Diagnostics\Scope\PrivacyDiagnosticScope;
use Base3\Diagnostics\Scope\DependencyDiagnosticScope;
use Base3\LinkTarget\Api\ILinkTargetService;
use Base3\Session\Api\ISession;
use Base3\Translation\Api\ITranslation;
use ClientStack\Display\DiagnosticScopeDisplay;
use PHPUnit\Framework\TestCase;

final class DiagnosticScopeTestMvcView implements IMvcView {

	public array $_ = [];
	private string $path = '';
	private string $template = '';

	public function setPath(string $path = '.'): void { $this->path = $path; }
	public function assign(string $key, $value): void { $this->_[$key] = $value; }
	public function setTemplate(string $template = 'default'): void { $this->template = $template; }
	public function loadTemplate(): string {
		ob_start();
		include $this->path . '/tpl/' . $this->template;
		return (string)ob_get_clean();
	}
	public function loadBricks(string $set, string $language = ''): void {}
	public function getBricks(string $set): ?array { return null; }

}
