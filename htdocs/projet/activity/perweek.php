<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010      François Legastelois <flegastelois@teclib.com>
 * Copyright (C) 2018      Frédéric France      <frederic.france@netlogic.fr>
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
 *	\file       htdocs/projet/activity/perweek.php
 *	\ingroup    projet
 *	\brief      List activities of tasks (per week entry)
 */

require "../../main.inc.php";
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';

// Load translation files required by the page
$langs->loadLangs(array('projects', 'users', 'companies'));

$action = GETPOST('action', 'aZ09');
$mode = GETPOST("mode", 'alpha');
$id = GETPOST('id', 'int');
$taskid = GETPOST('taskid', 'int');

$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'perweekcard';

$mine = 0;
if ($mode == 'mine') {
	$mine = 1;
}

$projectid = GETPOSTISSET("id") ? GETPOST("id", "int", 1) : GETPOST("projectid", "int");

$hookmanager->initHooks(array('timesheetperweekcard'));

// Security check
$socid = 0;
// For external user, no check is done on company because readability is managed by public status of project and assignement.
// if ($user->socid > 0) $socid=$user->socid;
$result = restrictedArea($user, 'projet', $projectid);

$now = dol_now();

$year = GETPOST('reyear', 'int') ?GETPOST('reyear', 'int') : (GETPOST("year", 'int') ?GETPOST("year", "int") : date("Y"));
$month = GETPOST('remonth', 'int') ?GETPOST('remonth', 'int') : (GETPOST("month", 'int') ?GETPOST("month", "int") : date("m"));
$day = GETPOST('reday', 'int') ?GETPOST('reday', 'int') : (GETPOST("day", 'int') ?GETPOST("day", "int") : date("d"));
$week = GETPOST("week", "int") ?GETPOST("week", "int") : date("W");

$day = (int) $day;

//$search_categ = GETPOST("search_categ", 'alpha');
$search_usertoprocessid = GETPOST('search_usertoprocessid', 'int');
$search_task_ref = GETPOST('search_task_ref', 'alpha');
$search_task_label = GETPOST('search_task_label', 'alpha');
$search_project_ref = GETPOST('search_project_ref', 'alpha');
$search_thirdparty = GETPOST('search_thirdparty', 'alpha');
$search_declared_progress = GETPOST('search_declared_progress', 'alpha');

$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');

$startdayarray = dol_get_first_day_week($day, $month, $year);

$prev = $startdayarray;
$prev_year  = $prev['prev_year'];
$prev_month = $prev['prev_month'];
$prev_day   = $prev['prev_day'];
$first_day  = $prev['first_day'];
$first_month = $prev['first_month'];
$first_year = $prev['first_year'];
$week = $prev['week'];

$next = dol_get_next_week($first_day, $week, $first_month, $first_year);
$next_year  = $next['year'];
$next_month = $next['month'];
$next_day   = $next['day'];

// Define firstdaytoshow and lastdaytoshow (warning: lastdaytoshow is last second to show + 1)
$firstdaytoshow = dol_mktime(0, 0, 0, $first_month, $first_day, $first_year);
$firstdaytoshowgmt = dol_mktime(0, 0, 0, $first_month, $first_day, $first_year, 'gmt');

if (empty($search_usertoprocessid) || $search_usertoprocessid == $user->id) {
	$usertoprocess = $user;
	$search_usertoprocessid = $usertoprocess->id;
} elseif ($search_usertoprocessid > 0) {
	$usertoprocess = new User($db);
	$usertoprocess->fetch($search_usertoprocessid);
	$search_usertoprocessid = $usertoprocess->id;
} else {
	$usertoprocess = new User($db);
}

$object = new Task($db);

// Extra fields
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Definition of fields for list
$arrayfields = array();
/*$arrayfields=array(
 // Project
 'p.opp_amount'=>array('label'=>$langs->trans("OpportunityAmountShort"), 'checked'=>0, 'enabled'=>($conf->global->PROJECT_USE_OPPORTUNITIES?1:0), 'position'=>103),
 'p.fk_opp_status'=>array('label'=>$langs->trans("OpportunityStatusShort"), 'checked'=>0, 'enabled'=>($conf->global->PROJECT_USE_OPPORTUNITIES?1:0), 'position'=>104),
 'p.opp_percent'=>array('label'=>$langs->trans("OpportunityProbabilityShort"), 'checked'=>0, 'enabled'=>($conf->global->PROJECT_USE_OPPORTUNITIES?1:0), 'position'=>105),
 'p.budget_amount'=>array('label'=>$langs->trans("Budget"), 'checked'=>0, 'position'=>110),
 'p.usage_bill_time'=>array('label'=>$langs->trans("BillTimeShort"), 'checked'=>0, 'position'=>115),
 );*/
$arrayfields['t.planned_workload'] = array('label'=>'PlannedWorkload', 'checked'=>1, 'enabled'=>1, 'position'=>5);
$arrayfields['t.progress'] = array('label'=>'ProgressDeclared', 'checked'=>1, 'enabled'=>1, 'position'=>10);
$arrayfields['timeconsumed'] = array('label'=>'TimeConsumed', 'checked'=>1, 'enabled'=>1, 'position'=>15);
/*foreach($object->fields as $key => $val)
 {
 // If $val['visible']==0, then we never show the field
 if (! empty($val['visible'])) $arrayfields['t.'.$key]=array('label'=>$val['label'], 'checked'=>(($val['visible']<0)?0:1), 'enabled'=>$val['enabled'], 'position'=>$val['position']);
 }*/
// Definition of fields for list
// Extra fields
if (!empty($extrafields->attributes['projet_task']['label']) && is_array($extrafields->attributes['projet_task']['label']) && count($extrafields->attributes['projet_task']['label']) > 0) {
	foreach ($extrafields->attributes['projet_task']['label'] as $key => $val) {
		if (!empty($extrafields->attributes['projet_task']['list'][$key])) {
			$arrayfields["efpt.".$key] = array('label'=>$extrafields->attributes['projet_task']['label'][$key], 'checked'=>(($extrafields->attributes['projet_task']['list'][$key] < 0) ? 0 : 1), 'position'=>$extrafields->attributes['projet_task']['pos'][$key], 'enabled'=>(abs((int) $extrafields->attributes['projet_task']['list'][$key]) != 3 && $extrafields->attributes['projet_task']['perms'][$key]));
		}
	}
}
$arrayfields = dol_sort_array($arrayfields, 'position');

$search_array_options = array();
$search_array_options_project = $extrafields->getOptionalsFromPost('projet', '', 'search_');
$search_array_options_task = $extrafields->getOptionalsFromPost('projet_task', '', 'search_task_');



/*
 * Actions
 */

$parameters = array('id' => $id, 'taskid' => $taskid, 'projectid' => $projectid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
// Purge criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$action = '';
	//$search_categ = '';
	$search_usertoprocessid = $user->id;
	$search_task_ref = '';
	$search_task_label = '';
	$search_project_ref = '';
	$search_thirdparty = '';
	$search_declared_progress = '';

	$search_array_options_project = array();
	$search_array_options_task = array();

	// We redefine $usertoprocess
	$usertoprocess = $user;
}
if (GETPOST("button_search_x", 'alpha') || GETPOST("button_search.x", 'alpha') || GETPOST("button_search", 'alpha')) {
	$action = '';
}

if (GETPOST('submitdateselect')) {
	if (GETPOST('remonth', 'int') && GETPOST('reday', 'int') && GETPOST('reyear', 'int')) {
		$daytoparse = dol_mktime(0, 0, 0, GETPOST('remonth', 'int'), GETPOST('reday', 'int'), GETPOST('reyear', 'int'));
	}

	$action = '';
}

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

if ($action == 'addtime' && $user->rights->projet->lire && GETPOST('assigntask') && GETPOST('formfilteraction') != 'listafterchangingselectedfields') {
	$action = 'assigntask';

	if ($taskid > 0) {
		$result = $object->fetch($taskid, $ref);
		if ($result < 0) {
			$error++;
		}
	} else {
		setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired", $langs->transnoentitiesnoconv("Task")), '', 'errors');
		$error++;
	}
	if (!GETPOST('type')) {
		setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), '', 'errors');
		$error++;
	}

	if (!$error) {
		$idfortaskuser = $usertoprocess->id;
		$result = $object->add_contact($idfortaskuser, GETPOST("type"), 'internal');

		if ($result >= 0 || $result == -2) {	// Contact add ok or already contact of task
			// Test if we are already contact of the project (should be rare but sometimes we can add as task contact without being contact of project, like when admin user has been removed from contact of project)
			$sql = 'SELECT ec.rowid FROM '.MAIN_DB_PREFIX.'element_contact as ec, '.MAIN_DB_PREFIX.'c_type_contact as tc WHERE tc.rowid = ec.fk_c_type_contact';
			$sql .= ' AND ec.fk_socpeople = '.((int) $idfortaskuser)." AND ec.element_id = ".((int) $object->fk_project)." AND tc.element = 'project' AND source = 'internal'";
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				if (!$obj) {	// User is not already linked to project, so we will create link to first type
					$project = new Project($db);
					$project->fetch($object->fk_project);
					// Get type
					$listofprojcontact = $project->liste_type_contact('internal');

					if (count($listofprojcontact)) {
						$typeforprojectcontact = reset(array_keys($listofprojcontact));
						$result = $project->add_contact($idfortaskuser, $typeforprojectcontact, 'internal');
					}
				}
			} else {
				dol_print_error($db);
			}
		}
	}

	if ($result < 0) {
		$error++;
		if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorTaskAlreadyAssigned"), null, 'warnings');
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if (!$error) {
		setEventMessages("TaskAssignedToEnterTime", null);
		$taskid = 0;
	}

	$action = '';
}

if ($action == 'addtime' && $user->rights->projet->lire && GETPOST('formfilteraction') != 'listafterchangingselectedfields') {
	$timetoadd = $_POST['task'];
	if (empty($timetoadd)) {
		setEventMessages($langs->trans("ErrorTimeSpentIsEmpty"), null, 'errors');
	} else {
		foreach ($timetoadd as $taskid => $value) {     // Loop on each task
			$updateoftaskdone = 0;
			foreach ($value as $key => $val) {          // Loop on each day
				$amountoadd = $timetoadd[$taskid][$key];
				if (!empty($amountoadd)) {
					$tmpduration = explode(':', $amountoadd);
					$newduration = 0;
					if (!empty($tmpduration[0])) {
						$newduration += ($tmpduration[0] * 3600);
					}
					if (!empty($tmpduration[1])) {
						$newduration += ($tmpduration[1] * 60);
					}
					if (!empty($tmpduration[2])) {
						$newduration += ($tmpduration[2]);
					}

					if ($newduration > 0) {
						$object->fetch($taskid);

						if (GETPOSTISSET($taskid.'progress')) {
							$object->progress = GETPOST($taskid.'progress', 'int');
						} else {
							unset($object->progress);
						}

						$object->timespent_duration = $newduration;
						$object->timespent_fk_user = $usertoprocess->id;
						$object->timespent_date = dol_time_plus_duree($firstdaytoshow, $key, 'd');
						$object->timespent_datehour = $object->timespent_date;
						$object->timespent_note = $object->description;

						$result = $object->addTimeSpent($user);
						if ($result < 0) {
							setEventMessages($object->error, $object->errors, 'errors');
							$error++;
							break;
						}

						$updateoftaskdone++;
					}
				}
			}

			if (!$updateoftaskdone) {  // Check to update progress if no update were done on task.
				$object->fetch($taskid);
				//var_dump($object->progress);var_dump(GETPOST($taskid . 'progress', 'int')); exit;
				if ($object->progress != GETPOST($taskid.'progress', 'int')) {
					$object->progress = GETPOST($taskid.'progress', 'int');
					$result = $object->update($user);
					if ($result < 0) {
						setEventMessages($object->error, $object->errors, 'errors');
						$error++;
						break;
					}
				}
			}
		}

		if (!$error) {
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');

			$param = '';
			$param .= ($mode ? '&mode='.urlencode($mode) : '');
			$param .= ($projectid ? 'id='.urlencode($projectid) : '');
			$param .= ($search_usertoprocessid ? '&search_usertoprocessid='.urlencode($search_usertoprocessid) : '');
			$param .= ($day ? '&day='.urlencode($day) : '').($month ? '&month='.urlencode($month) : '').($year ? '&year='.urlencode($year) : '');
			$param .= ($search_project_ref ? '&search_project_ref='.urlencode($search_project_ref) : '');
			$param .= ($search_usertoprocessid > 0 ? '&search_usertoprocessid='.urlencode($search_usertoprocessid) : '');
			$param .= ($search_thirdparty ? '&search_thirdparty='.urlencode($search_thirdparty) : '');
			$param .= ($search_declared_progress ? '&search_declared_progress='.urlencode($search_declared_progress) : '');
			$param .= ($search_task_ref ? '&search_task_ref='.urlencode($search_task_ref) : '');
			$param .= ($search_task_label ? '&search_task_label='.urlencode($search_task_label) : '');

			/*$search_array_options=$search_array_options_project;
			 $search_options_pattern='search_options_';
			 include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';
			 */

			$search_array_options = $search_array_options_task;
			$search_options_pattern = 'search_task_options_';
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

			// Redirect to avoid submit twice on back
			header('Location: '.$_SERVER["PHP_SELF"].'?'.$param);
			exit;
		}
	}
}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formcompany = new FormCompany($db);
$formproject = new FormProjets($db);
$projectstatic = new Project($db);
$project = new Project($db);
$taskstatic = new Task($db);
$thirdpartystatic = new Societe($db);
$holiday = new Holiday($db);

$title = $langs->trans("TimeSpent");

$projectsListId = $projectstatic->getProjectsAuthorizedForUser($usertoprocess, (empty($usertoprocess->id) ? 2 : 0), 1); // Return all project i have permission on (assigned to me+public). I want my tasks and some of my task may be on a public projet that is not my project
//var_dump($projectsListId);
if ($id) {
	$project->fetch($id);
	$project->fetch_thirdparty();
}

$onlyopenedproject = 1; // or -1
$morewherefilter = '';

if ($search_project_ref) {
	$morewherefilter .= natural_search(array("p.ref", "p.title"), $search_project_ref);
}
if ($search_task_ref) {
	$morewherefilter .= natural_search("t.ref", $search_task_ref);
}
if ($search_task_label) {
	$morewherefilter .= natural_search(array("t.ref", "t.label"), $search_task_label);
}
if ($search_thirdparty) {
	$morewherefilter .= natural_search("s.nom", $search_thirdparty);
}
if ($search_declared_progress) {
	$morewherefilter .= natural_search("t.progress", $search_declared_progress, 1);
}

$sql = &$morewherefilter;

/*$search_array_options = $search_array_options_project;
 $extrafieldsobjectprefix='efp.';
 $search_options_pattern='search_options_';
 $extrafieldsobjectkey='projet';
 include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
 */
$search_array_options = $search_array_options_task;
$extrafieldsobjectprefix = 'efpt.';
$search_options_pattern = 'search_task_options_';
$extrafieldsobjectkey = 'projet_task';
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';

$tasksarray = $taskstatic->getTasksArray(0, 0, ($project->id ? $project->id : 0), $socid, 0, $search_project_ref, $onlyopenedproject, $morewherefilter, ($search_usertoprocessid ? $search_usertoprocessid : 0), 0, $extrafields); // We want to see all tasks of open project i am allowed to see and that match filter, not only my tasks. Later only mine will be editable later.
if ($morewherefilter) {	// Get all task without any filter, so we can show total of time spent for not visible tasks
	$tasksarraywithoutfilter = $taskstatic->getTasksArray(0, 0, ($project->id ? $project->id : 0), $socid, 0, '', $onlyopenedproject, '', ($search_usertoprocessid ? $search_usertoprocessid : 0)); // We want to see all tasks of open project i am allowed to see and that match filter, not only my tasks. Later only mine will be editable later.
}
$projectsrole = $taskstatic->getUserRolesForProjectsOrTasks($usertoprocess, 0, ($project->id ? $project->id : 0), 0, $onlyopenedproject);
$tasksrole = $taskstatic->getUserRolesForProjectsOrTasks(0, $usertoprocess, ($project->id ? $project->id : 0), 0, $onlyopenedproject);
//var_dump($tasksarray);
//var_dump($projectsrole);
//var_dump($taskrole);


llxHeader("", $title, "", '', '', '', array('/core/js/timesheet.js'));

//print_barre_liste($title, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $num, '', 'project');

$param = '';
$param .= ($mode ? '&mode='.urlencode($mode) : '');
$param .= ($search_project_ref ? '&search_project_ref='.urlencode($search_project_ref) : '');
$param .= ($search_usertoprocessid > 0 ? '&search_usertoprocessid='.urlencode($search_usertoprocessid) : '');
$param .= ($search_thirdparty ? '&search_thirdparty='.urlencode($search_thirdparty) : '');
$param .= ($search_task_ref ? '&search_task_ref='.urlencode($search_task_ref) : '');
$param .= ($search_task_label ? '&search_task_label='.urlencode($search_task_label) : '');

$search_array_options = $search_array_options_project;
$search_options_pattern = 'search_options_';
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

$search_array_options = $search_array_options_task;
$search_options_pattern = 'search_task_options_';
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// Show navigation bar
$nav = '<a class="inline-block valignmiddle" href="?year='.$prev_year."&month=".$prev_month."&day=".$prev_day.$param.'">'.img_previous($langs->trans("Previous"))."</a>\n";
$nav .= ' <span id="month_name">'.dol_print_date(dol_mktime(0, 0, 0, $first_month, $first_day, $first_year), "%Y").", ".$langs->trans("WeekShort")." ".$week." </span>\n";
$nav .= '<a class="inline-block valignmiddle" href="?year='.$next_year."&month=".$next_month."&day=".$next_day.$param.'">'.img_next($langs->trans("Next"))."</a>\n";
$nav .= ' '.$form->selectDate(-1, '', 0, 0, 2, "addtime", 1, 1).' ';
$nav .= ' <button type="submit" name="submitdateselect" value="x" class="bordertransp"><span class="fa fa-search"></span></button>';

$picto = 'clock';

print '<form name="addtime" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="addtime">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';
print '<input type="hidden" name="day" value="'.$day.'">';
print '<input type="hidden" name="month" value="'.$month.'">';
print '<input type="hidden" name="year" value="'.$year.'">';

$head = project_timesheet_prepare_head($mode, $usertoprocess);
print dol_get_fiche_head($head, 'inputperweek', $langs->trans('TimeSpent'), -1, $picto);

// Show description of content
print '<div class="hideonsmartphone opacitymedium">';
if ($mine || ($usertoprocess->id == $user->id)) {
	print $langs->trans("MyTasksDesc").'.'.($onlyopenedproject ? ' '.$langs->trans("OnlyOpenedProject") : '').'<br>';
} else {
	if (empty($usertoprocess->id) || $usertoprocess->id < 0) {
		if ($user->rights->projet->all->lire && !$socid) {
			print $langs->trans("ProjectsDesc").'.'.($onlyopenedproject ? ' '.$langs->trans("OnlyOpenedProject") : '').'<br>';
		} else {
			print $langs->trans("ProjectsPublicTaskDesc").'.'.($onlyopenedproject ? ' '.$langs->trans("OnlyOpenedProject") : '').'<br>';
		}
	}
}
if ($mine || ($usertoprocess->id == $user->id)) {
	print $langs->trans("OnlyYourTaskAreVisible").'<br>';
} else {
	print $langs->trans("AllTaskVisibleButEditIfYouAreAssigned").'<br>';
}
print '</div>';

print dol_get_fiche_end();

print '<div class="floatright right'.($conf->dol_optimize_smallscreen ? ' centpercent' : '').'">'.$nav.'</div>'; // We move this before the assign to components so, the default submit button is not the assign to.

print '<div class="colorbacktimesheet float valignmiddle">';
$titleassigntask = $langs->transnoentities("AssignTaskToMe");
if ($usertoprocess->id != $user->id) {
	$titleassigntask = $langs->transnoentities("AssignTaskToUser", $usertoprocess->getFullName($langs));
}
print '<div class="taskiddiv inline-block">';
print img_picto('', 'projecttask', 'class="pictofixedwidth"');
$formproject->selectTasks($socid ? $socid : -1, $taskid, 'taskid', 32, 0, '-- '.$langs->trans("ChooseANotYetAssignedTask").' --', 1, 0, 0, '', '', 'all', $usertoprocess);
print '</div>';
print ' ';
print $formcompany->selectTypeContact($object, '', 'type', 'internal', 'rowid', 0, 'maxwidth150onsmartphone');
print '<input type="submit" class="button valignmiddle smallonsmartphone" name="assigntask" value="'.dol_escape_htmltag($titleassigntask).'">';
print '</div>';

print '<div class="clearboth" style="padding-bottom: 20px;"></div>';


$startday = dol_mktime(12, 0, 0, $startdayarray['first_month'], $startdayarray['first_day'], $startdayarray['first_year']);

$numendworkingday = 0;
$numstartworkingday = 0;
// Get if user is available or not for each day
$isavailable = array();

// Assume from Monday to Friday if conf empty or badly formed
$numstartworkingday = 1;
$numendworkingday = 5;

if (!empty($conf->global->MAIN_DEFAULT_WORKING_DAYS)) {
	$tmparray = explode('-', $conf->global->MAIN_DEFAULT_WORKING_DAYS);
	if (count($tmparray) >= 2) {
		$numstartworkingday = $tmparray[0];
		$numendworkingday = $tmparray[1];
	}
}

for ($idw = 0; $idw < 7; $idw++) {
	$dayinloopfromfirstdaytoshow = dol_time_plus_duree($firstdaytoshow, $idw, 'd'); // $firstdaytoshow is a date with hours = 0
	$dayinloopfromfirstdaytoshowgmt = dol_time_plus_duree($firstdaytoshowgmt, $idw, 'd'); // $firstdaytoshow is a date with hours = 0

	$statusofholidaytocheck = Holiday::STATUS_APPROVED;

	$isavailablefordayanduser = $holiday->verifDateHolidayForTimestamp($usertoprocess->id, $dayinloopfromfirstdaytoshow, $statusofholidaytocheck);
	$isavailable[$dayinloopfromfirstdaytoshow] = $isavailablefordayanduser; // in projectLinesPerWeek later, we are using $firstdaytoshow and dol_time_plus_duree to loop on each day

	$test = num_public_holiday($dayinloopfromfirstdaytoshowgmt, $dayinloopfromfirstdaytoshowgmt + 86400, $mysoc->country_code);
	if ($test) {
		$isavailable[$dayinloopfromfirstdaytoshow] = array('morning'=>false, 'afternoon'=>false, 'morning_reason'=>'public_holiday', 'afternoon_reason'=>'public_holiday');
	}
}



$moreforfilter = '';

// Filter on categories
/*
 if (! empty($conf->categorie->enabled))
 {
 require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
 $moreforfilter.='<div class="divsearchfield">';
 $moreforfilter.=$langs->trans('ProjectCategories'). ': ';
 $moreforfilter.=$formother->select_categories('project', $search_categ, 'search_categ', 1, 1, 'maxwidth300');
 $moreforfilter.='</div>';
 }*/

// If the user can view user other than himself
$moreforfilter .= '<div class="divsearchfield">';
$moreforfilter .= '<div class="inline-block hideonsmartphone"></div>';
$includeonly = 'hierarchyme';
if (empty($user->rights->user->user->lire)) {
	$includeonly = array($user->id);
}
$moreforfilter .= img_picto($langs->trans('Filter').' '.$langs->trans('User'), 'user', 'class="paddingright pictofixedwidth"').$form->select_dolusers($search_usertoprocessid ? $search_usertoprocessid : $usertoprocess->id, 'search_usertoprocessid', $user->rights->user->user->lire ? 0 : 0, null, 0, $includeonly, null, 0, 0, 0, '', 0, '', 'maxwidth200');
$moreforfilter .= '</div>';

if (empty($conf->global->PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT)) {
	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= '<div class="inline-block"></div>';
	$moreforfilter .= img_picto($langs->trans('Filter').' '.$langs->trans('Project'), 'project', 'class="paddingright pictofixedwidth"').'<input type="text" name="search_project_ref" class="maxwidth100" value="'.dol_escape_htmltag($search_project_ref).'">';
	$moreforfilter .= '</div>';

	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= '<div class="inline-block"></div>';
	$moreforfilter .= img_picto($langs->trans('Filter').' '.$langs->trans('ThirdParty'), 'company', 'class="paddingright pictofixedwidth"').'<input type="text" name="search_thirdparty" class="maxwidth100" value="'.dol_escape_htmltag($search_thirdparty).'">';
	$moreforfilter .= '</div>';
}

if (!empty($moreforfilter)) {
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	print '</div>';
}


$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;

$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

// This must be after the $selectedfields
$addcolspan = 0;
if (!empty($arrayfields['t.planned_workload']['checked'])) {
	$addcolspan++;
}
if (!empty($arrayfields['t.progress']['checked'])) {
	$addcolspan++;
}
foreach ($arrayfields as $key => $val) {
	if ($val['checked'] && substr($key, 0, 5) == 'efpt.') {
		$addcolspan++;
	}
}

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'" id="tablelines3">'."\n";

print '<tr class="liste_titre_filter">';
if (!empty($conf->global->PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT)) {
	print '<td class="liste_titre"><input type="text" size="4" name="search_project_ref" value="'.dol_escape_htmltag($search_project_ref).'"></td>';
}
if (!empty($conf->global->PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT)) {
	print '<td class="liste_titre"><input type="text" size="4" name="search_thirdparty" value="'.dol_escape_htmltag($search_thirdparty).'"></td>';
}
print '<td class="liste_titre"><input type="text" size="4" name="search_task_label" value="'.dol_escape_htmltag($search_task_label).'"></td>';
// TASK fields
$search_options_pattern = 'search_task_options_';
$extrafieldsobjectkey = 'projet_task';
$extrafieldsobjectprefix = 'efpt.';
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
if (!empty($arrayfields['t.planned_workload']['checked'])) {
	print '<td class="liste_titre"></td>';
}
if (!empty($arrayfields['t.progress']['checked'])) {
	print '<td class="liste_titre right"><input type="text" size="4" name="search_declared_progress" value="'.dol_escape_htmltag($search_declared_progress).'"></td>';
}
if (!empty($arrayfields['timeconsumed']['checked'])) {
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre"></td>';
}
for ($idw = 0; $idw < 7; $idw++) {
	print '<td class="liste_titre"></td>';
}
// Action column
print '<td class="liste_titre nowrap right">';
$searchpicto = $form->showFilterAndCheckAddButtons(0);
print $searchpicto;
print '</td>';
print "</tr>\n";

print '<tr class="liste_titre">';
if (!empty($conf->global->PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT)) {
	print '<th>'.$langs->trans("Project").'</th>';
}
if (!empty($conf->global->PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT)) {
	print '<th>'.$langs->trans("ThirdParty").'</th>';
}
print '<th>'.$langs->trans("Task").'</th>';
// TASK fields
$extrafieldsobjectkey = 'projet_task';
$extrafieldsobjectprefix = 'efpt.';
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
if (!empty($arrayfields['t.planned_workload']['checked'])) {
	print '<th class="leftborder plannedworkload minwidth75 maxwidth100 right" title="'.dol_escape_htmltag($langs->trans("PlannedWorkload")).'">'.$langs->trans("PlannedWorkload").'</th>';
}
if (!empty($arrayfields['t.progress']['checked'])) {
	print '<th class="right minwidth75 maxwidth100" title="'.dol_escape_htmltag($langs->trans("ProgressDeclared")).'">'.$langs->trans("ProgressDeclared").'</th>';
}
if (!empty($arrayfields['timeconsumed']['checked'])) {
	print '<th class="right maxwidth100">'.$langs->trans("TimeSpent").'<br>';
	print '<span class="nowraponall">';
	print '<span class="opacitymedium nopadding userimg"><img alt="Photo" class="photouserphoto userphoto" src="'.DOL_URL_ROOT.'/theme/common/everybody.png"></span>';
	print '<span class="opacitymedium paddingleft">'.$langs->trans("Everybody").'</span>';
	print '</span>';
	print '</th>';
	print '<th class="right maxwidth75 maxwidth100">'.$langs->trans("TimeSpent").($usertoprocess->firstname ? '<br><span class="nowraponall">'.$usertoprocess->getNomUrl(-2).'<span class="opacitymedium paddingleft">'.dol_trunc($usertoprocess->firstname, 10).'</span></span>' : '').'</th>';
}
for ($idw = 0; $idw < 7; $idw++) {
	$dayinloopfromfirstdaytoshow = dol_time_plus_duree($firstdaytoshow, $idw, 'd'); // $firstdaytoshow is a date with hours = 0

	$cssweekend = '';
	if ((($idw + 1) < $numstartworkingday) || (($idw + 1) > $numendworkingday)) {	// This is a day is not inside the setup of working days, so we use a week-end css.
		$cssweekend = 'weekend';
	}

	$tmpday = dol_time_plus_duree($firstdaytoshow, $idw, 'd');

	$cssonholiday = '';
	if (!$isavailable[$tmpday]['morning'] && !$isavailable[$tmpday]['afternoon']) {
		$cssonholiday .= 'onholidayallday ';
	} elseif (!$isavailable[$tmpday]['morning']) {
		$cssonholiday .= 'onholidaymorning ';
	} elseif (!$isavailable[$tmpday]['afternoon']) {
		$cssonholiday .= 'onholidayafternoon ';
	}

	print '<th width="6%" class="center bold hide'.$idw.($cssonholiday ? ' '.$cssonholiday : '').($cssweekend ? ' '.$cssweekend : '').'">';
	print dol_print_date($dayinloopfromfirstdaytoshow, '%a');
	print '<br>'.dol_print_date($dayinloopfromfirstdaytoshow, 'dayreduceformat').'</th>';
}
//print '<td></td>';
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');


print "</tr>\n";

$colspan = 1 + (empty($conf->global->PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT) ? 0 : 2);

if ($conf->use_javascript_ajax) {
	print '<tr class="liste_total">';
	print '<td class="liste_total" colspan="'.($colspan + $addcolspan).'">';
	print $langs->trans("Total");
	print '<span class="opacitymediumbycolor">  - '.$langs->trans("ExpectedWorkedHours").': <strong>'.price($usertoprocess->weeklyhours, 1, $langs, 0, 0).'</strong></span>';
	print '</td>';
	if (!empty($arrayfields['timeconsumed']['checked'])) {
		print '<td class="liste_total"></td>';
		print '<td class="liste_total"></td>';
	}
	for ($idw = 0; $idw < 7; $idw++) {
		$cssweekend = '';
		if ((($idw + 1) < $numstartworkingday) || (($idw + 1) > $numendworkingday)) {	// This is a day is not inside the setup of working days, so we use a week-end css.
			$cssweekend = 'weekend';
		}

		$tmpday = dol_time_plus_duree($firstdaytoshow, $idw, 'd');

		$cssonholiday = '';
		if (!$isavailable[$tmpday]['morning'] && !$isavailable[$tmpday]['afternoon']) {
			$cssonholiday .= 'onholidayallday ';
		} elseif (!$isavailable[$tmpday]['morning']) {
			$cssonholiday .= 'onholidaymorning ';
		} elseif (!$isavailable[$tmpday]['afternoon']) {
			$cssonholiday .= 'onholidayafternoon ';
		}
		print '<td class="liste_total hide'.$idw.($cssonholiday ? ' '.$cssonholiday : '').($cssweekend ? ' '.$cssweekend : '').'" align="center"><div class="totalDay'.$idw.'">&nbsp;</div></td>';
	}
	print '<td class="liste_total center"><div class="totalDayAll">&nbsp;</div></td>';
	print '</tr>';
}



// By default, we can edit only tasks we are assigned to
$restrictviewformytask = ((!isset($conf->global->PROJECT_TIME_SHOW_TASK_NOT_ASSIGNED)) ? 2 : $conf->global->PROJECT_TIME_SHOW_TASK_NOT_ASSIGNED);
if (count($tasksarray) > 0) {
	//var_dump($tasksarray);				// contains only selected tasks
	//var_dump($tasksarraywithoutfilter);	// contains all tasks (if there is a filter, not defined if no filter)
	//var_dump($tasksrole);

	$j = 0;
	$level = 0;
	$totalforvisibletasks = projectLinesPerWeek($j, $firstdaytoshow, $usertoprocess, 0, $tasksarray, $level, $projectsrole, $tasksrole, $mine, $restrictviewformytask, $isavailable, 0, $arrayfields, $extrafields);
	//var_dump($totalforvisibletasks);

	// Show total for all other tasks

	// Calculate total for all tasks
	$listofdistinctprojectid = array(); // List of all distinct projects
	if (is_array($tasksarraywithoutfilter) && count($tasksarraywithoutfilter)) {
		foreach ($tasksarraywithoutfilter as $tmptask) {
			$listofdistinctprojectid[$tmptask->fk_project] = $tmptask->fk_project;
		}
	}
	//var_dump($listofdistinctprojectid);
	$totalforeachday = array();
	foreach ($listofdistinctprojectid as $tmpprojectid) {
		$projectstatic->id = $tmpprojectid;
		$projectstatic->loadTimeSpent($firstdaytoshow, 0, $usertoprocess->id); // Load time spent from table projet_task_time for the project into this->weekWorkLoad and this->weekWorkLoadPerTask for all days of a week
		for ($idw = 0; $idw < 7; $idw++) {
			$tmpday = dol_time_plus_duree($firstdaytoshow, $idw, 'd');
			$totalforeachday[$tmpday] += $projectstatic->weekWorkLoad[$tmpday];
		}
	}

	//var_dump($totalforeachday);
	//var_dump($totalforvisibletasks);

	// Is there a diff between selected/filtered tasks and all tasks ?
	$isdiff = 0;
	if (count($totalforeachday)) {
		for ($idw = 0; $idw < 7; $idw++) {
			$tmpday = dol_time_plus_duree($firstdaytoshow, $idw, 'd');
			$timeonothertasks = ($totalforeachday[$tmpday] - $totalforvisibletasks[$tmpday]);
			if ($timeonothertasks) {
				$isdiff = 1;
				break;
			}
		}
	}

	// There is a diff between total shown on screen and total spent by user, so we add a line with all other cumulated time of user
	if ($isdiff) {
		print '<tr class="oddeven othertaskwithtime">';
		print '<td colspan="'.($colspan + $addcolspan).'" class="opacitymedium">';
		print $langs->trans("OtherFilteredTasks");
		print '</td>';
		if (!empty($arrayfields['timeconsumed']['checked'])) {
			print '<td class="liste_total"></td>';
			print '<td class="liste_total"></td>';
		}
		for ($idw = 0; $idw < 7; $idw++) {
			$cssweekend = '';
			if ((($idw + 1) < $numstartworkingday) || (($idw + 1) > $numendworkingday)) {	// This is a day is not inside the setup of working days, so we use a week-end css.
				$cssweekend = 'weekend';
			}

			print '<td class="center hide'.$idw.' '.($cssweekend ? ' '.$cssweekend : '').'">';
			$tmpday = dol_time_plus_duree($firstdaytoshow, $idw, 'd');
			$timeonothertasks = ($totalforeachday[$tmpday] - $totalforvisibletasks[$tmpday]);
			if ($timeonothertasks) {
				print '<span class="timesheetalreadyrecorded" title="texttoreplace"><input type="text" class="center smallpadd" size="2" disabled="" id="timespent[-1]['.$idw.']" name="task[-1]['.$idw.']" value="';
				print convertSecondToTime($timeonothertasks, 'allhourmin');
				print '"></span>';
			}
			print '</td>';
		}
		print ' <td class="liste_total"></td>';
		print '</tr>';
	}

	if ($conf->use_javascript_ajax) {
		print '<tr class="liste_total">';
		print '<td class="liste_total" colspan="'.($colspan + $addcolspan).'">';
		print $langs->trans("Total");
		print '<span class="opacitymediumbycolor">  - '.$langs->trans("ExpectedWorkedHours").': <strong>'.price($usertoprocess->weeklyhours, 1, $langs, 0, 0).'</strong></span>';
		print '</td>';
		if (!empty($arrayfields['timeconsumed']['checked'])) {
			print '<td class="liste_total"></td>';
			print '<td class="liste_total"></td>';
		}

		for ($idw = 0; $idw < 7; $idw++) {
			$cssweekend = '';
			if ((($idw + 1) < $numstartworkingday) || (($idw + 1) > $numendworkingday)) {	// This is a day is not inside the setup of working days, so we use a week-end css.
				$cssweekend = 'weekend';
			}

			$tmpday = dol_time_plus_duree($firstdaytoshow, $idw, 'd');

			$cssonholiday = '';
			if (!$isavailable[$tmpday]['morning'] && !$isavailable[$tmpday]['afternoon']) {
				$cssonholiday .= 'onholidayallday ';
			} elseif (!$isavailable[$tmpday]['morning']) {
				$cssonholiday .= 'onholidaymorning ';
			} elseif (!$isavailable[$tmpday]['afternoon']) {
				$cssonholiday .= 'onholidayafternoon ';
			}

			print '<td class="liste_total hide'.$idw.($cssonholiday ? ' '.$cssonholiday : '').($cssweekend ? ' '.$cssweekend : '').'" align="center"><div class="totalDay'.$idw.'">&nbsp;</div></td>';
		}
		print '<td class="liste_total center"><div class="totalDayAll">&nbsp;</div></td>';
		print '</tr>';
	}
} else {
	print '<tr><td colspan="15"><span class="opacitymedium">'.$langs->trans("NoAssignedTasks").'</span></td></tr>';
}
print "</table>";
print '</div>';

print '<input type="hidden" id="numberOfLines" name="numberOfLines" value="'.count($tasksarray).'"/>'."\n";

print $form->buttonsSaveCancel("Save", '');

print '</form>'."\n\n";

$modeinput = 'hours';

if ($conf->use_javascript_ajax) {
	print "\n<!-- JS CODE TO ENABLE Tooltips on all object with class classfortooltip -->\n";
	print '<script type="text/javascript">'."\n";
	print "jQuery(document).ready(function () {\n";
	print '		jQuery(".timesheetalreadyrecorded").tooltip({
					show: { collision: "flipfit", effect:\'toggle\', delay:50 },
					hide: { effect:\'toggle\', delay: 50 },
					tooltipClass: "mytooltip",
					content: function () {
						return \''.dol_escape_js($langs->trans("TimeAlreadyRecorded", $usertoprocess->getFullName($langs))).'\';
					}
				});'."\n";

	$idw = 0;
	while ($idw < 7) {
		print '    updateTotal('.$idw.',\''.$modeinput.'\');';
		$idw++;
	}
	print "\n});\n";
	print '</script>';
}

// End of page
llxFooter();
$db->close();
