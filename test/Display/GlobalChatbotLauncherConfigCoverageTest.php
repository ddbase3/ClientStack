<?php declare(strict_types=1);

namespace ClientStack\Test\Display;

use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\Authorization\Api\IAuthorizationService;
use Base3\Authorization\Dto\AuthorizationDecision;
use Base3\LinkTarget\Api\ILinkTargetService;
use Base3\Settings\Api\ISettingsStore;
use ClientStack\Display\GlobalChatbotLauncherConfigDisplay;
use ClientStack\GlobalChatbotLauncher\GlobalChatbotLauncherSettings;
use PHPUnit\Framework\TestCase;

final class GlobalChatbotLauncherConfigCoverageTest extends TestCase {

	protected function setUp(): void { http_response_code(200); }
	protected function tearDown(): void { http_response_code(200); }

	private function auth(bool $feature, bool $administrator): IAuthorizationService {
		$auth = $this->createStub(IAuthorizationService::class);
		$auth->method('getDefaultAuthority')->willReturn('base3');
		$index = 0;
		$auth->method('decide')->willReturnCallback(
			static function () use (&$index, $feature, $administrator): AuthorizationDecision {
				$index++;
				return ($index % 2 === 1 ? $feature : $administrator) ? AuthorizationDecision::allow() : AuthorizationDecision::deny();
			}
		);
		return $auth;
	}

	private function display(
		IAuthorizationService $authorization,
		?IRequest $request = null,
		?ISettingsStore $store = null,
		?IMvcView $view = null
	): GlobalChatbotLauncherConfigDisplay {
		$links = $this->createStub(ILinkTargetService::class);
		$links->method('getLink')->willReturn('/launcher-settings?action=save');
		return new GlobalChatbotLauncherConfigDisplay(
			$view ?? $this->createStub(IMvcView::class),
			$request ?? $this->createStub(IRequest::class),
			$store ?? $this->createStub(ISettingsStore::class),
			$links,
			$authorization
		);
	}

	public function testFeatureDenialBlocksBothHtmlAndJson(): void {
		$display = $this->display($this->auth(false, true));
		self::assertSame('globalchatbotlauncherconfigdisplay', $display::getName());
		self::assertSame('', $display->getOutput('html'));
		self::assertSame(403, http_response_code());
		self::assertStringContainsString('launcher', $display->getHelp());
	}

	public function testAdministratorDenialReturnsStructuredJsonError(): void {
		$display = $this->display($this->auth(true, false));
		$response = json_decode($display->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertFalse($response['success']);
		self::assertSame('Access denied.', $response['message']);
		self::assertSame(403, http_response_code());
	}

	public function testAdministratorDenialReturnsHtmlAlert(): void {
		$display = $this->display($this->auth(true, false));
		self::assertStringContainsString('role="alert"', $display->getOutput('html'));
		self::assertSame(403, http_response_code());
	}

	public function testAuthorizedHtmlReceivesPersistedOptionsAndEndpoint(): void {
		$assigned = [];
		$view = $this->createStub(IMvcView::class);
		$view->method('assign')->willReturnCallback(static function(string $key, mixed $value) use (&$assigned): void {
			$assigned[$key] = $value;
		});
		$view->method('loadTemplate')->willReturn('settings-form');
		$store = $this->createStub(ISettingsStore::class);
		$store->method('get')->willReturn(['mode_docked_enabled' => true, 'secret' => 'hidden']);
		$display = $this->display($this->auth(true, true), null, $store, $view);
		$display->setData(['title' => 'Launcher settings', 'description' => 'Choose mode']);
		self::assertSame('settings-form', $display->getOutput('html'));
		self::assertSame('Launcher settings', $assigned['title']);
		self::assertSame('Choose mode', $assigned['description']);
		self::assertSame('/launcher-settings?action=save', $assigned['save_url']);
		self::assertTrue($assigned['settings']['mode_docked_enabled']);
		self::assertArrayNotHasKey('secret', $assigned['settings']);
	}

	public function testHtmlUsesDefaultsWhenSetDataIsInvalid(): void {
		$assigned = [];
		$view = $this->createStub(IMvcView::class);
		$view->method('assign')->willReturnCallback(static function(string $key, mixed $value) use (&$assigned): void {
			$assigned[$key] = $value;
		});
		$display = $this->display($this->auth(true, true), null, null, $view);
		$display->setData(['title' => 'Old']);
		$display->setData('invalid');
		$display->getOutput('html');
		self::assertSame('Global chatbot launcher', $assigned['title']);
		self::assertSame(GlobalChatbotLauncherSettings::DEFAULTS, $assigned['settings']);
	}

	public function testUnsupportedFormatReturnsNoContent(): void {
		$display = $this->display($this->auth(true, true));
		self::assertSame('', $display->getOutput('yaml'));
	}

	public function testGetRequestCannotSave(): void {
		$request = $this->createStub(IRequest::class);
		$request->method('request')->willReturn('save');
		$request->method('getContext')->willReturn(IRequest::CONTEXT_WEB_GET);
		$store = $this->createMock(ISettingsStore::class);
		$store->expects(self::never())->method('set');
		$response = json_decode($this->display($this->auth(true, true), $request, $store)->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertFalse($response['success']);
		self::assertSame(405, http_response_code());
	}

	public function testUnknownActionCannotSave(): void {
		$request = $this->createStub(IRequest::class);
		$request->method('request')->willReturn('delete');
		$response = json_decode($this->display($this->auth(true, true), $request)->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame('Invalid save request.', $response['message']);
		self::assertSame(405, http_response_code());
	}

	public function testSavePrioritizesFormFieldsOverJsonBody(): void {
		$request = $this->createMock(IRequest::class);
		$request->method('request')->willReturn('save');
		$request->method('getContext')->willReturn('web_post');
		$request->method('allPost')->willReturn(['mode_overlay_enabled' => '0', 'mode_modal_enabled' => '1']);
		$request->expects(self::never())->method('getJsonBody');
		$store = $this->createMock(ISettingsStore::class);
		$store->expects(self::once())->method('set')->with(
			GlobalChatbotLauncherSettings::GROUP,
			GlobalChatbotLauncherSettings::NAME,
			GlobalChatbotLauncherSettings::normalize(['mode_overlay_enabled' => false, 'mode_modal_enabled' => '1'])
		);
		$store->expects(self::once())->method('save');
		$response = json_decode($this->display($this->auth(true, true), $request, $store)->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertTrue($response['success']);
		self::assertFalse($response['settings']['mode_overlay_enabled']);
		self::assertTrue($response['settings']['mode_modal_enabled']);
	}

	public function testSaveAcceptsJsonBodyWhenPostDataIsEmpty(): void {
		$request = $this->createMock(IRequest::class);
		$request->method('request')->willReturn('save');
		$request->method('allPost')->willReturn([]);
		$request->expects(self::once())->method('getJsonBody')->willReturn(['mode_cover_enabled' => true]);
		$store = $this->createMock(ISettingsStore::class);
		$store->expects(self::once())->method('set');
		$store->expects(self::once())->method('save');
		$response = json_decode($this->display($this->auth(true, true), $request, $store)->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertTrue($response['success']);
		self::assertTrue($response['settings']['mode_cover_enabled']);
	}

	public function testSaveRejectsConfigurationWithoutAnyEnabledMode(): void {
		$request = $this->createStub(IRequest::class);
		$request->method('request')->willReturn('save');
		$request->method('allPost')->willReturn(array_fill_keys(array_keys(GlobalChatbotLauncherSettings::DEFAULTS), '0'));
		$store = $this->createMock(ISettingsStore::class);
		$store->expects(self::never())->method('save');
		$response = json_decode($this->display($this->auth(true, true), $request, $store)->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertFalse($response['success']);
		self::assertSame(422, http_response_code());
		self::assertStringContainsString('At least one', $response['message']);
	}

	public function testSaveDoesNotExposeInternalStorageFailure(): void {
		$request = $this->createStub(IRequest::class);
		$request->method('request')->willReturn('save');
		$request->method('allPost')->willReturn(['mode_overlay_enabled' => true]);
		$store = $this->createStub(ISettingsStore::class);
		$store->method('save')->willReturnCallback(static function(): void { throw new \RuntimeException('storage-secret'); });
		$response = json_decode($this->display($this->auth(true, true), $request, $store)->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertFalse($response['success']);
		self::assertSame(500, http_response_code());
		self::assertStringNotContainsString('storage-secret', $response['message']);
	}
}
