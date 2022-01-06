<?php
/* Copyright (C) 2001-2006	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2015		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2018		Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
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
 *	\file       htdocs/product/stock/movement_list.php
 *	\ingroup    stock
 *	\brief      Page to list stock movements
 */

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
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'orders'));
if (!empty($conf->productbatch->enabled)) {
	$langs->load("productbatch");
}

// Security check
$result = restrictedArea($user, 'stock');

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$msid = GETPOST('msid', 'int');
$product_id = GETPOST("product_id", 'int');
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$confirm    = GETPOST('confirm', 'alpha'); // Result of a confirmation
$cancel = GETPOST('cancel', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'movementlist';
$toselect   = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list

// Security check
//$result=restrictedArea($user, 'stock', $id, 'entrepot&stock');
$result = restrictedArea($user, 'stock');

$idproduct = GETPOST('idproduct', 'int');
$search_date_startday = GETPOST('search_date_startday', 'int');
$search_date_startmonth = GETPOST('search_date_startmonth', 'int');
$search_date_startyear = GETPOST('search_date_startyear', 'int');
$search_date_endday = GETPOST('search_date_endday', 'int');
$search_date_endmonth = GETPOST('search_date_endmonth', 'int');
$search_date_endyear = GETPOST('search_date_endyear', 'int');
$search_date_start = dol_mktime(0, 0, 0, GETPOST('search_date_startmonth', 'int'), GETPOST('search_date_startday', 'int'), GETPOST('search_date_startyear', 'int'), 'tzuserrel');
$search_date_end = dol_mktime(23, 59, 59, GETPOST('search_date_endmonth', 'int'), GETPOST('search_date_endday', 'int'), GETPOST('search_date_endyear', 'int'), 'tzuserrel');
$search_ref = GETPOST('search_ref', 'alpha');
$search_movement = GETPOST("search_movement");
$search_product_ref = trim(GETPOST("search_product_ref"));
$search_product = trim(GETPOST("search_product"));
$search_warehouse = trim(GETPOST("search_warehouse"));
$search_inventorycode = trim(GETPOST("search_inventorycode"));
$search_user = trim(GETPOST("search_user"));
$search_batch = trim(GETPOST("search_batch"));
$search_qty = trim(GETPOST("search_qty"));
$search_type_mouvement = GETPOST('search_type_mouvement', 'int');
$search_fk_projet=GETPOST("search_fk_projet", 'int');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
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

$pdluoid = GETPOST('pdluoid', 'int');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new MouvementStock($db);
$hookmanager->initHooks(array('movementlist'));
$extrafields = new ExtraFields($db);
$formfile = new FormFile($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

$arrayfields = array(
	'm.rowid'=>array('label'=>"Ref", 'checked'=>1, 'position'=>1),
	'm.datem'=>array('label'=>"Date", 'checked'=>1, 'position'=>2),
	'p.ref'=>array('label'=>"ProductRef", 'checked'=>1, 'css'=>'maxwidth100', 'position'=>3),
	'p.label'=>array('label'=>"ProductLabel", 'checked'=>0, 'position'=>5),
	'm.batch'=>array('label'=>"BatchNumberShort", 'checked'=>1, 'position'=>8, 'enabled'=>(!empty($conf->productbatch->enabled))),
	'pl.eatby'=>array('label'=>"EatByDate", 'checked'=>0, 'position'=>9, 'enabled'=>(!empty($conf->productbatch->enabled))),
	'pl.sellby'=>array('label'=>"SellByDate", 'checked'=>0, 'position'=>10, 'enabled'=>(!empty($conf->productbatch->enabled))),
	'e.ref'=>array('label'=>"Warehouse", 'checked'=>1, 'position'=>100, 'enabled'=>(!$id > 0)), // If we are on specific warehouse, we hide it
	'm.fk_user_author'=>array('label'=>"Author", 'checked'=>0, 'position'=>120),
	'm.inventorycode'=>array('label'=>"InventoryCodeShort", 'checked'=>1, 'position'=>130),
	'm.label'=>array('label'=>"MovementLabel", 'checked'=>1, 'position'=>140),
	'm.type_mouvement'=>array('label'=>"TypeMovement", 'checked'=>0, 'position'=>150),
	'origin'=>array('label'=>"Origin", 'checked'=>1, 'position'=>155),
	'm.fk_projet'=>array('label'=>'Project', 'checked'=>0, 'position'=>180),
	'm.value'=>array('label'=>"Qty", 'checked'=>1, 'position'=>200),
	'm.price'=>array('label'=>"UnitPurchaseValue", 'checked'=>0, 'position'=>210)
	//'m.datec'=>array('label'=>"DateCreation", 'checked'=>0, 'position'=>500),
	//'m.tms'=>array('label'=>"DateModificationShort", 'checked'=>0, 'position'=>500)
);
if (!empty($conf->global->PRODUCT_DISABLE_SELLBY)) {
	unset($arrayfields['pl.sellby']);
}
if (!empty($conf->global->PRODUCT_DISABLE_EATBY)) {
	unset($arrayfields['pl.eatby']);
}

// Security check
if (!$user->rights->stock->mouvement->lire) {
	accessforbidden();
}

$permissiontoread = $user->rights->stock->mouvement->lire;
$permissiontoadd = $user->rights->stock->mouvement->creer;
$permissiontodelete = $user->rights->stock->mouvement->creer; // There is no deletion permission for stock movement as we shoul dnever delete

$usercanread = $user->rights->stock->mouvement->lire;
$usercancreate = $user->rights->stock->mouvement->creer;
$usercandelete = $user->rights->stock->mouvement->creer;

$error = 0;


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list'; $massaction = '';
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
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Do we click on purge search criteria ?
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // Both test are required to be compatible with all browsers
		$search_date_startday = '';
		$search_date_startmonth = '';
		$search_date_startyear = '';
		$search_date_endday = '';
		$search_date_endmonth = '';
		$search_date_endyear = '';
		$search_date_start = '';
		$search_date_end = '';
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
		$search_fk_projet=0;
		$sall = "";
		$toselect = '';
		$search_array_options = array();
	}

	// Mass actions
	$objectclass = 'MouvementStock';
	$objectlabel = 'MouvementStock';
	$uploaddir = $conf->stock->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}

if ($action == 'update_extras') {
	$tmpwarehouse = new Entrepot($db);
	$tmpwarehouse->fetch($id);

	$tmpwarehouse->oldcopy = dol_clone($tmpwarehouse);

	// Fill array 'array_options' with data from update form
	$ret = $extrafields->setOptionalsFromPost(null, $tmpwarehouse, GETPOST('attribute', 'restricthtml'));
	if ($ret < 0) {
		$error++;
	}
	if (!$error) {
		$result = $tmpwarehouse->insertExtraFields();
		if ($result < 0) {
			setEventMessages($tmpwarehouse->error, $tmpwarehouse->errors, 'errors');
			$error++;
		}
	}
	if ($error) {
		$action = 'edit_extras';
	}
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

		if (GETPOST('projectid', 'int')) {
			$origin_element = 'project';
			$origin_id = GETPOST('projectid', 'int');
		}

		if ($product->hasbatch()) {
			$batch = GETPOST('batch_number', 'alphanohtml');

			//$eatby=GETPOST('eatby');
			//$sellby=GETPOST('sellby');
			$eatby = dol_mktime(0, 0, 0, GETPOST('eatbymonth', 'int'), GETPOST('eatbyday', 'int'), GETPOST('eatbyyear', 'int'));
			$sellby = dol_mktime(0, 0, 0, GETPOST('sellbymonth', 'int'), GETPOST('sellbyday', 'int'), GETPOST('sellbyyear', 'int'));

			$result = $product->correct_stock_batch(
				$user,
				$id,
				GETPOST("nbpiece", 'int'),
				GETPOST("mouvement"),
				GETPOST("label", 'san_alpha'),
				GETPOST('unitprice'),
				$eatby,
				$sellby,
				$batch,
				GETPOST('inventorycode', 'alphanohtml'),
				$origin_element,
				$origin_id
			); // We do not change value of stock for a correction
		} else {
			$result = $product->correct_stock(
				$user,
				$id,
				GETPOST("nbpiece", 'int'),
				GETPOST("mouvement"),
				GETPOST("label", 'san_alpha'),
				GETPOST('unitprice'),
				GETPOST('inventorycode', 'alphanohtml'),
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
	$product = new Product($db);
	if (!empty($product_id)) {
		$result = $product->fetch($product_id);
	}

	if (!(GETPOST("id_entrepot_destination", 'int') > 0)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
		$error++;
		$action = 'transfert';
	}
	if (empty($product_id)) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Product")), null, 'errors');
		$action = 'transfert';
	}
	if (!GETPOST("nbpiece", 'int')) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NumberOfUnit")), null, 'errors');
		$error++;
		$action = 'transfert';
	}
	if ($id == GETPOST("id_entrepot_destination", 'int')) {
		setEventMessages($langs->trans("ErrorSrcAndTargetWarehouseMustDiffers"), null, 'errors');
		$error++;
		$action = 'transfert';
	}

	if (!empty($conf->productbatch->enabled)) {
		$product = new Product($db);
		$result = $product->fetch($product_id);

		if ($product->hasbatch() && !GETPOST("batch_number")) {
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
					$batch = GETPOST('batch_number', 'alphanohtml');
					$eatby = $d_eatby;
					$sellby = $d_sellby;
				}

				if (!$error) {
					// Remove stock
					$result1 = $product->correct_stock_batch(
						$user,
						$srcwarehouseid,
						GETPOST("nbpiece", 'int'),
						1,
						GETPOST("label", 'san_alpha'),
						$pricesrc,
						$eatby,
						$sellby,
						$batch,
						GETPOST('inventorycode')
					);
					// Add stock
					$result2 = $product->correct_stock_batch(
						$user,
						GETPOST("id_entrepot_destination", 'int'),
						GETPOST("nbpiece", 'int'),
						0,
						GETPOST("label", 'san_alpha'),
						$pricedest,
						$eatby,
						$sellby,
						$batch,
						GETPOST('inventorycode', 'alphanohtml')
					);
				}
			} else {
				// Remove stock
				$result1 = $product->correct_stock(
					$user,
					$id,
					GETPOST("nbpiece"),
					1,
					GETPOST("label", 'san_alpha'),
					$pricesrc,
					GETPOST('inventorycode', 'alphanohtml')
				);

				// Add stock
				$result2 = $product->correct_stock(
					$user,
					GETPOST("id_entrepot_destination"),
					GETPOST("nbpiece"),
					0,
					GETPOST("label", 'san_alpha'),
					$pricedest,
					GETPOST('inventorycode', 'alphanohtml')
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
 * View
 */

$productlot = new ProductLot($db);
$productstatic = new Product($db);
$warehousestatic = new Entrepot($db);
$movement = new MouvementStock($db);
$userstatic = new User($db);
$form = new Form($db);
$formproduct = new FormProduct($db);
if (!empty($conf->projet->enabled)) {
	$formproject = new FormProjets($db);
}

$sql = "SELECT p.rowid, p.ref as product_ref, p.label as produit, p.tosell, p.tobuy, p.tobatch, p.fk_product_type as type, p.entity,";
$sql .= " e.ref as warehouse_ref, e.rowid as entrepot_id, e.lieu, e.fk_parent, e.statut,";
$sql .= " m.rowid as mid, m.value as qty, m.datem, m.fk_user_author, m.label, m.inventorycode, m.fk_origin, m.origintype,";
$sql .= " m.batch, m.price,";
$sql .= " m.type_mouvement,";
$sql .= " m.fk_projet as fk_project,";
$sql .= " pl.rowid as lotid, pl.eatby, pl.sellby,";
$sql .= " u.login, u.photo, u.lastname, u.firstname, u.email as user_email, u.statut as user_status";
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
	}
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " FROM ".MAIN_DB_PREFIX."entrepot as e,";
$sql .= " ".MAIN_DB_PREFIX."product as p,";
$sql .= " ".MAIN_DB_PREFIX."stock_mouvement as m";
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
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
if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
	$sql .= " AND p.fk_product_type = 0";
}
if ($id > 0) {
	$sql .= " AND e.rowid = ".((int) $id);
}
if (!empty($search_date_start)) {
	$sql .= " AND m.datem >= '" . $db->idate($search_date_start) . "'";
}
if (!empty($search_date_end)) {
	$sql .= " AND m.datem <= '" . $db->idate($search_date_end) . "'";
}
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
if (!empty($product_id)) {
	$sql .= natural_search('p.rowid', $product_id);
}
if (!empty($search_fk_projet) && $search_fk_projet != '-1') {
	$sql .= natural_search('m.fk_projet', $search_fk_projet);
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
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);

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
	llxHeader("", $texte, $help_url);

	/*
	 * Show tab only if we ask a particular warehouse
	 */
	if ($object->id > 0) {
		$head = stock_prepare_head($object);

		print dol_get_fiche_head($head, 'movements', $langs->trans("Warehouse"), -1, 'stock');


		$linkback = '<a href="'.DOL_URL_ROOT.'/product/stock/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '<div class="refidno">';
		$morehtmlref .= $langs->trans("LocationSummary").' : '.$object->lieu;

		// Project
		if (!empty($conf->projet->enabled)) {
			$langs->load("projects");
			$morehtmlref .= '<br>'.img_picto('', 'project').' '.$langs->trans('Project').' ';
			if ($usercancreate && 1 == 2) {
				if ($action != 'classify') {
					$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
				}
				if ($action == 'classify') {
					$projectid = $object->fk_project;
					$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
					$morehtmlref .= '<input type="hidden" name="action" value="classin">';
					$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
					$morehtmlref .= $formproject->select_projects(($socid > 0 ? $socid : -1), $projectid, 'projectid', 0, 0, 1, 1, 0, 0, 0, '', 1, 0, 'maxwidth500');
					$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
					$morehtmlref .= '</form>';
				} else {
					$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
				}
			} else {
				if (!empty($object->fk_project)) {
					$proj = new Project($db);
					$proj->fetch($object->fk_project);
					$morehtmlref .= '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'" title="'.$langs->trans('ShowProject').'">';
					$morehtmlref .= $proj->ref;
					$morehtmlref .= '</a>';
				} else {
					$morehtmlref .= '';
				}
			}
		}
		$morehtmlref .= '</div>';

		$shownav = 1;
		if ($user->socid && !in_array('stock', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) {
			$shownav = 0;
		}

		dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref', 'ref', $morehtmlref);


		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border centpercent tableforfield">';

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
		print '<div class="ficheaddleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border centpercent tableforfield">';

		// Value
		print '<tr><td class="titlefield">'.$langs->trans("EstimatedStockValueShort").'</td><td>';
		print price((empty($calcproducts['value']) ? '0' : price2num($calcproducts['value'], 'MT')), 0, $langs, 0, -1, -1, $conf->currency);
		print "</td></tr>";

		// Last movement
		$sql = "SELECT MAX(m.datem) as datem";
		$sql .= " FROM ".MAIN_DB_PREFIX."stock_mouvement as m";
		$sql .= " WHERE m.fk_entrepot = ".((int) $object->id);
		$resqlbis = $db->query($sql);
		if ($resqlbis) {
			$objbis = $db->fetch_object($resqlbis);
			$lastmovementdate = $db->jdate($objbis->datem);
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

		// Other attributes
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

		// Categories
		if ($conf->categorie->enabled) {
			print '<tr><td valign="middle">'.$langs->trans("Categories").'</td><td colspan="3">';
			print $form->showCategories($object->id, Categorie::TYPE_WAREHOUSE, 1);
			print "</td></tr>";
		}

		print "</table>";

		print '</div>';
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

		if ($user->rights->stock->mouvement->creer) {
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&action=correction">'.$langs->trans("CorrectStock").'</a>';
		}

		if ($user->rights->stock->mouvement->creer) {
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&action=transfert">'.$langs->trans("TransferStock").'</a>';
		}

		print '</div><br>';
	}

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.urlencode($contextpage);
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.urlencode($limit);
	}
	if ($id > 0) {
		$param .= '&id='.urlencode($id);
	}
	if ($search_date_startday) {
		$param .= '&search_date_startday='.urlencode($search_date_startday);
	}
	if ($search_date_startmonth) {
		$param .= '&search_date_startmonth='.urlencode($search_date_startmonth);
	}
	if ($search_date_startyear) {
		$param .= '&search_date_startyear='.urlencode($search_date_startyear);
	}
	if ($search_date_endday) {
		$param .= '&search_date_endday='.urlencode($search_date_endday);
	}
	if ($search_date_endmonth) {
		$param .= '&search_date_endmonth='.urlencode($search_date_endmonth);
	}
	if ($search_date_endyear) {
		$param .= '&search_date_endyear='.urlencode($search_date_endyear);
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
		$param .= '&idproduct='.urlencode($idproduct);
	}
	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions = array(
	//    'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
	//    'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
	);
	// By default, we should never accept deletion of stock movement.
	if (!empty($conf->global->STOCK_ALLOW_DELETE_OF_MOVEMENT) && $permissiontodelete) {
		$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
	}
	if (GETPOST('nomassaction', 'int') || in_array($massaction, array('presend', 'predelete'))) {
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
	print '<input type="hidden" name="type" value="'.$type.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
	if ($id > 0) {
		print '<input type="hidden" name="id" value="'.$id.'">';
	}

	if ($id > 0) {
		print_barre_liste($texte, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'movement', 0, '', '', $limit, 0, 0, 1);
	} else {
		print_barre_liste($texte, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'movement', 0, '', '', $limit, 0, 0, 1);
	}

	// Add code for pre mass action (confirmation or email presend form)
	$topicmail = "SendStockMovement";
	$modelmail = "movementstock";
	$objecttmp = new MouvementStock($db);
	$trackid = 'mov'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	if ($sall) {
		foreach ($fieldstosearchall as $key => $val) {
			$fieldstosearchall[$key] = $langs->trans($val);
		}
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall).join(', ', $fieldstosearchall).'</div>';
	}

	$moreforfilter = '';

	$parameters = array('arrayfields'=>&$arrayfields);
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
	if (! empty($arrayfields['m.datem']['checked'])) {
		// Date
		print '<td class="liste_titre center">';
		print '<div class="nowrap">';
		print $form->selectDate($search_date_start?$search_date_start:-1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'), 'tzuserrel');
		print '</div>';
		print '<div class="nowrap">';
		print $form->selectDate($search_date_end?$search_date_end:-1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'), 'tzuserrel');
		print '</div>';
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
	if (!empty($arrayfields['origin']['checked'])) {
		// Origin of movement
		print '<td class="liste_titre left">';
		print '&nbsp; ';
		print '</td>';
	}
	if (!empty($arrayfields['m.fk_projet']['checked'])) {
		// fk_project
		print '<td class="liste_titre" align="left">';
		print '&nbsp; ';
		print '</td>';
	}
	if (!empty($arrayfields['m.type_mouvement']['checked'])) {
		// Type of movement
		print '<td class="liste_titre center">';
		//print '<input class="flat" type="text" size="3" name="search_type_mouvement" value="'.dol_escape_htmltag($search_type_mouvement).'">';
		print '<select id="search_type_mouvement" name="search_type_mouvement" class="maxwidth150">';
		print '<option value="" '.(($search_type_mouvement == "") ? 'selected="selected"' : '').'>&nbsp;</option>';
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
	if (!empty($arrayfields['m.value']['checked'])) {
		// Qty
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_qty" value="'.dol_escape_htmltag($search_qty).'">';
		print '</td>';
	}
	if (!empty($arrayfields['m.price']['checked'])) {
		// Price
		print '<td class="liste_titre" align="left">';
		print '&nbsp; ';
		print '</td>';
	}

	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields);
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
		print_liste_field_titre($arrayfields['m.datem']['label'], $_SERVER["PHP_SELF"], 'm.datem', '', $param, '', $sortfield, $sortorder, 'center ');
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
	if (!empty($arrayfields['origin']['checked'])) {
		print_liste_field_titre($arrayfields['origin']['label'], $_SERVER["PHP_SELF"], "", "", $param, "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['m.fk_projet']['checked'])) {
		print_liste_field_titre($arrayfields['m.fk_projet']['label'], $_SERVER["PHP_SELF"], "m.fk_projet", "", $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['m.type_mouvement']['checked'])) {
		print_liste_field_titre($arrayfields['m.type_mouvement']['label'], $_SERVER["PHP_SELF"], "m.type_mouvement", "", $param, '', $sortfield, $sortorder, 'center ');
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
	$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (!empty($arrayfields['m.datec']['checked'])) {
		print_liste_field_titre($arrayfields['p.datec']['label'], $_SERVER["PHP_SELF"], "p.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	}
	if (!empty($arrayfields['m.tms']['checked'])) {
		print_liste_field_titre($arrayfields['p.tms']['label'], $_SERVER["PHP_SELF"], "p.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	}
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	print "</tr>\n";


	$arrayofuniqueproduct = array();

	$i = 0;
	$totalarray = array();
	while ($i < min($num, $limit)) {
		$objp = $db->fetch_object($resql);

		$userstatic->id = $objp->fk_user_author;
		$userstatic->login = $objp->login;
		$userstatic->lastname = $objp->lastname;
		$userstatic->firstname = $objp->firstname;
		$userstatic->photo = $objp->photo;
		$userstatic->email = $objp->user_email;
		$userstatic->statut = $objp->user_status;

		$productstatic->id = $objp->rowid;
		$productstatic->ref = $objp->product_ref;
		$productstatic->label = $objp->produit;
		$productstatic->type = $objp->type;
		$productstatic->entity = $objp->entity;
		$productstatic->status = $objp->tosell;
		$productstatic->status_buy = $objp->tobuy;
		$productstatic->status_batch = $objp->tobatch;

		$productlot->id = $objp->lotid;
		$productlot->batch = $objp->batch;
		$productlot->eatby = $objp->eatby;
		$productlot->sellby = $objp->sellby;

		$warehousestatic->id = $objp->entrepot_id;
		$warehousestatic->ref = $objp->warehouse_ref;
		$warehousestatic->label = $objp->warehouse_ref;
		$warehousestatic->lieu = $objp->lieu;
		$warehousestatic->fk_parent = $objp->fk_parent;
		$warehousestatic->statut = $objp->statut;

		$movement->type = $objp->type_mouvement;

		$arrayofuniqueproduct[$objp->rowid] = $objp->produit;
		if (!empty($objp->fk_origin)) {
			$origin = $movement->get_origin($objp->fk_origin, $objp->origintype);
		} else {
			$origin = '';
		}

		print '<tr class="oddeven">';
		// Id movement
		if (!empty($arrayfields['m.rowid']['checked'])) {
			print '<td class="nowraponall">';
			print img_picto($langs->trans("StockMovement"), 'movement', 'class="pictofixedwidth"');
			print $objp->mid;
			print '</td>'; // This is primary key not movement ref
		}
		if (!empty($arrayfields['m.datem']['checked'])) {
			// Date
			print '<td class="nowraponall center">'.dol_print_date($db->jdate($objp->datem), 'dayhour', 'tzuserrel').'</td>';
		}
		if (!empty($arrayfields['p.ref']['checked'])) {
			// Product ref
			print '<td class="nowraponall">';
			print $productstatic->getNomUrl(1, 'stock', 16);
			print "</td>\n";
		}
		if (!empty($arrayfields['p.label']['checked'])) {
			// Product label
			print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($productstatic->label).'">';
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
			print '<td class="tdoverflowmax100">';
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
			print '<td><a href="'.$_SERVER["PHP_SELF"].'?search_inventorycode='.urlencode('^'.$objp->inventorycode.'$').'&search_type_mouvement='.urlencode($objp->type_mouvement).'">'.$objp->inventorycode.'</a></td>';
		}
		if (!empty($arrayfields['m.label']['checked'])) {
			// Label of movement
			print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($objp->label).'">'.$objp->label.'</td>';
		}
		if (!empty($arrayfields['origin']['checked'])) {
			// Origin of movement
			print '<td class="nowraponall">'.$origin.'</td>';
		}
		if (!empty($arrayfields['m.fk_projet']['checked'])) {
			// fk_project
			print '<td>';
			if ($objp->fk_project != 0) {
				print $movement->get_origin($objp->fk_project, 'project');
			}
			print '</td>';
		}
		if (!empty($arrayfields['m.type_mouvement']['checked'])) {
			// Type of movement
			print '<td class="center">';
			print $movement->getTypeMovement();
			print '</td>';
		}
		if (!empty($arrayfields['m.value']['checked'])) {
			// Qty
			print '<td class="right">';
			if ($objp->qty > 0) {
				print '<span class="stockmovemententry">';
				print '+';
				print $objp->qty;
				print '</span>';
			} else {
				print '<span class="stockmovementexit">';
				print $objp->qty;
				print '</span>';
			}
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

		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters = array('arrayfields'=>$arrayfields, 'objp'=>$objp, 'i'=>$i, 'totalarray'=>&$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;

		// Action column
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($objp->mid, $arrayofselected)) {
				$selected = 1;
			}
			print '<input id="cb'.$objp->mid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$objp->mid.'"'.($selected ? ' checked="checked"' : '').'>';
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
		$datebefore = dol_get_first_day($year ? $year : strftime("%Y", time()), $month ? $month : 1, true);
		$dateafter = dol_get_last_day($year ? $year : strftime("%Y", time()), $month ? $month : 12, true);
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


// End of page
llxFooter();
$db->close();
