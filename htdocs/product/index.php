<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 20004     Laurent Destailleur  <eldy@users.sourceforge.net>
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

$langs->load("products");


if (!$user->rights->produit->lire)
  accessforbidden();


if ($action == 'update')
{
  $sql = "UPDATE ".MAIN_DB_PREFIX."product SET description='$desc' where rowid = $rowid";
  $db->query($sql);
}


/*
 *
 *
 */

llxHeader("","",$langs->trans("ProductsAndServices"));

print_titre($langs->trans("ProductsAndServices"));

print '<table border="0" width="100%" cellspacing="0" cellpadding="3">';

print '<tr><td valign="top" width="30%">';


/*
 * Nombre de produits et/ou services
 */
$prodser = array();
$sql = "SELECT count(*), fk_product_type, envente FROM ".MAIN_DB_PREFIX."product as p GROUP BY fk_product_type, envente";
if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  while ($i < $num)
    {
      $row = $db->fetch_row($i);
      $prodser[$row[1]][$row[2]] = $row[0];
      $i++;
    }
  $db->free();
}

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="3">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Number").'</td></tr>';
if ($conf->produit->enabled)
{
    print "<tr $bc[0]>";
    print '<td><a href="liste.php?type=0&amp;envente=0">'.$langs->trans("ProductsNotOnSell").'</a></td><td>'.round($prodser[0][0]).'</td>';
    print "</tr>";
    print "<tr $bc[1]>";
    print '<td><a href="liste.php?type=0&amp;envente=1">'.$langs->trans("ProductsOnSell").'</a></td><td>'.round($prodser[0][1]).'</td>';
    print "</tr>";
}
if ($conf->service->enabled)
{
    print "<tr $bc[0]>";
    print '<td><a href="liste.php?type=1&amp;envente=0">'.$langs->trans("ServicesNotOnSell").'</a></td><td>'.round($prodser[1][0]).'</td>';
    print "</tr>";
    print "<tr $bc[1]>";
    print '<td><a href="liste.php?type=1&amp;envente=1">'.$langs->trans("ServicesOnSell").'</a></td><td>'.round($prodser[1][1]).'</td>';
    print "</tr>";
}
print '</table>';

print '</td><td valign="top" width="70%">';


/*
 * Derniers produits/services en vente
 */
$sql = "SELECT p.rowid, p.label, p.price, p.ref, p.fk_product_type FROM ".MAIN_DB_PREFIX."product as p WHERE envente=1";
$sql .= " ORDER BY p.datec DESC ";
$sql .= $db->plimit(15 ,0);
$result = $db->query($sql) ;

if ($result)
{
  $num = $db->num_rows();

  $i = 0;

  $typeprodser[0]=$langs->trans("Product");
  $typeprodser[1]=$langs->trans("Service");
    
  if ($num > 0)
    {
      print '<table class="noborder" width="100%" cellspacing="0" cellpadding="3">';

      print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("LastRecorded").'</td></tr>';
    
      $var=True;
      while ($i < $num)
	{
	  $objp = $db->fetch_object( $i);
	  $var=!$var;
	  print "<tr $bc[$var]>";
	  print "<td><a href=\"fiche.php?id=$objp->rowid\">";
	  print img_file();
	  print "</a> <a href=\"fiche.php?id=$objp->rowid\">$objp->ref</a></td>\n";
	  print "<td>$objp->label</td>";
	  print "<td>".$typeprodser[$objp->fk_product_type]."</td>";
	  print "</tr>\n";
	  $i++;
	}
      $db->free();

      print "</table>";
    }
}
else
{
  dolibarr_print_error();
}

print '</td></tr></table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
