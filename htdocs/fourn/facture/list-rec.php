<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2013      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Jean-François Ferry  <jfefe@aternatik.fr>
 * Copyright (C) 2012      Cedric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015-2021 Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2016      Meziane Sof          <virtualsof@yahoo.fr>
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
 *	\file       htdocs/fourn/facture/list-rec.php
 *	\ingroup    facture
 *	\brief      Page to show list of template/recurring invoices
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture-rec.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

// Load translation files required by the page
$langs->loadLangs(array('bills', 'compta', 'admin', 'other', 'suppliers'));

$action     = GETPOST('action', 'alpha');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm    = GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'alpha');
$toselect   = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'supplierinvoicestemplatelist'; // To manage different context of search
$optioncss  = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')
$mode       = GETPOST('mode', 'aZ'); // The output mode ('list', 'kanban', 'hierarchy', 'calendar', ...)

$socid = GETPOST('socid', 'int');

$id = (GETPOST('facid', 'int') ?GETPOST('facid', 'int') : GETPOST('id', 'int'));
$lineid = GETPOST('lineid', 'int');
$ref = GETPOST('ref', 'alpha');
if ($user->socid) {
	$socid = $user->socid;
}
$objecttype = 'facture_fourn_rec';
if ($action == "create" || $action == "add") {
	$objecttype = '';
}

$search_ref = GETPOST('search_ref');
$search_societe = GETPOST('search_societe');
$search_montant_ht = GETPOST('search_montant_ht');
$search_montant_vat = GETPOST('search_montant_vat');
$search_montant_ttc = GETPOST('search_montant_ttc');
$search_payment_mode = GETPOST('search_payment_mode');
$search_payment_term = GETPOST('search_payment_term');
$search_date_startday = GETPOST('search_date_startday', 'int');
$search_date_startmonth = GETPOST('search_date_startmonth', 'int');
$search_date_startyear = GETPOST('search_date_startyear', 'int');
$search_date_endday = GETPOST('search_date_endday', 'int');
$search_date_endmonth = GETPOST('search_date_endmonth', 'int');
$search_date_endyear = GETPOST('search_date_endyear', 'int');
$search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);	// Use tzserver
$search_date_end = dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);
$search_date_when_startday = GETPOST('search_date_when_startday', 'int');
$search_date_when_startmonth = GETPOST('search_date_when_startmonth', 'int');
$search_date_when_startyear = GETPOST('search_date_when_startyear', 'int');
$search_date_when_endday = GETPOST('search_date_when_endday', 'int');
$search_date_when_endmonth = GETPOST('search_date_when_endmonth', 'int');
$search_date_when_endyear = GETPOST('search_date_when_endyear', 'int');
$search_date_when_start = dol_mktime(0, 0, 0, $search_date_when_startmonth, $search_date_when_startday, $search_date_when_startyear);	// Use tzserver
$search_date_when_end = dol_mktime(23, 59, 59, $search_date_when_endmonth, $search_date_when_endday, $search_date_when_endyear);
$search_recurring = GETPOST('search_recurring', 'int');
$search_frequency = GETPOST('search_frequency', 'alpha');
$search_unit_frequency = GETPOST('search_unit_frequency', 'alpha');
$search_nb_gen_done = GETPOST('search_nb_gen_done', 'aplha');
$search_status = GETPOST('search_status', 'int');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical objects
$object = new FactureFournisseurRec($db);
$extrafields = new ExtraFields($db);

if (($id > 0 || $ref) && $action != 'create' && $action != 'add') {
	$ret = $object->fetch($id, $ref);
	if (!$ret) {
		setEventMessages($langs->trans("ErrorRecordNotFound"), null, 'errors');
	}
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('supplierinvoicereclist'));

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortorder) {
	$sortorder = 'DESC';
}
if (!$sortfield) {
	$sortfield = 'f.titre';
}
$arrayfields = array(
	'f.titre'=>array('label'=>'Ref', 'checked'=>1),
	's.nom'=>array('label'=>'ThirdParty', 'checked'=>1),
	'f.total_ht'=>array('label'=>'AmountHT', 'checked'=>1),
	'f.total_tva'=>array('label'=>'AmountVAT', 'checked'=>1),
	'f.total_ttc'=>array('label'=>'AmountTTC', 'checked'=>1),
	'f.fk_mode_reglement'=>array('label'=>'PaymentMode', 'checked'=>0),
	'f.fk_cond_reglement'=>array('label'=>'PaymentTerm', 'checked'=>0),
	'recurring'=>array('label'=>'RecurringInvoice', 'checked'=>1),
	'f.frequency'=>array('label'=>'Frequency', 'checked'=>1),
	'f.unit_frequency'=>array('label'=>'FrequencyUnit', 'checked'=>1),
	'f.nb_gen_done'=>array('label'=>'NbOfGenerationDoneShort', 'checked'=>1),
	'f.date_last_gen'=>array('label'=>'DateLastGenerationShort', 'checked'=>1),
	'f.date_when'=>array('label'=>'NextDateToExecutionShort', 'checked'=>1),
	'f.fk_user_author'=>array('label'=>'UserCreation', 'checked'=>0, 'position'=>500),
	'f.fk_user_modif'=>array('label'=>'UserModification', 'checked'=>0, 'position'=>505),
	'f.datec'=>array('label'=>'DateCreation', 'checked'=>0, 'position'=>520),
	'f.tms'=>array('label'=>'DateModificationShort', 'checked'=>0, 'position'=>525),
	'status'=>array('label'=>'Status', 'checked'=>1, 'position'=>1000),
);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

if ($socid > 0) {
	$tmpthirdparty = new Societe($db);
	$res = $tmpthirdparty->fetch($socid);
	if ($res > 0) {
		$search_societe = $tmpthirdparty->name;
	}
}
$objecttype = 'facture_fourn_rec';

$permissionnote = $user->hasRight('facture', 'creer'); // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->hasRight('facture', 'creer'); // Used by the include of actions_dellink.inc.php
$permissiontoedit = $user->hasRight('facture', 'creer'); // Used by the include of actions_lineupdonw.inc.php

$result = restrictedArea($user, 'supplier_invoicerec', $object->id, $objecttype);


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
	/*if (GETPOST('cancel', 'alpha')) {
		$action = '';
	}*/

	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$search_ref = '';
		$search_societe = '';
		$search_montant_ht = '';
		$search_montant_vat = '';
		$search_montant_ttc = '';
		$search_payment_mode = '';
		$search_payment_term = '';
		$search_date_startday = '';
		$search_date_startmonth = '';
		$search_date_startyear = '';
		$search_date_endday = '';
		$search_date_endmonth = '';
		$search_date_endyear = '';
		$search_date_start = '';
		$search_date_end = '';
		$search_date_when_startday = '';
		$search_date_when_startmonth = '';
		$search_date_when_startyear = '';
		$search_date_when_endday = '';
		$search_date_when_endmonth = '';
		$search_date_when_endyear = '';
		$search_date_when_start = '';
		$search_date_when_end = '';
		$search_recurring = '';
		$search_frequency = '';
		$search_unit_frequency = '';
		$search_nb_gen_done = '';
		$search_status = '';
		$toselect = array();
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
			$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions
	/*$objectclass='MyObject';
	$objectlabel='MyObject';
	$permissiontoread = $user->hasRight("mymodule", "read");
	$permissiontodelete = $user->hasRight("mymodule", "delete");
	$uploaddir = $conf->mymodule->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';*/
}


/*
 *	View
 */

$form = new Form($db);
$formother = new FormOther($db);
if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}
$companystatic = new Societe($db);
$supplierinvoicerectmp = new FactureFournisseurRec($db);
$tmpuser = new User($db);

$now = dol_now();

$help_url = '';
$title = $langs->trans("RepeatableSupplierInvoices");
$morejs = array();
$morecss = array();

$tmparray = dol_getdate($now);
$today = dol_mktime(23, 59, 59, $tmparray['mon'], $tmparray['mday'], $tmparray['year']); // Today is last second of current day


// Build and execute select
// --------------------------------------------------------------------

$sql = "SELECT s.nom as name, s.rowid as socid, f.rowid as facid, f.titre as title, f.total_ht, f.total_tva, f.total_ttc, f.frequency, f.unit_frequency,";
$sql .= " f.nb_gen_done, f.nb_gen_max, f.date_last_gen, f.date_when, f.suspended,";
$sql .= " f.datec, f.fk_user_author, f.tms, f.fk_user_modif,";
$sql .= " f.fk_cond_reglement, f.fk_mode_reglement";
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key." as options_".$key : '');
	}
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql = preg_replace('/,\s*$/', '', $sql);

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= ' FROM '.MAIN_DB_PREFIX.'societe as s, '.MAIN_DB_PREFIX.'facture_fourn_rec as f';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'facture_fourn_rec_extrafields as ef ON ef.fk_object = f.rowid';
if (!$user->hasRight("societe", "client", "voir") && !$socid) {
	$sql .= ', '.MAIN_DB_PREFIX.'societe_commerciaux as sc';
}
// Add table from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= " WHERE f.fk_soc = s.rowid";
$sql .= ' AND f.entity IN ('.getEntity('invoice').')';
if (!$user->hasRight("societe", "client", "voir") && !$socid) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}
if ($search_ref) {
	$sql .= natural_search('f.titre', $search_ref);
}
if ($socid) {
	$sql .= ' AND s.rowid = '.(int) $socid;
}
if ($search_societe) {
	$sql .= natural_search('s.nom', $search_societe);
}
if ($search_montant_ht != '') {
	$sql .= natural_search('f.total_ht', $search_montant_ht, 1);
}
if ($search_montant_vat != '') {
	$sql .= natural_search('f.total_tva', $search_montant_vat, 1);
}
if ($search_montant_ttc != '') {
	$sql .= natural_search('f.total_ttc', $search_montant_ttc, 1);
}
if (!empty($search_payment_mode) && $search_payment_mode != '-1') {
	$sql .= natural_search('f.fk_mode_reglement', $search_payment_mode, 1);
}
if (!empty($search_payment_term) && $search_payment_term != '-1') {
	$sql .= natural_search('f.fk_cond_reglement', $search_payment_term, 1);
}
if ($search_recurring == '1') {
	$sql .= ' AND f.frequency > 0';
}
if ($search_recurring == '0') {
	$sql .= ' AND (f.frequency IS NULL or f.frequency = 0)';
}
if ($search_frequency != '') {
	$sql .= natural_search('f.frequency', $search_frequency, 1);
}
if ($search_unit_frequency != '') {
	$sql .= ' AND f.frequency > 0';
	$sql .= natural_search('f.unit_frequency', $search_unit_frequency);
}
if ($search_nb_gen_done != '') {
	$sql .= natural_search("f.nb_gen_done", $search_nb_gen_done, 1);
}
if ($search_status != '' && $search_status >= -1) {
	if ($search_status == 0) {
		$sql .= ' AND frequency = 0 AND suspended = 0';
	}
	if ($search_status == 1) {
		$sql .= ' AND frequency != 0 AND suspended = 0';
	}
	if ($search_status == -1) {
		$sql .= ' AND suspended = 1';
	}
}
if ($search_date_start) {
	$sql .= " AND f.date_last_gen >= '".$db->idate($search_date_start)."'";
}
if ($search_date_end) {
	$sql .= " AND f.date_last_gen <= '".$db->idate($search_date_end)."'";
}
if ($search_date_when_start) {
	$sql .= " AND f.date_when >= '".$db->idate($search_date_when_start)."'";
}
if ($search_date_when_end) {
	$sql .= " AND f.date_when <= '".$db->idate($search_date_when_end)."'";
}

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

	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
	$db->free($resql);
}

$tmpsortfield = $sortfield;
if ($tmpsortfield == 'recurring') {
	$tmpsortfield = 'f.frequency';
}

// Complete request and execute it with limit
$sql .= $db->order($tmpsortfield, $sortorder);
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);

// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss, '', 'bodyforlist');

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
if ($socid > 0) {
	$param .= '&socid='.urlencode($socid);
}
if ($search_date_startday) {
	$param .= '&search_date_startday='.urlencode($search_date_startday);
}
if ($search_date_startmonth) {
	$param .= '&search_date_startmonth='.urlencode($search_date_startmonth);
}
if ($search_date_startyear) {
	$param .= '&search_date_startyear='.urlencode($search_date_startyear);
}
if ($search_date_endday) {
	$param .= '&search_date_endday='.urlencode($search_date_endday);
}
if ($search_date_endmonth) {
	$param .= '&search_date_endmonth='.urlencode($search_date_endmonth);
}
if ($search_date_endyear) {
	$param .= '&search_date_endyear='.urlencode($search_date_endyear);
}
if ($search_date_when_startday) {
	$param .= '&search_date_when_startday='.urlencode($search_date_when_startday);
}
if ($search_date_when_startmonth) {
	$param .= '&search_date_when_startmonth='.urlencode($search_date_when_startmonth);
}
if ($search_date_when_startyear) {
	$param .= '&search_date_when_startyear='.urlencode($search_date_when_startyear);
}
if ($search_date_when_endday) {
	$param .= '&search_date_when_endday='.urlencode($search_date_when_endday);
}
if ($search_date_when_endmonth) {
	$param .= '&search_date_when_endmonth='.urlencode($search_date_when_endmonth);
}
if ($search_date_when_endyear) {
	$param .= '&search_date_when_endyear='.urlencode($search_date_when_endyear);
}
if ($search_ref) {
	$param .= '&search_ref='.urlencode($search_ref);
}
if ($search_societe) {
	$param .= '&search_societe='.urlencode($search_societe);
}
if ($search_montant_ht != '') {
	$param .= '&search_montant_ht='.urlencode($search_montant_ht);
}
if ($search_montant_vat != '') {
	$param .= '&search_montant_vat='.urlencode($search_montant_vat);
}
if ($search_montant_ttc != '') {
	$param .= '&search_montant_ttc='.urlencode($search_montant_ttc);
}
if ($search_payment_mode != '') {
	$param .= '&search_payment_mode='.urlencode($search_payment_mode);
}
if ($search_payment_term != '') {
	$param .= '&search_payment_term='.urlencode($search_payment_term);
}
if ($search_recurring != '' && $search_recurring != '-1') {
	$param .= '&search_recurring='.urlencode($search_recurring);
}
if ($search_frequency > 0) {
	$param .= '&search_frequency='.urlencode($search_frequency);
}
if ($search_unit_frequency != '') {
	$param .= '&search_unit_frequency='.urlencode($search_unit_frequency);
}
if ($search_nb_gen_done != '') {
	$param .= '&search_nb_gen_done='.urlencode($search_nb_gen_done);
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
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object); // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;

// List of mass actions available
$arrayofmassactions = array(
	//'validate'=>img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("Validate"),
	//'generate_doc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
	//'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
	//'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
);

$massactionbutton = $form->selectMassAction('', $massaction == 'presend' ? array() : array('presend'=>$langs->trans("SendByMail"), 'builddoc'=>$langs->trans("PDFMerge")));

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = ($mode != 'kanban' ? $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN', '')) : ''); // This also change content of $arrayfields
//$selectedfields.=$form->showCheckAddButtons('checkforselect', 1);

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
print '<input type="hidden" name="search_status" value="'.$search_status.'">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="mode" value="'.$mode.'">';


print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'bill', 0, '', '', $limit, 0, 0, 1);

print '<span class="opacitymedium">'.$langs->trans("ToCreateAPredefinedSupplierInvoice", $langs->transnoentitiesnoconv("ChangeIntoRepeatableInvoice")).'</span><br><br>';

$i = 0;

$moreforfilter = '';

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
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN', '')); // This also change content of $arrayfields
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

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
// Ref
if (!empty($arrayfields['f.titre']['checked'])) {
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth100" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
	print '</td>';
}
// Thirdparty
if (!empty($arrayfields['s.nom']['checked'])) {
	print '<td class="liste_titre left"><input class="flat" type="text" size="8" name="search_societe" value="'.dol_escape_htmltag($search_societe).'"></td>';
}
if (!empty($arrayfields['f.total_ht']['checked'])) {
	// Amount net
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="5" name="search_montant_ht" value="'.dol_escape_htmltag($search_montant_ht).'">';
	print '</td>';
}
if (!empty($arrayfields['f.total_tva']['checked'])) {
	// Amount Vat
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="5" name="search_montant_vat" value="'.dol_escape_htmltag($search_montant_vat).'">';
	print '</td>';
}
if (!empty($arrayfields['f.total_ttc']['checked'])) {
	// Amount
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="5" name="search_montant_ttc" value="'.dol_escape_htmltag($search_montant_ttc).'">';
	print '</td>';
}
if (!empty($arrayfields['f.fk_cond_reglement']['checked'])) {
	// Payment term
	print '<td class="liste_titre">';
	print $form->getSelectConditionsPaiements($search_payment_term, 'search_payment_term', -1, 1, 1, 'maxwidth100');
	print "</td>";
}
if (!empty($arrayfields['f.fk_mode_reglement']['checked'])) {
	// Payment mode
	print '<td class="liste_titre">';
	print $form->select_types_paiements($search_payment_mode, 'search_payment_mode', '', 0, 1, 1, 0, 1, 'maxwidth100', 1);
	print '</td>';
}
if (!empty($arrayfields['recurring']['checked'])) {
	// Recurring or not
	print '<td class="liste_titre center">';
	print $form->selectyesno('search_recurring', $search_recurring, 1, false, 1);
	print '</td>';
}
if (!empty($arrayfields['f.frequency']['checked'])) {
	// Recurring or not
	print '<td class="liste_titre center">';
	print '<input class="flat" type="text" size="1" name="search_frequency" value="'.dol_escape_htmltag($search_frequency).'">';
	print '</td>';
}
if (!empty($arrayfields['f.unit_frequency']['checked'])) {
	// Frequency unit
	print '<td class="liste_titre center">';
	print '<input class="flat" type="text" size="1" name="search_unit_frequency" value="'.dol_escape_htmltag($search_unit_frequency).'">';
	print '</td>';
}
if (!empty($arrayfields['f.nb_gen_done']['checked'])) {
	// Nb generation
	print '<td class="liste_titre" align="center">';
	print '<input class="flat" type="text" size="1" name="search_nb_gen_done" value="'.dol_escape_htmltag($search_nb_gen_done).'">';
	print '</td>';
}
// Date invoice
if (!empty($arrayfields['f.date_last_gen']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrap">';
	print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrap">';
	print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
// Date next generation
if (!empty($arrayfields['f.date_when']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrap">';
	print $form->selectDate($search_date_when_start ? $search_date_when_start : -1, 'search_date_when_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrap">';
	print $form->selectDate($search_date_when_end ? $search_date_when_end : -1, 'search_date_when_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// User creation
if (!empty($arrayfields['f.fk_user_author']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
// User modification
if (!empty($arrayfields['f.fk_user_modif']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
// Date creation
if (!empty($arrayfields['f.datec']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
// Date modification
if (!empty($arrayfields['f.tms']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
// Status
if (!empty($arrayfields['status']['checked'])) {
	print '<td class="liste_titre center minwidth75imp parentonrightofpage">';
	$liststatus = array(
		0=>$langs->trans("Draft"),
		1=>$langs->trans("Active"),
		-1=>$langs->trans("Disabled"),
	);
	print $form->selectarray('search_status', $liststatus, $search_status, -2, 0, 0, '', 0, 0, 0, '', 'width100 onrightofpage');
	print '</td>';
}
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre maxwidthsearch center">';
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
	print getTitleFieldOfList(($mode != 'kanban' ? $selectedfields : ''), 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.titre']['checked'])) {
	print_liste_field_titre($arrayfields['f.titre']['label'], $_SERVER['PHP_SELF'], "f.titre", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.nom']['checked'])) {
	print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER['PHP_SELF'], "s.nom", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.total_ht']['checked'])) {
	print_liste_field_titre($arrayfields['f.total_ht']['label'], $_SERVER['PHP_SELF'], "f.total_ht", "", $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.total_tva']['checked'])) {
	print_liste_field_titre($arrayfields['f.total_tva']['label'], $_SERVER['PHP_SELF'], "f.total_tva", "", $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.total_ttc']['checked'])) {
	print_liste_field_titre($arrayfields['f.total_ttc']['label'], $_SERVER['PHP_SELF'], "f.total_ttc", "", $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.fk_cond_reglement']['checked'])) {
	print_liste_field_titre($arrayfields['f.fk_cond_reglement']['label'], $_SERVER['PHP_SELF'], "f.fk_cond_reglement", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.fk_mode_reglement']['checked'])) {
	print_liste_field_titre($arrayfields['f.fk_mode_reglement']['label'], $_SERVER['PHP_SELF'], "f.fk_mode_reglement", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['recurring']['checked'])) {
	print_liste_field_titre($arrayfields['recurring']['label'], $_SERVER['PHP_SELF'], "recurring", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.frequency']['checked'])) {
	print_liste_field_titre($arrayfields['f.frequency']['label'], $_SERVER['PHP_SELF'], "f.frequency", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.unit_frequency']['checked'])) {
	print_liste_field_titre($arrayfields['f.unit_frequency']['label'], $_SERVER['PHP_SELF'], "f.unit_frequency", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.nb_gen_done']['checked'])) {
	print_liste_field_titre($arrayfields['f.nb_gen_done']['label'], $_SERVER['PHP_SELF'], "f.nb_gen_done", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.date_last_gen']['checked'])) {
	print_liste_field_titre($arrayfields['f.date_last_gen']['label'], $_SERVER['PHP_SELF'], "f.date_last_gen", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.date_when']['checked'])) {
	print_liste_field_titre($arrayfields['f.date_when']['label'], $_SERVER['PHP_SELF'], "f.date_when", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.fk_user_author']['checked'])) {
	print_liste_field_titre($arrayfields['f.fk_user_author']['label'], $_SERVER['PHP_SELF'], "f.fk_user_author", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.fk_user_modif']['checked'])) {
	print_liste_field_titre($arrayfields['f.fk_user_modif']['label'], $_SERVER['PHP_SELF'], "f.fk_user_modif", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.datec']['checked'])) {
	print_liste_field_titre($arrayfields['f.datec']['label'], $_SERVER['PHP_SELF'], "f.datec", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.tms']['checked'])) {
	print_liste_field_titre($arrayfields['f.tms']['label'], $_SERVER['PHP_SELF'], "f.tms", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
if (!empty($arrayfields['status']['checked'])) {
	print_liste_field_titre($arrayfields['status']['label'], $_SERVER['PHP_SELF'], "f.suspended,f.frequency", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList(($mode != 'kanban' ? $selectedfields : ''), 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
print '</tr>'."\n";

$i = 0;
$savnbfield = $totalarray['nbfield'];
$totalarray = array();
$totalarray['nbfield'] = 0;
$totalarray['val']['f.total_ht'] = 0;
$totalarray['val']['f.total_tva'] = 0;
$totalarray['val']['f.total_ttc'] = 0;
$imaxinloop = ($limit ? min($num, $limit) : $num);

while ($i < $imaxinloop) {
	$objp = $db->fetch_object($resql);
	if (empty($objp)) {
		break;
	}

	$companystatic->id = $objp->socid;
	$companystatic->name = $objp->name;

	$supplierinvoicerectmp->id = !empty($objp->id) ? $objp->id : $objp->facid;
	$supplierinvoicerectmp->frequency = $objp->frequency;
	$supplierinvoicerectmp->suspended = $objp->suspended;
	$supplierinvoicerectmp->unit_frequency = $objp->unit_frequency;
	$supplierinvoicerectmp->nb_gen_max = $objp->nb_gen_max;
	$supplierinvoicerectmp->nb_gen_done = $objp->nb_gen_done;
	$supplierinvoicerectmp->ref = $objp->title;
	$supplierinvoicerectmp->total_ht = $objp->total_ht;
	$supplierinvoicerectmp->total_tva = $objp->total_tva;
	$supplierinvoicerectmp->total_ttc = $objp->total_ttc;

	// Show here line of result
	$j = 0;
	print '<tr data-rowid="'.$object->id.'" class="oddeven">';
	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="center tdoverflowmax125">';
		if ($user->hasRight('facture', 'creer') && empty($supplierinvoicerectmp->suspended)) {
			if ($supplierinvoicerectmp->isMaxNbGenReached()) {
				print $langs->trans("MaxNumberOfGenerationReached");
			} elseif (empty($objp->frequency) || $db->jdate($objp->date_when) <= $today) {
				print '<a href="'.DOL_URL_ROOT.'/fourn/facture/card.php?action=create&amp;socid='.$objp->socid.'&amp;fac_rec='.$objp->facid.'">';
				print img_picto($langs->trans("CreateBill"), 'add', 'class="paddingrightonly"');
				print $langs->trans("CreateBill").'</a>';
			} else {
				print $form->textwithpicto('', $langs->trans("DateIsNotEnough"));
			}
		}
		print "</td>";
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['f.titre']['checked'])) {
		print '<td class="nowrap tdoverflowmax200">';
		print $supplierinvoicerectmp->getNomUrl(1);
		print "</td>\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	if (!empty($arrayfields['s.nom']['checked'])) {
		print '<td class="tdoverflowmax150">'.$companystatic->getNomUrl(1, 'supplier').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	if (!empty($arrayfields['f.total_ht']['checked'])) {
		print '<td class="nowrap right amount">'.price($objp->total_ht).'</td>'."\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}
		if (!$i) {
			$totalarray['pos'][$totalarray['nbfield']] = 'f.total_ht';
		}
		$totalarray['val']['f.total_ht'] += $objp->total_ht;
	}
	if (!empty($arrayfields['f.total_tva']['checked'])) {
		print '<td class="nowrap right amount">'.price($objp->total_tva).'</td>'."\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}
		if (!$i) {
			$totalarray['pos'][$totalarray['nbfield']] = 'f.total_tva';
		}
		$totalarray['val']['f.total_tva'] += $objp->total_tva;
	}
	if (!empty($arrayfields['f.total_ttc']['checked'])) {
		print '<td class="nowrap right amount">'.price($objp->total_ttc).'</td>'."\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}
		if (!$i) {
			$totalarray['pos'][$totalarray['nbfield']] = 'f.total_ttc';
		}
		$totalarray['val']['f.total_ttc'] += $objp->total_ttc;
	}
	// Payment term
	if (!empty($arrayfields['f.fk_cond_reglement']['checked'])) {
		print '<td class="tdoverflowmax150">';
		$form->form_conditions_reglement('', $objp->fk_cond_reglement, 'none');
		print '</td>'."\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Payment mode
	if (!empty($arrayfields['f.fk_mode_reglement']['checked'])) {
		print '<td class="tdoverflowmax150">';
		$form->form_modes_reglement('', $objp->fk_mode_reglement, 'none');
		print '</td>'."\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Is it a recurring invoice
	if (!empty($arrayfields['recurring']['checked'])) {
		print '<td class="center">'.($objp->frequency ? img_picto($langs->trans("Frequency").': '.$objp->frequency.' '.$objp->unit_frequency, 'recurring', 'class="opacitymedium"').' ' : '').yn($objp->frequency ? 1 : 0).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	if (!empty($arrayfields['f.frequency']['checked'])) {
		print '<td class="center">';
		print ($objp->frequency > 0 ? $objp->frequency : '');
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	if (!empty($arrayfields['f.unit_frequency']['checked'])) {
		print '<td class="center">';
		if ($objp->frequency > 1) {
			$dur = array("i"=>$langs->trans("Minutes"), "h"=>$langs->trans("Hours"), "d"=>$langs->trans("Days"), "w"=>$langs->trans("Weeks"), "m"=>$langs->trans("Months"), "y"=>$langs->trans("Years"));
		} else {
			$dur = array("i"=>$langs->trans("Minute"), "h"=>$langs->trans("Hour"), "d"=>$langs->trans("Day"), "w"=>$langs->trans("Week"), "m"=>$langs->trans("Month"), "y"=>$langs->trans("Year"));
		}
		print ($objp->frequency > 0 ? $dur[$objp->unit_frequency] : '');
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	if (!empty($arrayfields['f.nb_gen_done']['checked'])) {
		print '<td class="center">';
		print ($objp->frequency > 0 ? $objp->nb_gen_done.($objp->nb_gen_max > 0 ? ' / '.$objp->nb_gen_max : '') : '<span class="opacitymedium">'.$langs->trans('NA').'</span>');
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Date last generation
	if (!empty($arrayfields['f.date_last_gen']['checked'])) {
		print '<td class="center">';
		print ($objp->frequency > 0 ? dol_print_date($db->jdate($objp->date_last_gen), 'day') : '<span class="opacitymedium">'.$langs->trans('NA').'</span>');
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Date next generation
	if (!empty($arrayfields['f.date_when']['checked'])) {
		print '<td class="center">';
		print '<div class="nowraponall">';
		print ($objp->frequency ? ($supplierinvoicerectmp->isMaxNbGenReached() ? '<strike>' : '').dol_print_date($db->jdate($objp->date_when), 'day').($supplierinvoicerectmp->isMaxNbGenReached() ? '</strike>' : '') : '<span class="opacitymedium">'.$langs->trans('NA').'</span>');
		if (!$supplierinvoicerectmp->isMaxNbGenReached()) {
			if (!$objp->suspended && $objp->frequency > 0 && $db->jdate($objp->date_when) && $db->jdate($objp->date_when) < $now) {
				print img_warning($langs->trans("Late"));
			}
		} else {
			print img_info($langs->trans("MaxNumberOfGenerationReached"));
		}
		print '</div>';
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	if (!empty($arrayfields['f.fk_user_author']['checked'])) {
		print '<td class="center tdoverflowmax150">';
		if ($objp->fk_user_author > 0) {
			$tmpuser->fetch($objp->fk_user_author);
			print $tmpuser->getNomUrl(1);
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	if (!empty($arrayfields['f.fk_user_modif']['checked'])) {
		print '<td class="center tdoverflowmax150">';
		if ($objp->fk_user_author > 0) {
			$tmpuser->fetch($objp->fk_user_author);
			print $tmpuser->getNomUrl(1);
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	if (!empty($arrayfields['f.datec']['checked'])) {
		print '<td class="center nowraponall">';
		print dol_print_date($db->jdate($objp->datec), 'dayhour');
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	if (!empty($arrayfields['f.tms']['checked'])) {
		print '<td class="center nowraponall">';
		print dol_print_date($db->jdate($objp->tms), 'dayhour');
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	$obj = $objp;
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
	$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Status
	if (!empty($arrayfields['status']['checked'])) {
		print '<td class="center">';
		print $supplierinvoicerectmp->getLibStatut(5, 0);
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="nowrap center tdoverflowmax125">';
		if ($user->hasRight('facture', 'creer') && empty($supplierinvoicerectmp->suspended)) {
			if ($supplierinvoicerectmp->isMaxNbGenReached()) {
				print $langs->trans("MaxNumberOfGenerationReached");
			} elseif (empty($objp->frequency) || $db->jdate($objp->date_when) <= $today) {
				print '<a href="'.DOL_URL_ROOT.'/fourn/facture/card.php?action=create&amp;socid='.$objp->socid.'&amp;fac_rec='.$objp->facid.'">';
				print img_picto($langs->trans("CreateBill"), 'add', 'class="paddingrightonly"');
				print $langs->trans("CreateBill").'</a>';
			} else {
				print $form->textwithpicto('', $langs->trans("DateIsNotEnough"));
			}
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


$parameters = array('arrayfields'=>$arrayfields, 'sql'=>$sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";


// End of page
llxFooter();
$db->close();
