<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
$user->getrights('produit');

if (!$user->rights->produit->lire)
  accessforbidden();

if ($action == 'update')
{
  $sql = "UPDATE llx_product SET description='$desc' where rowid = $rowid";
  $db->query($sql);
}

/*
 *
 *
 */

llxHeader("","","Accueil Produits et services");

print_titre("Produits et services");

print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td valign="top" width="70%">';

  
$sql = "SELECT p.rowid, p.label, p.price, p.ref FROM llx_product as p WHERE envente=1";
$sql .= " ORDER BY p.datec DESC ";
$sql .= $db->plimit(15 ,0);
$result = $db->query($sql) ;

if ($result)
{
  $num = $db->num_rows();

  $i = 0;
  
  if ($num > 0)
    {
      print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';

      print '<tr class="liste_titre"><td colspan="2">Derniers produits et services</td></tr>';
    
      $var=True;
      while ($i < $num)
	{
	  $objp = $db->fetch_object( $i);
	  $var=!$var;
	  print "<TR $bc[$var]>";
	  print "<TD><a href=\"fiche.php?id=$objp->rowid\">$objp->ref</a></TD>\n";
	  print "<TD>$objp->label</TD></tr>\n";
	  $i++;
	}
      $db->free();

      print "</table>";
    }
}
else
{
  print $db->error() . "<br>" .$sql;
}

print '</td><td valign="top" width="30%">';

print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td colspan="2">Hors vente</td></tr>';
print "<TR $bc[0]>";
print '<td><a href="liste.php?type=0&envente=0">Produits hors vente</a></td></tr>';
print "<TR $bc[1]>";
print '<td><a href="liste.php?type=1&envente=0">Services hors vente</a></td></tr></table>';

print '</td><td valign="top" width="30%">';
print '&nbsp';
print "</td>";

print '</tr></table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
