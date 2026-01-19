<?php declare(strict_types=1);

namespace ClientStack\AdminDisplay;

use Base3\Api\IClassMap;
use Base3\Api\IMvcView;
use Base3\Worker\Api\IJob;
use UiFoundation\Api\IAdminDisplay;

class JobsAdminDisplay implements IAdminDisplay {

	public function __construct(
		private readonly IClassMap $classmap,
		private readonly IMvcView $view
	) {}

	public static function getName(): string {
		return 'jobsadmindisplay';
	}

	public function getOutput($out = 'html') {
		$this->view->setPath(DIR_PLUGIN . 'ClientStack');
		$this->view->setTemplate('AdminDisplay/JobsAdminDisplay.php');

		$jobs = [];
		$instances = $this->classmap->getInstances(['interface' => IJob::class]);
		foreach ($instances as $job) {
			$jobs[] = [
				'job' => $job::class,
				'priority' => $job->getPriority(),
				'active' => $job->isActive()
			];
		}
		$this->view->assign('jobs', $jobs);

		return $this->view->loadTemplate();
	}

	public function getHelp() {
		return 'Help of JobsAdminDisplay';
	}

	public function setData($data) {
		// no-op
	}
}
