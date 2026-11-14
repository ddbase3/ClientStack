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

namespace Base3\ClientStack\Test\AdminDisplay;

use Base3\CoreFoundation\Api\IModuleRegistry;
use Base3\CoreFoundation\Api\IMvcView;
use Base3\CoreFoundation\Api\IRequest;
use Base3\CoreFoundation\Authorization\Api\IAuthorizationService;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationDecision;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest;
use Base3\CoreFoundation\LinkTarget\Api\ILinkTargetService;
use Base3\ClientStack\AdminDisplay\AgentFlowAdminDisplay;
use PHPUnit\Framework\TestCase;

final class AgentFlowAdminDisplayAuthorizationTest extends TestCase {

		private function authorization(bool $allowed): IAuthorizationService {
				$authorization = $this->createMock(IAuthorizationService::class);
				$authorization->method('getDefaultAuthority')->willReturn('base3');
				$authorization->expects(self::once())->method('decide')->with(self::callback(
						static fn(AuthorizationRequest $request): bool =>
								$request->authority === 'base3' &&
								$request->permission->scope === 'system' &&
								$request->permission->permission === 'admin'
				))->willReturn($allowed ? AuthorizationDecision::allow() : AuthorizationDecision::deny());
				return $authorization;
		}

		public function testDeniedAccessDoesNotScanFlows(): void {
				$modules = $this->createMock(IModuleRegistry::class);
				$modules->expects(self::never())->method('getModuleNames');
				$display = new AgentFlowAdminDisplay(
						$this->createStub(IRequest::class),
						$this->createStub(IMvcView::class),
						$this->createStub(ILinkTargetService::class),
						$modules,
						$this->authorization(false)
				);
				$previous = http_response_code();
				try {
						self::assertSame('', $display->getOutput('json'));
						self::assertSame(403, http_response_code());
				} finally {
						http_response_code($previous ?: 200);
				}
		}

		public function testAllowedAdministratorCanReadJsonFlowList(): void {
				$request = $this->createStub(IRequest::class);
				$request->method('get')->willReturnCallback(
						static fn(string $key) => $key === 'action' ? 'list' : null
				);
				$modules = $this->createMock(IModuleRegistry::class);
				$modules->expects(self::once())->method('getModuleNames')->willReturn([]);
				$display = new AgentFlowAdminDisplay(
						$request,
						$this->createStub(IMvcView::class),
						$this->createStub(ILinkTargetService::class),
						$modules,
						$this->authorization(true)
				);
				$result = json_decode($display->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
				self::assertSame('ok', $result['status']);
				self::assertSame([], $result['data']['flows']);
		}
}
