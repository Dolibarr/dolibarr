<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2019 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2018	   Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2021      Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2023      Gauthier VERDOL      <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 *	\file       htdocs/projet/tasks/list.php
 *	\ingroup    project
 *	\brief      List all tasks of a project
 */

require "../../main.inc.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcategory.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';

// Load translation files required by the page
$langs->loadLangs(array('projects', 'users', 'companies'));

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
//$show_files = GETPOSTINT('show_files');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$optioncss = GETPOST('optioncss', 'aZ09');
$mode = GETPOST('mode', 'aZ');

$id = GETPOSTINT('id');

$search_all = trim((GETPOST('search_all', 'alphanohtml') != '') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$search_categ = GETPOST("search_categ", 'intcomma');
$search_projectstatus = GETPOST('search_projectstatus', 'intcomma');
$search_project_ref = GETPOST('search_project_ref');
$search_project_title = GETPOST('search_project_title');
$search_task_ref = GETPOST('search_task_ref');
$search_task_label = GETPOST('search_task_label');
$search_task_description = GETPOST('search_task_description');
$search_task_ref_parent = GETPOST('search_task_ref_parent');
$search_project_user = GETPOST('search_project_user', 'intcomma');
$search_task_user = GETPOST('search_task_user', 'intcomma');
$search_task_progress = GETPOST('search_task_progress');
$search_task_budget_amount = GETPOST('search_task_budget_amount');
$search_societe = GETPOST('search_societe');
$search_societe_alias = GETPOST('search_societe_alias');
$search_opp_status = GETPOST("search_opp_status", 'alpha');
$searchCategoryCustomerOperator = 0;
if (GETPOSTISSET('formfilteraction')) {
	$searchCategoryCustomerOperator = GETPOSTINT('search_category_customer_operator');
} elseif (getDolGlobalString('MAIN_SEARCH_CAT_OR_BY_DEFAULT')) {
	$searchCategoryCustomerOperator = getDolGlobalString('MAIN_SEARCH_CAT_OR_BY_DEFAULT');
}
$searchCategoryCustomerList = GETPOST('search_category_customer_list', 'array');

if (!isset($search_projectstatus) || $search_projectstatus === '') {
	if ($search_all != '') {
		$search_projectstatus = -1;
	} else {
		$search_projectstatus = 1;
	}
}

$mine = GETPOST('mode', 'alpha') == 'mine' ? 1 : 0;
if ($mine) {
	$search_task_user = $user->id;
	$mine = 0;
}
$type = GETPOST('type');

$search_date_startday = GETPOSTINT('search_date_startday');
$search_date_startmonth = GETPOSTINT('search_date_startmonth');
$search_date_startyear = GETPOSTINT('search_date_startyear');
$search_date_endday = GETPOSTINT('search_date_endday');
$search_date_endmonth = GETPOSTINT('search_date_endmonth');
$search_date_endyear = GETPOSTINT('search_date_endyear');
$search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);	// Use tzserver
$search_date_end = dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);
$search_datelimit_startday = GETPOSTINT('search_datelimit_startday');
$search_datelimit_startmonth = GETPOSTINT('search_datelimit_startmonth');
$search_datelimit_startyear = GETPOSTINT('search_datelimit_startyear');
$search_datelimit_endday = GETPOSTINT('search_datelimit_endday');
$search_datelimit_endmonth = GETPOSTINT('search_datelimit_endmonth');
$search_datelimit_endyear = GETPOSTINT('search_datelimit_endyear');
$search_datelimit_start = dol_mktime(0, 0, 0, $search_datelimit_startmonth, $search_datelimit_startday, $search_datelimit_startyear);
$search_datelimit_end = dol_mktime(23, 59, 59, $search_datelimit_endmonth, $search_datelimit_endday, $search_datelimit_endyear);

// Initialize context for list
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'tasklist';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new Task($db);
$hookmanager->initHooks(array('tasklist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Security check
$socid = 0;
//if ($user->socid > 0) $socid = $user->socid;    // For external user, no check is done on company because readability is managed by public status of project and assignment.
if (!$user->hasRight('projet', 'lire')) {
	accessforbidden();
}

$diroutputmassaction = $conf->project->dir_output.'/tasks/temp/massgeneration/'.$user->id;

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
	$sortfield = 'p.ref';
}
if (!$sortorder) {
	$sortorder = 'DESC';
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	't.ref' => "Ref",
	't.label' => "Label",
	't.description' => "Description",
	't.note_public' => "NotePublic",
);
if (empty($user->socid)) {
	$fieldstosearchall['t.note_private'] = "NotePrivate";
}

$arrayfields = array(
	't.ref' => array('label' => "RefTask", 'checked' => 1, 'position' => 50),
	't.fk_task_parent' => array('label' => "RefTaskParent", 'checked' => 0, 'position' => 70),
	't.label' => array('label' => "LabelTask", 'checked' => 1, 'position' => 75),
	't.description' => array('label' => "Description", 'checked' => 0, 'position' => 80),
	't.dateo' => array('label' => "DateStart", 'checked' => 1, 'position' => 100),
	't.datee' => array('label' => "Deadline", 'checked' => 1, 'position' => 101),
	'p.ref' => array('label' => "ProjectRef", 'checked' => 1, 'position' => 151),
	'p.title' => array('label' => "ProjectLabel", 'checked' => 0, 'position' => 152),
	's.nom' => array('label' => "ThirdParty", 'checked' => -1, 'csslist' => 'tdoverflowmax125', 'position' => 200),
	's.name_alias' => array('label' => "AliasNameShort", 'checked' => 0, 'csslist' => 'tdoverflowmax125', 'position' => 201),
	'p.fk_statut' => array('label' => "ProjectStatus", 'checked' => 1, 'position' => 205),
	't.planned_workload' => array('label' => "PlannedWorkload", 'checked' => 1, 'position' => 302),
	't.duration_effective' => array('label' => "TimeSpent", 'checked' => 1, 'position' => 303),
	't.progress_calculated' => array('label' => "ProgressCalculated", 'checked' => -1, 'position' => 304),
	't.progress' => array('label' => "ProgressDeclared", 'checked' => 1, 'position' => 305),
	't.progress_summary' => array('label' => "TaskProgressSummary", 'checked' => 1, 'position' => 306),
	't.budget_amount' => array('label' => "Budget", 'checked' => 0, 'position' => 307),
	't.tobill' => array('label' => "TimeToBill", 'checked' => 0, 'position' => 310),
	't.billed' => array('label' => "TimeBilled", 'checked' => 0, 'position' => 311),
	't.datec' => array('label' => "DateCreation", 'checked' => 0, 'position' => 500),
	't.tms' => array('label' => "DateModificationShort", 'checked' => 0, 'position' => 501),
	//'t.fk_statut'=>array('label'=>"Status", 'checked'=>1, 'position'=>1000),
);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');
'@phan-var-force array<string,array{label:string,checked?:int<0,1>,position?:int,help?:string}> $arrayfields';  // dol_sort_array looses type for Phan

$permissiontoread = $user->hasRight('projet', 'lire');
$permissiontocreate = $user->hasRight('projet', 'creer');
$permissiontodelete = $user->hasRight('projet', 'supprimer');

if (!$permissiontoread) {
	accessforbidden();
}


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
		$search_all = "";
		$search_categ = "";
		$search_projectstatus = -1;
		$search_project_ref = "";
		$search_societe = "";
		$search_societe_alias = "";
		$search_project_title = "";
		$search_task_ref = "";
		$search_task_label = "";
		$search_task_description = "";
		$search_task_ref_parent = "";
		$search_task_progress = "";
		$search_task_budget_amount = "";
		$search_task_user = -1;
		$search_project_user = -1;
		$search_date_startday = '';
		$search_date_startmonth = '';
		$search_date_startyear = '';
		$search_date_endday = '';
		$search_date_endmonth = '';
		$search_date_endyear = '';
		$search_date_start = '';
		$search_date_end = '';
		$search_datelimit_startday = '';
		$search_datelimit_startmonth = '';
		$search_datelimit_startyear = '';
		$search_datelimit_endday = '';
		$search_datelimit_endmonth = '';
		$search_datelimit_endyear = '';
		$search_datelimit_start = '';
		$search_datelimit_end = '';
		$toselect = array();
		$searchCategoryCustomerList = array();
		$search_array_options = array();
	}

	// Mass actions
	$objectclass = 'Task';
	$objectlabel = 'Tasks';
	$uploaddir = $conf->project->dir_output.'/tasks';
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}

if (empty($search_projectstatus) && $search_projectstatus == '') {
	$search_projectstatus = 1;
}

/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$socstatic = new Societe($db);
$projectstatic = new Project($db);
$puser = new User($db);
$tuser = new User($db);

$now = dol_now();

$title = $langs->trans("Activities");
$help_url = "EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";
$morejs = array();
$morecss = array();

if ($search_project_user > 0) {
	$puser->fetch($search_project_user);
}
if ($search_task_user > 0) {
	$tuser->fetch($search_task_user);
}


$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')); // This also change content of $arrayfields


if ($id) {
	$projectstatic->fetch($id);
	$projectstatic->fetch_thirdparty();
}

// Get list of project id allowed to user (in a string list separated by coma)
if (!$user->hasRight('projet', 'all', 'lire')) {
	$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1, $socid);
}
//var_dump($projectsListId);

// Get id of types of contacts for projects (This list never contains a lot of elements)
$listofprojectcontacttype = array();
$sql = "SELECT ctc.rowid, ctc.code FROM ".MAIN_DB_PREFIX."c_type_contact as ctc";
$sql .= " WHERE ctc.element = '".$db->escape($projectstatic->element)."'";
$sql .= " AND ctc.source = 'internal'";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$listofprojectcontacttype[$obj->rowid] = $obj->code;
	}
} else {
	dol_print_error($db);
}
if (count($listofprojectcontacttype) == 0) {
	$listofprojectcontacttype[0] = '0'; // To avoid sql syntax error if not found
}
// Get id of types of contacts for tasks (This list never contains a lot of elements)
$listoftaskcontacttype = array();
$sql = "SELECT ctc.rowid, ctc.code FROM ".MAIN_DB_PREFIX."c_type_contact as ctc";
$sql .= " WHERE ctc.element = '".$db->escape($object->element)."'";
$sql .= " AND ctc.source = 'internal'";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$listoftaskcontacttype[$obj->rowid] = $obj->code;
	}
} else {
	dol_print_error($db);
}
if (count($listoftaskcontacttype) == 0) {
	$listoftaskcontacttype[0] = '0'; // To avoid sql syntax error if not found
}

// Build and execute select
// --------------------------------------------------------------------
$distinct = 'DISTINCT'; // We add distinct until we are added a protection to be sure a contact of a project and task is assigned only once.
$sql = "SELECT ".$distinct." p.rowid as projectid, p.ref as projectref, p.title as projecttitle, p.fk_statut as projectstatus, p.datee as projectdatee, p.fk_opp_status, p.public, p.fk_user_creat as projectusercreate, p.usage_bill_time,";
$sql .= " s.nom as name, s.name_alias as alias, s.rowid as socid,";
$sql .= " t.datec as date_creation, t.dateo as date_start, t.datee as date_end, t.tms as date_modification,";
$sql .= " t.rowid as id, t.ref, t.label, t.planned_workload, t.duration_effective, t.progress, t.fk_statut as status,";
$sql .= " t.description, t.fk_task_parent, t.budget_amount";
// Add sum fields
if (!empty($arrayfields['t.tobill']['checked']) || !empty($arrayfields['t.billed']['checked'])) {
	$sql .= " , SUM(tt.element_duration * ".$db->ifsql("invoice_id IS NULL", "1", "0").") as tobill, SUM(tt.element_duration * ".$db->ifsql("invoice_id IS NULL", "0", "1").") as billed";
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
$sql = preg_replace('/,\s*$/', '', $sql);

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
$sql .= ", ".MAIN_DB_PREFIX."projet_task as t";
if (!empty($arrayfields['t.tobill']['checked']) || !empty($arrayfields['t.billed']['checked'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_time as tt ON (tt.fk_element = t.rowid AND tt.elementtype = 'task')";
}
if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (t.rowid = ef.fk_object)";
}
if ($search_project_user > 0) {
	$sql .= ", ".MAIN_DB_PREFIX."element_contact as ecp";
}
if ($search_task_user > 0) {
	$sql .= ", ".MAIN_DB_PREFIX."element_contact as ect";
}
$sql .= " WHERE t.fk_projet = p.rowid";
$sql .= " AND p.entity IN (".getEntity('project').')';
if (!$user->hasRight('projet', 'all', 'lire')) {
	$sql .= " AND p.rowid IN (".$db->sanitize($projectsListId ? $projectsListId : '0').")"; // public and assigned to projects, or restricted to company for external users
}
if (is_object($projectstatic) && $projectstatic->id > 0) {
	$sql .= " AND p.rowid = ".((int) $projectstatic->id);
}
// No need to check company, as filtering of projects must be done by getProjectsAuthorizedForUser
if ($socid) {
	$sql .= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".((int) $socid).")";
}
if ($search_project_ref) {
	$sql .= natural_search('p.ref', $search_project_ref);
}
if ($search_project_title) {
	$sql .= natural_search('p.title', $search_project_title);
}
if ($search_task_ref) {
	$sql .= natural_search('t.ref', $search_task_ref);
}
if ($search_task_label) {
	$sql .= natural_search('t.label', $search_task_label);
}
if ($search_task_description) {
	$sql .= natural_search('t.description', $search_task_description);
}
if ($search_task_ref_parent) {
	$sql .= ' AND t.fk_task_parent IN (SELECT ipt.rowid FROM '.MAIN_DB_PREFIX.'projet_task  as ipt WHERE '.natural_search('ipt.ref', $search_task_ref_parent, 0, 1).')';
}
if ($search_task_progress) {
	$sql .= natural_search('t.progress', $search_task_progress, 1);
}
if ($search_task_budget_amount) {
	$sql .= natural_search('t.budget_amount', $search_task_budget_amount, 1);
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
if ($search_date_start) {
	$sql .= " AND t.dateo >= '".$db->idate($search_date_start)."'";
}
if ($search_date_end) {
	$sql .= " AND t.dateo <= '".$db->idate($search_date_end)."'";
}
if ($search_datelimit_start) {
	$sql .= " AND t.datee >= '".$db->idate($search_datelimit_start)."'";
}
if ($search_datelimit_end) {
	$sql .= " AND t.datee <= '".$db->idate($search_datelimit_end)."'";
}
if ($search_all) {
	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}
if ($search_projectstatus >= 0) {
	if ($search_projectstatus == 99) {
		$sql .= " AND p.fk_statut <> 2";
	} else {
		$sql .= " AND p.fk_statut = ".((int) $search_projectstatus);
	}
}
if ($search_project_user > 0) {
	$sql .= " AND ecp.fk_c_type_contact IN (".$db->sanitize(implode(',', array_keys($listofprojectcontacttype))).") AND ecp.element_id = p.rowid AND ecp.fk_socpeople = ".((int) $search_project_user);
}
if ($search_task_user > 0) {
	$sql .= " AND ect.fk_c_type_contact IN (".$db->sanitize(implode(',', array_keys($listoftaskcontacttype))).") AND ect.element_id = t.rowid AND ect.fk_socpeople = ".((int) $search_task_user);
}
// Search for tag/category ($searchCategoryProjectList is an array of ID)
$searchCategoryProjectList = array($search_categ);
$searchCategoryProjectOperator = 0;
if (!empty($searchCategoryProjectList)) {
	$searchCategoryProjectSqlList = array();
	$listofcategoryid = '';
	foreach ($searchCategoryProjectList as $searchCategoryProject) {
		if (intval($searchCategoryProject) == -2) {
			$searchCategoryProjectSqlList[] = "NOT EXISTS (SELECT ck.fk_project FROM ".MAIN_DB_PREFIX."categorie_project as ck WHERE p.rowid = ck.fk_project)";
		} elseif (intval($searchCategoryProject) > 0) {
			if ($searchCategoryProjectOperator == 0) {
				$searchCategoryProjectSqlList[] = " EXISTS (SELECT ck.fk_project FROM ".MAIN_DB_PREFIX."categorie_project as ck WHERE p.rowid = ck.fk_project AND ck.fk_categorie = ".((int) $searchCategoryProject).")";
			} else {
				$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategoryProject);
			}
		}
	}
	if ($listofcategoryid) {
		$searchCategoryProjectSqlList[] = " EXISTS (SELECT ck.fk_project FROM ".MAIN_DB_PREFIX."categorie_project as ck WHERE p.rowid = ck.fk_project AND ck.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
	}
	if ($searchCategoryProjectOperator == 1) {
		if (!empty($searchCategoryProjectSqlList)) {
			$sql .= " AND (".implode(' OR ', $searchCategoryProjectSqlList).")";
		}
	} else {
		if (!empty($searchCategoryProjectSqlList)) {
			$sql .= " AND (".implode(' AND ', $searchCategoryProjectSqlList).")";
		}
	}
}
$searchCategoryCustomerSqlList = array();
if ($searchCategoryCustomerOperator == 1) {
	$existsCategoryCustomerList = array();
	foreach ($searchCategoryCustomerList as $searchCategoryCustomer) {
		if (intval($searchCategoryCustomer) == -2) {
			$sqlCategoryCustomerNotExists  = " NOT EXISTS (";
			$sqlCategoryCustomerNotExists .= " SELECT cat_cus.fk_soc";
			$sqlCategoryCustomerNotExists .= " FROM ".$db->prefix()."categorie_societe AS cat_cus";
			$sqlCategoryCustomerNotExists .= " WHERE cat_cus.fk_soc = p.fk_soc";
			$sqlCategoryCustomerNotExists .= " )";
			$searchCategoryCustomerSqlList[] = $sqlCategoryCustomerNotExists;
		} elseif (intval($searchCategoryCustomer) > 0) {
			$existsCategoryCustomerList[] = $db->escape($searchCategoryCustomer);
		}
	}
	if (!empty($existsCategoryCustomerList)) {
		$sqlCategoryCustomerExists = " EXISTS (";
		$sqlCategoryCustomerExists .= " SELECT cat_cus.fk_soc";
		$sqlCategoryCustomerExists .= " FROM ".$db->prefix()."categorie_societe AS cat_cus";
		$sqlCategoryCustomerExists .= " WHERE cat_cus.fk_soc = p.fk_soc";
		$sqlCategoryCustomerExists .= " AND cat_cus.fk_categorie IN (".$db->sanitize(implode(',', $existsCategoryCustomerList)).")";
		$sqlCategoryCustomerExists .= " )";
		$searchCategoryCustomerSqlList[] = $sqlCategoryCustomerExists;
	}
	if (!empty($searchCategoryCustomerSqlList)) {
		$sql .= " AND (".implode(' OR ', $searchCategoryCustomerSqlList).")";
	}
} else {
	foreach ($searchCategoryCustomerList as $searchCategoryCustomer) {
		if (intval($searchCategoryCustomer) == -2) {
			$sqlCategoryCustomerNotExists = " NOT EXISTS (";
			$sqlCategoryCustomerNotExists .= " SELECT cat_cus.fk_soc";
			$sqlCategoryCustomerNotExists .= " FROM ".$db->prefix()."categorie_societe AS cat_cus";
			$sqlCategoryCustomerNotExists .= " WHERE cat_cus.fk_soc = p.fk_soc";
			$sqlCategoryCustomerNotExists .= " )";
			$searchCategoryCustomerSqlList[] = $sqlCategoryCustomerNotExists;
		} elseif (intval($searchCategoryCustomer) > 0) {
			$searchCategoryCustomerSqlList[] = "p.fk_soc IN (SELECT fk_soc FROM ".$db->prefix()."categorie_societe WHERE fk_categorie = ".((int) $searchCategoryCustomer).")";
		}
	}
	if (!empty($searchCategoryCustomerSqlList)) {
		$sql .= " AND (".implode(' AND ', $searchCategoryCustomerSqlList).")";
	}
}
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
if (!empty($arrayfields['t.tobill']['checked']) || !empty($arrayfields['t.billed']['checked'])) {
	$sql .= " GROUP BY p.rowid, p.ref, p.title, p.fk_statut, p.datee, p.fk_opp_status, p.public, p.fk_user_creat,";
	$sql .= " s.nom, s.rowid,";
	$sql .= " t.datec, t.dateo, t.datee, t.tms,";
	$sql .= " t.rowid, t.ref, t.label, t.planned_workload, t.duration_effective, t.progress,t.budget_amount, t.fk_statut as status";
	// Add fields from extrafields
	if (!empty($extrafields->attributes[$object->table_element]['label'])) {
		foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
			$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key : '');
		}
	}
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
if ($num == 1 && getDolGlobalString('MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE') && $search_all) {
	$obj = $db->fetch_object($resql);
	$id = $obj->id;		// in select, task id has been aliases into 'id'
	header("Location: ".DOL_URL_ROOT.'/projet/tasks/task.php?id='.$id.'&withproject=1');
	exit;
}


// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss, '', 'bodyforlist');	// Can use also classforhorizontalscrolloftabs instead of bodyforlist for no horizontal scroll

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
if ($search_datelimit_startday) {
	$param .= '&search_datelimit_startday='.urlencode((string) ($search_datelimit_startday));
}
if ($search_datelimit_startmonth) {
	$param .= '&search_datelimit_startmonth='.urlencode((string) ($search_datelimit_startmonth));
}
if ($search_datelimit_startyear) {
	$param .= '&search_datelimit_startyear='.urlencode((string) ($search_datelimit_startyear));
}
if ($search_datelimit_endday) {
	$param .= '&search_datelimit_endday='.urlencode((string) ($search_datelimit_endday));
}
if ($search_datelimit_endmonth) {
	$param .= '&search_datelimit_endmonth='.urlencode((string) ($search_datelimit_endmonth));
}
if ($search_datelimit_endyear) {
	$param .= '&search_datelimit_endyear='.urlencode((string) ($search_datelimit_endyear));
}
if ($search_task_budget_amount) {
	$param .= '&search_task_budget_amount='.urlencode($search_task_budget_amount);
}
if ($socid) {
	$param .= '&socid='.urlencode($socid);
}
if ($search_all != '') {
	$param .= '&search_all='.urlencode($search_all);
}
if ($search_project_ref != '') {
	$param .= '&search_project_ref='.urlencode($search_project_ref);
}
if ($search_project_title != '') {
	$param .= '&search_project_title='.urlencode($search_project_title);
}
if ($search_task_ref != '') {
	$param .= '&search_task_ref='.urlencode($search_task_ref);
}
if ($search_task_label != '') {
	$param .= '&search_task_label='.urlencode($search_task_label);
}
if ($search_task_description != '') {
	$param .= '&search_task_description='.urlencode($search_task_description);
}
if ($search_task_ref_parent != '') {
	$param .= '&search_task_ref_parent='.urlencode($search_task_ref_parent);
}
if ($search_task_progress != '') {
	$param .= '&search_task_progress='.urlencode($search_task_progress);
}
if ($search_societe != '') {
	$param .= '&search_societe='.urlencode($search_societe);
}
if ($search_societe != '') {
	$param .= '&search_societe_alias='.urlencode($search_societe_alias);
}
if ($search_projectstatus != '') {
	$param .= '&search_projectstatus='.urlencode($search_projectstatus);
}
if ((is_numeric($search_opp_status) && $search_opp_status >= 0) || in_array($search_opp_status, array('all', 'none'))) {
	$param .= '&search_opp_status='.urlencode($search_opp_status);
}
if ($search_project_user != '') {
	$param .= '&search_project_user='.urlencode((string) ($search_project_user));
}
if ($search_task_user > 0) {
	$param .= '&search_task_user='.urlencode($search_task_user);
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
foreach ($searchCategoryCustomerList as $searchCategoryCustomer) {
	$param .= "&search_category_customer_list[]=".urlencode($searchCategoryCustomer);
}
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';
// Add $param from hooks
$parameters = array('param' => &$param);
$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;

// List of mass actions available
$arrayofmassactions = array(
//    'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
//    'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
);
//if($user->rights->societe->creer) $arrayofmassactions['createbills']=$langs->trans("CreateInvoiceForThisCustomer");
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
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
if (!empty($type)) {
	print '<input type="hidden" name="type" value="'.$type.'">';
}
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="mode" value="'.$mode.'">';


$newcardbutton = '';

$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitleSeparator();
$newcardbutton .= dolGetButtonTitle($langs->trans('NewTask'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/projet/tasks.php?action=create&backtopage='.urlencode(DOL_URL_ROOT.'/projet/tasks/list.php'), '', $permissiontocreate);


// Show description of content
$texthelp = '';
if ($search_task_user == $user->id) {
	$texthelp .= $langs->trans("MyTasksDesc");
} else {
	if ($user->hasRight('projet', 'all', 'lire') && !$socid) {
		$texthelp .= $langs->trans("TasksOnProjectsDesc");
	} else {
		$texthelp .= $langs->trans("TasksOnProjectsPublicDesc");
	}
}

print_barre_liste($form->textwithpicto($title, $texthelp), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'projecttask', 0, $newcardbutton, '', $limit, 0, 0, 1);

$topicmail = "Information";
$modelmail = "task";
$objecttmp = new Task($db);
$trackid = 'tas'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($search_all) {
	$setupstring = '';
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
		$setupstring .= $key."=".$val.";";
	}
	print '<!-- Search done like if TASK_QUICKSEARCH_ON_FIELDS = '.$setupstring.' -->'."\n";
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).implode(', ', $fieldstosearchall).'</div>'."\n";
}

$moreforfilter = '';

// Filter on categories
if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('ProjectCategories');
	$moreforfilter .= img_picto($tmptitle, 'category', 'class="pictofixedwidth"').$formother->select_categories('project', $search_categ, 'search_categ', 1, $tmptitle, 'maxwidth300');
	$moreforfilter .= '</div>';
}

// If the user can view users
$moreforfilter .= '<div class="divsearchfield">';
$tmptitle = $langs->trans('ProjectsWithThisUserAsContact');
$includeonly = '';
if (!$user->hasRight('user', 'user', 'lire')) {
	$includeonly = array($user->id);
}
$moreforfilter .= img_picto($tmptitle, 'user', 'class="pictofixedwidth"').$form->select_dolusers($search_project_user ? $search_project_user : '', 'search_project_user', $tmptitle, '', 0, $includeonly, '', 0, 0, 0, '', 0, '', 'maxwidth250');
$moreforfilter .= '</div>';

// If the user can view users
$moreforfilter .= '<div class="divsearchfield">';
$tmptitle = $langs->trans('TasksWithThisUserAsContact');
$includeonly = '';
if (!$user->hasRight('user', 'user', 'lire')) {
	$includeonly = array($user->id);
}
$moreforfilter .= img_picto($tmptitle, 'user', 'class="pictofixedwidth"').$form->select_dolusers($search_task_user, 'search_task_user', $tmptitle, '', 0, $includeonly, '', 0, 0, 0, '', 0, '', 'maxwidth250');
$moreforfilter .= '</div>';

// Filter on customer categories
if (getDolGlobalString('MAIN_SEARCH_CATEGORY_CUSTOMER_ON_TASK_LIST') && isModEnabled("category") && $user->hasRight('categorie', 'lire')) {
	$formcategory = new FormCategory($db);
	$moreforfilter .= $formcategory->getFilterBox(Categorie::TYPE_CUSTOMER, $searchCategoryCustomerList, 'minwidth300', $searchCategoryCustomerList ? $searchCategoryCustomerList : 0);
	/*
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->transnoentities('CustomersProspectsCategoriesShort');
	$moreforfilter .= img_picto($tmptitle, 'category', 'class="pictofixedwidth"');
	$categoriesArr = $form->select_all_categories(Categorie::TYPE_CUSTOMER, '', '', 64, 0, 3);
	$categoriesArr[-2] = '- '.$langs->trans('NotCategorized').' -';
	$moreforfilter .= Form::multiselectarray('search_category_customer_list', $categoriesArr, $searchCategoryCustomerList, 0, 0, 'minwidth300', 0, 0, '', 'category', $tmptitle);
	$moreforfilter .= ' <input type="checkbox" class="valignmiddle" id="search_category_customer_operator" name="search_category_customer_operator" value="1"'.($searchCategoryCustomerOperator == 1 ? ' checked="checked"' : '').'/>';
	$moreforfilter .= $form->textwithpicto('', $langs->trans('UseOrOperatorForCategories') . ' : ' . $tmptitle, 1, 'help', '', 0, 2, 'tooltip_cat_cus'); // Tooltip on click
	$moreforfilter .= '</div>';
	*/
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

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'" id="tablelines3">'."\n";

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
if (!empty($arrayfields['t.ref']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_task_ref" value="'.dol_escape_htmltag($search_task_ref).'" size="4">';
	print '</td>';
}
if (!empty($arrayfields['t.fk_task_parent']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_task_ref_parent" value="'.dol_escape_htmltag($search_task_ref_parent).'" size="4">';
	print '</td>';
}
if (!empty($arrayfields['t.label']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_task_label" value="'.dol_escape_htmltag($search_task_label).'" size="8">';
	print '</td>';
}
// Task Description
if (!empty($arrayfields['t.description']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_task_description" value="'.dol_escape_htmltag($search_task_description).'" size="8">';
	print '</td>';
}
// Start date
if (!empty($arrayfields['t.dateo']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
// End date
if (!empty($arrayfields['t.datee']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_datelimit_start ? $search_datelimit_start : -1, 'search_datelimit_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_datelimit_end ? $search_datelimit_end : -1, 'search_datelimit_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	// TODO Add option late
	//print '<br><input type="checkbox" name="search_option" value="late"'.($option == 'late' ? ' checked' : '').'> '.$langs->trans("Alert");
	print '</div>';
	print '</td>';
}
if (!empty($arrayfields['p.ref']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_project_ref" value="'.$search_project_ref.'" size="4">';
	print '</td>';
}
if (!empty($arrayfields['p.title']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_project_title" value="'.$search_project_title.'" size="6">';
	print '</td>';
}
if (!empty($arrayfields['s.nom']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_societe" value="'.dol_escape_htmltag($search_societe).'" size="4">';
	print '</td>';
}
if (!empty($arrayfields['s.name_alias']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_societe_alias" value="'.dol_escape_htmltag($search_societe_alias).'" size="4">';
	print '</td>';
}
if (!empty($arrayfields['p.fk_statut']['checked'])) {
	print '<td class="liste_titre center">';
	$arrayofstatus = array();
	foreach ($projectstatic->labelStatusShort as $key => $val) {
		$arrayofstatus[$key] = $langs->trans($val);
	}
	$arrayofstatus['99'] = $langs->trans("NotClosed").' ('.$langs->trans('Draft').'+'.$langs->trans('Opened').')';
	print $form->selectarray('search_projectstatus', $arrayofstatus, $search_projectstatus, 1, 0, 0, '', 0, 0, 0, '', 'maxwidth100');
	print '</td>';
}
if (!empty($arrayfields['t.planned_workload']['checked'])) {
	print '<td class="liste_titre"></td>';
}
if (!empty($arrayfields['t.duration_effective']['checked'])) {
	print '<td class="liste_titre"></td>';
}
if (!empty($arrayfields['t.progress_calculated']['checked'])) {
	print '<td class="liste_titre"></td>';
}
if (!empty($arrayfields['t.progress']['checked'])) {
	print '<td class="liste_titre center">';
	print '<input type="text" class="flat" name="search_task_progress" value="'.$search_task_progress.'" size="4">';
	print '</td>';
}

if (!empty($arrayfields['t.progress_summary']['checked'])) {
	print '<td class="liste_titre"></td>';
}

if (!empty($arrayfields['t.budget_amount']['checked'])) {
	print '<td class="liste_titre center">';
	print '<input type="text" class="flat" name="search_task_budget_amount" value="'.$search_task_budget_amount.'" size="4">';
	print '</td>';
}

if (!empty($arrayfields['t.tobill']['checked'])) {
	print '<td class="liste_titre"></td>';
}
if (!empty($arrayfields['t.billed']['checked'])) {
	print '<td class="liste_titre"></td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
// Fields from hook
$parameters = array('arrayfields' => $arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!empty($arrayfields['t.datec']['checked'])) {
	// Date creation
	print '<td class="liste_titre">';
	print '</td>';
}
if (!empty($arrayfields['t.tms']['checked'])) {
	// Date modification
	print '<td class="liste_titre">';
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

$totalarray = array(
	'nbfield' => 0,
	'type' => [],
	'val' => array(
		't.planned_workload' => 0,
		't.duration_effective' => 0,
		't.progress' => 0,
		't.budget_amount' => 0,
	),
	'totalplannedworkload' => 0,
	'totaldurationeffective' => 0,
	'totaldurationdeclared' => 0,
	'totaltobillfield' => 0,
	'totalbilledfield' => 0,
	'totalbudget_amountfield' => 0,
	'totalbudgetamount' => 0,
	'totalbudget' => 0,
	'totaltobill' => 0,
	'totalbilled' => 0,
	'totalizable' => [],
);

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['t.ref']['checked'])) {
	print_liste_field_titre($arrayfields['t.ref']['label'], $_SERVER["PHP_SELF"], "t.ref", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['t.fk_task_parent']['checked'])) {
	print_liste_field_titre($arrayfields['t.fk_task_parent']['label'], $_SERVER["PHP_SELF"], "t.fk_task_parent", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['t.label']['checked'])) {
	print_liste_field_titre($arrayfields['t.label']['label'], $_SERVER["PHP_SELF"], "t.label", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['t.description']['checked'])) {
	print_liste_field_titre($arrayfields['t.description']['label'], $_SERVER["PHP_SELF"], "t.description", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['t.dateo']['checked'])) {
	print_liste_field_titre($arrayfields['t.dateo']['label'], $_SERVER["PHP_SELF"], "t.dateo", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['t.datee']['checked'])) {
	print_liste_field_titre($arrayfields['t.datee']['label'], $_SERVER["PHP_SELF"], "t.datee", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.ref']['checked'])) {
	print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER["PHP_SELF"], "p.ref", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.title']['checked'])) {
	print_liste_field_titre($arrayfields['p.title']['label'], $_SERVER["PHP_SELF"], "p.title", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.nom']['checked'])) {
	print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], "s.nom", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.name_alias']['checked'])) {
	print_liste_field_titre($arrayfields['s.name_alias']['label'], $_SERVER["PHP_SELF"], "s.name_alias", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.fk_statut']['checked'])) {
	print_liste_field_titre($arrayfields['p.fk_statut']['label'], $_SERVER["PHP_SELF"], "p.fk_statut", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['t.planned_workload']['checked'])) {
	print_liste_field_titre($arrayfields['t.planned_workload']['label'], $_SERVER["PHP_SELF"], "t.planned_workload", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['t.duration_effective']['checked'])) {
	print_liste_field_titre($arrayfields['t.duration_effective']['label'], $_SERVER["PHP_SELF"], "t.duration_effective", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['t.progress_calculated']['checked'])) {
	print_liste_field_titre($arrayfields['t.progress_calculated']['label'], $_SERVER["PHP_SELF"], "", "", $param, '', '', '', 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['t.progress']['checked'])) {
	print_liste_field_titre($arrayfields['t.progress']['label'], $_SERVER["PHP_SELF"], "t.progress", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['t.progress_summary']['checked'])) {
	print_liste_field_titre($arrayfields['t.progress_summary']['label'], $_SERVER["PHP_SELF"], "t.progress", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['t.budget_amount']['checked'])) {
	print_liste_field_titre($arrayfields['t.budget_amount']['label'], $_SERVER["PHP_SELF"], "t.budget_amount", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['t.tobill']['checked'])) {
	print_liste_field_titre($arrayfields['t.tobill']['label'], $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['t.billed']['checked'])) {
	print_liste_field_titre($arrayfields['t.billed']['label'], $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array(
	'arrayfields' => $arrayfields,
	'param' => $param,
	'sortfield' => $sortfield,
	'sortorder' => $sortorder,
	'totalarray' => &$totalarray,
);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!empty($arrayfields['t.datec']['checked'])) {
	print_liste_field_titre($arrayfields['t.datec']['label'], $_SERVER["PHP_SELF"], "t.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['t.tms']['checked'])) {
	print_liste_field_titre($arrayfields['t.tms']['label'], $_SERVER["PHP_SELF"], "t.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
print '</tr>'."\n";

$plannedworkloadoutputformat = 'allhourmin';
$timespentoutputformat = 'allhourmin';
if (getDolGlobalString('PROJECT_PLANNED_WORKLOAD_FORMAT')) {
	$plannedworkloadoutputformat = getDolGlobalString('PROJECT_PLANNED_WORKLOAD_FORMAT');
}
if (getDolGlobalString('PROJECT_TIMES_SPENT_FORMAT')) {
	$timespentoutputformat = getDolGlobalString('PROJECT_TIME_SPENT_FORMAT');
}

// Loop on record
// --------------------------------------------------------------------
$i = 0;
$savnbfield = $totalarray['nbfield'];

$totalarray['nbfield'] = 0;
$imaxinloop = ($limit ? min($num, $limit) : $num);
while ($i < $imaxinloop) {
	$obj = $db->fetch_object($resql);
	if (empty($obj)) {
		break; // Should not happen
	}

	// Store properties in $object
	$object->id = $obj->id;
	$object->ref = $obj->ref;
	$object->label = $obj->label;
	$object->description = $obj->description;
	$object->status = $obj->status;
	$object->progress = $obj->progress;
	$object->budget_amount = $obj->budget_amount;
	$object->date_start = $db->jdate($obj->date_start);
	$object->date_end = $db->jdate($obj->date_end);
	$object->planned_workload = $obj->planned_workload;
	$object->duration_effective = $obj->duration_effective;
	$object->fk_task_parent = $obj->fk_task_parent;

	$projectstatic->id = $obj->projectid;
	$projectstatic->ref = $obj->projectref;
	$projectstatic->title = $obj->projecttitle;
	$projectstatic->public = $obj->public;
	$projectstatic->statut = $obj->projectstatus;
	$projectstatic->datee = $db->jdate($obj->projectdatee);

	if ($obj->socid) {
		$socstatic->id = $obj->socid;
		$socstatic->name = $obj->name;
		$socstatic->name_alias = $obj->alias;
	}
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

		$arraydata = array();
		$arraydata['projectlink'] = $projectstatic->getNomUrl(1);
		$arraydata['selected'] = in_array($object->id, $arrayofselected);

		print $object->getKanbanView('', $arraydata);
		if ($i == ($imaxinloop - 1)) {
			print '</div>';
			print '</td></tr>';
		}
	} else {
		$userAccess = $projectstatic->restrictedProjectArea($user); // why this ?
		if ($userAccess >= 0) {
			// Show here line of result
			$j = 0;
			print '<tr data-rowid="'.$object->id.'" class="oddeven">';

			// Action column
			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="nowrap center">';
				if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
					$selected = 0;
					if (in_array($object->id, $arrayofselected)) {
						$selected = 1;
					}
					print '<input id="cb'.$object->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$object->id.'"'.($selected ? ' checked="checked"' : '').'>';
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Ref
			if (!empty($arrayfields['t.ref']['checked'])) {
				print '<td class="nowraponall">';
				print $object->getNomUrl(1, 'withproject');
				if ($object->hasDelay()) {
					print img_warning("Late");
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Ref Parent
			if (!empty($arrayfields['t.fk_task_parent']['checked'])) {
				print '<td class="nowraponall">';
				if (!empty($object->fk_task_parent)) {
					$object_parent = new Task($db);
					$result = $object_parent->fetch($object->fk_task_parent);
					if ($result < 0) {
						setEventMessage($object_parent->error, 'errors');
					} else {
						print $object_parent->getNomUrl(1, 'withproject');
						if ($object_parent->hasDelay()) {
							print img_warning("Late");
						}
					}
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Label
			if (!empty($arrayfields['t.label']['checked'])) {
				print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($object->label).'">';
				print dol_escape_htmltag($object->label);
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Description
			if (!empty($arrayfields['t.description']['checked'])) {
				print '<td>';
				print dolGetFirstLineOfText($object->description, 5);
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Date start project
			if (!empty($arrayfields['t.dateo']['checked'])) {
				print '<td class="center">';
				print dol_print_date($db->jdate($obj->date_start), 'day');
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Date end project
			if (!empty($arrayfields['t.datee']['checked'])) {
				print '<td class="center">';
				print dol_print_date($db->jdate($obj->date_end), 'day');
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Project ref
			if (!empty($arrayfields['p.ref']['checked'])) {
				print '<td class="nowraponall tdoverflowmax150">';
				print $projectstatic->getNomUrl(1, 'task');
				if ($projectstatic->hasDelay()) {
					print img_warning("Late");
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Project title
			if (!empty($arrayfields['p.title']['checked'])) {
				print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($obj->projecttitle).'">';
				print dol_escape_htmltag($obj->projecttitle);
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Third party
			if (!empty($arrayfields['s.nom']['checked'])) {
				print '<td class="tdoverflowmax125">';
				if ($obj->socid) {
					print $socstatic->getNomUrl(1, '', 0, 0, -1, empty($arrayfields['s.name_alias']['checked']) ? 0 : 1);
				} else {
					print '&nbsp;';
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Alias
			if (!empty($arrayfields['s.name_alias']['checked'])) {
				$name_alias = '';
				if ($obj->socid) {
					$name_alias = $socstatic->name_alias;
				}
				print '<td class="tdoverflowmax125" title="'.dol_escape_htmltag($name_alias).'">';
				print dol_escape_htmltag($name_alias);
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Project status
			if (!empty($arrayfields['p.fk_statut']['checked'])) {
				print '<td class="center">';
				print $projectstatic->getLibStatut(1);
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Planned workload
			if (!empty($arrayfields['t.planned_workload']['checked'])) {
				print '<td class="center">';
				$fullhour = convertSecondToTime($obj->planned_workload, $plannedworkloadoutputformat);
				$workingdelay = convertSecondToTime($obj->planned_workload, 'all', 86400, 7); // TODO Replace 86400 and 7 to take account working hours per day and working day per weeks
				if ($obj->planned_workload != '') {
					print $fullhour;
					// TODO Add delay taking account of working hours per day and working day per week
					//if ($workingdelay != $fullhour) print '<br>('.$workingdelay.')';
				}
				//else print '--:--';
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = 't.planned_workload';
				}
				if (!isset($totalarray['val']['t.planned_workload'])) {
					$totalarray['val']['t.planned_workload'] = 0;
				}
				$totalarray['val']['t.planned_workload'] += $obj->planned_workload;
				if (!$i) {
					$totalarray['totalplannedworkloadfield'] = $totalarray['nbfield'];
				}
				if (!isset($totalarray['totalplannedworkload'])) {
					$totalarray['totalplannedworkload'] = 0;
				}
				$totalarray['totalplannedworkload'] += $obj->planned_workload;
			}
			// Time spent
			if (!empty($arrayfields['t.duration_effective']['checked'])) {
				$showlineingray = 0;
				$showproject = 1;
				print '<td class="center">';
				if ($showlineingray) {
					print '<i>';
				} else {
					print '<a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?id='.$object->id.($showproject ? '' : '&withproject=1').'">';
				}
				if ($obj->duration_effective) {
					print convertSecondToTime($obj->duration_effective, $timespentoutputformat);
				} else {
					print '--:--';
				}
				if ($showlineingray) {
					print '</i>';
				} else {
					print '</a>';
				}
				if (empty($arrayfields['t.progress_calculated']['checked'])) {
					if ($obj->planned_workload || $obj->duration_effective) {
						if ($obj->planned_workload) {
							print ' <span class="opacitymedium">('.round(100 * $obj->duration_effective / $obj->planned_workload, 2).' %)</span>';
						} else {
							print $form->textwithpicto('', $langs->trans('WorkloadNotDefined'), 1, 'help');
						}
					}
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = 't.duration_effective';
				}
				if (!isset($totalarray['val']['t.duration_effective'])) {
					$totalarray['val']['t.duration_effective'] = 0;
				}
				$totalarray['val']['t.duration_effective'] += $obj->duration_effective;
				if (!$i) {
					$totalarray['totaldurationeffectivefield'] = $totalarray['nbfield'];
				}
				if (!isset($totalarray['totaldurationeffective'])) {
					$totalarray['totaldurationeffective'] = 0;
				}
				$totalarray['totaldurationeffective'] += $obj->duration_effective;
			}
			// Calculated progress
			if (!empty($arrayfields['t.progress_calculated']['checked'])) {
				print '<td class="center">';
				if ($obj->planned_workload || $obj->duration_effective) {
					if ($obj->planned_workload) {
						print round(100 * $obj->duration_effective / $obj->planned_workload, 2).' %';
					} else {
						print $form->textwithpicto('', $langs->trans('WorkloadNotDefined'), 1, 'help');
					}
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!$i) {
					$totalarray['totalprogress_calculatedfield'] = $totalarray['nbfield'];
				}
			}
			// Declared progress
			if (!empty($arrayfields['t.progress']['checked'])) {
				print '<td class="center">';
				if ($obj->progress != '') {
					print getTaskProgressBadge($object);
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = 't.progress';
				}
				if (!isset($totalarray['val']['t.progress'])) {
					$totalarray['val']['t.progress'] = 0;
				}
				$totalarray['val']['t.progress'] += ($obj->planned_workload * $obj->progress / 100);
				if (!$i) {
					$totalarray['totalprogress_declaredfield'] = $totalarray['nbfield'];
				}
				if (!isset($totalarray['totaldurationdeclared'])) {
					$totalarray['totaldurationdeclared'] = 0;
				}
				$totalarray['totaldurationdeclared'] += $obj->planned_workload * $obj->progress / 100;
			}
			// Progress summary
			if (!empty($arrayfields['t.progress_summary']['checked'])) {
				print '<td class="center">';
				//if ($obj->progress != '') {
					print getTaskProgressView($object, false, false);
				//}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!$i) {
					$totalarray['totalprogress_summary'] = $totalarray['nbfield'];
				}
			}
			// Budget for task
			if (!empty($arrayfields['t.budget_amount']['checked'])) {
				print '<td class="center">';
				if ($object->budget_amount) {
					print '<span class="amount">'.price($object->budget_amount, 0, $langs, 1, 0, 0, $conf->currency).'</span>';
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = 't.budget_amount';
				}
				$totalarray['val']['t.budget_amount'] += $obj->budget_amount;
				if (!$i) {
					$totalarray['totalbudget_amountfield'] = $totalarray['nbfield'];
				}
				$totalarray['totalbudgetamount'] += $obj->budget_amount;
			}
			// Time not billed
			if (!empty($arrayfields['t.tobill']['checked'])) {
				print '<td class="center">';
				if ($obj->usage_bill_time) {
					print convertSecondToTime($obj->tobill, 'allhourmin');
					$totalarray['val']['t.tobill'] += $obj->tobill;
					$totalarray['totaltobill'] += $obj->tobill;
				} else {
					print '<span class="opacitymedium">'.$langs->trans("NA").'</span>';
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = 't.tobill';
				}
				if (!$i) {
					$totalarray['totaltobillfield'] = $totalarray['nbfield'];
				}
			}
			// Time billed
			if (!empty($arrayfields['t.billed']['checked'])) {
				print '<td class="center">';
				if ($obj->usage_bill_time) {
					print convertSecondToTime($obj->billed, 'allhourmin');
					$totalarray['val']['t.billed'] += $obj->billed;
					$totalarray['totalbilled'] += $obj->billed;
				} else {
					print '<span class="opacitymedium">'.$langs->trans("NA").'</span>';
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = 't.billed';
				}
				if (!$i) {
					$totalarray['totalbilledfield'] = $totalarray['nbfield'];
				}
			}
			// Extra fields
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
			// Fields from hook
			$parameters = array('arrayfields' => $arrayfields, 'object' => $object, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
			$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;
			// Date creation
			if (!empty($arrayfields['t.datec']['checked'])) {
				print '<td class="center">';
				print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Date modification
			if (!empty($arrayfields['t.tms']['checked'])) {
				print '<td class="center">';
				print dol_print_date($db->jdate($obj->date_modification), 'dayhour', 'tzuser');
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Status
			/*if (!empty($arrayfields['p.fk_statut']['checked']))
			{
				$projectstatic->statut = $obj->fk_statut;
				print '<td class="right">'.$projectstatic->getLibStatut(5).'</td>';
			}*/
			// Action column
			if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="nowrap center">';
				if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
					$selected = 0;
					if (in_array($object->id, $arrayofselected)) {
						$selected = 1;
					}
					print '<input id="cb'.$object->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$object->id.'"'.($selected ? ' checked="checked"' : '').'>';
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			print '</tr>'."\n";
		}
	}

	$i++;
}

// Show total line
if (!empty($totalarray['totalizable']) && is_array($totalarray['totalizable'])) {
	foreach ($totalarray['totalizable'] as $keytotalizable => $valtotalizable) {
		$totalarray['pos'][$valtotalizable['pos']] = $keytotalizable;
		$totalarray['val'][$keytotalizable] = $valtotalizable['total'];
	}
}

// Show total line
//include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';
if (isset($totalarray['totaldurationeffectivefield']) || isset($totalarray['totalplannedworkloadfield']) || isset($totalarray['totalprogress_calculatedfield'])
	|| isset($totalarray['totaltobill']) || isset($totalarray['totalbilled']) || isset($totalarray['totalbudget'])) {
	print '<tr class="liste_total">';
	$i = 0;
	while ($i < $totalarray['nbfield']) {
		$i++;
		if ($i == 1) {
			if ((is_null($limit) || $num < $limit) && empty($offset)) {
				print '<td>'.$langs->trans("Total").'</td>';
			} else {
				print '<td>';
				if (is_object($form)) {
					print $form->textwithpicto($langs->trans("Total"), $langs->transnoentitiesnoconv("Totalforthispage"));
				} else {
					print $langs->trans("Totalforthispage");
				}
				print '</td>';
			}
			/*
			if ($num < $limit && empty($offset)) {
				print '<td class="left">'.$langs->trans("Total").'</td>';
			} else {
				print '<td class="left">'.$langs->trans("Totalforthispage").'</td>';
			}*/
		} elseif (isset($totalarray['totalplannedworkloadfield']) && $totalarray['totalplannedworkloadfield'] == $i) {
			print '<td class="center">'.convertSecondToTime($totalarray['totalplannedworkload'], $plannedworkloadoutputformat).'</td>';
		} elseif (isset($totalarray['totaldurationeffectivefield']) && $totalarray['totaldurationeffectivefield'] == $i) {
			print '<td class="center">'.convertSecondToTime($totalarray['totaldurationeffective'], $timespentoutputformat).'</td>';
		} elseif (isset($totalarray['totalprogress_calculatedfield']) && $totalarray['totalprogress_calculatedfield'] == $i) {
			print '<td class="center">'.($totalarray['totalplannedworkload'] > 0 ? round(100 * $totalarray['totaldurationeffective'] / $totalarray['totalplannedworkload'], 2).' %' : '').'</td>';
		} elseif (isset($totalarray['totalprogress_declaredfield']) && $totalarray['totalprogress_declaredfield'] == $i) {
			print '<td class="center">'.($totalarray['totalplannedworkload'] > 0 ? round(100 * $totalarray['totaldurationdeclared'] / $totalarray['totalplannedworkload'], 2).' %' : '').'</td>';
		} elseif (isset($totalarray['totaltobillfield']) && $totalarray['totaltobillfield'] == $i) {
			print '<td class="center">'.convertSecondToTime($totalarray['totaltobill'], $plannedworkloadoutputformat).'</td>';
		} elseif (isset($totalarray['totalbilledfield']) && $totalarray['totalbilledfield'] == $i) {
			print '<td class="center">'.convertSecondToTime($totalarray['totalbilled'], $plannedworkloadoutputformat).'</td>';
		} elseif (isset($totalarray['totalbudget_amountfield']) && $totalarray['totalbudget_amountfield'] == $i) {
			print '<td class="center">'.price((float) $totalarray['totalbudgetamount'], 0, $langs, 1, 0, 0, $conf->currency).'</td>';
		} elseif (!empty($totalarray['pos'][$i])) {
			print '<td class="right">';
			if (isset($totalarray['type']) && $totalarray['type'][$i] == 'duration') {
				print(!empty($totalarray['val'][$totalarray['pos'][$i]]) ? convertSecondToTime($totalarray['val'][$totalarray['pos'][$i]], 'allhourmin') : 0);
			} else {
				print price(!empty($totalarray['val'][$totalarray['pos'][$i]]) ? $totalarray['val'][$totalarray['pos'][$i]] : 0);
			}
			print '</td>';
		} else {
			print '<td></td>';
		}
	}
	print '</tr>';
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
