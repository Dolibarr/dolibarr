<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 */

/**
        \file       htdocs/commande/note.php
        \ingroup    commande
        \brief      Fiche de notes sur une commande
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/order.lib.php');

$socid=isset($_GET["socid"])?$_GET["socid"]:isset($_POST["socid"])?$_POST["socid"]:"";

$user->getrights('commande');
if (!$user->rights->commande->lire)
  accessforbidden();

$langs->load("companies");
$langs->load("bills");
$langs->load("orders");

// Sécurité accés
if ($user->societe_id > 0) 
{
  unset($_GET["action"]);
  $socid = $user->societe_id;
}


$commande = new Commande($db);
$commande->fetch($_GET["id"]);


/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($_POST["action"] == 'update_public' && $user->rights->commande->creer)
{
	$db->begin();
	
	$res=$commande->update_note_public($_POST["note_public"]);
	if ($res < 0)
	{
		$mesg='<div class="error">'.$commande->error.'</div>';
		$db->rollback();
	}
	else
	{
		$db->commit();
	}
}

if ($_POST["action"] == 'update' && $user->rights->commande->creer)
{
	$db->begin();
	
	$res=$commande->update_note($_POST["note"]);
	if ($res < 0)
	{
		$mesg='<div class="error">'.$commande->error.'</div>';
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

if ($_GET["id"])
{
    $soc = new Societe($db, $commande->socid);
    $soc->fetch($commande->socid);

    $head = commande_prepare_head($commande);

    dolibarr_fiche_head($head, 'note', $langs->trans("CustomerOrder"));


    print '<table class="border" width="100%">';

	// Ref
	print '<tr><td width="18%">'.$langs->trans("Ref").'</td><td colspan="3">';
	print $commande->ref;
	print "</td></tr>";

	// Ref commande client
	print '<tr><td>';
    print '<table class="nobordernopadding" width="100%"><tr><td nowrap>';
	print $langs->trans('RefCustomer').'</td><td align="left">';
    print '</td>';
    print '</tr></table>';
    print '</td><td colspan="3">';
	print $commande->ref_client;
	print '</td>';
	print '</tr>';
	
	// Customer
	print "<tr><td>".$langs->trans("Company")."</td>";
	print '<td colspan="3">'.$soc->getNomUrl(1).'</td></tr>';

	// Note publique
    print '<tr><td valign="top">'.$langs->trans("NotePublic").' :</td>';
	print '<td valign="top" colspan="3">';
    if ($_GET["action"] == 'edit')
    {
        print '<form method="post" action="note.php?id='.$commande->id.'">';
        print '<input type="hidden" name="action" value="update_public">';
        print '<textarea name="note_public" cols="80" rows="8">'.$commande->note_public."</textarea><br>";
        print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
        print '</form>';
    }
    else
    {
	    print ($commande->note_public?nl2br($commande->note_public):"&nbsp;");
    }
	print "</td></tr>";

	// Note privée
	if (! $user->societe_id)
	{
	    print '<tr><td valign="top">'.$langs->trans("NotePrivate").' :</td>';
		print '<td valign="top" colspan="3">';
	    if ($_GET["action"] == 'edit')
	    {
	        print '<form method="post" action="note.php?id='.$commande->id.'">';
	        print '<input type="hidden" name="action" value="update">';
	        print '<textarea name="note" cols="80" rows="8">'.$commande->note."</textarea><br>";
	        print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
	        print '</form>';
	    }
		else
		{
		    print ($commande->note?nl2br($commande->note):"&nbsp;");
		}
		print "</td></tr>";
	}
	
    print "</table>";


    /*
    * Actions
    */
    print '</div>';
    print '<div class="tabsAction">';

    if ($user->rights->commande->creer && $_GET["action"] <> 'edit')
    {
        print "<a class=\"butAction\" href=\"note.php?id=$commande->id&amp;action=edit\">".$langs->trans('Edit')."</a>";
    }

    print "</div>";


}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
