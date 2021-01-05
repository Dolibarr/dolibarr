<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2016-2018 Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2019      Nicolas ZABOURI      <info@inovea-conseil.com>
 * Copyright (C) 2020      Thibault FOUCART     <support@ptibogxiv.net>
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

$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'shipmentlist'; // To manage different context of search

$socid = GETPOST('socid', 'int');

$action     = GETPOST('action', 'alpha');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$toselect   = GETPOST('toselect', 'array');

// Security check
$expeditionid = GETPOST('id', 'int');
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'expedition', $expeditionid, '');

$search_ref_exp = GETPOST("search_ref_exp", 'alpha');
$search_ref_liv = GETPOST('search_ref_liv', 'alpha');
$search_ref_customer = GETPOST('search_ref_customer', 'alpha');
$search_company = GETPOST("search_company", 'alpha');
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
$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$socid = GETPOST('socid', 'int');
$search_user = GETPOST('search_user', 'int');
$search_sale = GETPOST('search_sale', 'int');
$search_categ_cus = GETPOST("search_categ_cus", 'int');
$search_product_category = GETPOST('search_product_category', 'int');
$optioncss = GETPOST('optioncss', 'alpha');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (!$sortfield) $sortfield = "e.ref";
if (!$sortorder) $sortorder = "DESC";
if (empty($page) || $page == -1 || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$search_status = GETPOST('search_status');

$diroutputmassaction = $conf->expedition->dir_output.'/sending/temp/massgeneration/'.$user->id;

$object = new Expedition($db);
$form = new Form($db);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('shipmentlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'e.ref'=>"Ref",
	's.nom'=>"ThirdParty",
	'e.note_public'=>'NotePublic',
	'e.tracking_number'=>"TrackingNumber",
);
if (empty($user->socid)) $fieldstosearchall["e.note_private"] = "NotePrivate";

$checkedtypetiers = 0;
$arrayfields = array(
	'e.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	'e.ref_customer'=>array('label'=>$langs->trans("RefCustomer"), 'checked'=>1),
	's.nom'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>1),
	's.town'=>array('label'=>$langs->trans("Town"), 'checked'=>1),
	's.zip'=>array('label'=>$langs->trans("Zip"), 'checked'=>1),
	'state.nom'=>array('label'=>$langs->trans("StateShort"), 'checked'=>0),
	'country.code_iso'=>array('label'=>$langs->trans("Country"), 'checked'=>0),
	'typent.code'=>array('label'=>$langs->trans("ThirdPartyType"), 'checked'=>$checkedtypetiers),
	'e.date_delivery'=>array('label'=>$langs->trans("DateDeliveryPlanned"), 'checked'=>1),
	'e.tracking_number'=>array('label'=>$langs->trans("TrackingNumber"), 'checked'=>1),
	'e.weight'=>array('label'=>$langs->trans("Weight"), 'checked'=>0),
	'e.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
	'e.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
	'e.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
	'l.ref'=>array('label'=>$langs->trans("DeliveryRef"), 'checked'=>1, 'enabled'=>(empty($conf->delivery_note->enabled) ? 0 : 1)),
	'l.date_delivery'=>array('label'=>$langs->trans("DateReceived"), 'checked'=>1, 'enabled'=>(empty($conf->delivery_note->enabled) ? 0 : 1)),
	'e.billed'=>array('label'=>$langs->trans("Billed"), 'checked'=>1, 'position'=>1000, 'enabled'=>(!empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)))
);

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');


/*
 * Actions
 */
$error = 0;

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

$parameters = array('socid'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
	$search_categ = '';
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
	$search_type_thirdparty = '';
	$search_billed = '';
	$search_datedelivery_start = '';
	$search_datedelivery_end = '';
	$search_datereceipt_start = '';
	$search_datereceipt_end = '';
	$search_status = '';
	$toselect = '';
	$search_array_options = array();
	$search_categ_cus = 0;
}

if (empty($reshook))
{
	$objectclass  = 'Expedition';
	$objectlabel  = 'Sendings';
	$permissiontoread   = $user->rights->expedition->lire;
	$permissiontoadd = $user->rights->expedition->creer;
	$permissiontodelete = $user->rights->expedition->supprimer;
	$uploaddir = $conf->expedition->dir_output.'/sending';
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
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
if ($sall || $search_product_category > 0) $sql = 'SELECT DISTINCT';
$sql .= " e.rowid, e.ref, e.ref_customer, e.date_expedition as date_expedition, e.weight, e.weight_units, e.date_delivery as delivery_date, l.date_delivery as date_reception, e.fk_statut, e.billed, e.tracking_number,";
$sql .= " s.rowid as socid, s.nom as name, s.town, s.zip, s.fk_pays, s.client, s.code_client, ";
$sql .= " typent.code as typent_code,";
$sql .= " state.code_departement as state_code, state.nom as state_name,";
$sql .= " e.date_creation as date_creation, e.tms as date_update,";
$sql .= " u.login";
if ($search_categ_cus) $sql .= ", cc.fk_categorie, cc.fk_soc";
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " FROM ".MAIN_DB_PREFIX."expedition as e";
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (e.rowid = ef.fk_object)";
if ($sall || $search_product_category > 0) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'expeditiondet as ed ON e.rowid=ed.fk_expedition';
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'commandedet as pd ON pd.rowid=ed.fk_origin_line';
}
if ($search_product_category > 0) $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=pd.fk_product';
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = e.fk_soc";
if (!empty($search_categ_cus)) $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_societe as cc ON s.rowid = cc.fk_soc"; // We'll need this table joined to the select in order to filter by categ
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as ee ON e.rowid = ee.fk_source AND ee.sourcetype = 'shipping' AND ee.targettype = 'delivery'";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."delivery as l ON l.rowid = ee.fk_target";
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON e.fk_user_author = u.rowid';

// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (!$user->rights->societe->client->voir && !$socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
if ($search_user > 0)
{
	$sql .= ", ".MAIN_DB_PREFIX."element_contact as ec";
	$sql .= ", ".MAIN_DB_PREFIX."c_type_contact as tc";
}
$sql .= " WHERE e.entity IN (".getEntity('expedition').")";
if ($search_product_category > 0) $sql .= " AND cp.fk_categorie = ".$search_product_category;
if ($socid > 0) $sql .= ' AND s.rowid = '.$socid;
if (!$user->rights->societe->client->voir && !$socid)	// Internal user with no permission to see all
{
	$sql .= " AND e.fk_soc = sc.fk_soc";
	$sql .= " AND sc.fk_user = ".$user->id;
}
if ($socid)
{
	$sql .= " AND e.fk_soc = ".$socid;
}
if ($search_status <> '' && $search_status >= 0) {
	$sql .= " AND e.fk_statut = ".$search_status;
}
if ($search_ref_customer != '') $sql .= natural_search('e.ref_customer', $search_ref_customer);
if ($search_billed != '' && $search_billed >= 0) $sql .= ' AND e.billed = '.$search_billed;
if ($search_town)  $sql .= natural_search('s.town', $search_town);
if ($search_zip)   $sql .= natural_search("s.zip", $search_zip);
if ($search_state) $sql .= natural_search("state.nom", $search_state);
if ($search_country) $sql .= " AND s.fk_pays IN (".$search_country.')';
if ($search_tracking)   $sql .= natural_search("e.tracking_number", $search_tracking);
if ($search_type_thirdparty) $sql .= " AND s.fk_typent IN (".$search_type_thirdparty.')';
if ($search_sale > 0)                        $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$search_sale;
if ($search_ref_exp) $sql .= natural_search('e.ref', $search_ref_exp);
if ($search_ref_liv) $sql .= natural_search('l.ref', $search_ref_liv);
if ($search_company) $sql .= natural_search('s.nom', $search_company);
if ($search_datedelivery_start)	$sql .= " AND e.date_delivery >= '".$db->idate($search_datedelivery_start)."'";
if ($search_datedelivery_end)	$sql .= " AND e.date_delivery <= '".$db->idate($search_datedelivery_end)."'";
if ($search_datereceipt_start)	$sql .= " AND l.date_delivery >= '".$db->idate($search_datereceipt_start)."'";
if ($search_datereceipt_end)	$sql .= " AND l.date_delivery <= '".$db->idate($search_datereceipt_end)."'";
if ($sall) $sql .= natural_search(array_keys($fieldstosearchall), $sall);
if ($search_categ_cus > 0)                   $sql .= " AND cc.fk_categorie = ".$db->escape($search_categ_cus);
if ($search_categ_cus == -2)                 $sql .= " AND cc.fk_categorie IS NULL";

// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';

// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= $db->order($sortfield, $sortorder);

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);

//print $sql;
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$arrayofselected = is_array($toselect) ? $toselect : array();

	$expedition = new Expedition($db);

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
	if ($sall) $param .= "&amp;sall=".urlencode($sall);
	if ($search_ref_exp)  $param .= "&amp;search_ref_exp=".urlencode($search_ref_exp);
	if ($search_ref_liv)  $param .= "&amp;search_ref_liv=".urlencode($search_ref_liv);
	if ($search_ref_customer) $param .= "&amp;search_ref_customer=".urlencode($search_ref_customer);
	if ($search_user > 0) 			$param .= '&search_user='.urlencode($search_user);
	if ($search_sale > 0) 			$param .= '&search_sale='.urlencode($search_sale);
	if ($search_company)   $param .= "&amp;search_company=".urlencode($search_company);
	if ($search_tracking)   $param .= "&amp;search_tracking=".urlencode($search_tracking);
	if ($search_town)      $param .= '&search_town='.urlencode($search_town);
	if ($search_zip)       $param .= '&search_zip='.urlencode($search_zip);

	if ($search_datedelivery_start)	$param .= '&search_datedelivery_start='.urlencode($search_datedelivery_start);
	if ($search_datedelivery_end)	$param .= '&search_datedelivery_end='.urlencode($search_datedelivery_end);
	if ($search_datereceipt_start)	$param .= '&search_datereceipt_start='.urlencode($search_datereceipt_start);
	if ($search_datereceipt_end)	$param .= '&search_datereceipt_end='.urlencode($search_datereceipt_end);

	if ($search_product_category != '') $param .= '&search_product_category='.urlencode($search_product_category);
	if ($search_categ_cus > 0)      $param .= '&search_categ_cus='.urlencode($search_categ_cus);
	if ($search_status != '') $param .= '&search_status='.urlencode($search_status);
	if ($optioncss != '')  $param .= '&optioncss='.urlencode($optioncss);
	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	$arrayofmassactions = array(
		'builddoc' => $langs->trans("PDFMerge"),
		//'classifyclose'=>$langs->trans("Close"), TODO massive close shipment ie: when truck is charged
		'presend'  => $langs->trans("SendByMail"),
	);
	if (in_array($massaction, array('presend'))) $arrayofmassactions = array();
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	// Currently: a sending can't create from sending list
	// $url = DOL_URL_ROOT.'/expedition/card.php?action=create';
	// if (!empty($socid)) $url .= '&socid='.$socid;
	// $newcardbutton = dolGetButtonTitle($langs->trans('NewSending'), '', 'fa fa-plus-circle', $url, '', $user->rights->expedition->creer);
	$newcardbutton = dolGetButtonTitle($langs->trans('NewSending'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/expedition/card.php?action=create2', '', $user->rights->expedition->creer);

	$i = 0;
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

	print_barre_liste($langs->trans('ListOfSendings'), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'dolly', 0, $newcardbutton, '', $limit, 0, 0, 1);

	$topicmail = "SendShippingRef";
	$modelmail = "shipping_send";
	$objecttmp = new Expedition($db);
	$trackid = 'shi'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	if ($sall)
	{
		foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall).join(', ', $fieldstosearchall).'</div>';
	}

	$moreforfilter = '';

 	// If the user can view prospects other than his'
 	if ($user->rights->societe->client->voir || $socid)
 	{
 		$langs->load("commercial");
		$moreforfilter .= '<div class="divsearchfield">';
 		$moreforfilter .= $langs->trans('ThirdPartiesOfSaleRepresentative').': ';
		$moreforfilter .= $formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, 1, 'maxwidth200');
	 	$moreforfilter .= '</div>';
 	}
	// If the user can view other users
	if ($user->rights->user->user->lire)
	{
		$moreforfilter .= '<div class="divsearchfield">';
		$moreforfilter .= $langs->trans('LinkedToSpecificUsers').': ';
		$moreforfilter .= $form->select_dolusers($search_user, 'search_user', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth200');
	 	$moreforfilter .= '</div>';
	}
	// If the user can view prospects other than his'
	if (!empty($conf->categorie->enabled) && $user->rights->categorie->lire && ($user->rights->produit->lire || $user->rights->service->lire))
	{
		include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$moreforfilter .= '<div class="divsearchfield">';
		$moreforfilter .= $langs->trans('IncludingProductWithTag').': ';
		$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, null, 'parent', null, null, 1);
		$moreforfilter .= $form->selectarray('search_product_category', $cate_arbo, $search_product_category, 1, 0, 0, '', 0, 0, 0, 0, 'maxwidth300', 1);
		$moreforfilter .= '</div>';
	}
	if (!empty($conf->categorie->enabled) && $user->rights->categorie->lire)
	{
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$moreforfilter .= '<div class="divsearchfield">';
	 	$moreforfilter .= $langs->trans('CustomersProspectsCategoriesShort').': ';
		$moreforfilter .= $formother->select_categories('customer', $search_categ_cus, 'search_categ_cus', 1);
	 	$moreforfilter .= '</div>';
	}
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
	else $moreforfilter = $hookmanager->resPrint;

	if (!empty($moreforfilter))
	{
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>';
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);
	if ($massactionbutton) $selectedfields .= $form->showCheckAddButtons('checkforselect', 1); // This also change content of $arrayfields

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	// Fields title search
	print '<tr class="liste_titre_filter">';
	// Ref
	if (!empty($arrayfields['e.ref']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="6" type="text" name="search_ref_exp" value="'.$search_ref_exp.'">';
		print '</td>';
	}
	// Ref customer
	if (!empty($arrayfields['e.ref_customer']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="6" type="text" name="search_ref_customer" value="'.$search_ref_customer.'">';
		print '</td>';
	}
	// Thirdparty
	if (!empty($arrayfields['s.nom']['checked']))
	{
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" size="8" name="search_company" value="'.dol_escape_htmltag($search_company).'">';
		print '</td>';
	}
	// Town
	if (!empty($arrayfields['s.town']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_town" value="'.$search_town.'"></td>';
	// Zip
	if (!empty($arrayfields['s.zip']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_zip" value="'.$search_zip.'"></td>';
	// State
	if (!empty($arrayfields['state.nom']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="4" type="text" name="search_state" value="'.dol_escape_htmltag($search_state).'">';
		print '</td>';
	}
	// Country
	if (!empty($arrayfields['country.code_iso']['checked']))
	{
		print '<td class="liste_titre center">';
		print $form->select_country($search_country, 'search_country', '', 0, 'minwidth100imp maxwidth100');
		print '</td>';
	}
	// Company type
	if (!empty($arrayfields['typent.code']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone center">';
		print $form->selectarray("search_type_thirdparty", $formcompany->typent_array(0), $search_type_thirdparty, 0, 0, 0, '', 0, 0, 0, (empty($conf->global->SOCIETE_SORT_ON_TYPEENT) ? 'ASC' : $conf->global->SOCIETE_SORT_ON_TYPEENT));
		print '</td>';
	}
	// Weight
	if (!empty($arrayfields['e.weight']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone center">';

		print '</td>';
	}
	// Date delivery planned
	if (!empty($arrayfields['e.date_delivery']['checked']))
	{
		print '<td class="liste_titre center">';
		print '<div class="nowrap">';
		print $langs->trans('From').' ';
		print $form->selectDate($search_delivery_start ? $search_delivery_start : -1, 'search_delivery_start', 0, 0, 1);
		print '</div>';
		print '<div class="nowrap">';
		print $langs->trans('to').' ';
		print $form->selectDate($search_delivery_end ? $search_delivery_end : -1, 'search_delivery_end', 0, 0, 1);
		print '</div>';
		print '</td>';
	}
	// Tracking number
	if (!empty($arrayfields['e.tracking_number']['checked']))
	{
		print '<td class="liste_titre center">';
		print '<input class="flat" size="6" type="text" name="search_tracking" value="'.dol_escape_htmltag($search_tracking).'">';
		print '</td>';
	}
	if (!empty($arrayfields['l.ref']['checked']))
	{
		// Delivery ref
		print '<td class="liste_titre">';
		print '<input class="flat" size="10" type="text" name="search_ref_liv" value="'.$search_ref_liv.'"';
		print '</td>';
	}
	if (!empty($arrayfields['l.date_delivery']['checked']))
	{
		// Date received
		print '<td class="liste_titre center">';
		print '<div class="nowrap">';
		print $langs->trans('From').' ';
		print $form->selectDate($search_receipt_start ? $search_receipt_start : -1, 'search_receipt_start', 0, 0, 1);
		print '</div>';
		print '<div class="nowrap">';
		print $langs->trans('to').' ';
		print $form->selectDate($search_receipt_end ? $search_receipt_end : -1, 'search_receipt_end', 0, 0, 1);
		print '</div>';
		print '</td>';
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (!empty($arrayfields['e.datec']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Date modification
	if (!empty($arrayfields['e.tms']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Status
	if (!empty($arrayfields['e.fk_statut']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone right">';
		print $form->selectarray('search_status', array('0'=>$langs->trans('StatusSendingDraftShort'), '1'=>$langs->trans('StatusSendingValidatedShort'), '2'=>$langs->trans('StatusSendingProcessedShort')), $search_status, 1);
		print '</td>';
	}
	// Status billed
	if (!empty($arrayfields['e.billed']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone center">';
		print $form->selectyesno('search_billed', $search_billed, 1, 0, 1);
		print '</td>';
	}
	// Action column
	print '<td class="liste_titre middle">';
	$searchpicto = $form->showFilterAndCheckAddButtons(0);
	print $searchpicto;
	print '</td>';
	print "</tr>\n";

	print '<tr class="liste_titre">';
	if (!empty($arrayfields['e.ref']['checked']))            print_liste_field_titre($arrayfields['e.ref']['label'], $_SERVER["PHP_SELF"], "e.ref", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['e.ref_customer']['checked']))   print_liste_field_titre($arrayfields['e.ref_customer']['label'], $_SERVER["PHP_SELF"], "e.ref_customer", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['s.nom']['checked']))            print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], "s.nom", "", $param, '', $sortfield, $sortorder, 'left ');
	if (!empty($arrayfields['s.town']['checked']))           print_liste_field_titre($arrayfields['s.town']['label'], $_SERVER["PHP_SELF"], 's.town', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['s.zip']['checked']))            print_liste_field_titre($arrayfields['s.zip']['label'], $_SERVER["PHP_SELF"], 's.zip', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['state.nom']['checked']))        print_liste_field_titre($arrayfields['state.nom']['label'], $_SERVER["PHP_SELF"], "state.nom", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['country.code_iso']['checked'])) print_liste_field_titre($arrayfields['country.code_iso']['label'], $_SERVER["PHP_SELF"], "country.code_iso", "", $param, '', $sortfield, $sortorder, 'center ');
	if (!empty($arrayfields['typent.code']['checked']))      print_liste_field_titre($arrayfields['typent.code']['label'], $_SERVER["PHP_SELF"], "typent.code", "", $param, '', $sortfield, $sortorder, 'center ');
	if (!empty($arrayfields['e.weight']['checked']))         print_liste_field_titre($arrayfields['e.weight']['label'], $_SERVER["PHP_SELF"], "e.weight", "", $param, '', $sortfield, $sortorder, 'center ');
	if (!empty($arrayfields['e.date_delivery']['checked']))  print_liste_field_titre($arrayfields['e.date_delivery']['label'], $_SERVER["PHP_SELF"], "e.date_delivery", "", $param, '', $sortfield, $sortorder, 'center ');
	if (!empty($arrayfields['e.tracking_number']['checked']))  print_liste_field_titre($arrayfields['e.tracking_number']['label'], $_SERVER["PHP_SELF"], "e.tracking_number", "", $param, '', $sortfield, $sortorder, 'center ');
	if (!empty($arrayfields['l.ref']['checked']))            print_liste_field_titre($arrayfields['l.ref']['label'], $_SERVER["PHP_SELF"], "l.ref", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['l.date_delivery']['checked']))  print_liste_field_titre($arrayfields['l.date_delivery']['label'], $_SERVER["PHP_SELF"], "l.date_delivery", "", $param, '', $sortfield, $sortorder, 'center ');
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (!empty($arrayfields['e.datec']['checked']))  print_liste_field_titre($arrayfields['e.datec']['label'], $_SERVER["PHP_SELF"], "e.date_creation", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	if (!empty($arrayfields['e.tms']['checked']))    print_liste_field_titre($arrayfields['e.tms']['label'], $_SERVER["PHP_SELF"], "e.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	if (!empty($arrayfields['e.fk_statut']['checked'])) print_liste_field_titre($arrayfields['e.fk_statut']['label'], $_SERVER["PHP_SELF"], "e.fk_statut", "", $param, '', $sortfield, $sortorder, 'right ');
	if (!empty($arrayfields['e.billed']['checked'])) print_liste_field_titre($arrayfields['e.billed']['label'], $_SERVER["PHP_SELF"], "e.billed", "", $param, '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	print "</tr>\n";

	$typenArray = $formcompany->typent_array(1);
	$i = 0;
	$totalarray = array();
	while ($i < min($num, $limit))
	{
		$obj = $db->fetch_object($resql);

		$shipment->id = $obj->rowid;
		$shipment->ref = $obj->ref;

		$companystatic->id = $obj->socid;
		$companystatic->ref = $obj->name;
		$companystatic->name = $obj->name;

		$object = new Expedition($db);
		$object->fetch($obj->rowid);

		print '<tr class="oddeven">';

		// Ref
		if (!empty($arrayfields['e.ref']['checked']))
		{
			print "<td>";
			print $shipment->getNomUrl(1);
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		// Ref customer
		if (!empty($arrayfields['e.ref_customer']['checked']))
		{
			print "<td>";
			print $obj->ref_customer;
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		// Third party
		if (!empty($arrayfields['s.nom']['checked']))
		{
			print '<td>';
			print $companystatic->getNomUrl(1);
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Town
		if (!empty($arrayfields['s.town']['checked']))
		{
			print '<td class="nocellnopadd">';
			print $obj->town;
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Zip
		if (!empty($arrayfields['s.zip']['checked']))
		{
			print '<td class="nocellnopadd">';
			print $obj->zip;
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// State
		if (!empty($arrayfields['state.nom']['checked']))
		{
			print "<td>".$obj->state_name."</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}
		// Country
		if (!empty($arrayfields['country.code_iso']['checked']))
		{
			print '<td class="center">';
			$tmparray = getCountry($obj->fk_pays, 'all');
			print $tmparray['label'];
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Type ent
		if (!empty($arrayfields['typent.code']['checked']))
		{
			print '<td class="center">';
			if (isset($typenArray[$obj->typent_code]))  print $typenArray[$obj->typent_code];
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Weight
		if (!empty($arrayfields['e.weight']['checked']))
		{
			print '<td class="center">';
			if (empty($object->trueWeight))
			{
				$tmparray = $object->getTotalWeightVolume();
				print showDimensionInBestUnit($tmparray['weight'], 0, "weight", $langs, isset($conf->global->MAIN_WEIGHT_DEFAULT_ROUND) ? $conf->global->MAIN_WEIGHT_DEFAULT_ROUND : -1, isset($conf->global->MAIN_WEIGHT_DEFAULT_UNIT) ? $conf->global->MAIN_WEIGHT_DEFAULT_UNIT : 'no');
				print $form->textwithpicto('', $langs->trans('EstimatedWeight'), 1);
			} else {
				print $object->trueWeight;
				print ($object->trueWeight && $object->weight_units != '') ? ' '.measuringUnitString(0, "weight", $object->weight_units) : '';
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Date delivery planed
		if (!empty($arrayfields['e.date_delivery']['checked']))
		{
			print '<td class="center">';
			print dol_print_date($db->jdate($obj->delivery_date), "dayhour");
			/*$now = time();
    		if ( ($now - $db->jdate($obj->date_expedition)) > $conf->warnings->lim && $obj->statutid == 1 )
    		{
    		}*/
			print "</td>\n";
		}
		// Tracking number
		if (!empty($arrayfields['e.tracking_number']['checked']))
		{
			print '<td class="center">'.$obj->tracking_number."</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		if (!empty($arrayfields['l.ref']['checked']) || !empty($arrayfields['l.date_delivery']['checked']))
		{
			$shipment->fetchObjectLinked($shipment->id, $shipment->element);
			$receiving = '';
			if (is_array($shipment->linkedObjects['delivery']) && count($shipment->linkedObjects['delivery']) > 0) $receiving = reset($shipment->linkedObjects['delivery']);

			if (!empty($arrayfields['l.ref']['checked']))
			{
				// Ref
				print '<td>';
				print !empty($receiving) ? $receiving->getNomUrl($db) : '';
				print '</td>';
			}

			if (!empty($arrayfields['l.date_delivery']['checked']))
			{
				// Date received
				print '<td class="center">';
				print dol_print_date($db->jdate($obj->date_reception), "day");
				print '</td>'."\n";
			}
		}

		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Date creation
		if (!empty($arrayfields['e.datec']['checked']))
		{
			print '<td class="center nowrap">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Date modification
		if (!empty($arrayfields['e.tms']['checked']))
		{
			print '<td class="center nowrap">';
			print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Status
		if (!empty($arrayfields['e.fk_statut']['checked']))
		{
			print '<td class="right nowrap">'.$shipment->LibStatut($obj->fk_statut, 5).'</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Billed
		if (!empty($arrayfields['e.billed']['checked']))
		{
			print '<td class="center">'.yn($obj->billed).'</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Action column
		print '<td class="nowrap" align="center">';
		if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		{
			$selected = 0;
			if (in_array($obj->rowid, $arrayofselected)) $selected = 1;
			print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
		if (!$i) $totalarray['nbfield']++;

		print "</tr>\n";

		$i++;
	}
	$db->free($resql);

	$parameters = array('arrayfields'=>$arrayfields, 'totalarray' => $totalarray, 'sql'=>$sql);
	$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print "</table>";
	print "</div>";
	print '</form>';

	$hidegeneratedfilelistifempty = 1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files)   $hidegeneratedfilelistifempty = 0;

	// Show list of available documents
	$urlsource  = $_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource .= str_replace('&amp;', '&', $param);

	$filedir    = $diroutputmassaction;
	$genallowed = $user->rights->expedition->lire;
	$delallowed = $user->rights->expedition->creer;
	$title      = '';

	print $formfile->showdocuments('massfilesarea_sendings', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
