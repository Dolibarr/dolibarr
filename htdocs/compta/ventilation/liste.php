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

/*!	    \file       htdocs/compta/facture.php
		\ingroup    facture
		\brief      Page de création d'une facture
		\version    $Revision$
*/

require("./pre.inc.php");

$user->getrights('facture');
$user->getrights('banque');

if (!$user->rights->facture->lire)
  accessforbidden();

$langs->load("bills");

require_once "../../facture.class.php";
require_once "../../paiement.class.php";


if ($_GET["socidp"]) { $socidp=$_GET["socidp"]; }
/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

llxHeader('',$langs->trans("Bill"),'Facture');

/*
 * Lignes de factures
 *
 */
$page = $_GET["page"];
if ($page < 0) $page = 0;
$limit = $conf->liste_limit;
$offset = $limit * $page ;

$sql = "SELECT f.facnumber, f.rowid as facid, l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_taux, l.remise_percent, l.subprice, ".$db->pdate("l.date_start")." as date_start, ".$db->pdate("l.date_end")." as date_end, l.fk_code_ventilation ";
$sql .= " FROM ".MAIN_DB_PREFIX."facturedet as l";
$sql .= " , ".MAIN_DB_PREFIX."facture as f";
$sql .= " WHERE f.rowid = l.fk_facture AND f.fk_statut = 1 AND fk_code_ventilation = 0";
$sql .= " ORDER BY l.rowid DESC";
$sql .= $db->plimit($limit+1,$offset);


$result = $db->query($sql);
if ($result)
{
  $num_lignes = $db->num_rows();
  $i = 0; 
  
  print_barre_liste("Lignes de facture à ventiler",$page,"liste.php","",$sortfield,$sortorder,'',$num_lignes);

  if ($num_lignes)
    {
      echo '<table class="noborder" width="100%">';
      print "<tr class=\"liste_titre\"><td>Facture</td>";
      print '<td width="54%">'.$langs->trans("Description").'</td>';
      print '<td width="20%" align="right">&nbsp;</td>';
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
      print '<td align="right"><a href="fiche.php?id='.$objp->rowid.'">';
      print img_edit();
      print '</a></td>';

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
