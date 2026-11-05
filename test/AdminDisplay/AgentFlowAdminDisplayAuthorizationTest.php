<?php declare(strict_types=1);

namespace ClientStack\Test\AdminDisplay;

use Base3\Api\IModuleRegistry;
use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\Authorization\Api\IAuthorizationService;
use Base3\Authorization\Dto\AuthorizationDecision;
use Base3\Authorization\Dto\AuthorizationRequest;
use Base3\LinkTarget\Api\ILinkTargetService;
use ClientStack\AdminDisplay\AgentFlowAdminDisplay;
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
