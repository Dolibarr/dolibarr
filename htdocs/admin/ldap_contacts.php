<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005      Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2013 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 * 	\file       htdocs/admin/ldap_contacts.php
 *  \ingroup    ldap
 *  \brief      Page d'administration/configuration du module Ldap
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ldap.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("admin", "errors"));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');

/*
 * Actions
 */

if ($action == 'setvalue' && $user->admin) {
	$error = 0;

	$db->begin();

	if (!dolibarr_set_const($db, 'LDAP_CONTACT_DN', GETPOST("contactdn"), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}
	if (!dolibarr_set_const($db, 'LDAP_CONTACT_OBJECT_CLASS', GETPOST("objectclass"), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}

	if (!dolibarr_set_const($db, 'LDAP_CONTACT_FIELD_FULLNAME', GETPOST("fieldfullname"), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}
	if (!dolibarr_set_const($db, 'LDAP_CONTACT_FIELD_NAME', GETPOST("fieldname"), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}
	if (!dolibarr_set_const($db, 'LDAP_CONTACT_FIELD_FIRSTNAME', GETPOST("fieldfirstname"), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}
	if (!dolibarr_set_const($db, 'LDAP_CONTACT_FIELD_COMPANY', GETPOST("fieldcompany"), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}
	if (!dolibarr_set_const($db, 'LDAP_CONTACT_FIELD_MAIL', GETPOST("fieldmail"), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}
	if (!dolibarr_set_const($db, 'LDAP_CONTACT_FIELD_PHONE', GETPOST("fieldphone"), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}
	if (!dolibarr_set_const($db, 'LDAP_CONTACT_FIELD_HOMEPHONE', GETPOST("fieldhomephone"), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}
	if (!dolibarr_set_const($db, 'LDAP_CONTACT_FIELD_MOBILE', GETPOST("fieldmobile"), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}
	if (!dolibarr_set_const($db, 'LDAP_CONTACT_FIELD_FAX', GETPOST("fieldfax"), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}
	if (!dolibarr_set_const($db, 'LDAP_CONTACT_FIELD_ADDRESS', GETPOST("fieldaddress"), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}
	if (!dolibarr_set_const($db, 'LDAP_CONTACT_FIELD_ZIP', GETPOST("fieldzip"), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}
	if (!dolibarr_set_const($db, 'LDAP_CONTACT_FIELD_TOWN', GETPOST("fieldtown"), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}
	if (!dolibarr_set_const($db, 'LDAP_CONTACT_FIELD_COUNTRY', GETPOST("fieldcountry"), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}

	// This one must be after the others
	$valkey = '';
	$key = GETPOST("key");
	if ($key) {
		$valkey = getDolGlobalString($key);
	}
	if (!dolibarr_set_const($db, 'LDAP_KEY_CONTACTS', $valkey, 'chaine', 0, '', $conf->entity)) {
		$error++;
	}

	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		$db->rollback();
		dol_print_error($db);
	}
}



/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans("LDAPSetup"), 'EN:Module_LDAP_En|FR:Module_LDAP|ES:M&oacute;dulo_LDAP', '', 0, 0, '', '', '', 'mod-admin page-ldap_contacts');
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans("LDAPSetup"), $linkback, 'title_setup');

$head = ldap_prepare_head();

// Test si function LDAP actives
if (!function_exists("ldap_connect")) {
	setEventMessages($langs->trans("LDAPFunctionsNotAvailableOnPHP"), null, 'errors');
}

print dol_get_fiche_head($head, 'contacts', '', -1);


print '<span class="opacitymedium">'.$langs->trans("LDAPDescContact").'</span><br>';
print '<br>';

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?action=setvalue&token='.newToken().'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td colspan="4">'.$langs->trans("LDAPSynchronizeContacts").'</td>';
print "</tr>\n";

// DN Pour les contacts
print '<!-- LDAP_CONTACT_DN -->';
print '<tr class="oddeven"><td><span class="fieldrequired">'.$langs->trans("LDAPContactDn").'</span></td><td>';
print '<input size="48" type="text" name="contactdn" value="' . getDolGlobalString('LDAP_CONTACT_DN').'">';
print '</td><td>'.$langs->trans("LDAPContactDnExample").'</td>';
print '<td>&nbsp;</td>';
print '</tr>';

// List of object class used to define attributes in structure
print '<!-- LDAP_CONTACT_OBJECT_CLASS -->';
print '<tr class="oddeven"><td><span class="fieldrequired">'.$langs->trans("LDAPContactObjectClassList").'</span></td><td>';
print '<input size="48" type="text" name="objectclass" value="' . getDolGlobalString('LDAP_CONTACT_OBJECT_CLASS').'">';
print '</td><td>'.$langs->trans("LDAPContactObjectClassListExample").'</td>';
print '<td>&nbsp;</td>';
print '</tr>';

print '</table>';
print '<br>';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("LDAPDolibarrMapping").'</td>';
print '<td colspan="2">'.$langs->trans("LDAPLdapMapping").'</td>';
print '<td class="right">'.$langs->trans("LDAPNamingAttribute").'</td>';
print "</tr>\n";

// Common name
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldFullname").'</td><td>';
print '<input size="25" type="text" name="fieldfullname" value="' . getDolGlobalString('LDAP_CONTACT_FIELD_FULLNAME').'">';
print '</td><td>'.$langs->trans("LDAPFieldFullnameExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_CONTACT_FIELD_FULLNAME"'.($conf->global->LDAP_KEY_CONTACTS && $conf->global->LDAP_KEY_CONTACTS == $conf->global->LDAP_CONTACT_FIELD_FULLNAME ? ' checked' : '')."></td>";
print '</tr>';

// Name
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldName").'</td><td>';
print '<input size="25" type="text" name="fieldname" value="' . getDolGlobalString('LDAP_CONTACT_FIELD_NAME').'">';
print '</td><td>'.$langs->trans("LDAPFieldNameExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_CONTACT_FIELD_NAME"'.($conf->global->LDAP_KEY_CONTACTS && $conf->global->LDAP_KEY_CONTACTS == $conf->global->LDAP_CONTACT_FIELD_NAME ? ' checked' : '')."></td>";
print '</tr>';

// Firstname
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldFirstName").'</td><td>';
print '<input size="25" type="text" name="fieldfirstname" value="' . getDolGlobalString('LDAP_CONTACT_FIELD_FIRSTNAME').'">';
print '</td><td>'.$langs->trans("LDAPFieldFirstNameExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_CONTACT_FIELD_FIRSTNAME"'.($conf->global->LDAP_KEY_CONTACTS && $conf->global->LDAP_KEY_CONTACTS == $conf->global->LDAP_CONTACT_FIELD_FIRSTNAME ? ' checked' : '')."></td>";
print '</tr>';

// Company
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldCompany").'</td><td>';
print '<input size="25" type="text" name="fieldcompany" value="' . getDolGlobalString('LDAP_CONTACT_FIELD_COMPANY').'">';
print '</td><td>'.$langs->trans("LDAPFieldCompanyExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_CONTACT_FIELD_COMPANY"'.($conf->global->LDAP_KEY_CONTACTS && $conf->global->LDAP_KEY_CONTACTS == $conf->global->LDAP_CONTACT_FIELD_COMPANY ? ' checked' : '')."></td>";
print '</tr>';

// Mail
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldMail").'</td><td>';
print '<input size="25" type="text" name="fieldmail" value="' . getDolGlobalString('LDAP_CONTACT_FIELD_MAIL').'">';
print '</td><td>'.$langs->trans("LDAPFieldMailExample").'</td>';
print '<td class="right"><input type="radio" name="key" value=">LDAP_CONTACT_FIELD_MAIL"'.($conf->global->LDAP_KEY_CONTACTS && $conf->global->LDAP_KEY_CONTACTS == $conf->global->LDAP_CONTACT_FIELD_MAIL ? ' checked' : '')."></td>";
print '</tr>';

// Phone pro
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldPhone").'</td><td>';
print '<input size="25" type="text" name="fieldphone" value="' . getDolGlobalString('LDAP_CONTACT_FIELD_PHONE').'">';
print '</td><td>'.$langs->trans("LDAPFieldPhoneExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_CONTACT_FIELD_PHONE"'.($conf->global->LDAP_KEY_CONTACTS && $conf->global->LDAP_KEY_CONTACTS == $conf->global->LDAP_CONTACT_FIELD_PHONE ? ' checked' : '')."></td>";
print '</tr>';

// Phone home
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldHomePhone").'</td><td>';
print '<input size="25" type="text" name="fieldhomephone" value="' . getDolGlobalString('LDAP_CONTACT_FIELD_HOMEPHONE').'">';
print '</td><td>'.$langs->trans("LDAPFieldHomePhoneExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_CONTACT_FIELD_HOMEPHONE"'.($conf->global->LDAP_KEY_CONTACTS && $conf->global->LDAP_KEY_CONTACTS == $conf->global->LDAP_CONTACT_FIELD_HOMEPHONE ? ' checked' : '')."></td>";
print '</tr>';

// Mobile
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldMobile").'</td><td>';
print '<input size="25" type="text" name="fieldmobile" value="' . getDolGlobalString('LDAP_CONTACT_FIELD_MOBILE').'">';
print '</td><td>'.$langs->trans("LDAPFieldMobileExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_CONTACT_FIELD_MOBILE"'.($conf->global->LDAP_KEY_CONTACTS && $conf->global->LDAP_KEY_CONTACTS == $conf->global->LDAP_CONTACT_FIELD_MOBILE ? ' checked' : '')."></td>";
print '</tr>';

// Fax
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldFax").'</td><td>';
print '<input size="25" type="text" name="fieldfax" value="' . getDolGlobalString('LDAP_CONTACT_FIELD_FAX').'">';
print '</td><td>'.$langs->trans("LDAPFieldFaxExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_CONTACT_FIELD_FAX"'.($conf->global->LDAP_KEY_CONTACTS && $conf->global->LDAP_KEY_CONTACTS == $conf->global->LDAP_CONTACT_FIELD_FAX ? ' checked' : '')."></td>";
print '</tr>';

// Address
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldAddress").'</td><td>';
print '<input size="25" type="text" name="fieldaddress" value="' . getDolGlobalString('LDAP_CONTACT_FIELD_ADDRESS').'">';
print '</td><td>'.$langs->trans("LDAPFieldAddressExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_CONTACT_FIELD_ADDRESS"'.($conf->global->LDAP_KEY_CONTACTS && $conf->global->LDAP_KEY_CONTACTS == $conf->global->LDAP_CONTACT_FIELD_ADDRESS ? ' checked' : '')."></td>";
print '</tr>';

// ZIP
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldZip").'</td><td>';
print '<input size="25" type="text" name="fieldzip" value="' . getDolGlobalString('LDAP_CONTACT_FIELD_ZIP').'">';
print '</td><td>'.$langs->trans("LDAPFieldZipExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_CONTACT_FIELD_ZIP"'.($conf->global->LDAP_KEY_CONTACTS && $conf->global->LDAP_KEY_CONTACTS == $conf->global->LDAP_CONTACT_FIELD_ZIP ? ' checked' : '')."></td>";
print '</tr>';

// TOWN
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldTown").'</td><td>';
print '<input size="25" type="text" name="fieldtown" value="' . getDolGlobalString('LDAP_CONTACT_FIELD_TOWN').'">';
print '</td><td>'.$langs->trans("LDAPFieldTownExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_CONTACT_FIELD_TOWN"'.($conf->global->LDAP_KEY_CONTACTS && $conf->global->LDAP_KEY_CONTACTS == $conf->global->LDAP_CONTACT_FIELD_TOWN ? ' checked' : '')."></td>";
print '</tr>';

// COUNTRY
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldCountry").'</td><td>';
print '<input size="25" type="text" name="fieldcountry" value="' . getDolGlobalString('LDAP_CONTACT_FIELD_COUNTRY').'">';
print '</td><td>&nbsp;</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_CONTACT_FIELD_COUNTRY"'.($conf->global->LDAP_KEY_CONTACTS && $conf->global->LDAP_KEY_CONTACTS == $conf->global->LDAP_CONTACT_FIELD_COUNTRY ? ' checked' : '')."></td>";
print '</tr>';

print '</table>';

print info_admin($langs->trans("LDAPDescValues"));

print dol_get_fiche_end();

print $form->buttonsSaveCancel("Modify", '');

print '</form>';


/*
 * Test the connection
 */
$butlabel = $langs->trans("LDAPTestSynchroContact");
$testlabel = 'test';
$key = getDolGlobalString('LDAP_KEY_CONTACTS');
$dn = getDolGlobalString('LDAP_CONTACT_DN');
$objectclass = getDolGlobalString('LDAP_CONTACT_OBJECT_CLASS');

show_ldap_test_button($butlabel, $testlabel, $key, $dn, $objectclass);


if (function_exists("ldap_connect")) {
	if ($action == 'test') {
		// Create object
		$object = new Contact($db);
		$object->initAsSpecimen();

		// Test synchro
		$ldap = new Ldap();
		$result = $ldap->connectBind();

		if ($result > 0) {
			$info = $object->_load_ldap_info();
			$dn = $object->_load_ldap_dn($info);

			$result1 = $ldap->delete($dn); // To be sure to delete existing records
			$result2 = $ldap->add($dn, $info, $user); // Now the test
			$result3 = $ldap->delete($dn); // Clean what we did

			if ($result2 > 0) {
				print img_picto('', 'info').' ';
				print '<span class="ok">'.$langs->trans("LDAPSynchroOK").'</span><br>';
			} else {
				print img_picto('', 'error').' ';
				print '<span class="error">'.$langs->trans("LDAPSynchroKOMayBePermissions");
				print ': '.$ldap->error;
				print '</span><br>';
				print $langs->trans("ErrorLDAPMakeManualTest", $conf->ldap->dir_temp).'<br>';
			}

			print "<br>\n";
			print "LDAP input file used for test:<br><br>\n";
			print nl2br($ldap->dumpContent($dn, $info));
			print "\n<br>";
		} else {
			print img_picto('', 'error').' ';
			print '<span class="error">'.$langs->trans("LDAPSynchroKO");
			print ': '.$ldap->error;
			print '</span><br>';
			print $langs->trans("ErrorLDAPMakeManualTest", $conf->ldap->dir_temp).'<br>';
		}
	}
}

// End of page
llxFooter();
$db->close();
