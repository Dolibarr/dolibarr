<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

llxHeader('','Telephonie');

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

/*
 * Mode Liste
 *
 *
 *
 */

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td width="30%" valign="top">';


$sql = "SELECT date_format(f.datef,'%Y%m'), sum(f.total_ttc)";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as tf";
$sql .= " , ".MAIN_DB_PREFIX."facture as f";

$sql .= " WHERE tf.fk_facture = f.rowid";
$sql .= " GROUP BY date_format(f.datef,'%Y%m')";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  $ligne = new LigneTel($db);

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Factures</td><td valign="center">Nb</td>';
  print "</tr>\n";
  $var=True;

  while ($i < $num)
    {
      $row = $db->fetch_row($i);
      $var=!$var;

      print "<tr $bc[$var]>";
      print "<td>".$row[0]."</td>\n";
      print "<td>".$row[1]."</td>\n";
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




print '</td><td valign="top" width="70%" rowspan="3">';



print '</td></tr>';


print '<tr><td width="30%" valign="top">';
/*
 *
 *
 */





/*
 *
 *
 */


print '</td></tr>';

print '</table>';



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
