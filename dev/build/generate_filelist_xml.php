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
define('NOREQUIREVIRTUALURL', 1);

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(1);
}

define('DOL_DOCUMENT_ROOT', dirname(dirname($path)).'/htdocs');

$algo = 'sha256';

require_once $path."../../htdocs/master.inc.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";


/*
 * Main
 */

$includecustom = 0;
$includeconstants = array();
$buildzip = 0;
$release = '';
$checklock = '';

print '***** '.$script_file.' *****'."\n";

if (empty($argv[1])) {
	print "Usage:   ".$script_file." release=auto[-mybuild]|x.y.z[-mybuild] [includecustom=1] [includeconstant=CC:MY_CONF_NAME:value] [buildzip=1]\n";
	print "Usage:   ".$script_file." checklock=auto[-mybuild]|x.y.z[-mybuild] unalterable_files\n";
	print "\n";
	print "Example: ".$script_file." release=6.0.0 includecustom=1 includeconstant=ES:CONST_XX_IS_ON includeconstant=all:MAILING_NO_USING_PHPMAIL:1\n";
	print "\n";
	print "Generate the file filelist-x.y.z[-mybuild].xml with signature of files. ";
	print "The file always includes the 3 sections:\n";
	print "- dolibarr_htdocs_dir\n";
	print "- dolibarr_scripts_dir\n";
	print "- dolibarr_unalterable_files (only files inside the scope of the unalterable module)\n";
	print "and if a specific setup/parameter need to be included into the signature for check:\n";
	print "- dolibarr_constants\n";
	print "\n";
	print "If used with parameter 'check_unalterable_files', it will validate that the signature generated is the samethan the one found into lockedfiles.txt";
	print "\n";
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
	if (!empty($result["checklock"])) {
		$checklock = $result["checklock"];
	}
	if (!empty($result["includecustom"])) {
		$includecustom = $result["includecustom"];
	}
	if (preg_match('/unalterable_files/', strval($argv[$i]))) {
		$checksource = 'unalterable_files';
	}
	if (preg_match('/includeconstant=/', strval($argv[$i]))) {
		$tmp = explode(':', $result['includeconstant'], 3);			// $includeconstant has been set with previous parse_str()
		if (count($tmp) != 3) {
			print "Error: Bad parameter includeconstant=".$result['includeconstant'] ."\n";
			exit(1);
		}
		$includeconstants[$tmp[0]][$tmp[1]] = $tmp[2];
	}
	if (!empty($result["buildzip"])) {
		$buildzip = 1;
	}
	$i++;
}

if (empty($release) && empty($checklock)) {
	print "Error: Missing release or checklock parameter\n";
	print "Usage: ".$script_file." release=auto[-mybuild]|x.y.z[-mybuild] [includecustom=1] [includeconstant=CC:MY_CONF_NAME:value]\n";
	print "Usage: ".$script_file." checklock=auto[-mybuild]|x.y.z[-mybuild] unalterable_files\n";
	exit(2);
}

$savrelease = $release;

// If release is auto, we take current version
$tmpver = explode('-', $release, 2);
if ($tmpver[0] == 'auto') {
	$release = DOL_VERSION;
	if (!empty($tmpver[1]) && $tmpver[0] == 'auto') {
		$release .= '-'.$tmpver[1];
	}
}
// If release is auto, we take current version
$tmpver = explode('-', $checklock, 2);
if ($tmpver[0] == 'auto') {
	$checklock = DOL_VERSION;
	if (!empty($tmpver[1]) && $tmpver[0] == 'auto') {
		$checklock .= '-'.$tmpver[1];
	}
}

$checklockmajorversion = '';
if ($checklock) {
	$checklockmajorversion = preg_replace('/-.*$/', '', $checklock);
	$checklockmajorversion = preg_replace('/\..*/', '', $checklockmajorversion);
	$checklockmajorversion .= '.0.0';
}

if ($release) {
	if (empty($includecustom)) {
		$tmpverbis = explode('-', $release, 2);
		if (empty($tmpverbis[1])) {
			if (DOL_VERSION != $tmpverbis[0] && $savrelease != 'auto') {
				print 'Error:  When parameter "includecustom" is not set and there is no suffix in release parameter, version declared into filefunc.in.php ('.DOL_VERSION.') must be exactly the same value than "release" parameter ('.$tmpverbis[0].')'."\n";
				print "Usage:  ".$script_file." release=auto[-mybuild]|x.y.z[-mybuild] [includecustom=1] [includeconstant=CC:MY_CONF_NAME:value]\n";
				print "\n";
				exit(3);
			}
		} else {
			$tmpverter = explode('-', DOL_VERSION, 2);
			if ($tmpverter[0] != $tmpverbis[0]) {
				print 'Error:  When parameter "includecustom" is not set, version declared into filefunc.in.php ('.DOL_VERSION.') must have value without prefix ('.$tmpverter[0].') that is exact same value than "release" parameter ('.$tmpverbis[0].')'."\n";
				print "Usage:  ".$script_file." release=auto[-mybuild]|x.y.z[-mybuild] [includecustom=1] [includeconstant=CC:MY_CONF_NAME:value]\n";
				print "\n";
				exit(4);
			}
		}
	} else {
		if (!preg_match('/'.preg_quote(DOL_VERSION, '/').'-/', $release)) {
			print 'Error:  When parameter "includecustom" is set, version declared into filefunc.inc.php ('.DOL_VERSION.') must be used with a suffix into "release" parameter (ex: '.DOL_VERSION.'-mydistrib).'."\n";
			print "Usage:  ".$script_file." release=auto[-mybuild]|x.y.z[-mybuild] [includecustom=1] [includeconstant=CC:MY_CONF_NAME:value]\n";
			print "\n";
			exit(5);
		}
	}
}

if ($checklock && empty($checksource)) {
	print 'Error:  When action "checklock" is set, second parameter must be the scope family to check, for example "unalterable_files"'."\n";
	print "Usage:  ".$script_file." checklock=auto[-mybuild]|x.y.z[-mybuild] unalterable_files\n";
	print "\n";
	exit(6);
}

if ($release) {
	print "Working on files into           : ".DOL_DOCUMENT_ROOT."\n";
	print "Version of target release       : ".$release."\n";
	print "Include custom dir in signature : ".(empty($includecustom) ? 'no' : 'yes')."\n";
	print "Include constants in signature  : ".(empty($includeconstants) ? 'none' : '');
	foreach ($includeconstants as $countrycode => $tmp) {
		foreach ($tmp as $constname => $constvalue) {
			print $constname.'='.$constvalue." ";
		}
	}
	print "\n";
}
if ($checklock) {
	print "Working on files into               : ".DOL_DOCUMENT_ROOT."\n";
	print "Version to check in lockedfiles.txt : ".$checklockmajorversion."\n";
	print "Check source                        : ".$checksource."\n";
}

if ($release) {
	//$outputfile=dirname(__FILE__).'/../htdocs/install/filelist-'.$release.'.xml';
	$outputdir = dirname(dirname(dirname(__FILE__))).'/htdocs/install';
	print 'Delete current files '.$outputdir.'/filelist*.xml*'."\n";
	dol_delete_file($outputdir.'/filelist*.xml*', 0, 1, 1);
}


$needtoclose = 0;


// Build the XML file
if ($release) {
	$checksumconcat = array();

	$outputfile = $outputdir.'/filelist-'.$release.'.xml';
	$fp = fopen($outputfile, 'w');
	if (empty($fp)) {
		print 'Failed to open file '.$outputfile."\n";
		exit(7);
	}

	$gitcommit = 'seetag';
	$branchname = preg_replace('/^(\d+\.\d+)\..*$/', '\1', $release);	// Keep only x.y into x.y.z
	$fileforgit = dirname(dirname(dirname(__FILE__))).'/.git/refs/heads/'.$branchname;
	print "Try to get last commit ID from file ".$fileforgit."\n";
	$fileforgitcontent = '';
	if (file_exists($fileforgit)) {
		$fileforgitcontent = file_get_contents($fileforgit);
	}
	if (empty($fileforgitcontent)) {
		print "Failed to get the last commit ID (are you on the branch for the release branch name ".$branchname." ?). We will use an empty value for gitcommit.\n";
	}
	$gitcommit = trim($fileforgitcontent);

	fputs($fp, '<?xml version="1.0" encoding="UTF-8" ?>'."\n");
	fputs($fp, '<checksum_list version="'.$release.'" date="'.dol_print_date(dol_now(), 'dayhourrfc').'" generator="'.$script_file.'" algo="'.$algo.'" gitcommit="'.$gitcommit.'">'."\n");

	foreach ($includeconstants as $countrycode => $tmp) {
		fputs($fp, '<dolibarr_constants country="'.$countrycode.'">'."\n");
		foreach ($tmp as $constname => $constvalue) {
			$valueforchecksum = (empty($constvalue) ? '0' : $constvalue);
			$checksumconcat[] = $valueforchecksum;
			fputs($fp, '    <constant name="'.$constname.'">'.$valueforchecksum.'</constant>'."\n");
		}
		fputs($fp, '</dolibarr_constants>'."\n\n");
	}

	fputs($fp, '<dolibarr_htdocs_dir includecustom="'.$includecustom.'">'."\n");

	// Define qualified files (must be same than into generate_filelist_xml.php and in api_setup.class.php)
	$regextoinclude = '\.(php|php3|php4|php5|phtml|phps|phar|inc|css|scss|html|xml|js|json|tpl|jpg|jpeg|png|gif|ico|sql|lang|txt|yml|bak|md|mp3|mp4|wav|mkv|z|gz|zip|rar|tar|less|svg|eot|woff|woff2|ttf|manifest)$';
	$regextoexclude = '('.($includecustom ? '' : 'custom|').'documents|escpos-php\/doc|escpos-php\/example|escpos-php\/test|conf|install|dejavu-fonts-ttf-.*|public\/test|sabre\/sabre\/.*\/tests|Shared\/PCLZip|nusoap\/lib\/Mail|php\/test|geoip\/sample.*\.php|ckeditor\/samples|ckeditor\/adapters)$';  // Exclude dirs
	$files = dol_dir_list(DOL_DOCUMENT_ROOT, 'files', 1, $regextoinclude, $regextoexclude, 'fullname');

	$dir = '';
	foreach ($files as $filetmp) {
		$file = $filetmp['fullname'];
		//$newdir = str_replace(dirname(__FILE__).'/../htdocs', '', dirname($file));
		$newdir = str_replace(DOL_DOCUMENT_ROOT, '', dirname($file));
		if ($newdir != $dir) {
			if ($needtoclose) {
				fputs($fp, '  </dir>'."\n");
				$needtoclose = 0;
			}
			fputs($fp, '  <dir name="'.$newdir.'">'."\n");
			$dir = $newdir;
			$needtoclose = 1;
		}
		if (filetype($file) == "file") {
			$hashoffile = hash_file($algo, $file);
			$checksumconcat[] = $hashoffile;
			fputs($fp, '    <'.$algo.'file name="'.basename($file).'" size="'.filesize($file).'">'.$hashoffile.'</'.$algo.'file>'."\n");
		}
	}
	if ($needtoclose) {
		fputs($fp, '  </dir>'."\n");
		$needtoclose = 0;
	}
	fputs($fp, '</dolibarr_htdocs_dir>'."\n");

	asort($checksumconcat); // Sort list of checksum
	$hashhtdocsdir = hash($algo, join(',', $checksumconcat));

	fputs($fp, '<dolibarr_htdocs_dir_checksum>'."\n");
	fputs($fp, $hashhtdocsdir."\n");
	fputs($fp, '</dolibarr_htdocs_dir_checksum>'."\n\n");


	// Add the checksum for the part in scripts

	$checksumconcat = array();

	fputs($fp, '<dolibarr_scripts_dir version="'.$release.'">'."\n");

	$regextoinclude = '\.(php|css|html|js|json|tpl|jpg|png|gif|sql|lang)$';
	$regextoexclude = '(custom|documents|conf|install)$';  // Exclude dirs
	$files = dol_dir_list(dirname(__FILE__).'/../../scripts/', 'files', 1, $regextoinclude, $regextoexclude, 'fullname');
	$dir = '';
	foreach ($files as $filetmp) {
		$file = $filetmp['fullname'];
		$newdir = str_replace(DOL_DOCUMENT_ROOT, '', dirname($file));
		$newdir = str_replace(dirname(__FILE__).'/../../scripts', '', dirname($file));
		if ($newdir != $dir) {
			if ($needtoclose) {
				fputs($fp, '  </dir>'."\n");
				$needtoclose = 0;
			}
			fputs($fp, '  <dir name="'.$newdir.'">'."\n");
			$dir = $newdir;
			$needtoclose = 1;
		}
		if (filetype($file) == "file") {
			$hashoffile = hash_file($algo, $file);
			$checksumconcat[] = $hashoffile;
			fputs($fp, '    <'.$algo.'file name="'.basename($file).'" size="'.filesize($file).'">'.$hashoffile.'</'.$algo.'file>'."\n");
		}
	}
	if ($needtoclose) {
		fputs($fp, '  </dir>'."\n");
		$needtoclose = 0;
	}
	fputs($fp, '</dolibarr_scripts_dir>'."\n");

	asort($checksumconcat); // Sort list of checksum
	$hashscriptsdir = hash($algo, join(',', $checksumconcat));

	fputs($fp, '<dolibarr_scripts_dir_checksum>'."\n");
	fputs($fp, $hashscriptsdir."\n");
	fputs($fp, '</dolibarr_scripts_dir_checksum>'."\n\n");
}



// Add the checksum for the files into the scope of the unalterable system (record, read, export)

$checksumconcat = array();

if ($release) {
	fputs($fp, '<dolibarr_unalterable_files version="'.$release.'">'."\n");
}

// Array of dir/files to include in the section
$arrayofunalterablefiles = array(
	array('dir' => dirname(__FILE__).'/../../htdocs/', 'file' => 'version.inc.php'),
	array('dir' => dirname(__FILE__).'/../../htdocs/blockedlog', 'file' => 'all', 'regextoinclude' => '(\.php|\.sql)$', 'regextoexclude' => ''),
	array('dir' => dirname(__FILE__).'/../../htdocs/install/mysql/tables', 'file' => 'all', 'regextoinclude' => 'llx_blockedlog.*(\.php|\.sql)$', 'regextoexclude' => ''),
	array('dir' => dirname(__FILE__).'/../../htdocs/core/triggers', 'file' => 'interface_50_modBlockedlog_ActionsBlockedLog.class.php'),
	array('dir' => dirname(__FILE__).'/../../htdocs/core/class', 'file' => 'all', 'regextoinclude' => '(interfaces.class.php|commontrigger.class.php)$', 'regextoexclude' => ''),
	array('dir' => dirname(__FILE__).'/../../htdocs/takepos', 'file' => 'receipt.php')
);

foreach ($arrayofunalterablefiles as $entry) {
	if ($entry['file'] == 'all') {
		$regextoinclude = $entry['regextoinclude'];
		$regextoexclude = $entry['regextoexclude'];
		$files = dol_dir_list($entry['dir'], 'files', 1, $regextoinclude, $regextoexclude, 'fullname');
		$dir = '';
		foreach ($files as $filetmp) {
			$file = $filetmp['fullname'];
			$newdir = str_replace(DOL_DOCUMENT_ROOT, '', dirname($file));
			$newdir = str_replace(dirname(__FILE__).'/../../htdocs', '', dirname($file));
			if ($newdir != $dir) {
				if ($needtoclose) {
					if ($release) {
						fputs($fp, '  </dir>'."\n");
					}
					$needtoclose = 0;
				}
				if ($release) {
					fputs($fp, '  <dir name="'.$newdir.'">'."\n");
				}
				$dir = $newdir;
				$needtoclose = 1;
			}
			if (filetype($file) == "file") {
				$hashoffile = hash_file($algo, $file);
				$checksumconcat[] = $hashoffile;
				if ($release) {
					fputs($fp, '    <'.$algo.'file name="'.basename($file).'" size="'.filesize($file).'">'.$hashoffile.'</'.$algo.'file>'."\n");
				}
			}
		}
		if ($needtoclose) {
			if ($release) {
				fputs($fp, '  </dir>'."\n");
			}
			$needtoclose = 0;
		}
	} else {
		$file = $entry['dir'].'/'.$entry['file'];
		$dir = '';
		$newdir = str_replace(DOL_DOCUMENT_ROOT, '', dirname($file));
		$newdir = str_replace(dirname(__FILE__).'/../../htdocs', '', dirname($file));
		if (!file_exists($file)) {
			print "Error file ".$file." does not exists.";
			exit(1);
		}
		if ($newdir != $dir) {
			if ($needtoclose) {
				if ($release) {
					fputs($fp, '  </dir>'."\n");
				}
				$needtoclose = 0;
			}
			if ($release) {
				fputs($fp, '  <dir name="'.$newdir.'">'."\n");
			}
			$dir = $newdir;
			$needtoclose = 1;
		}
		if (filetype($file) == "file") {
			$hashoffile = hash_file($algo, $file);
			$checksumconcat[] = $hashoffile;
			if ($release) {
				fputs($fp, '    <'.$algo.'file name="'.basename($file).'" size="'.filesize($file).'">'.$hashoffile.'</'.$algo.'file>'."\n");
			}
		}
		if ($needtoclose) {
			if ($release) {
				fputs($fp, '  </dir>'."\n");
			}
			$needtoclose = 0;
		}
	}
}

asort($checksumconcat); // Sort list of checksum
$hashunalterable_files = hash($algo, join(',', $checksumconcat));

if ($release) {
	fputs($fp, '</dolibarr_unalterable_files>'."\n");

	fputs($fp, '<dolibarr_unalterable_files_checksum>'."\n");
	fputs($fp, $hashunalterable_files."\n");
	fputs($fp, '</dolibarr_unalterable_files_checksum>'."\n\n");

	// End of file

	fputs($fp, '</checksum_list>'."\n");
	fclose($fp);
}

print "\n";

if ($release) {
	if (empty($buildzip)) {
		print "File ".$outputfile." generated.\n";
		print "Signature for htdocs files: ".$hashhtdocsdir."\n";
		print "Signature for scripts files: ".$hashscriptsdir."\n";
		print "Signature for the ".count($checksumconcat)." unalterable files: ".$hashunalterable_files."\n";
	} else {
		if ($buildzip == '1' || $buildzip == 'zip') {
			$result = dol_compress_file($outputfile, $outputfile.'.zip', 'zip');
			if ($result > 0) {
				dol_delete_file($outputfile);
				print "File ".$outputfile.".zip generated.\n";
			}
		} elseif ($buildzip == '2' || $buildzip == 'gz') {
			$result = dol_compress_file($outputfile, $outputfile.'.gz', 'gz');
			if ($result > 0) {
				dol_delete_file($outputfile);
				print "File ".$outputfile.".gz generated.\n";
			}
		}
	}
}

if ($checklock) {
	print "Signature for unalterable files: ".$algo." ".$hashunalterable_files."\n";

	$lockedfile = DOL_DOCUMENT_ROOT.'/../dev/lockedfiles.txt';
	$checksuminlockedfile = '';

	if (!file_exists($lockedfile)) {
		print "Can't find the file ".$lockedfile.". No checksum to check\n";
	} else {
		// Now we check the content of lockedfiles.txt
		$arraylocked = file($lockedfile);
		foreach ($arraylocked as $line) {
			$tmparray = preg_split("/\s+/", $line, 4);
			if ($tmparray[0] == $checklockmajorversion && $tmparray[2] == $algo) {
				$checksuminlockedfile = $tmparray[3];
			}
		}
		if (empty($checksuminlockedfile)) {
			print "The major version ".$checklockmajorversion." is not locked on the scope '".$checksource."' (file found but no matching entry found into dev/lockedfiles.txt).\n";
		} elseif ($checksuminlockedfile != $hashunalterable_files) {
			print "The major version ".$checklockmajorversion." is locked on scope '".$checksource."' to checksum ".$algo." ".$checksuminlockedfile."\n";
			if ($checklockmajorversion != $checksource) {
				print "The checksum now differs from the locked one, so we return an error.\n";
				print "\n";
				exit(10);
			}
		}
	}
}

print "\n";


exit(0);
