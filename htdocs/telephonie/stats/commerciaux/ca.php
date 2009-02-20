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

llxHeader('','Telephonie - Statistiques - Commerciaux');

/*
 *
 *
 *
 */

$h = 0;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/commerciaux/index.php';
$head[$h][1] = "Global";
$h++;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/commerciaux/ca.php';
$head[$h][1] = "CA";
$hselected = $h;
$h++;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/commerciaux/mensuel.php';
$head[$h][1] = "Mensuel";
$h++;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/commerciaux/contrats.php';
$head[$h][1] = "Contrats";
$h++;

dol_fiche_head($head, $hselected, "Commerciaux");

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
print '<tr><td width="30%" valign="top">';

$sql = "SELECT date_format(f.date,'%Y%m'), sum(f.cout_vente)";
$sql .= ", c.name, c.firstname, c.rowid";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as f";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= " , ".MAIN_DB_PREFIX."user as c";

$sql .= " WHERE l.rowid = f.fk_ligne";
$sql .= " AND l.fk_commercial_sign = c.rowid";
$sql .= " GROUP BY date_format(f.date,'%Y%m') DESC, c.rowid DESC";

print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';

print '<tr class="liste_titre"><td width="50%" valign="top">Nom</td><td align="center">Date</td><td>&nbsp;</td></tr>';

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows();
  $i = 0;
  $datas = array();
  $legends = array();
  $total = 0;
  while ($i < $num)
    {
      $row = $db->fetch_row($i);	

      $var=!$var;

      print "<tr $bc[$var]>";

      print '<td width="50%" valign="top">';
      print '<a href="commercial.php?commid='.$row[4];
      print '">'.$row[3]." ". $row[2].'</a></td><td align="center">'.$row[0].'</td>';
      print '<td align="right">'.price($row[1]).'</td></tr>';
      $total += $row[1];
      $i++;
    }
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}
$var=!$var;

print '</table><br />';



print '</td>';

print '</td><td valign="top" width="70%">';


print '</td></tr>';


print '</table>';



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
