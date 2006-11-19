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
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/user.class.php");
require_once(DOL_DOCUMENT_ROOT."/usergroup.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/authldap.lib.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();


/*
 * Actions
 */
 
if ($_GET["action"] == 'setvalue' && $user->admin)
{
	$error=0;
	if (! dolibarr_set_const($db, 'LDAP_USER_DN',$_POST["user"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_GROUP_DN',$_POST["group"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_LOGIN',$_POST["fieldlogin"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_LOGIN_SAMBA',$_POST["fieldloginsamba"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_NAME',$_POST["fieldname"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_FIRSTNAME',$_POST["fieldfirstname"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_MAIL',$_POST["fieldmail"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_PHONE',$_POST["fieldphone"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_FAX',$_POST["fieldfax"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_MOBILE',$_POST["fieldmobile"])) $error++;

	if ($error)
	{
		dolibarr_print_error($db->error());
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
	$hselected=$h;
	$h++;
}

if ($conf->global->LDAP_CONTACT_ACTIVE)
{
	$head[$h][0] = DOL_URL_ROOT."/admin/ldap_contacts.php";
	$head[$h][1] = $langs->trans("LDAPContactsSynchro");
	$h++;
}

if ($conf->global->LDAP_MEMBERS_ACTIVE)
{
	$head[$h][0] = DOL_URL_ROOT."/admin/ldap_members.php";
	$head[$h][1] = $langs->trans("LDAPMembersSynchro");
	$h++;
}

dolibarr_fiche_head($head, $hselected, $langs->trans("LDAP"));

print $langs->trans("LDAPDescUsers").'<br>';
print '<br>';


print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?action=setvalue">';

print '<table class="noborder" width="100%">';
   
$var=true;
$html=new Form($db);


print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("LDAPSynchronizeUsersAndGroup").'</td>';
print "</tr>\n";

// DN Pour les utilisateurs
$var=!$var;
print '<tr '.$bc[$var].'><td><b>'.$langs->trans("LDAPUserDn").picto_required().'</b></td><td>';
print '<input size="38" type="text" name="user" value="'.$conf->global->LDAP_USER_DN.'">';
print '</td><td>'.$langs->trans("LDAPUserDnExample").'</td></tr>';

// DN pour les groupes
$var=!$var;
print '<tr '.$bc[$var].'><td><b>'.$langs->trans("LDAPGroupDn").picto_required().'</b></td><td>';
print '<input size="38" type="text" name="group" value="'.$conf->global->LDAP_GROUP_DN.'">';
print '</td><td>'.$langs->trans("LDAPGroupDnExample").'</td></tr>';

// Filtre de connexion
$var=!$var;
print '<tr '.$bc[$var].'><td><b>'.$langs->trans("LDAPFilterConnection").picto_required().'</b></td><td>';
print '<input size="38" type="text" name="filterconnection" value="'.$conf->global->LDAP_FILTER_CONNECTION.'">';
print '</td><td>'.$langs->trans("LDAPFilterConnectionExample").'</td></tr>';

// Login unix
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldLoginUnix").'</td><td>';
print '<input size="25" type="text" name="fieldlogin" value="'.$conf->global->LDAP_FIELD_LOGIN.'">';
print '</td><td>'.$langs->trans("LDAPFieldLoginExample").'</td></tr>';

// Login samba
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldLoginSamba").'</td><td>';
print '<input size="25" type="text" name="fieldloginsamba" value="'.$conf->global->LDAP_FIELD_LOGIN_SAMBA.'">';
print '</td><td>'.$langs->trans("LDAPFieldLoginSambaExample").'</td></tr>';

// Name
$var=!$var;
print '<tr '.$bc[$var].'><td><b>'.$langs->trans("LDAPFieldName").picto_required().'</b></td><td>';
print '<input size="25" type="text" name="fieldname" value="'.$conf->global->LDAP_FIELD_NAME.'">';
print '</td><td>'.$langs->trans("LDAPFieldNameExample").'</td></tr>';

// Firstname
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldFirstName").'</td><td>';
print '<input size="25" type="text" name="fieldfirstname" value="'.$conf->global->LDAP_FIELD_FIRSTNAME.'">';
print '</td><td>'.$langs->trans("LDAPFieldFirstNameExample").'</td></tr>';

// Mail
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldMail").'</td><td>';
print '<input size="25" type="text" name="fieldmail" value="'.$conf->global->LDAP_FIELD_MAIL.'">';
print '</td><td>'.$langs->trans("LDAPFieldMailExample").'</td></tr>';

// Phone
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldPhone").'</td><td>';
print '<input size="25" type="text" name="fieldphone" value="'.$conf->global->LDAP_FIELD_PHONE.'">';
print '</td><td>'.$langs->trans("LDAPFieldPhoneExample").'</td></tr>';

// Fax
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldFax").'</td><td>';
print '<input size="25" type="text" name="fieldfax" value="'.$conf->global->LDAP_FIELD_FAX.'">';
print '</td><td>'.$langs->trans("LDAPFieldFaxExample").'</td></tr>';

// Mobile
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldMobile").'</td><td>';
print '<input size="25" type="text" name="fieldmobile" value="'.$conf->global->LDAP_FIELD_MOBILE.'">';
print '</td><td>'.$langs->trans("LDAPFieldMobileExample").'</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td colspan="3" align="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td></tr>';
print '</table>';

print '</form>';

print '</div>';

print info_admin($langs->trans("LDAPDescValues"));

/*
 * Test de la connexion
 */
if (function_exists("ldap_connect"))
{
	if ($conf->global->LDAP_SERVER_HOST && $conf->global->LDAP_SYNCHRO_ACTIVE == 'dolibarr2ldap')
	{
		print '<br>';
		print '<a class="tabAction" href="'.$_SERVER["PHP_SELF"].'?action=testuser">'.$langs->trans("LDAPTestSynchroUser").'</a>';
		print '<a class="tabAction" href="'.$_SERVER["PHP_SELF"].'?action=testgroup">'.$langs->trans("LDAPTestSynchroGroup").'</a>';
		print '<br><br>';
	}

	if ($_GET["action"] == 'testuser')
	{
		// Creation contact
		$fuser=new User($db);
		$fuser->initAsSpecimen();

		// Test synchro
		//$result1=$fuser->delete_ldap($user);
		$result2=$fuser->update_ldap($user);
		$result3=$fuser->delete_ldap($user);
	
		if ($result2 > 0)
		{
			print img_picto('','info').' ';
			print '<font class="ok">'.$langs->trans("LDAPSynchroOK").'</font><br>';
		}
		else
		{
			print img_picto('','error').' ';
			print '<font class="warning">'.$langs->trans("LDAPSynchroKO");
			print ': '.$fuser->error;
			print '</font><br>';
		}

	}

	if ($_GET["action"] == 'testgroup')
	{
		// Creation contact
		$fgroup=new UserGroup($db);
		$fgroup->initAsSpecimen();

		// Test synchro
		//$result1=$fgroup->delete_ldap($user);
		$result2=$fgroup->update_ldap($user);
		$result3=$fgroup->delete_ldap($user);
	
		if ($result2 > 0)
		{
			print img_picto('','info').' ';
			print '<font class="ok">'.$langs->trans("LDAPSynchroOK").'</font><br>';
		}
		else
		{
			print img_picto('','error').' ';
			print '<font class="warning">'.$langs->trans("LDAPSynchroKO");
			print ': '.$fgroup->error;
			print '</font><br>';
		}

	}
}

$db->close();

llxFooter('$Date$ - $Revision$');

?>
