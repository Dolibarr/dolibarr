#!/usr/bin/env php
<?php
/*
 * Copyright (C) 2007-2016 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2015 Jean Heimburger <http://tiaris.eu>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 * \file scripts/user/migrate_picture_path.php
 * \ingroup scripts
 * \brief Migrate pictures from old system prior to 3.7 to new path for 3.7+
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/functionscli.lib.php';
require_once DOL_DOCUMENT_ROOT."/user/class/user.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";
// After this $db, $mysoc, $langs, $conf and $hookmanager are defined (Opened $db handler to database will be closed at end of file).
// $user is created but empty.
/**
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
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

print "***** ".$script_file." (".$version.") pid=".dol_getmypid()." *****\n";
dol_syslog($script_file." launched with arg ".join(',', $argv));

if (!isset($argv[1]) || $argv[1] != 'user') {
	print "Usage:  $script_file user\n";
	exit(1);
}

print '--- start'."\n";

// Case to migrate products path
if ($argv[1] == 'user') {
	$u = new User($db);

	$sql = "SELECT rowid as uid from ".MAIN_DB_PREFIX."user"; // Get list of all products
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$u->fetch($obj->uid);
			print " migrating user id=".$u->id." ref=".$u->ref."\n";
			migrate_user_filespath($u);
		}
	} else {
		print "\n sql error ".$sql;
		exit();
	}
}

$db->close(); // Close $db database opened handler

exit($error);


/**
 * Migrate file from old path to new one for user $u
 *
 * @param 	User $u		Object user
 * @return 	void
 */
function migrate_user_filespath($u)
{
	global $conf;

	// Les fichiers joints des users sont toujours sur l'entité 1
	$dir = $conf->user->dir_output;
	$origin = $dir.'/'.get_exdir($u->id, 2, 0, 0, $u, 'user');
	$destin = $dir.'/'.$u->id;

	$error = 0;

	$origin_osencoded = dol_osencode($origin);
	$destin_osencoded = dol_osencode($destin);
	dol_mkdir($destin);

	if (dol_is_dir($origin)) {
		$handle = opendir($origin_osencoded);
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				if ($file != '.' && $file != '..' && is_dir($origin_osencoded.'/'.$file)) {
					$thumbs = opendir($origin_osencoded.'/'.$file);
					if (is_resource($thumbs)) {
						dol_mkdir($destin.'/'.$file);
						while (($thumb = readdir($thumbs)) !== false) {
							dol_move($origin.'/'.$file.'/'.$thumb, $destin.'/'.$file.'/'.$thumb);
						}
						// dol_delete_dir($origin.'/'.$file);
					}
				} else {
					if (dol_is_file($origin.'/'.$file)) {
						dol_move($origin.'/'.$file, $destin.'/'.$file);
					}
				}
			}
		}
	}
}
