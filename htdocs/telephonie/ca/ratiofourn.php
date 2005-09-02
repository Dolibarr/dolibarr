<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*
 * Sécurité accés client
 */

if (!$user->rights->telephonie->ca->lire) accessforbidden();

llxHeader('','Telephonie - Ratio fournisseur');

/*
 *
 *
 *
 */
print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td width="50%" valign="top">';

$page = $_GET["page"];
$offset = $conf->liste_limit * $page ;

$sql = "SELECT s.nom, af.mois, af.achat, af.vente";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_analyse_fournisseur as af";

$sql .= " , " .MAIN_DB_PREFIX."telephonie_fournisseur as s";
$sql .= " WHERE s.rowid = af.fk_fournisseur";
$sql .= " ORDER BY mois DESC " . $db->plimit($conf->liste_limit+1, $offset);

$resql = $db->query($sql);

if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;

  print_barre_liste("Ratio fournisseur", $page, "ratiofourn.php", $urladd, $sortfield, $sortorder, '', $num);

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Mois</td><td>Fournisseur</td><td align="right">Achat</td>';
  print '<td align="right">Revente</td>';
  print "</tr>\n";
  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $row = $db->fetch_row($resql);	
      $var=!$var;

      print "<tr $bc[$var]>";
      print '<td>'.strftime ("%B %Y",mktime(12,0,0,substr($row[1],-2),1,substr($row[1],0,4)))."</td>\n";
      print "<td>".$row[0]."</td>\n";
      print '<td align="right">'.price($row[2])." HT</td>\n";
      print '<td align="right">'.price($row[3])." HT</td>\n";
      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free($resql);
}
else 
{
  print $db->error() . ' ' . $sql;
}

//print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=ca/gain_moyen_par_client.png" alt="Gain moyen par client"><br /><br />';

print '</td></tr>';
print '</table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
