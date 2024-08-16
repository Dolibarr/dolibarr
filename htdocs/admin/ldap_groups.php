<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005      Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2013 Juanjo Menent		<jmenent@2byte.es>
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
 *     	\file       htdocs/admin/ldap_groups.php
 *     	\ingroup    ldap
 *		\brief      Page to setup LDAP synchronization for groups
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ldap.lib.php';

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

	if (!dolibarr_set_const($db, 'LDAP_GROUP_DN', GETPOST("group", 'alphanohtml'), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}
	if (!dolibarr_set_const($db, 'LDAP_GROUP_OBJECT_CLASS', GETPOST("objectclass", 'alphanohtml'), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}
	if (!dolibarr_set_const($db, 'LDAP_GROUP_FILTER', GETPOST("filter"), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}
	if (!dolibarr_set_const($db, 'LDAP_GROUP_FIELD_FULLNAME', GETPOST("fieldfullname", 'alphanohtml'), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}
	//if (! dolibarr_set_const($db, 'LDAP_GROUP_FIELD_NAME',GETPOST("fieldname", 'alphanohtml'),'chaine',0,'',$conf->entity)) $error++;
	if (!dolibarr_set_const($db, 'LDAP_GROUP_FIELD_DESCRIPTION', GETPOST("fielddescription", 'alphanohtml'), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}
	if (!dolibarr_set_const($db, 'LDAP_GROUP_FIELD_GROUPMEMBERS', GETPOST("fieldgroupmembers", 'alphanohtml'), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}
	if (!dolibarr_set_const($db, 'LDAP_GROUP_FIELD_GROUPID', GETPOST("fieldgroupid", 'alphanohtml'), 'chaine', 0, '', $conf->entity)) {
		$error++;
	}

	// This one must be after the others
	$valkey = '';
	$key = GETPOST("key");
	if ($key) {
		$valkey = getDolGlobalString($key);
	}
	if (!dolibarr_set_const($db, 'LDAP_KEY_GROUPS', $valkey, 'chaine', 0, '', $conf->entity)) {
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

llxHeader('', $langs->trans("LDAPSetup"), 'EN:Module_LDAP_En|FR:Module_LDAP|ES:M&oacute;dulo_LDAP', '', 0, 0, '', '', '', 'mod-admin page-ldap_groups');
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans("LDAPSetup"), $linkback, 'title_setup');

$head = ldap_prepare_head();

// Test if the LDAP functionality is available
if (!function_exists("ldap_connect")) {
	setEventMessages($langs->trans("LDAPFunctionsNotAvailableOnPHP"), null, 'errors');
}

print dol_get_fiche_head($head, 'groups', '', -1);


print '<span class="opacitymedium">'.$langs->trans("LDAPDescGroups").'</span><br>';
print '<br>';


print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?action=setvalue&token='.newToken().'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td colspan="4">'.$langs->trans("LDAPSynchronizeGroups").'</td>';
print "</tr>\n";

// DN (Domain Name) for the groups
print '<!-- LDAP_GROUP_DN -->';
print '<tr class="oddeven"><td><span class="fieldrequired">'.$langs->trans("LDAPGroupDn").'</span></td><td>';
print '<input size="48" type="text" name="group" value="'.getDolGlobalString('LDAP_GROUP_DN').'">';
print '</td><td>'.$langs->trans("LDAPGroupDnExample").'</td>';
print '<td>&nbsp;</td>';
print '</tr>';

// List of object class used to define attributes in structure
print '<!-- LDAP_GROUP_OBJECT_CLASS -->';
print '<tr class="oddeven"><td><span class="fieldrequired">'.$langs->trans("LDAPGroupObjectClassList").'</span></td><td>';
print '<input size="48" type="text" name="objectclass" value="'.getDolGlobalString('LDAP_GROUP_OBJECT_CLASS').'">';
print '</td><td>'.$langs->trans("LDAPGroupObjectClassListExample").'</td>';
print '<td>&nbsp;</td>';
print '</tr>';

// Filter, used to filter search
print '<!-- LDAP_GROUP_FILTER -->';
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFilterConnection").'</td><td>';
print '<input size="48" type="text" name="filter" value="'.getDolGlobalString('LDAP_GROUP_FILTER').'">';
print '</td><td>'.$langs->trans("LDAPGroupFilterExample").'</td>';
print '<td></td>';
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
print '<input size="25" type="text" name="fieldfullname" value="'.getDolGlobalString('LDAP_GROUP_FIELD_FULLNAME').'">';
print '</td><td>'.$langs->trans("LDAPFieldCommonNameExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_GROUP_FIELD_FULLNAME"'.((getDolGlobalString('LDAP_KEY_GROUPS') == getDolGlobalString('LDAP_GROUP_FIELD_FULLNAME')) ? ' checked' : '')."></td>";
print '</tr>';

// Name
/*
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldName").'</td><td>';
print '<input size="25" type="text" name="fieldname" value="'.$conf->global->LDAP_GROUP_FIELD_NAME.'">';
print '</td><td>'.$langs->trans("LDAPFieldNameExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="'.$conf->global->LDAP_GROUP_FIELD_NAME.'"'.($conf->global->LDAP_KEY_GROUPS==$conf->global->LDAP_GROUP_FIELD_NAME?' checked':'')."></td>";
print '</tr>';
*/

// Description
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldDescription").'</td><td>';
print '<input size="25" type="text" name="fielddescription" value="'.getDolGlobalString('LDAP_GROUP_FIELD_DESCRIPTION').'">';
print '</td><td>'.$langs->trans("LDAPFieldDescriptionExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_GROUP_FIELD_DESCRIPTION"'.((getDolGlobalString('LDAP_KEY_GROUPS') == getDolGlobalString('LDAP_GROUP_FIELD_DESCRIPTION')) ? ' checked' : '')."></td>";
print '</tr>';

// User group
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldGroupMembers").'</td><td>';
print '<input size="25" type="text" name="fieldgroupmembers" value="'.getDolGlobalString('LDAP_GROUP_FIELD_GROUPMEMBERS').'">';
print '</td><td>'.$langs->trans("LDAPFieldGroupMembersExample").'</td>';
print '<td class="right"><input type="radio" name="key" value="LDAP_GROUP_FIELD_GROUPMEMBERS"'.((getDolGlobalString('LDAP_KEY_GROUPS') == getDolGlobalString('LDAP_GROUP_FIELD_GROUPMEMBERS')) ? ' checked' : '')."></td>";
print '</tr>';

// Group id
print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldGroupid").'</td><td>';
print '<input size="25" type="text" name="fieldgroupid" value="'.getDolGlobalString('LDAP_GROUP_FIELD_GROUPID').'">';
print '</td><td>'.$langs->trans("LDAPFieldGroupidExample").'</td>';
print '<td class="right">&nbsp;</td>';
print '</tr>';

print '</table>';

print info_admin($langs->trans("LDAPDescValues"));

print dol_get_fiche_end();

print $form->buttonsSaveCancel("Modify", '');

print '</form>';


/*
 * Test the connection
 */
if (getDolGlobalInt('LDAP_SYNCHRO_ACTIVE') === Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
	$butlabel = $langs->trans("LDAPTestSynchroGroup");
	$testlabel = 'testgroup';
	$key = getDolGlobalString('LDAP_KEY_GROUPS');
	$dn = getDolGlobalString('LDAP_GROUP_DN');
	$objectclass = getDolGlobalString('LDAP_GROUP_OBJECT_CLASS');

	show_ldap_test_button($butlabel, $testlabel, $key, $dn, $objectclass);
} elseif (getDolGlobalInt('LDAP_SYNCHRO_ACTIVE') === Ldap::SYNCHRO_LDAP_TO_DOLIBARR) {
	$butlabel = $langs->trans("LDAPTestSearch");
	$testlabel = 'testsearchgroup';
	$key = getDolGlobalString('LDAP_KEY_GROUPS');
	$dn = getDolGlobalString('LDAP_GROUP_DN');
	$objectclass = getDolGlobalString('LDAP_GROUP_OBJECT_CLASS');
	show_ldap_test_button($butlabel, $testlabel, $key, $dn, $objectclass);
}

if (function_exists("ldap_connect")) {
	if ($action == 'testgroup') {
		// Create object
		$object = new UserGroup($db);
		$object->initAsSpecimen();

		// Test synchro
		$ldap = new Ldap();
		$result = $ldap->connectBind();

		if ($result > 0) {
			$info = $object->_load_ldap_info();
			$dn = $object->_load_ldap_dn($info);

			// Get a gid number for objectclass PosixGroup
			if (in_array('posixGroup', $info['objectclass'])) {
				$info['gidNumber'] = $ldap->getNextGroupGid('LDAP_KEY_GROUPS');
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

	if ($action == 'testsearchgroup') {
		// TODO Mutualize code below with other ldap_xxxx.php pages

		// Test synchro
		$ldap = new Ldap();
		$result = $ldap->connectBind();

		if ($result > 0) {
			$required_fields = array(
				getDolGlobalString('LDAP_KEY_GROUPS'),
				// getDolGlobalString('LDAP_GROUP_FIELD_NAME'),
				getDolGlobalString('LDAP_GROUP_FIELD_DESCRIPTION'),
				getDolGlobalString('LDAP_GROUP_FIELD_GROUPMEMBERS'),
				getDolGlobalString('LDAP_GROUP_FIELD_GROUPID')
			);

			// Remove from required_fields all entries not configured in LDAP (empty) and duplicated
			$required_fields = array_unique(array_values(array_filter($required_fields, "dol_validElement")));

			// Get from LDAP database an array of results
			$ldapgroups = $ldap->getRecords('*', getDolGlobalString('LDAP_GROUP_DN'), getDolGlobalString('LDAP_KEY_GROUPS'), $required_fields, 'group');
			//$ldapgroups = $ldap->getRecords('*', $conf->global->LDAP_GROUP_DN, $conf->global->LDAP_KEY_GROUPS, '', 'group');

			if (is_array($ldapgroups)) {
				$liste = array();
				foreach ($ldapgroups as $key => $ldapgroup) {
					// Define the label string for this group
					$label = '';
					foreach ($required_fields as $value) {
						if ($value) {
							$label .= $value."=".$ldapgroup[$value]." ";
						}
					}
					$liste[$key] = $label;
				}
			} else {
				setEventMessages($ldap->error, $ldap->errors, 'errors');
			}

			print "<br>\n";
			print "LDAP search for group:<br>\n";
			print "search: *<br>\n";
			print "userDN: ".getDolGlobalString('LDAP_GROUP_DN')."<br>\n";
			print "useridentifier: ".getDolGlobalString('LDAP_KEY_GROUPS')."<br>\n";
			print "required_fields: ".implode(',', $required_fields)."<br>\n";
			print "=> ".count($liste)." records<br>\n";
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
