#!/usr/bin/env php
<?php
/**
 * Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2012 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * \file scripts/user/sync_users_ldap2dolibarr.php
 * \ingroup ldap member
 * \brief Script to update users into Dolibarr from LDAP
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

require_once $path."../../htdocs/master.inc.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/class/ldap.class.php";
require_once DOL_DOCUMENT_ROOT."/user/class/user.class.php";

$langs->loadLangs(array("main", "errors"));

// Global variables
$version = DOL_VERSION;
$error = 0;
$forcecommit = 0;
$excludeuser = array();
$confirmed = 0;

$hookmanager->initHooks(array('cli'));


/*
 * Main
 */

@set_time_limit(0);
print "***** ".$script_file." (".$version.") pid=".dol_getmypid()." *****\n";
dol_syslog($script_file." launched with arg ".join(',', $argv));

// List of fields to get from LDAP
$required_fields = array(
	$conf->global->LDAP_KEY_USERS,
	$conf->global->LDAP_FIELD_FULLNAME,
	$conf->global->LDAP_FIELD_NAME,
	$conf->global->LDAP_FIELD_FIRSTNAME,
	$conf->global->LDAP_FIELD_LOGIN,
	$conf->global->LDAP_FIELD_LOGIN_SAMBA,
	$conf->global->LDAP_FIELD_PASSWORD,
	$conf->global->LDAP_FIELD_PASSWORD_CRYPTED,
	$conf->global->LDAP_FIELD_PHONE,
	$conf->global->LDAP_FIELD_FAX,
	$conf->global->LDAP_FIELD_MOBILE,
	// $conf->global->LDAP_FIELD_ADDRESS,
	// $conf->global->LDAP_FIELD_ZIP,
	// $conf->global->LDAP_FIELD_TOWN,
	// $conf->global->LDAP_FIELD_COUNTRY,
	$conf->global->LDAP_FIELD_MAIL,
	$conf->global->LDAP_FIELD_TITLE,
	$conf->global->LDAP_FIELD_DESCRIPTION,
	$conf->global->LDAP_FIELD_SID
);

// Remove from required_fields all entries not configured in LDAP (empty) and duplicated
$required_fields = array_unique(array_values(array_filter($required_fields, "dolValidElement")));

if (!isset($argv[1])) {
	print "Usage:  $script_file (nocommitiferror|commitiferror) [--server=ldapserverhost] [--excludeuser=user1,user2...] [-y]\n";
	exit(1);
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

print "Mails sending disabled (useless in batch mode)\n";
$conf->global->MAIN_DISABLE_ALL_MAILS = 1; // On bloque les mails
print "\n";
print "----- Synchronize all records from LDAP database:\n";
print "host=" . getDolGlobalString('LDAP_SERVER_HOST')."\n";
print "port=" . getDolGlobalString('LDAP_SERVER_PORT')."\n";
print "login=" . getDolGlobalString('LDAP_ADMIN_DN')."\n";
print "pass=".preg_replace('/./i', '*', getDolGlobalString('LDAP_ADMIN_PASS'))."\n";
print "DN to extract=" . getDolGlobalString('LDAP_USER_DN')."\n";
if (getDolGlobalString('LDAP_FILTER_CONNECTION')) {
	print 'Filter=(' . getDolGlobalString('LDAP_FILTER_CONNECTION').')'."\n"; // Note: filter is defined into function getRecords
} else {
	print 'Filter=(' . getDolGlobalString('LDAP_KEY_USERS').'=*)'."\n";
}
print "----- To Dolibarr database:\n";
print "type=".$conf->db->type."\n";
print "host=".$conf->db->host."\n";
print "port=".$conf->db->port."\n";
print "login=".$conf->db->user."\n";
print "database=".$conf->db->name."\n";
print "----- Options:\n";
print "commitiferror=".$forcecommit."\n";
print "excludeuser=".join(',', $excludeuser)."\n";
print "Mapped LDAP fields=".join(',', $required_fields)."\n";
print "\n";

if (!$confirmed) {
	print "Hit Enter to continue or CTRL+C to stop...\n";
	$input = trim(fgets(STDIN));
}

if (!getDolGlobalString('LDAP_USER_DN')) {
	print $langs->trans("Error").': '.$langs->trans("LDAP setup for users not defined inside Dolibarr");
	exit(1);
}

// Load table of correspondence of countries
$hashlib2rowid = array();
$countries = array();
$sql = "SELECT rowid, code, label, active";
$sql .= " FROM ".MAIN_DB_PREFIX."c_country";
$sql .= " WHERE active = 1";
$sql .= " ORDER BY code ASC";
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	if ($num) {
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				// print 'Load cache for country '.strtolower($obj->label).' rowid='.$obj->rowid."\n";
				$hashlib2rowid[strtolower($obj->label)] = $obj->rowid;
				$countries[$obj->rowid] = array('rowid' => $obj->rowid, 'label' => $obj->label, 'code' => $obj->code);
			}
			$i++;
		}
	}
} else {
	dol_print_error($db);
	exit(1);
}

$ldap = new Ldap();
$result = $ldap->connectBind();
if ($result >= 0) {
	$justthese = array();

	// We disable synchro Dolibarr-LDAP
	$conf->global->LDAP_SYNCHRO_ACTIVE = 0;

	$ldaprecords = $ldap->getRecords('*', getDolGlobalString('LDAP_USER_DN'), getDolGlobalString('LDAP_KEY_USERS'), $required_fields, 'user'); // Filter on 'user' filter param
	if (is_array($ldaprecords)) {
		$db->begin();

		// Warning $ldapuser has a key in lowercase
		foreach ($ldaprecords as $key => $ldapuser) {
			// If login into exclude list, we discard record
			if (in_array($ldapuser[getDolGlobalString('LDAP_FIELD_LOGIN')], $excludeuser)) {
				print $langs->transnoentities("UserDiscarded").' # '.$key.': login='.$ldapuser[getDolGlobalString('LDAP_FIELD_LOGIN')].' --> Discarded'."\n";
				continue;
			}

			$fuser = new User($db);

			if (getDolGlobalString('LDAP_KEY_USERS') == getDolGlobalString('LDAP_FIELD_SID')) {
				$fuser->fetch('', '', $ldapuser[getDolGlobalString('LDAP_KEY_USERS')]); // Chargement du user concerné par le SID
			} elseif (getDolGlobalString('LDAP_KEY_USERS') == getDolGlobalString('LDAP_FIELD_LOGIN')) {
				$fuser->fetch('', $ldapuser[getDolGlobalString('LDAP_KEY_USERS')]); // Chargement du user concerné par le login
			}

			// Propriete membre
			$fuser->firstname = $ldapuser[getDolGlobalString('LDAP_FIELD_FIRSTNAME')];
			$fuser->lastname = $ldapuser[getDolGlobalString('LDAP_FIELD_NAME')];
			$fuser->login = $ldapuser[getDolGlobalString('LDAP_FIELD_LOGIN')];
			$fuser->pass = $ldapuser[getDolGlobalString('LDAP_FIELD_PASSWORD')];
			$fuser->pass_indatabase_crypted = $ldapuser[getDolGlobalString('LDAP_FIELD_PASSWORD_CRYPTED')];

			// $user->societe;
			/*
			 * $fuser->address=$ldapuser[getDolGlobalString('LDAP_FIELD_ADDRESS')];
			 * $fuser->zip=$ldapuser[getDolGlobalString('LDAP_FIELD_ZIP')];
			 * $fuser->town=$ldapuser[getDolGlobalString('LDAP_FIELD_TOWN')];
			 * $fuser->country=$ldapuser[getDolGlobalString('LDAP_FIELD_COUNTRY')];
			 * $fuser->country_id=$countries[$hashlib2rowid[strtolower($fuser->country)]]['rowid'];
			 * $fuser->country_code=$countries[$hashlib2rowid[strtolower($fuser->country)]]['code'];
			 */

			$fuser->office_phone = $ldapuser[getDolGlobalString('LDAP_FIELD_PHONE')];
			$fuser->user_mobile = $ldapuser[getDolGlobalString('LDAP_FIELD_MOBILE')];
			$fuser->office_fax = $ldapuser[getDolGlobalString('LDAP_FIELD_FAX')];
			$fuser->email = $ldapuser[getDolGlobalString('LDAP_FIELD_MAIL')];
			$fuser->ldap_sid = $ldapuser[getDolGlobalString('LDAP_FIELD_SID')];

			$fuser->job = $ldapuser[getDolGlobalString('LDAP_FIELD_TITLE')];
			$fuser->note = $ldapuser[getDolGlobalString('LDAP_FIELD_DESCRIPTION')];
			$fuser->admin = 0;
			$fuser->socid = 0;
			$fuser->contact_id = 0;
			$fuser->fk_member = 0;

			$fuser->statut = 1;
			// TODO : revoir la gestion du status
			/*
			 * if (isset($ldapuser[getDolGlobalString('LDAP_FIELD_MEMBER_STATUS')])) {
			 * $fuser->datec=dol_stringtotime($ldapuser[$conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE]);
			 * $fuser->datevalid=dol_stringtotime($ldapuser[$conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE]);
			 * $fuser->statut=$ldapuser[getDolGlobalString('LDAP_FIELD_MEMBER_STATUS')];
			 * }
			 */
			// if ($fuser->statut > 1) $fuser->statut=1;

			// print_r($ldapuser);

			if ($fuser->id > 0) { // User update
				print $langs->transnoentities("UserUpdate").' # '.$key.': login='.$fuser->login.', fullname='.$fuser->getFullName($langs);
				$res = $fuser->update($user);

				if ($res < 0) {
					$error++;
					print ' --> '.$res.' '.$fuser->error;
				} else {
					print ' --> Updated user id='.$fuser->id.' login='.$fuser->login;
				}
			} else { // User creation
				print $langs->transnoentities("UserCreate").' # '.$key.': login='.$fuser->login.', fullname='.$fuser->getFullName($langs);
				$res = $fuser->create($user);

				if ($res > 0) {
					print ' --> Created user id='.$fuser->id.' login='.$fuser->login;
				} else {
					$error++;
					print ' --> '.$res.' '.$fuser->error;
				}
			}
			print "\n";
			// print_r($fuser);

			// Management of the groups
			// TODO : Review the group management (or script for syncing groups)
			/*
			 * if(!$error) {
			 * foreach ($ldapuser[getDolGlobalString('LDAP_FIELD_USERGROUPS') as $groupdn) {
			 * $groupdn;
			 * }
			 * }
			 */
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
		dol_print_error(null, $ldap->error);
		$error++;
	}
} else {
	dol_print_error(null, $ldap->error);
	$error++;
}

exit($error);


/**
 * Function to say if a value is empty or not
 *
 * @param	string 	$element	Value to test
 * @return 	boolean 			True of false
 */
function dolValidElement($element)
{
	return (trim($element) != '');
}
