<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2016-2021 Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2019      Nicolas ZABOURI      <info@inovea-conseil.com>
 * Copyright (C) 2020      Thibault FOUCART     <support@ptibogxiv.net>
 * Copyright (C) 2023      Christophe Battarel	<christophe@altairis.fr>
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
 *      \file       htdocs/expedition/list.php
 *      \ingroup    expedition
 *      \brief      Page to list all shipments
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("sendings", "deliveries", 'companies', 'bills', 'products'));

$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'shipmentlist'; // To manage different context of search

$socid = GETPOST('socid', 'int');

$action     = GETPOST('action', 'alpha');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$toselect   = GETPOST('toselect', 'array');
$optioncss = GETPOST('optioncss', 'alpha');
$mode = GETPOST('mode', 'alpha');

$search_ref_exp = GETPOST("search_ref_exp", 'alpha');
$search_ref_liv = GETPOST('search_ref_liv', 'alpha');
$search_ref_customer = GETPOST('search_ref_customer', 'alpha');
$search_company = GETPOST("search_company", 'alpha');
$search_shipping_method_id = GETPOST('search_shipping_method_id');
$search_tracking = GETPOST("search_tracking", 'alpha');
$search_town = GETPOST('search_town', 'alpha');
$search_zip = GETPOST('search_zip', 'alpha');
$search_state = GETPOST("search_state");
$search_country = GETPOST("search_country", 'int');
$search_type_thirdparty = GETPOST("search_type_thirdparty", 'int');
$search_billed = GETPOST("search_billed", 'int');
$search_datedelivery_start = dol_mktime(0, 0, 0, GETPOST('search_datedelivery_startmonth', 'int'), GETPOST('search_datedelivery_startday', 'int'), GETPOST('search_datedelivery_startyear', 'int'));
$search_datedelivery_end = dol_mktime(23, 59, 59, GETPOST('search_datedelivery_endmonth', 'int'), GETPOST('search_datedelivery_endday', 'int'), GETPOST('search_datedelivery_endyear', 'int'));
$search_datereceipt_start = dol_mktime(0, 0, 0, GETPOST('search_datereceipt_startmonth', 'int'), GETPOST('search_datereceipt_startday', 'int'), GETPOST('search_datereceipt_startyear', 'int'));
$search_datereceipt_end = dol_mktime(23, 59, 59, GETPOST('search_datereceipt_endmonth', 'int'), GETPOST('search_datereceipt_endday', 'int'), GETPOST('search_datereceipt_endyear', 'int'));
$search_all = trim((GETPOST('search_all', 'alphanohtml') != '') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$search_user = GETPOST('search_user', 'int');
$search_sale = GETPOST('search_sale', 'int');
$search_categ_cus = GETPOST("search_categ_cus", 'int');
$search_product_category = GETPOST('search_product_category', 'int');

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (!$sortfield) {
	$sortfield = "e.ref";
}
if (!$sortorder) {
	$sortorder = "DESC";
}
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$search_status = GETPOST('search_status', 'intcomma');

$diroutputmassaction = $conf->expedition->dir_output.'/sending/temp/massgeneration/'.$user->id;

$object = new Expedition($db);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('shipmentlist'));
$extrafields = new ExtraFields($db);

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'e.ref'=>"Ref",
	's.nom'=>"ThirdParty",
	'e.note_public'=>'NotePublic',
	//'e.fk_shipping_method'=>'SendingMethod', // TODO fix this, does not work
	'e.tracking_number'=>"TrackingNumber",
);
if (empty($user->socid)) {
	$fieldstosearchall["e.note_private"] = "NotePrivate";
}

$checkedtypetiers = 0;
$arrayfields = array(
	'e.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1, 'position'=>1),
	'e.ref_customer'=>array('label'=>$langs->trans("RefCustomer"), 'checked'=>1, 'position'=>2),
	's.nom'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>1, 'position'=>3),
	's.town'=>array('label'=>$langs->trans("Town"), 'checked'=>1, 'position'=>4),
	's.zip'=>array('label'=>$langs->trans("Zip"), 'checked'=>1, 'position'=>5),
	'state.nom'=>array('label'=>$langs->trans("StateShort"), 'checked'=>0, 'position'=>6),
	'country.code_iso'=>array('label'=>$langs->trans("Country"), 'checked'=>0, 'position'=>7),
	'typent.code'=>array('label'=>$langs->trans("ThirdPartyType"), 'checked'=>$checkedtypetiers, 'position'=>8),
	'e.date_delivery'=>array('label'=>$langs->trans("DateDeliveryPlanned"), 'checked'=>1, 'position'=>9),
	'e.fk_shipping_method'=>array('label'=>$langs->trans('SendingMethod'), 'checked'=>1, 'position'=>10),
	'e.tracking_number'=>array('label'=>$langs->trans("TrackingNumber"), 'checked'=>1, 'position'=>11),
	'e.weight'=>array('label'=>$langs->trans("Weight"), 'checked'=>0, 'position'=>12),
	'e.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
	'e.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
	'e.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
	'l.ref'=>array('label'=>$langs->trans("DeliveryRef"), 'checked'=>1, 'position'=>1010, 'enabled'=>(getDolGlobalInt('MAIN_SUBMODULE_DELIVERY') ? 1 : 0)),
	'l.date_delivery'=>array('label'=>$langs->trans("DateReceived"), 'position'=>1020, 'checked'=>1, 'enabled'=>(getDolGlobalInt('MAIN_SUBMODULE_DELIVERY') ? 1 : 0)),
	'e.billed'=>array('label'=>$langs->trans("Billed"), 'checked'=>1, 'position'=>1100, 'enabled'=>'getDolGlobalString("WORKFLOW_BILL_ON_SHIPMENT") !== "0"')
);

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

// Security check
$expeditionid = GETPOST('id', 'int');
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'expedition', $expeditionid, '');



/*
 * Actions
 */
$error = 0;

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

$parameters = array('socid'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$search_user = '';
	$search_sale = '';
	$search_product_category = '';
	$search_ref_exp = '';
	$search_ref_liv = '';
	$search_ref_customer = '';
	$search_company = '';
	$search_town = '';
	$search_zip = "";
	$search_state = "";
	$search_type = '';
	$search_country = '';
	$search_tracking = '';
	$search_shipping_method_id = '';
	$search_type_thirdparty = '';
	$search_billed = '';
	$search_datedelivery_start = '';
	$search_datedelivery_end = '';
	$search_datereceipt_start = '';
	$search_datereceipt_end = '';
	$search_status = '';
	$toselect = array();
	$search_array_options = array();
	$search_categ_cus = 0;
}

if (empty($reshook)) {
	$objectclass  = 'Expedition';
	$objectlabel  = 'Sendings';
	$permissiontoread   = $user->rights->expedition->lire;
	$permissiontoadd = $user->rights->expedition->creer;
	$permissiontodelete = $user->rights->expedition->supprimer;
	$uploaddir = $conf->expedition->dir_output.'/sending';
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

	// If massaction is close
	if ($massaction == 'classifyclose') {
		$error=0;
		$selectids = GETPOST('toselect', 'array');
		foreach ($selectids as $selectid) {
			//	$object->fetch($selectid);
			$object->fetch($selectid);
			$result = $object->setClosed();
		}

		$massaction = $action = 'classifyclose';

		if ($result < 0) {
			$error++;
		}


		if (!$error) {
			$db->commit();

			setEventMessage($langs->trans("Close Done"));
			header('Location: '.$_SERVER["PHP_SELF"]);
			exit;
		} else {
			$db->rollback();
			exit;
		}
	}
}

/*
 * View
 */

$now = dol_now();

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$companystatic = new Societe($db);
$formcompany = new FormCompany($db);
$shipment = new Expedition($db);

$helpurl = 'EN:Module_Shipments|FR:Module_Exp&eacute;ditions|ES:M&oacute;dulo_Expediciones';
llxHeader('', $langs->trans('ListOfSendings'), $helpurl);

$sql = 'SELECT';
if ($search_all || $search_user > 0) {
	$sql = 'SELECT DISTINCT';
}
$sql .= " e.rowid, e.ref, e.ref_customer, e.date_expedition as date_expedition, e.weight, e.weight_units, e.date_delivery as delivery_date, e.fk_statut, e.billed, e.tracking_number, e.fk_shipping_method,";
if (getDolGlobalInt('MAIN_SUBMODULE_DELIVERY')) {
	// Link for delivery fields ref and date. Does not duplicate the line because we should always have ony 1 link or 0 per shipment
	$sql .= " l.date_delivery as date_reception,";
}
$sql .= " s.rowid as socid, s.nom as name, s.town, s.zip, s.fk_pays, s.client, s.code_client, ";
$sql .= " typent.code as typent_code,";
$sql .= " state.code_departement as state_code, state.nom as state_name,";
$sql .= " e.date_creation as date_creation, e.tms as date_update,";
$sql .= " u.login";
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

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= " FROM ".MAIN_DB_PREFIX."expedition as e";
if (!empty($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (e.rowid = ef.fk_object)";
}
if ($search_all) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'expeditiondet as ed ON e.rowid=ed.fk_expedition';
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'commandedet as pd ON pd.rowid=ed.fk_origin_line';
}
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = e.fk_soc";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";
if (getDolGlobalInt('MAIN_SUBMODULE_DELIVERY')) {
	// Link for delivery fields ref and date. Does not duplicate the line because we should always have ony 1 link or 0 per shipment
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as ee ON e.rowid = ee.fk_source AND ee.sourcetype = 'shipping' AND ee.targettype = 'delivery'";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."delivery as l ON l.rowid = ee.fk_target";
}
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON e.fk_user_author = u.rowid';
if ($search_user > 0) {		// Get link to order to get the order id in eesource.fk_source
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as eesource ON eesource.fk_target = e.rowid AND eesource.targettype = 'shipping' AND eesource.sourcetype = 'commande'";
}
if ($search_user > 0) {
	$sql .= ", ".MAIN_DB_PREFIX."element_contact as ec";
	$sql .= ", ".MAIN_DB_PREFIX."c_type_contact as tc";
}

// Add table from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= " WHERE e.entity IN (".getEntity('expedition').")";

if ($socid > 0) {
	$sql .= " AND s.rowid = ".((int) $socid);
}
if ($socid) {
	$sql .= " AND e.fk_soc = ".((int) $socid);
}
if ($search_status != '' && $search_status >= 0) {
	$sql .= " AND e.fk_statut = ".((int) $search_status);
}
if ($search_ref_customer != '') {
	$sql .= natural_search('e.ref_customer', $search_ref_customer);
}
if ($search_billed != '' && $search_billed >= 0) {
	$sql .= ' AND e.billed = '.((int) $search_billed);
}
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
if ($search_shipping_method_id > 0) {
	$sql .= " AND e.fk_shipping_method = ".((int) $search_shipping_method_id);
}
if ($search_tracking) {
	$sql .= natural_search("e.tracking_number", $search_tracking);
}
if ($search_type_thirdparty != '' && $search_type_thirdparty > 0) {
	$sql .= " AND s.fk_typent IN (".$db->sanitize($search_type_thirdparty).')';
}
if ($search_user > 0) {
	// The contact on a shipment is also the contact of the order.
	$sql .= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='commande' AND tc.source='internal' AND ec.element_id = eesource.fk_source AND ec.fk_socpeople = ".((int) $search_user);
}
if ($search_company) {
	$sql .= natural_search('s.nom', $search_company);
}
if ($search_ref_exp) {
	$sql .= natural_search('e.ref', $search_ref_exp);
}
if ($search_datedelivery_start) {
	$sql .= " AND e.date_delivery >= '".$db->idate($search_datedelivery_start)."'";
}
if ($search_datedelivery_end) {
	$sql .= " AND e.date_delivery <= '".$db->idate($search_datedelivery_end)."'";
}
if (getDolGlobalInt('MAIN_SUBMODULE_DELIVERY')) {
	if ($search_ref_liv) {
		$sql .= natural_search('l.ref', $search_ref_liv);
	}
	if ($search_datereceipt_start) {
		$sql .= " AND l.date_delivery >= '".$db->idate($search_datereceipt_start)."'";
	}
	if ($search_datereceipt_end) {
		$sql .= " AND l.date_delivery <= '".$db->idate($search_datereceipt_end)."'";
	}
}
if ($search_all) {
	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}
// Search on sale representative
if ($search_sale && $search_sale != '-1') {
	if ($search_sale == -2) {
		$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = e.fk_soc)";
	} elseif ($search_sale > 0) {
		$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = e.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
	}
}
// Search for tag/category ($searchCategoryCustomerList is an array of ID)
$searchCategoryCustomerOperator = -1;
$searchCategoryCustomerList = array($search_categ_cus);
if (!empty($searchCategoryCustomerList)) {
	$searchCategoryCustomerSqlList = array();
	$listofcategoryid = '';
	foreach ($searchCategoryCustomerList as $searchCategoryCustomer) {
		if (intval($searchCategoryCustomer) == -2) {
			$searchCategoryCustomerSqlList[] = "NOT EXISTS (SELECT cs.fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe as cs WHERE s.rowid = cs.fk_soc)";
		} elseif (intval($searchCategoryCustomer) > 0) {
			if ($searchCategoryCustomerOperator == 0) {
				$searchCategoryCustomerSqlList[] = " EXISTS (SELECT cs.fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe as cs WHERE s.rowid = cs.fk_soc AND cs.fk_categorie = ".((int) $searchCategoryCustomer).")";
			} else {
				$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategoryCustomer);
			}
		}
	}
	if ($listofcategoryid) {
		$searchCategoryCustomerSqlList[] = " EXISTS (SELECT cs.fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe as cs WHERE s.rowid = cs.fk_soc AND cs.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
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
// Search for tag/category ($searchCategoryProductList is an array of ID)
$searchCategoryProductOperator = -1;
$searchCategoryProductList = array($search_product_category);
if (!empty($searchCategoryProductList)) {
	$searchCategoryProductSqlList = array();
	$listofcategoryid = '';
	foreach ($searchCategoryProductList as $searchCategoryProduct) {
		if (intval($searchCategoryProduct) == -2) {
			$searchCategoryProductSqlList[] = "NOT EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck, ".MAIN_DB_PREFIX."expeditiondet as ed, ".MAIN_DB_PREFIX."commandedet as cd WHERE ed.fk_expedition = e.rowid AND ed.fk_origin_line = cd.rowid AND cd.fk_product = ck.fk_product)";
		} elseif (intval($searchCategoryProduct) > 0) {
			if ($searchCategoryProductOperator == 0) {
				$searchCategoryProductSqlList[] = " EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck, ".MAIN_DB_PREFIX."expeditiondet as ed, ".MAIN_DB_PREFIX."commandedet as cd WHERE ed.fk_expedition = e.rowid AND ed.fk_origin_line = cd.rowid AND cd.fk_product = ck.fk_product AND ck.fk_categorie = ".((int) $searchCategoryProduct).")";
			} else {
				$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategoryProduct);
			}
		}
	}
	if ($listofcategoryid) {
		$searchCategoryProductSqlList[] = " EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck, ".MAIN_DB_PREFIX."expeditiondet as ed, ".MAIN_DB_PREFIX."commandedet as cd WHERE ed.fk_expedition = e.rowid AND ed.fk_origin_line = cd.rowid AND cd.fk_product = ck.fk_product AND ck.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
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

// Add HAVING from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListHaving', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= empty($hookmanager->resPrint) ? "" : " HAVING 1=1 ".$hookmanager->resPrint;

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

//print $sql;
$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);

$arrayofselected = is_array($toselect) ? $toselect : array();

$expedition = new Expedition($db);

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
	$param .= "&search_all=".urlencode($search_all);
}
if ($search_ref_exp) {
	$param .= "&search_ref_exp=".urlencode($search_ref_exp);
}
if ($search_ref_liv) {
	$param .= "&search_ref_liv=".urlencode($search_ref_liv);
}
if ($search_ref_customer) {
	$param .= "&search_ref_customer=".urlencode($search_ref_customer);
}
if ($search_user > 0) {
	$param .= '&search_user='.urlencode($search_user);
}
if ($search_sale > 0) {
	$param .= '&search_sale='.urlencode($search_sale);
}
if ($search_company) {
	$param .= "&search_company=".urlencode($search_company);
}
if ($search_shipping_method_id) {
	$param .= "&search_shipping_method_id=".urlencode($search_shipping_method_id);
}
if ($search_tracking) {
	$param .= "&search_tracking=".urlencode($search_tracking);
}
if ($search_town) {
	$param .= '&search_town='.urlencode($search_town);
}
if ($search_zip) {
	$param .= '&search_zip='.urlencode($search_zip);
}
if ($search_type_thirdparty != '' && $search_type_thirdparty > 0) {
	$param .= '&search_type_thirdparty='.urlencode($search_type_thirdparty);
}
if ($search_datedelivery_start) {
	$param .= '&search_datedelivery_startday='.urlencode(dol_print_date($search_datedelivery_start, '%d')).'&search_datedelivery_startmonth='.urlencode(dol_print_date($search_datedelivery_start, '%m')).'&search_datedelivery_startyear='.urlencode(dol_print_date($search_datedelivery_start, '%Y'));
}
if ($search_datedelivery_end) {
	$param .= '&search_datedelivery_endday='.urlencode(dol_print_date($search_datedelivery_end, '%d')).'&search_datedelivery_endmonth='.urlencode(dol_print_date($search_datedelivery_end, '%m')).'&search_datedelivery_endyear='.urlencode(dol_print_date($search_datedelivery_end, '%Y'));
}
if ($search_datereceipt_start) {
	$param .= '&search_datereceipt_startday='.urlencode(dol_print_date($search_datereceipt_start, '%d')).'&search_datereceipt_startmonth='.urlencode(dol_print_date($search_datereceipt_start, '%m')).'&search_datereceipt_startyear='.urlencode(dol_print_date($search_datereceipt_start, '%Y'));
}
if ($search_datereceipt_end) {
	$param .= '&search_datereceipt_endday='.urlencode(dol_print_date($search_datereceipt_end, '%d')).'&search_datereceipt_endmonth='.urlencode(dol_print_date($search_datereceipt_end, '%m')).'&search_datereceipt_endyear='.urlencode(dol_print_date($search_datereceipt_end, '%Y'));
}
if ($search_product_category != '') {
	$param .= '&search_product_category='.urlencode($search_product_category);
}
if (($search_categ_cus > 0) || ($search_categ_cus == -2)) {
	$param .= '&search_categ_cus='.urlencode($search_categ_cus);
}
if ($search_status != '') {
	$param .= '&search_status='.urlencode($search_status);
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// Add $param from hooks
$parameters = array('param' => &$param);
$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;

$arrayofmassactions = array(
	'builddoc' => img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
	'classifyclose' => img_picto('', 'stop-circle', 'class="pictofixedwidth"').$langs->trans("Close"),
	'presend'  => img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
);
if (in_array($massaction, array('presend'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

// Currently: a sending can't create from sending list
// $url = DOL_URL_ROOT.'/expedition/card.php?action=create';
// if (!empty($socid)) $url .= '&socid='.$socid;
// $newcardbutton = dolGetButtonTitle($langs->trans('NewSending'), '', 'fa fa-plus-circle', $url, '', $user->rights->expedition->creer);
$newcardbutton  = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss'=>'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss'=>'reposition'));
$newcardbutton .= dolGetButtonTitleSeparator();
$newcardbutton .= dolGetButtonTitle($langs->trans('NewSending'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/expedition/card.php?action=create2', '', $user->hasRight('expedition', 'creer'));

$i = 0;
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

print_barre_liste($langs->trans('ListOfSendings'), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'dolly', 0, $newcardbutton, '', $limit, 0, 0, 1);

$topicmail = "SendShippingRef";
$modelmail = "shipping_send";
$objecttmp = new Expedition($db);
$trackid = 'shi'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($search_all) {
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
	}
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).join(', ', $fieldstosearchall).'</div>';
}

$moreforfilter = '';

// If the user can view prospects other than his'
if ($user->hasRight('user', 'user', 'lire')) {
	$langs->load("commercial");
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('ThirdPartiesOfSaleRepresentative');
	$moreforfilter .= img_picto($tmptitle, 'user', 'class="pictofixedwidth"');
	$moreforfilter .= $formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, $tmptitle, 'maxwidth200');
	$moreforfilter .= '</div>';
}
// If the user can view other users
if ($user->hasRight('user', 'user', 'lire')) {
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('LinkedToSpecificUsers');
	$moreforfilter .= img_picto($tmptitle, 'user', 'class="pictofixedwidth"');
	$moreforfilter .= $form->select_dolusers($search_user, 'search_user', $tmptitle, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth200');
	$moreforfilter .= '</div>';
}
// If the user can view prospects other than his'
if (isModEnabled('categorie') && $user->hasRight('categorie', 'lire') && ($user->hasRight('produit', 'lire') || $user->hasRight('service', 'lire'))) {
	include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('IncludingProductWithTag');
	$moreforfilter .= img_picto($tmptitle, 'category');
	//$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, null, 'parent', null, null, 1);
	//$moreforfilter .= $form->selectarray('search_product_category', $cate_arbo, $search_product_category, 1, 0, 0, '', 0, 0, 0, 0, 'maxwidth300', 1);
	$moreforfilter .= $formother->select_categories(Categorie::TYPE_PRODUCT, $search_product_category, 'search_product_category', 1, $tmptitle);

	$moreforfilter .= '</div>';
}
if (isModEnabled('categorie') && $user->hasRight('categorie', 'lire')) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('CustomersProspectsCategoriesShort');
	$moreforfilter .= img_picto($tmptitle, 'category');
	$moreforfilter .= $formother->select_categories('customer', $search_categ_cus, 'search_categ_cus', 1, $tmptitle);
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
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN', ''));
if ($massactionbutton) {
	$selectedfields .= $form->showCheckAddButtons('checkforselect', 1); // This also change content of $arrayfields
}

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

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
// Ref
if (!empty($arrayfields['e.ref']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" size="6" type="text" name="search_ref_exp" value="'.$search_ref_exp.'">';
	print '</td>';
}
// Ref customer
if (!empty($arrayfields['e.ref_customer']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" size="6" type="text" name="search_ref_customer" value="'.$search_ref_customer.'">';
	print '</td>';
}
// Thirdparty
if (!empty($arrayfields['s.nom']['checked'])) {
	print '<td class="liste_titre left">';
	print '<input class="flat" type="text" size="8" name="search_company" value="'.dol_escape_htmltag($search_company).'">';
	print '</td>';
}
// Town
if (!empty($arrayfields['s.town']['checked'])) {
	print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_town" value="'.$search_town.'"></td>';
}
// Zip
if (!empty($arrayfields['s.zip']['checked'])) {
	print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_zip" value="'.$search_zip.'"></td>';
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
// Weight
if (!empty($arrayfields['e.weight']['checked'])) {
	print '<td class="liste_titre maxwidthonsmartphone center">';

	print '</td>';
}
// Date delivery planned
if (!empty($arrayfields['e.date_delivery']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_datedelivery_start ? $search_datedelivery_start : -1, 'search_datedelivery_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_datedelivery_end ? $search_datedelivery_end : -1, 'search_datedelivery_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
if (!empty($arrayfields['e.fk_shipping_method']['checked'])) {
	// Delivery method
	print '<td class="liste_titre center">';
	$shipment->fetch_delivery_methods();
	print $form->selectarray("search_shipping_method_id", $shipment->meths, $search_shipping_method_id, 1, 0, 0, "", 1, 0, 0, '', 'maxwidth150');
	print "</td>\n";
}
// Tracking number
if (!empty($arrayfields['e.tracking_number']['checked'])) {
	print '<td class="liste_titre center">';
	print '<input class="flat" size="6" type="text" name="search_tracking" value="'.dol_escape_htmltag($search_tracking).'">';
	print '</td>';
}
if (!empty($arrayfields['l.ref']['checked'])) {
	// Delivery ref
	print '<td class="liste_titre">';
	print '<input class="flat width75" type="text" name="search_ref_liv" value="'.dol_escape_htmltag($search_ref_liv).'"';
	print '</td>';
}
if (!empty($arrayfields['l.date_delivery']['checked'])) {
	// Date received
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_datereceipt_start ? $search_datereceipt_start : -1, 'search_datereceipt_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_datereceipt_end ? $search_datereceipt_end : -1, 'search_datereceipt_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Date creation
if (!empty($arrayfields['e.datec']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
// Date modification
if (!empty($arrayfields['e.tms']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
// Status
if (!empty($arrayfields['e.fk_statut']['checked'])) {
	print '<td class="liste_titre right parentonrightofpage">';
	print $form->selectarray('search_status', array('0'=>$langs->trans('StatusSendingDraftShort'), '1'=>$langs->trans('StatusSendingValidatedShort'), '2'=>$langs->trans('StatusSendingProcessedShort')), $search_status, 1, 0, 0, '', 0, 0, 0, '', 'search_status width100 onrightofpage');
	print '</td>';
}
// Status billed
if (!empty($arrayfields['e.billed']['checked'])) {
	print '<td class="liste_titre maxwidthonsmartphone center">';
	print $form->selectyesno('search_billed', $search_billed, 1, 0, 1);
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
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['e.ref']['checked'])) {
	print_liste_field_titre($arrayfields['e.ref']['label'], $_SERVER["PHP_SELF"], "e.ref", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['e.ref_customer']['checked'])) {
	print_liste_field_titre($arrayfields['e.ref_customer']['label'], $_SERVER["PHP_SELF"], "e.ref_customer", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.nom']['checked'])) {
	print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], "s.nom", "", $param, '', $sortfield, $sortorder, 'left ');
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
if (!empty($arrayfields['e.weight']['checked'])) {
	print_liste_field_titre($arrayfields['e.weight']['label'], $_SERVER["PHP_SELF"], "e.weight", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['e.date_delivery']['checked'])) {
	print_liste_field_titre($arrayfields['e.date_delivery']['label'], $_SERVER["PHP_SELF"], "e.date_delivery", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['e.fk_shipping_method']['checked'])) {
	print_liste_field_titre($arrayfields['e.fk_shipping_method']['label'], $_SERVER["PHP_SELF"], "e.fk_shipping_method", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['e.tracking_number']['checked'])) {
	print_liste_field_titre($arrayfields['e.tracking_number']['label'], $_SERVER["PHP_SELF"], "e.tracking_number", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['l.ref']['checked'])) {
	print_liste_field_titre($arrayfields['l.ref']['label'], $_SERVER["PHP_SELF"], "l.ref", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['l.date_delivery']['checked'])) {
	print_liste_field_titre($arrayfields['l.date_delivery']['label'], $_SERVER["PHP_SELF"], "l.date_delivery", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder, '$totalarray'=>&$totalarray);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!empty($arrayfields['e.datec']['checked'])) {
	print_liste_field_titre($arrayfields['e.datec']['label'], $_SERVER["PHP_SELF"], "e.date_creation", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['e.tms']['checked'])) {
	print_liste_field_titre($arrayfields['e.tms']['label'], $_SERVER["PHP_SELF"], "e.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['e.fk_statut']['checked'])) {
	print_liste_field_titre($arrayfields['e.fk_statut']['label'], $_SERVER["PHP_SELF"], "e.fk_statut", "", $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['e.billed']['checked'])) {
	print_liste_field_titre($arrayfields['e.billed']['label'], $_SERVER["PHP_SELF"], "e.billed", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	$totalarray['nbfield']++;
}
print "</tr>\n";

$typenArray = $formcompany->typent_array(1);

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

	$shipment->id = $obj->rowid;
	$shipment->ref = $obj->ref;
	$shipment->shipping_method_id=$obj->fk_shipping_method;

	$companystatic->id = $obj->socid;
	$companystatic->ref = $obj->name;
	$companystatic->name = $obj->name;

	$object = new Expedition($db);
	$object->fetch($obj->rowid);

	if ($mode == 'kanban') {
		if ($i == 0) {
			print '<tr class="trkanban"><td colspan="'.$savnbfield.'">';
			print '<div class="box-flex-container kanban">';
		}
		$object->date_delivery = $obj->delivery_date;
		$object->town = $obj->town;

		// Output Kanban
		$selected = -1;
		if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($object->id, $arrayofselected)) {
				$selected = 1;
			}
		}
		print $object->getKanbanView('', array('thirdparty' => $companystatic->getNomUrl(1), 'selected' => $selected));
		if ($i == min($num, $limit) - 1) {
			print '</div>';
			print '</td></tr>';
		}
	} else {
		print '<tr class="oddeven">';

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
		// Ref
		if (!empty($arrayfields['e.ref']['checked'])) {
			print '<td class="nowraponall">';
			print $shipment->getNomUrl(1);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Ref customer
		if (!empty($arrayfields['e.ref_customer']['checked'])) {
			print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($obj->ref_customer).'">';
			print dol_escape_htmltag($obj->ref_customer);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Third party
		if (!empty($arrayfields['s.nom']['checked'])) {
			print '<td class="tdoverflowmax150">';
			print $companystatic->getNomUrl(1);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Town
		if (!empty($arrayfields['s.town']['checked'])) {
			print '<td class="nocellnopadd">';
			print dol_escape_htmltag($obj->town);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Zip
		if (!empty($arrayfields['s.zip']['checked'])) {
			print '<td class="nocellnopadd center">';
			print dol_escape_htmltag($obj->zip);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// State
		if (!empty($arrayfields['state.nom']['checked'])) {
			print '<td class="center">'.$obj->state_name."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Country
		if (!empty($arrayfields['country.code_iso']['checked'])) {
			print '<td class="center">';
			$tmparray = getCountry($obj->fk_pays, 'all');
			print dol_escape_htmltag($tmparray['label']);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Type ent
		if (!empty($arrayfields['typent.code']['checked'])) {
			print '<td class="center">';
			if (isset($typenArray[$obj->typent_code])) {
				print $typenArray[$obj->typent_code];
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Weight
		if (!empty($arrayfields['e.weight']['checked'])) {
			print '<td class="center">';
			if (empty($object->trueWeight)) {
				$tmparray = $object->getTotalWeightVolume();
				print showDimensionInBestUnit($tmparray['weight'], 0, "weight", $langs, isset($conf->global->MAIN_WEIGHT_DEFAULT_ROUND) ? $conf->global->MAIN_WEIGHT_DEFAULT_ROUND : -1, isset($conf->global->MAIN_WEIGHT_DEFAULT_UNIT) ? $conf->global->MAIN_WEIGHT_DEFAULT_UNIT : 'no');
				print $form->textwithpicto('', $langs->trans('EstimatedWeight'), 1);
			} else {
				print $object->trueWeight;
				print ($object->trueWeight && $object->weight_units != '') ? ' '.measuringUnitString(0, "weight", $object->weight_units) : '';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Date delivery planed
		if (!empty($arrayfields['e.date_delivery']['checked'])) {
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->delivery_date), "dayhour");
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		if (!empty($arrayfields['e.fk_shipping_method']['checked'])) {
			// Get code using getLabelFromKey
			$code=$langs->getLabelFromKey($db, $shipment->shipping_method_id, 'c_shipment_mode', 'rowid', 'code');
			print '<td class="center tdoverflowmax150" title="'.dol_escape_htmltag($langs->trans("SendingMethod".strtoupper($code))).'">';
			if ($shipment->shipping_method_id > 0) {
				print $langs->trans("SendingMethod".strtoupper($code));
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Tracking number
		if (!empty($arrayfields['e.tracking_number']['checked'])) {
			$shipment->getUrlTrackingStatus($obj->tracking_number);
			print '<td class="center">'.$shipment->tracking_url."</td>\n";
			//print $form->editfieldval("TrackingNumber", 'tracking_number', $obj->tracking_url, $obj, $user->rights->expedition->creer, 'string', $obj->tracking_number);
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		if (!empty($arrayfields['l.ref']['checked']) || !empty($arrayfields['l.date_delivery']['checked'])) {
			$shipment->fetchObjectLinked($shipment->id, $shipment->element);
			$receiving = '';
			if (is_array($shipment->linkedObjects['delivery']) && count($shipment->linkedObjects['delivery']) > 0) {
				$receiving = reset($shipment->linkedObjects['delivery']);
			}

			if (!empty($arrayfields['l.ref']['checked'])) {
				// Ref
				print '<td>';
				print !empty($receiving) ? $receiving->getNomUrl($db) : '';
				print '</td>';
			}

			if (!empty($arrayfields['l.date_delivery']['checked'])) {
				// Date received
				print '<td class="center nowraponall">';
				print dol_print_date($db->jdate($obj->date_reception), "day");
				print '</td>'."\n";
			}
		}

		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Date creation
		if (!empty($arrayfields['e.datec']['checked'])) {
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuserrel');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Date modification
		if (!empty($arrayfields['e.tms']['checked'])) {
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Status
		if (!empty($arrayfields['e.fk_statut']['checked'])) {
			print '<td class="right nowrap">'.$shipment->LibStatut($obj->fk_statut, 5).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Billed
		if (!empty($arrayfields['e.billed']['checked'])) {
			print '<td class="center">'.yn($obj->billed).'</td>';
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

$parameters = array('arrayfields'=>$arrayfields, 'totalarray' => $totalarray, 'sql'=>$sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print "</table>";
print "</div>";
print '</form>';

$hidegeneratedfilelistifempty = 1;
if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) {
	$hidegeneratedfilelistifempty = 0;
}

// Show list of available documents
$urlsource  = $_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
$urlsource .= str_replace('&amp;', '&', $param);

$filedir    = $diroutputmassaction;
$genallowed = $user->rights->expedition->lire;
$delallowed = $user->rights->expedition->creer;
$title      = '';

print $formfile->showdocuments('massfilesarea_sendings', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);

// End of page
llxFooter();
$db->close();
