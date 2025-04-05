#!/usr/bin/env php
<?php
/* Copyright (C) 2025		Marc de Lima Lucio		<marc-dll@user.noreply.github.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    dev/tools/dolibarr-generate-classmap.php
 * \brief   Script to recursively scan htdocs folder looking for class declarations in order to generate a complete classmap
 */


if (php_sapi_name() !== 'cli') {
	http_response_code(403);
	echo 'This script should never be called from a browser';
	exit(1);
}


$htdocs = realpath(__DIR__ . '/../../htdocs');

$classmap = array();

classmapScanDir($htdocs);

ksort($classmap, SORT_STRING | SORT_FLAG_CASE);


$classmapFile = <<<'CLASSMAP'
<?php
/* Copyright (C) 2025		Dolibarr project		<contact@dolibarr.org>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/classmap.inc.php
 * \brief   Dolibarr classmap
 */

CLASSMAP;

$classmapFile .= "\n/* This file was automatically generated on " . date('Y-m-d H:i:s') . " */\n\n";
$classmapFile .= 'return '.var_export($classmap, true) . ";\n";

file_put_contents($htdocs.'/classmap.inc.php', $classmapFile);



function classmapScanDir(string $dir): void
{
	global $classmap, $htdocs;

	$dirContent = scandir($dir);

	foreach ($dirContent as $child) {
		if ($child === '.' || $child === '..') {
			continue;
		}

		$path = $dir.'/'.$child;

		// Exclude /htdocs/includes : most of its contents have their own autoloading scripts
		if ($path === $htdocs.'/includes') {
			continue;
		}

		// Exclude /htdocs/core/menus : each menu manager defines its own `MenuManager` classes
		if ($path === $htdocs.'/core/menus') {
			continue;
		}

		if (! is_dir($path)) {
			$phpExtension = substr($child, -4, 4);

			if ($phpExtension !== '.php' && $phpExtension !== '.PHP') {
				continue;
			}

			classmapScanPhpFile($path);

			continue;
		}

		if (is_link($path)) {
			// Prevent possible infinite loops
			continue;
		}

		classmapScanDir($path);
	}
}

function classmapScanPhpFile(string $path): void
{
	global $classmap, $htdocs;

	$relativePath = str_replace($htdocs.'/', '', $path);

	$phpCode = file_get_contents($path);

	$tokens = token_get_all($phpCode);

	$currentNamespace = '';

	// look for class, namespace, interface, trait or enum tokens to get name from the following token
	foreach ($tokens as $i => $token) {
		// Token is just text or punctuation : it's not our target
		if (! is_array($token)) {
			continue;
		}

		$nextToken = classmapGetNextNonWhitespaceToken($tokens, $i);

		if (null === $nextToken) {
			continue;
		}

		if ($token[0] === T_NAMESPACE) {
			if ($nextToken === '{' || $nextToken === ';') { // namespace cleared
				$currentNamespace = '';
			} elseif (is_array($nextToken)) {
				$currentNamespace = $nextToken[1];
			} else {
				echo "Error parsing namespace\n";
				// Should never happen
				exit(1);
			}

			continue;
		}

		// Token is neither not any of the `class`, `trait`, `interface` or `enum` keyword, it's not what we are looking for
		if ($token[0] !== T_CLASS && $token[0] !== T_TRAIT && $token[0] !== T_INTERFACE && (! defined('T_ENUM') || $token[0] !== T_ENUM)) {
			continue;
		}

		if ($token[0] === T_CLASS) {
			$previousToken = $i > 0 ? $tokens[$i - 1] : '';

			if (is_array($previousToken) && $previousToken[0] === T_DOUBLE_COLON) {
				// `class` keyword in use in an expression like `Facture::class`
				continue;
			}
		}

		if (! is_array($nextToken) || $nextToken[0] !== T_STRING) {
			// anonymous class/interface/trait/enum
			continue;
		}

		$fullyQualifiedName = $currentNamespace.(empty($currentNamespace) ? '' : '\\').$nextToken[1];


		if (empty($classmap[$fullyQualifiedName])) {
			echo $token[1].' '.$fullyQualifiedName.' found in file '.$relativePath.' at line '.$nextToken[2]."\n";
			$classmap[$fullyQualifiedName] = $relativePath;
		} else {
			echo 'WARNING : '. $token[1].' '.$fullyQualifiedName.' found in file '.$relativePath.' at line '.$nextToken[2].' but was already found in file '.$classmap[$fullyQualifiedName]."\n";
		}
	}
}

function classmapGetNextNonWhitespaceToken(array $tokens, int $i)
{
	while (1) {
		$i++;

		if (!array_key_exists($i, $tokens)) {
			return null;
		}

		$currentToken = $tokens[$i];

		if (! is_array($currentToken) || $currentToken[0] !== T_WHITESPACE) {
			return $currentToken;
		}
	}

	return null;
}
