<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/product/stock/mouvement.php
 *	\ingroup    stock
 *	\brief      Page liste des mouvements de stocks
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/stock.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/product/stock/entrepot.class.php");

$langs->load("products");
$langs->load("stocks");

if (!$user->rights->produit->lire) accessforbidden();

$page = $_GET["page"];
$sortfield = $_GET["sortfield"];
$sortorder = $_GET["sortorder"];
$idproduct = isset($_GET["idproduct"])?$_GET["idproduct"]:$_PRODUCT["idproduct"];
$year = isset($_GET["year"])?$_GET["year"]:$_POST["year"];
$month = isset($_GET["month"])?$_GET["month"]:$_POST["month"];
if ($page < 0) $page = 0;
$offset = $conf->liste_limit * $page;

if (! $sortfield) $sortfield="m.datem";
if (! $sortorder) $sortorder="DESC";


/*
 * View
 */

$productstatic=new Product($db);
$warehousestatic=new Entrepot($db);
$form=new Form($db);

$sql = "SELECT p.rowid, p.label as produit, p.fk_product_type as type,";
$sql.= " s.label as stock, s.rowid as entrepot_id,";
$sql.= " m.rowid as mid, m.value, m.datem";
$sql.= " FROM ".MAIN_DB_PREFIX."entrepot as s";
$sql.= ", ".MAIN_DB_PREFIX."stock_mouvement as m";
$sql.= ", ".MAIN_DB_PREFIX."product as p";
if ($conf->categorie->enabled && !$user->rights->categorie->voir)
{
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_product as cp ON cp.fk_product = p.rowid";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c ON cp.fk_categorie = c.rowid";
}
$sql.= " WHERE m.fk_product = p.rowid";
$sql.= " AND m.fk_entrepot = s.rowid";
$sql.= " AND s.entity = ".$conf->entity;
if ($_GET["id"])
$sql.= " AND s.rowid ='".$_GET["id"]."'";
if ($conf->categorie->enabled && !$user->rights->categorie->voir)
{
	$sql.= " AND IFNULL(c.visible,1)=1";
}
if ($month > 0)
{
	if ($year > 0)
	$sql.= " AND date_format(m.datem, '%Y-%m') = '$year-$month'";
	else
	$sql.= " AND date_format(m.datem, '%m') = '$month'";
}
if ($year > 0)         $sql .= " AND date_format(m.datem, '%Y') = $year";
if (! empty($_GET['search_product']))
{
	$sql.= " AND p.label LIKE '%".addslashes($_GET['search_product'])."%'";
}
if (! empty($_GET['idproduct']))
{
	$sql.= " AND p.rowid = '".$_GET['idproduct']."'";
}
$sql.= " ORDER BY $sortfield $sortorder ";
$sql.= $db->plimit($conf->liste_limit + 1 ,$offset);

$resql = $db->query($sql) ;
if ($resql)
{
	$num = $db->num_rows($resql);

	if ($idproduct)
	{
		$product = new Product($db);
		$product->fetch($idproduct);
	}

	if ($_GET["id"])
	{
		$entrepot = new Entrepot($db);
		$result = $entrepot->fetch($_GET["id"]);
		if ($result < 0)
		{
	  		dol_print_error($db);
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
		$head = stock_prepare_head($entrepot);

		dol_fiche_head($head, 'movements', $langs->trans("Warehouse"), 0, 'stock');


		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">';
		print $form->showrefnav($entrepot,'id','',1,'rowid','libelle');
		print '</td>';

		print '<tr><td>'.$langs->trans("LocationSummary").'</td><td colspan="3">'.$entrepot->lieu.'</td></tr>';

		// Description
		print '<tr><td valign="top">'.$langs->trans("Description").'</td><td colspan="3">'.nl2br($entrepot->description).'</td></tr>';

		// Address
		print '<tr><td>'.$langs->trans('Address').'</td><td colspan="3">';
		print $entrepot->address;
		print '</td></tr>';

		// Ville
		print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%">'.$entrepot->cp.'</td>';
		print '<td width="25%">'.$langs->trans('Town').'</td><td width="25%">'.$entrepot->ville.'</td></tr>';

		// Country
		print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
		print $entrepot->pays;
		print '</td></tr>';

		// Statut
		print '<tr><td>'.$langs->trans("Status").'</td><td colspan="3">'.$entrepot->getLibStatut(4).'</td></tr>';

		$calcproducts=$entrepot->nb_products();

		// Nb of products
		print '<tr><td valign="top">'.$langs->trans("NumberOfProducts").'</td><td colspan="3">';
		print empty($calcproducts['nb'])?'0':$calcproducts['nb'];
		print "</td></tr>";

		// Value
		print '<tr><td valign="top">'.$langs->trans("EstimatedStockValueShort").'</td><td colspan="3">';
		print empty($calcproducts['value'])?'0':$calcproducts['value'];
		print "</td></tr>";

		// Last movement
		$sql = "SELECT max( ".$db->pdate("m.datem").") as datem";
		$sql .= " FROM llx_stock_mouvement as m";
		$sql .= " WHERE m.fk_entrepot = '".$entrepot->id."';";
		$resqlbis = $db->query($sql);
		if ($resqlbis)
		{
			$row = $db->fetch_row($resqlbis);
		}
		else
		{
			dol_print_error($db);
		}

		print '<tr><td valign="top">'.$langs->trans("LastMovement").'</td><td colspan="3">';
		print '<a href="mouvement.php?id='.$entrepot->id.'">'.dol_print_date($row[0]).'</a>';
		print "</td></tr>";

		print "</table>";

		print '</div>';
	}


	$param="&id=".$_GET["id"]."&sref=$sref&snom=$snom";
	if ($_GET["id"]) print_barre_liste($texte, $page, "mouvement.php", $param, $sortfield, $sortorder,'',$num,0,'');
	else print_barre_liste($texte, $page, "mouvement.php", $param, $sortfield, $sortorder,'',$num);

	print '<table class="noborder" width="100%">';
	print "<tr class=\"liste_titre\">";
	//print_liste_field_titre($langs->trans("Id"),"mouvement.php", "m.rowid","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Date"),"mouvement.php", "m.datem","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Product"),"mouvement.php", "p.ref","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Warehouse"),"mouvement.php", "s.label","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Units"),"mouvement.php", "m.value","",$param,'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	// Lignes des champs de filtre
	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">';

	print '<tr class="liste_titre">';
	print '<td valign="right">';
	print $langs->trans('Month').': <input class="flat" type="text" size="2" maxlength="2" name="month" value="'.$month.'">';
	print '&nbsp;'.$langs->trans('Year').': ';
	$max_year = date("Y");
	$syear = $year;
	$form->select_year($syear,'year',1, '', $max_year);
	print '</td>';
	print '<td align="left">';
	print '<input class="flat" type="text" size="20" name="search_product" value="'.($idproduct?$product->libelle:$_GET['search_product']).'">';
	print '</td>';
	print '<td align="right">';
	print '&nbsp;';
	print '</td>';
	print '<td align="right"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
	print '</td>';
	print "</tr>\n";
	print '</form>';

	$var=True;
	while ($i < min($num,$conf->liste_limit))
	{
		$objp = $db->fetch_object($resql);
		$var=!$var;
		print "<tr $bc[$var]>";
		// Id movement
		//print '<td>'.$objp->mid.'</td>';	// This is primary not movement id
		// Date
		print '<td>'.dol_print_date($db->jdate($objp->datem),'dayhour').'</td>';
		// Product
		print '<td>';
		$productstatic->id=$objp->rowid;
		$productstatic->ref=$objp->produit;
		$productstatic->type=$objp->type;
		print $productstatic->getNomUrl(1,'',16);
		print "</td>\n";
		// Warehouse
		print '<td>';
		$warehousestatic->id=$objp->entrepot_id;
		$warehousestatic->libelle=$objp->stock;
		print $warehousestatic->getNomUrl(1);
		print "</td>\n";
		// Value
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
	dol_print_error($db);
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
