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
$action = GETPOST('action');

$langs->load("companies");
$langs->load("members");
$langs->load("bills");
$langs->load("users");

$object = new User($db);
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

/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

$parameters=array('id'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	if ($action == 'update' && $user->rights->user->user->creer && !$_POST["cancel"]) {
		$db->begin();

		$res = $object->update_note(dol_html_entity_decode(GETPOST('note_private'), ENT_QUOTES));
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

$title=$langs->trans("User").' - '.$langs->trans("Note");
$helpurl='';
llxHeader('',$title,$helpurl);

$form = new Form($db);
$note = New Note($db);
$modulepart = 'user';

if ($id)
{
	$head = user_prepare_head($object);

	$title = $langs->trans("User");
	dol_fiche_head($head, 'note', $title, 0, 'user');

	$linkback = '<a href="'.DOL_URL_ROOT.'/user/index.php">'.$langs->trans("BackToList").'</a>';
	
    dol_banner_tab($object,'id',$linkback,$user->rights->user->user->lire || $user->admin);
    
    print '<div class="underbanner clearboth"></div>';
    
    print "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

    print '<table class="border" width="100%">';

    // Notes
	$notes = array();
	$result = $note->fetchAll($notes, $modulepart, $id, $sortorder, $sortfield);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
	
	if ($result)
	{
		$num = $db->num_rows($result);
		$limit=10;

		$i = 0;
		while ($i < min($num,$limit))
		{
			// Id
			// print '<td><a href="card.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowTrip"),"trip").' '.$obj->rowid.'</a></td>';
			// Title
			print '<tr><td>'.$notes->title.' ('.dol_print_date($db->jdate($notes->datec),'day').')</td></tr>';
			// Text
			print '<tr><td align="center">'.dol_htmlentitiesbr($notes->text).'</td><tr>';
			
			/*
			// User
			print '<td>';
			$userstatic->id = $obj->fk_user;
			$userstatic->lastname = $obj->lastname;
			$userstatic->firstname = $obj->firstname;
			print $userstatic->getNomUrl(1);
			print '</td>';

			if ($obj->socid) print '<td>'.$soc->getNomUrl(1).'</td>';
			else print '<td>&nbsp;</td>';

			print '<td align="right">'.$obj->km.'</td>';

			$tripandexpense_static->statut=$obj->fk_statut;
			print '<td align="right">'.$tripandexpense_static->getLibStatut(5).'</td>';
			print "</tr>\n";
			*/

			$i++;
		}
	//print dol_htmlentitiesbr($object->note);

	}

    print "</table>";

	dol_fiche_end();

	if ($action == 'edit')
	{
		print '<div class="center">';
		print '<input type="submit" class="button" name="update" value="'.$langs->trans("Save").'">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</div>';
	}


	/*
     * Actions
     */

    print '<div class="tabsAction">';

    if ($user->rights->user->user->creer && $action != 'edit')
    {
        print "<a class=\"butAction\" href=\"note.php?id=".$object->id."&amp;action=edit\">".$langs->trans('Modify')."</a>";
    }

    print "</div>";

	print "</form>\n";
}

llxFooter();

$db->close();
