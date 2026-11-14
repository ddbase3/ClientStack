<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of ClientStack for BASE3 Framework.
 *
 * ClientStack manages browser assets for modular BASE3 applications.
 * It provides reusable JavaScript and CSS library registrations.
 * Logical asset identifiers are resolved to deployment-specific URLs.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/ClientStack
 **********************************************************************/

namespace Base3\ClientStack\Display;

use Base3\CoreFoundation\Usermanager\Permission;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest;
use Base3\CoreFoundation\Authorization\Api\IAuthorizationService;
use Base3\CoreFoundation\Api\IAssetResolver;
use Base3\CoreFoundation\Api\IClassMap;
use Base3\CoreFoundation\Api\IComponentResolver;
use Base3\CoreFoundation\Api\IContainer;
use Base3\CoreFoundation\Api\IDisplay;
use Base3\CoreFoundation\Api\IMvcView;
use Base3\CoreFoundation\Api\IRequest;
use Base3\CoreFoundation\Api\ISystemService;
use Base3\CoreFoundation\Accesscontrol\Api\IAccesscontrol;
use Base3\CoreFoundation\Configuration\Api\IConfiguration;
use Base3\CoreFoundation\Database\Api\IDatabase;
use Base3\CoreFoundation\Event\Api\IEventManager;
use Base3\CoreFoundation\Hook\Api\IHookManager;
use Base3\CoreFoundation\Language\Api\ILanguage;
use Base3\CoreFoundation\LinkTarget\Api\ILinkTargetService;
use Base3\CoreFoundation\Logger\Api\ILogger;
use Base3\CoreFoundation\ServiceSelector\Api\IServiceSelector;
use Base3\CoreFoundation\Session\Api\ISession;
use Base3\CoreFoundation\Settings\Api\ISettingsStore;
use Base3\CoreFoundation\State\Api\IStateStore;
use Base3\CoreFoundation\Usermanager\Api\IUsermanager;

class ServicesAdminDisplay implements IDisplay {

	public function __construct(
		private readonly IContainer $container,
		private readonly IMvcView $view,
		private readonly IAuthorizationService $settingsAuthorization
	) {}

	public static function getName(): string {
		return 'servicesadmindisplay';
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		if (!$this->settingsAuthorization->decide(new AuthorizationRequest(
			$this->settingsAuthorization->getDefaultAuthority(),
			Permission::for('feature', \Base3\ClientStack\SettingsTab\ServicesadminSettingsTab::getName())
		))->isAllowed()) {
			http_response_code(403);
			return '';
		}

		$this->view->setPath(dirname(__DIR__, 2));
		$this->view->setTemplate('Display/ServicesAdminDisplay.php');

		$list = [
			IContainer::class,
			ISystemService::class,
			IRequest::class,
			IClassMap::class,
			IComponentResolver::class,
			IConfiguration::class,
			ISettingsStore::class,
			IHookManager::class,
			IServiceSelector::class,
			ILinkTargetService::class,
			ILogger::class,
			IDatabase::class,
			ISession::class,
			ILanguage::class,
			IAccesscontrol::class,
			IUsermanager::class,
			IEventManager::class,
			IStateStore::class,
			IMvcView::class,
			IAssetResolver::class
		];

		$services = [];
		foreach ($list as $item) {
			$instance = $this->container->get($item);
			if ($instance == null) continue;
			$services[] = ['interface' => $item, 'service' => $instance::class];
		}
		$this->view->assign('services', $services);

		return $this->view->loadTemplate();
	}

	public function getHelp(): string {
		return 'Help of ServicesAdminDisplay';
	}

	public function setData($data) {
		// no-op
	}
}
