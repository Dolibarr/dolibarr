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

/*!
	    \file       htdocs/product/price.php
        \ingroup    product
		\brief      Page de la fiche produit
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("products");

$user->getrights('produit');

if (!$user->rights->produit->lire)
  accessforbidden();


$types[0] = $langs->trans("Product");
$types[1] = $langs->trans("Service");


if ($action == 'update')
{
  $sql = "UPDATE ".MAIN_DB_PREFIX."product SET description='$desc' where rowid = $rowid";
  $db->query($sql);
}

/*
 *
 *
 */

llxHeader("","","Prix");

$product = new Product($db);
$result = $product->fetch($_GET["id"]);


// Zone recherche
print '<div class="formsearch">';
print '<form action="liste.php" method="post">';
print '<input type="hidden" name="type" value="'.$product->type.'">';
print $langs->trans("Ref").': <input class="flat" type="text" size="10" name="sref">&nbsp;<input class="flat" type="submit" value="go"> &nbsp;';
print $langs->trans("Label").': <input class="flat" type="text" size="20" name="snom">&nbsp;<input class="flat" type="submit" value="go">';
print '</form></div>';


$h=0;

$head[$h][0] = DOL_URL_ROOT."/product/fiche.php?id=".$product->id;
$head[$h][1] = $langs->trans("Card");
$h++;

$head[$h][0] = DOL_URL_ROOT."/product/price.php?id=".$product->id;
$head[$h][1] = $langs->trans("Price");
$hselected=$h;
$h++;

if($product->type == 0)
{
    $head[$h][0] = DOL_URL_ROOT."/product/stock/product.php?id=".$product->id;
    $head[$h][1] = 'Stock';
    $h++;
}

$head[$h][0] = DOL_URL_ROOT."/product/stats/fiche.php?id=".$product->id;
$head[$h][1] = $langs->trans("Statistics");
$h++;


dolibarr_fiche_head($head, $hselected, $langs->trans("CardProduct".$product->type).' : '.$product->ref);
	      	      

$sql = "SELECT p.rowid, p.price, ".$db->pdate("p.date_price")." as dp";
$sql .= " FROM ".MAIN_DB_PREFIX."product_price as p";
$sql .= " WHERE fk_product = ".$product->id;
$sql .= " ORDER BY p.date_price DESC ";
$sql .= $db->plimit(15 ,0);
$result = $db->query($sql) ;

if ($result)
{
  $num = $db->num_rows();

  $i = 0;
    
  if ($num > 0)
    {
      print '<table class="noborder" width="100%" cellspacing="0" cellpadding="3">';

      print '<tr class="liste_titre"><td colspan="3">Prix de vente pratiqués</td></tr>';
    
      $var=True;
      while ($i < $num)
	{
	  $objp = $db->fetch_object( $i);
	  $var=!$var;
	  print "<tr $bc[$var]>";
	  print "<td>".strftime("%d %B %Y %H:%M:%S",$objp->dp)."</td>";
	  print "<td>".price($objp->price)."</td>";

	  print "</tr>\n";
	  $i++;
	}
      $db->free();
      print "</table>";
      print "<br>";
    }
}
else
{
  print $db->error() . "<br>" .$sql;
}

print "</div>\n";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
