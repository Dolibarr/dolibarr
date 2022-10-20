<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/projet/activity/perday.php
 *	\ingroup    projet
 *	\brief      List activities of tasks (per day entry)
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

$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'perdaycard';

$mine = 0;
if ($mode == 'mine') {
	$mine = 1;
}

$projectid = GETPOSTISSET("id") ? GETPOST("id", "int", 1) : GETPOST("projectid", "int");

$hookmanager->initHooks(array('timesheetperdaycard'));

// Security check
$socid = 0;
// For external user, no check is done on company because readability is managed by public status of project and assignement.
//if ($user->socid > 0) $socid=$user->socid;
$result = restrictedArea($user, 'projet', $projectid);

$now = dol_now();

$year = GETPOST('reyear', 'int') ?GETPOST('reyear', 'int') : (GETPOST("year", "int") ?GETPOST("year", "int") : (GETPOST("addtimeyear", "int") ?GETPOST("addtimeyear", "int") : date("Y")));
$month = GETPOST('remonth', 'int') ?GETPOST('remonth', 'int') : (GETPOST("month", "int") ?GETPOST("month", "int") : (GETPOST("addtimemonth", "int") ?GETPOST("addtimemonth", "int") : date("m")));
$day = GETPOST('reday', 'int') ?GETPOST('reday', 'int') : (GETPOST("day", "int") ?GETPOST("day", "int") : (GETPOST("addtimeday", "int") ?GETPOST("addtimeday", "int") : date("d")));
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

$monthofday = GETPOST('addtimemonth');
$dayofday = GETPOST('addtimeday');
$yearofday = GETPOST('addtimeyear');

//var_dump(GETPOST('remonth'));
//var_dump(GETPOST('button_search_x'));
//var_dump(GETPOST('button_addtime'));

$daytoparse = $now;
if ($year && $month && $day) {
	$daytoparse = dol_mktime(0, 0, 0, $month, $day, $year); // this are value submited after submit of action 'submitdateselect'
} elseif ($yearofday && $monthofday && $dayofday) {
	$daytoparse = dol_mktime(0, 0, 0, $monthofday, $dayofday, $yearofday); // xxxofday is value of day after submit action 'addtime'
}

$daytoparsegmt = dol_now('gmt');
if ($yearofday && $monthofday && $dayofday) $daytoparsegmt = dol_mktime(0, 0, 0, $monthofday, $dayofday, $yearofday, 'gmt'); // xxxofday is value of day after submit action 'addtime'
elseif ($year && $month && $day) $daytoparsegmt = dol_mktime(0, 0, 0, $month, $day, $year, 'gmt'); // this are value submited after submit of action 'submitdateselect'

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
$project = new Project($db);

// Extra fields
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Definition of fields for list
$arrayfields = array();
$arrayfields['t.planned_workload'] = array('label'=>'PlannedWorkload', 'checked'=>1, 'enabled'=>1, 'position'=>0);
$arrayfields['t.progress'] = array('label'=>'ProgressDeclared', 'checked'=>1, 'enabled'=>1, 'position'=>0);
$arrayfields['timeconsumed'] = array('label'=>'TimeConsumed', 'checked'=>1, 'enabled'=>1, 'position'=>15);
/*$arrayfields=array(
 // Project
 'p.opp_amount'=>array('label'=>$langs->trans("OpportunityAmountShort"), 'checked'=>0, 'enabled'=>($conf->global->PROJECT_USE_OPPORTUNITIES?1:0), 'position'=>103),
 'p.fk_opp_status'=>array('label'=>$langs->trans("OpportunityStatusShort"), 'checked'=>0, 'enabled'=>($conf->global->PROJECT_USE_OPPORTUNITIES?1:0), 'position'=>104),
 'p.opp_percent'=>array('label'=>$langs->trans("OpportunityProbabilityShort"), 'checked'=>0, 'enabled'=>($conf->global->PROJECT_USE_OPPORTUNITIES?1:0), 'position'=>105),
 'p.budget_amount'=>array('label'=>$langs->trans("Budget"), 'checked'=>0, 'position'=>110),
 'p.usage_bill_time'=>array('label'=>$langs->trans("BillTimeShort"), 'checked'=>0, 'position'=>115),
 );
 */
// Extra fields
if (!empty($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) > 0) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		if (!empty($extrafields->attributes[$object->table_element]['list'][$key])) {
			$arrayfields["efpt.".$key] = array('label'=>$extrafields->attributes[$object->table_element]['label'][$key], 'checked'=>(($extrafields->attributes[$object->table_element]['list'][$key] < 0) ? 0 : 1), 'position'=>$extrafields->attributes[$object->table_element]['pos'][$key], 'enabled'=>(abs((int) $extrafields->attributes[$object->table_element]['list'][$key]) != 3 && $extrafields->attributes[$object->table_element]['perms'][$key]));
		}
	}
}
$arrayfields = dol_sort_array($arrayfields, 'position');


$search_array_options_project = $extrafields->getOptionalsFromPost($project->table_element, '', 'search_');
$search_array_options_task = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_task_');


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
	$timespent_duration = array();

	if (is_array($_POST)) {
		foreach ($_POST as $key => $time) {
			if (intval($time) > 0) {
				$matches = array();
				// Hours or minutes of duration
				if (preg_match("/([0-9]+)duration(hour|min)/", $key, $matches)) {
					$id = $matches[1];
					if ($id > 0) {
						// We store HOURS in seconds
						if ($matches[2] == 'hour') {
							$timespent_duration[$id] += $time * 60 * 60;
						}

						// We store MINUTES in seconds
						if ($matches[2] == 'min') {
							$timespent_duration[$id] += $time * 60;
						}
					}
				}
			}
		}
	}

	if (count($timespent_duration) > 0) {
		foreach ($timespent_duration as $key => $val) {
			$object->fetch($key);
			$taskid = $object->id;

			if (GETPOSTISSET($taskid.'progress')) {
				$object->progress = GETPOST($taskid.'progress', 'int');
			} else {
				unset($object->progress);
			}

			$object->timespent_duration = $val;
			$object->timespent_fk_user = $usertoprocess->id;
			$object->timespent_note = GETPOST($key.'note');
			if (GETPOST($key."hour", 'int') != '' && GETPOST($key."hour", 'int') >= 0) {	// If hour was entered
				$object->timespent_datehour = dol_mktime(GETPOST($key."hour", 'int'), GETPOST($key."min", 'int'), 0, $monthofday, $dayofday, $yearofday);
				$object->timespent_withhour = 1;
			} else {
				$object->timespent_datehour = dol_mktime(12, 0, 0, $monthofday, $dayofday, $yearofday);
			}
			$object->timespent_date = $object->timespent_datehour;

			if ($object->timespent_date > 0) {
				$result = $object->addTimeSpent($user);
			} else {
				setEventMessages("ErrorBadDate", null, 'errors');
				$error++;
				break;
			}

			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
				break;
			}
		}

		if (!$error) {
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');

			// Redirect to avoid submit twice on back
			header('Location: '.$_SERVER["PHP_SELF"].'?'.($projectid ? 'id='.$projectid : '').($search_usertoprocessid ? '&search_usertoprocessid='.urlencode($search_usertoprocessid) : '').($mode ? '&mode='.$mode : '').'&year='.$yearofday.'&month='.$monthofday.'&day='.$dayofday);
			exit;
		}
	} else {
		setEventMessages($langs->trans("ErrorTimeSpentIsEmpty"), null, 'errors');
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

$prev = dol_getdate($daytoparse - (24 * 3600));
$prev_year  = $prev['year'];
$prev_month = $prev['mon'];
$prev_day   = $prev['mday'];

$next = dol_getdate($daytoparse + (24 * 3600));
$next_year  = $next['year'];
$next_month = $next['mon'];
$next_day   = $next['mday'];

$title = $langs->trans("TimeSpent");

$projectsListId = $projectstatic->getProjectsAuthorizedForUser($usertoprocess, (empty($usertoprocess->id) ? 2 : 0), 1); // Return all project i have permission on (assigned to me+public). I want my tasks and some of my task may be on a public projet that is not my project

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

$tasksarray = $taskstatic->getTasksArray(0, 0, ($project->id ? $project->id : 0), $socid, 0, $search_project_ref, $onlyopenedproject, $morewherefilter, ($search_usertoprocessid ? $search_usertoprocessid : 0), 0, $extrafields); // We want to see all task of opened project i am allowed to see and that match filter, not only my tasks. Later only mine will be editable later.
if ($morewherefilter) {	// Get all task without any filter, so we can show total of time spent for not visible tasks
	$tasksarraywithoutfilter = $taskstatic->getTasksArray(0, 0, ($project->id ? $project->id : 0), $socid, 0, '', $onlyopenedproject, '', ($search_usertoprocessid ? $search_usertoprocessid : 0)); // We want to see all task of opened project i am allowed to see and that match filter, not only my tasks. Later only mine will be editable later.
}
$projectsrole = $taskstatic->getUserRolesForProjectsOrTasks($usertoprocess, 0, ($project->id ? $project->id : 0), 0, $onlyopenedproject);
$tasksrole = $taskstatic->getUserRolesForProjectsOrTasks(0, $usertoprocess, ($project->id ? $project->id : 0), 0, $onlyopenedproject);
//var_dump($usertoprocess);
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

/*
$search_array_options = $search_array_options_project;
$search_options_pattern='search_options_';
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';
*/

$search_array_options = $search_array_options_task;
$search_options_pattern = 'search_task_options_';
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// Show navigation bar
$nav = '<a class="inline-block valignmiddle" href="?year='.$prev_year."&month=".$prev_month."&day=".$prev_day.$param.'">'.img_previous($langs->trans("Previous"))."</a>\n";
$nav .= dol_print_date(dol_mktime(0, 0, 0, $month, $day, $year), "%A").' ';
$nav .= " <span id=\"month_name\">".dol_print_date(dol_mktime(0, 0, 0, $month, $day, $year), "day")." </span>\n";
$nav .= '<a class="inline-block valignmiddle" href="?year='.$next_year."&month=".$next_month."&day=".$next_day.$param.'">'.img_next($langs->trans("Next"))."</a>\n";
$nav .= ' '.$form->selectDate(-1, '', 0, 0, 2, "addtime", 1, 1).' ';
$nav .= ' <button type="submit" name="button_search_x" value="x" class="bordertransp nobordertransp button_search"><span class="fa fa-search"></span></button>';

$picto = 'clock';

print '<form name="addtime" method="POST" action="'.$_SERVER["PHP_SELF"].($project->id > 0 ? '?id='.$project->id : '').'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="addtime">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';
$tmp = dol_getdate($daytoparse);
print '<input type="hidden" name="addtimeyear" value="'.$tmp['year'].'">';
print '<input type="hidden" name="addtimemonth" value="'.$tmp['mon'].'">';
print '<input type="hidden" name="addtimeday" value="'.$tmp['mday'].'">';

$head = project_timesheet_prepare_head($mode, $usertoprocess);
print dol_get_fiche_head($head, 'inputperday', $langs->trans('TimeSpent'), -1, $picto);

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


$moreforfilter = '';

// Filter on categories
/*if (! empty($conf->categorie->enabled))
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
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
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
	print '<th class="right minwidth75 maxwidth100 title="'.dol_escape_htmltag($langs->trans("ProgressDeclared")).'">'.$langs->trans("ProgressDeclared").'</th>';
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
print '<th class="center leftborder">'.$langs->trans("HourStart").'</td>';

// By default, we can edit only tasks we are assigned to
$restrictviewformytask = ((!isset($conf->global->PROJECT_TIME_SHOW_TASK_NOT_ASSIGNED)) ? 2 : $conf->global->PROJECT_TIME_SHOW_TASK_NOT_ASSIGNED);

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

$statusofholidaytocheck = Holiday::STATUS_APPROVED;
$isavailablefordayanduser = $holiday->verifDateHolidayForTimestamp($usertoprocess->id, $daytoparse, $statusofholidaytocheck); // $daytoparse is a date with hours = 0
$isavailable[$daytoparse] = $isavailablefordayanduser; // in projectLinesPerWeek later, we are using $firstdaytoshow and dol_time_plus_duree to loop on each day

$test = num_public_holiday($daytoparsegmt, $daytoparsegmt + 86400, $mysoc->country_code);
if ($test) {
	$isavailable[$daytoparse] = array('morning'=>false, 'afternoon'=>false, 'morning_reason'=>'public_holiday', 'afternoon_reason'=>'public_holiday');
}

$tmparray = dol_getdate($daytoparse, true); // detail of current day
// For monday, must be 0 for monday if MAIN_START_WEEK = 1, must be 1 for monday if MAIN_START_WEEK = 0
$idw = ($tmparray['wday'] - (empty($conf->global->MAIN_START_WEEK) ? 0 : 1));
// numstartworkingday and numendworkingday are default start and end date of working days (1 means sunday if MAIN_START_WEEK is 0, 1 means monday if MAIN_START_WEEK is 1)
$cssweekend = '';
if ((($idw + 1) < $numstartworkingday) || (($idw + 1) > $numendworkingday)) {	// This is a day is not inside the setup of working days, so we use a week-end css.
	$cssweekend = 'weekend';
}

$tmpday = dol_time_plus_duree($daytoparse, $idw, 'd');

$cssonholiday = '';
if (!$isavailable[$daytoparse]['morning'] && !$isavailable[$daytoparse]['afternoon']) {
	$cssonholiday .= 'onholidayallday ';
} elseif (!$isavailable[$daytoparse]['morning']) {
	$cssonholiday .= 'onholidaymorning ';
} elseif (!$isavailable[$daytoparse]['afternoon']) {
	$cssonholiday .= 'onholidayafternoon ';
}

print '<th class="center'.($cssonholiday ? ' '.$cssonholiday : '').($cssweekend ? ' '.$cssweekend : '').'">'.$langs->trans("Duration").'</th>';
print '<th class="center">'.$langs->trans("Note").'</th>';
//print '<td class="center"></td>';
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');

print "</tr>\n";

$colspan = 2 + (empty($conf->global->PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT) ? 0 : 2);

if ($conf->use_javascript_ajax) {
	print '<tr class="liste_total">';
	print '<td class="liste_total" colspan="'.($colspan - 1 + $addcolspan).'">';
	print $langs->trans("Total");
	print '</td>';
	if (!empty($arrayfields['timeconsumed']['checked'])) {
		print '<td class="liste_total"></td>';
		print '<td class="liste_total"></td>';
	}
	print '<td class="liste_total leftborder">';
	//print '  - '.$langs->trans("ExpectedWorkedHours").': <strong>'.price($usertoprocess->weeklyhours, 1, $langs, 0, 0).'</strong>';
	print '</td>';

	print '<td class="liste_total center'.($cssonholiday ? ' '.$cssonholiday : '').($cssweekend ? ' '.$cssweekend : '').'"><div class="totalDay0">&nbsp;</div></td>';

	print '<td class="liste_total"></td>';
	print '<td class="liste_total"></td>';
	print '</tr>';
}


if (count($tasksarray) > 0) {
	//var_dump($tasksarray);				// contains only selected tasks
	//var_dump($tasksarraywithoutfilter);	// contains all tasks (if there is a filter, not defined if no filter)
	//var_dump($tasksrole);

	$j = 0;
	$level = 0;
	$totalforvisibletasks = projectLinesPerDay($j, 0, $usertoprocess, $tasksarray, $level, $projectsrole, $tasksrole, $mine, $restrictviewformytask, $daytoparse, $isavailable, 0, $arrayfields, $extrafields);
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
		$projectstatic->loadTimeSpent($daytoparse, 0, $usertoprocess->id); // Load time spent from table projet_task_time for the project into this->weekWorkLoad and this->weekWorkLoadPerTask for all days of a week
		for ($idw = 0; $idw < 7; $idw++) {
			$tmpday = dol_time_plus_duree($daytoparse, $idw, 'd');
			$totalforeachday[$tmpday] += $projectstatic->weekWorkLoad[$tmpday];
		}
	}
	//var_dump($totalforeachday);

	// Is there a diff between selected/filtered tasks and all tasks ?
	$isdiff = 0;
	if (count($totalforeachday)) {
		$timeonothertasks = ($totalforeachday[$daytoparse] - $totalforvisibletasks[$daytoparse]);
		if ($timeonothertasks) {
			$isdiff = 1;
		}
	}

	// There is a diff between total shown on screen and total spent by user, so we add a line with all other cumulated time of user
	if ($isdiff) {
		print '<tr class="oddeven othertaskwithtime">';
		print '<td colspan="'.($colspan - 1).'" class="opacitymedium">';
		print $langs->trans("OtherFilteredTasks");
		print '</td>';
		if (!empty($arrayfields['timeconsumed']['checked'])) {
			print '<td class="liste_total"></td>';
			print '<td class="liste_total"></td>';
		}
		print '<td class="leftborder"></td>';
		print '<td class="center">';
		$timeonothertasks = ($totalforeachday[$daytoparse] - $totalforvisibletasks[$daytoparse]);
		//if ($timeonothertasks)
		//{
			print '<span class="timesheetalreadyrecorded" title="texttoreplace"><input type="text" class="center" size="2" disabled="" id="timespent[-1][0]" name="task[-1][0]" value="';
		if ($timeonothertasks) {
			print convertSecondToTime($timeonothertasks, 'allhourmin');
		}
			print '"></span>';
		//}
		print '</td>';
		print ' <td class="liste_total"></td>';
		print ' <td class="liste_total"></td>';
		print '</tr>';
	}

	if ($conf->use_javascript_ajax) {
		print '<tr class="liste_total">';
		print '<td class="liste_total" colspan="'.($colspan - 1 + $addcolspan).'">';
		print $langs->trans("Total");
		print '</td>';
		if (!empty($arrayfields['timeconsumed']['checked'])) {
			print '<td class="liste_total"></td>';
			print '<td class="liste_total"></td>';
		}
		print '<td class="liste_total leftborder">';
		//print '  - '.$langs->trans("ExpectedWorkedHours").': <strong>'.price($usertoprocess->weeklyhours, 1, $langs, 0, 0).'</strong>';
		print '</td>';

		print '<td class="liste_total center'.($cssonholiday ? ' '.$cssonholiday : '').($cssweekend ? ' '.$cssweekend : '').'"><div class="totalDay0">&nbsp;</div></td>';

		print '<td class="liste_total"></td>
                <td class="liste_total"></td>
                </tr>';
	}
} else {
	print '<tr><td colspan="14"><span class="opacitymedium">'.$langs->trans("NoAssignedTasks").'</span></td></tr>';
}
print "</table>";
print '</div>';

print '<input type="hidden" id="numberOfLines" name="numberOfLines" value="'.count($tasksarray).'"/>'."\n";

print '<div class="center">';
print '<input type="submit" name="button_addtime" class="button button-save"'.(!empty($disabledtask) ? ' disabled' : '').' value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

if (!empty($conf->use_javascript_ajax)) {
	$modeinput = 'hours';
	print "\n<!-- JS CODE TO ENABLE Tooltips on all object with class classfortooltip -->\n";
	print '<script type="text/javascript">'."\n";
	print "jQuery(document).ready(function () {\n";
	print "		updateTotal(0,\''.$modeinput.'\');\n";
	print '		jQuery(".timesheetalreadyrecorded").tooltip({
					show: { collision: "flipfit", effect:\'toggle\', delay:50 },
					hide: { effect:\'toggle\', delay: 50 },
					tooltipClass: "mytooltip",
					content: function () {
						return \''.dol_escape_js($langs->trans("TimeAlreadyRecorded", $usertoprocess->getFullName($langs))).'\';
					}
				});'."\n";
	print " 	jQuery('.inputhour, .inputminute').bind('keyup', function(e) { updateTotal(0, '".$modeinput."') });";
	print "\n});\n";
	print '</script>';
}

// End of page
llxFooter();
$db->close();
