<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *      \brief      Fiche de notes sur un utilisateur Dolibarr
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

$id = GETPOST('id','int');
$action = GETPOST('action');

$langs->load("companies");
$langs->load("members");
$langs->load("bills");
$langs->load("users");

$fuser = new User($db);
$fuser->fetch($id);

// If user is not user read and no permission to read other users, we stop
if (($fuser->id != $user->id) && (! $user->rights->user->user->lire)) accessforbidden();

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
$feature2 = (($socid && $user->rights->user->self->creer)?'':'user');
if ($user->id == $id) $feature2=''; // A user can always read its own card
$result = restrictedArea($user, 'user', $id, '&user', $feature2);



/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($action == 'update' && $user->rights->user->user->creer && ! $_POST["cancel"])
{
	$db->begin();

	$res=$fuser->update_note($_POST["note"],$user);
	if ($res < 0)
	{
		$mesg='<div class="error">'.$adh->error.'</div>';
		$db->rollback();
	}
	else
	{
		$db->commit();
	}
}



/******************************************************************************/
/* Affichage fiche                                                            */
/******************************************************************************/

llxHeader();

$form = new Form($db);

if ($id)
{
	$head = user_prepare_head($fuser);

	$title = $langs->trans("User");
	dol_fiche_head($head, 'note', $title, 0, 'user');

	if ($msg) print '<div class="error">'.$msg.'</div>';

	print "<form method=\"post\" action=\"note.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

    print '<table class="border" width="100%">';

    // Reference
	print '<tr><td width="20%">'.$langs->trans('Ref').'</td>';
	print '<td colspan="3">';
	print $form->showrefnav($fuser,'id','',$user->rights->user->user->lire || $user->admin);
	print '</td>';
	print '</tr>';

    // Nom
    print '<tr><td>'.$langs->trans("Lastname").'</td><td class="valeur" colspan="3">'.$fuser->nom.'&nbsp;</td>';
	print '</tr>';

    // Prenom
    print '<tr><td>'.$langs->trans("Firstname").'</td><td class="valeur" colspan="3">'.$fuser->prenom.'&nbsp;</td></tr>';

    // Login
    print '<tr><td>'.$langs->trans("Login").'</td><td class="valeur" colspan="3">'.$fuser->login.'&nbsp;</td></tr>';

	// Note
    print '<tr><td valign="top">'.$langs->trans("Note").'</td>';
	print '<td valign="top" colspan="3">';
	if ($action == 'edit' && $user->rights->user->user->creer)
	{
		print "<input type=\"hidden\" name=\"action\" value=\"update\">";
		print "<input type=\"hidden\" name=\"id\" value=\"".$fuser->id."\">";
	    // Editeur wysiwyg
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor=new DolEditor('note',$fuser->note,'',280,'dolibarr_notes','In',true,false,$conf->global->FCKEDITOR_ENABLE_SOCIETE,10,80);
		$doleditor->Create();
	}
	else
	{
		print dol_htmlentitiesbr($fuser->note);
	}
	print "</td></tr>";

    print "</table>";

	if ($action == 'edit')
	{
		print '<center><br>';
		print '<input type="submit" class="button" name="update" value="'.$langs->trans("Save").'">';
		print '&nbsp; &nbsp;';
		print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</center>';
	}

	print "</form>\n";


    /*
    * Actions
    */
    print '</div>';
    print '<div class="tabsAction">';

    if ($user->rights->user->user->creer && $action != 'edit')
    {
        print "<a class=\"butAction\" href=\"note.php?id=".$fuser->id."&amp;action=edit\">".$langs->trans('Modify')."</a>";
    }

    print "</div>";


}

$db->close();

llxFooter();
?>
