<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/projet/tasks/task.php
 *	\ingroup    projet
 *	\brief      Page of a project task
 */

require ("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/task.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");

$taskid = GETPOST("id");
$taskref = GETPOST("ref");

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
if (!$user->rights->projet->lire) accessforbidden();


/*
 * Actions
 */

if ($_POST["action"] == 'update' && ! $_POST["cancel"] && $user->rights->projet->creer)
{
	$error=0;

	if (empty($_POST["label"]))
	{
		$error++;
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Label")).'</div>';
	}
	if (! $error)
	{
		$task = new Task($db);
		$task->fetch($_POST["id"]);

		$tmparray=explode('_',$_POST['task_parent']);
		$task_parent=$tmparray[1];
		if (empty($task_parent)) $task_parent = 0;	// If task_parent is ''

		$task->label = $_POST["label"];
		$task->description = $_POST['description'];
		$task->fk_task_parent = $task_parent;
		$task->date_start = dol_mktime(12,0,0,$_POST['dateomonth'],$_POST['dateoday'],$_POST['dateoyear']);
		$task->date_end = dol_mktime(12,0,0,$_POST['dateemonth'],$_POST['dateeday'],$_POST['dateeyear']);
		$task->progress = $_POST['progress'];

		$result=$task->update($user);

		$taskid=$task->id;  // On retourne sur la fiche tache
	}
	else
	{
		$taskid=$_POST["id"];
		$_GET['action']='edit';
	}
}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == "yes" && $user->rights->projet->supprimer)
{
	$task = new Task($db);
	if ($task->fetch($_GET["id"]) >= 0 )
	{
		$projet = new Project($db);
		$result=$projet->fetch($task->fk_projet);
		if (! empty($projet->socid))
		{
			$projet->societe->fetch($projet->socid);
		}

		if ($task->delete($user) > 0)
		{
			Header("Location: index.php");
			exit;
		}
		else
		{
			$langs->load("errors");
			$mesg='<div class="error">'.$langs->trans($task->error).'</div>';
			$_POST["action"]='';
		}
	}
}


/*
 * View
 */

llxHeader("",$langs->trans("Task"));

$html = new Form($db);
$formother = new FormOther($db);
$project = new Project($db);

if ($taskid)
{
	$task = new Task($db);
	$projectstatic = new Project($db);

	if ($task->fetch($taskid) >= 0 )
	{
		$result=$projectstatic->fetch($task->fk_project);
		if (! empty($projectstatic->socid)) $projectstatic->societe->fetch($projectstatic->socid);

		// To verify role of users
		//$userAccess = $projectstatic->restrictedProjectArea($user); // We allow task affected to user even if a not allowed project
		//$arrayofuseridoftask=$task->getListContactId('internal');

		dol_htmloutput_mesg($mesg);

		$head=task_prepare_head($task);

		dol_fiche_head($head, 'task', $langs->trans("Task"),0,'projecttask');

		if ($_GET["action"] == 'edit' && $user->rights->projet->creer)
		{
			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="id" value="'.$task->id.'">';

			print '<table class="border" width="100%">';

			// Ref
			print '<tr><td width="30%">'.$langs->trans("Ref").'</td>';
			print '<td>'.$task->ref.'</td></tr>';

			// Label
			print '<tr><td>'.$langs->trans("Label").'</td>';
			print '<td><input size="30" name="label" value="'.$task->label.'"></td></tr>';

			// Project
			print '<tr><td>'.$langs->trans("Project").'</td><td colspan="3">';
			print $projectstatic->getNomUrl(1);
			print '</td></tr>';

			// Third party
			print '<td>'.$langs->trans("Company").'</td><td colspan="3">';
			if ($projectstatic->societe->id) print $projectstatic->societe->getNomUrl(1);
			else print '&nbsp;';
			print '</td></tr>';

			// Task parent
			print '<tr><td>'.$langs->trans("ChildOfTask").'</td><td>';
			print $formother->selectProjectTasks($task->fk_task_parent,$projectstatic->id, 'task_parent', $user->admin?0:1, 0);
			print '</td></tr>';

			// Date start
			print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
			print $html->select_date($task->date_start,'dateo');
			print '</td></tr>';

			// Date end
			print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
			print $html->select_date($task->date_end?$task->date_end:-1,'datee');
			print '</td></tr>';

			// Progress
			print '<tr><td>'.$langs->trans("Progress").'</td><td colspan="3">';
			print $formother->select_percent($task->progress,'progress');
			print '</td></tr>';

			// Description
			print '<tr><td valign="top">'.$langs->trans("Description").'</td>';
			print '<td>';
			print '<textarea name="description" wrap="soft" cols="80" rows="'.ROWS_3.'">'.$task->description.'</textarea>';
			print '</td></tr>';

			print '</table>';
			
			print '<center><br>';
			print '<input type="submit" class="button" name="update" value="'.$langs->trans("Modify").'"> &nbsp; ';
			print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
			print '<center>';

			print '</form>';
		}
		else
		{
			/*
			 * Fiche tache en mode visu
			 */

			if ($_GET["action"] == 'delete')
			{
				$ret=$html->form_confirm($_SERVER["PHP_SELF"]."?id=".$_GET["id"],$langs->trans("DeleteATask"),$langs->trans("ConfirmDeleteATask"),"confirm_delete");
				if ($ret == 'html') print '<br>';
			}

			print '<table class="border" width="100%">';

			// Ref
			print '<tr><td width="30%">';
			print $langs->trans("Ref");
			print '</td><td colspan="3">';
			$projectsListId = $project->getProjectsAuthorizedForUser($user,$mine,1);
			$task->next_prev_filter=" fk_projet in (".$projectsListId.")";
			print $html->showrefnav($task,'id','',1,'rowid','ref','','');
			print '</td>';
			print '</tr>';

			// Label
			print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$task->label.'</td></tr>';

			// Project
			print '<tr><td>'.$langs->trans("Project").'</td><td colspan="3">';
			print $projectstatic->getNomUrl(1);
			print '</td></tr>';

			// Third party
			print '<td>'.$langs->trans("Company").'</td><td colspan="3">';
			if ($projectstatic->societe->id) print $projectstatic->societe->getNomUrl(1);
			else print '&nbsp;';
			print '</td></tr>';

			// Date start
			print '<tr><td>'.$langs->trans("DateStart").'</td><td colspan="3">';
			print dol_print_date($task->date_start,'day');
			print '</td></tr>';

			// Date end
			print '<tr><td>'.$langs->trans("DateEnd").'</td><td colspan="3">';
			print dol_print_date($task->date_end,'day');
			print '</td></tr>';

			// Progress
			print '<tr><td>'.$langs->trans("Progress").'</td><td colspan="3">';
			print $task->progress.' %';
			print '</td></tr>';

			// Description
			print '<td valign="top">'.$langs->trans("Description").'</td><td colspan="3">';
			print nl2br($task->description);
			print '</td></tr>';

			print '</table>';
			
		}

		dol_fiche_end();
		
		
		if ($_GET["action"] != 'edit')
		{
			/*
			 * Actions
			 */
			print '<div class="tabsAction">';

			// Modify
			if ($user->rights->projet->creer)
			{
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$task->id.'&amp;action=edit">'.$langs->trans('Modify').'</a>';
			}
			else
			{
				print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Modify').'</a>';
			}

			// Delete
			if ($user->rights->projet->supprimer && ! $task->hasChildren())
			{
				print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$task->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';
			}
			else
			{
				print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Delete').'</a>';
			}

			print '</div>';
		}
	}
}

$db->close();

llxFooter();
?>
