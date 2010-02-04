<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/projet/tasks/fiche.php
 *	\ingroup    projet
 *	\brief      Fiche taches d'un projet
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");
require_once(DOL_DOCUMENT_ROOT."/projet/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/projet/tasks/task.class.php");
require_once(DOL_DOCUMENT_ROOT."/html.formother.class.php");

$projectid='';
$projectid=isset($_REQUEST["id"])?$_REQUEST["id"]:$_POST["id"];

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'projet', $projectid);

/*
 * Actions
 */

if ($_POST["action"] == 'createtask' && $user->rights->projet->creer)
{
	$error=0;

	if (empty($_POST["cancel"]))
	{
		if (empty($_POST['label']))
		{
			$mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Label"));
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
			$task->date_c = dol_now('tzserver');
			$task->date_start = dol_mktime(12,0,0,$_POST['dateomonth'],$_POST['dateoday'],$_POST['dateoyear']);
			$task->date_end = dol_mktime(12,0,0,$_POST['dateemonth'],$_POST['dateeday'],$_POST['dateeyear']);
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
}

/*
 * View
 */

$form=new Form($db);
$formother=new FormOther($db);

$help_url="EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";
llxHeader("",$langs->trans("Tasks"),$help_url);

$task = new Task($db);

$id = $_REQUEST['id'];
$ref= $_GET['ref'];
if ($id > 0 || ! empty($ref))
{
	$project = new Project($db);
	$project->fetch($_REQUEST["id"],$_GET["ref"]);
	if ($project->societe->id > 0)  $result=$project->societe->fetch($project->societe->id);

	// To verify role of users
	$userAccess = $project->restrictedProjectArea($user);
}

if ($_GET["action"] == 'create' && $user->rights->projet->task->creer && $userAccess)
{
	print_fiche_titre($langs->trans("NewTask"));

	if ($mesg) print '<div class="error">'.$mesg.'</div>';

	print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="createtask">';
	if ($_GET['id'])   print '<input type="hidden" name="id" value="'.$_GET['id'].'">';
	if ($_GET['mode']) print '<input type="hidden" name="mode" value="'.$_GET['mode'].'">';

	print '<table class="border" width="100%">';

	print '<tr><td>'.$langs->trans("Label").'</td><td>';
	print '<input type="text" size="25" name="label" class="flat" value="'.$_POST["label"].'">';
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("ChildOfTask").'</td><td>';
	print $formother->selectProjectTasks('',$projectid, 'task_parent', $user->admin?0:1, 0);
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("AffectedTo").'</td><td>';
	print $form->select_users($user->id,'userid',1);
	print '</td></tr>';

	// Date start
	print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
	print $form->select_date('','dateo');
	print '</td></tr>';

	// Date end
	print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
	print $form->select_date(-1,'datee');
	print '</td></tr>';

	// Progress
	print '<tr><td>'.$langs->trans("Progress").'</td><td colspan="3">';
	print $formother->select_percent($task->progress,'progress');
	print '</td></tr>';

	// Description
	print '<tr><td valign="top">'.$langs->trans("Description").'</td>';
	print '<td>';
	print '<textarea name="description" wrap="soft" cols="80" rows="'.ROWS_3.'"></textarea>';
	print '</td></tr>';

	$tasksarray=$task->getTasksArray(0, $user, 1);

	print '<tr><td colspan="2" align="center">';
	if (sizeof($tasksarray))
	{
		print '<input type="submit" class="button" name="add" value="'.$langs->trans("Add").'">';
		print ' &nbsp; &nbsp; ';
	}
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</td></tr>';

	print '</table>';
	print '</form>';

}
else
{
	/*
	 * Fiche projet en mode visu
	 *
	 */
	$userstatic=new User($db);

	$tab='tasks';
	if ($_REQUEST["mode"]=='mine') $tab='mytasks';

	$head=project_prepare_head($project);
	dol_fiche_head($head, $tab, $langs->trans("Project"),0,'project');

	$param=($_REQUEST["mode"]=='mine'?'&mode=mine':'');

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td width="30%">';
	print $langs->trans("Ref");
	print '</td><td>';
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
	else print $langs->trans('Private');
	print '</td></tr>';

	// Statut
	print '<tr><td>'.$langs->trans("Status").'</td><td>'.$project->getLibStatut(4).'</td></tr>';

	print '</table>';

	print '</div>';

	/*
	 * Actions
	 */
	print '<div class="tabsAction">';

	if ($user->rights->projet->task->creer)
	{
		if ($userAccess)
		{
			print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$project->id.'&action=create'.$param.'">'.$langs->trans('AddTask').'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('AddTask').'</a>';
		}
	}

	print '</div>';

	print '<br>';

	// Get list of tasks in tasksarray and taskarrayfiltered
	// We need all tasks (even not limited to a user because a task to user
	// can have a parent that is not affected to him).
	$tasksarray=$task->getTasksArray(0, 0, $project->id);
	// We load also tasks limited to a particular user
	$tasksrole=($_REQUEST["mode"]=='mine' ? $task->getTasksRoleForUser($user,$project->id) : '');

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	if ($projectstatic->id) print '<td>'.$langs->trans("Project").'</td>';
	print '<td width="80">'.$langs->trans("RefTask").'</td>';
	print '<td>'.$langs->trans("LabelTask").'</td>';
	print '<td align="right">'.$langs->trans("TimeSpent").'</td>';
	print "</tr>\n";
	// Show all lines in taskarray (recursive function to go down on tree)
	$j=0;
	$nboftaskshown=PLines($j, 0, $tasksarray, $level, true, 0, $tasksrole);
	print "</table>";

	print '</div>';

	// Test if database is clean. If not we clean it.
	//print '$nboftaskshown='.$nboftaskshown.' sizeof($tasksarray)='.sizeof($tasksarray).' sizeof($tasksrole)='.sizeof($tasksrole).'<br>';
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

llxFooter('$Date$ - $Revision$');
?>
