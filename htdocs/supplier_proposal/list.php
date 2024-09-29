<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Eric Seigne					<eric.seigne@ryxeo.com>
 * Copyright (C) 2005		Marc Barilley / Ocebo		<marc@ocebo.com>
 * Copyright (C) 2005-2013	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2006		Andre Cianfarani			<acianfa@free.fr>
 * Copyright (C) 2010-2011	Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2010-2019	Philippe Grand				<philippe.grand@atoo-net.com>
 * Copyright (C) 2012		Christophe Battarel			<christophe.battarel@altairis.fr>
 * Copyright (C) 2013		Cédric Salvador				<csalvador@gpcsolutions.fr>
 * Copyright (C) 2016		Ferran Marcet				<fmarcet@2byte.es>
 * Copyright (C) 2018-2023	Charlene Benke				<charlene@patas-monkey.com>
 * Copyright (C) 2021-2024	Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	\file       	htdocs/supplier_proposal/list.php
 *	\ingroup    	supplier_proposal
 *	\brief      	Page of supplier proposals card and list
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formpropal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('companies', 'propal', 'supplier_proposal', 'compta', 'bills', 'orders', 'products'));

$socid = GETPOSTINT('socid');

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOSTINT('show_files');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'supplierproposallist';
$optioncss = GETPOST('optioncss', 'alpha');
$mode = GETPOST('mode', 'alpha');

$search_user = GETPOST('search_user', 'intcomma');
$search_sale = GETPOST('search_sale', 'intcomma');
$search_ref = GETPOST('sf_ref') ? GETPOST('sf_ref', 'alpha') : GETPOST('search_ref', 'alpha');
$search_societe = GETPOST('search_societe', 'alpha');
$search_societe_alias = GETPOST('search_societe_alias', 'alpha');
$search_login = GETPOST('search_login', 'alpha');
$search_town = GETPOST('search_town', 'alpha');
$search_zip = GETPOST('search_zip', 'alpha');
$search_state = GETPOST("search_state");
$search_country = GETPOST("search_country", 'aZ09');
$search_date_startday = GETPOSTINT('search_date_startday');
$search_date_startmonth = GETPOSTINT('search_date_startmonth');
$search_date_startyear = GETPOSTINT('search_date_startyear');
$search_date_endday = GETPOSTINT('search_date_endday');
$search_date_endmonth = GETPOSTINT('search_date_endmonth');
$search_date_endyear = GETPOSTINT('search_date_endyear');
$search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);	// Use tzserver
$search_date_end = dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);
$search_date_valid_startday = GETPOSTINT('search_date_valid_startday');
$search_date_valid_startmonth = GETPOSTINT('search_date_valid_startmonth');
$search_date_valid_startyear = GETPOSTINT('search_date_valid_startyear');
$search_date_valid_endday = GETPOSTINT('search_date_valid_endday');
$search_date_valid_endmonth = GETPOSTINT('search_date_valid_endmonth');
$search_date_valid_endyear = GETPOSTINT('search_date_valid_endyear');
$search_date_valid_start = dol_mktime(0, 0, 0, $search_date_valid_startmonth, $search_date_valid_startday, $search_date_valid_startyear);	// Use tzserver
$search_date_valid_end = dol_mktime(23, 59, 59, $search_date_valid_endmonth, $search_date_valid_endday, $search_date_valid_endyear);
$search_type_thirdparty = GETPOST("search_type_thirdparty", 'intcomma');
$search_montant_ht = GETPOST('search_montant_ht', 'alpha');
$search_montant_vat = GETPOST('search_montant_vat', 'alpha');
$search_montant_ttc = GETPOST('search_montant_ttc', 'alpha');
$search_multicurrency_code = GETPOST('search_multicurrency_code', 'alpha');
$search_multicurrency_tx = GETPOST('search_multicurrency_tx', 'alpha');
$search_multicurrency_montant_ht = GETPOST('search_multicurrency_montant_ht', 'alpha');
$search_multicurrency_montant_vat = GETPOST('search_multicurrency_montant_vat', 'alpha');
$search_multicurrency_montant_ttc = GETPOST('search_multicurrency_montant_ttc', 'alpha');
$search_status = GETPOST('search_status', 'intcomma');
$search_product_category = GETPOST('search_product_category', 'int');
$search_all = trim((GETPOST('search_all', 'alphanohtml') != '') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));

$object_statut = GETPOST('supplier_proposal_statut', 'intcomma');
$search_btn = GETPOST('button_search', 'alpha');
$search_remove_btn = GETPOST('button_removefilter', 'alpha');

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = 'sp.date_livraison';
}
if (!$sortorder) {
	$sortorder = 'DESC';
}

if ($object_statut != '') {
	$search_status = $object_statut;
}

// Nombre de ligne pour choix de produit/service predefinis
$NBLINES = 4;

// Security check
$module = 'supplier_proposal';
$dbtable = '';
$objectid = '';
if (!empty($user->socid)) {
	$socid = $user->socid;
}
if (!empty($socid)) {
	$objectid = $socid;
	$module = 'societe';
	$dbtable = '&societe';
}

$diroutputmassaction = $conf->supplier_proposal->dir_output.'/temp/massgeneration/'.$user->id;

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$object = new SupplierProposal($db);
$hookmanager->initHooks(array('supplier_proposallist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');


// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'sp.ref' => 'Ref',
	's.nom' => 'Supplier',
	'pd.description' => 'Description',
	'sp.note_public' => 'NotePublic',
);
if (empty($user->socid)) {
	$fieldstosearchall["p.note_private"] = "NotePrivate";
}

$checkedtypetiers = 0;
$arrayfields = array(
	'sp.ref' => array('label' => $langs->trans("Ref"), 'checked' => 1),
	's.nom' => array('label' => $langs->trans("Supplier"), 'checked' => 1),
	's.name_alias' => array('label' => "AliasNameShort", 'checked' => 0),
	's.town' => array('label' => $langs->trans("Town"), 'checked' => 1),
	's.zip' => array('label' => $langs->trans("Zip"), 'checked' => 1),
	'state.nom' => array('label' => $langs->trans("StateShort"), 'checked' => 0),
	'country.code_iso' => array('label' => $langs->trans("Country"), 'checked' => 0),
	'typent.code' => array('label' => $langs->trans("ThirdPartyType"), 'checked' => $checkedtypetiers),
	'sp.date_valid' => array('label' => $langs->trans("DateValidation"), 'checked' => 1),
	'sp.date_livraison' => array('label' => $langs->trans("DateEnd"), 'checked' => 1),
	'sp.total_ht' => array('label' => $langs->trans("AmountHT"), 'checked' => 1),
	'sp.total_tva' => array('label' => $langs->trans("AmountVAT"), 'checked' => 0),
	'sp.total_ttc' => array('label' => $langs->trans("AmountTTC"), 'checked' => 0),
	'sp.multicurrency_code' => array('label' => 'Currency', 'checked' => 0, 'enabled' => (!isModEnabled("multicurrency") ? 0 : 1)),
	'sp.multicurrency_tx' => array('label' => 'CurrencyRate', 'checked' => 0, 'enabled' => (!isModEnabled("multicurrency") ? 0 : 1)),
	'sp.multicurrency_total_ht' => array('label' => 'MulticurrencyAmountHT', 'checked' => 0, 'enabled' => (!isModEnabled("multicurrency") ? 0 : 1)),
	'sp.multicurrency_total_vat' => array('label' => 'MulticurrencyAmountVAT', 'checked' => 0, 'enabled' => (!isModEnabled("multicurrency") ? 0 : 1)),
	'sp.multicurrency_total_ttc' => array('label' => 'MulticurrencyAmountTTC', 'checked' => 0, 'enabled' => (!isModEnabled("multicurrency") ? 0 : 1)),
	'u.login' => array('label' => $langs->trans("Author"), 'checked' => 1, 'position' => 10),
	'sp.datec' => array('label' => $langs->trans("DateCreation"), 'checked' => 0, 'position' => 500),
	'sp.tms' => array('label' => $langs->trans("DateModificationShort"), 'checked' => 0, 'position' => 500),
	'sp.fk_statut' => array('label' => $langs->trans("Status"), 'checked' => 1, 'position' => 1000),
);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');
'@phan-var-force array<string,array{label:string,checked?:int<0,1>,position?:int,help?:string}> $arrayfields';  // dol_sort_array looses type for Phan

if (!$user->hasRight('societe', 'client', 'voir')) {
	$search_sale = $user->id;
}

$result = restrictedArea($user, $module, $objectid, $dbtable);

$permissiontoread = $user->hasRight('supplier_proposal', 'lire');
$permissiontodelete = $user->hasRight('supplier_proposal', 'supprimer');


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

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$search_categ = '';
		$search_user = '';
		$search_sale = '';
		$search_ref = '';
		$search_societe = '';
		$search_societe_alias = '';
		$search_montant_ht = '';
		$search_montant_vat = '';
		$search_montant_ttc = '';
		$search_multicurrency_code = '';
		$search_multicurrency_tx = '';
		$search_multicurrency_montant_ht = '';
		$search_multicurrency_montant_vat = '';
		$search_multicurrency_montant_ttc = '';
		$search_login = '';
		$search_product_category = '';
		$search_town = '';
		$search_zip = "";
		$search_state = "";
		$search_type = '';
		$search_country = '';
		$search_type_thirdparty = '';
		$search_date_startday = '';
		$search_date_startmonth = '';
		$search_date_startyear = '';
		$search_date_endday = '';
		$search_date_endmonth = '';
		$search_date_endyear = '';
		$search_date_start = '';
		$search_date_end = '';
		$search_date_valid_startday = '';
		$search_date_valid_startmonth = '';
		$search_date_valid_startyear = '';
		$search_date_valid_endday = '';
		$search_date_valid_endmonth = '';
		$search_date_valid_endyear = '';
		$search_date_valid_start = '';
		$search_date_valid_end = '';
		$search_status = '';
		$object_statut = '';
	}

	$objectclass = 'SupplierProposal';
	$objectlabel = 'SupplierProposals';
	$uploaddir = $conf->supplier_proposal->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$formpropal = new FormPropal($db);
$companystatic = new Societe($db);
$formcompany = new FormCompany($db);

$now = dol_now();

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

$title = $langs->trans('ListOfSupplierProposals');
$help_url = 'EN:Ask_Price_Supplier|FR:Demande_de_prix_fournisseur';


// Build and execute select
// --------------------------------------------------------------------
$sql = 'SELECT';
if ($search_all || $search_user > 0) {
	$sql = 'SELECT DISTINCT';
}
$sql .= ' s.rowid as socid, s.nom as name, s.name_alias as alias, s.town, s.zip, s.fk_pays, s.client, s.code_client,';
$sql .= " typent.code as typent_code,";
$sql .= " state.code_departement as state_code, state.nom as state_name,";
$sql .= ' sp.rowid, sp.note_public, sp.note_private, sp.total_ht, sp.total_tva, sp.total_ttc, sp.localtax1, sp.localtax2, sp.ref, sp.fk_statut as status, sp.fk_user_author, sp.date_valid, sp.date_livraison as dp,';
$sql .= ' sp.fk_multicurrency, sp.multicurrency_code, sp.multicurrency_tx, sp.multicurrency_total_ht, sp.multicurrency_total_tva as multicurrency_total_vat, sp.multicurrency_total_ttc,';
$sql .= ' sp.datec as date_creation, sp.tms as date_modification,';
$sql .= " p.rowid as project_id, p.ref as project_ref,";
$sql .= " u.firstname, u.lastname, u.photo, u.login, u.statut as ustatus, u.admin, u.employee, u.email as uemail";
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
$sql .= ' FROM '.MAIN_DB_PREFIX.'societe as s';
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";
$sql .= ', '.MAIN_DB_PREFIX.'supplier_proposal as sp';
if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (sp.rowid = ef.fk_object)";
}
if ($search_all) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'supplier_proposaldet as pd ON sp.rowid=pd.fk_supplier_proposal';
}
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON sp.fk_user_author = u.rowid';
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = sp.fk_projet";
if ($search_user > 0) {
	$sql .= ", ".MAIN_DB_PREFIX."element_contact as c";
	$sql .= ", ".MAIN_DB_PREFIX."c_type_contact as tc";
}
$sql .= ' WHERE sp.fk_soc = s.rowid';
$sql .= ' AND sp.entity IN ('.getEntity('supplier_proposal').')';
if ($search_town) {
	$sql .= natural_search('s.town', $search_town);
}
if ($search_zip) {
	$sql .= natural_search("s.zip", $search_zip);
}
if ($search_state) {
	$sql .= natural_search("state.nom", $search_state);
}
if ($search_country) {
	$sql .= " AND s.fk_pays IN (".$db->sanitize($search_country).')';
}
if ($search_type_thirdparty != '' && $search_type_thirdparty > 0) {
	$sql .= " AND s.fk_typent IN (".$db->sanitize($search_type_thirdparty).')';
}
if ($search_ref) {
	$sql .= natural_search('sp.ref', $search_ref);
}
if (empty($arrayfields['s.name_alias']['checked']) && $search_societe) {
	$sql .= natural_search(array("s.nom", "s.name_alias"), $search_societe);
} else {
	if ($search_societe) {
		$sql .= natural_search('s.nom', $search_societe);
	}
	if ($search_societe_alias) {
		$sql .= natural_search('s.name_alias', $search_societe_alias);
	}
}
if ($search_login) {
	$sql .= natural_search(array('u.lastname', 'u.firstname', 'u.login'), $search_login);
}
if ($search_montant_ht) {
	$sql .= natural_search('sp.total_ht', $search_montant_ht, 1);
}
if ($search_montant_vat != '') {
	$sql .= natural_search("sp.total_tva", $search_montant_vat, 1);
}
if ($search_montant_ttc != '') {
	$sql .= natural_search("sp.total_ttc", $search_montant_ttc, 1);
}
if ($search_multicurrency_code != '') {
	$sql .= " AND sp.multicurrency_code = '".$db->escape($search_multicurrency_code)."'";
}
if ($search_multicurrency_tx != '') {
	$sql .= natural_search('sp.multicurrency_tx', $search_multicurrency_tx, 1);
}
if ($search_multicurrency_montant_ht != '') {
	$sql .= natural_search('sp.multicurrency_total_ht', $search_multicurrency_montant_ht, 1);
}
if ($search_multicurrency_montant_vat != '') {
	$sql .= natural_search('sp.multicurrency_total_tva', $search_multicurrency_montant_vat, 1);
}
if ($search_multicurrency_montant_ttc != '') {
	$sql .= natural_search('sp.multicurrency_total_ttc', $search_multicurrency_montant_ttc, 1);
}
if ($search_all) {
	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}
if ($socid > 0) {
	$sql .= ' AND s.rowid = '.((int) $socid);
}
if ($search_status >= 0 && $search_status != '') {
	$sql .= ' AND sp.fk_statut IN ('.$db->sanitize($db->escape($search_status)).')';
}
if ($search_date_start) {
	$sql .= " AND sp.date_livraison >= '".$db->idate($search_date_start)."'";
}
if ($search_date_end) {
	$sql .= " AND sp.date_livraison <= '".$db->idate($search_date_end)."'";
}
if ($search_date_valid_start) {
	$sql .= " AND sp.date_valid >= '".$db->idate($search_date_valid_start)."'";
}
if ($search_date_valid_end) {
	$sql .= " AND sp.date_valid <= '".$db->idate($search_date_valid_end)."'";
}
/*
if ($search_sale > 0) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $search_sale);
}*/
if ($search_user > 0) {
	$sql .= " AND c.fk_c_type_contact = tc.rowid AND tc.element='supplier_proposal' AND tc.source='internal' AND c.element_id = sp.rowid AND c.fk_socpeople = ".((int) $search_user);
}
// Search on sale representative
if ($search_sale && $search_sale != '-1') {
	if ($search_sale == -2) {
		$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = sp.fk_soc)";
	} elseif ($search_sale > 0) {
		$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = sp.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
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
			$searchCategoryProductSqlList[] = "NOT EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck, ".MAIN_DB_PREFIX."supplier_proposaldet as sd WHERE sd.fk_supplier_proposal = sp.rowid AND sd.fk_product = ck.fk_product)";
		} elseif (intval($searchCategoryProduct) > 0) {
			if ($searchCategoryProductOperator == 0) {
				$searchCategoryProductSqlList[] = " EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck, ".MAIN_DB_PREFIX."supplier_proposaldet as sd WHERE sd.fk_supplier_proposal = sp.rowid AND sd.fk_product = ck.fk_product AND ck.fk_categorie = ".((int) $searchCategoryProduct).")";
			} else {
				$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategoryProduct);
			}
		}
	}
	if ($listofcategoryid) {
		$searchCategoryProductSqlList[] = " EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck, ".MAIN_DB_PREFIX."supplier_proposaldet as sd WHERE sd.fk_supplier_proposal = sp.rowid AND sd.fk_product = ck.fk_product AND ck.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
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
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= $db->order($sortfield, $sortorder);
$sql .= ', sp.ref DESC';

// Count total nb of records
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

$resql = $db->query($sql);
if ($resql) {
	$objectstatic = new SupplierProposal($db);
	$userstatic = new User($db);

	if ($socid > 0) {
		$soc = new Societe($db);
		$soc->fetch($socid);
		$title = $langs->trans('SupplierProposals').' - '.$soc->name;
	} else {
		$title = $langs->trans('SupplierProposals');
	}

	$num = $db->num_rows($resql);

	$arrayofselected = is_array($toselect) ? $toselect : array();

	if ($num == 1 && getDolGlobalString('MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE') && $search_all) {
		$obj = $db->fetch_object($resql);

		$id = $obj->rowid;

		header("Location: ".DOL_URL_ROOT.'/supplier_proposal/card.php?id='.$id);
		exit;
	}

	// Output page
	// --------------------------------------------------------------------

	llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'bodyforlist mod-supplierproposal page-list');

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
	if ($search_all) {
		$param .= '&search_all='.urlencode($search_all);
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
	if ($search_date_valid_startday) {
		$param .= '&search_date_valid_startday='.urlencode((string) ($search_date_valid_startday));
	}
	if ($search_date_valid_startmonth) {
		$param .= '&search_date_valid_startmonth='.urlencode((string) ($search_date_valid_startmonth));
	}
	if ($search_date_valid_startyear) {
		$param .= '&search_date_valid_startyear='.urlencode((string) ($search_date_valid_startyear));
	}
	if ($search_date_valid_endday) {
		$param .= '&search_date_valid_endday='.urlencode((string) ($search_date_valid_endday));
	}
	if ($search_date_valid_endmonth) {
		$param .= '&search_date_valid_endmonth='.urlencode((string) ($search_date_valid_endmonth));
	}
	if ($search_date_valid_endyear) {
		$param .= '&search_date_valid_endyear='.urlencode((string) ($search_date_valid_endyear));
	}
	if ($search_ref) {
		$param .= '&search_ref='.urlencode($search_ref);
	}
	if ($search_societe) {
		$param .= '&search_societe='.urlencode($search_societe);
	}
	if ($search_societe_alias) {
		$param .= '&search_societe_alias='.urlencode($search_societe_alias);
	}
	if ($search_user > 0) {
		$param .= '&search_user='.urlencode((string) ($search_user));
	}
	if ($search_sale > 0) {
		$param .= '&search_sale='.urlencode($search_sale);
	}
	if ($search_montant_ht) {
		$param .= '&search_montant_ht='.urlencode($search_montant_ht);
	}
	if ($search_multicurrency_code != '') {
		$param .= '&search_multicurrency_code='.urlencode($search_multicurrency_code);
	}
	if ($search_multicurrency_tx != '') {
		$param .= '&search_multicurrency_tx='.urlencode($search_multicurrency_tx);
	}
	if ($search_multicurrency_montant_ht != '') {
		$param .= '&search_multicurrency_montant_ht='.urlencode($search_multicurrency_montant_ht);
	}
	if ($search_multicurrency_montant_vat != '') {
		$param .= '&search_multicurrency_montant_vat='.urlencode($search_multicurrency_montant_vat);
	}
	if ($search_multicurrency_montant_ttc != '') {
		$param .= '&search_multicurrency_montant_ttc='.urlencode($search_multicurrency_montant_ttc);
	}
	if ($search_login) {
		$param .= '&search_login='.urlencode($search_login);
	}
	if ($search_town) {
		$param .= '&search_town='.urlencode($search_town);
	}
	if ($search_zip) {
		$param .= '&search_zip='.urlencode($search_zip);
	}
	if ($socid > 0) {
		$param .= '&socid='.urlencode((string) ($socid));
	}
	if ($search_status != '') {
		$param .= '&search_status='.urlencode($search_status);
	}
	if ($optioncss != '') {
		$param .= '&optioncss='.urlencode($optioncss);
	}
	if ($search_type_thirdparty != '' && $search_type_thirdparty > 0) {
		$param .= '&search_type_thirdparty='.urlencode((string) ($search_type_thirdparty));
	}
	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions = array(
		'generate_doc' => img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
		'builddoc' => img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
		//'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
	);
	if ($user->hasRight('supplier_proposal', 'supprimer')) {
		$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
	}
	if (in_array($massaction, array('presend', 'predelete'))) {
		$arrayofmassactions = array();
	}
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	$url = DOL_URL_ROOT.'/supplier_proposal/card.php?action=create';
	if (!empty($socid)) {
		$url .= '&socid='.$socid;
	}
	$newcardbutton = '';
	$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss' => 'reposition'));
	$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss' => 'reposition'));
	$newcardbutton .= dolGetButtonTitleSeparator();
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewAskPrice'), '', 'fa fa-plus-circle', $url, '', $user->hasRight('supplier_proposal', 'creer'));

	// Fields title search
	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') {
		print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	}
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="mode" value="'.$mode.'">';

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'supplier_proposal', 0, $newcardbutton, '', $limit, 0, 0, 1);

	$topicmail = "SendSupplierProposalRef";
	$modelmail = "supplier_proposal_send";
	$objecttmp = new SupplierProposal($db);
	$trackid = 'spro'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	if ($search_all) {
		foreach ($fieldstosearchall as $key => $val) {
			$fieldstosearchall[$key] = $langs->trans($val);
		}
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).implode(', ', $fieldstosearchall).'</div>';
	}

	$i = 0;

	$moreforfilter = '';

	// If the user can view prospects other than his'
	if ($user->hasRight('user', 'user', 'lire')) {
		$langs->load("commercial");
		$moreforfilter .= '<div class="divsearchfield">';
		$tmptitle = $langs->trans('ThirdPartiesOfSaleRepresentative');
		$moreforfilter .= img_picto($tmptitle, 'user', 'class="pictofixedwidth"').$formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, $tmptitle, 'maxwidth250 widthcentpercentminusx');
		$moreforfilter .= '</div>';
	}
	// If the user can view prospects other than his'
	if ($user->hasRight('user', 'user', 'lire')) {
		$moreforfilter .= '<div class="divsearchfield">';
		$tmptitle = $langs->trans('LinkedToSpecificUsers');
		$moreforfilter .= img_picto($tmptitle, 'user', 'class="pictofixedwidth"').$form->select_dolusers($search_user, 'search_user', $tmptitle, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth250 widthcentpercentminusx');
		$moreforfilter .= '</div>';
	}
	// If the user can view products
	if (isModEnabled('category') && $user->hasRight('categorie', 'lire') && ($user->hasRight('produit', 'lire') || $user->hasRight('service', 'lire'))) {
		include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$moreforfilter .= '<div class="divsearchfield">';
		$tmptitle = $langs->trans('IncludingProductWithTag');
		$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, null, 'parent', null, null, 1);
		$moreforfilter .= img_picto($tmptitle, 'category', 'class="pictofixedwidth"').$form->selectarray('search_product_category', $cate_arbo, $search_product_category, $tmptitle, 0, 0, '', 0, 0, 0, 0, 'maxwidth300 widthcentpercentminusx', 1);
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
	$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

	print '<div class="div-table-responsive">';
	print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	// Fields title search
	// --------------------------------------------------------------------
	print '<tr class="liste_titre_filter">';
	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre maxwidthsearch">';
		$searchpicto = $form->showFilterButtons('left');
		print $searchpicto;
		print '</td>';
	}
	if (!empty($arrayfields['sp.ref']['checked'])) {
		print '<td class="liste_titre">';
		print '<input class="flat" size="6" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
		print '</td>';
	}
	if (!empty($arrayfields['s.nom']['checked'])) {
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" size="12" name="search_societe" value="'.dol_escape_htmltag($search_societe).'">';
		print '</td>';
	}
	if (!empty($arrayfields['s.name_alias']['checked'])) {
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" size="12" name="search_societe_alias" value="'.dol_escape_htmltag($search_societe_alias).'">';
		print '</td>';
	}
	if (!empty($arrayfields['s.town']['checked'])) {
		print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_town" value="'.$search_town.'"></td>';
	}
	if (!empty($arrayfields['s.zip']['checked'])) {
		print '<td class="liste_titre"><input class="flat" type="text" size="4" name="search_zip" value="'.$search_zip.'"></td>';
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
	// Date
	if (!empty($arrayfields['sp.date_valid']['checked'])) {
		print '<td class="liste_titre center">';
		print '<div class="nowrapfordate">';
		print $form->selectDate($search_date_valid_start ? $search_date_valid_start : -1, 'search_date_valid_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
		print '</div>';
		print '<div class="nowrapfordate">';
		print $form->selectDate($search_date_valid_end ? $search_date_valid_end : -1, 'search_date_valid_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
		print '</div>';
		print '</td>';
	}
	// Date
	if (!empty($arrayfields['sp.date_livraison']['checked'])) {
		print '<td class="liste_titre center">';
		print '<div class="nowrapfordate">';
		print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
		print '</div>';
		print '<div class="nowrapfordate">';
		print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
		print '</div>';
		print '</td>';
	}

	if (!empty($arrayfields['sp.total_ht']['checked'])) {
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="5" name="search_montant_ht" value="'.dol_escape_htmltag($search_montant_ht).'">';
		print '</td>';
	}
	if (!empty($arrayfields['sp.total_tva']['checked'])) {
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="5" name="search_montant_vat" value="'.dol_escape_htmltag($search_montant_vat).'">';
		print '</td>';
	}
	if (!empty($arrayfields['sp.total_ttc']['checked'])) {
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="5" name="search_montant_ttc" value="'.dol_escape_htmltag($search_montant_ttc).'">';
		print '</td>';
	}
	if (!empty($arrayfields['sp.multicurrency_code']['checked'])) {
		// Currency
		print '<td class="liste_titre">';
		print $form->selectMultiCurrency($search_multicurrency_code, 'search_multicurrency_code', 1);
		print '</td>';
	}
	if (!empty($arrayfields['sp.multicurrency_tx']['checked'])) {
		// Currency rate
		print '<td class="liste_titre">';
		print '<input class="flat" type="text" size="4" name="search_multicurrency_tx" value="'.dol_escape_htmltag($search_multicurrency_tx).'">';
		print '</td>';
	}
	if (!empty($arrayfields['sp.multicurrency_total_ht']['checked'])) {
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_multicurrency_montant_ht" value="'.dol_escape_htmltag($search_multicurrency_montant_ht).'">';
		print '</td>';
	}
	if (!empty($arrayfields['sp.multicurrency_total_vat']['checked'])) {
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_multicurrency_montant_vat" value="'.dol_escape_htmltag($search_multicurrency_montant_vat).'">';
		print '</td>';
	}
	if (!empty($arrayfields['sp.multicurrency_total_ttc']['checked'])) {
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_multicurrency_montant_ttc" value="'.dol_escape_htmltag($search_multicurrency_montant_ttc).'">';
		print '</td>';
	}
	if (!empty($arrayfields['u.login']['checked'])) {
		// Author
		print '<td class="liste_titre center">';
		print '<input class="flat" size="4" type="text" name="search_login" value="'.dol_escape_htmltag($search_login).'">';
		print '</td>';
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
	// Fields from hook
	$parameters = array('arrayfields' => $arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (!empty($arrayfields['sp.datec']['checked'])) {
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Date modification
	if (!empty($arrayfields['sp.tms']['checked'])) {
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Status
	if (!empty($arrayfields['sp.fk_statut']['checked'])) {
		print '<td class="liste_titre center parentonrightofpage">';
		$formpropal->selectProposalStatus($search_status, 1, 0, 1, 'supplier', 'search_status', 'search_status width100 onrightofpage');
		print '</td>';
	}
	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre maxwidthsearch">';
		$searchpicto = $form->showFilterButtons();
		print $searchpicto;
		print '</td>';
	}

	print "</tr>\n";

	$totalarray = array();
	$totalarray['nbfield'] = 0;

	// Fields title
	print '<tr class="liste_titre">';
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['sp.ref']['checked'])) {
		print_liste_field_titre($arrayfields['sp.ref']['label'], $_SERVER["PHP_SELF"], 'sp.ref', '', $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['s.nom']['checked'])) {
		print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], 's.nom', '', $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['s.name_alias']['checked'])) {
		print_liste_field_titre($arrayfields['s.name_alias']['label'], $_SERVER["PHP_SELF"], 's.name_alias', '', $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['s.town']['checked'])) {
		print_liste_field_titre($arrayfields['s.town']['label'], $_SERVER["PHP_SELF"], 's.town', '', $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['s.zip']['checked'])) {
		print_liste_field_titre($arrayfields['s.zip']['label'], $_SERVER["PHP_SELF"], 's.zip', '', $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['state.nom']['checked'])) {
		print_liste_field_titre($arrayfields['state.nom']['label'], $_SERVER["PHP_SELF"], "state.nom", "", $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['country.code_iso']['checked'])) {
		print_liste_field_titre($arrayfields['country.code_iso']['label'], $_SERVER["PHP_SELF"], "country.code_iso", "", $param, '', $sortfield, $sortorder, 'center ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['typent.code']['checked'])) {
		print_liste_field_titre($arrayfields['typent.code']['label'], $_SERVER["PHP_SELF"], "typent.code", "", $param, '', $sortfield, $sortorder, 'center ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['sp.date_valid']['checked'])) {
		print_liste_field_titre($arrayfields['sp.date_valid']['label'], $_SERVER["PHP_SELF"], 'sp.date_valid', '', $param, '', $sortfield, $sortorder, 'center ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['sp.date_livraison']['checked'])) {
		print_liste_field_titre($arrayfields['sp.date_livraison']['label'], $_SERVER["PHP_SELF"], 'sp.date_livraison', '', $param, '', $sortfield, $sortorder, 'center ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['sp.total_ht']['checked'])) {
		print_liste_field_titre($arrayfields['sp.total_ht']['label'], $_SERVER["PHP_SELF"], 'sp.total_ht', '', $param, '', $sortfield, $sortorder, 'right ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['sp.total_tva']['checked'])) {
		print_liste_field_titre($arrayfields['sp.total_tva']['label'], $_SERVER["PHP_SELF"], 'sp.total_tva', '', $param, '', $sortfield, $sortorder, 'right ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['sp.total_ttc']['checked'])) {
		print_liste_field_titre($arrayfields['sp.total_ttc']['label'], $_SERVER["PHP_SELF"], 'sp.total_ttc', '', $param, '', $sortfield, $sortorder, 'right ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['sp.multicurrency_code']['checked'])) {
		print_liste_field_titre($arrayfields['sp.multicurrency_code']['label'], $_SERVER['PHP_SELF'], 'sp.multicurrency_code', '', $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['sp.multicurrency_tx']['checked'])) {
		print_liste_field_titre($arrayfields['sp.multicurrency_tx']['label'], $_SERVER['PHP_SELF'], 'sp.multicurrency_tx', '', $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['sp.multicurrency_total_ht']['checked'])) {
		print_liste_field_titre($arrayfields['sp.multicurrency_total_ht']['label'], $_SERVER['PHP_SELF'], 'sp.multicurrency_total_ht', '', $param, 'class="right"', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['sp.multicurrency_total_vat']['checked'])) {
		print_liste_field_titre($arrayfields['sp.multicurrency_total_vat']['label'], $_SERVER['PHP_SELF'], 'sp.multicurrency_total_tva', '', $param, 'class="right"', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['sp.multicurrency_total_ttc']['checked'])) {
		print_liste_field_titre($arrayfields['sp.multicurrency_total_ttc']['label'], $_SERVER['PHP_SELF'], 'sp.multicurrency_total_ttc', '', $param, 'class="right"', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['u.login']['checked'])) {
		print_liste_field_titre($arrayfields['u.login']['label'], $_SERVER["PHP_SELF"], 'u.login', '', $param, '', $sortfield, $sortorder, 'center ');
		$totalarray['nbfield']++;
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (!empty($arrayfields['sp.datec']['checked'])) {
		print_liste_field_titre($arrayfields['sp.datec']['label'], $_SERVER["PHP_SELF"], "sp.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['sp.tms']['checked'])) {
		print_liste_field_titre($arrayfields['sp.tms']['label'], $_SERVER["PHP_SELF"], "sp.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['sp.fk_statut']['checked'])) {
		print_liste_field_titre($arrayfields['sp.fk_statut']['label'], $_SERVER["PHP_SELF"], "sp.fk_statut", "", $param, '', $sortfield, $sortorder, 'center ');
		$totalarray['nbfield']++;
	}
	// Action
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
		$totalarray['nbfield']++;
	}
	print '</tr>'."\n";

	$now = dol_now();
	$i = 0;
	$total = 0;
	$subtotal = 0;
	$savnbfield = $totalarray['nbfield'];
	$totalarray = array();
	$totalarray['nbfield'] = 0;
	$totalarray['val'] = array();
	$totalarray['val']['sp.total_ht'] = 0;
	$totalarray['val']['sp.total_tva'] = 0;
	$totalarray['val']['sp.total_ttc'] = 0;

	$imaxinloop = ($limit ? min($num, $limit) : $num);
	while ($i < $imaxinloop) {
		$obj = $db->fetch_object($resql);

		$objectstatic->id = $obj->rowid;
		$objectstatic->ref = $obj->ref;
		$objectstatic->note_public = $obj->note_public;
		$objectstatic->note_private = $obj->note_private;
		$objectstatic->status = $obj->status;

		// Company
		$companystatic->id = $obj->socid;
		$companystatic->name = $obj->name;
		$companystatic->name_alias = $obj->alias;
		$companystatic->client = $obj->client;
		$companystatic->code_client = $obj->code_client;

		if ($mode == 'kanban') {
			if ($i == 0) {
				print '<tr class="trkanban"><td colspan="'.$savnbfield.'">';
				print '<div class="box-flex-container kanban">';
			}
			// Output Kanban
			// TODO Use a cache on user
			$userstatic->fetch($obj->fk_user_author);
			$objectstatic->delivery_date = $obj->dp;
			print $objectstatic->getKanbanView('', array('thirdparty' => $companystatic, 'userauthor' => $userstatic, 'selected' => in_array($obj->rowid, $arrayofselected)));
			if ($i == ($imaxinloop - 1)) {
				print '</div>';
				print '</td></tr>';
			}
		} else {
			print '<tr class="oddeven '.((getDolGlobalInt('MAIN_FINISHED_LINES_OPACITY') == 1 && $obj->status > 1) ? 'opacitymedium' : '').'">';
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
			}
			if (!empty($arrayfields['sp.ref']['checked'])) {
				print '<td class="nowraponall">';

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				// Picto + Ref
				print '<td class="nobordernopadding nowraponall">';
				print $objectstatic->getNomUrl(1, '', '', 0, -1, 1);
				print '</td>';
				// Warning
				$warnornote = '';
				//if ($obj->fk_statut == 1 && $db->jdate($obj->date_valid) < ($now - $conf->supplier_proposal->warning_delay)) $warnornote .= img_warning($langs->trans("Late"));
				if ($warnornote) {
					print '<td style="min-width: 20px" class="nobordernopadding nowrap">';
					print $warnornote;
					print '</td>';
				}
				// Other picto tool
				print '<td width="16" class="right nobordernopadding hideonsmartphone">';
				$filename = dol_sanitizeFileName($obj->ref);
				$filedir = $conf->supplier_proposal->dir_output.'/'.dol_sanitizeFileName($obj->ref);
				$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;
				print $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
				print '</td></tr></table>';

				print "</td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Thirdparty
			if (!empty($arrayfields['s.nom']['checked'])) {
				print '<td class="tdoverflowmax200">';
				print $companystatic->getNomUrl(1, 'supplier', 0, 0, -1, empty($arrayfields['s.name_alias']['checked']) ? 0 : 1);
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Alias
			if (!empty($arrayfields['s.name_alias']['checked'])) {
				print '<td class="tdoverflowmax200">';
				print $companystatic->name_alias;
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
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
				print '<td class="nocellnopadd">';
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
				print '<td class="center">';
				$tmparray = getCountry($obj->fk_pays, 'all');
				print $tmparray['label'];
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Type ent
			if (!empty($arrayfields['typent.code']['checked'])) {
				print '<td class="center">';
				if (empty($typenArray) || !is_array($typenArray) || count($typenArray) == 0) {
					$typenArray = $formcompany->typent_array(1);
				}
				print $typenArray[$obj->typent_code];
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Date proposal
			if (!empty($arrayfields['sp.date_valid']['checked'])) {
				print '<td class="center">';
				print dol_print_date($db->jdate($obj->date_valid), 'day');
				print "</td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Date delivery
			if (!empty($arrayfields['sp.date_livraison']['checked'])) {
				print '<td class="center">';
				print dol_print_date($db->jdate($obj->dp), 'day');
				print "</td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Amount HT
			if (!empty($arrayfields['sp.total_ht']['checked'])) {
				print '<td class="right"><span class="amount">'.price($obj->total_ht)."</span></td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = 'sp.total_ht';
				}
				$totalarray['val']['sp.total_ht'] += $obj->total_ht;
			}
			// Amount VAT
			if (!empty($arrayfields['sp.total_tva']['checked'])) {
				print '<td class="right"><span class="amount">'.price($obj->total_tva)."</span></td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = 'sp.total_tva';
				}
				$totalarray['val']['sp.total_tva'] += $obj->total_tva;
			}
			// Amount TTC
			if (!empty($arrayfields['sp.total_ttc']['checked'])) {
				print '<td class="right"><span class="amount">'.price($obj->total_ttc)."</span></td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = 'sp.total_ttc';
				}
				$totalarray['val']['sp.total_ttc'] += $obj->total_ttc;
			}

			// Currency
			if (!empty($arrayfields['sp.multicurrency_code']['checked'])) {
				print '<td class="nowrap">'.$obj->multicurrency_code.' - '.$langs->trans('Currency'.$obj->multicurrency_code)."</td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Currency rate
			if (!empty($arrayfields['sp.multicurrency_tx']['checked'])) {
				print '<td class="nowrap">';
				$form->form_multicurrency_rate($_SERVER['PHP_SELF'].'?id='.$obj->rowid, $obj->multicurrency_tx, 'none', $obj->multicurrency_code);
				print "</td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Amount HT
			if (!empty($arrayfields['sp.multicurrency_total_ht']['checked'])) {
				print '<td class="right nowrap"><span class="amount">'.price($obj->multicurrency_total_ht)."</span></td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Amount VAT
			if (!empty($arrayfields['sp.multicurrency_total_vat']['checked'])) {
				print '<td class="right nowrap"><span class="amount">'.price($obj->multicurrency_total_vat)."</span></td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Amount TTC
			if (!empty($arrayfields['sp.multicurrency_total_ttc']['checked'])) {
				print '<td class="right nowrap"><span class="amount">'.price($obj->multicurrency_total_ttc)."</span></td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			$userstatic->id = $obj->fk_user_author;
			$userstatic->login = $obj->login;
			$userstatic->status = $obj->ustatus;
			$userstatic->lastname = $obj->name;
			$userstatic->firstname = $obj->firstname;
			$userstatic->photo = $obj->photo;
			$userstatic->admin = $obj->admin;
			$userstatic->ref = $obj->fk_user_author;
			$userstatic->employee = $obj->employee;
			$userstatic->email = $obj->uemail;

			// Author
			if (!empty($arrayfields['u.login']['checked'])) {
				print '<td class="tdoverflowmax125">';
				if ($userstatic->id > 0) {
					print $userstatic->getNomUrl(-1, '', 0, 0, 24, 1, 'login', '', 1);
				}
				print "</td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Extra fields
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
			// Fields from hook
			$parameters = array('arrayfields' => $arrayfields, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
			$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;
			// Date creation
			if (!empty($arrayfields['sp.datec']['checked'])) {
				print '<td class="center nowraponall">';
				print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Date modification
			if (!empty($arrayfields['sp.tms']['checked'])) {
				print '<td class="center nowraponall">';
				print dol_print_date($db->jdate($obj->date_modification), 'dayhour', 'tzuser');
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Status
			if (!empty($arrayfields['sp.fk_statut']['checked'])) {
				print '<td class="center">'.$objectstatic->getLibStatut(5)."</td>\n";
				if (!$i) {
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
			}
			if (!$i) {
				$totalarray['nbfield']++;
			}

			print "</tr>\n";

			$total += $obj->total_ht;
			$subtotal += $obj->total_ht;
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
	$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters); // Note that $action and $object may have been modified by hook
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

	$genallowed = $user->hasRight('supplier_proposal', 'lire');
	$delallowed = $user->hasRight('supplier_proposal', 'creer');

	print $formfile->showdocuments('massfilesarea_supplier_proposal', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
