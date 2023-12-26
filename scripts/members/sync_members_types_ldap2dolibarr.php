#!/usr/bin/env php
<?php
/**
 * Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2012 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2013 Maxime Kohlhaas <maxime@atm-consulting.fr>
 * Copyright (C) 2017 Regis Houssin <regis.houssin@inodbox.com>
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
 * \file 	scripts/members/sync_members_types_ldap2dolibarr.php
 * \ingroup ldap member
 * \brief 	Script to update members types into Dolibarr from LDAP
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
	exit(-1);
}

require_once $path."../../htdocs/master.inc.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/class/ldap.class.php";
require_once DOL_DOCUMENT_ROOT."/adherents/class/adherent_type.class.php";

$langs->loadLangs(array("main", "errors"));

// Global variables
$version = constant('DOL_VERSION');
$error = 0;
$forcecommit = 0;
$confirmed = 0;

/*
 * Main
 */

@set_time_limit(0);
print "***** ".$script_file." (".$version.") pid=".dol_getmypid()." *****\n";
dol_syslog($script_file." launched with arg ".join(',', $argv));

// List of fields to get from LDAP
$required_fields = array(getDolGlobalString('LDAP_KEY_MEMBERS_TYPES'), getDolGlobalString('LDAP_MEMBER_TYPE_FIELD_FULLNAME'), getDolGlobalString('LDAP_MEMBER_TYPE_FIELD_DESCRIPTION'), getDolGlobalString('LDAP_MEMBER_TYPE_FIELD_GROUPMEMBERS'));

// Remove from required_fields all entries not configured in LDAP (empty) and duplicated
$required_fields = array_unique(array_values(array_filter($required_fields, "dolValidElementType")));

if (!isset($argv[1])) {
	// print "Usage: $script_file (nocommitiferror|commitiferror) [id_group]\n";
	print "Usage:  $script_file (nocommitiferror|commitiferror) [--server=ldapserverhost] [--excludeuser=user1,user2...] [-y]\n";
	exit(-1);
}

foreach ($argv as $key => $val) {
	if ($val == 'commitiferror') {
		$forcecommit = 1;
	}
	if (preg_match('/--server=([^\s]+)$/', $val, $reg)) {
		$conf->global->LDAP_SERVER_HOST = $reg[1];
	}
	if (preg_match('/--excludeuser=([^\s]+)$/', $val, $reg)) {
		$excludeuser = explode(',', $reg[1]);
	}
	if (preg_match('/-y$/', $val, $reg)) {
		$confirmed = 1;
	}
}

if (!empty($dolibarr_main_db_readonly)) {
	print "Error: instance in read-onyl mode\n";
	exit(-1);
}

print "Mails sending disabled (useless in batch mode)\n";
$conf->global->MAIN_DISABLE_ALL_MAILS = 1; // On bloque les mails
print "\n";
print "----- Synchronize all records from LDAP database:\n";
print "host=" . getDolGlobalString('LDAP_SERVER_HOST')."\n";
print "port=" . getDolGlobalString('LDAP_SERVER_PORT')."\n";
print "login=" . getDolGlobalString('LDAP_ADMIN_DN')."\n";
print "pass=".preg_replace('/./i', '*', getDolGlobalString('LDAP_ADMIN_PASS'))."\n";
print "DN to extract=" . getDolGlobalString('LDAP_MEMBER_TYPE_DN')."\n";
print 'Filter=(' . getDolGlobalString('LDAP_KEY_MEMBERS_TYPES').'=*)'."\n";
print "----- To Dolibarr database:\n";
print "type=".$conf->db->type."\n";
print "host=".$conf->db->host."\n";
print "port=".$conf->db->port."\n";
print "login=".$conf->db->user."\n";
print "database=".$conf->db->name."\n";
print "----- Options:\n";
print "commitiferror=".$forcecommit."\n";
print "Mapped LDAP fields=".join(',', $required_fields)."\n";
print "\n";

if (!$confirmed) {
	print "Hit Enter to continue or CTRL+C to stop...\n";
	$input = trim(fgets(STDIN));
}

if (!getDolGlobalString('LDAP_MEMBER_TYPE_DN')) {
	print $langs->trans("Error").': '.$langs->trans("LDAP setup for members types not defined inside Dolibarr");
	exit(-1);
}

$ldap = new Ldap();
$result = $ldap->connect_bind();
if ($result >= 0) {
	$justthese = array();

	// We disable synchro Dolibarr-LDAP
	$conf->global->LDAP_MEMBER_TYPE_ACTIVE = 0;

	$ldaprecords = $ldap->getRecords('*', getDolGlobalString('LDAP_MEMBER_TYPE_DN'), getDolGlobalString('LDAP_KEY_MEMBERS_TYPES'), $required_fields, 0, array(getDolGlobalString('LDAP_MEMBER_TYPE_FIELD_GROUPMEMBERS')));
	if (is_array($ldaprecords)) {
		$db->begin();

		// Warning $ldapuser has a key in lowercase
		foreach ($ldaprecords as $key => $ldapgroup) {
			$membertype = new AdherentType($db);
			$membertype->fetch($ldapgroup[getDolGlobalString('LDAP_KEY_MEMBERS_TYPES')]);
			$membertype->label = $ldapgroup[getDolGlobalString('LDAP_MEMBER_TYPE_FIELD_FULLNAME')];
			$membertype->description = $ldapgroup[getDolGlobalString('LDAP_MEMBER_TYPE_FIELD_DESCRIPTION')];
			$membertype->entity = $conf->entity;

			// print_r($ldapgroup);

			if ($membertype->id > 0) { // Member type update
				print $langs->transnoentities("MemberTypeUpdate").' # '.$key.': name='.$membertype->label;
				$res = $membertype->update($user);

				if ($res > 0) {
					print ' --> Updated member type id='.$membertype->id.' name='.$membertype->label;
				} else {
					$error++;
					print ' --> '.$res.' '.$membertype->error;
				}
				print "\n";
			} else { // Member type creation
				print $langs->transnoentities("MemberTypeCreate").' # '.$key.': name='.$membertype->label;
				$res = $membertype->create($user);

				if ($res > 0) {
					print ' --> Created member type id='.$membertype->id.' name='.$membertype->label;
				} else {
					$error++;
					print ' --> '.$res.' '.$membertype->error;
				}
				print "\n";
			}

			// print_r($membertype);
		}

		if (!$error || $forcecommit) {
			if (!$error) {
				print $langs->transnoentities("NoErrorCommitIsDone")."\n";
			} else {
				print $langs->transnoentities("ErrorButCommitIsDone")."\n";
			}
			$db->commit();
		} else {
			print $langs->transnoentities("ErrorSomeErrorWereFoundRollbackIsDone", $error)."\n";
			$db->rollback();
		}
		print "\n";
	} else {
		dol_print_error('', $ldap->error);
		$error++;
	}
} else {
	dol_print_error('', $ldap->error);
	$error++;
}

exit($error);


/**
 * Function to say if a value is empty or not
 *
 * @param 	string 	$element	Value to test
 * @return 	boolean 			True of false
 */
function dolValidElementType($element)
{
	return (trim($element) != '');
}
