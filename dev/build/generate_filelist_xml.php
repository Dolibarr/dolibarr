#!/usr/bin/env php
<?php
/* Copyright (C) 2015-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 *      \file       dev/build/generate_filelist_xml.php
 *		\ingroup    dev
 * 		\brief      This script create a xml checksum file
 */

if (!defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');	// Do not create database handler $db
}

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(1);
}

require_once $path."../htdocs/master.inc.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";


/*
 * Main
 */

$includecustom = 0;
$includeconstants = array();
$buildzip = 0;

if (empty($argv[1])) {
	print "Usage:   ".$script_file." release=autostable|auto[-mybuild]|x.y.z[-mybuild] [includecustom=1] [includeconstant=CC:MY_CONF_NAME:value] [buildzip=1]\n";
	print "Example: ".$script_file." release=6.0.0 includecustom=1 includeconstant=FR:INVOICE_CAN_ALWAYS_BE_REMOVED:0 includeconstant=all:MAILING_NO_USING_PHPMAIL:1\n";
	exit(1);
}


$i = 0;
$result = array();
while ($i < $argc) {
	if (!empty($argv[$i])) {
		parse_str($argv[$i], $result);	// set all params $release, $includecustom, $includeconstant, $buildzip ...
	}
	if (!empty($result["release"])) {
		$release = $result["release"];
	}
	if (!empty($result["includecustom"])) {
		$includecustom = $result["includecustom"];
	}
	if (!empty($result["includeconstant"])) {
		$includeconstants[$i] = $result["includeconstant"];
	}
	if (!empty($result["buildzip"])) {
		$buildzip = 1;
	}
	if (preg_match('/includeconstant=/', strval($argv[$i]))) {
		$tmp = explode(':', $result['includeconstant'], 3);			// $includeconstant has been set with previous parse_str()
		if (count($tmp) != 3) {
			print "Error: Bad parameter includeconstant=".$result['includeconstant'] ."\n";
			exit -1;
		}
		$includeconstants[$tmp[0]][$tmp[1]] = $tmp[2];
	}
	$i++;
}

if (empty($release)) {
	print "Error: Missing release parameter\n";
	print "Usage: ".$script_file." release=autostable|auto[-mybuild]|x.y.z[-mybuild] [includecustom=1] [includeconstant=CC:MY_CONF_NAME:value]\n";
	exit(2);
}

$savrelease = $release;

// If release is auto, we take current version
$tmpver = explode('-', $release, 2);
if ($tmpver[0] == 'auto' || $tmpver[0] == 'autostable') {
	$release = DOL_VERSION;
	if ($tmpver[1] && $tmpver[0] == 'auto') {
		$release .= '-'.$tmpver[1];
	}
}

if (empty($includecustom)) {
	$tmpverbis = explode('-', $release, 2);
	if (empty($tmpverbis[1]) || $tmpver[0] == 'autostable') {
		if (DOL_VERSION != $tmpverbis[0] && $savrelease != 'auto') {
			print 'Error: When parameter "includecustom" is not set and there is no suffix in release parameter, version declared into filefunc.in.php ('.DOL_VERSION.') must be exact same value than "release" parameter ('.$tmpverbis[0].')'."\n";
			print "Usage:   ".$script_file." release=autostable|auto[-mybuild]|x.y.z[-mybuild] [includecustom=1] [includeconstant=CC:MY_CONF_NAME:value]\n";
			exit(3);
		}
	} else {
		$tmpverter = explode('-', DOL_VERSION, 2);
		if ($tmpverter[0] != $tmpverbis[0]) {
			print 'Error: When parameter "includecustom" is not set, version declared into filefunc.in.php ('.DOL_VERSION.') must have value without prefix ('.$tmpverter[0].') that is exact same value than "release" parameter ('.$tmpverbis[0].')'."\n";
			print "Usage:   ".$script_file." release=autostable|auto[-mybuild]|x.y.z[-mybuild] [includecustom=1] [includeconstant=CC:MY_CONF_NAME:value]\n";
			exit(4);
		}
	}
} else {
	if (!preg_match('/'.preg_quote(DOL_VERSION, '/').'-/', $release)) {
		print 'Error: When parameter "includecustom" is set, version declared into filefunc.inc.php ('.DOL_VERSION.') must be used with a suffix into "release" parameter (ex: '.DOL_VERSION.'-mydistrib).'."\n";
		print "Usage:   ".$script_file." release=autostable|auto[-mybuild]|x.y.z[-mybuild] [includecustom=1] [includeconstant=CC:MY_CONF_NAME:value]\n";
		exit(5);
	}
}

print "Working on files into          : ".DOL_DOCUMENT_ROOT."\n";
print "Release                        : ".$release."\n";
print "Include custom in signature    : ".$includecustom."\n";
print "Include constants in signature : ";
foreach ($includeconstants as $countrycode => $tmp) {
	foreach ($tmp as $constname => $constvalue) {
		print $constname.'='.$constvalue." ";
	}
}
print "\n";

//$outputfile=dirname(__FILE__).'/../htdocs/install/filelist-'.$release.'.xml';
$outputdir = dirname(dirname(__FILE__)).'/htdocs/install';
print 'Delete current files '.$outputdir.'/filelist*.xml*'."\n";
dol_delete_file($outputdir.'/filelist*.xml*', 0, 1, 1);

$checksumconcat = array();

$outputfile = $outputdir.'/filelist-'.$release.'.xml';
$fp = fopen($outputfile, 'w');
if (empty($fp)) {
	print 'Failed to open file '.$outputfile."\n";
	exit(6);
}

fputs($fp, '<?xml version="1.0" encoding="UTF-8" ?>'."\n");
fputs($fp, '<checksum_list version="'.$release.'" date="'.dol_print_date(dol_now(), 'dayhourrfc').'" generator="'.$script_file.'">'."\n");

foreach ($includeconstants as $countrycode => $tmp) {
	fputs($fp, '<dolibarr_constants country="'.$countrycode.'">'."\n");
	foreach ($tmp as $constname => $constvalue) {
		$valueforchecksum = (empty($constvalue) ? '0' : $constvalue);
		$checksumconcat[] = $valueforchecksum;
		fputs($fp, '    <constant name="'.$constname.'">'.$valueforchecksum.'</constant>'."\n");
	}
	fputs($fp, '</dolibarr_constants>'."\n");
}

fputs($fp, '<dolibarr_htdocs_dir includecustom="'.$includecustom.'">'."\n");

/*$dir_iterator1 = new RecursiveDirectoryIterator(dirname(__FILE__).'/../htdocs/');
$iterator1 = new RecursiveIteratorIterator($dir_iterator1);
// Need to ignore document custom etc. Note: this also ignore natively symbolic links.
$files = new RegexIterator($iterator1, '#^(?:[A-Z]:)?(?:/(?!(?:'.($includecustom?'':'custom\/|').'documents\/|conf\/|install\/))[^/]+)+/[^/]+\.(?:php|css|html|js|json|tpl|jpg|png|gif|sql|lang)$#i');
*/
// Define qualified files (must be same than into generate_filelist_xml.php and in api_setup.class.php)
$regextoinclude = '\.(php|php3|php4|php5|phtml|phps|phar|inc|css|scss|html|xml|js|json|tpl|jpg|jpeg|png|gif|ico|sql|lang|txt|yml|bak|md|mp3|mp4|wav|mkv|z|gz|zip|rar|tar|less|svg|eot|woff|woff2|ttf|manifest)$';
$regextoexclude = '('.($includecustom ? '' : 'custom|').'documents|conf|install|dejavu-fonts-ttf-.*|public\/test|sabre\/sabre\/.*\/tests|Shared\/PCLZip|nusoap\/lib\/Mail|php\/example|php\/test|geoip\/sample.*\.php|ckeditor\/samples|ckeditor\/adapters)$';  // Exclude dirs
$files = dol_dir_list(DOL_DOCUMENT_ROOT, 'files', 1, $regextoinclude, $regextoexclude, 'fullname');

$dir = '';
$needtoclose = 0;
foreach ($files as $filetmp) {
	$file = $filetmp['fullname'];
	//$newdir = str_replace(dirname(__FILE__).'/../htdocs', '', dirname($file));
	$newdir = str_replace(DOL_DOCUMENT_ROOT, '', dirname($file));
	if ($newdir != $dir) {
		if ($needtoclose) {
			fputs($fp, '  </dir>'."\n");
		}
		fputs($fp, '  <dir name="'.$newdir.'">'."\n");
		$dir = $newdir;
		$needtoclose = 1;
	}
	if (filetype($file) == "file") {
		$md5 = md5_file($file);
		$checksumconcat[] = $md5;
		fputs($fp, '    <md5file name="'.basename($file).'" size="'.filesize($file).'">'.$md5.'</md5file>'."\n");
	}
}
fputs($fp, '  </dir>'."\n");
fputs($fp, '</dolibarr_htdocs_dir>'."\n");

asort($checksumconcat); // Sort list of checksum

fputs($fp, '<dolibarr_htdocs_dir_checksum>'."\n");
fputs($fp, md5(join(',', $checksumconcat))."\n");
fputs($fp, '</dolibarr_htdocs_dir_checksum>'."\n");


$checksumconcat = array();

fputs($fp, '<dolibarr_script_dir version="'.$release.'">'."\n");

// TODO Replace RecursiveDirectoryIterator with dol_dir_list
/*$dir_iterator2 = new RecursiveDirectoryIterator(dirname(__FILE__).'/../scripts/');
$iterator2 = new RecursiveIteratorIterator($dir_iterator2);
// Need to ignore document custom etc. Note: this also ignore natively symbolic links.
$files = new RegexIterator($iterator2, '#^(?:[A-Z]:)?(?:/(?!(?:custom|documents|conf|install))[^/]+)+/[^/]+\.(?:php|css|html|js|json|tpl|jpg|png|gif|sql|lang)$#i');
*/
$regextoinclude = '\.(php|css|html|js|json|tpl|jpg|png|gif|sql|lang)$';
$regextoexclude = '(custom|documents|conf|install)$';  // Exclude dirs
$files = dol_dir_list(dirname(__FILE__).'/../scripts/', 'files', 1, $regextoinclude, $regextoexclude, 'fullname');
$dir = '';
$needtoclose = 0;
foreach ($files as $filetmp) {
	$file = $filetmp['fullname'];
	//$newdir = str_replace(dirname(__FILE__).'/../scripts', '', dirname($file));
	$newdir = str_replace(DOL_DOCUMENT_ROOT, '', dirname($file));
	$newdir = str_replace(dirname(__FILE__).'/../scripts', '', dirname($file));
	if ($newdir != $dir) {
		if ($needtoclose) {
			fputs($fp, '  </dir>'."\n");
		}
		fputs($fp, '  <dir name="'.$newdir.'" >'."\n");
		$dir = $newdir;
		$needtoclose = 1;
	}
	if (filetype($file) == "file") {
		$md5 = md5_file($file);
		$checksumconcat[] = $md5;
		fputs($fp, '    <md5file name="'.basename($file).'" size="'.filesize($file).'">'.$md5.'</md5file>'."\n");
	}
}
fputs($fp, '  </dir>'."\n");
fputs($fp, '</dolibarr_script_dir>'."\n");

asort($checksumconcat); // Sort list of checksum
fputs($fp, '<dolibarr_script_dir_checksum>'."\n");
fputs($fp, md5(join(',', $checksumconcat))."\n");
fputs($fp, '</dolibarr_script_dir_checksum>'."\n");

fputs($fp, '</checksum_list>'."\n");
fclose($fp);

if (empty($buildzip)) {
	print "File ".$outputfile." generated\n";
} else {
	if ($buildzip == '1' || $buildzip == 'zip') {
		$result = dol_compress_file($outputfile, $outputfile.'.zip', 'zip');
		if ($result > 0) {
			dol_delete_file($outputfile);
			print "File ".$outputfile.".zip generated\n";
		}
	} elseif ($buildzip == '2' || $buildzip == 'gz') {
		$result = dol_compress_file($outputfile, $outputfile.'.gz', 'gz');
		if ($result > 0) {
			dol_delete_file($outputfile);
			print "File ".$outputfile.".gz generated\n";
		}
	}
}

exit(0);
