#!/usr/bin/env php
<?php
/* Copyright (C) 2020 Laurent Destailleur <eldy@users.sourceforge.net>
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

if (!defined('NOSESSION')) define('NOSESSION', '1');
if (!defined('MAXEMAILS')) define('MAXEMAILS', 100);

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = __DIR__.'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(-1);
}

if (!isset($argv[2]) || !$argv[2]) {
	print "Usage: ".$script_file." inputfile-with-invalid-emails type\n";
	print "- inputfile-with-invalid-emails is a file with list of invalid email\n";
	print "- type can be 'all' or 'thirdparties', 'contacts', 'members', 'users'\n";
	exit(-1);
}
$fileofinvalidemail = $argv[1];
$type = $argv[2];

require_once $path."../../htdocs/master.inc.php";
require_once DOL_DOCUMENT_ROOT."/core/class/CMailFile.class.php";
require_once DOL_DOCUMENT_ROOT."/comm/mailing/class/mailing.class.php";

// Global variables
$version = DOL_VERSION;
$error = 0;

/*
 * Main
 */

@set_time_limit(0);
print "***** ".$script_file." (".$version.") pid=".dol_getmypid()." *****\n";


$user = new User($db);
// for signature, we use user send as parameter
if (!empty($login))
	$user->fetch('', $login);


$db->begin();


$myfile = fopen($fileofinvalidemail, "r");
if (!$myfile)
{
	echo "Failed to open file";
	exit(-1);
}

$tmp = 1;
while ($tmp!=null)
{
	$groupofemails=array();
	for ($i=0; $i < MAXEMAILS; $i++)
	{
		$tmp =fgets($myfile);
		if ($tmp == null)
		{
			break;
		}
		$groupofemails[$i] = trim($tmp, "\n");
	}

	// For each groupofemail, we update tables to set email field to empty

	$sql_base = "UPDATE ".MAIN_DB_PREFIX;
	foreach ($groupofemails as $email)
	{
		if ($type == 'all' || $type == 'thirdparty')
		{
			// Loop on each record and update the email to null if email into $groupofemails

			$sql=$sql_base."societe as s SET s.email = NULL WHERE s.email = '".$db->escape($email)."';";
			$db->query($sql);
		}

		if ($type == 'all' || $type == 'contact')
		{
			// Loop on each record and update the email to null if email into $groupofemails

			$sql=$sql_base."socpeople as s SET s.email = NULL WHERE s.email = '".$db->escape($email)."';";
			$db->query($sql);
		}

		if ($type == 'all' || $type == 'user')
		{
			// Loop on each record and update the email to null if email into $groupofemails

			$sql=$sql_base."user as u SET u.email = NULL WHERE u.email = '".$db->escape($email)."';";
			$db->query($sql);
		}

		if ($type == 'all' || $type == 'member')
		{
			// Loop on each record and update the email to null if email into $groupofemails

			$sql=$sql_base."adherent as a SET a.email = NULL WHERE a.email = '".$db->escape($email)."';";
			$resql=$db->query($sql);
		}
		echo $email;
	}
}

if (!$error) {
	$db->commit();
} else {
	$db->rollback();
}

exit($error);
