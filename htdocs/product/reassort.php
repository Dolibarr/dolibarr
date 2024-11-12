<?php
/* Copyright (C) 2001-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2018  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2019       Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2023 		Vincent de Grandpré  	<vincent@de-grandpre.quebec>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks'));

$action = GETPOST('action', 'aZ09');
$sref = GETPOST("sref", 'alpha');
$snom = GETPOST("snom", 'alpha');
$sall = trim(GETPOST('search_all', 'alphanohtml'));
$type = GETPOSTISSET('type') ? GETPOSTINT('type') : Product::TYPE_PRODUCT;
$search_barcode = GETPOST("search_barcode", 'alpha');
$search_toolowstock = GETPOST('search_toolowstock');
$tosell = GETPOST("tosell");
$tobuy = GETPOST("tobuy");
$fourn_id = GETPOSTINT("fourn_id");
$sbarcode = GETPOSTINT("sbarcode");
$search_stock_physique = GETPOST('search_stock_physique', 'alpha');

$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0) {
	$page = 0;
}
if (!$sortfield) {
	$sortfield = "p.ref";
}
if (!$sortorder) {
	$sortorder = "ASC";
}
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;

// Load sale and categ filters
$search_sale = GETPOST("search_sale");
if (GETPOSTISSET('catid')) {
	$search_categ = GETPOSTINT('catid');
} else {
	$search_categ = GETPOSTINT('search_categ');
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
if (getDolGlobalString('STOCK_CALCULATE_ON_SHIPMENT')
	|| getDolGlobalString('STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER')
	|| getDolGlobalString('STOCK_CALCULATE_ON_SHIPMENT_CLOSE')
	|| getDolGlobalString('STOCK_CALCULATE_ON_RECEPTION')
	|| getDolGlobalString('STOCK_CALCULATE_ON_RECEPTION_CLOSE')
	|| isModEnabled('mrp')) {
	$virtualdiffersfromphysical = 1; // According to increase/decrease stock options, virtual and physical stock may differs.
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('productreassortlist'));

if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'produit|service', 0, 'product&product');
$result = restrictedArea($user, 'stock');

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
	$search_toolowstock = '';
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
if (!empty($objp->stock_physique) && $objp->stock_physique < 0) {
	print '<span class="warning">';
}

$sql = 'SELECT p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type, p.entity,';
$sql .= ' p.fk_product_type, p.tms as datem,';
$sql .= ' p.duration, p.tosell as statut, p.tobuy, p.seuil_stock_alerte, p.desiredstock,';
if (getDolGlobalString('PRODUCT_STOCK_LIST_SHOW_WITH_PRECALCULATED_DENORMALIZED_PHYSICAL_STOCK')) {
	$sql .= ' p.stock as stock_physique';
} else {
	$sql .= ' SUM(s.reel) as stock_physique';
}
if (getDolGlobalString('PRODUCT_USE_UNITS')) {
	$sql .= ', u.short_label as unit_short';
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= ' FROM '.MAIN_DB_PREFIX.'product as p';
if (!getDolGlobalString('PRODUCT_STOCK_LIST_SHOW_WITH_PRECALCULATED_DENORMALIZED_PHYSICAL_STOCK')) {
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'product_stock as s ON p.rowid = s.fk_product';
}
if (getDolGlobalString('PRODUCT_USE_UNITS')) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_units as u on p.fk_unit = u.rowid';
}
// Add table from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
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
if (!getDolGlobalString('PRODUCT_STOCK_LIST_SHOW_WITH_PRECALCULATED_DENORMALIZED_PHYSICAL_STOCK')) {
	if (!getDolGlobalString('PRODUCT_STOCK_LIST_SHOW_VIRTUAL_WITH_NO_PHYSICAL')) {
		$sql .= " AND EXISTS (SELECT e.rowid FROM " . MAIN_DB_PREFIX . "entrepot as e WHERE e.rowid = s.fk_entrepot AND e.entity IN (" . getEntity('stock') . "))";
	} else {
		$sql .= " AND
		(
			EXISTS
				(SELECT e.rowid
				 FROM " . MAIN_DB_PREFIX . "entrepot as e
				 WHERE e.rowid = s.fk_entrepot AND e.entity IN (" . getEntity('stock') . "))
			OR (
				SELECT SUM(cd1.qty) as qty
				FROM " . MAIN_DB_PREFIX . "commande_fournisseurdet as cd1
				LEFT JOIN " . MAIN_DB_PREFIX . "commande_fournisseur as c1
					ON c1.rowid = cd1.fk_commande
				WHERE c1.entity IN (1) AND cd1.fk_product = p.rowid AND c1.fk_statut in (3,4) AND cd1.qty <> 0
			) IS NOT NULL
			OR (
				SELECT SUM(cd2.qty) as qty
				FROM " . MAIN_DB_PREFIX . "commandedet as cd2
				LEFT JOIN " . MAIN_DB_PREFIX . "commande as c2 ON c2.rowid = cd2.fk_commande
				WHERE c2.entity IN (1) AND cd2.fk_product = p.rowid AND c2.fk_statut in (1,2) AND cd2.qty <> 0
			) IS NOT NULL
			OR (
				SELECT SUM(ed3.qty) as qty
				FROM " . MAIN_DB_PREFIX . "expeditiondet as ed3
				LEFT JOIN " . MAIN_DB_PREFIX . "expedition as e3 ON e3.rowid = ed3.fk_expedition
				LEFT JOIN " . MAIN_DB_PREFIX . "commandedet as cd3 ON ed3.fk_elementdet = cd3.rowid
				LEFT JOIN " . MAIN_DB_PREFIX . "commande as c3 ON c3.rowid = cd3.fk_commande
				WHERE e3.entity IN (1) AND cd3.fk_product = p.rowid AND c3.fk_statut IN (1,2) AND e3.fk_statut IN (1,2) AND ed3.qty <> 0
			) IS NOT NULL
			OR (
				SELECT SUM(mp4.qty) as qty
				FROM " . MAIN_DB_PREFIX . "mrp_production as mp4
				LEFT JOIN " . MAIN_DB_PREFIX . "mrp_mo as m4 ON m4.rowid = mp4.fk_mo AND m4.entity IN (1) AND m4.status IN (1,2)
				WHERE mp4.fk_product = p.rowid AND mp4.qty <> 0
			) IS NOT NULL
			) ";
	}
}
if ($sall) {
	$sql .= natural_search(array('p.ref', 'p.label', 'p.description', 'p.note'), $sall);
}
// if the type is not 1, we show all products (type = 0,2,3)
if (dol_strlen((string) $type)) {
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
if (getDolGlobalString('PRODUCT_STOCK_LIST_SHOW_WITH_PRECALCULATED_DENORMALIZED_PHYSICAL_STOCK')) {
	if ($search_toolowstock) {
		$sql .= " AND p.stock < p.seuil_stock_alerte";
	}
	if ($search_stock_physique != '') {
		$sql .= natural_search('p.stock', $search_stock_physique, 1, 1);
	}
}
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
if (!getDolGlobalString('PRODUCT_STOCK_LIST_SHOW_WITH_PRECALCULATED_DENORMALIZED_PHYSICAL_STOCK')) {
	$sql .= " GROUP BY p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type, p.entity,";
	$sql .= " p.fk_product_type, p.tms, p.duration, p.tosell, p.tobuy, p.seuil_stock_alerte, p.desiredstock";
}

// Add GROUP BY from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListGroupBy', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql_having = '';
if (!getDolGlobalString('PRODUCT_STOCK_LIST_SHOW_WITH_PRECALCULATED_DENORMALIZED_PHYSICAL_STOCK')) {
	if ($search_toolowstock) {
		$sql_having .= " HAVING SUM(" . $db->ifsql('s.reel IS NULL', '0', 's.reel') . ") < p.seuil_stock_alerte";
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
}

// Add HAVING from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListHaving', $parameters, $object); // Note that $action and $object may have been modified by hook
if (!empty($hookmanager->resPrint)) {
	if (!empty($sql_having)) {
		$sql_having .= " AND";
	} else {
		$sql_having .= " HAVING";
	}
	$sql_having .= $hookmanager->resPrint;
}

if (!empty($sql_having)) {
	$sql .= $sql_having;
}
$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
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
		$param .= '&limit='.((int) $limit);
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
		$param .= "&type=".urlencode((string) ($type));
	}
	if ($fourn_id) {
		$param .= "&fourn_id=".urlencode((string) ($fourn_id));
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
		$param .= "&search_categ=".urlencode((string) ($search_categ));
	}
	if ($search_toolowstock) {
		$param .= "&search_toolowstock=".urlencode($search_toolowstock);
	}
	if ($sbarcode) {
		$param .= "&sbarcode=".urlencode((string) ($sbarcode));
	}
	if ($search_stock_physique) {
		$param .= '&search_stock_physique=' . urlencode($search_stock_physique);
	}

	llxHeader("", $texte, $helpurl, '', 0, 0, '', '', '', 'mod-product page-reassort');

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
	if (isModEnabled('category')) {
		$moreforfilter .= '<div class="divsearchfield">';
		$moreforfilter .= img_picto($langs->trans('Categories'), 'category', 'class="pictofixedwidth"');
		$moreforfilter .= $htmlother->select_categories(Categorie::TYPE_PRODUCT, $search_categ, 'search_categ', 1);
		$moreforfilter .= '</div>';
	}

	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= '<label for="search_toolowstock">'.$langs->trans("StockTooLow").' </label><input type="checkbox" id="search_toolowstock" name="search_toolowstock" value="1"'.($search_toolowstock ? ' checked' : '').'>';
	$moreforfilter .= '</div>';

	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	print '</div>';

	$formProduct = new FormProduct($db);
	$formProduct->loadWarehouses();
	$warehouses_list = $formProduct->cache_warehouses;
	$nb_warehouse = count($warehouses_list);
	$colspan_warehouse = 1;
	if (getDolGlobalString('STOCK_DETAIL_ON_WAREHOUSE')) {
		$colspan_warehouse = $nb_warehouse > 1 ? $nb_warehouse + 1 : 1;
	}

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">';

	// Fields title search
	print '<tr class="liste_titre_filter">';
	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre maxwidthsearch">';
		$searchpicto = $form->showFilterAndCheckAddButtons(0);
		print $searchpicto;
		print '</td>';
	}
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="sref" size="6" value="'.$sref.'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="snom" size="8" value="'.$snom.'">';
	print '</td>';
	// Duration
	if (isModEnabled("service") && $type == 1) {
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
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre maxwidthsearch">';
		$searchpicto = $form->showFilterAndCheckAddButtons(0);
		print $searchpicto;
		print '</td>';
	}
	print '</tr>';

	// Line for column titles
	print '<tr class="liste_titre">';
	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print_liste_field_titre('');
	}
	print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "p.ref", '', $param, "", $sortfield, $sortorder);
	print_liste_field_titre("Label", $_SERVER["PHP_SELF"], "p.label", '', $param, "", $sortfield, $sortorder);
	if (isModEnabled("service") && $type == 1) {
		print_liste_field_titre("Duration", $_SERVER["PHP_SELF"], "p.duration", '', $param, "", $sortfield, $sortorder, 'center ');
	}
	print_liste_field_titre("StockLimit", $_SERVER["PHP_SELF"], "p.seuil_stock_alerte", '', $param, "", $sortfield, $sortorder, 'right ');
	print_liste_field_titre("DesiredStock", $_SERVER["PHP_SELF"], "p.desiredstock", '', $param, "", $sortfield, $sortorder, 'right ');
	print_liste_field_titre("PhysicalStock", $_SERVER["PHP_SELF"], "stock_physique", '', $param, "", $sortfield, $sortorder, 'right ');
	// Details per warehouse
	if (getDolGlobalString('STOCK_DETAIL_ON_WAREHOUSE')) {	// TODO This should be moved into the selection of fields on page product/list (page product/stock will be removed and replaced with product/list with its own context)
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
	if (getDolGlobalString('PRODUCT_USE_UNITS')) {
		print_liste_field_titre("Unit", $_SERVER["PHP_SELF"], "unit_short", '', $param, 'align="right"', $sortfield, $sortorder);
	}
	print_liste_field_titre('');
	print_liste_field_titre("ProductStatusOnSell", $_SERVER["PHP_SELF"], "p.tosell", '', $param, "", $sortfield, $sortorder, 'right ');
	print_liste_field_titre("ProductStatusOnBuy", $_SERVER["PHP_SELF"], "p.tobuy", '', $param, "", $sortfield, $sortorder, 'right ');
	// Hook fields
	$parameters = array('param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print_liste_field_titre('');
	}
	print "</tr>\n";

	while ($i < min($num, $limit)) {
		$objp = $db->fetch_object($resql);

		$product = new Product($db);
		$product->fetch($objp->rowid);
		$product->load_stock();

		print '<tr>';
		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td></td>';
		}
		print '<td class="nowrap">';
		print $product->getNomUrl(1, '', 16);
		//if ($objp->stock_theorique < $objp->seuil_stock_alerte) print ' '.img_warning($langs->trans("StockTooLow"));
		print '</td>';
		print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($product->label).'">'.dol_escape_htmltag($product->label).'</td>';

		if (isModEnabled("service") && $type == 1) {
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
		print '<td class="right">';
		print $objp->seuil_stock_alerte;
		print '</td>';
		print '<td class="right">';
		print $objp->desiredstock;
		print '</td>';
		// Real stock
		print '<td class="right">';
		if ($objp->seuil_stock_alerte != '' && ($objp->stock_physique < $objp->seuil_stock_alerte)) {
			print img_warning($langs->trans("StockLowerThanLimit", $objp->seuil_stock_alerte)).' ';
		}
		if ($objp->stock_physique < 0) {
			print '<span class="warning">';
		}
		print price(price2num($objp->stock_physique, 'MS'), 0, $langs, 1, 0);
		if ($objp->stock_physique < 0) {
			print '</span>';
		}
		print '</td>';

		// Details per warehouse
		if (getDolGlobalString('STOCK_DETAIL_ON_WAREHOUSE')) {	// TODO This should be moved into the selection of fields on page product/list (page product/stock will be removed and replaced with product/list with its own context)
			if ($nb_warehouse > 1) {
				foreach ($warehouses_list as &$wh) {
					print '<td class="right">';
					print price(empty($product->stock_warehouse[$wh['id']]->real) ? 0 : price2num($product->stock_warehouse[$wh['id']]->real, 'MS'), 0, $langs, 1, 0);
					print '</td>';
				}
			}
		}

		// Virtual stock
		if ($virtualdiffersfromphysical) {
			print '<td class="right">';
			if ($objp->seuil_stock_alerte != '' && ($product->stock_theorique < (float) $objp->seuil_stock_alerte)) {
				print img_warning($langs->trans("StockLowerThanLimit", $objp->seuil_stock_alerte)).' ';
			}
			if ($objp->stock_physique < 0) {
				print '<span class="warning">';
			}
			print price(price2num($product->stock_theorique, 'MS'), 0, $langs, 1, 0);
			if ($objp->stock_physique < 0) {
				print '</span>';
			}
			print '</td>';
		}
		// Units
		if (getDolGlobalString('PRODUCT_USE_UNITS')) {
			print '<td class="left">'.dol_escape_htmltag($objp->unit_short).'</td>';
		}
		print '<td class="center nowraponall">';
		print img_picto($langs->trans("StockMovement"), 'movement', 'class="pictofixedwidth"');
		print '<a href="'.DOL_URL_ROOT.'/product/stock/movement_list.php?idproduct='.$product->id.'">'.$langs->trans("Movements").'</a>';
		print '</td>';
		print '<td class="right nowrap">'.$product->LibStatut($objp->statut, 5, 0).'</td>';
		print '<td class="right nowrap">'.$product->LibStatut($objp->tobuy, 5, 1).'</td>';
		// Fields from hook
		$parameters = array('obj' => $objp);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $product); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td></td>';
		}

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
