<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/projet/tasks/index.php
 *	\ingroup    project
 *	\brief      List all task of a project
 */

require ("../../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$langs->load('projects');
$langs->load('users');

$id=GETPOST('id','int');
$search_project=GETPOST('search_project');
$search_user=GETPOST('search_user', 'int');


// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
if (!$user->rights->projet->lire) accessforbidden();

$sortfield = GETPOST("sortfield");
$sortorder = GETPOST("sortorder");
$page = GETPOST("page");
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;

$mine = $_REQUEST['mode']=='mine' ? 1 : 0;

// Initialize technical object to manage hooks.
$hookmanager->initHooks(array('projecttaskcard'));

/*
 * View
 */

$form=new Form($db);
$projectstatic = new Project($db);
$taskstatic = new Task($db);

$title=$langs->trans("Activities");
if ($mine) $title=$langs->trans("MyActivities");

llxHeader("",$title,"Projet");

if ($id)
{
	$projectstatic->fetch($id);
	$projectstatic->societe->fetch($projectstatic->societe->id);
}

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $num);

// Show description of content
if ($mine) print $langs->trans("MyProjectsDesc").'<br><br>';
else
{
	if ($user->rights->projet->all->lire && ! $socid) print $langs->trans("ProjectsDesc").'<br><br>';
	else print $langs->trans("ProjectsPublicDesc").'<br><br>';
}

//If searching for user and with enough rights
if ($search_user !== '' && $user->rights->projet->all->lire) {
	$fake_user = new User($db);
	$fake_user->id = $search_user;

	// Get list of project id allowed to user (in a string list separated by coma)
	$projectsListId = $projectstatic->getProjectsAuthorizedForUser($fake_user,1,1,$socid);
} else {
	// Get list of project id allowed to user (in a string list separated by coma)
	$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,$mine,1,$socid);
}

// Get list of tasks in tasksarray and taskarrayfiltered
// We need all tasks (even not limited to a user because a task to user can have a parent that is not affected to him).
$tasksarray=$taskstatic->getTasksArray(0, 0, $projectstatic->id, $socid, 0, $search_project, 1, 100);

//If searching for user and with enough rights
if ($search_user !== '' && $user->rights->projet->all->lire) {
	// We load also tasks limited to a particular user
	$tasksrole= $taskstatic->getUserRolesForProjectsOrTasks(0,$fake_user,$projectstatic->id,0);
} else {
	// We load also tasks limited to a particular user
	$tasksrole=($mine ? $taskstatic->getUserRolesForProjectsOrTasks(0,$user,$projectstatic->id,0) : '');
}

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="mode" value="'.GETPOST('mode').'">';
print '<table class="noborder" width="100%">';

// If the user can view prospects other than his'
if ($user->rights->projet->all->lire)
{
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" colspan="8">';
	print $langs->trans('LinkedToSpecificUsers').': ';
	print $form->select_dolusers($search_user, 'search_user', 1);
	print '</td></tr>';

}

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("RefTask").'</td>';
print '<td>'.$langs->trans("AffectedUsers").'</td>';

print '<td>'.$langs->trans("LabelTask").'</td>';
print '<td align="center">'.$langs->trans("DateStart").'</td>';
print '<td align="center">'.$langs->trans("DateEnd").'</td>';
print '<td align="center">'.$langs->trans("PlannedWorkload");
// TODO Replace 86400 and 7 to take account working hours per day and working day per weeks
//print '<br>('.$langs->trans("DelayWorkHour").')';
print '</td>';
print '<td align="right">'.$langs->trans("ProgressDeclared").'</td>';
print '<td align="right">'.$langs->trans("TimeSpent").'</td>';

$hookmanager->executeHooks('printFieldListTitle', array());

print "</tr>\n";

print '<tr class="liste_titre">';
print '<td class="liste_titre">';
print '<input type="text" class="flat" name="search_project" value="'.$search_project.'" size="8">';
print '</td>';
print '<td class="liste_titre" colspan="6">';
print '&nbsp;';
print '</td>';
print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'"></td>';

$hookmanager->executeHooks('printFieldListOption', array());

print "</tr>\n";

if (count($tasksarray) > (empty($conf->global->PROJECT_LIMIT_TASK_PROJECT_AREA)?1000:$conf->global->PROJECT_LIMIT_TASK_PROJECT_AREA))
{
	$langs->load("errors");
	print '<tr '.$bc[0].'>';
	print '<td colspan="8">';
	print $langs->trans("WarningTooManyDataPleaseUseMoreFilters");
	print '</td></tr>';
}
else
{
	// Show all lines in taskarray (recursive function to go down on tree)
	$j=0; $level=0;

	$custom_projectLinesA = function(DoliDB $db, HookManager $hookmanager, &$inc, $parent, &$lines, &$level, $var, &$taskrole, $projectsListId='', &$custom_projectLinesA)
	{
		global $user, $bc, $langs;
		global $projectstatic, $taskstatic;

		$lastprojectid=0;

		$projectsArrayId=explode(',',$projectsListId);

		$numlines=count($lines);

		// We declare counter as global because we want to edit them into recursive call
		global $total_projectlinesa_spent,$total_projectlinesa_planned,$total_projectlinesa_spent_if_planned;
		if ($level == 0)
		{
			$total_projectlinesa_spent=0;
			$total_projectlinesa_planned=0;
			$total_projectlinesa_spent_if_planned=0;
		}

		for ($i = 0 ; $i < $numlines ; $i++)
		{
			if ($parent == 0) $level = 0;

			// Process line
			// print "i:".$i."-".$lines[$i]->fk_project.'<br>';

			if ($lines[$i]->fk_parent == $parent)
			{
				// Show task line.
				$showline=1;
				$showlineingray=0;

				// If there is filters to use
				if (is_array($taskrole))
				{
					// If task not legitimate to show, search if a legitimate task exists later in tree
					if (! isset($taskrole[$lines[$i]->id]) && $lines[$i]->id != $lines[$i]->fk_parent)
					{
						// So search if task has a subtask legitimate to show
						$foundtaskforuserdeeper=0;
						searchTaskInChild($foundtaskforuserdeeper,$lines[$i]->id,$lines,$taskrole);
						//print '$foundtaskforuserpeeper='.$foundtaskforuserdeeper.'<br>';
						if ($foundtaskforuserdeeper > 0)
						{
							$showlineingray=1;		// We will show line but in gray
						}
						else
						{
							$showline=0;			// No reason to show line
						}
					}
				}
				else
				{
					// Caller did not ask to filter on tasks of a specific user (this probably means he want also tasks of all users, into public project
					// or into all other projects if user has permission to).
					if (empty($user->rights->projet->all->lire))
					{
						// User is not allowed on this project and project is not public, so we hide line
						if (! in_array($lines[$i]->fk_project, $projectsArrayId))
						{
							// Note that having a user assigned to a task into a project user has no permission on, should not be possible
							// because assignement on task can be done only on contact of project.
							// If assignement was done and after, was removed from contact of project, then we can hide the line.
							$showline=0;
						}
					}
				}

				if ($showline)
				{
					// Break on a new project
					if ($parent == 0 && $lines[$i]->fk_project != $lastprojectid)
					{
						$var = !$var;
						$lastprojectid=$lines[$i]->fk_project;
					}

					print '<tr '.$bc[$var].' id="row-'.$lines[$i]->id.'">'."\n";

					$taskstatic->id=$lines[$i]->id;
					$taskstatic->ref=$lines[$i]->ref;

					// Ref of task
					print '<td>';
					if ($showlineingray)
					{
						print '<i>'.img_object('','projecttask').' '.$lines[$i]->ref.'</i>';
					}
					else
					{
						$taskstatic->label=($taskrole[$lines[$i]->id]?$langs->trans("YourRole").': '.$taskrole[$lines[$i]->id]:'');
						print $taskstatic->getNomUrl(1,'withproject');
					}
					print '</td>';

					//Affected users
					print '<td>';

					$users = $taskstatic->getIdContact('internal', 'TASKEXECUTIVE');

					foreach ($users as $userid) {
						$user = new User($db);
						$user->fetch($userid);

						print $user->getNomUrl(1);

						if (count($users) > 1) {
							print '<br />';
						}
					}

					print '</td>';

					// Title of task
					print "<td>";
					if ($showlineingray) print '<i>';
					else print '<a href="'.DOL_URL_ROOT.'/projet/tasks/task.php?id='.$lines[$i]->id.'&withproject=1">';
					for ($k = 0 ; $k < $level ; $k++)
					{
						print "&nbsp; &nbsp; &nbsp;";
					}
					print $lines[$i]->label;
					if ($showlineingray) print '</i>';
					else print '</a>';
					print "</td>\n";

					// Date start
					print '<td align="center">';
					print dol_print_date($lines[$i]->date_start,'dayhour');
					print '</td>';

					// Date end
					print '<td align="center">';
					print dol_print_date($lines[$i]->date_end,'dayhour');
					print '</td>';

					// Planned Workload (in working hours)
					print '<td align="center">';
					$fullhour=convertSecondToTime($lines[$i]->planned_workload,'allhourmin');
					$workingdelay=convertSecondToTime($lines[$i]->planned_workload,'all',86400,7);	// TODO Replace 86400 and 7 to take account working hours per day and working day per weeks
					if ($lines[$i]->planned_workload)
					{
						print $fullhour;
						// TODO Add delay taking account of working hours per day and working day per week
						//if ($workingdelay != $fullhour) print '<br>('.$workingdelay.')';
					}
					//else print '--:--';
					print '</td>';

					// Progress declared
					print '<td align="right">';
					print $lines[$i]->progress.' %';
					print '</td>';

					// Time spent
					print '<td align="right">';
					if ($showlineingray) print '<i>';
					else print '<a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?id='.$lines[$i]->id.'&withproject=1">';
					if ($lines[$i]->duration) print convertSecondToTime($lines[$i]->duration,'allhourmin');
					else print '--:--';
					if ($showlineingray) print '</i>';
					else print '</a>';
					print '</td>';

					$parameters = array('obj' => $lines[$i]);
					$hookmanager->executeHooks('printFieldListValue', $parameters);

					print "</tr>\n";

					if (! $showlineingray) $inc++;

					$level++;
					if ($lines[$i]->id) {
						$custom_projectLinesA($db, $hookmanager, $inc, $lines[$i]->id, $lines, $level, $var, $taskrole, $projectsListId, $custom_projectLinesA);
					}
					$level--;
					$total_projectlinesa_spent += $lines[$i]->duration;
					$total_projectlinesa_planned += $lines[$i]->planned_workload;
					if ($lines[$i]->planned_workload) $total_projectlinesa_spent_if_planned += $lines[$i]->duration;
				}
			}
			else
			{
				//$level--;
			}
		}

		if (($total_projectlinesa_planned > 0 || $total_projectlinesa_spent > 0) && $level==0)
		{
			print '<tr class="liste_total">';
			print '<td class="liste_total">'.$langs->trans("Total").'</td>';
			print '<td></td>';
			print '<td></td>';
			print '<td></td>';
			print '<td align="center" class="nowrap liste_total">';
			print convertSecondToTime($total_projectlinesa_planned, 'allhourmin');
			print '</td>';
			print '<td></td>';
			print '<td align="right" class="nowrap liste_total">';
			print convertSecondToTime($total_projectlinesa_spent, 'allhourmin');
			print '</td>';
			print '<td align="right" class="nowrap liste_total">';
			if ($total_projectlinesa_planned) print round(100 * $total_projectlinesa_spent_if_planned / $total_projectlinesa_planned,2).' %';
			print '</td>';

			$parameters = array('obj' => $lines[$i]);
			$hookmanager->executeHooks('printFieldListValue', $parameters);

			print '</tr>';
		}

		return $inc;
	};

	$custom_projectLinesA($db, $hookmanager, $j, 0, $tasksarray, $level, true, $tasksrole, $projectsListId, $custom_projectLinesA);

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
