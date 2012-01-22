<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/product/stock/mouvement.php
 *	\ingroup    stock
 *	\brief      Page to list stock movements
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/stock.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");

$langs->load("products");
$langs->load("stocks");

if (!$user->rights->produit->lire) accessforbidden();

$idproduct = isset($_GET["idproduct"])?$_GET["idproduct"]:$_PRODUCT["idproduct"];
$year = isset($_GET["year"])?$_GET["year"]:$_POST["year"];
$month = isset($_GET["month"])?$_GET["month"]:$_POST["month"];
$search_movement = isset($_REQUEST["search_movement"])?$_REQUEST["search_movement"]:'';
$search_product = isset($_REQUEST["search_product"])?$_REQUEST["search_product"]:'';
$search_warehouse = isset($_REQUEST["search_warehouse"])?$_REQUEST["search_warehouse"]:'';
$search_user = isset($_REQUEST["search_user"])?$_REQUEST["search_user"]:'';
$page = GETPOST("page");
$sortfield = GETPOST("sortfield");
$sortorder = GETPOST("sortorder");
if ($page < 0) $page = 0;
$offset = $conf->liste_limit * $page;

if (! $sortfield) $sortfield="m.datem";
if (! $sortorder) $sortorder="DESC";

if (GETPOST("button_removefilter"))
{
    $year='';
    $month='';
    $search_movement="";
    $search_product="";
    $search_warehouse="";
    $search_user="";
    $sall="";
}


/*
 * View
 */

$productstatic=new Product($db);
$warehousestatic=new Entrepot($db);
$userstatic=new User($db);
$form=new Form($db);
$formother=new FormOther($db);

$sql = "SELECT p.rowid, p.label as produit, p.fk_product_type as type,";
$sql.= " s.label as stock, s.rowid as entrepot_id,";
$sql.= " m.rowid as mid, m.value, m.datem, m.fk_user_author, m.label,";
$sql.= " u.login";
$sql.= " FROM (".MAIN_DB_PREFIX."entrepot as s,";
$sql.= " ".MAIN_DB_PREFIX."stock_mouvement as m,";
$sql.= " ".MAIN_DB_PREFIX."product as p)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON m.fk_user_author = u.rowid";
$sql.= " WHERE m.fk_product = p.rowid";
$sql.= " AND m.fk_entrepot = s.rowid";
$sql.= " AND s.entity = ".$conf->entity;
if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) $sql.= " AND p.fk_product_type = 0";
if ($_GET["id"])
{
	$sql.= " AND s.rowid ='".$_GET["id"]."'";
}
if ($month > 0)
{
	if ($year > 0)
	$sql.= " AND m.datem BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
	else
	$sql.= " AND date_format(m.datem, '%m') = '$month'";
}
else if ($year > 0)
{
	$sql.= " AND m.datem BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
}
if (! empty($search_movement))
{
	$sql.= " AND m.label LIKE '%".$db->escape($search_movement)."%'";
}
if (! empty($search_product))
{
	$sql.= " AND p.label LIKE '%".$db->escape($search_product)."%'";
}
if (! empty($search_warehouse))
{
	$sql.= " AND s.label LIKE '%".$db->escape($search_warehouse)."%'";
}
if (! empty($search_user))
{
	$sql.= " AND u.login LIKE '%".$db->escape($search_user)."%'";
}
if (! empty($_GET['idproduct']))
{
	$sql.= " AND p.rowid = '".$_GET['idproduct']."'";
}
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($conf->liste_limit+1, $offset);

//print $sql;
$resql = $db->query($sql);
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

	$help_url='EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
	$texte = $langs->trans("ListOfStockMovements");
	llxHeader("",$texte,$help_url);


	/*
	 * Show tab only if we ask a particular warehouse
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
		print '<tr><td valign="top">'.$langs->trans("Description").'</td><td colspan="3">'.dol_htmlentitiesbr($entrepot->description).'</td></tr>';

		// Address
		print '<tr><td>'.$langs->trans('Address').'</td><td colspan="3">';
		print $entrepot->address;
		print '</td></tr>';

		// Ville
		print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%">'.$entrepot->cp.'</td>';
		print '<td width="25%">'.$langs->trans('Town').'</td><td width="25%">'.$entrepot->ville.'</td></tr>';

		// Country
		print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
		$img=picto_from_langcode($entrepot->country_code);
		print ($img?$img.' ':'');
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
		$sql = "SELECT max(m.datem) as datem";
		$sql .= " FROM ".MAIN_DB_PREFIX."stock_mouvement as m";
		$sql .= " WHERE m.fk_entrepot = '".$entrepot->id."'";
		$resqlbis = $db->query($sql);
		if ($resqlbis)
		{
			$obj = $db->fetch_object($resqlbis);
			$lastmovementdate=$db->jdate($obj->datem);
		}
		else
		{
			dol_print_error($db);
		}

		print '<tr><td valign="top">'.$langs->trans("LastMovement").'</td><td colspan="3">';
		if ($lastmovementdate)
		{
		    print dol_print_date($lastmovementdate,'dayhour');
		}
		else
		{
		    print $langs->trans("None");
		}
		print "</td></tr>";

		print "</table>";

		print '</div>';
	}

	$param='';
	if ($_GET["id"]) $param.='&id='.$_GET["id"];
	if ($search_movement)   $param.='&search_movement='.urlencode($search_movement);
	if ($search_product)   $param.='&search_product='.urlencode($search_product);
	if ($search_warehouse) $param.='&search_warehouse='.urlencode($search_warehouse);
	if ($sref) $param.='&sref='.urlencode($sref);
	if ($snom) $param.='&snom='.urlencode($snom);
	if ($search_user)    $param.='&search_user='.urlencode($search_user);
	if ($idproduct > 0)  $param.='&idproduct='.$idproduct;
	if ($_GET["id"]) print_barre_liste($texte, $page, "mouvement.php", $param, $sortfield, $sortorder,'',$num,0,'');
	else print_barre_liste($texte, $page, "mouvement.php", $param, $sortfield, $sortorder,'',$num);

	print '<table class="noborder" width="100%">';
	print "<tr class=\"liste_titre\">";
	//print_liste_field_titre($langs->trans("Id"),$_SERVER["PHP_SELF"], "m.rowid","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"], "m.datem","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Label"),$_SERVER["PHP_SELF"], "m.label","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Product"),$_SERVER["PHP_SELF"], "p.ref","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Warehouse"),$_SERVER["PHP_SELF"], "s.label","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Author"),$_SERVER["PHP_SELF"], "m.fk_user_author","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Units"),$_SERVER["PHP_SELF"], "m.value","",$param,'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	// Lignes des champs de filtre
	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">';

	print '<tr class="liste_titre">';
	print '<td class="liste_titre" valign="right">';
	print $langs->trans('Month').': <input class="flat" type="text" size="2" maxlength="2" name="month" value="'.$month.'">';
	print '&nbsp;'.$langs->trans('Year').': ';
	$max_year = date("Y");
	$syear = $year;
	$formother->select_year($syear,'year',1, 20, 5);
	print '</td>';
	// Label of movement
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" size="12" name="search_movement" value="'.$search_movement.'">';
	print '</td>';
	// Product
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" size="12" name="search_product" value="'.($idproduct?$product->libelle:$search_product).'">';
	print '</td>';
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" size="10" name="search_warehouse" value="'.($search_warehouse).'">';
	print '</td>';
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" size="6" name="search_user" value="'.($search_user).'">';
	print '</td>';
	print '<td class="liste_titre" align="right">';
	print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
    print '&nbsp; ';
    print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" name="button_removefilter" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
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
		// Label of movement
		print '<td>'.$objp->label.'</td>';
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
		// Author
		print '<td>';
		$userstatic->id=$objp->fk_user_author;
		$userstatic->nom=$objp->login;
		print $userstatic->getNomUrl(1);
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

llxFooter();
?>
