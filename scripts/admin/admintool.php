#!/usr/bin/env php
<?php
/*
 * Copyright (C) 2012 Nicolas Villa aka Boyquotes http://informetic.fr
 * Copyright (C) 2013 Florian Henry <forian.henry@open-concept.pro
 * Copyright (C) 2013-2015 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2025		MDW						<mdeweerd@users.noreply.github.com>
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
 * \file scripts/tools/admintool.php
 * \ingroup core
 * \brief Execute some tools from commande line
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', '1');
}
if (!defined('NOSESSION')) {
	define('NOSESSION', '1');
}

// So log file will have a suffix
if (!defined('USESUFFIXINLOG')) {
	define('USESUFFIXINLOG', '_cron');
}

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = __DIR__.'/';

// Error if Web mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(1);
}

require_once $path."../../htdocs/master.inc.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/functionscli.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
require_once DOL_DOCUMENT_ROOT."/cron/class/cronjob.class.php";
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 *
 * @var string $dolibarr_main_db_pass
 */

// Check parameters
if (!isset($argv[1]) || !$argv[1]) {
	usageScript($path, $script_file);
	exit(1);
}
$key = $argv[1];

if (!in_array($key, array('showpass', 'encodepass', 'decodepass'))) {
	usageScript($path, $script_file);
	exit(1);
}


// Global variables
$version = DOL_VERSION;
$error = 0;

$hookmanager->initHooks(array('cli'));


/*
 * Main
 */

// current date
$now = dol_now();

@set_time_limit(0);
print "***** ".$script_file." (".$version.") pid=".dol_getmypid()." - ".dol_print_date($now, 'dayhourrfc', 'gmt')." - ".gethostname()." *****\n";

// Show TZ of the serveur when ran from command line.
//$ini_path = php_ini_loaded_file();
//print 'TZ server = '.getServerTimeZoneString()." - set in PHP ini ".$ini_path."\n";

if ($key == 'showpass') {
	print dolDecrypt($dolibarr_main_db_pass)."\n";
} elseif ($key == 'encodepass') {
	$result = encodedecode_dbpassconf(1);
	print $result;
} elseif ($key == 'decodepass') {
	$result = encodedecode_dbpassconf(0);
	print $result;
}

print "\n";

exit(0);


/**
 * Help for usageScript
 *
 * @param string $path				Path
 * @param string $script_file		Filename
 * @return void
 */
function usageScript($path, $script_file)
{
	print "Usage: ".$script_file." showpass|encodepass|decodepass\n";
}
