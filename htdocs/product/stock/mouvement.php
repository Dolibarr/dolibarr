<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis@dolibarr.fr>
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
   \file       htdocs/product/stock/mouvement.php
   \ingroup    stock
   \brief      Page liste des mouvements de stocks
   \version    $Id$
*/

require("./pre.inc.php");

$langs->load("products");

if (!$user->rights->produit->lire) accessforbidden();

$page = $_GET["page"];
$sortfield = $_GET["sortfield"];
$sortorder = $_GET["sortorder"];
if ($page < 0) $page = 0;
$offset = $conf->liste_limit * $page;

if (! $sortfield) $sortfield="m.datem";
if (! $sortorder) $sortorder="DESC";



$sql = "SELECT p.rowid, p.label as produit,";
$sql.= " s.label as stock, s.rowid as entrepot_id,";
$sql.= " m.value, ".$db->pdate("m.datem")." as datem";
$sql.= " FROM ".MAIN_DB_PREFIX."entrepot as s, ".MAIN_DB_PREFIX."stock_mouvement as m, ".MAIN_DB_PREFIX."product as p";
if ($conf->categorie->enabled && !$user->rights->categorie->voir)
{
  $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_product as cp ON cp.fk_product = p.rowid";
  $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c ON cp.fk_categorie = c.rowid";
}
$sql .= " WHERE m.fk_product = p.rowid AND m.fk_entrepot = s.rowid";
if ($_GET["id"])
  $sql .= " AND s.rowid ='".$_GET["id"]."'";
if ($conf->categorie->enabled && !$user->rights->categorie->voir)
{
  $sql.= " AND IFNULL(c.visible,1)=1";
}
$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $db->plimit($conf->liste_limit + 1 ,$offset);
$resql = $db->query($sql) ;

if ($resql)
{
  $num = $db->num_rows($resql);
  
  if ($_GET["id"])
    {
      $entrepot = new Entrepot($db);
      $result = $entrepot->fetch($_GET["id"]);
      if ($result < 0)
	{
	  dolibarr_print_error($db);
	}
    }
  
  $i = 0;
  
  $texte = $langs->trans("ListOfStockMovements");
  llxHeader("","",$texte);
  
  
  /*
   * Affichage onglets
   */
  if ($_GET["id"])
    {
		$h = 0;
		
		$head[$h][0] = DOL_URL_ROOT.'/product/stock/fiche.php?id='.$entrepot->id;
		$head[$h][1] = $langs->trans("WarehouseCard");
		$h++;
	
		$head[$h][0] = DOL_URL_ROOT.'/product/stock/mouvement.php?id='.$entrepot->id;
		$head[$h][1] = $langs->trans("StockMovements");
		$hselected=$h;
		$h++;
		
		$head[$h][0] = DOL_URL_ROOT.'/product/stock/fiche-valo.php?id='.$entrepot->id;
	  $head[$h][1] = $langs->trans("EnhancedValue");
	  $h++;

	          if ($conf->global->STOCK_USE_WAREHOUSE_BY_USER)
	          {
	          	// Add the constant STOCK_USE_WAREHOUSE_BY_USER in cont table to use this feature.
	          	// Should not be enabled by defaut because does not work yet correctly because
	          	// there is no way to add values in the table llx_user_entrepot
				  $head[$h][0] = DOL_URL_ROOT.'/product/stock/user.php?id='.$entrepot->id;
				  $head[$h][1] = $langs->trans("Users");
				  $h++;
	          }
	          
		$head[$h][0] = DOL_URL_ROOT.'/product/stock/info.php?id='.$entrepot->id;
		$head[$h][1] = $langs->trans("Info");
		$h++;
	
		dolibarr_fiche_head($head, $hselected, $langs->trans("Warehouse").': '.$entrepot->libelle);
	
	    print '<table class="border" width="100%">';
	
		// Ref
	    print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">'.$entrepot->libelle.'</td>';
	
	    print '<tr><td>'.$langs->trans("LocationSummary").'</td><td colspan="3">'.$entrepot->lieu.'</td></tr>';
	
		// Statut
	    print '<tr><td>'.$langs->trans("Status").'</td><td colspan="3">'.$entrepot->getLibStatut(4).'</td></tr>';
	
	    print "</table>";
	
	    print '</div>';
	}


	$param="&id=".$_GET["id"]."&sref=$sref&snom=$snom";
	print_barre_liste($texte, $page, "mouvement.php", $param, $sortfield, $sortorder,'',$num);
	print '<table class="noborder" width="100%">';
	print "<tr class=\"liste_titre\">";
	print_liste_field_titre($langs->trans("Date"),"mouvement.php", "m.datem","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Product"),"mouvement.php", "p.ref","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Warehouse"),"mouvement.php", "s.label","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Units"),"mouvement.php", "m.value","",$param,'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	$var=True;
	while ($i < min($num,$conf->liste_limit))
	{
		$objp = $db->fetch_object($resql);
		$var=!$var;
		print "<tr $bc[$var]>";
		print '<td>'.dolibarr_print_date($objp->datem,'dayhour').'</td>';
		print "<td><a href=\"../fiche.php?id=$objp->rowid\">";
		print img_object($langs->trans("ShowProduct"),"product").' '.$objp->produit;
		print "</a></td>\n";
		print '<td><a href="fiche.php?id='.$objp->entrepot_id.'">';
		print img_object($langs->trans("ShowWarehouse"),"stock").' '.$objp->stock;
		print "</a></td>\n";
		print '<td align="right">';
		if ($objp->value > 0) print '+';
		print $objp->value.'</td>';
		print "</tr>\n";
		$i++;
	}
	$db->free($resql);

	print "</table>";

}
else
{
	dolibarr_print_error($db);
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
