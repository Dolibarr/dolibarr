<?php
/* Copyright (C) 2002-2003	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2012	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013		Cédric Salvador			<csalvador@gpcsolutions.fr>
 * Copyright (C) 2015       Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2018    	Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2021-2024  Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2022		Charlène Benke			<charlene@patas-monkey.com>
 * Copyright (C) 2024		William Mead			<william.mead@manchenumerique.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Benjamin Falière		<benjamin.faliere@altairis.fr>
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
 *	\file       htdocs/fichinter/list.php
 *	\brief      List of all interventions
 *	\ingroup    ficheinter
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}
if (isModEnabled('contract')) {
	require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
}

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('companies', 'bills', 'interventions'));
if (isModEnabled('project')) {
	$langs->load("projects");
}
if (isModEnabled('contract')) {
	$langs->load("contracts");
}

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOSTINT('show_files');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'interventionlist';
$mode = GETPOST('mode', 'alpha');

$search_ref = GETPOST('search_ref') ? GETPOST('search_ref', 'alpha') : GETPOST('search_inter', 'alpha');
$search_ref_client = GETPOST('search_ref_client', 'alpha');
$search_company = GETPOST('search_company', 'alpha');
$search_desc = GETPOST('search_desc', 'alpha');
$search_projet_ref = GETPOST('search_projet_ref', 'alpha');
$search_contrat_ref = GETPOST('search_contrat_ref', 'alpha');
$search_status = GETPOST('search_status', 'alpha');
$search_signed_status = GETPOST('search_signed_status', 'alpha');
$search_all = trim(GETPOST('search_all', 'alphanohtml'));
$search_date_startday = GETPOSTINT('search_date_startday');
$search_date_startmonth = GETPOSTINT('search_date_startmonth');
$search_date_startyear = GETPOSTINT('search_date_startyear');
$search_date_endday = GETPOSTINT('search_date_endday');
$search_date_endmonth = GETPOSTINT('search_date_endmonth');
$search_date_endyear = GETPOSTINT('search_date_endyear');
$search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);	// Use tzserver
$search_date_end = dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);
$optioncss = GETPOST('optioncss', 'alpha');
$socid = GETPOSTINT('socid');

$diroutputmassaction = $conf->ficheinter->dir_output.'/temp/massgeneration/'.$user->id;

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
if (!$sortorder) {
	$sortorder = "DESC";
}
if (!$sortfield) {
	$sortfield = "f.ref";
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$object = new Fichinter($db);
$hookmanager->initHooks(array($contextpage)); 	// Note that conf->hooks_modules contains array of activated contexes

$extrafields = new ExtraFields($db);

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'f.ref' => 'Ref',
	's.nom' => "ThirdParty",
	'f.description' => 'Description',
	'f.note_public' => 'NotePublic',
	'fd.description' => 'DescriptionOfLine',
);
if (empty($user->socid)) {
	$fieldstosearchall["f.note_private"] = "NotePrivate";
}
if (getDolGlobalString('FICHINTER_DISABLE_DETAILS')) {
	unset($fieldstosearchall['fd.description']);
}

// Definition of fields for list
$arrayfields = array(
	'f.ref' => array('label' => 'Ref', 'checked' => 1),
	'f.ref_client' => array('label' => 'RefCustomer', 'checked' => 1),
	's.nom' => array('label' => 'ThirdParty', 'checked' => 1),
	'pr.ref' => array('label' => 'Project', 'checked' => 1, 'enabled' => (!isModEnabled('project') ? 0 : 1)),
	'c.ref' => array('label' => 'Contract', 'checked' => 1, 'enabled' => (empty($conf->contrat->enabled) ? 0 : 1)),
	'f.description' => array('label' => 'Description', 'checked' => 1),
	'f.datec' => array('label' => 'DateCreation', 'checked' => 0, 'position' => 500),
	'f.tms' => array('label' => 'DateModificationShort', 'checked' => 0, 'position' => 500),
	'f.note_public' => array('label' => 'NotePublic', 'checked' => 0, 'position' => 510, 'enabled' => (!getDolGlobalInt('MAIN_LIST_HIDE_PUBLIC_NOTES'))),
	'f.note_private' => array('label' => 'NotePrivate', 'checked' => 0, 'position' => 511, 'enabled' => (!getDolGlobalInt('MAIN_LIST_HIDE_PRIVATE_NOTES'))),
	'f.fk_statut' => array('label' => 'Status', 'checked' => 1, 'position' => 1000),
	'f.signed_status' => array('label' => 'SignedStatus', 'checked' => 0, 'position' => 1001),
	'fd.description' => array('label' => "DescriptionOfLine", 'checked' => 1, 'enabled' => getDolGlobalString('FICHINTER_DISABLE_DETAILS') != '1' ? 1 : 0),
	'fd.date' => array('label' => 'DateOfLine', 'checked' => 1, 'enabled' => getDolGlobalString('FICHINTER_DISABLE_DETAILS') != '1' ? 1 : 0),
	'fd.duree' => array('label' => 'DurationOfLine', 'type' => 'duration', 'checked' => 1, 'enabled' => !getDolGlobalString('FICHINTER_DISABLE_DETAILS') ? 1 : 0), //type duration is here because in database, column 'duree' is double
);
'@phan-var-force array{label:string,type?:string,checked:int,position?:int,enabled?:int,langfile?:string,help:string} $arrayfields';
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');
'@phan-var-force array<string,array{label:string,checked?:int<0,1>,position?:int,help?:string}> $arrayfields';  // dol_sort_array looses type for Phan

// Security check
$id = GETPOSTINT('id');
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'ficheinter', $id, 'fichinter');

$permissiontoread = $user->hasRight('ficheinter', 'lire');
$permissiontoadd = $user->hasRight('ficheinter', 'creer');
$permissiontodelete = $user->hasRight('ficheinter', 'supprimer');


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
		$search_ref = "";
		$search_ref_client = "";
		$search_company = "";
		$search_projet_ref = "";
		$search_contrat_ref = "";
		$search_desc = "";
		$search_status = "";
		$search_signed_status = '';
		$search_date_startday = '';
		$search_date_startmonth = '';
		$search_date_startyear = '';
		$search_date_endday = '';
		$search_date_endmonth = '';
		$search_date_endyear = '';
		$search_date_start = '';
		$search_date_end = '';
		$toselect = array();
		$search_array_options = array();
	}

	// Mass actions
	$objectclass = 'Fichinter';
	$objectlabel = 'Interventions';
	$uploaddir = $conf->ficheinter->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}



/*
 *	View
 */


$form = new Form($db);
$formfile = new FormFile($db);
$objectstatic = new Fichinter($db);
$companystatic = new Societe($db);
if (isModEnabled('project')) {
	$projetstatic = new Project($db);
}
if (isModEnabled('contract')) {
	$contratstatic = new Contrat($db);
}

$now = dol_now();

$title = $langs->trans("Interventions");
$help_url = '';
$morejs = array();
$morecss = array();


$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

$atleastonefieldinlines = 0;
foreach ($arrayfields as $tmpkey => $tmpval) {
	if (preg_match('/^fd\./', $tmpkey) && !empty($arrayfields[$tmpkey]['checked'])) {
		$atleastonefieldinlines++;
		break;
	}
}

$sql = "SELECT";
$sql .= " f.ref, f.ref_client, f.rowid, f.fk_statut as status, f.signed_status as signed_status, f.description, f.datec as date_creation, f.tms as date_modification, f.note_public, f.note_private,";
if (!getDolGlobalString('FICHINTER_DISABLE_DETAILS') && $atleastonefieldinlines) {
	$sql .= " fd.rowid as lineid, fd.description as descriptiondetail, fd.date as dp, fd.duree,";
}
$sql .= " s.nom as name, s.rowid as socid, s.client, s.fournisseur, s.email, s.status as thirdpartystatus";
if (isModEnabled('project')) {
	$sql .= ", pr.rowid as projet_id, pr.ref as projet_ref, pr.title as projet_title";
}
if (isModEnabled('contract')) {
	$sql .= ", c.rowid as contrat_id, c.ref as contrat_ref, c.ref_customer as contrat_ref_customer, c.ref_supplier as contrat_ref_supplier";
}
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

$sql .= " FROM ".MAIN_DB_PREFIX."fichinter as f";
if (isModEnabled('project')) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as pr on f.fk_projet = pr.rowid";
}
if (isModEnabled('contract')) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."contrat as c on f.fk_contrat = c.rowid";
}
if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (f.rowid = ef.fk_object)";
}
if (!getDolGlobalString('FICHINTER_DISABLE_DETAILS') && $atleastonefieldinlines) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."fichinterdet as fd ON fd.fk_fichinter = f.rowid";
}

// Add table from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql .= ", ".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE f.entity IN (".getEntity('intervention').")";
$sql .= " AND f.fk_soc = s.rowid";
if ($search_ref) {
	$sql .= natural_search('f.ref', $search_ref);
}
if ($search_ref_client) {
	$sql .= natural_search('f.ref_client', $search_ref_client);
}
if ($search_company) {
	$sql .= natural_search('s.nom', $search_company);
}
if ($search_projet_ref) {
	$sql .= natural_search('pr.ref', $search_projet_ref);
}
if ($search_contrat_ref) {
	$sql .= natural_search('c.ref', $search_contrat_ref);
}
if ($search_desc) {
	if (!getDolGlobalString('FICHINTER_DISABLE_DETAILS') && $atleastonefieldinlines) {
		$sql .= natural_search(array('f.description', 'fd.description'), $search_desc);
	} else {
		$sql .= natural_search(array('f.description'), $search_desc);
	}
}
if ($search_status != '' && $search_status >= 0) {
	$sql .= ' AND f.fk_statut = '.urlencode($search_status);
}
if ($search_signed_status != '' && $search_signed_status >= 0) {
	$sql .= ' AND f.signed_status = '.urlencode($search_signed_status);
}
if (!getDolGlobalString('FICHINTER_DISABLE_DETAILS') && $atleastonefieldinlines) {
	if ($search_date_start) {
		$sql .= " AND fd.date >= '".$db->idate($search_date_start)."'";
	}
	if ($search_date_end) {
		$sql .= " AND fd.date <= '".$db->idate($search_date_end)."'";
	}
}
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}
if ($socid) {
	$sql .= " AND s.rowid = ".((int) $socid);
}
if ($search_all) {
	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}
// Search on sale representative
/*
if ($search_sale && $search_sale != '-1') {
	if ($search_sale == -2) {
		$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = f.fk_soc)";
	} elseif ($search_sale > 0) {
		$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = f.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
	}
}*/
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
// Add GroupBy from hooks
$parameters = array('search_all' => $search_all, 'fieldstosearchall' => $fieldstosearchall);
$reshook = $hookmanager->executeHooks('printFieldListGroupBy', $parameters, $object); // Note that $action and $object may have been modified by hook
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
if ($num == 1 && getDolGlobalString('MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE') && $search_all && !$page) {
	$obj = $db->fetch_object($resql);
	$id = $obj->rowid;
	header("Location: ".dol_buildpath('/fichinter/card.php', 1).'?id='.$id);
	exit;
}


// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss, '', 'bodyforlist mod-fichinter page-list');	// Can use also classforhorizontalscrolloftabs instead of bodyforlist for no horizontal scroll


$arrayofselected = is_array($toselect) ? $toselect : array();

if ($socid > 0) {
	$soc = new Societe($db);
	$soc->fetch($socid);
	if (empty($search_company)) {
		$search_company = $soc->name;
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
if ($search_all) {
	$param .= "&search_all=".urlencode($search_all);
}
if ($socid) {
	$param .= "&socid=".urlencode((string) ($socid));
}
if ($search_ref) {
	$param .= "&search_ref=".urlencode($search_ref);
}
if ($search_ref_client) {
	$param .= "&search_ref_client=".urlencode($search_ref_client);
}
if ($search_company) {
	$param .= "&search_company=".urlencode($search_company);
}
if ($search_desc) {
	$param .= "&search_desc=".urlencode($search_desc);
}
if ($search_status != '' && $search_status > -1) {
	$param .= "&search_status=".urlencode($search_status);
}
if ($search_signed_status != '' && $search_signed_status >= 0) {
	$param .= '&search_signed_status='.urlencode($search_signed_status);
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
if ($show_files) {
	$param .= '&show_files='.urlencode((string) ($show_files));
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

// List of mass actions available
$arrayofmassactions = array(
	'generate_doc' => img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
	'builddoc' => img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
	//'presend'=>$langs->trans("SendByMail"),
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
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

$newcardbutton = '';
$url = DOL_URL_ROOT.'/fichinter/card.php?action=create';
if (!empty($socid)) {
	$url .= '&socid='.$socid;
}
$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitleSeparator();
$newcardbutton .= dolGetButtonTitle($langs->trans('NewIntervention'), '', 'fa fa-plus-circle', $url, '', $user->hasRight('ficheinter', 'creer'));

print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'object_'.$object->picto, 0, $newcardbutton, '', $limit, 0, 0, 1);

$topicmail = "Information";
$modelmail = "intervention";
$objecttmp = new Fichinter($db);
$trackid = 'int'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($search_all) {
	$setupstring = '';
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
		$setupstring .= $key."=".$val.";";
	}
	print '<!-- Search done like if MYOBJECT_QUICKSEARCH_ON_FIELDS = '.$setupstring.' -->'."\n";
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).implode(', ', $fieldstosearchall).'</div>'."\n";
}

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
$htmlofselectarray = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN'));  // This also change content of $arrayfields with user setup
$selectedfields = ($mode != 'kanban' ? $htmlofselectarray : '');
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

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
if (!empty($arrayfields['f.ref']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="8">';
	print '</td>';
}
if (!empty($arrayfields['f.ref_client']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ref_client" value="'.$search_ref_client.'" size="8">';
	print '</td>';
}
if (!empty($arrayfields['s.nom']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_company" value="'.$search_company.'" size="10">';
	print '</td>';
}
if (!empty($arrayfields['pr.ref']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_projet_ref" value="'.$search_projet_ref.'" size="8">';
	print '</td>';
}
if (!empty($arrayfields['c.ref']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_contrat_ref" value="'.$search_contrat_ref.'" size="8">';
	print '</td>';
}
if (!empty($arrayfields['f.description']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_desc" value="'.$search_desc.'" size="12">';
	print '</td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields' => $arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!empty($arrayfields['f.datec']['checked'])) {
	// Date creation
	print '<td class="liste_titre">';
	print '</td>';
}
if (!empty($arrayfields['f.tms']['checked'])) {
	// Date modification
	print '<td class="liste_titre">';
	print '</td>';
}
if (!empty($arrayfields['f.note_public']['checked'])) {
	// Note public
	print '<td class="liste_titre">';
	print '</td>';
}
if (!empty($arrayfields['f.note_private']['checked'])) {
	// Note private
	print '<td class="liste_titre">';
	print '</td>';
}
// Status
if (!empty($arrayfields['f.fk_statut']['checked'])) {
	print '<td class="liste_titre right parentonrightofpage">';
	$liststatus = [
		$object::STATUS_DRAFT => $langs->transnoentitiesnoconv('Draft'),
		$object::STATUS_VALIDATED => $langs->transnoentitiesnoconv('Validated'),
		$object::STATUS_BILLED => $langs->transnoentitiesnoconv('StatusInterInvoiced'),
		$object::STATUS_CLOSED => $langs->transnoentitiesnoconv('Done'),
	];
	if (!getDolGlobalString('FICHINTER_CLASSIFY_BILLED')) {
		unset($liststatus[2]); // Option deprecated. In a future, billed must be managed with a dedicated field to 0 or 1
	}
	// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
	print $form->selectarray('search_status', $liststatus, $search_status, 1, 0, 0, '', 1, 0, 0, '', 'search_status width100 onrightofpage');
	print '</td>';
}
// Signed status
if (!empty($arrayfields['f.signed_status']['checked'])) {
	print '<td class="liste_titre center">';
	$list_signed_status = $object->getSignedStatusLocalisedArray();
	print $form->selectarray('search_signed_status', $list_signed_status, $search_signed_status, 1, 0, 0, '', 1, 0, 0, '', 'search_status');
	print '</td>';
}
// Fields of detail line
if (!empty($arrayfields['fd.description']['checked'])) {
	print '<td class="liste_titre">&nbsp;</td>';
}
if (!empty($arrayfields['fd.date']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_start ?: -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_end ?: -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
if (!empty($arrayfields['fd.duree']['checked'])) {
	print '<td class="liste_titre">&nbsp;</td>';
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
if (!empty($arrayfields['f.ref']['checked'])) {
	// @phan-suppress-next-line PhanTypeInvalidDimOffset
	print_liste_field_titre($arrayfields['f.ref']['label'], $_SERVER["PHP_SELF"], "f.ref", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.ref_client']['checked'])) {
	print_liste_field_titre($arrayfields['f.ref_client']['label'], $_SERVER["PHP_SELF"], "f.ref_client", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.nom']['checked'])) {
	print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], "s.nom", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['pr.ref']['checked'])) {
	print_liste_field_titre($arrayfields['pr.ref']['label'], $_SERVER["PHP_SELF"], "pr.ref", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.ref']['checked'])) {
	print_liste_field_titre($arrayfields['c.ref']['label'], $_SERVER["PHP_SELF"], "c.ref", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.description']['checked'])) {
	print_liste_field_titre($arrayfields['f.description']['label'], $_SERVER["PHP_SELF"], "f.description", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder, 'totalarray' => &$totalarray);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!empty($arrayfields['f.datec']['checked'])) {
	print_liste_field_titre($arrayfields['f.datec']['label'], $_SERVER["PHP_SELF"], "f.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.tms']['checked'])) {
	print_liste_field_titre($arrayfields['f.tms']['label'], $_SERVER["PHP_SELF"], "f.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.note_public']['checked'])) {
	print_liste_field_titre($arrayfields['f.note_public']['label'], $_SERVER["PHP_SELF"], "f.note_public", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.note_private']['checked'])) {
	print_liste_field_titre($arrayfields['f.note_private']['label'], $_SERVER["PHP_SELF"], "f.note_private", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.fk_statut']['checked'])) {
	print_liste_field_titre($arrayfields['f.fk_statut']['label'], $_SERVER["PHP_SELF"], "f.fk_statut", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['f.signed_status']['checked'])) {
	print_liste_field_titre($arrayfields['f.signed_status']['label'], $_SERVER["PHP_SELF"], "f.signed_status", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['fd.description']['checked'])) {
	print_liste_field_titre($arrayfields['fd.description']['label'], $_SERVER["PHP_SELF"], '');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['fd.date']['checked'])) {
	print_liste_field_titre($arrayfields['fd.date']['label'], $_SERVER["PHP_SELF"], "fd.date", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['fd.duree']['checked'])) {
	print_liste_field_titre($arrayfields['fd.duree']['label'], $_SERVER["PHP_SELF"], "fd.duree", "", $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}

// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	$totalarray['nbfield']++;
}
print "</tr>\n";


// Loop on record
// --------------------------------------------------------------------
$i = 0;
$savnbfield = $totalarray['nbfield'];
$totalarray = array();
$totalarray['nbfield'] = 0;
$totalarray['val'] = array();
$totalarray['val']['fd.duree'] = 0;
$total = 0;
$imaxinloop = ($limit ? min($num, $limit) : $num);
while ($i < $imaxinloop) {
	$obj = $db->fetch_object($resql);
	if (empty($obj)) {
		break; // Should not happen
	}

	// Store properties in $object
	//$object->setVarsFromFetchObj($obj);

	$objectstatic->id = $obj->rowid;
	$objectstatic->ref = $obj->ref;
	$objectstatic->ref_client = $obj->ref_client;
	$objectstatic->statut = $obj->status;	// deprecated
	$objectstatic->status = $obj->status;
	$objectstatic->signed_status = $obj->signed_status;

	$companystatic->name = $obj->name;
	$companystatic->id = $obj->socid;
	$companystatic->client = $obj->client;
	$companystatic->fournisseur = $obj->fournisseur;
	$companystatic->email = $obj->email;
	$companystatic->status = $obj->thirdpartystatus;

	//mode kanban
	if ($mode == 'kanban') {
		if ($i == 0) {
			print '<tr class="trkanban"><td colspan="'.$savnbfield.'">';
			print '<div class="box-flex-container kanban">';
		}

		// Output Kanban
		if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($object->id, $arrayofselected)) {
				$selected = 1;
			}
		}

		$objectstatic->duration = $obj->duree;
		$arraydata = array();
		$arraydata['thirdparty'] = $companystatic;
		print $objectstatic->getKanbanView('', $arraydata);
		if ($i == ($imaxinloop - 1)) {
			print '</div>';
			print '</td></tr>';
		}
	} else {
		// Show here line of result
		$j = 0;
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

		// Picto + Ref
		if (!empty($arrayfields['f.ref']['checked'])) {
			print "<td>";

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			print '<td class="nobordernopadding nowraponall">';
			print $objectstatic->getNomUrl(1);
			print '</td>';
			// Warning
			$warnornote = '';
			//if ($obj->fk_statut == 1 && $db->jdate($obj->dfv) < ($now - $conf->fichinter->warning_delay)) $warnornote.=img_warning($langs->trans("Late"));
			if (!empty($obj->note_private)) {
				$warnornote .= ($warnornote ? ' ' : '');
				$warnornote .= '<span class="note">';
				$warnornote .= '<a href="note.php?id='.$obj->rowid.'">'.img_picto($langs->trans("ViewPrivateNote"), 'object_generic').'</a>';
				$warnornote .= '</span>';
			}
			if ($warnornote) {
				print '<td style="min-width: 20px" class="nobordernopadding nowrap">';
				print $warnornote;
				print '</td>';
			}

			// Other picto tool
			print '<td width="16" class="right nobordernopadding hideonsmartphone">';
			$filename = dol_sanitizeFileName($obj->ref);
			$filedir = $conf->ficheinter->dir_output.'/'.dol_sanitizeFileName($obj->ref);
			$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;
			print $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
			print '</td></tr></table>';

			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Customer ref
		if (!empty($arrayfields['f.ref_client']['checked'])) {
			print '<td class="nowrap tdoverflowmax200">';
			print dol_escape_htmltag($obj->ref_client);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Third party
		if (!empty($arrayfields['s.nom']['checked'])) {
			print '<td class="tdoverflowmax125">';
			print $companystatic->getNomUrl(1, '', 44);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Project ref
		if (!empty($arrayfields['pr.ref']['checked'])) {
			print '<td class="tdoverflowmax150">';
			$projetstatic->id = $obj->projet_id;
			$projetstatic->ref = $obj->projet_ref;
			$projetstatic->title = $obj->projet_title;
			if ($projetstatic->id > 0) {
				print $projetstatic->getNomUrl(1, '');
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Contract
		if (!empty($arrayfields['c.ref']['checked'])) {
			print '<td class="tdoverflowmax150">';
			$contratstatic->id = $obj->contrat_id;
			$contratstatic->ref = $obj->contrat_ref;
			$contratstatic->ref_customer = $obj->contrat_ref_customer;
			$contratstatic->ref_supplier = $obj->contrat_ref_supplier;
			if ($contratstatic->id > 0) {
				print $contratstatic->getNomUrl(1, '');
				print '</td>';
			}
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		if (!empty($arrayfields['f.description']['checked'])) {
			print '<td>'.dol_trunc(dolGetFirstLineOfText(dol_string_nohtmltag($obj->description, 1)), 48).'</td>';
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
		if (!empty($arrayfields['f.datec']['checked'])) {
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Date modification
		if (!empty($arrayfields['f.tms']['checked'])) {
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->date_modification), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Note public
		if (!empty($arrayfields['f.note_public']['checked'])) {
			print '<td class="sensiblehtmlcontent center">';
			print dolPrintHTML($obj->note_public);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Note private
		if (!empty($arrayfields['f.note_private']['checked'])) {
			print '<td class="sensiblehtmlcontent center">';
			print dolPrintHTML($obj->note_private);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Status
		if (!empty($arrayfields['f.fk_statut']['checked'])) {
			print '<td class="center">'.$objectstatic->getLibStatut(5).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Signed Status
		if (!empty($arrayfields['f.signed_status']['checked'])) {
			print '<td class="center">'.$objectstatic->getLibSignedStatus(5).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Fields of detail of line
		if (!empty($arrayfields['fd.description']['checked'])) {
			$text = dolGetFirstLineOfText(dol_string_nohtmltag($obj->descriptiondetail, 1));
			print '<td>';
			print '<div class="classfortooltip tdoverflowmax250 small" title="'.dol_escape_htmltag($obj->descriptiondetail, 1, 1).'">';
			print dol_escape_htmltag($text);
			print '</div>';
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Date line
		if (!empty($arrayfields['fd.date']['checked'])) {
			print '<td class="center nowraponall" title="'.dol_print_date($db->jdate($obj->dp), 'dayhour').'">'.dol_print_date($db->jdate($obj->dp), 'dayhour')."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Duration line
		if (!empty($arrayfields['fd.duree']['checked'])) {
			print '<td class="right">'.convertSecondToTime($obj->duree, 'allhourmin').'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['type'][$totalarray['nbfield']] = 'duration';
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'fd.duree';
			}
			$totalarray['val']['fd.duree'] += $obj->duree;
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

		$total += (isset($obj->duree) ? $obj->duree : 0);
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

print '</form>'."\n";

if (in_array('builddoc', array_keys($arrayofmassactions)) && ($nbtotalofrecords === '' || $nbtotalofrecords)) {
	$hidegeneratedfilelistifempty = 1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) {
		$hidegeneratedfilelistifempty = 0;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	$formfile = new FormFile($db);

	// Show list of available documents
	$urlsource = $_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource .= str_replace('&amp;', '&', $param);

	$filedir = $diroutputmassaction;
	$genallowed = $permissiontoread;
	$delallowed = $permissiontoadd;

	print $formfile->showdocuments('massfilesarea_interventions', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
}

// End of page
llxFooter();
$db->close();
