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

$page = $_GET["page"];
$sortorder = $_GET["sortorder"];

if (!$user->rights->telephonie->lire) accessforbidden();
if (!$user->rights->telephonie->stats->lire) accessforbidden();

llxHeader('','Telephonie - Statistiques');

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

$h = 0;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/destinations/index.php';
$head[$h][1] = "Destinations";
$hselected = $h;
$h++;

dol_fiche_head($head, $hselected, "Destinations");

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td valign="top">';

$sql = "SELECT destination, nbappels, ca, duree, duree_moy";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_stats_destination";
$sql .= " ORDER BY ca DESC";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Destination</td><td align="right">CA</td>';
  print '<td align="right">Nb Appels</td>';
  print '<td align="right">Durée (sec)</td><td align="right">Durée moyenne (sec)</td></tr>';
  $var=True;

  while ($i < $num)
    {
      $row = $db->fetch_row();	

      $var=!$var;

      print "<tr $bc[$var]>";
      print '<td>'.$row[0].'</td>'."\n";
      print '<td align="right">'.sprintf("%01.2f",$row[2])."</td>\n";
      print '<td align="right">'.$row[1]."</td>\n";
      print '<td align="right">'.$row[3]."</td>\n";
      print '<td align="right">'.sprintf("%01.2f",$row[4])."</td>\n";


      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}


print '</td><td valign="top">';

print '</td></tr>';

print '</table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
