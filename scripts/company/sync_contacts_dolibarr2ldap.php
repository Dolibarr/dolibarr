#!/usr/bin/env php
<?php
/*
 * Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2009 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * \file scripts/company/sync_contacts_dolibarr2ldap.php
 * \ingroup ldap company
 * \brief Script to update all contacts from Dolibarr into a LDAP database
 */
$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = __DIR__.'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(-1);
}

require_once $path."../../htdocs/master.inc.php";
require_once DOL_DOCUMENT_ROOT."/contact/class/contact.class.php";
require_once DOL_DOCUMENT_ROOT."/user/class/user.class.php";
require_once DOL_DOCUMENT_ROOT."/core/class/ldap.class.php";

// Global variables
$version = DOL_VERSION;
$error = 0;
$confirmed = 0;

/*
 * Main
 */

@set_time_limit(0);
print "***** ".$script_file." (".$version.") pid=".dol_getmypid()." *****\n";
dol_syslog($script_file." launched with arg ".join(',', $argv));

if (!isset($argv[1]) || !$argv[1]) {
	print "Usage: $script_file now [-y]\n";
	exit(-1);
}

foreach ($argv as $key => $val) {
	if (preg_match('/-y$/', $val, $reg))
		$confirmed = 1;
}

$now = $argv[1];

print "Mails sending disabled (useless in batch mode)\n";
$conf->global->MAIN_DISABLE_ALL_MAILS = 1; // On bloque les mails
print "\n";
print "----- Synchronize all records from Dolibarr database:\n";
print "type=".$conf->db->type."\n";
print "host=".$conf->db->host."\n";
print "port=".$conf->db->port."\n";
print "login=".$conf->db->user."\n";
// print "pass=".preg_replace('/./i','*',$conf->db->password)."\n"; // Not defined for security reasons
print "database=".$conf->db->name."\n";
print "\n";
print "----- To LDAP database:\n";
print "host=".$conf->global->LDAP_SERVER_HOST."\n";
print "port=".$conf->global->LDAP_SERVER_PORT."\n";
print "login=".$conf->global->LDAP_ADMIN_DN."\n";
print "pass=".preg_replace('/./i', '*', $conf->global->LDAP_ADMIN_PASS)."\n";
print "DN target=".$conf->global->LDAP_CONTACT_DN."\n";
print "\n";

if (!$confirmed) {
	print "Press a key to confirm...\n";
	$input = trim(fgets(STDIN));
	print "Warning, this operation may result in data loss if it failed.\n";
	print "Be sure to have a backup of your LDAP database (With OpenLDAP: slapcat > save.ldif).\n";
	print "Hit Enter to continue or CTRL+C to stop...\n";
	$input = trim(fgets(STDIN));
}

/*
 * if (! $conf->global->LDAP_CONTACT_ACTIVE)
 * {
 * print $langs->trans("LDAPSynchronizationNotSetupInDolibarr");
 * exit(-1);
 * }
 */

$sql = "SELECT rowid";
$sql .= " FROM ".MAIN_DB_PREFIX."socpeople";

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	$ldap = new Ldap();
	$ldap->connect_bind();

	while ($i < $num) {
		$ldap->error = "";

		$obj = $db->fetch_object($resql);

		$contact = new Contact($db);
		$contact->id = $obj->rowid;
		$contact->fetch($contact->id);

		print $langs->trans("UpdateContact")." rowid=".$contact->id." ".$contact->getFullName($langs);

		$oldobject = $contact;

		$oldinfo = $oldobject->_load_ldap_info();
		$olddn = $oldobject->_load_ldap_dn($oldinfo);

		$info = $contact->_load_ldap_info();
		$dn = $contact->_load_ldap_dn($info);

		$result = $ldap->add($dn, $info, $user); // Wil fail if already exists
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
	$ldap->close();
} else {
	dol_print_error($db);
}

exit($error);
