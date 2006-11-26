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
require_once(DOL_DOCUMENT_ROOT."/adherents/cotisation.class.php");

$error=0;


if ($argv[2]) $conf->global->LDAP_SERVER_HOST=$argv[2];

print "***** $script_file ($version) *****\n";
print 'DN='.$conf->global->LDAP_MEMBER_DN."\n";
print 'Filter=('.$conf->global->LDAP_KEY_MEMBERS.'=*)'."\n";

/*
if (! $conf->global->LDAP_MEMBER_ACTIVE)
{
	print $langs->trans("LDAPSynchronizationNotSetupInDolibarr");
	exit 1;	
}
*/

// Charge tableau de correspondance des pays
$hashlib2rowid=array();
$countries=array();
$sql = "SELECT rowid, code, libelle, active";
$sql.= " FROM ".MAIN_DB_PREFIX."c_pays";
$sql.= " WHERE active = 1";
$sql.= " ORDER BY code ASC;";
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	if ($num)
	{
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
			if ($obj)
			{
				//print 'Load cache for country '.strtolower($obj->libelle).' rowid='.$obj->rowid."\n";
				$hashlib2rowid[strtolower($obj->libelle)]=$obj->rowid;
				$countries[$obj->rowid]=array('rowid' => $obj->rowid, 'label' => $obj->libelle, 'code' => $obj->code);
			}
			$i++;
		}
	}
}
else
{
	dolibarr_print_error($db);
	exit;
}



$ldap = new Ldap();
$result = $ldap->connect_bind();
if ($result >= 0)
{
	$justthese=array();

	
	// On désactive la synchro Dolibarr vers LDAP
	$conf->global->LDAP_MEMBER_ACTIVE=0;
	
	
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
			$member->login=$ldapuser[$conf->global->LDAP_FIELD_LOGIN][0];
			//$member->pass;

			//$member->societe;
			$member->adresse=$ldapuser[$conf->global->LDAP_FIELD_ADDRESS][0];
			$member->cp=$ldapuser[$conf->global->LDAP_FIELD_ZIP][0];
			$member->ville=$ldapuser[$conf->global->LDAP_FIELD_TOWN][0];
			$member->pays=$ldapuser[$conf->global->LDAP_FIELD_COUNTRY][0];	// Pays en libelle
			$member->pays_id=$countries[$hashlib2rowid[strtolower($member->pays)]]['rowid'];
			$member->pays_code=$countries[$hashlib2rowid[strtolower($member->pays)]]['code'];

			$member->phone=$ldapuser[$conf->global->LDAP_FIELD_PHONE][0];
			$member->phone_perso=$ldapuser[$conf->global->LDAP_FIELD_PHONE_PERSO][0];
			$member->phone_mobile=$ldapuser[$conf->global->LDAP_FIELD_MOBILE][0];
			$member->email=$ldapuser[$conf->global->LDAP_FIELD_MAIL][0];

			$member->commentaire=$ldapuser[$conf->global->LDAP_FIELD_DESCRIPTION][0];
			$member->morphy='phy';
			$member->photo='';
			$member->public=1;
			$member->statut=-1;		// Par defaut, statut brouillon
			$member->naiss=dolibarr_mktime($ldapuser[$conf->global->LDAP_FIELD_BIRTHDATE][0]);
			// Cas particulier (on ne rentre jamais dans ce if)
			if (isset($ldapuser["prnxstatus"][0]))
			{
				$member->datec=dolibarr_mktime($ldapuser["prnxfirtscontribution"][0]);
				$member->datevalid=dolibarr_mktime($ldapuser["prnxfirtscontribution"][0]);
				if ($ldapuser["prnxstatus"][0]==1)
				{
					$member->statut=1;
				}
				else
				{
					$member->statut=0;
				}
			}
			
			// Propriete type membre
			$member->typeid=$typeid;

			// Creation membre
			print $langs->trans("MemberCreate").' no '.$key.': '.$member->fullname;
			$member_id=$member->create();
			if ($member_id > 0)
			{
				print ' --> '.$member_id;
			}
			else
			{
				$error++;
				print ' --> '.$member->error;
			}
			print "\n";

			//print_r($member);


			//----------------------------
			// YOUR OWN CODE HERE
			//----------------------------
			
			$datefirst=dolibarr_mktime($ldapuser["prnxfirtscontribution"][0]);
			$datelast=dolibarr_mktime($ldapuser["prnxlastcontribution"][0]);
			if ($datefirst)
			{
				$crowid=$member->cotisation($datefirst, 0, 0);
			}
			if ($datelast)
			{
				// Cree derniere cotisation et met a jour datefin dans adherent
				$price=price2num($ldapuser["prnxlastcontributionprice"][0]);
				//print "xx".$datelast."-".dolibarr_time_plus_duree($datelast,-1,'y')."\n";
				$crowid=$member->cotisation(dolibarr_time_plus_duree($datelast,-1,'y'), $price, 0);
			}
			
			//----------------------------
			// END OF OWN CODE HERE
			//----------------------------
		}
		
		if (! $error)
		{
			print $langs->trans("NoErrorCommitIsDone")."\n";
			$db->commit();
		}
		else
		{
			print $langs->trans("SommeErrorWereFoundRollbackIsDone",$error)."\n";
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
