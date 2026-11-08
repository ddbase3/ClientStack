<?php declare(strict_types=1);

namespace ClientStack\Test\FileManager;

use Base3\Api\IContainer;
use ClientStack\ClientStackPlugin;
use ClientStack\FileManager\FileManagerHttpService;
use ClientStack\FileManager\FileUploadService;
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
