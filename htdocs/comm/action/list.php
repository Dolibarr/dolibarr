<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2020		Tobias Sekan		<tobias.sekan@startmail.com>
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

if (!defined("NOREDIRECTBYMAINTOLOGIN")) {
	define('NOREDIRECTBYMAINTOLOGIN', 1);
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

// Load translation files required by the page
$langs->loadLangs(array("users", "companies", "agenda", "commercial", "other", "orders", "bills"));

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'actioncommlist'; // To manage different context of search
$resourceid = GETPOST("search_resourceid", "int") ?GETPOST("search_resourceid", "int") : GETPOST("resourceid", "int");
$pid = GETPOST("search_projectid", 'int', 3) ?GETPOST("search_projectid", 'int', 3) : GETPOST("projectid", 'int', 3);
$search_status = (GETPOST("search_status", 'aZ09') != '') ? GETPOST("search_status", 'aZ09') : GETPOST("status", 'aZ09');
$type = GETPOST('search_type', 'alphanohtml') ?GETPOST('search_type', 'alphanohtml') : GETPOST('type', 'alphanohtml');
$optioncss = GETPOST('optioncss', 'alpha');
$year = GETPOST("year", 'int');
$month = GETPOST("month", 'int');
$day = GETPOST("day", 'int');
$toselect = GETPOST('toselect', 'array');
$confirm = GETPOST('confirm', 'alpha');

// Set actioncode (this code must be same for setting actioncode into peruser, listacton and index)
if (GETPOST('search_actioncode', 'array')) {
	$actioncode = GETPOST('search_actioncode', 'array', 3);
	if (!count($actioncode)) {
		$actioncode = '0';
	}
} else {
	$actioncode = GETPOST("search_actioncode", "alpha", 3) ?GETPOST("search_actioncode", "alpha", 3) : (GETPOST("search_actioncode") == '0' ? '0' : (empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE) ? '' : $conf->global->AGENDA_DEFAULT_FILTER_TYPE));
}
if ($actioncode == '' && empty($actioncodearray)) {
	$actioncode = (empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE) ? '' : $conf->global->AGENDA_DEFAULT_FILTER_TYPE);
}
$search_id = GETPOST('search_id', 'alpha');
$search_title = GETPOST('search_title', 'alpha');
$search_note = GETPOST('search_note', 'alpha');

$dateselect = dol_mktime(0, 0, 0, GETPOST('dateselectmonth', 'int'), GETPOST('dateselectday', 'int'), GETPOST('dateselectyear', 'int'), 'tzuserrel');
$datestart = dol_mktime(0, 0, 0, GETPOST('datestartmonth', 'int'), GETPOST('datestartday', 'int'), GETPOST('datestartyear', 'int'), 'tzuserrel');
$dateend = dol_mktime(0, 0, 0, GETPOST('dateendmonth', 'int'), GETPOST('dateendday', 'int'), GETPOST('dateendyear', 'int'), 'tzuserrel');
if ($search_status == '' && !GETPOSTISSET('search_status')) {
	$search_status = (empty($conf->global->AGENDA_DEFAULT_FILTER_STATUS) ? '' : $conf->global->AGENDA_DEFAULT_FILTER_STATUS);
}
if (empty($action) && !GETPOSTISSET('action')) {
	$action = (empty($conf->global->AGENDA_DEFAULT_VIEW) ? 'show_month' : $conf->global->AGENDA_DEFAULT_VIEW);
}

$filter = GETPOST("search_filter", 'alpha', 3) ?GETPOST("search_filter", 'alpha', 3) : GETPOST("filter", 'alpha', 3);
$filtert = GETPOST("search_filtert", "int", 3) ?GETPOST("search_filtert", "int", 3) : GETPOST("filtert", "int", 3);
$usergroup = GETPOST("search_usergroup", "int", 3) ?GETPOST("search_usergroup", "int", 3) : GETPOST("usergroup", "int", 3);
$showbirthday = empty($conf->use_javascript_ajax) ? (GETPOST("search_showbirthday", "int") ?GETPOST("search_showbirthday", "int") : GETPOST("showbirthday", "int")) : 1;

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new ActionComm($db);
$hookmanager->initHooks(array('agendalist'));

$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');
// If not choice done on calendar owner, we filter on user.
if (empty($filtert) && empty($conf->global->AGENDA_ALL_CALENDARS)) {
	$filtert = $user->id;
}

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
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
$socid = GETPOST("search_socid", 'int') ?GETPOST("search_socid", 'int') : GETPOST("socid", 'int');
if ($user->socid) {
	$socid = $user->socid;
}
if ($socid < 0) {
	$socid = '';
}

$canedit = 1;
if (!$user->rights->agenda->myactions->read) {
	accessforbidden();
}
if (!$user->rights->agenda->allactions->read) {
	$canedit = 0;
}
if (!$user->rights->agenda->allactions->read || $filter == 'mine') {	// If no permission to see all, we show only affected to me
	$filtert = $user->id;
}

$arrayfields = array(
	'a.id'=>array('label'=>"Ref", 'checked'=>1),
	'owner'=>array('label'=>"Owner", 'checked'=>1),
	'c.libelle'=>array('label'=>"Type", 'checked'=>1),
	'a.label'=>array('label'=>"Title", 'checked'=>1),
	'a.note'=>array('label'=>'Description', 'checked'=>0),
	'a.datep'=>array('label'=>"DateStart", 'checked'=>1),
	'a.datep2'=>array('label'=>"DateEnd", 'checked'=>1),
	's.nom'=>array('label'=>"ThirdParty", 'checked'=>1),
	'a.fk_contact'=>array('label'=>"Contact", 'checked'=>0),
	'a.fk_element'=>array('label'=>"LinkedObject", 'checked'=>1, 'enabled'=>(!empty($conf->global->AGENDA_SHOW_LINKED_OBJECT))),
	'a.datec'=>array('label'=>'DateCreation', 'checked'=>0, 'position'=>510),
	'a.tms'=>array('label'=>'DateModification', 'checked'=>0, 'position'=>520),
	'a.percent'=>array('label'=>"Status", 'checked'=>1, 'position'=>1000)
);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

$result = restrictedArea($user, 'agenda', 0, '', 'myactions');
if ($user->socid && $socid) {
	$result = restrictedArea($user, 'societe', $socid);
}


/*
 *	Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list'; $massaction = '';
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

$parameters = array('id'=>$socid);
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
	$datestart = '';
	$dateend = '';
	$search_status = '';
	$toselect = '';
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
	$permissiontodelete = $user->rights->agenda->allactions->delete;
	$permissiontoadd = $user->rights->agenda->myactions->create;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}

/*
 *  View
 */

$form = new Form($db);
$userstatic = new User($db);
$formactions = new FormActions($db);

$nav = '';
$nav .= $form->selectDate($dateselect, 'dateselect', 0, 0, 1, '', 1, 0);
$nav .= ' <input type="submit" name="submitdateselect" class="button" value="'.$langs->trans("Refresh").'">';

$now = dol_now();

$help_url = 'EN:Module_Agenda_En|FR:Module_Agenda|ES:M&omodulodulo_Agenda';
llxHeader('', $langs->trans("Agenda"), $help_url);

// Define list of all external calendars
$listofextcals = array();

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.urlencode($limit);
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
	$param .= "&search_resourceid=".urlencode($resourceid);
}
if ($search_status != '' && $search_status > -1) {
	$param .= "&search_status=".urlencode($search_status);
}
if ($filter) {
	$param .= "&search_filter=".urlencode($filter);
}
if ($filtert) {
	$param .= "&search_filtert=".urlencode($filtert);
}
if ($usergroup > 0) {
	$param .= "&search_usergroup=".urlencode($usergroup);
}
if ($socid > 0) {
	$param .= "&search_socid=".urlencode($socid);
}
if ($showbirthday) {
	$param .= "&search_showbirthday=1";
}
if ($pid) {
	$param .= "&search_projectid=".urlencode($pid);
}
if ($type) {
	$param .= "&search_type=".urlencode($type);
}
if ($search_id != '') {
	$param .= '&search_title='.urlencode($search_id);
}
if ($search_title != '') {
	$param .= '&search_title='.urlencode($search_title);
}
if ($search_note != '') {
	$param .= '&search_note='.urlencode($search_note);
}
if (GETPOST('datestartday', 'int')) {
	$param .= '&datestartday='.GETPOST('datestartday', 'int');
}
if (GETPOST('datestartmonth', 'int')) {
	$param .= '&datestartmonth='.GETPOST('datestartmonth', 'int');
}
if (GETPOST('datestartyear', 'int')) {
	$param .= '&datestartyear='.GETPOST('datestartyear', 'int');
}
if (GETPOST('dateendday', 'int')) {
	$param .= '&dateendday='.GETPOST('dateendday', 'int');
}
if (GETPOST('dateendmonth', 'int')) {
	$param .= '&dateendmonth='.GETPOST('dateendmonth', 'int');
}
if (GETPOST('dateendyear', 'int')) {
	$param .= '&dateendyear='.GETPOST('dateendyear', 'int');
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

$paramnoactionodate = $param;

// List of mass actions available
$arrayofmassactions = array(
	'set_all_events_to_todo' => $langs->trans("SetAllEventsToTodo"),
	'set_all_events_to_in_progress' => $langs->trans("SetAllEventsToInProgress"),
	'set_all_events_to_finished' => $langs->trans("SetAllEventsToFinished"),
);
if ($user->rights->agenda->allactions->delete) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if ($user->rights->agenda->myactions->create) {
	$arrayofmassactions['preaffecttag'] = img_picto('', 'category', 'class="pictofixedwidth"').$langs->trans("AffectTag");
}
if (GETPOST('nomassaction', 'int') || in_array($massaction, array('presend', 'predelete','preaffecttag'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

$sql = "SELECT";
if ($usergroup > 0) {
	$sql .= " DISTINCT";
}
$sql .= " s.nom as societe, s.rowid as socid, s.client, s.email as socemail,";
$sql .= " a.id, a.code, a.label, a.note, a.datep as dp, a.datep2 as dp2, a.fulldayevent, a.location,";
$sql .= ' a.fk_user_author,a.fk_user_action,';
$sql .= " a.fk_contact, a.note, a.percent as percent,";
$sql .= " a.fk_element, a.elementtype, a.datec, a.tms as datem,";
$sql .= " c.code as type_code, c.libelle as type_label, c.color as type_color, c.type as type_type, c.picto as type_picto,";
$sql .= " sp.lastname, sp.firstname, sp.email, sp.phone, sp.address, sp.phone as phone_pro, sp.phone_mobile, sp.phone_perso, sp.fk_pays as country_id";

// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
	}
}

// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."actioncomm_extrafields as ef ON (a.id = ef.fk_object)";
if (!$user->rights->societe->client->voir && !$socid) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON a.fk_soc = sc.fk_soc";
}
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON a.fk_soc = s.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON a.fk_contact = sp.rowid";
$sql .= " ,".MAIN_DB_PREFIX."c_actioncomm as c";
// We must filter on resource table
if ($resourceid > 0) {
	$sql .= ", ".MAIN_DB_PREFIX."element_resources as r";
}
// We must filter on assignement table
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
	if (empty($conf->global->AGENDA_USE_EVENT_TYPE)) {
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
if (!$user->rights->societe->client->voir && !$socid) {
	$sql .= " AND (a.fk_soc IS NULL OR sc.fk_user = ".((int) $user->id).")";
}
if ($socid > 0) {
	$sql .= " AND s.rowid = ".((int) $socid);
}
// We must filter on assignement table
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
// We must filter on assignement table
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
if ($datestart > 0) {
	$sql .= " AND a.datep BETWEEN '".$db->idate($datestart)."' AND '".$db->idate($datestart + 3600 * 24 - 1)."'";
}
if ($dateend > 0) {
	$sql .= " AND a.datep2 BETWEEN '".$db->idate($dateend)."' AND '".$db->idate($dateend + 3600 * 24 - 1)."'";
}

// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';

// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= $db->order($sortfield, $sortorder);

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	// TODO Set and use an optimized request in $sqlforcount with no fields and no useless join to caluclate nb of records
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);
//print $sql;

dol_syslog("comm/action/list.php", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	$actionstatic = new ActionComm($db);
	$societestatic = new Societe($db);

	$num = $db->num_rows($resql);

	$arrayofselected = is_array($toselect) ? $toselect : array();

	// Local calendar
	$newtitle = '<div class="nowrap clear inline-block minheight30 margintoponly">';
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

	// Add link to show birthdays
	/*
	$link = '';
	if (empty($conf->use_javascript_ajax))
	{
		$newparam=$param;   // newparam is for birthday links
		$newparam=preg_replace('/showbirthday=[0-1]/i','showbirthday='.(empty($showbirthday)?1:0),$newparam);
		if (! preg_match('/showbirthday=/i',$newparam)) $newparam.='&showbirthday=1';
		$link='<a href="'.$_SERVER['PHP_SELF'];
		$link.='?'.$newparam;
		$link.='">';
		if (empty($showbirthday)) $link.=$langs->trans("AgendaShowBirthdayEvents");
		else $link.=$langs->trans("AgendaHideBirthdayEvents");
		$link.='</a>';
	}
	*/

	$s = $newtitle;

	// Calendars from hooks
	$parameters = array(); $object = null;
	$reshook = $hookmanager->executeHooks('addCalendarChoice', $parameters, $object, $action);
	if (empty($reshook)) {
		$s .= $hookmanager->resPrint;
	} elseif ($reshook > 1) {
		$s = $hookmanager->resPrint;
	}

	$viewmode = '';
	$viewmode .= '<a class="btnTitle btnTitleSelected reposition" href="'.DOL_URL_ROOT.'/comm/action/list.php?action=show_list&restore_lastsearch_values=1'.$paramnoactionodate.'">';
	//$viewmode .= '<span class="fa paddingleft imgforviewmode valignmiddle btnTitle-icon">';
	$viewmode .= img_picto($langs->trans("List"), 'object_list', 'class="imgforviewmode pictoactionview block"');
	//$viewmode .= '</span>';
	$viewmode .= '<span class="valignmiddle text-plus-circle btnTitle-label hideonsmartphone">'.$langs->trans("ViewList").'</span></a>';

	$viewmode .= '<a class="btnTitle reposition" href="'.DOL_URL_ROOT.'/comm/action/index.php?action=show_month&year='.dol_print_date($object->datep, '%Y').'&month='.dol_print_date($object->datep, '%m').'&day='.dol_print_date($object->datep, '%d').$paramnoactionodate.'">';
	//$viewmode .= '<span class="fa paddingleft imgforviewmode valignmiddle btnTitle-icon">';
	$viewmode .= img_picto($langs->trans("ViewCal"), 'object_calendarmonth', 'class="pictoactionview block"');
	//$viewmode .= '</span>';
	$viewmode .= '<span class="valignmiddle text-plus-circle btnTitle-label hideonsmartphone">'.$langs->trans("ViewCal").'</span></a>';

	$viewmode .= '<a class="btnTitle reposition" href="'.DOL_URL_ROOT.'/comm/action/index.php?action=show_week&year='.dol_print_date($object->datep, '%Y').'&month='.dol_print_date($object->datep, '%m').'&day='.dol_print_date($object->datep, '%d').$paramnoactionodate.'">';
	//$viewmode .= '<span class="fa paddingleft imgforviewmode valignmiddle btnTitle-icon">';
	$viewmode .= img_picto($langs->trans("ViewWeek"), 'object_calendarweek', 'class="pictoactionview block"');
	//$viewmode .= '</span>';
	$viewmode .= '<span class="valignmiddle text-plus-circle btnTitle-label hideonsmartphone">'.$langs->trans("ViewWeek").'</span></a>';

	$viewmode .= '<a class="btnTitle reposition" href="'.DOL_URL_ROOT.'/comm/action/index.php?action=show_day&year='.dol_print_date($object->datep, '%Y').'&month='.dol_print_date($object->datep, '%m').'&day='.dol_print_date($object->datep, '%d').$paramnoactionodate.'">';
	//$viewmode .= '<span class="fa paddingleft imgforviewmode valignmiddle btnTitle-icon">';
	$viewmode .= img_picto($langs->trans("ViewDay"), 'object_calendarday', 'class="pictoactionview block"');
	//$viewmode .= '</span>';
	$viewmode .= '<span class="valignmiddle text-plus-circle btnTitle-label hideonsmartphone">'.$langs->trans("ViewDay").'</span></a>';

	$viewmode .= '<a class="btnTitle reposition marginrightonly" href="'.DOL_URL_ROOT.'/comm/action/peruser.php?action=show_peruser&year='.dol_print_date($object->datep, '%Y').'&month='.dol_print_date($object->datep, '%m').'&day='.dol_print_date($object->datep, '%d').$paramnoactionodate.'">';
	//$viewmode .= '<span class="fa paddingleft imgforviewmode valignmiddle btnTitle-icon">';
	$viewmode .= img_picto($langs->trans("ViewPerUser"), 'object_calendarperuser', 'class="pictoactionview block"');
	//$viewmode .= '</span>';
	$viewmode .= '<span class="valignmiddle text-plus-circle btnTitle-label hideonsmartphone">'.$langs->trans("ViewPerUser").'</span></a>';

	$viewmode .= '<span class="marginrightonly"></span>';

	// Add more views from hooks
	$parameters = array(); $object = null;
	$reshook = $hookmanager->executeHooks('addCalendarView', $parameters, $object, $action);
	if (empty($reshook)) {
		$viewmode .= $hookmanager->resPrint;
	} elseif ($reshook > 1) {
		$viewmode = $hookmanager->resPrint;
	}

	$tmpforcreatebutton = dol_getdate(dol_now(), true);

	$newparam .= '&month='.str_pad($month, 2, "0", STR_PAD_LEFT).'&year='.$tmpforcreatebutton['year'];

	//$param='month='.$monthshown.'&year='.$year;
	$hourminsec = '100000';

	$url = DOL_URL_ROOT.'/comm/action/card.php?action=create';
	$url .= '&datep='.sprintf("%04d%02d%02d", $tmpforcreatebutton['year'], $tmpforcreatebutton['mon'], $tmpforcreatebutton['mday']).$hourminsec;
	$url .= '&backtopage='.urlencode($_SERVER["PHP_SELF"].($newparam ? '?'.$newparam : ''));

	$newcardbutton = dolGetButtonTitle($langs->trans('AddAction'), '', 'fa fa-plus-circle', $url, '', $user->rights->agenda->myactions->create || $user->rights->agenda->allactions->create);

	$param .= '&action='.$action;

	print_barre_liste($langs->trans("Agenda"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, -1 * $nbtotalofrecords, 'object_action', 0, $nav.$newcardbutton, '', $limit, 0, 0, 1, $viewmode);

	print $s;

	$objecttmp = new ActionComm($db);
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	$moreforfilter = '';

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
	if ($massactionbutton) {
		$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
	}
	$i = 0;

	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print_actions_filter($form, $canedit, $search_status, $year, $month, $day, $showbirthday, 0, $filtert, 0, $pid, $socid, $action, -1, $actioncode, $usergroup, '', $resourceid);
	print '</div>';

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	print '<tr class="liste_titre_filter">';
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
		print $form->selectDate($datestart, 'datestart', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', '', 'tzuserrel');
		print '</td>';
	}
	if (!empty($arrayfields['a.datep2']['checked'])) {
		print '<td class="liste_titre nowraponall" align="center">';
		print $form->selectDate($dateend, 'dateend', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', '', 'tzuserrel');
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
	$parameters = array('arrayfields'=>$arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	if (!empty($arrayfields['a.datec']['checked'])) {
		print '<td class="liste_titre"></td>';
	}
	if (!empty($arrayfields['a.tms']['checked'])) {
		print '<td class="liste_titre"></td>';
	}
	if (!empty($arrayfields['a.percent']['checked'])) {
		print '<td class="liste_titre center">';
		$formactions->form_select_status_action('formaction', $search_status, 1, 'search_status', 1, 2, 'minwidth100imp maxwidth125');
		print ajax_combobox('selectsearch_status');
		print '</td>';
	}
	// Action column
	print '<td class="liste_titre" align="middle">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
	print "</tr>\n";

	print '<tr class="liste_titre">';
	if (!empty($arrayfields['a.id']['checked'])) {
		print_liste_field_titre($arrayfields['a.id']['label'], $_SERVER["PHP_SELF"], "a.id", $param, "", "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['owner']['checked'])) {
		print_liste_field_titre($arrayfields['owner']['label'], $_SERVER["PHP_SELF"], "", $param, "", "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['c.libelle']['checked'])) {
		print_liste_field_titre($arrayfields['c.libelle']['label'], $_SERVER["PHP_SELF"], "c.libelle", $param, "", "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['a.label']['checked'])) {
		print_liste_field_titre($arrayfields['a.label']['label'], $_SERVER["PHP_SELF"], "a.label", $param, "", "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['a.note']['checked'])) {
		print_liste_field_titre($arrayfields['a.note']['label'], $_SERVER["PHP_SELF"], "a.note", $param, "", "", $sortfield, $sortorder);
	}
	//if (! empty($conf->global->AGENDA_USE_EVENT_TYPE))
	if (!empty($arrayfields['a.datep']['checked'])) {
		print_liste_field_titre($arrayfields['a.datep']['label'], $_SERVER["PHP_SELF"], "a.datep,a.id", $param, '', 'align="center"', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['a.datep2']['checked'])) {
		print_liste_field_titre($arrayfields['a.datep2']['label'], $_SERVER["PHP_SELF"], "a.datep2", $param, '', 'align="center"', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['s.nom']['checked'])) {
		print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], "s.nom", $param, "", "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['a.fk_contact']['checked'])) {
		print_liste_field_titre($arrayfields['a.fk_contact']['label'], $_SERVER["PHP_SELF"], "", $param, "", "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['a.fk_element']['checked'])) {
		print_liste_field_titre($arrayfields['a.fk_element']['label'], $_SERVER["PHP_SELF"], "", $param, "", "", $sortfield, $sortorder);
	}

	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';

	// Hook fields
	$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	if (!empty($arrayfields['a.datec']['checked'])) {
		print_liste_field_titre($arrayfields['a.datec']['label'], $_SERVER["PHP_SELF"], "a.datec,a.id", $param, "", 'align="center"', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['a.tms']['checked'])) {
		print_liste_field_titre($arrayfields['a.tms']['label'], $_SERVER["PHP_SELF"], "a.tms,a.id", $param, "", 'align="center"', $sortfield, $sortorder);
	}

	if (!empty($arrayfields['a.percent']['checked'])) {
		print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "a.percent", $param, "", 'align="center"', $sortfield, $sortorder);
	}
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
	print "</tr>\n";

	$contactstatic = new Contact($db);
	$now = dol_now();
	$delay_warning = $conf->global->MAIN_DELAY_ACTIONS_TODO * 24 * 60 * 60;

	require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
	$caction = new CActionComm($db);
	$arraylist = $caction->liste_array(1, 'code', '', (empty($conf->global->AGENDA_USE_EVENT_TYPE) ? 1 : 0), '', 1);
	$contactListCache = array();

	while ($i < min($num, $limit)) {
		$obj = $db->fetch_object($resql);

		// Discard auto action if option is on
		if (!empty($conf->global->AGENDA_ALWAYS_HIDE_AUTO) && $obj->type_code == 'AC_OTH_AUTO') {
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

		// Initialize $this->userassigned && this->socpeopleassigned array && this->userownerid
		// but only if we need it
		if (!empty($arrayfields['a.fk_contact']['checked'])) {
			$actionstatic->fetchResources();
		}

		print '<tr class="oddeven">';

		// Ref
		if (!empty($arrayfields['a.id']['checked'])) {
			print '<td class="nowraponall">';
			print $actionstatic->getNomUrl(1, -1);
			print '</td>';
		}

		// User owner
		if (!empty($arrayfields['owner']['checked'])) {
			print '<td class="tdoverflowmax150">'; // With edge and chrome the td overflow is not supported correctly when content is not full text.
			if ($obj->fk_user_action > 0) {
				$userstatic->fetch($obj->fk_user_action);
				print $userstatic->getNomUrl(-1);
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
			if (empty($conf->global->AGENDA_USE_EVENT_TYPE) && empty($arraylist[$labeltype])) {
				$labeltype = 'AC_OTH';
			}
			if ($actionstatic->type_code == 'AC_OTH' && $actionstatic->code == 'TICKET_MSG') {
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
			print '<td class="tdoverflowonsmartphone">';
			$text = dolGetFirstLineOfText(dol_string_nohtmltag($actionstatic->note_private, 0));
			print $form->textwithtooltip(dol_trunc($text, 40), $actionstatic->note_private);
			print '</td>';
		}

		$formatToUse = $obj->fulldayevent ? 'day' : 'dayhour';

		// Start date
		if (!empty($arrayfields['a.datep']['checked'])) {
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->dp), $formatToUse, 'tzuser');
			$late = 0;
			if ($obj->percent == 0 && $obj->dp && $db->jdate($obj->dp) < ($now - $delay_warning)) {
				$late = 1;
			}
			if ($obj->percent == 0 && !$obj->dp && $obj->dp2 && $db->jdate($obj->dp) < ($now - $delay_warning)) {
				$late = 1;
			}
			if ($obj->percent > 0 && $obj->percent < 100 && $obj->dp2 && $db->jdate($obj->dp2) < ($now - $delay_warning)) {
				$late = 1;
			}
			if ($obj->percent > 0 && $obj->percent < 100 && !$obj->dp2 && $obj->dp && $db->jdate($obj->dp) < ($now - $delay_warning)) {
				$late = 1;
			}
			if ($late) {
				print img_warning($langs->trans("Late")).' ';
			}
			print '</td>';
		}

		// End date
		if (!empty($arrayfields['a.datep2']['checked'])) {
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->dp2), $formatToUse, 'tzuser');
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
		$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;

		// Date creation
		if (!empty($arrayfields['a.datec']['checked'])) {
			// Status/Percent
			print '<td align="center" class="nowrap">'.dol_print_date($db->jdate($obj->datec), 'dayhour', 'tzuser').'</td>';
		}
		// Date update
		if (!empty($arrayfields['a.tms']['checked'])) {
			print '<td align="center" class="nowrap">'.dol_print_date($db->jdate($obj->datem), 'dayhour', 'tzuser').'</td>';
		}
		if (!empty($arrayfields['a.percent']['checked'])) {
			// Status/Percent
			$datep = $db->jdate($obj->dp);
			print '<td align="center" class="nowrap">'.$actionstatic->LibStatut($obj->percent, 5, 0, $datep).'</td>';
		}
		// Action column
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($obj->id, $arrayofselected)) {
				$selected = 1;
			}
			print '<input id="cb'.$obj->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';

		print "</tr>\n";
		$i++;
	}
	print "</table>";
	print '</div>';
	print '</form>';

	$db->free($resql);
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
