<?php declare(strict_types=1);

namespace ClientStack\Test\Display;

use ClientStack\Display\ConfigurationAdminDisplay;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

final class ConfigurationAdminNormalizationCoverageTest extends TestCase {

	private function invoke(string $method, mixed ...$args): mixed {
		$instance = (new ReflectionClass(ConfigurationAdminDisplay::class))->newInstanceWithoutConstructor();
		return (new ReflectionMethod(ConfigurationAdminDisplay::class, $method))->invoke($instance, ...$args);
	}

	public function testRequestNormalizationDefaultsToFirstPageAndGroupSort(): void {
		$request = $this->invoke('normalizeRequest', []);
		self::assertSame('page', $request['mode']);
		self::assertSame(1, $request['page']);
		self::assertSame(50, $request['pageSize']);
		self::assertSame('', $request['search']);
		self::assertSame(['key' => 'group', 'dir' => 'asc', 'type' => 'string'], $request['sort']);
	}

	public function testRequestNormalizationClampsPageAndPageSize(): void {
		$request = $this->invoke('normalizeRequest', ['page' => -10, 'pageSize' => 999]);
		self::assertSame(1, $request['page']);
		self::assertSame(250, $request['pageSize']);
		$request = $this->invoke('normalizeRequest', ['page' => 5, 'pageSize' => -1]);
		self::assertSame(5, $request['page']);
		self::assertSame(1, $request['pageSize']);
	}

	public function testInvalidModeDoesNotEnableMutation(): void {
		$request = $this->invoke('normalizeRequest', ['mode' => 'drop_table', 'search' => ['invalid'], 'id' => []]);
		self::assertSame('page', $request['mode']);
		self::assertSame('', $request['search']);
		self::assertSame('', $request['id']);
	}

	public function testValidModesRemainDistinct(): void {
		foreach (['page', 'record', 'save', 'delete', 'reload'] as $mode) {
			self::assertSame($mode, $this->invoke('normalizeRequest', ['mode' => $mode])['mode']);
		}
	}

	public function testSortOnlyAllowsKnownColumns(): void {
		foreach (['group', 'key', 'type', 'value_preview'] as $key) {
			self::assertSame($key, $this->invoke('normalizeSort', [['key' => $key]])['key']);
		}
		self::assertSame('group', $this->invoke('normalizeSort', [['key' => 'password']])['key']);
		self::assertSame('group', $this->invoke('normalizeSort', 'bad')['key']);
	}

	public function testSortCanonicalizesDirectionAndMalformedRows(): void {
		self::assertSame('desc', $this->invoke('normalizeSort', [['dir' => 'DESC']])['dir']);
		self::assertSame('asc', $this->invoke('normalizeSort', [['dir' => 'reverse']])['dir']);
		self::assertSame('asc', $this->invoke('normalizeSort', ['bad'])['dir']);
	}

	public function testFiltersRemoveUnsupportedTypeAndIgnoreUnknownFields(): void {
		self::assertSame(['group' => 'admin', 'key' => 'flag', 'type' => ''],
			$this->invoke('normalizeFilters', [
				'group' => ' admin ', 'key' => ' flag ', 'type' => 'unsupported', 'secret' => 'hidden'
			]));
		self::assertSame(['group' => '', 'key' => '', 'type' => ''], $this->invoke('normalizeFilters', 'bad'));
	}

	public function testFiltersPreserveAllSupportedValueTypes(): void {
		foreach (['string', 'int', 'float', 'bool', 'array', 'null'] as $type) {
			self::assertSame($type, $this->invoke('normalizeFilters', ['type' => $type])['type']);
		}
	}

	public function testTypeDetectionHandlesAllPersistableValues(): void {
		foreach ([[null, 'null'], [true, 'bool'], [7, 'int'], [1.5, 'float'], [[], 'array'], [new \stdClass(), 'array'], ['x', 'string']] as [$value, $type]) {
			self::assertSame($type, $this->invoke('detectValueType', $value));
		}
	}

	public function testTypeNormalizationTrimsLowercasesAndDefaultsToString(): void {
		self::assertSame('array', $this->invoke('normalizeValueType', ' ARRAY '));
		self::assertSame('string', $this->invoke('normalizeValueType', 'secret'));
		self::assertSame('string', $this->invoke('normalizeValueType', ''));
	}

	public function testEditorDecodesIntegerAndFloatValues(): void {
		self::assertSame(-17, $this->invoke('decodeEditorValue', 'int', ' -17 '));
		self::assertSame(1.25, $this->invoke('decodeEditorValue', 'float', ' 1.25 '));
		self::assertSame('hello', $this->invoke('decodeEditorValue', 'string', 'hello'));
	}

	public function testEditorRejectsInvalidInteger(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->invoke('decodeEditorValue', 'int', '12.5');
	}

	public function testEditorRejectsEmptyFloat(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->invoke('decodeEditorValue', 'float', '');
	}

	public function testEditorDecodesNullAndJsonArray(): void {
		self::assertNull($this->invoke('decodeEditorValue', 'null', 'ignored'));
		self::assertSame(['nested' => true], $this->invoke('decodeEditorValue', 'array', '{"nested":true}'));
		self::assertSame([], $this->invoke('decodeEditorValue', 'array', ''));
		self::assertSame([1, 2], $this->invoke('decodeEditorValue', 'array', [1, 2]));
	}

	public function testEditorRejectsMalformedArrayJson(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->invoke('decodeEditorValue', 'array', '{bad');
	}

	public function testEditorRejectsScalarArrayJson(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->invoke('decodeEditorValue', 'array', '5');
	}

	public function testEditorDecodesAllBooleanLiterals(): void {
		foreach (['true', 'YES', 'on', '1'] as $value) {
			self::assertTrue($this->invoke('decodeBoolEditorValue', $value));
		}
		foreach (['false', 'NO', 'off', '0', ''] as $value) {
			self::assertFalse($this->invoke('decodeBoolEditorValue', $value));
		}
	}

	public function testEditorRejectsUnknownBooleanValue(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->invoke('decodeBoolEditorValue', 'possibly');
	}

	public function testValuesAreFormattedForDisplayAndEditing(): void {
		self::assertSame('null', $this->invoke('formatValueText', null, 'null'));
		self::assertSame('', $this->invoke('formatEditorValue', null, 'null'));
		self::assertSame('true', $this->invoke('formatValueText', true, 'bool'));
		self::assertSame('false', $this->invoke('formatEditorValue', false, 'bool'));
		self::assertSame('12.5', $this->invoke('formatValueText', 12.5, 'float'));
		self::assertStringContainsString('"foo"', $this->invoke('formatEditorValue', ['foo' => 2], 'array'));
	}

	public function testEncodingInvalidJsonValuesFailsExplicitly(): void {
		$this->expectException(RuntimeException::class);
		$this->invoke('formatJson', INF);
	}

	public function testConfigurationIdEncodesGroupAndKeyWithoutLeakingRawText(): void {
		$id = $this->invoke('encodeId', 'platform', 'session');
		self::assertSame(['group' => 'platform', 'key' => 'session'], $this->invoke('decodeId', $id));
		self::assertStringNotContainsString('platform', $id);
	}

	public function testConfigurationIdRejectsMalformedAndIncompletePayloads(): void {
		foreach (['', '!!!!', base64_encode('{}'), base64_encode('5'), base64_encode('{"group":"", "key":"x"}')] as $id) {
			self::assertNull($this->invoke('decodeId', $id));
		}
	}

	public function testConfigurationSearchIncludesGroupKeyTypeAndValue(): void {
		$row = ['group' => 'System', 'key' => 'Widget', 'type' => 'bool', 'value_text' => 'Enabled'];
		foreach (['system', 'WIDGET', 'BOOL', 'enabled', ''] as $search) {
			self::assertTrue($this->invoke('matchesSearch', $row, $search));
		}
		self::assertFalse($this->invoke('matchesSearch', $row, 'missing'));
	}

	public function testConfigurationFiltersAreCaseInsensitiveExceptType(): void {
		$row = ['group' => 'Platform', 'key' => 'Enable', 'type' => 'bool'];
		self::assertTrue($this->invoke('matchesFilters', $row, ['group' => 'plat', 'key' => 'EN', 'type' => 'bool']));
		self::assertFalse($this->invoke('matchesFilters', $row, ['group' => 'missing', 'key' => '', 'type' => '']));
		self::assertFalse($this->invoke('matchesFilters', $row, ['group' => '', 'key' => 'missing', 'type' => '']));
		self::assertFalse($this->invoke('matchesFilters', $row, ['group' => '', 'key' => '', 'type' => 'string']));
	}

	public function testConfigurationSortUsesNaturalAndReversedOrdering(): void {
		$a = ['group' => 'same', 'key' => 'setting2'];
		$b = ['group' => 'same', 'key' => 'setting10'];
		self::assertTrue($this->invoke('compareRows', $a, $b, ['key' => 'key', 'dir' => 'asc']) < 0);
		self::assertTrue($this->invoke('compareRows', $a, $b, ['key' => 'key', 'dir' => 'desc']) > 0);
	}

	public function testNormalizedRowIncludesTypedEditorMetadata(): void {
		$row = $this->invoke('normalizeRow', 'ui', 'enabled', true);
		self::assertSame('bool', $row['type']);
		self::assertSame('true', $row['value_text']);
		self::assertTrue($row['is_scalar']);
		self::assertFalse($row['is_array']);
		self::assertSame(['group' => 'ui', 'key' => 'enabled'], $this->invoke('decodeId', $row['id']));
	}

	public function testNormalizedArrayRowIncludesJsonForEditing(): void {
		$row = $this->invoke('normalizeRow', 'ui', 'options', ['a' => 1]);
		self::assertSame('array', $row['type']);
		self::assertTrue($row['is_array']);
		self::assertStringContainsString('"a"', $row['value_edit']);
	}

	public function testLongValuesAreTruncatedWithoutChangingShortValues(): void {
		self::assertSame('short', $this->invoke('shorten', 'short', 10));
		self::assertTrue(str_ends_with($this->invoke('shorten', str_repeat('a', 50), 20), '…'));
	}

	public function testMalformedTextFieldsAreNotCoercedIntoIdentifiers(): void {
		self::assertSame('', $this->invoke('readString', ['key' => ['private']], 'key'));
		self::assertSame('value', $this->invoke('readString', ['key' => ' value '], 'key'));
	}

	public function testTypeOptionsAreCompleteAndErrorResponsesPreserveMode(): void {
		$options = $this->invoke('getTypeOptions');
		self::assertSame(['', 'string', 'int', 'float', 'bool', 'array', 'null'], array_column($options, 'value'));
		self::assertSame(['ok' => false, 'mode' => 'save', 'error' => 'bad'], $this->invoke('buildErrorResponse', 'bad', 'save'));
	}
}
