<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005      Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2016 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2019       Abbes Bahfir            <dolipar@dolipar.org>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *   	\file       htdocs/admin/ldap_users.php
 *		\ingroup    ldap
 *		\brief      Page d'administration/configuration du module Ldap
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ldap.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'errors'));

if (!$user->admin)
  accessforbidden();

$action = GETPOST('action', 'aZ09');

/*
 * Actions
 */

if ($action == 'setvalue' && $user->admin)
{
	$error = 0;
	$db->begin();

	if (!dolibarr_set_const($db, 'LDAP_USER_DN', GETPOST("user"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_USER_OBJECT_CLASS', GETPOST("objectclass"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_FILTER_CONNECTION', GETPOST("filterconnection"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_FIELD_FULLNAME', GETPOST("fieldfullname"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_FIELD_LOGIN', GETPOST("fieldlogin"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_FIELD_LOGIN_SAMBA', GETPOST("fieldloginsamba"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_FIELD_PASSWORD', GETPOST("fieldpassword"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_FIELD_PASSWORD_CRYPTED', GETPOST("fieldpasswordcrypted"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_FIELD_NAME', GETPOST("fieldname"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_FIELD_FIRSTNAME', GETPOST("fieldfirstname"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_FIELD_MAIL', GETPOST("fieldmail"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_FIELD_PHONE', GETPOST("fieldphone"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_FIELD_MOBILE', GETPOST("fieldmobile"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_FIELD_SKYPE', GETPOST("fieldskype"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_FIELD_FAX', GETPOST("fieldfax"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_FIELD_COMPANY', GETPOST("fieldcompany"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_FIELD_ADDRESS', GETPOST("fieldaddress"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_FIELD_ZIP', GETPOST("fieldzip"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_FIELD_TOWN', GETPOST("fieldtown"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_FIELD_COUNTRY', GETPOST("fieldcountry"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_FIELD_DESCRIPTION', GETPOST("fielddescription"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_FIELD_SID', GETPOST("fieldsid"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_FIELD_TITLE', GETPOST("fieldtitle"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_FIELD_GROUPID', GETPOST("fieldgroupid"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_FIELD_USERID', GETPOST("fielduserid"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_FIELD_HOMEDIRECTORY', GETPOST("fieldhomedirectory"), 'chaine', 0, '', $conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_FIELD_HOMEDIRECTORYPREFIX', GETPOST("fieldhomedirectoryprefix"), 'chaine', 0, '', $conf->entity)) $error++;

	// This one must be after the others
	$valkey = '';
	$key = GETPOST("key");
	if ($key) $valkey = $conf->global->$key;
	if (!dolibarr_set_const($db, 'LDAP_KEY_USERS', $valkey, 'chaine', 0, '', $conf->entity)) $error++;

	if (!$error)
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
 * Visu
 */

$form = new Form($db);

llxHeader('', $langs->trans("LDAPSetup"), 'EN:Module_LDAP_En|FR:Module_LDAP|ES:M&oacute;dulo_LDAP');
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans("LDAPSetup"), $linkback, 'title_setup');

$head = ldap_prepare_head();

// Test si fonction LDAP actives
if (!function_exists("ldap_connect"))
{
	setEventMessages($langs->trans("LDAPFunctionsNotAvailableOnPHP"), null, 'errors');
}


print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?action=setvalue">';
print '<input type="hidden" name="token" value="'.newToken().'">';


dol_fiche_head($head, 'users', $langs->trans("LDAPSetup"), -1);

print $langs->trans("LDAPDescUsers").'<br>';
print '<br>';


print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td colspan="4">'.$langs->trans("LDAPSynchronizeUsers").'</td>';
print "</tr>\n";

// DN Pour les utilisateurs
print '<tr class="oddeven"><td width="25%"><span class="fieldrequired">'.$langs->trans("LDAPUserDn").'</span></td><td>';
print '<input size="48" type="text" name="user" value="'.$conf->global->LDAP_USER_DN.'">';
print '</td><td>'.$langs->trans("LDAPUserDnExample").'</td>';
print '<td>&nbsp;</td>';
print '</tr>';

// List of object class used to define attributes in structure
print '<tr class="oddeven"><td width="25%"><span class="fieldrequired">'.$langs->trans("LDAPUserObjectClassList").'</span></td><td>';
print '<input size="48" type="text" name="objectclass" value="'.$conf->global->LDAP_USER_OBJECT_CLASS.'">';
print '</td><td>'.$langs->trans("LDAPUserObjectClassListExample").'</td>';
print '<td>&nbsp;</td>';
print '</tr>';

// Filter, used to filter search
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFilterConnection").'</td><td>';
print '<input size="48" type="text" name="filterconnection" value="'.$conf->global->LDAP_FILTER_CONNECTION.'">';
print '</td><td>'.$langs->trans("LDAPFilterConnectionExample").'</td>';
print '<td></td>';
print '</tr>';

print '</table>';
print '<br>';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td width="25%">'.$langs->trans("LDAPDolibarrMapping").'</td>';
print '<td colspan="2">'.$langs->trans("LDAPLdapMapping").'</td>';
print '<td class="right">'.$langs->trans("LDAPNamingAttribute").'</td>';
print "</tr>\n";

// Common name
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldFullname").'</td><td>';
print '<input size="25" type="text" name="fieldfullname" value="'.$conf->global->LDAP_FIELD_FULLNAME.'">';
print '</td><td>'.$langs->trans("LDAPFieldFullnameExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_FIELD_FULLNAME"'.(($conf->global->LDAP_KEY_USERS && $conf->global->LDAP_KEY_USERS == $conf->global->LDAP_FIELD_FULLNAME) ? ' checked' : '')."></td>";
print '</tr>';

// Name
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldName").'</td><td>';
print '<input size="25" type="text" name="fieldname" value="'.$conf->global->LDAP_FIELD_NAME.'">';
print '</td><td>'.$langs->trans("LDAPFieldNameExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_FIELD_NAME"'.(($conf->global->LDAP_KEY_USERS && $conf->global->LDAP_KEY_USERS == $conf->global->LDAP_FIELD_NAME) ? ' checked' : '')."></td>";
print '</tr>';

// Firstname
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldFirstName").'</td><td>';
print '<input size="25" type="text" name="fieldfirstname" value="'.$conf->global->LDAP_FIELD_FIRSTNAME.'">';
print '</td><td>'.$langs->trans("LDAPFieldFirstNameExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_FIELD_FIRSTNAME"'.(($conf->global->LDAP_KEY_USERS && $conf->global->LDAP_KEY_USERS == $conf->global->LDAP_FIELD_FIRSTNAME) ? ' checked' : '')."></td>";
print '</tr>';

// Login unix
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldLoginUnix").'</td><td>';
print '<input size="25" type="text" name="fieldlogin" value="'.$conf->global->LDAP_FIELD_LOGIN.'">';
print '</td><td>'.$langs->trans("LDAPFieldLoginExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_FIELD_LOGIN"'.(($conf->global->LDAP_KEY_USERS && $conf->global->LDAP_KEY_USERS == $conf->global->LDAP_FIELD_LOGIN) ? ' checked' : '')."></td>";
print '</tr>';

// Login samba
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldLoginSamba").'</td><td>';
print '<input size="25" type="text" name="fieldloginsamba" value="'.$conf->global->LDAP_FIELD_LOGIN_SAMBA.'">';
print '</td><td>'.$langs->trans("LDAPFieldLoginSambaExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_FIELD_LOGIN_SAMBA"'.(($conf->global->LDAP_KEY_USERS && $conf->global->LDAP_KEY_USERS == $conf->global->LDAP_FIELD_LOGIN_SAMBA) ? ' checked' : '')."></td>";
print '</tr>';

// Password not crypted
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldPasswordNotCrypted").'</td><td>';
print '<input size="25" type="text" name="fieldpassword" value="'.$conf->global->LDAP_FIELD_PASSWORD.'">';
print '</td><td>'.$langs->trans("LDAPFieldPasswordExample").'</td>';
print '<td class="right">&nbsp;</td>';
print '</tr>';

// Password crypted
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldPasswordCrypted").'</td><td>';
print '<input size="25" type="text" name="fieldpasswordcrypted" value="'.$conf->global->LDAP_FIELD_PASSWORD_CRYPTED.'">';
print '</td><td>'.$langs->trans("LDAPFieldPasswordExample").'</td>';
print '<td class="right">&nbsp;</td>';
print '</tr>';

// Mail
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldMail").'</td><td>';
print '<input size="25" type="text" name="fieldmail" value="'.$conf->global->LDAP_FIELD_MAIL.'">';
print '</td><td>'.$langs->trans("LDAPFieldMailExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_FIELD_MAIL"'.(($conf->global->LDAP_KEY_USERS && $conf->global->LDAP_KEY_USERS == $conf->global->LDAP_FIELD_MAIL) ? ' checked' : '')."></td>";
print '</tr>';

// Phone
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldPhone").'</td><td>';
print '<input size="25" type="text" name="fieldphone" value="'.$conf->global->LDAP_FIELD_PHONE.'">';
print '</td><td>'.$langs->trans("LDAPFieldPhoneExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_FIELD_PHONE"'.(($conf->global->LDAP_KEY_USERS && $conf->global->LDAP_KEY_USERS == $conf->global->LDAP_FIELD_PHONE) ? ' checked' : '')."></td>";
print '</tr>';

// Mobile
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldMobile").'</td><td>';
print '<input size="25" type="text" name="fieldmobile" value="'.$conf->global->LDAP_FIELD_MOBILE.'">';
print '</td><td>'.$langs->trans("LDAPFieldMobileExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_FIELD_MOBILE"'.(($conf->global->LDAP_KEY_USERS && $conf->global->LDAP_KEY_USERS == $conf->global->LDAP_FIELD_MOBILE) ? ' checked' : '')."></td>";
print '</tr>';

// Skype
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldSkype").'</td><td>';
print '<input size="25" type="text" name="fieldskype" value="'.$conf->global->LDAP_FIELD_SKYPE.'">';
print '</td><td>'.$langs->trans("LDAPFieldSkypeExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_FIELD_SKYPE"'.(($conf->global->LDAP_KEY_USERS && $conf->global->LDAP_KEY_USERS == $conf->global->LDAP_FIELD_SKYPE) ? ' checked' : '')."></td>";
print '</tr>';

// Fax
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldFax").'</td><td>';
print '<input size="25" type="text" name="fieldfax" value="'.$conf->global->LDAP_FIELD_FAX.'">';
print '</td><td>'.$langs->trans("LDAPFieldFaxExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_FIELD_FAX"'.(($conf->global->LDAP_KEY_USERS && $conf->global->LDAP_KEY_USERS == $conf->global->LDAP_FIELD_FAX) ? ' checked' : '')."></td>";
print '</tr>';

// Company
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldCompany").'</td><td>';
print '<input size="25" type="text" name="fieldcompany" value="'.$conf->global->LDAP_FIELD_COMPANY.'">';
print '</td><td>'.$langs->trans("LDAPFieldCompanyExample").'</td>';
print '<td class="right">&nbsp;</td>';
print '</tr>';

// Address
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldAddress").'</td><td>';
print '<input size="25" type="text" name="fieldaddress" value="'.$conf->global->LDAP_FIELD_ADDRESS.'">';
print '</td><td>'.$langs->trans("LDAPFieldAddressExample").'</td>';
print '<td class="right">&nbsp;</td>';
print '</tr>';

// ZIP
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldZip").'</td><td>';
print '<input size="25" type="text" name="fieldzip" value="'.$conf->global->LDAP_FIELD_ZIP.'">';
print '</td><td>'.$langs->trans("LDAPFieldZipExample").'</td>';
print '<td class="right">&nbsp;</td>';
print '</tr>';

// TOWN
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldTown").'</td><td>';
print '<input size="25" type="text" name="fieldtown" value="'.$conf->global->LDAP_FIELD_TOWN.'">';
print '</td><td>'.$langs->trans("LDAPFieldTownExample").'</td>';
print '<td class="right">&nbsp;</td>';
print '</tr>';

// COUNTRY
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldCountry").'</td><td>';
print '<input size="25" type="text" name="fieldcountry" value="'.$conf->global->LDAP_FIELD_COUNTRY.'">';
print '</td><td>&nbsp;</td>';
print '<td class="right">&nbsp;</td>';
print '</tr>';

// Title
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldTitle").'</td><td>';
print '<input size="25" type="text" name="fieldtitle" value="'.$conf->global->LDAP_FIELD_TITLE.'">';
print '</td><td>'.$langs->trans("LDAPFieldTitleExample").'</td>';
print '<td class="right">&nbsp;</td>';
print '</tr>';

// Note
print '<tr class="oddeven"><td>'.$langs->trans("Note").'</td><td>';
print '<input size="25" type="text" name="fielddescription" value="'.$conf->global->LDAP_FIELD_DESCRIPTION.'">';
print '</td><td>'.$langs->trans("LDAPFieldDescriptionExample").'</td>';
print '<td class="right">&nbsp;</td>';
print '</tr>';

// Sid
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldSid").'</td><td>';
print '<input size="25" type="text" name="fieldsid" value="'.$conf->global->LDAP_FIELD_SID.'">';
print '</td><td>'.$langs->trans("LDAPFieldSidExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_FIELD_SID"'.(($conf->global->LDAP_KEY_USERS && $conf->global->LDAP_KEY_USERS == $conf->global->LDAP_FIELD_SID) ? ' checked' : '')."></td>";
print '</tr>';

// Group id
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldGroupid").'</td><td>';
print '<input size="25" type="text" name="fieldgroupid" value="'.$conf->global->LDAP_FIELD_GROUPID.'">';
print '</td><td>'.$langs->trans("LDAPFieldGroupidExample").'</td>';
print '<td class="right">&nbsp;</td>';
print '</tr>';

// Userid
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldUserid").'</td><td>';
print '<input size="25" type="text" name="fielduserid" value="'.$conf->global->LDAP_FIELD_USERID.'">';
print '</td><td>'.$langs->trans("LDAPFieldUseridExample").'</td>';
print '<td class="right">&nbsp;</td>';
print '</tr>';

// Home Directory
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldHomedirectory").'</td><td>';
print '<input size="25" type="text" name="fieldhomedirectory" value="'.$conf->global->LDAP_FIELD_HOMEDIRECTORY.'">';
print '</td><td>'.$langs->trans("LDAPFieldHomedirectoryExample").'</td>';
print '<td class="right">&nbsp;</td>';
print '</tr>';

// Home Directory Prefix
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldHomedirectoryprefix").'</td><td>';
print '<input size="25" type="text" name="fieldhomedirectoryprefix" value="'.$conf->global->LDAP_FIELD_HOMEDIRECTORYPREFIX.'">';
print '</td><td></td>';
print '<td class="right">&nbsp;</td>';
print '</tr>';

print '</table>';

print info_admin($langs->trans("LDAPDescValues"));

dol_fiche_end();

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></div>';

print '</form>';


/*
 * Test de la connexion
 */
if ($conf->global->LDAP_SYNCHRO_ACTIVE == 'dolibarr2ldap')
{
	$butlabel = $langs->trans("LDAPTestSynchroUser");
	$testlabel = 'testuser';
	$key = $conf->global->LDAP_KEY_USERS;
	$dn = $conf->global->LDAP_USER_DN;
	$objectclass = $conf->global->LDAP_USER_OBJECT_CLASS;

	show_ldap_test_button($butlabel, $testlabel, $key, $dn, $objectclass);
}
elseif ($conf->global->LDAP_SYNCHRO_ACTIVE == 'ldap2dolibarr')
{
	$butlabel = $langs->trans("LDAPTestSearch");
	$testlabel = 'testsearchuser';
	$key = $conf->global->LDAP_KEY_USERS;
	$dn = $conf->global->LDAP_USER_DN;
	$objectclass = $conf->global->LDAP_USER_OBJECT_CLASS;
	show_ldap_test_button($butlabel, $testlabel, $key, $dn, $objectclass);
}

if (function_exists("ldap_connect"))
{
	if ($action == 'testuser')
	{
		// Creation objet
		$object = new User($db);
		$object->initAsSpecimen();

		// TODO Mutualize code following with other ldap_xxxx.php pages

		// Test synchro
		$ldap = new Ldap();
		$result = $ldap->connect_bind();

		if ($result > 0)
		{
			$info = $object->_load_ldap_info();
			$dn = $object->_load_ldap_dn($info);

			$result1 = $ldap->delete($dn); // To be sure to delete existing records
			$result2 = $ldap->add($dn, $info, $user); // Now the test
			$result3 = $ldap->delete($dn); // Clean what we did

			if ($result2 > 0)
			{
				print img_picto('', 'info').' ';
				print '<font class="ok">'.$langs->trans("LDAPSynchroOK").'</font><br>';
			}
			else
			{
				print img_picto('', 'error').' ';
				print '<font class="error">'.$langs->trans("LDAPSynchroKOMayBePermissions");
				print ': '.$ldap->error;
				print '</font><br>';
				print $langs->trans("ErrorLDAPMakeManualTest", $conf->ldap->dir_temp).'<br>';
			}

			print "<br>\n";
			print "LDAP input file used for test:<br><br>\n";
			print nl2br($ldap->dump_content($dn, $info));
			print "\n<br>";
		}
		else
		{
			print img_picto('', 'error').' ';
			print '<font class="error">'.$langs->trans("LDAPSynchroKO");
			print ': '.$ldap->error;
			print '</font><br>';
			print $langs->trans("ErrorLDAPMakeManualTest", $conf->ldap->dir_temp).'<br>';
		}
	}

	if ($action == 'testsearchuser')
	{
		// Creation objet
		$object = new User($db);
		$object->initAsSpecimen();

		// TODO Mutualize code following with other ldap_xxxx.php pages

		// Test synchro
		$ldap = new Ldap();
		$result = $ldap->connect_bind();

		if ($result > 0)
		{
			$required_fields = array(
				$conf->global->LDAP_KEY_USERS,
				$conf->global->LDAP_FIELD_FULLNAME,
				$conf->global->LDAP_FIELD_NAME,
				$conf->global->LDAP_FIELD_FIRSTNAME,
				$conf->global->LDAP_FIELD_LOGIN,
				$conf->global->LDAP_FIELD_LOGIN_SAMBA,
				$conf->global->LDAP_FIELD_PASSWORD,
				$conf->global->LDAP_FIELD_PASSWORD_CRYPTED,
				$conf->global->LDAP_FIELD_PHONE,
				$conf->global->LDAP_FIELD_FAX,
				$conf->global->LDAP_FIELD_SKYPE,
				$conf->global->LDAP_FIELD_MOBILE,
				$conf->global->LDAP_FIELD_MAIL,
				$conf->global->LDAP_FIELD_TITLE,
				$conf->global->LDAP_FIELD_DESCRIPTION,
				$conf->global->LDAP_FIELD_SID
			);

			// Remove from required_fields all entries not configured in LDAP (empty) and duplicated
			$required_fields = array_unique(array_values(array_filter($required_fields, "dol_validElement")));

			// Get from LDAP database an array of results
			$ldapusers = $ldap->getRecords('*', $conf->global->LDAP_USER_DN, $conf->global->LDAP_KEY_USERS, $required_fields, 1);
			//$ldapusers = $ldap->getRecords('*', $conf->global->LDAP_USER_DN, $conf->global->LDAP_KEY_USERS, '', 1);

			if (is_array($ldapusers))
			{
				$liste = array();
				foreach ($ldapusers as $key => $ldapuser)
				{
					// Define the label string for this user
					$label = '';
					foreach ($required_fields as $value)
					{
						if ($value)
						{
							$label .= $value."=".$ldapuser[$value]." ";
						}
					}
					$liste[$key] = $label;
				}
			}
			else
		    {
				setEventMessages($ldap->error, $ldap->errors, 'errors');
			}

			print "<br>\n";
			print "LDAP search for user:<br>\n";
			print "search: *<br>\n";
			print "userDN: ".$conf->global->LDAP_USER_DN."<br>\n";
			print "useridentifier: ".$conf->global->LDAP_KEY_USERS."<br>\n";
			print "required_fields: ".implode(',', $required_fields)."<br>\n";
			print "=> ".count($liste)." records<br>\n";
			print "\n<br>";
		}
		else
		{
			print img_picto('', 'error').' ';
			print '<font class="error">'.$langs->trans("LDAPSynchroKO");
			print ': '.$ldap->error;
			print '</font><br>';
			print $langs->trans("ErrorLDAPMakeManualTest", $conf->ldap->dir_temp).'<br>';
		}
	}
}

// End of page
llxFooter();
$db->close();
