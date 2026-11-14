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

use Base3\CoreFoundation\Api\IAssetResolver;
use Base3\CoreFoundation\Api\IMvcView;
use Base3\ClientStack\Display\ClassicChatbotDisplay;
use PHPUnit\Framework\TestCase;

class ClassicChatbotDisplayTest extends TestCase {

	public function testGetNameReturnsExpectedValue(): void {
		$this->assertSame('classicchatbotdisplay', ClassicChatbotDisplay::getName());
	}

	public function testGetOutputRendersClientStackTemplateAndAssets(): void {
		$view = new FakeMvcView();
		$display = new ClassicChatbotDisplay($view, new FakeAssetResolver());
		$display->setData([
			'service_url' => '/chatbot/service',
			'service' => 'chatbotservice',
			'use_voice' => false,
			'transport_mode' => 'rest'
		]);

		$output = $display->getOutput();

		$this->assertSame(dirname(__DIR__, 2), $view->getLastPath());
		$this->assertSame('Display/ClassicChatbotDisplay.php', $view->getLastTemplate());
		$this->assertSame('/chatbot/service', $view->getAssigned('service_url'));
		$this->assertSame('chatbotservice', $view->getAssigned('service'));
		$this->assertFalse($view->getAssigned('use_voice'));
		$this->assertSame('rest', $view->getAssigned('transport_mode'));
		$this->assertTrue($view->getAssigned('use_markdown'));
		$this->assertTrue($view->getAssigned('use_threads'));
		$this->assertSame('FAKE_TEMPLATE_OUTPUT', $output);

		$resolve = $view->getAssigned('resolve');
		$this->assertIsCallable($resolve);
		$this->assertSame(
			'/resolved/plugin/ClientStack/assets/classicchatbot/chatbot/chatbot.js',
			$resolve('plugin/ClientStack/assets/classicchatbot/chatbot/chatbot.js')
		);
	}

	public function testGetHelpReturnsExpectedText(): void {
		$display = new ClassicChatbotDisplay(new FakeMvcView(), new FakeAssetResolver());

		$this->assertSame('Renders the classic Chatbot browser client.', $display->getHelp());
	}
}

require_once __DIR__ . '/FakeAssetResolver.php';
require_once __DIR__ . '/FakeMvcView.php';
