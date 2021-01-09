#!/usr/bin/env php
<?php
/*
 * Copyright (C) 2007-2016 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * \file scripts/product/migrate_picture_path.php
 * \ingroup scripts
 * \brief Migrate pictures from old system prior to 3.7 to new path for 3.7+
 */

if (!defined('NOSESSION')) define('NOSESSION', '1');

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = __DIR__.'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(-1);
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

print "***** ".$script_file." (".$version.") pid=".dol_getmypid()." *****\n";
dol_syslog($script_file." launched with arg ".join(',', $argv));

if (empty($argv[1])) {
	print "Usage:    $script_file  subdirtoscan\n";
	print "Example:  $script_file  produit\n";
	exit(-1);
}

print '--- start'."\n";

$dir = DOL_DATA_ROOT;
$subdir = $argv[1];
if (empty($dir) || empty($subdir)) {
	dol_print_error('', 'dir not defined');
	exit(1);
}
if (!dol_is_dir($dir.'/'.$subdir)) {
	print 'Directory '.$dir.'/'.$subdir.' not found.'."\n";
	exit(2);
}

$filearray = dol_dir_list($dir.'/'.$subdir, "directories", 0, '', 'temp$');

global $maxwidthsmall, $maxheightsmall, $maxwidthmini, $maxheightmini;

foreach ($filearray as $keyf => $valf) {
	$ref = basename($valf['name']);
	$filearrayimg = dol_dir_list($valf['fullname'], "files", 0, '(\.gif|\.png|\.jpg|\.jpeg|\.bmp)$', '(\.meta|_preview.*\.png)$');
	foreach ($filearrayimg as $keyi => $vali) {
		print 'Process image for ref '.$ref.' : '.$vali['name']."\n";

		// Create small thumbs for image
		// Used on logon for example
		$imgThumbSmall = vignette($vali['fullname'], $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
		if (preg_match('/Error/', $imgThumbSmall))
			print $imgThumbSmall."\n";

		// Create mini thumbs for image (Ratio is near 16/9)
		// Used on menu or for setup page for example
		$imgThumbMini = vignette($vali['fullname'], $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");
		if (preg_match('/Error/', $imgThumbMini))
			print $imgThumbMini."\n";
	}
}

$db->close(); // Close $db database opened handler

exit($error);
