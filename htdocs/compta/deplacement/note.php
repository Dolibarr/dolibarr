<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/compta/deplacement/note.php
 *      \ingroup    trip
 *      \brief      Notes on a trip card
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/trip.lib.php");
require_once(DOL_DOCUMENT_ROOT."/compta/deplacement/class/deplacement.class.php");

$langs->load("companies");
$langs->load("trips");

$id			= GETPOST('id');
$ref		= GETPOST('ref');
$action		= GETPOST('action');
$confirm	= GETPOST('confirm');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'deplacement', $id, '');

$object = new Deplacement($db);


/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($action == 'update_public' && $user->rights->deplacement->creer)
{
	$db->begin();

	$object->fetch($id);

	$res=$object->update_note_public($_POST["note_public"]);
	if ($res < 0)
	{
		$mesg='<div class="error">'.$object->error.'</div>';
		$db->rollback();
	}
	else
	{
		$db->commit();
	}
}

if ($action == 'update' && $user->rights->deplacement->creer)
{
	$db->begin();

	$object->fetch($id);

	$res=$object->update_note($_POST["note"]);
	if ($res < 0)
	{
		$mesg='<div class="error">'.$object->error.'</div>';
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

$html = new Form($db);

if ($id > 0 || ! empty($ref))
{
	$object->fetch($id, $ref);

	$soc = new Societe($db);
    $soc->fetch($object->socid);

	$head = trip_prepare_head($object);

	dol_fiche_head($head, 'note', $langs->trans("TripCard"), 0, 'trip');

    print '<table class="border" width="100%">';

	// Ref
	print '<tr><td width="20%">'.$langs->trans('Ref').'</td>';
	print '<td colspan="3">';
	$morehtmlref='';
	print $html->showrefnav($object,'ref','',1,'ref','ref',$morehtmlref);
	print '</td></tr>';

	// Type
	print '<tr><td>'.$langs->trans("Type").'</td><td>'.$langs->trans($object->type).'</td></tr>';

	// Who
	print "<tr>";
	print '<td>'.$langs->trans("Person").'</td><td>';
	$userfee=new User($db);
	$userfee->fetch($object->fk_user);
	print $userfee->getNomUrl(1);
	print '</td></tr>';

	print '<tr><td width="20%">'.$langs->trans("CompanyVisited").'</td>';
	print '<td>';
	if ($soc->id) print $soc->getNomUrl(1);
	print '</td></tr>';

	// Note publique
    print '<tr><td valign="top">'.$langs->trans("NotePublic").'</td>';
	print '<td valign="top" colspan="3">';
    if ($action == 'edit')
    {
        print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="update_public">';
        print '<textarea name="note_public" cols="80" rows="8">'.$object->note_public."</textarea><br>";
        print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
        print '</form>';
    }
    else
    {
	    print ($object->note_public?nl2br($object->note_public):"&nbsp;");
    }
	print "</td></tr>";

	// Note privee
	if (! $user->societe_id)
	{
	    print '<tr><td valign="top">'.$langs->trans("NotePrivate").'</td>';
		print '<td valign="top" colspan="3">';
	    if ($action == 'edit')
	    {
	        print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
	        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	        print '<input type="hidden" name="action" value="update">';
	        print '<textarea name="note" cols="80" rows="8">'.$object->note."</textarea><br>";
	        print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
	        print '</form>';
	    }
		else
		{
		    print ($object->note?nl2br($object->note):"&nbsp;");
		}
		print "</td></tr>";
	}

    print "</table>";


    /*
    * Actions
    */
    print '</div>';
    print '<div class="tabsAction">';

    if ($action <> 'edit' && $user->rights->deplacement->creer)
    {
        print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id .'&amp;action=edit">' . $langs->trans('Modify') . '</a>';
    }

    print "</div>";


}

$db->close();

llxFooter();
?>
