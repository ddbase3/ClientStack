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

namespace Base3\ClientStack\Test\Display;

use Base3\CoreFoundation\Api\IClassMap;
use Base3\CoreFoundation\Api\IDisplay;
use Base3\CoreFoundation\Api\IMvcView;
use Base3\CoreFoundation\LinkTarget\Api\ILinkTargetService;
use Base3\ClientStack\Display\TabControlDisplay;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class TabControlRemainingMethodsCoverageTest extends TestCase {

	/** @return mixed */
	private function invokeHidden(TabControlDisplay $display, string $method, mixed ...$arguments): mixed {
		return (new ReflectionMethod(TabControlDisplay::class, $method))->invoke($display, ...$arguments);
	}

	private function buildControl(array &$assigned, ?IDisplay $child = null, ?array &$links = null): TabControlDisplay {
		$classmap = $this->createStub(IClassMap::class);
		$classmap->method('getInstanceByInterfaceName')->willReturnCallback(
			static fn(string $type, string $name): mixed => $name === 'childdisplay' ? $child : null
		);
		$view = $this->createStub(IMvcView::class);
		$view->method('getBricks')->willReturn(['empty_message' => 'Translated empty message']);
		$view->method('assign')->willReturnCallback(static function(string $key, mixed $value) use (&$assigned): void {
			$assigned[$key] = $value;
		});
		$view->method('loadTemplate')->willReturn('tabs-html');
		$target = $this->createStub(ILinkTargetService::class);
		$target->method('getLink')->willReturnCallback(static function(array $target, array $params = []) use (&$links): string {
			$links[] = [$target, $params];
			return '/display/' . (string)($target['name'] ?? '');
		});
		return new TabControlDisplay($classmap, $view, $target);
	}

	public function testMetadataIsStable(): void {
		$assigned = [];
		$display = $this->buildControl($assigned);
		self::assertSame('tabcontroldisplay', TabControlDisplay::getName());
		self::assertStringContainsString('subtab control', $display->getHelp());
	}

	public function testNameReadingTrimsScalarsAndRejectsNonScalarValues(): void {
		$assigned = [];
		$display = $this->buildControl($assigned);
		self::assertSame('child', $this->invokeHidden($display, 'readName', ['name' => ' child '], 'name'));
		self::assertSame('7', $this->invokeHidden($display, 'readName', ['name' => 7], 'name'));
		self::assertSame('', $this->invokeHidden($display, 'readName', ['name' => []], 'name'));
		self::assertSame('', $this->invokeHidden($display, 'readName', [], 'name'));
	}

	public function testLabelReadingTrimsValuesAndUsesFallbackForInvalidLabels(): void {
		$assigned = [];
		$display = $this->buildControl($assigned);
		self::assertSame('Custom', $this->invokeHidden($display, 'readLabel', ['label' => ' Custom '], 'Fallback'));
		self::assertSame('12', $this->invokeHidden($display, 'readLabel', ['label' => 12], 'Fallback'));
		self::assertSame('Fallback', $this->invokeHidden($display, 'readLabel', ['label' => '  '], 'Fallback'));
		self::assertSame('Fallback', $this->invokeHidden($display, 'readLabel', ['label' => []], 'Fallback'));
		self::assertSame('Fallback', $this->invokeHidden($display, 'readLabel', [], 'Fallback'));
	}

	public function testDescriptionsAreTrimmedOnlyForStringValues(): void {
		$assigned = [];
		$display = $this->buildControl($assigned);
		self::assertSame('A label', $this->invokeHidden($display, 'readDescription', ['description' => ' A label ']));
		self::assertSame('', $this->invokeHidden($display, 'readDescription', ['description' => 42]));
		self::assertSame('', $this->invokeHidden($display, 'readDescription', []));
	}

	public function testActiveEntrySelectionFallsBackToFirstConfiguredChild(): void {
		$assigned = [];
		$display = $this->buildControl($assigned);
		$tabs = [
			['name' => 'one', 'displays' => [['name' => 'first'], ['name' => 'second']]],
			['name' => 'two', 'displays' => [['name' => 'third']]]
		];
		self::assertSame('second', $this->invokeHidden($display, 'resolveActiveEntryName', $tabs, 'second'));
		self::assertSame('first', $this->invokeHidden($display, 'resolveActiveEntryName', $tabs, 'unknown'));
		self::assertSame('', $this->invokeHidden($display, 'resolveActiveEntryName', [], 'unknown'));
		self::assertSame('two', $this->invokeHidden($display, 'resolveActiveTabName', $tabs, 'third'));
		self::assertSame('', $this->invokeHidden($display, 'resolveActiveTabName', $tabs, 'unknown'));
	}

	public function testActiveDisplayLookupRejectsUnknownNames(): void {
		$assigned = [];
		$display = $this->buildControl($assigned);
		$tabs = [['name' => 'root', 'displays' => [['name' => 'selected', 'url' => '/selected']]]];
		self::assertSame(['name' => 'selected', 'url' => '/selected'], $this->invokeHidden($display, 'getDisplayConfig', $tabs, 'selected'));
		self::assertNull($this->invokeHidden($display, 'getDisplayConfig', $tabs, 'unknown'));
	}

	public function testMissingOrInvalidDisplayInstancesDoNotRenderAnything(): void {
		$assigned = [];
		$display = $this->buildControl($assigned);
		self::assertSame('', $this->invokeHidden($display, 'renderDisplay', null, ['secret' => true]));
		self::assertNull($this->invokeHidden($display, 'createDisplayInstance', 'missingdisplay'));
	}

	public function testActiveChildReceivesOnlyOwnDataAndHtmlOutput(): void {
		$assigned = [];
		$child = $this->createMock(IDisplay::class);
		$child->expects(self::once())->method('setData')->with(['value' => 7]);
		$child->expects(self::once())->method('getOutput')->with('html', false)->willReturn('rendered-child');
		$display = $this->buildControl($assigned, $child);
		self::assertSame('rendered-child', $this->invokeHidden($display, 'renderDisplay', $child, ['value' => 7]));
		self::assertSame($child, $this->invokeHidden($display, 'createDisplayInstance', 'childdisplay'));
	}

	public function testHistoryUrlsPreserveUnrelatedArgumentsAndReplaceStaleTabSelection(): void {
		$assigned = [];
		$display = $this->buildControl($assigned);
		$hadUri = is_array($_SERVER ?? null) && array_key_exists('REQUEST_URI', $_SERVER);
		$oldUri = $_SERVER['REQUEST_URI'] ?? null;
		try {
			$_SERVER['REQUEST_URI'] = '/admin/settings?language=de&base3_tab=old&keep=value';
			self::assertSame('/admin/settings?language=de&keep=value&base3_tab=new%20entry',
				$this->invokeHidden($display, 'createHistoryUrl', 'new entry'));
			$_SERVER['REQUEST_URI'] = '/settings?base3%5Ftab=old&x=1';
			self::assertSame('/settings?x=1&base3_tab=second',
				$this->invokeHidden($display, 'createHistoryUrl', 'second'));
			$_SERVER['REQUEST_URI'] = '/settings';
			self::assertSame('/settings?base3_tab=one', $this->invokeHidden($display, 'createHistoryUrl', 'one'));
		} finally {
			if ($hadUri) $_SERVER['REQUEST_URI'] = $oldUri;
			else unset($_SERVER['REQUEST_URI']);
		}
	}

	public function testEmbeddedDisplayDataUsesCanonicalUrlSafeBase64(): void {
		$assigned = [];
		$display = $this->buildControl($assigned);
		$encoded = $this->invokeHidden($display, 'encodeDisplayData', ['content' => 'hello/world', 'enabled' => true]);
		self::assertSame(['content' => 'hello/world', 'enabled' => true],
			json_decode(base64_decode(strtr($encoded, '-_', '+/'), true), true, 512, JSON_THROW_ON_ERROR));
		self::assertStringNotContainsString('=', $encoded);
		$recursive = [];
		$recursive['self'] = &$recursive;
		self::assertSame('', $this->invokeHidden($display, 'encodeDisplayData', $recursive));
	}

	public function testInvalidTabDefinitionsAreDroppedBeforeRendering(): void {
		$assigned = [];
		$display = $this->buildControl($assigned);
		$display->setData(['tabs' => [
			'not a tab', [], ['name' => 'empty', 'displays' => []],
			['name' => 'wrong', 'displays' => 'not a list'],
			['name' => 'duplicate', 'displays' => [['name' => 'missing', 'display' => 'notfound']]]
		]]);
		self::assertSame('tabs-html', $display->getOutput());
		self::assertSame([], $assigned['tabs']);
		self::assertSame('', $assigned['activeDisplay']);
		self::assertSame('Translated empty message', $assigned['emptyMessage']);
	}

	public function testExplicitUrlsAndEmbeddedDataDoNotRequireNewRuntimePaths(): void {
		$assigned = [];
		$links = [];
		$child = $this->createStub(IDisplay::class);
		$child->method('getOutput')->willReturn('child-html');
		$display = $this->buildControl($assigned, $child, $links);
		$display->setData(['initial_load_via_ajax' => true, 'tabs' => [
			['name' => 'root', 'label' => ' Root ', 'displays' => [
				['name' => 'one', 'display' => 'childdisplay', 'url' => '/already-defined', 'data' => ['one' => 1]],
				['name' => 'two', 'display' => 'childdisplay', 'params' => ['lang' => 'de'], 'data' => ['two' => 2]]
			]]
		]]);
		self::assertSame('tabs-html', $display->getOutput());
		self::assertSame('/already-defined', $assigned['tabs'][0]['displays'][0]['url']);
		self::assertSame('/display/childdisplay', $assigned['tabs'][0]['displays'][1]['url']);
		self::assertSame('root', $assigned['activeTab']);
		self::assertSame('one', $assigned['activeDisplay']);
		self::assertSame('', $assigned['content']);
		self::assertTrue($assigned['initialLoadViaAjax']);
		self::assertSame('de', $links[0][1]['lang']);
		self::assertArrayHasKey('base3_display_data', $links[0][1]);
	}

	public function testChangingRequestToKnownEntrySelectsCorrectTabAndRendersChild(): void {
		$assigned = [];
		$child = $this->createStub(IDisplay::class);
		$child->method('getOutput')->willReturn('child-html');
		$display = $this->buildControl($assigned, $child);
		$hadTab = is_array($_GET ?? null) && array_key_exists('base3_tab', $_GET);
		$oldTab = $_GET['base3_tab'] ?? null;
		try {
			$_GET['base3_tab'] = 'second';
			$display->setData(['tabs' => [
				['name' => 'one', 'displays' => [['name' => 'first', 'display' => 'childdisplay']]],
				['name' => 'two', 'displays' => [['name' => 'second', 'display' => 'childdisplay']]]
			]]);
			self::assertSame('tabs-html', $display->getOutput());
			self::assertSame('second', $assigned['activeDisplay']);
			self::assertSame('two', $assigned['activeTab']);
			self::assertSame('child-html', $assigned['content']);
		} finally {
			if ($hadTab) $_GET['base3_tab'] = $oldTab;
			else unset($_GET['base3_tab']);
		}
	}

	public function testSettingNonArrayConfigurationClearsPreviousTabs(): void {
		$assigned = [];
		$child = $this->createStub(IDisplay::class);
		$display = $this->buildControl($assigned, $child);
		$display->setData(['tabs' => [['name' => 'one', 'displays' => [['name' => 'first', 'display' => 'childdisplay']]]]]);
		$display->setData('reset');
		self::assertSame('tabs-html', $display->getOutput());
		self::assertSame([], $assigned['tabs']);
	}
}
