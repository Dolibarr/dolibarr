#!/usr/bin/env php
<?php
/* Copyright (C) 2026 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * \file       dev/translation/find_notranslate.php
 * \ingroup    dev
 * \brief      Script to remove the translation entries that contain the string "notranslate",
 *             which means a translator has translated the content of a notranslate tag and
 *             broken the entry. Removing it is the fix: a key missing from a translation file
 *             falls back to its en_US value, while a broken entry stays broken and makes the
 *             test LangTest::testLang fail. The entry must still be fixed into Transifex, so
 *             the link to do it is shown for each removed entry.
 *             With --report, nothing is removed and the entries are shown one by one instead.
 *
 * Usage: php dev/translation/fix_notranslate.php [--report] [all|lang_code] [all|file.lang]
 * Examples:
 *   php dev/translation/fix_notranslate.php all
 *   php dev/translation/fix_notranslate.php fr_FR
 *   php dev/translation/fix_notranslate.php fr_FR website.lang
 *   php dev/translation/fix_notranslate.php --report fr_FR
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit;
}


/**
 * Print usage
 *
 * @param	string	$script_file	Name of the script
 * @return	void
 */
function printUsage($script_file)
{
	echo "Remove the translation entries containing the string 'notranslate', which are entries a\n";
	echo "translator broke by translating the content of a notranslate tag. A key missing from a\n";
	echo "translation file falls back to its en_US value, so removing the entry restores a readable\n";
	echo "string, while keeping it makes the test LangTest::testLang fail. A clickable Transifex link\n";
	echo "is shown for each removed entry, so the translation can also be fixed at the source.\n";
	echo "\n";
	echo "With --report, nothing is removed: the entries are shown one by one instead.\n";
	echo "\n";
	echo "Usage: php dev/translation/".$script_file." [--report] [all|lang_code] [all|file.lang]\n";
	echo "Examples:\n";
	echo "  php dev/translation/".$script_file." all\n";
	echo "  php dev/translation/".$script_file." fr_FR\n";
	echo "  php dev/translation/".$script_file." fr_FR website.lang\n";
	echo "  php dev/translation/".$script_file." --report fr_FR\n";
}


/**
 * Parse the .tx/config file and return the map lang file base name => Transifex resource name
 *
 * @param	string	$txconfigfile	Path to the .tx/config file
 * @return	array					Array of lang file base name (example: main) => Transifex resource name
 */
function loadTransifexResourceMap($txconfigfile)
{
	$resourcemap = array();

	if (!is_file($txconfigfile)) {
		return $resourcemap;
	}

	$content = file_get_contents($txconfigfile);
	if ($content === false) {
		return $resourcemap;
	}

	// Example of section to parse:
	// [o:dolibarr-association:p:dolibarr:r:admin]
	// file_filter = htdocs/langs/<lang>/admin.lang
	$matches = array();
	preg_match_all('/\[o:dolibarr-association:p:dolibarr:r:([^\]]+)\]\s*\nfile_filter\s*=\s*htdocs\/langs\/<lang>\/([^\s]+)\.lang/', $content, $matches, PREG_SET_ORDER);
	foreach ($matches as $match) {
		$resourcemap[$match[2]] = $match[1];
	}

	return $resourcemap;
}


/**
 * Load a lang file and return an array key => value of its translation entries
 *
 * @param	string	$langfilepath	Full path of the .lang file
 * @return	array					Array of translation key => translation value
 */
function loadLangFileKeyValues($langfilepath)
{
	$arrayofkeyvalues = array();

	if (!is_file($langfilepath)) {
		return $arrayofkeyvalues;
	}

	$lines = file($langfilepath, FILE_IGNORE_NEW_LINES);
	if ($lines === false) {
		return $arrayofkeyvalues;
	}

	foreach ($lines as $line) {
		$cleanline = ltrim($line);
		if ($cleanline == '' || substr($cleanline, 0, 1) == '#') {
			continue;	// This is a comment or an empty line
		}
		$pos = strpos($line, '=');
		if ($pos === false) {
			continue;	// This is not a translation entry
		}
		$arrayofkeyvalues[substr($line, 0, $pos)] = rtrim(substr($line, $pos + 1));
	}

	return $arrayofkeyvalues;
}


/**
 * Remove some lines from a file, by their line number
 *
 * @param	string		$filepath		Full path of the file to rewrite
 * @param	int[]		$linenumbers	Line numbers to remove, counted from 1
 * @return	int							Number of lines removed, -1 if the file could not be rewritten
 */
function removeLinesFromFile($filepath, $linenumbers)
{
	$lines = file($filepath);
	if ($lines === false) {
		return -1;
	}

	$removed = 0;
	foreach ($linenumbers as $linenumber) {
		if (isset($lines[$linenumber - 1])) {
			unset($lines[$linenumber - 1]);
			$removed++;
		}
	}

	if ($removed == 0) {
		return 0;
	}

	if (file_put_contents($filepath, implode('', $lines)) === false) {
		return -1;
	}

	return $removed;
}


/**
 * Print a clickable URL (using the OSC 8 terminal hyperlink sequence).
 * If the terminal does not support OSC 8, the plain URL remains visible and can be opened manually.
 *
 * @param	string	$url	URL to print
 * @return	void
 */
function printClickableUrl($url)
{
	global $outputistty;

	if ($outputistty) {
		echo "\033]8;;".$url."\007".$url."\033]8;;\007";
	} else {
		echo $url;
	}
}


// The string to search into translation entries
$searchstring = 'notranslate';

// Root of Transifex translation pages
$transifexurlroot = 'https://app.transifex.com/dolibarr-association/dolibarr/translate/#';

// Map of Dolibarr lang codes that differ from Transifex lang codes (see lang_map into .tx/config)
$langmaptx = array('uz_UZ' => 'uz', 'sw_SW' => 'sw', 'sr_RS' => 'sr@latin');

// Detect if output is a real terminal, so escape sequences for clickable links can be used or not
$outputistty = true;
if (function_exists('posix_isatty') && !posix_isatty(STDOUT)) {
	$outputistty = false;
}

// Read the options, then the 2 positional parameters left
$reportonly = false;
$parameters = array();
foreach (array_slice($argv, 1) as $argument) {
	$argument = trim($argument);
	if ($argument == '') {
		continue;
	}
	if ($argument == '--report' || $argument == '-n') {
		$reportonly = true;
	} elseif (in_array($argument, array('-h', '--help', 'help'))) {
		printUsage($script_file);
		exit(0);
	} else {
		$parameters[] = $argument;
	}
}

$langcode = isset($parameters[0]) ? $parameters[0] : 'all';
$filename = isset($parameters[1]) ? $parameters[1] : 'all';

$langsdir = $path.'../../htdocs/langs/';



// Build the map lang file base name => Transifex resource name

$resourcemap = loadTransifexResourceMap($path.'../../.tx/config');



// Build list of language directories to scan

$langs = array();
if ($langcode == 'all') {
	$content = scandir($langsdir);
	if ($content === false) {
		echo "Error: Can't scan directory ".$langsdir."\n";
		exit(1);
	}
	foreach ($content as $dir) {
		if (is_dir($langsdir.$dir) && $dir != '.' && $dir != '..') {
			$langs[] = $dir;
		}
	}
	sort($langs);
} else {
	if (!is_dir($langsdir.$langcode)) {
		echo "Error: Directory for lang '".$langcode."' not found into ".$langsdir."\n";
		printUsage($script_file);
		exit(1);
	}
	$langs = array($langcode);
}



// Scan lang files to find all entries containing the search string

$listofmatches = array();

foreach ($langs as $lang) {
	$langdir = $langsdir.$lang.'/';

	$files = array();
	if ($filename == 'all') {
		$content = scandir($langdir);
		if ($content === false) {
			continue;
		}
		foreach ($content as $file) {
			if (preg_match('/\.lang$/', $file)) {
				$files[] = $file;
			}
		}
	} else {
		if (!is_file($langdir.$filename) && !preg_match('/\.lang$/', $filename)) {
			$filename .= '.lang';
		}
		if (!is_file($langdir.$filename)) {
			echo "Warning: File htdocs/langs/".$lang."/".$filename." not found. Skipped.\n";
			continue;
		}
		$files = array($filename);
	}

	foreach ($files as $file) {
		$handle = fopen($langdir.$file, 'r');
		if (!$handle) {
			continue;
		}

		$linenum = 0;
		while (($line = fgets($handle)) !== false) {
			$linenum++;

			if (stripos($line, $searchstring) === false) {
				continue;
			}
			$cleanline = ltrim($line);
			if ($cleanline == '' || substr($cleanline, 0, 1) == '#') {
				continue;	// This is a comment or an empty line
			}
			$pos = strpos($line, '=');
			if ($pos === false) {
				continue;	// This is not a translation entry
			}

			$listofmatches[] = array(
				'lang' => $lang,
				'file' => $file,
				'line' => $linenum,
				'key' => substr($line, 0, $pos),
				'value' => rtrim(substr($line, $pos + 1))
			);
		}
		fclose($handle);
	}
}



// Show each entry, with a clickable Transifex link, and wait for Enter to see the next one

$num = count($listofmatches);

echo "Found ".$num." ".($num > 1 ? 'entries' : 'entry')." containing '".$searchstring."'";
echo ($langcode != 'all' ? " for lang '".$langcode."'" : '')." into htdocs/langs/*/*.lang\n";

if ($num == 0) {
	echo "Nothing to review. Perfect!\n";
	exit(0);
}



// Remove the entries, unless only a report was asked for

if (!$reportonly) {
	// Group the line numbers by file, so each file is rewritten once, and remove the highest
	// line numbers first so removing one does not move the next ones
	$linestoremove = array();
	foreach ($listofmatches as $match) {
		$linestoremove[$match['lang'].'/'.$match['file']][] = $match['line'];
	}

	$removed = 0;
	$errors = 0;
	foreach ($linestoremove as $relativepath => $linenumbers) {
		rsort($linenumbers);
		$result = removeLinesFromFile($langsdir.$relativepath, $linenumbers);
		if ($result < 0) {
			echo "Error: Can't rewrite htdocs/langs/".$relativepath."\n";
			$errors++;
		} else {
			$removed += $result;
		}
	}

	foreach ($listofmatches as $match) {
		echo "Removed htdocs/langs/".$match['lang']."/".$match['file']." line ".$match['line'].": ".$match['key']."\n";

		$filebase = preg_replace('/\.lang$/', '', $match['file']);
		$resource = isset($resourcemap[$filebase]) ? $resourcemap[$filebase] : '';
		$langtx = isset($langmaptx[$match['lang']]) ? $langmaptx[$match['lang']] : $match['lang'];
		if ($resource == '') {
			echo "  No Transifex resource found for file ".$match['file'].", fix it directly into the .lang file.\n";
		} else {
			echo "  Fix it into Transifex: ";
			printClickableUrl($transifexurlroot.$langtx.'/'.$resource.'?q=key%3A'.rawurlencode($match['key']));
			echo "\n";
		}
	}

	echo "\n".$removed." ".($removed > 1 ? 'entries' : 'entry')." removed.";
	echo " Until they are fixed into Transifex, these keys fall back to their en_US value.\n";

	exit($errors > 0 ? 1 : 0);
}

$enUScache = array();	// Cache of en_US values, one entry per lang file already loaded
$i = 0;

foreach ($listofmatches as $match) {
	$i++;

	$filebase = preg_replace('/\.lang$/', '', $match['file']);
	$resource = isset($resourcemap[$filebase]) ? $resourcemap[$filebase] : '';
	$langtx = isset($langmaptx[$match['lang']]) ? $langmaptx[$match['lang']] : $match['lang'];

	echo "\n".str_repeat('=', 79)."\n";
	echo "Entry ".$i."/".$num."\n";
	echo "File:    htdocs/langs/".$match['lang']."/".$match['file']." (line ".$match['line'].")\n";
	echo "Key:     ".$match['key']."\n";
	echo "Current: ".$match['value']."\n";

	// Show the English reference value, so it can be compared with the translation to fix
	if (!isset($enUScache[$match['file']])) {
		$enUScache[$match['file']] = loadLangFileKeyValues($langsdir.'en_US/'.$match['file']);
	}
	if (isset($enUScache[$match['file']][$match['key']])) {
		echo "English: ".$enUScache[$match['file']][$match['key']]."\n";
	}

	if ($resource == '') {
		echo "Transifex: No Transifex resource found for file ".$match['file'].", fix it directly into the .lang file.\n";
	} else {
		$url = $transifexurlroot.$langtx.'/'.$resource.'?q=key%3A'.rawurlencode($match['key']);
		echo "Transifex: ";
		printClickableUrl($url);
		echo "\n";
	}

	if ($i < $num) {
		echo "Press Enter to see the next entry (or type q + Enter to quit)... ";
		$input = fgets(STDIN);
		if ($input === false) {
			echo "\nNo more input, stop here.\n";
			break;
		}
		if (strtolower(trim($input)) == 'q') {
			echo "\nStopped by user.\n";
			break;
		}
		echo "\n";
	}
}



// Reminder for pulling fixed translations

if ($i == $num) {
	$resources = array();
	foreach ($listofmatches as $match) {
		$filebase = preg_replace('/\.lang$/', '', $match['file']);
		if (!isset($resourcemap[$filebase])) {
			continue;
		}
		$resources[$match['lang'].'/'.$resourcemap[$filebase]] = 1;
	}

	echo "\nAll entries were shown.\n";
	if (count($resources) > 0) {
		echo "Once translations are fixed into Transifex, you can refresh local files with:\n";
		foreach (array_keys($resources) as $resourcekey) {
			list($resourcelang, $resourcename) = explode('/', $resourcekey, 2);
			echo "  ./dev/translation/txpull.sh ".$resourcelang." -r dolibarr.".$resourcename."\n";
		}
	} else {
		echo "No file matched is on Transifex, translations must be fixed directly into the .lang files.\n";
	}
}
