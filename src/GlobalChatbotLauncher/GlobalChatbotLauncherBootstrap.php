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

namespace Base3\ClientStack\GlobalChatbotLauncher;

use Base3\CoreFoundation\Api\IAssetResolver;
use Base3\CoreFoundation\Api\IMvcView;
use Base3\CoreFoundation\Authorization\Api\IAuthorizationService;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest;
use Base3\CoreFoundation\Usermanager\Permission;
use Throwable;
use Base3\CoreFoundation\Settings\Api\ISettingsStore;
use InvalidArgumentException;

/** Renders one host-neutral launcher bootstrap using the canonical ClientStack assets and settings. */
final class GlobalChatbotLauncherBootstrap {

	public function __construct(
		private readonly IAssetResolver $assets,
		private readonly ISettingsStore $settings,
		private readonly IMvcView $view
	) {}

	/**
	 * Emits the shared launcher only when the current host authority explicitly
	 * allows the canonical ClientStack launcher feature. The render endpoint
	 * must independently enforce the same permission for every request.
	 *
	 * @param IAuthorizationService $authorization Authoritative host decision service.
	 * @param string $renderUrl Host-controlled chatbot HTML endpoint.
	 * @param string $group Canonical chatbot settings group.
	 * @param string $name Canonical chatbot settings name.
	 * @param array<string,string> $appearance Optional host presentation settings.
	 * @param array<string,string> $labels Optional host translations.
	 * @return string Bootstrap markup or an empty string when denied or unavailable.
	 */
	public function renderAuthorized(
		IAuthorizationService $authorization,
		string $renderUrl,
		string $group,
		string $name,
		array $appearance = [],
		array $labels = []
	): string {
		try {
			if (!$authorization->decide(new AuthorizationRequest(
				$authorization->getDefaultAuthority(),
				Permission::for('feature', GlobalChatbotLauncherFeature::getName())
			))->isAllowed()) return '';
		} catch (Throwable) {
			return '';
		}
		return $this->render($renderUrl, $group, $name, $appearance, $labels);
	}

	/**
	 * @param string $renderUrl Host-authorized endpoint returning chatbot HTML.
	 * @param string $group Stored chatbot configuration group, not a permission selector.
	 * @param string $name Stored chatbot configuration name.
	 * @param array<string,string> $appearance Optional host branding and dock targets.
	 * @param array<string,string> $labels Optional host-specific translated labels.
	 * @return string Escaped configuration element and ES module script tag.
	 * @throws InvalidArgumentException If the render URL or configuration identity is empty.
	 */
	public function render(
		string $renderUrl,
		string $group,
		string $name,
		array $appearance = [],
		array $labels = []
	): string {
		if (trim($renderUrl) === '' || trim($group) === '' || trim($name) === '') {
			throw new InvalidArgumentException('Launcher render URL and configuration identity are required.');
		}

		$this->view->setPath(dirname(__DIR__, 2));
		$this->view->loadBricks('GlobalChatbotLauncher');
		$bricks = $this->view->getBricks('globalchatbotlauncher') ?? [];
		$translation = static function(string $key) use ($bricks, $labels): string {
			if (array_key_exists($key, $labels)) return $labels[$key];
			return (string)($bricks[$key] ?? '');
		};

		$settings = GlobalChatbotLauncherSettings::get($this->settings);
		$attributes = [
			'data-base3-global-chatbot-launcher' => null,
			'data-ajax-url' => $renderUrl,
			'data-config-group' => $group,
			'data-config-name' => $name,
			'data-button-label' => $translation('launcher_label'),
			'data-close-label' => $translation('close_chatbot'),
			'data-loading-label' => $translation('loading_chatbot'),
			'data-load-error' => $translation('load_error'),
			'data-resize-label' => $translation('window_resizable'),
			'data-mode-overlay-label' => $translation('window_mode_overlay'),
			'data-mode-modal-label' => $translation('window_mode_modal'),
			'data-mode-docked-label' => $translation('window_mode_docked'),
			'data-mode-cover-label' => $translation('window_mode_cover'),
			'data-mode-fullscreen-label' => $translation('window_mode_fullscreen'),
			'data-launcher-icon' => (string)($appearance['launcher_icon'] ?? ''),
			'data-header-icon' => (string)($appearance['header_icon'] ?? ''),
			'data-header-title' => (string)($appearance['header_title'] ?? $translation('launcher_label')),
			'data-dock-target' => (string)($appearance['dock_target'] ?? ''),
			'data-dock-header-target' => (string)($appearance['dock_header_target'] ?? '')
		];
		foreach ($settings as $key => $enabled) {
			$attributes['data-' . str_replace('_', '-', $key)] = $enabled ? '1' : '0';
		}
		$escaped = [];
		foreach ($attributes as $key => $value) {
			$escaped[] = $value === null
				? $key
				: $key . '="' . htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
		}
		$css = htmlspecialchars($this->assets->resolve('plugin/ClientStack/assets/globalchatbotlauncher/styles/globalchatbotlauncher.css'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
		$module = htmlspecialchars($this->assets->resolve('plugin/ClientStack/assets/globalchatbotlauncher/index.js'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
		return '<link rel="stylesheet" href="' . $css . '">'
			. '<div id="base3-chatbot-config" ' . implode(' ', $escaped) . ' hidden></div>'
			. '<script type="module" src="' . $module . '"></script>';
	}
}
