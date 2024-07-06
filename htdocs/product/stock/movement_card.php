<?php
/* Copyright (C) 2001-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2020	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2015		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2018		Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2019-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/product/stock/movement_card.php
 *	\ingroup    stock
 *	\brief      Page to list stock movements
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/stock.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'orders'));
if (isModEnabled('productbatch')) {
	$langs->load("productbatch");
}

// Security check
$result = restrictedArea($user, 'stock');

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$msid = GETPOSTINT('msid');
$product_id = GETPOSTINT("product_id");
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'movementlist';

$idproduct = GETPOSTINT('idproduct');
$year = GETPOSTINT("year");
$month = GETPOSTINT("month");
$search_ref = GETPOST('search_ref', 'alpha');
$search_movement = GETPOST("search_movement", 'alpha');
$search_product_ref = trim(GETPOST("search_product_ref", 'alpha'));
$search_product = trim(GETPOST("search_product", 'alpha'));
$search_warehouse = trim(GETPOST("search_warehouse", 'alpha'));
$search_inventorycode = trim(GETPOST("search_inventorycode", 'alpha'));
$search_user = trim(GETPOST("search_user", 'alpha'));
$search_batch = trim(GETPOST("search_batch", 'alpha'));
$search_qty = trim(GETPOST("search_qty", 'alpha'));
$search_type_mouvement = GETPOST('search_type_mouvement', "intcomma");

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
if (!$sortfield) {
	$sortfield = "m.datem";
}
if (!$sortorder) {
	$sortorder = "DESC";
}

$pdluoid = GETPOSTINT('pdluoid');

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$object = new MouvementStock($db);
$hookmanager->initHooks(array('movementlist'));
$extrafields = new ExtraFields($db);
$formfile = new FormFile($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

$arrayfields = array(
	'm.rowid' => array('label' => $langs->trans("Ref"), 'checked' => 1),
	'm.datem' => array('label' => $langs->trans("Date"), 'checked' => 1),
	'p.ref' => array('label' => $langs->trans("ProductRef"), 'checked' => 1, 'css' => 'maxwidth100'),
	'p.label' => array('label' => $langs->trans("ProductLabel"), 'checked' => 1),
	'm.batch' => array('label' => $langs->trans("BatchNumberShort"), 'checked' => 1, 'enabled' => (isModEnabled('productbatch'))),
	'pl.eatby' => array('label' => $langs->trans("EatByDate"), 'checked' => 0, 'position' => 10, 'enabled' => (isModEnabled('productbatch'))),
	'pl.sellby' => array('label' => $langs->trans("SellByDate"), 'checked' => 0, 'position' => 10, 'enabled' => (isModEnabled('productbatch'))),
	'e.ref' => array('label' => $langs->trans("Warehouse"), 'checked' => 1, 'enabled' => (!($id > 0))), // If we are on specific warehouse, we hide it
	'm.fk_user_author' => array('label' => $langs->trans("Author"), 'checked' => 0),
	'm.inventorycode' => array('label' => $langs->trans("InventoryCodeShort"), 'checked' => 1),
	'm.label' => array('label' => $langs->trans("MovementLabel"), 'checked' => 1),
	'm.type_mouvement' => array('label' => $langs->trans("TypeMovement"), 'checked' => 1),
	'origin' => array('label' => $langs->trans("Origin"), 'checked' => 1),
	'm.value' => array('label' => $langs->trans("Qty"), 'checked' => 1),
	'm.price' => array('label' => $langs->trans("UnitPurchaseValue"), 'checked' => 0),
	//'m.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
	//'m.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500)
);

$usercanread = (($user->hasRight('stock', 'mouvement', 'lire')));
$usercancreate = (($user->hasRight('stock', 'mouvement', 'creer')));
$usercandelete = (($user->hasRight('stock', 'mouvement', 'supprimer')));



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

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Do we click on purge search criteria ?
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // Both test are required to be compatible with all browsers
	$year = '';
	$month = '';
	$search_ref = '';
	$search_movement = "";
	$search_type_mouvement = "";
	$search_inventorycode = "";
	$search_product_ref = "";
	$search_product = "";
	$search_warehouse = "";
	$search_user = "";
	$search_batch = "";
	$search_qty = '';
	$sall = "";
	$toselect = array();
	$search_array_options = array();
}

// Correct stock
if ($action == "correct_stock") {
	$product = new Product($db);
	if (!empty($product_id)) {
		$result = $product->fetch($product_id);
	}

	$error = 0;

	if (empty($product_id)) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Product")), null, 'errors');
		$action = 'correction';
	}
	if (!is_numeric(GETPOST("nbpiece"))) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldMustBeANumeric", $langs->transnoentitiesnoconv("NumberOfUnit")), null, 'errors');
		$action = 'correction';
	}

	if (!$error) {
		$origin_element = '';
		$origin_id = null;

		if (GETPOSTINT('projectid')) {
			$origin_element = 'project';
			$origin_id = GETPOSTINT('projectid');
		}

		if ($product->hasbatch()) {
			$batch = GETPOST('batch_number', 'alpha');

			//$eatby=GETPOST('eatby');
			//$sellby=GETPOST('sellby');
			$eatby = dol_mktime(0, 0, 0, GETPOSTINT('eatbymonth'), GETPOSTINT('eatbyday'), GETPOSTINT('eatbyyear'));
			$sellby = dol_mktime(0, 0, 0, GETPOSTINT('sellbymonth'), GETPOSTINT('sellbyday'), GETPOSTINT('sellbyyear'));

			$result = $product->correct_stock_batch(
				$user,
				$id,
				GETPOSTINT("nbpiece"),
				GETPOSTINT("mouvement"),
				GETPOST("label", 'san_alpha'),
				GETPOST('unitprice', 'alpha'),
				$eatby,
				$sellby,
				$batch,
				GETPOST('inventorycode', 'alpha'),
				$origin_element,
				$origin_id
			); // We do not change value of stock for a correction
		} else {
			$result = $product->correct_stock(
				$user,
				$id,
				GETPOSTINT("nbpiece"),
				GETPOST("mouvement", 'alpha'),
				GETPOST("label", 'san_alpha'),
				GETPOST('unitprice', 'alpha'),
				GETPOST('inventorycode', 'alpha'),
				$origin_element,
				$origin_id
			); // We do not change value of stock for a correction
		}

		if ($result > 0) {
			header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
			exit;
		} else {
			$error++;
			setEventMessages($product->error, $product->errors, 'errors');
			$action = 'correction';
		}
	}

	if (!$error) {
		$action = '';
	}
}

// Transfer stock from a warehouse to another warehouse
if ($action == "transfert_stock" && !$cancel) {
	$error = 0;
	$product = new Product($db);
	if (!empty($product_id)) {
		$result = $product->fetch($product_id);
	}

	if (!(GETPOSTINT("id_entrepot_destination") > 0)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
		$error++;
		$action = 'transfert';
	}
	if (empty($product_id)) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Product")), null, 'errors');
		$action = 'transfert';
	}
	if (!GETPOSTINT("nbpiece")) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NumberOfUnit")), null, 'errors');
		$error++;
		$action = 'transfert';
	}
	if ($id == GETPOSTINT("id_entrepot_destination")) {
		setEventMessages($langs->trans("ErrorSrcAndTargetWarehouseMustDiffers"), null, 'errors');
		$error++;
		$action = 'transfert';
	}

	if (isModEnabled('productbatch')) {
		$product = new Product($db);
		$result = $product->fetch($product_id);

		if ($product->hasbatch() && !GETPOST("batch_number", 'alpha')) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("batch_number")), null, 'errors');
			$error++;
			$action = 'transfert';
		}
	}

	if (!$error) {
		if ($id) {
			$object = new Entrepot($db);
			$result = $object->fetch($id);

			$db->begin();

			$product->load_stock('novirtual'); // Load array product->stock_warehouse

			// Define value of products moved
			$pricesrc = 0;
			if (isset($product->pmp)) {
				$pricesrc = $product->pmp;
			}
			$pricedest = $pricesrc;

			if ($product->hasbatch()) {
				$pdluo = new Productbatch($db);

				if ($pdluoid > 0) {
					$result = $pdluo->fetch($pdluoid);
					if ($result) {
						$srcwarehouseid = $pdluo->warehouseid;
						$batch = $pdluo->batch;
						$eatby = $pdluo->eatby;
						$sellby = $pdluo->sellby;
					} else {
						setEventMessages($pdluo->error, $pdluo->errors, 'errors');
						$error++;
					}
				} else {
					$srcwarehouseid = $id;
					$batch = GETPOST('batch_number', 'alpha');
					$eatby = $d_eatby;
					$sellby = $d_sellby;
				}

				if (!$error) {
					// Remove stock
					$result1 = $product->correct_stock_batch(
						$user,
						$srcwarehouseid,
						GETPOSTINT("nbpiece"),
						1,
						GETPOST("label", 'san_alpha'),
						$pricesrc,
						$eatby,
						$sellby,
						$batch,
						GETPOST('inventorycode', 'alpha')
					);
					// Add stock
					$result2 = $product->correct_stock_batch(
						$user,
						GETPOSTINT("id_entrepot_destination"),
						GETPOSTINT("nbpiece"),
						0,
						GETPOST("label", 'san_alpha'),
						$pricedest,
						$eatby,
						$sellby,
						$batch,
						GETPOST('inventorycode', 'alpha')
					);
				}
			} else {
				// Remove stock
				$result1 = $product->correct_stock(
					$user,
					$id,
					GETPOSTINT("nbpiece"),
					1,
					GETPOST("label", 'alpha'),
					$pricesrc,
					GETPOST('inventorycode', 'alpha')
				);

				// Add stock
				$result2 = $product->correct_stock(
					$user,
					GETPOST("id_entrepot_destination"),
					GETPOSTINT("nbpiece"),
					0,
					GETPOST("label", 'alpha'),
					$pricedest,
					GETPOST('inventorycode', 'alpha')
				);
			}
			if (!$error && $result1 >= 0 && $result2 >= 0) {
				$db->commit();

				if ($backtopage) {
					header("Location: ".$backtopage);
					exit;
				} else {
					header("Location: movement_list.php?id=".$object->id);
					exit;
				}
			} else {
				setEventMessages($product->error, $product->errors, 'errors');
				$db->rollback();
				$action = 'transfert';
			}
		}
	}
}


/*
 * Build document
 */
// The builddoc action for object of a movement must be on the movement card
// Actions to build doc
$upload_dir = $conf->stock->dir_output."movement/";
$permissiontoadd = $user->hasRight('stock', 'creer');
include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';


if (empty($reshook) && $action != 'remove_file') {
	$objectclass = 'MouvementStock';
	$objectlabel = 'Movements';
	$permissiontoread = $user->hasRight('stock', 'lire');
	$permissiontodelete = $user->hasRight('stock', 'supprimer');
	$uploaddir = $conf->stock->dir_output."/movement/";
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}



/*
 * View
 */

$productlot = new Productlot($db);
$productstatic = new Product($db);
$warehousestatic = new Entrepot($db);
$movement = new MouvementStock($db);
$userstatic = new User($db);
$form = new Form($db);
$formother = new FormOther($db);
$formproduct = new FormProduct($db);
if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}

$sql = "SELECT p.rowid, p.ref as product_ref, p.label as produit, p.tobatch, p.fk_product_type as type, p.entity,";
$sql .= " e.ref as warehouse_ref, e.rowid as entrepot_id, e.lieu,";
$sql .= " m.rowid as mid, m.value as qty, m.datem, m.fk_user_author, m.label, m.inventorycode, m.fk_origin, m.origintype,";
$sql .= " m.batch, m.price,";
$sql .= " m.type_mouvement,";
$sql .= " pl.rowid as lotid, pl.eatby, pl.sellby,";
$sql .= " u.login, u.photo, u.lastname, u.firstname";
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key." as options_".$key : '');
	}
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " FROM ".MAIN_DB_PREFIX."entrepot as e,";
$sql .= " ".MAIN_DB_PREFIX."product as p,";
$sql .= " ".MAIN_DB_PREFIX."stock_mouvement as m";
if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (m.rowid = ef.fk_object)";
}
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON m.fk_user_author = u.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_lot as pl ON m.batch = pl.batch AND m.fk_product = pl.fk_product";
$sql .= " WHERE m.fk_product = p.rowid";
if ($msid > 0) {
	$sql .= " AND m.rowid = ".((int) $msid);
}
$sql .= " AND m.fk_entrepot = e.rowid";
$sql .= " AND e.entity IN (".getEntity('stock').")";
if (!getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
	$sql .= " AND p.fk_product_type = 0";
}
if ($id > 0) {
	$sql .= " AND e.rowid = ".((int) $id);
}
$sql .= dolSqlDateFilter('m.datem', 0, $month, $year);
if ($idproduct > 0) {
	$sql .= " AND p.rowid = ".((int) $idproduct);
}
if (!empty($search_ref)) {
	$sql .= natural_search('m.rowid', $search_ref, 1);
}
if (!empty($search_movement)) {
	$sql .= natural_search('m.label', $search_movement);
}
if (!empty($search_inventorycode)) {
	$sql .= natural_search('m.inventorycode', $search_inventorycode);
}
if (!empty($search_product_ref)) {
	$sql .= natural_search('p.ref', $search_product_ref);
}
if (!empty($search_product)) {
	$sql .= natural_search('p.label', $search_product);
}
if ($search_warehouse != '' && $search_warehouse != '-1') {
	$sql .= natural_search('e.rowid', $search_warehouse, 2);
}
if (!empty($search_user)) {
	$sql .= natural_search(array('u.lastname', 'u.firstname', 'u.login'), $search_user);
}
if (!empty($search_batch)) {
	$sql .= natural_search('m.batch', $search_batch);
}
if ($search_qty != '') {
	$sql .= natural_search('m.value', $search_qty, 1);
}
if ($search_type_mouvement != '' && $search_type_mouvement != '-1') {
	$sql .= natural_search('m.type_mouvement', $search_type_mouvement, 2);
}
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= $db->order($sortfield, $sortorder);

$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
}

//print $sql;

$resql = $db->query($sql);

if ($resql) {
	$product = new Product($db);
	$object = new Entrepot($db);

	if ($idproduct > 0) {
		$product->fetch($idproduct);
	}
	if ($id > 0 || $ref) {
		$result = $object->fetch($id, $ref);
		if ($result < 0) {
			dol_print_error($db);
		}
	}

	$num = $db->num_rows($resql);

	$arrayofselected = is_array($toselect) ? $toselect : array();


	$i = 0;
	$help_url = 'EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
	if ($msid) {
		$texte = $langs->trans('StockMovementForId', $msid);
	} else {
		$texte = $langs->trans("ListOfStockMovements");
		if ($id) {
			$texte .= ' ('.$langs->trans("ForThisWarehouse").')';
		}
	}
	llxHeader("", $texte, $help_url, '', 0, 0, '', '', '', 'mod-product page-stock_movement_card');

	/*
	 * Show tab only if we ask a particular warehouse
	 */
	if ($object->id > 0) {
		$head = stock_prepare_head($object);

		print dol_get_fiche_head($head, 'movements', $langs->trans("Warehouse"), -1, 'stock');


		$linkback = '<a href="'.DOL_URL_ROOT.'/product/stock/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '<div class="refidno">';
		$morehtmlref .= $langs->trans("LocationSummary").' : '.$object->lieu;
		$morehtmlref .= '</div>';

		$shownav = 1;
		if ($user->socid && !in_array('stock', explode(',', getDolGlobalString('MAIN_MODULES_FOR_EXTERNAL')))) {
			$shownav = 0;
		}

		dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref', 'ref', $morehtmlref);


		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border centpercent">';

		print '<tr>';

		// Description
		print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>'.dol_htmlentitiesbr($object->description).'</td></tr>';

		$calcproductsunique = $object->nb_different_products();
		$calcproducts = $object->nb_products();

		// Total nb of different products
		print '<tr><td>'.$langs->trans("NumberOfDifferentProducts").'</td><td>';
		print empty($calcproductsunique['nb']) ? '0' : $calcproductsunique['nb'];
		print "</td></tr>";

		// Nb of products
		print '<tr><td>'.$langs->trans("NumberOfProducts").'</td><td>';
		$valtoshow = price2num($calcproducts['nb'], 'MS');
		print empty($valtoshow) ? '0' : $valtoshow;
		print "</td></tr>";

		print '</table>';

		print '</div>';
		print '<div class="fichehalfright">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border centpercent">';

		// Value
		print '<tr><td class="titlefield">'.$langs->trans("EstimatedStockValueShort").'</td><td>';
		print price((empty($calcproducts['value']) ? '0' : price2num($calcproducts['value'], 'MT')), 0, $langs, 0, -1, -1, $conf->currency);
		print "</td></tr>";

		// Last movement
		$sql = "SELECT MAX(m.datem) as datem";
		$sql .= " FROM ".MAIN_DB_PREFIX."stock_mouvement as m";
		$sql .= " WHERE m.fk_entrepot = ".(int) $object->id;
		$resqlbis = $db->query($sql);
		if ($resqlbis) {
			$obj = $db->fetch_object($resqlbis);
			$lastmovementdate = $db->jdate($obj->datem);
		} else {
			dol_print_error($db);
		}

		print '<tr><td>'.$langs->trans("LastMovement").'</td><td>';
		if ($lastmovementdate) {
			print dol_print_date($lastmovementdate, 'dayhour');
		} else {
			print $langs->trans("None");
		}
		print "</td></tr>";

		print "</table>";

		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div>';

		print dol_get_fiche_end();
	}


	/*
	 * Correct stock
	 */
	if ($action == "correction") {
		include DOL_DOCUMENT_ROOT.'/product/stock/tpl/stockcorrection.tpl.php';
		print '<br>';
	}

	/*
	 * Transfer of units
	 */
	if ($action == "transfert") {
		include DOL_DOCUMENT_ROOT.'/product/stock/tpl/stocktransfer.tpl.php';
		print '<br>';
	}


	/*
	 * Action bar
	 */
	if ((empty($action) || $action == 'list') && $id > 0) {
		print "<div class=\"tabsAction\">\n";

		if ($user->hasRight('stock', 'mouvement', 'creer')) {
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&action=correction&token='.newToken().'">'.$langs->trans("CorrectStock").'</a>';
		}

		if ($user->hasRight('stock', 'mouvement', 'creer')) {
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&action=transfert&token='.newToken().'">'.$langs->trans("TransferStock").'</a>';
		}

		print '</div><br>';
	}

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.urlencode($contextpage);
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.((int) $limit);
	}
	if ($id > 0) {
		$param .= '&id='.urlencode((string) ($id));
	}
	if ($search_movement) {
		$param .= '&search_movement='.urlencode($search_movement);
	}
	if ($search_inventorycode) {
		$param .= '&search_inventorycode='.urlencode($search_inventorycode);
	}
	if ($search_type_mouvement) {
		$param .= '&search_type_mouvement='.urlencode($search_type_mouvement);
	}
	if ($search_product_ref) {
		$param .= '&search_product_ref='.urlencode($search_product_ref);
	}
	if ($search_product) {
		$param .= '&search_product='.urlencode($search_product);
	}
	if ($search_batch) {
		$param .= '&search_batch='.urlencode($search_batch);
	}
	if ($search_warehouse > 0) {
		$param .= '&search_warehouse='.urlencode($search_warehouse);
	}
	if ($search_user) {
		$param .= '&search_user='.urlencode($search_user);
	}
	if ($idproduct > 0) {
		$param .= '&idproduct='.urlencode((string) ($idproduct));
	}
	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions = array(
	//    'presend'=>$langs->trans("SendByMail"),
	//    'builddoc'=>$langs->trans("PDFMerge"),
	);
	//if ($user->rights->stock->supprimer) $arrayofmassactions['predelete']='<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
	if (in_array($massaction, array('presend', 'predelete'))) {
		$arrayofmassactions = array();
	}
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') {
		print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	}
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
	if ($id > 0) {
		print '<input type="hidden" name="id" value="'.$id.'">';
	}

	if ($id > 0) {
		print_barre_liste($texte, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, '', 0, '', '', $limit);
	} else {
		print_barre_liste($texte, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'generic', 0, '', '', $limit);
	}

	if ($sall) {
		if (!isset($fieldstosearchall) || !is_array($fieldstosearchall)) {
			// Ensure $fieldstosearchall is array
			$fieldstosearchall = array();
		}
		foreach ($fieldstosearchall as $key => $val) {
			$fieldstosearchall[$key] = $langs->trans($val);
		}
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall).implode(', ', $fieldstosearchall).'</div>';
	}

	$moreforfilter = '';

	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$moreforfilter .= $hookmanager->resPrint;
	} else {
		$moreforfilter = $hookmanager->resPrint;
	}

	if (!empty($moreforfilter)) {
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>';
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	// Fields title search
	print '<tr class="liste_titre_filter">';
	if (!empty($arrayfields['m.rowid']['checked'])) {
		// Ref
		print '<td class="liste_titre left">';
		print '<input class="flat maxwidth25" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
		print '</td>';
	}
	if (!empty($arrayfields['m.datem']['checked'])) {
		print '<td class="liste_titre nowraponall">';
		print '<input class="flat" type="text" size="2" maxlength="2" placeholder="'.dol_escape_htmltag($langs->trans("Month")).'" name="month" value="'.$month.'">';
		if (empty($conf->productbatch->enabled)) {
			print '&nbsp;';
		}
		//else print '<br>';
		$syear = $year ? $year : -1;
		print '<input class="flat maxwidth50" type="text" maxlength="4" placeholder="'.dol_escape_htmltag($langs->trans("Year")).'" name="year" value="'.($syear > 0 ? $syear : '').'">';
		//print $formother->selectyear($syear,'year',1, 20, 5);
		print '</td>';
	}
	if (!empty($arrayfields['p.ref']['checked'])) {
		// Product Ref
		print '<td class="liste_titre left">';
		print '<input class="flat maxwidth75" type="text" name="search_product_ref" value="'.dol_escape_htmltag($idproduct ? $product->ref : $search_product_ref).'">';
		print '</td>';
	}
	if (!empty($arrayfields['p.label']['checked'])) {
		// Product label
		print '<td class="liste_titre left">';
		print '<input class="flat maxwidth100" type="text" name="search_product" value="'.dol_escape_htmltag($idproduct ? $product->label : $search_product).'">';
		print '</td>';
	}
	// Batch
	if (!empty($arrayfields['m.batch']['checked'])) {
		print '<td class="liste_titre center"><input class="flat maxwidth75" type="text" name="search_batch" value="'.dol_escape_htmltag($search_batch).'"></td>';
	}
	if (!empty($arrayfields['pl.eatby']['checked'])) {
		print '<td class="liste_titre left">';
		print '</td>';
	}
	if (!empty($arrayfields['pl.sellby']['checked'])) {
		print '<td class="liste_titre left">';
		print '</td>';
	}
	// Warehouse
	if (!empty($arrayfields['e.ref']['checked'])) {
		print '<td class="liste_titre maxwidthonsmartphone left">';
		//print '<input class="flat" type="text" size="8" name="search_warehouse" value="'.($search_warehouse).'">';
		print $formproduct->selectWarehouses($search_warehouse, 'search_warehouse', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, null, 'maxwidth200');
		print '</td>';
	}
	if (!empty($arrayfields['m.fk_user_author']['checked'])) {
		// Author
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" size="6" name="search_user" value="'.dol_escape_htmltag($search_user).'">';
		print '</td>';
	}
	if (!empty($arrayfields['m.inventorycode']['checked'])) {
		// Inventory code
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" size="4" name="search_inventorycode" value="'.dol_escape_htmltag($search_inventorycode).'">';
		print '</td>';
	}
	if (!empty($arrayfields['m.label']['checked'])) {
		// Label of movement
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" size="8" name="search_movement" value="'.dol_escape_htmltag($search_movement).'">';
		print '</td>';
	}
	if (!empty($arrayfields['m.type_mouvement']['checked'])) {
		// Type of movement
		print '<td class="liste_titre center">';
		//print '<input class="flat" type="text" size="3" name="search_type_mouvement" value="'.dol_escape_htmltag($search_type_mouvement).'">';
		print '<select id="search_type_mouvement" name="search_type_mouvement" class="maxwidth150">';
		print '<option value="" '.(($search_type_mouvement == "") ? 'selected="selected"' : '').'></option>';
		print '<option value="0" '.(($search_type_mouvement == "0") ? 'selected="selected"' : '').'>'.$langs->trans('StockIncreaseAfterCorrectTransfer').'</option>';
		print '<option value="1" '.(($search_type_mouvement == "1") ? 'selected="selected"' : '').'>'.$langs->trans('StockDecreaseAfterCorrectTransfer').'</option>';
		print '<option value="2" '.(($search_type_mouvement == "2") ? 'selected="selected"' : '').'>'.$langs->trans('StockDecrease').'</option>';
		print '<option value="3" '.(($search_type_mouvement == "3") ? 'selected="selected"' : '').'>'.$langs->trans('StockIncrease').'</option>';
		print '</select>';
		print ajax_combobox('search_type_mouvement');
		// TODO: add new function $formentrepot->selectTypeOfMovement(...) like
		// print $formproduct->selectWarehouses($search_warehouse, 'search_warehouse', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, null, 'maxwidth200');
		print '</td>';
	}
	if (!empty($arrayfields['origin']['checked'])) {
		// Origin of movement
		print '<td class="liste_titre left">';
		print '&nbsp; ';
		print '</td>';
	}
	if (!empty($arrayfields['m.value']['checked'])) {
		// Qty
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_qty" value="'.dol_escape_htmltag($search_qty).'">';
		print '</td>';
	}
	if (!empty($arrayfields['m.price']['checked'])) {
		// Price
		print '<td class="liste_titre left">';
		print '&nbsp; ';
		print '</td>';
	}


	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters = array('arrayfields' => $arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (!empty($arrayfields['m.datec']['checked'])) {
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Date modification
	if (!empty($arrayfields['m.tms']['checked'])) {
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Actions
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterAndCheckAddButtons(0);
	print $searchpicto;
	print '</td>';
	print "</tr>\n";

	print '<tr class="liste_titre">';
	if (!empty($arrayfields['m.rowid']['checked'])) {
		print_liste_field_titre($arrayfields['m.rowid']['label'], $_SERVER["PHP_SELF"], 'm.rowid', '', $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['m.datem']['checked'])) {
		print_liste_field_titre($arrayfields['m.datem']['label'], $_SERVER["PHP_SELF"], 'm.datem', '', $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['p.ref']['checked'])) {
		print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER["PHP_SELF"], 'p.ref', '', $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['p.label']['checked'])) {
		print_liste_field_titre($arrayfields['p.label']['label'], $_SERVER["PHP_SELF"], 'p.label', '', $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['m.batch']['checked'])) {
		print_liste_field_titre($arrayfields['m.batch']['label'], $_SERVER["PHP_SELF"], 'm.batch', '', $param, '', $sortfield, $sortorder, 'center ');
	}
	if (!empty($arrayfields['pl.eatby']['checked'])) {
		print_liste_field_titre($arrayfields['pl.eatby']['label'], $_SERVER["PHP_SELF"], 'pl.eatby', '', $param, '', $sortfield, $sortorder, 'center ');
	}
	if (!empty($arrayfields['pl.sellby']['checked'])) {
		print_liste_field_titre($arrayfields['pl.sellby']['label'], $_SERVER["PHP_SELF"], 'pl.sellby', '', $param, '', $sortfield, $sortorder, 'center ');
	}
	if (!empty($arrayfields['e.ref']['checked'])) {
		// We are on a specific warehouse card, no filter on other should be possible
		print_liste_field_titre($arrayfields['e.ref']['label'], $_SERVER["PHP_SELF"], "e.ref", "", $param, "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['m.fk_user_author']['checked'])) {
		print_liste_field_titre($arrayfields['m.fk_user_author']['label'], $_SERVER["PHP_SELF"], "m.fk_user_author", "", $param, "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['m.inventorycode']['checked'])) {
		print_liste_field_titre($arrayfields['m.inventorycode']['label'], $_SERVER["PHP_SELF"], "m.inventorycode", "", $param, "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['m.label']['checked'])) {
		print_liste_field_titre($arrayfields['m.label']['label'], $_SERVER["PHP_SELF"], "m.label", "", $param, "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['m.type_mouvement']['checked'])) {
		print_liste_field_titre($arrayfields['m.type_mouvement']['label'], $_SERVER["PHP_SELF"], "m.type_mouvement", "", $param, '', $sortfield, $sortorder, 'center ');
	}
	if (!empty($arrayfields['origin']['checked'])) {
		print_liste_field_titre($arrayfields['origin']['label'], $_SERVER["PHP_SELF"], "", "", $param, "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['m.value']['checked'])) {
		print_liste_field_titre($arrayfields['m.value']['label'], $_SERVER["PHP_SELF"], "m.value", "", $param, '', $sortfield, $sortorder, 'right ');
	}
	if (!empty($arrayfields['m.price']['checked'])) {
		print_liste_field_titre($arrayfields['m.price']['label'], $_SERVER["PHP_SELF"], "m.price", "", $param, '', $sortfield, $sortorder, 'right ');
	}

	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';

	// Hook fields
	$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (!empty($arrayfields['m.datec']['checked'])) {
		print_liste_field_titre($arrayfields['m.datec']['label'], $_SERVER["PHP_SELF"], "m.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	}
	if (!empty($arrayfields['m.tms']['checked'])) {
		print_liste_field_titre($arrayfields['m.tms']['label'], $_SERVER["PHP_SELF"], "m.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	}
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	print "</tr>\n";


	$arrayofuniqueproduct = array();
	while ($i < ($limit ? min($num, $limit) : $num)) {
		$objp = $db->fetch_object($resql);

		$userstatic->id = $objp->fk_user_author;
		$userstatic->login = $objp->login;
		$userstatic->lastname = $objp->lastname;
		$userstatic->firstname = $objp->firstname;
		$userstatic->photo = $objp->photo;

		$productstatic->id = $objp->rowid;
		$productstatic->ref = $objp->product_ref;
		$productstatic->label = $objp->produit;
		$productstatic->type = $objp->type;
		$productstatic->entity = $objp->entity;
		$productstatic->status_batch = $objp->tobatch;

		$productlot->id = $objp->lotid;
		$productlot->batch = $objp->batch;
		$productlot->eatby = $objp->eatby;
		$productlot->sellby = $objp->sellby;

		$warehousestatic->id = $objp->entrepot_id;
		$warehousestatic->label = $objp->warehouse_ref;
		$warehousestatic->lieu = $objp->lieu;

		$arrayofuniqueproduct[$objp->rowid] = $objp->produit;
		if (!empty($objp->fk_origin)) {
			$origin = $movement->get_origin($objp->fk_origin, $objp->origintype);
		} else {
			$origin = '';
		}

		print '<tr class="oddeven">';
		// Id movement
		if (!empty($arrayfields['m.rowid']['checked'])) {
			// This is primary not movement id
			print '<td>'.$objp->mid.'</td>';
		}
		if (!empty($arrayfields['m.datem']['checked'])) {
			// Date
			print '<td>'.dol_print_date($db->jdate($objp->datem), 'dayhour').'</td>';
		}
		if (!empty($arrayfields['p.ref']['checked'])) {
			// Product ref
			print '<td class="nowraponall">';
			print $productstatic->getNomUrl(1, 'stock', 16);
			print "</td>\n";
		}
		if (!empty($arrayfields['p.label']['checked'])) {
			// Product label
			print '<td>';
			/*$productstatic->id=$objp->rowid;
			$productstatic->ref=$objp->produit;
			$productstatic->type=$objp->type;
			print $productstatic->getNomUrl(1,'',16);*/
			print $productstatic->label;
			print "</td>\n";
		}
		if (!empty($arrayfields['m.batch']['checked'])) {
			print '<td class="center nowraponall">';
			if ($productlot->id > 0) {
				print $productlot->getNomUrl(1);
			} else {
				print $productlot->batch; // the id may not be defined if movement was entered when lot was not saved or if lot was removed after movement.
			}
			print '</td>';
		}
		if (!empty($arrayfields['pl.eatby']['checked'])) {
			print '<td class="center">'.dol_print_date($objp->eatby, 'day').'</td>';
		}
		if (!empty($arrayfields['pl.sellby']['checked'])) {
			print '<td class="center">'.dol_print_date($objp->sellby, 'day').'</td>';
		}
		// Warehouse
		if (!empty($arrayfields['e.ref']['checked'])) {
			print '<td>';
			print $warehousestatic->getNomUrl(1);
			print "</td>\n";
		}
		// Author
		if (!empty($arrayfields['m.fk_user_author']['checked'])) {
			print '<td class="tdoverflowmax100">';
			print $userstatic->getNomUrl(-1);
			print "</td>\n";
		}
		if (!empty($arrayfields['m.inventorycode']['checked'])) {
			// Inventory code
			print '<td><a href="'
						.DOL_URL_ROOT.'/product/stock/movement_card.php?id='.urlencode($objp->entrepot_id)
						.'&search_inventorycode='.urlencode($objp->inventorycode)
						.'&search_type_mouvement='.urlencode($objp->type_mouvement)
						.'">'
						.$objp->inventorycode
						.'</a></td>';
		}
		if (!empty($arrayfields['m.label']['checked'])) {
			// Label of movement
			print '<td class="tdoverflowmax100aaa">'.$objp->label.'</td>';
		}
		if (!empty($arrayfields['m.type_mouvement']['checked'])) {
			// Type of movement
			switch ($objp->type_mouvement) {
				case "0":
					print '<td class="center">'.$langs->trans('StockIncreaseAfterCorrectTransfer').'</td>';
					break;
				case "1":
					print '<td class="center">'.$langs->trans('StockDecreaseAfterCorrectTransfer').'</td>';
					break;
				case "2":
					print '<td class="center">'.$langs->trans('StockDecrease').'</td>';
					break;
				case "3":
					print '<td class="center">'.$langs->trans('StockIncrease').'</td>';
					break;
			}
		}
		if (!empty($arrayfields['origin']['checked'])) {
			// Origin of movement
			print '<td class="nowraponall">'.$origin.'</td>';
		}
		if (!empty($arrayfields['m.value']['checked'])) {
			// Qty
			print '<td class="right">';
			if ($objp->qt > 0) {
				print '+';
			}
			print $objp->qty;
			print '</td>';
		}
		if (!empty($arrayfields['m.price']['checked'])) {
			// Price
			print '<td class="right">';
			if ($objp->price != 0) {
				print price($objp->price);
			}
			print '</td>';
		}
		// Action column
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($obj->rowid, $arrayofselected)) {
				$selected = 1;
			}
			print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}

		print "</tr>\n";
		$i++;
	}
	$db->free($resql);

	print "</table>";
	print '</div>';
	print "</form>";

	// Add number of product when there is a filter on period
	if (count($arrayofuniqueproduct) == 1 && is_numeric($year)) {
		print "<br>";

		$productidselected = 0;
		foreach ($arrayofuniqueproduct as $key => $val) {
			$productidselected = $key;
			$productlabelselected = $val;
		}
		$datebefore = dol_get_first_day($year ? $year : dol_print_date(time(), "%Y"), $month ? $month : 1, true);
		$dateafter = dol_get_last_day($year ? $year : dol_print_date(time(), "%Y"), $month ? $month : 12, true);
		$balancebefore = $movement->calculateBalanceForProductBefore($productidselected, $datebefore);
		$balanceafter = $movement->calculateBalanceForProductBefore($productidselected, $dateafter);

		//print '<tr class="total"><td class="liste_total">';
		print $langs->trans("NbOfProductBeforePeriod", $productlabelselected, dol_print_date($datebefore, 'day', 'gmt'));
		//print '</td>';
		//print '<td class="liste_total right" colspan="6">';
		print ': '.$balancebefore;
		print "<br>\n";
		//print '</td></tr>';
		//print '<tr class="total"><td class="liste_total">';
		print $langs->trans("NbOfProductAfterPeriod", $productlabelselected, dol_print_date($dateafter, 'day', 'gmt'));
		//print '</td>';
		//print '<td class="liste_total right" colspan="6">';
		print ': '.$balanceafter;
		print "<br>\n";
		//print '</td></tr>';
	}
} else {
	dol_print_error($db);
}



/*
 * Generated documents
 */
//Area for doc and last events of warehouse are stored on the main card of warehouse
$modulepart = 'movement';

if ($action != 'create' && $action != 'edit' && $action != 'delete' && $id > 0) {
	print '<br>';
	print '<div class="fichecenter"><div class="fichehalfleft">';
	print '<a name="builddoc"></a>'; // ancre

	// Documents
	$objectref = dol_sanitizeFileName($object->ref);
	// Add inventorycode & type_mouvement to filename of the pdf
	if (!empty($search_inventorycode)) {
		$objectref .= "_".$id."_".$search_inventorycode;
	}
	if ($search_type_mouvement) {
		$objectref .= "_".$search_type_mouvement;
	}
	$relativepath = $comref.'/'.$objectref.'.pdf';
	$filedir = $conf->stock->dir_output.'/movement/'.$objectref;

	$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id."&search_inventorycode=".$search_inventorycode."&search_type_mouvement=$search_type_mouvement";
	$genallowed = $usercanread;
	$delallowed = $usercancreate;

	$genallowed = $user->hasRight('stock', 'lire');
	$delallowed = $user->hasRight('stock', 'creer');

	print $formfile->showdocuments($modulepart, $objectref, $filedir, $urlsource, $genallowed, $delallowed, '', 0, 0, 0, 28, 0, '', 0, '', $object->default_lang, '', $object);
	$somethingshown = $formfile->numoffiles;

	print '</div><div class="fichehalfright">';

	$MAXEVENT = 10;

	$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT.'/product/agenda.php?id='.$object->id);

	// List of actions on element
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	$formactions = new FormActions($db);
	$somethingshown = $formactions->showactions($object, 'mouvement', 0, 1, '', $MAXEVENT, '', $morehtmlcenter); // Show all action for product

	print '</div></div>';
}


// End of page
llxFooter();
$db->close();
