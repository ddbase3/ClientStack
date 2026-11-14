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

namespace Base3\ClientStack\Test\SettingsTab;

use Base3\CoreFoundation\Authorization\Api\IAuthorizationService;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationDecision;
use Base3\CoreFoundation\Translation\Api\ITranslation;
use Base3\ClientStack\SettingsTab\AuthorizationadministrationSettingsTab;
use Base3\ClientStack\SettingsTab\AuthorizationfeaturesSettingsTab;
use Base3\ClientStack\SettingsTab\AuthorizationgroupsSettingsTab;
use Base3\ClientStack\SettingsTab\Base3SettingsTab;
use Base3\ClientStack\SettingsTab\ConfigurationadminSettingsTab;
use Base3\ClientStack\SettingsTab\DiagnosticscopeSettingsTab;
use Base3\ClientStack\SettingsTab\GlobalChatbotLauncherSettingsTab;
use Base3\ClientStack\SettingsTab\GovernanceSettingsTab;
use Base3\ClientStack\SettingsTab\JobsadminSettingsTab;
use Base3\ClientStack\SettingsTab\LogadminSettingsTab;
use Base3\ClientStack\SettingsTab\ServicesadminSettingsTab;
use Base3\ClientStack\SettingsTab\StatestoreadminSettingsTab;
use Base3\ClientStack\SettingsTab\UsermanagerdebugSettingsTab;
use PHPUnit\Framework\TestCase;

final class ClientStackSettingsTabCoverageTest extends TestCase {

		private function translation(): ITranslation {
				$translation = $this->createStub(ITranslation::class);
				$translation->method('translate')->willReturnCallback(
						static fn(string $group, string $name, string $key): string => $group . '/' . $name . '/' . $key
				);
				return $translation;
		}

		private function authorization(bool $allowed): IAuthorizationService {
				$authorization = $this->createStub(IAuthorizationService::class);
				$authorization->method('getDefaultAuthority')->willReturn('base3');
				$authorization->method('decide')->willReturn($allowed ? AuthorizationDecision::allow() : AuthorizationDecision::deny());
				return $authorization;
		}

		public function testBase3RootTabOwnsNameAndDefaults(): void {
				$tab = new Base3SettingsTab($this->translation());
				self::assertSame('base3settingstab', $tab::getName());
				self::assertSame(10, $tab->getOrder());
				self::assertNull($tab->getParentTabName());
				self::assertNull($tab->getDisplayName());
				self::assertNull($tab->getDisplayData());
				self::assertTrue($tab->isVisible());
				self::assertSame('Settings/base3settingstab/label', $tab->getLabel());
				self::assertSame('Settings/base3settingstab/description', $tab->getDescription());
		}

		public function testGovernanceRootUsesItsOwnIdentity(): void {
				$tab = new GovernanceSettingsTab($this->translation());
				self::assertSame('governancesettingstab', $tab::getName());
				self::assertSame(20, $tab->getOrder());
				self::assertNull($tab->getParentTabName());
				self::assertNull($tab->getDisplayName());
				self::assertTrue($tab->isVisible());
		}

		public function testAllBase3FeatureTabsAdvertiseTheirOwningDisplayAndCorrectOrder(): void {
				$expected = [
						[LogadminSettingsTab::class, 'logadmindisplay', 10],
						[ServicesadminSettingsTab::class, 'servicesadmindisplay', 20],
						[ConfigurationadminSettingsTab::class, 'configurationadmindisplay', 30],
						[UsermanagerdebugSettingsTab::class, 'usermanagerdebugdisplay', 40],
						[StatestoreadminSettingsTab::class, 'statestoreadmindisplay', 50],
						[JobsadminSettingsTab::class, 'jobsadmindisplay', 60],
						[AuthorizationgroupsSettingsTab::class, 'authorizationgroupsdisplay', 80],
						[AuthorizationfeaturesSettingsTab::class, 'authorizationfeaturesdisplay', 90],
						[AuthorizationadministrationSettingsTab::class, 'authorizationadministrationdisplay', 100]
				];
				foreach ($expected as [$class, $display, $order]) {
						$tab = new $class($this->translation(), $this->authorization(true));
						self::assertSame('base3settingstab', $tab->getParentTabName(), $class);
						self::assertSame($display, $tab->getDisplayName(), $class);
						self::assertSame($order, $tab->getOrder(), $class);
						self::assertSame(strtolower(substr($class, strrpos($class, '\\') + 1)), $tab::getName());
						self::assertSame('Settings/' . $tab::getName() . '/label', $tab->getLabel());
						self::assertSame('Settings/' . $tab::getName() . '/description', $tab->getDescription());
				}
		}

		public function testEveryFeatureTabRequiresAnExplicitFeatureAllow(): void {
				$classes = [
						LogadminSettingsTab::class, ServicesadminSettingsTab::class,
						ConfigurationadminSettingsTab::class, UsermanagerdebugSettingsTab::class,
						StatestoreadminSettingsTab::class, JobsadminSettingsTab::class,
						AuthorizationgroupsSettingsTab::class, AuthorizationfeaturesSettingsTab::class,
						AuthorizationadministrationSettingsTab::class
				];
				foreach ($classes as $class) {
						$allowed = new $class($this->translation(), $this->authorization(true));
						$denied = new $class($this->translation(), $this->authorization(false));
						self::assertTrue($allowed->isVisible(), $class);
						self::assertFalse($denied->isVisible(), $class);
						$permissions = $allowed->getPermissions();
						self::assertCount(1, $permissions);
						self::assertSame('feature', $permissions[0]->scope);
						self::assertSame($class::getName(), $permissions[0]->permission);
				}
		}

		public function testFeatureAudienceDoesNotImplicitlyGrantDevelopmentAccess(): void {
				$tab = new AuthorizationfeaturesSettingsTab($this->translation(), $this->authorization(false));
				self::assertSame(['group' => 'development'], $tab->getDefaultAudience());
				self::assertFalse($tab->isVisible());
				$other = new AuthorizationgroupsSettingsTab($this->translation(), $this->authorization(false));
				self::assertSame(['group' => 'administration'], $other->getDefaultAudience());
		}

		public function testDiagnosticScopeIsHiddenWithoutBothNativeAndFeatureAuthorization(): void {
				$tab = new DiagnosticscopeSettingsTab($this->translation(), $this->authorization(false));
				self::assertSame('governancesettingstab', $tab->getParentTabName());
				self::assertSame('diagnosticscopedisplay', $tab->getDisplayName());
				self::assertSame(10, $tab->getOrder());
				self::assertFalse($tab->isVisible());
		}

		public function testDiagnosticScopeIsShownWithBothDecisionsAllowed(): void {
				$auth = $this->createMock(IAuthorizationService::class);
				$auth->method('getDefaultAuthority')->willReturn('base3');
				$auth->expects(self::exactly(2))->method('decide')->willReturn(AuthorizationDecision::allow());
				$tab = new DiagnosticscopeSettingsTab($this->translation(), $auth);
				self::assertTrue($tab->isVisible());
		}

		public function testLauncherTabNeedsFeatureAndSystemAdministratorAuthorization(): void {
				$auth = $this->createMock(IAuthorizationService::class);
				$auth->method('getDefaultAuthority')->willReturn('base3');
				$auth->expects(self::exactly(2))->method('decide')->willReturn(AuthorizationDecision::allow());
				$tab = new GlobalChatbotLauncherSettingsTab($this->translation(), $auth);
				self::assertSame('chatbotsettingstab', $tab->getParentTabName());
				self::assertSame('globalchatbotlauncherconfigdisplay', $tab->getDisplayName());
				self::assertSame(20, $tab->getOrder());
				self::assertSame(['title' => $tab->getLabel(), 'description' => $tab->getDescription()], $tab->getDisplayData());
				self::assertTrue($tab->isVisible());
		}

		public function testLauncherIsHiddenWhenFeatureAuthorizationIsDenied(): void {
				$auth = $this->createMock(IAuthorizationService::class);
				$auth->method('getDefaultAuthority')->willReturn('base3');
				$auth->expects(self::once())->method('decide')->willReturn(AuthorizationDecision::deny());
				$tab = new GlobalChatbotLauncherSettingsTab($this->translation(), $auth);
				self::assertFalse($tab->isVisible());
		}

		public function testLauncherIsHiddenWhenSystemAdministratorAuthorizationIsDenied(): void {
				$auth = $this->createMock(IAuthorizationService::class);
				$auth->method('getDefaultAuthority')->willReturn('base3');
				$index = 0;
				$auth->expects(self::exactly(2))->method('decide')->willReturnCallback(
						static function () use (&$index): AuthorizationDecision {
								$index++;
								return $index === 1 ? AuthorizationDecision::allow() : AuthorizationDecision::deny();
						}
				);
				$tab = new GlobalChatbotLauncherSettingsTab($this->translation(), $auth);
				self::assertFalse($tab->isVisible());
		}
}
