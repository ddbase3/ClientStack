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
use ReflectionMethod;
use UiFoundation\Api\IAuthorizationAudienceAdministrationService;

final class AuthorizationAudienceUserSearchRemainingCoverageTest extends TestCase {

	private const TOKEN = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

	protected function setUp(): void {
		http_response_code(200);
	}

	protected function tearDown(): void {
		http_response_code(200);
	}

	private function makeEndpoint(array $post, IUsermanager $users, bool $started = true, mixed $token = self::TOKEN): AuthorizationAudienceUserSearchDisplay {
		$authorization = $this->createStub(IAuthorizationService::class);
		$authorization->method('getDefaultAuthority')->willReturn('base3');
		$authorization->method('decide')->willReturn(AuthorizationDecision::allow());
		$admin = $this->createStub(IAuthorizationAudienceAdministrationService::class);
		$request = $this->createStub(IRequest::class);
		$request->method('server')->willReturn('POST');
		$request->method('post')->willReturnCallback(static fn(string $field, mixed $default = null): mixed => $post[$field] ?? $default);
		$session = $this->createStub(ISession::class);
		$session->method('started')->willReturn($started);
		$session->method('get')->willReturn($token);
		return new AuthorizationAudienceUserSearchDisplay($authorization, $admin, $users, $request, $session);
	}

	private function requiredPost(string $action = 'search'): array {
		return ['scope' => 'groups', 'csrf' => self::TOKEN, 'action' => $action];
	}

	public function testEndpointNameAndIgnoringInjectedConfiguration(): void {
		$users = $this->createMock(IUsermanager::class);
		$users->expects(self::never())->method('searchUsers');
		$endpoint = $this->makeEndpoint($this->requiredPost(), $users);
		self::assertSame('authorizationaudienceusersearchdisplay', AuthorizationAudienceUserSearchDisplay::getName());
		self::assertNull($endpoint->setData(['action' => 'resolve', 'id' => 99]));
		self::assertSame(['items' => [], 'hasMore' => false], json_decode($endpoint->getOutput('json'), true, 512, JSON_THROW_ON_ERROR));
	}

	public function testIncorrectScopeAndUnavailableSessionBothDenyDirectoryQueries(): void {
		foreach ([
			[$this->requiredPost() + ['query' => 'alice'], false, self::TOKEN],
			[array_replace($this->requiredPost(), ['scope' => 'other', 'query' => 'alice']), true, self::TOKEN],
			[array_replace($this->requiredPost(), ['scope' => ['groups'], 'query' => 'alice']), true, self::TOKEN],
			[$this->requiredPost() + ['query' => 'alice'], true, 'too short']
		] as [$post, $started, $token]) {
			$users = $this->createMock(IUsermanager::class);
			$users->expects(self::never())->method('searchUsers');
			$endpoint = $this->makeEndpoint($post, $users, $started, $token);
			self::assertSame(['success' => false, 'message' => 'Access denied.'],
				json_decode($endpoint->getOutput('json'), true, 512, JSON_THROW_ON_ERROR));
			self::assertSame(403, http_response_code());
		}
	}

	public function testUnsupportedOutputModeNeverReadsTheDirectory(): void {
		$users = $this->createMock(IUsermanager::class);
		$users->expects(self::never())->method('searchUsers');
		$endpoint = $this->makeEndpoint($this->requiredPost(), $users);
		self::assertSame('', $endpoint->getOutput('html'));
		self::assertSame(404, http_response_code());
	}

	public function testSearchingWithoutAnAdditionalPageReportsHasMoreFalse(): void {
		$users = $this->createMock(IUsermanager::class);
		$users->expects(self::once())->method('searchUsers')->with('alice', 3, 0)->willReturn([
			User::fromArray(['id' => 12, 'userid' => 'alice', 'name' => 'Alice']),
			User::fromArray(['id' => 0, 'userid' => 'invalid', 'name' => 'Invalid'])
		]);
		$endpoint = $this->makeEndpoint($this->requiredPost() + ['query' => ' alice ', 'limit' => 2, 'offset' => 0], $users);
		self::assertSame(['items' => [['value' => '12', 'label' => 'Alice (alice) [ID 12]']], 'hasMore' => false],
			json_decode($endpoint->getOutput('json'), true, 512, JSON_THROW_ON_ERROR));
	}

	public function testOversizedOrNonStringSearchNeverHitsNativeUsermanager(): void {
		foreach ([['query' => str_repeat('a', 257)], ['query' => ['alice']]] as $invalid) {
			$users = $this->createMock(IUsermanager::class);
			$users->expects(self::never())->method('searchUsers');
			$endpoint = $this->makeEndpoint(array_replace($this->requiredPost(), $invalid), $users);
			self::assertSame(422, $this->responseCodeAfter($endpoint));
		}
	}

	public function testMalformedPaginationNeverHitsNativeUsermanager(): void {
		foreach ([['limit' => []], ['offset' => []], ['limit' => '2.0'], ['limit' => '-1'], ['offset' => 'not-a-number']] as $invalid) {
			$users = $this->createMock(IUsermanager::class);
			$users->expects(self::never())->method('searchUsers');
			$endpoint = $this->makeEndpoint(array_replace($this->requiredPost(), ['query' => 'alice'], $invalid), $users);
			self::assertSame(422, $this->responseCodeAfter($endpoint));
		}
	}

	public function testResolvingNonNumericAndOversizedIdsNeverCallsNativeLookup(): void {
		foreach ([['id' => '0'], ['id' => '-4'], ['id' => 'not-an-id'], ['id' => '9999999999999999999999'], ['id' => []]] as $invalid) {
			$users = $this->createMock(IUsermanager::class);
			$users->expects(self::never())->method('getUserById');
			$endpoint = $this->makeEndpoint(array_replace($this->requiredPost('resolve'), $invalid), $users);
			self::assertSame(422, $this->responseCodeAfter($endpoint));
		}
	}

	public function testResolvedIdentityMismatchDoesNotBecomeAnOption(): void {
		$users = $this->createMock(IUsermanager::class);
		$users->expects(self::once())->method('getUserById')->with('12')->willReturn(
			User::fromArray(['id' => 13, 'name' => 'Other person', 'userid' => 'other'])
		);
		$endpoint = $this->makeEndpoint($this->requiredPost('resolve') + ['id' => 12], $users);
		self::assertSame(['item' => null], json_decode($endpoint->getOutput('json'), true, 512, JSON_THROW_ON_ERROR));
	}

	public function testMissingResolvedIdentityProducesNullInsteadOfPlaceholder(): void {
		$users = $this->createMock(IUsermanager::class);
		$users->expects(self::once())->method('getUserById')->with('12')->willReturn(null);
		$endpoint = $this->makeEndpoint($this->requiredPost('resolve') + ['id' => '12'], $users);
		self::assertSame(['item' => null], json_decode($endpoint->getOutput('json'), true, 512, JSON_THROW_ON_ERROR));
	}

	public function testProjectedLabelsFallBackToLoginOrGenericIdentityWithoutPrivateFields(): void {
		$method = new ReflectionMethod(AuthorizationAudienceUserSearchDisplay::class, 'project');
		self::assertSame(['value' => '12', 'label' => 'User [ID 12]'], $method->invoke(null,
			User::fromArray(['id' => 12, 'userid' => '', 'name' => '', 'email' => 'private@example.org'])));
		self::assertSame(['value' => '13', 'label' => 'alice [ID 13]'], $method->invoke(null,
			User::fromArray(['id' => 13, 'userid' => 'alice', 'name' => '  '])));
		self::assertSame(['value' => '14', 'label' => 'Alice [ID 14]'], $method->invoke(null,
			User::fromArray(['id' => 14, 'userid' => 'Alice', 'name' => 'Alice'])));
	}

	public function testProjectRejectsUnsupportedNativeIdentityShapes(): void {
		$method = new ReflectionMethod(AuthorizationAudienceUserSearchDisplay::class, 'project');
		self::assertNull($method->invoke(null, new \stdClass()));
		foreach ([0, -1, 'wrong', str_repeat('9', 21), [], null] as $id) {
			self::assertNull($method->invoke(null, User::fromArray(['id' => $id, 'userid' => 'test'])));
		}
	}

	private function responseCodeAfter(AuthorizationAudienceUserSearchDisplay $endpoint): int {
		http_response_code(200);
		$endpoint->getOutput('json');
		return http_response_code();
	}
}
