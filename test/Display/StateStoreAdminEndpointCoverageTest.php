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
use Base3\CoreFoundation\Database\Api\IDatabase;
use Base3\CoreFoundation\LinkTarget\Api\ILinkTargetService;
use Base3\ClientStack\Display\StateStoreAdminDisplay;
use PHPUnit\Framework\TestCase;

final class StateStoreAdminEndpointCoverageTest extends TestCase {

	protected function setUp(): void { http_response_code(200); }
	protected function tearDown(): void { http_response_code(200); }

	private function display(IDatabase $database, array $payload, bool $allowed = true, ?array &$assigned = null): StateStoreAdminDisplay {
		$request = $this->createStub(IRequest::class);
		$request->method('getJsonBody')->willReturn($payload);
		$auth = $this->createStub(IAuthorizationService::class);
		$auth->method('getDefaultAuthority')->willReturn('base3');
		$auth->method('decide')->willReturn($allowed ? AuthorizationDecision::allow() : AuthorizationDecision::deny());
		$view = $this->createStub(IMvcView::class);
		$view->method('loadTemplate')->willReturn('state-view');
		$view->method('assign')->willReturnCallback(static function(string $key, mixed $value) use (&$assigned): void {
			$assigned[$key] = $value;
		});
		$assets = $this->createStub(IAssetResolver::class);
		$assets->method('resolve')->willReturnCallback(static fn(string $path): string => '/asset/' . $path);
		$links = $this->createStub(ILinkTargetService::class);
		$links->method('getLink')->willReturn('/state?out=json');
		return new StateStoreAdminDisplay($request, $view, $assets, $database, $links, $auth);
	}

	private function database(): IDatabase {
		$db = $this->createStub(IDatabase::class);
		$db->method('escape')->willReturnCallback(static fn(string $value): string => addslashes($value));
		return $db;
	}

	private function json(StateStoreAdminDisplay $display): array {
		return json_decode($display->getOutput('JSON'), true, 512, JSON_THROW_ON_ERROR);
	}

	private function id(string $key): string {
		return rtrim(strtr(base64_encode(json_encode(['key' => $key], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
	}

	public function testFeatureDenialPreventsAllDatabaseQueries(): void {
		$db = $this->createMock(IDatabase::class);
		$db->expects(self::never())->method('multiQuery');
		$display = $this->display($db, ['mode' => 'page'], false);
		self::assertSame('statestoreadmindisplay', $display::getName());
		self::assertSame('', $display->getOutput('json'));
		self::assertSame(403, http_response_code());
	}

	public function testHtmlRendersWithGridAssetsAndExpiryOptions(): void {
		$assigned = [];
		$display = $this->display($this->database(), [], true, $assigned);
		self::assertSame('state-view', $display->getOutput());
		self::assertSame('/state?out=json', $assigned['service']);
		self::assertStringContainsString('modulargrid.css', $assigned['modularGridCssUrl']);
		self::assertContains('raw', array_column($assigned['typeOptions'], 'value'));
		self::assertContains('expired', array_column($assigned['expiresStateOptions'], 'value'));
		self::assertStringContainsString('StateStoreAdminDisplay', $display->getHelp());
		$display->setData(['ignored' => true]);
	}

	public function testPageListsStoredRowsAndTheirExpirationState(): void {
		$db = $this->database();
		$db->method('multiQuery')->willReturn([
			['key' => 'task2', 'value' => 'true', 'updated_at' => '2026-01-01', 'expires_at' => ''],
			['key' => 'task10', 'value' => '{invalid', 'updated_at' => '2026-01-01', 'expires_at' => '2000-01-01']
		]);
		$response = $this->json($this->display($db, ['mode' => 'page', 'pageSize' => 1, 'sort' => [['key' => 'key']]]));
		self::assertTrue($response['ok']);
		self::assertSame(2, $response['total']);
		self::assertTrue($response['hasMore']);
		self::assertSame('task2', $response['data'][0]['key']);
		self::assertSame('persistent', $response['data'][0]['expires_state']);
	}

	public function testPageFiltersByExpirationStateAndSearch(): void {
		$db = $this->database();
		$db->method('multiQuery')->willReturn([
			['key' => 'queue', 'value' => 'false', 'expires_at' => '2000-01-01'],
			['key' => 'session', 'value' => 'true', 'expires_at' => '']
		]);
		$response = $this->json($this->display($db, ['mode' => 'page', 'search' => 'queue', 'filters' => ['expires_state' => 'expired']]));
		self::assertSame(1, $response['total']);
		self::assertSame('queue', $response['data'][0]['key']);
	}

	public function testInvalidRecordIdIsRejectedBeforeQuery(): void {
		$db = $this->createMock(IDatabase::class);
		$db->expects(self::never())->method('singleQuery');
		$response = $this->json($this->display($db, ['mode' => 'record', 'id' => 'invalid']));
		self::assertFalse($response['found']);
		self::assertSame('Invalid state store id.', $response['error']);
	}

	public function testMissingRecordIsReportedAsNotFound(): void {
		$db = $this->database();
		$db->method('singleQuery')->willReturn(null);
		$response = $this->json($this->display($db, ['mode' => 'record', 'id' => $this->id('gone')]));
		self::assertFalse($response['found']);
		self::assertSame('State store entry not found.', $response['error']);
	}

	public function testRecordDisplaysDecodedValueAndDates(): void {
		$db = $this->database();
		$db->method('singleQuery')->willReturn([
			'key' => 'queue', 'value' => '{"enabled":true}',
			'updated_at' => '2026-01-01', 'expires_at' => ''
		]);
		$response = $this->json($this->display($db, ['mode' => 'record', 'id' => $this->id('queue')]));
		self::assertTrue($response['found']);
		self::assertSame('array', $response['record']['type']);
		self::assertSame('2026-01-01', $response['record']['updated_at']);
	}

	public function testSaveValidatesIdentifierAndEntryExistence(): void {
		$db = $this->createMock(IDatabase::class);
		$db->expects(self::never())->method('multiQuery');
		$response = $this->json($this->display($db, ['mode' => 'save', 'id' => 'invalid']));
		self::assertSame('Invalid state store id.', $response['error']);
		$db->method('singleQuery')->willReturn(null);
		$response = $this->json($this->display($db, ['mode' => 'save', 'id' => $this->id('gone')]));
		self::assertSame('State store entry not found.', $response['error']);
	}

	public function testSaveRejectsMalformedTypedValueBeforeUpdate(): void {
		$db = $this->createMock(IDatabase::class);
		$db->method('escape')->willReturnCallback(static fn(string $s): string => addslashes($s));
		$db->method('singleQuery')->willReturn(['key' => 'counter', 'value' => '1']);
		$db->expects(self::never())->method('multiQuery');
		$response = $this->json($this->display($db, [
			'mode' => 'save', 'id' => $this->id('counter'), 'type' => 'int', 'value' => 'not-an-int'
		]));
		self::assertFalse($response['ok']);
		self::assertStringContainsString('valid integer', $response['error']);
	}

	public function testSaveUpdatesOnlyTheExistingKeyUsingDatabaseOwner(): void {
		$db = $this->createMock(IDatabase::class);
		$db->method('escape')->willReturnCallback(static fn(string $s): string => addslashes($s));
		$db->method('singleQuery')->willReturn(['key' => 'counter', 'value' => '7']);
		$db->expects(self::once())->method('multiQuery')->willReturn([]);
		$response = $this->json($this->display($db, [
			'mode' => 'save', 'id' => $this->id('counter'), 'type' => 'int', 'value' => '8'
		]));
		self::assertTrue($response['ok']);
		self::assertSame('saved', $response['action']);
		self::assertSame('counter', $response['record']['key']);
	}

	public function testSaveFailureDoesNotReportSuccess(): void {
		$db = $this->database();
		$db->method('singleQuery')->willReturn(['key' => 'counter', 'value' => '7']);
		$db->method('multiQuery')->willReturnCallback(static function(): never { throw new \RuntimeException('not writable'); });
		$response = $this->json($this->display($db, [
			'mode' => 'save', 'id' => $this->id('counter'), 'type' => 'int', 'value' => '8'
		]));
		self::assertFalse($response['ok']);
		self::assertStringContainsString('not writable', $response['error']);
	}

	public function testDeleteRequiresValidExistingEntry(): void {
		$db = $this->database();
		$db->method('singleQuery')->willReturn(null);
		$response = $this->json($this->display($db, ['mode' => 'delete', 'id' => 'invalid']));
		self::assertSame('Invalid state store id.', $response['error']);
		$response = $this->json($this->display($db, ['mode' => 'delete', 'id' => $this->id('gone')]));
		self::assertSame('State store entry not found.', $response['error']);
	}

	public function testDeleteUsesBoundedDatabaseStatement(): void {
		$db = $this->createMock(IDatabase::class);
		$db->method('escape')->willReturnCallback(static fn(string $s): string => addslashes($s));
		$db->method('singleQuery')->willReturn(['key' => 'queue', 'value' => 'true']);
		$sql = '';
		$db->expects(self::once())->method('multiQuery')->willReturnCallback(static function(string $query) use (&$sql): array {
			$sql = $query;
			return [];
		});
		$response = $this->json($this->display($db, ['mode' => 'delete', 'id' => $this->id('queue')]));
		self::assertTrue($response['ok']);
		self::assertSame('deleted', $response['action']);
		self::assertStringContainsString('DELETE FROM', $sql);
		self::assertStringContainsString('LIMIT 1', $sql);
	}

	public function testDeleteFailureDoesNotReportSuccess(): void {
		$db = $this->database();
		$db->method('singleQuery')->willReturn(['key' => 'queue', 'value' => 'true']);
		$db->method('multiQuery')->willReturnCallback(static function(): never { throw new \RuntimeException('db unavailable'); });
		$response = $this->json($this->display($db, ['mode' => 'delete', 'id' => $this->id('queue')]));
		self::assertFalse($response['ok']);
		self::assertStringContainsString('db unavailable', $response['error']);
	}

	public function testUnexpectedDatabaseErrorProducesStructuredJsonFailure(): void {
		$db = $this->database();
		$db->method('multiQuery')->willReturnCallback(static function(): never { throw new \RuntimeException('query failed'); });
		$response = $this->json($this->display($db, ['mode' => 'page']));
		self::assertFalse($response['ok']);
		self::assertSame('State store request failed.', $response['error']);
	}
}
