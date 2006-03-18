<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * $Source$
 */

/**
        \file       htdocs/contrat/note.php
        \ingroup    contrat
        \brief      Fiche de notes sur un contrat
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/contract.lib.php');
if ($conf->contrat->enabled) require_once(DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");

$socidp=isset($_GET["socidp"])?$_GET["socidp"]:isset($_POST["socidp"])?$_POST["socidp"]:"";

$user->getrights('contrat');
if (!$user->rights->contrat->lire)
  accessforbidden();

$langs->load("companies");
$langs->load("contracts");

// Sécurité accés client et commerciaux
$contratid = isset($_GET["id"])?$_GET["id"]:'';

if ($user->societe_id > 0) 
{
  unset($_GET["action"]);
  $socidp = $user->societe_id;
}

// Protection restriction commercial
if ($contratid)
{
        $sql = "SELECT sc.fk_soc, c.fk_soc";
        $sql .= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc, ".MAIN_DB_PREFIX."contrat as c";
        $sql .= " WHERE c.rowid = ".$contratid;
        if (!$user->rights->commercial->client->voir && !$user->societe_id > 0)
        {
        	$sql .= " AND sc.fk_soc = c.fk_soc AND sc.fk_user = ".$user->id;
        }
        if ($user->societe_id > 0) $sql .= " AND c.fk_soc = ".$socidp;

        if ( $db->query($sql) )
        {
          if ( $db->num_rows() == 0) accessforbidden();
        }
}

$contrat = new Contrat($db);
$contrat->fetch($_GET["id"]);


/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($_POST["action"] == 'update_public' && $user->rights->contrat->creer)
{
	$db->begin();
	
	$res=$contrat->update_note_public($_POST["note_public"]);
	if ($res < 0)
	{
		$mesg='<div class="error">'.$contrat->error.'</div>';
		$db->rollback();
	}
	else
	{
		$db->commit();
	}
}

if ($_POST["action"] == 'update' && $user->rights->contrat->creer)
{
	$db->begin();
	
	$res=$contrat->update_note($_POST["note"]);
	if ($res < 0)
	{
		$mesg='<div class="error">'.$contrat->error.'</div>';
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
	if ($mesg) print $mesg;
	
    $soc = new Societe($db, $contrat->societe->id);
    $soc->fetch($contrat->societe->id);

    $head = contract_prepare_head($contrat);

    $hselected = 2;

    dolibarr_fiche_head($head, $hselected, $langs->trans("Contract"));


    print '<table class="border" width="100%">';

    // Reference
	print '<tr><td width="20%">'.$langs->trans('Ref').'</td><td colspan="5">'.$contrat->ref.'</td></tr>';

    print '<tr><td>'.$langs->trans("Customer").'</td>';
    print '<td colspan="3">';
    print '<a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></td>';

	// Note publique
    print '<tr><td valign="top">'.$langs->trans("NotePublic").' :</td>';
	print '<td valign="top" colspan="3">';
    if ($_GET["action"] == 'edit')
    {
        print '<form method="post" action="note.php?id='.$contrat->id.'">';
        print '<input type="hidden" name="action" value="update_public">';
        print '<textarea name="note_public" cols="80" rows="'.ROWS_8.'">'.$contrat->note_public."</textarea><br>";
        print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
        print '</form>';
    }
    else
    {
	    print ($contrat->note_public?nl2br($contrat->note_public):"&nbsp;");
    }
	print "</td></tr>";

	// Note privée
    print '<tr><td valign="top">'.$langs->trans("NotePrivate").' :</td>';
	print '<td valign="top" colspan="3">';
    if ($_GET["action"] == 'edit')
    {
        print '<form method="post" action="note.php?id='.$contrat->id.'">';
        print '<input type="hidden" name="action" value="update">';
        print '<textarea name="note" cols="80" rows="'.ROWS_8.'">'.$contrat->note."</textarea><br>";
        print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
        print '</form>';
    }
	else
	{
	    print ($contrat->note?nl2br($contrat->note):"&nbsp;");
	}
	print "</td></tr>";
    print "</table>";


    /*
    * Actions
    */
    print '</div>';
    print '<div class="tabsAction">';

    if ($user->rights->contrat->creer && $_GET["action"] <> 'edit')
    {
        print "<a class=\"tabAction\" href=\"note.php?id=".$contrat->id."&amp;action=edit\">".$langs->trans('Edit')."</a>";
    }

    print "</div>";


}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
