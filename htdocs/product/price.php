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

llxHeader("","","Prix");

$product = new Product($db);
$result = $product->fetch($_GET["id"]);

$head[0][0] = DOL_URL_ROOT."/product/fiche.php?id=".$product->id;
$head[0][1] = 'Fiche';

$head[1][0] = DOL_URL_ROOT."/product/price.php?id=".$product->id;
$head[1][1] = 'Prix';

dolibarr_fiche_head($head, 1, 'Fiche '.$types[$product->type].' : '.$product->ref);
	      	      


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
    }
}
else
{
  print $db->error() . "<br>" .$sql;
}



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
