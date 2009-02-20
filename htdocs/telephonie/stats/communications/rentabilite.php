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

if (!$user->rights->telephonie->lire)
  accessforbidden();

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

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/communications/index.php';
$head[$h][1] = "Global";
$h++;
$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/communications/lastmonth.php';
$head[$h][1] = "Durée";
$h++;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/communications/destmonth.php';
$head[$h][1] = "Destinations";
$h++;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/communications/rentabilite.php';
$head[$h][1] = "Rentabilite";
$hselected = $h;
$h++;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/communications/analyse.php';
$head[$h][1] = "Analyse";
$h++;

dol_fiche_head($head, $hselected, "Communications");

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td valign="top">';

$sql = "SELECT date_format(date, '%Y%m'), avg(cout_vente)";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
$sql .= " GROUP BY date_format(date, '%Y%m') DESC";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  $ligne = new LigneTel($db);

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Mois</td><td align="right">Marge</td>';
  print "</tr>\n";
  $var=True;

  while ($i < $num)
    {
      $row = $db->fetch_row();	

      $var=!$var;

      print "<tr $bc[$var]>";
      print '<td>'.$row[0].'</td>'."\n";
      print '<td align="right">'.round($row[1],4)."</td>\n";


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
