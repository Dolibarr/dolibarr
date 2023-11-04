<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2015 Regis Houssin        <regis.houssin@inodbox.com>
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
 *      \file       htdocs/user/note.php
 *      \ingroup    usergroup
 *      \brief      Fiche de notes sur un utilisateur Dolibarr
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'usernote'; // To manage different context of search

// Load translation files required by page
$langs->loadLangs(array('companies', 'members', 'bills', 'users'));

$object = new User($db);
$object->fetch($id, '', '', 1);
$object->getrights();

// If user is not user read and no permission to read other users, we stop
if (($object->id != $user->id) && (!$user->rights->user->user->lire)) {
	accessforbidden();
}

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}
$feature2 = (($socid && $user->rights->user->self->creer) ? '' : 'user');

$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('usercard', 'usernote', 'globalcard'));


/*
 * Actions
 */

$parameters = array('id'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($action == 'update' && $user->rights->user->user->creer && !GETPOST("cancel")) {
		$db->begin();

		$res = $object->update_note(dol_html_entity_decode(GETPOST('note_private', 'restricthtml'), ENT_QUOTES | ENT_HTML5));
		if ($res < 0) {
			$mesg = '<div class="error">'.$adh->error.'</div>';
			$db->rollback();
		} else {
			$db->commit();
		}
	}
}


/*
 * View
 */

llxHeader();

$form = new Form($db);

if ($id) {
	$head = user_prepare_head($object);

	$title = $langs->trans("User");
	print dol_get_fiche_head($head, 'note', $title, -1, 'user');

	$linkback = '';

	if ($user->rights->user->user->lire || $user->admin) {
		$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
	}

	dol_banner_tab($object, 'id', $linkback, $user->rights->user->user->lire || $user->admin);

	print '<div class="underbanner clearboth"></div>';

	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';

	print '<div class="fichecenter">';
	print '<table class="border centpercent tableforfield">';

	// Login
	print '<tr><td class="titlefield">'.$langs->trans("Login").'</td><td class="valeur">'.$object->login.'&nbsp;</td></tr>';

	$editenabled = (($action == 'edit') && !empty($user->rights->user->user->creer));

	// Note
	print '<tr><td class="tdtop">'.$langs->trans("Note").'</td>';
	print '<td class="'.($editenabled ? '' : 'sensiblehtmlcontent').'">';
	if ($editenabled) {
		print "<input type=\"hidden\" name=\"action\" value=\"update\">";
		print "<input type=\"hidden\" name=\"id\" value=\"".$object->id."\">";
		// Editeur wysiwyg
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor = new DolEditor('note_private', $object->note_private, '', 280, 'dolibarr_notes', 'In', true, false, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_8, '90%');
		$doleditor->Create();
	} else {
		print dol_string_onlythesehtmltags(dol_htmlentitiesbr($object->note_private));
	}
	print "</td></tr>";

	print "</table>";
	print '</div>';

	print dol_get_fiche_end();

	if ($action == 'edit') {
		print '<div class="center">';
		print '<input type="submit" class="button button-save" name="update" value="'.$langs->trans("Save").'">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</div>';
	}


	/*
	 * Actions
	 */

	print '<div class="tabsAction">';

	if ($user->rights->user->user->creer && $action != 'edit') {
		print "<a class=\"butAction\" href=\"note.php?id=".$object->id."&amp;action=edit\">".$langs->trans('Modify')."</a>";
	}

	print "</div>";

	print "</form>\n";
}

// End of page
llxFooter();
$db->close();
