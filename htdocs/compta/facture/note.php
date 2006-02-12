<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/compta/facture/note.php
        \ingroup    facture
        \brief      Fiche de notes sur une facture
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/invoice.lib.php');

$socidp=isset($_GET["socidp"])?$_GET["socidp"]:isset($_POST["socidp"])?$_POST["socidp"]:"";

$user->getrights('facture');
if (!$user->rights->facture->lire)
  accessforbidden();

$langs->load("companies");
$langs->load("bills");

// Sécurité accés
if ($user->societe_id > 0) 
{
  unset($_GET["action"]);
  $socidp = $user->societe_id;
}


$fac = new Facture($db);
$fac->fetch($_GET["facid"]);


/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($_POST["action"] == 'update_public' && $user->rights->facture->creer)
{
	$db->begin();
	
	$res=$fac->update_note_public($_POST["note_public"]);
	if ($res < 0)
	{
		$db->rollback();
		$msg=$fac->error();
	}
	else
	{
		$db->commit();
	}
}

if ($_POST["action"] == 'update' && $user->rights->facture->creer)
{
	$db->begin();
	
	$res=$fac->update_note($_POST["note"]);
	if ($res < 0)
	{
		$db->rollback();
		$msg=$fac->error();
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

if ($_GET["facid"])
{
    $soc = new Societe($db, $fac->socidp);
    $soc->fetch($fac->socidp);

    $head = facture_prepare_head($fac);
    $hselected = 2;
    if ($conf->use_preview_tabs) $hselected++;
    if ($fac->mode_reglement_code == 'PRE') $hselected++;

    dolibarr_fiche_head($head, $hselected, $langs->trans("Bill")." : $fac->ref");


    print '<table class="border" width="100%">';

    // Reference
	print '<tr><td width="20%">'.$langs->trans('Ref').'</td><td colspan="5">'.$fac->ref.'</td></tr>';

    print '<tr><td>'.$langs->trans("Company").'</td>';
    print '<td colspan="3">';
    print '<a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></td>';

    // Dates
    print '<tr><td>'.$langs->trans("Date").'</td>';
    print '<td>'.dolibarr_print_date($fac->date,"%A %d %B %Y").'</td>';
    print '<td>'.$langs->trans("DateClosing").'</td><td>' . dolibarr_print_date($fac->date_lim_reglement,"%A %d %B %Y");
    if ($fac->date_lim_reglement < (time() - $conf->facture->client->warning_delay) && ! $fac->paye && $fac->statut == 1 && ! $fac->am) print img_warning($langs->trans("Late"));
    print "</td></tr>";

    // Conditions et modes de réglement
    print '<tr><td>'.$langs->trans("PaymentConditions").'</td><td>';
    $html->form_conditions_reglement($_SERVER["PHP_SELF"]."?facid=$fac->id",$fac->cond_reglement_id,"none");
    print '</td>';
    print '<td width="25%">'.$langs->trans("PaymentMode").'</td><td width="25%">';
    $html->form_modes_reglement($_SERVER["PHP_SELF"]."?facid=$fac->id",$fac->mode_reglement_id,"none");
    print '</td></tr>';

	// Note publique
    print '<tr><td valign="top">'.$langs->trans("NotePublic").' :</td>';
	print '<td valign="top" colspan="3">';
    if ($_GET["action"] == 'edit')
    {
        print '<form method="post" action="note.php?facid='.$fac->id.'">';
        print '<input type="hidden" name="action" value="update_public">';
        print '<textarea name="note_public" cols="80" rows="8">'.$fac->note_public."</textarea><br>";
        print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
        print '</form>';
    }
    else
    {
	    print ($fac->note_public?nl2br($fac->note_public):"&nbsp;");
    }
	print "</td></tr>";

	// Note privée
    print '<tr><td valign="top">'.$langs->trans("NotePrivate").' :</td>';
	print '<td valign="top" colspan="3">';
    if ($_GET["action"] == 'edit')
    {
        print '<form method="post" action="note.php?facid='.$fac->id.'">';
        print '<input type="hidden" name="action" value="update">';
        print '<textarea name="note" cols="80" rows="8">'.$fac->note."</textarea><br>";
        print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
        print '</form>';
    }
	else
	{
	    print ($fac->note?nl2br($fac->note):"&nbsp;");
	}
	print "</td></tr>";
    print "</table>";


    /*
    * Actions
    */
    print '</div>';
    print '<div class="tabsAction">';

    if ($user->rights->facture->creer && $_GET["action"] <> 'edit')
    {
        print "<a class=\"tabAction\" href=\"note.php?facid=$fac->id&amp;action=edit\">".$langs->trans('Edit')."</a>";
    }

    print "</div>";


}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
