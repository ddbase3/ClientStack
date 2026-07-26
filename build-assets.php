#!/usr/bin/env php
<?php declare(strict_types=1);

function removeDirectory(string $directory): void {
	if (!is_dir($directory)) return;

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ($iterator as $file) {
		if ($file->isDir() && !$file->isLink()) {
			rmdir($file->getPathname());
			continue;
		}

		unlink($file->getPathname());
	}

	rmdir($directory);
}

$baseDir = __DIR__;
$devDir = "$baseDir/dev";
$assetsDir = "$baseDir/assets";
$configPath = "$baseDir/local/libs.json";
$filter = $argv[1] ?? '';
$versions = [];

if (!file_exists($configPath)) {
	echo "Konfigurationsdatei nicht gefunden: $configPath\n";
	exit(1);
}

$libs = json_decode(file_get_contents($configPath), true);
if (!is_array($libs)) {
	echo "Fehlerhafte JSON-Datei: $configPath\n";
	exit(1);
}

$versionsPath = "$assetsDir/versions.json";
if (file_exists($versionsPath)) {
	$storedVersions = json_decode(file_get_contents($versionsPath), true);
	if (is_array($storedVersions)) $versions = $storedVersions;
}

foreach ($libs as $lib) {
	$name = $lib['name'];
	if ($filter !== '' && $name !== $filter) continue;

	$targetBase = $lib['target'];
	$repoDir = $lib['dir'] ?? $lib['repoDir'] ?? $name;

	if (!empty($lib['repo'])) {
		$repoPath = "$devDir/$repoDir";
		$subdir = $lib['subdir'] ?? '';
		$sourceDir = rtrim("$repoPath/$subdir", '/');
		$targetDir = "$assetsDir/$targetBase";

		if (!is_dir($sourceDir)) {
			echo "Quellverzeichnis nicht gefunden: $sourceDir\n";
			continue;
		}

		echo "--> Kopiere $name aus $sourceDir\n";
		removeDirectory($targetDir);
		mkdir($targetDir, 0777, true);

		$directoryIterator = new RecursiveDirectoryIterator(
			$sourceDir,
			FilesystemIterator::SKIP_DOTS
		);
		$filterIterator = new RecursiveCallbackFilterIterator(
			$directoryIterator,
			fn(SplFileInfo $file): bool => $file->getFilename() !== '.git'
		);
		$iterator = new RecursiveIteratorIterator($filterIterator);

		foreach ($iterator as $file) {
			if ($file->isDir()) continue;

			$relPath = substr($file->getPathname(), strlen($sourceDir) + 1);
			$destPath = "$targetDir/$relPath";
			$destDir = dirname($destPath);
			if (!is_dir($destDir)) mkdir($destDir, 0777, true);
			copy($file->getPathname(), $destPath);
		}

		chdir($repoPath);
		$commit = trim((string) shell_exec('git rev-parse HEAD'));
		$timestamp = trim((string) shell_exec("git show -s --format=%ci $commit"));
		$versions[$name] = [
			'type' => 'repo',
			'tag' => $lib['tag'] ?? null,
			'branch' => $lib['branch'] ?? null,
			'commit' => $commit,
			'timestamp' => $timestamp
		];
	} elseif (!empty($lib['download'])) {
		$targetDir = "$assetsDir/$targetBase";
		mkdir($targetDir, 0777, true);

		foreach ($lib['download'] as $dl) {
			$url = $dl['url'];
			$target = $dl['target'];
			$destPath = "$targetDir/$target";
			$destDir = dirname($destPath);
			if (!is_dir($destDir)) mkdir($destDir, 0777, true);

			echo "--> Lade $url -> $destPath\n";
			copy($url, $destPath);
		}

		$versions[$name] = [
			'type' => 'download',
			'timestamp' => date('c')
		];
	}
}

file_put_contents($versionsPath, json_encode($versions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "--> Versionsdatei geschrieben: $versionsPath\n";

echo "✔ Build abgeschlossen\n";
