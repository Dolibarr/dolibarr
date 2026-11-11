<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010	   Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2026  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2026		Jose Martinez			<jose.martinez@pichinov.com>
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
 *    \file       htdocs/fourn/product/list.php
 *    \ingroup    product
 *    \brief      Page to list supplier products and services
 */


// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var ExtraFields $extrafields
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT .'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT .'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT .'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT .'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT .'/core/class/extrafields.class.php';


// Load translation files required by the page
$langs->loadLangs(array('products', 'suppliers'));

// Get Parameters
$action     = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view'; // The action 'create'/'add', 'edit'/'update', 'view', ...
$massaction = GETPOST('massaction', 'alpha');
$toselect   = GETPOST('toselect', 'array:int'); // Array of ids of elements selected into a list
$optioncss = GETPOST('optioncss', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'supplierpricelist'; // To manage different context of search
$mode       = GETPOST('mode', 'aZ'); // The output mode ('list', 'kanban', 'hierarchy', 'calendar', ...)

$sref = GETPOST('sref', 'alphanohtml');
$sRefSupplier = GETPOST('srefsupplier');
$snom = GETPOST('snom', 'alphanohtml');
$search_barcode = GETPOST('search_barcode', 'alphanohtml');
// Fields of the supplier price that can be filtered with a text or a numeric criterion (key of $arrayfields => array(name of the search parameter, 1 if numeric))
$arrayofsearchableprice = array(
	'ppf.rowid' => array('search_ppf_rowid', 1),
	'ppf.price' => array('search_price', 1),
	'ppf.quantity' => array('search_qty', 1),
	'ppf.unitprice' => array('search_unitprice', 1),
	'ppf.remise_percent' => array('search_remise_percent', 1),
	'ppf.remise' => array('search_remise', 1),
	'ppf.charges' => array('search_charges', 1),
	'ppf.packaging' => array('search_packaging', 1),
	'ppf.delivery_time_days' => array('search_delivery_time_days', 1),
	'ppf.default_vat_code' => array('search_default_vat_code', 0),
	'ppf.desc_fourn' => array('search_desc_fourn', 0),
	'ppf.import_key' => array('search_import_key', 0),
);
$search_price_fields = array();
foreach ($arrayofsearchableprice as $keyofsearch => $defofsearch) {
	$valueofsearch = trim(GETPOST($defofsearch[0], 'alphanohtml'));
	if ($valueofsearch !== '') {
		$search_price_fields[$keyofsearch] = $valueofsearch;
	}
}
$search_supplier_barcode = GETPOST('search_supplier_barcode', 'alphanohtml');
$search_tva_tx = GETPOST('search_tva_tx', 'array:alphanohtml');
// Keep only numeric rates
$search_tva_tx = array_values(array_unique(array_map('strval', array_filter($search_tva_tx, 'is_numeric'))));
$type = GETPOST('type', 'alphanohtml');

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT('page');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = "p.ref"; // Set here default search field
}
if (!$sortorder) {
	$sortorder = "ASC";
}

$fourn_id = GETPOST('fourn_id', 'intcomma');
if ($user->isExternalUser()) {
	$fourn_id = $user->isExternalUser();
}

$catid = GETPOST('catid', 'intcomma');

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('supplierpricelist'));

if (!$user->hasRight("produit", "lire") && !$user->hasRight("service", "lire")) {
	accessforbidden();
}

// Permissions
$permissiontoadd = ($user->hasRight('product', 'read') || $user->hasRight('service', 'read'));

// Initialize technical object
$object = new ProductFournisseurPrice($db);

// Fetch optionals attributes and labels
$extrafields = new ExtraFields($db);
$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');
// Definition of array of fields for columns. Keys are the real columns of the list, so that the selector of columns
// (multiSelectArrayWithCheckbox) controls the columns that are really output below.
$arrayfields = array(
	'p.ref' => array('label' => 'Ref', 'checked' => '1', 'position' => 10),
	'ppf.ref_fourn' => array('label' => 'RefSupplierShort', 'checked' => '1', 'position' => 20),
	'p.barcode' => array('label' => 'BarCode', 'checked' => '0', 'position' => 25),
	'ppf.barcode' => array('label' => $langs->trans('BarCode').' ('.$langs->trans('Supplier').')', 'checked' => '0', 'position' => 26),
	'p.label' => array('label' => 'Label', 'checked' => '1', 'position' => 30),
	'ppf.fk_soc' => array('label' => 'Supplier', 'checked' => '1', 'position' => 40),
	'ppf.price' => array('label' => 'BuyingPrice', 'checked' => '1', 'position' => 50),
	'ppf.quantity' => array('label' => 'QtyMin', 'checked' => '1', 'position' => 60),
	'ppf.unitprice' => array('label' => 'UnitPrice', 'checked' => '1', 'position' => 70),
	'ppf.tva_tx' => array('label' => 'VATRate', 'checked' => '0', 'position' => 80),
	'ppf.default_vat_code' => array('label' => $langs->trans('VATRate').' ('.$langs->trans('Code').')', 'checked' => '0', 'position' => 82),
	'ppf.localtax1_tx' => array('label' => 'LT1', 'checked' => '0', 'position' => 84),
	'ppf.localtax2_tx' => array('label' => 'LT2', 'checked' => '0', 'position' => 86),
	'ppf.remise_percent' => array('label' => 'Discount', 'checked' => '0', 'position' => 90),
	'ppf.remise' => array('label' => $langs->trans('Discount').' ('.$langs->trans('Amount').')', 'checked' => '0', 'position' => 95),
	'ppf.charges' => array('label' => 'Charges', 'enabled' => (string) getDolGlobalInt('PRODUCT_CHARGES'), 'checked' => '0', 'position' => 100),
	'ppf.packaging' => array('label' => 'PackagingForThisProduct', 'enabled' => (string) getDolGlobalInt('PRODUCT_USE_SUPPLIER_PACKAGING'), 'checked' => '0', 'position' => 110),
	'ppf.multicurrency_unitprice' => array('label' => 'UnitPriceHTCurrency', 'enabled' => (string) (int) isModEnabled('multicurrency'), 'checked' => '0', 'position' => 120),
	'ppf.multicurrency_price' => array('label' => 'PriceQtyMinHTCurrency', 'enabled' => (string) (int) isModEnabled('multicurrency'), 'checked' => '0', 'position' => 130),
	'ppf.delivery_time_days' => array('label' => 'NbDaysToDelivery', 'checked' => '0', 'position' => 140),
	'ppf.supplier_reputation' => array('label' => 'ReputationForThisProduct', 'checked' => '0', 'position' => 150),
	'ppf.fk_availability' => array('label' => 'Availability', 'enabled' => (string) getDolGlobalInt('FOURN_PRODUCT_AVAILABILITY'), 'checked' => '0', 'position' => 160),
	'ppf.fk_barcode_type' => array('label' => 'BarcodeType', 'enabled' => (string) (int) isModEnabled('barcode'), 'checked' => '0', 'position' => 170),
	'ppf.datec' => array('label' => 'AppliedPricesFrom', 'checked' => '0', 'position' => 180),
	'ppf.tms' => array('label' => 'DateModification', 'checked' => '0', 'position' => 190),
	'ppf.multicurrency_tx' => array('label' => 'CurrencyRate', 'enabled' => (string) (int) isModEnabled('multicurrency'), 'checked' => '0', 'position' => 135),
	'ppf.desc_fourn' => array('label' => 'Description', 'checked' => '0', 'position' => 165),
	'ppf.fk_supplier_price_expression' => array('label' => 'PriceExpressionEditor', 'enabled' => (string) (int) isModEnabled('dynamicprices'), 'checked' => '0', 'position' => 175),
	'ppf.fk_user' => array('label' => 'Author', 'checked' => '0', 'position' => 185),
	'ppf.import_key' => array('label' => 'ImportId', 'checked' => '0', 'position' => 195),
	'ppf.rowid' => array('label' => 'TechnicalID', 'checked' => '0', 'position' => 199),
	'ppf.status' => array('label' => 'Status', 'checked' => '0', 'position' => 200),
);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';
// Add hook to complete $arrayfield
$parameters = array('arrayfields' => &$arrayfields);
$reshook = $hookmanager->executeHooks('completeArrayFields', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

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
		$sref = '';
		$sRefSupplier = '';
		$snom = '';
		$search_tva_tx = array();
		$search_barcode = '';
		$search_price_fields = array();
		$search_supplier_barcode = '';
		$search_field1 = '';
		$search_field2 = '';
		$search_date_creation = '';
		$search_date_modification = '';
		$toselect = array();
		$search_array_options = array();
	}
}


/*
 * View
 */

$form = new Form($db);
$productstatic = new Product($db);
$productfournstatic = new ProductFournisseur($db);
if (getDolGlobalInt('FOURN_PRODUCT_AVAILABILITY')) {
	$form->load_cache_availability();
}
$companystatic = new Societe($db);

$title = $langs->trans('Supplier')." - ".$langs->trans('ProductsAndServices');

if ($fourn_id) {
	$supplier = new Fournisseur($db);
	$supplier->fetch($fourn_id);
}


// Build and execute select
// --------------------------------------------------------------------
$sql = "SELECT p.rowid, p.label, p.ref, p.fk_product_type, p.entity, p.tosell, p.tobuy, p.barcode, p.fk_barcode_type,";
$sql .= " ppf.fk_soc, ppf.ref_fourn, ppf.price as price, ppf.quantity as qty, ppf.unitprice, ppf.tva_tx, ppf.barcode as supplier_barcode,";
$sql .= " ppf.rowid as ppf_rowid, ppf.default_vat_code, ppf.localtax1_tx, ppf.localtax2_tx, ppf.remise, ppf.multicurrency_tx, ppf.desc_fourn, ppf.fk_supplier_price_expression, ppf.import_key, uu.login as ppf_user_login, ppf.remise_percent, ppf.charges, ppf.packaging, ppf.multicurrency_code, ppf.multicurrency_unitprice, ppf.multicurrency_price,";
$sql .= " ppf.delivery_time_days, ppf.supplier_reputation, ppf.fk_availability, ppf.datec as ppf_datec, ppf.tms as ppf_tms, ppf.status as ppf_status,";
$sql .= " bt.libelle as supplier_barcode_type,";
$sql .= " s.rowid as socid, s.nom as name";
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key." as options_".$key : '');
	}
}
// Add fields to SELECT from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
$sql .= $hookmanager->resPrint;

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= " FROM ".MAIN_DB_PREFIX."product as p";
if ($catid) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_product as cp ON cp.fk_product = p.rowid";
}
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as ppf ON p.rowid = ppf.fk_product AND ppf.entity IN (".getEntity('productsupplierprice').")";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON ppf.fk_soc = s.rowid AND s.entity IN (".getEntity('societe').")";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_barcode_type as bt ON ppf.fk_barcode_type = bt.rowid AND bt.entity IN (".getEntity('c_barcode_type').")";
if (!empty($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef ON ppf.rowid = ef.fk_object";
}
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as uu ON ppf.fk_user = uu.rowid";
// Add table from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " WHERE p.entity IN (".getEntity('product').")";
if ($sRefSupplier) {
	$sql .= natural_search('ppf.ref_fourn', $sRefSupplier);
}
if (GETPOST('type')) {
	$sql .= " AND p.fk_product_type = ".GETPOSTINT('type');
}
if ($sref) {
	$sql .= natural_search('p.ref', $sref);
}
if ($snom) {
	$sql .= natural_search('p.label', $snom);
}
if ($catid) {
	$sql .= " AND cp.fk_categorie = ".((int) $catid);
}
if ($fourn_id > 0) {
	$sql .= " AND ppf.fk_soc = ".((int) $fourn_id);
}
if (!empty($search_tva_tx)) {
	$sql .= " AND ppf.tva_tx IN (".$db->sanitize(implode(',', array_map(function (string $vatrate): string {
		return (string) ((float) $vatrate);
	}, $search_tva_tx)), 0, 0, 0, 0).")";
}
if ($search_barcode != '') {
	$sql .= natural_search('p.barcode', $search_barcode);
}
if ($search_supplier_barcode != '') {
	$sql .= natural_search('ppf.barcode', $search_supplier_barcode);
}
foreach ($search_price_fields as $keyofsearch => $valueofsearch) {
	$sql .= natural_search($keyofsearch, $valueofsearch, $arrayofsearchableprice[$keyofsearch][1]);
}
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';

// Add WHERE filters from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
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

	if (($page * $limit) > (int) $nbtotalofrecords) {	// if total resultset is smaller than the paging size (filtering), goto and load page 0
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

dol_syslog("fourn/product/list.php:", LOG_DEBUG);
$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);

$i = 0;
$help_url = '';
if ($num == 1 && (GETPOST("mode") == 'search')) {
	$objp = $db->fetch_object($resql);
	header("Location: ".DOL_URL_ROOT."/product/card.php?id=".$objp->rowid);
	exit;
}

if (!empty($supplier->id)) {
	$title = $langs->trans("ListOfSupplierProductForSupplier", $supplier->name);
} else {
	$title = $langs->trans("List");
}


// Output page
// --------------------------------------------------------------------

llxHeader("", $title, $help_url, '', 0, 0, '', '', '', 'bodyforlist mod-fourn-product page-list');

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
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
if ($sref != '') {
	$param .= '&sref='.urlencode($sref);
}
if ($sRefSupplier != '') {
	$param .= '&srefsupplier='.urlencode($sRefSupplier);
}
if ($snom != '') {
	$param .= '&snom='.urlencode($snom);
}
foreach ($search_tva_tx as $searchvatrate) {
	$param .= '&search_tva_tx[]='.urlencode($searchvatrate);
}
if ($search_barcode != '') {
	$param .= '&search_barcode='.urlencode($search_barcode);
}
if ($search_supplier_barcode != '') {
	$param .= '&search_supplier_barcode='.urlencode($search_supplier_barcode);
}
foreach ($search_price_fields as $keyofsearch => $valueofsearch) {
	$param .= '&'.$arrayofsearchableprice[$keyofsearch][0].'='.urlencode($valueofsearch);
}
if ($fourn_id > 0) {
	$param .= '&fourn_id='.((int) $fourn_id);
}
if ($type !== '') {
	$param .= '&type='.urlencode($type);
}
if ($catid) {
	$param .= '&catid='.((int) $catid);
}
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// Add $param from hooks
$parameters = array('param' => &$param);
$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;

$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/product/list.php?action=create&backtopage='.urlencode($_SERVER['PHP_SELF']), '', (int) $permissiontoadd);

$arrayofmassactions = array(
	//'generate_doc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
	//'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
	//'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
);
if (!empty($permissiontodelete)) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if (GETPOSTINT('nomassaction') || in_array($massaction, array('presend', 'predelete'))) {
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
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
if ($fourn_id > 0) {
	print '<input type="hidden" name="fourn_id" value="'.$fourn_id.'">';
}
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
//print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="mode" value="'.$mode.'">';
print '<input type="hidden" name="type" value="'.$type.'">';
if ($catid) {
	print '<input type="hidden" name="catid" value="'.((int) $catid).'">';
}

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'product', 0, $newcardbutton, '', $limit, 0, 0, 1);

if (!empty($catid)) {
	print "<div id='ways'>";
	$c = new Categorie($db);
	$ways = $c->print_all_ways('auto', 'fourn/product/list.php');
	print " &gt; ".$ways[0]."<br>\n";
	print "</div><br>";
}


$moreforfilter = '';
$topicmail = "Information";
$modelmail = "product";
$objecttmp = new Product($db);
$trackid = 'prod'.$productstatic->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$htmlofselectarray = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, $conf->main_checkbox_left_column);  // This also change content of $arrayfields with user setup
$selectedfields = ($mode != 'kanban' ? $htmlofselectarray : '');
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

$parameters = array('arrayfields' => $arrayfields);
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '<div class="div-table-responsive-no-min">';
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre_filter">';

// Action column
if ($conf->main_checkbox_left_column) {
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}
if (!empty($arrayfields['p.ref']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth100" type="text" name="sref" value="'.$sref.'">';
	print '</td>';
}
if (!empty($arrayfields['ppf.ref_fourn']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth100" type="text" name="srefsupplier" value="'.$sRefSupplier.'">';
	print '</td>';
}
if (!empty($arrayfields['p.barcode']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth100" type="text" name="search_barcode" value="'.dol_escape_htmltag($search_barcode).'">';
	print '</td>';
}
if (!empty($arrayfields['ppf.barcode']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth100" type="text" name="search_supplier_barcode" value="'.dol_escape_htmltag($search_supplier_barcode).'">';
	print '</td>';
}
if (!empty($arrayfields['p.label']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth100" type="text" name="snom" value="'.$snom.'">';
	print '</td>';
}
foreach (array('ppf.fk_soc', 'ppf.price', 'ppf.quantity', 'ppf.unitprice') as $keyofsearchcell) {
	if (!empty($arrayfields[$keyofsearchcell]['checked'])) {
		print '<td class="liste_titre'.(isset($arrayofsearchableprice[$keyofsearchcell]) ? ' right' : '').'">';
		if (isset($arrayofsearchableprice[$keyofsearchcell])) {
			print '<input class="flat maxwidth50 right" type="text" name="'.$arrayofsearchableprice[$keyofsearchcell][0].'" value="'.dol_escape_htmltag(isset($search_price_fields[$keyofsearchcell]) ? $search_price_fields[$keyofsearchcell] : '').'">';
		}
		print '</td>';
	}
}
if (!empty($arrayfields['ppf.tva_tx']['checked'])) {
	// List of VAT rates of the dictionary of VAT rates, for the country of the company
	$form->load_cache_vatrates("'".$mysoc->country_code."'");
	$arrayofvatrates = array();
	foreach ($form->cache_vatrates as $vatdef) {
		$arrayofvatrates[(string) ((float) $vatdef['txtva'])] = vatrate($vatdef['txtva'], true);
	}
	foreach ($search_tva_tx as $searchvatrate) {	// A selected rate that is not in the dictionary is still shown
		if (!isset($arrayofvatrates[$searchvatrate])) {
			$arrayofvatrates[$searchvatrate] = vatrate($searchvatrate, true);
		}
	}
	uksort($arrayofvatrates, function (string $a, string $b): int {
		return (float) $a <=> (float) $b;
	});
	print '<td class="liste_titre center">';
	print $form->multiselectarray('search_tva_tx', $arrayofvatrates, $search_tva_tx, 0, 0, 'minwidth75 maxwidth100', 0, 0);
	print '</td>';
}
foreach (array('ppf.default_vat_code', 'ppf.localtax1_tx', 'ppf.localtax2_tx', 'ppf.remise_percent', 'ppf.remise', 'ppf.charges', 'ppf.packaging', 'ppf.multicurrency_unitprice', 'ppf.multicurrency_price', 'ppf.multicurrency_tx', 'ppf.delivery_time_days', 'ppf.supplier_reputation', 'ppf.fk_availability', 'ppf.desc_fourn', 'ppf.fk_barcode_type', 'ppf.fk_supplier_price_expression', 'ppf.datec', 'ppf.tms', 'ppf.fk_user', 'ppf.import_key', 'ppf.rowid', 'ppf.status') as $keyofsearchcell) {
	if (!empty($arrayfields[$keyofsearchcell]['checked'])) {
		print '<td class="liste_titre'.(isset($arrayofsearchableprice[$keyofsearchcell]) ? ' right' : '').'">';
		if (isset($arrayofsearchableprice[$keyofsearchcell])) {
			print '<input class="flat maxwidth75'.($arrayofsearchableprice[$keyofsearchcell][1] ? ' right' : '').'" type="text" name="'.$arrayofsearchableprice[$keyofsearchcell][0].'" value="'.dol_escape_htmltag(isset($search_price_fields[$keyofsearchcell]) ? $search_price_fields[$keyofsearchcell] : '').'">';
		}
		print '</td>';
	}
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
// Fields from hook
$parameters = array('arrayfields' => $arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Action column
if (!$conf->main_checkbox_left_column) {
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}
print '</tr>'."\n";

$totalarray = [
	'nbfield' => 0
];

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
// Action column
if ($conf->main_checkbox_left_column) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
$listoftitles = array(
	'p.ref' => array('Ref', ''),
	'ppf.ref_fourn' => array('RefSupplierShort', ''),
	'p.barcode' => array('BarCode', ''),
	'ppf.barcode' => array($arrayfields['ppf.barcode']['label'], ''),
	'p.label' => array('Label', ''),
	'ppf.fk_soc' => array('Supplier', ''),
	'ppf.price' => array('BuyingPrice', 'right '),
	'ppf.quantity' => array('QtyMin', 'right '),
	'ppf.unitprice' => array('UnitPrice', 'right '),
	'ppf.tva_tx' => array('VATRate', 'right '),
	'ppf.default_vat_code' => array($arrayfields['ppf.default_vat_code']['label'], ''),
	'ppf.localtax1_tx' => array('LT1', 'right '),
	'ppf.localtax2_tx' => array('LT2', 'right '),
	'ppf.remise_percent' => array('Discount', 'right '),
	'ppf.remise' => array($arrayfields['ppf.remise']['label'], 'right '),
	'ppf.charges' => array('Charges', 'right '),
	'ppf.packaging' => array('PackagingForThisProduct', 'right '),
	'ppf.multicurrency_unitprice' => array('UnitPriceHTCurrency', 'right '),
	'ppf.multicurrency_price' => array('PriceQtyMinHTCurrency', 'right '),
	'ppf.multicurrency_tx' => array('CurrencyRate', 'right '),
	'ppf.delivery_time_days' => array('NbDaysToDelivery', 'right '),
	'ppf.supplier_reputation' => array('ReputationForThisProduct', ''),
	'ppf.fk_availability' => array('Availability', ''),
	'ppf.desc_fourn' => array('Description', ''),
	'ppf.fk_barcode_type' => array('BarcodeType', ''),
	'ppf.fk_supplier_price_expression' => array('PriceExpressionEditor', 'right '),
	'ppf.datec' => array('AppliedPricesFrom', 'center '),
	'ppf.tms' => array('DateModification', 'center '),
	'ppf.fk_user' => array('Author', ''),
	'ppf.import_key' => array('ImportId', ''),
	'ppf.rowid' => array('TechnicalID', 'right '),
	'ppf.status' => array('Status', 'center '),
);
foreach ($listoftitles as $key => $titledef) {
	if (!empty($arrayfields[$key]['checked'])) {
		print_liste_field_titre($titledef[0], $_SERVER["PHP_SELF"], $key, "", $param, "", $sortfield, $sortorder, $titledef[1]);
		$totalarray['nbfield']++;
	}
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder, 'totalarray' => &$totalarray);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
print $hookmanager->resPrint;
// Action column
if (!$conf->main_checkbox_left_column) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
print '</tr>'."\n";

// Loop on record
// --------------------------------------------------------------------
$i = 0;
$savnbfield = $totalarray['nbfield'];
$totalarray = array();
$totalarray['nbfield'] = 0;
$imaxinloop = ($limit ? min($num, $limit) : $num);
while ($i < $imaxinloop) {
	$objp = $db->fetch_object($resql);
	if (empty($objp)) {
		break; // Should not happen
	}

	$productstatic->id = $objp->rowid;
	$productstatic->ref = $objp->ref;
	$productstatic->type = $objp->fk_product_type;
	$productstatic->entity = $objp->entity;
	$productstatic->status = $objp->tosell;
	$productstatic->status_buy = $objp->tobuy;
	$productstatic->barcode = $objp->barcode;
	$productstatic->barcode_type = $objp->fk_barcode_type;

	print '<tr data-rowid="'.$productstatic->id.'" class="oddeven row-with-select">';

	// Action column
	if ($conf->main_checkbox_left_column) {
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($productstatic->id, $arrayofselected)) {
				$selected = 1;
			}
			print '<input id="cb'.$productstatic->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$productstatic->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['p.ref']['checked'])) {
		print '<td>';
		print $productstatic->getNomUrl(1, 'supplier');
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.ref_fourn']['checked'])) {
		print '<td>'.$objp->ref_fourn.'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['p.barcode']['checked'])) {
		print '<td>'.dol_escape_htmltag($objp->barcode).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.barcode']['checked'])) {
		print '<td>'.dol_escape_htmltag($objp->supplier_barcode).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['p.label']['checked'])) {
		print '<td>'.$objp->label.'</td>'."\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.fk_soc']['checked'])) {
		$companystatic->name = $objp->name;
		$companystatic->id = $objp->socid;
		print '<td>';
		if ($companystatic->id > 0) {
			print $companystatic->getNomUrl(1, 'supplier');
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.price']['checked'])) {
		print '<td class="right">'.(isset($objp->price) ? price($objp->price) : '').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.quantity']['checked'])) {
		print '<td class="right">'.$objp->qty.'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.unitprice']['checked'])) {
		print '<td class="right">'.(isset($objp->unitprice) ? price($objp->unitprice) : '').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.tva_tx']['checked'])) {
		print '<td class="right">'.(isset($objp->tva_tx) ? vatrate($objp->tva_tx, true) : '').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.default_vat_code']['checked'])) {
		print '<td>'.dol_escape_htmltag($objp->default_vat_code).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.localtax1_tx']['checked'])) {
		print '<td class="right">'.(isset($objp->localtax1_tx) ? vatrate($objp->localtax1_tx, true) : '').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.localtax2_tx']['checked'])) {
		print '<td class="right">'.(isset($objp->localtax2_tx) ? vatrate($objp->localtax2_tx, true) : '').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.remise_percent']['checked'])) {
		print '<td class="right">'.($objp->remise_percent ? price2num($objp->remise_percent).'%' : '').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.remise']['checked'])) {
		print '<td class="right">'.($objp->remise ? price($objp->remise) : '').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.charges']['checked'])) {
		print '<td class="right">'.(isset($objp->charges) ? price($objp->charges) : '').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.packaging']['checked'])) {
		print '<td class="right">'.(isset($objp->packaging) ? price2num($objp->packaging) : '').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.multicurrency_unitprice']['checked'])) {
		print '<td class="right">'.(isset($objp->multicurrency_unitprice) ? price($objp->multicurrency_unitprice, 0, $langs, 1, -1, -1, $objp->multicurrency_code) : '').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.multicurrency_price']['checked'])) {
		print '<td class="right">'.(isset($objp->multicurrency_price) ? price($objp->multicurrency_price, 0, $langs, 1, -1, -1, $objp->multicurrency_code) : '').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.multicurrency_tx']['checked'])) {
		print '<td class="right">'.(isset($objp->multicurrency_tx) ? price2num($objp->multicurrency_tx) : '').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.delivery_time_days']['checked'])) {
		print '<td class="right">'.$objp->delivery_time_days.'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.supplier_reputation']['checked'])) {
		print '<td>'.(!empty($objp->supplier_reputation) && !empty($productfournstatic->reputations[$objp->supplier_reputation]) ? $productfournstatic->reputations[$objp->supplier_reputation] : '').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.fk_availability']['checked'])) {
		print '<td>'.(!empty($objp->fk_availability) && !empty($form->cache_availability[$objp->fk_availability]['label']) ? $form->cache_availability[$objp->fk_availability]['label'] : '').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.desc_fourn']['checked'])) {
		print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag(dol_string_nohtmltag($objp->desc_fourn)).'">'.dol_escape_htmltag(dol_trunc(dol_string_nohtmltag($objp->desc_fourn), 80)).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.fk_barcode_type']['checked'])) {
		print '<td>'.dol_escape_htmltag($objp->supplier_barcode_type).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.fk_supplier_price_expression']['checked'])) {
		print '<td class="right">'.$objp->fk_supplier_price_expression.'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.datec']['checked'])) {
		print '<td class="center nowraponall">'.dol_print_date($db->jdate($objp->ppf_datec), 'dayhour', 'tzuser').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.tms']['checked'])) {
		print '<td class="center nowraponall">'.dol_print_date($db->jdate($objp->ppf_tms), 'dayhour', 'tzuser').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.fk_user']['checked'])) {
		print '<td>'.dol_escape_htmltag($objp->ppf_user_login).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.import_key']['checked'])) {
		print '<td>'.dol_escape_htmltag($objp->import_key).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.rowid']['checked'])) {
		print '<td class="right">'.$objp->ppf_rowid.'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ppf.status']['checked'])) {
		print '<td class="center">'.($objp->ppf_status ? dolGetStatus($langs->transnoentitiesnoconv('Enabled'), '', '', 'status4', 3) : dolGetStatus($langs->transnoentitiesnoconv('Disabled'), '', '', 'status5', 3)).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Extra fields
	$obj = $objp;
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';

	// Fields from hook
	$parameters = array('arrayfields' => $arrayfields, 'obj' => $objp, 'i' => $i, 'totalarray' => &$totalarray);
	$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object, $action);
	if ($reshook < 0) {
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	}
	print $hookmanager->resPrint;

	// Action column
	if (!$conf->main_checkbox_left_column) {
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($productstatic->id, $arrayofselected)) {
				$selected = 1;
			}
			print '<input id="cb'.$productstatic->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$productstatic->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	print '</tr>'."\n";
	$i++;
}

// Show total line
include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

// If no record found
if ($num == 0) {
	$colspan = 1; // The column of the selector of fields / mass actions
	foreach ($arrayfields as $key => $val) {
		if (!empty($val['checked'])) {
			$colspan++;
		}
	}
	print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
}

$db->free($resql);

$parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $productstatic, $action); // Note that $action and $productstatic may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";


// End of page
llxFooter();
$db->close();
