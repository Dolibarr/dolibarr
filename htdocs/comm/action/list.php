<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2020		Tobias Sekan		<tobias.sekan@startmail.com>
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
 *      \file       htdocs/comm/action/list.php
 *      \ingroup    agenda
 *		\brief      Page to list actions
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';

// Load translation files required by the page
$langs->loadLangs(array("users", "companies", "agenda", "commercial", "other", "orders", "bills"));

// Get Parameters
$action 	= GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$confirm 	= GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'alpha');
$toselect 	= GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'actioncommlist'; // To manage different context of search
$optioncss 	= GETPOST('optioncss', 'alpha');


$disabledefaultvalues = GETPOSTINT('disabledefaultvalues');

$mode = GETPOST('mode', 'aZ09');
if (empty($mode) && preg_match('/show_/', $action)) {
	$mode = $action;	// For backward compatibility
}
$resourceid = GETPOSTINT("search_resourceid") ? GETPOSTINT("search_resourceid") : GETPOSTINT("resourceid");
$pid = GETPOSTINT("search_projectid", 3) ? GETPOSTINT("search_projectid", 3) : GETPOSTINT("projectid", 3);
$search_status = (GETPOST("search_status", 'aZ09') != '') ? GETPOST("search_status", 'aZ09') : GETPOST("status", 'aZ09');
$type = GETPOST('search_type', 'alphanohtml') ? GETPOST('search_type', 'alphanohtml') : GETPOST('type', 'alphanohtml');
$year = GETPOSTINT("year");
$month = GETPOSTINT("month");
$day = GETPOSTINT("day");

// Set actioncode (this code must be same for setting actioncode into peruser, listacton and index)
if (GETPOST('search_actioncode', 'array')) {
	$actioncode = GETPOST('search_actioncode', 'array', 3);
	if (!count($actioncode)) {
		$actioncode = '0';
	}
} else {
	$actioncode = GETPOST("search_actioncode", "alpha", 3) ? GETPOST("search_actioncode", "alpha", 3) : (GETPOST("search_actioncode") == '0' ? '0' : ((!getDolGlobalString('AGENDA_DEFAULT_FILTER_TYPE') || $disabledefaultvalues) ? '' : $conf->global->AGENDA_DEFAULT_FILTER_TYPE));
}

// Search Fields
$search_id = GETPOST('search_id', 'alpha');
$search_title = GETPOST('search_title', 'alpha');
$search_note = GETPOST('search_note', 'alpha');

// $dateselect is a day included inside the event range
$dateselect = dol_mktime(0, 0, 0, GETPOSTINT('dateselectmonth'), GETPOSTINT('dateselectday'), GETPOSTINT('dateselectyear'), 'tzuserrel');
$datestart_dtstart = dol_mktime(0, 0, 0, GETPOSTINT('datestart_dtstartmonth'), GETPOSTINT('datestart_dtstartday'), GETPOSTINT('datestart_dtstartyear'), 'tzuserrel');
$datestart_dtend = dol_mktime(23, 59, 59, GETPOSTINT('datestart_dtendmonth'), GETPOSTINT('datestart_dtendday'), GETPOSTINT('datestart_dtendyear'), 'tzuserrel');
$dateend_dtstart = dol_mktime(0, 0, 0, GETPOSTINT('dateend_dtstartmonth'), GETPOSTINT('dateend_dtstartday'), GETPOSTINT('dateend_dtstartyear'), 'tzuserrel');
$dateend_dtend = dol_mktime(23, 59, 59, GETPOSTINT('dateend_dtendmonth'), GETPOSTINT('dateend_dtendday'), GETPOSTINT('dateend_dtendyear'), 'tzuserrel');
if ($search_status == '' && !GETPOSTISSET('search_status')) {
	$search_status = ((!getDolGlobalString('AGENDA_DEFAULT_FILTER_STATUS') || $disabledefaultvalues) ? '' : $conf->global->AGENDA_DEFAULT_FILTER_STATUS);
}
if (empty($mode) && !GETPOSTISSET('mode')) {
	$mode = (!getDolGlobalString('AGENDA_DEFAULT_VIEW') ? 'show_month' : $conf->global->AGENDA_DEFAULT_VIEW);
}

$filter = GETPOST("search_filter", 'alpha', 3) ? GETPOST("search_filter", 'alpha', 3) : GETPOST("filter", 'alpha', 3);
$filtert = GETPOST("search_filtert", "intcomma", 3) ? GETPOST("search_filtert", "intcomma", 3) : GETPOST("filtert", "intcomma", 3);
$usergroup = GETPOSTINT("search_usergroup", 3) ? GETPOSTINT("search_usergroup", 3) : GETPOSTINT("usergroup", 3);
$showbirthday = empty($conf->use_javascript_ajax) ? (GETPOSTINT("search_showbirthday") ? GETPOSTINT("search_showbirthday") : GETPOSTINT("showbirthday")) : 1;
$search_categ_cus = GETPOST("search_categ_cus", "intcomma", 3) ? GETPOST("search_categ_cus", "intcomma", 3) : 0;

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$object = new ActionComm($db);
$hookmanager->initHooks(array('agendalist'));

$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');
// If not choice done on calendar owner, we filter on user.
if (empty($filtert) && !getDolGlobalString('AGENDA_ALL_CALENDARS')) {
	$filtert = $user->id;
}

// Pagination parameters
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
if (!$sortorder) {
	$sortorder = "DESC,DESC";
	if ($search_status == 'todo') {
		$sortorder = "DESC,DESC";
	}
}
if (!$sortfield) {
	$sortfield = "a.datep,a.id";
	if ($search_status == 'todo') {
		$sortfield = "a.datep,a.id";
	}
}

// Security check
$socid = GETPOSTINT("search_socid") ? GETPOSTINT("search_socid") : GETPOSTINT("socid");
if ($user->socid) {
	$socid = $user->socid;
}
if ($socid < 0) {
	$socid = '';
}

$canedit = 1;
if (!$user->hasRight('agenda', 'myactions', 'read')) {
	accessforbidden();
}
if (!$user->hasRight('agenda', 'allactions', 'read')) {
	$canedit = 0;
}
if (!$user->hasRight('agenda', 'allactions', 'read') || $filter == 'mine') {	// If no permission to see all, we show only affected to me
	$filtert = $user->id;
}

$arrayfields = array(
	'a.id' => array('label' => "Ref", 'checked' => 1),
	'owner' => array('label' => "Owner", 'checked' => 1),
	'c.libelle' => array('label' => "Type", 'checked' => 1),
	'a.label' => array('label' => "Title", 'checked' => 1),
	'a.note' => array('label' => 'Description', 'checked' => 0),
	'a.datep' => array('label' => "DateStart", 'checked' => 1),
	'a.datep2' => array('label' => "DateEnd", 'checked' => 1),
	's.nom' => array('label' => "ThirdParty", 'checked' => 1),
	'a.fk_contact' => array('label' => "Contact", 'checked' => 0),
	'a.fk_element' => array('label' => "LinkedObject", 'checked' => 1, 'enabled' => (getDolGlobalString('AGENDA_SHOW_LINKED_OBJECT'))),
	'a.datec' => array('label' => 'DateCreation', 'checked' => 0, 'position' => 510),
	'a.tms' => array('label' => 'DateModification', 'checked' => 0, 'position' => 520),
	'a.percent' => array('label' => "Status", 'checked' => 1, 'position' => 1000)
);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');
'@phan-var-force array<string,array{label:string,checked?:int<0,1>,position?:int,help?:string}> $arrayfields';  // dol_sort_array looses type for Phan

$result = restrictedArea($user, 'agenda', 0, '', 'myactions');
if ($user->socid && $socid) {
	$result = restrictedArea($user, 'societe', $socid);
}


/*
 *	Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$mode = 'list';
	$massaction = '';
}

if (GETPOST("viewcal") || GETPOST("viewweek") || GETPOST("viewday")) {
	$param = '';
	if (is_array($_POST)) {
		foreach ($_POST as $key => $val) {
			$param .= '&'.$key.'='.urlencode($val);
		}
	}
	//print $param;
	header("Location: ".DOL_URL_ROOT.'/comm/action/index.php?'.$param);
	exit;
}

$parameters = array('id' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

// Selection of new fields
include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';
// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	//$actioncode='';
	$search_id = '';
	$search_title = '';
	$search_note = '';
	$datestart_dtstart = '';
	$datestart_dtend = '';
	$dateend_dtstart = '';
	$dateend_dtend = '';
	$actioncode = '';
	$search_status = '';
	$pid = '';
	$socid = '';
	$resourceid = '';
	$filter = '';
	$filtert = '';
	$usergroup = '';
	$toselect = array();
	$search_array_options = array();
}

if (empty($reshook) && !empty($massaction)) {
	unset($percent);

	switch ($massaction) {
		case 'set_all_events_to_todo':
			$percent = ActionComm::EVENT_TODO;
			break;

		case 'set_all_events_to_in_progress':
			$percent = ActionComm::EVENT_IN_PROGRESS;
			break;

		case 'set_all_events_to_finished':
			$percent = ActionComm::EVENT_FINISHED;
			break;
	}

	if (isset($percent)) {
		foreach ($toselect as $toselectid) {
			$result = $object->updatePercent($toselectid, $percent);
			if ($result < 0) {
				dol_print_error($db);
				break;
			}
		}
	}
}

// As mass deletion happens with a confirm step, $massaction is not use for the final step (deletion).
if (empty($reshook)) {
	$objectclass = 'ActionComm';
	$objectlabel = 'Events';
	$uploaddir = true;
	// Only users that can delete any event can remove records.
	$permissiontodelete = $user->hasRight('agenda', 'allactions', 'delete');
	$permissiontoadd = $user->hasRight('agenda', 'myactions', 'create');
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}

/*
 * View
 */

$form = new Form($db);
$userstatic = new User($db);
$formactions = new FormActions($db);

$actionstatic = new ActionComm($db);
$societestatic = new Societe($db);
$contactstatic = new Contact($db);

$nav = '';
$nav .= $form->selectDate($dateselect, 'dateselect', 0, 0, 1, '', 1, 0);
$nav .= ' <input type="submit" name="submitdateselect" class="button" value="'.$langs->trans("Refresh").'">';

$now = dol_now();

$help_url = 'EN:Module_Agenda_En|FR:Module_Agenda|ES:M&omodulodulo_Agenda|DE:Modul_Terminplanung';
$title = $langs->trans("Agenda");
llxHeader('', $title, $help_url);

// Define list of all external calendars
$listofextcals = array();

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($actioncode != '') {
	if (is_array($actioncode)) {
		foreach ($actioncode as $str_action) {
			$param .= "&search_actioncode[]=".urlencode($str_action);
		}
	} else {
		$param .= "&search_actioncode=".urlencode($actioncode);
	}
}
if ($resourceid > 0) {
	$param .= "&search_resourceid=".urlencode((string) ($resourceid));
}
if ($search_status != '') {
	$param .= "&search_status=".urlencode($search_status);
}
if ($filter) {
	$param .= "&search_filter=".urlencode($filter);
}
if ($filtert) {
	$param .= "&search_filtert=".urlencode($filtert);
}
if ($usergroup > 0) {
	$param .= "&search_usergroup=".urlencode((string) ($usergroup));
}
if ($socid > 0) {
	$param .= "&search_socid=".urlencode((string) ($socid));
}
if ($showbirthday) {
	$param .= "&search_showbirthday=1";
}
if ($pid) {
	$param .= "&search_projectid=".urlencode((string) ($pid));
}
if ($type) {
	$param .= "&search_type=".urlencode($type);
}
if ($search_id != '') {
	$param .= '&search_id='.urlencode($search_id);
}
if ($search_title != '') {
	$param .= '&search_title='.urlencode($search_title);
}
if ($search_note != '') {
	$param .= '&search_note='.urlencode($search_note);
}
if (GETPOSTINT('datestart_dtstartday')) {
	$param .= '&datestart_dtstartday='.GETPOSTINT('datestart_dtstartday');
}
if (GETPOSTINT('datestart_dtstartmonth')) {
	$param .= '&datestart_dtstartmonth='.GETPOSTINT('datestart_dtstartmonth');
}
if (GETPOSTINT('datestart_dtstartyear')) {
	$param .= '&datestart_dtstartyear='.GETPOSTINT('datestart_dtstartyear');
}
if (GETPOSTINT('datestart_dtendday')) {
	$param .= '&datestart_dtendday='.GETPOSTINT('datestart_dtendday');
}
if (GETPOSTINT('datestart_dtendmonth')) {
	$param .= '&datestart_dtendmonth='.GETPOSTINT('datestart_dtendmonth');
}
if (GETPOSTINT('datestart_dtendyear')) {
	$param .= '&datestart_dtendyear='.GETPOSTINT('datestart_dtendyear');
}
if (GETPOSTINT('dateend_dtstartday')) {
	$param .= '&dateend_dtstartday='.GETPOSTINT('dateend_dtstartday');
}
if (GETPOSTINT('dateend_dtstartmonth')) {
	$param .= '&dateend_dtstartmonth='.GETPOSTINT('dateend_dtstartmonth');
}
if (GETPOSTINT('dateend_dtstartyear')) {
	$param .= '&dateend_dtstartyear='.GETPOSTINT('dateend_dtstartyear');
}
if (GETPOSTINT('dateend_dtendday')) {
	$param .= '&dateend_dtendday='.GETPOSTINT('dateend_dtendday');
}
if (GETPOSTINT('dateend_dtendmonth')) {
	$param .= '&dateend_dtendmonth='.GETPOSTINT('dateend_dtendmonth');
}
if (GETPOSTINT('dateend_dtendyear')) {
	$param .= '&dateend_dtendyear='.GETPOSTINT('dateend_dtendyear');
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
if ($search_categ_cus != 0) {
	$param .= '&search_categ_cus='.urlencode((string) ($search_categ_cus));
}

// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

$paramnoactionodate = $param;

// List of mass actions available
$arrayofmassactions = array(
	'set_all_events_to_todo' => img_picto('', 'circle', 'class="pictofixedwidth font-status1"').$langs->trans("SetAllEventsToTodo"),
	'set_all_events_to_in_progress' => img_picto('', 'stop-circle', 'class="pictofixedwidth font-status2"').$langs->trans("SetAllEventsToInProgress"),
	'set_all_events_to_finished' => img_picto('', 'stop-circle', 'class="pictofixedwidth badge-status5"').$langs->trans("SetAllEventsToFinished"),
);
if ($user->hasRight('agenda', 'allactions', 'delete')) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if (isModEnabled('category') && $user->hasRight('agenda', 'myactions', 'create')) {
	$arrayofmassactions['preaffecttag'] = img_picto('', 'category', 'class="pictofixedwidth"').$langs->trans("AffectTag");
}
if (GETPOSTINT('nomassaction') || in_array($massaction, array('presend', 'predelete','preaffecttag'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

$sql = "SELECT";
if ($usergroup > 0) {
	$sql .= " DISTINCT";
}
$sql .= " s.nom as societe, s.rowid as socid, s.client, s.email as socemail,";
$sql .= " a.id, a.code, a.label, a.note, a.datep as dp, a.datep2 as dp2, a.fulldayevent, a.location,";
$sql .= " a.fk_user_author, a.fk_user_action,";
$sql .= " a.fk_contact, a.note, a.percent as percent,";
$sql .= " a.fk_element, a.elementtype, a.datec, a.tms as datem,";
$sql .= " c.code as type_code, c.libelle as type_label, c.color as type_color, c.type as type_type, c.picto as type_picto,";
$sql .= " sp.lastname, sp.firstname, sp.email, sp.phone, sp.address, sp.phone as phone_pro, sp.phone_mobile, sp.phone_perso, sp.fk_pays as country_id";

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

$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."actioncomm_extrafields as ef ON (a.id = ef.fk_object)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON a.fk_soc = s.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON a.fk_contact = sp.rowid";
$sql .= " ,".MAIN_DB_PREFIX."c_actioncomm as c";
// We must filter on resource table
if ($resourceid > 0) {
	$sql .= ", ".MAIN_DB_PREFIX."element_resources as r";
}
// We must filter on assignment table
if ($filtert > 0 || $usergroup > 0) {
	$sql .= ", ".MAIN_DB_PREFIX."actioncomm_resources as ar";
}
if ($usergroup > 0) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user as ugu ON ugu.fk_user = ar.fk_element";
}
$sql .= " WHERE c.id = a.fk_action";
$sql .= ' AND a.entity IN ('.getEntity('agenda').')';
// Condition on actioncode
if (!empty($actioncode)) {
	if (!getDolGlobalString('AGENDA_USE_EVENT_TYPE')) {
		if ($actioncode == 'AC_NON_AUTO') {
			$sql .= " AND c.type != 'systemauto'";
		} elseif ($actioncode == 'AC_ALL_AUTO') {
			$sql .= " AND c.type = 'systemauto'";
		} else {
			if ($actioncode == 'AC_OTH') {
				$sql .= " AND c.type != 'systemauto'";
			}
			if ($actioncode == 'AC_OTH_AUTO') {
				$sql .= " AND c.type = 'systemauto'";
			}
		}
	} else {
		if ($actioncode == 'AC_NON_AUTO') {
			$sql .= " AND c.type != 'systemauto'";
		} elseif ($actioncode == 'AC_ALL_AUTO') {
			$sql .= " AND c.type = 'systemauto'";
		} else {
			if (is_array($actioncode)) {
				$sql .= " AND c.code IN (".$db->sanitize("'".implode("','", $actioncode)."'", 1).")";
			} else {
				$sql .= " AND c.code IN (".$db->sanitize("'".implode("','", explode(',', $actioncode))."'", 1).")";
			}
		}
	}
}
if ($resourceid > 0) {
	$sql .= " AND r.element_type = 'action' AND r.element_id = a.id AND r.resource_id = ".((int) $resourceid);
}
if ($pid) {
	$sql .= " AND a.fk_project=".((int) $pid);
}
// If the internal user must only see his customers, force searching by him
$search_sale = 0;
if (!$user->hasRight('societe', 'client', 'voir')) {
	$search_sale = $user->id;
}
// Search on sale representative
if ($search_sale && $search_sale != '-1') {
	if ($search_sale == -2) {
		$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = a.fk_soc)";
	} elseif ($search_sale > 0) {
		$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = a.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
	}
}
// Search on socid
if ($socid) {
	$sql .= " AND a.fk_soc = ".((int) $socid);
}
// We must filter on assignment table
if ($filtert > 0 || $usergroup > 0) {
	$sql .= " AND ar.fk_actioncomm = a.id AND ar.element_type='user'";
}
if ($type) {
	$sql .= " AND c.id = ".((int) $type);
}
if ($search_status == '0') {
	$sql .= " AND a.percent = 0";
}
if ($search_status == 'na') {
	$sql .= " AND a.percent = -1";
}	// Not applicable
if ($search_status == '50') {
	$sql .= " AND (a.percent > 0 AND a.percent < 100)";
}	// Running already started
if ($search_status == '100') {
	$sql .= " AND a.percent = 100";
}
if ($search_status == 'done') {
	$sql .= " AND (a.percent = 100)";
}
if ($search_status == 'todo') {
	$sql .= " AND (a.percent >= 0 AND a.percent < 100)";
}
if ($search_id) {
	$sql .= natural_search("a.id", $search_id, 1);
}
if ($search_title) {
	$sql .= natural_search("a.label", $search_title);
}
if ($search_note) {
	$sql .= natural_search('a.note', $search_note);
}
// We must filter on assignment table
if ($filtert > 0 || $usergroup > 0) {
	$sql .= " AND (";
	if ($filtert > 0) {
		$sql .= "(ar.fk_element = ".((int) $filtert)." OR (ar.fk_element IS NULL AND a.fk_user_action = ".((int) $filtert)."))"; // The OR is for backward compatibility
	}
	if ($usergroup > 0) {
		$sql .= ($filtert > 0 ? " OR " : "")." ugu.fk_usergroup = ".((int) $usergroup);
	}
	$sql .= ")";
}

// The second or of next test is to take event with no end date (we suppose duration is 1 hour in such case)
if ($dateselect > 0) {
	$sql .= " AND ((a.datep2 >= '".$db->idate($dateselect)."' AND a.datep <= '".$db->idate($dateselect + 3600 * 24 - 1)."') OR (a.datep2 IS NULL AND a.datep > '".$db->idate($dateselect - 3600)."' AND a.datep <= '".$db->idate($dateselect + 3600 * 24 - 1)."'))";
}
if ($datestart_dtstart > 0) {
	$sql .= " AND a.datep >= '".$db->idate($datestart_dtstart)."'";
}
if ($datestart_dtend > 0) {
	$sql .= " AND a.datep <= '".$db->idate($datestart_dtend)."'";
}
if ($dateend_dtstart > 0) {
	$sql .= " AND a.datep2 >= '".$db->idate($dateend_dtstart)."'";
}
if ($dateend_dtend > 0) {
	$sql .= " AND a.datep2 <= '".$db->idate($dateend_dtend)."'";
}

// Search in categories, -1 is all and -2 is no categories
if ($search_categ_cus != -1) {
	if ($search_categ_cus == -2) {
		$sql .= " AND NOT EXISTS (SELECT ca.fk_actioncomm FROM ".MAIN_DB_PREFIX."categorie_actioncomm as ca WHERE ca.fk_actioncomm = a.id)";
	} elseif ($search_categ_cus > 0) {
		$sql .= " AND EXISTS (SELECT ca.fk_actioncomm FROM ".MAIN_DB_PREFIX."categorie_actioncomm as ca WHERE ca.fk_actioncomm = a.id AND ca.fk_categorie IN (".$db->sanitize($search_categ_cus)."))";
	}
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

$arrayofselected = is_array($toselect) ? $toselect : array();

// Local calendar
$newtitle = '<div class="nowrap clear inline-block minheight30">';
$newtitle .= '<input type="checkbox" id="check_mytasks" name="check_mytasks" checked disabled> '.$langs->trans("LocalAgenda").' &nbsp; ';
$newtitle .= '</div>';
//$newtitle=$langs->trans($title);

$tabactive = 'cardlist';

$head = calendars_prepare_head($param);

print '<form method="POST" id="searchFormList" class="listactionsfilter" action="'.$_SERVER["PHP_SELF"].'">'."\n";

if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="type" value="'.$type.'">';
$nav = '';

if ($filter) {
	$nav .= '<input type="hidden" name="search_filter" value="'.$filter.'">';
}
if ($showbirthday) {
	$nav .= '<input type="hidden" name="search_showbirthday" value="1">';
}
print $nav;

//print dol_get_fiche_head($head, $tabactive, $langs->trans('Agenda'), 0, 'action');
//print_actions_filter($form, $canedit, $search_status, $year, $month, $day, $showbirthday, 0, $filtert, 0, $pid, $socid, $action, -1, $actioncode, $usergroup, '', $resourceid);
//print dol_get_fiche_end();


$s = $newtitle;

// Calendars from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('addCalendarChoice', $parameters, $object, $action);
if (empty($reshook)) {
	$s .= $hookmanager->resPrint;
} elseif ($reshook > 1) {
	$s = $hookmanager->resPrint;
}

$viewyear = is_object($object) ? dol_print_date($object->datep, '%Y') : '';
$viewmonth = is_object($object) ? dol_print_date($object->datep, '%m') : '';
$viewday = is_object($object) ? dol_print_date($object->datep, '%d') : '';

$viewmode = '<div class="navmode inline-block">';

$viewmode .= '<a class="btnTitle'.($mode == 'list' ? ' btnTitleSelected' : '').' btnTitleSelected reposition" href="'.DOL_URL_ROOT.'/comm/action/list.php?mode=show_list&restore_lastsearch_values=1'.$paramnoactionodate.'">';
//$viewmode .= '<span class="fa paddingleft imgforviewmode valignmiddle btnTitle-icon">';
$viewmode .= img_picto($langs->trans("List"), 'object_calendarlist', 'class="imgforviewmode pictoactionview block"');
//$viewmode .= '</span>';
$viewmode .= '<span class="valignmiddle text-plus-circle btnTitle-label hideonsmartphone inline-block width75 divoverflow">'.$langs->trans("ViewList").'</span></a>';

$viewmode .= '<a class="btnTitle'.($mode == 'show_month' ? ' btnTitleSelected' : '').' reposition" href="'.DOL_URL_ROOT.'/comm/action/index.php?mode=show_month&year='.$viewyear.'&month='.$viewmonth.'&day='.$viewday.$paramnoactionodate.'">';
//$viewmode .= '<span class="fa paddingleft imgforviewmode valignmiddle btnTitle-icon">';
$viewmode .= img_picto($langs->trans("ViewCal"), 'object_calendarmonth', 'class="pictoactionview block"');
//$viewmode .= '</span>';
$viewmode .= '<span class="valignmiddle text-plus-circle btnTitle-label hideonsmartphone inline-block width75 divoverflow">'.$langs->trans("ViewCal").'</span></a>';

$viewmode .= '<a class="btnTitle'.($mode == 'show_week' ? ' btnTitleSelected' : '').' reposition" href="'.DOL_URL_ROOT.'/comm/action/index.php?mode=show_week&year='.$viewyear.'&month='.$viewmonth.'&day='.$viewday.$paramnoactionodate.'">';
//$viewmode .= '<span class="fa paddingleft imgforviewmode valignmiddle btnTitle-icon">';
$viewmode .= img_picto($langs->trans("ViewWeek"), 'object_calendarweek', 'class="pictoactionview block"');
//$viewmode .= '</span>';
$viewmode .= '<span class="valignmiddle text-plus-circle btnTitle-label hideonsmartphone inline-block width75 divoverflow">'.$langs->trans("ViewWeek").'</span></a>';

$viewmode .= '<a class="btnTitle'.($mode == 'show_day' ? ' btnTitleSelected' : '').' reposition" href="'.DOL_URL_ROOT.'/comm/action/index.php?mode=show_day&year='.$viewyear.'&month='.$viewmonth.'&day='.$viewday.$paramnoactionodate.'">';
//$viewmode .= '<span class="fa paddingleft imgforviewmode valignmiddle btnTitle-icon">';
$viewmode .= img_picto($langs->trans("ViewDay"), 'object_calendarday', 'class="pictoactionview block"');
//$viewmode .= '</span>';
$viewmode .= '<span class="valignmiddle text-plus-circle btnTitle-label hideonsmartphone inline-block width75 divoverflow">'.$langs->trans("ViewDay").'</span></a>';

$viewmode .= '<a class="btnTitle'.($mode == 'show_peruser' ? ' btnTitleSelected' : '').' reposition marginrightonly" href="'.DOL_URL_ROOT.'/comm/action/peruser.php?mode=show_peruser&year='.$viewyear.'&month='.$viewmonth.'&day='.$viewday.$paramnoactionodate.'">';
//$viewmode .= '<span class="fa paddingleft imgforviewmode valignmiddle btnTitle-icon">';
$viewmode .= img_picto($langs->trans("ViewPerUser"), 'object_calendarperuser', 'class="pictoactionview block"');
//$viewmode .= '</span>';
$viewmode .= '<span class="valignmiddle text-plus-circle btnTitle-label hideonsmartphone inline-block width75 divoverflow" title="'.dolPrintHTML($langs->trans("ViewPerUser")).'">'.$langs->trans("ViewPerUser").'</span></a>';

// Add more views from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('addCalendarView', $parameters, $object, $action);
if (empty($reshook)) {
	$viewmode .= $hookmanager->resPrint;
} elseif ($reshook > 1) {
	$viewmode = $hookmanager->resPrint;
}

$viewmode .= '</div>';

$viewmode .= '<span class="marginrightonly"></span>';


$tmpforcreatebutton = dol_getdate(dol_now(), true);

$newparam = '&month='.str_pad((string) $month, 2, "0", STR_PAD_LEFT).'&year='.$tmpforcreatebutton['year'];

$url = DOL_URL_ROOT.'/comm/action/card.php?action=create';
$url .= '&apyear='.$tmpforcreatebutton['year'].'&apmonth='.$tmpforcreatebutton['mon'].'&apday='.$tmpforcreatebutton['mday'].'&aphour='.$tmpforcreatebutton['hours'].'&apmin='.$tmpforcreatebutton['minutes'];
$url .= '&backtopage='.urlencode($_SERVER["PHP_SELF"].($newparam ? '?'.$newparam : ''));

$newcardbutton = dolGetButtonTitle($langs->trans('AddAction'), '', 'fa fa-plus-circle', $url, '', $user->hasRight('agenda', 'myactions', 'create') || $user->hasRight('agenda', 'allactions', 'create'));

$param .= '&mode='.urlencode($mode);

print_barre_liste($langs->trans("Agenda"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, is_numeric($nbtotalofrecords) ? -1 * $nbtotalofrecords : $nbtotalofrecords, 'object_action', 0, $nav.$newcardbutton, '', $limit, 0, 0, 1, $viewmode);

print $s;

$objecttmp = new ActionComm($db);
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

$moreforfilter = '';

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')); // This also change content of $arrayfields
if ($massactionbutton) {
	$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
}
$i = 0;

print '<div class="liste_titre liste_titre_bydiv centpercent">';
print_actions_filter($form, $canedit, $search_status, $year, $month, $day, $showbirthday, 0, $filtert, 0, $pid, $socid, $action, -1, $actioncode, $usergroup, '', $resourceid, $search_categ_cus);
print '</div>';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

print '<tr class="liste_titre_filter">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre" align="middle">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}
if (!empty($arrayfields['a.id']['checked'])) {
	print '<td class="liste_titre"><input type="text" class="maxwidth50" name="search_id" value="'.$search_id.'"></td>';
}
if (!empty($arrayfields['owner']['checked'])) {
	print '<td class="liste_titre"></td>';
}
if (!empty($arrayfields['c.libelle']['checked'])) {
	print '<td class="liste_titre"></td>';
}
if (!empty($arrayfields['a.label']['checked'])) {
	print '<td class="liste_titre"><input type="text" class="maxwidth75" name="search_title" value="'.$search_title.'"></td>';
}
if (!empty($arrayfields['a.note']['checked'])) {
	print '<td class="liste_titre"><input type="text" class="maxwidth75" name="search_note" value="'.$search_note.'"></td>';
}
if (!empty($arrayfields['a.datep']['checked'])) {
	print '<td class="liste_titre nowraponall" align="center">';
	print '<div class="nowrap">';
	print $form->selectDate($datestart_dtstart, 'datestart_dtstart', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'), 'tzuserrel');
	print '</div>';
	print '<div class="nowrap">';
	print $form->selectDate($datestart_dtend, 'datestart_dtend', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('To'), 'tzuserrel');
	print '</div>';
	print '</td>';
}
if (!empty($arrayfields['a.datep2']['checked'])) {
	print '<td class="liste_titre nowraponall" align="center">';
	print '<div class="nowrap">';
	print $form->selectDate($dateend_dtstart, 'dateend_dtstart', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'), 'tzuserrel');
	print '</div>';
	print '<div class="nowrap">';
	print $form->selectDate($dateend_dtend, 'dateend_dtend', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('To'), 'tzuserrel');
	print '</div>';
	print '</td>';
}
if (!empty($arrayfields['s.nom']['checked'])) {
	print '<td class="liste_titre"></td>';
}
if (!empty($arrayfields['a.fk_contact']['checked'])) {
	print '<td class="liste_titre"></td>';
}
if (!empty($arrayfields['a.fk_element']['checked'])) {
	print '<td class="liste_titre"></td>';
}

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields' => $arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

if (!empty($arrayfields['a.datec']['checked'])) {
	print '<td class="liste_titre"></td>';
}
if (!empty($arrayfields['a.tms']['checked'])) {
	print '<td class="liste_titre"></td>';
}
if (!empty($arrayfields['a.percent']['checked'])) {
	print '<td class="liste_titre center parentonrightofpage">';
	$formactions->form_select_status_action('formaction', $search_status, 1, 'search_status', 1, 2, 'search_status width100 onrightofpage');
	print '</td>';
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
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['a.id']['checked'])) {
	print_liste_field_titre($arrayfields['a.id']['label'], $_SERVER["PHP_SELF"], "a.id", $param, "", "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['owner']['checked'])) {
	print_liste_field_titre($arrayfields['owner']['label'], $_SERVER["PHP_SELF"], "", $param, "", "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.libelle']['checked'])) {
	print_liste_field_titre($arrayfields['c.libelle']['label'], $_SERVER["PHP_SELF"], "c.libelle", $param, "", "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['a.label']['checked'])) {
	print_liste_field_titre($arrayfields['a.label']['label'], $_SERVER["PHP_SELF"], "a.label", $param, "", "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['a.note']['checked'])) {
	print_liste_field_titre($arrayfields['a.note']['label'], $_SERVER["PHP_SELF"], "a.note", $param, "", "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
//if (!empty($conf->global->AGENDA_USE_EVENT_TYPE))
if (!empty($arrayfields['a.datep']['checked'])) {
	print_liste_field_titre($arrayfields['a.datep']['label'], $_SERVER["PHP_SELF"], "a.datep,a.id", $param, '', '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['a.datep2']['checked'])) {
	print_liste_field_titre($arrayfields['a.datep2']['label'], $_SERVER["PHP_SELF"], "a.datep2", $param, '', '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.nom']['checked'])) {
	print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], "s.nom", $param, "", "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['a.fk_contact']['checked'])) {
	print_liste_field_titre($arrayfields['a.fk_contact']['label'], $_SERVER["PHP_SELF"], "", $param, "", "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['a.fk_element']['checked'])) {
	print_liste_field_titre($arrayfields['a.fk_element']['label'], $_SERVER["PHP_SELF"], "", $param, "", "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

if (!empty($arrayfields['a.datec']['checked'])) {
	print_liste_field_titre($arrayfields['a.datec']['label'], $_SERVER["PHP_SELF"], "a.datec,a.id", $param, "", '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['a.tms']['checked'])) {
	print_liste_field_titre($arrayfields['a.tms']['label'], $_SERVER["PHP_SELF"], "a.tms,a.id", $param, "", '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
// Status
if (!empty($arrayfields['a.percent']['checked'])) {
	print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "a.percent", $param, "", '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'maxwidthsearch center ');
	$totalarray['nbfield']++;
}
print "</tr>\n";

$now = dol_now();
$delay_warning = $conf->global->MAIN_DELAY_ACTIONS_TODO * 24 * 60 * 60;
$today_start_time = dol_mktime(0, 0, 0, date('m', $now), date('d', $now), date('Y', $now));

require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
$caction = new CActionComm($db);
$arraylist = $caction->liste_array(1, 'code', '', (!getDolGlobalString('AGENDA_USE_EVENT_TYPE') ? 1 : 0), '', 1);
$contactListCache = array();

// Loop on record
// --------------------------------------------------------------------
$i = 0;
//$savnbfield = $totalarray['nbfield'];
//$totalarray['nbfield'] = 0;
$imaxinloop = ($limit ? min($num, $limit) : $num);
$cache_user_list = array();
while ($i < $imaxinloop) {
	$obj = $db->fetch_object($resql);
	if (empty($obj)) {
		break; // Should not happen
	}

	// Store properties in $object
	$object->setVarsFromFetchObj($obj);

	// Discard auto action if option is on
	if (getDolGlobalString('AGENDA_ALWAYS_HIDE_AUTO') && $obj->type_code == 'AC_OTH_AUTO') {
		$i++;
		continue;
	}

	$actionstatic->id = $obj->id;
	$actionstatic->ref = $obj->id;
	$actionstatic->code = $obj->code;
	$actionstatic->type_code = $obj->type_code;
	$actionstatic->type_label = $obj->type_label;
	$actionstatic->type_picto = $obj->type_picto;
	$actionstatic->type_color = $obj->type_color;
	$actionstatic->label = $obj->label;
	$actionstatic->location = $obj->location;
	$actionstatic->note_private = dol_htmlentitiesbr($obj->note);
	$actionstatic->datep = $db->jdate($obj->dp);
	$actionstatic->percentage = $obj->percent;
	$actionstatic->authorid = $obj->fk_user_author;
	$actionstatic->userownerid = $obj->fk_user_action;

	// Initialize $this->userassigned && this->socpeopleassigned array && this->userownerid
	// but only if we need it
	if (!empty($arrayfields['a.fk_contact']['checked'])) {
		$actionstatic->fetchResources();
	}

	// cache of user list (owners)
	if ($obj->fk_user_action > 0 && !isset($cache_user_list[$obj->fk_user_action])) {
		$userstatic = new User($db);
		$res = $userstatic->fetch($obj->fk_user_action);
		if ($res > 0) {
			$cache_user_list[$obj->fk_user_action] = $userstatic;
		}
	}

	// get event style for user owner
	$event_owner_style = '';
	// We decide to choose color of owner of event (event->userownerid is user id of owner, event->userassigned contains all users assigned to event)
	if ($obj->fk_user_action > 0 && $cache_user_list[$obj->fk_user_action]->color != '') {
		$event_owner_style .= 'border-left: #' . $cache_user_list[$obj->fk_user_action]->color . ' 5px solid;';
	}

	// get event style for start and end date
	$event_more_class = '';
	$event_start_date_css = '';
	$event_end_date_css = '';
	$event_start_date_time = $actionstatic->datep;
	if ($event_start_date_time > $now) {
		// future event
		$event_more_class = 'event-future';
		$event_start_date_css = $event_end_date_css = $event_more_class;
	} else {
		if ($obj->fulldayevent == 1) {
			$today_start_date_time = $today_start_time;
		} else {
			$today_start_date_time = $now;
		}

		// check event end date
		$event_end_date_time = $db->jdate($obj->dp2);
		if ($event_end_date_time != null && $event_end_date_time < $today_start_date_time) {
			// past event
			$event_more_class = 'event-past';
		} elseif ($event_end_date_time == null && $event_start_date_time < $today_start_date_time) {
			// past event
			$event_more_class = 'event-past';
		} else {
			// current event
			$event_more_class = 'event-current';
		}
		$event_start_date_css = $event_end_date_css = $event_more_class;
	}
	$event_start_date_css = $event_end_date_css = $event_more_class;

	print '<tr class="oddeven' . ($event_more_class != '' ? ' '.$event_more_class : '') . '">';
	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($obj->id, $arrayofselected)) {
				$selected = 1;
			}
			print '<input id="cb'.$obj->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
	}
	// Ref
	if (!empty($arrayfields['a.id']['checked'])) {
		print '<td class="nowraponall">';
		print $actionstatic->getNomUrl(1, -1);
		print '</td>';
	}

	// User owner
	if (!empty($arrayfields['owner']['checked'])) {
		//print '<td class="tdoverflowmax150"' . ($event_owner_style != '' ? ' style="'.$event_owner_style.'"' : '') . '>';
		print '<td class="tdoverflowmax150">';
		if ($obj->fk_user_action > 0 && !isset($cache_user_list[$obj->fk_user_action])) {
			$userstatic = new User($db);
			$res = $userstatic->fetch($obj->fk_user_action);
			if ($res > 0) {
				$cache_user_list[$obj->fk_user_action] = $userstatic;
			}
		}
		if (isset($cache_user_list[$obj->fk_user_action])) {
			print $cache_user_list[$obj->fk_user_action]->getNomUrl(-1);
		} else {
			print '&nbsp;';
		}
		print '</td>';
	}

	// Type
	if (!empty($arrayfields['c.libelle']['checked'])) {
		print '<td class="nowraponall">';
		print $actionstatic->getTypePicto();
		$labeltype = $obj->type_code;
		if (!getDolGlobalString('AGENDA_USE_EVENT_TYPE') && empty($arraylist[$labeltype])) {
			$labeltype = 'AC_OTH';
		}
		if (!empty($actionstatic->code) && preg_match('/^TICKET_MSG/', $actionstatic->code)) {
			$labeltype = $langs->trans("Message");
		} else {
			if (!empty($arraylist[$labeltype])) {
				$labeltype = $arraylist[$labeltype];
			}
			if ($obj->type_code == 'AC_OTH_AUTO' && ($obj->type_code != $obj->code) && $labeltype && !empty($arraylist[$obj->code])) {
				$labeltype .= ' - '.$arraylist[$obj->code]; // Use code in priority on type_code
			}
		}
		print dol_trunc($labeltype, 28);
		print '</td>';
	}

	// Label
	if (!empty($arrayfields['a.label']['checked'])) {
		print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($actionstatic->label).'">';
		print $actionstatic->label;
		print '</td>';
	}

	// Description
	if (!empty($arrayfields['a.note']['checked'])) {
		$text = dolGetFirstLineOfText(dol_string_nohtmltag($actionstatic->note_private, 1));
		print '<td class="tdoverflow200" title="'.dol_escape_htmltag($text).'">';
		print $form->textwithtooltip(dol_trunc($text, 48), $actionstatic->note_private);
		print '</td>';
	}

	$formatToUse = $obj->fulldayevent ? 'day' : 'dayhour';

	// Start date
	if (!empty($arrayfields['a.datep']['checked'])) {
		print '<td class="center nowraponall'.($event_start_date_css ? ' '.$event_start_date_css : '').'"><span>';
		if (empty($obj->fulldayevent)) {
			print dol_print_date($db->jdate($obj->dp), $formatToUse, 'tzuserrel');
		} else {
			$tzforfullday = getDolGlobalString('MAIN_STORE_FULL_EVENT_IN_GMT');
			print dol_print_date($db->jdate($obj->dp), $formatToUse, ($tzforfullday ? $tzforfullday : 'tzuserrel'));
		}
		print '</span>';
		$late = 0;
		if ($actionstatic->hasDelay() && $actionstatic->percentage >= 0 && $actionstatic->percentage < 100) {
			$late = 1;
		}
		if ($late) {
			print img_warning($langs->trans("Late")).' ';
		}
		print '</td>';
	}

	// End date
	if (!empty($arrayfields['a.datep2']['checked'])) {
		print '<td class="center nowraponall'.($event_end_date_css ? ' '.$event_end_date_css : '').'"><span>';
		if (empty($obj->fulldayevent)) {
			print dol_print_date($db->jdate($obj->dp2), $formatToUse, 'tzuserrel');
		} else {
			$tzforfullday = getDolGlobalString('MAIN_STORE_FULL_EVENT_IN_GMT');
			print dol_print_date($db->jdate($obj->dp2), $formatToUse, ($tzforfullday ? $tzforfullday : 'tzuserrel'));
		}
		print '</span>';
		print '</td>';
	}

	// Third party
	if (!empty($arrayfields['s.nom']['checked'])) {
		print '<td class="tdoverflowmax150">';
		if ($obj->socid > 0) {
			$societestatic->id = $obj->socid;
			$societestatic->client = $obj->client;
			$societestatic->name = $obj->societe;
			$societestatic->email = $obj->socemail;

			print $societestatic->getNomUrl(1, '', 28);
		} else {
			print '&nbsp;';
		}
		print '</td>';
	}

	// Contact
	if (!empty($arrayfields['a.fk_contact']['checked'])) {
		print '<td class="tdoverflowmax100">';

		if (!empty($actionstatic->socpeopleassigned)) {
			$contactList = array();
			foreach ($actionstatic->socpeopleassigned as $socpeopleassigned) {
				if (!isset($contactListCache[$socpeopleassigned['id']])) {
					// if no cache found we fetch it
					$contact = new Contact($db);
					if ($contact->fetch($socpeopleassigned['id']) > 0) {
						$contactListCache[$socpeopleassigned['id']] = $contact->getNomUrl(1, '', 0);
						$contactList[] = $contact->getNomUrl(1, '', 0);
					}
				} else {
					// use cache
					$contactList[] = $contactListCache[$socpeopleassigned['id']];
				}
			}
			if (!empty($contactList)) {
				print implode(', ', $contactList);
			}
		} elseif ($obj->fk_contact > 0) { //keep for retrocompatibility with faraway event
			$contactstatic->id = $obj->fk_contact;
			$contactstatic->email = $obj->email;
			$contactstatic->lastname = $obj->lastname;
			$contactstatic->firstname = $obj->firstname;
			$contactstatic->phone_pro = $obj->phone_pro;
			$contactstatic->phone_mobile = $obj->phone_mobile;
			$contactstatic->phone_perso = $obj->phone_perso;
			$contactstatic->country_id = $obj->country_id;
			print $contactstatic->getNomUrl(1, '', 0);
		} else {
			print "&nbsp;";
		}
		print '</td>';
	}

	// Linked object
	if (!empty($arrayfields['a.fk_element']['checked'])) {
		print '<td class="tdoverflowmax150">';
		//var_dump($obj->fkelement.' '.$obj->elementtype);
		if ($obj->fk_element > 0 && !empty($obj->elementtype)) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			print dolGetElementUrl($obj->fk_element, $obj->elementtype, 1);
		} else {
			print "&nbsp;";
		}
		print '</td>';
	}

	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
	// Fields from hook
	$parameters = array('arrayfields' => $arrayfields, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
	$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	// Date creation
	if (!empty($arrayfields['a.datec']['checked'])) {
		// Status/Percent
		print '<td align="center" class="nowrap">'.dol_print_date($db->jdate($obj->datec), 'dayhour', 'tzuserrel').'</td>';
	}
	// Date update
	if (!empty($arrayfields['a.tms']['checked'])) {
		print '<td align="center" class="nowrap">'.dol_print_date($db->jdate($obj->datem), 'dayhour', 'tzuserrel').'</td>';
	}
	if (!empty($arrayfields['a.percent']['checked'])) {
		// Status/Percent
		$datep = $db->jdate($obj->dp);
		print '<td align="center" class="nowrap">'.$actionstatic->LibStatut($obj->percent, 5, 0, $datep).'</td>';
	}
	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($obj->id, $arrayofselected)) {
				$selected = 1;
			}
			print '<input id="cb'.$obj->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
	}

	print '</tr>'."\n";

	$i++;
}
// If no record found
if ($num == 0) {
	print '<tr><td colspan="'.$totalarray['nbfield'].'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
}


print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";

$db->free($resql);

// End of page
llxFooter();
$db->close();
