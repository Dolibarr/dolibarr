<?PHP
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

if (!$user->rights->telephonie->lire) accessforbidden();
if (!$user->rights->telephonie->stats->lire) accessforbidden();

llxHeader('','Telephonie - Statistiques - Contrats');

/*
 *
 *
 */

include "./onglets.php";
dol_fiche_head($head, $hselected, "Contrats");

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td valign="top" width="50%">';

print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=contrats/modereglement.png" alt="Mode de réglement" title="Mode de réglement"><br /><br />'."\n";

print '</td><td valign="top" width="50%">';

$sql = "SELECT date_format(f.date, '%Y%m'), sum(f.cout_vente), c.mode_paiement";

$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as f";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_contrat as c";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";

$sql .= " WHERE f.fk_ligne = l.rowid";
$sql .= " AND l.fk_contrat = c.rowid";
$sql .= " AND c.isfacturable = 'oui'";
$sql .= " GROUP BY date_format(f.date, '%Y%m'), c.mode_paiement";
$sql .= " ORDER BY date_format(f.date, '%Y%m') DESC";
$sql .= " LIMIT 10";
$resql = $db->query($sql);

print '<table class="border" width="100%">';
print '<tr><td>Mois</td><td align="right">Montant</td><td align="center">Mode</td></tr>';
if ($resql)
{
  $num = $db->num_rows();
  $i = 0;

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);

      print '<tr><td>'.$row[0].'</td>';
      print '<td align="right">'.sprintf("%01.2f",$row[1]).'</td>';
      print '<td align="center">'.$row[2].'</td></tr>';

      $i++;
    }
}
print '</table>';

/*
 *
 *
 */

print '</td></tr>';
print '</table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
