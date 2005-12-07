<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       htdocs/product/index.php
        \ingroup    product
        \brief      Page accueil des produits et services
        \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/product.class.php');

if (!$user->rights->produit->lire)
  accessforbidden();

$staticproduct=new Product($db);


/*
 * Affichage page accueil
 *
 */

llxHeader("","",$langs->trans("ProductsAndServices"));

print_fiche_titre($langs->trans("ProductsAndServicesArea"));

print '<table border="0" width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="30%" class="notopnoleft">';

/*
 * Zone recherche produit/service
 */
print '<form method="post" action="'.DOL_URL_ROOT.'/product/liste.php">';
print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\">";
print '<td colspan="3">'.$langs->trans("Search").'</td></tr>';
print "<tr $bc[0]><td>";
print $langs->trans("Ref").':</td><td><input class="flat" type="text" size="18" name="sref"></td>';
print '<td rowspan="2"><input type="submit" class="button" value="'.$langs->trans("Search").'"></td></tr>';
print "<tr $bc[0]><td>";
print $langs->trans("Other").':</td><td><input class="flat" type="text" size="18" name="sall"></td>';
//print '<td><input type="submit" class="button" value="'.$langs->trans("Search").'"></td>';
print '</tr>';
print "</table></form><br>";


/*
 * Nombre de produits et/ou services
 */
$prodser = array();
$prodser[0][0]=$prodser[0][1]=$prodser[1][0]=$prodser[1][1]=0;

$sql = "SELECT count(*), fk_product_type, envente FROM ".MAIN_DB_PREFIX."product as p GROUP BY fk_product_type, envente";
$result=$db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;
    while ($i < $num)
    {
        $row = $db->fetch_row($result);
        $prodser[$row[1]][$row[2]] = $row[0];
        $i++;
    }
    $db->free();
}

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Statistics").'</td></tr>';
if ($conf->produit->enabled)
{
    print "<tr $bc[0]>";
    print '<td><a href="liste.php?type=0&amp;envente=0">'.$langs->trans("ProductsNotOnSell").'</a></td><td align="right">'.round($prodser[0][0]).'</td>';
    print "</tr>";
    print "<tr $bc[1]>";
    print '<td><a href="liste.php?type=0&amp;envente=1">'.$langs->trans("ProductsOnSell").'</a></td><td align="right">'.round($prodser[0][1]).'</td>';
    print "</tr>";
}
if ($conf->service->enabled)
{
    print "<tr $bc[0]>";
    print '<td><a href="liste.php?type=1&amp;envente=0">'.$langs->trans("ServicesNotOnSell").'</a></td><td align="right">'.round($prodser[1][0]).'</td>';
    print "</tr>";
    print "<tr $bc[1]>";
    print '<td><a href="liste.php?type=1&amp;envente=1">'.$langs->trans("ServicesOnSell").'</a></td><td align="right">'.round($prodser[1][1]).'</td>';
    print "</tr>";
}
print '</table>';

print '</td><td valign="top" width="70%" class="notopnoleftnoright">';


/*
 * Derniers produits/services en vente
 */
$max=15;
$sql = "SELECT p.rowid, p.label, p.price, p.ref, p.fk_product_type, p.envente";
$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
$sql.= " ORDER BY p.datec DESC ";
$sql.= $db->plimit($max,0);
$result = $db->query($sql) ;

if ($result)
{
  $num = $db->num_rows($result);

  $i = 0;

  if ($num > 0)
    {
      print '<table class="noborder" width="100%">';

      print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("LastRecordedProducts",$max).'</td></tr>';
    
      $var=True;
      while ($i < $num)
	{
	  $objp = $db->fetch_object($result);
	  $var=!$var;
	  print "<tr $bc[$var]>";
	  print '<td nowrap="nowrap"><a href="fiche.php?id='.$objp->rowid.'">';
	  if ($objp->fk_product_type) print img_object($langs->trans("ShowService"),"service");
	  else print img_object($langs->trans("ShowProduct"),"product");
	  print "</a> <a href=\"fiche.php?id=$objp->rowid\">$objp->ref</a></td>\n";
	  print "<td>$objp->label</td>";
	  print "<td>".$staticproduct->typeprodser[$objp->fk_product_type]."</td>";
	  print '<td align="center" nowrap="nowrap">'.($objp->envente?$langs->trans("OnSell"):$langs->trans("NotOnSell"))."</td>";
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

llxFooter('$Date$ - $Revision$');
?>
