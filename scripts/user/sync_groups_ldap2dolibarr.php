#!/usr/bin/env php
<?php
/**
 * Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2012 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2013 Maxime Kohlhaas <maxime@atm-consulting.fr>
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
 * \file scripts/user/sync_groups_ldap2dolibarr.php
 * \ingroup ldap member
 * \brief Script to update groups into Dolibarr from LDAP
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
require_once DOL_DOCUMENT_ROOT."/user/class/user.class.php";
require_once DOL_DOCUMENT_ROOT."/user/class/usergroup.class.php";

$langs->loadLangs(array("main", "errors"));

// Global variables
$version = DOL_VERSION;
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
$required_fields = array(getDolGlobalString('LDAP_KEY_GROUPS'), getDolGlobalString('LDAP_GROUP_FIELD_FULLNAME'), getDolGlobalString('LDAP_GROUP_FIELD_DESCRIPTION'), getDolGlobalString('LDAP_GROUP_FIELD_GROUPMEMBERS'));

// Remove from required_fields all entries not configured in LDAP (empty) and duplicated
$required_fields = array_unique(array_values(array_filter($required_fields, "dolValidElement")));

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

print "Mails sending disabled (useless in batch mode)\n";
$conf->global->MAIN_DISABLE_ALL_MAILS = 1; // On bloque les mails
print "\n";
print "----- Synchronize all records from LDAP database:\n";
print "host=" . getDolGlobalString('LDAP_SERVER_HOST')."\n";
print "port=" . getDolGlobalString('LDAP_SERVER_PORT')."\n";
print "login=" . getDolGlobalString('LDAP_ADMIN_DN')."\n";
print "pass=".preg_replace('/./i', '*', getDolGlobalString('LDAP_ADMIN_PASS'))."\n";
print "DN to extract=" . getDolGlobalString('LDAP_GROUP_DN')."\n";
if (getDolGlobalString('LDAP_GROUP_FILTER')) {
	print 'Filter=(' . getDolGlobalString('LDAP_GROUP_FILTER').')'."\n"; // Note: filter is defined into function getRecords
} else {
	print 'Filter=(' . getDolGlobalString('LDAP_KEY_GROUPS').'=*)'."\n";
}
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

if (!getDolGlobalString('LDAP_GROUP_DN')) {
	print $langs->trans("Error").': '.$langs->trans("LDAP setup for groups not defined inside Dolibarr");
	exit(-1);
}

$ldap = new Ldap();
$result = $ldap->connect_bind();
if ($result >= 0) {
	$justthese = array();

	// We disable synchro Dolibarr-LDAP
	$conf->global->LDAP_SYNCHRO_ACTIVE = 0;

	$ldaprecords = $ldap->getRecords('*', getDolGlobalString('LDAP_GROUP_DN'), getDolGlobalString('LDAP_KEY_GROUPS'), $required_fields, 'group', array(getDolGlobalString('LDAP_GROUP_FIELD_GROUPMEMBERS')));
	if (is_array($ldaprecords)) {
		$db->begin();

		// Warning $ldapuser has a key in lowercase
		foreach ($ldaprecords as $key => $ldapgroup) {
			$group = new UserGroup($db);
			$group->fetch('', $ldapgroup[getDolGlobalString('LDAP_KEY_GROUPS')]);
			$group->name = $ldapgroup[getDolGlobalString('LDAP_GROUP_FIELD_FULLNAME')];
			$group->nom = $group->name; // For backward compatibility
			$group->note = $ldapgroup[getDolGlobalString('LDAP_GROUP_FIELD_DESCRIPTION')];
			$group->entity = $conf->entity;

			// print_r($ldapgroup);

			if ($group->id > 0) { // Group update
				print $langs->transnoentities("GroupUpdate").' # '.$key.': name='.$group->name;
				$res = $group->update();

				if ($res > 0) {
					print ' --> Updated group id='.$group->id.' name='.$group->name;
				} else {
					$error++;
					print ' --> '.$res.' '.$group->error;
				}
				print "\n";
			} else { // Group creation
				print $langs->transnoentities("GroupCreate").' # '.$key.': name='.$group->name;
				$res = $group->create();

				if ($res > 0) {
					print ' --> Created group id='.$group->id.' name='.$group->name;
				} else {
					$error++;
					print ' --> '.$res.' '.$group->error;
				}
				print "\n";
			}

			// print_r($group);

			// Gestion des utilisateurs associés au groupe
			// 1 - Association des utilisateurs du groupe LDAP au groupe Dolibarr
			$userList = array();
			$userIdList = array();
			foreach ($ldapgroup[getDolGlobalString('LDAP_GROUP_FIELD_GROUPMEMBERS')] as $tmpkey => $userdn) {
				if ($tmpkey === 'count') {
					continue;
				}
				if (empty($userList[$userdn])) { // Récupération de l'utilisateur
					// Schéma rfc2307: les membres sont listés dans l'attribut memberUid sous form de login uniquement
					if (getDolGlobalString('LDAP_GROUP_FIELD_GROUPMEMBERS') === 'memberUid') {
						$userKey = array($userdn);
					} else { // Pour les autres schémas, les membres sont listés sous forme de DN complets
						$userFilter = explode(',', $userdn);
						$userKey = $ldap->getAttributeValues('('.$userFilter[0].')', getDolGlobalString('LDAP_KEY_USERS'));
					}
					if (!is_array($userKey)) {
						continue;
					}

					$fuser = new User($db);

					if (getDolGlobalString('LDAP_KEY_USERS') == getDolGlobalString('LDAP_FIELD_SID')) {
						$fuser->fetch('', '', $userKey[0]); // Chargement du user concerné par le SID
					} elseif (getDolGlobalString('LDAP_KEY_USERS') == getDolGlobalString('LDAP_FIELD_LOGIN')) {
						$fuser->fetch('', $userKey[0]); // Chargement du user concerné par le login
					}

					$userList[$userdn] = $fuser;
				} else {
					$fuser = &$userList[$userdn];
				}

				$userIdList[$userdn] = $fuser->id;

				// Ajout de l'utilisateur dans le groupe
				if (!in_array($fuser->id, array_keys($group->members))) {
					$fuser->SetInGroup($group->id, $group->entity);
					echo $fuser->login.' added'."\n";
				}
			}

			// 2 - Suppression des utilisateurs du groupe Dolibarr qui ne sont plus dans le groupe LDAP
			foreach ($group->members as $guser) {
				if (!in_array($guser->id, $userIdList)) {
					$guser->RemoveFromGroup($group->id, $group->entity);
					echo $guser->login.' removed'."\n";
				}
			}
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
 * @param	string 	$element		Value to test
 * @return 	boolean 				True of false
 */
function dolValidElement($element)
{
	return (trim($element) != '');
}
