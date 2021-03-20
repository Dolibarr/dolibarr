<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2017 Regis Houssin        <regis.houssin@inodbox.com>
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
 *	\file       htdocs/projet/tasks.php
 *	\ingroup    project
 *	\brief      List all tasks of a project
 */

require "../main.inc.php";
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
if ($conf->categorie->enabled) { require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php'; }

// Load translation files required by the page
$langs->loadLangs(array('projects', 'users', 'companies'));

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$taskref = GETPOST('taskref', 'alpha');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$backtopage = GETPOST('backtopage', 'alpha');
$cancel = GETPOST('cancel', 'alpha');

$search_user_id = GETPOST('search_user_id', 'int');
$search_taskref = GETPOST('search_taskref');
$search_tasklabel = GETPOST('search_tasklabel');
$search_taskdescription = GETPOST('search_taskdescription');
$search_dtstartday = GETPOST('search_dtstartday');
$search_dtstartmonth = GETPOST('search_dtstartmonth');
$search_dtstartyear = GETPOST('search_dtstartyear');
$search_dtendday = GETPOST('search_dtendday');
$search_dtendmonth = GETPOST('search_dtendmonth');
$search_dtendyear = GETPOST('search_dtendyear');
$search_planedworkload = GETPOST('search_planedworkload');
$search_timespend = GETPOST('search_timespend');
$search_progresscalc = GETPOST('search_progresscalc');
$search_progressdeclare = GETPOST('search_progressdeclare');

$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'projecttasklist';

//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects

$object = new Project($db);
$taskstatic = new Task($db);
$extrafields = new ExtraFields($db);

include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once
if (!empty($conf->global->PROJECT_ALLOW_COMMENT_ON_PROJECT) && method_exists($object, 'fetchComments') && empty($object->comments)) $object->fetchComments();

if ($id > 0 || !empty($ref))
{
	// fetch optionals attributes and labels
	$extrafields->fetch_name_optionals_label($object->table_element);
}
$extrafields->fetch_name_optionals_label($taskstatic->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($taskstatic->table_element, '', 'search_');


// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) { reset($object->fields); $sortfield="t.".key($object->fields); }   // Set here default search field. By default 1st field in definition. Reset is required to avoid key() to return null.
if (!$sortorder) $sortorder = "ASC";


// Security check
$socid = 0;
//if ($user->socid > 0) $socid = $user->socid;    // For external user, no check is done on company because readability is managed by public status of project and assignement.
$result = restrictedArea($user, 'projet', $id, 'projet&project');

$diroutputmassaction = $conf->projet->dir_output.'/tasks/temp/massgeneration/'.$user->id;

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('projecttaskscard', 'globalcard'));

$progress = GETPOST('progress', 'int');
$label = GETPOST('label', 'alpha');
$description = GETPOST('description', 'restricthtml');
$planned_workloadhour = (GETPOST('planned_workloadhour', 'int') ?GETPOST('planned_workloadhour', 'int') : 0);
$planned_workloadmin = (GETPOST('planned_workloadmin', 'int') ?GETPOST('planned_workloadmin', 'int') : 0);
$planned_workload = $planned_workloadhour * 3600 + $planned_workloadmin * 60;

// Definition of fields for list
$arrayfields = array(
	't.ref'=>array('label'=>$langs->trans("RefTask"), 'checked'=>1, 'position'=>1),
	't.label'=>array('label'=>$langs->trans("LabelTask"), 'checked'=>1, 'position'=>2),
	't.description'=>array('label'=>$langs->trans("Description"), 'checked'=>0, 'position'=>3),
	't.dateo'=>array('label'=>$langs->trans("DateStart"), 'checked'=>1, 'position'=>4),
	't.datee'=>array('label'=>$langs->trans("Deadline"), 'checked'=>1, 'position'=>5),
	't.planned_workload'=>array('label'=>$langs->trans("PlannedWorkload"), 'checked'=>1, 'position'=>6),
	't.duration_effective'=>array('label'=>$langs->trans("TimeSpent"), 'checked'=>1, 'position'=>7),
	't.progress_calculated'=>array('label'=>$langs->trans("ProgressCalculated"), 'checked'=>1, 'position'=>8),
	't.progress'=>array('label'=>$langs->trans("ProgressDeclared"), 'checked'=>1, 'position'=>9),
	't.progress_summary'=>array('label'=>$langs->trans("TaskProgressSummary"), 'checked'=>1, 'position'=>10),
);
if ($object->usage_bill_time) {
	$arrayfields['t.tobill'] = array('label'=>$langs->trans("TimeToBill"), 'checked'=>0, 'position'=>11);
	$arrayfields['t.billed'] = array('label'=>$langs->trans("TimeBilled"), 'checked'=>0, 'position'=>12);
}

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$arrayfields = dol_sort_array($arrayfields, 'position');

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;


/*
 * Actions
 */

$parameters = array('id'=>$id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
	{
		$search_user_id = "";
		$search_taskref = '';
		$search_tasklabel = '';
		$search_dtstartday = '';
		$search_dtstartmonth = '';
		$search_dtstartyear = '';
		$search_dtendday = '';
		$search_dtendmonth = '';
		$search_dtendyear = '';
		$search_planedworkload = '';
		$search_timespend = '';
		$search_progresscalc = '';
		$search_progressdeclare = '';
		$toselect = '';
		$search_array_options = array();
	}

	// Mass actions
	$objectclass = 'Task';
	$objectlabel = 'Tasks';
	$permissiontoread = $user->rights->projet->lire;
	$permissiontodelete = $user->rights->projet->supprimer;
	$uploaddir = $conf->projet->dir_output.'/tasks';
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}

$morewherefilterarray = array();

if (!empty($search_taskref)) {
	$morewherefilterarray[] = natural_search('t.ref', $search_taskref, 0, 1);
}

if (!empty($search_tasklabel)) {
	$morewherefilterarray[] = natural_search('t.label', $search_tasklabel, 0, 1);
}

$moresql = dolSqlDateFilter('t.dateo', $search_dtstartday, $search_dtstartmonth, $search_dtstartyear, 1);
if ($moresql) $morewherefilterarray[] = $moresql;

$moresql = dolSqlDateFilter('t.datee', $search_dtendday, $search_dtendmonth, $search_dtendyear, 1);
if ($moresql) $morewherefilterarray[] = $moresql;

if (!empty($search_planedworkload)) {
	$morewherefilterarray[] = natural_search('t.planned_workload', $search_planedworkload, 1, 1);
}

if (!empty($search_timespend)) {
	$morewherefilterarray[] = natural_search('t.duration_effective', $search_timespend, 1, 1);
}

if (!empty($search_progresscalc)) {
	$filterprogresscalc = 'if '.natural_search('round(100 * $line->duration / $line->planned_workload,2)', $search_progresscalc, 1, 1).'{return 1;} else {return 0;}';
} else {
	$filterprogresscalc = '';
}

if (!empty($search_progressdeclare)) {
	$morewherefilterarray[] = natural_search('t.progress', $search_progressdeclare, 1, 1);
}

$morewherefilter = '';
if (count($morewherefilterarray) > 0) {
	$morewherefilter = ' AND '.implode(' AND ', $morewherefilterarray);
}

if ($action == 'createtask' && $user->rights->projet->creer)
{
	$error = 0;

	// If we use user timezone, we must change also view/list to use user timezone everywhere
	//$date_start = dol_mktime($_POST['dateohour'],$_POST['dateomin'],0,$_POST['dateomonth'],$_POST['dateoday'],$_POST['dateoyear'],'user');
	//$date_end = dol_mktime($_POST['dateehour'],$_POST['dateemin'],0,$_POST['dateemonth'],$_POST['dateeday'],$_POST['dateeyear'],'user');
	$date_start = dol_mktime(GETPOST('dateohour', 'int'), GETPOST('dateomin', 'int'), 0, GETPOST('dateomonth', 'int'), GETPOST('dateoday', 'int'), GETPOST('dateoyear', 'int'));
	$date_end = dol_mktime(GETPOST('dateehour', 'int'), GETPOST('dateemin', 'int'), 0, GETPOST('dateemonth', 'int'), GETPOST('dateeday', 'int'), GETPOST('dateeyear', 'int'));

	if (!$cancel)
	{
		if (empty($taskref))
		{
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Ref")), null, 'errors');
			$action = 'create';
			$error++;
		}
		if (empty($label))
		{
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
			$action = 'create';
			$error++;
		} elseif (empty($_POST['task_parent']))
		{
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("ChildOfProjectTask")), null, 'errors');
			$action = 'create';
			$error++;
		}

		if (!$error)
		{
			$tmparray = explode('_', GETPOST('task_parent'));
			$projectid = $tmparray[0];
			if (empty($projectid)) $projectid = $id; // If projectid is ''
			$task_parent = $tmparray[1];
			if (empty($task_parent)) $task_parent = 0; // If task_parent is ''

			$task = new Task($db);

			$task->fk_project = $projectid;
			$task->ref = $taskref;
			$task->label = $label;
			$task->description = $description;
			$task->planned_workload = $planned_workload;
			$task->fk_task_parent = $task_parent;
			$task->date_c = dol_now();
			$task->date_start = $date_start;
			$task->date_end = $date_end;
			$task->progress = $progress;

			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost(null, $task);

			$taskid = $task->create($user);

			if ($taskid > 0)
			{
				$result = $task->add_contact($_POST["userid"], 'TASKEXECUTIVE', 'internal');
			} else {
				if ($db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
				{
					$langs->load("projects");
					setEventMessages($langs->trans('NewTaskRefSuggested'), '', 'warnings');
					$duplicate_code_error = true;
				} else {
					setEventMessages($task->error, $task->errors, 'errors');
				}
				$action = 'create';
				$error++;
			}
		}

		if (!$error)
		{
			if (!empty($backtopage))
			{
				header("Location: ".$backtopage);
				exit;
			} elseif (empty($projectid))
			{
				header("Location: ".DOL_URL_ROOT.'/projet/tasks/list.php'.(empty($mode) ? '' : '?mode='.$mode));
				exit;
			}
			$id = $projectid;
		}
	} else {
		if (!empty($backtopage))
		{
			header("Location: ".$backtopage);
			exit;
		} elseif (empty($id))
		{
			// We go back on task list
			header("Location: ".DOL_URL_ROOT.'/projet/tasks/list.php'.(empty($mode) ? '' : '?mode='.$mode));
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
$socstatic = new Societe($db);
$projectstatic = new Project($db);
$taskstatic = new Task($db);
$userstatic = new User($db);

$title = $langs->trans("Project").' - '.$langs->trans("Tasks").' - '.$object->ref.' '.$object->name;
if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/projectnameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name) $title = $object->ref.' '.$object->name.' - '.$langs->trans("Tasks");
$help_url = "EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";

llxHeader("", $title, $help_url);


if ($id > 0 || !empty($ref))
{
	$object->fetch($id, $ref);
	$object->fetch_thirdparty();
	$res = $object->fetch_optionals();

	// To verify role of users
	//$userAccess = $object->restrictedProjectArea($user,'read');
	$userWrite = $object->restrictedProjectArea($user, 'write');
	//$userDelete = $object->restrictedProjectArea($user,'delete');
	//print "userAccess=".$userAccess." userWrite=".$userWrite." userDelete=".$userDelete;


	$tab = (GETPOSTISSET('tab') ? GETPOST('tab') : 'tasks');

	$head = project_prepare_head($object);
	print dol_get_fiche_head($head, $tab, $langs->trans("Project"), -1, ($object->public ? 'projectpub' : 'project'));

	$param = '&id='.$object->id;
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
	if ($search_user_id)                 		$param .= '&search_user_id='.urlencode($search_user_id);
	if ($search_taskref)              		$param .= '&search_taskref='.urlencode($search_taskref);
	if ($search_tasklabel)              		$param .= '&search_tasklabel='.urlencode($search_tasklabel);
	if ($search_taskdescription)              		$param .= '&search_taskdescription='.urlencode($search_taskdescription);
	if ($search_dtstartday)              		$param .= '&search_dtstartday='.urlencode($search_dtstartday);
	if ($search_dtstartmonth)              		$param .= '&search_dtstartmonth='.urlencode($search_dtstartmonth);
	if ($search_dtstartyear)              		$param .= '&search_dtstartyear='.urlencode($search_dtstartyear);
	if ($search_dtendday)              		$param .= '&search_dtendday='.urlencode($search_dtendday);
	if ($search_dtendmonth)              		$param .= '&search_dtendmonth='.urlencode($search_dtendmonth);
	if ($search_dtendyear)              		$param .= '&search_dtendyear='.urlencode($search_dtendyear);
	if ($search_planedworkload)              		$param .= '&search_planedworkload='.urlencode($search_planedworkload);
	if ($search_timespend)              		$param .= '&search_timespend='.urlencode($search_timespend);
	if ($search_progresscalc)              		$param .= '&search_progresscalc='.urlencode($search_progresscalc);
	if ($search_progressdeclare)              		$param .= '&search_progressdeclare='.urlencode($search_progressdeclare);
	if ($optioncss != '')           $param .= '&optioncss='.urlencode($optioncss);
	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// Project card

	$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	// Title
	$morehtmlref .= $object->title;
	// Thirdparty
	if ($object->thirdparty->id > 0)
	{
		$morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1, 'project');
	}
	$morehtmlref .= '</div>';

	// Define a complementary filter for search of next/prev ref.
	if (!$user->rights->projet->all->lire)
	{
		$objectsListId = $object->getProjectsAuthorizedForUser($user, 0, 0);
		$object->next_prev_filter = " rowid in (".(count($objectsListId) ?join(',', array_keys($objectsListId)) : '0').")";
	}

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield" width="100%">';

	// Usage
	if (!empty($conf->global->PROJECT_USE_OPPORTUNITIES) || empty($conf->global->PROJECT_HIDE_TASKS))
	{
		print '<tr><td class="tdtop">';
		print $langs->trans("Usage");
		print '</td>';
		print '<td>';
		if (!empty($conf->global->PROJECT_USE_OPPORTUNITIES))
		{
			print '<input type="checkbox" disabled name="usage_opportunity"'.(GETPOSTISSET('usage_opportunity') ? (GETPOST('usage_opportunity', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_opportunity ? ' checked="checked"' : '')).'"> ';
			$htmltext = $langs->trans("ProjectFollowOpportunity");
			print $form->textwithpicto($langs->trans("ProjectFollowOpportunity"), $htmltext);
			print '<br>';
		}
		if (empty($conf->global->PROJECT_HIDE_TASKS))
		{
			print '<input type="checkbox" disabled name="usage_task"'.(GETPOSTISSET('usage_task') ? (GETPOST('usage_task', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_task ? ' checked="checked"' : '')).'"> ';
			$htmltext = $langs->trans("ProjectFollowTasks");
			print $form->textwithpicto($langs->trans("ProjectFollowTasks"), $htmltext);
			print '<br>';
		}
		if (empty($conf->global->PROJECT_HIDE_TASKS) && !empty($conf->global->PROJECT_BILL_TIME_SPENT))
		{
			print '<input type="checkbox" disabled name="usage_bill_time"'.(GETPOSTISSET('usage_bill_time') ? (GETPOST('usage_bill_time', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_bill_time ? ' checked="checked"' : '')).'"> ';
			$htmltext = $langs->trans("ProjectBillTimeDescription");
			print $form->textwithpicto($langs->trans("BillTime"), $htmltext);
			print '<br>';
		}
		print '</td></tr>';
	}

	// Visibility
	print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
	if ($object->public) print $langs->trans('SharedProject');
	else print $langs->trans('PrivateProject');
	print '</td></tr>';

	// Date start - end
	print '<tr><td>'.$langs->trans("DateStart").' - '.$langs->trans("DateEnd").'</td><td>';
	$start = dol_print_date($object->date_start, 'day');
	print ($start ? $start : '?');
	$end = dol_print_date($object->date_end, 'day');
	print ' - ';
	print ($end ? $end : '?');
	if ($object->hasDelay()) print img_warning("Late");
	print '</td></tr>';

	// Budget
	print '<tr><td>'.$langs->trans("Budget").'</td><td>';
	if (strcmp($object->budget_amount, '')) print price($object->budget_amount, '', $langs, 1, 0, 0, $conf->currency);
	print '</td></tr>';

	// Other attributes
	$cols = 2;
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';

	print '</div>';
	print '<div class="fichehalfright">';
	print '<div class="ficheaddleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield" width="100%">';

	// Description
	print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
	print nl2br($object->description);
	print '</td></tr>';

	// Categories
	if ($conf->categorie->enabled) {
		print '<tr><td valign="middle">'.$langs->trans("Categories").'</td><td>';
		print $form->showCategories($object->id, Categorie::TYPE_PROJECT, 1);
		print "</td></tr>";
	}

	print '</table>';

	print '</div>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';


	print dol_get_fiche_end();
}


if ($action == 'create' && $user->rights->projet->creer && (empty($object->thirdparty->id) || $userWrite > 0))
{
	if ($id > 0 || !empty($ref)) print '<br>';

	print load_fiche_titre($langs->trans("NewTask"), '', 'projecttask');

	if ($object->id > 0 && $object->statut == Project::STATUS_CLOSED) {
		print '<div class="warning">';
		$langs->load("errors");
		print $langs->trans("WarningProjectClosed");
		print '</div>';
	}

	if ($object->id > 0 && $object->statut == Project::STATUS_DRAFT) {
		print '<div class="warning">';
		$langs->load("errors");
		print $langs->trans("WarningProjectDraft");
		print '</div>';
	}

	print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="createtask">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if (!empty($object->id)) print '<input type="hidden" name="id" value="'.$object->id.'">';

	print dol_get_fiche_head('');

	print '<table class="border centpercent">';

	$defaultref = '';
	$obj = empty($conf->global->PROJECT_TASK_ADDON) ? 'mod_task_simple' : $conf->global->PROJECT_TASK_ADDON;
	if (!empty($conf->global->PROJECT_TASK_ADDON) && is_readable(DOL_DOCUMENT_ROOT."/core/modules/project/task/".$conf->global->PROJECT_TASK_ADDON.".php"))
	{
		require_once DOL_DOCUMENT_ROOT."/core/modules/project/task/".$conf->global->PROJECT_TASK_ADDON.'.php';
		$modTask = new $obj;
		$defaultref = $modTask->getNextValue($object->thirdparty, null);
	}

	if (is_numeric($defaultref) && $defaultref <= 0) $defaultref = '';

	// Ref
	print '<tr><td class="titlefieldcreate"><span class="fieldrequired">'.$langs->trans("Ref").'</span></td><td>';
	if (empty($duplicate_code_error))
	{
		print (GETPOSTISSET("ref") ? GETPOST("ref", 'alpha') : $defaultref);
	} else {
		print $defaultref;
	}
	print '<input type="hidden" name="taskref" value="'.(GETPOSTISSET("ref") ? GETPOST("ref", 'alpha') : $defaultref).'">';
	print '</td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td>';
	print '<input type="text" name="label" autofocus class="minwidth500 maxwidthonsmartphone" value="'.$label.'">';
	print '</td></tr>';

	// List of projects
	print '<tr><td class="fieldrequired">'.$langs->trans("ChildOfProjectTask").'</td><td>';
	print img_picto('', 'project');
	$formother->selectProjectTasks(GETPOST('task_parent'), $projectid ? $projectid : $object->id, 'task_parent', 0, 0, 1, 1, 0, '0,1', 'maxwidth500');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("AffectedTo").'</td><td>';
	$contactsofproject = (!empty($object->id) ? $object->getListContactId('internal') : '');
	if (is_array($contactsofproject) && count($contactsofproject))
	{
		print $form->select_dolusers($user->id, 'userid', 0, '', 0, '', $contactsofproject, 0, 0, 0, '', 0, '', 'maxwidth300');
	} else {
		print $langs->trans("NoUserAssignedToTheProject");
	}
	print '</td></tr>';

	// Date start
	print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
	print $form->selectDate(($date_start ? $date_start : ''), 'dateo', 1, 1, 0, '', 1, 1);
	print '</td></tr>';

	// Date end
	print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
	print $form->selectDate(($date_end ? $date_end : -1), 'datee', -1, 1, 0, '', 1, 1);
	print '</td></tr>';

	// Planned workload
	print '<tr><td>'.$langs->trans("PlannedWorkload").'</td><td>';
	print $form->select_duration('planned_workload', $planned_workload ? $planned_workload : 0, 0, 'text');
	print '</td></tr>';

	// Progress
	print '<tr><td>'.$langs->trans("ProgressDeclared").'</td><td colspan="3">';
	print $formother->select_percent($progress, 'progress', 0, 5, 0, 100, 1);
	print '</td></tr>';

	// Description
	print '<tr><td class="tdtop">'.$langs->trans("Description").'</td>';
	print '<td>';
	print '<textarea name="description" class="quatrevingtpercent" rows="'.ROWS_4.'">'.$description.'</textarea>';
	print '</td></tr>';

	// Other options
	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $taskstatic, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	if (empty($reshook) && !empty($extrafields->attributes[$taskstatic->table_element]['label']))
	{
		print $taskstatic->showOptionals($extrafields, 'edit'); // Do not use $object here that is object of project but use $taskstatic
	}

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="add" value="'.$langs->trans("Add").'">';
	print ' &nbsp; &nbsp; ';
	print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
} elseif ($id > 0 || !empty($ref)) {
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

	/*
	 * Projet card in view mode
	 */

	print '<br>';

	// Link to create task
	$linktocreatetaskParam = array();
	$linktocreatetaskUserRight = false;
	if ($user->rights->projet->all->creer || $user->rights->projet->creer) {
		if ($object->public || $userWrite > 0) {
			$linktocreatetaskUserRight = true;
		} else {
			$linktocreatetaskParam['attr']['title'] = $langs->trans("NotOwnerOfProject");
		}
	}

	$linktocreatetask = dolGetButtonTitle($langs->trans('AddTask'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/projet/tasks.php?action=create'.$param.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$object->id), '', $linktocreatetaskUserRight, $linktocreatetaskParam);

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

	$title = $langs->trans("ListOfTasks");
	$linktotasks = dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-list-alt imgforviewmode', DOL_URL_ROOT.'/projet/tasks.php?id='.$object->id, '', 1, array('morecss'=>'reposition btnTitleSelected'));
	$linktotasks .= dolGetButtonTitle($langs->trans('ViewGantt'), '', 'fa fa-stream imgforviewmode', DOL_URL_ROOT.'/projet/ganttview.php?id='.$object->id.'&withproject=1', '', 1, array('morecss'=>'reposition marginleftonly'));

	//print_barre_liste($title, 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, $linktotasks, $num, $totalnboflines, 'generic', 0, '', '', 0, 1);
	print load_fiche_titre($title, $linktotasks.' &nbsp; '.$linktocreatetask, 'projecttask');

	// Get list of tasks in tasksarray and taskarrayfiltered
	// We need all tasks (even not limited to a user because a task to user can have a parent that is not affected to him).
	$filteronthirdpartyid = $socid;
	$tasksarray = $taskstatic->getTasksArray(0, 0, $object->id, $filteronthirdpartyid, 0, '', -1, $morewherefilter, 0, 0, $extrafields, 1, $search_array_options);

	// We load also tasks limited to a particular user
	$tmpuser = new User($db);
	if ($search_user_id > 0) $tmpuser->fetch($search_user_id);

	$tasksrole = ($tmpuser->id > 0 ? $taskstatic->getUserRolesForProjectsOrTasks(0, $tmpuser, $object->id, 0) : '');
	//var_dump($tasksarray);
	//var_dump($tasksrole);

	if (!empty($conf->use_javascript_ajax))
	{
		include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
	}

	// Filter on categories
	$moreforfilter = '';
	if (count($tasksarray) > 0)
	{
		$moreforfilter .= '<div class="divsearchfield">';
		$moreforfilter .= $langs->trans("TasksAssignedTo").': ';
		$moreforfilter .= $form->select_dolusers($tmpuser->id > 0 ? $tmpuser->id : '', 'search_user_id', 1);
		$moreforfilter .= '</div>';
	}
	if ($moreforfilter)
	{
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>';
	}

	print '<div class="div-table-responsive">';
	print '<table id="tablelines" class="tagtable nobottom liste'.($moreforfilter ? " listwithfilterbefore" : "").'">';

	// Fields title search
	print '<tr class="liste_titre_filter">';

	if (!empty($arrayfields['t.ref']['checked'])) {
		print '<td class="liste_titre">';
		print '<input class="flat searchstring maxwidth50" type="text" name="search_taskref" value="'.dol_escape_htmltag($search_taskref).'">';
		print '</td>';
	}

	if (!empty($arrayfields['t.label']['checked'])) {
		print '<td class="liste_titre">';
		print '<input class="flat searchstring maxwidth100" type="text" name="search_tasklabel" value="'.dol_escape_htmltag($search_tasklabel).'">';
		print '</td>';
	}

	if (!empty($arrayfields['t.description']['checked'])) {
		print '<td class="liste_titre">';
		print '<input class="flat searchstring maxwidth100" type="text" name="search_taskdescription" value="'.dol_escape_htmltag($search_taskdescription).'">';
		print '</td>';
	}

	if (!empty($arrayfields['t.dateo']['checked'])) {
		print '<td class="liste_titre center">';
		print '<span class="nowraponall"><input class="flat valignmiddle width20" type="text" maxlength="2" name="search_dtstartday" value="'.$search_dtstartday.'">';
		print '<input class="flat valignmiddle width20" type="text" maxlength="2" name="search_dtstartmonth" value="'.$search_dtstartmonth.'"></span>';
		$formother->select_year($search_dtstartyear ? $search_dtstartyear : -1, 'search_dtstartyear', 1, 20, 5);
		print '</td>';
	}

	if (!empty($arrayfields['t.datee']['checked'])) {
		print '<td class="liste_titre center">';
		print '<span class="nowraponall"><input class="flat valignmiddle width20" type="text" maxlength="2" name="search_dtendday" value="'.$search_dtendday.'">';
		print '<input class="flat valignmiddle width20" type="text" maxlength="2" name="search_dtendmonth" value="'.$search_dtendmonth.'"></span>';
		$formother->select_year($search_dtendyear ? $search_dtendyear : -1, 'search_dtendyear', 1, 20, 5);
		print '</td>';
	}

	if (!empty($arrayfields['t.planned_workload']['checked'])) {
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_planedworkload" value="'.$search_planedworkload.'">';
		print '</td>';
	}

	if (!empty($arrayfields['t.duration_effective']['checked'])) {
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_timespend" value="'.$search_timespend.'">';
		print '</td>';
	}

	if (!empty($arrayfields['t.progress_calculated']['checked'])) {
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_progresscalc" value="'.$search_progresscalc.'">';
		print '</td>';
	}

	if (!empty($arrayfields['t.progress']['checked'])) {
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_progressdeclare" value="'.$search_progressdeclare.'">';
		print '</td>';
	}

	// progress resume not searchable
	print '<td class="liste_titre right"></td>';

	if ($object->usage_bill_time)
	{
		if (!empty($arrayfields['t.tobill']['checked'])) {
			print '<td class="liste_titre right">';
			print '</td>';
		}

		if (!empty($arrayfields['t.billed']['checked'])) {
			print '<td class="liste_titre right">';
			print '</td>';
		}
	}

	if (!empty($conf->global->PROJECT_SHOW_CONTACTS_IN_LIST)) print '<td class="liste_titre"></td>';

	$extrafieldsobjectkey = $taskstatic->table_element;
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

	// Action column
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
	print "</tr>\n";

	print '<tr class="liste_titre nodrag nodrop">';
	// print '<td>'.$langs->trans("Project").'</td>';
	if (!empty($arrayfields['t.ref']['checked'])) print_liste_field_titre($arrayfields['t.ref']['label'], $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, '');
	if (!empty($arrayfields['t.label']['checked'])) print_liste_field_titre($arrayfields['t.label']['label'], $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, '');
	if (!empty($arrayfields['t.description']['checked'])) print_liste_field_titre($arrayfields['t.description']['label'], $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, '');
	if (!empty($arrayfields['t.dateo']['checked'])) print_liste_field_titre($arrayfields['t.dateo']['label'], $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'center ');
	if (!empty($arrayfields['t.datee']['checked'])) print_liste_field_titre($arrayfields['t.datee']['label'], $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'center ');
	if (!empty($arrayfields['t.planned_workload']['checked'])) print_liste_field_titre($arrayfields['t.planned_workload']['label'], $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'right ', '', 1);
	if (!empty($arrayfields['t.duration_effective']['checked'])) print_liste_field_titre($arrayfields['t.duration_effective']['label'], $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'right ', '', 1);
	if (!empty($arrayfields['t.progress_calculated']['checked'])) print_liste_field_titre($arrayfields['t.progress_calculated']['label'], $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'right ', '', 1);
	if (!empty($arrayfields['t.progress']['checked']))  print_liste_field_titre($arrayfields['t.progress']['label'], $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'right ', '', 1);
	if (!empty($arrayfields['t.progress_summary']['checked'])) print_liste_field_titre($arrayfields['t.progress_summary']['label'], $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'center ', '', 1);
	if ($object->usage_bill_time)
	{
		if (!empty($arrayfields['t.tobill']['checked']))  print_liste_field_titre($arrayfields['t.tobill']['label'], $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'right ');
		if (!empty($arrayfields['t.billed']['checked'])) print_liste_field_titre($arrayfields['t.billed']['label'], $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'right ');
	}
	if (!empty($conf->global->PROJECT_SHOW_CONTACTS_IN_LIST)) print_liste_field_titre("TaskRessourceLinks", $_SERVER["PHP_SELF"], '', '', $param, $sortfield, $sortorder);
	// Extra fields
	$disablesortlink = 1;
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	print "</tr>\n";

	if (count($tasksarray) > 0)
	{
		// Show all lines in taskarray (recursive function to go down on tree)
		$j = 0; $level = 0;
		$nboftaskshown = projectLinesa($j, 0, $tasksarray, $level, true, 0, $tasksrole, $object->id, 1, $object->id, $filterprogresscalc, ($object->usage_bill_time ? 1 : 0), $arrayfields);
	} else {
		$colspan = 10;
		if ($object->usage_bill_time) $colspan += 2;
		print '<tr class="oddeven nobottom"><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoTasks").'</span></td></tr>';
	}

	print "</table>";
	print '</div>';

	print '</form>';


	// Test if database is clean. If not we clean it.
	//print 'mode='.$_REQUEST["mode"].' $nboftaskshown='.$nboftaskshown.' count($tasksarray)='.count($tasksarray).' count($tasksrole)='.count($tasksrole).'<br>';
	if (!empty($user->rights->projet->all->lire))	// We make test to clean only if user has permission to see all (test may report false positive otherwise)
	{
		if ($search_user_id == $user->id)
		{
			if ($nboftaskshown < count($tasksrole))
			{
				include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
				cleanCorruptedTree($db, 'projet_task', 'fk_task_parent');
			}
		} else {
			if ($nboftaskshown < count($tasksarray) && !GETPOST('search_user_id', 'int'))
			{
				include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
				cleanCorruptedTree($db, 'projet_task', 'fk_task_parent');
			}
		}
	}
}

// End of page
llxFooter();
$db->close();
