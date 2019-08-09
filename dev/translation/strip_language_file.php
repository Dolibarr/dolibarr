#!/usr/bin/env php
<?php
/* Copyright (C) 2014 by FromDual GmbH, licensed under GPL v2
 * Copyright (C) 2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * -----
 *
 * Compares a secondary language translation file with its primary
 * language file and strips redundant translations.
 *
 * Todo: Check if it works with multi byte (mb_*) character sets!
 *
 * Usage:
 * cd htdocs/langs
 * ./dev/translation/strip_language_file.php <primary_lang_dir> <secondary_lang_dir> [file.lang|all]
 *
 * To rename all .delta files, you can do
 * for fic in `ls *.delta`; do f=`echo $fic | sed -e 's/\.delta//'`; echo $f; mv $f.delta $f; done
 *
 * Rules:
 * secondary string == primary string -> strip
 * secondary string redundant -> strip and warning
 * secondary string not in primary -> strip and warning
 * secondary string has no value -> strip and warning
 * secondary string != primary string -> secondary.lang.delta
 */

/**
 *      \file       dev/translation/strip_language_file.php
 *		\ingroup    dev
 * 		\brief      This script clean sub-languages from duplicate keys-values
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit;
}

$rc = 0;

// Get and check arguments

$lPrimary = isset($argv[1])?$argv[1]:'';
$lSecondary = isset($argv[2])?$argv[2]:'';
$lEnglish = 'en_US';
$filesToProcess = isset($argv[3])?$argv[3]:'';

if (empty($lPrimary) || empty($lSecondary) || empty($filesToProcess))
{
	$rc = 1;
	$msg = '***** Script to clean language files *****'."\n";
	$msg.= 'Usage: ./dev/translation/strip_language_file.php xx_XX xx_YY [file.lang|all]'."\n";
	print $msg . "(rc=$rc).\n";
	exit($rc);
}

$aPrimary = array();
$aSecondary = array();
$aEnglish = array();

// Define array $filesToProcess
if ($filesToProcess == 'all')
{
	$dir = new DirectoryIterator('htdocs/langs/'.$lPrimary);
	while($dir->valid()) {
		if(!$dir->isDot() && $dir->isFile() && ! preg_match('/^\./', $dir->getFilename())) {
			$files[] =  $dir->getFilename();
		}
		$dir->next();
	}
	$filesToProcess=$files;
}
else $filesToProcess=explode(',', $filesToProcess);

// Arguments should be OK here.


// Loop on each file
foreach($filesToProcess as $fileToProcess)
{
	$lPrimaryFile = 'htdocs/langs/'.$lPrimary.'/'.$fileToProcess;
	$lSecondaryFile = 'htdocs/langs/'.$lSecondary.'/'.$fileToProcess;
	$lEnglishFile = 'htdocs/langs/'.$lEnglish.'/'.$fileToProcess;
	$output = $lSecondaryFile . '.delta';

	print "---- Process language file ".$lSecondaryFile."\n";

	if ( ! is_readable($lPrimaryFile) ) {
		$rc = 2;
		$msg = "Cannot read primary language file $lPrimaryFile.";
		print $msg . " (rc=$rc).\n";
		exit($rc);
	}

	if ( ! is_readable($lSecondaryFile) ) {
		$rc = 3;
		$msg = "Cannot read secondary language file $lSecondaryFile. We discard this file.";
		print $msg . "\n";
		continue;
	}

	if ( ! is_readable($lEnglishFile) ) {
		$rc = 3;
		$msg = "Cannot read english language file $lEnglishFile. We discard this file.";
		print $msg . "\n";
		continue;
	}

	// Start reading and parsing Secondary

	if ( $handle = fopen($lSecondaryFile, 'r') )
	{
		print "Read Secondary File $lSecondaryFile:\n";
		$cnt = 0;
		while (($line = fgets($handle)) !== false)
		{
			$cnt++;

			// strip comments
			if ( preg_match("/^\w*#/", $line) ) {
				continue;
			}
			// strip empty lines
			if ( preg_match("/^\w*$/", $line) ) {
				continue;
			}

			$a = mb_split('=', trim($line), 2);
			if ( count($a) != 2 ) {
				print "ERROR in file $lSecondaryFile, line $cnt: " . trim($line) . "\n";
				continue;
			}

			list($key, $value) = $a;

			// key is redundant
			if ( array_key_exists($key, $aSecondary) ) {
				print "Key $key is redundant in file $lSecondaryFile (line: $cnt).\n";
				continue;
			}

			// String has no value
			if ( $value == '' ) {
				print "Key $key has no value in file $lSecondaryFile (line: $cnt).\n";
				continue;
			}

			$aSecondary[$key] = trim($value);
		}
		if ( ! feof($handle) )
		{
			$rc = 5;
			$msg = "Unexpected fgets() fail";
			print $msg . " (rc=$rc).\n";
			exit($rc);
		}
		fclose($handle);
	}
	else {
		$rc = 6;
		$msg = "Cannot open file $lSecondaryFile";
		print $msg . " (rc=$rc).\n";
		exit($rc);
	}


	// Start reading and parsing English

	if ( $handle = fopen($lEnglishFile, 'r') )
	{
		print "Read English File $lEnglishFile:\n";
		$cnt = 0;
		while (($line = fgets($handle)) !== false)
		{
			$cnt++;

			// strip comments
			if ( preg_match("/^\w*#/", $line) ) {
				continue;
			}
			// strip empty lines
			if ( preg_match("/^\w*$/", $line) ) {
				continue;
			}

			$a = mb_split('=', trim($line), 2);
			if ( count($a) != 2 ) {
				print "ERROR in file $lEnglishFile, line $cnt: " . trim($line) . "\n";
				continue;
			}

			list($key, $value) = $a;

			// key is redundant
			if ( array_key_exists($key, $aEnglish) ) {
				print "Key $key is redundant in file $lEnglishFile (line: $cnt).\n";
				continue;
			}

			// String has no value
			if ( $value == '' ) {
				print "Key $key has no value in file $lEnglishFile (line: $cnt).\n";
				continue;
			}

			$aEnglish[$key] = trim($value);
		}
		if ( ! feof($handle) )
		{
			$rc = 5;
			$msg = "Unexpected fgets() fail";
			print $msg . " (rc=$rc).\n";
			exit($rc);
		}
		fclose($handle);
	}
	else {
		$rc = 6;
		$msg = "Cannot open file $lEnglishFile";
		print $msg . " (rc=$rc).\n";
		exit($rc);
	}



	// Start reading and parsing Primary. See rules in header!

	$arrayofkeytoalwayskeep=array('DIRECTION','FONTFORPDF','FONTSIZEFORPDF','SeparatorDecimal','SeparatorThousand');


	if ( $handle = fopen($lPrimaryFile, 'r') )
	{
		if ( ! $oh = fopen($output, 'w') )
		{
			print "ERROR in writing to file $output\n";
			exit;
		}

		print "Read Primary File $lPrimaryFile and write ".$output.":\n";

		fwrite($oh, "# Dolibarr language file - Source file is en_US - ".(preg_replace('/\.lang$/', '', $fileToProcess))."\n");

		$cnt = 0;
		while (($line = fgets($handle)) !== false)
		{
			$cnt++;

			// strip comments
			if ( preg_match("/^\w*#/", $line) ) {
				continue;
			}
			// strip empty lines
			if ( preg_match("/^\w*$/", $line) ) {
				continue;
			}

			$a = mb_split('=', trim($line), 2);
			if ( count($a) != 2 ) {
				print "ERROR in file $lPrimaryFile, line $cnt: " . trim($line) . "\n";
				continue;
			}

			list($key, $value) = $a;

			// key is redundant
			if ( array_key_exists($key, $aPrimary) ) {
				print "Key $key is redundant in file $lPrimaryFile (line: $cnt) - Already found into ".$fileFirstFound[$key]." (line: ".$lineFirstFound[$key].").\n";
				continue;
			}
			else
			{
				$fileFirstFound[$key] = $fileToProcess;
				$lineFirstFound[$key] = $cnt;
			}

			// String has no value
			if ( $value == '' ) {
				print "Key $key has no value in file $lPrimaryFile (line: $cnt).\n";
				continue;
			}

			$aPrimary[$key] = trim($value);
			$fileFirstFound[$key] = $fileToProcess;
			$lineFirstFound[$key] = $cnt;

			// ----- Process output now -----

			//print "Found primary key = ".$key."\n";

			// Key not in other file
			if (in_array($key, $arrayofkeytoalwayskeep) || preg_match('/^FormatDate/', $key) || preg_match('/^FormatHour/', $key))
			{
				//print "Key $key is a key we always want to see into secondary file (line: $cnt).\n";
			}
			elseif ( ! array_key_exists($key, $aSecondary))
			{
				//print "Key $key does NOT exist in secondary language (line: $cnt).\n";
				continue;
			}

			// String exists in both files and value into alternative language differs from main language but also from english files
			if (
				(! empty($aSecondary[$key]) && $aSecondary[$key] != $aPrimary[$key]
			    && ! empty($aEnglish[$key]) && $aSecondary[$key] != $aEnglish[$key])
				|| in_array($key, $arrayofkeytoalwayskeep) || preg_match('/^FormatDate/', $key) || preg_match('/^FormatHour/', $key)
				)
			{
				//print "Key $key differs (aSecondary=".$aSecondary[$key].", aPrimary=".$aPrimary[$key].", aEnglish=".$aEnglish[$key].") so we add it into new secondary language (line: $cnt).\n";
				fwrite($oh, $key."=".(empty($aSecondary[$key])?$aPrimary[$key]:$aSecondary[$key])."\n");
			}
		}
		if ( ! feof($handle) ) {
			$rc = 7;
			$msg = "Unexpected fgets() fail";
			print $msg . " (rc=$rc).\n";
			exit($rc);
		}
		fclose($oh);
		fclose($handle);
	}
	else {
		$rc = 8;
		$msg = "Cannot open file $lPrimaryFile";
		print $msg . " (rc=$rc).\n";
		exit($rc);
	}

	print "Output can be found at $output.\n";

	print "To rename all .delta files, you can do:\n";
	print '> for fic in `ls htdocs/langs/'.$lSecondary.'/*.delta`; do f=`echo $fic | sed -e \'s/\.delta//\'`; echo $f; mv $f.delta $f; done'."\n";
}


return 0;
