<?php declare(strict_types=1);

namespace ClientStack\Display;

use Base3\Api\IClassMap;
use Base3\Api\IDisplay;
use Base3\Api\IRequest;
use Base3\LinkTarget\Api\ILinkTargetService;
use Base3\Translation\Api\ITranslation;
use Base3\Usermanager\Api\IUsermanager;
use Throwable;
use UiFoundation\Api\ISettingsTab;

/** Discovers, filters and renders plugin-owned settings entries in two levels. */
final class SettingsDisplay implements IDisplay {

	public function __construct(
		private readonly IClassMap $classmap,
		private readonly IUsermanager $userManager,
		private readonly ITranslation $translation,
		private readonly IRequest $request,
		private readonly ILinkTargetService $links
	) {}

	public static function getName(): string {
		return 'settingsdisplay';
	}

	public function setData($data) {}

	public function getOutput(string $out = 'html', bool $final = false): string {
		if ($out !== 'html') {
			http_response_code(404);
			return '';
		}

		if ($this->userManager->getUser() === null) {
			http_response_code(403);
			return '<p role="alert">' . htmlspecialchars(
				$this->translation->translate('Settings', self::getName(), 'authentication_required'),
				ENT_QUOTES, 'UTF-8'
			) . '</p>';
		}

		$groups = [];
		$children = [];
		$names = [];
		foreach ($this->classmap->getInstancesByInterface(ISettingsTab::class) as $tab) {
			if (!$tab instanceof ISettingsTab) continue;
			$name = $tab::getName();
			if ($name === '' || isset($names[$name])) continue;
			$names[$name] = true;

			try {
				if (!$tab->isVisible()) continue;
				$parent = $tab->getParentTabName();
				$display = $tab->getDisplayName();
				if ($parent === null && $display === null) {
					$groups[$name] = $tab;
				} elseif ($parent !== null && $parent !== '' && $display !== null && $display !== '') {
					$children[$parent][] = $tab;
				}
			} catch (Throwable) {
				// Broken or unavailable plugin contributions must never grant access.
			}
		}

		$compare = static fn(ISettingsTab $a, ISettingsTab $b): int =>
			($a->getOrder() <=> $b->getOrder()) ?: strcmp($a::getName(), $b::getName());
		uasort($groups, $compare);
		$tabs = [];
		$availableEntries = [];
		foreach ($groups as $groupName => $group) {
			$entries = $children[$groupName] ?? [];
			usort($entries, $compare);
			$displays = [];
			foreach ($entries as $entry) {
				$availableEntries[$entry::getName()] = $entry;
				$displays[] = [
					'name' => $entry::getName(),
					'label' => $entry->getLabel(),
					'description' => $entry->getDescription(),
					'display' => $entry->getDisplayName(),
					'data' => $entry->getDisplayData(),
					'url' => $this->links->getLink(
						['name' => self::getName(), 'out' => 'html'],
						['base3_settings_entry' => $entry::getName()]
					)
				];
			}
			if (!$displays) continue;
			$tabs[] = [
				'name' => $groupName,
				'label' => $group->getLabel(),
				'description' => $group->getDescription(),
				'displays' => $displays
			];
		}

		$requested = $this->request->get('base3_settings_entry');
		if ($requested !== null) {
			if (!is_string($requested) || !isset($availableEntries[$requested])) {
				http_response_code(403);
				return '';
			}

			$entry = $availableEntries[$requested];
			$display = $this->classmap->getInstanceByInterfaceName(IDisplay::class, (string)$entry->getDisplayName());
			if (!$display instanceof IDisplay) {
				http_response_code(404);
				return '';
			}
			$display->setData($entry->getDisplayData());
			return $display->getOutput('html', false);
		}

		$tabControl = $this->classmap->getInstanceByInterfaceName(IDisplay::class, TabControlDisplay::getName());
		if (!$tabControl instanceof TabControlDisplay) {
			http_response_code(503);
			return '';
		}
		$tabControl->setData([
			'tabs' => $tabs,
			'initial_load_via_ajax' => false,
			'empty_message' => $this->translation->translate('Settings', self::getName(), 'empty_message')
		]);
		return $tabControl->getOutput($out, false);
	}
}
