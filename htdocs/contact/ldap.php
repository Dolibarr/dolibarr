<?php
/* Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
        \file       htdocs/contact/ldap.php
        \ingroup    ldap
        \brief      Page fiche LDAP contact
        \version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/contact.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/ldap.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/ldap.lib.php");

$langs->load("companies");
$langs->load("ldap");
$langs->load("admin");

// Security check
$contactid = isset($_GET["id"])?$_GET["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'contact', $contactid, 'socpeople');


/*
*	View
*/

llxHeader();

$form = new Form($db);

$contact = new Contact($db);
$contact->fetch($_GET["id"], $user);


/*
 * Affichage onglets
 */
$head = contact_prepare_head($contact);

dol_fiche_head($head, 'ldap', $langs->trans("Contact"));


/*
 * Fiche en mode visu
 */
print '<table class="border" width="100%">';

// Ref
print '<tr><td width="20%">'.$langs->trans("Ref").'</td><td colspan="3">';
print $form->showrefnav($contact,'id');
print '</td></tr>';

// Name
print '<tr><td>'.$langs->trans("Lastname").'</td><td>'.$contact->name.'</td>';
print '<td>'.$langs->trans("Firstname").'</td><td width="25%">'.$contact->firstname.'</td></tr>';

// Company
if ($contact->socid > 0)
{
    $objsoc = new Societe($db);
    $objsoc->fetch($contact->socid);

    print '<tr><td width="20%">'.$langs->trans("Company").'</td><td colspan="3">'.$objsoc->getNomUrl(1).'</td></tr>';
}
else
{
    print '<tr><td width="20%">'.$langs->trans("Company").'</td><td colspan="3">';
    print $langs->trans("ContactNotLinkedToCompany");
    print '</td></tr>';
}

// Civility
print '<tr><td>'.$langs->trans("UserTitle").'</td><td colspan="3">';
print $contact->getCivilityLabel();
print '</td></tr>';

// LDAP DN
print '<tr><td>LDAP '.$langs->trans("LDAPContactDn").'</td><td class="valeur" colspan="3">'.$conf->global->LDAP_CONTACT_DN."</td></tr>\n";

// LDAP Clé
print '<tr><td>LDAP '.$langs->trans("LDAPNamingAttribute").'</td><td class="valeur" colspan="3">'.$conf->global->LDAP_KEY_CONTACTS."</td></tr>\n";

// LDAP Server
print '<tr><td>LDAP '.$langs->trans("LDAPPrimaryServer").'</td><td class="valeur" colspan="3">'.$conf->global->LDAP_SERVER_HOST."</td></tr>\n";
print '<tr><td>LDAP '.$langs->trans("LDAPSecondaryServer").'</td><td class="valeur" colspan="3">'.$conf->global->LDAP_SERVER_HOST_SLAVE."</td></tr>\n";
print '<tr><td>LDAP '.$langs->trans("LDAPServerPort").'</td><td class="valeur" colspan="3">'.$conf->global->LDAP_SERVER_PORT."</td></tr>\n";

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
$ldap=new Ldap();
$result=$ldap->connect_bind();
if ($result > 0)
{
	$info=$contact->_load_ldap_info();
	$dn=$contact->_load_ldap_dn($info,1);
	$search = "(".$contact->_load_ldap_dn($info,2).")";
	$records=$ldap->search($dn,$search);

	//print_r($records);

	// Affichage arbre
	if (sizeof($records) && (! isset($records['count']) || $records['count'] > 0))
	{
		if (! is_array($records))
		{
			print '<tr '.$bc[false].'><td colspan="2"><font class="error">'.$langs->trans("ErrorFailedToReadLDAP").'</font></td></tr>';	
		}
		else
		{
			$result=show_ldap_content($records,0,0,true);
		}
	}
	else
	{
		print '<tr '.$bc[false].'><td colspan="2">'.$langs->trans("LDAPRecordNotFound").' (dn='.$dn.' - search='.$search.')</td></tr>';
	}

	$ldap->unbind();
	$ldap->close();
}
else
{
	dol_print_error('',$ldap->error);
}


print '</table>';




$db->close();

llxFooter('$Date$ - $Revision$');
?>
