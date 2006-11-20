<?php
/* Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006 Regis Houssin        <regis.houssin@cap-networks.com>
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
        \file       htdocs/contact/ldap.php
        \ingroup    ldap
        \brief      Page fiche LDAP contact
        \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/contact.lib.php");
require_once (DOL_DOCUMENT_ROOT."/lib/authldap.lib.php");

$user->getrights('commercial');

$langs->load("companies");
$langs->load("ldap");

// Protection quand utilisateur externe
$contactid = isset($_GET["id"])?$_GET["id"]:'';

$socid=0;
if ($user->societe_id > 0)
{
    $socid = $user->societe_id;
}

// Protection restriction commercial
if ($contactid && ! $user->rights->commercial->client->voir)
{
    $sql = "SELECT sc.fk_soc, sp.fk_soc";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc, ".MAIN_DB_PREFIX."socpeople as sp";
    $sql .= " WHERE sp.idp = ".$contactid;
    if (! $user->rights->commercial->client->voir && ! $socid)
    {
    	$sql .= " AND sc.fk_soc = sp.fk_soc AND sc.fk_user = ".$user->id;
    }
    if ($socid) $sql .= " AND sp.fk_soc = ".$socid;

    $resql=$db->query($sql);
    if ($resql)
    {
    	if ($db->num_rows() == 0) accessforbidden();
    }
    else
    {
    	dolibarr_print_error($db);
    }
}


/*
 *
 *
 */

llxHeader();

$form = new Form($db);

$contact = new Contact($db);
$contact->fetch($_GET["id"], $user);


/*
 * Affichage onglets
 */
$head = contact_prepare_head($contact);

dolibarr_fiche_head($head, 'ldap', $langs->trans("Contact").": ".$contact->firstname.' '.$contact->name);



/*
 * Fiche en mode visu
 */
print '<table class="border" width="100%">';

if ($contact->socid > 0)
{
    $objsoc = new Societe($db);
    $objsoc->fetch($contact->socid);

    print '<tr><td width="15%">'.$langs->trans("Company").'</td><td colspan="3">'.$objsoc->getNomUrl(1).'</td></tr>';
}
else
{
    print '<tr><td width="15%">'.$langs->trans("Company").'</td><td colspan="3">';
    print $langs->trans("ContactNotLinkedToCompany");
    print '</td></tr>';
}

print '<tr><td>'.$langs->trans("UserTitle").'</td><td colspan="3">';
print $form->civilite_name($contact->civilite_id);
print '</td></tr>';

// Nom
print '<tr><td width="20%">'.$langs->trans("Lastname").'</td><td>'.$contact->name.'</td>';

// Prenom
print '<td width="20%">'.$langs->trans("Firstname").'</td><td width="25%">'.$contact->firstname.'</td></tr>';

// LDAP DN
$langs->load("admin");
print '<tr><td>'.$langs->trans("LDAPContactDn").'*</td><td class="valeur">'.$conf->global->LDAP_CONTACT_DN."</td></tr>\n";

// LDAP Server
print '<tr><td>'.$langs->trans("LDAPPrimaryServer").'*</td><td class="valeur">'.$conf->global->LDAP_SERVER_HOST."</td></tr>\n";
print '<tr><td>'.$langs->trans("LDAPSecondaryServer").'*</td><td class="valeur">'.$conf->global->LDAP_SERVER_HOST_SLAVE."</td></tr>\n";
print '<tr><td>'.$langs->trans("LDAPServerPort").'*</td><td class="valeur">'.$conf->global->LDAP_SERVER_PORT."</td></tr>\n";

print '</table>';

print '</div>';

print '<br>';


print_titre($langs->trans("LDAPInformationsForThisContact"));

// Affichage attributs LDAP
print '<table width="100%" class="noborder">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("LDAPAttributes").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '</tr>';

// Lecture LDAP
$ldap=new AuthLdap();
$result=$ldap->connect();
if ($result)
{
	$bind='';
	if ($conf->global->LDAP_ADMIN_DN && $conf->global->LDAP_ADMIN_PASS)
	{
		dolibarr_syslog("ldap.php: authBind user=".$conf->global->LDAP_ADMIN_DN,LOG_DEBUG);
		$bind=$ldap->authBind($conf->global->LDAP_ADMIN_DN,$conf->global->LDAP_ADMIN_PASS);
	}
	if (! $bind)	// Si pas de login ou si connexion avec login en echec, on tente en anonyme
	{
		dolibarr_syslog("ldap.php: bind",LOG_DEBUG);
		$bind=$ldap->bind();
	}

	if ($bind)
	{
		$info["cn"] = trim($contact->firstname." ".$contact->name);

		$dn = $conf->global->LDAP_CONTACT_DN;
//		$dn = "cn=".$info["cn"].",".$dn;
//		$dn = "uid=".$info["uid"].",".$dn
		$search = "(cn=".$info["cn"].")";
		//$search = "(uid=".$info["uid"].")";

		$result=$ldap->search($dn,$search);

		// Affichage arbre
		if (sizeof($result))
		{
			$html=new Form($db);
			$html->show_ldap_content($result,0,0,true);
		}
		else
		{
			print '<tr><td colspan="2">'.$langs->trans("LDAPRecordNotFound").'</td></tr>';
		}
		
		$ldap->unbind();
	}
	else
	{
		dolibarr_print_error('',$ldap->error);
	}
	$ldap->close();
}
else
{
	dolibarr_print_error('',$ldap->error);
}

print '</table>';




$db->close();

llxFooter('$Date$ - $Revision$');
?>
