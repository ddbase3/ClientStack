#!/usr/bin/env php
<?php declare(strict_types=1);

$root = dirname(__DIR__, 3);
$source = $root . '/dev/GlobalChatbotLauncher/src';
$target = $root . '/assets/globalchatbotlauncher';
foreach (['index.js', 'GlobalChatbotLauncher.js', 'styles/globalchatbotlauncher.css'] as $relativePath) {
	$left = $source . '/' . $relativePath;
	$right = $target . '/' . $relativePath;
	if (!is_file($left) || !is_file($right) || hash_file('sha256', $left) !== hash_file('sha256', $right)) {
		fwrite(STDERR, 'Deployment mismatch: ' . $relativePath . PHP_EOL);
		exit(1);
	}
}
echo 'GlobalChatbotLauncher development and deployed assets match.' . PHP_EOL;
