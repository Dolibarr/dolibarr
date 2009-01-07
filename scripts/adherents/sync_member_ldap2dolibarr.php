<?PHP
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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

// Main
$version='$Revision$';
$path=eregi_replace($script_file,'',$_SERVER["PHP_SELF"]);
@set_time_limit(0);
$error=0;


require_once($path."../../htdocs/master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/ldap.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/cotisation.class.php");


$langs->load("main");


if ($argv[2]) $conf->global->LDAP_SERVER_HOST=$argv[2];

print "***** $script_file ($version) *****\n";

if (! isset($argv[1]) || ! is_numeric($argv[1])) {
    print "Usage:  $script_file id_member_type\n";   
    exit;
}
$typeid=$argv[1];

print "Mails sending disabled (useless in batch mode)\n";
$conf->global->MAIN_DISABLE_ALL_MAILS=1;	// On bloque les mails
print "\n";
print "----- Synchronize all records from LDAP database:\n";
print "host=".$conf->global->LDAP_SERVER_HOST."\n";
print "port=".$conf->global->LDAP_SERVER_PORT."\n";
print "login=".$conf->global->LDAP_ADMIN_DN."\n";
print "pass=".eregi_replace('.','*',$conf->global->LDAP_ADMIN_PASS)."\n";
print "DN to extract=".$conf->global->LDAP_MEMBER_DN."\n";
print 'Filter=('.$conf->global->LDAP_KEY_MEMBERS.'=*)'."\n";
print "----- To Dolibarr database:\n";
print "type=".$conf->db->type."\n";
print "host=".$conf->db->host."\n";
print "port=".$conf->db->port."\n";
print "login=".$conf->db->user."\n";
print "database=".$conf->db->name."\n";
print "\n";
print "Press a key to confirm...\n";
$input = trim(fgets(STDIN));
print "Warning, this operation may result in data loss if it failed.\n";
print "Hit Enter to continue or CTRL+C to stop...\n";
$input = trim(fgets(STDIN));


if (! $conf->global->LDAP_MEMBER_DN)
{
	print $langs->trans("Error").': '.$langs->trans("LDAP setup for members not defined inside Dolibarr");
	exit(1);	
}


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

	
	// On d�sactive la synchro Dolibarr vers LDAP
	$conf->global->LDAP_MEMBER_ACTIVE=0;
	
	// Liste des champs a r�cup�rer de LDAP
	$required_fields = array(
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
	$required_fields=array_unique(array_values(array_filter($required_fields, "dolValidElement")));
	
	$ldaprecords = $ldap->getRecords('*',$conf->global->LDAP_MEMBER_DN, $conf->global->LDAP_KEY_MEMBERS, $required_fields, 0);
	if (is_array($ldaprecords))
	{
		$db->begin();

		// Warning $ldapuser a une cl� en minuscule
		foreach ($ldaprecords as $key => $ldapuser)
		{
			$member = new Adherent($db);

			// Propriete membre
			$member->prenom=$ldapuser[$conf->global->LDAP_FIELD_FIRSTNAME];
			$member->nom=$ldapuser[$conf->global->LDAP_FIELD_NAME];
			$member->fullname=($ldapuser[$conf->global->LDAP_FIELD_FULLNAME] ? $ldapuser[$conf->global->LDAP_FIELD_FULLNAME] : trim($member->prenom." ".$member->nom));
			$member->login=$ldapuser[$conf->global->LDAP_FIELD_LOGIN];
			$member->pass=$ldapuser[$conf->global->LDAP_FIELD_PASSWORD];

			//$member->societe;
			$member->adresse=$ldapuser[$conf->global->LDAP_FIELD_ADDRESS];
			$member->cp=$ldapuser[$conf->global->LDAP_FIELD_ZIP];
			$member->ville=$ldapuser[$conf->global->LDAP_FIELD_TOWN];
			$member->pays=$ldapuser[$conf->global->LDAP_FIELD_COUNTRY];	// Pays en libelle
			$member->pays_id=$countries[$hashlib2rowid[strtolower($member->pays)]]['rowid'];
			$member->pays_code=$countries[$hashlib2rowid[strtolower($member->pays)]]['code'];

			$member->phone=$ldapuser[$conf->global->LDAP_FIELD_PHONE];
			$member->phone_perso=$ldapuser[$conf->global->LDAP_FIELD_PHONE_PERSO];
			$member->phone_mobile=$ldapuser[$conf->global->LDAP_FIELD_MOBILE];
			$member->email=$ldapuser[$conf->global->LDAP_FIELD_MAIL];

			$member->note=$ldapuser[$conf->global->LDAP_FIELD_DESCRIPTION];
			$member->morphy='phy';
			$member->photo='';
			$member->public=1;
			$member->naiss=dol_stringtotime($ldapuser[$conf->global->LDAP_FIELD_BIRTHDATE]);

			$member->statut=-1;
			if (isset($ldapuser[$conf->global->LDAP_FIELD_MEMBER_STATUS]))
			{
				$member->datec=dol_stringtotime($ldapuser[$conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE]);
				$member->datevalid=dol_stringtotime($ldapuser[$conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE]);
				$member->statut=$ldapuser[$conf->global->LDAP_FIELD_MEMBER_STATUS];
			}
			//if ($member->statut > 1) $member->statut=1;

			//print_r($ldapuser);

			// Propriete type membre
			$member->typeid=$typeid;

			// Creation membre
			print $langs->trans("MemberCreate").' # '.$key.': fullname='.$member->fullname;
			print ', datec='.$member->datec;
			$member_id=$member->create();
			if ($member_id > 0)
			{
				print ' --> Created member id='.$member_id.' login='.$member->login;
			}
			else
			{
				$error++;
				print ' --> '.$member->error;
			}
			print "\n";

			//print_r($member);
			
			$datefirst='';
			if ($conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE)
			{
				$datefirst=dol_stringtotime($ldapuser[$conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE]);
				$pricefirst=price2num($ldapuser[$conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_AMOUNT]);
			}

			$datelast='';
			if ($conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_DATE)
			{
				$datelast=dol_stringtotime($ldapuser[$conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_DATE]);
				$pricelast=price2num($ldapuser[$conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_AMOUNT]);
			}
			elseif ($conf->global->LDAP_FIELD_MEMBER_END_LASTSUBSCRIPTION)
			{
				$datelast=dol_time_plus_duree(dol_stringtotime($ldapuser[$conf->global->LDAP_FIELD_MEMBER_END_LASTSUBSCRIPTION]),-1,'y')+60*60*24;
				$pricelast=price2num($ldapuser[$conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_AMOUNT]);

				// Cas special ou date derniere <= date premiere
				if ($datefirst && $datelast && $datelast <= $datefirst)
				{
					// On ne va inserer que la premiere
					$datelast=0;
					if (! $pricefirst && $pricelast) $pricefirst = $pricelast;
				}
			}

			
			// Insertion premi�re adh�sion
			if ($datefirst)
			{
				// Cree premiere cotisation et met a jour datefin dans adherent
				//print "xx".$datefirst."\n";
				$crowid=$member->cotisation($datefirst, $pricefirst, 0);
			}

			// Insertion derni�re adh�sion
			if ($datelast)
			{
				// Cree derniere cotisation et met a jour datefin dans adherent
				//print "yy".dolibarr_print_date($datelast)."\n";
				$crowid=$member->cotisation($datelast, $pricelast, 0);
			}
			
		}
		
		if (! $error)
		{
			print $langs->transnoentities("NoErrorCommitIsDone")."\n";
			$db->commit();
		}
		else
		{
			print $langs->transnoentities("ErrorSomeErrorWereFoundRollbackIsDone",$error)."\n";
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


function dolValidElement($element) {
	return (trim($element) != '');
}

?>
