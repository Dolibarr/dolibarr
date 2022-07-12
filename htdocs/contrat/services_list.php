<?php
/* Copyright (C) 2001-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2018       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2019      Juanjo Menent		<jmenent@2byte.es>
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

$optioncss = GETPOST('optioncss', 'aZ09');

$massaction = GETPOST('massaction', 'alpha');
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = "c.rowid";
}
if (!$sortorder) {
	$sortorder = "ASC";
}

$mode = GETPOST("mode");
$filter = GETPOST("filter");
$search_name = GETPOST("search_name", 'alpha');
$search_contract = GETPOST("search_contract", 'alpha');
$search_service = GETPOST("search_service", 'alpha');
$search_status = GETPOST("search_status", 'alpha');
$search_product_category = GETPOST('search_product_category', 'int');
$socid = GETPOST('socid', 'int');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'contractservicelist'.$mode;

$opouvertureprevuemonth = GETPOST('opouvertureprevuemonth');
$opouvertureprevueday = GETPOST('opouvertureprevueday');
$opouvertureprevueyear = GETPOST('opouvertureprevueyear');
$filter_opouvertureprevue = GETPOST('filter_opouvertureprevue');

$op1month = GETPOST('op1month', 'int');
$op1day = GETPOST('op1day', 'int');
$op1year = GETPOST('op1year', 'int');
$filter_op1 = GETPOST('filter_op1', 'alpha');

$op2month = GETPOST('op2month', 'int');
$op2day = GETPOST('op2day', 'int');
$op2year = GETPOST('op2year', 'int');
$filter_op2 = GETPOST('filter_op2', 'alpha');

$opcloturemonth = GETPOST('opcloturemonth', 'int');
$opclotureday = GETPOST('opclotureday', 'int');
$opclotureyear = GETPOST('opclotureyear', 'int');
$filter_opcloture = GETPOST('filter_opcloture', 'alpha');


// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new ContratLigne($db);
$hookmanager->initHooks(array('contractservicelist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Security check
$contratid = GETPOST('id', 'int');
if (!empty($user->socid)) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'contrat', $contratid);

if ($search_status != '') {
	$tmp = explode('&', $search_status);
	$mode = $tmp[0];
	if (empty($tmp[1])) {
		$filter = '';
	} else {
		if ($tmp[1] == 'filter=notexpired') {
			$filter = 'notexpired';
		}
		if ($tmp[1] == 'filter=expired') {
			$filter = 'expired';
		}
	}
} else {
	$search_status = $mode;
	if ($filter == 'expired') {
		$search_status .= '&filter=expired';
	}
	if ($filter == 'notexpired') {
		$search_status .= '&filter=notexpired';
	}
}

$staticcontrat = new Contrat($db);
$staticcontratligne = new ContratLigne($db);
$companystatic = new Societe($db);

$arrayfields = array(
	'c.ref'=>array('label'=>"Contract", 'checked'=>1, 'position'=>80),
	'p.description'=>array('label'=>"Service", 'checked'=>1, 'position'=>80),
	's.nom'=>array('label'=>"ThirdParty", 'checked'=>1, 'position'=>90),
	'cd.tva_tx'=>array('label'=>"VATRate", 'checked'=>-1, 'position'=>100),
	'cd.subprice'=>array('label'=>"PriceUHT", 'checked'=>-1, 'position'=>105),
	'cd.qty'=>array('label'=>"Qty", 'checked'=>-1, 'position'=>108),
	'cd.total_ht'=>array('label'=>"TotalHT", 'checked'=>-1, 'position'=>109),
	'cd.total_tva'=>array('label'=>"TotalVAT", 'checked'=>-1, 'position'=>110),
	'cd.date_ouverture_prevue'=>array('label'=>"DateStartPlannedShort", 'checked'=>(($mode == "" || $mode == -1) || $mode == "0"), 'position'=>150),
	'cd.date_ouverture'=>array('label'=>"DateStartRealShort", 'checked'=>(($mode == "" || $mode == -1) || $mode > 0), 'position'=>160),
	'cd.date_fin_validite'=>array('label'=>"DateEndPlannedShort", 'checked'=>(($mode == "" || $mode == -1) || $mode < 5), 'position'=>170),
	'cd.date_cloture'=>array('label'=>"DateEndRealShort", 'checked'=>(($mode == "" || $mode == -1) || $mode >= 5), 'position'=>180),
	//'cd.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
	'cd.tms'=>array('label'=>"DateModificationShort", 'checked'=>0, 'position'=>500),
	'status'=>array('label'=>"Status", 'checked'=>1, 'position'=>1000)
);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');




/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list'; $massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

$parameters = array('socid'=>$socid);
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
		$search_contract = "";
		$search_service = "";
		$search_status = -1;
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
		$mode = '';
		$filter = '';
		$toselect = array();
		$search_array_options = array();
	}
}


/*
 * View
 */

$now = dol_now();

$form = new Form($db);

$sql = "SELECT c.rowid as cid, c.ref, c.statut as cstatut, c.ref_customer, c.ref_supplier,";
$sql .= " s.rowid as socid, s.nom as name, s.email, s.client, s.fournisseur,";
$sql .= " cd.rowid, cd.description, cd.statut,";
$sql .= " p.rowid as pid, p.ref as pref, p.label as label, p.fk_product_type as ptype, p.tobuy, p.tosell, p.barcode, p.entity as pentity,";
if (empty($user->rights->societe->client->voir) && !$socid) {
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
$sql .= " cd.tms as date_update";
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
$sql .= " FROM ".MAIN_DB_PREFIX."contrat as c,";
$sql .= " ".MAIN_DB_PREFIX."societe as s,";
if (empty($user->rights->societe->client->voir) && !$socid) {
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
$sql .= " WHERE c.entity = ".$conf->entity;
$sql .= " AND c.rowid = cd.fk_contrat";
if ($search_product_category > 0) {
	$sql .= " AND cp.fk_categorie = ".((int) $search_product_category);
}
$sql .= " AND c.fk_soc = s.rowid";
if (empty($user->rights->societe->client->voir) && !$socid) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}
if ($mode == "0") {
	$sql .= " AND cd.statut = 0";
}
if ($mode == "4") {
	$sql .= " AND cd.statut = 4";
}
if ($mode == "5") {
	$sql .= " AND cd.statut = 5";
}
if ($filter == "expired") {
	$sql .= " AND cd.date_fin_validite < '".$db->idate($now)."'";
}
if ($filter == "notexpired") {
	$sql .= " AND cd.date_fin_validite >= '".$db->idate($now)."'";
}
if ($search_name) {
	$sql .= " AND s.nom LIKE '%".$db->escape($search_name)."%'";
}
if ($search_contract) {
	$sql .= " AND c.ref LIKE '%".$db->escape($search_contract)."%' ";
}
if ($search_service) {
	$sql .= " AND (p.ref LIKE '%".$db->escape($search_service)."%' OR p.description LIKE '%".$db->escape($search_service)."%' OR cd.description LIKE '%".$db->escape($search_service)."%')";
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
$sql .= $db->order($sortfield, $sortorder);

//print $sql;

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
dol_syslog("contrat/services_list.php", LOG_DEBUG);
$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);

/*
if ($num == 1 && ! empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all)
{
	$obj = $db->fetch_object($resql);
	$id = $obj->id;
	header("Location: ".DOL_URL_ROOT.'/projet/tasks/task.php?id='.$id.'&withprojet=1');
	exit;
}*/

llxHeader(null, $langs->trans("Services"));

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.$limit;
}
if ($search_contract) {
	$param .= '&amp;search_contract='.urlencode($search_contract);
}
if ($search_name) {
	$param .= '&amp;search_name='.urlencode($search_name);
}
if ($search_service) {
	$param .= '&amp;search_service='.urlencode($search_service);
}
if ($mode) {
	$param .= '&amp;mode='.urlencode($mode);
}
if ($filter) {
	$param .= '&amp;filter='.urlencode($filter);
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

if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// List of mass actions available
$arrayofmassactions = array(
	//'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
	//'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
);
//if ($user->rights->contrat->supprimer) $arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
//if (in_array($massaction, array('presend','predelete'))) $arrayofmassactions=array();
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
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

$title = $langs->trans("ListOfServices");
if ($mode == "0") {
	$title = $langs->trans("ListOfInactiveServices"); // Must use == "0"
}
if ($mode == "4" && $filter != "expired") {
	$title = $langs->trans("ListOfRunningServices");
}
if ($mode == "4" && $filter == "expired") {
	$title = $langs->trans("ListOfExpiredServices");
}
if ($mode == "5") {
	$title = $langs->trans("ListOfClosedServices");
}

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'contract', 0, '', '', $limit);

if (!empty($sall)) {
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
	}
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall).join(', ', $fieldstosearchall).'</div>';
}

$morefilter = '';
$moreforfilter = '';

// If the user can view categories of products
if (isModEnabled('categorie') && ($user->rights->produit->lire || $user->rights->service->lire)) {
	include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('IncludingProductWithTag');
	$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, null, 'parent', null, null, 1);
	$moreforfilter .= img_picto($tmptitle, 'category', 'class="pictofixedwidth"').$form->selectarray('search_product_category', $cate_arbo, $search_product_category, $tmptitle, 0, 0, '', 0, 0, 0, 0, 'widthcentpercentminusx maxwidth300', 1);
	$moreforfilter .= '</div>';
}

$parameters = array();
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

print '<tr class="liste_titre">';
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
	print_liste_field_titre($arrayfields['cd.qty']['label'], $_SERVER["PHP_SELF"], "cd.qty", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
}
if (!empty($arrayfields['cd.total_ht']['checked'])) {
	print_liste_field_titre($arrayfields['cd.total_ht']['label'], $_SERVER["PHP_SELF"], "cd.total_ht", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
}
if (!empty($arrayfields['cd.total_tva']['checked'])) {
	print_liste_field_titre($arrayfields['cd.total_tva']['label'], $_SERVER["PHP_SELF"], "cd.total_tva", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
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
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
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
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
print "</tr>\n";

print '<tr class="liste_titre">';
if (!empty($arrayfields['c.ref']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="hidden" name="filter" value="'.$filter.'">';
	print '<input type="hidden" name="mode" value="'.$mode.'">';
	print '<input type="text" class="flat" size="3" name="search_contract" value="'.dol_escape_htmltag($search_contract).'">';
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
	print '<td class="liste_titre">';
	print '</td>';
}
if (!empty($arrayfields['cd.qty']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
if (!empty($arrayfields['cd.total_ht']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
if (!empty($arrayfields['cd.total_tva']['checked'])) {
	print '<td class="liste_titre">';
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
	$arrayofoperators = array('<'=>'<', '>'=>'>');
	print $form->selectarray('filter_opouvertureprevue', $arrayofoperators, $filter_opouvertureprevue, 1, 0, 0, '', 0, 0, 0, '', 'width50');
	print ' ';
	$filter_dateouvertureprevue = dol_mktime(0, 0, 0, $opouvertureprevuemonth, $opouvertureprevueday, $opouvertureprevueyear);
	print $form->selectDate($filter_dateouvertureprevue, 'opouvertureprevue', 0, 0, 1, '', 1, 0);
	print '</td>';
}
if (!empty($arrayfields['cd.date_ouverture']['checked'])) {
	print '<td class="liste_titre center">';
	$arrayofoperators = array('<'=>'<', '>'=>'>');
	print $form->selectarray('filter_op1', $arrayofoperators, $filter_op1, 1, 0, 0, '', 0, 0, 0, '', 'width50');
	print ' ';
	$filter_date1 = dol_mktime(0, 0, 0, $op1month, $op1day, $op1year);
	print $form->selectDate($filter_date1, 'op1', 0, 0, 1, '', 1, 0);
	print '</td>';
}
if (!empty($arrayfields['cd.date_fin_validite']['checked'])) {
	print '<td class="liste_titre center">';
	$arrayofoperators = array('<'=>'<', '>'=>'>');
	print $form->selectarray('filter_op2', $arrayofoperators, $filter_op2, 1, 0, 0, '', 0, 0, 0, '', 'width50');
	print ' ';
	$filter_date2 = dol_mktime(0, 0, 0, $op2month, $op2day, $op2year);
	print $form->selectDate($filter_date2, 'op2', 0, 0, 1, '', 1, 0);
	print '</td>';
}
if (!empty($arrayfields['cd.date_cloture']['checked'])) {
	print '<td class="liste_titre center">';
	$arrayofoperators = array('<'=>'<', '>'=>'>');
	print $form->selectarray('filter_opcloture', $arrayofoperators, $filter_opcloture, 1, 0, 0, '', 0, 0, 0, '', 'width50');
	print ' ';
	$filter_date_cloture = dol_mktime(0, 0, 0, $opcloturemonth, $opclotureday, $opclotureyear);
	print $form->selectDate($filter_date_cloture, 'opcloture', 0, 0, 1, '', 1, 0);
	print '</td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
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
	print '<td class="liste_titre right">';
	$arrayofstatus = array(
		'0'=>$langs->trans("ServiceStatusInitial"),
		'4'=>$langs->trans("ServiceStatusRunning"),
		'4&filter=notexpired'=>$langs->trans("ServiceStatusNotLate"),
		'4&filter=expired'=>$langs->trans("ServiceStatusLate"),
		'5'=>$langs->trans("ServiceStatusClosed")
	);
	print $form->selectarray('search_status', $arrayofstatus, (strstr($search_status, ',') ?-1 : $search_status), 1, 0, 0, '', 0, 0, 0, '', 'minwidth100imp maxwidth150');
	print '</td>';
}
// Action column
print '<td class="liste_titre maxwidthsearch">';
$searchpicto = $form->showFilterAndCheckAddButtons(0);
print $searchpicto;
print '</td>';
print "</tr>\n";

$contractstatic = new Contrat($db);
$productstatic = new Product($db);

$i = 0;
$totalarray = array();
while ($i < min($num, $limit)) {
	$obj = $db->fetch_object($resql);

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

	print '<tr class="oddeven">';

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
			if (!empty($obj->description) && !empty($conf->global->PRODUCT_DESC_IN_LIST)) {
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
		print ($obj->date_ouverture_prevue ?dol_print_date($db->jdate($obj->date_ouverture_prevue), 'dayhour') : '&nbsp;');
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
		print '<td class="center nowraponall">'.($obj->date_ouverture ?dol_print_date($db->jdate($obj->date_ouverture), 'dayhour') : '&nbsp;').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// End date
	if (!empty($arrayfields['cd.date_fin_validite']['checked'])) {
		print '<td class="center nowraponall">'.($obj->date_fin_validite ?dol_print_date($db->jdate($obj->date_fin_validite), 'dayhour') : '&nbsp;');
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
	$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
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
		print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
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
			print $contractstatic->LibStatut(0, 5);
		} else {
			print $staticcontratligne->LibStatut($obj->statut, 5, ($obj->date_fin_validite && $db->jdate($obj->date_fin_validite) < $now) ? 1 : 0);
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Action column
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

	print "</tr>\n";
	$i++;
}

// Show total line
include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

$db->free($resql);

$parameters = array('sql' => $sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>';
print '</div>';

print '</form>';



llxFooter();

$db->close();
