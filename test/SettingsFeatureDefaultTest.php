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

namespace Base3\ClientStack\Test;

use Base3\CoreFoundation\Authorization\Api\IAuthorizationService;
use Base3\CoreFoundation\Translation\Api\ITranslation;
use Base3\ClientStack\SettingsTab\AuthorizationfeaturesSettingsTab;
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
