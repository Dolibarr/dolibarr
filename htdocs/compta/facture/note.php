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

$user->getrights('facture');
if (!$user->rights->facture->lire)
  accessforbidden();

$langs->load("companies");
$langs->load("bills");

/*
 * Sécurité accés client
 */
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

if ($_POST["action"] == 'update' && $user->rights->facture->creer)
{
  $fac->update_note($_POST["note"]);
}


/******************************************************************************/
/*                   Fin des  Actions                                         */
/******************************************************************************/


llxHeader();

$html = new Form($db);

if ($_GET["facid"])
{
    $soc = new Societe($db, $fac->socidp);
    $soc->fetch($fac->socidp);

    $h=0;

    $head[$h][0] = DOL_URL_ROOT.'/compta/facture.php?facid='.$fac->id;
    $head[$h][1] = $langs->trans("CardBill");
    $h++;
    $head[$h][0] = DOL_URL_ROOT.'/compta/facture/apercu.php?facid='.$fac->id;
    $head[$h][1] = $langs->trans("Preview");
    $h++;

    if ($fac->mode_reglement_code == 'PRE')
    {
        $head[$h][0] = DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$fac->id;
        $head[$h][1] = $langs->trans("StandingOrders");
        $h++;
    }

    $head[$h][0] = DOL_URL_ROOT.'/compta/facture/note.php?facid='.$fac->id;
    $head[$h][1] = $langs->trans("Note");
    $hselected = $h;
    $h++;
    $head[$h][0] = DOL_URL_ROOT.'/compta/facture/info.php?facid='.$fac->id;
    $head[$h][1] = $langs->trans("Info");
    $h++;

    dolibarr_fiche_head($head, $hselected, $langs->trans("Bill")." : $fac->ref");


    print '<table class="border" width="100%">';

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

    print '<tr><td valign="top" colspan="4">'.$langs->trans("Note").' :</td></tr>';

    print '<tr><td valign="top" colspan="4">'.($fac->note?nl2br($fac->note):"&nbsp;")."</td></tr>";

    if ($_GET["action"] == 'edit')
    {
        print '<form method="post" action="note.php?facid='.$fac->id.'">';
        print '<input type="hidden" name="action" value="update">';
        print '<tr><td valign="top" colspan="4"><textarea name="note" cols="80" rows="8">'.$fac->note."</textarea></td></tr>";
        print '<tr><td align="center" colspan="4"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td></tr>';
        print '</form>';
    }

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
