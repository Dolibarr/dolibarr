<?php
/* Copyright (C) 2001-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2018  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/product/reassort.php
 *  \ingroup    produit
 *  \brief      Page to list stocks
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks'));

$action = GETPOST('action', 'aZ09');
$sref = GETPOST("sref", 'alpha');
$snom = GETPOST("snom", 'alpha');
$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$type = GETPOSTISSET('type') ? GETPOST('type', 'int') : Product::TYPE_PRODUCT;
$search_barcode = GETPOST("search_barcode", 'alpha');
$toolowstock = GETPOST('toolowstock');
$tosell = GETPOST("tosell");
$tobuy = GETPOST("tobuy");
$fourn_id = GETPOST("fourn_id", 'int');
$sbarcode = GETPOST("sbarcode", 'int');
$search_stock_physique = GETPOST('search_stock_physique', 'alpha');

$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0) {
	$page = 0;
}
if (!$sortfield) {
	$sortfield = "p.ref";
}
if (!$sortorder) {
	$sortorder = "ASC";
}
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;

// Load sale and categ filters
$search_sale = GETPOST("search_sale");
if (GETPOSTISSET('catid')) {
	$search_categ = GETPOST('catid', 'int');
} else {
	$search_categ = GETPOST('search_categ', 'int');
}

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$canvas = GETPOST("canvas");
$objcanvas = null;
if (!empty($canvas)) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('product', 'list', $canvas);
}

// Define virtualdiffersfromphysical
$virtualdiffersfromphysical = 0;
if (!empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT)
	|| !empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER)
	|| !empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE)
	|| !empty($conf->global->STOCK_CALCULATE_ON_RECEPTION)
	|| !empty($conf->global->STOCK_CALCULATE_ON_RECEPTION_CLOSE)
	|| !empty($conf->mrp->enabled)) {
	$virtualdiffersfromphysical = 1; // According to increase/decrease stock options, virtual and physical stock may differs.
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('productreassortlist'));

if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'produit|service', 0, 'product&product');

$object = new Product($db);

/*
 * Actions
 */

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$sref = "";
	$snom = "";
	$sall = "";
	$tosell = "";
	$tobuy = "";
	$search_sale = "";
	$search_categ = "";
	$toolowstock = '';
	$fourn_id = '';
	$sbarcode = '';
	$search_stock_physique = '';
}



/*
 * View
 */

$helpurl = 'EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';

$form = new Form($db);
$htmlother = new FormOther($db);

$sql = 'SELECT p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type, p.entity,';
$sql .= ' p.fk_product_type, p.tms as datem,';
$sql .= ' p.duration, p.tosell as statut, p.tobuy, p.seuil_stock_alerte, p.desiredstock,';
$sql .= ' SUM(s.reel) as stock_physique';
if (!empty($conf->global->PRODUCT_USE_UNITS)) {
	$sql .= ', u.short_label as unit_short';
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= ' FROM '.MAIN_DB_PREFIX.'product as p';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_stock as s ON p.rowid = s.fk_product';
if (!empty($conf->global->PRODUCT_USE_UNITS)) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_units as u on p.fk_unit = u.rowid';
}
$sql .= " WHERE p.entity IN (".getEntity('product').")";
if (!empty($search_categ) && $search_categ != '-1') {
	$sql .= " AND ";
	if ($search_categ == -2) {
		$sql .= " NOT EXISTS ";
	} else {
		$sql .= " EXISTS ";
	}
	$sql .= "(";
	$sql .= " SELECT cp.fk_categorie, cp.fk_product";
	$sql .= " FROM " . MAIN_DB_PREFIX . "categorie_product as cp";
	$sql .= " WHERE cp.fk_product = p.rowid"; // Join for the needed table to filter by categ
	if ($search_categ > 0) {
		$sql .= " AND cp.fk_categorie = " . ((int) $search_categ);
	}
	$sql .= ")";
}
$sql .= " AND EXISTS (SELECT e.rowid FROM ".MAIN_DB_PREFIX."entrepot as e WHERE e.rowid = s.fk_entrepot AND e.entity IN (".getEntity('stock')."))";
if ($sall) {
	$sql .= natural_search(array('p.ref', 'p.label', 'p.description', 'p.note'), $sall);
}
// if the type is not 1, we show all products (type = 0,2,3)
if (dol_strlen($type)) {
	if ($type == 1) {
		$sql .= " AND p.fk_product_type = '1'";
	} else {
		$sql .= " AND p.fk_product_type <> '1'";
	}
}
if ($sref) {
	$sql .= natural_search('p.ref', $sref);
}
if ($search_barcode) {
	$sql .= natural_search('p.barcode', $search_barcode);
}
if ($snom) {
	$sql .= natural_search('p.label', $snom);
}
if (!empty($tosell)) {
	$sql .= " AND p.tosell = ".((int) $tosell);
}
if (!empty($tobuy)) {
	$sql .= " AND p.tobuy = ".((int) $tobuy);
}
if (!empty($canvas)) {
	$sql .= " AND p.canvas = '".$db->escape($canvas)."'";
}
if ($fourn_id > 0) {
	$sql .= " AND p.rowid = pf.fk_product AND pf.fk_soc = ".((int) $fourn_id);
}
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " GROUP BY p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type, p.entity,";
$sql .= " p.fk_product_type, p.tms, p.duration, p.tosell, p.tobuy, p.seuil_stock_alerte, p.desiredstock";
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldSelect', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql_having = '';
if ($toolowstock) {
	$sql_having .= " HAVING SUM(".$db->ifsql('s.reel IS NULL', '0', 's.reel').") < p.seuil_stock_alerte";
}
if ($search_stock_physique != '') {
	//$natural_search_physique = natural_search('HAVING SUM(' . $db->ifsql('s.reel IS NULL', '0', 's.reel') . ')', $search_stock_physique, 1, 1);
	$natural_search_physique = natural_search('SUM(' . $db->ifsql('s.reel IS NULL', '0', 's.reel') . ')', $search_stock_physique, 1, 1);
	$natural_search_physique = " " . substr($natural_search_physique, 1, -1); // remove first "(" and last ")" characters
	if (!empty($sql_having)) {
		$sql_having .= " AND";
	} else {
		$sql_having .= " HAVING";
	}
	$sql_having .= $natural_search_physique;
}
if (!empty($sql_having)) {
	$sql .= $sql_having;
}
$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	$i = 0;

	if ($num == 1 && GETPOST('autojumpifoneonly') && ($sall || $snom || $sref)) {
		$objp = $db->fetch_object($resql);
		header("Location: card.php?id=$objp->rowid");
		exit;
	}

	if (isset($type)) {
		if ($type == 1) {
			$texte = $langs->trans("Services");
		} else {
			$texte = $langs->trans("Products");
		}
	} else {
		$texte = $langs->trans("ProductsAndServices");
	}
	$texte .= ' ('.$langs->trans("MenuStocks").')';

	$param = '';
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.urlencode($limit);
	}
	if ($sall) {
		$param .= "&sall=".urlencode($sall);
	}
	if ($tosell) {
		$param .= "&tosell=".urlencode($tosell);
	}
	if ($tobuy) {
		$param .= "&tobuy=".urlencode($tobuy);
	}
	if ($type != '') {
		$param .= "&type=".urlencode($type);
	}
	if ($fourn_id) {
		$param .= "&fourn_id=".urlencode($fourn_id);
	}
	if ($snom) {
		$param .= "&snom=".urlencode($snom);
	}
	if ($sref) {
		$param .= "&sref=".urlencode($sref);
	}
	if ($search_sale) {
		$param .= "&search_sale=".urlencode($search_sale);
	}
	if ($search_categ > 0) {
		$param .= "&search_categ=".urlencode($search_categ);
	}
	if ($toolowstock) {
		$param .= "&toolowstock=".urlencode($toolowstock);
	}
	if ($sbarcode) {
		$param .= "&sbarcode=".urlencode($sbarcode);
	}
	if ($search_stock_physique) {
		$param .= '&search_stock_physique=' . urlencode($search_stock_physique);
	}

	llxHeader("", $texte, $helpurl);

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="type" value="'.$type.'">';

	print_barre_liste($texte, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'product', 0, '', '', $limit);

	if ($search_categ > 0) {
		print "<div id='ways'>";
		$c = new Categorie($db);
		$c->fetch($search_categ);
		$ways = $c->print_all_ways(' &gt; ', 'product/reassort.php');
		print " &gt; ".$ways[0]."<br>\n";
		print "</div><br>";
	}

	// Filter on categories
	$moreforfilter = '';
	if (!empty($conf->categorie->enabled)) {
		$moreforfilter .= '<div class="divsearchfield">';
		$moreforfilter .= img_picto($langs->trans('Categories'), 'category', 'class="pictofixedwidth"');
		$moreforfilter .= $htmlother->select_categories(Categorie::TYPE_PRODUCT, $search_categ, 'search_categ', 1);
		$moreforfilter .= '</div>';
	}

	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= $langs->trans("StockTooLow").' <input type="checkbox" name="toolowstock" value="1"'.($toolowstock ? ' checked' : '').'>';
	$moreforfilter .= '</div>';

	if (!empty($moreforfilter)) {
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		print '</div>';
	}

	$formProduct = new FormProduct($db);
	$formProduct->loadWarehouses();
	$warehouses_list = $formProduct->cache_warehouses;
	$nb_warehouse = count($warehouses_list);
	$colspan_warehouse = 1;
	if (!empty($conf->global->STOCK_DETAIL_ON_WAREHOUSE)) {
		$colspan_warehouse = $nb_warehouse > 1 ? $nb_warehouse + 1 : 1;
	}

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">';

	// Fields title search
	print '<tr class="liste_titre_filter">';
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="sref" size="6" value="'.$sref.'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="snom" size="8" value="'.$snom.'">';
	print '</td>';
	// Duration
	if (!empty($conf->service->enabled) && $type == 1) {
		print '<td class="liste_titre">';
		print '&nbsp;';
		print '</td>';
	}
	// Stock limit
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre right">&nbsp;</td>';
	// Physical stock
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="5" name="search_stock_physique" value="'.dol_escape_htmltag($search_stock_physique).'">';
	print '</td>';
	if ($virtualdiffersfromphysical) {
		print '<td class="liste_titre">&nbsp;</td>';
	}
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre" colspan="'.$colspan_warehouse.'">&nbsp;</td>';
	print '<td class="liste_titre"></td>';
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterAndCheckAddButtons(0);
	print $searchpicto;
	print '</td>';
	print '</tr>';

	//Line for column titles
	print "<tr class=\"liste_titre\">";
	print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "p.ref", '', $param, "", $sortfield, $sortorder);
	print_liste_field_titre("Label", $_SERVER["PHP_SELF"], "p.label", '', $param, "", $sortfield, $sortorder);
	if (!empty($conf->service->enabled) && $type == 1) {
		print_liste_field_titre("Duration", $_SERVER["PHP_SELF"], "p.duration", '', $param, "", $sortfield, $sortorder, 'center ');
	}
	print_liste_field_titre("StockLimit", $_SERVER["PHP_SELF"], "p.seuil_stock_alerte", '', $param, "", $sortfield, $sortorder, 'right ');
	print_liste_field_titre("DesiredStock", $_SERVER["PHP_SELF"], "p.desiredstock", '', $param, "", $sortfield, $sortorder, 'right ');
	print_liste_field_titre("PhysicalStock", $_SERVER["PHP_SELF"], "stock_physique", '', $param, "", $sortfield, $sortorder, 'right ');
	// Details per warehouse
	if (!empty($conf->global->STOCK_DETAIL_ON_WAREHOUSE)) {	// TODO This should be moved into the selection of fields on page product/list (page product/stock will be removed and replaced with product/list with its own context)
		if ($nb_warehouse > 1) {
			foreach ($warehouses_list as &$wh) {
				print_liste_field_titre($wh['label'], '', '', '', '', '', '', '', 'right ');
			}
		}
	}
	if ($virtualdiffersfromphysical) {
		print_liste_field_titre("VirtualStock", $_SERVER["PHP_SELF"], "", '', $param, "", $sortfield, $sortorder, 'right ', 'VirtualStockDesc');
	}
	// Units
	if (!empty($conf->global->PRODUCT_USE_UNITS)) {
		print_liste_field_titre("Unit", $_SERVER["PHP_SELF"], "unit_short", '', $param, 'align="right"', $sortfield, $sortorder);
	}
	print_liste_field_titre('');
	print_liste_field_titre("ProductStatusOnSell", $_SERVER["PHP_SELF"], "p.tosell", '', $param, "", $sortfield, $sortorder, 'right ');
	print_liste_field_titre("ProductStatusOnBuy", $_SERVER["PHP_SELF"], "p.tobuy", '', $param, "", $sortfield, $sortorder, 'right ');
	// Hook fields
	$parameters = array('param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	print_liste_field_titre('');
	print "</tr>\n";

	while ($i < min($num, $limit)) {
		$objp = $db->fetch_object($resql);

		$product = new Product($db);
		$product->fetch($objp->rowid);
		$product->load_stock();

		print '<tr>';
		print '<td class="nowrap">';
		print $product->getNomUrl(1, '', 16);
		//if ($objp->stock_theorique < $objp->seuil_stock_alerte) print ' '.img_warning($langs->trans("StockTooLow"));
		print '</td>';
		print '<td>'.$product->label.'</td>';

		if (!empty($conf->service->enabled) && $type == 1) {
			print '<td class="center">';
			if (preg_match('/([0-9]+)y/i', $objp->duration, $regs)) {
				print $regs[1].' '.$langs->trans("DurationYear");
			} elseif (preg_match('/([0-9]+)m/i', $objp->duration, $regs)) {
				print $regs[1].' '.$langs->trans("DurationMonth");
			} elseif (preg_match('/([0-9]+)d/i', $objp->duration, $regs)) {
				print $regs[1].' '.$langs->trans("DurationDay");
			} else {
				print $objp->duration;
			}
			print '</td>';
		}
		//print '<td class="right">'.$objp->stock_theorique.'</td>';
		print '<td class="right">'.$objp->seuil_stock_alerte.'</td>';
		print '<td class="right">'.$objp->desiredstock.'</td>';
		// Real stock
		print '<td class="right">';
		if ($objp->seuil_stock_alerte != '' && ($objp->stock_physique < $objp->seuil_stock_alerte)) {
			print img_warning($langs->trans("StockTooLow")).' ';
		}
		print price2num($objp->stock_physique, 'MS');
		print '</td>';

		// Details per warehouse
		if (!empty($conf->global->STOCK_DETAIL_ON_WAREHOUSE)) {	// TODO This should be moved into the selection of fields on page product/list (page product/stock will be removed and replaced with product/list with its own context)
			if ($nb_warehouse > 1) {
				foreach ($warehouses_list as &$wh) {
					print '<td class="right">';
					print empty($product->stock_warehouse[$wh['id']]->real) ? '0' : $product->stock_warehouse[$wh['id']]->real;
					print '</td>';
				}
			}
		}

		// Virtual stock
		if ($virtualdiffersfromphysical) {
			print '<td class="right">';
			if ($objp->seuil_stock_alerte != '' && ($product->stock_theorique < $objp->seuil_stock_alerte)) {
				print img_warning($langs->trans("StockTooLow")).' ';
			}
			print price2num($product->stock_theorique, 'MS');
			print '</td>';
		}
		// Units
		if (!empty($conf->global->PRODUCT_USE_UNITS)) {
			print '<td class="left">'.$objp->unit_short.'</td>';
		}
		print '<td class="center">';
		print img_picto($langs->trans("StockMovement"), 'movement', 'class="pictofixedwidth"');
		print '<a href="'.DOL_URL_ROOT.'/product/stock/movement_list.php?idproduct='.$product->id.'">'.$langs->trans("Movements").'</a>';
		print '</td>';
		print '<td class="right nowrap">'.$product->LibStatut($objp->statut, 5, 0).'</td>';
		print '<td class="right nowrap">'.$product->LibStatut($objp->tobuy, 5, 1).'</td>';
		// Fields from hook
		$parameters = array('obj'=>$objp);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $product); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		print '<td></td>';
		print "</tr>\n";
		$i++;
	}

	print "</table>";
	print '</div>';

	print '</form>';

	$db->free($resql);
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
