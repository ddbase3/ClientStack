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

use Base3\ClientStack\Display\StateStoreAdminDisplay;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

final class StateStoreAdminNormalizationCoverageTest extends TestCase {

	private function invoke(string $method, mixed ...$args): mixed {
		$instance = (new ReflectionClass(StateStoreAdminDisplay::class))->newInstanceWithoutConstructor();
		return (new ReflectionMethod(StateStoreAdminDisplay::class, $method))->invoke($instance, ...$args);
	}

	public function testRequestDefaultsToSafePageQuery(): void {
		$request = $this->invoke('normalizeRequest', []);
		self::assertSame('page', $request['mode']);
		self::assertSame(1, $request['page']);
		self::assertSame(50, $request['pageSize']);
		self::assertSame('', $request['search']);
		self::assertSame('', $request['id']);
		self::assertSame(['key' => 'key', 'dir' => 'asc', 'type' => 'string'], $request['sort']);
	}

	public function testRequestClampsBothPaginationBounds(): void {
		self::assertSame(1, $this->invoke('normalizeRequest', ['page' => -3, 'pageSize' => 0])['pageSize']);
		self::assertSame(250, $this->invoke('normalizeRequest', ['page' => 3, 'pageSize' => 999])['pageSize']);
		self::assertSame(1, $this->invoke('normalizeRequest', ['page' => -3])['page']);
	}

	public function testInvalidModeAndNonscalarFieldsAreDiscarded(): void {
		$request = $this->invoke('normalizeRequest', ['mode' => 'drop', 'search' => ['bad'], 'id' => new \stdClass()]);
		self::assertSame('page', $request['mode']);
		self::assertSame('', $request['search']);
		self::assertSame('', $request['id']);
	}

	public function testPageRecordSaveDeleteAreDistinctModes(): void {
		foreach (['page', 'record', 'save', 'delete'] as $mode) {
			self::assertSame($mode, $this->invoke('normalizeRequest', ['mode' => $mode])['mode']);
		}
		self::assertSame('page', $this->invoke('normalizeRequest', ['mode' => 'reload'])['mode']);
	}

	public function testSortRejectsUnknownColumnsAndCanonicalizesDirection(): void {
		foreach (['key', 'type', 'value_preview', 'updated_at', 'expires_at', 'expires_state'] as $key) {
			self::assertSame($key, $this->invoke('normalizeSort', [['key' => $key]])['key']);
		}
		self::assertSame('key', $this->invoke('normalizeSort', [['key' => 'password']])['key']);
		self::assertSame('desc', $this->invoke('normalizeSort', [['dir' => 'DESC']])['dir']);
		self::assertSame('asc', $this->invoke('normalizeSort', [['dir' => 'reverse']])['dir']);
		self::assertSame('asc', $this->invoke('normalizeSort', ['invalid'])['dir']);
	}

	public function testFiltersRejectUnknownTypeAndExpiryState(): void {
		self::assertSame(['key' => 'job', 'type' => '', 'expires_state' => ''],
			$this->invoke('normalizeFilters', ['key' => ' job ', 'type' => 'root', 'expires_state' => 'unknown']));
		self::assertSame(['key' => '', 'type' => '', 'expires_state' => ''], $this->invoke('normalizeFilters', 'bad'));
	}

	public function testFiltersAcceptRawDataTypeAndAllExpiryStates(): void {
		foreach (['persistent', 'active', 'expired'] as $state) {
			self::assertSame(['key' => '', 'type' => 'raw', 'expires_state' => $state],
				$this->invoke('normalizeFilters', ['type' => 'raw', 'expires_state' => $state]));
		}
	}

	public function testTypeDetectionDistinguishesJsonValueTypes(): void {
		foreach ([[null, 'null'], [true, 'bool'], [1, 'int'], [1.25, 'float'], [[], 'array'], [new \stdClass(), 'array'], ['text', 'string']] as [$value, $type]) {
			self::assertSame($type, $this->invoke('detectValueType', $value));
		}
	}

	public function testTypeNormalizationAcceptsRawAndRejectsUnrecognized(): void {
		self::assertSame('raw', $this->invoke('normalizeValueType', ' RAW '));
		self::assertSame('array', $this->invoke('normalizeValueType', ' ARRAY '));
		self::assertSame('string', $this->invoke('normalizeValueType', 'unknown'));
	}

	public function testRawAndJsonValuesAreEncodedDifferently(): void {
		self::assertSame('raw text', $this->invoke('encodeStoredValue', 'raw', 'raw text'));
		self::assertSame('"string"', $this->invoke('encodeStoredValue', 'string', 'string'));
		self::assertSame('true', $this->invoke('encodeStoredValue', 'bool', true));
		self::assertSame('[1,2]', $this->invoke('encodeStoredValue', 'array', [1, 2]));
	}

	public function testInvalidJsonEncodingThrowsInsteadOfPersistingCorruptData(): void {
		$this->expectException(RuntimeException::class);
		$this->invoke('encodeStoredValue', 'float', INF);
	}

	public function testRawStoredTextIsNotTreatedAsParsedJson(): void {
		self::assertSame(['is_json' => false, 'value' => '{invalid'], $this->invoke('decodeStoredValue', '{invalid'));
		self::assertSame(['is_json' => true, 'value' => false], $this->invoke('decodeStoredValue', 'false'));
		self::assertSame(['is_json' => true, 'value' => ['x' => 1]], $this->invoke('decodeStoredValue', '{"x":1}'));
	}

	public function testEditorDecodesRawAndTypedScalarValues(): void {
		self::assertSame('hi', $this->invoke('decodeEditorValue', 'raw', 'hi'));
		self::assertNull($this->invoke('decodeEditorValue', 'null', 'ignored'));
		self::assertSame(-10, $this->invoke('decodeEditorValue', 'int', '-10'));
		self::assertSame(2.5, $this->invoke('decodeEditorValue', 'float', '2.5'));
		self::assertSame('text', $this->invoke('decodeEditorValue', 'string', 'text'));
	}

	public function testEditorRejectsInvalidInteger(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->invoke('decodeEditorValue', 'int', '5.5');
	}

	public function testEditorRejectsInvalidFloat(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->invoke('decodeEditorValue', 'float', 'not-a-number');
	}

	public function testEditorDecodesArrayAndEmptyArray(): void {
		self::assertSame(['a' => 1], $this->invoke('decodeEditorValue', 'array', '{"a":1}'));
		self::assertSame([], $this->invoke('decodeEditorValue', 'array', ''));
		self::assertSame([true], $this->invoke('decodeEditorValue', 'array', [true]));
	}

	public function testEditorRejectsMalformedJson(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->invoke('decodeEditorValue', 'array', '{invalid');
	}

	public function testEditorRejectsScalarJsonAsArray(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->invoke('decodeEditorValue', 'array', '7');
	}

	public function testEditorRecognizesExplicitBooleanLiterals(): void {
		foreach (['true', 'YES', 'ON', '1'] as $text) self::assertTrue($this->invoke('decodeBoolEditorValue', $text));
		foreach (['false', 'NO', 'OFF', '0', ''] as $text) self::assertFalse($this->invoke('decodeBoolEditorValue', $text));
	}

	public function testEditorRejectsUnknownBooleanValue(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->invoke('decodeBoolEditorValue', 'perhaps');
	}

	public function testFormattingDistinguishesJsonBooleanNullAndScalar(): void {
		self::assertSame('null', $this->invoke('formatValueText', null, 'null'));
		self::assertSame('', $this->invoke('formatEditorValue', null, 'null'));
		self::assertSame('true', $this->invoke('formatValueText', true, 'bool'));
		self::assertSame('false', $this->invoke('formatEditorValue', false, 'bool'));
		self::assertSame('5.5', $this->invoke('formatValueText', 5.5, 'float'));
		self::assertStringContainsString('"x"', $this->invoke('formatEditorValue', ['x' => true], 'array'));
	}

	public function testJsonFormatterRejectsUnsupportedValues(): void {
		$this->expectException(RuntimeException::class);
		$this->invoke('formatJson', INF);
	}

	public function testExpiryStateSeparatesPermanentExpiredAndFutureEntries(): void {
		self::assertSame('persistent', $this->invoke('detectExpiresState', ''));
		self::assertSame('expired', $this->invoke('detectExpiresState', '2000-01-01 00:00:00'));
		self::assertSame('active', $this->invoke('detectExpiresState', '2999-01-01 00:00:00'));
	}

	public function testExpiryLabelsIdentifyExpiredAndPersistentStates(): void {
		self::assertSame('Never', $this->invoke('formatExpiresLabel', '', 'persistent'));
		self::assertSame('old (expired)', $this->invoke('formatExpiresLabel', 'old', 'expired'));
		self::assertSame('future', $this->invoke('formatExpiresLabel', 'future', 'active'));
	}

	public function testStateStoreIdsRoundTripAndRejectInvalidInput(): void {
		$id = $this->invoke('encodeId', 'queue.cursor');
		self::assertSame('queue.cursor', $this->invoke('decodeId', $id));
		foreach (['', '!!!!', base64_encode('{}'), base64_encode('false'), base64_encode('{"key":""}')] as $bad) {
			self::assertNull($this->invoke('decodeId', $bad));
		}
	}

	public function testRawAndStructuredRowsExposeCorrectTypes(): void {
		$raw = $this->invoke('normalizeRow', ['key' => 'x', 'value' => '{bad', 'expires_at' => '']);
		self::assertSame('raw', $raw['type']);
		self::assertFalse($raw['is_json']);
		self::assertSame('{bad', $raw['stored_value_raw']);
		self::assertSame('persistent', $raw['expires_state']);
		$json = $this->invoke('normalizeRow', ['key' => 'y', 'value' => '[1,2]', 'expires_at' => '2999-01-01 00:00:00']);
		self::assertSame('array', $json['type']);
		self::assertTrue($json['is_json']);
		self::assertSame('active', $json['expires_state']);
	}

	public function testStateSearchIncludesStoredAndExpirationMetadata(): void {
		$row = ['key' => 'Queue', 'type' => 'raw', 'value_text' => 'hello', 'stored_value_raw' => 'secret',
			'updated_at' => '2000-01-01', 'expires_at' => '2999-01-01', 'expires_state' => 'active'];
		foreach (['', 'queue', 'HELLO', 'secret', '2000', 'active'] as $search) {
			self::assertTrue($this->invoke('matchesSearch', $row, $search));
		}
		self::assertFalse($this->invoke('matchesSearch', $row, 'not-present'));
	}

	public function testStateFiltersRespectTypeKeyAndExpirationState(): void {
		$row = ['key' => 'Queue', 'type' => 'bool', 'expires_state' => 'active'];
		self::assertTrue($this->invoke('matchesFilters', $row, ['key' => 'queue', 'type' => 'bool', 'expires_state' => 'active']));
		self::assertFalse($this->invoke('matchesFilters', $row, ['key' => 'secret', 'type' => '', 'expires_state' => '']));
		self::assertFalse($this->invoke('matchesFilters', $row, ['key' => '', 'type' => 'int', 'expires_state' => '']));
		self::assertFalse($this->invoke('matchesFilters', $row, ['key' => '', 'type' => '', 'expires_state' => 'expired']));
	}

	public function testStateSortingSupportsNaturalAndReverseOrder(): void {
		$a = ['key' => 'item2'];
		$b = ['key' => 'item10'];
		self::assertTrue($this->invoke('compareRows', $a, $b, ['key' => 'key', 'dir' => 'asc']) < 0);
		self::assertTrue($this->invoke('compareRows', $a, $b, ['key' => 'key', 'dir' => 'desc']) > 0);
	}

	public function testShorteningPreservesSmallTextAndMarksLongText(): void {
		self::assertSame('hi', $this->invoke('shorten', 'hi', 20));
		self::assertTrue(str_ends_with($this->invoke('shorten', str_repeat('x', 100), 20), '…'));
	}

	public function testReadRowStringNeverConvertsArraysToText(): void {
		self::assertSame('', $this->invoke('readRowString', ['x' => ['bad']], 'x'));
		self::assertSame('1', $this->invoke('readRowString', ['x' => 1], 'x'));
		self::assertSame('', $this->invoke('readRowString', [], 'x'));
	}

	public function testReadStringTrimsOnlyScalarValues(): void {
		self::assertSame('hi', $this->invoke('readString', ['key' => ' hi '], 'key'));
		self::assertSame('', $this->invoke('readString', ['key' => ['invalid']], 'key'));
	}

	public function testTypeAndExpirationOptionsCoverAvailableValues(): void {
		self::assertSame(['', 'string', 'int', 'float', 'bool', 'array', 'null', 'raw'],
			array_column($this->invoke('getTypeOptions'), 'value'));
		self::assertSame(['', 'persistent', 'active', 'expired'],
			array_column($this->invoke('getExpiresStateOptions'), 'value'));
	}

	public function testErrorResponseContainsOperationAndMessage(): void {
		self::assertSame(['ok' => false, 'mode' => 'delete', 'error' => 'Invalid id'],
			$this->invoke('buildErrorResponse', 'Invalid id', 'delete'));
	}
}
