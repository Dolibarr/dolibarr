#!/usr/bin/env php
<?php
/**
 * Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * \file scripts/user/sync_users_dolibarr2ldap.php
 * \ingroup ldap core
 * \brief Script to update users in LDAP from Dolibarr DB
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

if (!isset($argv[1]) || !$argv[1]) {
	print "Usage: $script_file now\n";
	exit(1);
}
$now = $argv[1];

require_once $path."../../htdocs/master.inc.php";
require_once DOL_DOCUMENT_ROOT."/core/class/ldap.class.php";
require_once DOL_DOCUMENT_ROOT."/user/class/user.class.php";

// Global variables
$version = DOL_VERSION;
$error = 0;

$hookmanager->initHooks(array('cli'));


/*
 * Main
 */

@set_time_limit(0);
print "***** ".$script_file." (".$version.") pid=".dol_getmypid()." *****\n";
dol_syslog($script_file." launched with arg ".join(',', $argv));

/*
 * if (! getDolGlobalString('LDAP_SYNCHRO_ACTIVE')) {
 * print $langs->trans("LDAPSynchronizationNotSetupInDolibarr");
 * exit(1);
 * }
 */

$sql = "SELECT rowid";
$sql .= " FROM ".MAIN_DB_PREFIX."user";

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	$ldap = new Ldap();
	$ldap->connectBind();

	while ($i < $num) {
		$ldap->error = "";

		$obj = $db->fetch_object($resql);

		$fuser = new User($db);
		$fuser->fetch($obj->rowid);

		print $langs->trans("UpdateUser")." rowid=".$fuser->id." ".$fuser->getFullName($langs);

		$oldobject = $fuser;

		$oldinfo = $oldobject->_load_ldap_info();
		$olddn = $oldobject->_load_ldap_dn($oldinfo);

		$info = $fuser->_load_ldap_info();
		$dn = $fuser->_load_ldap_dn($info);

		$result = $ldap->add($dn, $info, $user); // Will fail if already exists
		$result = $ldap->update($dn, $info, $user, $olddn);
		if ($result > 0) {
			print " - ".$langs->trans("OK");
		} else {
			$error++;
			print " - ".$langs->trans("KO").' - '.$ldap->error;
		}
		print "\n";

		$i++;
	}

	$ldap->unbind();
} else {
	dol_print_error($db);
}

exit($error);
