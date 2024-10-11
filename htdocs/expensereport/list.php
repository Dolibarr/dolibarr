<?php
/* Copyright (C) 2003     	Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004     	Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009	Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015       Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Ferran Marcet	     <fmarcet@2byte.es>
 * Copyright (C) 2018       Charlene Benke       <charlie@patas-monkey.com>
 * Copyright (C) 2019       Juanjo Menent		 <jmenent@2byte.es>
 * Copyright (C) 2019-2021  Frédéric France      <frederic.france@netlogic.fr>
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
 *	    \file       htdocs/expensereport/list.php
 *      \ingroup    expensereport
 *		\brief      list of expense reports
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formexpensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport_ik.class.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'users', 'trips'));

$action      = GETPOST('action', 'aZ09');
$massaction  = GETPOST('massaction', 'alpha');
$show_files  = GETPOSTINT('show_files');
$confirm     = GETPOST('confirm', 'alpha');
$cancel      = GETPOST('cancel', 'alpha'); // We click on a Cancel button
$toselect    = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'expensereportlist';
$mode        = GETPOST('mode', 'alpha');

$childids = $user->getAllChildIds(1);

// Security check
$socid = GETPOSTINT('socid');
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'expensereport', '', '');
$id = GETPOSTINT('id');
// If we are on the view of a specific user
if ($id > 0) {
	$canread = 0;
	if ($id == $user->id) {
		$canread = 1;
	}
	if ($user->hasRight('expensereport', 'readall')) {
		$canread = 1;
	}
	if ($user->hasRight('expensereport', 'lire') && in_array($id, $childids)) {
		$canread = 1;
	}
	if (!$canread) {
		accessforbidden();
	}
}

$diroutputmassaction = $conf->expensereport->dir_output.'/temp/massgeneration/'.$user->id;


// Load variable for pagination
$limit 		= GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield	= GETPOST('sortfield', 'aZ09comma');
$sortorder	= GETPOST('sortorder', 'aZ09comma');
$page 		= GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "DESC";
}
if (!$sortfield) {
	$sortfield = "d.date_debut";
}


$search_all			= trim((GETPOST('search_all', 'alphanohtml') != '') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));

$search_ref			= GETPOST('search_ref', 'alpha');
$search_user		= GETPOST('search_user', 'intcomma');
$search_amount_ht	= GETPOST('search_amount_ht', 'alpha');
$search_amount_vat	= GETPOST('search_amount_vat', 'alpha');
$search_amount_ttc	= GETPOST('search_amount_ttc', 'alpha');
$search_status		= (GETPOST('search_status', 'intcomma') != '' ? GETPOST('search_status', 'intcomma') : GETPOST('statut', 'intcomma'));

$search_date_startday		= GETPOSTINT('search_date_startday');
$search_date_startmonth		= GETPOSTINT('search_date_startmonth');
$search_date_startyear		= GETPOSTINT('search_date_startyear');
$search_date_startendday	= GETPOSTINT('search_date_startendday');
$search_date_startendmonth	= GETPOSTINT('search_date_startendmonth');
$search_date_startendyear	= GETPOSTINT('search_date_startendyear');
$search_date_start			= dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);	// Use tzserver
$search_date_startend		= dol_mktime(23, 59, 59, $search_date_startendmonth, $search_date_startendday, $search_date_startendyear);

$search_date_endday			= GETPOSTINT('search_date_endday');
$search_date_endmonth		= GETPOSTINT('search_date_endmonth');
$search_date_endyear		= GETPOSTINT('search_date_endyear');
$search_date_endendday		= GETPOSTINT('search_date_endendday');
$search_date_endendmonth	= GETPOSTINT('search_date_endendmonth');
$search_date_endendyear		= GETPOSTINT('search_date_endendyear');
$search_date_end			= dol_mktime(0, 0, 0, $search_date_endmonth, $search_date_endday, $search_date_endyear);	// Use tzserver
$search_date_endend			= dol_mktime(23, 59, 59, $search_date_endendmonth, $search_date_endendday, $search_date_endendyear);

$optioncss    = GETPOST('optioncss', 'alpha');

if ($search_status == '') {
	$search_status = -1;
}
if ($search_user == '') {
	$search_user = -1;
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new ExpenseReport($db);
$hookmanager->initHooks(array('expensereportlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');


// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'd.ref' => 'Ref',
	'd.note_public' => "NotePublic",
	'u.lastname' => 'EmployeeLastname',
	'u.firstname' => "EmployeeFirstname",
	'u.login' => "Login",
);
if (empty($user->socid)) {
	$fieldstosearchall["d.note_private"] = "NotePrivate";
}

$arrayfields = array(
	'd.ref' => array('label' => $langs->trans("Ref"), 'checked' => 1),
	'user' => array('label' => $langs->trans("User"), 'checked' => 1),
	'd.date_debut' => array('label' => $langs->trans("DateStart"), 'checked' => 1),
	'd.date_fin' => array('label' => $langs->trans("DateEnd"), 'checked' => 1),
	'd.date_valid' => array('label' => $langs->trans("DateValidation"), 'checked' => 1),
	'd.date_approve' => array('label' => $langs->trans("DateApprove"), 'checked' => 1),
	'd.total_ht' => array('label' => $langs->trans("AmountHT"), 'checked' => 1),
	'd.total_vat' => array('label' => $langs->trans("AmountVAT"), 'checked' => -1),
	'd.total_ttc' => array('label' => $langs->trans("AmountTTC"), 'checked' => 1),
	'd.date_create' => array('label' => $langs->trans("DateCreation"), 'checked' => 0, 'position' => 500),
	'd.tms' => array('label' => $langs->trans("DateModificationShort"), 'checked' => 0, 'position' => 500),
	'd.fk_statut' => array('label' => $langs->trans("Status"), 'checked' => 1, 'position' => 1000),
);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$canedituser = (!empty($user->admin) || $user->hasRight('user', 'user', 'creer'));

$permissiontoread = $user->hasRight('expensereport', 'lire');
$permissiontodelete = $user->hasRight('expensereport', 'supprimer');

$objectuser = new User($db);


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

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$search_ref = "";
		$search_user = "";
		$search_amount_ht = "";
		$search_amount_vat = "";
		$search_amount_ttc = "";
		$search_status = "";
		$search_date_startday = '';
		$search_date_startmonth = '';
		$search_date_startyear = '';
		$search_date_startendday = '';
		$search_date_startendmonth = '';
		$search_date_startendyear = '';
		$search_date_start = '';
		$search_date_startend = '';
		$search_date_endday = '';
		$search_date_endmonth = '';
		$search_date_endyear = '';
		$search_date_endendday = '';
		$search_date_endendmonth = '';
		$search_date_endendyear = '';
		$search_date_end = '';
		$search_date_endend = '';
		$toselect = array();
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions
	$objectclass = 'ExpenseReport';
	$objectlabel = 'ExpenseReport';
	$uploaddir = $conf->expensereport->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$formexpensereport = new FormExpenseReport($db);

$fuser = new User($db);

$title = $langs->trans("TripsAndExpenses");
$help_url = '';
$morejs = array();
$morecss = array();

$max_year = 5;
$min_year = 10;

// Get current user id
$user_id = $user->id;

if ($id > 0) {
	// Charge utilisateur edite
	$fuser->fetch($id, '', '', 1);
	$fuser->getrights();
	$user_id = $fuser->id;

	$search_user = $user_id;
}

// Build and execute select
// --------------------------------------------------------------------
$sql = "SELECT d.rowid, d.ref, d.fk_user_author, d.total_ht, d.total_tva, d.total_ttc, d.fk_statut as status,";
$sql .= " d.date_debut, d.date_fin, d.date_create, d.tms as date_modif, d.date_valid, d.date_approve, d.note_private, d.note_public,";
$sql .= " u.rowid as id_user, u.firstname, u.lastname, u.login, u.email, u.statut as user_status, u.photo";
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

$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as d";
if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (d.rowid = ef.fk_object)";
}
$sql .= ", ".MAIN_DB_PREFIX."user as u";
$sql .= " WHERE d.fk_user_author = u.rowid AND d.entity IN (".getEntity('expensereport').")";
// Search all
if (!empty($search_all)) {
	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}
// Ref
if (!empty($search_ref)) {
	$sql .= natural_search('d.ref', $search_ref);
}
// Date Start
if ($search_date_start) {
	$sql .= " AND d.date_debut >= '".$db->idate($search_date_start)."'";
}
if ($search_date_startend) {
	$sql .= " AND d.date_debut <= '".$db->idate($search_date_startend)."'";
}
// Date End
if ($search_date_end) {
	$sql .= " AND d.date_fin >= '".$db->idate($search_date_end)."'";
}
if ($search_date_endend) {
	$sql .= " AND d.date_fin <= '".$db->idate($search_date_endend)."'";
}

if ($search_amount_ht != '') {
	$sql .= natural_search('d.total_ht', $search_amount_ht, 1);
}
if ($search_amount_ttc != '') {
	$sql .= natural_search('d.total_ttc', $search_amount_ttc, 1);
}
// User
if ($search_user != '' && $search_user >= 0) {
	$sql .= " AND u.rowid = '".$db->escape($search_user)."'";
}
// Status
if ($search_status != '' && $search_status >= 0) {
	$sql .= " AND d.fk_statut IN (".$db->sanitize($search_status).")";
}
// RESTRICT RIGHTS
if (!$user->hasRight('expensereport', 'readall') && !$user->hasRight('expensereport', 'lire_tous')
	&& (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') || !$user->hasRight('expensereport', 'writeall_advance'))) {
	$sql .= " AND d.fk_user_author IN (".$db->sanitize(implode(',', $childids)).")\n";
}
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
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

	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller than paging size (filtering), goto and load page 0
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
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
if ($search_all) {
	$param .= "&search_all=".urlencode($search_all);
}
if ($search_ref) {
	$param .= "&search_ref=".urlencode($search_ref);
}
// Start date
if ($search_date_startday) {
	$param .= '&search_date_startday='.urlencode((string) ($search_date_startday));
}
if ($search_date_startmonth) {
	$param .= '&search_date_startmonth='.urlencode((string) ($search_date_startmonth));
}
if ($search_date_startyear) {
	$param .= '&search_date_startyear='.urlencode((string) ($search_date_startyear));
}
if ($search_date_startendday) {
	$param .= '&search_date_startendday='.urlencode((string) ($search_date_startendday));
}
if ($search_date_startendmonth) {
	$param .= '&search_date_startendmonth='.urlencode((string) ($search_date_startendmonth));
}
if ($search_date_startendyear) {
	$param .= '&search_date_startendyear='.urlencode((string) ($search_date_startendyear));
}
// End date
if ($search_date_endday) {
	$param .= '&search_date_endday='.urlencode((string) ($search_date_endday));
}
if ($search_date_endmonth) {
	$param .= '&search_date_endmonth='.urlencode((string) ($search_date_endmonth));
}
if ($search_date_endyear) {
	$param .= '&search_date_endyear='.urlencode((string) ($search_date_endyear));
}
if ($search_date_endendday) {
	$param .= '&search_date_endendday='.urlencode((string) ($search_date_endendday));
}
if ($search_date_endendmonth) {
	$param .= '&search_date_endendmonth='.urlencode((string) ($search_date_endendmonth));
}
if ($search_date_endendyear) {
	$param .= '&search_date_endendyear='.urlencode((string) ($search_date_endendyear));
}
if ($search_user) {
	$param .= "&search_user=".urlencode($search_user);
}
if ($search_amount_ht) {
	$param .= "&search_amount_ht=".urlencode($search_amount_ht);
}
if ($search_amount_ttc) {
	$param .= "&search_amount_ttc=".urlencode($search_amount_ttc);
}
if ($search_status >= 0) {
	$param .= "&search_status=".urlencode($search_status);
}
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// List of mass actions available
$arrayofmassactions = array(
	'generate_doc' => img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
	'builddoc' => img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
	'presend' => img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
);
if ($user->hasRight('expensereport', 'supprimer')) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if (GETPOSTINT('nomassaction') || in_array($massaction, array('presend', 'predelete'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

// Lines of title fields
print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="'.($action == 'edit' ? 'update' : 'list').'">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="mode" value="'.$mode.'">';
if ($id > 0) {
	print '<input type="hidden" name="id" value="'.$id.'">';
}

if ($id > 0) {		// For user tab
	$title = $langs->trans("User");
	$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
	$head = user_prepare_head($fuser);

	print dol_get_fiche_head($head, 'expensereport', $title, -1, 'user');

	dol_banner_tab($fuser, 'id', $linkback, $user->hasRight('user', 'user', 'lire') || $user->admin);

	print dol_get_fiche_end();

	if ($action != 'edit') {
		print '<div class="tabsAction">';

		$childids = $user->getAllChildIds(1);

		$canedit = ((in_array($user_id, $childids) && $user->hasRight('expensereport', 'creer'))
			|| ($conf->global->MAIN_USE_ADVANCED_PERMS && $user->hasRight('expensereport', 'writeall_advance')));

		// Buttons for actions
		if ($canedit) {
			print '<a href="'.DOL_URL_ROOT.'/expensereport/card.php?action=create&fk_user_author='.$fuser->id.'" class="butAction">'.$langs->trans("AddTrip").'</a>';
		} else {
			print '<a href="#" class="butActionRefused" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("AddTrip").'</a>';
		}

		print '</div>';
	} else {
		print $form->buttonsSaveCancel("Save", '');
	}
} else {
	$title = $langs->trans("ListTripsAndExpenses");

	$url = DOL_URL_ROOT.'/expensereport/card.php?action=create';
	if (!empty($socid)) {
		$url .= '&socid='.$socid;
	}
	$newcardbutton = '';
	$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss' => 'reposition'));
	$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss' => 'reposition'));
	$newcardbutton .= dolGetButtonTitleSeparator();
	$newcardbutton .= dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', $url, '', $user->hasRight('expensereport', 'creer'));

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'expensereport', 0, $newcardbutton, '', $limit, 0, 0, 1);
}

// Add code for pre mass action (confirmation or email presend form)
$topicmail = "SendExpenseReport";
$modelmail = "expensereport";
$objecttmp = new ExpenseReport($db);
$trackid = 'exp'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($search_all) {
	$setupstring = '';
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
		$setupstring .= $key."=".$val.";";
	}
	print '<!-- Search done like if EXPENSEREPORT_QUICKSEARCH_ON_FIELDS = '.$setupstring.' -->'."\n";
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
if (!empty($arrayfields['d.ref']['checked'])) {
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" size="15" type="text" name="search_ref" value="'.$search_ref.'">';
	print '</td>';
}
// User
if (!empty($arrayfields['user']['checked'])) {
	if ($user->hasRight('expensereport', 'readall') || $user->hasRight('expensereport', 'lire_tous')) {
		print '<td class="liste_titre maxwidthonspartphone" align="left">';
		print $form->select_dolusers($search_user, 'search_user', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth200');
		print '</td>';
	} else {
		print '<td class="liste_titre">&nbsp;</td>';
	}
}
// Date start
if (!empty($arrayfields['d.date_debut']['checked'])) {
	print '<td class="liste_titre" align="center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_startend ? $search_date_startend : -1, 'search_date_startend', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
// Date end
if (!empty($arrayfields['d.date_fin']['checked'])) {
	print '<td class="liste_titre" align="center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_endend ? $search_date_endend : -1, 'search_date_endend', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
// Date valid
if (!empty($arrayfields['d.date_valid']['checked'])) {
	print '<td class="liste_titre" align="center">';
	//print '<input class="flat" type="text" size="1" maxlength="2" name="month_end" value="'.$month_end.'">';
	//print $formother->selectyear($year_end,'year_end',1, $min_year, $max_year);
	print '</td>';
}
// Date approve
if (!empty($arrayfields['d.date_approve']['checked'])) {
	print '<td class="liste_titre" align="center">';
	//print '<input class="flat" type="text" size="1" maxlength="2" name="month_end" value="'.$month_end.'">';
	//print $formother->selectyear($year_end,'year_end',1, $min_year, $max_year);
	print '</td>';
}
// Amount with no tax
if (!empty($arrayfields['d.total_ht']['checked'])) {
	print '<td class="liste_titre right"><input class="flat" type="text" size="5" name="search_amount_ht" value="'.$search_amount_ht.'"></td>';
}
if (!empty($arrayfields['d.total_vat']['checked'])) {
	print '<td class="liste_titre right"><input class="flat" type="text" size="5" name="search_amount_vat" value="'.$search_amount_vat.'"></td>';
}
// Amount with all taxes
if (!empty($arrayfields['d.total_ttc']['checked'])) {
	print '<td class="liste_titre right"><input class="flat" type="text" size="5" name="search_amount_ttc" value="'.$search_amount_ttc.'"></td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields' => $arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Date creation
if (!empty($arrayfields['d.date_create']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
// Date modification
if (!empty($arrayfields['d.tms']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
// Status
if (!empty($arrayfields['d.fk_statut']['checked'])) {
	print '<td class="liste_titre center parentonrightofpage">';
	print $formexpensereport->selectExpensereportStatus($search_status, 'search_status', 1, 1, 'search_status width100 onrightofpage');
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
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'maxwidthsearch center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.ref']['checked'])) {
	print_liste_field_titre($arrayfields['d.ref']['label'], $_SERVER["PHP_SELF"], "d.ref", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['user']['checked'])) {
	print_liste_field_titre($arrayfields['user']['label'], $_SERVER["PHP_SELF"], "u.lastname", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.date_debut']['checked'])) {
	print_liste_field_titre($arrayfields['d.date_debut']['label'], $_SERVER["PHP_SELF"], "d.date_debut", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.date_fin']['checked'])) {
	print_liste_field_titre($arrayfields['d.date_fin']['label'], $_SERVER["PHP_SELF"], "d.date_fin", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.date_valid']['checked'])) {
	print_liste_field_titre($arrayfields['d.date_valid']['label'], $_SERVER["PHP_SELF"], "d.date_valid", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.date_approve']['checked'])) {
	print_liste_field_titre($arrayfields['d.date_approve']['label'], $_SERVER["PHP_SELF"], "d.date_approve", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.total_ht']['checked'])) {
	print_liste_field_titre($arrayfields['d.total_ht']['label'], $_SERVER["PHP_SELF"], "d.total_ht", "", $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.total_vat']['checked'])) {
	print_liste_field_titre($arrayfields['d.total_vat']['label'], $_SERVER["PHP_SELF"], "d.total_tva", "", $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.total_ttc']['checked'])) {
	print_liste_field_titre($arrayfields['d.total_ttc']['label'], $_SERVER["PHP_SELF"], "d.total_ttc", "", $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!empty($arrayfields['d.date_create']['checked'])) {
	print_liste_field_titre($arrayfields['d.date_create']['label'], $_SERVER["PHP_SELF"], "d.date_create", "", $param, '', $sortfield, $sortorder, 'nowraponall center');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.tms']['checked'])) {
	print_liste_field_titre($arrayfields['d.tms']['label'], $_SERVER["PHP_SELF"], "d.tms", "", $param, '', $sortfield, $sortorder, 'nowraponall center');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.fk_statut']['checked'])) {
	print_liste_field_titre($arrayfields['d.fk_statut']['label'], $_SERVER["PHP_SELF"], "d.fk_statut", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	$totalarray['nbfield']++;
}
print '</tr>'."\n";


// Loop on record
// --------------------------------------------------------------------

$total_total_ht = 0;
$total_total_ttc = 0;
$total_total_tva = 0;

$expensereportstatic = new ExpenseReport($db);
$usertmp = new User($db);

if ($num > 0) {
	$i = 0;
	$savnbfield = $totalarray['nbfield'];
	$totalarray = array();
	$totalarray['nbfield'] = 0;
	$totalarray['val'] = array();
	$totalarray['val']['d.total_ht'] = 0;
	$totalarray['val']['d.total_tva'] = 0;
	$totalarray['val']['d.total_ttc'] = 0;
	$totalarray['totalizable'] = array();

	$imaxinloop = ($limit ? min($num, $limit) : $num);
	while ($i < $imaxinloop) {
		$obj = $db->fetch_object($resql);
		if (empty($obj)) {
			break; // Should not happen
		}

		$expensereportstatic->id = $obj->rowid;
		$expensereportstatic->ref = $obj->ref;
		$expensereportstatic->status = $obj->status;
		$expensereportstatic->date_debut = $db->jdate($obj->date_debut);
		$expensereportstatic->date_fin = $db->jdate($obj->date_fin);
		$expensereportstatic->date_create = $db->jdate($obj->date_create);
		$expensereportstatic->date_modif = $db->jdate($obj->date_modif);
		$expensereportstatic->date_valid = $db->jdate($obj->date_valid);
		$expensereportstatic->date_approve = $db->jdate($obj->date_approve);
		$expensereportstatic->note_private = $obj->note_private;
		$expensereportstatic->note_public = $obj->note_public;
		$expensereportstatic->fk_user = $obj->id_user;

		$usertmp->id = $obj->id_user;
		$usertmp->lastname = $obj->lastname;
		$usertmp->firstname = $obj->firstname;
		$usertmp->login = $obj->login;
		$usertmp->statut = $obj->user_status;
		$usertmp->status = $obj->user_status;
		$usertmp->photo = $obj->photo;
		$usertmp->email = $obj->email;

		if ($mode == 'kanban') {
			if ($i == 0) {
				print '<tr class="trkanban"><td colspan="'.$savnbfield.'">';
				print '<div class="box-flex-container kanban">';
			}
			// TODO Use a cache on user
			$usertmp->fetch($obj->id_user);

			// Output Kanban
			if ($massactionbutton || $massaction) {
				$selected = 0;

				print $expensereportstatic->getKanbanView('', array('userauthor' => $usertmp, 'selected' => in_array($expensereportstatic->id, $arrayofselected)));
			}
			if ($i == ($imaxinloop - 1)) {
				print '</div>';
				print '</td></tr>';
			}
		} else {
			// Show line of result
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
			// Ref
			if (!empty($arrayfields['d.ref']['checked'])) {
				print '<td>';
				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td class="nobordernopadding nowrap">';
				print $expensereportstatic->getNomUrl(1);
				print '</td>';
				// Warning late icon and note
				print '<td class="nobordernopadding nowrap">';
				if ($expensereportstatic->status == 2 && $expensereportstatic->hasDelay('toappove')) {
					print img_warning($langs->trans("Late"));
				}
				if ($expensereportstatic->status == 5 && $expensereportstatic->hasDelay('topay')) {
					print img_warning($langs->trans("Late"));
				}
				if (!empty($obj->note_private) || !empty($obj->note_public)) {
					print ' <span class="note">';
					print '<a href="'.DOL_URL_ROOT.'/expensereport/note.php?id='.$obj->rowid.'">'.img_picto($langs->trans("ViewPrivateNote"), 'object_generic').'</a>';
					print '</span>';
				}
				print '</td>';
				print '<td width="16" class="nobordernopadding hideonsmartphone right">';
				$filename = dol_sanitizeFileName($obj->ref);
				$filedir = $conf->expensereport->dir_output.'/'.dol_sanitizeFileName($obj->ref);
				$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;
				print $formfile->getDocumentsLink($expensereportstatic->element, $filename, $filedir);
				print '</td>';
				print '</tr></table>';
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// User
			if (!empty($arrayfields['user']['checked'])) {
				print '<td class="left">';
				print $usertmp->getNomUrl(-1);
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Start date
			if (!empty($arrayfields['d.date_debut']['checked'])) {
				print '<td class="center">'.($obj->date_debut > 0 ? dol_print_date($db->jdate($obj->date_debut), 'day') : '').'</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// End date
			if (!empty($arrayfields['d.date_fin']['checked'])) {
				print '<td class="center">'.($obj->date_fin > 0 ? dol_print_date($db->jdate($obj->date_fin), 'day') : '').'</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Date validation
			if (!empty($arrayfields['d.date_valid']['checked'])) {
				print '<td class="center">'.($obj->date_valid > 0 ? dol_print_date($db->jdate($obj->date_valid), 'day') : '').'</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Date approval
			if (!empty($arrayfields['d.date_approve']['checked'])) {
				print '<td class="center">'.($obj->date_approve > 0 ? dol_print_date($db->jdate($obj->date_approve), 'day') : '').'</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Amount HT
			if (!empty($arrayfields['d.total_ht']['checked'])) {
				print '<td class="right">'.price($obj->total_ht)."</td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = 'd.total_ht';
				}
				$totalarray['val']['d.total_ht'] += $obj->total_ht;
			}
			// Amount VAT
			if (!empty($arrayfields['d.total_vat']['checked'])) {
				print '<td class="right">'.price($obj->total_tva)."</td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = 'd.total_tva';
				}
				$totalarray['val']['d.total_tva'] += $obj->total_tva;
			}
			// Amount TTC
			if (!empty($arrayfields['d.total_ttc']['checked'])) {
				print '<td class="right">'.price($obj->total_ttc)."</td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = 'd.total_ttc';
				}
				$totalarray['val']['d.total_ttc'] += $obj->total_ttc;
			}

			// Extra fields
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
			// Fields from hook
			$parameters = array('arrayfields' => $arrayfields, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
			$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;

			// Date creation
			if (!empty($arrayfields['d.date_create']['checked'])) {
				print '<td class="nowrap center">';
				print dol_print_date($db->jdate($obj->date_create), 'dayhour');
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Date modification
			if (!empty($arrayfields['d.tms']['checked'])) {
				print '<td class="nowrap center">';
				print dol_print_date($db->jdate($obj->date_modif), 'dayhour');
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Status
			if (!empty($arrayfields['d.fk_statut']['checked'])) {
				print '<td class="nowrap center">'.$expensereportstatic->getLibStatut(5).'</td>';
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
		}

		$total_total_ht = $total_total_ht + $obj->total_ht;
		$total_total_tva = $total_total_tva + $obj->total_tva;
		$total_total_ttc = $total_total_ttc + $obj->total_ttc;

		$i++;
	}
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

if (empty($id)) {
	$hidegeneratedfilelistifempty = 1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) {
		$hidegeneratedfilelistifempty = 0;
	}

	// Show list of available documents
	$urlsource = $_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource .= str_replace('&amp;', '&', $param);

	$filedir = $diroutputmassaction;
	$genallowed = $user->hasRight('expensereport', 'lire');
	$delallowed = $user->hasRight('expensereport', 'creer');

	print $formfile->showdocuments('massfilesarea_expensereport', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
}

// End of page
llxFooter();
$db->close();
