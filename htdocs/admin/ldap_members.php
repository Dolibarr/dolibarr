<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005      Regis Houssin        <regis@dolibarr.fr>
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
 *   	\file       htdocs/admin/ldap_members.php
 *		\ingroup    ldap member
 *		\brief      Page d'administration/configuration du module Ldap adherent
 *		\version    $Id: ldap_members.php,v 1.35 2011/07/31 22:23:22 eldy Exp $
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent_type.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/ldap.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/ldap.lib.php");

$langs->load("admin");
$langs->load("errors");

if (!$user->admin)
  accessforbidden();


/*
 * Actions
 */

if ($_GET["action"] == 'setvalue' && $user->admin)
{
	$error=0;

	if (! dolibarr_set_const($db, 'LDAP_MEMBER_DN',$_POST["user"],'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_OBJECT_CLASS',$_POST["objectclass"],'chaine',0,'',$conf->entity)) $error++;
	// Members
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_FULLNAME',$_POST["fieldfullname"],'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_LOGIN',$_POST["fieldlogin"],'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_LOGIN_SAMBA',$_POST["fieldloginsamba"],'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_PASSWORD',$_POST["fieldpassword"],'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_PASSWORD_CRYPTED',$_POST["fieldpasswordcrypted"],'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_NAME',$_POST["fieldname"],'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_FIRSTNAME',$_POST["fieldfirstname"],'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_MAIL',$_POST["fieldmail"],'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_PHONE',$_POST["fieldphone"],'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_PHONE_PERSO',$_POST["fieldphoneperso"],'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_MOBILE',$_POST["fieldmobile"],'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_FAX',$_POST["fieldfax"],'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_ADDRESS',$_POST["fieldaddress"],'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_ZIP',$_POST["fieldzip"],'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_TOWN',$_POST["fieldtown"],'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_COUNTRY',$_POST["fieldcountry"],'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_DESCRIPTION',$_POST["fielddescription"],'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_BIRTHDATE',$_POST["fieldbirthdate"],'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_MEMBER_STATUS',$_POST["fieldstatus"],'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_MEMBER_END_LASTSUBSCRIPTION', $_POST["fieldendlastsubscription"],'chaine',0,'',$conf->entity)) $error++;

	// Subscriptions
	if (! dolibarr_set_const($db, 'LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE',  $_POST["fieldfirstsubscriptiondate"],'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_AMOUNT',$_POST["fieldfirstsubscriptionamount"],'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_DATE',   $_POST["fieldlastsubscriptiondate"],'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_AMOUNT', $_POST["fieldlastsubscriptionamount"],'chaine',0,'',$conf->entity)) $error++;

    // This one must be after the others
    $valkey='';
    $key=$_POST["key"];
    if ($key) $valkey=$conf->global->$key;
    if (! dolibarr_set_const($db, 'LDAP_KEY_MEMBERS',$valkey,'chaine',0,'',$conf->entity)) $error++;

	if ($error)
	{
		dol_print_error($db->error());
	}
}



/*
 * View
 */

llxHeader('',$langs->trans("LDAPSetup"),'EN:Module_LDAP_En|FR:Module_LDAP|ES:M&oacute;dulo_LDAP');

print_fiche_titre($langs->trans("LDAPSetup"),'','setup');

$head = ldap_prepare_head();

// Test si fonction LDAP actives
if (! function_exists("ldap_connect"))
{
	$mesg=$langs->trans("LDAPFunctionsNotAvailableOnPHP");
}

if ($mesg) print '<div class="error">'.$mesg.'</div>';


dol_fiche_head($head, 'members', $langs->trans("LDAPSetup"));


print $langs->trans("LDAPDescMembers").'<br>';
print '<br>';


print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?action=setvalue">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

$html=new Form($db);

print '<table class="noborder" width="100%">';
$var=true;


print '<tr class="liste_titre">';
print '<td colspan="4">'.$langs->trans("LDAPSynchronizeMembers").'</td>';
print "</tr>\n";

// DN Pour les adherents
$var=!$var;
print '<tr '.$bc[$var].'><td width="25%"><span class="fieldrequired">'.$langs->trans("LDAPMemberDn").'</span></td><td>';
print '<input size="48" type="text" name="user" value="'.$conf->global->LDAP_MEMBER_DN.'">';
print '</td><td>'.$langs->trans("LDAPMemberDnExample").'</td>';
print '<td>&nbsp;</td>';
print '</tr>';

// List of object class used to define attributes in structure
$var=!$var;
print '<tr '.$bc[$var].'><td width="25%"><span class="fieldrequired">'.$langs->trans("LDAPMemberObjectClassList").'</span></td><td>';
print '<input size="48" type="text" name="objectclass" value="'.$conf->global->LDAP_MEMBER_OBJECT_CLASS.'">';
print '</td><td>'.$langs->trans("LDAPMemberObjectClassListExample").'</td>';
print '<td>&nbsp;</td>';
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

// Filtre

// Common name
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldFullname").'</td><td>';
print '<input size="25" type="text" name="fieldfullname" value="'.$conf->global->LDAP_MEMBER_FIELD_FULLNAME.'">';
print '</td><td>'.$langs->trans("LDAPFieldFullnameExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="LDAP_MEMBER_FIELD_FULLNAME"'.(($conf->global->LDAP_KEY_MEMBERS && $conf->global->LDAP_KEY_MEMBERS==$conf->global->LDAP_MEMBER_FIELD_FULLNAME)?' checked="true"':'')."></td>";
print '</tr>';

// Name
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldName").'</td><td>';
print '<input size="25" type="text" name="fieldname" value="'.$conf->global->LDAP_MEMBER_FIELD_NAME.'">';
print '</td><td>'.$langs->trans("LDAPFieldNameExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="LDAP_MEMBER_FIELD_NAME"'.(($conf->global->LDAP_KEY_MEMBERS && $conf->global->LDAP_KEY_MEMBERS==$conf->global->LDAP_MEMBER_FIELD_NAME)?' checked="true"':'')."></td>";
print '</tr>';

// Firstname
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldFirstName").'</td><td>';
print '<input size="25" type="text" name="fieldfirstname" value="'.$conf->global->LDAP_MEMBER_FIELD_FIRSTNAME.'">';
print '</td><td>'.$langs->trans("LDAPFieldFirstNameExample").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Login unix
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldLoginUnix").'</td><td>';
print '<input size="25" type="text" name="fieldlogin" value="'.$conf->global->LDAP_MEMBER_FIELD_LOGIN.'">';
print '</td><td>'.$langs->trans("LDAPFieldLoginExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="LDAP_MEMBER_FIELD_LOGIN"'.(($conf->global->LDAP_KEY_MEMBERS && $conf->global->LDAP_KEY_MEMBERS==$conf->global->LDAP_MEMBER_FIELD_LOGIN)?' checked="true"':'')."></td>";
print '</tr>';

// Login samba
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldLoginSamba").'</td><td>';
print '<input size="25" type="text" name="fieldloginsamba" value="'.$conf->global->LDAP_MEMBER_FIELD_LOGIN_SAMBA.'">';
print '</td><td>'.$langs->trans("LDAPFieldLoginSambaExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="LDAP_MEMBER_FIELD_LOGIN_SAMBA"'.(($conf->global->LDAP_KEY_MEMBERS && $conf->global->LDAP_KEY_MEMBERS==$conf->global->LDAP_MEMBER_FIELD_LOGIN_SAMBA)?' checked="true"':'')."></td>";
print '</tr>';

// Password not crypted
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldPasswordNotCrypted").'</td><td>';
print '<input size="25" type="text" name="fieldpassword" value="'.$conf->global->LDAP_MEMBER_FIELD_PASSWORD.'">';
print '</td><td>'.$langs->trans("LDAPFieldPasswordExample").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Password crypted
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldPasswordCrypted").'</td><td>';
print '<input size="25" type="text" name="fieldpasswordcrypted" value="'.$conf->global->LDAP_MEMBER_FIELD_PASSWORD_CRYPTED.'">';
print '</td><td>'.$langs->trans("LDAPFieldPasswordExample").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Mail
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldMail").'</td><td>';
print '<input size="25" type="text" name="fieldmail" value="'.$conf->global->LDAP_MEMBER_FIELD_MAIL.'">';
print '</td><td>'.$langs->trans("LDAPFieldMailExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="LDAP_MEMBER_FIELD_MAIL"'.(($conf->global->LDAP_KEY_MEMBERS && $conf->global->LDAP_KEY_MEMBERS==$conf->global->LDAP_MEMBER_FIELD_MAIL)?' checked="true"':'')."></td>";
print '</tr>';

// Phone pro
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldPhone").'</td><td>';
print '<input size="25" type="text" name="fieldphone" value="'.$conf->global->LDAP_MEMBER_FIELD_PHONE.'">';
print '</td><td>'.$langs->trans("LDAPFieldPhoneExample").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Phone perso
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldHomePhone").'</td><td>';
print '<input size="25" type="text" name="fieldphoneperso" value="'.$conf->global->LDAP_MEMBER_FIELD_PHONE_PERSO.'">';
print '</td><td>'.$langs->trans("LDAPFieldHomePhoneExample").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Mobile
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldMobile").'</td><td>';
print '<input size="25" type="text" name="fieldmobile" value="'.$conf->global->LDAP_MEMBER_FIELD_MOBILE.'">';
print '</td><td>'.$langs->trans("LDAPFieldMobileExample").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Fax
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldFax").'</td><td>';
print '<input size="25" type="text" name="fieldfax" value="'.$conf->global->LDAP_MEMBER_FIELD_FAX.'">';
print '</td><td>'.$langs->trans("LDAPFieldFaxExample").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Address
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldAddress").'</td><td>';
print '<input size="25" type="text" name="fieldaddress" value="'.$conf->global->LDAP_MEMBER_FIELD_ADDRESS.'">';
print '</td><td>'.$langs->trans("LDAPFieldAddressExample").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// CP
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldZip").'</td><td>';
print '<input size="25" type="text" name="fieldzip" value="'.$conf->global->LDAP_MEMBER_FIELD_ZIP.'">';
print '</td><td>'.$langs->trans("LDAPFieldZipExample").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Ville
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldTown").'</td><td>';
print '<input size="25" type="text" name="fieldtown" value="'.$conf->global->LDAP_MEMBER_FIELD_TOWN.'">';
print '</td><td>'.$langs->trans("LDAPFieldTownExample").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Pays
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldCountry").'</td><td>';
print '<input size="25" type="text" name="fieldcountry" value="'.$conf->global->LDAP_MEMBER_FIELD_COUNTRY.'">';
print '</td><td>&nbsp;</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Description
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldDescription").'</td><td>';
print '<input size="25" type="text" name="fielddescription" value="'.$conf->global->LDAP_MEMBER_FIELD_DESCRIPTION.'">';
print '</td><td>'.$langs->trans("LDAPFieldDescriptionExample").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Date naissance
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldBirthdate").'</td><td>';
print '<input size="25" type="text" name="fieldbirthdate" value="'.$conf->global->LDAP_MEMBER_FIELD_BIRTHDATE.'">';
print '</td><td>&nbsp;</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Status
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldStatus").'</td><td>';
print '<input size="25" type="text" name="fieldstatus" value="'.$conf->global->LDAP_FIELD_MEMBER_STATUS.'">';
print '</td><td>&nbsp;</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// First subscription date
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldFirstSubscriptionDate").'</td><td>';
print '<input size="25" type="text" name="fieldfirstsubscriptiondate" value="'.$conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE.'">';
print '</td><td>&nbsp;</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// First subscription amount
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldFirstSubscriptionAmount").'</td><td>';
print '<input size="25" type="text" name="fieldfirstsubscriptionamount" value="'.$conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_AMOUNT.'">';
print '</td><td>&nbsp;</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Last subscription date
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldLastSubscriptionDate").'</td><td>';
print '<input size="25" type="text" name="fieldlastsubscriptiondate" value="'.$conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_DATE.'">';
print '</td><td>&nbsp;</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Last subscription amount
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldLastSubscriptionAmount").'</td><td>';
print '<input size="25" type="text" name="fieldlastsubscriptionamount" value="'.$conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_AMOUNT.'">';
print '</td><td>&nbsp;</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// End last subscriptions
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldEndLastSubscription").'</td><td>';
print '<input size="25" type="text" name="fieldendlastsubscription" value="'.$conf->global->LDAP_FIELD_MEMBER_END_LASTSUBSCRIPTION.'">';
print '</td><td>&nbsp;</td>';
print '<td align="right">&nbsp;</td>';
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
if ($conf->global->LDAP_MEMBER_ACTIVE)
{
	$butlabel=$langs->trans("LDAPTestSynchroMember");
	$testlabel='testmember';
	$key=$conf->global->LDAP_KEY_MEMBERS;
	$dn=$conf->global->LDAP_MEMBER_DN;
	$objectclass=$conf->global->LDAP_MEMBER_OBJECT_CLASS;

	show_ldap_test_button($butlabel,$testlabel,$key,$dn,$objectclass);
}

if (function_exists("ldap_connect"))
{
	if ($_GET["action"] == 'testmember')
	{
		// Creation objet
		$object=new Adherent($db);
		$object->initAsSpecimen();

		// Test synchro
		$ldap=new Ldap();
		$result=$ldap->connect_bind();

		if ($result > 0)
		{
			$info=$object->_load_ldap_info();
			$dn=$object->_load_ldap_dn($info);

			$result1=$ldap->delete($dn);			// To be sure to delete existing records
			$result2=$ldap->add($dn,$info,$user);	// Now the test
			$result3=$ldap->delete($dn);			// Clean what we did

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

			print "<br>\n";
			print "LDAP input file used for test:<br><br>\n";
			print nl2br($ldap->dump_content($dn,$info));
			print "\n<br>";
		}
		else
		{
			print img_picto('','error').' ';
			print '<font class="error">'.$langs->trans("LDAPSynchroKO");
			print ': '.$ldap->error;
			print '</font><br>';
			print $langs->trans("ErrorLDAPMakeManualTest",$conf->ldap->dir_temp).'<br>';
		}
	}

}

$db->close();

llxFooter('$Date: 2011/07/31 22:23:22 $ - $Revision: 1.35 $');
?>
