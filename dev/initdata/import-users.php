#!/usr/bin/env php
<?php
/* Copyright (C) 2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2016 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *
 * WARNING, THIS WILL LOAD MASS DATA ON YOUR INSTANCE
 */

/**
 *      \file       dev/initdata/import-users.php
 *		\brief      Script example to insert thirdparties from a csv file.
 *                  To purge data, you can have a look at purge-data.php
 */

// Test si mode batch
$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit;
}

// Recupere root dolibarr
$path=preg_replace('/import-users.php/i', '', $_SERVER["PHP_SELF"]);
require $path."../../htdocs/master.inc.php";
include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$delimiter=',';
$enclosure='"';
$linelength=10000;
$escape='/';

// Global variables
$version=DOL_VERSION;
$confirmed=1;
$error=0;


/*
 * Main
 */

@set_time_limit(0);
print "***** ".$script_file." (".$version.") pid=".dol_getmypid()." *****\n";
dol_syslog($script_file." launched with arg ".implode(',', $argv));

$mode = $argv[1];
$filepath = $argv[2];
$filepatherr = $filepath.'.err';
//$defaultlang = empty($argv[3])?'en_US':$argv[3];
$startlinenb = empty($argv[3]) ? 1 : $argv[3];
$endlinenb = empty($argv[4]) ? 0 : $argv[4];

if (empty($mode) || ! in_array($mode, array('test','confirm','confirmforced')) || empty($filepath)) {
	print "Usage:  $script_file (test|confirm|confirmforced) filepath.csv [startlinenb] [endlinenb]\n";
	print "Usage:  $script_file test myfilepath.csv 2 1002\n";
	print "\n";
	exit(-1);
}
if (! file_exists($filepath)) {
	print "Error: File ".$filepath." not found.\n";
	print "\n";
	exit(-1);
}

$ret=$user->fetch('', 'admin');
if (! $ret > 0) {
	print 'A user with login "admin" and all permissions must be created to use this script.'."\n";
	exit;
}
$user->getrights();

// Ask confirmation
if (! $confirmed) {
	print "Hit Enter to continue or CTRL+C to stop...\n";
	$input = trim(fgets(STDIN));
}

// Open input and output files
$fhandle = fopen($filepath, 'r');
if (! $fhandle) {
	print 'Error: Failed to open file '.$filepath."\n";
	exit(1);
}
$fhandleerr = fopen($filepatherr, 'w');
if (! $fhandleerr) {
	print 'Error: Failed to open file '.$filepatherr."\n";
	exit(1);
}

//$langs->setDefaultLang($defaultlang);


$db->begin();

$i=0;
$nboflines=0;
while ($fields=fgetcsv($fhandle, $linelength, $delimiter, $enclosure, $escape)) {
	$i++;
	$errorrecord=0;

	if ($startlinenb && $i < $startlinenb) {
		continue;
	}
	if ($endlinenb && $i > $endlinenb) {
		continue;
	}

	$nboflines++;

	$object = new User($db);
	$object->status = 1;

	$tmp=explode(' ', $fields[3], 2);
	$object->firstname = trim($tmp[0]);
	$object->lastname = trim($tmp[1]);
	if ($object->lastname) {
		$object->login = strtolower(substr($object->firstname, 0, 1)) . strtolower(substr($object->lastname, 0));
	} else {
		$object->login=strtolower($object->firstname);
	}
	$object->login=preg_replace('/ /', '', $object->login);
	$object->password = 'init';

	print "Process line nb ".$i.", login ".$object->login;

	$ret=$object->create($user);
	if ($ret < 0) {
		print " - Error in create result code = ".$ret." - ".$object->errorsToString();
		$errorrecord++;
	} else {
		print " - Creation OK with login ".$object->login." - id = ".$ret;
	}

	print "\n";

	if ($errorrecord) {
		fwrite($fhandleerr, 'Error on record nb '.$i." - ".$object->errorsToString()."\n");
		$error++;    // $errorrecord will be reset
	}
}





// commit or rollback
print "Nb of lines qualified: ".$nboflines."\n";
print "Nb of errors: ".$error."\n";
if ($mode != 'confirmforced' && ($error || $mode != 'confirm')) {
	print "Rollback any changes.\n";
	$db->rollback();
} else {
	print "Commit all changes.\n";
	$db->commit();
}

$db->close();
fclose($fhandle);
fclose($fhandleerr);

exit($error);
