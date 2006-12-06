<?php
/* Copyright (C) 2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
   \file       htdocs/product/stock/fiche.php
    \ingroup    stock
     \brief      Page fiche de valorisation du stock dans l'entrepot
      \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("products");
$langs->load("stocks");
$mesg = '';

llxHeader("","",$langs->trans("WarehouseCard"));

if ($_GET["id"])
{
  if ($mesg) print $mesg;
  
  $entrepot = new Entrepot($db);
  $result = $entrepot->fetch($_GET["id"]);
  if ($result < 0)
    {
      dolibarr_print_error($db);
    }
  
  /*
   * Affichage fiche
   */

      /*
       * Affichage onglets
       */
      $h = 0;
      
      $head[$h][0] = DOL_URL_ROOT.'/product/stock/fiche.php?id='.$entrepot->id;
      $head[$h][1] = $langs->trans("WarehouseCard");
      $h++;
      
      $head[$h][0] = DOL_URL_ROOT.'/product/stock/mouvement.php?id='.$entrepot->id;
      $head[$h][1] = $langs->trans("StockMovements");
      $h++;
      
      $head[$h][0] = DOL_URL_ROOT.'/product/stock/fiche-valo.php?id='.$entrepot->id;
      $head[$h][1] = $langs->trans("EnhancedValue");
      $hselected=$h;
      $h++;
      
      $head[$h][0] = DOL_URL_ROOT.'/product/stock/user.php?id='.$entrepot->id;
      $head[$h][1] = $langs->trans("Users");
      $h++;
      
      $head[$h][0] = DOL_URL_ROOT.'/product/stock/info.php?id='.$entrepot->id;
      $head[$h][1] = $langs->trans("Info");
      $h++;
      
      dolibarr_fiche_head($head, $hselected, $langs->trans("Warehouse").': '.$entrepot->libelle);
      
      print '<table class="border" width="100%">';
      
      // Ref
      print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">'.$entrepot->libelle.'</td>';
      
      print '<tr><td>'.$langs->trans("LocationSummary").'</td><td colspan="3">'.$entrepot->lieu.'</td></tr>';
      
      // Description
      print '<tr><td valign="top">'.$langs->trans("Description").'</td><td colspan="3">'.nl2br($entrepot->description).'</td></tr>';
      
      print '<tr><td>'.$langs->trans('Address').'</td><td colspan="3">';
      print $entrepot->address;
      print '</td></tr>';
      
      print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%">'.$entrepot->cp.'</td>';
      print '<td width="25%">'.$langs->trans('Town').'</td><td width="25%">'.$entrepot->ville.'</td></tr>';
      
      print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
      print $entrepot->pays;
      print '</td></tr>';
      
      // Statut
      print '<tr><td>'.$langs->trans("Status").'</td><td colspan="3">'.$entrepot->getLibStatut(4).'</td></tr>';
      
      print '<tr><td valign="top">'.$langs->trans("NumberOfProducts").'</td><td colspan="3">';
      print $entrepot->nb_products();
      print "</td></tr>";           
      print "</table>";      
      print '</div>';
      
      
      /* ************************************************************************** */
      /*                                                                            */
      /* Graph                                                                      */
      /*                                                                            */
      /* ************************************************************************** */
            
      print "<div class=\"graph\">\n";
      
      $url=DOL_URL_ROOT.'/viewimage.php?modulepart=graph_stock&file=entrepot-'.$entrepot->id.'.png';
      
      print '<img src="'.$url.'">';            
      print "</div>";
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
