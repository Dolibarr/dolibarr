<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Éric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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



require("./pre.inc.php");


if ($_GET["socidp"]) { $socidp=$_GET["socidp"]; }
/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

llxHeader('');

/*
 * Lignes de factures
 *
 */
$page = $_GET["page"];
if ($page < 0) $page = 0;
$limit = $conf->liste_limit;
$offset = $limit * $page ;

$sql = "SELECT f.facnumber, f.rowid as facid, l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_taux, l.fk_code_ventilation, c.intitule, c.numero ";
$sql .= " FROM ".MAIN_DB_PREFIX."facturedet as l";
$sql .= " , ".MAIN_DB_PREFIX."facture as f";
$sql .= " , ".MAIN_DB_PREFIX."compta_compte_generaux as c";

$sql .= " WHERE f.rowid = l.fk_facture AND f.fk_statut = 1 AND l.fk_code_ventilation <> 0 AND c.rowid = l.fk_code_ventilation";
$sql .= " ORDER BY l.rowid DESC";
$sql .= $db->plimit($limit+1,$offset);


$result = $db->query($sql);
if ($result)
{
  $num_lignes = $db->num_rows();
  $i = 0; 
  
  print_barre_liste("Lignes de facture ventilées",$page,"lignes.php","",$sortfield,$sortorder,'',$num_lignes);

  if ($num_lignes)
    {
      echo '<table class="noborder" width="100%">';
      print "<tr class=\"liste_titre\"><td>Facture</td>";
      print '<td width="54%">'.$langs->trans("Description").'</td>';
      print '<td colspan="2" align="center">'.$langs->trans("Compte").'</td>';
      print "</tr>\n";
    }
  $var=True;
  while ($i < min($num_lignes, $limit))
    {
      $objp = $db->fetch_object();
      $var=!$var;
      print "<tr $bc[$var]>";
      
      print '<td><a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$objp->facid.'">'.$objp->facnumber.'</a></td>';

      print '<td>'.stripslashes(nl2br($objp->description)).'</td>';
                       
      print '<td>';
      print $objp->numero;
      print '</td>';

      print '<td>';
      print $objp->intitule;
      print '</td>';

      print "</tr>";
      $i++;
    }
  print "</table>";
}
else
{
  print $db->error();
}
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
