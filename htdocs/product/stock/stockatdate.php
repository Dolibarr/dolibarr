<?php
/* Copyright (C) 2013		Cédric Salvador		<csalvador@gpcsolutions.fr>
 * Copyright (C) 2013-2018	Laurent Destaileur	<ely@users.sourceforge.net>
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

$action = GETPOST('action', 'alpha');
$type = GETPOST('type', 'int');
$mode = GETPOST('mode', 'alpha');


$productid = GETPOST('productid', 'int');
$fk_entrepot = GETPOST('fk_entrepot', 'int');
$texte = '';

$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
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


/*
 * Actions
 */

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha') || isset($_POST['valid'])) // Both test are required to be compatible with all browsers
{
    $sref = '';
    $snom = '';
    $sall = '';
    $salert = '';
	$draftorder = '';
}


/*
 * View
 */

$form = new Form($db);
$formproduct = new FormProduct($db);
$prod = new Product($db);

$title = $langs->trans('Status');

if (!empty($conf->global->STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE) && $fk_entrepot > 0) {
	$sqldesiredtock = $db->ifsql("pse.desiredstock IS NULL", "p.desiredstock", "pse.desiredstock");
	$sqlalertstock = $db->ifsql("pse.seuil_stock_alerte IS NULL", "p.seuil_stock_alerte", "pse.seuil_stock_alerte");
} else {
	$sqldesiredtock = 'p.desiredstock';
	$sqlalertstock = 'p.seuil_stock_alerte';
}

$sql = 'SELECT p.rowid, p.ref, p.label, p.description, p.price,';
$sql .= ' p.price_ttc, p.price_base_type,p.fk_product_type,';
$sql .= ' p.tms as datem, p.duration, p.tobuy,';
$sql .= ' p.desiredstock, p.seuil_stock_alerte,';
if (!empty($conf->global->STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE) && $fk_entrepot > 0) {
	$sql .= ' pse.desiredstock as desiredstockpse, pse.seuil_stock_alerte as seuil_stock_alertepse,';
}
$sql .= ' '.$sqldesiredtock.' as desiredstockcombined, '.$sqlalertstock.' as seuil_stock_alertecombined,';
$sql .= ' s.fk_product,';
$sql .= ' SUM('.$db->ifsql("s.reel IS NULL", "0", "s.reel").') as stock_physique';

// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= ' FROM '.MAIN_DB_PREFIX.'product as p';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_stock as s ON p.rowid = s.fk_product';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'entrepot AS ent ON s.fk_entrepot = ent.rowid AND ent.entity IN('.getEntity('stock').')';
if ($fk_supplier > 0) {
	$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'product_fournisseur_price pfp ON (pfp.fk_product = p.rowid AND pfp.fk_soc = '.$fk_supplier.')';
}
if (!empty($conf->global->STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE) && $fk_entrepot > 0) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_warehouse_properties AS pse ON (p.rowid = pse.fk_product AND pse.fk_entrepot = '.$fk_entrepot.')';
}

// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListJoin', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= ' WHERE p.entity IN ('.getEntity('product').')';
if ($sall) $sql .= natural_search(array('p.ref', 'p.label', 'p.description', 'p.note'), $sall);
// if the type is not 1, we show all products (type = 0,2,3)
if (dol_strlen($type)) {
    if ($type == 1) {
        $sql .= ' AND p.fk_product_type = 1';
    } else {
        $sql .= ' AND p.fk_product_type <> 1';
    }
}
if ($sref) $sql .= natural_search('p.ref', $sref);
if ($snom) $sql .= natural_search('p.label', $snom);
$sql .= ' AND p.tobuy = 1';
if (!empty($canvas)) $sql .= ' AND p.canvas = "'.$db->escape($canvas).'"';
$sql .= ' GROUP BY p.rowid, p.ref, p.label, p.description, p.price';
$sql .= ', p.price_ttc, p.price_base_type,p.fk_product_type, p.tms';
$sql .= ', p.duration, p.tobuy';
$sql .= ', p.desiredstock';
$sql .= ', p.seuil_stock_alerte';
if (!empty($conf->global->STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE) && $fk_entrepot > 0) {
	$sql .= ', pse.desiredstock';
	$sql .= ', pse.seuil_stock_alerte';
}
$sql .= ', s.fk_product';

if ($usevirtualstock)
{
	if (!empty($conf->commande->enabled)) {
		$sqlCommandesCli = "(SELECT ".$db->ifsql("SUM(cd1.qty) IS NULL", "0", "SUM(cd1.qty)")." as qty"; // We need the ifsql because if result is 0 for product p.rowid, we must return 0 and not NULL
		$sqlCommandesCli .= " FROM ".MAIN_DB_PREFIX."commandedet as cd1, ".MAIN_DB_PREFIX."commande as c1";
		$sqlCommandesCli .= " WHERE c1.rowid = cd1.fk_commande AND c1.entity IN (".getEntity('commande').")";
		$sqlCommandesCli .= " AND cd1.fk_product = p.rowid";
		$sqlCommandesCli .= " AND c1.fk_statut IN (1,2))";
	} else {
		$sqlCommandesCli = '0';
	}

	if (!empty($conf->expedition->enabled)) {
		$sqlExpeditionsCli = "(SELECT ".$db->ifsql("SUM(ed2.qty) IS NULL", "0", "SUM(ed2.qty)")." as qty"; // We need the ifsql because if result is 0 for product p.rowid, we must return 0 and not NULL
		$sqlExpeditionsCli .= " FROM ".MAIN_DB_PREFIX."expedition as e2,";
		$sqlExpeditionsCli .= " ".MAIN_DB_PREFIX."expeditiondet as ed2,";
                $sqlExpeditionsCli .= " ".MAIN_DB_PREFIX."commande as c2,";
		$sqlExpeditionsCli .= " ".MAIN_DB_PREFIX."commandedet as cd2";
		$sqlExpeditionsCli .= " WHERE ed2.fk_expedition = e2.rowid AND cd2.rowid = ed2.fk_origin_line AND e2.entity IN (".getEntity('expedition').")";
                $sqlExpeditionsCli .= " AND cd2.fk_commande = c2.rowid";
                $sqlExpeditionsCli .= " AND c2.fk_statut IN (1,2)";
		$sqlExpeditionsCli .= " AND cd2.fk_product = p.rowid";
		$sqlExpeditionsCli .= " AND e2.fk_statut IN (1,2))";
	} else {
		$sqlExpeditionsCli = '0';
	}

	if (!empty($conf->fournisseur->enabled)) {
		$sqlCommandesFourn = "(SELECT ".$db->ifsql("SUM(cd3.qty) IS NULL", "0", "SUM(cd3.qty)")." as qty"; // We need the ifsql because if result is 0 for product p.rowid, we must return 0 and not NULL
		$sqlCommandesFourn .= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cd3,";
		$sqlCommandesFourn .= " ".MAIN_DB_PREFIX."commande_fournisseur as c3";
		$sqlCommandesFourn .= " WHERE c3.rowid = cd3.fk_commande";
		$sqlCommandesFourn .= " AND c3.entity IN (".getEntity('supplier_order').")";
		$sqlCommandesFourn .= " AND cd3.fk_product = p.rowid";
		$sqlCommandesFourn .= " AND c3.fk_statut IN (3,4))";

		$sqlReceptionFourn = "(SELECT ".$db->ifsql("SUM(fd4.qty) IS NULL", "0", "SUM(fd4.qty)")." as qty"; // We need the ifsql because if result is 0 for product p.rowid, we must return 0 and not NULL
		$sqlReceptionFourn .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as cf4,";
		$sqlReceptionFourn .= " ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as fd4";
		$sqlReceptionFourn .= " WHERE fd4.fk_commande = cf4.rowid AND cf4.entity IN (".getEntity('supplier_order').")";
		$sqlReceptionFourn .= " AND fd4.fk_product = p.rowid";
		$sqlReceptionFourn .= " AND cf4.fk_statut IN (3,4))";
	} else {
		$sqlCommandesFourn = '0';
		$sqlReceptionFourn = '0';
	}

	if (!empty($conf->mrp->enabled)) {
		$sqlProductionToConsume = "(SELECT GREATEST(0, ".$db->ifsql("SUM(".$db->ifsql("mp5.role = 'toconsume'", 'mp5.qty', '- mp5.qty').") IS NULL", "0", "SUM(".$db->ifsql("mp5.role = 'toconsume'", 'mp5.qty', '- mp5.qty').")").") as qty"; // We need the ifsql because if result is 0 for product p.rowid, we must return 0 and not NULL
		$sqlProductionToConsume .= " FROM ".MAIN_DB_PREFIX."mrp_mo as mm5,";
		$sqlProductionToConsume .= " ".MAIN_DB_PREFIX."mrp_production as mp5";
		$sqlProductionToConsume .= " WHERE mm5.rowid = mp5.fk_mo AND mm5.entity IN (".getEntity('mo').")";
		$sqlProductionToConsume .= " AND mp5.fk_product = p.rowid";
		$sqlProductionToConsume .= " AND mp5.role IN ('toconsume', 'consummed')";
		$sqlProductionToConsume .= " AND mm5.status IN (1,2))";

		$sqlProductionToProduce = "(SELECT GREATEST(0, ".$db->ifsql("SUM(".$db->ifsql("mp5.role = 'toproduce'", 'mp5.qty', '- mp5.qty').") IS NULL", "0", "SUM(".$db->ifsql("mp5.role = 'toconsume'", 'mp5.qty', '- mp5.qty').")").") as qty"; // We need the ifsql because if result is 0 for product p.rowid, we must return 0 and not NULL
		$sqlProductionToProduce .= " FROM ".MAIN_DB_PREFIX."mrp_mo as mm5,";
		$sqlProductionToProduce .= " ".MAIN_DB_PREFIX."mrp_production as mp5";
		$sqlProductionToProduce .= " WHERE mm5.rowid = mp5.fk_mo AND mm5.entity IN (".getEntity('mo').")";
		$sqlProductionToProduce .= " AND mp5.fk_product = p.rowid";
		$sqlProductionToProduce .= " AND mp5.role IN ('toproduce', 'produced')";
		$sqlProductionToProduce .= " AND mm5.status IN (1,2))";
	} else {
		$sqlProductionToConsume = '0';
		$sqlProductionToProduce = '0';
	}

	$sql .= ' HAVING (';
	$sql .= ' ('.$sqldesiredtock.' >= 0 AND ('.$sqldesiredtock.' > SUM('.$db->ifsql("s.reel IS NULL", "0", "s.reel").')';
	$sql .= ' - ('.$sqlCommandesCli.' - '.$sqlExpeditionsCli.') + ('.$sqlCommandesFourn.' - '.$sqlReceptionFourn.') + ('.$sqlProductionToProduce.' - '.$sqlProductionToConsume.')))';
	$sql .= ' OR ';
	$sql .= ' ('.$sqlalertstock.' >= 0 AND ('.$sqlalertstock.' > SUM('.$db->ifsql("s.reel IS NULL", "0", "s.reel").')';
	$sql .= ' - ('.$sqlCommandesCli.' - '.$sqlExpeditionsCli.') + ('.$sqlCommandesFourn.' - '.$sqlReceptionFourn.') + ('.$sqlProductionToProduce.' - '.$sqlProductionToConsume.')))';
	$sql .= ')';

	if ($salert == 'on')	// Option to see when stock is lower than alert
	{
		$sql .= ' AND (';
		$sql .= $sqlalertstock.' >= 0 AND ('.$sqlalertstock.' > SUM('.$db->ifsql("s.reel IS NULL", "0", "s.reel").')';
		$sql .= ' - ('.$sqlCommandesCli.' - '.$sqlExpeditionsCli.') + ('.$sqlCommandesFourn.' - '.$sqlReceptionFourn.')  + ('.$sqlProductionToProduce.' - '.$sqlProductionToConsume.'))';
		$sql .= ')';
		$alertchecked = 'checked';
	}
} else {
	$sql .= ' HAVING (('.$sqldesiredtock.' >= 0 AND ('.$sqldesiredtock.' > SUM('.$db->ifsql("s.reel IS NULL", "0", "s.reel").')))';
	$sql .= ' OR ('.$sqlalertstock.' >= 0 AND ('.$sqlalertstock.' > SUM('.$db->ifsql("s.reel IS NULL", "0", "s.reel").'))))';

	if ($salert == 'on')	// Option to see when stock is lower than alert
	{
		$sql .= ' AND ('.$sqlalertstock.' >= 0 AND ('.$sqlalertstock.' > SUM('.$db->ifsql("s.reel IS NULL", "0", "s.reel").')))';
		$alertchecked = 'checked';
	}
}

// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

//print $sql;
$resql = $db->query($sql);
if (empty($resql))
{
    dol_print_error($db);
    exit;
}

$num = $db->num_rows($resql);
$i = 0;

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

dol_fiche_head($head, ($mode == 'future' ? 'stockatdatefuture' : 'stockatdatepast'), '', -1, '');

$desc = $langs->trans("StockAtDatePastDesc");
if ($mode == 'future') $desc = $langs->trans("StockAtDateFutureDesc");
print '<span class="opacitymedium">'.$desc.'</span><br>'."\n";
print '<br>'."\n";

print '<form name="formFilterWarehouse" method="GET" action="">';
print '<input type="hidden" name="action" value="filter">';
print '<input type="hidden" name="sref" value="'.$sref.'">';
print '<input type="hidden" name="snom" value="'.$snom.'">';
print '<input type="hidden" name="salert" value="'.$salert.'">';
print '<input type="hidden" name="draftorder" value="'.$draftorder.'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

print '<div class="inline-block valignmiddle" style="padding-right: 20px;">';
print '<span class="fieldrequired">'.$langs->trans('Date').'</span> '.$form->selectDate($date, 'date');

print ' <span class="clearbothonsmartphone marginleftonly paddingleftonly marginrightonly paddinrightonly">&nbsp;</span> '.$langs->trans('Product').'</span> ';
$form->select_produits($productid, 'productid', '', 0, 0, -1, 2, '', 0, array(), 0, '1', 0, 'maxwidth300');

print ' <span class="clearbothonsmartphone marginleftonly paddingleftonly marginrightonly paddinrightonly">&nbsp;</span> '.$langs->trans('Warehouse').'</span> '.$formproduct->selectWarehouses($fk_entrepot, 'fk_entrepot', '', 1);
print '</div>';

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
if (empty($reshook)) print $hookmanager->resPrint;

print '<div class="inline-block valignmiddle">';
print '<input class="button" type="submit" name="valid" value="'.$langs->trans('Refresh').'">';
print '</div>';

print '</form>';

if ($sref || $snom || $sall || $salert || $draftorder || GETPOST('search', 'alpha')) {
	$filters = '&sref='.$sref.'&snom='.$snom;
	$filters .= '&sall='.$sall;
	$filters .= '&salert='.$salert;
	$filters .= '&draftorder='.$draftorder;
	$filters .= '&mode='.$mode;
	$filters .= '&fk_supplier='.$fk_supplier;
	$filters .= '&fk_entrepot='.$fk_entrepot;
	print_barre_liste(
		$texte,
		$page,
		'replenish.php',
		$filters,
		$sortfield,
		$sortorder,
		'',
		$num
	);
} else {
	$filters = '&sref='.$sref.'&snom='.$snom;
	$filters .= '&fourn_id='.$fourn_id;
	$filters .= (isset($type) ? '&type='.$type : '');
	$filters .= '&='.$salert;
	$filters .= '&draftorder='.$draftorder;
	$filters .= '&mode='.$mode;
	$filters .= '&fk_supplier='.$fk_supplier;
	$filters .= '&fk_entrepot='.$fk_entrepot;
	print_barre_liste(
		$texte,
		$page,
		'replenish.php',
		$filters,
		$sortfield,
		$sortorder,
		'',
		$num
	);
}

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="liste centpercent">';

$param = (isset($type) ? '&type='.$type : '');
$param .= '&fourn_id='.$fourn_id.'&snom='.$snom.'&salert='.$salert.'&draftorder='.$draftorder;
$param .= '&sref='.$sref;
$param .= '&mode='.$mode;
$param .= '&fk_supplier='.$fk_supplier;
$param .= '&fk_entrepot='.$fk_entrepot;

$stocklabel = $langs->trans('Stock');
if ($mode == 'future') $stocklabel = $langs->trans("VirtualStock");

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="formulaire">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="fk_supplier" value="'.$fk_supplier.'">';
print '<input type="hidden" name="fk_entrepot" value="'.$fk_entrepot.'">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="type" value="'.$type.'">';
print '<input type="hidden" name="linecount" value="'.$num.'">';
print '<input type="hidden" name="action" value="order">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

// Fields title search
print '<tr class="liste_titre_filter">';
print '<td class="liste_titre"><input class="flat" type="text" name="sref" size="8" value="'.dol_escape_htmltag($sref).'"></td>';
print '<td class="liste_titre"><input class="flat" type="text" name="snom" size="8" value="'.dol_escape_htmltag($snom).'"></td>';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre right">&nbsp;</td>';
print '<td class="liste_titre right"></td>';
print '<td class="liste_titre right"></td>';
// Fields from hook
$parameters = array('param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '<td class="liste_titre maxwidthsearch">';
$searchpicto = $form->showFilterAndCheckAddButtons(0);
print $searchpicto;
print '</td>';
print '</tr>';

// Lines of title
print '<tr class="liste_titre">';
print_liste_field_titre('Ref', $_SERVER["PHP_SELF"], 'p.ref', $param, '', '', $sortfield, $sortorder);
print_liste_field_titre('Label', $_SERVER["PHP_SELF"], 'p.label', $param, '', '', $sortfield, $sortorder);
print_liste_field_titre('', $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'right ');
print_liste_field_titre('', $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'right ');
print_liste_field_titre($stocklabel, $_SERVER["PHP_SELF"], 'stock_physique', $param, '', '', $sortfield, $sortorder, 'right ');
print_liste_field_titre('', $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'right ');
print_liste_field_titre('', $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'right ');

// Hook fields
$parameters = array('param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print "</tr>\n";

while ($i < ($limit ? min($num, $limit) : $num))
{
	$objp = $db->fetch_object($resql);

	if (!empty($conf->global->STOCK_SUPPORTS_SERVICES) || $objp->fk_product_type == 0)
	{
		$prod->fetch($objp->rowid);
		$prod->load_stock('warehouseopen, warehouseinternal', $draftchecked);

		// Multilangs
		if (!empty($conf->global->MAIN_MULTILANGS))
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
		}

		if ($usevirtualstock)
		{
			// If option to increase/decrease is not on an object validation, virtual stock may differs from physical stock.
			$stock = $prod->stock_theorique;
		} else {
			$stock = $prod->stock_reel;
		}

		// Force call prod->load_stats_xxx to choose status to count (otherwise it is loaded by load_stock function)
		if (isset($draftchecked)) {
			$result = $prod->load_stats_commande_fournisseur(0, '0,1,2,3,4');
		} else {
			$result = $prod->load_stats_commande_fournisseur(0, '1,2,3,4');
		}

		$result = $prod->load_stats_reception(0, '4');

		//print $prod->stats_commande_fournisseur['qty'].'<br>'."\n";
		//print $prod->stats_reception['qty'];
		$ordered = $prod->stats_commande_fournisseur['qty'] - $prod->stats_reception['qty'];


		print '<tr class="oddeven">';

		print '<td class="nowrap">'.$prod->getNomUrl(1, '').'</td>';

		print '<td>'.$objp->label;
		print '<input type="hidden" name="desc'.$i.'" value="'.dol_escape_htmltag($objp->description).'">'; // TODO Remove this and make a fetch to get description when creating order instead of a GETPOST
		print '</td>';

		// Desired stock
		print '<td class="right"></td>';

		// Limit stock for alert
		print '<td class="right"></td>';

		// Current stock (all warehouses)
		print '<td class="right">'.$stock.'</td>';

		// Already ordered
		print '<td class="right"></td>';

		// To order
		//print '<td class="right"><input type="text" name="tobuy'.$i.'" value="'.$stocktobuy.'" '.$disabled.'></td>';
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

print '</table>';
print '</div>';

$db->free($resql);

dol_fiche_end();

print '</form>';

llxFooter();

$db->close();
