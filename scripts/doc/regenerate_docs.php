#!/usr/bin/env php
<?php
/* Copyright (C) 2007-2016 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2015 Jean Heimburger <http://tiaris.eu>
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
 * \file 	scripts/doc/regenerate_docs.php
 * \ingroup scripts
 * \brief 	Massive re-generation of the main documents into one sub-directory of the documents directory
 */

if (!defined('NOSESSION')) {
	define('NOSESSION', '1');
}

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = __DIR__.'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(1);
}

@set_time_limit(0); // No timeout for this script
define('EVEN_IF_ONLY_LOGIN_ALLOWED', 1); // Set this define to 0 if you want to lock your script when dolibarr setup is "locked to admin user only".

// Include and load Dolibarr environment variables
require_once $path."../../htdocs/master.inc.php";
require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/images.lib.php";
// After this $db, $mysoc, $langs, $conf and $hookmanager are defined (Opened $db handler to database will be closed at end of file).
// $user is created but empty.

// $langs->setDefaultLang('en_US'); // To change default language of $langs
$langs->load("main"); // To load language file for default language

// Global variables
$version = DOL_VERSION;
$error = 0;
$forcecommit = 0;

$hookmanager->initHooks(array('cli'));


/*
 * Main
 */

print "***** ".$script_file." (".$version.") pid=".dol_getmypid()." - dir=".DOL_DATA_ROOT." *****\n";
dol_syslog($script_file." launched with arg ".join(',', $argv));

if (empty($argv[1])) {
	print "Usage:    $script_file  subdirtoscan (test|confirm)\n";
	print "Example:  $script_file  propale test\n";
	exit(1);
}

print '--- start'."\n";

$dir = DOL_DATA_ROOT;
$subdir = $argv[1];
if (empty($dir) || empty($subdir)) {
	dol_print_error(null, 'dir not defined');
	exit(1);
}
if (!dol_is_dir($dir.'/'.$subdir)) {
	print 'Directory '.$dir.'/'.$subdir.' not found.'."\n";
	exit(2);
}

print 'Scan directory '.$dir.'/'.$subdir."\n";

$filearray = dol_dir_list($dir.'/'.$subdir, "directories", 0, '', 'temp$');

$nbok = $nbko = 0;

$tmpobject = null;
if ($subdir == 'propale' || $subdir == 'proposal') {
	if (isModEnabled('propal')) {
		require_once DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php";
		$tmpobject = new Propal($db);
	} else {
		print 'Error, module not enabled'."\n";
	}
} elseif ($subdir == 'commande' || $subdir == 'order') {
	if (isModEnabled('commande')) {
		require_once DOL_DOCUMENT_ROOT."/commande/class/commande.class.php";
		$tmpobject = new Commande($db);
	} else {
		print 'Error, module not enabled'."\n";
	}
} elseif ($subdir == 'facture' || $subdir == 'invoice') {
	if (isModEnabled('facture')) {
		require_once DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php";
		$tmpobject = new Facture($db);
	} else {
		print 'Error, module not enabled'."\n";
	}
} else {
	print 'Dir '.$subdir.' not yet supported'."\n";
}

if ($tmpobject) {
	foreach ($filearray as $keyf => $valf) {
		$ref = basename($valf['name']);
		print 'Process document for dir = '.$subdir.', ref '.$ref."\n";

		$ret1 = $tmpobject->fetch(0, $ref);
		$ret2 = $tmpobject->fetch_thirdparty();

		if ($ret1 > 0) {
			//$tmpobject->build
			//$tmpobject->setDocModel($user, GETPOST('model', 'alpha'));
			$outputlangs = $langs;
			$newlang = '';

			if (!empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			}

			$hidedetails = 0;
			$hidedesc = 0;
			$hideref = 0;
			$moreparams = null;

			$result = 0;
			if (!empty($argv[2]) && $argv[2] == 'confirm') {
				$result = $tmpobject->generateDocument($tmpobject->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
			}
			if ($result < 0) {
				$nbko++;
			}
			if ($result == 0) {
				print 'File for ref '.$tmpobject->ref.' returned 0 during regeneration with template '.$tmpobject->model_pdf."\n";
				$nbok++;
			} else {
				print 'File for ref '.$tmpobject->ref.' regenerated with template '.$tmpobject->model_pdf."\n";
				$nbok++;
			}
		} else {
			$nbko++;
		}
	}
}

print $nbok." objects processed\n";
print $nbko." objects with errors\n";

$db->close(); // Close $db database opened handler

exit($error);
