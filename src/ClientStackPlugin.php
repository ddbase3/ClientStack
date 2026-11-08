<?php declare(strict_types=1);

namespace ClientStack;

use Base3\Api\IAssetResolver;
use Base3\Api\ICheck;
use Base3\Api\IContainer;
use Base3\Api\IMvcView;
use Base3\Api\IModuleRegistry;
use Base3\Api\IRequest;
use ResourceFoundation\Api\IManagedFileStorageService;
use ClientStack\FileManager\FileManagerHttpService;
use ClientStack\FileManager\FileUploadService;
use Base3\Settings\Api\ISettingsStore;
use Base3\Translation\Api\ITranslation;
use ClientStack\GlobalChatbotLauncher\GlobalChatbotLauncherBootstrap;
use Base3\Api\IPlugin;
use Base3\Core\Check;
use ClientStack\Api\IAssetService;
use ClientStack\Display\CkEditorRichTextEditorDisplay;
use ClientStack\Display\ClassicChatbotDisplay;
use ClientStack\Service\DefaultAssetService;
use ClientStack\Display\ModularChatbotDisplay;
use UiFoundation\Api\IChatbotDisplay;
use UiFoundation\Api\IRichTextEditorDisplay;

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
