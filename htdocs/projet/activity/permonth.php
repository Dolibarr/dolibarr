<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010      François Legastelois <flegastelois@teclib.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *	\file       htdocs/projet/activity/permonth.php
 *	\ingroup    projet
 *	\brief      List activities of tasks (per month entry)
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
$id = GETPOSTINT('id');
$taskid = GETPOSTINT('taskid');

$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'timespent';

$mine = 0;
if ($mode == 'mine') {
	$mine = 1;
}

$projectid = GETPOSTISSET("id") ? GETPOSTINT("id", 1) : GETPOSTINT("projectid");

$hookmanager->initHooks(array('timesheetpermonthcard'));

// Security check
$socid = 0;
// For external user, no check is done on company because readability is managed by public status of project and assignment.
// if ($user->socid > 0) $socid=$user->socid;
$result = restrictedArea($user, 'projet', $projectid);

$now = dol_now();

$year = GETPOSTINT('reyear') ? GETPOSTINT('reyear') : (GETPOSTINT("year") ? GETPOSTINT("year") : date("Y"));
$month = GETPOSTINT('remonth') ? GETPOSTINT('remonth') : (GETPOSTINT("month") ? GETPOSTINT("month") : date("m"));
$day = GETPOSTINT('reday') ? GETPOSTINT('reday') : (GETPOSTINT("day") ? GETPOSTINT("day") : date("d"));
$week = GETPOSTINT("week") ? GETPOSTINT("week") : date("W");

$day = (int) $day;

//$search_categ = GETPOST("search_categ", 'alpha');
$search_usertoprocessid = GETPOSTINT('search_usertoprocessid');
$search_task_ref = GETPOST('search_task_ref', 'alpha');
$search_task_label = GETPOST('search_task_label', 'alpha');
$search_project_ref = GETPOST('search_project_ref', 'alpha');
$search_thirdparty = GETPOST('search_thirdparty', 'alpha');
$search_declared_progress = GETPOST('search_declared_progress', 'alpha');

$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');

$startdayarray = dol_get_prev_month($month, $year);

$prev = $startdayarray;
$prev_year  = $prev['year'];
$prev_month = $prev['month'];
$prev_day   = 1;

$next = dol_get_next_month($month, $year);
$next_year  = $next['year'];
$next_month = $next['month'];
$next_day   = 1;
$TWeek = getWeekNumbersOfMonth($month, $year);
$firstdaytoshow = dol_mktime(0, 0, 0, $month, 1, $year);
$TFirstDays = getFirstDayOfEachWeek($TWeek, $year);
$TFirstDays[reset($TWeek)] = '01'; //first day of month
$TLastDays = getLastDayOfEachWeek($TWeek, $year);
$TLastDays[end($TWeek)] = date("t", strtotime($year.'-'.$month.'-'.$day)); //last day of month
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
$arrayfields['t.planned_workload'] = array('label' => 'PlannedWorkload', 'checked' => 1, 'enabled' => 1, 'position' => 5);
$arrayfields['t.progress'] = array('label' => 'ProgressDeclared', 'checked' => 1, 'enabled' => 1, 'position' => 10);
$arrayfields['timeconsumed'] = array('label' => 'TimeConsumed', 'checked' => 1, 'enabled' => 1, 'position' => 15);
/*foreach($object->fields as $key => $val)
 {
 // If $val['visible']==0, then we never show the field
 if (!empty($val['visible'])) $arrayfields['t.'.$key]=array('label'=>$val['label'], 'checked'=>(($val['visible']<0)?0:1), 'enabled'=>$val['enabled'], 'position'=>$val['position']);
 }*/
// Definition of fields for list
// Extra fields
if (!empty($extrafields->attributes['projet_task']['label']) && is_array($extrafields->attributes['projet_task']['label']) && count($extrafields->attributes['projet_task']['label']) > 0) {
	foreach ($extrafields->attributes['projet_task']['label'] as $key => $val) {
		if (!empty($extrafields->attributes['projet_task']['list'][$key])) {
			$arrayfields["efpt.".$key] = array('label' => $extrafields->attributes['projet_task']['label'][$key], 'checked' => (($extrafields->attributes['projet_task']['list'][$key] < 0) ? 0 : 1), 'position' => $extrafields->attributes['projet_task']['pos'][$key], 'enabled' => (abs((int) $extrafields->attributes['projet_task']['list'][$key]) != 3 && $extrafields->attributes['projet_task']['perms'][$key]));
		}
	}
}
$arrayfields = dol_sort_array($arrayfields, 'position');

$search_array_options = array();
$search_array_options_project = $extrafields->getOptionalsFromPost('projet', '', 'search_');
$search_array_options_task = $extrafields->getOptionalsFromPost('projet_task', '', 'search_task_');

$error = 0;


/*
 * Actions
 */

$parameters = array('id' => $id, 'taskid' => $taskid, 'projectid' => $projectid, 'TWeek' => $TWeek, 'TFirstDays' => $TFirstDays, 'TLastDays' => $TLastDays);
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
	if (GETPOSTINT('remonth') && GETPOSTINT('reday') && GETPOSTINT('reyear')) {
		$daytoparse = dol_mktime(0, 0, 0, GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'));
	}

	$action = '';
}

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

if ($action == 'addtime' && $user->hasRight('projet', 'lire') && GETPOST('assigntask') && GETPOST('formfilteraction') != 'listafterchangingselectedfields') {
	$action = 'assigntask';

	if ($taskid > 0) {
		$result = $object->fetch($taskid, $ref);
		if ($result < 0) {
			$error++;
		}
	} else {
		setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired", $langs->transnoentitiesnoconv("Task")), null, 'errors');
		$error++;
	}
	if (!GETPOST('type')) {
		setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
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
						$tmparray = array_keys($listofprojcontact);
						$typeforprojectcontact = reset($tmparray);
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

if ($action == 'addtime' && $user->hasRight('projet', 'lire') && GETPOST('formfilteraction') != 'listafterchangingselectedfields') {
	$timetoadd = GETPOST('task');
	if (empty($timetoadd)) {
		setEventMessages($langs->trans("ErrorTimeSpentIsEmpty"), null, 'errors');
	} else {
		foreach ($timetoadd as $tmptaskid => $tmpvalue) {     // Loop on each task
			$updateoftaskdone = 0;
			foreach ($tmpvalue as $key => $val) {          // Loop on each day
				$amountoadd = $timetoadd[$tmptaskid][$key];
				if (!empty($amountoadd)) {
					$tmpduration = explode(':', $amountoadd);
					$newduration = 0;
					if (!empty($tmpduration[0])) {
						$newduration += (int) ((float) $tmpduration[0] * 3600);
					}
					if (!empty($tmpduration[1])) {
						$newduration += (int) ((float) $tmpduration[1] * 60);
					}
					if (!empty($tmpduration[2])) {
						$newduration += ((int) $tmpduration[2]);
					}

					if ($newduration > 0) {
						$object->fetch($tmptaskid);

						if (GETPOSTISSET($tmptaskid.'progress')) {
							$object->progress = GETPOSTINT($tmptaskid.'progress');
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
				$object->fetch($tmptaskid);
				//var_dump($object->progress);
				//var_dump(GETPOST($tmptaskid . 'progress', 'int')); exit;
				if ($object->progress != GETPOSTINT($tmptaskid.'progress')) {
					$object->progress = GETPOSTINT($tmptaskid.'progress');
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
			$param .= ($projectid ? 'id='.urlencode((string) ($projectid)) : '');
			$param .= ($search_usertoprocessid ? '&search_usertoprocessid='.urlencode($search_usertoprocessid) : '');
			$param .= ($day ? '&day='.urlencode((string) ($day)) : '').($month ? '&month='.urlencode((string) ($month)) : '').($year ? '&year='.urlencode((string) ($year)) : '');
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
$projectsrole = $taskstatic->getUserRolesForProjectsOrTasks($usertoprocess, null, ($project->id ? $project->id : 0), 0, $onlyopenedproject);
$tasksrole = $taskstatic->getUserRolesForProjectsOrTasks(null, $usertoprocess, ($project->id ? $project->id : 0), 0, $onlyopenedproject);
//var_dump($tasksarray);
//var_dump($projectsrole);
//var_dump($taskrole);


llxHeader('', $title, '', '', 0, 0, array('/core/js/timesheet.js'), '', '', 'mod-project project-activity page-activity_permonth');

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
$nav .= ' <span id="month_name">'.dol_print_date(dol_mktime(0, 0, 0, $month, 1, $year), "%Y").", ".$langs->trans(date('F', mktime(0, 0, 0, $month, 10)))." </span>\n";
$nav .= '<a class="inline-block valignmiddle" href="?year='.$next_year."&month=".$next_month."&day=".$next_day.$param.'">'.img_next($langs->trans("Next"))."</a>\n";
$nav .= ' '.$form->selectDate(-1, '', 0, 0, 2, "addtime", 1, ($conf->dol_optimize_smallscreen ? 0 : 1)).' ';
$nav .= ' <button type="submit" name="submitdateselect" value="x" class="bordertransp nobordertransp button_search_x"><span class="fa fa-search"></span></button>';

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
print dol_get_fiche_head($head, 'inputpermonth', $langs->trans('TimeSpent'), -1, $picto);

// Show description of content
print '<div class="hideonsmartphone opacitymedium">';
if ($mine || ($usertoprocess->id == $user->id)) {
	print $langs->trans("MyTasksDesc").'.'.($onlyopenedproject ? ' '.$langs->trans("OnlyOpenedProject") : '').'<br>';
} else {
	if (empty($usertoprocess->id) || $usertoprocess->id < 0) {
		if ($user->hasRight('projet', 'all', 'lire') && !$socid) {
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

print '<div class="'.($conf->dol_optimize_smallscreen ? 'center centpercent' : 'floatright right').'">'.$nav.'</div>'; // We move this before the assign to components so, the default submit button is not the assign to.

print '<div class="colorbacktimesheet valignmiddle'.($conf->dol_optimize_smallscreen ? ' center' : ' float').'">';
$titleassigntask = $langs->transnoentities("AssignTaskToMe");
if ($usertoprocess->id != $user->id) {
	$titleassigntask = $langs->transnoentities("AssignTaskToUser", $usertoprocess->getFullName($langs));
}
print '<div class="taskiddiv inline-block">';
print img_picto('', 'projecttask', 'class="pictofixedwidth"');
$formproject->selectTasks($socid ? $socid : -1, $taskid, 'taskid', 32, 0, '-- '.$langs->trans("ChooseANotYetAssignedTask").' --', 1, 0, 0, 'widthcentpercentminusx', '', 'all', $usertoprocess);
print '</div>';
print ' ';
print $formcompany->selectTypeContact($object, '', 'type', 'internal', 'position', 0, 'maxwidth150onsmartphone');
print '<input type="submit" class="button valignmiddle smallonsmartphone small" name="assigntask" value="'.dol_escape_htmltag($titleassigntask).'">';
print '</div>';

print '<div class="clearboth" style="padding-bottom: 20px;"></div>';


$moreforfilter = '';

// Filter on categories
/*
if (isModEnabled("categorie")) {
	require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
	$moreforfilter.='<div class="divsearchfield">';
	$moreforfilter.=$langs->trans('ProjectCategories'). ': ';
	$moreforfilter.=$formother->select_categories('project', $search_categ, 'search_categ', 1, 1, 'maxwidth300');
	$moreforfilter.='</div>';
}*/

// If the user can view user other than himself
$includeonly = 'hierarchyme';
if (!$user->hasRight('user', 'user', 'lire')) {
	$includeonly = array($user->id);
}
$selecteduser = $search_usertoprocessid ? $search_usertoprocessid : $usertoprocess->id;
$moreforfiltertmp = $form->select_dolusers($selecteduser, 'search_usertoprocessid', 0, null, 0, $includeonly, null, 0, 0, 0, '', 0, '', 'maxwidth200');
if ($form->num > 1 || empty($conf->dol_optimize_smallscreen)) {
	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= '<div class="inline-block hideonsmartphone"></div>';
	$moreforfilter .= img_picto($langs->trans('Filter').' '.$langs->trans('User'), 'user', 'class="paddingright pictofixedwidth"');
	$moreforfilter .= $moreforfiltertmp;
	$moreforfilter .= '</div>';
} else {
	$moreforfilter .= '<input type="hidden" name="search_usertoprocessid" value="'.$selecteduser.'">';
}

if (!getDolGlobalString('PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT')) {
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
if (getDolGlobalString('PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT')) {
	print '<td class="liste_titre"><input type="text" size="4" name="search_project_ref" value="'.dol_escape_htmltag($search_project_ref).'"></td>';
}
if (getDolGlobalString('PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT')) {
	print '<td class="liste_titre"><input type="text" size="4" name="search_thirdparty" value="'.dol_escape_htmltag($search_thirdparty).'"></td>';
}
print '<td class="liste_titre"><input type="text" size="4" name="search_task_label" value="'.dol_escape_htmltag($search_task_label).'"></td>';
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
foreach ($TWeek as $week_number) {
	print '<td class="liste_titre"></td>';
}
// Action column
print '<td class="liste_titre nowrap right">';
$searchpicto = $form->showFilterAndCheckAddButtons(0);
print $searchpicto;
print '</td>';
print "</tr>\n";

print '<tr class="liste_titre">';
if (getDolGlobalString('PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT')) {
	print '<td>'.$langs->trans("Project").'</td>';
}
if (getDolGlobalString('PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT')) {
	print '<td>'.$langs->trans("ThirdParty").'</td>';
}
print '<td>'.$langs->trans("Task").'</td>';
if (!empty($arrayfields['t.planned_workload']['checked'])) {
	print '<td align="right" class="leftborder plannedworkload maxwidth75">'.$form->textwithpicto($langs->trans("PlannedWorkloadShort"), $langs->trans("PlannedWorkload")).'</td>';
}
if (!empty($arrayfields['t.progress']['checked'])) {
	print '<td class="right maxwidth75">'.$langs->trans("ProgressDeclared").'</td>';
}
if (!empty($arrayfields['timeconsumed']['checked'])) {
	print '<td class="right maxwidth100">'.$langs->trans("TimeSpentSmall").'<br>';
	print '<span class="nowraponall">';
	print '<span class="opacitymedium nopadding userimg"><img alt="Photo" class="photouserphoto userphoto" src="'.DOL_URL_ROOT.'/theme/common/everybody.png"></span>';
	print '<span class="opacitymedium paddingleft">'.$langs->trans("EverybodySmall").'</span>';
	print '</span>';
	print '</td>';
	print '<td class="right maxwidth75">'.$langs->trans("TimeSpentSmall").($usertoprocess->firstname ? '<br><span class="nowraponall">'.$usertoprocess->getNomUrl(-2).'<span class="opacitymedium paddingleft">'.dol_trunc($usertoprocess->firstname, 10).'</span></span>' : '').'</td>';
}
foreach ($TWeek as $week_number) {
	print '<td width="6%" class="center bold hide">'.$langs->trans("WeekShort").' '.$week_number.'<br>('.$TFirstDays[$week_number].'...'.$TLastDays[$week_number].')</td>';
}

//print '<td></td>';
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');

print "</tr>\n";

$colspan = 1;


// Get if user is available or not for each day
$isavailable = array();
// TODO See code into perweek.php to initialize isavailable array

// By default, we can edit only tasks we are assigned to
$restrictviewformytask = getDolGlobalInt('PROJECT_TIME_SHOW_TASK_NOT_ASSIGNED', 2);
if (count($tasksarray) > 0) {
	//var_dump($tasksarray);				// contains only selected tasks
	//var_dump($tasksarraywithoutfilter);	// contains all tasks (if there is a filter, not defined if no filter)
	//var_dump($tasksrole);

	$j = 0;
	$level = 0;
	$totalforvisibletasks = projectLinesPerMonth($j, $firstdaytoshow, $usertoprocess, 0, $tasksarray, $level, $projectsrole, $tasksrole, $mine, $restrictviewformytask, $isavailable, 0, $TWeek, $arrayfields);
	//var_dump($totalforvisibletasks);

	// Show total for all other tasks

	// Calculate total for all tasks
	$listofdistinctprojectid = array(); // List of all distinct projects
	if (!empty($tasksarraywithoutfilter) && is_array($tasksarraywithoutfilter) && count($tasksarraywithoutfilter)) {
		foreach ($tasksarraywithoutfilter as $tmptask) {
			$listofdistinctprojectid[$tmptask->fk_project] = $tmptask->fk_project;
		}
	}
	//var_dump($listofdistinctprojectid);
	$totalforeachweek = array();
	foreach ($listofdistinctprojectid as $tmpprojectid) {
		$projectstatic->id = $tmpprojectid;
		$projectstatic->loadTimeSpentMonth($firstdaytoshow, 0, $usertoprocess->id); // Load time spent from table element_time for the project into this->weekWorkLoad and this->weekWorkLoadPerTask for all days of a week
		foreach ($TWeek as $weekNb) {
			$totalforeachweek[$weekNb] += $projectstatic->monthWorkLoad[$weekNb];
		}
	}

	//var_dump($totalforeachday);
	//var_dump($totalforvisibletasks);

	// Is there a diff between selected/filtered tasks and all tasks ?
	$isdiff = 0;
	if (count($totalforeachweek)) {
		foreach ($TWeek as $weekNb) {
			$timeonothertasks = ($totalforeachweek[$weekNb] - $totalforvisibletasks[$weekNb]);
			if ($timeonothertasks) {
				$isdiff = 1;
				break;
			}
		}
	}

	// There is a diff between total shown on screen and total spent by user, so we add a line with all other cumulated time of user
	if ($isdiff) {
		print '<tr class="oddeven othertaskwithtime">';
		print '<td colspan="'.$colspan.'" class="opacitymedium">';
		print $langs->trans("OtherFilteredTasks");
		print '</td>';
		if (!empty($arrayfields['t.planned_workload']['checked'])) {
			print '<td class="liste_total"></td>';
		}
		if (!empty($arrayfields['t.progress']['checked'])) {
			print '<td class="liste_total"></td>';
		}
		if (!empty($arrayfields['timeconsumed']['checked'])) {
			print '<td class="liste_total"></td>';
			print '<td class="liste_total"></td>';
		}
		$j = 0;
		foreach ($TWeek as $weekNb) {
			$j++;
			print '<td class="center hide'.($j <= 1 ? ' borderleft' : '').'">';

			$timeonothertasks = ($totalforeachweek[$weekNb] - $totalforvisibletasks[$weekNb]);
			if ($timeonothertasks) {
				print '<span class="timesheetalreadyrecorded" title="texttoreplace"><input type="text" class="center smallpadd width40" disabled="" id="timespent[-1]['.$weekNb.']" name="task[-1]['.$weekNb.']" value="';
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

		$j = 0;
		foreach ($TWeek as $weekNb) {
			$j++;
			print '<td class="liste_total hide'.$weekNb.' center'.($j <= 1 ? ' borderleft' : '').'"><div class="totalDay'.$weekNb.'">'.convertSecondToTime($totalforvisibletasks[$weekNb], 'allhourmin').'</div></td>';
		}
		print '<td class="liste_total center"><div class="totalDayAll">&nbsp;</div></td>
    	</tr>';
	}
} else {
	print '<tr><td colspan="15"><span class="opacitymedium">'.$langs->trans("NoAssignedTasks").'</span></td></tr>';
}
print "</table>";
print '</div>';

print '<input type="hidden" id="numberOfLines" name="numberOfLines" value="'.count($tasksarray).'"/>'."\n";
print '<input type="hidden" id="numberOfFirstLine" name="numberOfFirstLine" value="'.(reset($TWeek)).'"/>'."\n";

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

	foreach ($TWeek as $week_number) {
		print "    updateTotal(".((int) $week_number).", '".dol_escape_js($modeinput)."');";
	}
	print "\n});\n";
	print '</script>';
}

// End of page
llxFooter();
$db->close();
