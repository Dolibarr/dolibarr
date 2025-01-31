<?php
/* Copyright (C) 2001-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2019  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2013-2015  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2016-2024  Josep Lluis Amador      <joseplluis@lliuretic.cat>
 * Copyright (C) 2016       Ferran Marcet      	    <fmarcet@2byte.es>
 * Copyright (C) 2017       Rui Strecht      	    <rui.strecht@aliartalentos.com>
 * Copyright (C) 2017       Juanjo Menent      	    <jmenent@2byte.es>
 * Copyright (C) 2018       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2020       Open-Dsi                <support@open-dsi.fr>
 * Copyright (C) 2021-2024	Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2022       Anthony Berton          <anthony.berton@bb2a.fr>
 * Copyright (C) 2023       William Mead            <william.mead@manchenumerique.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Benjamin Falière		<benjamin.faliere@altairis.fr>
 * Copyright (C) 2024       Nick Fragoulis
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
 *	\file       htdocs/societe/list.php
 *	\ingroup    societe
 *	\brief      Page to show list of third parties
 */

// Load Dolibarr environment
require_once '../main.inc.php';
include_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
if (isModEnabled('category')) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcategory.class.php';
}

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("companies", "commercial", "customers", "suppliers", "bills", "compta", "categories", "cashdesk"));


// Get parameters
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOSTINT('show_files');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'thirdpartylist';
$optioncss = GETPOST('optioncss', 'alpha');
if ($contextpage == 'poslist') {
	$optioncss = 'print';
}
$mode = GETPOST("mode", 'alpha');

// search fields
$search_all = trim(GETPOST('search_all', 'alphanohtml'));
$search_cti = preg_replace('/^0+/', '', preg_replace('/[^0-9]/', '', GETPOST('search_cti', 'alphanohtml'))); // Phone number without any special chars

$search_id = GETPOST("search_id", 'int');
$search_nom = trim(GETPOST("search_nom", 'restricthtml'));
$search_alias = trim(GETPOST("search_alias", 'restricthtml'));
$search_nom_only = trim(GETPOST("search_nom_only", 'restricthtml'));
$search_ref_ext = trim(GETPOST("search_ref_ext", 'restricthtml'));
$search_barcode = trim(GETPOST("search_barcode", 'alpha'));
$search_customer_code = trim(GETPOST('search_customer_code', 'alpha'));
$search_supplier_code = trim(GETPOST('search_supplier_code', 'alpha'));
$search_account_customer_code = trim(GETPOST('search_account_customer_code', 'alpha'));
$search_account_supplier_code = trim(GETPOST('search_account_supplier_code', 'alpha'));
$search_address = trim(GETPOST('search_address', 'alpha'));
$search_zip = trim(GETPOST("search_zip", 'alpha'));
$search_town = trim(GETPOST("search_town", 'alpha'));
$search_state = trim(GETPOST("search_state", 'alpha'));
$search_region = trim(GETPOST("search_region", 'alpha'));
$search_email = trim(GETPOST('search_email', 'alpha'));
$search_phone = trim(GETPOST('search_phone', 'alpha'));
$search_phone_mobile = trim(GETPOST('search_phone_mobile', 'alpha'));
$search_fax = trim(GETPOST('search_fax', 'alpha'));
$search_url = trim(GETPOST('search_url', 'alpha'));
$search_idprof1 = trim(GETPOST('search_idprof1', 'alpha'));
$search_idprof2 = trim(GETPOST('search_idprof2', 'alpha'));
$search_idprof3 = trim(GETPOST('search_idprof3', 'alpha'));
$search_idprof4 = trim(GETPOST('search_idprof4', 'alpha'));
$search_idprof5 = trim(GETPOST('search_idprof5', 'alpha'));
$search_idprof6 = trim(GETPOST('search_idprof6', 'alpha'));
$search_vat = trim(GETPOST('search_vat', 'alpha'));
$search_sale = "";
if (GETPOSTISARRAY('search_sale')) {
	$search_sale = GETPOST('search_sale', 'array:int');
} elseif (GETPOSTISSET('search_sale')) {
	$search_sale = array(GETPOSTINT('search_sale'));
}
$search_categ_cus = GETPOSTINT("search_categ_cus");
$search_categ_sup = GETPOSTINT("search_categ_sup");
$searchCategoryCustomerOperator = GETPOSTINT('search_category_customer_operator');
$searchCategorySupplierOperator = GETPOSTINT('search_category_supplier_operator');
if (GETPOSTISSET('formfilteraction')) {
	$searchCategoryCustomerOperator = GETPOST('search_category_customer_operator');
	$searchCategorySupplierOperator = GETPOST('search_category_supplier_operator');
} elseif (getDolGlobalString('MAIN_SEARCH_CAT_OR_BY_DEFAULT')) {
	$searchCategoryCustomerOperator = getDolGlobalString('MAIN_SEARCH_CAT_OR_BY_DEFAULT');
	$searchCategorySupplierOperator = getDolGlobalString('MAIN_SEARCH_CAT_OR_BY_DEFAULT');
}
$searchCategoryCustomerList = GETPOST('search_category_customer_list', 'array');
if (!empty($search_categ_cus) && empty($searchCategoryCustomerList)) {
	$searchCategoryCustomerList = array($search_categ_cus);
}
$searchCategorySupplierList = GETPOST('search_category_supplier_list', 'array');
if (!empty($search_categ_sup) && empty($searchCategorySupplierList)) {
	$searchCategorySupplierList = array($search_categ_sup);
}
$search_country = GETPOST("search_country", 'aZ09');
$search_type_thirdparty = GETPOST("search_type_thirdparty", 'intcomma');
$search_price_level = GETPOST('search_price_level', 'int');
$search_staff = GETPOST("search_staff", 'int');
$search_legalform = GETPOST("search_legalform", 'int');
$search_status = GETPOST("search_status", 'intcomma');
$search_type = GETPOST('search_type', 'alpha');
$search_level = GETPOST("search_level", "array:alpha");
$search_stcomm = GETPOST('search_stcomm', "array:int");
$search_import_key  = trim(GETPOST("search_import_key", "alpha"));
$search_parent_name = trim(GETPOST('search_parent_name', 'alpha'));

$search_date_creation_startmonth = GETPOSTINT('search_date_creation_startmonth');
$search_date_creation_startyear = GETPOSTINT('search_date_creation_startyear');
$search_date_creation_startday = GETPOSTINT('search_date_creation_startday');
$search_date_creation_start = dol_mktime(0, 0, 0, $search_date_creation_startmonth, $search_date_creation_startday, $search_date_creation_startyear);	// Use tzserver
$search_date_creation_endmonth = GETPOSTINT('search_date_creation_endmonth');
$search_date_creation_endyear = GETPOSTINT('search_date_creation_endyear');
$search_date_creation_endday = GETPOSTINT('search_date_creation_endday');
$search_date_creation_end = dol_mktime(23, 59, 59, $search_date_creation_endmonth, $search_date_creation_endday, $search_date_creation_endyear);	// Use tzserver

$search_date_modif_startmonth = GETPOSTINT('search_date_modif_startmonth');
$search_date_modif_startyear = GETPOSTINT('search_date_modif_startyear');
$search_date_modif_startday = GETPOSTINT('search_date_modif_startday');
$search_date_modif_start = dol_mktime(0, 0, 0, $search_date_modif_startmonth, $search_date_modif_startday, $search_date_modif_startyear);	// Use tzserver
$search_date_modif_endmonth = GETPOSTINT('search_date_modif_endmonth');
$search_date_modif_endyear = GETPOSTINT('search_date_modif_endyear');
$search_date_modif_endday = GETPOSTINT('search_date_modif_endday');
$search_date_modif_end = dol_mktime(23, 59, 59, $search_date_modif_endmonth, $search_date_modif_endday, $search_date_modif_endyear);	// Use tzserver

$type = GETPOST('type', 'alpha');
$place = GETPOST('place', 'aZ09') ? GETPOST('place', 'aZ09') : '0'; // $place is string id of table for Bar or Restaurant

$diroutputmassaction = $conf->societe->dir_output.'/temp/massgeneration/'.$user->id;

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "s.nom";
}
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if ($type == 'c') {
	if (empty($contextpage) || $contextpage == 'thirdpartylist') {
		$contextpage = 'customerlist';
	}
	if ($search_type == '') {
		$search_type = '1,3';
	}
}
if ($type == 'p') {
	if (empty($contextpage) || $contextpage == 'thirdpartylist') {
		$contextpage = 'prospectlist';
	}
	if ($search_type == '') {
		$search_type = '2,3';
	}
}
if ($type == 't') {
	if (empty($contextpage) || $contextpage == 'poslist') {
		$contextpage = 'poslist';
	}
	if ($search_type == '') {
		$search_type = '1,2,3';
	}
}
if ($type == 'f') {
	if (empty($contextpage) || $contextpage == 'thirdpartylist') {
		$contextpage = 'supplierlist';
	}
	if ($search_type == '') {
		$search_type = '4';
	}
}
// Initialize a technical objects to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$object = new Societe($db);
$extrafields = new ExtraFields($db);
$hookmanager->initHooks(array($contextpage, 'thirdpartylist'));

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	's.nom' => "ThirdPartyName",
	's.name_alias' => "AliasNameShort",
	's.code_client' => "CustomerCode",
	's.code_fournisseur' => "SupplierCode",
	's.code_compta' => "CustomerAccountancyCodeShort",
	's.code_compta_fournisseur' => "SupplierAccountancyCodeShort",
	's.zip' => "Zip",
	's.town' => "Town",
	's.email' => "EMail",
	's.url' => "URL",
	's.tva_intra' => "VATIntra",
	's.siren' => "ProfId1",
	's.siret' => "ProfId2",
	's.ape' => "ProfId3",
	's.phone' => "Phone",
	's.phone_mobile' => "PhoneMobile",
	's.fax' => "Fax",
);
if (($tmp = $langs->transnoentities("ProfId4".$mysoc->country_code)) && $tmp != "ProfId4".$mysoc->country_code && $tmp != '-') {
	$fieldstosearchall['s.idprof4'] = 'ProfId4';
}
if (($tmp = $langs->transnoentities("ProfId5".$mysoc->country_code)) && $tmp != "ProfId5".$mysoc->country_code && $tmp != '-') {
	$fieldstosearchall['s.idprof5'] = 'ProfId5';
}
if (($tmp = $langs->transnoentities("ProfId6".$mysoc->country_code)) && $tmp != "ProfId6".$mysoc->country_code && $tmp != '-') {
	$fieldstosearchall['s.idprof6'] = 'ProfId6';
}
if (isModEnabled('barcode')) {
	$fieldstosearchall['s.barcode'] = 'Gencod';
}
// Personalized search criteria. Example: $conf->global->THIRDPARTY_QUICKSEARCH_ON_FIELDS = 's.nom=ThirdPartyName;s.name_alias=AliasNameShort;s.code_client=CustomerCode'
if (getDolGlobalString('THIRDPARTY_QUICKSEARCH_ON_FIELDS')) {
	$fieldstosearchall = dolExplodeIntoArray($conf->global->THIRDPARTY_QUICKSEARCH_ON_FIELDS);
}


// Define list of fields to show into list
$checkedcustomercode = (in_array($contextpage, array('thirdpartylist', 'customerlist', 'prospectlist', 'poslist')) ? 1 : 0);
$checkedsuppliercode = (in_array($contextpage, array('supplierlist')) ? 1 : 0);
$checkedcustomeraccountcode = (in_array($contextpage, array('customerlist')) ? 1 : 0);
$checkedsupplieraccountcode = (in_array($contextpage, array('supplierlist')) ? 1 : 0);
$checkedtypetiers = 1;
$checkedprofid1 = 0;
$checkedprofid2 = 0;
$checkedprofid3 = 0;
$checkedprofid4 = 0;
$checkedprofid5 = 0;
$checkedprofid6 = 0;
//$checkedprofid4=((($tmp = $langs->transnoentities("ProfId4".$mysoc->country_code)) && $tmp != "ProfId4".$mysoc->country_code && $tmp != '-') ? 1 : 0);
//$checkedprofid5=((($tmp = $langs->transnoentities("ProfId5".$mysoc->country_code)) && $tmp != "ProfId5".$mysoc->country_code && $tmp != '-') ? 1 : 0);
//$checkedprofid6=((($tmp = $langs->transnoentities("ProfId6".$mysoc->country_code)) && $tmp != "ProfId6".$mysoc->country_code && $tmp != '-') ? 1 : 0);
$checkprospectlevel = (in_array($contextpage, array('prospectlist')) ? 1 : 0);
$checkstcomm = (in_array($contextpage, array('prospectlist')) ? 1 : 0);
$arrayfields = array(
	's.rowid' => array('label' => "TechnicalID", 'position' => 1, 'checked' => -1, 'enabled' => 1),
	's.nom' => array('label' => "ThirdPartyName", 'position' => 2, 'checked' => 1),
	's.name_alias' => array('label' => "AliasNameShort", 'position' => 3, 'checked' => 1),
	's.ref_ext' => array('label' => "RefExt", 'position' => 4, 'checked' => -1, 'enabled' => getDolGlobalInt('MAIN_LIST_SHOW_REF_EXT')),
	's.barcode' => array('label' => "Gencod", 'position' => 5, 'checked' => 1, 'enabled' => isModEnabled('barcode')),
	's.code_client' => array('label' => "CustomerCodeShort", 'position' => 10, 'checked' => $checkedcustomercode),
	's.code_fournisseur' => array('label' => "SupplierCodeShort", 'position' => 11, 'checked' => $checkedsuppliercode, 'enabled' => (isModEnabled("supplier_order") || isModEnabled("supplier_invoice"))),
	's.code_compta' => array('label' => "CustomerAccountancyCodeShort", 'position' => 13, 'checked' => $checkedcustomeraccountcode),
	's.code_compta_fournisseur' => array('label' => "SupplierAccountancyCodeShort", 'position' => 14, 'checked' => $checkedsupplieraccountcode, 'enabled' => (isModEnabled("supplier_order") || isModEnabled("supplier_invoice"))),
	's.address' => array('label' => "Address", 'position' => 19, 'checked' => 0),
	's.zip' => array('label' => "Zip", 'position' => 20, 'checked' => 1),
	's.town' => array('label' => "Town", 'position' => 21, 'checked' => 0),
	'state.nom' => array('label' => "State", 'position' => 22, 'checked' => 0),
	'region.nom' => array('label' => "Region", 'position' => 23, 'checked' => 0),
	'country.code_iso' => array('label' => "Country", 'position' => 24, 'checked' => 0),
	's.email' => array('label' => "Email", 'position' => 25, 'checked' => 0),
	's.url' => array('label' => "Url", 'position' => 26, 'checked' => 0),
	's.phone' => array('label' => "Phone", 'position' => 27, 'checked' => 1),
	's.fax' => array('label' => "Fax", 'position' => 28, 'checked' => 0),
	'typent.code' => array('label' => "ThirdPartyType", 'position' => 29, 'checked' => $checkedtypetiers),
	'staff.code' => array('label' => "Workforce", 'position' => 31, 'checked' => 0),
	'legalform.code' => array('label' => 'JuridicalStatus', 'position' => 32, 'checked' => 0),
	's.phone_mobile' => array('label' => "PhoneMobile", 'position' => 35, 'checked' => 0),
	's.siren' => array('label' => "ProfId1Short", 'position' => 40, 'checked' => $checkedprofid1),
	's.siret' => array('label' => "ProfId2Short", 'position' => 41, 'checked' => $checkedprofid2),
	's.ape' => array('label' => "ProfId3Short", 'position' => 42, 'checked' => $checkedprofid3),
	's.idprof4' => array('label' => "ProfId4Short", 'position' => 43, 'checked' => $checkedprofid4),
	's.idprof5' => array('label' => "ProfId5Short", 'position' => 44, 'checked' => $checkedprofid5),
	's.idprof6' => array('label' => "ProfId6Short", 'position' => 45, 'checked' => $checkedprofid6),
	's.tva_intra' => array('label' => "VATIntraShort", 'position' => 50, 'checked' => 0),
	'customerorsupplier' => array('label' => 'NatureOfThirdParty', 'position' => 61, 'checked' => 1),
	's.fk_prospectlevel' => array('label' => "ProspectLevel", 'position' => 62, 'checked' => $checkprospectlevel),
	's.fk_stcomm' => array('label' => "StatusProsp", 'position' => 63, 'checked' => $checkstcomm),
	's2.nom' => array('label' => 'ParentCompany', 'position' => 64, 'checked' => 0),
	's.ip' => array('type'=>'ip', 'label' => "IPAddress", 'checked' => -2, 'position' => 500),
	's.datec' => array('label' => "DateCreation", 'checked' => 0, 'position' => 501),
	's.tms' => array('label' => "DateModificationShort", 'checked' => 0, 'position' => 505),
	's.note_public' => array('label' => 'NotePublic', 'checked' => 0, 'position' => 520, 'enabled' => (!getDolGlobalInt('MAIN_LIST_HIDE_PUBLIC_NOTES'))),
	's.note_private' => array('label' => 'NotePrivate', 'checked' => 0, 'position' => 521, 'enabled' => (!getDolGlobalInt('MAIN_LIST_HIDE_PRIVATE_NOTES'))),
	's.status' => array('label' => "Status", 'checked' => 1, 'position' => 1000),
	's.import_key' => array('label' => "ImportId", 'checked' => 0, 'position' => 1100),
);
if (getDolGlobalString('PRODUIT_MULTIPRICES') || getDolGlobalString('PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES') || getDolGlobalString('PRODUIT_CUSTOMER_PRICES_AND_MULTIPRICES')) {
	$arrayfields['s.price_level'] = array('label' => "PriceLevel", 'position' => 30, 'checked' => 0);
}

// Add non object fields to fields for list
$arrayfields['sales.representative'] = array('label' => $langs->trans("SalesRepresentatives"), 'checked' => 1, 'position' => 12);

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');
'@phan-var-force array<string,array{label:string,checked?:int<0,1>,position?:int,help?:string}> $arrayfields';  // dol_sort_array looses type for Phan

// Security check
$socid = GETPOSTINT('socid');
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'societe', $socid, '');

$permissiontoadd = $user->hasRight('societe', 'lire');


/*
 * Actions
 */

if ($action == "change" && $user->hasRight('takepos', 'run')) {	// Change customer for TakePOS
	$idcustomer = GETPOSTINT('idcustomer');

	// Check if draft invoice already exists, if not create it
	$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."facture where ref='(PROV-POS".$_SESSION["takeposterminal"]."-".$place.")' AND entity IN (".getEntity('invoice').")";
	$result = $db->query($sql);
	$num_lines = $db->num_rows($result);
	if ($num_lines == 0) {
		require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		$invoice = new Facture($db);
		$constforthirdpartyid = 'CASHDESK_ID_THIRDPARTY'.$_SESSION["takeposterminal"];
		$invoice->socid = getDolGlobalInt($constforthirdpartyid);
		$invoice->date = dol_now();
		$invoice->module_source = 'takepos';
		$invoice->pos_source = $_SESSION["takeposterminal"];
		$placeid = $invoice->create($user);
		$sql = "UPDATE ".MAIN_DB_PREFIX."facture set ref='(PROV-POS".$_SESSION["takeposterminal"]."-".$place.")' where rowid = ".((int) $placeid);
		$db->query($sql);
	}

	$sql = "UPDATE ".MAIN_DB_PREFIX."facture set fk_soc=".((int) $idcustomer)." where ref='(PROV-POS".$_SESSION["takeposterminal"]."-".$place.")'";
	$resql = $db->query($sql); ?>
		<script>
		console.log("Reload page invoice.php with place=<?php print $place; ?>");
		parent.$("#poslines").load("invoice.php?place=<?php print $place; ?>", function() {
			//parent.$("#poslines").scrollTop(parent.$("#poslines")[0].scrollHeight);
			<?php if (!$resql) { ?>
				alert('Error failed to update customer on draft invoice.');
			<?php } ?>
			parent.$.colorbox.close(); /* Close the popup */
		});
		</script>
	<?php
	exit;
}

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

$parameters = array('arrayfields' => &$arrayfields);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$search_id = '';
		$search_nom = '';
		$search_alias = '';
		$search_ref_ext = '';
		$search_categ_cus = 0;
		$search_categ_sup = 0;
		$searchCategoryCustomerOperator = 0;
		$searchCategorySupplierOperator = 0;
		$searchCategoryCustomerList = array();
		$searchCategorySupplierList = array();
		$search_sale = array();
		$search_barcode = "";
		$search_customer_code = '';
		$search_supplier_code = '';
		$search_account_customer_code = '';
		$search_account_supplier_code = '';
		$search_address = '';
		$search_zip = "";
		$search_town = "";
		$search_state = "";
		$search_region = "";
		$search_country = '';
		$search_email = '';
		$search_phone = '';
		$search_phone_mobile = '';
		$search_fax = '';
		$search_url = '';
		$search_idprof1 = '';
		$search_idprof2 = '';
		$search_idprof3 = '';
		$search_idprof4 = '';
		$search_idprof5 = '';
		$search_idprof6 = '';
		$search_vat = '';
		$search_type = '';
		$search_price_level = '';
		$search_type_thirdparty = '';
		$search_staff = '';
		$search_legalform = '';
		$search_date_creation_startmonth = "";
		$search_date_creation_startyear = "";
		$search_date_creation_startday = "";
		$search_date_creation_start = "";
		$search_date_creation_endmonth = "";
		$search_date_creation_endyear = "";
		$search_date_creation_endday = "";
		$search_date_creation_end = "";
		$search_date_modif_startmonth = "";
		$search_date_modif_startyear = "";
		$search_date_modif_startday = "";
		$search_date_modif_start = "";
		$search_date_modif_endmonth = "";
		$search_date_modif_endyear = "";
		$search_date_modif_endday = "";
		$search_date_modif_end = "";
		$search_status = -1;
		$search_stcomm = '';
		$search_level = '';
		$search_parent_name = '';
		$search_import_key = '';

		$search_all = '';
		$toselect = array();
		$search_array_options = array();
	}

	// Mass actions
	$objectclass = 'Societe';
	$objectlabel = 'ThirdParty';
	$permissiontoread = $user->hasRight('societe', 'lire');
	$permissiontodelete = $user->hasRight('societe', 'supprimer');
	$permissiontoadd = $user->hasRight("societe", "creer");
	$uploaddir = $conf->societe->dir_output;

	global $error;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

	if (!$error && $action == 'setstcomm' && $permissiontoadd) {
		$object = new Client($db);
		$result = $object->fetch(GETPOST('stcommsocid'));
		$object->stcomm_id = dol_getIdFromCode($db, GETPOST('stcomm', 'alpha'), 'c_stcomm');
		$result = $object->update($object->id, $user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}

		$action = '';
	}
}

if ($search_status == '' && empty($search_all)) {
	$search_status = 1; // display active thirdparty only by default
}



/*
 * View
 */

/*
 REM: Rules on permissions to see thirdparties
 Internal or External user + No permission to see customers => See nothing
 Internal user socid=0 + Permission to see ALL customers    => See all thirdparties
 Internal user socid=0 + No permission to see ALL customers => See only thirdparties linked to user that are sale representative
 External user socid=x + Permission to see ALL customers    => Can see only himself
 External user socid=x + No permission to see ALL customers => Can see only himself
 */

$form = new Form($db);
$formother = new FormOther($db);
$companystatic = new Societe($db);
$companyparent = new Societe($db);
$formcompany = new FormCompany($db);
$prospectstatic = new Client($db);
$prospectstatic->client = 2;
$prospectstatic->loadCacheOfProspStatus();

$now = dol_now();

$title = $langs->trans("ThirdParties");
if ($type == 'c' && (empty($search_type) || ($search_type == '1,3'))) {
	$title = $langs->trans("Customers");
}
if ($type == 'p' && (empty($search_type) || ($search_type == '2,3'))) {
	$title = $langs->trans("Prospects");
}
if ($type == 'f' && (empty($search_type) || ($search_type == '4'))) {
	$title = $langs->trans("Suppliers");
}
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';


// Select every potentials, and note each potentials which fit in search parameters
$tab_level = array();
$sql = "SELECT code, label, sortorder";
$sql .= " FROM ".MAIN_DB_PREFIX."c_prospectlevel";
$sql .= " WHERE active > 0";
$sql .= " ORDER BY sortorder";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		// Compute level text
		$level = $langs->trans($obj->code);
		if ($level == $obj->code) {
			$level = $langs->trans($obj->label);
		}
		$tab_level[$obj->code] = $level;
	}
} else {
	dol_print_error($db);
}

// Build and execute select
// --------------------------------------------------------------------
$sql = "SELECT s.rowid, s.nom as name, s.name_alias, s.ref_ext, s.barcode, s.address, s.town, s.zip, s.datec, s.code_client, s.code_fournisseur, s.logo,";
$sql .= " s.entity,";
$sql .= " st.libelle as stcomm, st.picto as stcomm_picto, s.fk_stcomm as stcomm_id, s.fk_prospectlevel, s.prefix_comm, s.client, s.fournisseur, s.canvas, s.status as status, s.note_private, s.note_public,";
$sql .= " s.email, s.phone, s.phone_mobile, s.fax, s.url, s.siren as idprof1, s.siret as idprof2, s.ape as idprof3, s.idprof4 as idprof4, s.idprof5 as idprof5, s.idprof6 as idprof6, s.tva_intra, s.fk_pays,";
$sql .= " s.ip, s.tms as date_modification, s.datec as date_creation, s.import_key,";
$sql .= " s.code_compta, s.code_compta_fournisseur, s.parent as fk_parent,s.price_level,";
$sql .= " s2.nom as name2,";
$sql .= " typent.code as typent_code,";
$sql .= " staff.code as staff_code,";
$sql .= " s.fk_forme_juridique as legalform_code,";
$sql .= " country.code as country_code, country.label as country_label,";
$sql .= " state.code_departement as state_code, state.nom as state_name,";
$sql .= " region.code_region as region_code, region.nom as region_name";
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
//$sql .= ", COUNT(rc.rowid) as anotherfield";

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s2 ON s.parent = s2.rowid";
if (!empty($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (s.rowid = ef.fk_object)";
}

$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_effectif as staff on (staff.id = s.fk_effectif)";
//$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_forme_juridique as legalform on (legalform.rowid = s.fk_forme_juridique)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_regions as region on (region.code_region = state.fk_region)";
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX."c_stcomm as st ON s.fk_stcomm = st.id";
// Add table from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " WHERE s.entity IN (".getEntity('societe').")";
if (!$user->hasRight('fournisseur', 'lire')) {
	$sql .= " AND (s.fournisseur <> 1 OR s.client <> 0)"; // client=0, fournisseur=0 must be visible
}

// Force the sales representative if they don't have permissions
if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
	$search_sale = array($user->id);
}
// Search on sale representative
if (!empty($search_sale) && $search_sale != '-1') {
	$search_sale_req = array_filter($search_sale, function (string $value): bool {
		$value = intval($value);
		return $value >= 0;
	});

	$search_sale_req = implode(',', $search_sale_req);

	if (count($search_sale) == 1 && in_array('-2', $search_sale)) {
		$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = s.rowid)";
	} elseif (count($search_sale) > 0 && !in_array('-2', $search_sale)) {
		$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = s.rowid AND sc.fk_user IN (".$db->sanitize($search_sale_req)."))";
	} elseif (count($search_sale) > 0 && in_array('-2', $search_sale)) {
		$sql .= " AND (EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = s.rowid AND sc.fk_user IN (".$db->sanitize($search_sale_req)."))";
		$sql .= " OR NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = s.rowid))";
	}
}

// Search for tag/category ($searchCategoryCustomerList is an array of ID)
if (!empty($searchCategoryCustomerList)) {
	$searchCategoryCustomerSqlList = array();
	$listofcategoryid = '';
	foreach ($searchCategoryCustomerList as $searchCategoryCustomer) {
		if (intval($searchCategoryCustomer) == -2) {
			$searchCategoryCustomerSqlList[] = "NOT EXISTS (SELECT ck.fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe as ck WHERE s.rowid = ck.fk_soc)";
		} elseif (intval($searchCategoryCustomer) > 0) {
			if ($searchCategoryCustomerOperator == 0) {
				$searchCategoryCustomerSqlList[] = " EXISTS (SELECT ck.fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe as ck WHERE s.rowid = ck.fk_soc AND ck.fk_categorie = ".((int) $searchCategoryCustomer).")";
			} else {
				$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategoryCustomer);
			}
		}
	}
	if ($listofcategoryid) {
		$searchCategoryCustomerSqlList[] = " EXISTS (SELECT ck.fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe as ck WHERE s.rowid = ck.fk_soc AND ck.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
	}
	if ($searchCategoryCustomerOperator == 1) {
		if (!empty($searchCategoryCustomerSqlList)) {
			$sql .= " AND (".implode(' OR ', $searchCategoryCustomerSqlList).")";
		}
	} else {
		if (!empty($searchCategoryCustomerSqlList)) {
			$sql .= " AND (".implode(' AND ', $searchCategoryCustomerSqlList).")";
		}
	}
}

// Search for tag/category ($searchCategorySupplierList is an array of ID)
if (!empty($searchCategorySupplierList)) {
	$searchCategorySupplierSqlList = array();
	$listofcategoryid = '';
	foreach ($searchCategorySupplierList as $searchCategorySupplier) {
		if (intval($searchCategorySupplier) == -2) {
			$searchCategorySupplierSqlList[] = "NOT EXISTS (SELECT ck.fk_soc FROM ".MAIN_DB_PREFIX."categorie_fournisseur as ck WHERE s.rowid = ck.fk_soc)";
		} elseif (intval($searchCategorySupplier) > 0) {
			if ($searchCategorySupplierOperator == 0) {
				$searchCategorySupplierSqlList[] = " EXISTS (SELECT ck.fk_soc FROM ".MAIN_DB_PREFIX."categorie_fournisseur as ck WHERE s.rowid = ck.fk_soc AND ck.fk_categorie = ".((int) $searchCategorySupplier).")";
			} else {
				$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategorySupplier);
			}
		}
	}
	if ($listofcategoryid) {
		$searchCategorySupplierSqlList[] = " EXISTS (SELECT ck.fk_soc FROM ".MAIN_DB_PREFIX."categorie_fournisseur as ck WHERE s.rowid = ck.fk_soc AND ck.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
	}
	if ($searchCategorySupplierOperator == 1) {
		if (!empty($searchCategorySupplierSqlList)) {
			$sql .= " AND (".implode(' OR ', $searchCategorySupplierSqlList).")";
		}
	} else {
		if (!empty($searchCategorySupplierSqlList)) {
			$sql .= " AND (".implode(' AND ', $searchCategorySupplierSqlList).")";
		}
	}
}
if ($search_all) {
	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}
if (strlen($search_cti)) {
	$sql .= natural_search('s.phone', $search_cti);
}
if ($search_id > 0) {
	$sql .= natural_search("s.rowid", $search_id, 1);
}
if (empty($arrayfields['s.name_alias']['checked']) && $search_nom) {
	$sql .= natural_search(array("s.nom", "s.name_alias"), $search_nom);
} else {
	if ($search_nom) {
		$sql .= natural_search("s.nom", $search_nom);
	}

	if ($search_alias) {
		$sql .= natural_search("s.name_alias", $search_alias);
	}
}
if ($search_nom_only) {
	$sql .= natural_search("s.nom", $search_nom_only);
}
if ($search_ref_ext) {
	$sql .= natural_search("s.ref_ext", $search_ref_ext);
}
if ($search_customer_code) {
	$sql .= natural_search("s.code_client", $search_customer_code);
}
if ($search_supplier_code) {
	$sql .= natural_search("s.code_fournisseur", $search_supplier_code);
}
if ($search_account_customer_code) {
	$sql .= natural_search("s.code_compta", $search_account_customer_code);
}
if ($search_account_supplier_code) {
	$sql .= natural_search("s.code_compta_fournisseur", $search_account_supplier_code);
}
if ($search_address) {
	$sql .= natural_search('s.address', $search_address);
}
if (strlen($search_zip)) {
	$sql .= natural_search("s.zip", $search_zip);
}
if ($search_town) {
	$sql .= natural_search("s.town", $search_town);
}
if ($search_state) {
	$sql .= natural_search("state.nom", $search_state);
}
if ($search_region) {
	$sql .= natural_search("region.nom", $search_region);
}
if ($search_country && $search_country != '-1') {
	$sql .= " AND s.fk_pays IN (".$db->sanitize($search_country).')';
}
if ($search_email) {
	$sql .= natural_search("s.email", $search_email);
}
if (strlen($search_phone)) {
	$sql .= natural_search("s.phone", $search_phone);
}
if (strlen($search_phone_mobile)) {
	$sql .= natural_search("s.phone_mobile", $search_phone_mobile);
}
if (strlen($search_fax)) {
	$sql .= natural_search("s.fax", $search_fax);
}
if ($search_url) {
	$sql .= natural_search("s.url", $search_url);
}
if (strlen($search_idprof1)) {
	$sql .= natural_search("s.siren", $search_idprof1);
}
if (strlen($search_idprof2)) {
	$sql .= natural_search("s.siret", $search_idprof2);
}
if (strlen($search_idprof3)) {
	$sql .= natural_search("s.ape", $search_idprof3);
}
if (strlen($search_idprof4)) {
	$sql .= natural_search("s.idprof4", $search_idprof4);
}
if (strlen($search_idprof5)) {
	$sql .= natural_search("s.idprof5", $search_idprof5);
}
if (strlen($search_idprof6)) {
	$sql .= natural_search("s.idprof6", $search_idprof6);
}
if (strlen($search_vat)) {
	$sql .= natural_search("s.tva_intra", $search_vat);
}
// Filter on type of thirdparty
if ($search_type > 0 && in_array($search_type, array('1,3', '1,2,3', '2,3'))) {
	$sql .= " AND s.client IN (".$db->sanitize($search_type).")";
}
if ($search_type > 0 && in_array($search_type, array('4'))) {
	$sql .= " AND s.fournisseur = 1";
}
if ($search_type == '0') {
	$sql .= " AND s.client = 0 AND s.fournisseur = 0";
}
if ($search_status != '' && $search_status >= 0) {
	$sql .= natural_search("s.status", $search_status, 2);
}
if (isModEnabled('barcode') && $search_barcode) {
	$sql .= natural_search("s.barcode", $search_barcode);
}
if ($search_price_level && $search_price_level != '-1') {
	$sql .= natural_search("s.price_level", $search_price_level, 2);
}
if ($search_type_thirdparty && $search_type_thirdparty > 0) {
	$sql .= natural_search("s.fk_typent", $search_type_thirdparty, 2);
}
if (!empty($search_staff) && $search_staff != '-1') {
	$sql .= natural_search("s.fk_effectif", $search_staff, 2);
}
if (!empty($search_legalform) && $search_legalform != '-1') {
	$sql .= natural_search("s.fk_forme_juridique", $search_legalform, 2);
}
if ($search_parent_name) {
	$sql .= natural_search("s2.nom", $search_parent_name);
}
if ($search_level) {
	$sql .= natural_search("s.fk_prospectlevel", implode(',', $search_level), 3);
}
if ($search_stcomm) {
	$sql .= natural_search("s.fk_stcomm", implode(',', $search_stcomm), 2);
}
if ($search_import_key) {
	$sql .= natural_search("s.import_key", $search_import_key);
}
if ($search_date_creation_start) {
	$sql .= " AND s.datec >= '".$db->idate($search_date_creation_start)."'";
}
if ($search_date_creation_end) {
	$sql .= " AND s.datec <= '".$db->idate($search_date_creation_end)."'";
}

if ($search_date_modif_start) {
	$sql .= " AND s.tms >= '".$db->idate($search_date_modif_start)."'";
}
if ($search_date_modif_end) {
	$sql .= " AND s.tms <= '".$db->idate($search_date_modif_end)."'";
}

// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
	if ($socid) {
		$sql .= " AND s.rowid = ".((int) $socid);
	}
}
$sql .= $hookmanager->resPrint;

// Add GroupBy from hooks
$parameters = array('fieldstosearchall' => $fieldstosearchall);
$reshook = $hookmanager->executeHooks('printFieldListGroupBy', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
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


// Direct jump if only one record found
if ($num == 1 && getDolGlobalString('MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE') && ($search_all != '' || $search_cti != '') && $action != 'list') {
	$obj = $db->fetch_object($resql);
	$id = $obj->rowid;
	if (getDolGlobalString('SOCIETE_ON_SEARCH_AND_LIST_GO_ON_CUSTOMER_OR_SUPPLIER_CARD')) {
		if ($obj->client > 0) {
			header("Location: ".DOL_URL_ROOT.'/comm/card.php?socid='.$id);
			exit;
		}
		if ($obj->fournisseur > 0) {
			header("Location: ".DOL_URL_ROOT.'/fourn/card.php?socid='.$id);
			exit;
		}
	}

	header("Location: ".DOL_URL_ROOT.'/societe/card.php?socid='.$id);
	exit;
}

// Output page
// --------------------------------------------------------------------

$paramsCat = '';
foreach ($searchCategoryCustomerList as $searchCategoryCustomer) {
	$paramsCat .= "&search_category_customer_list[]=".urlencode($searchCategoryCustomer);
}
foreach ($searchCategorySupplierList as $searchCategorySupplier) {
	$paramsCat .= "&search_category_supplier_list[]=".urlencode($searchCategorySupplier);
}

llxHeader('', $title, $help_url, '', 0, 0, array(), array(), $paramsCat, 'bodyforlist');


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
if ($search_all != '') {
	$param = "&search_all=".urlencode($search_all);
}
if ($search_categ_cus > 0) {
	$param .= '&search_categ_cus='.urlencode((string) ($search_categ_cus));
}
if ($search_categ_sup > 0) {
	$param .= '&search_categ_sup='.urlencode((string) ($search_categ_sup));
}
if ($searchCategoryCustomerOperator == 1) {
	$param .= "&search_category_customer_operator=".urlencode((string) ($searchCategoryCustomerOperator));
}
if ($searchCategorySupplierOperator == 1) {
	$param .= "&search_category_supplier_operator=".urlencode((string) ($searchCategorySupplierOperator));
}
foreach ($searchCategoryCustomerList as $searchCategoryCustomer) {
	$param .= "&search_category_customer_list[]=".urlencode($searchCategoryCustomer);
}
foreach ($searchCategorySupplierList as $searchCategorySupplier) {
	$param .= "&search_category_supplier_list[]=".urlencode($searchCategorySupplier);
}
if (is_array($search_sale)) {
	foreach ($search_sale as $sale_id) {
		$param .= '&search_sale[]=' . urlencode($sale_id);
	}
}
if ($search_id > 0) {
	$param .= "&search_id=".((int) $search_id);
}
if ($search_nom != '') {
	$param .= "&search_nom=".urlencode($search_nom);
}
if ($search_alias != '') {
	$param .= "&search_alias=".urlencode($search_alias);
}
if ($search_ref_ext != '') {
	$param .= "&search_ref_ext=".urlencode($search_ref_ext);
}
if ($search_address != '') {
	$param .= '&search_address='.urlencode($search_address);
}
if ($search_zip != '') {
	$param .= "&search_zip=".urlencode($search_zip);
}
if ($search_town != '') {
	$param .= "&search_town=".urlencode($search_town);
}
if ($search_phone != '') {
	$param .= "&search_phone=".urlencode($search_phone);
}
if ($search_phone_mobile != '') {
	$param .= "&search_phone_mobile=".urlencode($search_phone_mobile);
}
if ($search_fax != '') {
	$param .= "&search_fax=".urlencode($search_fax);
}
if ($search_email != '') {
	$param .= "&search_email=".urlencode($search_email);
}
if ($search_url != '') {
	$param .= "&search_url=".urlencode($search_url);
}
if ($search_state != '') {
	$param .= "&search_state=".urlencode($search_state);
}
if ($search_region != '') {
	$param .= "&search_region=".urlencode($search_region);
}
if ($search_country != '') {
	$param .= "&search_country=".urlencode($search_country);
}
if ($search_customer_code != '') {
	$param .= "&search_customer_code=".urlencode($search_customer_code);
}
if ($search_supplier_code != '') {
	$param .= "&search_supplier_code=".urlencode($search_supplier_code);
}
if ($search_account_customer_code != '') {
	$param .= "&search_account_customer_code=".urlencode($search_account_customer_code);
}
if ($search_account_supplier_code != '') {
	$param .= "&search_account_supplier_code=".urlencode($search_account_supplier_code);
}
if ($search_barcode != '') {
	$param .= "&search_barcode=".urlencode($search_barcode);
}
if ($search_idprof1 != '') {
	$param .= '&search_idprof1='.urlencode($search_idprof1);
}
if ($search_idprof2 != '') {
	$param .= '&search_idprof2='.urlencode($search_idprof2);
}
if ($search_idprof3 != '') {
	$param .= '&search_idprof3='.urlencode($search_idprof3);
}
if ($search_idprof4 != '') {
	$param .= '&search_idprof4='.urlencode($search_idprof4);
}
if ($search_idprof5 != '') {
	$param .= '&search_idprof5='.urlencode($search_idprof5);
}
if ($search_idprof6 != '') {
	$param .= '&search_idprof6='.urlencode($search_idprof6);
}
if ($search_vat != '') {
	$param .= '&search_vat='.urlencode($search_vat);
}
if ($search_price_level != '') {
	$param .= '&search_price_level='.urlencode($search_price_level);
}
if ($search_type_thirdparty != '' && $search_type_thirdparty > 0) {
	$param .= '&search_type_thirdparty='.urlencode((string) ($search_type_thirdparty));
}
if ($search_type != '') {
	$param .= '&search_type='.urlencode($search_type);
}
if ($search_status != '') {
	$param .= '&search_status='.urlencode((string) ($search_status));
}
if (is_array($search_level) && count($search_level)) {
	foreach ($search_level as $slevel) {
		$param .= '&search_level[]='.urlencode($slevel);
	}
}
if (is_array($search_stcomm) && count($search_stcomm)) {
	foreach ($search_stcomm as $slevel) {
		$param .= '&search_stcomm[]='.urlencode($slevel);
	}
}
if ($search_parent_name != '') {
	$param .= '&search_parent_name='.urlencode($search_parent_name);
}
if ($search_import_key != '') {
	$param .= '&search_import_key='.urlencode($search_import_key);
}
if ($type != '') {
	$param .= '&type='.urlencode($type);
}
if ($search_date_creation_startmonth) {
	$param .= '&search_date_creation_startmonth='.urlencode((string) ($search_date_creation_startmonth));
}
if ($search_date_creation_startyear) {
	$param .= '&search_date_creation_startyear='.urlencode((string) ($search_date_creation_startyear));
}
if ($search_date_creation_startday) {
	$param .= '&search_date_creation_startday='.urlencode((string) ($search_date_creation_startday));
}
if ($search_date_creation_start) {
	$param .= '&search_date_creation_start='.urlencode($search_date_creation_start);
}
if ($search_date_creation_endmonth) {
	$param .= '&search_date_creation_endmonth='.urlencode((string) ($search_date_creation_endmonth));
}
if ($search_date_creation_endyear) {
	$param .= '&search_date_creation_endyear='.urlencode((string) ($search_date_creation_endyear));
}
if ($search_date_creation_endday) {
	$param .= '&search_date_creation_endday='.urlencode((string) ($search_date_creation_endday));
}
if ($search_date_creation_end) {
	$param .= '&search_date_creation_end='.urlencode($search_date_creation_end);
}
if ($search_date_modif_startmonth) {
	$param .= '&search_date_modif_startmonth='.urlencode((string) ($search_date_modif_startmonth));
}
if ($search_date_modif_startyear) {
	$param .= '&search_date_modif_startyear='.urlencode((string) ($search_date_modif_startyear));
}
if ($search_date_modif_startday) {
	$param .= '&search_date_modif_startday='.urlencode((string) ($search_date_modif_startday));
}
if ($search_date_modif_start) {
	$param .= '&search_date_modif_start='.urlencode($search_date_modif_start);
}
if ($search_date_modif_endmonth) {
	$param .= '&search_date_modif_endmonth='.urlencode((string) ($search_date_modif_endmonth));
}
if ($search_date_modif_endyear) {
	$param .= '&search_date_modif_endyear='.urlencode((string) ($search_date_modif_endyear));
}
if ($search_date_modif_endday) {
	$param .= '&search_date_modif_endday='.urlencode((string) ($search_date_modif_endday));
}
if ($search_date_modif_end) {
	$param .= '&search_date_modif_end=' . urlencode($search_date_modif_end);
}

// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';
// Add $param from hooks
$parameters = array('param' => &$param);
$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;

// Show delete result message
if (GETPOST('delsoc')) {
	setEventMessages($langs->trans("CompanyDeleted", GETPOST('delsoc')), null, 'mesgs');
}

// List of mass actions available
$arrayofmassactions = array(
	'presend' => img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
	//'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
);
//if($user->rights->societe->creer) $arrayofmassactions['createbills']=$langs->trans("CreateInvoiceForThisCustomer");
if (isModEnabled('category') && $user->hasRight("societe", "creer")) {
	$arrayofmassactions['preaffecttag'] = img_picto('', 'category', 'class="pictofixedwidth"').$langs->trans("AffectTag");
}
if ($user->hasRight("societe", "creer")) {
	$arrayofmassactions['preenable'] = img_picto('', 'stop-circle', 'class="pictofixedwidth"').$langs->trans("SetToStatus", $object->LibStatut($object::STATUS_INACTIVITY));
}
if ($user->hasRight("societe", "creer")) {
	$arrayofmassactions['predisable'] = img_picto('', 'stop-circle', 'class="pictofixedwidth"').$langs->trans("SetToStatus", $object->LibStatut($object::STATUS_CEASED));
}
if ($user->hasRight("societe", "creer")) {
	$arrayofmassactions['presetcommercial'] = img_picto('', 'user', 'class="pictofixedwidth"').$langs->trans("AllocateCommercial");
	$arrayofmassactions['unsetcommercial'] = img_picto('', 'user', 'class="pictofixedwidth"').$langs->trans("UnallocateCommercial");
}

if ($user->hasRight('societe', 'supprimer')) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if (GETPOSTINT('nomassaction') || in_array($massaction, array('presend', 'predelete', 'preaffecttag', 'preenable', 'preclose'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

$typefilter = '';
$label = 'MenuNewThirdParty';

if (!empty($type)) {
	$typefilter = '&amp;type='.$type;
	if ($type == 'p') {
		$label = 'MenuNewProspect';
	}
	if ($type == 'c') {
		$label = 'MenuNewCustomer';
	}
	if ($type == 'f') {
		$label = 'NewSupplier';
	}
}

if ($contextpage == 'poslist' && $type == 't' && (getDolGlobalString('PRODUIT_MULTIPRICES') || getDolGlobalString('PRODUIT_CUSTOMER_PRICES') || getDolGlobalString('PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES') || getDolGlobalString('PRODUIT_CUSTOMER_PRICES_AND_MULTIPRICES'))) {
	print get_htmloutput_mesg(img_warning('default').' '.$langs->trans("BecarefullChangeThirdpartyBeforeAddProductToInvoice"), [], 'warning', 1);
}

// Show the new button only when this page is not opend from the Extended POS (pop-up window)
// but allow it too, when a user has the rights to create a new customer
if ($contextpage != 'poslist') {
	$url = DOL_URL_ROOT.'/societe/card.php?action=create'.$typefilter;
	if (!empty($socid)) {
		$url .= '&socid='.$socid;
	}
	$newcardbutton   = '';
	$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss' => 'reposition'));
	$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss' => 'reposition'));
	$newcardbutton .= dolGetButtonTitle($langs->trans($label), '', 'fa fa-plus-circle', $url, '', $user->hasRight('societe', 'creer'));
} elseif ($user->hasRight('societe', 'creer')) {
	$url = DOL_URL_ROOT.'/societe/card.php?action=create&type=t&contextpage=poslist&optioncss=print&backtopage='.urlencode($_SERVER["PHP_SELF"].'?type=t&contextpage=poslist&nomassaction=1&optioncss=print&place='.$place);
	$label = 'MenuNewCustomer';
	$newcardbutton = dolGetButtonTitle($langs->trans($label), '', 'fa fa-plus-circle', $url);
}

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'" name="formfilter" autocomplete="off">'."\n";
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
//print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
if (!empty($place)) {
	print '<input type="hidden" name="place" value="'.$place.'">';
}
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="mode" value="'.$mode.'">';
if (empty($arrayfields['customerorsupplier']['checked'])) {
	print '<input type="hidden" name="type" value="'.$type.'">';
}
if (!empty($place)) {
	print '<input type="hidden" name="place" value="'.$place.'">';
}

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'building', 0, $newcardbutton, '', $limit, 0, 0, 1);

$langs->load("other");
$textprofid = array();
foreach (array(1, 2, 3, 4, 5, 6) as $key) {
	$label = $langs->transnoentities("ProfId".$key.$mysoc->country_code);
	$textprofid[$key] = '';
	if ($label != "ProfId".$key.$mysoc->country_code) {	// Get only text between ()
		if (preg_match('/\((.*)\)/i', $label, $reg)) {
			$label = $reg[1];
		}
		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		$textprofid[$key] = $langs->trans("ProfIdShortDesc", $key, $mysoc->country_code, $label);
	}
}

// Add code for pre mass action (confirmation or email presend form)
$topicmail = "Information";
$modelmail = "thirdparty";
$objecttmp = new Societe($db);
$trackid = 'thi'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

/*
if (!empty($search_categ_cus) || !empty($search_categ_sup)) {
	print "<div id='ways'>";
	$c = new Categorie($db);
	$ways = $c->print_all_ways(' &gt; ', 'societe/list.php');
	print " &gt; ".$ways[0]."<br>\n";
	print "</div><br>";
}
*/

if ($search_all) {
	$setupstring = '';
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
		$setupstring .= $key."=".$val.";";
	}
	print '<!-- Search done like if SOCIETE_QUICKSEARCH_ON_FIELDS = '.$setupstring.' -->'."\n";
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).implode(', ', $fieldstosearchall).'</div>';
}

$moreforfilter = '';

// Filter for customer categories
if (empty($type) || $type == 'c' || $type == 'p') {
	if (isModEnabled('category') && $user->hasRight('categorie', 'read')) {
		$formcategory = new FormCategory($db);
		$moreforfilter .= $formcategory->getFilterBox(Categorie::TYPE_CUSTOMER, $searchCategoryCustomerList, 'minwidth300', $searchCategoryCustomerOperator ? $searchCategoryCustomerOperator : 0, 1, 1, $langs->transnoentities("CustomersProspectsCategoriesShort"));
	}
}

// Filter for supplier categories
if (empty($type) || $type == 'f') {
	if (isModEnabled("fournisseur") && isModEnabled('category') && $user->hasRight('categorie', 'read')) {
		$formcategory = new FormCategory($db);
		$moreforfilter .= $formcategory->getFilterBox(Categorie::TYPE_SUPPLIER, $searchCategorySupplierList, 'minwidth300', $searchCategorySupplierOperator ? $searchCategorySupplierOperator : 0, 1, 1, $langs->transnoentities("SuppliersCategoriesShort"));
	}
}

// If the user can view prospects other than his'
$userlist = $form->select_dolusers('', '', 0, null, 0, '', '', 0, 0, 0, 'u.statut:=:1', 0, '', '', 0, 1);
$userlist[-2] = $langs->trans("NoSalesRepresentativeAffected");
if ($user->hasRight("societe", "client", "voir") || $socid) {
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('SalesRepresentatives');
	$moreforfilter .= img_picto($tmptitle, 'user', 'class="pictofixedwidth"');
	$moreforfilter .=  $form->multiselectarray('search_sale', $userlist, $search_sale, 0, 0, '', 0, 300, '', '', $langs->trans('SalesRepresentatives'), 1);
	$moreforfilter .= '</div>';
}
if (!empty($moreforfilter)) {
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	$parameters = array('type' => $type);
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	print '</div>';
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$htmlofselectarray = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN'));  // This also change content of $arrayfields with user setup
$selectedfields = ($mode != 'kanban' ? $htmlofselectarray : '');
$selectedfields .= ((count($arrayofmassactions) && $contextpage != 'poslist') ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre_filter">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre maxwidthsearch center actioncolumn">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}
if (!empty($arrayfields['s.rowid']['checked'])) {
	print '<td class="liste_titre" data-key="id">';
	print '<input class="flat searchstring" type="text" name="search_id" size="1" value="'.dol_escape_htmltag($search_id).'">';
	print '</td>';
}
if (!empty($arrayfields['s.nom']['checked'])) {
	print '<td class="liste_titre" data-key="ref">';
	if (!empty($search_nom_only) && empty($search_nom)) {
		$search_nom = $search_nom_only;
	}
	print '<input class="flat searchstring maxwidth75imp" type="text" name="search_nom" value="'.dol_escape_htmltag($search_nom).'">';
	print '</td>';
}
if (!empty($arrayfields['s.name_alias']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth75imp" type="text" name="search_alias" value="'.dol_escape_htmltag($search_alias).'">';
	print '</td>';
}
if (!empty($arrayfields['s.ref_ext']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth75imp" type="text" name="search_ref_ext" value="'.dol_escape_htmltag($search_ref_ext).'">';
	print '</td>';
}
// Barcode
if (!empty($arrayfields['s.barcode']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth75imp" type="text" name="search_barcode" value="'.dol_escape_htmltag($search_barcode).'">';
	print '</td>';
}
// Customer code
if (!empty($arrayfields['s.code_client']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth75imp" type="text" name="search_customer_code" value="'.dol_escape_htmltag($search_customer_code).'">';
	print '</td>';
}
// Supplier code
if (!empty($arrayfields['s.code_fournisseur']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth75imp" type="text" name="search_supplier_code" value="'.dol_escape_htmltag($search_supplier_code).'">';
	print '</td>';
}
// Account Customer code
if (!empty($arrayfields['s.code_compta']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth75imp" type="text" name="search_account_customer_code" value="'.dol_escape_htmltag($search_account_customer_code).'">';
	print '</td>';
}
// Account Supplier code
if (!empty($arrayfields['s.code_compta_fournisseur']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth75imp" type="text" name="search_account_supplier_code" value="'.dol_escape_htmltag($search_account_supplier_code).'">';
	print '</td>';
}
// Address
if (!empty($arrayfields['s.address']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_address" value="'.dol_escape_htmltag($search_address).'">';
	print '</td>';
}
// Sales representatives
if (!empty($arrayfields['sales.representative']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
// Zip
if (!empty($arrayfields['s.zip']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_zip" value="'.dol_escape_htmltag($search_zip).'">';
	print '</td>';
}
// Town
if (!empty($arrayfields['s.town']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_town" value="'.dol_escape_htmltag($search_town).'">';
	print '</td>';
}
// State
if (!empty($arrayfields['state.nom']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_state" value="'.dol_escape_htmltag($search_state).'">';
	print '</td>';
}
// Region
if (!empty($arrayfields['region.nom']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_region" value="'.dol_escape_htmltag($search_region).'">';
	print '</td>';
}
// Country
if (!empty($arrayfields['country.code_iso']['checked'])) {
	print '<td class="liste_titre center">';
	print $form->select_country($search_country, 'search_country', '', 0, 'minwidth100imp maxwidth100');
	print '</td>';
}
// Company type
if (!empty($arrayfields['typent.code']['checked'])) {
	print '<td class="liste_titre maxwidthonsmartphone center">';
	// We use showempty=0 here because there is already an unknown value into dictionary.
	print $form->selectarray("search_type_thirdparty", $formcompany->typent_array(0), $search_type_thirdparty, 1, 0, 0, '', 0, 0, 0, (!getDolGlobalString('SOCIETE_SORT_ON_TYPEENT') ? 'ASC' : $conf->global->SOCIETE_SORT_ON_TYPEENT), 'minwidth50 maxwidth125', 1);
	print '</td>';
}
// Multiprice level
if (!empty($arrayfields['s.price_level']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_price_level" value="'.dol_escape_htmltag($search_price_level).'">';
	print '</td>';
}
// Staff
if (!empty($arrayfields['staff.code']['checked'])) {
	print '<td class="liste_titre maxwidthonsmartphone center">';
	print $form->selectarray("search_staff", $formcompany->effectif_array(0), $search_staff, 0, 0, 0, '', 0, 0, 0, 'ASC', 'maxwidth100', 1);
	print '</td>';
}
// Legal form
if (!empty($arrayfields['legalform.code']['checked'])) {
	print '<td class="liste_titre maxwidthonsmartphone center">';
	//print $form->selectarray("search_legalform", $formcompany->effectif_array(0), $search_legalform, 0, 0, 0, '', 0, 0, 0, 'ASC', 'maxwidth100', 1);
	print '</td>';
}
if (!empty($arrayfields['s.email']['checked'])) {
	// Email
	print '<td class="liste_titre">';
	print '<input class="flat searchemail maxwidth50imp" type="text" name="search_email" value="'.dol_escape_htmltag($search_email).'">';
	print '</td>';
}
if (!empty($arrayfields['s.phone']['checked'])) {
	// Phone
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_phone" value="'.dol_escape_htmltag($search_phone).'">';
	print '</td>';
}
if (!empty($arrayfields['s.phone_mobile']['checked'])) {
	// PhoneMobile
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_phone_mobile" value="'.dol_escape_htmltag($search_phone_mobile).'">';
	print '</td>';
}
if (!empty($arrayfields['s.fax']['checked'])) {
	// Fax
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_fax" value="'.dol_escape_htmltag($search_fax).'">';
	print '</td>';
}
if (!empty($arrayfields['s.url']['checked'])) {
	// Url
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_url" value="'.dol_escape_htmltag($search_url).'">';
	print '</td>';
}
if (!empty($arrayfields['s.siren']['checked'])) {
	// IdProf1
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_idprof1" value="'.dol_escape_htmltag($search_idprof1).'">';
	print '</td>';
}
if (!empty($arrayfields['s.siret']['checked'])) {
	// IdProf2
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_idprof2" value="'.dol_escape_htmltag($search_idprof2).'">';
	print '</td>';
}
if (!empty($arrayfields['s.ape']['checked'])) {
	// IdProf3
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_idprof3" value="'.dol_escape_htmltag($search_idprof3).'">';
	print '</td>';
}
if (!empty($arrayfields['s.idprof4']['checked'])) {
	// IdProf4
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_idprof4" value="'.dol_escape_htmltag($search_idprof4).'">';
	print '</td>';
}
if (!empty($arrayfields['s.idprof5']['checked'])) {
	// IdProf5
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_idprof5" value="'.dol_escape_htmltag($search_idprof5).'">';
	print '</td>';
}
if (!empty($arrayfields['s.idprof6']['checked'])) {
	// IdProf6
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_idprof6" value="'.dol_escape_htmltag($search_idprof6).'">';
	print '</td>';
}
if (!empty($arrayfields['s.tva_intra']['checked'])) {
	// Vat number
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_vat" value="'.dol_escape_htmltag($search_vat).'">';
	print '</td>';
}

// Nature (customer/prospect/supplier)
if (!empty($arrayfields['customerorsupplier']['checked'])) {
	print '<td class="liste_titre maxwidthonsmartphone center">';
	if ($type != '') {
		print '<input type="hidden" name="type" value="'.$type.'">';
	}
	print $formcompany->selectProspectCustomerType($search_type, 'search_type', 'search_type', 'list');
	print '</td>';
}
// Prospect level
if (!empty($arrayfields['s.fk_prospectlevel']['checked'])) {
	print '<td class="liste_titre center">';
	print $form->multiselectarray('search_level', $tab_level, $search_level, 0, 0, 'width75', 0, 0, '', '', '', 2);
	print '</td>';
}
// Prospect status
if (!empty($arrayfields['s.fk_stcomm']['checked'])) {
	print '<td class="liste_titre maxwidthonsmartphone center">';
	$arraystcomm = array();
	foreach ($prospectstatic->cacheprospectstatus as $key => $val) {
		$arraystcomm[$val['id']] = ($langs->trans("StatusProspect".$val['id']) != "StatusProspect".$val['id'] ? $langs->trans("StatusProspect".$val['id']) : $val['label']);
	}
	//print $form->selectarray('search_stcomm', $arraystcomm, $search_stcomm, -2, 0, 0, '', 0, 0, 0, '', '', 1);
	print $form->multiselectarray('search_stcomm', $arraystcomm, $search_stcomm, 0, 0, 'width100', 0, 0, '', '', '', 2);
	print '</td>';
}
if (!empty($arrayfields['s2.nom']['checked'])) {
	print '<td class="liste_titre center">';
	print '<input class="flat searchstring maxwidth75imp" type="text" name="search_parent_name" value="'.dol_escape_htmltag($search_parent_name).'">';
	print '</td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields' => $arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// IP address
if (!empty($arrayfields['s.ip']['checked'])) {
	print '<td class="liste_titre center nowraponall">';
	print '</td>';
}
// Creation date
if (!empty($arrayfields['s.datec']['checked'])) {
	print '<td class="liste_titre center nowraponall">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_creation_start ? $search_date_creation_start : -1, 'search_date_creation_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_creation_end ? $search_date_creation_end : -1, 'search_date_creation_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
// Modification date
if (!empty($arrayfields['s.tms']['checked'])) {
	print '<td class="liste_titre center nowraponall">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_modif_start ? $search_date_modif_start : -1, 'search_date_modif_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_modif_end ? $search_date_modif_end : -1, 'search_date_modif_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
if (!empty($arrayfields['s.note_public']['checked'])) {
	// Note public
	print '<td class="liste_titre">';
	print '</td>';
}
if (!empty($arrayfields['s.note_private']['checked'])) {
	// Note private
	print '<td class="liste_titre">';
	print '</td>';
}
// Status
if (!empty($arrayfields['s.status']['checked'])) {
	print '<td class="liste_titre center minwidth75imp parentonrightofpage">';
	print $form->selectarray('search_status', array('0' => $langs->trans('ActivityCeased'), '1' => $langs->trans('InActivity')), $search_status, 1, 0, 0, '', 0, 0, 0, '', 'search_status width100 onrightofpage', 1);
	print '</td>';
}
if (!empty($arrayfields['s.import_key']['checked'])) {
	print '<td class="liste_titre center">';
	print '<input class="flat searchstring maxwidth50" type="text" name="search_import_key" value="'.dol_escape_htmltag($search_import_key).'">';
	print '</td>';
}
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch actioncolumn">';
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
if (!empty($arrayfields['s.rowid']['checked'])) {
	print_liste_field_titre($arrayfields['s.rowid']['label'], $_SERVER["PHP_SELF"], "s.rowid", "", $param, ' data-key="id"', $sortfield, $sortorder, '');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.nom']['checked'])) {
	print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], "s.nom", "", $param, ' data-key="ref"', $sortfield, $sortorder, ' ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.name_alias']['checked'])) {
	// @phan-suppress-next-line PhanTypeInvalidDimOffset
	print_liste_field_titre($arrayfields['s.name_alias']['label'], $_SERVER["PHP_SELF"], "s.name_alias", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.ref_ext']['checked'])) {
	// @phan-suppress-next-line PhanTypeInvalidDimOffset
	print_liste_field_titre($arrayfields['s.ref_ext']['label'], $_SERVER["PHP_SELF"], "s.ref_ext", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.barcode']['checked'])) {
	print_liste_field_titre($arrayfields['s.barcode']['label'], $_SERVER["PHP_SELF"], "s.barcode", $param, '', '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.code_client']['checked'])) {
	print_liste_field_titre($arrayfields['s.code_client']['label'], $_SERVER["PHP_SELF"], "s.code_client", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.code_fournisseur']['checked'])) {
	print_liste_field_titre($arrayfields['s.code_fournisseur']['label'], $_SERVER["PHP_SELF"], "s.code_fournisseur", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.code_compta']['checked'])) {
	print_liste_field_titre($arrayfields['s.code_compta']['label'], $_SERVER["PHP_SELF"], "s.code_compta", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.code_compta_fournisseur']['checked'])) {
	print_liste_field_titre($arrayfields['s.code_compta_fournisseur']['label'], $_SERVER["PHP_SELF"], "s.code_compta_fournisseur", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.address']['checked'])) {
	print_liste_field_titre($arrayfields['s.address']['label'], $_SERVER['PHP_SELF'], 's.address', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['sales.representative']['checked'])) {
	print_liste_field_titre($arrayfields['sales.representative']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.zip']['checked'])) {
	print_liste_field_titre($arrayfields['s.zip']['label'], $_SERVER["PHP_SELF"], "s.zip", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.town']['checked'])) {
	print_liste_field_titre($arrayfields['s.town']['label'], $_SERVER["PHP_SELF"], "s.town", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['state.nom']['checked'])) {
	print_liste_field_titre($arrayfields['state.nom']['label'], $_SERVER["PHP_SELF"], "state.nom", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['region.nom']['checked'])) {
	print_liste_field_titre($arrayfields['region.nom']['label'], $_SERVER["PHP_SELF"], "region.nom", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['country.code_iso']['checked'])) {
	print_liste_field_titre($arrayfields['country.code_iso']['label'], $_SERVER["PHP_SELF"], "country.code_iso", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['typent.code']['checked'])) {
	print_liste_field_titre($arrayfields['typent.code']['label'], $_SERVER["PHP_SELF"], "typent.code", "", $param, "", $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['staff.code']['checked'])) {
	print_liste_field_titre($arrayfields['staff.code']['label'], $_SERVER["PHP_SELF"], "staff.code", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['legalform.code']['checked'])) {
	// s.fk_forme_juridique as legalform_code
	print_liste_field_titre($arrayfields['legalform.code']['label'], $_SERVER["PHP_SELF"], "legalform_code", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.price_level']['checked'])) {
	print_liste_field_titre($arrayfields['s.price_level']['label'], $_SERVER["PHP_SELF"], "s.price_level", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.email']['checked'])) {
	print_liste_field_titre($arrayfields['s.email']['label'], $_SERVER["PHP_SELF"], "s.email", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.phone']['checked'])) {
	print_liste_field_titre($arrayfields['s.phone']['label'], $_SERVER["PHP_SELF"], "s.phone", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.phone_mobile']['checked'])) {
	print_liste_field_titre($arrayfields['s.phone_mobile']['label'], $_SERVER["PHP_SELF"], "s.phone_mobile", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.fax']['checked'])) {
	print_liste_field_titre($arrayfields['s.fax']['label'], $_SERVER["PHP_SELF"], "s.fax", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.url']['checked'])) {
	print_liste_field_titre($arrayfields['s.url']['label'], $_SERVER["PHP_SELF"], "s.url", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.siren']['checked'])) {
	print_liste_field_titre($form->textwithpicto($langs->trans("ProfId1Short"), $textprofid[1], 1, 0), $_SERVER["PHP_SELF"], "s.siren", "", $param, '', $sortfield, $sortorder, 'nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.siret']['checked'])) {
	print_liste_field_titre($form->textwithpicto($langs->trans("ProfId2Short"), $textprofid[2], 1, 0), $_SERVER["PHP_SELF"], "s.siret", "", $param, '', $sortfield, $sortorder, 'nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.ape']['checked'])) {
	print_liste_field_titre($form->textwithpicto($langs->trans("ProfId3Short"), $textprofid[3], 1, 0), $_SERVER["PHP_SELF"], "s.ape", "", $param, '', $sortfield, $sortorder, 'nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.idprof4']['checked'])) {
	print_liste_field_titre($form->textwithpicto($langs->trans("ProfId4Short"), $textprofid[4], 1, 0), $_SERVER["PHP_SELF"], "s.idprof4", "", $param, '', $sortfield, $sortorder, 'nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.idprof5']['checked'])) {
	print_liste_field_titre($form->textwithpicto($langs->trans("ProfId5Short"), $textprofid[5], 1, 0), $_SERVER["PHP_SELF"], "s.idprof5", "", $param, '', $sortfield, $sortorder, 'nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.idprof6']['checked'])) {
	print_liste_field_titre($form->textwithpicto($langs->trans("ProfId6Short"), $textprofid[6], 1, 0), $_SERVER["PHP_SELF"], "s.idprof6", "", $param, '', $sortfield, $sortorder, 'nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.tva_intra']['checked'])) {
	print_liste_field_titre($arrayfields['s.tva_intra']['label'], $_SERVER["PHP_SELF"], "s.tva_intra", "", $param, '', $sortfield, $sortorder, 'nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['customerorsupplier']['checked'])) {
	print_liste_field_titre($arrayfields['customerorsupplier']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'center '); // type of customer
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.fk_prospectlevel']['checked'])) {
	print_liste_field_titre($arrayfields['s.fk_prospectlevel']['label'], $_SERVER["PHP_SELF"], "s.fk_prospectlevel", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.fk_stcomm']['checked'])) {
	print_liste_field_titre($arrayfields['s.fk_stcomm']['label'], $_SERVER["PHP_SELF"], "s.fk_stcomm", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s2.nom']['checked'])) {
	print_liste_field_titre($arrayfields['s2.nom']['label'], $_SERVER["PHP_SELF"], "s2.nom", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// IP Address
if (!empty($arrayfields['s.ip']['checked'])) {
	print_liste_field_titre($arrayfields['s.ip']['label'], $_SERVER["PHP_SELF"], "s.ip", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;	// For the column action
}
// Date creation
if (!empty($arrayfields['s.datec']['checked'])) {
	print_liste_field_titre($arrayfields['s.datec']['label'], $_SERVER["PHP_SELF"], "s.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;	// For the column action
}
if (!empty($arrayfields['s.tms']['checked'])) {
	print_liste_field_titre($arrayfields['s.tms']['label'], $_SERVER["PHP_SELF"], "s.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;	// For the column action
}
if (!empty($arrayfields['s.status']['checked'])) {
	print_liste_field_titre($arrayfields['s.status']['label'], $_SERVER["PHP_SELF"], "s.status", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;	// For the column action
}
if (!empty($arrayfields['s.note_public']['checked'])) {
	print_liste_field_titre($arrayfields['s.note_public']['label'], $_SERVER["PHP_SELF"], "s.note_public", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.note_private']['checked'])) {
	print_liste_field_titre($arrayfields['s.note_private']['label'], $_SERVER["PHP_SELF"], "s.note_private", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.import_key']['checked'])) {
	print_liste_field_titre($arrayfields['s.import_key']['label'], $_SERVER["PHP_SELF"], "s.import_key", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;	// For the column action
}
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
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
	$obj = $db->fetch_object($resql);
	if (empty($obj)) {
		break; // Should not happen
	}

	$parameters = array('staticdata' => $obj);
	// Note that $action and $object may have been modified by hook
	// do companystatic fetch in hook if wanted or anything else
	$reshook = $hookmanager->executeHooks('loadStaticObject', $parameters, $companystatic, $action);
	if (empty($reshook)) {
		$companystatic->id = $obj->rowid;
		$companystatic->name = $obj->name;
		$companystatic->name_alias = $obj->name_alias;
		$companystatic->ref_ext = $obj->ref_ext;
		$companystatic->logo = $obj->logo;
		$companystatic->barcode = $obj->barcode;
		$companystatic->canvas = $obj->canvas;
		$companystatic->client = $obj->client;
		$companystatic->status = $obj->status;
		$companystatic->email = $obj->email;
		$companystatic->phone = $obj->phone;
		$companystatic->phone_mobile = $obj->phone_mobile;
		$companystatic->fax = $obj->fax;
		$companystatic->address = $obj->address;
		$companystatic->zip = $obj->zip;
		$companystatic->town = $obj->town;
		$companystatic->fournisseur = $obj->fournisseur;
		$companystatic->code_client = $obj->code_client;
		$companystatic->code_fournisseur = $obj->code_fournisseur;
		$companystatic->tva_intra = $obj->tva_intra;
		$companystatic->country_code = $obj->country_code;

		$companystatic->ip = $obj->ip;

		$companystatic->code_compta_client = $obj->code_compta;
		$companystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;
		$companystatic->note_public = $obj->note_public;
		$companystatic->note_private = $obj->note_private;
		$companystatic->fk_prospectlevel = $obj->fk_prospectlevel;
		$companystatic->parent = $obj->fk_parent;
		$companystatic->entity = $obj->entity;

		$object = $companystatic;
	}

	if ($mode == 'kanban') {
		if ($i == 0) {
			print '<tr class="trkanban"><td colspan="'.$savnbfield.'">';
			print '<div class="box-flex-container kanban">';
		}
		// Output Kanban
		print $companystatic->getKanbanView('', array('selected' => in_array($obj->rowid, $arrayofselected)));
		if ($i == ($imaxinloop - 1)) {
			print '</div>';
			print '</td></tr>';
		}
	} else {
		// Show line of result
		$j = 0;
		print '<tr data-rowid="'.$companystatic->id.'" class="oddeven"';
		if ($contextpage == 'poslist') {
			print ' onclick="location.href=\'list.php?action=change&contextpage=poslist&idcustomer='.$obj->rowid.'&place='.urlencode($place).'\'"';
		}
		print '>';

		// Action column (Show the massaction button only when this page is not opend from the Extended POS)
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center actioncolumn">';
			if (($massactionbutton || $massaction) && $contextpage != 'poslist') {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
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
		}
		if (!empty($arrayfields['s.rowid']['checked'])) {
			print '<td class="tdoverflowmax50" data-key="id">';
			print dol_escape_htmltag($obj->rowid);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		if (!empty($arrayfields['s.nom']['checked'])) {
			print '<td'.(getDolGlobalString('MAIN_SOCIETE_SHOW_COMPLETE_NAME') ? '' : ' class="tdoverflowmax200"').' data-key="ref">';
			if ($contextpage == 'poslist') {
				print dol_escape_htmltag($companystatic->name);
			} else {
				print $companystatic->getNomUrl(1, '', 100, 0, 1, empty($arrayfields['s.name_alias']['checked']) ? 0 : 1);
			}
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		if (!empty($arrayfields['s.name_alias']['checked'])) {
			print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($companystatic->name_alias).'">';
			print dol_escape_htmltag($companystatic->name_alias);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Ref ext
		if (!empty($arrayfields['s.ref_ext']['checked'])) {
			print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($companystatic->ref_ext).'">';
			print dol_escape_htmltag($companystatic->ref_ext);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Barcode
		if (!empty($arrayfields['s.barcode']['checked'])) {
			print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($companystatic->barcode).'">'.dol_escape_htmltag($companystatic->barcode).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Customer code
		if (!empty($arrayfields['s.code_client']['checked'])) {
			print '<td class="nowraponall">'.dol_escape_htmltag($companystatic->code_client).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Supplier code
		if (!empty($arrayfields['s.code_fournisseur']['checked'])) {
			print '<td class="nowraponall">'.dol_escape_htmltag($companystatic->code_fournisseur).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Account customer code
		if (!empty($arrayfields['s.code_compta']['checked'])) {
			print '<td>'.dol_escape_htmltag($companystatic->code_compta_client).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Account supplier code
		if (!empty($arrayfields['s.code_compta_fournisseur']['checked'])) {
			print '<td>'.dol_escape_htmltag($companystatic->code_compta_fournisseur).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Address
		if (!empty($arrayfields['s.address']['checked'])) {
			print '<td class="tdoverflowmax250" title="'.dol_escape_htmltag($companystatic->address).'">'.dol_escape_htmltag($companystatic->address).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Sales Representative
		if (!empty($arrayfields['sales.representative']['checked'])) {
			print '<td class="nowraponall tdoverflowmax200">';
			$listsalesrepresentatives = $companystatic->getSalesRepresentatives($user);
			$nbofsalesrepresentative = count($listsalesrepresentatives);
			if ($nbofsalesrepresentative > 6) {
				// We print only number
				print $nbofsalesrepresentative;
			} elseif ($nbofsalesrepresentative > 0) {
				$userstatic = new User($db);
				$j = 0;
				foreach ($listsalesrepresentatives as $val) {
					$userstatic->id = $val['id'];
					$userstatic->lastname = $val['lastname'];
					$userstatic->firstname = $val['firstname'];
					$userstatic->email = $val['email'];
					$userstatic->entity = $val['entity'];
					$userstatic->photo = $val['photo'];
					$userstatic->login = $val['login'];
					$userstatic->office_phone = $val['office_phone'];
					$userstatic->office_fax = $val['office_fax'];
					$userstatic->user_mobile = $val['user_mobile'];
					$userstatic->job = $val['job'];
					$userstatic->gender = $val['gender'];
					$userstatic->statut = $val['statut'];
					$userstatic->status = $val['statut'];
					print ($nbofsalesrepresentative < 2) ? $userstatic->getNomUrl(-1, '', 0, 0, 12) : $userstatic->getNomUrl(-2);
					$j++;
					if ($j < $nbofsalesrepresentative) {
						print ' ';
					}
				}
			} else {
				print '&nbsp;';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Zip
		if (!empty($arrayfields['s.zip']['checked'])) {
			print "<td>".dol_escape_htmltag($companystatic->zip)."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Town
		if (!empty($arrayfields['s.town']['checked'])) {
			print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($companystatic->town).'">'.dol_escape_htmltag($companystatic->town)."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// State
		if (!empty($arrayfields['state.nom']['checked'])) {
			print "<td>".dol_escape_htmltag($obj->state_name)."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Region
		if (!empty($arrayfields['region.nom']['checked'])) {
			print "<td>".dol_escape_htmltag($obj->region_name)."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Country
		if (!empty($arrayfields['country.code_iso']['checked'])) {
			print '<td class="center tdoverflowmax100">';
			$labelcountry = ($companystatic->country_code && ($langs->trans("Country".$companystatic->country_code) != "Country".$companystatic->country_code)) ? $langs->trans("Country".$companystatic->country_code) : $obj->country_label;
			print $labelcountry;
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Type ent
		if (!empty($arrayfields['typent.code']['checked'])) {
			if (!isset($typenArray) || !is_array($typenArray) || count($typenArray) == 0) {
				$typenArray = $formcompany->typent_array(1);
			}
			$labeltypeofcompany = empty($typenArray[$obj->typent_code]) ? '' : $typenArray[$obj->typent_code];

			print '<td class="center tdoverflowmax125" title="'.dol_escape_htmltag($labeltypeofcompany).'">';
			print dol_escape_htmltag($labeltypeofcompany);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Multiprice level
		if (!empty($arrayfields['s.price_level']['checked'])) {
			print '<td class="center">'.$obj->price_level."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Staff
		if (!empty($arrayfields['staff.code']['checked'])) {
			print '<td class="center">';
			if (!empty($obj->staff_code)) {
				if (empty($conf->cache['staffArray'])) {
					$conf->cache['staffArray'] = $formcompany->effectif_array(1);
				}
				print $conf->cache['staffArray'][$obj->staff_code];
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Legal form
		if (!empty($arrayfields['legalform.code']['checked'])) {
			$labeltoshow = '';
			if (!empty($obj->legalform_code)) {
				if (empty($conf->cache['legalformArray'][$obj->legalform_code])) {
					$conf->cache['legalformArray'][$obj->legalform_code] = getFormeJuridiqueLabel($obj->legalform_code);
				}
				$labeltoshow = $conf->cache['legalformArray'][$obj->legalform_code];
			}
			print '<td class="center tdoverflowmax100" title="'.dol_escape_htmltag($labeltoshow).'">';
			print dol_escape_htmltag($labeltoshow);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Email
		if (!empty($arrayfields['s.email']['checked'])) {
			print '<td class="tdoverflowmax150">'.dol_print_email($obj->email, $obj->rowid, $obj->rowid, 1, 0, 0, 1)."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		if (!empty($arrayfields['s.phone']['checked'])) {
			print '<td class="nowraponall">'.dol_print_phone($obj->phone, $companystatic->country_code, 0, $obj->rowid, 'AC_TEL', ' ', 'phone')."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		if (!empty($arrayfields['s.phone_mobile']['checked'])) {
			print '<td class="nowraponall">'.dol_print_phone($obj->phone_mobile, $companystatic->country_code, 0, $obj->rowid, 'AC_TEL', ' ', 'phone_mobile')."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		if (!empty($arrayfields['s.fax']['checked'])) {
			print '<td class="nowraponall">'.dol_print_phone($obj->fax, $companystatic->country_code, 0, $obj->rowid, 'AC_TEL', ' ', 'fax')."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		if (!empty($arrayfields['s.url']['checked'])) {
			print "<td>".dol_print_url($obj->url, '', 0, 1)."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		if (!empty($arrayfields['s.siren']['checked'])) {
			print "<td>".$obj->idprof1."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		if (!empty($arrayfields['s.siret']['checked'])) {
			print "<td>".$obj->idprof2."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		if (!empty($arrayfields['s.ape']['checked'])) {
			print "<td>".$obj->idprof3."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		if (!empty($arrayfields['s.idprof4']['checked'])) {
			print "<td>".$obj->idprof4."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		if (!empty($arrayfields['s.idprof5']['checked'])) {
			print "<td>".$obj->idprof5."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		if (!empty($arrayfields['s.idprof6']['checked'])) {
			print "<td>".$obj->idprof6."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// VAT
		if (!empty($arrayfields['s.tva_intra']['checked'])) {
			print '<td class="tdoverflowmax125" title="'.dol_escape_htmltag($companystatic->tva_intra).'">';
			if ($companystatic->tva_intra && !isValidVATID($companystatic)) {
				print img_warning("BadVATNumber", '', 'pictofixedwidth');
			}
			print $companystatic->tva_intra;
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Nature
		if (!empty($arrayfields['customerorsupplier']['checked'])) {
			print '<td class="center">';
			print $companystatic->getTypeUrl(1);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Prospect level
		if (!empty($arrayfields['s.fk_prospectlevel']['checked'])) {
			print '<td class="center nowraponall">';
			print $companystatic->getLibProspLevel();
			print "</td>";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Prospect status
		if (!empty($arrayfields['s.fk_stcomm']['checked'])) {
			print '<td class="center nowraponall">';

			$prospectid = $obj->rowid;
			$statusprospect = $obj->stcomm_id;

			$formcompany->selectProspectStatus('status_prospect', $prospectstatic, $statusprospect, $prospectid);

			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Parent company
		if (!empty($arrayfields['s2.nom']['checked'])) {
			print '<td class="center tdoverflowmax100">';
			if ($companystatic->parent > 0) {
				$companyparent->fetch($companystatic->parent);
				print $companyparent->getNomUrl(1);
			}
			print "</td>";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters = array('arrayfields' => $arrayfields, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// IP
		if (!empty($arrayfields['s.ip']['checked'])) {
			print '<td class="center nowraponall">';
			print dol_print_ip($obj->ip);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Date creation
		if (!empty($arrayfields['s.datec']['checked'])) {
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Date modification
		if (!empty($arrayfields['s.tms']['checked'])) {
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->date_modification), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Note public
		if (!empty($arrayfields['s.note_public']['checked'])) {
			print '<td class="flat maxwidth250imp">';
			print dolPrintHTML(dolGetFirstLineOfText($obj->note_public), 5);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Note private
		if (!empty($arrayfields['s.note_private']['checked'])) {
			print '<td class="flat maxwidth250imp">';
			print dolPrintHTML(dolGetFirstLineOfText($obj->note_private), 5);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Status
		if (!empty($arrayfields['s.status']['checked'])) {
			print '<td class="center nowraponall">'.$companystatic->getLibStatut(5).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Import key
		if (!empty($arrayfields['s.import_key']['checked'])) {
			print '<td class="tdoverflowmax125" title="'.dol_escape_htmltag($obj->import_key).'">';
			print dol_escape_htmltag($obj->import_key);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Action column (Show the massaction button only when this page is not opend from the Extended POS)
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center actioncolumn">';
			if (($massactionbutton || $massaction) && $contextpage != 'poslist') {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
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
		}

		print '</tr>'."\n";
	}
	$i++;
}

// Show total line
include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

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

// Line that calls the select_status function by passing it js as the 5th parameter in order to activate the js script
$formcompany->selectProspectStatus('status_prospect', $prospectstatic, null, null, "js");

print '</form>'."\n";

// End of page
llxFooter();
$db->close();
