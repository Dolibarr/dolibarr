<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 * $Source$
 */

/**
        \file       scripts/adherents/sync_member_ldap2dolibarr.php
        \ingroup    ldap adherent
        \brief      Script de mise a jour des adherents dans Dolibarr depuis LDAP
*/

// Test si mode batch
$sapi_type = php_sapi_name();
$script_file=__FILE__; 
if (eregi('([^\\\/]+)$',$script_file,$reg)) $script_file=$reg[1];

if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Erreur: Vous utilisez l'interpreteur PHP pour le mode CGI. Pour executer $script_file en ligne de commande, vous devez utiliser l'interpreteur PHP pour le mode CLI.\n";
    exit;
}

if (! isset($argv[1]) || ! is_numeric($argv[1])) {
    print "Usage:  $script_file id_member_type\n";   
    exit;
}
$typeid=$argv[1];

// Recupere env dolibarr
$version='$Revision$';
$path=eregi_replace($script_file,'',$_SERVER["PHP_SELF"]);

require_once($path."../../htdocs/master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/ldap.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");

$error=0;


print "***** $script_file ($version) *****\n";

if ($argv[2]) $conf->global->LDAP_SERVER_HOST=$argv[2];
if ($argv[2]) $conf->global->LDAP_SERVER_HOST=$argv[2];

/*
if (! $conf->global->LDAP_MEMBER_ACTIVE)
{
	print $langs->trans("LDAPSynchronizationNotSetupInDolibarr");
	exit 1;	
}
*/


$ldap = new Ldap();
$result = $ldap->connect_bind();
if ($result >= 0)
{
	$justthese=array();

	$ldaprecords = $ldap->search($conf->global->LDAP_MEMBER_DN, '('.$conf->global->LDAP_KEY_MEMBERS.'=*)');
	if (is_array($ldaprecords))
	{
		$db->begin();
		
		foreach ($ldaprecords as $key => $ldapuser)
		{
			if ($key == 'count') continue;
			
			$member = new Adherent($db);

			// Propriete membre
			$member->prenom=$ldapuser[$conf->global->LDAP_FIELD_FIRSTNAME][0];
			$member->nom=$ldapuser[$conf->global->LDAP_FIELD_NAME][0];
			$member->fullname=($ldapuser[$conf->global->LDAP_FIELD_FULLNAME][0] ? $ldapuser[$conf->global->LDAP_FIELD_FULLNAME][0] : trim($member->prenom." ".$member->nom));
			//$member->societe;
			//$member->adresse=$ldapuser[$conf->global->LDAP_FIELD_FULLNAME]
			//$member->cp;
			//$member->ville;
			//$member->pays_id;
			//$member->pays_code;
			//$member->pays;
			//$member->morphy;
			$member->email=$ldapuser[$conf->global->LDAP_FIELD_EMAIL][0];
			//$member->public;
			//$member->commentaire;
			$member->statut=-1;
			$member->login=$ldapuser[$conf->global->LDAP_FIELD_LOGIN][0];
			//$member->pass;
			//$member->naiss;
			//$member->photo;
			
			// Propriete type membre
			$member->typeid=$typeid;


			//----------------------------
			// YOUR OWN RULES HERE
			//----------------------------
			
			
			
			//----------------------------
			// END
			//----------------------------

			print $langs->trans("MemberCreate").' no '.$key.': '.$member->fullname."\n";

			print_r($member);
			exit;			
			
//			$member->create();

			$error++;
		}
		
		if (! $error)
		{
			$db->commit();
		}
		else
		{
			$db->rollback();
		}
	}
	else
	{
		dolibarr_print_error('',$ldap->error);
		$error++;
	}
}
else
{
	dolibarr_print_error('',$ldap->error);
	$error++;
}
		

return $error;
?>
