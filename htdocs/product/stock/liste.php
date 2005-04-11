<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 */

/**
        \file       htdocs/product/stock/liste.php
        \ingroup    stock
        \brief      Page liste des stocks
        \version    $Revision$
*/

require("./pre.inc.php");
require_once("./entrepot.class.php");

$user->getrights('stocks');
$langs->load("stocks");

if (!$user->rights->stock->lire)
  accessforbidden();


if ($page < 0) $page = 0;
$limit = $conf->liste_limit;
$offset = $limit * $page ;
  
if (! $sortfield) $sortfield="e.label";
if (! $sortorder) $sortorder="ASC";

  
$sql  = "SELECT e.rowid as ref, e.label, e.statut, e.lieu, e.address, e.cp, e.ville, e.fk_pays";
$sql .= " FROM ".MAIN_DB_PREFIX."entrepot as e";
$sql .= " ORDER BY $sortfield $sortorder";
$sql .= $db->plimit($limit + 1 ,$offset);
$result = $db->query($sql) ;

if ($result)
{
  $num = $db->num_rows($result);

  $i = 0;
  
  llxHeader("","",$langs->trans("ListOfWarehouses"));

  print_barre_liste($langs->trans("ListOfWarehouses"), $page, "liste.php", "", $sortfield, $sortorder,'',$num);

  print '<table class="noborder" width="100%">';

  print "<tr class=\"liste_titre\">";
  print_liste_field_titre($langs->trans("Ref"),"liste.php", "e.ref","");
  print_liste_field_titre($langs->trans("Label"),"liste.php", "e.label","");
  print_liste_field_titre($langs->trans("Status"),"liste.php", "e.statut","");
  print_liste_field_titre($langs->trans("LocationSummary"),"liste.php", "e.lieu","");
  print "</tr>\n";
  
  if ($num) {
      $entrepot=new Entrepot($db);
    
      $var=True;
      while ($i < min($num,$limit))
        {
          $objp = $db->fetch_object($result);
          $var=!$var;
          print "<tr $bc[$var]>";
          print '<td><a href="fiche.php?id='.$objp->ref.'">'.img_object($langs->trans("ShowWarehouse"),'stock').' '.$objp->ref.'</a></td>';
          print '<td>'.$objp->label.'</td>';
          print '<td>'.$entrepot->LibStatut($objp->statut).'</td>';
          print '<td>'.$objp->lieu.'</td>';
          print "</tr>\n";
          $i++;
        }
    }
    
  $db->free($result);

  print "</table>";

}
else
{
  dolibarr_print_error($db);
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
