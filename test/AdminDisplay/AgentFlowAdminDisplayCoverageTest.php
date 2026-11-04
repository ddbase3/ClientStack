<?php declare(strict_types=1);

namespace ClientStack\Test\AdminDisplay;

use Base3\Api\IModuleRegistry;
use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\Authorization\Api\IAuthorizationService;
use Base3\Authorization\Dto\AuthorizationDecision;
use Base3\LinkTarget\Api\ILinkTargetService;
use ClientStack\AdminDisplay\AgentFlowAdminDisplay;
use PHPUnit\Framework\TestCase;

final class AgentFlowAdminDisplayCoverageTest extends TestCase {

	private string $directory = '';

	protected function setUp(): void {
		http_response_code(200);
		$this->directory = sys_get_temp_dir() . '/clientstack-flow-tests-' . bin2hex(random_bytes(8));
		mkdir($this->directory . '/local/Flows', 0700, true);
	}

	protected function tearDown(): void {
		foreach (glob($this->directory . '/local/Flows/*') ?: [] as $file) {
			if (is_file($file)) @unlink($file);
		}
		@rmdir($this->directory . '/local/Flows');
		@rmdir($this->directory . '/local');
		@rmdir($this->directory);
		http_response_code(200);
	}

	private function buildDisplay(array &$assigned, ?IRequest $request = null, bool $allowed = true, ?IModuleRegistry $registry = null): AgentFlowAdminDisplay {
		$request ??= $this->createStub(IRequest::class);
		$view = $this->createStub(IMvcView::class);
		$view->method('assign')->willReturnCallback(static function(string $key, mixed $value) use (&$assigned): void {
			$assigned[$key] = $value;
		});
		$view->method('loadTemplate')->willReturn('flow-view');
		$link = $this->createStub(ILinkTargetService::class);
		$link->method('getLink')->willReturn('/agent-flows');
		$registry ??= $this->createStub(IModuleRegistry::class);
		$registry->method('getModuleNames')->willReturn(['missing-module', 'test-module']);
		$registry->method('getModulePath')->willReturnCallback(
			fn(string $module): ?string => $module === 'test-module' ? $this->directory : null
		);
		$auth = $this->createStub(IAuthorizationService::class);
		$auth->method('getDefaultAuthority')->willReturn('base3');
		$auth->method('decide')->willReturn($allowed ? AuthorizationDecision::allow() : AuthorizationDecision::deny());
		return new AgentFlowAdminDisplay($request, $view, $link, $registry, $auth);
	}

	public function testAuthorizationDenialPreventsFlowDiscovery(): void {
		$assigned = [];
		$display = $this->buildDisplay($assigned, null, false);
		self::assertSame('agentflowadmindisplay', $display::getName());
		self::assertSame('', $display->getOutput('json'));
		self::assertSame(403, http_response_code());
	}

	public function testEmptyFlowFolderProducesDocumentedEmptyState(): void {
		$assigned = [];
		$display = $this->buildDisplay($assigned);
		self::assertSame('flow-view', $display->getOutput());
		self::assertSame('No flows found.', $assigned['flow']['error']);
		self::assertSame([], $assigned['flow']['nodes']);
		self::assertSame('/agent-flows', $assigned['self_url']);
		self::assertSame('/agent-flows', $assigned['list_endpoint']);
		self::assertStringContainsString('flow viewer', $display->getHelp());
		$display->setData(['ignored' => true]);
	}

	public function testFlowScanIncludesOnlyJsonFilenamesContainingFlow(): void {
		file_put_contents($this->directory . '/local/Flows/example-flow.json', '{"nodes":[]}');
		file_put_contents($this->directory . '/local/Flows/not-a-flow.js', '{}');
		file_put_contents($this->directory . '/local/Flows/normal.json', '{}');
		$request = $this->createStub(IRequest::class);
		$request->method('get')->willReturn('list');
		$assigned = [];
		$response = json_decode($this->buildDisplay($assigned, $request)->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame('ok', $response['status']);
		self::assertCount(1, $response['data']['flows']);
		self::assertSame('test-module', $response['data']['flows'][0]['plugin']);
		self::assertSame('local/Flows/example-flow.json', $response['data']['flows'][0]['relpath']);
	}

	public function testHtmlLoadsNodesResourcesConnectionsAndIndexes(): void {
		file_put_contents($this->directory . '/local/Flows/example-flow.json', json_encode([
			'nodes' => [['id' => 'n1', 'name' => 'Start'], 'ignored', ['id' => 'n2']],
			'resources' => [['id' => 'r1', 'name' => 'Resource']],
			'connections' => [['from' => 'n1', 'to' => 'n2']]
		], JSON_THROW_ON_ERROR));
		$assigned = [];
		$display = $this->buildDisplay($assigned);
		self::assertSame('flow-view', $display->getOutput());
		self::assertNull($assigned['flow']['error']);
		self::assertSame(3, $assigned['flow']['meta']['node_count']);
		self::assertSame(1, $assigned['flow']['meta']['resource_count']);
		self::assertSame(1, $assigned['flow']['meta']['connection_count']);
		self::assertSame('Start', $assigned['flow']['node_index']['n1']['name']);
		self::assertSame('Resource', $assigned['flow']['resource_index']['r1']['name']);
		self::assertArrayNotHasKey('abspath', $assigned['flow']['selected']);
	}

	public function testFlowIndexIgnoresInvalidAndMissingIds(): void {
		file_put_contents($this->directory . '/local/Flows/example-flow.json', json_encode([
			'nodes' => [['name' => 'no id'], 'bad', ['id' => ''], ['id' => 'x', 'name' => 'yes']]
		], JSON_THROW_ON_ERROR));
		$assigned = [];
		$this->buildDisplay($assigned)->getOutput();
		self::assertCount(1, $assigned['flow']['node_index']);
		self::assertSame('yes', $assigned['flow']['node_index']['x']['name']);
	}

	public function testInvalidJsonIsRejectedWithoutRenderingNodes(): void {
		file_put_contents($this->directory . '/local/Flows/broken-flow.json', '{bad');
		$assigned = [];
		$this->buildDisplay($assigned)->getOutput();
		self::assertSame('Invalid JSON.', $assigned['flow']['error']);
		self::assertSame([], $assigned['flow']['nodes']);
	}

	public function testFlowWithMissingOrMalformedCollectionsUsesEmptyCollections(): void {
		file_put_contents($this->directory . '/local/Flows/simple-flow.json', json_encode([
			'nodes' => 'not-array', 'resources' => false, 'connections' => null
		], JSON_THROW_ON_ERROR));
		$assigned = [];
		$this->buildDisplay($assigned)->getOutput();
		self::assertSame([], $assigned['flow']['nodes']);
		self::assertSame([], $assigned['flow']['resources']);
		self::assertSame([], $assigned['flow']['connections']);
	}

	public function testUnknownFlowIdUsesNewestAvailableEntry(): void {
		file_put_contents($this->directory . '/local/Flows/example-flow.json', '{"nodes":[]}');
		$request = $this->createStub(IRequest::class);
		$request->method('get')->willReturnCallback(static fn(string $key): mixed => $key === 'flow' ? 'unknown' : null);
		$assigned = [];
		$this->buildDisplay($assigned, $request)->getOutput('html');
		self::assertStringContainsString('example-flow.json', $assigned['flow']['selected']['id']);
	}

	public function testUnknownJsonActionIsReportedWithoutFileSystemMutation(): void {
		$assigned = [];
		$request = $this->createStub(IRequest::class);
		$request->method('get')->willReturn('delete');
		$response = json_decode($this->buildDisplay($assigned, $request)->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame('error', $response['status']);
		self::assertStringContainsString('Unknown action', $response['message']);
	}
}
