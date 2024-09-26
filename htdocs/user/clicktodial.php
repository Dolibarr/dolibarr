<?php
/* Copyright (C) 2005		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@inodbox.com>
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
 *       \file       htdocs/user/clicktodial.php
 *       \brief      Page for Click to dial datas
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';

// Load translation files required by page
$langs->loadLangs(array('users', 'admin'));

$action = (string) GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'aZ09');

$id = GETPOSTINT('id');

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}
$feature2 = (($socid && $user->hasRight('user', 'self', 'creer')) ? '' : 'user');

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('usercard', 'globalcard'));

$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);

// Define value to know what current user can do on properties of edited user
$canedituser = 0;
if ($id > 0) {
	// $user is the current logged user, $id is the user we want to edit
	$canedituser = (($user->id == $id) && $user->hasRight("user", "self", "write")) || (($user->id != $id) && $user->hasRight("user", "user", "write"));
}


/*
 * Actions
 */

$parameters = array('id'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($action == 'update' && !$cancel && $canedituser) {
		$edituser = new User($db);
		$edituser->fetch($id);

		$edituser->clicktodial_url = (string) GETPOST("url", "alpha");
		$edituser->clicktodial_login = (string) GETPOST("login", "alpha");
		$edituser->clicktodial_password = (string) GETPOST("password", "alpha");
		$edituser->clicktodial_poste = (string) GETPOST("poste", "alpha");

		$result = $edituser->update_clicktodial();
		if ($result < 0) {
			setEventMessages($edituser->error, $edituser->errors, 'errors');
		}
	}
}


/*
 * View
 */

$form = new Form($db);

if ($id > 0) {
	$object = new User($db);
	$object->fetch($id, '', '', 1);
	$object->loadRights();
	$object->fetch_clicktodial();

	$person_name = !empty($object->firstname) ? $object->lastname.", ".$object->firstname : $object->lastname;
	$title = $person_name." - ".$langs->trans('ClickToDial');
	$help_url = '';
	llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-user page-clicktodial');

	$head = user_prepare_head($object);

	$title = $langs->trans("User");


	print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';

	print dol_get_fiche_head($head, 'clicktodial', $title, -1, 'user');

	$linkback = '';

	if ($user->hasRight('user', 'user', 'lire') || $user->admin) {
		$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
	}

	$morehtmlref = '<a href="'.DOL_URL_ROOT.'/user/vcard.php?id='.$object->id.'&output=file&file='.urlencode(dol_sanitizeFileName($object->getFullName($langs).'.vcf')).'" class="refid" rel="noopener">';
	$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
	$morehtmlref .= '</a>';

	$urltovirtualcard = '/user/virtualcard.php?id='.((int) $object->id);
	$morehtmlref .= dolButtonToOpenUrlInDialogPopup('publicvirtualcard', $langs->transnoentitiesnoconv("PublicVirtualCardUrl").' - '.$object->getFullName($langs), img_picto($langs->trans("PublicVirtualCardUrl"), 'card', 'class="valignmiddle marginleftonly paddingrightonly"'), $urltovirtualcard, '', 'nohover');

	dol_banner_tab($object, 'id', $linkback, $user->hasRight('user', 'user', 'lire') || $user->admin, 'rowid', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	// Edit mode
	if ($action == 'edit') {
		print '<table class="border centpercent">';

		if ($user->admin) {
			print '<tr><td class="titlefield fieldrequired">ClickToDial URL</td>';
			print '<td class="valeur">';
			print '<input name="url" value="'.(!empty($object->clicktodial_url) ? $object->clicktodial_url : '').'" size="92">';
			if (!getDolGlobalString('CLICKTODIAL_URL') && empty($object->clicktodial_url)) {
				$langs->load("errors");
				print '<span class="error">'.$langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("ClickToDial")).'</span>';
			} else {
				print '<br>'.$form->textwithpicto('<span class="opacitymedium">'.$langs->trans("KeepEmptyToUseDefault").'</span>:<br>' . getDolGlobalString('CLICKTODIAL_URL'), $langs->trans("ClickToDialUrlDesc"));
			}
			print '</td>';
			print '</tr>';
		}

		print '<tr><td class="titlefield fieldrequired">ClickToDial '.$langs->trans("IdPhoneCaller").'</td>';
		print '<td class="valeur">';
		print '<input name="poste" value="'.(!empty($object->clicktodial_poste) ? $object->clicktodial_poste : '').'"></td>';
		print "</tr>\n";

		print '<tr><td>ClickToDial '.$langs->trans("Login").'</td>';
		print '<td class="valeur">';
		print '<input name="login" value="'.(!empty($object->clicktodial_login) ? $object->clicktodial_login : '').'"></td>';
		print '</tr>';

		print '<tr><td>ClickToDial '.$langs->trans("Password").'</td>';
		print '<td class="valeur">';
		print '<input type="password" name="password" value="'.dol_escape_htmltag(empty($object->clicktodial_password) ? '' : $object->clicktodial_password).'"></td>';
		print "</tr>\n";

		print '</table>';
	} else { // View mode
		print '<table class="border centpercent tableforfield">';

		if (!empty($user->admin)) {
			print '<tr><td class="">ClickToDial URL</td>';
			print '<td class="valeur">';
			if (getDolGlobalString('CLICKTODIAL_URL')) {
				$url = getDolGlobalString('CLICKTODIAL_URL');
			}
			if (!empty($object->clicktodial_url)) {
				$url = $object->clicktodial_url;
			}
			if (empty($url)) {
				$langs->load("errors");
				print '<span class="error">'.$langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("ClickToDial")).'</span>';
			} else {
				print $form->textwithpicto((empty($object->clicktodial_url) ? '<span class="opacitymedium">'.$langs->trans("DefaultLink").':</span> ' : '').$url, $langs->trans("ClickToDialUrlDesc"));
			}
			print '</td>';
			print '</tr>';
		}

		print '<tr><td class="">ClickToDial '.$langs->trans("IdPhoneCaller").'</td>';
		print '<td class="valeur">'.(!empty($object->clicktodial_poste) ? $object->clicktodial_poste : '').'</td>';
		print "</tr>";

		print '<tr><td>ClickToDial '.$langs->trans("Login").'</td>';
		print '<td class="valeur">'.(!empty($object->clicktodial_login) ? $object->clicktodial_login : '').'</td>';
		print '</tr>';

		print '<tr><td>ClickToDial '.$langs->trans("Password").'</td>';
		print '<td class="valeur">'.preg_replace('/./', '*', (!empty($object->clicktodial_password) ? $object->clicktodial_password : '')).'</a></td>';
		print "</tr>\n";

		print "</table>\n";
	}

	print dol_get_fiche_end();

	if ($action == 'edit') {
		print '<br>';
		print '<div class="center"><input class="button button-save" type="submit" value="'.$langs->trans("Save").'">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input class="button button-cancel" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</div>';
	}

	print '</div>';
	print '</form>';

	/*
	 * Action bar
	 */
	print '<div class="tabsAction">';

	if (!empty($user->admin) && $action != 'edit') {
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>';
	}

	print "</div>\n";
}

// End of page
llxFooter();
$db->close();
