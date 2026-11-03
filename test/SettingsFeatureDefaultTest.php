<?php declare(strict_types=1);

namespace ClientStack\Test;

use Base3\Authorization\Api\IAuthorizationService;
use Base3\Translation\Api\ITranslation;
use ClientStack\SettingsTab\AuthorizationfeaturesSettingsTab;
use PHPUnit\Framework\TestCase;

final class SettingsFeatureDefaultTest extends TestCase {

	public function testFeatureEditorDefaultsToDevelopmentAudienceOnly(): void {
		$tab = new AuthorizationfeaturesSettingsTab(
			$this->createStub(ITranslation::class),
			$this->createStub(IAuthorizationService::class)
		);
		self::assertSame(['group' => 'development'], $tab->getDefaultAudience());
		self::assertSame('authorizationfeaturesdisplay', $tab->getDisplayName());
	}
}
