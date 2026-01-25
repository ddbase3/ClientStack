<?php declare(strict_types=1);

namespace ClientStack\AdminDisplay;

use Base3\Api\IClassMap;
use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\Configuration\Api\IConfiguration;
use Base3\Worker\Api\IJob;
use UiFoundation\Api\IAdminDisplay;

final class JobsAdminDisplay implements IAdminDisplay {

	private const CONFIG_GROUP = 'missionbayilias';

	private const PRIO_MIN = 1;
	private const PRIO_MAX = 10;

	public function __construct(
		private readonly IClassMap $classmap,
		private readonly IMvcView $view,
		private readonly IRequest $request,
		private readonly IConfiguration $config
	) {}

	public static function getName(): string {
		return 'jobsadmindisplay';
	}

	public function getHelp() {
		return 'Interactive job control panel: toggle active and adjust priority via configuration.';
	}

	public function setData($data) {
		// no-op
	}

	public function getOutput($out = 'html') {
		$out = strtolower((string)$out);

		if ($out === 'json') {
			return $this->handleJson();
		}

		return $this->handleHtml();
	}

	private function handleHtml(): string {
		$this->view->setPath(DIR_PLUGIN . 'ClientStack');
		$this->view->setTemplate('AdminDisplay/JobsAdminDisplay.php');

		$baseEndpoint = $this->buildEndpointBase();

		$this->view->assign('endpoint', $baseEndpoint);
		$this->view->assign('configGroup', self::CONFIG_GROUP);
		$this->view->assign('prioMin', self::PRIO_MIN);
		$this->view->assign('prioMax', self::PRIO_MAX);

		return $this->view->loadTemplate();
	}

	private function handleJson(): string {
		$action = (string)($this->request->get('action') ?? '');

		try {
			return match ($action) {
				'list' => $this->jsonSuccess([
					'group' => self::CONFIG_GROUP,
					'jobs' => $this->listJobs(),
				]),
				'set_active' => $this->jsonSuccess([
					'group' => self::CONFIG_GROUP,
					'job' => $this->setActive(
						(string)($this->request->get('job') ?? ''),
						$this->normalizeBool($this->request->get('value'))
					),
				]),
				'prio' => $this->jsonSuccess([
					'group' => self::CONFIG_GROUP,
					'job' => $this->setPriority(
						(string)($this->request->get('job') ?? ''),
						$this->request->get('value'),
						$this->request->get('delta')
					),
				]),
				default => $this->jsonError("Unknown action '$action'. Use: list|set_active|prio"),
			};
		} catch (\Throwable $e) {
			return $this->jsonError('Exception: ' . $e->getMessage());
		}
	}

	// ---------------------------------------------------------------------
	// Core logic
	// ---------------------------------------------------------------------

	private function listJobs(): array {
		$rows = [];

		$instances = $this->classmap->getInstances(['interface' => IJob::class]);
		foreach ($instances as $job) {
			$class = $job::class;
			$short = $this->getShortClass($class);

			$jobKey = $this->getJobKey($job);
			$activeKey = $jobKey . '.active';
			$prioKey = $jobKey . '.priority';

			$defaultActive = (bool)$job->isActive();
			$defaultPrio = (int)$job->getPriority();

			$hasActive = $this->config->hasValue(self::CONFIG_GROUP, $activeKey);
			$hasPrio = $this->config->hasValue(self::CONFIG_GROUP, $prioKey);

			$active = $hasActive
				? $this->config->getBool(self::CONFIG_GROUP, $activeKey, $defaultActive)
				: $defaultActive;

			$prio = $hasPrio
				? $this->config->getInt(self::CONFIG_GROUP, $prioKey, $defaultPrio)
				: $defaultPrio;

			$prio = $this->clampInt($prio, self::PRIO_MIN, self::PRIO_MAX);

			$rows[] = [
				'jobKey' => $jobKey,
				'class' => $class,
				'short' => $short,
				'active' => (bool)$active,
				'priority' => (int)$prio,

				// for UI diagnostics:
				'defaultActive' => $defaultActive,
				'defaultPriority' => $defaultPrio,
				'hasActiveConfig' => $hasActive,
				'hasPriorityConfig' => $hasPrio,
			];
		}

		/*
		usort($rows, function(array $a, array $b): int {
			if ($a['priority'] === $b['priority']) {
				return strcasecmp($a['short'], $b['short']);
			}
			return $a['priority'] <=> $b['priority'];
		});
		 */

		return $rows;
	}

	private function setActive(string $jobKey, bool $value): array {
		$jobKey = $this->normalizeKey($jobKey);
		if ($jobKey === '') {
			throw new \RuntimeException('Missing job.');
		}

		$key = $jobKey . '.active';
		$this->persist(self::CONFIG_GROUP, $key, $value ? '1' : '0');

		return [
			'jobKey' => $jobKey,
			'active' => $value,
		];
	}

	private function setPriority(string $jobKey, mixed $value, mixed $delta): array {
		$jobKey = $this->normalizeKey($jobKey);
		if ($jobKey === '') {
			throw new \RuntimeException('Missing job.');
		}

		$key = $jobKey . '.priority';

		$current = $this->config->getInt(self::CONFIG_GROUP, $key, self::PRIO_MIN);

		$target = null;

		if ($delta !== null && $delta !== '') {
			$d = (int)$delta;
			$target = $current + $d;
		} else if ($value !== null && $value !== '') {
			$target = (int)$value;
		} else {
			throw new \RuntimeException('Missing value or delta.');
		}

		$target = $this->clampInt($target, self::PRIO_MIN, self::PRIO_MAX);

		$this->persist(self::CONFIG_GROUP, $key, (string)$target);

		return [
			'jobKey' => $jobKey,
			'priority' => $target,
		];
	}

	// ---------------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------------

	private function persist(string $group, string $key, string $value): void {
		$ok = false;

		// Prefer single-value persistence if available
		try {
			$ok = $this->config->persistValue($group, $key, $value);
		} catch (\Throwable $e) {
			$ok = false;
		}

		if ($ok) {
			return;
		}

		// Fallback: set + saveIfDirty
		$this->config->setValue($group, $key, $value);

		// saveIfDirty() exists by interface; safe to call
		$this->config->saveIfDirty();
	}

	private function getJobKey(IJob $job): string {
		// Prefer job's technical name if provided
		if (method_exists($job, 'getName')) {
			try {
				$name = (string)$job::getName();
				$name = $this->normalizeKey($name);
				if ($name !== '') return $name;
			} catch (\Throwable $e) {
				// ignore
			}
		}

		// Fallback: lowercased short class name
		$short = $this->getShortClass($job::class);
		return strtolower($short);
	}

	private function getShortClass(string $fqcn): string {
		$pos = strrpos($fqcn, '\\');
		return $pos === false ? $fqcn : substr($fqcn, $pos + 1);
	}

	private function normalizeKey(string $s): string {
		$s = trim($s);
		$s = strtolower($s);
		// allow only: a-z 0-9 . _ -
		$s = preg_replace('/[^a-z0-9._-]+/', '', $s) ?? '';
		return $s;
	}

	private function normalizeBool(mixed $v): bool {
		if (is_bool($v)) return $v;
		$s = strtolower(trim((string)$v));
		return in_array($s, ['1', 'true', 'yes', 'on'], true);
	}

	private function clampInt(int $n, int $min, int $max): int {
		if ($n < $min) return $min;
		if ($n > $max) return $max;
		return $n;
	}

	private function buildEndpointBase(): string {
		$baseEndpoint = '';
		try {
			$baseEndpoint = (string)($this->config->get('base')['endpoint'] ?? '');
		} catch (\Throwable $e) {
			$baseEndpoint = '';
		}

		$baseEndpoint = trim($baseEndpoint);
		if ($baseEndpoint === '') {
			$baseEndpoint = 'base3.php';
		}

		$sep = str_contains($baseEndpoint, '?') ? '&' : '?';
		return $baseEndpoint . $sep . 'name=' . rawurlencode(self::getName()) . '&out=json&action=';
	}

	private function jsonSuccess(array $data): string {
		return json_encode([
			'status' => 'ok',
			'timestamp' => gmdate('c'),
			'data' => $data,
		], JSON_UNESCAPED_UNICODE);
	}

	private function jsonError(string $message): string {
		return json_encode([
			'status' => 'error',
			'timestamp' => gmdate('c'),
			'message' => $message,
		], JSON_UNESCAPED_UNICODE);
	}
}
