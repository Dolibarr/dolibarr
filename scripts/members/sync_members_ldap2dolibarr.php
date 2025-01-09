#!/usr/bin/env php
<?php
/**
 * Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2015 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * \file scripts/members/sync_members_ldap2dolibarr.php
 * \ingroup ldap member
 * \brief Script de mise a jour des adherents dans Dolibarr depuis LDAP
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/functionscli.lib.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/class/ldap.class.php";
require_once DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php";
require_once DOL_DOCUMENT_ROOT."/adherents/class/subscription.class.php";

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 */

$langs->loadLangs(array("main", "errors"));

// Global variables
$version = constant('DOL_VERSION');
$error = 0;
$forcecommit = 0;
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
	$conf->global->LDAP_KEY_MEMBERS,
	$conf->global->LDAP_FIELD_FULLNAME,
	$conf->global->LDAP_FIELD_LOGIN,
	$conf->global->LDAP_FIELD_LOGIN_SAMBA,
	$conf->global->LDAP_FIELD_PASSWORD,
	$conf->global->LDAP_FIELD_PASSWORD_CRYPTED,
	$conf->global->LDAP_FIELD_NAME,
	$conf->global->LDAP_FIELD_FIRSTNAME,
	$conf->global->LDAP_FIELD_MAIL,
	$conf->global->LDAP_FIELD_PHONE,
	$conf->global->LDAP_FIELD_PHONE_PERSO,
	$conf->global->LDAP_FIELD_MOBILE,
	$conf->global->LDAP_FIELD_FAX,
	$conf->global->LDAP_FIELD_ADDRESS,
	$conf->global->LDAP_FIELD_ZIP,
	$conf->global->LDAP_FIELD_TOWN,
	$conf->global->LDAP_FIELD_COUNTRY,
	$conf->global->LDAP_FIELD_DESCRIPTION,
	$conf->global->LDAP_FIELD_BIRTHDATE,
	$conf->global->LDAP_FIELD_MEMBER_STATUS,
	$conf->global->LDAP_FIELD_MEMBER_END_LASTSUBSCRIPTION,
	// Subscriptions
	$conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE,
	$conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_AMOUNT,
	$conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_DATE,
	$conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_AMOUNT
);

// Remove from required_fields all entries not configured in LDAP (empty) and duplicated
$required_fields = array_unique(array_values(array_filter($required_fields, "dolValidElement")));

if (!isset($argv[2]) || !is_numeric($argv[2])) {
	print "Usage:  $script_file (nocommitiferror|commitiferror) id_member_type  [--server=ldapserverhost] [-y]\n";
	exit(1);
}

$typeid = (int) $argv[2];
foreach ($argv as $key => $val) {
	if ($val == 'commitiferror') {
		$forcecommit = 1;
	}
	if (preg_match('/--server=([^\s]+)$/', $val, $reg)) {
		$conf->global->LDAP_SERVER_HOST = $reg[1];
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
print "DN to extract=" . getDolGlobalString('LDAP_MEMBER_DN')."\n";
if (getDolGlobalString('LDAP_MEMBER_FILTER')) {
	print 'Filter=(' . getDolGlobalString('LDAP_MEMBER_FILTER').')'."\n"; // Note: filter is defined into function getRecords
} else {
	print 'Filter=(' . getDolGlobalString('LDAP_KEY_MEMBERS').'=*)'."\n";
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

// Check parameters
if (!getDolGlobalString('LDAP_MEMBER_DN')) {
	print $langs->trans("Error").': '.$langs->trans("LDAP setup for members not defined inside Dolibarr")."\n";
	exit(1);
}
if ($typeid <= 0) {
	print $langs->trans("Error").': Parameter id_member_type is not a valid ref of an existing member type'."\n";
	exit(-2);
}

if (!empty($dolibarr_main_db_readonly)) {
	print "Error: instance in read-onyl mode\n";
	exit(1);
}

if (!$confirmed) {
	print "Hit Enter to continue or CTRL+C to stop...\n";
	$input = trim(fgets(STDIN));
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
	$pricefirst = 0;
	$pricelast = 0;
	// We disable synchro Dolibarr-LDAP
	$conf->global->LDAP_MEMBER_ACTIVE = 0;

	$ldaprecords = $ldap->getRecords('*', getDolGlobalString('LDAP_MEMBER_DN'), getDolGlobalString('LDAP_KEY_MEMBERS'), $required_fields, 'member'); // Filter on 'member' filter param
	if (is_array($ldaprecords)) {
		$db->begin();

		// Warning $ldapuser has a key in lowercase
		foreach ($ldaprecords as $key => $ldapuser) {
			$member = new Adherent($db);

			// Propriete membre
			$member->firstname = $ldapuser[getDolGlobalString('LDAP_FIELD_FIRSTNAME')];
			$member->lastname = $ldapuser[getDolGlobalString('LDAP_FIELD_NAME')];
			$member->login = $ldapuser[getDolGlobalString('LDAP_FIELD_LOGIN')];
			$member->pass = $ldapuser[getDolGlobalString('LDAP_FIELD_PASSWORD')];

			// $member->societe;
			$member->address = $ldapuser[getDolGlobalString('LDAP_FIELD_ADDRESS')];
			$member->zip = $ldapuser[getDolGlobalString('LDAP_FIELD_ZIP')];
			$member->town = $ldapuser[getDolGlobalString('LDAP_FIELD_TOWN')];
			$member->country = $ldapuser[getDolGlobalString('LDAP_FIELD_COUNTRY')];
			$member->country_id = $countries[$hashlib2rowid[strtolower($member->country)]]['rowid'];
			$member->country_code = $countries[$hashlib2rowid[strtolower($member->country)]]['code'];

			$member->phone = $ldapuser[getDolGlobalString('LDAP_FIELD_PHONE')];
			$member->phone_perso = $ldapuser[getDolGlobalString('LDAP_FIELD_PHONE_PERSO')];
			$member->phone_mobile = $ldapuser[getDolGlobalString('LDAP_FIELD_MOBILE')];
			$member->email = $ldapuser[getDolGlobalString('LDAP_FIELD_MAIL')];

			$member->note = $ldapuser[getDolGlobalString('LDAP_FIELD_DESCRIPTION')];
			$member->morphy = 'phy';
			$member->photo = '';
			$member->public = 1;
			$member->birth = dol_stringtotime($ldapuser[getDolGlobalString('LDAP_FIELD_BIRTHDATE')]);

			$member->statut = -1;
			if (isset($ldapuser[getDolGlobalString('LDAP_FIELD_MEMBER_STATUS')])) {
				$member->datec = dol_stringtotime($ldapuser[getDolGlobalString('LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE')]);
				$member->datevalid = dol_stringtotime($ldapuser[getDolGlobalString('LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE')]);
				$member->statut = (int) $ldapuser[getDolGlobalString('LDAP_FIELD_MEMBER_STATUS')];
			}
			// if ($member->statut > 1) $member->statut=1;

			// print_r($ldapuser);

			// Propriete type membre
			$member->typeid = $typeid;

			// Creation membre
			print $langs->transnoentities("MemberCreate").' # '.$key.': login='.$member->login.', fullname='.$member->getFullName($langs);
			print ', datec='.$member->datec;
			$member_id = $member->create($user);
			if ($member_id > 0) {
				print ' --> Created member id='.$member_id.' login='.$member->login;
			} else {
				$error++;
				print ' --> '.$member->error;
			}
			print "\n";

			// print_r($member);

			$datefirst = '';
			if (getDolGlobalString('LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE')) {
				$datefirst = dol_stringtotime($ldapuser[getDolGlobalString('LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE')]);
				$pricefirst = price2num($ldapuser[getDolGlobalString('LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_AMOUNT')]);
			}

			$datelast = '';
			if (getDolGlobalString('LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_DATE')) {
				$datelast = dol_stringtotime($ldapuser[getDolGlobalString('LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_DATE')]);
				$pricelast = price2num($ldapuser[getDolGlobalString('LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_AMOUNT')]);
			} elseif (getDolGlobalString('LDAP_FIELD_MEMBER_END_LASTSUBSCRIPTION')) {
				$datelast = dol_time_plus_duree(dol_stringtotime($ldapuser[getDolGlobalString('LDAP_FIELD_MEMBER_END_LASTSUBSCRIPTION')]), -1, 'y') + 60 * 60 * 24;
				$pricelast = price2num($ldapuser[getDolGlobalString('LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_AMOUNT')]);

				// Cas special ou date derniere <= date premiere
				if ($datefirst && $datelast && $datelast <= $datefirst) {
					// On ne va inserer que la premiere
					$datelast = 0;
					if (!$pricefirst && $pricelast) {
						$pricefirst = $pricelast;
					}
				}
			}

			// Insert first subscription
			if ($datefirst) {
				// Cree premiere cotisation et met a jour datefin dans adherent
				// print "xx".$datefirst."\n";
				$crowid = $member->subscription($datefirst, $pricefirst, 0);
			}

			// Insert last subscription
			if ($datelast) {
				// Cree derniere cotisation et met a jour datefin dans adherent
				// print "yy".dol_print_date($datelast)."\n";
				$crowid = $member->subscription($datelast, $pricelast, 0);
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
 * @param 	string $element	Value to test
 * @return 	boolean 		True of false
 */
function dolValidElement($element)
{
	return (trim($element) != '');
}
