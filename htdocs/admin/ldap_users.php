<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005      Regis Houssin        <regis@dolibarr.fr>
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
 */
 
/**
    	\file       htdocs/admin/ldap_users.php
		\ingroup    ldap
		\brief      Page d'administration/configuration du module Ldap
		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/user.class.php");
require_once(DOL_DOCUMENT_ROOT."/usergroup.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/ldap.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/ldap.lib.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();


/*
 * Actions
 */
 
if ($_GET["action"] == 'setvalue' && $user->admin)
{
	$error=0;
	if (! dolibarr_set_const($db, 'LDAP_KEY_USERS',$_POST["key"])) $error++;

	if (! dolibarr_set_const($db, 'LDAP_USER_DN',$_POST["user"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_USER_OBJECT_CLASS',$_POST["objectclass"])) $error++;
	
	if (! dolibarr_set_const($db, 'LDAP_FILTER_CONNECTION',$_POST["filterconnection"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_FULLNAME',$_POST["fieldfullname"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_LOGIN',$_POST["fieldlogin"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_LOGIN_SAMBA',$_POST["fieldloginsamba"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_PASSWORD',$_POST["fieldpassword"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_PASSWORD_CRYPTED',$_POST["fieldpasswordcrypted"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_NAME',$_POST["fieldname"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_FIRSTNAME',$_POST["fieldfirstname"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_MAIL',$_POST["fieldmail"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_PHONE',$_POST["fieldphone"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_MOBILE',$_POST["fieldmobile"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_FAX',$_POST["fieldfax"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_DESCRIPTION',$_POST["fielddescription"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_SID',$_POST["fieldsid"])) $error++;

	if ($error)
	{
		dolibarr_print_error($db->error());
	}
}



/*
 * Visu
 */

llxHeader();

$head = ldap_prepare_head();

// Test si fonction LDAP actives
if (! function_exists("ldap_connect"))
{
	$mesg=$langs->trans("LDAPFunctionsNotAvailableOnPHP");
}

if ($mesg) print '<div class="error">'.$mesg.'</div>';


dolibarr_fiche_head($head, 'users', $langs->trans("LDAPSetup"));


print $langs->trans("LDAPDescUsers").'<br>';
print '<br>';


print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?action=setvalue">';

   
$html=new Form($db);

print '<table class="noborder" width="100%">';
$var=true;

print '<tr class="liste_titre">';
print '<td colspan="4">'.$langs->trans("LDAPSynchronizeUsers").'</td>';
print "</tr>\n";

// DN Pour les utilisateurs
$var=!$var;
print '<tr '.$bc[$var].'><td width="25%"><b>'.$langs->trans("LDAPUserDn").picto_required().'</b></td><td>';
print '<input size="48" type="text" name="user" value="'.$conf->global->LDAP_USER_DN.'">';
print '</td><td>'.$langs->trans("LDAPUserDnExample").'</td>';
print '<td>&nbsp;</td>';
print '</tr>';

// List of object class used to define attributes in structure
$var=!$var;
print '<tr '.$bc[$var].'><td width="25%"><b>'.$langs->trans("LDAPUserObjectClassList").picto_required().'</b></td><td>';
print '<input size="48" type="text" name="objectclass" value="'.$conf->global->LDAP_USER_OBJECT_CLASS.'">';
print '</td><td>'.$langs->trans("LDAPUserObjectClassListExample").'</td>';
print '<td>&nbsp;</td>';
print '</tr>';

// Filtre
//Utilise pour filtrer la recherche
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFilterConnection").'</td><td>';
print '<input size="48" type="text" name="filterconnection" value="'.$conf->global->LDAP_FILTER_CONNECTION.'">';
print '</td><td>'.$langs->trans("LDAPFilterConnectionExample").'</td>';
print '<td></td>';
print '</tr>';

print '</table>';
print '<br>';
print '<table class="noborder" width="100%">';
$var=true;

print '<tr class="liste_titre">';
print '<td width="25%">'.$langs->trans("LDAPDolibarrMapping").'</td>';
print '<td colspan="2">'.$langs->trans("LDAPLdapMapping").'</td>';
print '<td align="right">'.$langs->trans("LDAPNamingAttribute").'</td>';
print "</tr>\n";

// Common name
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldFullname").'</td><td>';
print '<input size="25" type="text" name="fieldfullname" value="'.$conf->global->LDAP_FIELD_FULLNAME.'">';
print '</td><td>'.$langs->trans("LDAPFieldFullnameExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_FULLNAME.'"'.($conf->global->LDAP_KEY_USERS==$conf->global->LDAP_FIELD_FULLNAME?' checked="true"':'')."></td>";
print '</tr>';

// Name
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldName").'</td><td>';
print '<input size="25" type="text" name="fieldname" value="'.$conf->global->LDAP_FIELD_NAME.'">';
print '</td><td>'.$langs->trans("LDAPFieldNameExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_NAME.'"'.($conf->global->LDAP_KEY_USERS==$conf->global->LDAP_FIELD_NAME?' checked="true"':'')."></td>";
print '</tr>';

// Firstname
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldFirstName").'</td><td>';
print '<input size="25" type="text" name="fieldfirstname" value="'.$conf->global->LDAP_FIELD_FIRSTNAME.'">';
print '</td><td>'.$langs->trans("LDAPFieldFirstNameExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_FIRSTNAME.'"'.($conf->global->LDAP_KEY_USERS==$conf->global->LDAP_FIELD_FIRSTNAME?' checked="true"':'')."></td>";
print '</tr>';

// Login unix
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldLoginUnix").'</td><td>';
print '<input size="25" type="text" name="fieldlogin" value="'.$conf->global->LDAP_FIELD_LOGIN.'">';
print '</td><td>'.$langs->trans("LDAPFieldLoginExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_LOGIN.'"'.($conf->global->LDAP_KEY_USERS==$conf->global->LDAP_FIELD_LOGIN?' checked="true"':'')."></td>";
print '</tr>';

// Login samba
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldLoginSamba").'</td><td>';
print '<input size="25" type="text" name="fieldloginsamba" value="'.$conf->global->LDAP_FIELD_LOGIN_SAMBA.'">';
print '</td><td>'.$langs->trans("LDAPFieldLoginSambaExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_LOGIN_SAMBA.'"'.($conf->global->LDAP_KEY_USERS==$conf->global->LDAP_FIELD_LOGIN_SAMBA?' checked="true"':'')."></td>";
print '</tr>';

// Password not crypted
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldPasswordNotCrypted").'</td><td>';
print '<input size="25" type="text" name="fieldpassword" value="'.$conf->global->LDAP_FIELD_PASSWORD.'">';
print '</td><td>'.$langs->trans("LDAPFieldPasswordExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_PASSWORD.'"'.($conf->global->LDAP_KEY_USERS==$conf->global->LDAP_FIELD_PASSWORD?' checked="true"':'')."></td>";
print '</tr>';

// Password crypted
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldPasswordCrypted").'</td><td>';
print '<input size="25" type="text" name="fieldpasswordcrypted" value="'.$conf->global->LDAP_FIELD_PASSWORD_CRYPTED.'">';
print '</td><td>'.$langs->trans("LDAPFieldPasswordExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_PASSWORD_CRYPTED.'"'.($conf->global->LDAP_KEY_USERS==$conf->global->LDAP_FIELD_PASSWORD_CRYPTED?' checked="true"':'')."></td>";
print '</tr>';

// Mail
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldMail").'</td><td>';
print '<input size="25" type="text" name="fieldmail" value="'.$conf->global->LDAP_FIELD_MAIL.'">';
print '</td><td>'.$langs->trans("LDAPFieldMailExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_MAIL.'"'.($conf->global->LDAP_KEY_USERS==$conf->global->LDAP_FIELD_MAIL?' checked="true"':'')."></td>";
print '</tr>';

// Phone
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldPhone").'</td><td>';
print '<input size="25" type="text" name="fieldphone" value="'.$conf->global->LDAP_FIELD_PHONE.'">';
print '</td><td>'.$langs->trans("LDAPFieldPhoneExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_PHONE.'"'.($conf->global->LDAP_KEY_USERS==$conf->global->LDAP_FIELD_PHONE?' checked="true"':'')."></td>";
print '</tr>';

// Mobile
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldMobile").'</td><td>';
print '<input size="25" type="text" name="fieldmobile" value="'.$conf->global->LDAP_FIELD_MOBILE.'">';
print '</td><td>'.$langs->trans("LDAPFieldMobileExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_MOBILE.'"'.($conf->global->LDAP_KEY_USERS==$conf->global->LDAP_FIELD_MOBILE?' checked="true"':'')."></td>";
print '</tr>';

// Fax
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldFax").'</td><td>';
print '<input size="25" type="text" name="fieldfax" value="'.$conf->global->LDAP_FIELD_FAX.'">';
print '</td><td>'.$langs->trans("LDAPFieldFaxExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_FAX.'"'.($conf->global->LDAP_KEY_USERS==$conf->global->LDAP_FIELD_FAX?' checked="true"':'')."></td>";
print '</tr>';

// Description
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldDescription").'</td><td>';
print '<input size="25" type="text" name="fielddescription" value="'.$conf->global->LDAP_FIELD_DESCRIPTION.'">';
print '</td><td>'.$langs->trans("LDAPFieldDescriptionExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_DESCRIPTION.'"'.($conf->global->LDAP_KEY_GROUPS==$conf->global->LDAP_FIELD_DESCRIPTION?' checked="true"':'')."></td>";
print '</tr>';

// Sid
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldSid").'</td><td>';
print '<input size="25" type="text" name="fieldsid" value="'.$conf->global->LDAP_FIELD_SID.'">';
print '</td><td>'.$langs->trans("LDAPFieldSidExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_SID.'"'.($conf->global->LDAP_KEY_GROUPS==$conf->global->LDAP_FIELD_SID?' checked="true"':'')."></td>";
print '</tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td colspan="4" align="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td></tr>';
print '</table>';

print '</form>';

print '</div>';

print info_admin($langs->trans("LDAPDescValues"));


/*
 * Test de la connexion
 */
if ($conf->global->LDAP_SYNCHRO_ACTIVE == 'dolibarr2ldap')
{
	$butlabel=$langs->trans("LDAPTestSynchroUser");
	$testlabel='testuser';
	$key=$conf->global->LDAP_KEY_USERS;
	$dn=$conf->global->LDAP_USER_DN;
	$objectclass=$conf->global->LDAP_USER_OBJECT_CLASS;

	show_ldap_test_button($butlabel,$testlabel,$key,$dn,$objectclass);
}

if (function_exists("ldap_connect"))
{
	if ($_GET["action"] == 'testuser')
	{
		// Creation objet
		$object=new User($db);
		$object->initAsSpecimen();

		// TODO Mutualize code following with other ldap_xxxx.php pages
		
		// Test synchro
		$ldap=new Ldap();
		$result=$ldap->connect_bind();

		if ($result > 0)
		{
			$info=$object->_load_ldap_info();
			$dn=$object->_load_ldap_dn($info);

			$result2=$ldap->update($dn,$info,$user);
			$result3=$ldap->delete($dn);

			if ($result2 > 0)
			{
				print img_picto('','info').' ';
				print '<font class="ok">'.$langs->trans("LDAPSynchroOK").'</font><br>';
			}
			else
			{
				print img_picto('','error').' ';
				print '<font class="error">'.$langs->trans("LDAPSynchroKOMayBePermissions");
				print ': '.$ldap->error;
				print '</font><br>';
				print $langs->trans("ErrorLDAPMakeManualTest",$conf->ldap->dir_temp).'<br>';
			}
		}
		else
		{
			print img_picto('','error').' ';
			print '<font class="error">'.$langs->trans("LDAPSynchroKO");
			print ': '.$ldap->error;
			print '</font><br>';
			print $langs->trans("ErrorLDAPMakeManualTest",$conf->ldap->dir_temp).'<br>';
		}

		print "<br>\n";
		print "LDAP input file used for test:<br><br>\n";
		print nl2br($ldap->dump_content($dn,$info));
		print "\n<br>";
	}
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
