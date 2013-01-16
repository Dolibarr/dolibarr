<?php
/* Copyright (C) 2002-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005		Simon TOSSER			<simon@kornog-computing.com>
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
 *   \file       htdocs/compta/ventilation/fournisseur/lignes.php
 *   \ingroup    facture
 *   \brief      Page de detail des lignes de ventilation d'une facture
 */

require '../../../main.inc.php';

$langs->load("bills");

if (!$user->rights->facture->lire) accessforbidden();
if (!$user->rights->compta->ventilation->creer) accessforbidden();
/*
 * Securite acces client
 */
if ($user->societe_id > 0) accessforbidden();

llxHeader('');

/*
 * Lignes de factures
 *
 */
$page = $_GET["page"];
if ($page < 0) $page = 0;
$limit = $conf->liste_limit;
$offset = $limit * $page ;

$sql = "SELECT f.facnumber, f.rowid as facid, l.fk_product, l.description, l.total_ttc as price, l.qty, l.rowid, l.tva_tx, l.fk_code_ventilation, c.intitule, c.numero ";
$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn_det as l";
$sql .= " , ".MAIN_DB_PREFIX."facture_fourn as f";
$sql .= " , ".MAIN_DB_PREFIX."compta_compte_generaux as c";

$sql .= " WHERE f.rowid = l.fk_facture_fourn AND f.fk_statut = 1 AND l.fk_code_ventilation <> 0 ";
$sql .= " AND c.rowid = l.fk_code_ventilation";

if (dol_strlen(trim($_GET["search_facture"])))
{
  $sql .= " AND f.facnumber like '%".$_GET["search_facture"]."%'";
}

$sql .= " ORDER BY l.rowid DESC";
$sql .= $db->plimit($limit+1,$offset);

$result = $db->query($sql);

if ($result)
{
  $num_lignes = $db->num_rows($result);
  $i = 0;

  print_barre_liste("Lignes de facture ventilées",$page,"lignes.php","",$sortfield,$sortorder,'',$num_lignes);

  print '<form method="GET" action="lignes.php">';
  print '<table class="noborder" width="100%">';
  print "<tr class=\"liste_titre\"><td>Facture</td>";
  print '<td>'.$langs->trans("Description").'</td>';
  print '<td align="right">'.$langs->trans("Montant").'</td>';
  print '<td colspan="2" align="center">'.$langs->trans("Compte").'</td>';
  print "</tr>\n";

  print '<tr class="liste_titre"><td><input name="search_facture" size="8" value="'.$_GET["search_facture"].'"></td>';
  print '<td><input type="submit"></td>';
  print '<td align="right">&nbsp;</td>';
  print '<td align="center">&nbsp;</td>';
  print '<td align="center">&nbsp;</td>';
  print "</tr>\n";

  $var=True;
  while ($i < min($num_lignes, $limit))
    {
      $objp = $db->fetch_object($result);
      $var=!$var;
      print "<tr $bc[$var]>";

      print '<td><a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$objp->facid.'">'.$objp->facnumber.'</a></td>';

      print '<td>'.stripslashes(nl2br($objp->description)).'</td>';
      print '<td align="right">'.price($objp->price).'</td>';
      print '<td align="right">'.$objp->numero.'</td>';
      print '<td align="left">'.stripslashes($objp->intitule).'</td>';

      print "</tr>";
      $i++;
    }
}
else
{
  print $db->error();
}

print "</table></form>";

$db->close();

llxFooter();
?>
