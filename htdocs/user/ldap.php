<?php
/* Copyright (C) 2006-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2006-2021	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2024		William Mead			<william.mead@manchenumerique.fr>
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
 *      \file       htdocs/user/ldap.php
 *      \ingroup    ldap
 *      \brief      Page fiche LDAP utilisateur
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ldap.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by page
$langs->loadLangs(array('users', 'admin', 'companies', 'ldap'));

$id = GETPOSTINT('id');
$action = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?: 'userldap'; // To manage different context of search

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}
$feature2 = (($socid && $user->hasRight('user', 'self', 'creer')) ? '' : 'user');

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('usercard', 'userldap', 'globalcard'));

$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);

$object = new User($db);
$object->fetch($id, '', '', 1);
$object->loadRights();


/*
 * Actions
 */


$parameters = array('id'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($action == 'dolibarr2ldap') {
		$ldap = new Ldap();
		$result = $ldap->connectBind();

		if ($result > 0) {
			$info = $object->_load_ldap_info();
			$dn = $object->_load_ldap_dn($info);
			$olddn = $dn; // We can say that old dn = dn as we force synchro

			$result = $ldap->update($dn, $info, $user, $olddn);
		}

		if ($result >= 0) {
			setEventMessages($langs->trans("UserSynchronized"), null, 'mesgs');
		} else {
			setEventMessages($ldap->error, $ldap->errors, 'errors');
		}
	}
}

/*
 * View
 */

$form = new Form($db);

$person_name = !empty($object->firstname) ? $object->lastname.", ".$object->firstname : $object->lastname;
$title = $person_name." - ".$langs->trans('LDAP');
$help_url = '';
llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-user page-ldap');

$head = user_prepare_head($object);

$title = $langs->trans("User");
print dol_get_fiche_head($head, 'ldap', $title, -1, 'user');

$linkback = '';

if ($user->hasRight('user', 'user', 'lire') || !empty($user->admin)) {
	$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
}

dol_banner_tab($object, 'id', $linkback, $user->hasRight('user', 'user', 'lire') || $user->admin);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border centpercent tableforfield">';

// Login
print '<tr><td class="titlefield">'.$langs->trans("Login").'</td>';
if ($object->ldap_sid) {
	print '<td class="warning">'.$langs->trans("LoginAccountDisableInDolibarr").'</td>';
} else {
	print '<td>'.$object->login.'</td>';
}
print '</tr>';

if (getDolGlobalString('LDAP_SERVER_TYPE') == "activedirectory") {
	$ldap = new Ldap();
	$result = $ldap->connectBind();
	$userSID = '';
	if ($result > 0) {
		$userSID = $ldap->getObjectSid($object->login);
	}
	print '<tr><td class="valigntop">'.$langs->trans("SID").'</td>';
	print '<td>'.$userSID.'</td>';
	print "</tr>\n";
}

// LDAP DN
print '<tr><td>LDAP '.$langs->trans("LDAPUserDn").'</td><td class="valeur">'.getDolGlobalString('LDAP_USER_DN')."</td></tr>\n";

// LDAP Cle
print '<tr><td>LDAP '.$langs->trans("LDAPNamingAttribute").'</td><td class="valeur">'.getDolGlobalString('LDAP_KEY_USERS')."</td></tr>\n";

// LDAP Server
print '<tr><td>LDAP '.$langs->trans("Type").'</td><td class="valeur">'.getDolGlobalString('LDAP_SERVER_TYPE')."</td></tr>\n";
print '<tr><td>LDAP '.$langs->trans("Version").'</td><td class="valeur">'.getDolGlobalString('LDAP_SERVER_PROTOCOLVERSION')."</td></tr>\n";
print '<tr><td>LDAP '.$langs->trans("LDAPPrimaryServer").'</td><td class="valeur">'.getDolGlobalString('LDAP_SERVER_HOST')."</td></tr>\n";
print '<tr><td>LDAP '.$langs->trans("LDAPSecondaryServer").'</td><td class="valeur">'.getDolGlobalString('LDAP_SERVER_HOST_SLAVE')."</td></tr>\n";
print '<tr><td>LDAP '.$langs->trans("LDAPServerPort").'</td><td class="valeur">'.getDolGlobalString('LDAP_SERVER_PORT')."</td></tr>\n";

print '</table>';

print '</div>';

print dol_get_fiche_end();

/*
 * Action bar
 */
print '<div class="tabsAction">';

if (getDolGlobalInt('LDAP_SYNCHRO_ACTIVE') === Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=dolibarr2ldap">'.$langs->trans("ForceSynchronize").'</a>';
}

print "</div>\n";

if (getDolGlobalInt('LDAP_SYNCHRO_ACTIVE') === Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
	print "<br>\n";
}



// Affichage attributes LDAP
print load_fiche_titre($langs->trans("LDAPInformationsForThisUser"));

print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("LDAPAttributes").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '</tr>';

// Lecture LDAP
$ldap = new Ldap();
$result = $ldap->connectBind();
if ($result > 0) {
	$info = $object->_load_ldap_info();
	$dn = $object->_load_ldap_dn($info, 1);
	$search = "(".$object->_load_ldap_dn($info, 2).")";

	$records = $ldap->getAttribute($dn, $search);

	//print_r($records);

	// Affichage arbre
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
	print '<tr class="oddeven"><td colspan="2"><span class="error">'.$langs->trans("ErrorFailedToReadLDAP").'</span></td></tr>';
	setEventMessages($ldap->error, $ldap->errors, 'errors');
}

print '</table>';

// End of page
llxFooter();
$db->close();
