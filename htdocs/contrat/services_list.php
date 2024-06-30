<?php
/* Copyright (C) 2001-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2018       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2019      Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2023-2024	William Mead			<william.mead@manchenumerique.fr>
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
 *	    \file       htdocs/contrat/services_list.php
 *      \ingroup    contrat
 *		\brief      Page to list services in contracts
 */

require "../main.inc.php";
require_once DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php";
require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT."/societe/class/societe.class.php";

// Load translation files required by the page
$langs->loadLangs(array('products', 'contracts', 'companies'));

// Get parameters
$massaction = GETPOST('massaction', 'alpha');
$toselect   = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$optioncss  = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')
$mode       = GETPOST('mode', 'aZ'); // The output mode ('list', 'kanban', 'hierarchy', 'calendar', ...)
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php')).$mode; // To manage different context of search

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
	$sortfield = "c.rowid";
}
if (!$sortorder) {
	$sortorder = "ASC";
}

$search_name = GETPOST("search_name", 'alpha');
$search_subprice = GETPOST("search_subprice", 'alpha');
$search_qty = GETPOST("search_qty", 'alpha');
$search_total_ht = GETPOST("search_total_ht", 'alpha');
$search_total_tva = GETPOST("search_total_tva", 'alpha');
$search_total_ttc = GETPOST("search_total_ttc", 'alpha');
$search_contract = GETPOST("search_contract", 'alpha');
$search_service = GETPOST("search_service", 'alpha');
$search_status = GETPOST("search_status", 'alpha');
$search_product_category = GETPOSTINT('search_product_category');

// To support selection into combo list of status with detailed status '4&filter'
$filter = '';
if ($search_status == '4&filter=notexpired') {
	$search_status = '4';
	$filter = 'notexpired';
}
if ($search_status == '4&filter=expired') {
	$search_status = '4';
	$filter = 'expired';
}

$socid = GETPOSTINT('socid');

$opouvertureprevuemonth = GETPOST('opouvertureprevuemonth');
$opouvertureprevueday = GETPOST('opouvertureprevueday');
$opouvertureprevueyear = GETPOST('opouvertureprevueyear');
$filter_opouvertureprevue = GETPOST('filter_opouvertureprevue');

$op1month = GETPOSTINT('op1month');
$op1day = GETPOSTINT('op1day');
$op1year = GETPOSTINT('op1year');
$filter_op1 = GETPOST('filter_op1', 'alpha');

$op2month = GETPOSTINT('op2month');
$op2day = GETPOSTINT('op2day');
$op2year = GETPOSTINT('op2year');
$filter_op2 = GETPOST('filter_op2', 'alpha');

$opcloturemonth = GETPOSTINT('opcloturemonth');
$opclotureday = GETPOSTINT('opclotureday');
$opclotureyear = GETPOSTINT('opclotureyear');
$filter_opcloture = GETPOST('filter_opcloture', 'alpha');


// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$object = new ContratLigne($db);
$hookmanager->initHooks(array('contractservicelist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Security check
$contratid = GETPOSTINT('id');
if (!empty($user->socid)) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'contrat', $contratid);

$staticcontrat = new Contrat($db);
$staticcontratligne = new ContratLigne($db);
$companystatic = new Societe($db);

$arrayfields = array(
	'c.ref' => array('label' => "Contract", 'checked' => 1, 'position' => 80),
	'p.description' => array('label' => "Service", 'checked' => 1, 'position' => 80),
	's.nom' => array('label' => "ThirdParty", 'checked' => 1, 'position' => 90),
	'cd.tva_tx' => array('label' => "VATRate", 'checked' => -1, 'position' => 100),
	'cd.subprice' => array('label' => "PriceUHT", 'checked' => -1, 'position' => 105),
	'cd.qty' => array('label' => "Qty", 'checked' => 1, 'position' => 108),
	'cd.total_ht' => array('label' => "TotalHT", 'checked' => -1, 'position' => 109, 'isameasure' => 1),
	'cd.total_tva' => array('label' => "TotalVAT", 'checked' => -1, 'position' => 110),
	'cd.date_ouverture_prevue' => array('label' => "DateStartPlannedShort", 'checked' => 1, 'position' => 150),
	'cd.date_ouverture' => array('label' => "DateStartRealShort", 'checked' => 1, 'position' => 160),
	'cd.date_fin_validite' => array('label' => "DateEndPlannedShort", 'checked' => 1, 'position' => 170),
	'cd.date_cloture' => array('label' => "DateEndRealShort", 'checked' => 1, 'position' => 180),
	//'cd.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
	'cd.tms' => array('label' => "DateModificationShort", 'checked' => 0, 'position' => 500),
	'status' => array('label' => "Status", 'checked' => 1, 'position' => 1000)
);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');
'@phan-var-force array<string,array{label:string,checked?:int<0,1>,position?:int,help?:string}> $arrayfields';  // dol_sort_array looses type for Phan

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

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All test are required to be compatible with all browsers
		$search_product_category = 0;
		$search_name = "";
		$search_subprice = "";
		$search_qty = "";
		$search_total_ht = "";
		$search_total_tva = "";
		$search_total_ttc = "";
		$search_contract = "";
		$search_service = "";
		$search_status = "";
		$opouvertureprevuemonth = "";
		$opouvertureprevueday = "";
		$opouvertureprevueyear = "";
		$filter_opouvertureprevue = "";
		$op1month = "";
		$op1day = "";
		$op1year = "";
		$filter_op1 = "";
		$op2month = "";
		$op2day = "";
		$op2year = "";
		$filter_op2 = "";
		$opcloturemonth = "";
		$opclotureday = "";
		$opclotureyear = "";
		$filter_opcloture = "";
		$filter = '';
		$toselect = array();
		$search_array_options = array();
	}
}


/*
 * View
 */

$form = new Form($db);

$now = dol_now();

$title = $langs->trans("ListOfServices");
if ($search_status == "0") {
	$title = $langs->trans("ListOfInactiveServices"); // Must use == "0"
}
if ($search_status == "4" && $filter != "expired") {
	$title = $langs->trans("ListOfRunningServices");
}
if ($search_status == "4" && $filter == "expired") {
	$title = $langs->trans("ListOfExpiredServices");
}
if ($search_status == "5") {
	$title = $langs->trans("ListOfClosedServices");
}
$help_url = '';

// Build and execute select
// --------------------------------------------------------------------
$sql = "SELECT c.rowid as cid, c.ref, c.statut as cstatut, c.ref_customer, c.ref_supplier,";
$sql .= " s.rowid as socid, s.nom as name, s.email, s.client, s.fournisseur,";
$sql .= " cd.rowid, cd.description, cd.statut, cd.product_type as type,";
$sql .= " p.rowid as pid, p.ref as pref, p.label as label, p.fk_product_type as ptype, p.tobuy, p.tosell, p.barcode, p.entity as pentity,";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " sc.fk_soc, sc.fk_user,";
}
$sql .= " cd.date_ouverture_prevue,";
$sql .= " cd.date_ouverture,";
$sql .= " cd.date_fin_validite,";
$sql .= " cd.date_cloture,";
$sql .= " cd.qty,";
$sql .= " cd.total_ht,";
$sql .= " cd.total_tva,";
$sql .= " cd.tva_tx,";
$sql .= " cd.subprice,";
//$sql.= " cd.date_c as date_creation,";
$sql .= " cd.tms as date_modification";
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
$sql .= " FROM ".MAIN_DB_PREFIX."contrat as c,";
$sql .= " ".MAIN_DB_PREFIX."societe as s,";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " ".MAIN_DB_PREFIX."societe_commerciaux as sc,";
}
$sql .= " ".MAIN_DB_PREFIX."contratdet as cd";
if (!empty($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (cd.rowid = ef.fk_object)";
}
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON cd.fk_product = p.rowid";
if ($search_product_category > 0) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=cd.fk_product';
}
$sql .= " WHERE c.entity IN (".getEntity($object->element).")";
$sql .= " AND c.rowid = cd.fk_contrat";
if ($search_product_category > 0) {
	$sql .= " AND cp.fk_categorie = ".((int) $search_product_category);
}
$sql .= " AND c.fk_soc = s.rowid";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}
if ($search_status == "0") {
	$sql .= " AND cd.statut = 0";
}
if ($search_status == "4") {
	$sql .= " AND cd.statut = 4";
}
if ($search_status == "4&filter=expired" || ($search_status == '4' && $filter == 'expired')) {
	$sql .= " AND cd.statut = 4 AND cd.date_fin_validite < '".$db->idate($now)."'";
}
if ($search_status == "4&filter=notexpired" || ($search_status == '4' && $filter == 'notexpired')) {
	$sql .= " AND cd.statut = 4 AND cd.date_fin_validite >= '".$db->idate($now)."'";
}
if ($search_status == "5") {
	$sql .= " AND cd.statut = 5";
}
if ($search_subprice) {
	$sql .= natural_search("cd.subprice", $search_subprice, 1);
}
if ($search_qty) {
	$sql .= natural_search("cd.qty", $search_qty, 1);
}
if ($search_total_ht) {
	$sql .= natural_search("cd.total_ht", $search_total_ht, 1);
}
if ($search_total_tva) {
	$sql .= natural_search("cd.total_tva", $search_total_tva, 1);
}
if ($search_total_ttc) {
	$sql .= natural_search("cd.total_ttc", $search_total_ttc, 1);
}
if ($search_name) {
	$sql .= natural_search("s.nom", $search_name);
}
if ($search_contract) {
	$sql .= natural_search("c.ref", $search_contract);
}
if ($search_service) {
	$sql .= natural_search(array("p.ref", "p.description", "cd.description"), $search_service);
}
if ($socid > 0) {
	$sql .= " AND s.rowid = ".((int) $socid);
}

$filter_dateouvertureprevue = '';
$filter_date1 = '';
$filter_date2 = '';
$filter_opcloture = '';

$filter_dateouvertureprevue_start = dol_mktime(0, 0, 0, $opouvertureprevuemonth, $opouvertureprevueday, $opouvertureprevueyear);
$filter_dateouvertureprevue_end = dol_mktime(23, 59, 59, $opouvertureprevuemonth, $opouvertureprevueday, $opouvertureprevueyear);
if ($filter_dateouvertureprevue_start != '' && $filter_opouvertureprevue == -1) {
	$filter_opouvertureprevue = ' BETWEEN ';
}

$filter_date1_start = dol_mktime(0, 0, 0, $op1month, $op1day, $op1year);
$filter_date1_end = dol_mktime(23, 59, 59, $op1month, $op1day, $op1year);
if ($filter_date1_start != '' && $filter_op1 == -1) {
	$filter_op1 = ' BETWEEN ';
}

$filter_date2_start = dol_mktime(0, 0, 0, $op2month, $op2day, $op2year);
$filter_date2_end = dol_mktime(23, 59, 59, $op2month, $op2day, $op2year);
if ($filter_date2_start != '' && $filter_op2 == -1) {
	$filter_op2 = ' BETWEEN ';
}

$filter_datecloture_start = dol_mktime(0, 0, 0, $opcloturemonth, $opclotureday, $opclotureyear);
$filter_datecloture_end = dol_mktime(23, 59, 59, $opcloturemonth, $opclotureday, $opclotureyear);
if ($filter_datecloture_start != '' && $filter_opcloture == -1) {
	$filter_opcloture = ' BETWEEN ';
}

if (!empty($filter_opouvertureprevue) && $filter_opouvertureprevue != -1 && $filter_opouvertureprevue != ' BETWEEN ' && $filter_dateouvertureprevue_start != '') {
	$sql .= " AND cd.date_ouverture_prevue ".$filter_opouvertureprevue." '".$db->idate($filter_dateouvertureprevue_start)."'";
}
if (!empty($filter_opouvertureprevue) && $filter_opouvertureprevue == ' BETWEEN ') {
	$sql .= " AND cd.date_ouverture_prevue ".$filter_opouvertureprevue." '".$db->idate($filter_dateouvertureprevue_start)."' AND '".$db->idate($filter_dateouvertureprevue_end)."'";
}
if (!empty($filter_op1) && $filter_op1 != -1 && $filter_op1 != ' BETWEEN ' && $filter_date1_start != '') {
	$sql .= " AND cd.date_ouverture ".$filter_op1." '".$db->idate($filter_date1_start)."'";
}
if (!empty($filter_op1) && $filter_op1 == ' BETWEEN ') {
	$sql .= " AND cd.date_ouverture ".$filter_op1." '".$db->idate($filter_date1_start)."' AND '".$db->idate($filter_date1_end)."'";
}
if (!empty($filter_op2) && $filter_op2 != -1 && $filter_op2 != ' BETWEEN ' && $filter_date2_start != '') {
	$sql .= " AND cd.date_fin_validite ".$filter_op2." '".$db->idate($filter_date2_start)."'";
}
if (!empty($filter_op2) && $filter_op2 == ' BETWEEN ') {
	$sql .= " AND cd.date_fin_validite ".$filter_op2." '".$db->idate($filter_date2_start)."' AND '".$db->idate($filter_date2_end)."'";
}
if (!empty($filter_opcloture) && $filter_opcloture != ' BETWEEN ' && $filter_opcloture != -1 && $filter_datecloture_start != '') {
	$sql .= " AND cd.date_cloture ".$filter_opcloture." '".$db->idate($filter_datecloture_start)."'";
}
if (!empty($filter_opcloture) && $filter_opcloture == ' BETWEEN ') {
	$sql .= " AND cd.date_cloture ".$filter_opcloture." '".$db->idate($filter_datecloture_start)."' AND '".$db->idate($filter_datecloture_end)."'";
}
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';

//print $sql;

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
}

// Complete request and execute it with limit
$sql .= $db->order($sortfield, $sortorder);
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}

//print $sql;
dol_syslog("contrat/services_list.php", LOG_DEBUG);
$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);

/*
// Direct jump if only one record found
if ($num == 1 && getDolGlobalInt('MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE') && $search_all && !$page) {
	$obj = $db->fetch_object($resql);
	$id = $obj->id;
	header("Location: ".DOL_URL_ROOT.'/projet/tasks/task.php?id='.$id.'&withprojet=1');
	exit;
}*/


// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url);

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
if ($search_contract) {
	$param .= '&amp;search_contract='.urlencode($search_contract);
}
if ($search_name) {
	$param .= '&amp;search_name='.urlencode($search_name);
}
if ($search_subprice) {
	$param .= '&amp;search_subprice='.urlencode($search_subprice);
}
if ($search_qty) {
	$param .= '&amp;search_qty='.urlencode($search_qty);
}
if ($search_total_ht) {
	$param .= '&amp;search_total_ht='.urlencode($search_total_ht);
}
if ($search_total_tva) {
	$param .= '&amp;search_total_tva='.urlencode($search_total_tva);
}
if ($search_total_ttc) {
	$param .= '&amp;search_total_ttc='.urlencode($search_total_ttc);
}
if ($search_service) {
	$param .= '&amp;search_service='.urlencode($search_service);
}
if ($search_status) {
	$param .= '&amp;search_status='.urlencode($search_status);
}
if (!empty($filter_opouvertureprevue) && $filter_opouvertureprevue != -1) {
	$param .= '&amp;filter_opouvertureprevue='.urlencode($filter_opouvertureprevue);
}
if (!empty($filter_op1) && $filter_op1 != -1) {
	$param .= '&amp;filter_op1='.urlencode($filter_op1);
}
if (!empty($filter_op2) && $filter_op2 != -1) {
	$param .= '&amp;filter_op2='.urlencode($filter_op2);
}
if (!empty($filter_opcloture) && $filter_opcloture != -1) {
	$param .= '&amp;filter_opcloture='.urlencode($filter_opcloture);
}
if ($filter_dateouvertureprevue_start != '') {
	$param .= '&amp;opouvertureprevueday='.((int) $opouvertureprevueday).'&amp;opouvertureprevuemonth='.((int) $opouvertureprevuemonth).'&amp;opouvertureprevueyear='.((int) $opouvertureprevueyear);
}
if ($filter_date1_start != '') {
	$param .= '&amp;op1day='.((int) $op1day).'&amp;op1month='.((int) $op1month).'&amp;op1year='.((int) $op1year);
}
if ($filter_date2_start != '') {
	$param .= '&amp;op2day='.((int) $op2day).'&amp;op2month='.((int) $op2month).'&amp;op2year='.((int) $op2year);
}
if ($filter_datecloture_start != '') {
	$param .= '&amp;opclotureday='.((int) $op2day).'&amp;opcloturemonth='.((int) $op2month).'&amp;opclotureyear='.((int) $op2year);
}
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// List of mass actions available
$arrayofmassactions = array(
	//'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
	//'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
);
//if ($user->hasRight('contrat', 'supprimer')) $arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
//if (in_array($massaction, array('presend','predelete'))) $arrayofmassactions=array();
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
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

$newcardbutton = '';


print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'contract', 0, '', '', $limit);

if (!empty($sall)) {
	$fieldstosearchall = array();
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
	}
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall).implode(', ', $fieldstosearchall).'</div>';
}

$morefilter = '';
$moreforfilter = '';

// If the user can view categories of products
if (isModEnabled('category') && ($user->hasRight('produit', 'lire') || $user->hasRight('service', 'lire'))) {
	include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('IncludingProductWithTag');
	$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, null, 'parent', null, null, 1);
	$moreforfilter .= img_picto($tmptitle, 'category', 'class="pictofixedwidth"').$form->selectarray('search_product_category', $cate_arbo, $search_product_category, $tmptitle, 0, 0, '', 0, 0, 0, 0, 'widthcentpercentminusx maxwidth300', 1);
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
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	print '</div>';
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$htmlofselectarray = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN'));  // This also change content of $arrayfields with user setup
$selectedfields = ($mode != 'kanban' ? $htmlofselectarray : '');
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');


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
if (!empty($arrayfields['c.ref']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="hidden" name="mode" value="'.$mode.'">';
	print '<input type="text" class="flat maxwidth75" name="search_contract" value="'.dol_escape_htmltag($search_contract).'">';
	print '</td>';
}
// Service label
if (!empty($arrayfields['p.description']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat maxwidth100" name="search_service" value="'.dol_escape_htmltag($search_service).'">';
	print '</td>';
}
// detail lines
if (!empty($arrayfields['cd.tva_tx']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
if (!empty($arrayfields['cd.subprice']['checked'])) {
	print '<td class="liste_titre right">';
	print '<input type="text" class="flat maxwidth50 right" name="search_subprice" value="'.dol_escape_htmltag($search_subprice).'">';
	print '</td>';
}
if (!empty($arrayfields['cd.qty']['checked'])) {
	print '<td class="liste_titre right">';
	print '<input type="text" class="flat maxwidth50 right" name="search_qty" value="'.dol_escape_htmltag($search_qty).'">';
	print '</td>';
}
if (!empty($arrayfields['cd.total_ht']['checked'])) {
	print '<td class="liste_titre right">';
	print '<input type="text" class="flat maxwidth50" name="search_total_ht" value="'.dol_escape_htmltag($search_total_ht).'">';
	print '</td>';
}
if (!empty($arrayfields['cd.total_tva']['checked'])) {
	print '<td class="liste_titre right">';
	print '<input type="text" class="flat maxwidth50" name="search_total_tva" value="'.dol_escape_htmltag($search_total_tva).'">';
	print '</td>';
}
// Third party
if (!empty($arrayfields['s.nom']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat maxwidth100" name="search_name" value="'.dol_escape_htmltag($search_name).'">';
	print '</td>';
}

if (!empty($arrayfields['cd.date_ouverture_prevue']['checked'])) {
	print '<td class="liste_titre center">';
	$arrayofoperators = array('<' => '<', '>' => '>');
	print $form->selectarray('filter_opouvertureprevue', $arrayofoperators, $filter_opouvertureprevue, 1, 0, 0, '', 0, 0, 0, '', 'width50');
	print ' ';
	$filter_dateouvertureprevue = dol_mktime(0, 0, 0, $opouvertureprevuemonth, $opouvertureprevueday, $opouvertureprevueyear);
	print $form->selectDate($filter_dateouvertureprevue, 'opouvertureprevue', 0, 0, 1, '', 1, 0);
	print '</td>';
}
if (!empty($arrayfields['cd.date_ouverture']['checked'])) {
	print '<td class="liste_titre center">';
	$arrayofoperators = array('<' => '<', '>' => '>');
	print $form->selectarray('filter_op1', $arrayofoperators, $filter_op1, 1, 0, 0, '', 0, 0, 0, '', 'width50');
	print ' ';
	$filter_date1 = dol_mktime(0, 0, 0, $op1month, $op1day, $op1year);
	print $form->selectDate($filter_date1, 'op1', 0, 0, 1, '', 1, 0);
	print '</td>';
}
if (!empty($arrayfields['cd.date_fin_validite']['checked'])) {
	print '<td class="liste_titre center">';
	$arrayofoperators = array('<' => '<', '>' => '>');
	print $form->selectarray('filter_op2', $arrayofoperators, $filter_op2, 1, 0, 0, '', 0, 0, 0, '', 'width50');
	print ' ';
	$filter_date2 = dol_mktime(0, 0, 0, $op2month, $op2day, $op2year);
	print $form->selectDate($filter_date2, 'op2', 0, 0, 1, '', 1, 0);
	print '</td>';
}
if (!empty($arrayfields['cd.date_cloture']['checked'])) {
	print '<td class="liste_titre center">';
	$arrayofoperators = array('<' => '<', '>' => '>');
	print $form->selectarray('filter_opcloture', $arrayofoperators, $filter_opcloture, 1, 0, 0, '', 0, 0, 0, '', 'width50');
	print ' ';
	$filter_date_cloture = dol_mktime(0, 0, 0, $opcloturemonth, $opclotureday, $opclotureyear);
	print $form->selectDate($filter_date_cloture, 'opcloture', 0, 0, 1, '', 1, 0);
	print '</td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields' => $arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!empty($arrayfields['cd.datec']['checked'])) {
	// Date creation
	print '<td class="liste_titre">';
	print '</td>';
}
if (!empty($arrayfields['cd.tms']['checked'])) {
	// Date modification
	print '<td class="liste_titre">';
	print '</td>';
}
if (!empty($arrayfields['status']['checked'])) {
	// Status
	print '<td class="liste_titre right parentonrightofpage">';
	$arrayofstatus = array(
		'0' => $langs->trans("ServiceStatusInitial"),
		'4' => $langs->trans("ServiceStatusRunning"),
		'4&filter=notexpired' => $langs->trans("ServiceStatusNotLate"),
		'4&filter=expired' => $langs->trans("ServiceStatusLate"),
		'5' => $langs->trans("ServiceStatusClosed")
	);
	$search_status_new = GETPOST('search_status', 'alpha');
	if ($filter == 'expired' && !preg_match('/expired/', $search_status_new)) {
		$search_status_new .= '&filter=expired';
	}
	print $form->selectarray('search_status', $arrayofstatus, (strstr($search_status_new, ',') ? -1 : $search_status_new), 1, 0, 0, '', 0, 0, 0, '', 'search_status width100 onrightofpage');
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
if (!empty($arrayfields['c.ref']['checked'])) {
	print_liste_field_titre($arrayfields['c.ref']['label'], $_SERVER["PHP_SELF"], "c.ref", "", $param, "", $sortfield, $sortorder);
}
if (!empty($arrayfields['p.description']['checked'])) {
	print_liste_field_titre($arrayfields['p.description']['label'], $_SERVER["PHP_SELF"], "p.description", "", $param, "", $sortfield, $sortorder);
}
if (!empty($arrayfields['cd.tva_tx']['checked'])) {
	print_liste_field_titre($arrayfields['cd.tva_tx']['label'], $_SERVER["PHP_SELF"], "cd.tva_tx", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
}
if (!empty($arrayfields['cd.subprice']['checked'])) {
	print_liste_field_titre($arrayfields['cd.subprice']['label'], $_SERVER["PHP_SELF"], "cd.subprice", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
}
if (!empty($arrayfields['cd.qty']['checked'])) {
	print_liste_field_titre($arrayfields['cd.qty']['label'], $_SERVER["PHP_SELF"], "cd.qty", "", $param, '', $sortfield, $sortorder, 'right nowrap ');
}
if (!empty($arrayfields['cd.total_ht']['checked'])) {
	print_liste_field_titre($arrayfields['cd.total_ht']['label'], $_SERVER["PHP_SELF"], "cd.total_ht", "", $param, '', $sortfield, $sortorder, 'right nowrap ');
}
if (!empty($arrayfields['cd.total_tva']['checked'])) {
	print_liste_field_titre($arrayfields['cd.total_tva']['label'], $_SERVER["PHP_SELF"], "cd.total_tva", "", $param, '', $sortfield, $sortorder, 'right nowrap ');
}
if (!empty($arrayfields['s.nom']['checked'])) {
	print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], "s.nom", "", $param, "", $sortfield, $sortorder);
}
if (!empty($arrayfields['cd.date_ouverture_prevue']['checked'])) {
	print_liste_field_titre($arrayfields['cd.date_ouverture_prevue']['label'], $_SERVER["PHP_SELF"], "cd.date_ouverture_prevue", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['cd.date_ouverture']['checked'])) {
	print_liste_field_titre($arrayfields['cd.date_ouverture']['label'], $_SERVER["PHP_SELF"], "cd.date_ouverture", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['cd.date_fin_validite']['checked'])) {
	print_liste_field_titre($arrayfields['cd.date_fin_validite']['label'], $_SERVER["PHP_SELF"], "cd.date_fin_validite", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['cd.date_cloture']['checked'])) {
	print_liste_field_titre($arrayfields['cd.date_cloture']['label'], $_SERVER["PHP_SELF"], "cd.date_cloture", "", $param, '', $sortfield, $sortorder, 'center ');
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!empty($arrayfields['cd.datec']['checked'])) {
	print_liste_field_titre($arrayfields['cd.datec']['label'], $_SERVER["PHP_SELF"], "cd.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
}
if (!empty($arrayfields['cd.tms']['checked'])) {
	print_liste_field_titre($arrayfields['cd.tms']['label'], $_SERVER["PHP_SELF"], "cd.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
}
if (!empty($arrayfields['status']['checked'])) {
	print_liste_field_titre($arrayfields['status']['label'], $_SERVER["PHP_SELF"], "cd.statut,c.statut", "", $param, '', $sortfield, $sortorder, 'right ');
}
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
print '</tr>'."\n";


// Loop on record
// --------------------------------------------------------------------

$contractstatic = new Contrat($db);
$productstatic = new Product($db);

$i = 0;
$savnbfield = $totalarray['nbfield'];
$totalarray = array('nbfield' => 0, 'val' => array('cd.qty' => 0, 'cd.total_ht' => 0, 'cd.total_tva' => 0));
$imaxinloop = ($limit ? min($num, $limit) : $num);
while ($i < $imaxinloop) {
	$obj = $db->fetch_object($resql);
	if (empty($obj)) {
		break; // Should not happen
	}

	$contractstatic->id = $obj->cid;
	$contractstatic->ref = $obj->ref ? $obj->ref : $obj->cid;
	$contractstatic->ref_customer = $obj->ref_customer;
	$contractstatic->ref_supplier = $obj->ref_supplier;

	$companystatic->id = $obj->socid;
	$companystatic->name = $obj->name;
	$companystatic->email = $obj->email;
	$companystatic->client = $obj->client;
	$companystatic->fournisseur = $obj->fournisseur;

	$productstatic->id = $obj->pid;
	$productstatic->type = $obj->ptype;
	$productstatic->ref = $obj->pref;
	$productstatic->entity = $obj->pentity;
	$productstatic->status = $obj->tosell;
	$productstatic->status_buy = $obj->tobuy;
	$productstatic->label = $obj->label;
	$productstatic->description = $obj->description;
	$productstatic->barcode = $obj->barcode;

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
		print $contractstatic->getNomUrl(1, 16);
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Service
	if (!empty($arrayfields['p.description']['checked'])) {
		print '<td class="tdoverflowmax300">';
		if ($obj->pid > 0) {
			print $productstatic->getNomUrl(1, '', 24);
			print $obj->label ? ' - '.dol_trunc($obj->label, 16) : '';
			if (!empty($obj->description) && getDolGlobalString('PRODUCT_DESC_IN_LIST')) {
				print '<br><span class="small">'.dol_nl2br($obj->description).'</span>';
			}
		} else {
			if ($obj->type == 0) {
				print img_object($obj->description, 'product').' '.dol_trunc($obj->description, 24);
			}
			if ($obj->type == 1) {
				print img_object($obj->description, 'service').' '.dol_trunc($obj->description, 24);
			}
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['cd.tva_tx']['checked'])) {
		print '<td class="right nowraponall">';
		print price2num($obj->tva_tx).'%';
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	if (!empty($arrayfields['cd.subprice']['checked'])) {
		print '<td class="right nowraponall">';
		print price($obj->subprice);
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	if (!empty($arrayfields['cd.qty']['checked'])) {
		print '<td class="right nowraponall">';
		print $obj->qty;
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
		if (!$i) {
			$totalarray['pos'][$totalarray['nbfield']] = 'cd.qty';
		}
		$totalarray['val']['cd.qty'] += $obj->qty;
	}
	if (!empty($arrayfields['cd.total_ht']['checked'])) {
		print '<td class="right nowraponall">';
		print '<span class="amount">'.price($obj->total_ht).'</span>';
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
		if (!$i) {
			$totalarray['pos'][$totalarray['nbfield']] = 'cd.total_ht';
		}
		$totalarray['val']['cd.total_ht'] += $obj->total_ht;
	}
	if (!empty($arrayfields['cd.total_tva']['checked'])) {
		print '<td class="right nowraponall">';
		print '<span class="amount">'.price($obj->total_tva).'</span>';
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
		if (!$i) {
			$totalarray['pos'][$totalarray['nbfield']] = 'cd.total_tva';
		}
		$totalarray['val']['cd.total_tva'] += $obj->total_tva;
	}

	// Third party
	if (!empty($arrayfields['s.nom']['checked'])) {
		print '<td class="tdoverflowmax100">';
		print $companystatic->getNomUrl(1, 'customer', 28);
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Start date
	if (!empty($arrayfields['cd.date_ouverture_prevue']['checked'])) {
		print '<td class="center nowraponall">';
		print($obj->date_ouverture_prevue ? dol_print_date($db->jdate($obj->date_ouverture_prevue), 'dayhour') : '&nbsp;');
		if ($db->jdate($obj->date_ouverture_prevue) && ($db->jdate($obj->date_ouverture_prevue) < ($now - $conf->contrat->services->inactifs->warning_delay)) && $obj->statut == 0) {
			print ' '.img_picto($langs->trans("Late"), "warning");
		} else {
			print '&nbsp;&nbsp;&nbsp;&nbsp;';
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	if (!empty($arrayfields['cd.date_ouverture']['checked'])) {
		print '<td class="center nowraponall">'.($obj->date_ouverture ? dol_print_date($db->jdate($obj->date_ouverture), 'dayhour') : '&nbsp;').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// End date
	if (!empty($arrayfields['cd.date_fin_validite']['checked'])) {
		print '<td class="center nowraponall">'.($obj->date_fin_validite ? dol_print_date($db->jdate($obj->date_fin_validite), 'dayhour') : '&nbsp;');
		if ($obj->date_fin_validite && $db->jdate($obj->date_fin_validite) < ($now - $conf->contrat->services->expires->warning_delay) && $obj->statut < 5) {
			$warning_delay = $conf->contrat->services->expires->warning_delay / 3600 / 24;
			$textlate = $langs->trans("Late").' = '.$langs->trans("DateReference").' > '.$langs->trans("DateToday").' '.(ceil($warning_delay) >= 0 ? '+' : '').ceil($warning_delay).' '.$langs->trans("days");
			print img_warning($textlate);
		} else {
			print '&nbsp;&nbsp;&nbsp;&nbsp;';
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Close date (real end date)
	if (!empty($arrayfields['cd.date_cloture']['checked'])) {
		print '<td class="center nowraponall">'.dol_print_date($db->jdate($obj->date_cloture), 'dayhour').'</td>';
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
	if (!empty($arrayfields['cd.datec']['checked'])) {
		print '<td class="center">';
		print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Date modification
	if (!empty($arrayfields['cd.tms']['checked'])) {
		print '<td class="center nowraponall">';
		print dol_print_date($db->jdate($obj->date_modification), 'dayhour', 'tzuser');
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Status
	if (!empty($arrayfields['status']['checked'])) {
		print '<td class="right">';
		if ($obj->cstatut == 0) {
			// If contract is draft, we say line is also draft
			//print $contractstatic->LibStatut(0, 5);
			print $staticcontratligne->LibStatut($obj->statut, 5, ($obj->date_fin_validite && $db->jdate($obj->date_fin_validite) < $now) ? 1 : 0, '', ' - '.$langs->trans("Draft"));
		} else {
			print $staticcontratligne->LibStatut($obj->statut, 5, ($obj->date_fin_validite && $db->jdate($obj->date_fin_validite) < $now) ? 1 : 0);
		}
		print '</td>';
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


// End of page
llxFooter();
$db->close();
