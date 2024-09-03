<?php
/* Copyright (C) 2001-2004	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2020	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2013		Cédric Salvador				<csalvador@gpcsolutions.fr>
 * Copyright (C) 2014-2019	Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2015		Claudio Aschieri			<c.aschieri@19.coop>
 * Copyright (C) 2015		Jean-François Ferry			<jfefe@aternatik.fr>
 * Copyright (C) 2016-2018	Ferran Marcet				<fmarcet@2byte.es>
 * Copyright (C) 2019		Nicolas Zabouri				<info@inovea-conseil.com>
 * Copyright (C) 2021-2024	Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2024		Benjamin Falière			<benjamin.faliere@altairis.fr>
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
 *       \file       htdocs/contrat/list.php
 *       \ingroup    contrat
 *       \brief      Page to list contracts
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
if (isModEnabled("category")) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('contracts', 'products', 'companies', 'compta'));

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOSTINT('show_files');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'contractlist'; // To manage different context of search
$optioncss = GETPOST('optioncss', 'alpha');
$mode = GETPOST('mode', 'alpha');

$socid = GETPOSTINT('socid');

$search_name = GETPOST('search_name', 'alpha');
$search_email = GETPOST('search_email', 'alpha');
$search_town = GETPOST('search_town', 'alpha');
$search_zip = GETPOST('search_zip', 'alpha');
$search_state = GETPOST("search_state", 'alpha');
$search_country = GETPOST("search_country", 'aZ09');
$search_type_thirdparty = GETPOST("search_type_thirdparty", 'intcomma');
$search_contract = GETPOST('search_contract', 'alpha');
$search_ref_customer = GETPOST('search_ref_customer', 'alpha');
$search_ref_supplier = GETPOST('search_ref_supplier', 'alpha');
$search_all = (GETPOST('search_all', 'alphanohtml') != '') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml');
$search_status = GETPOST('search_status', 'alpha');
$search_user = GETPOST('search_user', 'intcomma');
$search_sale = GETPOST('search_sale', 'intcomma');
$search_product_category = GETPOST('search_product_category', 'intcomma');
$search_dfmonth = GETPOSTINT('search_dfmonth');
$search_dfyear = GETPOSTINT('search_dfyear');
$search_op2df = GETPOST('search_op2df', 'alpha');
$search_date_startday = GETPOSTINT('search_date_startday');
$search_date_startmonth = GETPOSTINT('search_date_startmonth');
$search_date_startyear = GETPOSTINT('search_date_startyear');
$search_date_endday = GETPOSTINT('search_date_endday');
$search_date_endmonth = GETPOSTINT('search_date_endmonth');
$search_date_endyear = GETPOSTINT('search_date_endyear');
$search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);	// Use tzserver
$search_date_end = dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);
$searchCategoryCustomerOperator = 0;
if (GETPOSTISSET('formfilteraction')) {
	$searchCategoryCustomerOperator = GETPOSTINT('search_category_customer_operator');
} elseif (getDolGlobalString('MAIN_SEARCH_CAT_OR_BY_DEFAULT')) {
	$searchCategoryCustomerOperator = getDolGlobalString('MAIN_SEARCH_CAT_OR_BY_DEFAULT');
}
$searchCategoryCustomerList = GETPOST('search_category_customer_list', 'array');

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
	$sortfield = 'c.ref';
}
if (!$sortorder) {
	$sortorder = 'DESC';
}

// Security check
$id = GETPOSTINT('id');
if ($user->socid > 0) {
	$socid = $user->socid;
}

$hookmanager->initHooks(array('contractlist'));

$result = restrictedArea($user, 'contrat', $id);

$diroutputmassaction = $conf->contrat->dir_output.'/temp/massgeneration/'.$user->id;

$staticcontrat = new Contrat($db);
$staticcontratligne = new ContratLigne($db);

if ($search_status == '') {
	$search_status = 1;
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$object = new Contrat($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');
// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();
foreach ($object->fields as $key => $val) {
	if (!empty($val['searchall'])) {
		$fieldstosearchall['c.'.$key] = $val['label'];
	}
}
$fieldstosearchall["s.nom"] = "ThirdParty";
if (empty($user->socid)) {
	$fieldstosearchall["c.note_private"] = "NotePrivate";
}
$parameters = array('fieldstosearchall' => $fieldstosearchall);
$reshook = $hookmanager->executeHooks('completeFieldsToSearchAll', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook > 0) {
	$fieldstosearchall = $hookmanager->resArray['fieldstosearchall'];
} elseif ($reshook == 0) {
	if (!empty($hookmanager->resArray['fieldstosearchall'])) {
		$fieldstosearchall = array_merge($fieldstosearchall, $hookmanager->resArray['fieldstosearchall']);
	}
}

$arrayfields = array(
	'c.ref' => array('label' => $langs->trans("Ref"), 'checked' => 1, 'position' => 10),
	'c.ref_customer' => array('label' => $langs->trans("RefCustomer"), 'checked' => 1, 'position' => 12),
	'c.ref_supplier' => array('label' => $langs->trans("RefSupplier"), 'checked' => 1, 'position' => 14),
	's.nom' => array('label' => $langs->trans("ThirdParty"), 'checked' => 1, 'position' => 30),
	's.email' => array('label' => $langs->trans("ThirdPartyEmail"), 'checked' => 0, 'position' => 30),
	's.town' => array('label' => $langs->trans("Town"), 'checked' => 0, 'position' => 31),
	's.zip' => array('label' => $langs->trans("Zip"), 'checked' => 1, 'position' => 32),
	'state.nom' => array('label' => $langs->trans("StateShort"), 'checked' => 0, 'position' => 33),
	'country.code_iso' => array('label' => $langs->trans("Country"), 'checked' => 0, 'position' => 34),
	'sale_representative' => array('label' => $langs->trans("SaleRepresentativesOfThirdParty"), 'checked' => -1, 'position' => 80),
	'c.date_contrat' => array('label' => $langs->trans("DateContract"), 'checked' => 1, 'position' => 45),
	'c.datec' => array('label' => $langs->trans("DateCreation"), 'checked' => 0, 'position' => 500),
	'c.tms' => array('label' => $langs->trans("DateModificationShort"), 'checked' => 0, 'position' => 500),
	'lower_planned_end_date' => array('label' => $langs->trans("LowerDateEndPlannedShort"), 'checked' => 1, 'position' => 900, 'help' => $langs->trans("LowerDateEndPlannedShort")),
	'status' => array('label' => $langs->trans("Status"), 'checked' => 1, 'position' => 1000),
);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');
'@phan-var-force array<string,array{label:string,checked?:int<0,1>,position?:int,help?:string}> $arrayfields';  // dol_sort_array looses type for Phan

if (!$user->hasRight('societe', 'client', 'voir')) {
	$search_sale = $user->id;
}

$permissiontoread = $user->hasRight('contrat', 'lire');
$permissiontoadd = $user->hasRight('contrat', 'creer');
$permissiontodelete = $user->hasRight('contrat', 'supprimer');

$result = restrictedArea($user, 'contrat', 0);



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

$parameters = array('socid' => $socid, 'arrayfields' => &$arrayfields);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';
// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All test are required to be compatible with all browsers
	$search_dfmonth = '';
	$search_dfyear = '';
	$search_op2df = '';
	$search_name = "";
	$search_email = "";
	$search_town = '';
	$search_zip = "";
	$search_state = "";
	$search_type = '';
	$search_country = '';
	$search_contract = "";
	$search_ref_customer = "";
	$search_ref_supplier = "";
	$search_user = '';
	$search_sale = '';
	$search_product_category = '';
	$search_date_startday = '';
	$search_date_startmonth = '';
	$search_date_startyear = '';
	$search_date_endday = '';
	$search_date_endmonth = '';
	$search_date_endyear = '';
	$search_date_start = '';
	$search_date_end = '';
	$search_all = "";
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
	$search_status = "";
	$toselect = array();
	$search_type_thirdparty = '';
	$searchCategoryCustomerList = array();
	$search_array_options = array();
}

if (empty($reshook)) {
	$objectclass = 'Contrat';
	$objectlabel = 'Contracts';
	$uploaddir = $conf->contrat->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formother = new FormOther($db);
$socstatic = new Societe($db);
$formcompany = new FormCompany($db);
$contracttmp = new Contrat($db);

$now = dol_now();

$title = "";

$sql = 'SELECT';
$sql .= " c.rowid, c.ref, c.datec as date_creation, c.tms as date_modification, c.date_contrat, c.statut, c.ref_customer, c.ref_supplier, c.note_private, c.note_public, c.entity,";
$sql .= ' s.rowid as socid, s.nom as name, s.name_alias, s.email, s.town, s.zip, s.fk_pays as country_id, s.client, s.code_client, s.status as company_status, s.logo as company_logo,';
$sql .= " typent.code as typent_code,";
$sql .= " state.code_departement as state_code, state.nom as state_name,";
$sql .= " MIN(".$db->ifsql("cd.statut=4", "cd.date_fin_validite", "null").") as lower_planned_end_date,";
$sql .= " SUM(".$db->ifsql("cd.statut=0", 1, 0).') as nb_initial,';
$sql .= " SUM(".$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NULL OR cd.date_fin_validite >= '".$db->idate($now)."')", 1, 0).') as nb_running,';
$sql .= " SUM(".$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NOT NULL AND cd.date_fin_validite < '".$db->idate($now)."')", 1, 0).') as nb_expired,';
$sql .= " SUM(".$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NOT NULL AND cd.date_fin_validite < '".$db->idate($now - $conf->contrat->services->expires->warning_delay)."')", 1, 0).') as nb_late,';
$sql .= " SUM(".$db->ifsql("cd.statut=5", 1, 0).') as nb_closed';
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

$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";
$sql .= ", ".MAIN_DB_PREFIX."contrat as c";
if (!empty($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (c.rowid = ef.fk_object)";
}
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."contratdet as cd ON c.rowid = cd.fk_contrat";
if ($search_user > 0) {
	$sql .= ", ".MAIN_DB_PREFIX."element_contact as ec";
	$sql .= ", ".MAIN_DB_PREFIX."c_type_contact as tc";
}
$sql .= " WHERE c.fk_soc = s.rowid ";
$sql .= ' AND c.entity IN ('.getEntity('contract').')';
if ($search_type_thirdparty != '' && $search_type_thirdparty > 0) {
	$sql .= " AND s.fk_typent IN (".$db->sanitize($db->escape($search_type_thirdparty)).')';
}
if ($socid > 0) {
	$sql .= " AND s.rowid = ".((int) $socid);
}
if ($search_date_start) {
	$sql .= " AND c.date_contrat >= '".$db->idate($search_date_start)."'";
}
if ($search_date_end) {
	$sql .= " AND c.date_contrat <= '".$db->idate($search_date_end)."'";
}
if ($search_name) {
	$sql .= natural_search('s.nom', $search_name);
}
if ($search_email) {
	$sql .= natural_search('s.email', $search_email);
}
if ($search_contract) {
	$sql .= natural_search(array('c.rowid', 'c.ref'), $search_contract);
}
if (!empty($search_ref_customer)) {
	$sql .= natural_search(array('c.ref_customer'), $search_ref_customer);
}
if (!empty($search_ref_supplier)) {
	$sql .= natural_search(array('c.ref_supplier'), $search_ref_supplier);
}
if ($search_zip) {
	$sql .= natural_search(array('s.zip'), $search_zip);
}
if ($search_town) {
	$sql .= natural_search(array('s.town'), $search_town);
}
if ($search_country && $search_country != '-1') {
	$sql .= " AND s.fk_pays IN (".$db->sanitize($search_country).')';
}
/*if ($search_sale > 0) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $search_sale);
}*/
if ($search_all) {
	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}
if ($search_user > 0) {
	$sql .= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='contrat' AND tc.source='internal' AND ec.element_id = c.rowid AND ec.fk_socpeople = ".((int) $search_user);
}
// Search on sale representative
if ($search_sale && $search_sale != '-1') {
	if ($search_sale == -2) {
		$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = c.fk_soc)";
	} elseif ($search_sale > 0) {
		$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = c.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
	}
}
// Search for tag/category ($searchCategoryProductList is an array of ID)
$searchCategoryProductOperator = -1;
$searchCategoryProductList = array($search_product_category);
if (!empty($searchCategoryProductList)) {
	$searchCategoryProductSqlList = array();
	$listofcategoryid = '';
	foreach ($searchCategoryProductList as $searchCategoryProduct) {
		if (intval($searchCategoryProduct) == -2) {
			$searchCategoryProductSqlList[] = "NOT EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck, ".MAIN_DB_PREFIX."contratdet as cd WHERE cd.fk_contrat = c.rowid AND cd.fk_product = ck.fk_product)";
		} elseif (intval($searchCategoryProduct) > 0) {
			if ($searchCategoryProductOperator == 0) {
				$searchCategoryProductSqlList[] = " EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck, ".MAIN_DB_PREFIX."contratdet as cd WHERE cd.fk_contrat = c.rowid AND cd.fk_product = ck.fk_product AND ck.fk_categorie = ".((int) $searchCategoryProduct).")";
			} else {
				$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategoryProduct);
			}
		}
	}
	if ($listofcategoryid) {
		$searchCategoryProductSqlList[] = " EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck, ".MAIN_DB_PREFIX."contratdet as cd WHERE cd.fk_contrat = c.rowid AND cd.fk_product = ck.fk_product AND ck.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
	}
	if ($searchCategoryProductOperator == 1) {
		if (!empty($searchCategoryProductSqlList)) {
			$sql .= " AND (".implode(' OR ', $searchCategoryProductSqlList).")";
		}
	} else {
		if (!empty($searchCategoryProductSqlList)) {
			$sql .= " AND (".implode(' AND ', $searchCategoryProductSqlList).")";
		}
	}
}
$searchCategoryCustomerSqlList = array();
if ($searchCategoryCustomerOperator == 1) {
	$existsCategoryCustomerList = array();
	foreach ($searchCategoryCustomerList as $searchCategoryCustomer) {
		if (intval($searchCategoryCustomer) == -2) {
			$sqlCategoryCustomerNotExists  = " NOT EXISTS (";
			$sqlCategoryCustomerNotExists .= " SELECT cat_cus.fk_soc";
			$sqlCategoryCustomerNotExists .= " FROM ".$db->prefix()."categorie_societe AS cat_cus";
			$sqlCategoryCustomerNotExists .= " WHERE cat_cus.fk_soc = s.rowid";
			$sqlCategoryCustomerNotExists .= " )";
			$searchCategoryCustomerSqlList[] = $sqlCategoryCustomerNotExists;
		} elseif (intval($searchCategoryCustomer) > 0) {
			$existsCategoryCustomerList[] = $db->escape($searchCategoryCustomer);
		}
	}
	if (!empty($existsCategoryCustomerList)) {
		$sqlCategoryCustomerExists = " EXISTS (";
		$sqlCategoryCustomerExists .= " SELECT cat_cus.fk_soc";
		$sqlCategoryCustomerExists .= " FROM ".$db->prefix()."categorie_societe AS cat_cus";
		$sqlCategoryCustomerExists .= " WHERE cat_cus.fk_soc = s.rowid";
		$sqlCategoryCustomerExists .= " AND cat_cus.fk_categorie IN (".$db->sanitize(implode(',', $existsCategoryCustomerList)).")";
		$sqlCategoryCustomerExists .= " )";
		$searchCategoryCustomerSqlList[] = $sqlCategoryCustomerExists;
	}
	if (!empty($searchCategoryCustomerSqlList)) {
		$sql .= " AND (".implode(' OR ', $searchCategoryCustomerSqlList).")";
	}
} else {
	foreach ($searchCategoryCustomerList as $searchCategoryCustomer) {
		if (intval($searchCategoryCustomer) == -2) {
			$sqlCategoryCustomerNotExists = " NOT EXISTS (";
			$sqlCategoryCustomerNotExists .= " SELECT cat_cus.fk_soc";
			$sqlCategoryCustomerNotExists .= " FROM ".$db->prefix()."categorie_societe AS cat_cus";
			$sqlCategoryCustomerNotExists .= " WHERE cat_cus.fk_soc = s.rowid";
			$sqlCategoryCustomerNotExists .= " )";
			$searchCategoryCustomerSqlList[] = $sqlCategoryCustomerNotExists;
		} elseif (intval($searchCategoryCustomer) > 0) {
			$searchCategoryCustomerSqlList[] = "s.rowid IN (SELECT fk_soc FROM ".$db->prefix()."categorie_societe WHERE fk_categorie = ".((int) $searchCategoryCustomer).")";
		}
	}
	if (!empty($searchCategoryCustomerSqlList)) {
		$sql .= " AND (".implode(' AND ', $searchCategoryCustomerSqlList).")";
	}
}

if ($search_date_creation_start) {
	$sql .= " AND c.datec >= '".$db->idate($search_date_creation_start)."'";
}
if ($search_date_creation_end) {
	$sql .= " AND c.datec <= '".$db->idate($search_date_creation_end)."'";
}

if ($search_date_modif_start) {
	$sql .= " AND c.tms >= '".$db->idate($search_date_modif_start)."'";
}
if ($search_date_modif_end) {
	$sql .= " AND c.tms <= '".$db->idate($search_date_modif_end)."'";
}

// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " GROUP BY c.rowid, c.ref, c.datec, c.tms, c.date_contrat, c.statut, c.ref_customer, c.ref_supplier, c.note_private, c.note_public, c.entity,";
$sql .= ' s.rowid, s.nom, s.name_alias, s.email, s.town, s.zip, s.fk_pays, s.client, s.code_client, s.status, s.logo,';
$sql .= " typent.code,";
$sql .= " state.code_departement, state.nom";
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key : '');
	}
}
// Add where from hooks
$parameters = array('search_dfyear' => $search_dfyear, 'search_op2df' => $search_op2df);
$reshook = $hookmanager->executeHooks('printFieldListGroupBy', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
// Add HAVING from hooks
$parameters = array('search_dfyear' => $search_dfyear, 'search_op2df' => $search_op2df);
$reshook = $hookmanager->executeHooks('printFieldListHaving', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
	if ($search_dfyear > 0 && $search_op2df) {
		if ($search_op2df == '<=') {
			$sql .= " HAVING MIN(".$db->ifsql("cd.statut=4", "cd.date_fin_validite", "null").") <= '".$db->idate(dol_get_last_day($search_dfyear, $search_dfmonth, false))."'";
		} elseif ($search_op2df == '>=') {
			$sql .= " HAVING MIN(".$db->ifsql("cd.statut=4", "cd.date_fin_validite", "null").") >= '".$db->idate(dol_get_first_day($search_dfyear, $search_dfmonth, false))."'";
		} else {
			$sql .= " HAVING MIN(".$db->ifsql("cd.statut=4", "cd.date_fin_validite", "null").") <= '".$db->idate(dol_get_last_day($search_dfyear, $search_dfmonth, false))."' AND MIN(".$db->ifsql("cd.statut=4", "cd.date_fin_validite", "null").") >= '".$db->idate(dol_get_first_day($search_dfyear, $search_dfmonth, false))."'";
		}
	}
}
$sql .= $hookmanager->resPrint;

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	//$result = $db->query($sql);
	//$nbtotalofrecords = $db->num_rows($result);

	if ($search_dfyear > 0 && $search_op2df) {
		$resql = $db->query($sql, 0, 'auto', 1);
		while ($db->fetch_object($resql)) {
			if (empty($nbtotalofrecords)) {
				$nbtotalofrecords = 1;    // We can't make +1 because init value is ''
			} else {
				$nbtotalofrecords++;
			}
		}
	} else {
		/* The fast and low memory method to get and count full list converts the sql into a sql count */
		$sqlforcount = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
		$sqlforcount = preg_replace('/LEFT JOIN '.MAIN_DB_PREFIX.'contratdet as cd ON c.rowid = cd.fk_contrat /', '', $sqlforcount);
		$sqlforcount = preg_replace('/GROUP BY.*$/', '', $sqlforcount);

		$resql = $db->query($sqlforcount);
		if ($resql) {
			$objforcount = $db->fetch_object($resql);
			$nbtotalofrecords = $objforcount->nbtotalofrecords;
		} else {
			dol_print_error($db);
		}
	}

	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
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
if ($num == 1 && getDolGlobalString('MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE') && $search_all && !$page) {
	$obj = $db->fetch_object($resql);
	$id = $obj->rowid;
	header("Location: ".DOL_URL_ROOT.'/contrat/card.php?id='.$id);
	exit;
}


// Output page
// --------------------------------------------------------------------
$title = $langs->trans("Contracts");
$help_url = 'EN:Module_Contracts|FR:Module_Contrat|ES:Contratos_de_servicio';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-contrat page-list bodyforlist');

$i = 0;

$arrayofselected = is_array($toselect) ? $toselect : array();

if ($socid > 0) {
	$soc = new Societe($db);
	$soc->fetch($socid);
	if (empty($search_name)) {
		$search_name = $soc->name;
	}
}

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
if ($search_all != '') {
	$param .= '&search_all='.urlencode($search_all);
}
if ($search_contract != '') {
	$param .= '&search_contract='.urlencode($search_contract);
}
if ($search_name != '') {
	$param .= '&search_name='.urlencode($search_name);
}
if ($search_email != '') {
	$param .= '&search_email='.urlencode($search_email);
}
if ($search_ref_customer != '') {
	$param .= '&search_ref_customer='.urlencode($search_ref_customer);
}
if ($search_ref_supplier != '') {
	$param .= '&search_ref_supplier='.urlencode($search_ref_supplier);
}
if ($search_op2df != '') {
	$param .= '&search_op2df='.urlencode($search_op2df);
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
if ($search_date_startday > 0) {
	$param .= '&search_date_startday='.urlencode((string) ($search_date_startday));
}
if ($search_date_startmonth > 0) {
	$param .= '&search_date_startmonth='.urlencode((string) ($search_date_startmonth));
}
if ($search_date_startyear > 0) {
	$param .= '&search_date_startyear='.urlencode((string) ($search_date_startyear));
}
if ($search_date_endday > 0) {
	$param .= '&search_date_endday='.urlencode((string) ($search_date_endday));
}
if ($search_date_endmonth > 0) {
	$param .= '&search_date_endmonth='.urlencode((string) ($search_date_endmonth));
}
if ($search_date_endyear > 0) {
	$param .= '&search_date_endyear='.urlencode((string) ($search_date_endyear));
}
if ($search_dfyear > 0) {
	$param .= '&search_dfyear='.urlencode((string) ($search_dfyear));
}
if ($search_dfmonth > 0) {
	$param .= '&search_dfmonth='.urlencode((string) ($search_dfmonth));
}
if ($search_sale > 0) {
	$param .= '&search_sale='.urlencode($search_sale);
}
if ($search_user > 0) {
	$param .= '&search_user='.urlencode((string) ($search_user));
}
if ($search_type_thirdparty > 0) {
	$param .= '&search_type_thirdparty='.urlencode((string) ($search_type_thirdparty));
}
if ($search_country != '') {
	$param .= "&search_country=".urlencode($search_country);
}
if ($search_product_category > 0) {
	$param .= '&search_product_category='.urlencode((string) ($search_product_category));
}
if ($show_files) {
	$param .= '&show_files='.urlencode((string) ($show_files));
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
foreach ($searchCategoryCustomerList as $searchCategoryCustomer) {
	$param .= "&search_category_customer_list[]=".urlencode($searchCategoryCustomer);
}
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// List of mass actions available
$arrayofmassactions = array(
	'generate_doc' => img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
	'builddoc' => img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
	'presend' => img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
);
if (!empty($permissiontodelete)) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if (GETPOSTINT('nomassaction') || in_array($massaction, array('presend', 'predelete'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

$url = DOL_URL_ROOT.'/contrat/card.php?action=create';
if (!empty($socid)) {
	$url .= '&socid='.((int) $socid);
}
$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitleSeparator();
$newcardbutton .= dolGetButtonTitle($langs->trans('NewContractSubscription'), '', 'fa fa-plus-circle', $url, '', $user->hasRight('contrat', 'creer'));

print '<form method="POST" id="searchFormList" action="'.$_SERVER['PHP_SELF'].'">'."\n";
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
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
print_barre_liste($langs->trans("Contracts"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'contract', 0, $newcardbutton, '', $limit, 0, 0, 1);

$topicmail = "SendContractRef";
$modelmail = "contract";
$objecttmp = new Contrat($db);
$trackid = 'con'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($search_all) {
	$setupstring = '';
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
		$setupstring .= $key."=".$val.";";
	}
	print '<!-- Search done like if CONTRACT_QUICKSEARCH_ON_FIELDS = '.$setupstring.' -->'."\n";
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).implode(', ', $fieldstosearchall).'</div>'."\n";
}

$moreforfilter = '';

// If the user can view prospects other than his'
if ($user->hasRight('user', 'user', 'lire')) {
	$langs->load("commercial");
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('ThirdPartiesOfSaleRepresentative');
	$moreforfilter .= img_picto($tmptitle, 'user', 'class="pictofixedwidth"').$formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, $tmptitle, 'widthcentpercentminusx maxwidth300');
	$moreforfilter .= '</div>';
}
// If the user can view other users
if ($user->hasRight('user', 'user', 'lire')) {
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('LinkedToSpecificUsers');
	$moreforfilter .= img_picto($tmptitle, 'user', 'class="pictofixedwidth"').$form->select_dolusers($search_user, 'search_user', $tmptitle, '', 0, '', '', 0, 0, 0, '', 0, '', 'widthcentpercentminusx maxwidth300');
	$moreforfilter .= '</div>';
}
// If the user can view categories of products
if (isModEnabled('category') && $user->hasRight('categorie', 'lire') && ($user->hasRight('produit', 'lire') || $user->hasRight('service', 'lire'))) {
	include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('IncludingProductWithTag');
	$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, '', 'parent', 64, 0, 2);
	$moreforfilter .= img_picto($tmptitle, 'category', 'class="pictofixedwidth"').$form->selectarray('search_product_category', $cate_arbo, $search_product_category, $tmptitle, 0, 0, '', 0, 0, 0, 0, 'widthcentpercentminusx maxwidth300', 1);
	$moreforfilter .= '</div>';
}
// Filter on customer categories
if (getDolGlobalString('MAIN_SEARCH_CATEGORY_CUSTOMER_ON_CONTRACT_LIST') && isModEnabled("category") && $user->hasRight('categorie', 'lire')) {
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->transnoentities('CustomersProspectsCategoriesShort');
	$moreforfilter .= img_picto($tmptitle, 'category', 'class="pictofixedwidth"');
	$categoriesArr = $form->select_all_categories(Categorie::TYPE_CUSTOMER, '', '', 64, 0, 2);
	$categoriesArr[-2] = '- '.$langs->trans('NotCategorized').' -';
	$moreforfilter .= Form::multiselectarray('search_category_customer_list', $categoriesArr, $searchCategoryCustomerList, 0, 0, 'minwidth300', 0, 0, '', 'category', $tmptitle);
	$moreforfilter .= ' <input type="checkbox" class="valignmiddle" id="search_category_customer_operator" name="search_category_customer_operator" value="1"'.($searchCategoryCustomerOperator == 1 ? ' checked="checked"' : '').'/>';
	$moreforfilter .= $form->textwithpicto('', $langs->trans('UseOrOperatorForCategories') . ' : ' . $tmptitle, 1, 'help', '', 0, 2, 'tooltip_cat_cus'); // Tooltip on click
	$moreforfilter .= '</div>';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
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
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')); // This also change content of $arrayfields
if ($massactionbutton) {
	$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
}

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre_filter">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre maxwidthsearch center">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}
if (!empty($arrayfields['c.ref']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" size="3" name="search_contract" value="'.dol_escape_htmltag($search_contract).'">';
	print '</td>';
}
if (!empty($arrayfields['c.ref_customer']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" size="6" name="search_ref_customer" value="'.dol_escape_htmltag($search_ref_customer).'">';
	print '</td>';
}
if (!empty($arrayfields['c.ref_supplier']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" size="6" name="search_ref_supplier" value="'.dol_escape_htmltag($search_ref_supplier).'">';
	print '</td>';
}
if (!empty($arrayfields['s.nom']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" size="8" name="search_name" value="'.dol_escape_htmltag($search_name).'">';
	print '</td>';
}
if (!empty($arrayfields['s.email']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" size="6" name="search_email" value="'.dol_escape_htmltag($search_email).'">';
	print '</td>';
}
// Town
if (!empty($arrayfields['s.town']['checked'])) {
	print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_town" value="'.dol_escape_htmltag($search_town).'"></td>';
}
// Zip
if (!empty($arrayfields['s.zip']['checked'])) {
	print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_zip" value="'.dol_escape_htmltag($search_zip).'"></td>';
}
// State
if (!empty($arrayfields['state.nom']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" size="4" type="text" name="search_state" value="'.dol_escape_htmltag($search_state).'">';
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
	print $form->selectarray("search_type_thirdparty", $formcompany->typent_array(0), $search_type_thirdparty, 1, 0, 0, '', 0, 0, 0, (!getDolGlobalString('SOCIETE_SORT_ON_TYPEENT') ? 'ASC' : $conf->global->SOCIETE_SORT_ON_TYPEENT), '', 1);
	print '</td>';
}
if (!empty($arrayfields['sale_representative']['checked'])) {
	print '<td class="liste_titre"></td>';
}
if (!empty($arrayfields['c.date_contrat']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields' => $arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Creation date
if (!empty($arrayfields['c.datec']['checked'])) {
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
if (!empty($arrayfields['c.tms']['checked'])) {
	print '<td class="liste_titre center nowraponall">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_modif_start ? $search_date_modif_start : -1, 'search_date_modif_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_modif_end ? $search_date_modif_end : -1, 'search_date_modif_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
// First end date
if (!empty($arrayfields['lower_planned_end_date']['checked'])) {
	print '<td class="liste_titre nowraponall center">';
	$arrayofoperators = array('0' => '', '=' => '=', '<=' => '<=', '>=' => '>=');
	print $form->selectarray('search_op2df', $arrayofoperators, $search_op2df, 0, 0, 0, '', 0, 0, 0, '', 'maxwidth50imp');
	print '</br>';
	print $formother->select_month($search_dfmonth, 'search_dfmonth', 1, 0);
	print ' ';
	print $formother->selectyear($search_dfyear, 'search_dfyear', 1, 20, 5, 0, 0, '');
	print '</td>';
}
// Status
if (!empty($arrayfields['status']['checked'])) {
	print '<td class="liste_titre right" colspan="4"></td>';
}

// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center">';
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
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	$totalarray['nbfield']++;	// For the column action
}
if (!empty($arrayfields['c.ref']['checked'])) {
	print_liste_field_titre($arrayfields['c.ref']['label'], $_SERVER["PHP_SELF"], "c.ref", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;	// For the column action
}
if (!empty($arrayfields['c.ref_customer']['checked'])) {
	print_liste_field_titre($arrayfields['c.ref_customer']['label'], $_SERVER["PHP_SELF"], "c.ref_customer", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;	// For the column action
}
if (!empty($arrayfields['c.ref_supplier']['checked'])) {
	print_liste_field_titre($arrayfields['c.ref_supplier']['label'], $_SERVER["PHP_SELF"], "c.ref_supplier", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;	// For the column action
}
if (!empty($arrayfields['s.nom']['checked'])) {
	print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], "s.nom", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;	// For the column action
}
if (!empty($arrayfields['s.email']['checked'])) {
	print_liste_field_titre($arrayfields['s.email']['label'], $_SERVER["PHP_SELF"], "s.email", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;	// For the column action
}
if (!empty($arrayfields['s.town']['checked'])) {
	print_liste_field_titre($arrayfields['s.town']['label'], $_SERVER["PHP_SELF"], 's.town', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;	// For the column action
}
if (!empty($arrayfields['s.zip']['checked'])) {
	print_liste_field_titre($arrayfields['s.zip']['label'], $_SERVER["PHP_SELF"], 's.zip', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;	// For the column action
}
if (!empty($arrayfields['state.nom']['checked'])) {
	print_liste_field_titre($arrayfields['state.nom']['label'], $_SERVER["PHP_SELF"], "state.nom", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;	// For the column action
}
if (!empty($arrayfields['country.code_iso']['checked'])) {
	print_liste_field_titre($arrayfields['country.code_iso']['label'], $_SERVER["PHP_SELF"], "country.code_iso", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;	// For the column action
}
if (!empty($arrayfields['typent.code']['checked'])) {
	print_liste_field_titre($arrayfields['typent.code']['label'], $_SERVER["PHP_SELF"], "typent.code", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;	// For the column action
}
if (!empty($arrayfields['sale_representative']['checked'])) {
	print_liste_field_titre($arrayfields['sale_representative']['label'], $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;	// For the column action
}
if (!empty($arrayfields['c.date_contrat']['checked'])) {
	print_liste_field_titre($arrayfields['c.date_contrat']['label'], $_SERVER["PHP_SELF"], "c.date_contrat", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;	// For the column action
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder, 'totalarray' => &$totalarray);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!empty($arrayfields['c.datec']['checked'])) {
	print_liste_field_titre($arrayfields['c.datec']['label'], $_SERVER["PHP_SELF"], "c.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;	// For the column action
}
if (!empty($arrayfields['c.tms']['checked'])) {
	print_liste_field_titre($arrayfields['c.tms']['label'], $_SERVER["PHP_SELF"], "c.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;	// For the column action
}
if (!empty($arrayfields['lower_planned_end_date']['checked'])) {
	print_liste_field_titre($arrayfields['lower_planned_end_date']['label'], $_SERVER["PHP_SELF"], "lower_planned_end_date", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;	// For the column action
}
if (!empty($arrayfields['status']['checked'])) {
	print_liste_field_titre($staticcontratligne->LibStatut(0, 3, -1, 'class="nochangebackground"'), '', '', '', '', 'width="16"');
	$totalarray['nbfield']++;	// For the column action
	print_liste_field_titre($staticcontratligne->LibStatut(4, 3, 0, 'class="nochangebackground"'), '', '', '', '', 'width="16"');
	$totalarray['nbfield']++;	// For the column action
	print_liste_field_titre($staticcontratligne->LibStatut(4, 3, 1, 'class="nochangebackground"'), '', '', '', '', 'width="16"');
	$totalarray['nbfield']++;	// For the column action
	print_liste_field_titre($staticcontratligne->LibStatut(5, 3, -1, 'class="nochangebackground"'), '', '', '', '', 'width="16"');
	$totalarray['nbfield']++;	// For the column action
}
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	$totalarray['nbfield']++;	// For the column action
}
print "</tr>\n";

// Loop on record
// --------------------------------------------------------------------
$i = 0;
$savnbfield = $totalarray['nbfield'];
$totalarray = array();
$totalarray['nbfield'] = 0;
$typenArray = array();
$cacheCountryIDCode = array();
$imaxinloop = ($limit ? min($num, $limit) : $num);
while ($i < $imaxinloop) {
	$obj = $db->fetch_object($resql);
	if (empty($obj)) {
		break; // Should not happen
	}

	$contracttmp->ref = $obj->ref;
	$contracttmp->id = $obj->rowid;
	$contracttmp->ref_customer = $obj->ref_customer;
	$contracttmp->ref_supplier = $obj->ref_supplier;

	$contracttmp->nbofserviceswait = $obj->nb_initial;
	$contracttmp->nbofservicesopened = $obj->nb_running;
	$contracttmp->nbofservicesexpired = $obj->nb_expired;
	$contracttmp->nbofservicesclosed = $obj->nb_closed;

	$socstatic->id = $obj->socid;
	$socstatic->name = $obj->name;
	$socstatic->name_alias = $obj->name_alias;
	$socstatic->email = $obj->email;
	$socstatic->status = $obj->company_status;
	$socstatic->logo = $obj->company_logo;
	$socstatic->country_id = $obj->country_id;
	$socstatic->country_code = '';
	$socstatic->country = '';

	if ($obj->country_id > 0) {
		if (!isset($cacheCountryIDCode[$obj->country_id]['code'])) {
			$tmparray = getCountry($obj->country_id, 'all');
			$cacheCountryIDCode[$obj->country_id] = array('code' => empty($tmparray['code']) ? '' : $tmparray['code'], 'label' => empty($tmparray['label']) ? '' : $tmparray['label']);
		}
		$socstatic->country_code = $cacheCountryIDCode[$obj->country_id]['code'];
		$socstatic->country = $cacheCountryIDCode[$obj->country_id]['label'];
	}

	if ($mode == 'kanban') {
		if ($i == 0) {
			print '<tr class="trkanban"><td colspan="'.$savnbfield.'">';
			print '<div class="box-flex-container kanban">';
		}
		// Output Kanban
		$arraydata = array();
		$arraydata['thirdparty'] = $socstatic;
		$arraydata['selected'] = in_array($obj->rowid, $arrayofselected);
		$contracttmp->date_contrat = $obj->date_contrat;
		print $contracttmp->getKanbanView('', $arraydata);
		if ($i == ($imaxinloop - 1)) {
			print '</div>';
			print '</td></tr>';
		}
	} else {
		// Show here line of result
		print '<tr data-rowid="'.$object->id.'" class="oddeven">';
		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
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
		}
		// Ref
		if (!empty($arrayfields['c.ref']['checked'])) {
			print '<td class="nowraponall">';
			print $contracttmp->getNomUrl(1);
			if ($obj->nb_late) {
				print img_warning($langs->trans("Late"));
			}
			if (!empty($obj->note_private) || !empty($obj->note_public)) {
				print ' <span class="note">';
				print '<a href="'.DOL_URL_ROOT.'/contrat/note.php?id='.$obj->rowid.'&save_lastsearch_values=1">'.img_picto($langs->trans("ViewPrivateNote"), 'note').'</a>';
				print '</span>';
			}

			$filename = dol_sanitizeFileName($obj->ref);
			$filedir = $conf->contrat->multidir_output[$obj->entity].'/'.dol_sanitizeFileName($obj->ref);
			$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;
			print $formfile->getDocumentsLink($contracttmp->element, $filename, $filedir);
			print '</td>';

			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Ref thirdparty
		if (!empty($arrayfields['c.ref_customer']['checked'])) {
			print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag(dol_string_nohtmltag($contracttmp->getFormatedCustomerRef($obj->ref_customer))).'">'.$contracttmp->getFormatedCustomerRef($obj->ref_customer).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		if (!empty($arrayfields['c.ref_supplier']['checked'])) {
			print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($obj->ref_supplier).'">'.dol_escape_htmltag($obj->ref_supplier).'</td>';
		}
		if (!empty($arrayfields['s.nom']['checked'])) {
			print '<td class="tdoverflowmax150">';
			if ($obj->socid > 0) {
				// TODO Use a cache for this string
				print $socstatic->getNomUrl(1, '');
			}
			print '</td>';
		}
		// Email
		if (!empty($arrayfields['s.email']['checked'])) {
			print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($obj->email).'">'.dol_print_email($obj->email, 0, $obj->socid, 1, 0, 1, 1).'</td>';
		}
		// Town
		if (!empty($arrayfields['s.town']['checked'])) {
			print '<td class="nocellnopadd">';
			print $obj->town;
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Zip
		if (!empty($arrayfields['s.zip']['checked'])) {
			print '<td class="center nocellnopadd">';
			print $obj->zip;
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// State
		if (!empty($arrayfields['state.nom']['checked'])) {
			print "<td>".$obj->state_name."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Country
		if (!empty($arrayfields['country.code_iso']['checked'])) {
			print '<td class="center tdoverflowmax100" title="'.dol_escape_htmltag($socstatic->country).'">';
			print dol_escape_htmltag($socstatic->country);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Type ent
		if (!empty($arrayfields['typent.code']['checked'])) {
			print '<td class="center">';
			if (count($typenArray) == 0) {
				$typenArray = $formcompany->typent_array(1);
			}
			print $typenArray[$obj->typent_code];
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		if (!empty($arrayfields['sale_representative']['checked'])) {
			// Sales representatives
			print '<td>';
			if ($obj->socid > 0) {
				$listsalesrepresentatives = $socstatic->getSalesRepresentatives($user);
				if ($listsalesrepresentatives < 0) {
					dol_print_error($db);
				}
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
						$userstatic->status = $val['statut'];
						$userstatic->entity = $val['entity'];
						$userstatic->photo = $val['photo'];
						$userstatic->login = $val['login'];
						$userstatic->phone = $val['phone'];
						$userstatic->job = $val['job'];
						$userstatic->gender = $val['gender'];

						//print '<div class="float">':
						print ($nbofsalesrepresentative < 2) ? $userstatic->getNomUrl(-1, '', 0, 0, 12) : $userstatic->getNomUrl(-2);
						$j++;
						if ($j < $nbofsalesrepresentative) {
							print ' ';
						}
						//print '</div>';
					}
				}
				//else print $langs->trans("NoSalesRepresentativeAffected");
			} else {
				print '&nbsp;';
			}
			print '</td>';
		}
		// Date
		if (!empty($arrayfields['c.date_contrat']['checked'])) {
			print '<td class="center">'.dol_print_date($db->jdate($obj->date_contrat), 'day', 'tzserver').'</td>';
		}
		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters = array('arrayfields' => $arrayfields, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Date creation
		if (!empty($arrayfields['c.datec']['checked'])) {
			print '<td class="center nowrap">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Date modification
		if (!empty($arrayfields['c.tms']['checked'])) {
			print '<td class="center nowrap">';
			print dol_print_date($db->jdate($obj->date_modification), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Date lower end date
		if (!empty($arrayfields['lower_planned_end_date']['checked'])) {
			print '<td class="center nowrapforall">';
			print dol_print_date($db->jdate($obj->lower_planned_end_date), 'day', 'tzuser');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Status
		if (!empty($arrayfields['status']['checked'])) {
			print '<td class="center">'.($obj->nb_initial > 0 ? $obj->nb_initial : '').'</td>';
			print '<td class="center">'.($obj->nb_running > 0 ? $obj->nb_running : '').'</td>';
			print '<td class="center">'.($obj->nb_expired > 0 ? $obj->nb_expired : '').'</td>';
			print '<td class="center">'.($obj->nb_closed > 0 ? $obj->nb_closed : '').'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
				$totalarray['nbfield']++;
				$totalarray['nbfield']++;
				$totalarray['nbfield']++;
			}
		}
		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
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
		}

		print '</tr>'."\n";
	}
	$i++;
}

// If no record found
if ($num == 0) {
	$colspan = 4;	// Include the 4 columns of status
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

$hidegeneratedfilelistifempty = 1;
if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) {
	$hidegeneratedfilelistifempty = 0;
}

// Show list of available documents
$urlsource = $_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
$urlsource .= str_replace('&amp;', '&', $param);

$filedir = $diroutputmassaction;
$genallowed = $permissiontoread;
$delallowed = $permissiontoadd;

print $formfile->showdocuments('massfilesarea_contract', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);


llxFooter();
$db->close();
