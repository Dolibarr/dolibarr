<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

llxHeader();

$db = new Db();


$sql = "SELECT sum(d.amount) as somme , d.fk_statut FROM llx_don as d GROUP BY d.fk_statut";

$result = $db->query($sql);

if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);

      $somme[$objp->fk_statut] = $objp->somme;
      $i++;
    }
  $db->free();
}

print_barre_liste("Dons", $page, $PHP_SELF);

$libelle[0] = "Promesses non validées";
$libelle[1] = "Promesses validées";
$libelle[2] = "Dons payés";
$libelle[3] = "Dons encaissés";

print '<TABLE border="0" cellspacing="0" cellpadding="4">';
print '<TR class="liste_titre">';
print "<td>&nbsp;</td>";
print "<td>Somme</td>";
print "</TR>\n";

$var=True;

for ($i = 0 ; $i < 4 ; $i++)
{
  $var=!$var;
  print "<TR $bc[$var]>";
  print '<TD><a href="liste.php?statut='.$i.'">'.$libelle[$i].'</a></TD>';
  print '<TD align="right">'.price($somme[$i]).'</TD>';
  print "</tr>";
}
print "</table>";



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
