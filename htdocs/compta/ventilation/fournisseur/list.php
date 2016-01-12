<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */


/**
 *   \file       htdocs/compta/ventilation/list.php
 *   \ingroup    compta
 *   \brief      Page de ventilation des lignes de facture
 */

require '../../../main.inc.php';

$langs->load("bills");

if (!$user->rights->facture->lire) accessforbidden();
if (!$user->rights->compta->ventilation->creer) accessforbidden();
/*
 * Securite acces client
 */
if ($user->societe_id > 0) accessforbidden();


llxHeader('','Ventilation');

/*
 * Lignes de factures
 *
 */
$page = $_GET["page"];
if ($page < 0) $page = 0;
$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$offset = $limit * $page ;

$sql = "SELECT f.facnumber, f.rowid as facid, l.fk_product, l.description, l.total_ttc as price, l.rowid, l.fk_code_ventilation ";
$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn_det as l";
$sql .= " , ".MAIN_DB_PREFIX."facture_fourn as f";
$sql .= " WHERE f.rowid = l.fk_facture_fourn AND f.fk_statut = 1 AND fk_code_ventilation = 0";
$sql .= " ORDER BY l.rowid DESC ".$db->plimit($limit+1,$offset);

$result = $db->query($sql);
if ($result)
{
  $num_lignes = $db->num_rows($result);
  $i = 0;

  print_barre_liste("Lignes de facture à ventiler",$page,"list.php","",$sortfield,$sortorder,'',$num_lignes);

  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre"><td>Facture</td>';
  print '<td>'.$langs->trans("Description").'</td>';
  print '<td align="right">&nbsp;</td>';
  print '<td>&nbsp;</td>';
  print "</tr>\n";

  $var=True;
  while ($i < min($num_lignes, $limit))
    {
      $objp = $db->fetch_object($result);
      $var=!$var;
      print "<tr ".$bc[$var].">";

      print '<td><a href="'.DOL_URL_ROOT.'/fourn/facture/card.php?facid='.$objp->facid.'">'.$objp->facnumber.'</a></td>';
      print '<td>'.stripslashes(nl2br($objp->description)).'</td>';

      print '<td align="right">';
      print price($objp->price);
      print '</td>';

      print '<td align="right"><a href="card.php?id='.$objp->rowid.'">';
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

llxFooter();
$db->close();
