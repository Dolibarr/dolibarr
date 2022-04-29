<?php
/* Copyright (C) 2006-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006-2017 Regis Houssin        <regis.houssin@inodbox.com>
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
 *      \file       htdocs/adherents/type_ldap.php
 *      \ingroup    ldap
 *      \brief      Page fiche LDAP members types
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ldap.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "members", "ldap"));

$id = GETPOST('rowid', 'int');
$action = GETPOST('action', 'aZ09');

// Security check
$result = restrictedArea($user, 'adherent', $id, 'adherent_type');

$object = new AdherentType($db);
$object->fetch($id);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('membertypeldapcard', 'globalcard'));

/*
 * Actions
 */


$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($action == 'dolibarr2ldap') {
		$ldap = new Ldap();
		$result = $ldap->connect_bind();

		if ($result > 0) {
			$object->listMembersForMemberType('', 1);

			$info = $object->_load_ldap_info();
			$dn = $object->_load_ldap_dn($info);
			$olddn = $dn; // We can say that old dn = dn as we force synchro

			$result = $ldap->update($dn, $info, $user, $olddn);
		}

		if ($result >= 0) {
			setEventMessages($langs->trans("MemberTypeSynchronized"), null, 'mesgs');
		} else {
			setEventMessages($ldap->error, $ldap->errors, 'errors');
		}
	}
}

/*
 * View
 */

llxHeader();

$form = new Form($db);

$head = member_type_prepare_head($object);

print dol_get_fiche_head($head, 'ldap', $langs->trans("MemberType"), -1, 'group');

$linkback = '<a href="'.DOL_URL_ROOT.'/adherents/type.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'rowid', $linkback);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border centpercent">';

// LDAP DN
print '<tr><td>LDAP '.$langs->trans("LDAPMemberTypeDn").'</td><td class="valeur">'.$conf->global->LDAP_MEMBER_TYPE_DN."</td></tr>\n";

// LDAP Cle
print '<tr><td>LDAP '.$langs->trans("LDAPNamingAttribute").'</td><td class="valeur">'.$conf->global->LDAP_KEY_MEMBERS_TYPES."</td></tr>\n";

// LDAP Server
print '<tr><td>LDAP '.$langs->trans("Type").'</td><td class="valeur">'.$conf->global->LDAP_SERVER_TYPE."</td></tr>\n";
print '<tr><td>LDAP '.$langs->trans("Version").'</td><td class="valeur">'.$conf->global->LDAP_SERVER_PROTOCOLVERSION."</td></tr>\n";
print '<tr><td>LDAP '.$langs->trans("LDAPPrimaryServer").'</td><td class="valeur">'.$conf->global->LDAP_SERVER_HOST."</td></tr>\n";
print '<tr><td>LDAP '.$langs->trans("LDAPSecondaryServer").'</td><td class="valeur">'.$conf->global->LDAP_SERVER_HOST_SLAVE."</td></tr>\n";
print '<tr><td>LDAP '.$langs->trans("LDAPServerPort").'</td><td class="valeur">'.$conf->global->LDAP_SERVER_PORT."</td></tr>\n";

print '</table>';

print '</div>';

print dol_get_fiche_end();

/*
 * Action bar
 */

print '<div class="tabsAction">';

if (getDolGlobalInt('LDAP_MEMBER_TYPE_ACTIVE') === Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'&action=dolibarr2ldap">'.$langs->trans("ForceSynchronize").'</a>';
}

print "</div>\n";

if (getDolGlobalInt('LDAP_MEMBER_TYPE_ACTIVE') === Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
	print "<br>\n";
}



// Display LDAP attributes
print load_fiche_titre($langs->trans("LDAPInformationsForThisMemberType"));

print '<table width="100%" class="noborder">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("LDAPAttributes").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '</tr>';

// LDAP reading
$ldap = new Ldap();
$result = $ldap->connect_bind();
if ($result > 0) {
	$info = $object->_load_ldap_info();
	$dn = $object->_load_ldap_dn($info, 1);
	$search = "(".$object->_load_ldap_dn($info, 2).")";

	$records = $ldap->getAttribute($dn, $search);

	//print_r($records);

	// Show tree
	if (((!is_numeric($records)) || $records != 0) && (!isset($records['count']) || $records['count'] > 0)) {
		if (!is_array($records)) {
			print '<tr class="oddeven"><td colspan="2"><span class="error">'.$langs->trans("ErrorFailedToReadLDAP").'</span></td></tr>';
		} else {
			$result = show_ldap_content($records, 0, $records['count'], true);
		}
	} else {
		print '<tr class="oddeven"><td colspan="2">'.$langs->trans("LDAPRecordNotFound").' (dn='.dol_escape_htmltag($dn).' - search='.dol_escape_htmltag($search).')</td></tr>';
	}

	$ldap->unbind();
} else {
	setEventMessages($ldap->error, $ldap->errors, 'errors');
}

print '</table>';

// End of page
llxFooter();
$db->close();
