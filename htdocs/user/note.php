<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2015	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2016		Alexandre Spangaro		<aspangaro.dolibarr@gmail.com>
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
 *      \file       htdocs/user/note.php
 *      \ingroup    usergroup
 *      \brief      Note card of an user
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/note.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

$id = GETPOST('id','int');
$noteid = GETPOST('noteid','int');
$action = GETPOST('action');

$langs->load("companies");
$langs->load("members");
$langs->load("bills");
$langs->load("users");

$object = new User($db);
$note = New Note($db);
$modulepart = 'user';
$object->fetch($id);

// If user is not user read and no permission to read other users, we stop
if (($object->id != $user->id) && (! $user->rights->user->user->lire)) accessforbidden();

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
$feature2 = (($socid && $user->rights->user->self->creer)?'':'user');
if ($user->id == $id) $feature2=''; // A user can always read its own card
$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('usercard','globalcard'));

/*
 * Actions
 */

if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->user->user->lire)
{
	$result=$note->delete($noteid);
	if ($result >= 0)
	{
		header("Location: index.php");
		exit;
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
	}
}


/*
 * View
 */

$title=$langs->trans("User").' - '.$langs->trans("Note");
$helpurl='';
llxHeader('',$title,$helpurl);

$form = new Form($db);

if ($id)
{
	$head = user_prepare_head($object);

	$title = $langs->trans("User");
	dol_fiche_head($head, 'note', $title, 0, 'user');

	$linkback = '<a href="'.DOL_URL_ROOT.'/user/index.php">'.$langs->trans("BackToList").'</a>';
	
	dol_banner_tab($object,'id',$linkback,$user->rights->user->user->lire || $user->admin);

	/*
	 * Confirm delete note 
	 */
	if ($action == 'delete')
	{
		print $form->formconfirm($_SERVER["PHP_SELF"]."?id=" . $id . "&noteid=" . $noteid,$langs->trans("DeleteNote"),$langs->trans("ConfirmDeleteNote"),"confirm_delete");
	}

	print '<div class="underbanner clearboth"></div>';

	print '<br>';

	print "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

	// Notes
	$notes = array();
	$result = $note->fetchAll($notes, $modulepart, $id, $sortorder, $sortfield);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
	
	foreach ( $notes as $note )
	{
		print '<table class="border" width="100%">';

		$userstatic = New User($db);

		// Title
		print '<tr class="liste_titre">';
		print '<td>'.$note->title.' ('.$userstatic->getNomUrl($note->fk_user_author).' - '.dol_print_date($db->jdate($note->datec),'day').')';
		//print '<td class="right">';
		print '<a class="right" href="./note.php?action=delete&id=' . $id . '&noteid=' . $note->id . '">'.img_delete().'</a>';
		print '&nbsp;&nbsp;&nbsp;';
		print '<a class="right" href="./note.php?action=edit&id=' . $id . '&noteid=' . $note->id . '">'.img_edit().'</a>';
		print '</td></tr>';

		// Text
		print '<tr><td colspan="2">'.dol_htmlentitiesbr($note->text).'</td><tr>';

		print "</table>";
		print '<br>';
	}

	dol_fiche_end();

	/*
	 * Actions
	 */

	print '<div class="tabsAction">';

	if ($user->rights->user->user->creer && $action != 'add')
	{
		print "<a class=\"butAction\" href=\"note.php?id=".$object->id."&amp;action=add\">".$langs->trans('AddNote')."</a>";
	}

	print "</div>";

	print "</form>\n";
}

llxFooter();

$db->close();
