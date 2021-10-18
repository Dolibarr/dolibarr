<?php
/* Copyright (C) 2021      Michael Jeanmotte <michael.jeanmotte@gmelectronics.be>
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
 *	\file       htdocs/projet/activity/statistics.php
 *	\ingroup    projet
 *	\brief      Statistics of activities
 */


require "../../../main.inc.php";
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/holiday/class/holiday.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/timestats.class.php';

// Load translation files required by the page
$langs->loadLangs(array('projects', 'users', 'companies'));

$action = GETPOST('action', 'aZ09');
$mode = GETPOST("mode", 'alpha');
$id = GETPOST('id', 'int');
$taskid = GETPOST('taskid', 'int');

$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'perweekcard';

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

$year = GETPOST('reyear', 'int') ? GETPOST('reyear', 'int') : (GETPOST("year", 'int') ? GETPOST("year", "int") : date("Y"));
$month = GETPOST('remonth', 'int') ? GETPOST('remonth', 'int') : (GETPOST("month", 'int') ? GETPOST("month", "int") : date("m"));
$day = GETPOST('reday', 'int') ? GETPOST('reday', 'int') : (GETPOST("day", 'int') ? GETPOST("day", "int") : date("d"));
$week = GETPOST("week", "int") ? GETPOST("week", "int") : date("W");

$day = (int)$day;

//$search_categ = GETPOST("search_categ", 'alpha');
$search_usertoprocessid = GETPOST('search_usertoprocessid', 'int');
$search_task_ref = GETPOST('search_task_ref', 'alpha');
$search_task_label = GETPOST('search_task_label', 'alpha');
$search_project_ref = GETPOST('search_project_ref', 'alpha');
$search_thirdparty = GETPOST('search_thirdparty', 'alpha');
$search_declared_progress = GETPOST('search_declared_progress', 'alpha');

$startdayarray = dol_get_first_day_week($day, $month, $year);

$prev = $startdayarray;
$prev_year = $prev['prev_year'];
$prev_month = $prev['prev_month'];
$prev_day = $prev['prev_day'];
$first_day = $prev['first_day'];
$first_month = $prev['first_month'];
$first_year = $prev['first_year'];
$week = $prev['week'];

$next = dol_get_next_week($first_day, $week, $first_month, $first_year);
$next_year = $next['year'];
$next_month = $next['month'];
$next_day = $next['day'];

// Define firstdaytoshow and lastdaytoshow (warning: lastdaytoshow is last second to show + 1)
$firstdaytoshow = dol_mktime(0, 0, 0, $first_month, $first_day, $first_year);
$lastdaytoshow = dol_time_plus_duree($firstdaytoshow, 7, 'd');

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
 if (! empty($val['visible'])) $arrayfields['t.'.$key]=array('label'=>$val['label'], 'checked'=>(($val['visible']<0)?0:1), 'enabled'=>$val['enabled'], 'position'=>$val['position']);
 }*/
// Definition of fields for list
// Extra fields
if (is_array($extrafields->attributes['projet_task']['label']) && count($extrafields->attributes['projet_task']['label']) > 0) {
	foreach ($extrafields->attributes['projet_task']['label'] as $key => $val) {
		if (!empty($extrafields->attributes['projet_task']['list'][$key])) {
			$arrayfields["efpt." . $key] = array('label' => $extrafields->attributes['projet_task']['label'][$key], 'checked' => (($extrafields->attributes['projet_task']['list'][$key] < 0) ? 0 : 1), 'position' => $extrafields->attributes['projet_task']['pos'][$key], 'enabled' => (abs((int)$extrafields->attributes['projet_task']['list'][$key]) != 3 && $extrafields->attributes['projet_task']['perms'][$key]));
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

include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

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
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';

$tasksarray = $taskstatic->getTasksArray(0, 0, ($project->id ? $project->id : 0), $socid, 0, $search_project_ref, $onlyopenedproject, $morewherefilter, ($search_usertoprocessid ? $search_usertoprocessid : 0), 0, $extrafields); // We want to see all tasks of open project i am allowed to see and that match filter, not only my tasks. Later only mine will be editable later.
if ($morewherefilter) {    // Get all task without any filter, so we can show total of time spent for not visible tasks
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
$param .= ($mode ? '&mode=' . urlencode($mode) : '');
$param .= ($search_project_ref ? '&search_project_ref=' . urlencode($search_project_ref) : '');
$param .= ($search_usertoprocessid > 0 ? '&search_usertoprocessid=' . urlencode($search_usertoprocessid) : '');
$param .= ($search_thirdparty ? '&search_thirdparty=' . urlencode($search_thirdparty) : '');
$param .= ($search_task_ref ? '&search_task_ref=' . urlencode($search_task_ref) : '');
$param .= ($search_task_label ? '&search_task_label=' . urlencode($search_task_label) : '');

$search_array_options = $search_array_options_project;
$search_options_pattern = 'search_options_';
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_param.tpl.php';

$search_array_options = $search_array_options_task;
$search_options_pattern = 'search_task_options_';
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_param.tpl.php';

// Show navigation bar
$nav = '<a class="inline-block valignmiddle" href="?year=' . $prev_year . "&month=" . $prev_month . "&day=" . $prev_day . $param . '">' . img_previous($langs->trans("Previous")) . "</a>\n";
$nav .= " <span id=\"month_name\">" . dol_print_date(dol_mktime(0, 0, 0, $first_month, $first_day, $first_year), "%Y") . ", " . $langs->trans("WeekShort") . " " . $week . " </span>\n";
$nav .= '<a class="inline-block valignmiddle" href="?year=' . $next_year . "&month=" . $next_month . "&day=" . $next_day . $param . '">' . img_next($langs->trans("Next")) . "</a>\n";
$nav .= ' ' . $form->selectDate(-1, '', 0, 0, 2, "addtime", 1, 1) . ' ';
$nav .= ' <button type="submit" name="submitdateselect" value="x" class="bordertransp"><span class="fa fa-search"></span></button>';

$picto = 'clock';

print '<form name="addtime" method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="addtime">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';
print '<input type="hidden" name="mode" value="' . $mode . '">';
print '<input type="hidden" name="day" value="' . $day . '">';
print '<input type="hidden" name="month" value="' . $month . '">';
print '<input type="hidden" name="year" value="' . $year . '">';

$head = project_timesheet_statistics_prepare_head($mode, $usertoprocess);
print dol_get_fiche_head($head, 'inputstatisticsbytask', $langs->trans('TimeSpent'), -1, $picto);

// Show description of content
print '<div class="hideonsmartphone opacitymedium">';
if ($mine || ($usertoprocess->id == $user->id)) {
	print $langs->trans("MyTasksDesc") . '.' . ($onlyopenedproject ? ' ' . $langs->trans("OnlyOpenedProject") : '') . '<br>';
} else {
	if (empty($usertoprocess->id) || $usertoprocess->id < 0) {
		if ($user->rights->projet->all->lire && !$socid) {
			print $langs->trans("ProjectsDesc") . '.' . ($onlyopenedproject ? ' ' . $langs->trans("OnlyOpenedProject") : '') . '<br>';
		} else {
			print $langs->trans("ProjectsPublicTaskDesc") . '.' . ($onlyopenedproject ? ' ' . $langs->trans("OnlyOpenedProject") : '') . '<br>';
		}
	}
}

print '</div>';

print dol_get_fiche_end();

/*********************************
 * Week Search
 */

print '<div class="floatright right'.($conf->dol_optimize_smallscreen ? ' centpercent' : '').'">'.$nav.'</div>'; // We move this before the assign to components so, the default submit button is not the assign to.

/*********************************
 * User Filter
 */

$moreforfilter = '';

// If the user can view user other than himself
$moreforfilter .= '<div class="divsearchfield">';
$moreforfilter .= '<div class="inline-block hideonsmartphone"></div>';
$includeonly = 'hierarchyme';
if (empty($user->rights->user->user->lire)) {
	$includeonly = array($user->id);
}
$moreforfilter .= img_picto($langs->trans('Filter').' '.$langs->trans('User'), 'user', 'class="paddingright pictofixedwidth"').$form->select_dolusers($search_usertoprocessid ? $search_usertoprocessid : $usertoprocess->id, 'search_usertoprocessid', $user->rights->user->user->lire ? 0 : 0, null, 0, $includeonly, null, 0, 0, 0, '', 0, '', 'maxwidth200');
$moreforfilter .= '</div>';

if (!empty($moreforfilter)) {
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	print '</div>';
}

print '</form>' . "\n\n";

/*********************************
 * Graphic: TotalTimeInHourByMonth
 */

$stats_time = new TimeStats($db);
if (!empty($userid) && $userid != -1) {
	$stats_time->userid = $userid;
}
if (!empty($socid) && $socid != -1) {
	$stats_time->socid = $socid;
}
if (!empty($year)) {
	$stats_time->year = $year;
}

$nowyear = strftime("%Y", dol_now());
$year = GETPOST('year') > 0 ?GETPOST('year') : $nowyear;
$startyear = $year - (empty($conf->global->MAIN_STATS_GRAPHS_SHOW_N_YEARS) ? 2 : max(1, min(10, $conf->global->MAIN_STATS_GRAPHS_SHOW_N_YEARS)));
$endyear = $year;

// Build graphic number of object
// $data = array(array('Lib',val1,val2,val3),...)
$data = $stats_time->getConsumedTimeByMonth($endyear);

$filenamenb = $conf->project->dir_output."/stats/tasknbprevyear-".$year.".png";
$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=taskstats&amp;file=tasknbprevyear-'.$year.'.png';

$WIDTH = DolGraph::getDefaultGraphSizeForStats('width');
$HEIGHT = DolGraph::getDefaultGraphSizeForStats('height');

$px1 = new DolGraph();
$mesg = $px1->isGraphKo();
if (!$mesg) {
	$px1->SetData($data);
	$i = $startyear; $legend = array();
	while ($i <= $endyear) {
		$legend[] = $i;
		$i++;
	}
	$px1->SetLegend($legend);
	$px1->SetMaxValue($px1->GetCeilMaxValue());
	$px1->SetWidth($WIDTH);
	$px1->SetHeight($HEIGHT);
	$px1->SetYLabel($langs->trans("ProjectNbTask"));
	$px1->SetShading(3);
	$px1->SetHorizTickIncrement(1);
	$px1->mode = 'depth';
	$px1->SetTitle($langs->trans("TotalTimeInHourByMonth"));

	$px1->draw($filenamenb, $fileurlnb);
}

print '<div class="fichetwothirdright"><div class="ficheaddleft">';

$stringtoshow = '<table class="border centpercent"><tr class="pair nohover"><td class="center">';
if ($mesg) {
	print $mesg;
} else {
	$stringtoshow .= $px1->show();
	$stringtoshow .= "<br>\n";
}
$stringtoshow .= '</td></tr></table>';

print $stringtoshow;
print '</div></div>';

/*********************************
 * Graphic: TotalTimeInHourByProjectCurrentYear
 */

$stats_time = new TimeStats($db);
if (!empty($userid) && $userid != -1) {
	$stats_time->userid = $userid;
}
if (!empty($socid) && $socid != -1) {
	$stats_time->socid = $socid;
}
if (!empty($year)) {
	$stats_time->year = $year;
}

$nowyear = strftime("%Y", dol_now());
$year = GETPOST('year') > 0 ?GETPOST('year') : $nowyear;
$startyear = $year - (empty($conf->global->MAIN_STATS_GRAPHS_SHOW_N_YEARS) ? 2 : max(1, min(10, $conf->global->MAIN_STATS_GRAPHS_SHOW_N_YEARS)));
$endyear = $year;

// Build graphic number of object
// $data = array(array('Lib',val1,val2,val3),...)
$data = $stats_time->getConsumedTimeByMonth($endyear);

$filenamenb = $conf->project->dir_output."/stats/tasknbprevyear-".$year.".png";
$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=taskstats&amp;file=tasknbprevyear-'.$year.'.png';

$WIDTH = DolGraph::getDefaultGraphSizeForStats('width');
$HEIGHT = DolGraph::getDefaultGraphSizeForStats('height');

$px1 = new DolGraph();
$mesg = $px1->isGraphKo();
if (!$mesg) {
	$px1->SetData($data);
	$i = $startyear; $legend = array();
	while ($i <= $endyear) {
		$legend[] = $i;
		$i++;
	}
	$px1->SetLegend($legend);
	$px1->SetMaxValue($px1->GetCeilMaxValue());
	$px1->SetWidth($WIDTH);
	$px1->SetHeight($HEIGHT);
	$px1->SetYLabel($langs->trans("ProjectNbTask"));
	$px1->SetShading(3);
	$px1->SetHorizTickIncrement(1);
	$px1->mode = 'depth';
	$px1->SetTitle($langs->trans("TotalTimeInHourByMonth"));

	$px1->draw($filenamenb, $fileurlnb);
}

print '<div class="fichetwothirdright"><div class="ficheaddleft">';

$stringtoshow = '<table class="border centpercent"><tr class="pair nohover"><td class="center">';
if ($mesg) {
	print $mesg;
} else {
	$stringtoshow .= $px1->show();
	$stringtoshow .= "<br>\n";
}
$stringtoshow .= '</td></tr></table>';

print $stringtoshow;
print '</div></div>';

// End of page
llxFooter();
$db->close();

