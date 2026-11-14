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
use Base3\CoreFoundation\Api\IMvcView;
use Base3\CoreFoundation\Api\IRequest;
use Base3\CoreFoundation\Authorization\Api\IAuthorizationService;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationDecision;
use Base3\CoreFoundation\Configuration\Api\IConfiguration;
use Base3\CoreFoundation\LinkTarget\Api\ILinkTargetService;
use Base3\CoreFoundation\Session\Api\ISession;
use Base3\CoreFoundation\Translation\Api\ITranslation;
use Base3\CoreFoundation\Worker\Api\IJob;
use Base3\ClientStack\Display\JobsAdminDisplay;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class JobsAdminAdditionalCoverageTest extends TestCase {

	private function display(
		IRequest $request, IConfiguration $config, ISession $session,
		?IClassMap $classmap = null, ?IMvcView $view = null
	): JobsAdminDisplay {
		$classmap ??= $this->createStub(IClassMap::class);
		$view ??= $this->createStub(IMvcView::class);
		$links = $this->createStub(ILinkTargetService::class);
		$links->method('getLink')->willReturn('/jobs?out=json');
		$auth = $this->createStub(IAuthorizationService::class);
		$auth->method('getDefaultAuthority')->willReturn('base3');
		$auth->method('decide')->willReturn(AuthorizationDecision::allow());
		$translation = $this->createStub(ITranslation::class);
		$translation->method('translate')->willReturn('Rejected');
		return new JobsAdminDisplay($classmap, $view, $request, $config, $links, $auth, $session, $translation);
	}

	private function request(array $post, string $method = 'POST'): IRequest {
		$request = $this->createStub(IRequest::class);
		$request->method('post')->willReturnCallback(static fn(string $key, mixed $default = null): mixed => $post[$key] ?? $default);
		$request->method('server')->willReturn($method);
		return $request;
	}

	private function session(): ISession {
		$session = $this->createStub(ISession::class);
		$session->method('started')->willReturn(true);
		$session->method('get')->willReturn(str_repeat('x', 64));
		return $session;
	}

	public function testHtmlRendersWithActiveCsrfAndPriorityBounds(): void {
		$assigned = [];
		$view = $this->createStub(IMvcView::class);
		$view->method('loadTemplate')->willReturn('jobs-html');
		$view->method('assign')->willReturnCallback(static function(string $key, mixed $value) use (&$assigned): void {
			$assigned[$key] = $value;
		});
		$display = $this->display($this->createStub(IRequest::class),
			$this->createStub(IConfiguration::class), $this->session(), null, $view);
		self::assertSame('jobsadmindisplay', $display::getName());
		self::assertSame('jobs-html', $display->getOutput('html'));
		self::assertSame(1, $assigned['prioMin']);
		self::assertSame(10, $assigned['prioMax']);
		self::assertSame('job', $assigned['configGroup']);
		self::assertSame(str_repeat('x', 64), $assigned['csrf']);
		self::assertSame('/jobs?out=json', $assigned['endpoint']);
		self::assertStringContainsString('job control panel', $display->getHelp());
		$display->setData(null);
	}

	public function testHtmlGeneratesCsrfTokenWhenSessionTokenIsInvalid(): void {
		$session = $this->createMock(ISession::class);
		$session->method('started')->willReturn(true);
		$session->method('get')->willReturn('short');
		$token = '';
		$session->expects(self::once())->method('set')->willReturnCallback(
			static function(string $name, mixed $value) use (&$token): void { $token = $value; }
		);
		$display = $this->display($this->createStub(IRequest::class), $this->createStub(IConfiguration::class), $session);
		$display->getOutput('html');
		self::assertSame(64, strlen($token));
	}

	public function testHtmlRequiresAnActiveSessionForCsrfGeneration(): void {
		$session = $this->createStub(ISession::class);
		$session->method('started')->willReturn(false);
		$display = $this->display($this->createStub(IRequest::class), $this->createStub(IConfiguration::class), $session);
		$this->expectException(\RuntimeException::class);
		$display->getOutput();
	}

	public function testUnknownActionReturnsStructuredError(): void {
		$display = $this->display($this->request(['action' => 'remove_all']), $this->createStub(IConfiguration::class), $this->session());
		$response = json_decode($display->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame('error', $response['status']);
		self::assertStringContainsString('Unknown action', $response['message']);
	}

	public function testEmptyJobListReturnsEmptyArray(): void {
		$classmap = $this->createStub(IClassMap::class);
		$classmap->method('getInstances')->willReturn([]);
		$display = $this->display($this->request(['action' => 'list']),
			$this->createStub(IConfiguration::class), $this->session(), $classmap);
		$response = json_decode($display->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame('ok', $response['status']);
		self::assertSame('job', $response['data']['group']);
		self::assertSame([], $response['data']['jobs']);
	}

	public function testJobListingUsesEffectiveConfigurationAndClampsPriority(): void {
		$job = $this->createStub(IJob::class);
		$job->method('isActive')->willReturn(true);
		$job->method('getPriority')->willReturn(99);
		$classmap = $this->createStub(IClassMap::class);
		$classmap->method('getInstances')->willReturn([$job]);
		$config = $this->createStub(IConfiguration::class);
		$config->method('hasValue')->willReturn(true);
		$config->method('getBool')->willReturn(false);
		$config->method('getInt')->willReturn(999);
		$display = $this->display($this->request(['action' => 'list']), $config, $this->session(), $classmap);
		$response = json_decode($display->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame('ok', $response['status']);
		self::assertCount(1, $response['data']['jobs']);
		self::assertFalse($response['data']['jobs'][0]['active']);
		self::assertSame(10, $response['data']['jobs'][0]['priority']);
		self::assertTrue($response['data']['jobs'][0]['hasActiveConfig']);
	}

	public function testSetPriorityClampsPositiveDeltaAndPersistsExactlyOnce(): void {
		$config = $this->createMock(IConfiguration::class);
		$config->method('getInt')->willReturn(9);
		$config->expects(self::once())->method('persistValue')->with('job', 'task.priority', '10')->willReturn(true);
		$display = $this->display($this->request(['action' => 'prio', 'job' => 'task', 'delta' => '8', 'csrf' => str_repeat('x', 64)]),
			$config, $this->session());
		$response = json_decode($display->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame('ok', $response['status']);
		self::assertSame(10, $response['data']['job']['priority']);
	}

	public function testSetPriorityClampsNegativeDelta(): void {
		$config = $this->createMock(IConfiguration::class);
		$config->method('getInt')->willReturn(2);
		$config->expects(self::once())->method('persistValue')->with('job', 'task.priority', '1')->willReturn(true);
		$display = $this->display($this->request(['action' => 'prio', 'job' => 'task', 'delta' => '-10', 'csrf' => str_repeat('x', 64)]),
			$config, $this->session());
		$response = json_decode($display->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame(1, $response['data']['job']['priority']);
	}

	public function testSetPriorityRejectsMissingValueAndDelta(): void {
		$config = $this->createMock(IConfiguration::class);
		$config->expects(self::never())->method('persistValue');
		$display = $this->display($this->request(['action' => 'prio', 'job' => 'task', 'csrf' => str_repeat('x', 64)]),
			$config, $this->session());
		$response = json_decode($display->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame('error', $response['status']);
		self::assertStringContainsString('Missing value or delta', $response['message']);
	}

	public function testSetActiveNormalizesJobKeyBeforePersistence(): void {
		$config = $this->createMock(IConfiguration::class);
		$config->expects(self::once())->method('persistValue')->with('job', 'myjob.active', '0')->willReturn(true);
		$display = $this->display($this->request(['action' => 'set_active', 'job' => '  MY JOB  ', 'value' => 'no', 'csrf' => str_repeat('x', 64)]),
			$config, $this->session());
		$response = json_decode($display->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame('myjob', $response['data']['job']['jobKey']);
		self::assertFalse($response['data']['job']['active']);
	}

	public function testEmptyJobKeyIsRejectedBeforePersisting(): void {
		$config = $this->createMock(IConfiguration::class);
		$config->expects(self::never())->method('persistValue');
		$display = $this->display($this->request(['action' => 'set_active', 'job' => '!!', 'value' => 'yes', 'csrf' => str_repeat('x', 64)]),
			$config, $this->session());
		$response = json_decode($display->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertStringContainsString('Missing job', $response['message']);
	}

	public function testWriteFallbackUsesCanonicalConfigurationSetAndSave(): void {
		$config = $this->createMock(IConfiguration::class);
		$config->expects(self::once())->method('persistValue')->willReturn(false);
		$config->expects(self::once())->method('setValue')->with('job', 'myjob.active', '1');
		$config->expects(self::once())->method('saveIfDirty')->willReturn(true);
		$display = $this->display($this->request(['action' => 'set_active', 'job' => 'myjob', 'value' => '1', 'csrf' => str_repeat('x', 64)]),
			$config, $this->session());
		$response = json_decode($display->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame('ok', $response['status']);
	}

	public function testBooleanAndKeyNormalizationOnlyAllowExpectedCharacters(): void {
		$display = $this->display($this->createStub(IRequest::class), $this->createStub(IConfiguration::class), $this->session());
		$key = new ReflectionMethod(JobsAdminDisplay::class, 'normalizeKey');
		self::assertSame('job.name-1', $key->invoke($display, ' JOB.NAME-1 '));
		self::assertSame('xy', $key->invoke($display, 'x@y'));
		$bool = new ReflectionMethod(JobsAdminDisplay::class, 'normalizeBool');
		self::assertTrue($bool->invoke($display, ' on '));
		self::assertFalse($bool->invoke($display, 'off'));
		self::assertTrue($bool->invoke($display, true));
	}
}
