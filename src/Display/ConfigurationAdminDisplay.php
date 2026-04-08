<?php declare(strict_types=1);

namespace ClientStack\Display;

use Base3\Api\IDisplay;
use Base3\Api\IMvcView;
use Base3\Configuration\Api\IConfiguration;

class ConfigurationAdminDisplay implements IDisplay {

	public function __construct(
		private readonly IMvcView $view,
		private readonly IConfiguration $configuration
	) {}

	public static function getName(): string {
		return 'configurationadmindisplay';
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		$this->view->setPath(DIR_PLUGIN . 'ClientStack');
		$this->view->setTemplate('Display/ConfigurationAdminDisplay.php');

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
