<?php
/* Copyright (C) 2001-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2018  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2016       Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2019       Juanjo Menent			<jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *  \file       htdocs/product/reassortlot.php
 *  \ingroup    produit
 *  \brief      Page to list stocks
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'productbatch'));

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'produit|service');


$action=GETPOST('action','alpha');
$sref=GETPOST("sref", 'alpha');
$snom=GETPOST("snom", 'alpha');
$sall=trim((GETPOST('search_all', 'alphanohtml')!='')?GETPOST('search_all', 'alphanohtml'):GETPOST('sall', 'alphanohtml'));
$type=GETPOST("type","int");
$search_barcode=GETPOST("search_barcode",'alpha');
$search_warehouse=GETPOST('search_warehouse','alpha');
$search_batch=GETPOST('search_batch','alpha');
$catid=GETPOST('catid','int');
$toolowstock=GETPOST('toolowstock');
$tosell = GETPOST("tosell");
$tobuy = GETPOST("tobuy");
$fourn_id = GETPOST("fourn_id",'int');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page < 0) $page = 0;
if (! $sortfield) $sortfield="p.ref";
if (! $sortorder) $sortorder="ASC";
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page ;

// Load sale and categ filters
$search_sale = GETPOST("search_sale");
$search_categ = GETPOST("search_categ");

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$canvas=GETPOST("canvas");
$objcanvas=null;
if (! empty($canvas))
{
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db,$action);
	$objcanvas->getCanvas('product','list',$canvas);
}



/*
 * Actions
 */

if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
{
    $sref="";
    $snom="";
    $sall="";
	$tosell="";
	$tobuy="";
    $search_sale="";
    $search_categ="";
    $type="";
    $catid='';
    $toolowstock='';
    $search_batch='';
    $search_warehouse='';
	$fourn_id='';
	$sbarcode='';
}


/*
 * View
 */

$helpurl='EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';

$form=new Form($db);
$htmlother=new FormOther($db);

$title=$langs->trans("ProductsAndServices");

$sql = 'SELECT p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type, p.entity,';
$sql.= ' p.fk_product_type, p.tms as datem,';
$sql.= ' p.duration, p.tosell as statut, p.tobuy, p.seuil_stock_alerte, p.desiredstock, p.stock, p.tobatch,';
$sql.= ' ps.fk_entrepot,';
$sql.= ' e.ref as warehouse_ref, e.lieu as warehouse_lieu, e.fk_parent as warehouse_parent,';
$sql.= ' pb.batch, pb.eatby as oldeatby, pb.sellby as oldsellby,';
$sql.= ' pl.rowid as lotid, pl.eatby, pl.sellby,';
$sql.= ' SUM(pb.qty) as stock_physique, COUNT(pb.rowid) as nbinbatchtable';
$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_stock as ps on p.rowid = ps.fk_product';                       // Detail for each warehouse
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'entrepot as e on ps.fk_entrepot = e.rowid';                            // Link on unique key
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_batch as pb on pb.fk_product_stock = ps.rowid';                // Detail for each lot on each warehouse
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_lot as pl on pl.fk_product = p.rowid AND pl.batch = pb.batch'; // Link on unique key
// We'll need this table joined to the select in order to filter by categ
if ($search_categ) $sql.= ", ".MAIN_DB_PREFIX."categorie_product as cp";
$sql.= " WHERE p.entity IN (".getEntity('product').")";
if ($search_categ) $sql.= " AND p.rowid = cp.fk_product";	// Join for the needed table to filter by categ
if ($sall) $sql.=natural_search(array('p.ref','p.label','p.description','p.note'), $sall);
// if the type is not 1, we show all products (type = 0,2,3)
if (dol_strlen($type))
{
    if ($type==1)
    {
        $sql.= " AND p.fk_product_type = '1'";
    }
    else
    {
        $sql.= " AND p.fk_product_type <> '1'";
    }
}
if ($sref)     $sql.= natural_search("p.ref", $sref);
if ($search_barcode) $sql.= natural_search("p.barcode", $search_barcode);
if ($snom)     $sql.= natural_search("p.label", $snom);
if (! empty($tosell)) $sql.= " AND p.tosell = ".$tosell;
if (! empty($tobuy))  $sql.= " AND p.tobuy = ".$tobuy;
if (! empty($canvas)) $sql.= " AND p.canvas = '".$db->escape($canvas)."'";
if($catid) $sql.= " AND cp.fk_categorie = ".$catid;
if ($fourn_id > 0) $sql.= " AND p.rowid = pf.fk_product AND pf.fk_soc = ".$fourn_id;
// Insert categ filter
if ($search_categ) $sql .= " AND cp.fk_categorie = ".$db->escape($search_categ);
if ($search_warehouse) $sql .= natural_search("e.ref", $search_warehouse);
if ($search_batch) $sql .= natural_search("pb.batch", $search_batch);
$sql.= " GROUP BY p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type, p.entity,";
$sql.= " p.fk_product_type, p.tms,";
$sql.= " p.duration, p.tosell, p.tobuy, p.seuil_stock_alerte, p.desiredstock, p.stock, p.tobatch,";
$sql.= " ps.fk_entrepot,";
$sql.= " e.ref, e.lieu, e.fk_parent,";
$sql.= " pb.batch, pb.eatby, pb.sellby,";
$sql.= " pl.rowid, pl.eatby, pl.sellby";
if ($toolowstock) $sql.= " HAVING SUM(".$db->ifsql('ps.reel IS NULL', '0', 'ps.reel').") < p.seuil_stock_alerte";    // Not used yet
$sql.= $db->order($sortfield,$sortorder);

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
    if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
    {
    	$page = 0;
    	$offset = 0;
    }
}

$sql.= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$i = 0;

	if ($num == 1 && GETPOST('autojumpifoneonly') && ($sall or $snom or $sref))
	{
		$objp = $db->fetch_object($resql);
		header("Location: card.php?id=$objp->rowid");
		exit;
	}

	if (isset($type))
	{
		if ($type==1) { $texte = $langs->trans("Services"); }
		else { $texte = $langs->trans("Products"); }
	} else {
		$texte = $langs->trans("ProductsAndServices");
	}
	$texte.=' ('.$langs->trans("StocksByLotSerial").')';

	$param='';
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
	if ($sall)		$param.="&sall=".$sall;
	if ($tosell)		$param.="&tosell=".$tosell;
	if ($tobuy)			$param.="&tobuy=".$tobuy;
	if ($type)			$param.="&type=".$type;
	if ($fourn_id)		$param.="&fourn_id=".$fourn_id;
	if ($snom)			$param.="&snom=".$snom;
	if ($sref)			$param.="&sref=".$sref;
	if ($search_batch)	$param.="&search_batch=".$search_batch;
	if ($sbarcode)		$param.="&sbarcode=".$sbarcode;
	if ($search_warehouse)	$param.="&search_warehouse=".$search_warehouse;
	if ($catid)			$param.="&catid=".$catid;
	if ($toolowstock)	$param.="&toolowstock=".$toolowstock;
	if ($search_sale)	$param.="&search_sale=".$search_sale;
	if ($search_categ)	$param.="&search_categ=".$search_categ;
	/*if ($eatby)		$param.="&eatby=".$eatby;
	if ($sellby)	$param.="&sellby=".$sellby;*/

	llxHeader("",$title,$helpurl,$texte);

	print '<form action="'. $_SERVER["PHP_SELF"] .'" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="type" value="'.$type.'">';

	print_barre_liste($texte, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder,'',$num, $nbtotalofrecords, 'title_products', 0, '', '', $limit);


	if (! empty($catid))
	{
		print "<div id='ways'>";
		$c = new Categorie($db);
		$c->fetch($catid);
		$ways = $c->print_all_ways(' &gt; ','product/reassortlot.php');
		print " &gt; ".$ways[0]."<br>\n";
		print "</div><br>";
	}

	// Filter on categories
 	$moreforfilter='';
	if (! empty($conf->categorie->enabled))
	{
	 	$moreforfilter.='<div class="divsearchfield">';
	 	$moreforfilter.=$langs->trans('Categories'). ': ';
		$moreforfilter.=$htmlother->select_categories(Categorie::TYPE_PRODUCT,$search_categ,'search_categ');
	 	$moreforfilter.='</div>';
	}
	//$moreforfilter.=$langs->trans("StockTooLow").' <input type="checkbox" name="toolowstock" value="1"'.($toolowstock?' checked':'').'>';

    if (! empty($moreforfilter))
    {
        print '<div class="liste_titre liste_titre_bydiv centpercent">';
        print $moreforfilter;
        $parameters=array();
        $reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        print '</div>';
    }


    print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">';

	// Lignes des champs de filtre
	print '<tr class="liste_titre_filter">';
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="sref" size="6" value="'.$sref.'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="snom" size="8" value="'.$snom.'">';
	print '</td>';
	if (! empty($conf->service->enabled) && $type == 1)
	{
		print '<td class="liste_titre">';
		print '&nbsp;';
		print '</td>';
	}
	print '<td class="liste_titre"><input class="flat" type="text" name="search_warehouse" size="6" value="'.$search_warehouse.'"></td>';
	print '<td class="liste_titre" align="center"><input class="flat" type="text" name="search_batch" size="6" value="'.$search_batch.'"></td>';
	print '<td class="liste_titre" align="right">&nbsp;</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre" align="right">';
    $searchpicto=$form->showFilterAndCheckAddButtons(0);
    print $searchpicto;
    print '</td>';
	print '</tr>';

	// Lignes des titres
	print "<tr class=\"liste_titre\">";
	print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "p.ref",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre("Label", $_SERVER["PHP_SELF"], "p.label",$param,"","",$sortfield,$sortorder);
	if (! empty($conf->service->enabled) && $type == 1) print_liste_field_titre("Duration", $_SERVER["PHP_SELF"], "p.duration",$param,"",'align="center"',$sortfield,$sortorder);
	print_liste_field_titre("Warehouse", $_SERVER["PHP_SELF"], "e.ref",$param,"",'',$sortfield,$sortorder);
	//print_liste_field_titre("DesiredStock", $_SERVER["PHP_SELF"], "p.desiredstock",$param,"",'align="right"',$sortfield,$sortorder);
	print_liste_field_titre("Batch", $_SERVER["PHP_SELF"], "pb.batch",$param,"",'align="center"',$sortfield,$sortorder);
	print_liste_field_titre("EatByDate", $_SERVER["PHP_SELF"], "pb.eatby",$param,"",'align="center"',$sortfield,$sortorder);
	print_liste_field_titre("SellByDate", $_SERVER["PHP_SELF"], "pb.sellby",$param,"",'align="center"',$sortfield,$sortorder);
	print_liste_field_titre("PhysicalStock", $_SERVER["PHP_SELF"], "stock_physique",$param,"",'align="right"',$sortfield,$sortorder);
	// TODO Add info of running suppliers/customers orders
	//print_liste_field_titre("TheoreticalStock",$_SERVER["PHP_SELF"], "stock_theorique",$param,"",'align="right"',$sortfield,$sortorder);
	print_liste_field_titre('');
	print_liste_field_titre( $langs->trans("Status").' ('.$langs->trans("Sell").')',$_SERVER["PHP_SELF"], "p.tosell","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre( $langs->trans("Status").' ('.$langs->trans("Buy").')',$_SERVER["PHP_SELF"], "p.tobuy","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre('');
	print "</tr>\n";

	$product_static=new Product($db);
	$product_lot_static=new Productlot($db);
	$warehousetmp=new Entrepot($db);

	while ($i < min($num,$limit))
	{
		$objp = $db->fetch_object($resql);

		// Multilangs
		if (! empty($conf->global->MAIN_MULTILANGS)) // si l'option est active
		{
			$sql = "SELECT label";
			$sql.= " FROM ".MAIN_DB_PREFIX."product_lang";
			$sql.= " WHERE fk_product=".$objp->rowid;
			$sql.= " AND lang='". $langs->getDefaultLang() ."'";
			$sql.= " LIMIT 1";

			$result = $db->query($sql);
			if ($result)
			{
				$objtp = $db->fetch_object($result);
				if (! empty($objtp->label)) $objp->label = $objtp->label;
			}
		}


		$product_static->ref=$objp->ref;
		$product_static->id=$objp->rowid;
        $product_static->label = $objp->label;
		$product_static->type=$objp->fk_product_type;
		$product_static->entity=$objp->entity;
		$product_static->status_batch=$objp->tobatch;

		$product_lot_static->batch=$objp->batch;
		$product_lot_static->product_id=$objp->rowid;
		$product_lot_static->id=$objp->lotid;
		$product_lot_static->eatby=$objp->eatby;
		$product_lot_static->sellby=$objp->sellby;


		$warehousetmp->id=$objp->fk_entrepot;
		$warehousetmp->ref=$objp->warehouse_ref;
		$warehousetmp->label=$objp->warehouse_ref;
		$warehousetmp->fk_parent=$objp->warehouse_parent;

		print '<tr>';

		// Ref
		print '<td class="nowrap">';
		print $product_static->getNomUrl(1,'',16);
		//if ($objp->stock_theorique < $objp->seuil_stock_alerte) print ' '.img_warning($langs->trans("StockTooLow"));
		print '</td>';

		// Label
		print '<td>'.$objp->label.'</td>';

		if (! empty($conf->service->enabled) && $type == 1)
		{
			print '<td align="center">';
			if (preg_match('/([0-9]+)y/i',$objp->duration,$regs)) print $regs[1].' '.$langs->trans("DurationYear");
			elseif (preg_match('/([0-9]+)m/i',$objp->duration,$regs)) print $regs[1].' '.$langs->trans("DurationMonth");
			elseif (preg_match('/([0-9]+)d/i',$objp->duration,$regs)) print $regs[1].' '.$langs->trans("DurationDay");
			else print $objp->duration;
			print '</td>';
		}
		//print '<td align="right">'.$objp->stock_theorique.'</td>';
		//print '<td align="right">'.$objp->seuil_stock_alerte.'</td>';
		//print '<td align="right">'.$objp->desiredstock.'</td>';

		// Warehouse
		print '<td>';
		if ($objp->fk_entrepot > 0)
		{
    		print $warehousetmp->getNomUrl(1);
		}
		print '</td>';

		// Lot
		print '<td align="center">';
		if ($product_lot_static->batch)
		{
			print $product_lot_static->getNomUrl(1);
		}
		print '</td>';

		print '<td align="center">'.dol_print_date($db->jdate($objp->eatby), 'day').'</td>';
		print '<td align="center">'.dol_print_date($db->jdate($objp->sellby), 'day').'</td>';
		print '<td align="right">';
        //if ($objp->seuil_stock_alerte && ($objp->stock_physique < $objp->seuil_stock_alerte)) print img_warning($langs->trans("StockTooLow")).' ';
		print $objp->stock_physique;
		print '</td>';
		print '<td align="right"><a href="'.DOL_URL_ROOT.'/product/stock/mouvement.php?idproduct='.$product_static->id.'&search_warehouse='.$objp->fk_entrepot.'&search_batch='.($objp->batch != 'Undefined' ? $objp->batch : 'Undefined').'">'.$langs->trans("Movements").'</a></td>';
		print '<td align="right" class="nowrap">'.$product_static->LibStatut($objp->statut,5,0).'</td>';
        print '<td align="right" class="nowrap">'.$product_static->LibStatut($objp->tobuy,5,1).'</td>';
        print '<td></td>';
		print "</tr>\n";
		$i++;
	}

	print "</table>";
	print '</div>';
	print '</form>';

	$db->free($resql);

}
else
{
	dol_print_error($db);
}


llxFooter();
$db->close();
