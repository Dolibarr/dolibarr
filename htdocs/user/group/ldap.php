<?php
/* Copyright (C) 2006-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2006-2017  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
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
 *       \file       htdocs/user/group/ldap.php
 *       \ingroup    ldap
 *       \brief      Page fiche LDAP groupe
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ldap.lib.php';

// Load translation files required by page
$langs->loadLangs(array('companies', 'ldap', 'users', 'admin'));

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');

$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}

$object = new Usergroup($db);
$object->fetch($id);
$object->getrights();

// Users/Groups management only in master entity if transverse mode
if (!empty($conf->multicompany->enabled) && $conf->entity > 1 && $conf->global->MULTICOMPANY_TRANSVERSE_MODE) {
	accessforbidden();
}

$canreadperms = true;
if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS)) {
	$canreadperms = ($user->admin || $user->rights->user->group_advance->read);
}


/*
 * Actions
 */

if ($action == 'dolibarr2ldap') {
	$ldap = new Ldap();
	$result = $ldap->connect_bind();

	if ($result > 0) {
		$info = $object->_load_ldap_info();

		// Get a gid number for objectclass PosixGroup if none was provided
		if (empty($info[$conf->global->LDAP_GROUP_FIELD_GROUPID]) && in_array('posixGroup', $info['objectclass'])) {
			$info['gidNumber'] = $ldap->getNextGroupGid('LDAP_KEY_GROUPS');
		}

		$dn = $object->_load_ldap_dn($info);
		$olddn = $dn; // We can say that old dn = dn as we force synchro

		$result = $ldap->update($dn, $info, $user, $olddn);
	}

	if ($result >= 0) {
		setEventMessages($langs->trans("GroupSynchronized"), null, 'mesgs');
	} else {
		setEventMessages($ldap->error, $ldap->errors, 'errors');
	}
}


/*
 *	View
 */

$form = new Form($db);

llxHeader();

$head = group_prepare_head($object);

print dol_get_fiche_head($head, 'ldap', $langs->trans("Group"), -1, 'group');

$linkback = '<a href="'.DOL_URL_ROOT.'/user/group/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'id', $linkback, $user->rights->user->user->lire || $user->admin);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border centpercent">';

// Name (already in dol_banner, we keep it to have the GlobalGroup picto, but we should move it in dol_banner)
if (!empty($conf->mutlicompany->enabled)) {
	print '<tr><td class="titlefield">'.$langs->trans("Name").'</td>';
	print '<td class="valeur">'.$object->name;
	if (!$object->entity) {
		print img_picto($langs->trans("GlobalGroup"), 'redstar');
	}
	print "</td></tr>\n";
}

// Note
print '<tr><td class="tdtop">'.$langs->trans("Description").'</td>';
print '<td class="valeur sensiblehtmlcontent">';
print dol_string_onlythesehtmltags(dol_htmlentitiesbr($object->note));
print '</td>';
print "</tr>\n";

// LDAP DN
print '<tr><td>LDAP '.$langs->trans("LDAPGroupDn").'</td><td class="valeur">'.$conf->global->LDAP_GROUP_DN."</td></tr>\n";

// LDAP Cle
print '<tr><td>LDAP '.$langs->trans("LDAPNamingAttribute").'</td><td class="valeur">'.$conf->global->LDAP_KEY_GROUPS."</td></tr>\n";

// LDAP Server
print '<tr><td>LDAP '.$langs->trans("LDAPPrimaryServer").'</td><td class="valeur">'.$conf->global->LDAP_SERVER_HOST."</td></tr>\n";
print '<tr><td>LDAP '.$langs->trans("LDAPSecondaryServer").'</td><td class="valeur">'.$conf->global->LDAP_SERVER_HOST_SLAVE."</td></tr>\n";
print '<tr><td>LDAP '.$langs->trans("LDAPServerPort").'</td><td class="valeur">'.$conf->global->LDAP_SERVER_PORT."</td></tr>\n";

print "</table>\n";

print '</div>';

print dol_get_fiche_end();


/*
 * Action bar
 */
print '<div class="tabsAction">';

if ($conf->global->LDAP_SYNCHRO_ACTIVE == 'dolibarr2ldap') {
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=dolibarr2ldap">'.$langs->trans("ForceSynchronize").'</a>';
}

print "</div>\n";

if ($conf->global->LDAP_SYNCHRO_ACTIVE == 'dolibarr2ldap') {
	print "<br>\n";
}



// Affichage attributs LDAP
print load_fiche_titre($langs->trans("LDAPInformationsForThisGroup"));

print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("LDAPAttributes").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '</tr>';

// Lecture LDAP
$ldap = new Ldap();
$result = $ldap->connect_bind();
if ($result > 0) {
	$info = $object->_load_ldap_info();
	$dn = $object->_load_ldap_dn($info, 1);
	$search = "(".$object->_load_ldap_dn($info, 2).")";

	$records = $ldap->getAttribute($dn, $search);

	//var_dump($records);

	// Show tree
	if (((!is_numeric($records)) || $records != 0) && (!isset($records['count']) || $records['count'] > 0)) {
		if (!is_array($records)) {
			print '<tr class="oddeven"><td colspan="2"><font class="error">'.$langs->trans("ErrorFailedToReadLDAP").'</font></td></tr>';
		} else {
			$result = show_ldap_content($records, 0, $records['count'], true);
		}
	} else {
		print '<tr class="oddeven"><td colspan="2">'.$langs->trans("LDAPRecordNotFound").' (dn='.$dn.' - search='.$search.')</td></tr>';
	}
	$ldap->unbind();
	$ldap->close();
} else {
	setEventMessages($ldap->error, $ldap->errors, 'errors');
}

print '</table>';

// End of page
llxFooter();
$db->close();
