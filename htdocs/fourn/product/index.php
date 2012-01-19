<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   \file       htdocs/product/index.php
 *   \ingroup    product
 *   \brief      Page accueil des produits et services
 */

require("../../main.inc.php");

// Security check
if (!$user->rights->produit->lire && !$user->rights->service->lire) accessforbidden();


/*
 * View
 */

llxHeader("","",$langs->trans("ProductsAndServices"));

print_fiche_titre($langs->trans("ProductsAndServicesArea"));

print '<table border="0" width="100%">';

print '<tr><td valign="top" width="30%">';

/*
 * Zone recherche produit/service
 */
print '<form method="post" action="liste.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\">\n";
print '<td colspan="3">'.$langs->trans("Search").'</td></tr>';
print "<tr $bc[0]><td>";
print $langs->trans("Ref").' :</td><td><input class="flat" type="text" size="20" name="sf_ref"></td><td><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
print "<tr $bc[0]><td>";
print $langs->trans("Label").' :</td><td><input class="flat" type="text" size="20" name="snom"></td><td><input class="button" type="submit" value="'.$langs->trans("Search").'"></td></tr>';
print "</table></form><br>\n";

/*
 * Nombre de produits et/ou services
 */
$prodser = array();
$sql = "SELECT count(*), fk_product_type  FROM ".MAIN_DB_PREFIX."product as p GROUP BY fk_product_type";
$resql=$db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  while ($i < $num)
    {
      $row = $db->fetch_row($resql);
      $prodser[$row[1]] = $row[0];
      $i++;
    }
  $db->free($resql);
}

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Statistics").'</td></tr>';
if ($conf->product->enabled)
{
    print "<tr $bc[0]>";
    print '<td><a href="liste.php?type=0">'.$langs->trans("Products").'</a></td><td>'.round($prodser[0]).'</td>';
    print "</tr>";
}
if ($conf->service->enabled)
{
    print "<tr $bc[1]>";
    print '<td><a href="liste.php?type=1">'.$langs->trans("Services").'</a></td><td>'.round($prodser[1]).'</td>';
    print "</tr>";
}
print '</table>';

print '</td><td valign="top" width="70%">';


/*
 * Derniers produits en vente
 */
$sql = "SELECT p.rowid, p.label, p.price, p.ref, p.type";
$sql .= " FROM ".MAIN_DB_PREFIX."product as p ";
$sql .= " WHERE p.fk_product_type <> 1";
$sql .= " ORDER BY p.datec DESC ";
$sql .= $db->plimit(15, 0);

$resql = $db->query($sql);

if ($resql)
{
  $num = $db->num_rows($resql);

  $i = 0;

  if ($num > 0)
    {
      print '<table class="noborder" width="100%">';

      print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("LastProducts").'</td></tr>';

      $var=True;
      while ($i < $num)
	{
	  $objp = $db->fetch_object($resql);
	  $var=!$var;
	  print "<tr $bc[$var]>";
	  print "<td><a href=\"fiche.php?id=$objp->rowid\">";
	  if ($objp->fk_product_type==1) print img_object($langs->trans("ShowService"),"service");
	  else print img_object($langs->trans("ShowProduct"),"product");
	  print "</a> <a href=\"fiche.php?id=$objp->rowid\">$objp->ref</a></td>\n";
	  print "<td>$objp->label</td>";
	  print "<td>";
	  if ($objp->fk_product_type==1) print $langs->trans('ShowService');
	  else print $langs->trans('ShowProduct');
	  print "</td></tr>\n";
	  $i++;
	}
      $db->free($resql);

      print "</table>";
    }
}
else
{
  dol_print_error();
}

print '</td></tr></table>';

$db->close();

llxFooter();
?>
