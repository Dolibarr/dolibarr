<?php
/* Copyright (C) 2013		Cédric Salvador		<csalvador@gpcsolutions.fr>
 * Copyright (C) 2013-2020	Laurent Destaileur	<ely@users.sourceforge.net>
 * Copyright (C) 2014		Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2016		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2016		ATM Consulting		<support@atm-consulting.fr>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/product/stock/stockatdate.php
 *  \ingroup    stock
 *  \brief      Page to list stocks at a given date
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once './lib/replenishment.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'orders'));

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'produit|service');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('stockreplenishlist'));

//checks if a product has been ordered

$action = GETPOST('action', 'aZ09');
$type = GETPOST('type', 'int');
$mode = GETPOST('mode', 'alpha');

$date = '';
$dateendofday = '';
if (GETPOSTISSET('dateday') && GETPOSTISSET('datemonth') && GETPOSTISSET('dateyear')) {
	$date = dol_mktime(0, 0, 0, GETPOST('datemonth', 'int'), GETPOST('dateday', 'int'), GETPOST('dateyear', 'int'));
	$dateendofday = dol_mktime(23, 59, 59, GETPOST('datemonth', 'int'), GETPOST('dateday', 'int'), GETPOST('dateyear', 'int'));
}

$search_ref = GETPOST('search_ref', 'alphanohtml');
$search_nom = GETPOST('search_nom', 'alphanohtml');

$now = dol_now();

$productid = GETPOST('productid', 'int');
$fk_warehouse = GETPOST('fk_warehouse', 'int');

$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$offset = $limit * $page;
if (!$sortfield) {
	$sortfield = 'p.ref';
}
if (!$sortorder) {
	$sortorder = 'ASC';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

$dateIsValid = true;
if ($mode == 'future') {
	if ($date && $date < $now) {
		setEventMessages($langs->trans("ErrorDateMustBeInFuture"), null, 'errors');
		$dateIsValid = false;
	}
} else {
	if ($date && $date > $now) {
		setEventMessages($langs->trans("ErrorDateMustBeBeforeToday"), null, 'errors');
		$dateIsValid = false;
	}
}


/*
 * Actions
 */

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // Both test are required to be compatible with all browsers
{
	$date = '';
	$productid = 0;
	$fk_warehouse = 0;
	$search_ref = '';
	$search_nom = '';
}

$warehouseStatus = array();
if ($conf->global->ENTREPOT_EXTRA_STATUS) {
	//$warehouseStatus[] = Entrepot::STATUS_CLOSED;
	$warehouseStatus[] = Entrepot::STATUS_OPEN_ALL;
	$warehouseStatus[] = Entrepot::STATUS_OPEN_INTERNAL;
}

// Get array with current stock per product, warehouse
$stock_prod_warehouse = array();
$stock_prod = array();
if ($date && $dateIsValid) {	// Avoid heavy sql if mandatory date is not defined
	$sql = "SELECT ps.fk_product, ps.fk_entrepot as fk_warehouse,";
	$sql .= " SUM(ps.reel) AS stock";
	$sql .= " FROM ".MAIN_DB_PREFIX."product_stock as ps";
	$sql .= ", ".MAIN_DB_PREFIX."entrepot as w";
	$sql .= " WHERE w.entity IN (".getEntity('stock').")";
	$sql .= " AND w.rowid = ps.fk_entrepot";
	if (!empty($conf->global->ENTREPOT_EXTRA_STATUS) && count($warehouseStatus)) {
		$sql .= " AND w.statut IN (".$db->sanitize($db->escape(implode(',', $warehouseStatus))).")";
	}
	if ($productid > 0) {
		$sql .= " AND ps.fk_product = ".$productid;
	}
	if ($fk_warehouse > 0) {
		$sql .= " AND ps.fk_entrepot = ".$fk_warehouse;
	}
	$sql .= " GROUP BY fk_product, fk_entrepot";
	//print $sql;

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			$tmp_fk_product   = $obj->fk_product;
			$tmp_fk_warehouse = $obj->fk_warehouse;
			$stock = $obj->stock;

			$stock_prod_warehouse[$tmp_fk_product][$tmp_fk_warehouse] = $stock;
			$stock_prod[$tmp_fk_product] = (isset($stock_prod[$tmp_fk_product]) ? $stock_prod[$tmp_fk_product] : 0) + $stock;

			$i++;
		}

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
	//var_dump($stock_prod_warehouse);
} elseif ($action == 'filter') {
	setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
}

// Get array with list of stock movements between date and now (for product/warehouse=
$movements_prod_warehouse = array();
$movements_prod = array();
$movements_prod_warehouse_nb = array();
$movements_prod_nb = array();
if ($date && $dateIsValid) {
	$sql = "SELECT sm.fk_product, sm.fk_entrepot, SUM(sm.value) AS stock, COUNT(sm.rowid) AS nbofmovement";
	$sql .= " FROM ".MAIN_DB_PREFIX."stock_mouvement as sm";
	$sql .= ", ".MAIN_DB_PREFIX."entrepot as w";
	$sql .= " WHERE w.entity IN (".getEntity('stock').")";
	$sql .= " AND w.rowid = sm.fk_entrepot";
	if (!empty($conf->global->ENTREPOT_EXTRA_STATUS) && count($warehouseStatus)) {
		$sql .= " AND w.statut IN (".$db->sanitize($db->escape(implode(',', $warehouseStatus))).")";
	}
	if ($mode == 'future') {
		$sql .= " AND sm.datem <= '".$db->idate($dateendofday)."'";
	} else {
		$sql .= " AND sm.datem >= '".$db->idate($date)."'";
	}
	if ($productid > 0) {
		$sql .= " AND sm.fk_product = ".$productid;
	}
	if ($fk_warehouse > 0) {
		$sql .= " AND sm.fk_entrepot = ".$fk_warehouse;
	}
	$sql .= " GROUP BY sm.fk_product, sm.fk_entrepot";
	$resql = $db->query($sql);

	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			$fk_product = $obj->fk_product;
			$fk_entrepot 	= $obj->fk_entrepot;
			$stock = $obj->stock;
			$nbofmovement	= $obj->nbofmovement;

			// Pour llx_product_stock.reel
			$movements_prod_warehouse[$fk_product][$fk_entrepot] = $stock;
			$movements_prod_warehouse_nb[$fk_product][$fk_entrepot] = $nbofmovement;

			// Pour llx_product.stock
			$movements_prod[$fk_product] += $stock;
			$movements_prod_nb[$fk_product] += $nbofmovement;

			$i++;
		}

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}
//var_dump($movements_prod_warehouse);
//var_dump($movements_prod);


/*
 * View
 */

$form = new Form($db);
$formproduct = new FormProduct($db);
$prod = new Product($db);

$num = 0;

$title = $langs->trans('StockAtDate');

$sql = 'SELECT p.rowid, p.ref, p.label, p.description, p.price,';
$sql .= ' p.price_ttc, p.price_base_type, p.fk_product_type, p.desiredstock, p.seuil_stock_alerte,';
$sql .= ' p.tms as datem, p.duration, p.tobuy, p.stock';
if ($fk_warehouse > 0) {
	$sql .= ', SUM(ps.reel) as stock_reel';
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= ' FROM '.MAIN_DB_PREFIX.'product as p';
if ($fk_warehouse > 0) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_stock as ps ON p.rowid = ps.fk_product AND ps.fk_entrepot = '.$fk_warehouse;
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListJoin', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= ' WHERE p.entity IN ('.getEntity('product').')';
if ($productid > 0) {
	$sql .= " AND p.rowid = ".$productid;
}
if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
	$sql .= " AND p.fk_product_type = 0";
}
if (!empty($canvas)) $sql .= ' AND p.canvas = "'.$db->escape($canvas).'"';
if ($fk_warehouse > 0) {
	$sql .= ' GROUP BY p.rowid, p.ref, p.label, p.description, p.price, p.price_ttc, p.price_base_type, p.fk_product_type, p.desiredstock, p.seuil_stock_alerte,';
	$sql .= ' p.tms, p.duration, p.tobuy, p.stock';
}
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

if ($sortfield == 'stock_reel' && $fk_warehouse <= 0) {
	$sortfield = 'stock';
}
if ($sortfield == 'stock' && $fk_warehouse > 0) {
	$sortfield = 'stock_reel';
}
$sql .= $db->order($sortfield, $sortorder);

if ($date && $dateIsValid) {	// We avoid a heavy sql if mandatory parameter date not yet defined
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

	$sql .= $db->plimit($limit + 1, $offset);

	//print $sql;
	$resql = $db->query($sql);
	if (empty($resql))
	{
		dol_print_error($db);
		exit;
	}

	$num = $db->num_rows($resql);
}

$i = 0;
//print $sql;

$helpurl = 'EN:Module_Stocks_En|FR:Module_Stock|';
$helpurl .= 'ES:M&oacute;dulo_Stocks';

llxHeader('', $title, $helpurl, '');

$head = array();

$head[0][0] = DOL_URL_ROOT.'/product/stock/stockatdate.php';
$head[0][1] = $langs->trans("StockAtDateInPast");
$head[0][2] = 'stockatdatepast';

$head[1][0] = DOL_URL_ROOT.'/product/stock/stockatdate.php?mode=future';
$head[1][1] = $langs->trans("StockAtDateInFuture");
$head[1][2] = 'stockatdatefuture';


print load_fiche_titre($langs->trans('StockAtDate'), '', 'stock');

print dol_get_fiche_head($head, ($mode == 'future' ? 'stockatdatefuture' : 'stockatdatepast'), '', -1, '');

$desc = $langs->trans("StockAtDatePastDesc");
if ($mode == 'future') $desc = $langs->trans("StockAtDateFutureDesc");
print '<span class="opacitymedium">'.$desc.'</span><br>'."\n";
print '<br>'."\n";

print '<form name="formFilterWarehouse" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="filter">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

print '<div class="inline-block valignmiddle" style="padding-right: 20px;">';
print '<span class="fieldrequired">'.$langs->trans('Date').'</span> '.$form->selectDate(($date ? $date : -1), 'date');

print ' <span class="clearbothonsmartphone marginleftonly paddingleftonly marginrightonly paddinrightonly">&nbsp;</span> ';
print img_picto('', 'product').' ';
print $langs->trans('Product').'</span> ';
$form->select_produits($productid, 'productid', '', 0, 0, -1, 2, '', 0, array(), 0, '1', 0, 'maxwidth300');

print ' <span class="clearbothonsmartphone marginleftonly paddingleftonly marginrightonly paddinrightonly">&nbsp;</span> ';
print img_picto('', 'stock').' ';
print $langs->trans('Warehouse').'</span> ';
print $formproduct->selectWarehouses((GETPOSTISSET('fk_warehouse') ? $fk_warehouse : 'ifone'), 'fk_warehouse', '', 1);
print '</div>';

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
if (empty($reshook)) print $hookmanager->resPrint;

print '<div class="inline-block valignmiddle">';
print '<input class="button" type="submit" name="valid" value="'.$langs->trans('Refresh').'">';
print '</div>';

//print '</form>';

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
$param .= '&mode='.$mode;
if ($fk_warehouse > 0) $param .= '&fk_warehouse='.$fk_warehouse;
if ($productid > 0) $param .= '&productid='.$productid;
if (GETPOST('dateday', 'int') > 0) $param .= '&dateday='.GETPOST('dateday', 'int');
if (GETPOST('datemonth', 'int') > 0) $param .= '&datemonth='.GETPOST('datemonth', 'int');
if (GETPOST('dateyear', 'int') > 0) $param .= '&dateyear='.GETPOST('dateyear', 'int');

// TODO Move this into the title line ?
print_barre_liste('', $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'stock', 0, '', '', $limit, 0, 0, 1);

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="liste centpercent">';

$stocklabel = $langs->trans('StockAtDate');
if ($mode == 'future') $stocklabel = $langs->trans("VirtualStockAtDate");

//print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="formulaire">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="type" value="'.$type.'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

// Fields title search
print '<tr class="liste_titre_filter">';
print '<td class="liste_titre"><input class="flat" type="text" name="search_ref" size="8" value="'.dol_escape_htmltag($search_ref).'"></td>';
print '<td class="liste_titre"><input class="flat" type="text" name="search_nom" size="8" value="'.dol_escape_htmltag($search_nom).'"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
if ($mode == 'future') {
	print '<td class="liste_titre"></td>';
}
// Fields from hook
$parameters = array('param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '<td class="liste_titre maxwidthsearch">';
$searchpicto = $form->showFilterAndCheckAddButtons(0);
print $searchpicto;
print '</td>';
print '</tr>';

$fieldtosortcurrentstock = 'stock';
if ($fk_warehouse > 0) {
	$fieldtosortcurrentstock = 'stock_reel';
}

// Lines of title
print '<tr class="liste_titre">';
print_liste_field_titre('Ref', $_SERVER["PHP_SELF"], 'p.ref', $param, '', '', $sortfield, $sortorder);
print_liste_field_titre('Label', $_SERVER["PHP_SELF"], 'p.label', $param, '', '', $sortfield, $sortorder);
if ($mode == 'future') {
	print_liste_field_titre('CurrentStock', $_SERVER["PHP_SELF"], $fieldtosortcurrentstock, $param, '', '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre('', $_SERVER["PHP_SELF"]);
	print_liste_field_titre($stocklabel, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'right ', 'VirtualStockAtDateDesc');
	print_liste_field_titre('VirtualStock', $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'right ', 'VirtualStockDesc');
} else {
	print_liste_field_titre($stocklabel, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre('', $_SERVER["PHP_SELF"]);
	print_liste_field_titre('CurrentStock', $_SERVER["PHP_SELF"], $fieldtosortcurrentstock, $param, '', '', $sortfield, $sortorder, 'right ');
}
print_liste_field_titre('', $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'right ');

// Hook fields
$parameters = array('param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print "</tr>\n";

$i = 0;
while ($i < ($limit ? min($num, $limit) : $num))
{
	$objp = $db->fetch_object($resql);

	if (!empty($conf->global->STOCK_SUPPORTS_SERVICES) || $objp->fk_product_type == 0)
	{
		$prod->fetch($objp->rowid);

		// Multilangs
		/*if (!empty($conf->global->MAIN_MULTILANGS))
		{
			$sql = 'SELECT label,description';
			$sql .= ' FROM '.MAIN_DB_PREFIX.'product_lang';
			$sql .= ' WHERE fk_product = '.$objp->rowid;
			$sql .= ' AND lang = "'.$langs->getDefaultLang().'"';
			$sql .= ' LIMIT 1';

			$resqlm = $db->query($sql);
			if ($resqlm)
			{
				$objtp = $db->fetch_object($resqlm);
				if (!empty($objtp->description)) $objp->description = $objtp->description;
				if (!empty($objtp->label)) $objp->label = $objtp->label;
			}
		}*/

		$currentstock = '';
		if ($fk_warehouse > 0)
		{
			//if ($productid > 0) {
				$currentstock = $stock_prod_warehouse[$objp->rowid][$fk_warehouse];
			//} else {
			//	$currentstock = $objp->stock_reel;
			//}
		} else {
			//if ($productid > 0) {
				$currentstock = $stock_prod[$objp->rowid];
			//} else {
			//	$currentstock = $objp->stock;
			//}
		}

		if ($mode == 'future') {
			$prod->load_stock('warehouseopen, warehouseinternal', 0); // This call also ->load_virtual_stock()

			//$result = $prod->load_stats_reception(0, '4');
			//print $prod->stats_commande_fournisseur['qty'].'<br>'."\n";
			//print $prod->stats_reception['qty'];

			$stock = '<span class="opacitymedium">'.$langs->trans("FeatureNotYetAvailable").'</span>';
			$virtualstock = $prod->stock_theorique;
		} else {
			if ($fk_warehouse > 0) {
				$stock = $currentstock - $movements_prod_warehouse[$objp->rowid][$fk_warehouse];
				$nbofmovement = $movements_prod_warehouse_nb[$objp->rowid][$fk_warehouse];
			} else {
				$stock = $currentstock - $movements_prod[$objp->rowid];
				$nbofmovement = $movements_prod_nb[$objp->rowid];
			}
		}


		print '<tr class="oddeven">';

		// Product ref
		print '<td class="nowrap">'.$prod->getNomUrl(1, '').'</td>';

		// Product label
		print '<td>'.$objp->label;
		print '<input type="hidden" name="desc'.$i.'" value="'.dol_escape_htmltag($objp->description).'">'; // TODO Remove this and make a fetch to get description when creating order instead of a GETPOST
		print '</td>';

		if ($mode == 'future') {
			// Current stock
			print '<td class="right">'.$currentstock.'</td>';

			print '<td class="right"></td>';

			// Virtual stock at date
			print '<td class="right">'.$stock.'</td>';

			// Final virtual stock
			print '<td class="right">'.$virtualstock.'</td>';
		} else {
			// Stock at date
			print '<td class="right">'.($stock ? $stock : '<span class="opacitymedium">'.$stock.'</span>').'</td>';

			print '<td class="right">';
			if ($nbofmovement > 0) {
				print '<a href="'.DOL_URL_ROOT.'/product/stock/movement_list.php?idproduct='.$objp->rowid.($fk_warehouse > 0 ? '&search_warehouse='.$fk_warehouse : '').'">'.$langs->trans("Movements").'</a>';
				print ' <span class="tabs"><span class="badge">'.$nbofmovement.'</span></span>';
			}
			print '</td>';

			// Current stock
			print '<td class="right">'.($currentstock ? $currentstock : '<span class="opacitymedium">0</span>').'</td>';
		}

		// Action
		print '<td class="right"></td>';

		// Fields from hook
		$parameters = array('objp'=>$objp);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;

		print '</tr>';
	}
	$i++;
}

$parameters = array('sql'=>$sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

if (empty($date) || ! $dateIsValid) {
	$colspan = 6;
	if ($mode == 'future') $colspan++;
	print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("EnterADateCriteria").'</span></td></tr>';
}

print '</table>';
print '</div>';

$db->free($resql);

print dol_get_fiche_end();

print '</form>';

llxFooter();

$db->close();
