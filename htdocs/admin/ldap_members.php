<?php
/* Copyright (C) 2004		Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004		Sebastien Di Cintio		<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier			<benoit.mortier@opensides.be>
 * Copyright (C) 2005-2017	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2006-2008	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2011-2013	Juanjo Menent			<jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ldap.lib.php';

$langs->load("admin");
$langs->load("errors");

if (!$user->admin)
  accessforbidden();

$action = GETPOST('action','aZ09');

/*
 * Actions
 */

if ($action == 'setvalue' && $user->admin)
{
	$error=0;

	$db->begin();

	if (! dolibarr_set_const($db, 'LDAP_MEMBER_DN',GETPOST("user"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_OBJECT_CLASS',GETPOST("objectclass"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FILTER',GETPOST("filterconnection"),'chaine',0,'',$conf->entity)) $error++;
	// Members
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_FULLNAME',GETPOST("fieldfullname"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_LOGIN',GETPOST("fieldlogin"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_LOGIN_SAMBA',GETPOST("fieldloginsamba"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_PASSWORD',GETPOST("fieldpassword"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_PASSWORD_CRYPTED',GETPOST("fieldpasswordcrypted"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_NAME',GETPOST("fieldname"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_FIRSTNAME',GETPOST("fieldfirstname"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_MAIL',GETPOST("fieldmail"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_PHONE',GETPOST("fieldphone"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_PHONE_PERSO',GETPOST("fieldphoneperso"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_MOBILE',GETPOST("fieldmobile"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_SKYPE',GETPOST("fieldskype"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_FAX',GETPOST("fieldfax"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_COMPANY',GETPOST("fieldcompany"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_ADDRESS',GETPOST("fieldaddress"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_ZIP',GETPOST("fieldzip"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_TOWN',GETPOST("fieldtown"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_COUNTRY',GETPOST("fieldcountry"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_DESCRIPTION',GETPOST("fielddescription"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_NOTE_PUBLIC',GETPOST("fieldnotepublic"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_FIELD_BIRTHDATE',GETPOST("fieldbirthdate"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_MEMBER_STATUS',GETPOST("fieldstatus"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_MEMBER_END_LASTSUBSCRIPTION', GETPOST("fieldendlastsubscription"),'chaine',0,'',$conf->entity)) $error++;

	// Subscriptions
	if (! dolibarr_set_const($db, 'LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE',  GETPOST("fieldfirstsubscriptiondate"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_AMOUNT',GETPOST("fieldfirstsubscriptionamount"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_DATE',   GETPOST("fieldlastsubscriptiondate"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_AMOUNT', GETPOST("fieldlastsubscriptionamount"),'chaine',0,'',$conf->entity)) $error++;

	// This one must be after the others
	$valkey='';
	$key=GETPOST("key");
	if ($key) $valkey=$conf->global->$key;
	if (! dolibarr_set_const($db, 'LDAP_KEY_MEMBERS',$valkey,'chaine',0,'',$conf->entity)) $error++;

	if (! $error)
	{
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else
	{
		$db->rollback();
		dol_print_error($db);
	}
}



/*
 * View
 */

$form=new Form($db);

llxHeader('',$langs->trans("LDAPSetup"),'EN:Module_LDAP_En|FR:Module_LDAP|ES:M&oacute;dulo_LDAP');
$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans("LDAPSetup"),$linkback,'title_setup');

$head = ldap_prepare_head();

// Test si fonction LDAP actives
if (! function_exists("ldap_connect"))
{
	setEventMessages($langs->trans("LDAPFunctionsNotAvailableOnPHP"), null, 'errors');
}

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?action=setvalue">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

dol_fiche_head($head, 'members', $langs->trans("LDAPSetup"), -1);


print $langs->trans("LDAPDescMembers").'<br>';
print '<br>';


print '<table class="noborder" width="100%">';
$var=true;


print '<tr class="liste_titre">';
print '<td colspan="4">'.$langs->trans("LDAPSynchronizeMembers").'</td>';
print "</tr>\n";

// DN Pour les adherents

print '<tr class="oddeven"><td width="25%"><span class="fieldrequired">'.$langs->trans("LDAPMemberDn").'</span></td><td>';
print '<input size="48" type="text" name="user" value="'.$conf->global->LDAP_MEMBER_DN.'">';
print '</td><td>'.$langs->trans("LDAPMemberDnExample").'</td>';
print '<td>&nbsp;</td>';
print '</tr>';

// List of object class used to define attributes in structure

print '<tr class="oddeven"><td width="25%"><span class="fieldrequired">'.$langs->trans("LDAPMemberObjectClassList").'</span></td><td>';
print '<input size="48" type="text" name="objectclass" value="'.$conf->global->LDAP_MEMBER_OBJECT_CLASS.'">';
print '</td><td>'.$langs->trans("LDAPMemberObjectClassListExample").'</td>';
print '<td>&nbsp;</td>';
print '</tr>';

// Filter, used to filter search

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFilterConnection").'</td><td>';
print '<input size="48" type="text" name="filterconnection" value="'.$conf->global->LDAP_MEMBER_FILTER.'">';
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

// Filtre

// Common name

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldFullname").'</td><td>';
print '<input size="25" type="text" name="fieldfullname" value="'.$conf->global->LDAP_MEMBER_FIELD_FULLNAME.'">';
print '</td><td>'.$langs->trans("LDAPFieldFullnameExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="LDAP_MEMBER_FIELD_FULLNAME"'.(($conf->global->LDAP_KEY_MEMBERS && $conf->global->LDAP_KEY_MEMBERS==$conf->global->LDAP_MEMBER_FIELD_FULLNAME)?' checked':'')."></td>";
print '</tr>';

// Name

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldName").'</td><td>';
print '<input size="25" type="text" name="fieldname" value="'.$conf->global->LDAP_MEMBER_FIELD_NAME.'">';
print '</td><td>'.$langs->trans("LDAPFieldNameExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="LDAP_MEMBER_FIELD_NAME"'.(($conf->global->LDAP_KEY_MEMBERS && $conf->global->LDAP_KEY_MEMBERS==$conf->global->LDAP_MEMBER_FIELD_NAME)?' checked':'')."></td>";
print '</tr>';

// Firstname

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldFirstName").'</td><td>';
print '<input size="25" type="text" name="fieldfirstname" value="'.$conf->global->LDAP_MEMBER_FIELD_FIRSTNAME.'">';
print '</td><td>'.$langs->trans("LDAPFieldFirstNameExample").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Login unix

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldLoginUnix").'</td><td>';
print '<input size="25" type="text" name="fieldlogin" value="'.$conf->global->LDAP_MEMBER_FIELD_LOGIN.'">';
print '</td><td>'.$langs->trans("LDAPFieldLoginExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="LDAP_MEMBER_FIELD_LOGIN"'.(($conf->global->LDAP_KEY_MEMBERS && $conf->global->LDAP_KEY_MEMBERS==$conf->global->LDAP_MEMBER_FIELD_LOGIN)?' checked':'')."></td>";
print '</tr>';

// Login samba

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldLoginSamba").'</td><td>';
print '<input size="25" type="text" name="fieldloginsamba" value="'.$conf->global->LDAP_MEMBER_FIELD_LOGIN_SAMBA.'">';
print '</td><td>'.$langs->trans("LDAPFieldLoginSambaExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="LDAP_MEMBER_FIELD_LOGIN_SAMBA"'.(($conf->global->LDAP_KEY_MEMBERS && $conf->global->LDAP_KEY_MEMBERS==$conf->global->LDAP_MEMBER_FIELD_LOGIN_SAMBA)?' checked':'')."></td>";
print '</tr>';

// Password not crypted

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldPasswordNotCrypted").'</td><td>';
print '<input size="25" type="text" name="fieldpassword" value="'.$conf->global->LDAP_MEMBER_FIELD_PASSWORD.'">';
print '</td><td>'.$langs->trans("LDAPFieldPasswordExample").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Password crypted

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldPasswordCrypted").'</td><td>';
print '<input size="25" type="text" name="fieldpasswordcrypted" value="'.$conf->global->LDAP_MEMBER_FIELD_PASSWORD_CRYPTED.'">';
print '</td><td>'.$langs->trans("LDAPFieldPasswordExample").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Mail

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldMail").'</td><td>';
print '<input size="25" type="text" name="fieldmail" value="'.$conf->global->LDAP_MEMBER_FIELD_MAIL.'">';
print '</td><td>'.$langs->trans("LDAPFieldMailExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="LDAP_MEMBER_FIELD_MAIL"'.(($conf->global->LDAP_KEY_MEMBERS && $conf->global->LDAP_KEY_MEMBERS==$conf->global->LDAP_MEMBER_FIELD_MAIL)?' checked':'')."></td>";
print '</tr>';

// Phone pro

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldPhone").'</td><td>';
print '<input size="25" type="text" name="fieldphone" value="'.$conf->global->LDAP_MEMBER_FIELD_PHONE.'">';
print '</td><td>'.$langs->trans("LDAPFieldPhoneExample").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Phone perso

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldHomePhone").'</td><td>';
print '<input size="25" type="text" name="fieldphoneperso" value="'.$conf->global->LDAP_MEMBER_FIELD_PHONE_PERSO.'">';
print '</td><td>'.$langs->trans("LDAPFieldHomePhoneExample").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Mobile

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldMobile").'</td><td>';
print '<input size="25" type="text" name="fieldmobile" value="'.$conf->global->LDAP_MEMBER_FIELD_MOBILE.'">';
print '</td><td>'.$langs->trans("LDAPFieldMobileExample").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Skype

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldSkype").'</td><td>';
print '<input size="25" type="text" name="fieldskype" value="'.$conf->global->LDAP_MEMBER_FIELD_SKYPE.'">';
print '</td><td>'.$langs->trans("LDAPFieldSkypeExample").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Fax

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldFax").'</td><td>';
print '<input size="25" type="text" name="fieldfax" value="'.$conf->global->LDAP_MEMBER_FIELD_FAX.'">';
print '</td><td>'.$langs->trans("LDAPFieldFaxExample").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Company

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldCompany").'</td><td>';
print '<input size="25" type="text" name="fieldcompany" value="'.$conf->global->LDAP_MEMBER_FIELD_COMPANY.'">';
print '</td><td>'.$langs->trans("LDAPFieldCompanyExample").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Address

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldAddress").'</td><td>';
print '<input size="25" type="text" name="fieldaddress" value="'.$conf->global->LDAP_MEMBER_FIELD_ADDRESS.'">';
print '</td><td>'.$langs->trans("LDAPFieldAddressExample").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// ZIP

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldZip").'</td><td>';
print '<input size="25" type="text" name="fieldzip" value="'.$conf->global->LDAP_MEMBER_FIELD_ZIP.'">';
print '</td><td>'.$langs->trans("LDAPFieldZipExample").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// TOWN

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldTown").'</td><td>';
print '<input size="25" type="text" name="fieldtown" value="'.$conf->global->LDAP_MEMBER_FIELD_TOWN.'">';
print '</td><td>'.$langs->trans("LDAPFieldTownExample").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// COUNTRY

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldCountry").'</td><td>';
print '<input size="25" type="text" name="fieldcountry" value="'.$conf->global->LDAP_MEMBER_FIELD_COUNTRY.'">';
print '</td><td>&nbsp;</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Description

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldDescription").'</td><td>';
print '<input size="25" type="text" name="fielddescription" value="'.$conf->global->LDAP_MEMBER_FIELD_DESCRIPTION.'">';
print '</td><td>'.$langs->trans("LDAPFieldDescriptionExample").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Public Note

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldNotePublic").'</td><td>';
print '<input size="25" type="text" name="fieldnotepublic" value="'.$conf->global->LDAP_MEMBER_FIELD_NOTE_PUBLIC.'">';
print '</td><td>'.$langs->trans("LDAPFieldNotePublicExample").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Birthday

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldBirthdate").'</td><td>';
print '<input size="25" type="text" name="fieldbirthdate" value="'.$conf->global->LDAP_MEMBER_FIELD_BIRTHDATE.'">';
print '</td><td>&nbsp;</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Status

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldStatus").'</td><td>';
print '<input size="25" type="text" name="fieldstatus" value="'.$conf->global->LDAP_FIELD_MEMBER_STATUS.'">';
print '</td><td>&nbsp;</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// First subscription date

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldFirstSubscriptionDate").'</td><td>';
print '<input size="25" type="text" name="fieldfirstsubscriptiondate" value="'.$conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE.'">';
print '</td><td>&nbsp;</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// First subscription amount

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldFirstSubscriptionAmount").'</td><td>';
print '<input size="25" type="text" name="fieldfirstsubscriptionamount" value="'.$conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_AMOUNT.'">';
print '</td><td>&nbsp;</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Last subscription date

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldLastSubscriptionDate").'</td><td>';
print '<input size="25" type="text" name="fieldlastsubscriptiondate" value="'.$conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_DATE.'">';
print '</td><td>&nbsp;</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// Last subscription amount

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldLastSubscriptionAmount").'</td><td>';
print '<input size="25" type="text" name="fieldlastsubscriptionamount" value="'.$conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_AMOUNT.'">';
print '</td><td>&nbsp;</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

// End last subscriptions

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldEndLastSubscription").'</td><td>';
print '<input size="25" type="text" name="fieldendlastsubscription" value="'.$conf->global->LDAP_FIELD_MEMBER_END_LASTSUBSCRIPTION.'">';
print '</td><td>&nbsp;</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

print '</table>';

print info_admin($langs->trans("LDAPDescValues"));

dol_fiche_end();

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></div>';

print '</form>';



/*
 * Test de la connexion
 */
if (! empty($conf->global->LDAP_MEMBER_ACTIVE))
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


llxFooter();

$db->close();
