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

if (!$user->rights->telephonie->lire) accessforbidden();

llxHeader('','Telephonie - Statistiques');

$h = 0;
$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/clients/index.php';
$head[$h][1] = "Global";
$h++;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/clients/gain.php';
$head[$h][1] = "Gain";
$hselected = $h;
$h++;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/clients/alerte.php';
$head[$h][1] = "Alerte";
$h++;

dol_fiche_head($head, $hselected, "Clients");

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td width="70%" valign="top">';

$page = $_GET["page"];
$sortfield = $_GET["sortfield"];
$sortorder = $_GET["sortorder"];

if ($sortorder == "") $sortorder="DESC";
if ($sortfield == "") $sortfield="marge";

$sql = "SELECT s.nom, tcs.ca, tcs.gain, tcs.cout, tcs.marge, s.rowid";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_client_stats as tcs";
$sql .= " , " .MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE s.rowid = tcs.fk_client_comm";
$sql .= " ORDER BY $sortfield $sortorder ";// . $db->plimit($conf->liste_limit+1, $offset);

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print_liste_field_titre("Client","gain.php","s.nom");
  print_liste_field_titre("Marge","gain.php","tcs.marge",'','','align="right"');
  print_liste_field_titre("Gain Total","gain.php","tcs.gain",'','','align="right"');
  print_liste_field_titre("Vente","gain.php","tcs.ca",'','','align="right"');
  print_liste_field_titre("Achat","gain.php","tcs.cout",'','','align="right"');
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
	  print '<td align="right"><b><font color="red">'.$marge."</font></b> %</td>\n";
	}
      else
	{
	  print '<td align="right">'.$marge." %</td>\n";
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
