<?php declare(strict_types=1);

namespace ClientStack;

use Base3\Api\IAssetResolver;
use Base3\Api\ICheck;
use Base3\Api\IContainer;
use Base3\Api\IMvcView;
use Base3\Api\IPlugin;
use Base3\Core\Check;
use ClientStack\Api\IAssetService;
use ClientStack\Display\CkEditorRichTextEditorDisplay;
use ClientStack\Service\DefaultAssetService;
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
			->set(IAssetService::class, fn() => new DefaultAssetService, IContainer::SHARED)
			->set(
				IRichTextEditorDisplay::class,
				fn($c) => new CkEditorRichTextEditorDisplay(
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
