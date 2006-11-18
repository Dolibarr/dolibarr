<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004 Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005 Regis Houssin        <regis.houssin@cap-networks.com>
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
    	\file       htdocs/admin/ldap.php
		\ingroup    ldap
		\brief      Page d'administration/configuration du module Ldap
		\version    $Revision$
        \remarks    Exemple configuration :
                    LDAP_SERVER_HOST    Serveur LDAP		      192.168.1.50
                    LDAP_SERVER_PORT    Port LDAP             389
                    LDAP_ADMIN_DN       Administrateur LDAP	  cn=adminldap,dc=societe,dc=com	
                    LDAP_ADMIN_PASS     Mot de passe		      xxxxxxxx
                    LDAP_USER_DN        DN des utilisateurs	  ou=users,dc=societe,dc=com
                    LDAP_GROUP_DN       DN des groupes		    ou=groups,dc=societe,dc=com	
                    LDAP_CONTACT_DN     DN des contacts		    ou=contacts,dc=societe,dc=com
                    LDAP_SERVER_TYPE    Type				          Openldap
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/authldap.lib.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();


/*
 * Actions
 */
 
if ($_GET["action"] == 'setvalue' && $user->admin)
{
	if (! dolibarr_set_const($db, 'LDAP_CONTACT_DN',$_POST["contactdn"]))
	{
		print $db->error();
	}
}



/*
 * Visu
 */

llxHeader();

print_fiche_titre($langs->trans("LDAPSetup"),'','setup');

// Test si fonction LDAP actives
if (! function_exists("ldap_connect"))
{
	$mesg=$langs->trans("LDAPFunctionsNotAvailableOnPHP");
}

if ($mesg) print '<div class="error">'.$mesg.'</div>';
else print '<br>';


// Onglets
$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/ldap.php";
$head[$h][1] = $langs->trans("LDAPGlobalParameters");
$h++;

if ($conf->global->LDAP_SYNCHRO_ACTIVE)
{
	$head[$h][0] = DOL_URL_ROOT."/admin/ldap_users.php";
	$head[$h][1] = $langs->trans("LDAPUsersAndGroupsSynchro");
	$h++;
}

if ($conf->global->LDAP_CONTACT_ACTIVE)
{
	$head[$h][0] = DOL_URL_ROOT."/admin/ldap_contacts.php";
	$head[$h][1] = $langs->trans("LDAPContactsSynchro");
	$hselected=$h;
	$h++;
}

if ($conf->global->LDAP_MEMBERS_ACTIVE)
{
	$head[$h][0] = DOL_URL_ROOT."/admin/ldap_members.php";
	$head[$h][1] = $langs->trans("LDAPMembersSynchro");
	$h++;
}

dolibarr_fiche_head($head, $hselected, $langs->trans("LDAP"));


print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?action=setvalue">';

print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("LDAPSynchronizeContacts").'</td>';
print "</tr>\n";
   
$var=true;
$html=new Form($db);


// DN Pour les contacts
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPContactDn").'</td><td>';
print '<input size="38" type="text" name="contactdn" value="'.$conf->global->LDAP_CONTACT_DN.'">';
print '</td><td>'.$langs->trans("LDAPContactDnExample").'</td></tr>';


print '<tr><td colspan="3" align="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td></tr>';
print '</table>';

print '</form>';

print '</div>';


$db->close();

llxFooter('$Date$ - $Revision$');

?>
