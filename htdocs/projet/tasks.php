<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/projet/tasks.php
 *	\ingroup    projet
 *	\brief      List all tasks of a project
 *	\version    $Id: tasks.php,v 1.8 2011/07/31 23:23:39 eldy Exp $
 */

require ("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/task.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");

$mine = $_REQUEST['mode']=='mine' ? 1 : 0;
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
//$result = restrictedArea($user, 'projet', $projectid);
if (!$user->rights->projet->lire) accessforbidden();

$userAccess=0;

$langs->load("users");
$langs->load("projects");

$progress=GETPOST('progress');
$description=GETPOST('description');


/*
 * Actions
 */

if ($_POST["action"] == 'createtask' && $user->rights->projet->creer)
{
	$error=0;

	$date_start = dol_mktime(12,0,0,$_POST['dateomonth'],$_POST['dateoday'],$_POST['dateoyear']);
    $date_end = dol_mktime(12,0,0,$_POST['dateemonth'],$_POST['dateeday'],$_POST['dateeyear']);

	if (empty($_POST["cancel"]))
	{
		if (empty($_POST['label']))
		{
			$mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Label"));
			$_GET["action"]='create';
			$error++;
		}
		else if (empty($_POST['task_parent']))
		{
			$mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentities("ChildOfTask"));
			$_GET["action"]='create';
			$error++;
		}

		if (! $error)
		{
			$tmparray=explode('_',$_POST['task_parent']);
			$projectid=$tmparray[0];
			if (empty($projectid)) $projectid = $_POST["id"]; // If projectid is ''
			$task_parent=$tmparray[1];
			if (empty($task_parent)) $task_parent = 0;	// If task_parent is ''

			$task = new Task($db);

			$task->fk_project = $projectid;
			$task->label = $_POST["label"];
			$task->description = $_POST['description'];
			$task->fk_task_parent = $task_parent;
			$task->date_c = dol_now();
			$task->date_start = $date_start;
			$task->date_end = $date_end;
			$task->progress = $_POST['progress'];

			$taskid = $task->create($user);

			if ($taskid > 0)
			{
				$result = $task->add_contact($_POST["userid"], 'TASKEXECUTIVE', 'internal');
			}
		}

		if (! $error)
		{
			if (empty($projectid))
			{
				Header("Location: ".DOL_URL_ROOT.'/projet/tasks/index.php'.(empty($_REQUEST["mode"])?'':'?mode='.$_REQUEST["mode"]));
				exit;
			}
			else
			{
				Header("Location: ".DOL_URL_ROOT.'/projet/tasks/task.php?id='.$taskid);
				exit;
			}
		}
	}
	else
	{
        if (empty($_GET["id"]) && empty($_POST["id"]))
        {
            // We go back on task list
            Header("Location: ".DOL_URL_ROOT.'/projet/tasks/index.php'.(empty($_REQUEST["mode"])?'':'?mode='.$_REQUEST["mode"]));
            exit;
        }
	}
}

/*
 * View
 */

$form=new Form($db);
$formother=new FormOther($db);

$help_url="EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";
llxHeader("",$langs->trans("Tasks"),$help_url);

$task = new Task($db);

$id = (! empty($_GET['id']))?$_GET['id']:$_POST['id'];
$ref= $_GET['ref'];
if ($id > 0 || ! empty($ref))
{
	$project = new Project($db);
	$project->fetch($_REQUEST["id"],$_GET["ref"]);
	if ($project->societe->id > 0)  $result=$project->societe->fetch($project->societe->id);

	// To verify role of users
	$userAccess = $project->restrictedProjectArea($user);
}

if ($_GET["action"] == 'create' && $user->rights->projet->creer && (empty($project->societe->id) || $userAccess))
{
	print_fiche_titre($langs->trans("NewTask"));

	if ($mesg) print '<div class="error">'.$mesg.'</div>';

	print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="createtask">';
	if ($_GET['id'])   print '<input type="hidden" name="id" value="'.$_GET['id'].'">';
	if ($_GET['mode']) print '<input type="hidden" name="mode" value="'.$_GET['mode'].'">';

	print '<table class="border" width="100%">';

	print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td>';
	print '<input type="text" size="25" name="label" class="flat" value="'.$_POST["label"].'">';
	print '</td></tr>';

	// List of projects
	print '<tr><td class="fieldrequired">'.$langs->trans("ChildOfTask").'</td><td>';
	print $formother->selectProjectTasks('',$projectid?$projectid:$_GET["id"], 'task_parent', 0, 0, 1, 1);
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("AffectedTo").'</td><td>';
	print $form->select_users($user->id,'userid',1);
	print '</td></tr>';

	// Date start
	print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
	print $form->select_date(($date_start?$date_start:''),'dateo',0,0,0,'',1,1);
	print '</td></tr>';

	// Date end
	print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
	print $form->select_date(($date_end?$date_end:-1),'datee',0,0,0,'',1,1);
	print '</td></tr>';

	// Progress
	print '<tr><td>'.$langs->trans("Progress").'</td><td colspan="3">';
	print $formother->select_percent($progress,'progress');
	print '</td></tr>';

	// Description
	print '<tr><td valign="top">'.$langs->trans("Description").'</td>';
	print '<td>';
	print '<textarea name="description" wrap="soft" cols="80" rows="'.ROWS_3.'">'.$description.'</textarea>';
	print '</td></tr>';

	print '<tr><td colspan="2" align="center">';
	//if (sizeof($tasksarray))
	//{
		print '<input type="submit" class="button" name="add" value="'.$langs->trans("Add").'">';
		print ' &nbsp; &nbsp; ';
	//}
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</td></tr>';

	print '</table>';
	print '</form>';

}
else
{
	/*
	 * Fiche projet en mode visu
	 */
	$userstatic=new User($db);

	$tab='tasks';

	$head=project_prepare_head($project);
	dol_fiche_head($head, $tab, $langs->trans("Project"),0,($project->public?'projectpub':'project'));

	$param=($_REQUEST["mode"]=='mine'?'&mode=mine':'');

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td width="30%">';
	print $langs->trans("Ref");
	print '</td><td>';
	// Define a complementary filter for search of next/prev ref.
	$projectsListId = $project->getProjectsAuthorizedForUser($user,$mine,1);
	$project->next_prev_filter=" rowid in (".$projectsListId.")";
	print $form->showrefnav($project,'ref','',1,'ref','ref','',$param);
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("Label").'</td><td>'.$project->title.'</td></tr>';

	print '<tr><td>'.$langs->trans("Company").'</td><td>';
	if (! empty($project->societe->id)) print $project->societe->getNomUrl(1);
	else print '&nbsp;';
	print '</td>';
	print '</tr>';

	// Visibility
	print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
	if ($project->public) print $langs->trans('SharedProject');
	else print $langs->trans('PrivateProject');
	print '</td></tr>';

	// Statut
	print '<tr><td>'.$langs->trans("Status").'</td><td>'.$project->getLibStatut(4).'</td></tr>';

	print '</table>';

	print '</div>';

	/*
	 * Actions
	 */
	print '<div class="tabsAction">';

	if ($user->rights->projet->all->creer || $user->rights->projet->creer)
	{
		if ($project->public || $userAccess)
		{
			print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$project->id.'&action=create'.$param.'">'.$langs->trans('AddTask').'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('AddTask').'</a>';
		}
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NoPermission").'">'.$langs->trans('AddTask').'</a>';
	}

	print '</div>';

	print '<br>';


	// Link to switch in "my task" / "all task"
	print '<table width="100%"><tr><td align="right">';
	if ($_REQUEST["mode"] == 'mine')
	{
		print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$project->id.'">'.$langs->trans("DoNotShowMyTasksOnly").'</a>';
		//print ' - ';
		//print $langs->trans("ShowMyTaskOnly");
	}
	else
	{
		//print $langs->trans("DoNotShowMyTaskOnly");
		//print ' - ';
		print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$project->id.'&mode=mine">'.$langs->trans("ShowMyTasksOnly").'</a>';
	}
	print '</td></tr></table>';

	// Get list of tasks in tasksarray and taskarrayfiltered
	// We need all tasks (even not limited to a user because a task to user
	// can have a parent that is not affected to him).
	$tasksarray=$task->getTasksArray(0, 0, $project->id, $socid, 0);
	// We load also tasks limited to a particular user
	$tasksrole=($_REQUEST["mode"]=='mine' ? $task->getUserRolesForProjectsOrTasks(0,$user,$project->id,0) : '');
	//var_dump($tasksarray);
	//var_dump($tasksrole);

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	if ($projectstatic->id) print '<td>'.$langs->trans("Project").'</td>';
	print '<td width="80">'.$langs->trans("RefTask").'</td>';
	print '<td>'.$langs->trans("LabelTask").'</td>';
	print '<td align="right">'.$langs->trans("Progress").'</td>';
	print '<td align="right">'.$langs->trans("TimeSpent").'</td>';
	print "</tr>\n";
	if (sizeof($tasksarray) > 0)
	{
		// Show all lines in taskarray (recursive function to go down on tree)
		$j=0;
		$nboftaskshown=PLines($j, 0, $tasksarray, $level, true, 0, $tasksrole);
	}
	else
	{
		print '<tr><td colspan="'.($projectstatic->id?"5":"4").'">'.$langs->trans("NoTasks").'</td></tr>';
	}
	print "</table>";


	// Test if database is clean. If not we clean it.
	//print 'mode='.$_REQUEST["mode"].' $nboftaskshown='.$nboftaskshown.' sizeof($tasksarray)='.sizeof($tasksarray).' sizeof($tasksrole)='.sizeof($tasksrole).'<br>';
	if ($_REQUEST["mode"]=='mine')
	{
		if ($nboftaskshown < sizeof($tasksrole)) clean_orphelins($db);
	}
	else
	{
		if ($nboftaskshown < sizeof($tasksarray)) clean_orphelins($db);
	}
}

$db->close();

llxFooter('$Date: 2011/07/31 23:23:39 $ - $Revision: 1.8 $');
?>
