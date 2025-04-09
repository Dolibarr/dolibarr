#!/usr/bin/env php
<?php
/* Copyright (C) 2020       Laurent Destailleur     <eldy@users.sourceforge.net>
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
 * \file scripts/emailings/reset-invalid-emails.php
 * \ingroup 	mailing
 * \brief 		Script to reset (set email to empty) from a list of email
 */

if (!defined('NOSESSION')) {
	define('NOSESSION', '1');
}
if (!defined('MAXEMAILS')) {
	define('MAXEMAILS', 100);
}

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = __DIR__.'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(1);
}

if (!isset($argv[3]) || !$argv[3]) {
	print "Usage: ".$script_file." inputfile-with-invalid-emails type [test|confirm]\n";
	print "- inputfile-with-invalid-emails is a file with list of invalid email\n";
	print "- type can be 'all' or 'thirdparties', 'contacts', 'members', 'users'\n";
	exit(1);
}
$fileofinvalidemail = $argv[1];
$type = $argv[2];
$mode = $argv[3];

require_once $path."../../htdocs/master.inc.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/functionscli.lib.php';
require_once DOL_DOCUMENT_ROOT."/core/class/CMailFile.class.php";
require_once DOL_DOCUMENT_ROOT."/comm/mailing/class/mailing.class.php";
/**
 * @var DoliDB $db
 * @var HookManager $hookmanager
 *
 * @var int $dolibarr_main_db_readonly
 */
// Global variables
$version = DOL_VERSION;
$error = 0;

if (!isModEnabled('mailing')) {
	print 'Module Emailing not enabled';
	exit(1);
}

$hookmanager->initHooks(array('cli'));


/*
 * Main
 */

$user = new User($db);

@set_time_limit(0);
print "***** ".$script_file." (".$version.") pid=".dol_getmypid()." *****\n";

if (!in_array($type, array('all', 'thirdparties', 'contacts', 'users', 'members'))) {
	print "Bad value for parameter type.\n";
	exit(1);
}

if (!empty($dolibarr_main_db_readonly)) {
	print "Error: instance in read-onyl mode\n";
	exit(1);
}

$db->begin();


$myfile = fopen($fileofinvalidemail, "r");
if (!$myfile) {
	echo "Failed to open file";
	exit(1);
}

$tmp = 1;
$counter = 1;
$numerasedtotal = 0;

while ($tmp != null) {
	$groupofemails = array();
	for ($i = 0; $i < MAXEMAILS; $i++) {
		$tmp = fgets($myfile);
		if ($tmp == null) {
			break;
		}
		$groupofemails[$i] = trim($tmp, "\n");
	}

	// Generate the string tp allow a mass update (with a limit of MAXEMAILS per request).
	$emailsin = '';
	foreach ($groupofemails as $email) {
		$emailsin .= ($emailsin ? ", " : "")."'".$db->escape($email)."'";
	}

	// For each groupofemail, we update tables to set email field to empty
	$nbingroup = count($groupofemails);

	print "Process group of ".$nbingroup." emails (".$counter." - ".($counter + $nbingroup - 1)."), type = ".$type."\n";

	$numerased = 0;

	$sql_base = "UPDATE ".MAIN_DB_PREFIX;

	if ($type == 'all' || $type == 'users') {
		// Loop on each record and update the email to null if email into $groupofemails
		$sql = $sql_base."user as u SET u.email = NULL WHERE u.email IN (".$db->sanitize($emailsin, 1).");";
		print "Try to update users, ";
		$resql = $db->query($sql);
		if (!$resql) {
			dol_print_error($db);
		}
		$numerased += $db->affected_rows($resql);
	}

	if ($type == 'all' || $type == 'thirdparties') {
		// Loop on each record and update the email to null if email into $groupofemails
		$sql = $sql_base."societe as s SET s.email = NULL WHERE s.email IN (".$db->sanitize($emailsin, 1).");";
		print "Try to update thirdparties, ";
		$resql = $db->query($sql);
		if (!$resql) {
			dol_print_error($db);
		}
		$numerased += $db->affected_rows($resql);
	}

	if ($type == 'all' || $type == 'contacts') {
		// Loop on each record and update the email to null if email into $groupofemails

		$sql = $sql_base."socpeople as s SET s.email = NULL WHERE s.email IN (".$db->sanitize($emailsin, 1).");";
		print "Try to update contacts, ";
		$resql = $db->query($sql);
		if (!$resql) {
			dol_print_error($db);
		}
		$numerased += $db->affected_rows($resql);
	}

	if ($type == 'all' || $type == 'members') {
		// Loop on each record and update the email to null if email into $groupofemails

		$sql = $sql_base."adherent as a SET a.email = NULL WHERE a.email IN (".$db->sanitize($emailsin, 1).");";
		print "Try to update members, ";
		$resql = $db->query($sql);
		if (!$resql) {
			dol_print_error($db);
		}
		$numerased += $db->affected_rows($resql);
	}

	$numerasedtotal += $numerased;

	print $numerased." emails cleared.\n";
	$counter = $counter + $nbingroup;
}

if (!$error && $mode == 'confirm') {
	print "Commit - ".$numerasedtotal." operations validated.\n";
	$db->commit();
} else {
	print "Rollback - ".$numerasedtotal." Operations canceled.\n";
	$db->rollback();
}

exit($error);
