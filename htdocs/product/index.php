<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
 *	\file       htdocs/product/index.php
 *  \ingroup    product
 *  \brief      Page accueil des produits et services
 *  \version    $Id$
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

$transAreaType = $langs->trans("ProductsAndServicesArea");
if (isset($_GET["type"]) && $_GET["type"] == 0) $transAreaType = $langs->trans("ProductsArea");
if (isset($_GET["type"]) && $_GET["type"] == 1) $transAreaType = $langs->trans("ServicesArea");

llxHeader("","",$langs->trans("ProductsAndServices"));

print_fiche_titre($transAreaType);

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

$sql = "SELECT COUNT(p.rowid) as total, p.fk_product_type, p.envente";
$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
$sql.= " GROUP BY p.fk_product_type, p.envente";
$result = $db->query($sql);
while ($objp = $db->fetch_object($result))
{
	$prodser[$objp->fk_product_type][$objp->envente]=$objp->total;
}

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Statistics").'</td></tr>';
if ($conf->produit->enabled)
{
  $statProducts = "<tr $bc[0]>";
  $statProducts.= '<td><a href="liste.php?type=0&amp;envente=0">'.$langs->trans("ProductsNotOnSell").'</a></td><td align="right">'.round($prodser[0][0]).'</td>';
  $statProducts.= "</tr>";
  $statProducts.= "<tr $bc[1]>";
  $statProducts.= '<td><a href="liste.php?type=0&amp;envente=1">'.$langs->trans("ProductsOnSell").'</a></td><td align="right">'.round($prodser[0][1]).'</td>';
  $statProducts.= "</tr>";
}
if ($conf->service->enabled)
{
  $statServices = "<tr $bc[0]>";
  $statServices.= '<td><a href="liste.php?type=1&amp;envente=0">'.$langs->trans("ServicesNotOnSell").'</a></td><td align="right">'.round($prodser[1][0]).'</td>';
  $statServices.= "</tr>";
  $statServices.= "<tr $bc[1]>";
  $statServices.= '<td><a href="liste.php?type=1&amp;envente=1">'.$langs->trans("ServicesOnSell").'</a></td><td align="right">'.round($prodser[1][1]).'</td>';
  $statServices.= "</tr>";
}
if (isset($_GET["type"]) && $_GET["type"] == 0)
{
	print $statProducts;
}
else if (isset($_GET["type"]) && $_GET["type"] == 1)
{
	print $statServices;
}
else
{
	print $statProducts.$statServices;
}
print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td align="right">';
print round($prodser[1][0])+round($prodser[1][1])+round($prodser[0][0])+round($prodser[0][1]);
print '</td></tr>';
print '</table>';

print '</td><td valign="top" width="70%" class="notopnoleftnoright">';

/*
 * Derniers produits/services en vente
 */
$max=15;
$sql = "SELECT p.rowid, p.label, p.price, p.ref, p.fk_product_type, p.envente,";
$sql.= " ".$db->pdate("tms")." as datem";
$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
if ($conf->categorie->enabled && !$user->rights->categorie->voir)
{
  $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_product as cp ON cp.fk_product = p.rowid";
  $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c ON cp.fk_categorie = c.rowid";
}
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_subproduct as sp ON p.rowid = sp.fk_product_subproduct";
$sql.= " WHERE sp.fk_product_subproduct IS NULL";
if ($conf->categorie->enabled && !$user->rights->categorie->voir) $sql.= " AND IFNULL(c.visible,1)=1 ";
if (isset($_GET["type"])) $sql.= " AND p.fk_product_type = ".$_GET["type"];
$sql.= " ORDER BY p.tms DESC ";
$sql.= $db->plimit($max,0);
$result = $db->query($sql) ;

if ($result)
{
  $num = $db->num_rows($result);
  
  $i = 0;
  
  if ($num > 0)
  {
  	$transRecordedType = $langs->trans("LastModifiedProductsAndServices",$max);
  	if (isset($_GET["type"]) && $_GET["type"] == 0) $transRecordedType = $langs->trans("LastRecordedProducts",$max);
  	if (isset($_GET["type"]) && $_GET["type"] == 1) $transRecordedType = $langs->trans("LastRecordedServices",$max);
  	
  	print '<table class="noborder" width="100%">';

    print '<tr class="liste_titre"><td colspan="4">'.$transRecordedType.'</td></tr>';
    
    $var=True;

      while ($i < $num)
	{
	  $objp = $db->fetch_object($result);
	  
	  //Multilangs
	  if ($conf->global->MAIN_MULTILANGS)
	    {
	      $sql = "SELECT label FROM ".MAIN_DB_PREFIX."product_det";
	      $sql.= " WHERE fk_product=".$objp->rowid." AND lang='". $langs->getDefaultLang() ."'";
	      $resultd = $db->query($sql);
	      if ($resultd)
		{
		  $objtp = $db->fetch_object($resultd);
		  if ($objtp->label != '') $objp->label = $objtp->label;
		}
	    }
	  
	  $var=!$var;
	  print "<tr $bc[$var]>";
	  print '<td nowrap="nowrap">';
	  $staticproduct->id=$objp->rowid;
	  $staticproduct->ref=$objp->ref;
	  $staticproduct->type=$objp->fk_product_type;
	  print $staticproduct->getNomUrl(1,'',16);
	  print "</td>\n";
	  print '<td>'.dolibarr_trunc($objp->label,32).'</td>';
	  print "<td>";
	  print dolibarr_print_date($objp->datem,'day');
	  print "</td>";
	  print '<td align="right" nowrap="nowrap">';
	  print $staticproduct->LibStatut($objp->envente,5);
	  print "</td>";
	  print "</tr>\n";
	  $i++;
	}
      
      $db->free();
      
      print "</table>";
    }
}
else
{
  dolibarr_print_error($db);
}

print '</td></tr></table>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
