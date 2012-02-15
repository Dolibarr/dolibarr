<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010      François Legastelois <flegastelois@teclib.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/task.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/project.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");

$langs->load('projects');

$action=GETPOST('action');
$mode=GETPOST("mode");
$id=GETPOST('id');

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

    $timespent_duration=0;

    foreach($_POST as $key => $time)
    {
        if(intval($time)>0)
        {
            // Hours or minutes
            if(preg_match("/([0-9]+)(hour|min)/",$key,$matches))
            {
                $id = $matches[1];

                // We store HOURS in seconds
                if($matches[2]=='hour') $timespent_duration += $time*60*60;

                // We store MINUTES in seconds
                if($matches[2]=='min') $timespent_duration += $time*60;
            }
        }
    }

    if ($timespent_duration > 0)
    {
        $task->fetch($id);
        $task->timespent_duration = $timespent_duration;
        $task->timespent_fk_user = $user->id;
        $task->timespent_date = dol_mktime(12,0,0,$_POST["{$id}month"],$_POST["{$id}day"],$_POST["{$id}year"]);
        $task->addTimeSpent($user);

        // header to avoid submit twice on back
        header('Location: '.$_SERVER["PHP_SELF"].'?id='.$projectid.($mode?'&mode='.$mode:''));
        exit;
    }
    else
    {
        $mesg='<div class="error">'.$langs->trans("ErrorTimeSpentIsEmpty").'</div>';
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


llxHeader("",$title,"");

//$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,$mine,1);
$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,0,1);  // Return all project i have permission on. I want my tasks and some of my task may be on a public projet that is not my project

if ($id)
{
    $project->fetch($id);
    $project->societe->fetch($project->societe->id);
}

$tasksarray=$taskstatic->getTasksArray(0,0,($project->id?$project->id:$projectsListId),$socid,0);    // We want to see all task of project i am allowed to see, not only mine. Later only mine will be editable later.
$projectsrole=$taskstatic->getUserRolesForProjectsOrTasks($user,0,($project->id?$project->id:$projectsListId),0);
$tasksrole=$taskstatic->getUserRolesForProjectsOrTasks(0,$user,($project->id?$project->id:$projectsListId),0);
//var_dump($tasksarray);
//var_dump($projectsrole);
//var_dump($taskrole);


print_barre_liste($title, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $num);


dol_htmloutput_mesg($mesg);


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
print '<td align="right">'.$langs->trans("Progress").'</td>';
print '<td align="right">'.$langs->trans("TimeSpent").'</td>';
print '<td colspan="2">'.$langs->trans("AddDuration").'</td>';
print "</tr>\n";
projectLinesb($j, 0, $tasksarray, $level, $projectsrole, $tasksrole, $mine);

print "</table>";
print '</form>';


llxFooter();

$db->close();
?>
