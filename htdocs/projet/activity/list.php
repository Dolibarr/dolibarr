<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010      François Legastelois <flegastelois@teclib.com>
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
 *	\file       htdocs/projet/activity/list.php
 *	\ingroup    projet
 *	\brief      List activities of tasks
 */

require ("../../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$langs->load('projects');

$action=GETPOST('action');
$mode=GETPOST("mode");
$id=GETPOST('id','int');

$mine=0;
if ($mode == 'mine') $mine=1;

$projectid='';
$projectid=isset($_GET["id"])?$_GET["id"]:$_POST["projectid"];

// Security check
$socid=0;
if ($user->societe_id > 0) $socid=$user->societe_id;
$result = restrictedArea($user, 'projet', $projectid);


/*
 * Actions
 */

if ($action == 'addtime' && $user->rights->projet->creer)
{
    $task = new Task($db);

    $timespent_duration=array();

    foreach($_POST as $key => $time)
    {
        if (intval($time) > 0)
        {
            // Hours or minutes
            if (preg_match("/([0-9]+)(hour|min)/",$key,$matches))
            {
                $id = $matches[1];
				if ($id > 0)
				{
	                // We store HOURS in seconds
	                if($matches[2]=='hour') $timespent_duration[$id] += $time*60*60;

	                // We store MINUTES in seconds
	                if($matches[2]=='min') $timespent_duration[$id] += $time*60;
				}
            }
        }
    }

    if (count($timespent_duration) > 0)
    {
    	foreach($timespent_duration as $key => $val)
    	{
	        $task->fetch($key);
		    $task->progress = GETPOST($key . 'progress', 'int');
	        $task->timespent_duration = $val;
	        $task->timespent_fk_user = $user->id;
	        $task->timespent_date = dol_mktime(12,0,0,$_POST["{$key}month"],$_POST["{$key}day"],$_POST["{$key}year"]);
	        $task->addTimeSpent($user);
    	}

    	setEventMessage($langs->trans("RecordSaved"));

        // Redirect to avoid submit twice on back
        header('Location: '.$_SERVER["PHP_SELF"].'?id='.$projectid.($mode?'&mode='.$mode:''));
        exit;
    }
    else
    {
	    setEventMessage($langs->trans("ErrorTimeSpentIsEmpty"), 'errors');
    }
}



/*
 * View
 */

$form=new Form($db);
$projectstatic=new Project($db);
$project = new Project($db);
$taskstatic = new Task($db);

$title=$langs->trans("TimeSpent");
if ($mine) $title=$langs->trans("MyTimeSpent");

//$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,$mine,1);
$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,0,1);  // Return all project i have permission on. I want my tasks and some of my task may be on a public projet that is not my project

if ($id)
{
    $project->fetch($id);
    $project->fetch_thirdparty();
}

$onlyopened=1;	// or -1
$tasksarray=$taskstatic->getTasksArray(0,0,($project->id?$project->id:$projectsListId),$socid,0,'',$onlyopened);    // We want to see all task of opened project i am allowed to see, not only mine. Later only mine will be editable later.
$projectsrole=$taskstatic->getUserRolesForProjectsOrTasks($user,0,($project->id?$project->id:$projectsListId),0);
$tasksrole=$taskstatic->getUserRolesForProjectsOrTasks(0,$user,($project->id?$project->id:$projectsListId),0);
//var_dump($tasksarray);
//var_dump($projectsrole);
//var_dump($taskrole);


llxHeader("",$title,"");

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $num);

// Show description of content
if ($mine) print $langs->trans("MyTasksDesc").($onlyopened?' '.$langs->trans("OnlyOpenedProject"):'').'<br><br>';
else
{
	if ($user->rights->projet->all->lire && ! $socid) print $langs->trans("ProjectsDesc").($onlyopened?' '.$langs->trans("OnlyOpenedProject"):'').'<br><br>';
	else print $langs->trans("ProjectsPublicTaskDesc").($onlyopened?' '.$langs->trans("AlsoOnlyOpenedProject"):'').'<br><br>';
}


// Filter on user
/*	dol_fiche_head('');
	print '<table class="border" width="100%"><tr><td width="25%">'.$langs->trans("User").'</td>';
	print '<td>';
	if ($mine) print $user->getLoginUrl(1);
	print '</td>';
	print '</tr></table>';
	dol_fiche_end();
*/

// Filter on user
/*	dol_fiche_head('');
	print '<table class="border" width="100%"><tr><td width="25%">'.$langs->trans("User").'</td>';
	print '<td>';
	if ($mine) print $user->getLoginUrl(1);
	print '</td>';
	print '</tr></table>';
	dol_fiche_end();
*/

print '<form name="addtime" method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$project->id.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="addtime">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Project").'</td>';
print '<td>'.$langs->trans("RefTask").'</td>';
print '<td>'.$langs->trans("LabelTask").'</td>';
print '<td align="center">'.$langs->trans("DateStart").'</td>';
print '<td align="center">'.$langs->trans("DateEnd").'</td>';
print '<td align="right">'.$langs->trans("PlannedWorkload").'</td>';
print '<td align="right">'.$langs->trans("ProgressDeclared").'</td>';
print '<td align="right">'.$langs->trans("TimeSpent").'</td>';
print '<td colspan="2" align="right">'.$langs->trans("NewTimeSpent").'</td>';
print "</tr>\n";

// By default, we can edit only tasks we are assigned to
$restricteditformytask=(empty($conf->global->PROJECT_TIME_ON_ALL_TASKS_MY_PROJECTS)?1:0);

if (count($tasksarray) > 0)
{
	projectLinesb($j, 0, $tasksarray, $level, $projectsrole, $tasksrole, $mine, $restricteditformytask);
}
else
{
	print '<tr><td colspan="10">'.$langs->trans("NoTasks").'</td></tr>';
}
print "</table>";
print '</form>';


llxFooter();

$db->close();
