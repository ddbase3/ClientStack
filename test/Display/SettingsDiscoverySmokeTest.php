<?php declare(strict_types=1);

/** Run from the BASE3 root: php plugin/ClientStack/test/Display/SettingsDiscoverySmokeTest.php */

use Base3\Authorization\Api\IAuthorizationService;
use Base3\Authorization\Api\IFeature;
use Base3\Authorization\Dto\AuthorizationDecision;
use Base3\Authorization\Dto\AuthorizationRequest;
use Base3\Translation\NoTranslation\NoTranslation;
use UiFoundation\Api\ISettingsTab;

$root = dirname(__DIR__, 4);
if (!defined('DIR_ROOT')) define('DIR_ROOT', $root . DIRECTORY_SEPARATOR);
if (!defined('DIR_SRC')) define('DIR_SRC', DIR_ROOT . 'src' . DIRECTORY_SEPARATOR);
if (!defined('DIR_TEST')) define('DIR_TEST', DIR_ROOT . 'test' . DIRECTORY_SEPARATOR);
if (!defined('DIR_PLUGIN')) define('DIR_PLUGIN', DIR_ROOT . 'plugin' . DIRECTORY_SEPARATOR);
require DIR_SRC . 'Core/Autoloader.php';
Base3\Core\Autoloader::register();

$translation = new NoTranslation();
$authorization = new class implements IAuthorizationService {
	public function getDefaultAuthority(): string { return 'base3'; }
	public function decide(AuthorizationRequest $request): AuthorizationDecision { return AuthorizationDecision::deny(); }
	public function decideMany(array $requests): array {
		return array_map(fn(AuthorizationRequest $request): AuthorizationDecision => $this->decide($request), $requests);
	}
};
$names = [];
$parents = [];
$sections = [];
$languages = ['ar', 'bg', 'de', 'en', 'es', 'fr', 'hi', 'it', 'pl', 'pt', 'ru', 'zh'];
foreach (glob(DIR_PLUGIN . '*/src/SettingsTab/*.php') ?: [] as $path) {
	$module = basename(dirname(dirname(dirname($path))));
	$class = $module . '\\SettingsTab\\' . basename($path, '.php');
	$instance = new $class($translation, $authorization);
	if (!$instance instanceof ISettingsTab) throw new RuntimeException($class . ' must implement ISettingsTab.');
	$name = $class::getName();
	if (isset($names[$name])) throw new RuntimeException('Duplicate settings name: ' . $name);
	$names[$name] = $instance;
	if ($instance->getParentTabName() !== null) {
		if ($instance->getDisplayName() === null || $instance->getDisplayName() === '') throw new RuntimeException('Missing display for ' . $name);
		if (!$instance instanceof IFeature && $module !== 'DatabaseWorkbench') throw new RuntimeException('Missing feature for ' . $name);
		if ($instance instanceof IFeature && $instance->isVisible()) throw new RuntimeException('Denied feature is visible: ' . $name);
		$parents[$name] = $instance->getParentTabName();
	} elseif ($instance->getDisplayName() !== null) {
		throw new RuntimeException('Root tab must not contain a display: ' . $name);
	}
	foreach ($languages as $language) {
		$file = DIR_PLUGIN . $module . '/lang/Settings/' . $language . '.ini';
		$ini = parse_ini_file($file, true, INI_SCANNER_RAW);
		if (!is_array($ini) || trim((string)($ini[$name]['label'] ?? '')) === ''
			|| trim((string)($ini[$name]['description'] ?? '')) === '') {
			throw new RuntimeException('Missing plugin-owned translation: ' . $module . '/' . $language . '/' . $name);
		}
		$sections[$language][$name] = $module;
	}
}
// Exercise the shipped module translation loader, not just INI syntax.
$language = new class implements \Base3\Language\Api\ILanguage {
	private string $active = 'en';
	public function getLanguage(): string { return $this->active; }
	public function setLanguage(string $language): void { $this->active = $language; }
	public function getLanguages(): array { return ['en', 'de', 'fr']; }
};
$registry = new class implements \Base3\Api\IModuleRegistry {
	public function getModuleNames(): array {
		return array_map('basename', glob(DIR_PLUGIN . '*', GLOB_ONLYDIR) ?: []);
	}
	public function getModulePath(string $name): ?string {
		$path = DIR_PLUGIN . $name;
		return is_dir($path) ? $path : null;
	}
	public function requireModulePath(string $name): string {
		return $this->getModulePath($name) ?? throw new RuntimeException('Missing module: ' . $name);
	}
};
$moduleTranslation = new \Base3Template\Translation\ModuleTranslation($language, $registry);
foreach (['en', 'de', 'fr'] as $lang) {
	$language->setLanguage($lang);
	foreach ($sections[$lang] as $name => $owner) {
		$actual = $moduleTranslation->translate('Settings', $name, 'label');
		$ini = parse_ini_file(DIR_PLUGIN . $owner . '/lang/Settings/' . $lang . '.ini', true, INI_SCANNER_RAW);
		if ($actual !== $ini[$name]['label']) {
			throw new RuntimeException('Runtime translation is not owned by ' . $owner . ': ' . $name);
		}
	}
}
// Composite item headings are also translated by their owning plugin.
$overview = new \MissionBay\SettingsTab\AiUsageOverviewSettingsTab($moduleTranslation, $authorization);
foreach (['en', 'de', 'fr'] as $lang) {
	$language->setLanguage($lang);
	$items = $overview->getDisplayData()['items'];
	if (($items[3]['title'] ?? '') !== $moduleTranslation->translate('Settings', $overview::getName(), 'models_title')) {
		throw new RuntimeException('MissionBay composite report title is not localized for ' . $lang);
	}
}
foreach ($parents as $name => $parent) {
	if (!isset($names[$parent]) || $names[$parent]->getParentTabName() !== null) {
		throw new RuntimeException('Missing root parent for ' . $name . ': ' . $parent);
	}
}
if (count($names) < 60) throw new RuntimeException('Expected the shipped settings contributions.');
$page = json_decode((string)file_get_contents(DIR_PLUGIN . 'Base3XrmWebsite/local/Page/page-administration.json'), true, 512, JSON_THROW_ON_ERROR);
$displays = array_values(array_filter($page['pagecontents'], static fn(array $item): bool => $item['name'] === 'displaypagemodule'));
if (count($displays) !== 1 || $displays[0]['data']['display'] !== 'settingsdisplay' || $displays[0]['data']['data'] !== []) {
	throw new RuntimeException('XRM page must delegate to settingsdisplay without static tabs.');
}
echo 'Settings discovery, permissions and plugin-local translations: ' . count($names) . " tabs verified.\n";
