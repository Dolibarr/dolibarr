<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Jean-Louis Bergamo <jlb@j1b.org>
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

//$db = new Db();

print_titre("Gestion des adherents");

print '<p><TABLE border="0" cellspacing="0" cellpadding="4">';
print '<TR class="liste_titre">';
print "<td>Type</td>";
print "<td>Nb</td>";
print "<td>Cotisant</td>";
print "</TR>\n";

$var=True;


$sql = "SELECT count(*) as somme , t.libelle FROM llx_adherent as d, llx_adherent_type as t";
$sql .= " WHERE d.fk_adherent_type = t.rowid  AND d.statut = 1 GROUP BY t.libelle";

$Adherents=array();
$Cotisants=array();

$result = $db->query($sql);

if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $Adherents[$objp->libelle]=$objp->somme;
      $i++;
    }
  $db->free();

}

$sql = "SELECT count(*) as somme , t.libelle FROM llx_adherent as d, llx_adherent_type as t";
$sql .= " WHERE d.fk_adherent_type = t.rowid  AND d.statut = 1 AND d.datefin >= ".time()." GROUP BY t.libelle";

$result = $db->query($sql);

if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $Cotisants[$objp->libelle]=$objp->somme;
      $i++;
    }
  $db->free();

}
$SommeA=0;
$SommeC=0;

foreach ($Adherents as $key=>$value){
  $var=!$var;
  print "<TR $bc[$var]>";
  print '<TD><a href="liste.php">'.$key.'</a></TD>';
  print '<TD align="right">'.$value.'</TD>';
  print '<TD align="right">'.$Cotisants[$key].'</TD>';
  print "</TR>\n";
  $SommeA+=$value;
  $SommeC+=$Cotisants[$key];
}
$var=!$var;
print "<TR $bc[$var]>";
print '<TD> <B>Total</B> </TD>';
print '<TD align="right"><B>'.$SommeA.'</B></TD>';
print '<TD align="right"><B>'.$SommeC.'</B></TD>';
print "<TR>\n";

print "</table>";

print '<form action="liste.php" method="post" name="action" value="search">';
print '<input type="hidden" name="action" value="search">';
print '<p><TABLE border="0" cellspacing="0" cellpadding="4">';
print '<TR class="liste_titre">';
print "<td>Rechercher un adhérent</td>";
print "</TR>\n";

print "<TR $bc[$var]>";
print '<td>';

print 'Nom/Prénom <input type="text" name="search" class="flat" size="20">';

print '&nbsp; <input class="flat" type="submit" value="Chercher">';
print '</td></tr>';
print "</table></form>";




$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
