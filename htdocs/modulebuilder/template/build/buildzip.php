#!/usr/bin/env php -d memory_limit=256M
<?php
/**
 * buildzip.php
 *
 * Copyright (c) 2023-2025 Eric Seigne <eric.seigne@cap-rel.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/*
   The goal of that php CLI script is to make zip package of your module
   as an alternative to web "build zip" or "perl script makepack"
*/

// ============================================= configuration

/**
 * list of files & dirs of your module
 *
 * @var string[]
 */
$listOfModuleContent = [
	'admin',
	'ajax',
	'backport',
	'class',
	'css',
	'COPYING',
	'core',
	'img',
	'js',
	'langs',
	'lib',
	'sql',
	'tpl',
	'*.md',
	'*.json',
	'*.php',
	'modulebuilder.txt',
];

/**
 * if you want to exclude some files from your zip
 *
 * @var string[]
 */
$exclude_list = [
	'/^.git$/',
	'/.*js.map/',
	'/DEV.md/'
];

// ============================================= end of configuration

/**
 * auto detect module name and version from file name
 *
 * @return  (string|string)[] module name and module version
 */
function detectModule()
{
	$name  = $version = "";
	$tab = glob("core/modules/mod*.class.php");
	if (count($tab) == 0) {
		echo "[fail] Error on auto detect data : there is no mod*.class.php file into core/modules dir\n";
		exit(-1);
	}
	if (count($tab) == 1) {
		$file = $tab[0];
		$pattern = "/.*mod(?<mod>.*)\.class\.php/";
		if (preg_match_all($pattern, $file, $matches)) {
			$name = strtolower(reset($matches['mod']));
		}

		echo "extract data from $file\n";
		if (!file_exists($file) || $name == "") {
			echo "[fail] Error on auto detect data\n";
			exit(-2);
		}
	} else {
		echo "[fail] Error there is more than one mod*.class.php file into core/modules dir\n";
		exit(-3);
	}

	//extract version from file
	$contents = file_get_contents($file);
	$pattern = "/^.*this->version\s*=\s*'(?<version>.*)'\s*;.*\$/m";

	// search, and store all matching occurrences in $matches
	if (preg_match_all($pattern, $contents, $matches)) {
		$version = reset($matches['version']);
	}

	if (version_compare($version, '0.0.1', '>=') != 1) {
		echo "[fail] Error auto extract version fail\n";
		exit(-4);
	}

	echo "module name = $name, version = $version\n";
	return [(string) $name, (string) $version];
}

/**
 * delete recursively a directory
 *
 * @param   string  $dir  dir path to delete
 *
 * @return bool true on success or false on failure.
 */
function delTree($dir)
{
	$files = array_diff(scandir($dir), array('.', '..'));
	foreach ($files as $file) {
		(is_dir("$dir/$file")) ? delTree("$dir/$file") : secureUnlink("$dir/$file");
	}
	return rmdir($dir);
}


/**
 * do a secure delete file/dir with double check
 * (don't trust unlink return)
 *
 * @param   string  $path  full path to delete
 *
 * @return bool true on success ($path does not exists at the end of process), else exit
 */
function secureUnlink($path)
{
	if (file_exists($path)) {
		if (unlink($path)) {
			//then check if really deleted
			clearstatcache();
			if (file_exists($path)) {	// @phpstan-ignore-line
				echo "[fail] unlink of $path fail !\n";
				exit(-5);
			}
		} else {
			echo "[fail] unlink of $path fail !\n";
			exit(-6);
		}
	}
	return true;
}

/**
 * create a directory and check if dir exists
 *
 * @param   string  $path  path to make
 *
 * @return bool true on success ($path exists at the end of process), else exit
 */
function mkdirAndCheck($path)
{
	if (mkdir($path)) {
		clearstatcache();
		if (is_dir($path)) {
			return true;
		}
	}
	echo "[fail] Error on $path (mkdir)\n";
	exit(7);
}

/**
 * check if that filename is concerned by exclude filter
 *
 * @param   string  $filename  file name to check
 *
 * @return bool true if file is in excluded list
 */
function is_excluded($filename)
{
	global $exclude_list;
	$count = 0;
	$notused = preg_filter($exclude_list, '1', $filename, -1, $count);
	if ($count > 0) {
		echo " - exclude $filename\n";
		return true;
	}
	return false;
}

/**
 * recursive copy files & dirs
 *
 * @param   string  $src  source dir
 * @param   string  $dst  target dir
 *
 * @return bool true on success or false on failure.
 */
function rcopy($src, $dst)
{
	if (is_dir($src)) {
		// Make the destination directory if not exist
		mkdirAndCheck($dst);
		// open the source directory
		$dir = opendir($src);

		// Loop through the files in source directory
		while ($file = readdir($dir)) {
			if (($file != '.') && ($file != '..')) {
				if (is_dir($src . '/' . $file)) {
					// Recursively calling custom copy function
					// for sub directory
					if (!rcopy($src . '/' . $file, $dst . '/' . $file)) {
						return false;
					}
				} else {
					if (!is_excluded($file)) {
						if (!copy($src . '/' . $file, $dst . '/' . $file)) {
							return false;
						}
					}
				}
			}
		}
		closedir($dir);
	} elseif (is_file($src)) {
		if (!is_excluded($src)) {
			if (!copy($src, $dst)) {
				return false;
			}
		}
	}
	return true;
}

/**
 * build a zip file with only php code and no external depends
 * on "zip" exec for example
 *
 * @param   string  	$folder  folder to use as zip root
 * @param   ZipArchive  $zip     zip object (ZipArchive)
 * @param   string  	$root    relative root path into the zip
 *
 * @return bool true on success or false on failure.
 */
function zipDir($folder, &$zip, $root = "")
{
	foreach (new \DirectoryIterator($folder) as $f) {
		if ($f->isDot()) {
			continue;
		} //skip . ..
		$src = $folder . '/' . $f;
		$dst = substr($f->getPathname(), strlen($root));
		if ($f->isDir()) {
			if ($zip->addEmptyDir($dst)) {
				if (zipDir($src, $zip, $root)) {
					continue;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
		if ($f->isFile()) {
			if (! $zip->addFile($src, $dst)) {
				return false;
			}
		}
	}
	return true;
}

/**
 * main part of script
 */

list($mod, $version) = detectModule();
$outzip = sys_get_temp_dir() . "/module_" . $mod . "-" . $version . ".zip";
if (file_exists($outzip)) {
	secureUnlink($outzip);
}

//copy all sources into system temp directory
$tmpdir = tempnam(sys_get_temp_dir(), $mod . "-module");
secureUnlink($tmpdir);
mkdirAndCheck($tmpdir);
$dst = $tmpdir . "/" . $mod;
mkdirAndCheck($dst);

foreach ($listOfModuleContent as $moduleContent) {
	foreach (glob($moduleContent) as $entry) {
		if (!rcopy($entry, $dst . '/' . $entry)) {
			echo "[fail]  Error on copy " . $entry . " to " . $dst . "/" . $entry . "\n";
			echo "Please take time to analyze the problem and fix the bug\n";
			exit(-8);
		}
	}
}

$z = new ZipArchive();
$z->open($outzip, ZIPARCHIVE::CREATE);
zipDir($tmpdir, $z, $tmpdir . '/');
$z->close();
delTree($tmpdir);
if (file_exists($outzip)) {
	echo "[success] module archive is ready : $outzip ...\n";
} else {
	echo "[fail] build zip error\n";
	exit(-9);
}
