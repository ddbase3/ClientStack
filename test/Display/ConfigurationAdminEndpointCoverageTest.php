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

use Base3\CoreFoundation\Api\IAssetResolver;
use Base3\CoreFoundation\Api\IMvcView;
use Base3\CoreFoundation\Api\IRequest;
use Base3\CoreFoundation\Authorization\Api\IAuthorizationService;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationDecision;
use Base3\CoreFoundation\Configuration\Api\IConfiguration;
use Base3\CoreFoundation\LinkTarget\Api\ILinkTargetService;
use Base3\ClientStack\Display\ConfigurationAdminDisplay;
use PHPUnit\Framework\TestCase;

final class ConfigurationAdminEndpointCoverageTest extends TestCase {

	protected function setUp(): void { http_response_code(200); }
	protected function tearDown(): void { http_response_code(200); }

	private function createDisplay(
		IConfiguration $configuration, array $payload, bool $allowed = true, ?array &$assigned = null
	): ConfigurationAdminDisplay {
		$request = $this->createStub(IRequest::class);
		$request->method('getJsonBody')->willReturn($payload);
		$auth = $this->createStub(IAuthorizationService::class);
		$auth->method('getDefaultAuthority')->willReturn('base3');
		$auth->method('decide')->willReturn($allowed ? AuthorizationDecision::allow() : AuthorizationDecision::deny());
		$view = $this->createStub(IMvcView::class);
		$view->method('loadTemplate')->willReturn('configuration-view');
		$view->method('assign')->willReturnCallback(static function(string $key, mixed $value) use (&$assigned): void {
			$assigned[$key] = $value;
		});
		$assets = $this->createStub(IAssetResolver::class);
		$assets->method('resolve')->willReturnCallback(static fn(string $path): string => '/asset/' . $path);
		$link = $this->createStub(ILinkTargetService::class);
		$link->method('getLink')->willReturn('/settings?out=json');
		return new ConfigurationAdminDisplay($request, $view, $assets, $configuration, $link, $auth);
	}

	private function encodeId(string $group, string $key): string {
		return rtrim(strtr(base64_encode(json_encode(['group' => $group, 'key' => $key], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
	}

	private function json(ConfigurationAdminDisplay $display): array {
		return json_decode($display->getOutput('JSON'), true, 512, JSON_THROW_ON_ERROR);
	}

	public function testDeniedFeatureStopsBeforeReadingConfiguration(): void {
		$configuration = $this->createMock(IConfiguration::class);
		$configuration->expects(self::never())->method('get');
		$display = $this->createDisplay($configuration, ['mode' => 'page'], false);
		self::assertSame('configurationadmindisplay', $display::getName());
		self::assertSame('', $display->getOutput('json'));
		self::assertSame(403, http_response_code());
	}

	public function testHtmlRendersWithOwnAssetsAndSupportedTypeOptions(): void {
		$assigned = [];
		$display = $this->createDisplay($this->createStub(IConfiguration::class), [], true, $assigned);
		self::assertSame('configuration-view', $display->getOutput('html'));
		self::assertSame('/settings?out=json', $assigned['service']);
		self::assertStringContainsString('modulargrid.css', $assigned['modularGridCssUrl']);
		self::assertStringContainsString('modulardialog/index.js', $assigned['modularDialogJsUrl']);
		self::assertContains('null', array_column($assigned['typeOptions'], 'value'));
		self::assertStringContainsString('ConfigurationAdminDisplay', $display->getHelp());
		$display->setData(['ignored' => true]);
	}

	public function testJsonPageListsAndPaginatesConfigurationValues(): void {
		$configuration = $this->createStub(IConfiguration::class);
		$configuration->method('get')->willReturn(['base3' => ['key2' => true, 'key10' => 'text'], 'bad' => 'not-array']);
		$response = $this->json($this->createDisplay($configuration, ['mode' => 'page', 'page' => 1, 'pageSize' => 1,
			'sort' => [['key' => 'key', 'dir' => 'asc']]]));
		self::assertTrue($response['ok']);
		self::assertSame(2, $response['total']);
		self::assertSame(2, $response['totalPages']);
		self::assertTrue($response['hasMore']);
		self::assertSame('key2', $response['data'][0]['key']);
	}

	public function testJsonPageAppliesSearchAndTypeFiltersBeforePagination(): void {
		$configuration = $this->createStub(IConfiguration::class);
		$configuration->method('get')->willReturn(['base3' => ['enabled' => true, 'label' => 'welcome']]);
		$response = $this->json($this->createDisplay($configuration, [
			'mode' => 'page', 'search' => 'label', 'filters' => ['type' => 'string']
		]));
		self::assertSame(1, $response['total']);
		self::assertSame('label', $response['data'][0]['key']);
		self::assertFalse($response['hasMore']);
	}

	public function testInvalidRecordIdentifierIsRejectedWithoutReadingStorage(): void {
		$configuration = $this->createMock(IConfiguration::class);
		$configuration->expects(self::never())->method('getValue');
		$response = $this->json($this->createDisplay($configuration, ['mode' => 'record', 'id' => 'invalid']));
		self::assertFalse($response['found']);
		self::assertSame('record', $response['mode']);
	}

	public function testKnownRecordIsReadFromCanonicalConfigurationOwner(): void {
		$configuration = $this->createMock(IConfiguration::class);
		$configuration->expects(self::once())->method('hasValue')->with('chat', 'enabled')->willReturn(true);
		$configuration->expects(self::once())->method('getValue')->with('chat', 'enabled')->willReturn(true);
		$response = $this->json($this->createDisplay($configuration, [
			'mode' => 'record', 'id' => $this->encodeId('chat', 'enabled')
		]));
		self::assertTrue($response['found']);
		self::assertSame('bool', $response['record']['type']);
		self::assertSame('true', $response['record']['value_text']);
	}

	public function testMissingRecordIsReportedAsNotFound(): void {
		$configuration = $this->createStub(IConfiguration::class);
		$configuration->method('hasValue')->willReturn(false);
		$response = $this->json($this->createDisplay($configuration, [
			'mode' => 'record', 'id' => $this->encodeId('chat', 'removed')
		]));
		self::assertFalse($response['found']);
		self::assertSame('Configuration value not found.', $response['error']);
	}

	public function testSaveRequiresNonemptyGroupAndKey(): void {
		$configuration = $this->createMock(IConfiguration::class);
		$configuration->expects(self::never())->method('setValue');
		$response = $this->json($this->createDisplay($configuration, ['mode' => 'save', 'group' => '', 'key' => 'x']));
		self::assertSame('Group must not be empty.', $response['error']);
		$response = $this->json($this->createDisplay($configuration, ['mode' => 'save', 'group' => 'chat', 'key' => '']));
		self::assertSame('Key must not be empty.', $response['error']);
	}

	public function testSaveRejectsInvalidTypedValueBeforePersistence(): void {
		$configuration = $this->createMock(IConfiguration::class);
		$configuration->expects(self::never())->method('persistValue');
		$response = $this->json($this->createDisplay($configuration, [
			'mode' => 'save', 'group' => 'chat', 'key' => 'timeout', 'type' => 'int', 'value' => 'abc'
		]));
		self::assertFalse($response['ok']);
		self::assertStringContainsString('valid integer', $response['error']);
	}

	public function testSaveUsesSingleCanonicalPersistValueOperation(): void {
		$configuration = $this->createMock(IConfiguration::class);
		$configuration->expects(self::once())->method('persistValue')->with('chat', 'timeout', 42)->willReturn(true);
		$configuration->expects(self::never())->method('setValue');
		$configuration->method('hasValue')->willReturn(true);
		$configuration->method('getValue')->willReturn(42);
		$response = $this->json($this->createDisplay($configuration, [
			'mode' => 'save', 'group' => 'chat', 'key' => 'timeout', 'type' => 'int', 'value' => '42'
		]));
		self::assertTrue($response['ok']);
		self::assertSame('saved', $response['action']);
		self::assertSame('int', $response['record']['type']);
	}

	public function testSaveUsesConfigurationSetWhenPersistenceIsUnavailable(): void {
		$configuration = $this->createMock(IConfiguration::class);
		$configuration->expects(self::once())->method('persistValue')->willReturn(false);
		$configuration->expects(self::once())->method('setValue')->with('chat', 'enabled', true);
		$configuration->expects(self::once())->method('saveIfDirty')->willReturn(true);
		$configuration->method('hasValue')->willReturn(true);
		$configuration->method('getValue')->willReturn(true);
		$response = $this->json($this->createDisplay($configuration, [
			'mode' => 'save', 'group' => 'chat', 'key' => 'enabled', 'type' => 'bool', 'value' => 'yes'
		]));
		self::assertTrue($response['ok']);
	}

	public function testRenameDoesNotOverwriteAnExistingTarget(): void {
		$configuration = $this->createMock(IConfiguration::class);
		$configuration->method('hasValue')->willReturn(true);
		$configuration->expects(self::never())->method('persistValue');
		$response = $this->json($this->createDisplay($configuration, [
			'mode' => 'save', 'oldGroup' => 'old', 'oldKey' => 'name', 'group' => 'new', 'key' => 'name',
			'type' => 'string', 'value' => 'new'
		]));
		self::assertStringContainsString('already exists', $response['error']);
	}

	public function testRenameDeletesOldValueOnlyAfterSavingNewValue(): void {
		$configuration = $this->createMock(IConfiguration::class);
		$persisted = false;
		$configuration->method('hasValue')->willReturnCallback(static function(string $group, string $key) use (&$persisted): bool {
			return $group === 'new' && $persisted;
		});
		$configuration->expects(self::once())->method('persistValue')->with('new', 'name', 'new-value')
			->willReturnCallback(static function() use (&$persisted): bool { $persisted = true; return true; });
		$configuration->expects(self::once())->method('removeValue')->with('old', 'name');
		$configuration->expects(self::once())->method('saveIfDirty')->willReturn(true);
		$configuration->method('getValue')->willReturn('new-value');
		$response = $this->json($this->createDisplay($configuration, [
			'mode' => 'save', 'oldGroup' => 'old', 'oldKey' => 'name', 'group' => 'new', 'key' => 'name',
			'type' => 'string', 'value' => 'new-value'
		]));
		self::assertTrue($response['ok']);
		self::assertSame('renamed and saved', $response['action']);
	}

	public function testDeleteRejectsInvalidAndMissingKeys(): void {
		$configuration = $this->createMock(IConfiguration::class);
		$configuration->method('hasValue')->willReturn(false);
		$configuration->expects(self::never())->method('removeValue');
		$response = $this->json($this->createDisplay($configuration, ['mode' => 'delete', 'id' => 'invalid']));
		self::assertFalse($response['ok']);
		$response = $this->json($this->createDisplay($configuration, [
			'mode' => 'delete', 'id' => $this->encodeId('gone', 'key')
		]));
		self::assertSame('Configuration value not found.', $response['error']);
	}

	public function testDeleteRemovesMatchingValueAndSavesConfiguration(): void {
		$configuration = $this->createMock(IConfiguration::class);
		$configuration->method('hasValue')->willReturn(true);
		$configuration->expects(self::once())->method('removeValue')->with('old', 'key');
		$configuration->expects(self::once())->method('saveIfDirty')->willReturn(true);
		$response = $this->json($this->createDisplay($configuration, [
			'mode' => 'delete', 'id' => $this->encodeId('old', 'key')
		]));
		self::assertTrue($response['ok']);
		self::assertSame('deleted', $response['action']);
	}

	public function testReloadUsesCanonicalConfigurationReload(): void {
		$configuration = $this->createMock(IConfiguration::class);
		$configuration->expects(self::once())->method('reload');
		$response = $this->json($this->createDisplay($configuration, ['mode' => 'reload']));
		self::assertTrue($response['ok']);
		self::assertSame('reloaded', $response['action']);
	}

	public function testReloadReportsStorageFailures(): void {
		$configuration = $this->createStub(IConfiguration::class);
		$configuration->method('reload')->willReturnCallback(static function(): void { throw new \RuntimeException('unavailable'); });
		$response = $this->json($this->createDisplay($configuration, ['mode' => 'reload']));
		self::assertFalse($response['ok']);
		self::assertStringContainsString('unavailable', $response['error']);
	}

	public function testUnexpectedConfigurationReadFailureProducesStructuredResponse(): void {
		$configuration = $this->createStub(IConfiguration::class);
		$configuration->method('get')->willReturnCallback(static function(): never { throw new \RuntimeException('storage failed'); });
		$response = $this->json($this->createDisplay($configuration, ['mode' => 'page']));
		self::assertFalse($response['ok']);
		self::assertSame('Configuration request failed.', $response['error']);
	}
}
