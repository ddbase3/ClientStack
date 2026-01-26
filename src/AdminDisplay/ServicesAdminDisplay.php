<?php declare(strict_types=1);

namespace ClientStack\AdminDisplay;

use Base3\Api\IAssetResolver;
use Base3\Api\IClassMap;
use Base3\Api\IContainer;
use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\Accesscontrol\Api\IAccesscontrol;
use Base3\Configuration\Api\IConfiguration;
use Base3\Database\Api\IDatabase;
use Base3\Language\Api\ILanguage;
use Base3\Logger\Api\ILogger;
use Base3\ServiceSelector\Api\IServiceSelector;
use Base3\Session\Api\ISession;
use Base3\State\Api\IStateStore;
use Base3\Usermanager\Api\IUsermanager;
use UiFoundation\Api\IAdminDisplay;

class ServicesAdminDisplay implements IAdminDisplay {

	public function __construct(
		private readonly IContainer $container,
		private readonly IMvcView $view
	) {}

	public static function getName(): string {
		return 'servicesadmindisplay';
	}

	public function getOutput($out = 'html') {
		$this->view->setPath(DIR_PLUGIN . 'ClientStack');
		$this->view->setTemplate('AdminDisplay/ServicesAdminDisplay.php');

		$list = [
			IContainer::class,
			IRequest::class,
			IClassMap::class,
			IConfiguration::class,
			IServiceSelector::class,
			ILogger::class,
			IDatabase::class,
			ISession::class,
			ILanguage::class,
			IAccesscontrol::class,
			IUsermanager::class,
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

	public function getHelp() {
		return 'Help of ServicesAdminDisplay';
	}

	public function setData($data) {
		// no-op
	}
}
