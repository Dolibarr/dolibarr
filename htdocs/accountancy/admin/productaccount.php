<?php
/* Copyright (C) 2013-2014  Olivier Geffroy     <jeff@jeffinfo.com>
 * Copyright (C) 2013-2024  Alexandre Spangaro  <aspangaro@easya.solutions>
 * Copyright (C) 2014       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Ari Elbaz (elarifr) <github@accedinfo.com>
 * Copyright (C) 2021       Gauthier VERDOL     <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * \file		htdocs/accountancy/admin/productaccount.php
 * \ingroup		Accountancy (Double entries)
 * \brief		To define accounting account on product / service
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcategory.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "compta", "accountancy", "products"));

// search & action GETPOST
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php')); // To manage different context of search
$optioncss = GETPOST('optioncss', 'alpha');

$toselect = GETPOST('chk_prod', 'array:int');
'@phan-var-force string[] $toselect';
$default_account = GETPOSTINT('default_account');
$searchCategoryProductOperator = GETPOSTINT('search_category_product_operator');
$searchCategoryProductList = GETPOST('search_category_product_list', 'array:int');
'@phan-var-force string[] $searchCategoryProductList';
$search_ref = GETPOST('search_ref', 'alpha');
$search_label = GETPOST('search_label', 'alpha');
$search_desc = GETPOST('search_desc', 'alpha');
$search_vat = GETPOST('search_vat', 'alpha');
$search_current_account = GETPOST('search_current_account', 'alpha');
$search_current_account_valid = GETPOST('search_current_account_valid', 'alpha');
if ($search_current_account_valid == '') {
	$search_current_account_valid = 'withoutvalidaccount';
}
$search_onsell = GETPOST('search_onsell', 'alpha');
$search_onpurchase = GETPOST('search_onpurchase', 'alpha');

if (!is_array($toselect)) {
	$toselect = array();
}

$accounting_product_mode = GETPOST('accounting_product_mode', 'alpha');
$btn_changetype = GETPOST('changetype', 'alpha');

// Show/hide child product variants
$show_childproducts = 0;
if (isModEnabled('variants')) {
	$show_childproducts = GETPOST('search_show_childproducts');
}

if (empty($accounting_product_mode)) {
	$accounting_product_mode = 'ACCOUNTANCY_SELL';
}

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : getDolGlobalInt('ACCOUNTING_LIMIT_LIST_VENTILATION', $conf->liste_limit);
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = "p.ref";
}
if (!$sortorder) {
	$sortorder = "ASC";
}

if (empty($action)) {
	$action = 'list';
}

$arrayfields = array();

$accounting_product_modes = array(
	'ACCOUNTANCY_SELL',
	'ACCOUNTANCY_SELL_INTRA',
	'ACCOUNTANCY_SELL_EXPORT',
	'ACCOUNTANCY_BUY',
	'ACCOUNTANCY_BUY_INTRA',
	'ACCOUNTANCY_BUY_EXPORT'
);

if ($accounting_product_mode == 'ACCOUNTANCY_BUY') {
	$accountancy_field_name = "accountancy_code_buy";
} elseif ($accounting_product_mode == 'ACCOUNTANCY_BUY_INTRA') {
	$accountancy_field_name = "accountancy_code_buy_intra";
} elseif ($accounting_product_mode == 'ACCOUNTANCY_BUY_EXPORT') {
	$accountancy_field_name = "accountancy_code_buy_export";
} elseif ($accounting_product_mode == 'ACCOUNTANCY_SELL') {
	$accountancy_field_name = "accountancy_code_sell";
} elseif ($accounting_product_mode == 'ACCOUNTANCY_SELL_INTRA') {
	$accountancy_field_name = "accountancy_code_sell_intra";
} else { // $accounting_product_mode == 'ACCOUNTANCY_SELL_EXPORT'
	$accountancy_field_name = "accountancy_code_sell_export";
}

// Security check
if (!isModEnabled('accounting')) {
	accessforbidden();
}
if (!$user->hasRight('accounting', 'bind', 'write')) {
	accessforbidden();
}

$permissiontobind = $user->hasRight('accounting', 'bind', 'write');


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

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All test are required to be compatible with all browsers
	$searchCategoryProductOperator = 0;
	$searchCategoryProductList = array();
	$search_ref = '';
	$search_label = '';
	$search_desc = '';
	$search_vat = '';
	$search_onsell = '';
	$search_onpurchase = '';
	$search_current_account = '';
	$search_current_account_valid = '-1';
	$toselect = array();
}

// Sales or Purchase mode ?
if ($action == 'update' && $permissiontobind) {
	if (!empty($btn_changetype)) {
		$error = 0;

		if (in_array($accounting_product_mode, $accounting_product_modes)) {
			if (!dolibarr_set_const($db, 'ACCOUNTING_PRODUCT_MODE', $accounting_product_mode, 'chaine', 0, '', $conf->entity)) {
				$error++;
			}
		} else {
			$error++;
		}
	}

	if (!empty($toselect) && $massaction === 'changeaccount') {
		//$msg = '<div><span class="accountingprocessing">' . $langs->trans("Processing") . '...</span></div>';
		$ok = 0;
		$ko = 0;
		$msg = '';
		$sql = '';
		if (!empty($toselect) && in_array($accounting_product_mode, $accounting_product_modes)) {
			$accounting = new AccountingAccount($db);

			//$msg .= '<div><span class="accountingprocessing">' . count($toselect) . ' ' . $langs->trans("SelectedLines") . '</span></div>';
			$arrayofdifferentselectedvalues = array();

			$cpt = 0;
			foreach ($toselect as $productid) {
				$accounting_account_id = GETPOST('codeventil_'.$productid);

				$result = 0;
				if ($accounting_account_id > 0) {
					$arrayofdifferentselectedvalues[$accounting_account_id] = $accounting_account_id;
					$result = $accounting->fetch($accounting_account_id, null, 1);
				}
				if ($result <= 0) {
					// setEventMessages(null, $accounting->errors, 'errors');
					$msg .= '<div><span class="error">'.$langs->trans("ErrorDB").' : '.$langs->trans("Product").' '.$productid.' '.$langs->trans("NotVentilatedinAccount").' : id='.$accounting_account_id.'<br> <pre>'.$sql.'</pre></span></div>';
					$ko++;
				} else {
					if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
						$sql_exists  = "SELECT rowid FROM " . MAIN_DB_PREFIX . "product_perentity";
						$sql_exists .= " WHERE fk_product = " . ((int) $productid) . " AND entity = " . ((int) $conf->entity);
						$resql_exists = $db->query($sql_exists);
						if (!$resql_exists) {
							$msg .= '<div><span class="error">'.$langs->trans("ErrorDB").' : '.$langs->trans("Product").' '.$productid.' '.$langs->trans("NotVentilatedinAccount").' : id='.$accounting_account_id.'<br> <pre>'.json_encode($resql_exists).'</pre></span></div>';
							$ko++;
						} else {
							$nb_exists = $db->num_rows($resql_exists);
							if ($nb_exists <= 0) {
								// insert
								$sql  = "INSERT INTO " . MAIN_DB_PREFIX . "product_perentity (fk_product, entity, " . $db->sanitize($accountancy_field_name) . ")";
								$sql .= " VALUES (" . ((int) $productid) . ", " . ((int) $conf->entity) . ", '" . $db->escape($accounting->account_number) . "')";
							} else {
								$obj_exists = $db->fetch_object($resql_exists);
								// update
								$sql  = "UPDATE " . MAIN_DB_PREFIX . "product_perentity";
								$sql .= " SET " . $db->sanitize($accountancy_field_name) . " = '" . $db->escape($accounting->account_number) . "'";
								$sql .= " WHERE rowid = " . ((int) $obj_exists->rowid);
							}
						}
					} else {
						$sql = " UPDATE ".MAIN_DB_PREFIX."product";
						$sql .= " SET ".$db->sanitize($accountancy_field_name)." = '".$db->escape($accounting->account_number)."'";
						$sql .= " WHERE rowid = ".((int) $productid);
					}

					dol_syslog("/accountancy/admin/productaccount.php", LOG_DEBUG);

					$db->begin();

					if ($db->query($sql)) {
						$ok++;
						$db->commit();
					} else {
						$ko++;
						$db->rollback();
					}
				}

				$cpt++;
			}
		}

		if ($ko) {
			setEventMessages($langs->trans("XLineFailedToBeBinded", $ko), null, 'errors');
		}
		if ($ok) {
			setEventMessages($langs->trans("XLineSuccessfullyBinded", $ok), null, 'mesgs');
		}
	}
}



/*
 * View
 */

$form = new FormAccounting($db);

// Default AccountingAccount RowId Product / Service
// at this time ACCOUNTING_SERVICE_SOLD_ACCOUNT & ACCOUNTING_PRODUCT_SOLD_ACCOUNT are account number not accountingacount rowid
// so we need to get those the rowid of those default value first
$accounting = new AccountingAccount($db);
// TODO: we should need to check if result is already exists accountaccount rowid.....
$aarowid_servbuy            = $accounting->fetch(0, getDolGlobalString('ACCOUNTING_SERVICE_BUY_ACCOUNT'), 1);
$aarowid_servbuy_intra      = $accounting->fetch(0, getDolGlobalString('ACCOUNTING_SERVICE_BUY_INTRA_ACCOUNT'), 1);
$aarowid_servbuy_export     = $accounting->fetch(0, getDolGlobalString('ACCOUNTING_SERVICE_BUY_EXPORT_ACCOUNT'), 1);
$aarowid_prodbuy            = $accounting->fetch(0, getDolGlobalString('ACCOUNTING_PRODUCT_BUY_ACCOUNT'), 1);
$aarowid_prodbuy_intra      = $accounting->fetch(0, getDolGlobalString('ACCOUNTING_PRODUCT_BUY_INTRA_ACCOUNT'), 1);
$aarowid_prodbuy_export     = $accounting->fetch(0, getDolGlobalString('ACCOUNTING_PRODUCT_BUY_EXPORT_ACCOUNT'), 1);
$aarowid_servsell           = $accounting->fetch(0, getDolGlobalString('ACCOUNTING_SERVICE_SOLD_ACCOUNT'), 1);
$aarowid_servsell_intra     = $accounting->fetch(0, getDolGlobalString('ACCOUNTING_SERVICE_SOLD_INTRA_ACCOUNT'), 1);
$aarowid_servsell_export    = $accounting->fetch(0, getDolGlobalString('ACCOUNTING_SERVICE_SOLD_EXPORT_ACCOUNT'), 1);
$aarowid_prodsell           = $accounting->fetch(0, getDolGlobalString('ACCOUNTING_PRODUCT_SOLD_ACCOUNT'), 1);
$aarowid_prodsell_intra     = $accounting->fetch(0, getDolGlobalString('ACCOUNTING_PRODUCT_SOLD_INTRA_ACCOUNT'), 1);
$aarowid_prodsell_export    = $accounting->fetch(0, getDolGlobalString('ACCOUNTING_PRODUCT_SOLD_EXPORT_ACCOUNT'), 1);

$aacompta_servbuy           = getDolGlobalString('ACCOUNTING_SERVICE_BUY_ACCOUNT', $langs->trans("CodeNotDef"));
$aacompta_servbuy_intra     = getDolGlobalString('ACCOUNTING_SERVICE_BUY_INTRA_ACCOUNT', $langs->trans("CodeNotDef"));
$aacompta_servbuy_export    = getDolGlobalString('ACCOUNTING_SERVICE_BUY_EXPORT_ACCOUNT', $langs->trans("CodeNotDef"));
$aacompta_prodbuy           = getDolGlobalString('ACCOUNTING_PRODUCT_BUY_ACCOUNT', $langs->trans("CodeNotDef"));
$aacompta_prodbuy_intra     = getDolGlobalString('ACCOUNTING_PRODUCT_BUY_INTRA_ACCOUNT', $langs->trans("CodeNotDef"));
$aacompta_prodbuy_export    = getDolGlobalString('ACCOUNTING_PRODUCT_BUY_EXPORT_ACCOUNT', $langs->trans("CodeNotDef"));
$aacompta_servsell          = getDolGlobalString('ACCOUNTING_SERVICE_SOLD_ACCOUNT', $langs->trans("CodeNotDef"));
$aacompta_servsell_intra    = getDolGlobalString('ACCOUNTING_SERVICE_SOLD_INTRA_ACCOUNT', $langs->trans("CodeNotDef"));
$aacompta_servsell_export   = getDolGlobalString('ACCOUNTING_SERVICE_SOLD_EXPORT_ACCOUNT', $langs->trans("CodeNotDef"));
$aacompta_prodsell          = getDolGlobalString('ACCOUNTING_PRODUCT_SOLD_ACCOUNT', $langs->trans("CodeNotDef"));
$aacompta_prodsell_intra    = getDolGlobalString('ACCOUNTING_PRODUCT_SOLD_INTRA_ACCOUNT', $langs->trans("CodeNotDef"));
$aacompta_prodsell_export   = getDolGlobalString('ACCOUNTING_PRODUCT_SOLD_EXPORT_ACCOUNT', $langs->trans("CodeNotDef"));


$title = $langs->trans("ProductsBinding");
$help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

$paramsCat = '';
foreach ($searchCategoryProductList as $searchCategoryProduct) {
	$paramsCat .= "&search_category_product_list[]=".urlencode($searchCategoryProduct);
}

llxHeader('', $title, $help_url, '', 0, 0, array(), array(), $paramsCat, '');

$pcgverid = getDolGlobalString('CHARTOFACCOUNTS');
$pcgvercode = dol_getIdFromCode($db, $pcgverid, 'accounting_system', 'rowid', 'pcg_version');
if (empty($pcgvercode)) {
	$pcgvercode = $pcgverid;
}

$sql = "SELECT p.rowid, p.ref, p.label, p.description, p.tosell, p.tobuy, p.tva_tx,";
if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
	$sql .= " ppe.accountancy_code_sell, ppe.accountancy_code_sell_intra, ppe.accountancy_code_sell_export,";
	$sql .= " ppe.accountancy_code_buy, ppe.accountancy_code_buy_intra, ppe.accountancy_code_buy_export,";
} else {
	$sql .= " p.accountancy_code_sell, p.accountancy_code_sell_intra, p.accountancy_code_sell_export,";
	$sql .= " p.accountancy_code_buy, p.accountancy_code_buy_intra, p.accountancy_code_buy_export,";
}
$sql .= " p.tms, p.fk_product_type as product_type,";
$sql .= " aa.rowid as aaid";
$sql .= " FROM ".MAIN_DB_PREFIX."product as p";
if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_perentity as ppe ON ppe.fk_product = p.rowid AND ppe.entity = " . ((int) $conf->entity);
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON aa.account_number = ppe." . $db->sanitize($accountancy_field_name) . " AND aa.fk_pcg_version = '" . $db->escape($pcgvercode) . "'";
} else {
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON aa.account_number = p." . $db->sanitize($accountancy_field_name) . " AND aa.fk_pcg_version = '" . $db->escape($pcgvercode) . "'";
}
if (!empty($searchCategoryProductList)) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON p.rowid = cp.fk_product"; // We'll need this table joined to the select in order to filter by categ
}
$sql .= ' WHERE p.entity IN ('.getEntity('product').')';
if (strlen(trim($search_current_account))) {
	$sql .= natural_search((!getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED') ? "p." : "ppe.") . $db->sanitize($accountancy_field_name), $search_current_account);
}
if ($search_current_account_valid == 'withoutvalidaccount') {
	$sql .= " AND aa.account_number IS NULL";
}
if ($search_current_account_valid == 'withvalidaccount') {
	$sql .= " AND aa.account_number IS NOT NULL";
}
$searchCategoryProductSqlList = array();
if ($searchCategoryProductOperator == 1) {
	foreach ($searchCategoryProductList as $searchCategoryProduct) {
		if (intval($searchCategoryProduct) == -2) {
			$searchCategoryProductSqlList[] = "cp.fk_categorie IS NULL";
		} elseif (intval($searchCategoryProduct) > 0) {
			$searchCategoryProductSqlList[] = "cp.fk_categorie = ".((int) $searchCategoryProduct);
		}
	}
	if (!empty($searchCategoryProductSqlList)) {
		$sql .= " AND (".implode(' OR ', $searchCategoryProductSqlList).")";
	}
} else {
	foreach ($searchCategoryProductList as $searchCategoryProduct) {
		if (intval($searchCategoryProduct) == -2) {
			$searchCategoryProductSqlList[] = "cp.fk_categorie IS NULL";
		} elseif (intval($searchCategoryProduct) > 0) {
			$searchCategoryProductSqlList[] = "p.rowid IN (SELECT fk_product FROM ".MAIN_DB_PREFIX."categorie_product WHERE fk_categorie = ".((int) $searchCategoryProduct).")";
		}
	}
	if (!empty($searchCategoryProductSqlList)) {
		$sql .= " AND (".implode(' AND ', $searchCategoryProductSqlList).")";
	}
}
// Add search filter like
if (strlen(trim($search_ref))) {
	$sql .= natural_search("p.ref", $search_ref);
}
if (strlen(trim($search_label))) {
	$sql .= natural_search("p.label", $search_label);
}
if (strlen(trim($search_desc))) {
	$sql .= natural_search("p.description", $search_desc);
}
if (strlen(trim($search_vat))) {
	$sql .= natural_search("p.tva_tx", price2num($search_vat), 1);
}
if ($search_onsell != '' && $search_onsell != '-1') {
	$sql .= natural_search('p.tosell', $search_onsell, 1);
}
if ($search_onpurchase != '' && $search_onpurchase != '-1') {
	$sql .= natural_search('p.tobuy', $search_onpurchase, 1);
}

$sql .= " GROUP BY p.rowid, p.ref, p.label, p.description, p.tosell, p.tobuy, p.tva_tx,";
$sql .= " p.fk_product_type,";
$sql .= ' p.tms,';
$sql .= ' aa.rowid,';
if (!getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
	$sql .= " p.accountancy_code_sell, p.accountancy_code_sell_intra, p.accountancy_code_sell_export, p.accountancy_code_buy, p.accountancy_code_buy_intra, p.accountancy_code_buy_export";
} else {
	$sql .= " ppe.accountancy_code_sell, ppe.accountancy_code_sell_intra, ppe.accountancy_code_sell_export, ppe.accountancy_code_buy, ppe.accountancy_code_buy_intra, ppe.accountancy_code_buy_export";
}

$sql .= $db->order($sortfield, $sortorder);

$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);

dol_syslog("/accountancy/admin/productaccount.php", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.urlencode($contextpage);
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.((int) $limit);
	}
	if ($searchCategoryProductOperator == 1) {
		$param .= "&search_category_product_operator=".urlencode((string) ($searchCategoryProductOperator));
	}
	foreach ($searchCategoryProductList as $searchCategoryProduct) {
		$param .= "&search_category_product_list[]=".urlencode($searchCategoryProduct);
	}
	if ($search_ref > 0) {
		$param .= "&search_ref=".urlencode($search_ref);
	}
	if ($search_label > 0) {
		$param .= "&search_label=".urlencode($search_label);
	}
	if ($search_desc > 0) {
		$param .= "&search_desc=".urlencode($search_desc);
	}
	if ($search_vat > 0) {
		$param .= '&search_vat='.urlencode($search_vat);
	}
	if ($search_current_account > 0) {
		$param .= "&search_current_account=".urlencode($search_current_account);
	}
	if ($search_current_account_valid && $search_current_account_valid != '-1') {
		$param .= "&search_current_account_valid=".urlencode($search_current_account_valid);
	}
	if ($accounting_product_mode) {
		$param .= '&accounting_product_mode='.urlencode($accounting_product_mode);
	}

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
	if ($optioncss != '') {
		print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	}
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page_y" value="">';

	print load_fiche_titre($langs->trans("ProductsBinding"), '', 'title_accountancy');
	print '<br>';

	print '<span class="opacitymedium">'.$langs->trans("InitAccountancyDesc").'</span><br>';
	print '<br>';

	// Select mode
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans('Options').'</td><td>'.$langs->trans('Description').'</td>';
	print "</tr>\n";
	print '<tr class="oddeven"><td><input type="radio" id="accounting_product_mode1" name="accounting_product_mode" value="ACCOUNTANCY_SELL"'.($accounting_product_mode == 'ACCOUNTANCY_SELL' ? ' checked' : '').'> <label for="accounting_product_mode1">'.$langs->trans('OptionModeProductSell').'</label></td>';
	print '<td>'.$langs->trans('OptionModeProductSellDesc');
	print "</td></tr>\n";
	if ($mysoc->isInEEC()) {
		print '<tr class="oddeven"><td><input type="radio" id="accounting_product_mode2" name="accounting_product_mode" value="ACCOUNTANCY_SELL_INTRA"'.($accounting_product_mode == 'ACCOUNTANCY_SELL_INTRA' ? ' checked' : '').'> <label for="accounting_product_mode2">'.$langs->trans('OptionModeProductSellIntra').'</label></td>';
		print '<td>'.$langs->trans('OptionModeProductSellIntraDesc');
		print "</td></tr>\n";
	}
	print '<tr class="oddeven"><td><input type="radio" id="accounting_product_mode3" name="accounting_product_mode" value="ACCOUNTANCY_SELL_EXPORT"'.($accounting_product_mode == 'ACCOUNTANCY_SELL_EXPORT' ? ' checked' : '').'> <label for="accounting_product_mode3">'.$langs->trans('OptionModeProductSellExport').'</label></td>';
	print '<td>'.$langs->trans('OptionModeProductSellExportDesc');
	print "</td></tr>\n";
	print '<tr class="oddeven"><td><input type="radio" id="accounting_product_mode4" name="accounting_product_mode" value="ACCOUNTANCY_BUY"'.($accounting_product_mode == 'ACCOUNTANCY_BUY' ? ' checked' : '').'> <label for="accounting_product_mode4">'.$langs->trans('OptionModeProductBuy').'</label></td>';
	print '<td>'.$langs->trans('OptionModeProductBuyDesc')."</td></tr>\n";
	if ($mysoc->isInEEC()) {
		print '<tr class="oddeven"><td><input type="radio" id="accounting_product_mode5" name="accounting_product_mode" value="ACCOUNTANCY_BUY_INTRA"'.($accounting_product_mode == 'ACCOUNTANCY_BUY_INTRA' ? ' checked' : '').'> <label for="accounting_product_mode5">'.$langs->trans('OptionModeProductBuyIntra').'</label></td>';
		print '<td>'.$langs->trans('OptionModeProductBuyDesc')."</td></tr>\n";
	}
	print '<tr class="oddeven"><td><input type="radio" id="accounting_product_mode6" name="accounting_product_mode" value="ACCOUNTANCY_BUY_EXPORT"'.($accounting_product_mode == 'ACCOUNTANCY_BUY_EXPORT' ? ' checked' : '').'> <label for="accounting_product_mode6">'.$langs->trans('OptionModeProductBuyExport').'</label></td>';
	print '<td>'.$langs->trans('OptionModeProductBuyDesc')."</td></tr>\n";
	print "</table>\n";

	print '<div class="center"><input type="submit" class="button" value="'.$langs->trans('Refresh').'" name="changetype"></div>';

	print "<br>\n";


	// Filter on categories
	$moreforfilter = '';
	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

	$massactionbutton = '';

	$nbselected = is_array($toselect) ? count($toselect) : 0;
	if ($massaction == 'set_default_account') {
		if ($nbselected <= 0) {
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorSelectAtLeastOne"), null, 'warnings');
			$action = '';
			$massaction = '';
		}
	}

	if ($massaction !== 'set_default_account') {
		$arrayofmassactions = array(
			'set_default_account' => img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("ConfirmPreselectAccount"),
			'changeaccount' => img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("Save")	// TODO The save action should be a button "Save"
		);
		$massactionbutton = $form->selectMassAction('', $arrayofmassactions, 1);
	}

	//$buttonsave = '<input type="submit" class="button button-save" id="changeaccount" name="changeaccount" value="'.$langs->trans("Save").'">';
	//print '<br><div class="center">'.$buttonsave.'</div>';

	$texte = $langs->trans("ListOfProductsServices");
	print_barre_liste($texte, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, '', 0, '', '', $limit, 0, 0, 1);

	if ($massaction == 'set_default_account') {
		$formquestion = array();
		$formquestion[] = array('type' => 'other',
			'name' => 'set_default_account',
			'label' => $langs->trans("AccountancyCode"),
			'value' => $form->select_account('', 'default_account', 1, array(), 0, 0, 'maxwidth200 maxwidthonsmartphone', 'cachewithshowemptyone'));
		print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmPreselectAccount"), $langs->trans("ConfirmPreselectAccountQuestion", $nbselected), "confirm_set_default_account", $formquestion, 1, 0, 200, 500, 1);
	}

	// Filter on categories
	$moreforfilter = '';
	if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
		$formcategory = new FormCategory($db);
		$moreforfilter .= $formcategory->getFilterBox(Categorie::TYPE_PRODUCT, $searchCategoryProductList, 'minwidth300', $searchCategoryProductList ? $searchCategoryProductList : 0);
	}

	// Show/hide child products. Hidden by default
	if (isModEnabled('variants') && getDolGlobalInt('PRODUIT_ATTRIBUTES_HIDECHILD')) {
		$moreforfilter .= '<div class="divsearchfield">';
		$moreforfilter .= '<input type="checkbox" id="search_show_childproducts" name="search_show_childproducts"'.($show_childproducts ? 'checked="checked"' : '').'>';
		$moreforfilter .= ' <label for="search_show_childproducts">'.$langs->trans('ShowChildProducts').'</label>';
		$moreforfilter .= '</div>';
	}

	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$moreforfilter .= $hookmanager->resPrint;
	} else {
		$moreforfilter = $hookmanager->resPrint;
	}

	if ($moreforfilter) {
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>';
	}

	print '<div class="div-table-responsive">';
	print '<table class="liste '.($moreforfilter ? "listwithfilterbefore" : "").'">';

	print '<tr class="liste_titre_filter">';
	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="center liste_titre">';
		$searchpicto = $form->showFilterButtons();
		print $searchpicto;
		print '</td>';
	}
	print '<td class="liste_titre"><input type="text" class="flat" size="8" name="search_ref" value="'.dol_escape_htmltag($search_ref).'"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" size="10" name="search_label" value="'.dol_escape_htmltag($search_label).'"></td>';
	print '<td class="liste_titre right"><input type="text" class="flat maxwidth50 right" name="search_vat" placeholder="%" value="'.dol_escape_htmltag($search_vat).'"></td>';

	if (getDolGlobalInt('ACCOUNTANCY_SHOW_PROD_DESC')) {
		print '<td class="liste_titre"><input type="text" class="flat" size="20" name="search_desc" value="'.dol_escape_htmltag($search_desc).'"></td>';
	}
	// On sell
	if ($accounting_product_mode == 'ACCOUNTANCY_SELL' || $accounting_product_mode == 'ACCOUNTANCY_SELL_INTRA' || $accounting_product_mode == 'ACCOUNTANCY_SELL_EXPORT') {
		print '<td class="liste_titre center">'.$form->selectyesno('search_onsell', $search_onsell, 1, false, 1, 1).'</td>';
	} else {
		// On buy
		print '<td class="liste_titre center">'.$form->selectyesno('search_onpurchase', $search_onpurchase, 1, false, 1, 1).'</td>';
	}
	// Current account
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" size="6" name="search_current_account" id="search_current_account" value="'.dol_escape_htmltag($search_current_account).'">';
	$listofvals = array('withoutvalidaccount' => $langs->trans("WithoutValidAccount"), 'withvalidaccount' => $langs->trans("WithValidAccount"));
	print ' '.$langs->trans("or").' '.$form->selectarray('search_current_account_valid', $listofvals, $search_current_account_valid, 1);
	print '</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="center liste_titre">';
		$searchpicto = $form->showFilterButtons();
		print $searchpicto;
		print '</td>';
	}
	print '</tr>';

	print '<tr class="liste_titre">';
	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		$clickpitco = $form->showCheckAddButtons('checkforselect', 1);
		print_liste_field_titre($clickpitco, '', '', '', '', '', '', '', 'center ');
	}
	print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "p.ref", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Label", $_SERVER["PHP_SELF"], "p.label", "", $param, '', $sortfield, $sortorder);
	if (getDolGlobalInt('ACCOUNTANCY_SHOW_PROD_DESC')) {
		print_liste_field_titre("Description", $_SERVER["PHP_SELF"], "p.description", "", $param, '', $sortfield, $sortorder);
	}
	print_liste_field_titre("VATRate", $_SERVER["PHP_SELF"], "p.tva_tx", "", $param, '', $sortfield, $sortorder, 'right ');
	// On sell / On purchase
	if ($accounting_product_mode == 'ACCOUNTANCY_SELL' || $accounting_product_mode == 'ACCOUNTANCY_SELL_INTRA' || $accounting_product_mode == 'ACCOUNTANCY_SELL_EXPORT') {
		print_liste_field_titre("OnSell", $_SERVER["PHP_SELF"], "p.tosell", "", $param, '', $sortfield, $sortorder, 'center ');
	} else {
		print_liste_field_titre("OnBuy", $_SERVER["PHP_SELF"], "p.tobuy", "", $param, '', $sortfield, $sortorder, 'center ');
	}
	print_liste_field_titre("CurrentDedicatedAccountingAccount", $_SERVER["PHP_SELF"], (!getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED') ? "p." : "ppe.") . $accountancy_field_name, "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("AssignDedicatedAccountingAccount");
	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		$clickpitco = $form->showCheckAddButtons('checkforselect', 1);
		print_liste_field_titre($clickpitco, '', '', '', '', '', '', '', 'center ');
	}
	print '</tr>';

	$product_static = new Product($db);

	$i = 0;
	while ($i < min($num, $limit)) {
		$obj = $db->fetch_object($resql);

		// Ref produit as link
		$product_static->ref = $obj->ref;
		$product_static->id = $obj->rowid;
		$product_static->type = $obj->product_type;
		$product_static->label = $obj->label;
		$product_static->description = $obj->description;
		$product_static->status = $obj->tosell;
		$product_static->status_buy = $obj->tobuy;

		// Sales
		if ($obj->product_type == 0) {
			if ($accounting_product_mode == 'ACCOUNTANCY_SELL') {
				$compta_prodsell = getDolGlobalString('ACCOUNTING_PRODUCT_SOLD_ACCOUNT', $langs->trans("CodeNotDef"));
				$compta_prodsell_id = $aarowid_prodsell;
			} elseif ($accounting_product_mode == 'ACCOUNTANCY_SELL_INTRA') {
				$compta_prodsell = getDolGlobalString('ACCOUNTING_PRODUCT_SOLD_INTRA_ACCOUNT', $langs->trans("CodeNotDef"));
				$compta_prodsell_id = $aarowid_prodsell_intra;
			} elseif ($accounting_product_mode == 'ACCOUNTANCY_SELL_EXPORT') {
				$compta_prodsell = getDolGlobalString('ACCOUNTING_PRODUCT_SOLD_EXPORT_ACCOUNT', $langs->trans("CodeNotDef"));
				$compta_prodsell_id = $aarowid_prodsell_export;
			} else {
				$compta_prodsell = getDolGlobalString('ACCOUNTING_PRODUCT_SOLD_ACCOUNT', $langs->trans("CodeNotDef"));
				$compta_prodsell_id = $aarowid_prodsell;
			}
		} else {
			if ($accounting_product_mode == 'ACCOUNTANCY_SELL') {
				$compta_prodsell = getDolGlobalString('ACCOUNTING_SERVICE_SOLD_ACCOUNT', $langs->trans("CodeNotDef"));
				$compta_prodsell_id = $aarowid_servsell;
			} elseif ($accounting_product_mode == 'ACCOUNTANCY_SELL_INTRA') {
				$compta_prodsell = getDolGlobalString('ACCOUNTING_SERVICE_SOLD_INTRA_ACCOUNT', $langs->trans("CodeNotDef"));
				$compta_prodsell_id = $aarowid_servsell_intra;
			} elseif ($accounting_product_mode == 'ACCOUNTANCY_SELL_EXPORT') {
				$compta_prodsell = getDolGlobalString('ACCOUNTING_SERVICE_SOLD_EXPORT_ACCOUNT', $langs->trans("CodeNotDef"));

				$compta_prodsell_id = $aarowid_servsell_export;
			} else {
				$compta_prodsell = getDolGlobalString('ACCOUNTING_SERVICE_SOLD_ACCOUNT', $langs->trans("CodeNotDef"));
				$compta_prodsell_id = $aarowid_servsell;
			}
		}

		// Purchases
		if ($obj->product_type == 0) {
			if ($accounting_product_mode == 'ACCOUNTANCY_BUY') {
				$compta_prodbuy = getDolGlobalString('ACCOUNTING_PRODUCT_BUY_ACCOUNT', $langs->trans("CodeNotDef"));
				$compta_prodbuy_id = $aarowid_prodbuy;
			} elseif ($accounting_product_mode == 'ACCOUNTANCY_BUY_INTRA') {
				$compta_prodbuy = getDolGlobalString('ACCOUNTING_PRODUCT_BUY_INTRA_ACCOUNT', $langs->trans("CodeNotDef"));
				$compta_prodbuy_id = $aarowid_prodbuy_intra;
			} elseif ($accounting_product_mode == 'ACCOUNTANCY_BUY_EXPORT') {
				$compta_prodbuy = getDolGlobalString('ACCOUNTING_PRODUCT_BUY_EXPORT_ACCOUNT', $langs->trans("CodeNotDef"));
				$compta_prodbuy_id = $aarowid_prodbuy_export;
			} else {
				$compta_prodbuy = getDolGlobalString('ACCOUNTING_PRODUCT_BUY_ACCOUNT', $langs->trans("CodeNotDef"));
				$compta_prodbuy_id = $aarowid_prodbuy;
			}
		} else {
			if ($accounting_product_mode == 'ACCOUNTANCY_BUY') {
				$compta_prodbuy = getDolGlobalString('ACCOUNTING_SERVICE_BUY_ACCOUNT', $langs->trans("CodeNotDef"));
				$compta_prodbuy_id = $aarowid_servbuy;
			} elseif ($accounting_product_mode == 'ACCOUNTANCY_BUY_INTRA') {
				$compta_prodbuy = getDolGlobalString('ACCOUNTING_SERVICE_BUY_INTRA_ACCOUNT', $langs->trans("CodeNotDef"));
				$compta_prodbuy_id = $aarowid_servbuy_intra;
			} elseif ($accounting_product_mode == 'ACCOUNTANCY_BUY_EXPORT') {
				$compta_prodbuy = getDolGlobalString('ACCOUNTING_SERVICE_BUY_EXPORT_ACCOUNT', $langs->trans("CodeNotDef"));
				$compta_prodbuy_id = $aarowid_servbuy_export;
			} else {
				$compta_prodbuy = getDolGlobalString('ACCOUNTING_SERVICE_BUY_ACCOUNT', $langs->trans("CodeNotDef"));
				$compta_prodbuy_id = $aarowid_servbuy;
			}
		}

		$selected = 0;
		if (!empty($toselect)) {
			if (in_array($product_static->id, $toselect)) {
				$selected = 1;
			}
		}

		print '<tr class="oddeven">';

		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="center">';
			print '<input type="checkbox" class="checkforselect productforselectcodeventil_'.$product_static->id.'" name="chk_prod[]" '.($selected ? "checked" : "").' value="'.$obj->rowid.'"/>';
			print '</td>';
		}

		print '<td>';
		print $product_static->getNomUrl(1);
		print '</td>';

		print '<td class="left">'.$obj->label.'</td>';

		if (getDolGlobalInt('ACCOUNTANCY_SHOW_PROD_DESC')) {
			// TODO ADJUST DESCRIPTION SIZE
			// print '<td class="left">' . $obj->description . '</td>';
			// TODO: we should set a user defined value to adjust user square / wide screen size
			$trunclength = getDolGlobalInt('ACCOUNTING_LENGTH_DESCRIPTION', 32);
			print '<td>'.nl2br(dol_trunc($obj->description, $trunclength)).'</td>';
		}

		// VAT
		print '<td class="right">';
		print vatrate($obj->tva_tx);
		print '</td>';

		// On sell / On purchase
		if ($accounting_product_mode == 'ACCOUNTANCY_SELL' || $accounting_product_mode == 'ACCOUNTANCY_SELL_INTRA' || $accounting_product_mode == 'ACCOUNTANCY_SELL_EXPORT') {
			print '<td class="center">'.$product_static->getLibStatut(3, 0).'</td>';
		} else {
			print '<td class="center">'.$product_static->getLibStatut(3, 1).'</td>';
		}

		// Current accounting account
		print '<td class="left">';
		if ($accounting_product_mode == 'ACCOUNTANCY_BUY') {
			print length_accountg($obj->accountancy_code_buy);
			if ($obj->accountancy_code_buy && empty($obj->aaid)) {
				print ' '.img_warning($langs->trans("ValueNotIntoChartOfAccount"));
			}
		} elseif ($accounting_product_mode == 'ACCOUNTANCY_BUY_INTRA') {
			print length_accountg($obj->accountancy_code_buy_intra);
			if ($obj->accountancy_code_buy_intra && empty($obj->aaid)) {
				print ' '.img_warning($langs->trans("ValueNotIntoChartOfAccount"));
			}
		} elseif ($accounting_product_mode == 'ACCOUNTANCY_BUY_EXPORT') {
			print length_accountg($obj->accountancy_code_buy_export);
			if ($obj->accountancy_code_buy_export && empty($obj->aaid)) {
				print ' '.img_warning($langs->trans("ValueNotIntoChartOfAccount"));
			}
		} elseif ($accounting_product_mode == 'ACCOUNTANCY_SELL') {
			print length_accountg($obj->accountancy_code_sell);
			if ($obj->accountancy_code_sell && empty($obj->aaid)) {
				print ' '.img_warning($langs->trans("ValueNotIntoChartOfAccount"));
			}
		} elseif ($accounting_product_mode == 'ACCOUNTANCY_SELL_INTRA') {
			print length_accountg($obj->accountancy_code_sell_intra);
			if ($obj->accountancy_code_sell_intra && empty($obj->aaid)) {
				print ' '.img_warning($langs->trans("ValueNotIntoChartOfAccount"));
			}
		} else {
			print length_accountg($obj->accountancy_code_sell_export);
			if ($obj->accountancy_code_sell_export && empty($obj->aaid)) {
				print ' '.img_warning($langs->trans("ValueNotIntoChartOfAccount"));
			}
		}
		print '</td>';

		// New account to set
		$defaultvalue = '';
		if ($accounting_product_mode == 'ACCOUNTANCY_BUY') {
			// Accounting account buy
			print '<td class="left">';
			//$defaultvalue=GETPOST('codeventil_' . $product_static->id,'alpha');        This is id and we need a code
			if (empty($defaultvalue)) {
				$defaultvalue = $compta_prodbuy;
			}
			$codesell = length_accountg($obj->accountancy_code_buy);
			if (!empty($obj->aaid)) {
				$defaultvalue = ''; // Do not suggest default new value is code is already valid
			}
			print $form->select_account(($default_account > 0 && $confirm === 'yes' && in_array($product_static->id, $toselect)) ? $default_account : $defaultvalue, 'codeventil_'.$product_static->id, 1, array(), 1, 0, 'maxwidth300 maxwidthonsmartphone productforselect');
			print '</td>';
		} elseif ($accounting_product_mode == 'ACCOUNTANCY_BUY_INTRA') {
			// Accounting account buy intra (In EEC)
			print '<td class="left">';
			//$defaultvalue=GETPOST('codeventil_' . $product_static->id,'alpha');        This is id and we need a code
			if (empty($defaultvalue)) {
				$defaultvalue = $compta_prodbuy;
			}
			$codesell = length_accountg($obj->accountancy_code_buy_intra);
			//var_dump($defaultvalue.' - '.$codesell.' - '.$compta_prodsell);
			if (!empty($obj->aaid)) {
				$defaultvalue = ''; // Do not suggest default new value is code is already valid
			}
			print $form->select_account(($default_account > 0 && $confirm === 'yes' && in_array($product_static->id, $toselect)) ? $default_account : $defaultvalue, 'codeventil_'.$product_static->id, 1, array(), 1, 0, 'maxwidth300 maxwidthonsmartphone productforselect');
			print '</td>';
		} elseif ($accounting_product_mode == 'ACCOUNTANCY_BUY_EXPORT') {
			// Accounting account buy export (Out of EEC)
			print '<td class="left">';
			//$defaultvalue=GETPOST('codeventil_' . $product_static->id,'alpha');        This is id and we need a code
			if (empty($defaultvalue)) {
				$defaultvalue = $compta_prodbuy;
			}
			$codesell = length_accountg($obj->accountancy_code_buy_export);
			//var_dump($defaultvalue.' - '.$codesell.' - '.$compta_prodsell);
			if (!empty($obj->aaid)) {
				$defaultvalue = ''; // Do not suggest default new value is code is already valid
			}
			print $form->select_account(($default_account > 0 && $confirm === 'yes' && in_array($product_static->id, $toselect)) ? $default_account : $defaultvalue, 'codeventil_'.$product_static->id, 1, array(), 1, 0, 'maxwidth300 maxwidthonsmartphone productforselect');
			print '</td>';
		} elseif ($accounting_product_mode == 'ACCOUNTANCY_SELL') {
			// Accounting account sell
			print '<td class="left">';
			//$defaultvalue=GETPOST('codeventil_' . $product_static->id,'alpha');        This is id and we need a code
			if (empty($defaultvalue)) {
				$defaultvalue = $compta_prodsell;
			}
			$codesell = length_accountg($obj->accountancy_code_sell);
			//var_dump($defaultvalue.' - '.$codesell.' - '.$compta_prodsell);
			if (!empty($obj->aaid)) {
				$defaultvalue = ''; // Do not suggest default new value is code is already valid
			}
			print $form->select_account(($default_account > 0 && $confirm === 'yes' && in_array($product_static->id, $toselect)) ? $default_account : $defaultvalue, 'codeventil_'.$product_static->id, 1, array(), 1, 0, 'maxwidth300 maxwidthonsmartphone productforselect');
			print '</td>';
		} elseif ($accounting_product_mode == 'ACCOUNTANCY_SELL_INTRA') {
			// Accounting account sell intra (In EEC)
			print '<td class="left">';
			//$defaultvalue=GETPOST('codeventil_' . $product_static->id,'alpha');        This is id and we need a code
			if (empty($defaultvalue)) {
				$defaultvalue = $compta_prodsell;
			}
			$codesell = length_accountg($obj->accountancy_code_sell_intra);
			//var_dump($defaultvalue.' - '.$codesell.' - '.$compta_prodsell);
			if (!empty($obj->aaid)) {
				$defaultvalue = ''; // Do not suggest default new value is code is already valid
			}
			print $form->select_account(($default_account > 0 && $confirm === 'yes' && in_array($product_static->id, $toselect)) ? $default_account : $defaultvalue, 'codeventil_'.$product_static->id, 1, array(), 1, 0, 'maxwidth300 maxwidthonsmartphone productforselect');
			print '</td>';
		} else {
			// Accounting account sell export (Out of EEC)
			print '<td class="left">';
			//$defaultvalue=GETPOST('codeventil_' . $product_static->id,'alpha');        This is id and we need a code
			if (empty($defaultvalue)) {
				$defaultvalue = $compta_prodsell;
			}
			$codesell = length_accountg($obj->accountancy_code_sell_export);
			if (!empty($obj->aaid)) {
				$defaultvalue = ''; // Do not suggest default new value is code is already valid
			}
			print $form->select_account(($default_account > 0 && $confirm === 'yes' && in_array($product_static->id, $toselect)) ? $default_account : $defaultvalue, 'codeventil_'.$product_static->id, 1, array(), 1, 0, 'maxwidth300 maxwidthonsmartphone productforselect');
			print '</td>';
		}

		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="center">';
			print '<input type="checkbox" class="checkforselect productforselectcodeventil_'.$product_static->id.'" name="chk_prod[]" '.($selected ? "checked" : "").' value="'.$obj->rowid.'"/>';
			print '</td>';
		}

		print "</tr>";
		$i++;
	}
	print '</table>';
	print '</div>';

	print '<script type="text/javascript">
        jQuery(document).ready(function() {
        	function init_savebutton()
        	{
	            console.log("We check if at least one line is checked")

    			atleastoneselected=0;
	    		jQuery(".checkforselect").each(function( index ) {
	  				/* console.log( index + ": " + $( this ).text() ); */
	  				if ($(this).is(\':checked\')) atleastoneselected++;
	  			});

	            if (atleastoneselected) jQuery("#changeaccount").removeAttr(\'disabled\');
	            else jQuery("#changeaccount").attr(\'disabled\',\'disabled\');
	            if (atleastoneselected) jQuery("#changeaccount").attr(\'class\',\'button\');
	            else jQuery("#changeaccount").attr(\'class\',\'button\');
        	}

        	jQuery(".checkforselect").change(function() {
        		init_savebutton();
        	});
        	jQuery(".productforselect").change(function() {
				console.log($(this).attr("id")+" "+$(this).val());
				if ($(this).val() && $(this).val() != -1) {
					$(".productforselect"+$(this).attr("id")).prop(\'checked\', true);
				} else {
					$(".productforselect"+$(this).attr("id")).prop(\'checked\', false);
				}
        		init_savebutton();
        	});

        	init_savebutton();

            jQuery("#search_current_account").keyup(function() {
        		if (jQuery("#search_current_account").val() != \'\')
                {
                    console.log("We set a value of account to search "+jQuery("#search_current_account").val()+", so we disable the other search criteria on account");
                    jQuery("#search_current_account_valid").val(-1);
                }
        	});
        });
        </script>';

	print '</form>';

	$db->free($resql);
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
