<?php
/* Copyright (C) 2001-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2018  Regis Houssin           <regis.houssin@inodbox.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
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
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'productbatch'));

$action     = GETPOST('action', 'aZ09') ?GETPOST('action', 'aZ09') : 'view'; // The action 'add', 'create', 'edit', 'update', 'view', ...
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'myobjectlist'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
$optioncss  = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')
$mode       = GETPOST('mode', 'aZ');

$sref = GETPOST("sref", 'alpha');
$snom = GETPOST("snom", 'alpha');
$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$type = GETPOSTISSET('type') ? GETPOST('type', 'int') : Product::TYPE_PRODUCT;
$search_barcode = GETPOST("search_barcode", 'alpha');
$search_warehouse = GETPOST('search_warehouse', 'alpha');
$search_batch = GETPOST('search_batch', 'alpha');
$toolowstock = GETPOST('toolowstock');
$tosell = GETPOST("tosell");
$tobuy = GETPOST("tobuy");
$fourn_id = GETPOST("fourn_id", 'int');
$sbarcode = GETPOST("sbarcode", 'int');
$search_stock_physique = GETPOST('search_stock_physique', 'alpha');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize array of search criterias
$object = new Product($db);
$search_sale = GETPOST("search_sale");
if (GETPOSTISSET('catid')) {
	$search_categ = GETPOST('catid', 'int');
} else {
	$search_categ = GETPOST('search_categ', 'int');
}

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
//$extrafields->fetch_name_optionals_label($object->table_element_line);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) {
	reset($object->fields);					// Reset is required to avoid key() to return null.
	$sortfield = "p.".key($object->fields); // Set here default search field. By default 1st field in definition.
}
if (!$sortorder) {
	$sortorder = "ASC";
}


// Initialize array of search criterias
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha') !== '') {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
	if (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
		$search[$key.'_dtstart'] = dol_mktime(0, 0, 0, GETPOST('search_'.$key.'_dtstartmonth', 'int'), GETPOST('search_'.$key.'_dtstartday', 'int'), GETPOST('search_'.$key.'_dtstartyear', 'int'));
		$search[$key.'_dtend'] = dol_mktime(23, 59, 59, GETPOST('search_'.$key.'_dtendmonth', 'int'), GETPOST('search_'.$key.'_dtendday', 'int'), GETPOST('search_'.$key.'_dtendyear', 'int'));
	}
}
$key = 'sellby';
$search[$key.'_dtstart'] = dol_mktime(0, 0, 0, GETPOST('search_'.$key.'_dtstartmonth', 'int'), GETPOST('search_'.$key.'_dtstartday', 'int'), GETPOST('search_'.$key.'_dtstartyear', 'int'));
$search[$key.'_dtend'] = dol_mktime(23, 59, 59, GETPOST('search_'.$key.'_dtendmonth', 'int'), GETPOST('search_'.$key.'_dtendday', 'int'), GETPOST('search_'.$key.'_dtendyear', 'int'));
$key = 'eatby';
$search[$key.'_dtstart'] = dol_mktime(0, 0, 0, GETPOST('search_'.$key.'_dtstartmonth', 'int'), GETPOST('search_'.$key.'_dtstartday', 'int'), GETPOST('search_'.$key.'_dtstartyear', 'int'));
$search[$key.'_dtend'] = dol_mktime(23, 59, 59, GETPOST('search_'.$key.'_dtendmonth', 'int'), GETPOST('search_'.$key.'_dtendday', 'int'), GETPOST('search_'.$key.'_dtendyear', 'int'));

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$canvas = GETPOST("canvas");
$objcanvas = null;
if (!empty($canvas)) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('product', 'list', $canvas);
}

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'produit|service', 0, 'product&product');


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		foreach ($object->fields as $key => $val) {
			$search[$key] = '';
			if (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
				$search[$key.'_dtstart'] = '';
				$search[$key.'_dtend'] = '';
			}
		}
		$search['sellby_dtstart'] = '';
		$search['eatby_dtstart'] = '';
		$search['sellby_dtend'] = '';
		$search['eatby_dtend'] = '';
		$sref = "";
		$snom = "";
		$sall = "";
		$tosell = "";
		$tobuy = "";
		$search_sale = "";
		$search_categ = "";
		$toolowstock = '';
		$search_batch = '';
		$search_warehouse = '';
		$fourn_id = '';
		$sbarcode = '';
		$search_stock_physique = '';
		$toselect = array();
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
			$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

		// Mass actions
		/*$objectclass = 'MyObject';
		$objectlabel = 'MyObject';
		$uploaddir = $conf->mymodule->dir_output;
		include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
		*/
}



/*
 * View
 */

$form = new Form($db);
$htmlother = new FormOther($db);

$now = dol_now();

$helpurl = 'EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
$title = $langs->trans("ProductsAndServices");
$morejs = array();
$morecss = array();

$sql = 'SELECT p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type, p.entity,';
$sql .= ' p.fk_product_type, p.tms as datem,';
$sql .= ' p.duration, p.tosell as statut, p.tobuy, p.seuil_stock_alerte, p.desiredstock, p.stock, p.tosell, p.tobuy, p.tobatch,';
$sql .= ' ps.fk_entrepot,';
$sql .= ' e.ref as warehouse_ref, e.lieu as warehouse_lieu, e.fk_parent as warehouse_parent,';
$sql .= ' pb.batch, pb.eatby as oldeatby, pb.sellby as oldsellby,';
$sql .= ' pl.rowid as lotid, pl.eatby, pl.sellby,';
$sql .= ' SUM(pb.qty) as stock_physique, COUNT(pb.rowid) as nbinbatchtable';
$sql .= ' FROM '.MAIN_DB_PREFIX.'product as p';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_stock as ps on p.rowid = ps.fk_product'; // Detail for each warehouse
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'entrepot as e on ps.fk_entrepot = e.rowid'; // Link on unique key
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_batch as pb on pb.fk_product_stock = ps.rowid'; // Detail for each lot on each warehouse
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_lot as pl on pl.fk_product = p.rowid AND pl.batch = pb.batch'; // Link on unique key
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
	$sql .= natural_search("p.ref", $sref);
}
if ($search_barcode) {
	$sql .= natural_search("p.barcode", $search_barcode);
}
if ($snom) {
	$sql .= natural_search("p.label", $snom);
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
if ($search_warehouse) {
	$sql .= natural_search("e.ref", $search_warehouse);
}
if ($search_batch) {
	$sql .= natural_search("pb.batch", $search_batch);
}

foreach ($search as $key => $val) {
	if (array_key_exists($key, $object->fields)) {
		if ($key == 'status' && $search[$key] == -1) {
			continue;
		}
		$mode_search = (($object->isInt($object->fields[$key]) || $object->isFloat($object->fields[$key])) ? 1 : 0);
		if ((strpos($object->fields[$key]['type'], 'integer:') === 0) || (strpos($object->fields[$key]['type'], 'sellist:') === 0) || !empty($object->fields[$key]['arrayofkeyval'])) {
			if ($search[$key] == '-1' || ($search[$key] === '0' && (empty($object->fields[$key]['arrayofkeyval']) || !array_key_exists('0', $object->fields[$key]['arrayofkeyval'])))) {
				$search[$key] = '';
			}
			$mode_search = 2;
		}
		if ($search[$key] != '') {
			$sql .= natural_search("t.".$db->escape($key), $search[$key], (($key == 'status') ? 2 : $mode_search));
		}
	} else {
		if (preg_match('/(_dtstart|_dtend)$/', $key) && $search[$key] != '') {
			$columnName = preg_replace('/(_dtstart|_dtend)$/', '', $key);
			if ($columnName == 'eatby' || $columnName == 'sellby') {
				if (preg_match('/_dtstart$/', $key)) {
					$sql .= " AND pl.".$db->escape($columnName)." >= '".$db->idate($search[$key])."'";
				}
				if (preg_match('/_dtend$/', $key)) {
					$sql .= " AND pl.".$db->escape($columnName)." <= '".$db->idate($search[$key])."'";
				}
			}
		}
	}
}

$sql .= " GROUP BY p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type, p.entity,";
$sql .= " p.fk_product_type, p.tms,";
$sql .= " p.duration, p.tosell, p.tobuy, p.seuil_stock_alerte, p.desiredstock, p.stock, p.tosell, p.tobuy, p.tobatch,";
$sql .= " ps.fk_entrepot,";
$sql .= " e.ref, e.lieu, e.fk_parent,";
$sql .= " pb.batch, pb.eatby, pb.sellby,";
$sql .= " pl.rowid, pl.eatby, pl.sellby";
$sql_having = '';
if ($toolowstock) {
	$sql_having .= " HAVING SUM(".$db->ifsql('ps.reel IS NULL', '0', 'ps.reel').") < p.seuil_stock_alerte"; // Not used yet
}
if ($search_stock_physique != '') {
	$natural_search_physique = natural_search('SUM(' . $db->ifsql('pb.qty IS NULL', '0', 'pb.qty') . ')', $search_stock_physique, 1, 1);
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

//print $sql;

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);

	if (($page * $limit) > $nbtotalofrecords) {	// if total of record found is smaller than page * limit, goto and load page 0
		$page = 0;
		$offset = 0;
	}
	$db->free($resql);
}

// Complete request and execute it with limit
$sql .= $db->order($sortfield, $sortorder);
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);

$i = 0;

if ($num == 1 && GETPOST('autojumpifoneonly') && ($sall or $snom or $sref)) {
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
$texte .= ' ('.$langs->trans("StocksByLotSerial").')';

$param = '';
if (!empty($mode)) {
	$param .= '&mode='.urlencode($mode);
}
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.urlencode($limit);
}
foreach ($search as $key => $val) {
	if (is_array($search[$key]) && count($search[$key])) {
		foreach ($search[$key] as $skey) {
			if ($skey != '') {
				$param .= '&search_'.$key.'[]='.urlencode($skey);
			}
		}
	} elseif ($search[$key] != '') {
		$param .= '&search_'.$key.'='.urlencode($search[$key]);
	}
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
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
if ($search_batch) {
	$param .= "&search_batch=".urlencode($search_batch);
}
if ($sbarcode) {
	$param .= "&sbarcode=".urlencode($sbarcode);
}
if ($search_warehouse) {
	$param .= "&search_warehouse=".urlencode($search_warehouse);
}
if ($toolowstock) {
	$param .= "&toolowstock=".urlencode($toolowstock);
}
if ($search_sale) {
	$param .= "&search_sale=".urlencode($search_sale);
}
if (!empty($search_categ) && $search_categ != '-1') {
	$param .= "&search_categ=".urlencode($search_categ);
}
if ($search_stock_physique) {
	$param .= '&search_stock_physique=' . urlencode($search_stock_physique);
}
/*if ($eatby)		$param.="&eatby=".$eatby;
if ($sellby)	$param.="&sellby=".$sellby;*/

llxHeader("", $title, $helpurl, $texte);

print '<form id="searchFormList" action="'.$_SERVER["PHP_SELF"].'" method="POST" name="formulaire">'."\n";
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="type" value="'.$type.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

print_barre_liste($texte, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'product', 0, '', '', $limit, 0, 0, 1);


if ($search_categ > 0) {
	print "<div id='ways'>";
	$c = new Categorie($db);
	$c->fetch($search_categ);
	$ways = $c->print_all_ways(' &gt; ', 'product/reassortlot.php');
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
//$moreforfilter.=$langs->trans("StockTooLow").' <input type="checkbox" name="toolowstock" value="1"'.($toolowstock?' checked':'').'>';

if (!empty($moreforfilter)) {
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	print '</div>';
}


print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">';

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre_filter">';
print '<td class="liste_titre">';
print '<input class="flat" type="text" name="sref" size="6" value="'.$sref.'">';
print '</td>';
print '<td class="liste_titre">';
print '<input class="flat" type="text" name="snom" size="8" value="'.$snom.'">';
print '</td>';
if (!empty($conf->service->enabled) && $type == 1) {
	print '<td class="liste_titre">';
	print '&nbsp;';
	print '</td>';
}
print '<td class="liste_titre"><input class="flat" type="text" name="search_warehouse" size="6" value="'.$search_warehouse.'"></td>';
print '<td class="liste_titre center"><input class="flat" type="text" name="search_batch" size="6" value="'.$search_batch.'"></td>';
if (empty($conf->global->PRODUCT_DISABLE_SELLBY)) {
	print '<td class="liste_titre center">';
	$key = 'sellby';
	print '<div class="nowrap">';
	print $form->selectDate($search[$key.'_dtstart'] ? $search[$key.'_dtstart'] : '', "search_".$key."_dtstart", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrap">';
	print $form->selectDate($search[$key.'_dtend'] ? $search[$key.'_dtend'] : '', "search_".$key."_dtend", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
if (empty($conf->global->PRODUCT_DISABLE_EATBY)) {
	print '<td class="liste_titre center">';
	$key = 'eatby';
	print '<div class="nowrap">';
	print $form->selectDate($search[$key.'_dtstart'] ? $search[$key.'_dtstart'] : '', "search_".$key."_dtstart", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrap">';
	print $form->selectDate($search[$key.'_dtend'] ? $search[$key.'_dtend'] : '', "search_".$key."_dtend", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
// Physical stock
print '<td class="liste_titre right">';
print '<input class="flat" type="text" size="5" name="search_stock_physique" value="'.dol_escape_htmltag($search_stock_physique).'">';
print '</td>';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre">&nbsp;</td>';
// Action column
if (empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}
print '</tr>'."\n";

$totalarray = array();
$totalarray['nbfield'] = 0;

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "p.ref", '', $param, "", $sortfield, $sortorder);
print_liste_field_titre("Label", $_SERVER["PHP_SELF"], "p.label", '', $param, "", $sortfield, $sortorder);
if (!empty($conf->service->enabled) && $type == 1) {
	print_liste_field_titre("Duration", $_SERVER["PHP_SELF"], "p.duration", '', $param, "", $sortfield, $sortorder, 'center ');
}
print_liste_field_titre("Warehouse", $_SERVER["PHP_SELF"], "e.ref", '', $param, "", $sortfield, $sortorder);
//print_liste_field_titre("DesiredStock", $_SERVER["PHP_SELF"], "p.desiredstock",$param,"",'',$sortfield,$sortorder, 'right );
print_liste_field_titre("Batch", $_SERVER["PHP_SELF"], "pb.batch", '', $param, "", $sortfield, $sortorder, 'center ');
if (empty($conf->global->PRODUCT_DISABLE_SELLBY)) {
	print_liste_field_titre("SellByDate", $_SERVER["PHP_SELF"], "pl.sellby", '', $param, "", $sortfield, $sortorder, 'center ');
}
if (empty($conf->global->PRODUCT_DISABLE_EATBY)) {
	print_liste_field_titre("EatByDate", $_SERVER["PHP_SELF"], "pl.eatby", '', $param, "", $sortfield, $sortorder, 'center ');
}
print_liste_field_titre("PhysicalStock", $_SERVER["PHP_SELF"], "stock_physique", '', $param, "", $sortfield, $sortorder, 'right ');
// TODO Add info of running suppliers/customers orders
//print_liste_field_titre("TheoreticalStock",$_SERVER["PHP_SELF"], "stock_theorique",$param,"",'',$sortfield,$sortorder, 'right ');
print_liste_field_titre('');
print_liste_field_titre("ProductStatusOnSell", $_SERVER["PHP_SELF"], "p.tosell", "", $param, '', $sortfield, $sortorder, 'right ');
print_liste_field_titre("ProductStatusOnBuy", $_SERVER["PHP_SELF"], "p.tobuy", "", $param, '', $sortfield, $sortorder, 'right ');
print_liste_field_titre('');
print "</tr>\n";

$product_static = new Product($db);
$product_lot_static = new Productlot($db);
$warehousetmp = new Entrepot($db);

// Loop on record
// --------------------------------------------------------------------
$i = 0;
$savnbfield = $totalarray['nbfield'];
$totalarray['nbfield'] = 0;
$imaxinloop = ($limit ? min($num, $limit) : $num);
while ($i < $imaxinloop) {
	$objp = $db->fetch_object($resql);

	// Multilangs
	if (!empty($conf->global->MAIN_MULTILANGS)) { // si l'option est active
		// TODO Use a cache
		$sql = "SELECT label";
		$sql .= " FROM ".MAIN_DB_PREFIX."product_lang";
		$sql .= " WHERE fk_product = ".((int) $objp->rowid);
		$sql .= " AND lang = '".$db->escape($langs->getDefaultLang())."'";
		$sql .= " LIMIT 1";

		$result = $db->query($sql);
		if ($result) {
			$objtp = $db->fetch_object($result);
			if (!empty($objtp->label)) {
				$objp->label = $objtp->label;
			}
		}
	}

	$product_static->ref = $objp->ref;
	$product_static->id = $objp->rowid;
	$product_static->label = $objp->label;
	$product_static->type = $objp->fk_product_type;
	$product_static->entity = $objp->entity;
	$product_static->status = $objp->tosell;
	$product_static->status_buy = $objp->tobuy;
	$product_static->status_batch = $objp->tobatch;

	$product_lot_static->batch = $objp->batch;
	$product_lot_static->product_id = $objp->rowid;
	$product_lot_static->id = $objp->lotid;
	$product_lot_static->eatby = $objp->eatby;
	$product_lot_static->sellby = $objp->sellby;


	$warehousetmp->id = $objp->fk_entrepot;
	$warehousetmp->ref = $objp->warehouse_ref;
	$warehousetmp->label = $objp->warehouse_ref;
	$warehousetmp->fk_parent = $objp->warehouse_parent;

	print '<tr>';

	// Ref
	print '<td class="nowrap">';
	print $product_static->getNomUrl(1, '', 16);
	//if ($objp->stock_theorique < $objp->seuil_stock_alerte) print ' '.img_warning($langs->trans("StockTooLow"));
	print '</td>';

	// Label
	print '<td>'.$objp->label.'</td>';

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
	//print '<td class="right">'.$objp->seuil_stock_alerte.'</td>';
	//print '<td class="right">'.$objp->desiredstock.'</td>';

	// Warehouse
	print '<td class="nowrap">';
	if ($objp->fk_entrepot > 0) {
		print $warehousetmp->getNomUrl(1);
	}
	print '</td>';

	// Lot
	print '<td class="center nowrap">';
	if ($product_lot_static->batch) {
		print $product_lot_static->getNomUrl(1);
	}
	print '</td>';

	if (empty($conf->global->PRODUCT_DISABLE_SELLBY)) {
		print '<td class="center">'.dol_print_date($db->jdate($objp->sellby), 'day').'</td>';
	}

	if (empty($conf->global->PRODUCT_DISABLE_EATBY)) {
		print '<td class="center">'.dol_print_date($db->jdate($objp->eatby), 'day').'</td>';
	}

	print '<td class="right">';
	//if ($objp->seuil_stock_alerte && ($objp->stock_physique < $objp->seuil_stock_alerte)) print img_warning($langs->trans("StockTooLow")).' ';
	print $objp->stock_physique;
	print '</td>';

	print '<td class="right">';
	print img_picto($langs->trans("StockMovement"), 'movement', 'class="pictofixedwidth"');
	print '<a href="'.DOL_URL_ROOT.'/product/stock/movement_list.php?idproduct='.$product_static->id.'&search_warehouse='.$objp->fk_entrepot.'&search_batch='.($objp->batch != 'Undefined' ? $objp->batch : 'Undefined').'">'.$langs->trans("Movements").'</a>';
	print '</td>';

	print '<td class="right nowrap">'.$product_static->LibStatut($objp->statut, 5, 0).'</td>';

	print '<td class="right nowrap">'.$product_static->LibStatut($objp->tobuy, 5, 1).'</td>';

	print '<td></td>';

	print "</tr>\n";
	$i++;
}

$db->free($resql);

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";


// End of page
llxFooter();
$db->close();
