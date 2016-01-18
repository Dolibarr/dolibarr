<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006-2010 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/projet/tasks/list.php
 *	\ingroup    project
 *	\brief      List all task of a project
 */

require ("../../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$langs->load('projects');
$langs->load('users');

$id=GETPOST('id','int');

$search_all=GETPOST('search_all');
$search_project=GETPOST('search_project');
if (! isset($_GET['search_projectstatus']) && ! isset($_POST['search_projectstatus'])) $search_projectstatus=1;
else $search_projectstatus=GETPOST('search_projectstatus');
$search_task_ref=GETPOST('search_task_ref');
$search_task_label=GETPOST('search_task_label');
$search_project_user=GETPOST('search_project_user');
$search_task_user=GETPOST('search_task_user');

$mine = $_REQUEST['mode']=='mine' ? 1 : 0;
if ($mine) $search_task_user = $user->id;

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
if (!$user->rights->projet->lire) accessforbidden();

$sortfield = GETPOST("sortfield");
$sortorder = GETPOST("sortorder");
$page = GETPOST("page");
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;

// Purge criteria
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
    $search_all="";
	$search_project="";
	$search_projectstatus="";
	$search_task_ref="";
	$search_task_label="";
	$search_task_user=-1;
	$search_project_user=-1;
}
if (empty($search_projectstatus) && $search_projectstatus == '') $search_projectstatus=1;

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	't.ref'=>"Ref",
	't.label'=>"Label",
);


/*
 * Actions
 */

// None


/*
 * View
 */

$form=new Form($db);
$projectstatic = new Project($db);
$taskstatic = new Task($db);
$puser=new User($db);
$tuser=new User($db);
if ($search_project_user > 0) $puser->fetch($search_project_user);
if ($search_task_user > 0) $tuser->fetch($search_task_user);

$title=$langs->trans("Activities");
if ($mine) $title=$langs->trans("MyActivities");

llxHeader("",$title,"Projet");

if ($id)
{
	$projectstatic->fetch($id);
	$projectstatic->societe->fetch($projectstatic->societe->id);
}

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $num, '', 'title_project');

// Show description of content
if ($mine) print $langs->trans("MyTasksDesc").'<br><br>';
else
{
	if ($user->rights->projet->all->lire && ! $socid) print $langs->trans("ProjectsDesc").'<br><br>';
	else print $langs->trans("ProjectsPublicDesc").'<br><br>';
}

// Get list of project id allowed to user (in a string list separated by coma)
$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,$mine,1,$socid);
//var_dump($projectsListId);

// Get list of tasks in tasksarray and taskarrayfiltered
// We need all tasks (even not limited to a user because a task assigned to a user can have a parent that is not assigned to him and we need such parents).
$morewherefilter='';
if ($search_all)        $morewherefilter.=natural_search(array_keys($fieldstosearchall), $search_all);
if ($search_task_ref)   $morewherefilter.=natural_search('t.ref', $search_task_ref);
if ($search_task_label) $morewherefilter.=natural_search('t.label', $search_task_label);

$tasksarray=$taskstatic->getTasksArray(0, 0, $projectstatic->id, $socid, 0, $search_project, $search_projectstatus, $morewherefilter, $search_project_user, 0);    // We don't use filter on task user. Because sometimes a task is assigned but not the parent one and we want to show also parent, so filtering is done during output
$tasksrole=$taskstatic->getUserRolesForProjectsOrTasks(0, ($tuser->id?$tuser:null), $projectstatic->id, 0, $search_projectstatus); // We load also tasks limited to a particular user

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="type" value="'.$type.'">';

if ($search_all)
{
    foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
    print $langs->trans("FilterOnInto", $search_all, join(', ',$fieldstosearchall));
}


// If the user can view users
//if ($mine) $search_project_user=$user->id;    // We want by default all project. "mine" is a filter for task contact on this page
$moreforfilter.='<div class="divsearchfield">';
$moreforfilter.=$langs->trans('ProjectsWithThisUserAsContact'). ' ';
$includeonly='';
if (empty($user->rights->user->user->lire)) $includeonly=array($user->id);
$moreforfilter.=$form->select_dolusers($search_project_user, 'search_project_user', 1, '', 0, $includeonly, '', 0, 0, 0, '', 0, '', 'maxwidth300');
$moreforfilter.='</div>';

// If the user can view users
$moreforfilter.='<div class="divsearchfield">';
$moreforfilter.=$langs->trans('TasksWithThisUserAsContact'). ': ';
$includeonly='';
if (empty($user->rights->user->user->lire)) $includeonly=array($user->id);
$moreforfilter.=$form->select_dolusers($search_task_user, 'search_task_user', 1, '', 0, $includeonly, '', 0, 0, 0, '', 0, '', 'maxwidth300');
$moreforfilter.='</div>';

if (! empty($moreforfilter))
{
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	print '</div>';
}

print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'" id="tablelines3">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Project").'</td>';
print '<td>'.$langs->trans("ProjectStatus").'</td>';
print '<td>'.$langs->trans("RefTask").'</td>';
print '<td>'.$langs->trans("LabelTask").'</td>';
print '<td align="center">'.$langs->trans("DateStart").'</td>';
print '<td align="center">'.$langs->trans("DateEnd").'</td>';
print '<td align="right">'.$langs->trans("PlannedWorkload");
// TODO Replace 86400 and 7 to take account working hours per day and working day per weeks
//print '<br>('.$langs->trans("DelayWorkHour").')';
print '</td>';
print '<td align="right">'.$langs->trans("TimeSpent").'</td>';
print '<td align="right">'.$langs->trans("ProgressCalculated").'</td>';
print '<td align="right">'.$langs->trans("ProgressDeclared").'</td>';
print "</tr>\n";

print '<tr class="liste_titre">';
print '<td class="liste_titre">';
print '<input type="text" class="flat" name="search_project" value="'.$search_project.'" size="8">';
print '</td>';
print '<td class="liste_titre">';
$listofstatus=array(-1=>'&nbsp;');
foreach($projectstatic->statuts_short as $key => $val) $listofstatus[$key]=$langs->trans($val);
print $form->selectarray('search_projectstatus', $listofstatus, $search_projectstatus);
print '</td>';
print '<td class="liste_titre">';
print '<input type="text" class="flat" name="search_task_ref" value="'.dol_escape_htmltag($search_task_ref).'" size="4">';
print '</td>';
print '<td class="liste_titre">';
print '<input type="text" class="flat" name="search_task_label" value="'.dol_escape_htmltag($search_task_label).'" size="8">';
print '</td>';
print '<td class="liste_titre" colspan="5">';
print '&nbsp;';
print '<td class="liste_titre nowrap" align="right">';
print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("RemoveFilter"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
print '</td>';

$max=10000;

if (count($tasksarray) > (empty($conf->global->PROJECT_LIMIT_TASK_PROJECT_AREA)?$max:$conf->global->PROJECT_LIMIT_TASK_PROJECT_AREA))
{
	$langs->load("errors");
	print '<tr '.$bc[0].'>';
	print '<td colspan="9">';
	print $langs->trans("WarningTooManyDataPleaseUseMoreFilters", $max, 'PROJECT_LIMIT_TASK_PROJECT_AREA');
	print '</td></tr>';
}
else
{
	// Show all lines in taskarray (recursive function to go down on tree)
	$j=0; $level=0;
	//var_dump($tasksarray);
	$nboftaskshown=projectLinesa($j, 0, $tasksarray, $level, true, 1, $tasksrole, $projectsListId, 0);
}

print "</table>";

print '</form>';

print '</div>';

/*
 * Actions
 */
if ($user->rights->projet->creer)
{
	print '<div class="tabsAction">';
	print '<a class="butAction" href="'.DOL_URL_ROOT.'/projet/tasks.php?action=create">'.$langs->trans('AddTask').'</a>';
	print '</div>';
}


llxFooter();

$db->close();
