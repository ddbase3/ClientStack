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

namespace Base3\ClientStack;

use Base3\CoreFoundation\Api\IAssetResolver;
use Base3\CoreFoundation\Api\ICheck;
use Base3\CoreFoundation\Api\IContainer;
use Base3\CoreFoundation\Api\IMvcView;
use Base3\CoreFoundation\Api\IModuleRegistry;
use Base3\CoreFoundation\Api\IRequest;
use Base3\ResourceFoundation\Api\IManagedFileStorageService;
use Base3\ClientStack\FileManager\FileManagerHttpService;
use Base3\ClientStack\FileManager\FileUploadService;
use Base3\CoreFoundation\Settings\Api\ISettingsStore;
use Base3\CoreFoundation\Translation\Api\ITranslation;
use Base3\ClientStack\GlobalChatbotLauncher\GlobalChatbotLauncherBootstrap;
use Base3\CoreFoundation\Api\IPlugin;
use Base3\Core\Check;
use Base3\ClientStack\Api\IAssetService;
use Base3\ClientStack\Display\CkEditorRichTextEditorDisplay;
use Base3\ClientStack\Display\ClassicChatbotDisplay;
use Base3\ClientStack\Service\DefaultAssetService;
use Base3\ClientStack\Display\ModularChatbotDisplay;
use Base3\UiFoundation\Api\IChatbotDisplay;
use Base3\UiFoundation\Api\IRichTextEditorDisplay;

class ClientStackPlugin implements IPlugin, ICheck {

	public function __construct(private readonly IContainer $container) {}

	// Implementation of IBase

	public static function getName(): string {
		return 'clientstackplugin';
	}

	// Implementation of IPlugin

	public function init() {
		$this->container
			->set(self::getName(), $this, IContainer::SHARED)
			->set(GlobalChatbotLauncherBootstrap::class, fn($c) => new GlobalChatbotLauncherBootstrap(
				$c->get(IAssetResolver::class), $c->get(ISettingsStore::class), $c->get(IMvcView::class)
			), IContainer::SHARED)
			->set(IAssetService::class, fn($c) => new DefaultAssetService($c->get(IModuleRegistry::class)), IContainer::SHARED)
			->set(FileUploadService::class, fn($c) => new FileUploadService(
				$c->get(IManagedFileStorageService::class),
				rtrim(DIR_TMP, '/\\') . DIRECTORY_SEPARATOR . 'filemanager-uploads'
			), IContainer::SHARED | IContainer::NOOVERWRITE)
			->set(FileManagerHttpService::class, fn($c) => new FileManagerHttpService(
				$c->get(IManagedFileStorageService::class),
				$c->get(FileUploadService::class),
				$c->get(IRequest::class),
				$c->get(ITranslation::class)
			), IContainer::SHARED | IContainer::NOOVERWRITE)
			->set(
				IRichTextEditorDisplay::class,
				fn($c) => new CkEditorRichTextEditorDisplay(
					$c->get(IMvcView::class),
					$c->get(IAssetResolver::class)
				),
				IContainer::SHARED | IContainer::NOOVERWRITE
			)
			->set(
				IChatbotDisplay::class,
				fn($c) => new ModularChatbotDisplay(
					$c->get(IMvcView::class),
					$c->get(IAssetResolver::class)
				),
				IContainer::SHARED | IContainer::NOOVERWRITE
			);
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			'uifoundationplugin_installed' => $this->container->get('uifoundationplugin') ? 'Ok' : 'uifoundationplugin not installed'
		);
	}
}
