<?php declare(strict_types=1);

namespace ClientStack\Test\Display;

use Base3\Api\IAssetResolver;
use Base3\Api\IMvcView;
use ClientStack\Display\ModularChatbotDisplay;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class ModularChatbotRemainingMethodsCoverageTest extends TestCase {

	private function buildDisplay(array $bricks = [], string $prefix = '/resolved/'): ModularChatbotDisplay {
		$view = $this->createStub(IMvcView::class);
		$view->method('getBricks')->willReturn($bricks);
		$view->method('loadTemplate')->willReturn('chatbot-html');
		$assets = $this->createStub(IAssetResolver::class);
		$assets->method('resolve')->willReturnCallback(
			static fn(string $path): string => $prefix . $path
		);
		return new ModularChatbotDisplay($view, $assets);
	}

	private function invokeHidden(ModularChatbotDisplay $display, string $method, mixed ...$arguments): mixed {
		return (new ReflectionMethod(ModularChatbotDisplay::class, $method))->invoke($display, ...$arguments);
	}

	public function testInvalidExtensionContainerIsTreatedAsNoExtensions(): void {
		$display = $this->buildDisplay();
		self::assertSame([], $this->invokeHidden($display, 'normalizeExtensions', 'invalid'));
		self::assertSame([], $this->invokeHidden($display, 'normalizeExtensions', null));
	}

	public function testExtensionEntriesMustBeArrays(): void {
		$display = $this->buildDisplay();
		$this->expectException(InvalidArgumentException::class);
		$this->invokeHidden($display, 'normalizeExtensions', ['not an entry']);
	}

	public function testExtensionNameMustBeCanonicalAndNonempty(): void {
		$display = $this->buildDisplay();
		foreach (['', 'Invalid Name', 'plugin/other'] as $name) {
			try {
				$this->invokeHidden($display, 'normalizeExtensions', [[
					'name' => $name, 'module_url' => '/module.js', 'export_name' => 'Plugin'
				]]);
				self::fail('Invalid extension name was accepted: ' . $name);
			} catch (InvalidArgumentException $expected) {
				self::assertStringContainsString('invalid', $expected->getMessage());
			}
		}
	}

	public function testExtensionExportMustBeAValidJavaScriptIdentifier(): void {
		$display = $this->buildDisplay();
		foreach (['', '1Invalid', 'A-B'] as $export) {
			try {
				$this->invokeHidden($display, 'normalizeExtensions', [[
					'name' => 'extension', 'module_url' => '/module.js', 'export_name' => $export
				]]);
				self::fail('Invalid export identifier was accepted: ' . $export);
			} catch (InvalidArgumentException $expected) {
				self::assertStringContainsString('invalid', $expected->getMessage());
			}
		}
	}

	public function testExtensionRequiresModuleUrlAndArrayOptions(): void {
		$display = $this->buildDisplay();
		foreach ([
			['name' => 'extension', 'module_url' => ' ', 'export_name' => 'Plugin'],
			['name' => 'extension', 'module_url' => '/module.js', 'export_name' => 'Plugin', 'options' => 'invalid']
		] as $invalid) {
			try {
				$this->invokeHidden($display, 'normalizeExtensions', [$invalid]);
				self::fail('Invalid extension was accepted.');
			} catch (InvalidArgumentException $expected) {
				self::assertNotSame('', $expected->getMessage());
			}
		}
	}

	public function testValidExtensionsKeepExactlyTheirPublicFields(): void {
		$display = $this->buildDisplay();
		self::assertSame([[
			'name' => 'content-card',
			'moduleUrl' => '/module.js?x=1&y=2',
			'exportName' => 'CardPlugin',
			'options' => ['enabled' => true]
		]], $this->invokeHidden($display, 'normalizeExtensions', [[
			'name' => ' content-card ',
			'module_url' => ' /module.js?x=1&amp;y=2 ',
			'export_name' => ' CardPlugin ',
			'options' => ['enabled' => true],
			'internal' => 'not exposed'
		]]));
	}

	public function testTranslatedStringsRespectPrecedenceAndRejectUnknownKeys(): void {
		$display = $this->buildDisplay([
			'busy' => '  Busy in translation  ',
			'sendMessage' => ['invalid'],
			'deleteConfirm' => '   '
		]);
		$strings = $this->invokeHidden($display, 'getStrings', [
			'busy' => '  Override busy  ',
			'sendMessage' => 123,
			'deleteConfirm' => '',
			'internalSecret' => 'not a public string'
		]);
		self::assertSame('  Override busy  ', $strings['busy']);
		self::assertSame('123', $strings['sendMessage']);
		self::assertSame('Delete', $strings['deleteConfirm']);
		self::assertArrayNotHasKey('internalSecret', $strings);
	}

	public function testInvalidTranslationBricksDoNotOverrideCanonicalDefaults(): void {
		$display = $this->buildDisplay();
		$strings = $this->invokeHidden($display, 'getStrings', ['busy' => ' ', 'copyResponse' => []]);
		self::assertSame('The current request must finish first.', $strings['busy']);
		self::assertSame('Copy response', $strings['copyResponse']);
	}

	public function testUnknownAndNonPluginAssetsKeepTheirResolvedUrls(): void {
		$display = $this->buildDisplay();
		self::assertSame('/resolved/another/plugin/icon.svg',
			$this->invokeHidden($display, 'resolveVersionedAsset', 'another/plugin/icon.svg'));
		self::assertSame('/resolved/plugin/ClientStack/assets/missing/icon.svg',
			$this->invokeHidden($display, 'resolveVersionedAsset', 'plugin/ClientStack/assets/missing/icon.svg'));
	}

	public function testExistingAssetVersionAppendsQueryValueWithoutDiscardingParameters(): void {
		$display = $this->buildDisplay(prefix: '/cdn/?entry=');
		$path = 'plugin/ClientStack/assets/modularchatbot/icons/send.svg';
		$file = dirname(__DIR__, 2) . '/assets/modularchatbot/icons/send.svg';
		self::assertFileExists($file);
		$expected = '/cdn/?entry=' . $path . '&v=' . substr(hash_file('sha256', $file), 0, 12);
		self::assertSame($expected, $this->invokeHidden($display, 'resolveVersionedAsset', $path));
	}

	public function testOptionalAssetPathsAreTrimmedAndEmptyPathsAreNotResolved(): void {
		$display = $this->buildDisplay();
		self::assertSame('', $this->invokeHidden($display, 'resolveOptionalAsset', '  '));
		self::assertSame('/resolved/plugin/Custom/logo.svg',
			$this->invokeHidden($display, 'resolveOptionalAsset', ' plugin/Custom/logo.svg '));
	}

	public function testControlIconOverrideDoesNotReplaceOtherDefaultIcons(): void {
		$display = $this->buildDisplay();
		self::assertSame('/resolved/plugin/Custom/icon.svg',
			$this->invokeHidden($display, 'resolveControlIcon', ['voice_microphone' => ' plugin/Custom/icon.svg '], 'voice_microphone', 'microphone'));
		$default = $this->invokeHidden($display, 'resolveControlIcon', [], 'voice_microphone', 'microphone');
		self::assertStringContainsString('/modularchatbot/icons/microphone.svg', $default);
	}

	public function testClientUrlNormalizerDecodesHtmlEntitiesOnlyOnce(): void {
		$display = $this->buildDisplay();
		self::assertSame('/service?x=1&y=2',
			$this->invokeHidden($display, 'normalizeClientUrl', ' /service?x=1&amp;y=2 '));
	}
}
