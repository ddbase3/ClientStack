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

namespace Base3\ClientStack\Test\FileManager;

use Base3\CoreFoundation\Api\IContainer;
use Base3\ClientStack\ClientStackPlugin;
use Base3\ClientStack\FileManager\FileManagerHttpService;
use Base3\ClientStack\FileManager\FileUploadService;
use PHPUnit\Framework\TestCase;

final class FileManagerServiceRegistrationTest extends TestCase {

	public function testGenericFileManagerServicesAreRegisteredLazilyAndReplaceably(): void {
		$entries = [];
		$container = $this->createMock(IContainer::class);
		$container->method('set')->willReturnCallback(
			static function(string $name, mixed $value, int $flags = 0) use (&$entries, $container): IContainer {
				$entries[$name] = [$value, $flags];
				return $container;
			}
		);
		$container->expects(self::never())->method('get');
		(new ClientStackPlugin($container))->init();
		foreach ([FileUploadService::class, FileManagerHttpService::class] as $name) {
			self::assertArrayHasKey($name, $entries);
			self::assertInstanceOf(\Closure::class, $entries[$name][0]);
			self::assertSame(IContainer::SHARED | IContainer::NOOVERWRITE, $entries[$name][1]);
		}
	}
}
