<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004		Sebastien Di Cintio	<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier		<benoit.mortier@opensides.be>
 * Copyright (C) 2005-2017	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2006-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2011-2013	Juanjo Menent		<jmenent@2byte.es>
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
 *     	\file       htdocs/admin/ldap_members_types.php
 *     	\ingroup    ldap
 *		\brief      Page to setup LDAP synchronization for members types
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ldap.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('admin', 'errors'));

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

	if (!dolibarr_set_const($db, 'LDAP_MEMBER_TYPE_DN', GETPOST("membertype"), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}
	if (!dolibarr_set_const($db, 'LDAP_MEMBER_TYPE_OBJECT_CLASS', GETPOST("objectclass"), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}

	if (!dolibarr_set_const($db, 'LDAP_MEMBER_TYPE_FIELD_FULLNAME', GETPOST("fieldfullname"), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}
	if (!dolibarr_set_const($db, 'LDAP_MEMBER_TYPE_FIELD_DESCRIPTION', GETPOST("fielddescription"), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}
	if (!dolibarr_set_const($db, 'LDAP_MEMBER_TYPE_FIELD_GROUPMEMBERS', GETPOST("fieldmembertypemembers"), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}

	// This one must be after the others
	$valkey = '';
	$key = GETPOST("key");
	if ($key) {
		$valkey = getDolGlobalString($key);
	}
	if (!dolibarr_set_const($db, 'LDAP_KEY_MEMBERS_TYPES', $valkey, 'chaine', 0, '', $conf->entity)) {
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

llxHeader('', $langs->trans("LDAPSetup"), 'EN:Module_LDAP_En|FR:Module_LDAP|ES:M&oacute;dulo_LDAP', '', 0, 0, '', '', '', 'mod-admin page-ldap_members_types');
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans("LDAPSetup"), $linkback, 'title_setup');

$head = ldap_prepare_head();

// Test if the LDAP functions are enabled
if (!function_exists("ldap_connect")) {
	setEventMessages($langs->trans("LDAPFunctionsNotAvailableOnPHP"), null, 'errors');
}

print dol_get_fiche_head($head, 'memberstypes', '', -1);


print '<span class="opacitymedium">'.$langs->trans("LDAPDescMembersTypes").'</span><br>';
print '<br>';


print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?action=setvalue&token='.newToken().'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

$form = new Form($db);

print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td colspan="4">'.$langs->trans("LDAPSynchronizeMembersTypes").'</td>';
print "</tr>\n";

// DN pour les types de membres
print '<!-- LDAP_MEMBER_TYPE_DN -->';
print '<tr class="oddeven"><td><span class="fieldrequired">'.$langs->trans("LDAPMemberTypeDn").'</span></td><td>';
print '<input size="48" type="text" name="membertype" value="' . getDolGlobalString('LDAP_MEMBER_TYPE_DN').'">';
print '</td><td>'.$langs->trans("LDAPMemberTypepDnExample").'</td>';
print '<td>&nbsp;</td>';
print '</tr>';

// List of object class used to define attributes in structure
print '<!-- LDAP_MEMBER_TYPE_OBJECT_CLASS -->';
print '<tr class="oddeven"><td><span class="fieldrequired">'.$langs->trans("LDAPMemberTypeObjectClassList").'</span></td><td>';
print '<input size="48" type="text" name="objectclass" value="' . getDolGlobalString('LDAP_MEMBER_TYPE_OBJECT_CLASS').'">';
print '</td><td>'.$langs->trans("LDAPMemberTypeObjectClassListExample").'</td>';
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

// Filtre

// Common name
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldName").'</td><td>';
print '<input size="25" type="text" name="fieldfullname" value="' . getDolGlobalString('LDAP_MEMBER_TYPE_FIELD_FULLNAME').'">';
print '</td><td>'.$langs->trans("LDAPFieldCommonNameExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_MEMBER_TYPE_FIELD_FULLNAME"'.(($conf->global->LDAP_KEY_MEMBERS_TYPES && $conf->global->LDAP_KEY_MEMBERS_TYPES == $conf->global->LDAP_MEMBER_TYPE_FIELD_FULLNAME) ? ' checked' : '')."></td>";
print '</tr>';

// Description
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldDescription").'</td><td>';
print '<input size="25" type="text" name="fielddescription" value="' . getDolGlobalString('LDAP_MEMBER_TYPE_FIELD_DESCRIPTION').'">';
print '</td><td>'.$langs->trans("LDAPFieldDescriptionExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_MEMBER_TYPE_FIELD_DESCRIPTION"'.(($conf->global->LDAP_KEY_MEMBERS_TYPES && $conf->global->LDAP_KEY_MEMBER_TYPES == $conf->global->LDAP_MEMBER_TYPE_FIELD_DESCRIPTION) ? ' checked' : '')."></td>";
print '</tr>';

// User group
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldGroupMembers").'</td><td>';
print '<input size="25" type="text" name="fieldmembertypemembers" value="' . getDolGlobalString('LDAP_MEMBER_TYPE_FIELD_GROUPMEMBERS').'">';
print '</td><td>'.$langs->trans("LDAPFieldGroupMembersExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_MEMBER_TYPE_FIELD_GROUPMEMBERS"'.(($conf->global->LDAP_KEY_MEMBERS_TYPES && $conf->global->LDAP_KEY_MEMBERS_TYPES == $conf->global->LDAP_MEMBER_TYPE_FIELD_GROUPMEMBERS) ? ' checked' : '')."></td>";
print '</tr>';

print '</table>';

print info_admin($langs->trans("LDAPDescValues"));

print dol_get_fiche_end();

print $form->buttonsSaveCancel("Modify", '');

print '</form>';


/*
 * Test de la connection
 */
if (getDolGlobalInt('LDAP_MEMBER_TYPE_ACTIVE') === Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
	$butlabel = $langs->trans("LDAPTestSynchroMemberType");
	$testlabel = 'testmembertype';
	$key = getDolGlobalString('LDAP_KEY_MEMBERS_TYPES');
	$dn = getDolGlobalString('LDAP_MEMBER_TYPE_DN');
	$objectclass = getDolGlobalString('LDAP_MEMBER_TYPE_OBJECT_CLASS');

	show_ldap_test_button($butlabel, $testlabel, $key, $dn, $objectclass);
}

if (function_exists("ldap_connect")) {
	if ($action == 'testmembertype') {
		// Create object
		$object = new AdherentType($db);
		$object->initAsSpecimen();

		// Test synchro
		$ldap = new Ldap();
		$result = $ldap->connectBind();

		if ($result > 0) {
			$info = $object->_load_ldap_info();
			$dn = $object->_load_ldap_dn($info);

			// Get a gid number for objectclass PosixGroup
			if (in_array('posixGroup', $info['objectclass'])) {
				$info['gidNumber'] = $ldap->getNextGroupGid('LDAP_KEY_MEMBERS_TYPES');
			}

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
