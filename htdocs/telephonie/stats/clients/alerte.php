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

$h = 0;
$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/clients/index.php';
$head[$h][1] = "Global";
$h++;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/clients/gain.php';
$head[$h][1] = "Gain";
$h++;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/clients/alerte.php';
$head[$h][1] = "Alerte";
$hselected = $h;
$h++;

dol_fiche_head($head, $hselected, "Clients");

if ($_GET["marge"] > 0)
{
  $marge = $_GET["marge"];
}
else
{
  $marge = TELEPHONIE_MARGE_MINI;
}
print '<form method="get" action="alerte.php">';
print "Clients dont la marge est inférieure à ";

print '<input type="text"  name="marge" value="'.$marge.'" size="3" >';
print '%<input type="submit"></form>';

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td width="70%" valign="top">';

$sql = "SELECT s.nom, tcs.ca, tcs.gain, tcs.cout, tcs.marge, s.rowid";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_client_stats as tcs";
$sql .= " , " .MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE s.rowid = tcs.fk_client_comm";
$sql .= " AND tcs.marge < ". $marge;
$sql .= " GROUP BY tcs.marge DESC";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Client</td><td align="right">Marge</td>';
  print '<td align="right">Gain Total</td>';
  print '<td align="right">Vente</td>';
  print '<td align="right">Achat</td>';
  print "</tr>\n";
  $var=True;

  while ($i < $num)
    {
      $row = $db->fetch_row($i);	
      $var=!$var;

      print "<tr $bc[$var]>";
      print '<td><a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$row[5].'">'.$row[0].'</a></td>'."\n";

      $marge = round($row[4],2);

      if ($marge < 0)
	{
	  print '<td align="right"><b><font color="red">';
	  printf("%.2f",$marge);
	  print "</font></b> %</td>\n";
	}
      else
	{
	  print '<td align="right">';
	  printf("%.2f",$marge);
	  print " %</td>\n";
	}
      print '<td align="right">'.price($row[2])." HT</td>\n";
      print '<td align="right">'.price($row[1])." HT</td>\n";
      print '<td align="right">'.price($row[3])." HT</td>\n";

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

print '</td></tr>';

print '</table>';



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
