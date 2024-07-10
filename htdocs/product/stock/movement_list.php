<?php
/* Copyright (C) 2001-2006	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2015		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2018-2022	Ferran Marcet			<fmarcet@2byte.es>
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
 *	\file       htdocs/product/stock/movement_list.php
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
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'orders'));
if (isModEnabled('productbatch')) {
	$langs->load("productbatch");
}

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$confirm    = GETPOST('confirm', 'alpha'); // Result of a confirmation
$cancel = GETPOST('cancel', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php')); // To manage different context of search
$toselect   = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$backtopage = GETPOST("backtopage", "alpha");
$optioncss  = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')
$show_files = GETPOST('show_files', 'aZ');
$mode       = GETPOST('mode', 'aZ'); // The output mode ('list', 'kanban', 'hierarchy', 'calendar', ...)

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$msid = GETPOSTINT('msid');
$idproduct = GETPOST('idproduct', 'intcomma');
$product_id = GETPOST("product_id", 'intcomma');
$show_files = GETPOSTINT('show_files');

$search_all = trim((GETPOST('search_all', 'alphanohtml') != '') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$search_date_startday = GETPOSTINT('search_date_startday');
$search_date_startmonth = GETPOSTINT('search_date_startmonth');
$search_date_startyear = GETPOSTINT('search_date_startyear');
$search_date_endday = GETPOSTINT('search_date_endday');
$search_date_endmonth = GETPOSTINT('search_date_endmonth');
$search_date_endyear = GETPOSTINT('search_date_endyear');
$search_date_start = dol_mktime(0, 0, 0, GETPOSTINT('search_date_startmonth'), GETPOSTINT('search_date_startday'), GETPOSTINT('search_date_startyear'), 'tzuserrel');
$search_date_end = dol_mktime(23, 59, 59, GETPOSTINT('search_date_endmonth'), GETPOSTINT('search_date_endday'), GETPOSTINT('search_date_endyear'), 'tzuserrel');
$search_ref = GETPOST('search_ref', 'alpha');
$search_movement = GETPOST("search_movement");
$search_product_ref = trim(GETPOST("search_product_ref"));
$search_product = trim(GETPOST("search_product"));
$search_warehouse = trim(GETPOST("search_warehouse"));
$search_inventorycode = trim(GETPOST("search_inventorycode"));
$search_user = trim(GETPOST("search_user"));
$search_batch = trim(GETPOST("search_batch"));
$search_qty = trim(GETPOST("search_qty"));
$search_type_mouvement = GETPOST('search_type_mouvement');
$search_fk_project = GETPOST("search_fk_project");

$type = GETPOSTINT("type");

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (!$sortfield) {
	$sortfield = "m.datem";
}
if (!$sortorder) {
	$sortorder = "DESC";
}

$pdluoid = GETPOSTINT('pdluoid');

// Initialize technical objects
$object = new MouvementStock($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->stock->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array($contextpage)); 	// Note that conf->hooks_modules contains array of activated contexes

$formfile = new FormFile($db);

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

$arrayfields = array(
	'm.rowid' => array('label' => "Ref", 'checked' => 1, 'position' => 1),
	'm.datem' => array('label' => "Date", 'checked' => 1, 'position' => 2),
	'p.ref' => array('label' => "ProductRef", 'checked' => 1, 'css' => 'maxwidth100', 'position' => 3),
	'p.label' => array('label' => "ProductLabel", 'checked' => 0, 'position' => 5),
	'm.batch' => array('label' => "BatchNumberShort", 'checked' => 1, 'position' => 8, 'enabled' => (isModEnabled('productbatch'))),
	'pl.eatby' => array('label' => "EatByDate", 'checked' => 0, 'position' => 9, 'enabled' => (isModEnabled('productbatch'))),
	'pl.sellby' => array('label' => "SellByDate", 'checked' => 0, 'position' => 10, 'enabled' => (isModEnabled('productbatch'))),
	'e.ref' => array('label' => "Warehouse", 'checked' => 1, 'position' => 100, 'enabled' => (!($id > 0))), // If we are on specific warehouse, we hide it
	'm.fk_user_author' => array('label' => "Author", 'checked' => 0, 'position' => 120),
	'm.inventorycode' => array('label' => "InventoryCodeShort", 'checked' => 1, 'position' => 130),
	'm.label' => array('label' => "MovementLabel", 'checked' => 1, 'position' => 140),
	'm.type_mouvement' => array('label' => "TypeMovement", 'checked' => 0, 'position' => 150),
	'origin' => array('label' => "Origin", 'checked' => 1, 'position' => 155),
	'm.fk_projet' => array('label' => 'Project', 'checked' => 0, 'position' => 180),
	'm.value' => array('label' => "Qty", 'checked' => 1, 'position' => 200),
	'm.price' => array('label' => "UnitPurchaseValue", 'checked' => 0, 'position' => 210, 'enabled' => (!getDolGlobalInt('STOCK_MOVEMENT_LIST_HIDE_UNIT_PRICE')))
	//'m.datec'=>array('label'=>"DateCreation", 'checked'=>0, 'position'=>500),
	//'m.tms'=>array('label'=>"DateModificationShort", 'checked'=>0, 'position'=>500)
);

include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

if (getDolGlobalString('PRODUCT_DISABLE_SELLBY')) {
	unset($arrayfields['pl.sellby']);
}
if (getDolGlobalString('PRODUCT_DISABLE_EATBY')) {
	unset($arrayfields['pl.eatby']);
}


$tmpwarehouse = new Entrepot($db);
if ($id > 0 || !empty($ref)) {
	$tmpwarehouse->fetch($id, $ref);
	$id = $tmpwarehouse->id;
}


// Security check
//$result=restrictedArea($user, 'stock', $id, 'entrepot&stock');
$result = restrictedArea($user, 'stock');

// Security check
if (!$user->hasRight('stock', 'mouvement', 'lire')) {
	accessforbidden();
}

$uploaddir = $conf->stock->dir_output.'/movements';

$permissiontoread = $user->hasRight('stock', 'mouvement', 'lire');
$permissiontoadd = $user->hasRight('stock', 'mouvement', 'creer');
$permissiontodelete = $user->hasRight('stock', 'mouvement', 'creer'); // There is no deletion permission for stock movement as we should never delete

$usercanread = $user->hasRight('stock', 'mouvement', 'lire');
$usercancreate = $user->hasRight('stock', 'mouvement', 'creer');
$usercandelete = $user->hasRight('stock', 'mouvement', 'creer');

$error = 0;


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
		$search_fk_project = "";
		$search_all = "";
		$toselect = array();
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions
	$objectclass = 'MouvementStock';
	$objectlabel = 'MouvementStock';

	if (!$error && $massaction == "builddoc" && $permissiontoread && !GETPOST('button_search')) {
		if (empty($diroutputmassaction)) {
			dol_print_error(null, 'include of actions_massactions.inc.php is done but var $diroutputmassaction was not defined');
			exit;
		}

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

		$objecttmp = new MouvementStock($db);
		$listofobjectid = array();
		foreach ($toselect as $toselectid) {
			$objecttmp = new MouvementStock($db); // must create new instance because instance is saved into $listofobjectref array for future use
			$result = $objecttmp->fetch($toselectid);
			if ($result > 0) {
				$listofobjectid[$toselectid] = $toselectid;
			}
		}

		$arrayofinclusion = array();
		foreach ($listofobjectref as $tmppdf) {
			$arrayofinclusion[] = '^'.preg_quote(dol_sanitizeFileName($tmppdf), '/').'\.pdf$';
		}
		foreach ($listofobjectref as $tmppdf) {
			$arrayofinclusion[] = '^'.preg_quote(dol_sanitizeFileName($tmppdf), '/').'_[a-zA-Z0-9-_]+\.pdf$'; // To include PDF generated from ODX files
		}
		$listoffiles = dol_dir_list($uploaddir, 'all', 1, implode('|', $arrayofinclusion), '\.meta$|\.png', 'date', SORT_DESC, 0, true);

		// Define output language (Here it is not used because we do only merging existing PDF)
		$outputlangs = $langs;
		$newlang = '';
		if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
			$newlang = GETPOST('lang_id', 'aZ09');
		}
		//elseif (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && is_object($objecttmp->thirdparty)) {		// On massaction, we can have several values for $objecttmp->thirdparty
		//	$newlang = $objecttmp->thirdparty->default_lang;
		//}
		if (!empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}

		// Create output dir if not exists
		dol_mkdir($diroutputmassaction);

		// Defined name of merged file
		$filename = strtolower(dol_sanitizeFileName($langs->transnoentities($objectlabel)));
		$filename = preg_replace('/\s/', '_', $filename);

		// Save merged file
		/*
		 if ($year) {
		 $filename .= '_'.$year;
		 }
		 if ($month) {
		 $filename .= '_'.$month;
		 }
		 */
		$now = dol_now();
		$file = $diroutputmassaction.'/'.$filename.'_'.dol_print_date($now, 'dayhourlog').'.pdf';


		// Create PDF
		// TODO Create the pdf including list of movement ids found into $listofobjectid
		// ...


		if (!$error) {
			$langs->load("exports");
			setEventMessage($langs->trans('FeatureNotYetAvailable'));
			//setEventMessages($langs->trans('FileSuccessfullyBuilt', $filename.'_'.dol_print_date($now, 'dayhourlog')), null, 'mesgs');
		}

		$massaction = '';
		$action = '';
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}

if ($action == 'update_extras' && $permissiontoadd) {
	$tmpwarehouse->oldcopy = dol_clone($tmpwarehouse, 2);

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
if ($action == "correct_stock" && $permissiontoadd) {
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
			$batch = GETPOST('batch_number', 'alphanohtml');

			//$eatby=GETPOST('eatby');
			//$sellby=GETPOST('sellby');
			$eatby = dol_mktime(0, 0, 0, GETPOSTINT('eatbymonth'), GETPOSTINT('eatbyday'), GETPOSTINT('eatbyyear'));
			$sellby = dol_mktime(0, 0, 0, GETPOSTINT('sellbymonth'), GETPOSTINT('sellbyday'), GETPOSTINT('sellbyyear'));

			$result = $product->correct_stock_batch(
				$user,
				$id,
				GETPOSTINT("nbpiece"),
				GETPOSTINT("mouvement"),
				GETPOST("label", 'alphanohtml'),
				price2num(GETPOST('unitprice'), 'MT'),
				$eatby,
				$sellby,
				$batch,
				GETPOST('inventorycode', 'alphanohtml'),
				$origin_element,
				$origin_id,
				0,
				$extrafields
			); // We do not change value of stock for a correction
		} else {
			$result = $product->correct_stock(
				$user,
				$id,
				GETPOSTINT("nbpiece"),
				GETPOSTINT("mouvement"),
				GETPOST("label", 'alphanohtml'),
				price2num(GETPOST('unitprice'), 'MT'),
				GETPOST('inventorycode', 'alphanohtml'),
				$origin_element,
				$origin_id,
				0,
				$extrafields
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
if ($action == "transfert_stock" && $permissiontoadd && !$cancel) {
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

		if ($product->hasbatch() && !GETPOST("batch_number")) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("batch_number")), null, 'errors');
			$error++;
			$action = 'transfert';
		}
	}

	if (!$error) {
		if ($id) {
			$warehouse = new Entrepot($db);
			$result = $warehouse->fetch($id);

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
						GETPOSTINT("nbpiece"),
						1,
						GETPOST("label", 'san_alpha'),
						$pricesrc,
						$eatby,
						$sellby,
						$batch,
						GETPOST('inventorycode'),
						'',
						null,
						0,
						$extrafields
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
						GETPOST('inventorycode', 'alphanohtml'),
						'',
						null,
						0,
						$extrafields
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
					GETPOST('inventorycode', 'alphanohtml'),
					'',
					null,
					0,
					$extrafields
				);

				// Add stock
				$result2 = $product->correct_stock(
					$user,
					GETPOST("id_entrepot_destination"),
					GETPOST("nbpiece"),
					0,
					GETPOST("label", 'san_alpha'),
					$pricedest,
					GETPOST('inventorycode', 'alphanohtml'),
					'',
					null,
					0,
					$extrafields
				);
			}
			if (!$error && $result1 >= 0 && $result2 >= 0) {
				$db->commit();

				if ($backtopage) {
					header("Location: ".$backtopage);
					exit;
				} else {
					header("Location: movement_list.php?id=".$warehouse->id);
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

// reverse movement of stock
if ($action == 'confirm_reverse' && $confirm == "yes" && $permissiontoadd) {
	$toselect = array_map('intval', $toselect);

	$sql = "SELECT rowid, label, inventorycode, datem";
	$sql .= " FROM ".MAIN_DB_PREFIX."stock_mouvement";
	$sql .= " WHERE rowid IN (";
	foreach ($toselect as $id) {
		$sql .= ((int) $id).",";
	}
	$sql = rtrim($sql, ',');
	$sql .= ")";

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			$object->fetch($obj->rowid);
			$reverse = $object->reverseMouvement();
			if ($reverse < 0) {
				$hasError = true;
			} else {
				$hasSuccess = true;
			}
			$i++;
		}
		if ($hasError) {
			setEventMessages($langs->trans("WarningAlreadyReverse", $langs->transnoentities($idAlreadyReverse)), null, 'warnings');
		}
		if ($hasSuccess) {
			setEventMessages($langs->trans("ReverseConfirmed"), null);
		}
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
}

/*
 * View
 */

$form = new Form($db);
$formproduct = new FormProduct($db);
if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}
$productlot = new Productlot($db);
$productstatic = new Product($db);
$warehousestatic = new Entrepot($db);

$userstatic = new User($db);

$now = dol_now();

// Build and execute select
// --------------------------------------------------------------------
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
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key." as options_".$key : '');
	}
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql = preg_replace('/,\s*$/', '', $sql);

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= " FROM ".MAIN_DB_PREFIX."entrepot as e,";
$sql .= " ".MAIN_DB_PREFIX."product as p,";
$sql .= " ".MAIN_DB_PREFIX."stock_mouvement as m";
if (!empty($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (m.rowid = ef.fk_object)";
}
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON m.fk_user_author = u.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_lot as pl ON m.batch = pl.batch AND m.fk_product = pl.fk_product";

// Add table from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

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
if (!empty($product_id) && $product_id != '-1') {
	$sql .= natural_search('p.rowid', $product_id);
}
if (!empty($search_fk_project) && $search_fk_project != '-1') {
	$sql .= natural_search('m.fk_projet', $search_fk_project);
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
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	/* The fast and low memory method to get and count full list converts the sql into a sql count */
	$sqlforcount = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
	$sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
	$resql = $db->query($sqlforcount);
	if ($resql) {
		$objforcount = $db->fetch_object($resql);
		$nbtotalofrecords = $objforcount->nbtotalofrecords;
	} else {
		dol_print_error($db);
	}

	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller than the paging size (filtering), goto and load page 0
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


$product = new Product($db);
$warehouse = new Entrepot($db);

if ($idproduct > 0) {
	$product->fetch($idproduct);
}
if ($id > 0 || $ref) {
	$result = $warehouse->fetch($id, $ref);
	if ($result < 0) {
		dol_print_error($db);
	}
}


// Output page
// --------------------------------------------------------------------

$i = 0;
$help_url = 'EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
if ($msid) {
	$title = $langs->trans('StockMovementForId', $msid);
} else {
	$title = $langs->trans("ListOfStockMovements");
	if ($id) {
		if (!empty($warehouse->ref)) {
			$title .= ' ('.$warehouse->ref.')';
		} else {
			$title .= ' ('.$langs->trans("ForThisWarehouse").')';
		}
	}
}


// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'bodyforlist mod-product page-stock_movement_list');

/*
 * Show tab only if we ask a particular warehouse
 */
if ($warehouse->id > 0) {
	$head = stock_prepare_head($warehouse);

	print dol_get_fiche_head($head, 'movements', $langs->trans("Warehouse"), -1, 'stock');


	$linkback = '<a href="'.DOL_URL_ROOT.'/product/stock/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= $langs->trans("LocationSummary").' : '.$warehouse->lieu;

	// Project
	if (isModEnabled('project')) {
		$langs->load("projects");
		$morehtmlref .= '<br>'.img_picto('', 'project').' '.$langs->trans('Project').' ';
		if ($usercancreate && 1 == 2) {
			if ($action != 'classify') {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$warehouse->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
			}
			if ($action == 'classify') {
				$projectid = $warehouse->fk_project;
				$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$warehouse->id.'">';
				$morehtmlref .= '<input type="hidden" name="action" value="classin">';
				$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
				$morehtmlref .= $formproject->select_projects(($socid > 0 ? $socid : -1), $projectid, 'projectid', 0, 0, 1, 1, 0, 0, 0, '', 1, 0, 'maxwidth500');
				$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
				$morehtmlref .= '</form>';
			} else {
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$warehouse->id, $warehouse->socid, $warehouse->fk_project, 'none', 0, 0, 0, 1, '', 'maxwidth300');
			}
		} else {
			if (!empty($warehouse->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($warehouse->fk_project);
				$morehtmlref .= ' : '.$proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref .= ' - '.$proj->title;
				}
			} else {
				$morehtmlref .= '';
			}
		}
	}
	$morehtmlref .= '</div>';

	$shownav = 1;
	if ($user->socid && !in_array('stock', explode(',', getDolGlobalString('MAIN_MODULES_FOR_EXTERNAL')))) {
		$shownav = 0;
	}

	dol_banner_tab($warehouse, 'ref', $linkback, $shownav, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent tableforfield">';

	print '<tr>';

	// Description
	print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>'.dol_htmlentitiesbr($warehouse->description).'</td></tr>';

	$calcproductsunique = $warehouse->nb_different_products();
	$calcproducts = $warehouse->nb_products();

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

	print '<table class="border centpercent tableforfield">';

	// Value
	print '<tr><td class="titlefield">'.$langs->trans("EstimatedStockValueShort").'</td><td>';
	print price((empty($calcproducts['value']) ? '0' : price2num($calcproducts['value'], 'MT')), 0, $langs, 0, -1, -1, $conf->currency);
	print "</td></tr>";

	// Last movement
	$sql = "SELECT MAX(m.datem) as datem";
	$sql .= " FROM ".MAIN_DB_PREFIX."stock_mouvement as m";
	$sql .= " WHERE m.fk_entrepot = ".((int) $warehouse->id);
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
	if (isModEnabled('category')) {
		print '<tr><td valign="middle">'.$langs->trans("Categories").'</td><td colspan="3">';
		print $form->showCategories($warehouse->id, Categorie::TYPE_WAREHOUSE, 1);
		print "</td></tr>";
	}

	print "</table>";

	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();
}


// Correct stock
if ($action == "correction") {
	include DOL_DOCUMENT_ROOT.'/product/stock/tpl/stockcorrection.tpl.php';
	print '<br>';
}

// Transfer of units
if ($action == "transfert") {
	include DOL_DOCUMENT_ROOT.'/product/stock/tpl/stocktransfer.tpl.php';
	print '<br>';
}


// Action bar
if ((empty($action) || $action == 'list') && $id > 0) {
	print "<div class=\"tabsAction\">\n";

	$parameters = array();
	$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $warehouse, $action); // Note that $action and $warehouse may have been
	// modified by hook
	if (empty($reshook)) {
		if ($user->hasRight('stock', 'mouvement', 'creer')) {
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&action=correction&token='.newToken().'">'.$langs->trans("CorrectStock").'</a>';
		}

		if ($user->hasRight('stock', 'mouvement', 'creer')) {
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&action=transfert&token='.newToken().'">'.$langs->trans("TransferStock").'</a>';
		}
	}

	print '</div><br>';
}

$arrayofselected = is_array($toselect) ? $toselect : array();

$param = '';
if (!empty($mode)) {
	$param .= '&mode='.urlencode($mode);
}
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($id > 0) {
	$param .= '&id='.urlencode((string) ($id));
}
if ($show_files) {
	$param .= '&show_files='.urlencode((string) ($show_files));
}
if ($search_date_startday) {
	$param .= '&search_date_startday='.urlencode((string) ($search_date_startday));
}
if ($search_date_startmonth) {
	$param .= '&search_date_startmonth='.urlencode((string) ($search_date_startmonth));
}
if ($search_date_startyear) {
	$param .= '&search_date_startyear='.urlencode((string) ($search_date_startyear));
}
if ($search_date_endday) {
	$param .= '&search_date_endday='.urlencode((string) ($search_date_endday));
}
if ($search_date_endmonth) {
	$param .= '&search_date_endmonth='.urlencode((string) ($search_date_endmonth));
}
if ($search_date_endyear) {
	$param .= '&search_date_endyear='.urlencode((string) ($search_date_endyear));
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
if ($search_fk_project != '' && $search_fk_project != '-1') {
	$param .= '&search_fk_project='.urlencode((string) ($search_fk_project));
}
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';
// Add $param from hooks
$parameters = array('param' => &$param);
$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $warehouse, $action); // Note that $action and $warehouse may have been modified by hook
$param .= $hookmanager->resPrint;

// List of mass actions available
$arrayofmassactions = array();
if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
	$arrayofmassactions['builddoc'] = img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("GeneratePDF");
}
// By default, we should never accept deletion of stock movement
if (getDolGlobalString('STOCK_ALLOW_DELETE_OF_MOVEMENT') && $permissiontodelete) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if (!empty($permissiontoadd)) {
	$arrayofmassactions['prereverse'] = img_picto('', 'add', 'class="pictofixedwidth"').$langs->trans("Reverse");
}
if (GETPOSTINT('nomassaction') || in_array($massaction, array('presend', 'predelete', 'prereverse'))) {
	$arrayofmassactions = array();
}

$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="type" value="'.$type.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="mode" value="'.$mode.'">';
if ($id > 0) {
	print '<input type="hidden" name="id" value="'.$id.'">';
}


$newcardbutton = '';

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'movement', 0, '', '', $limit, 0, 0, 1);

// Add code for pre mass action (confirmation or email presend form)
$topicmail = "SendStockMovement";
$modelmail = "movementstock";
$objecttmp = new MouvementStock($db);
$trackid = 'mov'.$warehouse->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';
if ($massaction == 'prereverse') {
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassReverse"), $langs->trans("ConfirmMassReverseQuestion", count($toselect)), "confirm_reverse", null, '', 0, 200, 500, 1, 'Yes');
}


if ($search_all) {
	$setupstring = '';
	if (!isset($fieldstosearchall) || !is_array($fieldstosearchall)) {
		// Ensure $fieldstosearchall is array
		$fieldstosearchall = array();
	}
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
		$setupstring .= $key."=".$val.";";
	}
	print '<!-- Search done like if STOCK_QUICKSEARCH_ON_FIELDS = '.$setupstring.' -->'."\n";
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).implode(', ', $fieldstosearchall).'</div>'."\n";
}

$moreforfilter = '';

$parameters = array('arrayfields' => &$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $warehouse, $action); // Note that $action and $warehouse may have been modified by hook
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
$htmlofselectarray = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN'));  // This also change content of $arrayfields with user setup
$selectedfields = ($mode != 'kanban' ? $htmlofselectarray : '');
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre_filter">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}
if (!empty($arrayfields['m.rowid']['checked'])) {
	// Ref
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth40" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
	print '</td>';
}
if (!empty($arrayfields['m.datem']['checked'])) {
	// Date
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'), 'tzuserrel');
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'), 'tzuserrel');
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
	print $warehouse->showInputField($warehouse->fields['fk_project'], 'fk_project', $search_fk_project, '', '', 'search_', 'maxwidth125', 1);
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
	print '<input class="flat width50 right" type="text" name="search_qty" value="'.dol_escape_htmltag($search_qty).'">';
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
$parameters = array('arrayfields' => $arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $warehouse, $action); // Note that $action and $warehouse may have been modified by hook
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
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch">';
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
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
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
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder, 'totalarray' => &$totalarray);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $warehouse, $action); // Note that $action and $warehouse may have been modified by hook
print $hookmanager->resPrint;
if (!empty($arrayfields['m.datec']['checked'])) {
	print_liste_field_titre($arrayfields['m.datec']['label'], $_SERVER["PHP_SELF"], "m.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
}
if (!empty($arrayfields['m.tms']['checked'])) {
	print_liste_field_titre($arrayfields['m.tms']['label'], $_SERVER["PHP_SELF"], "m.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
}
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
print '</tr>'."\n";


$arrayofuniqueproduct = array();


// Loop on record
// --------------------------------------------------------------------
$i = 0;
$savnbfield = $totalarray['nbfield'];
$totalarray = array();
$totalarray['nbfield'] = 0;
$imaxinloop = ($limit ? min($num, $limit) : $num);
while ($i < $imaxinloop) {
	$obj = $db->fetch_object($resql);
	if (empty($obj)) {
		break; // Should not happen
	}

	$userstatic->id = $obj->fk_user_author;
	$userstatic->login = $obj->login;
	$userstatic->lastname = $obj->lastname;
	$userstatic->firstname = $obj->firstname;
	$userstatic->photo = $obj->photo;
	$userstatic->email = $obj->user_email;
	$userstatic->status = $obj->user_status;

	// Multilangs
	if (getDolGlobalInt('MAIN_MULTILANGS')) {  // If multilang is enabled
		// TODO Use a cache
		$sql = "SELECT label";
		$sql .= " FROM ".MAIN_DB_PREFIX."product_lang";
		$sql .= " WHERE fk_product = ".((int) $obj->rowid);
		$sql .= " AND lang = '".$db->escape($langs->getDefaultLang())."'";
		$sql .= " LIMIT 1";

		$result = $db->query($sql);
		if ($result) {
			$objtp = $db->fetch_object($result);
			if (!empty($objtp->label)) {
				$obj->produit = $objtp->label;
			}
		}
	}

	$productstatic->id = $obj->rowid;
	$productstatic->ref = $obj->product_ref;
	$productstatic->label = $obj->produit;
	$productstatic->type = $obj->type;
	$productstatic->entity = $obj->entity;
	$productstatic->status = $obj->tosell;
	$productstatic->status_buy = $obj->tobuy;
	$productstatic->status_batch = $obj->tobatch;

	$productlot->id = $obj->lotid;
	$productlot->batch = $obj->batch;
	$productlot->eatby = $obj->eatby;
	$productlot->sellby = $obj->sellby;

	$warehousestatic->id = $obj->entrepot_id;
	$warehousestatic->ref = $obj->warehouse_ref;
	$warehousestatic->label = $obj->warehouse_ref;
	$warehousestatic->lieu = $obj->lieu;
	$warehousestatic->fk_parent = $obj->fk_parent;
	$warehousestatic->statut = $obj->statut;

	$object->id = $obj->mid;
	$object->qty = $obj->qty;
	$object->label = $obj->label;
	$object->batch = $obj->batch;
	$object->warehouse_id = $obj->entrepot_id;
	$object->type = $obj->type_mouvement;

	$arrayofuniqueproduct[$obj->rowid] = $obj->produit;
	if (!empty($obj->fk_origin)) {
		$origin = $object->get_origin($obj->fk_origin, $obj->origintype);
	} else {
		$origin = '';
	}

	if ($mode == 'kanban') {
		if ($i == 0) {
			print '<tr class="trkanban"><td colspan="'.$savnbfield.'">';
			print '<div class="box-flex-container kanban">';
		}
		// Output Kanban
		$selected = -1;
		if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($warehouse->id, $arrayofselected)) {
				$selected = 1;
			}
		}
		print $warehouse->getKanbanView('', array('selected' => $selected));
		if ($i == ($imaxinloop - 1)) {
			print '</div>';
			print '</td></tr>';
		}
	} else {
		// Show here line of result
		$j = 0;
		print '<tr data-rowid="'.$warehouse->id.'" class="oddeven">';
		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center">';
			if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($obj->mid, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$obj->mid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->mid.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Id movement
		if (!empty($arrayfields['m.rowid']['checked'])) {
			print '<td class="nowraponall">';
			//print img_picto($langs->trans("StockMovement"), 'movement', 'class="pictofixedwidth"');
			print $object->getNomUrl(1);
			;
			print '</td>'; // This is primary not movement id
		}
		// Date
		if (!empty($arrayfields['m.datem']['checked'])) {
			print '<td class="nowraponall center">'.dol_print_date($db->jdate($obj->datem), 'dayhour', 'tzuserrel').'</td>';
		}
		// Product ref
		if (!empty($arrayfields['p.ref']['checked'])) {
			print '<td class="nowraponall">';
			print $productstatic->getNomUrl(1, 'stock', 16);
			print "</td>\n";
		}
		// Product label
		if (!empty($arrayfields['p.label']['checked'])) {
			print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($productstatic->label).'">';
			print $productstatic->label;
			print "</td>\n";
		}
		// Lot
		if (!empty($arrayfields['m.batch']['checked'])) {
			print '<td class="center nowraponall">';
			if ($productlot->id > 0) {
				print $productlot->getNomUrl(1);
			} else {
				print $productlot->batch; // the id may not be defined if movement was entered when lot was not saved or if lot was removed after movement.
			}
			print '</td>';
		}
		// Eatby
		if (!empty($arrayfields['pl.eatby']['checked'])) {
			print '<td class="center">'.dol_print_date($obj->eatby, 'day').'</td>';
		}
		// Sellby
		if (!empty($arrayfields['pl.sellby']['checked'])) {
			print '<td class="center">'.dol_print_date($obj->sellby, 'day').'</td>';
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
		// Inventory code
		if (!empty($arrayfields['m.inventorycode']['checked'])) {
			print '<td class="tdoverflowmax150" title="'.dolPrintHTML($obj->inventorycode).'">';
			if ($obj->inventorycode) {
				print img_picto('', 'movement', 'class="pictofixedwidth"');
				print '<a href="'.$_SERVER["PHP_SELF"].'?search_inventorycode='.urlencode('^'.$obj->inventorycode.'$').'">'.dol_escape_htmltag($obj->inventorycode).'</a>';
			}
			print '</td>';
		}
		// Label of movement
		if (!empty($arrayfields['m.label']['checked'])) {
			print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($obj->label).'">'.dol_escape_htmltag($obj->label).'</td>';
		}
		// Origin of movement
		if (!empty($arrayfields['origin']['checked'])) {
			print '<td class="nowraponall">'.$origin.'</td>';
		}
		// fk_project
		if (!empty($arrayfields['m.fk_projet']['checked'])) {
			print '<td>';
			if ($obj->fk_project != 0) {
				print $object->get_origin($obj->fk_project, 'project');
			}
			print '</td>';
		}
		// Type of movement
		if (!empty($arrayfields['m.type_mouvement']['checked'])) {
			print '<td class="center">';
			print $object->getTypeMovement();
			print '</td>';
		}
		// Qty
		if (!empty($arrayfields['m.value']['checked'])) {
			print '<td class="right">';
			if ($obj->qty > 0) {
				print '<span class="stockmovemententry">';
				print '+';
				print $obj->qty;
				print '</span>';
			} else {
				print '<span class="stockmovementexit">';
				print $obj->qty;
				print '</span>';
			}
			print '</td>';
		}
		// Price
		if (!empty($arrayfields['m.price']['checked'])) {
			print '<td class="right">';
			if ($obj->price != 0) {
				print price($obj->price);
			}
			print '</td>';
		}

		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters = array('arrayfields' => $arrayfields, 'object' => $object, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;

		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center">';
			if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($obj->mid, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$obj->mid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->mid.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		print '</tr>'."\n";
	}

	$i++;
}

// If no record found
if ($num == 0) {
	$colspan = 1;
	foreach ($arrayfields as $key => $val) {
		if (!empty($val['checked'])) {
			$colspan++;
		}
	}
	print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
}

$db->free($resql);

$parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";

// Add number of product when there is a filter on period
if (count($arrayofuniqueproduct) == 1 && !empty($year) && is_numeric($year)) {
	print "<br>";

	$productidselected = 0;
	foreach ($arrayofuniqueproduct as $key => $val) {
		$productidselected = $key;
		$productlabelselected = $val;
	}
	$datebefore = dol_get_first_day($year ? $year : dol_print_date(time(), "%Y"), $month ? $month : 1, true);
	$dateafter = dol_get_last_day($year ? $year : dol_print_date(time(), "%Y"), $month ? $month : 12, true);
	$balancebefore = $object->calculateBalanceForProductBefore($productidselected, $datebefore);
	$balanceafter = $object->calculateBalanceForProductBefore($productidselected, $dateafter);

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

if (in_array('builddoc', array_keys($arrayofmassactions)) && ($nbtotalofrecords === '' || $nbtotalofrecords)) {
	$hidegeneratedfilelistifempty = 1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) {
		$hidegeneratedfilelistifempty = 0;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	$formfile = new FormFile($db);

	// Show list of available documents
	$urlsource = $_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource .= str_replace('&amp;', '&', $param);

	$filedir = $diroutputmassaction;
	$genallowed = $permissiontoread;
	$delallowed = $permissiontoadd;

	print $formfile->showdocuments('massfilesarea_stock', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
}

// End of page
llxFooter();
$db->close();
