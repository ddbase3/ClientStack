#!/usr/bin/env php
<?php declare(strict_types=1);

$baseDir = __DIR__;
$devDir = "$baseDir/dev";
$assetsDir = "$baseDir/assets";
$configPath = "$baseDir/local/libs.json";
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

foreach ($libs as $lib) {
	$name = $lib['name'];
	$targetBase = $lib['target'];
	$repoDir = $lib['repoDir'] ?? $name;

	if (!empty($lib['repo'])) {
		// Git-Modus
		$repoPath = "$devDir/$repoDir";
		$subdir = $lib['subdir'] ?? '';
		$sourceDir = rtrim("$repoPath/$subdir", '/');
		$targetDir = "$assetsDir/$targetBase";

		if (!is_dir($sourceDir)) {
			echo "Quellverzeichnis nicht gefunden: $sourceDir\n";
			continue;
		}

		echo "--> Kopiere $name aus $sourceDir\n";
		@mkdir($targetDir, 0777, true);
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDir)) as $file) {
			if ($file->isDir()) continue;
			$relPath = substr($file->getPathname(), strlen($sourceDir) + 1);
			$destPath = "$targetDir/$relPath";
			@mkdir(dirname($destPath), 0777, true);
			copy($file->getPathname(), $destPath);
		}

		// Commit holen, falls möglich
		$commitFile = "$repoPath/.git/HEAD";
		if (file_exists($commitFile)) {
			chdir($repoPath);
			$commit = trim(`git rev-parse HEAD`);
			$timestamp = trim(`git show -s --format=%ci $commit`);
			$versions[$name] = [
				'type' => 'repo',
				'commit' => $commit,
				'timestamp' => $timestamp
			];
		}

	} elseif (!empty($lib['download'])) {
		// Download-Modus
		$targetDir = "$assetsDir/$targetBase";
		@mkdir($targetDir, 0777, true);

		foreach ($lib['download'] as $dl) {
			$url = $dl['url'];
			$target = $dl['target'];
			$destPath = "$targetDir/$target";
			$destDir = dirname($destPath);
			if (!is_dir($destDir)) {
				mkdir($destDir, 0777, true);
			}

			echo "--> Lade $url -> $destPath\n";
			copy($url, $destPath);
		}

		$versions[$name] = [
			'type' => 'download',
			'timestamp' => date('c')
		];
	}
}

// Versionsdatei schreiben
$versionsPath = "$assetsDir/versions.json";
file_put_contents($versionsPath, json_encode($versions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "--> Versionsdatei geschrieben: $versionsPath\n";

echo "✔ Build abgeschlossen\n";

