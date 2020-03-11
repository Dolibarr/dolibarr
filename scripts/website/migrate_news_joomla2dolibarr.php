#!/usr/bin/env php
<?php
/* Copyright (C) 2007-2016 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * \file scripts/website/migrate_newsèjoomla2dolibarr.php
 * \ingroup scripts
 * \brief Migrate news from a Joomla databse into a Dolibarr website
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = __DIR__ . '/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute " . $script_file . " from command line, you must use PHP for CLI mode.\n";
	exit(- 1);
}

@set_time_limit(0); // No timeout for this script
define('EVEN_IF_ONLY_LOGIN_ALLOWED', 1); // Set this define to 0 if you want to lock your script when dolibarr setup is "locked to admin user only".

$error = 0;

if (empty($argv[3]) || ! in_array($argv[1], array('test','confirm'))) {
	print "Usage: $script_file (test|confirm) website login:pass@serverjoomla/tableprefix/databasejoomla\n";
	print "\n";
	print "Load joomla news and create them into Dolibarr database (if they don't alreay exist).\n";
	exit(- 1);
}

$mode = $argv[1];
$website = $argv[2];
$joomlaserverinfo = $argv[3];

require $path . "../../htdocs/master.inc.php";

$langs->load('main');

$joomlaserverinfoarray = preg_split('/(:|@|\/)/', $joomlaserverinfo);
$joomlalogin = $joomlaserverinfoarray[0];
$joomlapass = $joomlaserverinfoarray[1];
$joomlahost = $joomlaserverinfoarray[2];
$joomlaprefix = $joomlaserverinfoarray[3];
$joomladatabase = $joomlaserverinfoarray[4];
$joomlaport = 3306;


$dbjoomla=getDoliDBInstance('mysqli', $joomlahost, $joomlalogin, $joomlapass, $joomladatabase, $joomlaport);
if ($dbjoomla->error)
{
	dol_print_error($dbjoomla,"host=".$joomlahost.", port=".$joomlaport.", user=".$joomlalogin.", databasename=".$joomladatabase.", ".$dbjoomla->error);
	exit(-1);
}

$sql = 'SELECT id, title, alias, created, introtext, `fulltext` FROM '.$joomlaprefix.'_content WHERE 1 = 1';
$resql = $dbjoomla->query($sql);

if (! $resql) {
	dol_print_error($dbjoomla);
	exit;
}

while ($obj = $dbjoomla->fetch_object($resql)) {
	$i = 0;
	if ($obj) {
		$i++;
		$id = $obj->id;
		$title = $obj->title;
		$alias = $obj->alias;
		$description = dol_string_nohtmltag($obj->introtext);
		$hmtltext = $obj->fulltext;

		print $i.' '.$id.' '.$title."\n";
	}
}

exit($error);
