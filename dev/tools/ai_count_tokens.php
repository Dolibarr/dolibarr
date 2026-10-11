#!/usr/bin/env php
<?php
/*
 * Copyright (C) 2026 	Laurent Destailleur 	<eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * ai_count_tokens.php
 *
 * Usage:
 *   php ai_count_tokens.php toto/
 *
 * Estimate :
 *   ~1 token for 3.5 chars
 *
 * This is not exactly the tokenizer of Claude or Mistral, but it's a good approximation.
 */

if ($argc < 2) {
	echo "Usage: php count_tokens.php <repertoire>\n";
	exit(1);
}

$root = realpath($argv[1]);

if ($root === false || !is_dir($root)) {
	echo "Dir not found : {$argv[1]}\n";
	exit(1);
}

$excludedDirs = [
	'.agents',
	'.git',
	'.github/tmp',
	'.svn',
	'.idea',
	'.phan',
	'.settings',
	'.tx',
	'.vibe',
	'.vscode',
	'vendor',
	'documents',
	'node_modules',
	'cache',
	'dev',
	'doc',
	'tmp',
	'htdocs/custom',
	'htdocs/includes',
	'htdocs/install/doctemplates/websites',
	'htdocs/install/mysql/data/',
	'htdocs/install/mysql/migration',
	'htdocs/install/pgsql',
	'htdocs/public/includes',
	'htdocs/theme/common',
	'test/acceptance',
	'test/assets',
	'test/awbot',
	'test/hurl',
	'test/manual',
	'test/other',
	'test/selenium',
	'test/soapui',
	'test/sqlmap',
	'logs',
	'var/cache',
	'var/log',
];

$extensions = [
	'php',
	'phtml',
	'html',
	'htm',
	'js',
	'css',
	'sql',
	'md',
];

$totalChars = 0;
$totalTokens = 0;
$totalFiles = 0;

$files = [];

$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator(
		$root,
		FilesystemIterator::SKIP_DOTS
	)
);

foreach ($iterator as $file) {
	if (!$file->isFile()) {
		continue;
	}

	$path = $file->getPathname();

	// Check excluded dir
	$relativePath = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
	$relativePathUnix = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

	$excluded = false;

	foreach ($excludedDirs as $dir) {
		$dir = trim(str_replace('\\', '/', $dir), '/');
		if (
			$relativePathUnix === $dir ||
			strpos($relativePathUnix, $dir . '/') === 0
		) {
			$excluded = true;
			break;
		}
	}

	if ($excluded) {
		continue;
	}

	// Extension
	$extension = strtolower($file->getExtension());

	if (!in_array($extension, $extensions, true)) {
		continue;
	}

	// Size
	$content = @file_get_contents($path);

	if ($content === false) {
		echo "Could not read : {$relativePath}\n";
		continue;
	}

	$chars = mb_strlen($content, 'UTF-8');

	/*
	 * Estimate tokens.
	 *
	 * For code, 3 to 4 chars is a good solution.
	 */
	$tokens = (int) ceil($chars / 3.5);

	$totalChars += $chars;
	$totalTokens += $tokens;
	$totalFiles++;

	$files[] = [
		'path' => $relativePathUnix,
		'chars' => $chars,
		'tokens' => $tokens,
	];
}

// Sort by number of tokens
usort($files, function ($a, $b) {
	return $b['tokens'] <=> $a['tokens'];
});

// --------------------------------------------------
// Result
// --------------------------------------------------

/**
 * formatBytes
 *
 * @param int $bytes	Bytes
 * @return string
 */
function formatBytes(int $bytes): string
{
	if ($bytes < 1024) {
		return $bytes . ' B';
	}

	if ($bytes < 1024 * 1024) {
		return number_format($bytes / 1024, 2, ',', ' ') . ' KB';
	}

	if ($bytes < 1024 * 1024 * 1024) {
		return number_format($bytes / (1024 * 1024), 2, ',', ' ') . ' MB';
	}

	return number_format($bytes / (1024 * 1024 * 1024), 2, ',', ' ') . ' GB';
}

/**
 * formatNumber
 *
 * @param int $number		Number
 * @return string
 */
function formatNumber(int $number): string
{
	return number_format($number, 0, ',', ' ');
}

echo "\n";
echo "========================================\n";
echo "       TOKEN ESTIMATION\n";
echo "========================================\n\n";

echo "Project      : {$root}\n";
echo "Files        : " . formatNumber($totalFiles) . "\n";
echo "Chars        : " . formatNumber($totalChars) . "\n";
echo "Size         : " . formatBytes($totalChars) . "\n";
echo "Tokens       : " . formatNumber($totalTokens) . "\n";
echo "\n";

echo "Ratio used: 1 token / 3.5 chars\n";
echo "\n";

echo "----------------------------------------\n";
echo "TOP 20 FILES\n";
echo "----------------------------------------\n\n";

foreach (array_slice($files, 0, 20) as $file) {
	printf(
		"%10s tokens  %s\n",
		formatNumber($file['tokens']),
		$file['path']
	);
}

echo "\n";
echo "========================================\n";
echo "TOTAL : " . formatNumber($totalTokens) . " tokens\n";
echo "========================================\n";
