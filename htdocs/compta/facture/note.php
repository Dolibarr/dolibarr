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


$facture = new Facture($db);
$facture->fetch($_GET["facid"]);


/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($_POST["action"] == 'update' && $user->rights->facture->creer)
{
  $facture->update_note($_POST["note"]);
}


/******************************************************************************/
/*                   Fin des  Actions                                         */
/******************************************************************************/


llxHeader();


if ($_GET["facid"])
{
      $soc = new Societe($db, $facture->socidp);
      $soc->fetch($facture->socidp);

      $h=0;
      
      $head[$h][0] = DOL_URL_ROOT.'/compta/facture.php?facid='.$facture->id;
      $head[$h][1] = $langs->trans("CardBill");
      $h++;
      $head[$h][0] = DOL_URL_ROOT.'/compta/facture/apercu.php?facid='.$facture->id;
      $head[$h][1] = $langs->trans("Preview");
      $h++;

      if ($facture->mode_reglement == 3)
	{
	  $head[$h][0] = DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$facture->id;
	  $head[$h][1] = $langs->trans("StandingOrders");
	  $h++;
	}
      
      $head[$h][0] = DOL_URL_ROOT.'/compta/facture/note.php?facid='.$facture->id;
      $head[$h][1] = $langs->trans("Note");
      $hselected = $h;
      $h++;      
      $head[$h][0] = DOL_URL_ROOT.'/compta/facture/info.php?facid='.$facture->id;
      $head[$h][1] = $langs->trans("Info");
      $h++;

      dolibarr_fiche_head($head, $hselected, $langs->trans("Bill")." : $facture->ref");
                  
	  
      print '<table class="border" width="100%">';

      print '<tr><td>'.$langs->trans("Company").'</td>';
      print '<td colspan="3">';
      print '<a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></td>';
      
      print '<tr><td>'.$langs->trans("Date").'</td>';
      print '<td>'.dolibarr_print_date($facture->date,"%A %d %B %Y")."</td>\n";
      print '<td width="25%">'.$langs->trans("DateClosing").'</td><td width="25%">'.dolibarr_print_date($facture->date_lim_reglement,"%A %d %B %Y") ."</td></tr>";
      
      // Conditions et modes de réglement
      print '<tr><td>'.$langs->trans("PaymentConditions").'</td><td>'. $facture->cond_reglement . '</td>';
      print '<td>'.$langs->trans("PaymentMode").'</td><td>'. $facture->mode_reglement . '</td></tr>';

      print '<tr><td valign="top" colspan="4">'.$langs->trans("Note").' :</td></tr>';

      print '<tr><td valign="top" colspan="4">'.($facture->note?nl2br($facture->note):"&nbsp;")."</td></tr>";
      
      if ($_GET["action"] == 'edit')
	{
	  print '<form method="post" action="note.php?facid='.$facture->id.'">';
	  print '<input type="hidden" name="action" value="update">';
	  print '<tr><td valign="top" colspan="4"><textarea name="note" cols="80" rows="8">'.$facture->note."</textarea></td></tr>";
	  print '<tr><td align="center" colspan="4"><input type="submit" value="'.$langs->trans("Save").'"></td></tr>';
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
	  print "<a class=\"tabAction\" href=\"note.php?facid=$facture->id&amp;action=edit\">".$langs->trans('Edit')."</a>";
	}
      
      print "</div>";
      
      
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
