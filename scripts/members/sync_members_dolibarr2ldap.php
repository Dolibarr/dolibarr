#!/usr/bin/php
<?php
/**
 * Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       scripts/members/sync_members_dolibarr2ldap.php
 *      \ingroup    ldap member
 *      \brief      Script de mise a jour des adherents dans LDAP depuis base Dolibarr
 * 		\version	$Id: sync_members_dolibarr2ldap.php,v 1.10 2011/07/31 22:22:12 eldy Exp $
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You ar usingr PH for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
    exit;
}

// Main
$version='$Revision: 1.10 $';
$path=str_replace($script_file,'',$_SERVER["PHP_SELF"]);
@set_time_limit(0);
$error=0;

require_once($path."../../htdocs/master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/ldap.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");


$langs->load("main");


print "***** $script_file ($version) *****\n";

if (! isset($argv[1]) || ! $argv[1]) {
    print "Usage: $script_file now\n";
    exit;
}
$now=$argv[1];

print "Mails sending disabled (useless in batch mode)\n";
$conf->global->MAIN_DISABLE_ALL_MAILS=1;	// On bloque les mails
print "\n";
print "----- Synchronize all records from Dolibarr database:\n";
print "type=".$conf->db->type."\n";
print "host=".$conf->db->host."\n";
print "port=".$conf->db->port."\n";
print "login=".$conf->db->user."\n";
//print "pass=".preg_replace('/./i','*',$conf->db->password)."\n";	// Not defined for security reasons
print "database=".$conf->db->name."\n";
print "\n";
print "----- To LDAP database:\n";
print "host=".$conf->global->LDAP_SERVER_HOST."\n";
print "port=".$conf->global->LDAP_SERVER_PORT."\n";
print "login=".$conf->global->LDAP_ADMIN_DN."\n";
print "pass=".preg_replace('/./i','*',$conf->global->LDAP_ADMIN_PASS)."\n";
print "DN target=".$conf->global->LDAP_MEMBER_DN."\n";
print "\n";
print "Press a key to confirm...\n";
$input = trim(fgets(STDIN));
print "Warning, this operation may result in data loss if it failed.\n";
print "Be sure to have a backup of your LDAP database (With OpenLDAP: slapcat > save.ldif).\n";
print "Hit Enter to continue or CTRL+C to stop...\n";
$input = trim(fgets(STDIN));

/*
if (! $conf->global->LDAP_MEMBER_ACTIVE)
{
	print $langs->trans("LDAPSynchronizationNotSetupInDolibarr");
	exit 1;
}
*/

$sql = "SELECT rowid";
$sql .= " FROM ".MAIN_DB_PREFIX."adherent";

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	$ldap=new Ldap();
	$ldap->connect_bind();

	while ($i < $num)
	{
		$ldap->error="";

		$obj = $db->fetch_object($resql);

		$member = new Adherent($db);
		$result=$member->fetch($obj->rowid);
		if ($result < 0)
		{
			dol_print_error($db,$member->error);
			exit;
		}
		$result=$member->fetch_subscriptions();
		if ($result < 0)
		{
			dol_print_error($db,$member->error);
			exit;
		}

		print $langs->transnoentities("UpdateMember")." rowid=".$member->id." ".$member->getFullName($langs);

		$oldobject=$member;

	    $oldinfo=$oldobject->_load_ldap_info();
	    $olddn=$oldobject->_load_ldap_dn($oldinfo);

	    $info=$member->_load_ldap_info();
		$dn=$member->_load_ldap_dn($info);

		$result=$ldap->add($dn,$info,$user);	// Wil fail if already exists
		$result=$ldap->update($dn,$info,$user,$olddn);
		if ($result > 0)
		{
			print " - ".$langs->transnoentities("OK");
		}
		else
		{
			$error++;
			print " - ".$langs->transnoentities("KO").' - '.$ldap->error;
		}
		print "\n";

		$i++;
	}

	$ldap->unbind();
	$ldap->close();
}
else
{
	dol_print_error($db);
}

return $error;
?>
