<?php declare(strict_types=1);

namespace ClientStack\AdminDisplay;

use Base3\Api\IMvcView;
use Base3\Configuration\Api\IConfiguration;
use UiFoundation\Api\IAdminDisplay;

class ConfigurationAdminDisplay implements IAdminDisplay {

	public function __construct(
		private readonly IMvcView $view,
		private readonly IConfiguration $configuration
	) {}

	public static function getName(): string {
		return 'configurationadmindisplay';
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		$this->view->setPath(DIR_PLUGIN . 'ClientStack');
		$this->view->setTemplate('AdminDisplay/ConfigurationAdminDisplay.php');

		$configuration = $this->configuration->get();
                $this->view->assign('configuration', $configuration);

                return $this->view->loadTemplate();
	}

	public function getHelp(): string {
		return 'Help of ConfigurationAdminDisplay';
	}

	public function setData($data) {
		// no-op
	}
}
