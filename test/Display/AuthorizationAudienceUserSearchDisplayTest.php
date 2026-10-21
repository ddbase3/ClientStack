<?php declare(strict_types=1);

namespace ClientStack\Test\Display;

use Base3\Api\IRequest;
use Base3\Authorization\Api\IAuthorizationService;
use Base3\Authorization\Dto\AuthorizationDecision;
use Base3\Session\Api\ISession;
use Base3\Usermanager\Api\IUsermanager;
use Base3\Usermanager\User;
use ClientStack\Display\AuthorizationAudienceUserSearchDisplay;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use UiFoundation\Api\IAuthorizationAudienceAdministrationService;

/** Exercised only through PHPUnit; no ILIAS global classes or executable file-level fixtures. */
final class AuthorizationAudienceUserSearchDisplayTest extends TestCase {

	private const TOKEN = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

	/** @param array<string,mixed> $post */
	private function endpoint(
		array $post,
		bool $authorized = true,
		bool $hostAllowed = true,
		string $method = 'POST'
	): array {
		$authorization = $this->createMock(IAuthorizationService::class);
		$authorization->method('getDefaultAuthority')->willReturn('ilias');
		$authorization->method('decide')->willReturn($authorized ? AuthorizationDecision::allow() : AuthorizationDecision::deny());
		$administration = $this->createMock(IAuthorizationAudienceAdministrationService::class);
		if (!$hostAllowed) {
			$administration->method('assertCanAdminister')->willThrowException(new RuntimeException('Native access denied.'));
		}
		$request = $this->createMock(IRequest::class);
		$request->method('server')->willReturnCallback(static fn(string $key, $default = null) => $key === 'REQUEST_METHOD' ? $method : $default);
		$request->method('post')->willReturnCallback(static fn(string $key, $default = null) => $post[$key] ?? $default);
		$session = $this->createMock(ISession::class);
		$session->method('started')->willReturn(true);
		$session->method('get')->willReturnCallback(static fn(string $key) => in_array($key, ['authorization.groups.csrf', 'authorization.features.csrf'], true) ? self::TOKEN : null);
		$users = $this->createMock(IUsermanager::class);
		$endpoint = new AuthorizationAudienceUserSearchDisplay($authorization, $administration, $users, $request, $session);
		return [$endpoint, $users, $administration];
	}

	/** @return array<string,mixed> */
	private function requestData(string $action = 'search'): array {
		return ['scope' => 'groups', 'csrf' => self::TOKEN, 'action' => $action, 'query' => 'müller', 'limit' => '2', 'offset' => '20'];
	}

	public function testMissingSystemPermissionNeverQueriesUserDirectory(): void {
		[$endpoint, $users, $administration] = $this->endpoint($this->requestData(), false);
		$administration->expects(self::never())->method('assertCanAdminister');
		$users->expects(self::never())->method('searchUsers');
		self::assertSame(403, $this->captureEndpointResponseCode($endpoint));
	}

	public function testHostDenialNeverQueriesUserDirectory(): void {
		[$endpoint, $users] = $this->endpoint($this->requestData(), true, false);
		$users->expects(self::never())->method('searchUsers');
		self::assertSame(403, $this->captureEndpointResponseCode($endpoint));
	}

	public function testInvalidCsrfAndGetAreRejectedBeforeReadingUsers(): void {
		foreach ([[$this->requestData() + [], 'GET', 405], [array_replace($this->requestData(), ['csrf' => 'wrong']), 'POST', 403]] as [$input, $method, $expected]) {
			[$endpoint, $users] = $this->endpoint($input, true, true, $method);
			$users->expects(self::never())->method('searchUsers');
			self::assertSame($expected, $this->captureEndpointResponseCode($endpoint));
		}
	}

	public function testSearchUsesNativePagedMethodAndReturnsOnlyMinimalLabels(): void {
		[$endpoint, $users] = $this->endpoint($this->requestData());
		$users->expects(self::once())->method('searchUsers')->with('müller', 3, 20)->willReturn([
			User::fromArray(['id' => 11, 'userid' => 'maxm', 'name' => 'Max Müller', 'email' => 'secret@example.org', 'roles' => ['admin']]),
			User::fromArray(['id' => 21, 'userid' => 'annam', 'name' => 'Anna Müller']),
			User::fromArray(['id' => 31, 'userid' => 'third', 'name' => 'Third'])
		]);
		$data = json_decode($endpoint->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame([
			'items' => [
				['value' => '11', 'label' => 'Max Müller (maxm) [ID 11]'],
				['value' => '21', 'label' => 'Anna Müller (annam) [ID 21]']
			],
			'hasMore' => true
		], $data);
		self::assertStringNotContainsString('secret@example.org', json_encode($data));
		self::assertStringNotContainsString('admin', json_encode($data));
	}

	public function testResolveUsesExistingSingleUserLookupOnly(): void {
		$payload = ['scope' => 'features', 'csrf' => self::TOKEN, 'action' => 'resolve', 'id' => '12'];
		[$endpoint, $users] = $this->endpoint($payload);
		$users->expects(self::never())->method('searchUsers');
		$users->expects(self::once())->method('getUserById')->with('12')->willReturn(
			User::fromArray(['id' => 12, 'userid' => 'ms', 'name' => 'Maria Schmidt'])
		);
		self::assertSame(['item' => ['value' => '12', 'label' => 'Maria Schmidt (ms) [ID 12]']],
			json_decode($endpoint->getOutput('json'), true, 512, JSON_THROW_ON_ERROR));
	}

	public function testEmptySearchDoesNotEnumerateUsers(): void {
		[$endpoint, $users] = $this->endpoint(array_replace($this->requestData(), ['query' => '  ']));
		$users->expects(self::never())->method('searchUsers');
		self::assertSame(['items' => [], 'hasMore' => false], json_decode($endpoint->getOutput('json'), true));
	}

	public function testInvalidPaginationCannotQueryUsers(): void {
		foreach ([['limit' => '99'], ['offset' => '-1'], ['limit' => '0'], ['offset' => '999999999999999999999999999999']] as $invalid) {
			[$endpoint, $users] = $this->endpoint(array_replace($this->requestData(), $invalid));
			$users->expects(self::never())->method('searchUsers');
			self::assertSame(422, $this->captureEndpointResponseCode($endpoint));
		}
	}

	public function testBackendFailuresDoNotExposeInternalDetails(): void {
		[$endpoint, $users] = $this->endpoint($this->requestData());
		$users->method('searchUsers')->willThrowException(new RuntimeException('sensitive sql connection details'));
		self::assertSame(500, $this->captureEndpointResponseCode($endpoint));
		self::assertStringNotContainsString('sql connection', $endpoint->getOutput('json'));
	}

	private function captureEndpointResponseCode(AuthorizationAudienceUserSearchDisplay $endpoint): int {
		http_response_code(200);
		$endpoint->getOutput('json');
		return http_response_code();
	}
}
