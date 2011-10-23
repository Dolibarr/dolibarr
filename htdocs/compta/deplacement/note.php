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
require_once(DOL_DOCUMENT_ROOT."/compta/deplacement/class/deplacement.class.php");

$socid=isset($_GET["socid"])?$_GET["socid"]:isset($_POST["socid"])?$_POST["socid"]:"";

if (!$user->rights->deplacement->lire)
  accessforbidden();

$langs->load("companies");
$langs->load("bills");
$langs->load("trips");

// Securiy check
if ($user->societe_id > 0)
{
  unset($_GET["action"]);
  $socid = $user->societe_id;
}


$trip = new Deplacement($db);


/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($_POST["action"] == 'update_public' && $user->rights->deplacement->creer)
{
	$db->begin();

	$trip->fetch($_GET["id"]);

	$res=$trip->update_note_public($_POST["note_public"],$user);
	if ($res < 0)
	{
		$mesg='<div class="error">'.$fac->error.'</div>';
		$db->rollback();
	}
	else
	{
		$db->commit();
	}
}

if ($_POST["action"] == 'update' && $user->rights->deplacement->creer)
{
	$db->begin();

	$trip->fetch($_GET["id"]);

	$res=$trip->update_note($_POST["note"],$user);
	if ($res < 0)
	{
		$mesg='<div class="error">'.$fac->error.'</div>';
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

$id = $_GET['id'];
$ref= $_GET['ref'];
if ($id > 0 || ! empty($ref))
{
	$trip = new Deplacement($db);
	$trip->fetch($id,$ref);

	$soc = new Societe($db);
    $soc->fetch($trip->socid);

	$h=0;

	$head[$h][0] = DOL_URL_ROOT."/compta/deplacement/fiche.php?id=$trip->id";
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/compta/deplacement/note.php?id=$trip->id";
	$head[$h][1] = $langs->trans("Note");
	$head[$h][2] = 'note';
	$h++;
	
	$head[$h][0] = DOL_URL_ROOT."/compta/deplacement/info.php?id=$deplacement->id";
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	dol_fiche_head($head, 'note', $langs->trans("TripCard"), 0, 'trip');


    print '<table class="border" width="100%">';

	// Ref
	print '<tr><td width="20%">'.$langs->trans('Ref').'</td>';
	print '<td colspan="3">';
	$morehtmlref='';
	print $html->showrefnav($trip,'ref','',1,'ref','ref',$morehtmlref);
	print '</td></tr>';

	// Type
	print '<tr><td>'.$langs->trans("Type").'</td><td>'.$langs->trans($trip->type).'</td></tr>';

	// Who
	print "<tr>";
	print '<td>'.$langs->trans("Person").'</td><td>';
	$userfee=new User($db);
	$userfee->fetch($trip->fk_user);
	print $userfee->getNomUrl(1);
	print '</td></tr>';

	print '<tr><td width="20%">'.$langs->trans("CompanyVisited").'</td>';
	print '<td>';
	if ($soc->id) print $soc->getNomUrl(1);
	print '</td></tr>';

	// Note publique
    print '<tr><td valign="top">'.$langs->trans("NotePublic").'</td>';
	print '<td valign="top" colspan="3">';
    if ($_GET["action"] == 'edit')
    {
        print '<form method="post" action="note.php?id='.$trip->id.'">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="update_public">';
        print '<textarea name="note_public" cols="80" rows="8">'.$trip->note_public."</textarea><br>";
        print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
        print '</form>';
    }
    else
    {
	    print ($trip->note_public?nl2br($trip->note_public):"&nbsp;");
    }
	print "</td></tr>";

	// Note privee
	if (! $user->societe_id)
	{
	    print '<tr><td valign="top">'.$langs->trans("NotePrivate").'</td>';
		print '<td valign="top" colspan="3">';
	    if ($_GET["action"] == 'edit')
	    {
	        print '<form method="post" action="note.php?id='.$trip->id.'">';
	        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	        print '<input type="hidden" name="action" value="update">';
	        print '<textarea name="note" cols="80" rows="8">'.$trip->note."</textarea><br>";
	        print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
	        print '</form>';
	    }
		else
		{
		    print ($trip->note?nl2br($trip->note):"&nbsp;");
		}
		print "</td></tr>";
	}

    print "</table>";


    /*
    * Actions
    */
    print '</div>';
    print '<div class="tabsAction">';

    if ($user->rights->deplacement->creer && $_GET["action"] <> 'edit')
    {
        print "<a class=\"butAction\" href=\"note.php?id=$trip->id&amp;action=edit\">".$langs->trans('Modify')."</a>";
    }

    print "</div>";


}

$db->close();

llxFooter();
?>
