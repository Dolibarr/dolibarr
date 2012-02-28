<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011      Juanjo Menent        <jmenent@2byte.es>
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
 *	\file       htdocs/projet/tasks/time.php
 *	\ingroup    projet
 *	\brief      Page to add new time spent on a task
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/task.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/project.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");

$langs->load('projects');

$taskid = isset($_GET["id"])?$_GET["id"]:'';
$id = GETPOST('id','int');
$ref= GETPOST('ref');
$action=GETPOST('action');
$withproject=GETPOST('withproject');

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
if (!$user->rights->projet->lire) accessforbidden();


/*
 * Actions
 */
if ($action == 'addtimespent' && $user->rights->projet->creer)
{
	$error=0;

	if (empty($_POST["timespent_durationhour"]) && empty($_POST["timespent_durationmin"]))
	{
		$mesg='<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("Duration")).'</div>';
		$error++;
	}
	if (empty($_POST["userid"]))
	{
		$mesg='<div class="error">'.$langs->trans('ErrorUserNotAffectedToTask').'</div>';
		$error++;
	}

	if (! $error)
	{
		$task = new Task($db);
		$task->fetch($_POST["id"]);

		$task->timespent_note = $_POST["timespent_note"];
		$task->timespent_duration = $_POST["timespent_durationhour"]*60*60;	// We store duration in seconds
		$task->timespent_duration+= $_POST["timespent_durationmin"]*60;		// We store duration in seconds
		$task->timespent_date = dol_mktime(12,0,0,$_POST["timemonth"],$_POST["timeday"],$_POST["timeyear"]);
		$task->timespent_fk_user = $_POST["userid"];

		$result=$task->addTimeSpent($user);
		if ($result >= 0)
		{

		}
		else
		{
			$mesg='<div class="error">'.$langs->trans($task->error).'</div>';
		}
	}
	else
	{
		$_POST["action"]='';
	}
}

if ($action == 'updateline' && ! $_POST["cancel"] && $user->rights->projet->creer)
{
	$error=0;

	if (empty($_POST["new_durationhour"]) && empty($_POST["new_durationmin"]))
	{
		$mesg='<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("Duration")).'</div>';
		$error++;
	}

	if (! $error)
	{
		$task = new Task($db);
		$task->fetch($_POST["id"]);

		$task->timespent_id = $_POST["lineid"];
		$task->timespent_note = $_POST["timespent_note_line"];
		$task->timespent_old_duration = $_POST["old_duration"];
		$task->timespent_duration = $_POST["new_durationhour"]*60*60;	// We store duration in seconds
		$task->timespent_duration+= $_POST["new_durationmin"]*60;		// We store duration in seconds
		$task->timespent_date = dol_mktime(12,0,0,$_POST["timelinemonth"],$_POST["timelineday"],$_POST["timelineyear"]);
		$task->timespent_fk_user = $_POST["userid_line"];

		$result=$task->updateTimeSpent($user);
		if ($result >= 0)
		{

		}
		else
		{
			$mesg='<div class="error">'.$langs->trans($task->error).'</div>';
		}
	}
	else
	{
		$_POST["action"]='';
	}
}

if ($action == 'confirm_delete' && $_REQUEST["confirm"] == "yes" && $user->rights->projet->creer)
{
	$task = new Task($db);
	$task->fetchTimeSpent($_GET['lineid']);
	$result = $task->delTimeSpent($user);

	if (!$result)
	{
		$langs->load("errors");
		$mesg='<div class="error">'.$langs->trans($task->error).'</div>';
		$_POST["action"]='';
	}
}


/*
 * View
 */

$form = new Form($db);
$project = new Project($db);
$task = new Task($db);

llxHeader("",$langs->trans("Task"));

$form = new Form($db);

if ($id > 0 || ! empty($ref))
{
	/*
	 * Fiche projet en mode visu
	 */
	if ($task->fetch($id,$ref) >= 0)
	{
		$result=$project->fetch($task->fk_project);
		if (! empty($project->socid)) $project->societe->fetch($project->socid);

		$userWrite  = $project->restrictedProjectArea($user,'write');

		if ($withproject)
		{
    		// Tabs for project
    		$tab='tasks';
    		$head=project_prepare_head($project);
    		dol_fiche_head($head, $tab, $langs->trans("Project"),0,($project->public?'projectpub':'project'));

    		$param=($mode=='mine'?'&mode=mine':'');

    		print '<table class="border" width="100%">';

    		// Ref
    		print '<tr><td width="30%">';
    		print $langs->trans("Ref");
    		print '</td><td>';
    		// Define a complementary filter for search of next/prev ref.
    		if (! $user->rights->projet->all->lire)
    		{
    		    $projectsListId = $project->getProjectsAuthorizedForUser($user,$mine,0);
    		    $project->next_prev_filter=" rowid in (".(count($projectsListId)?join(',',array_keys($projectsListId)):'0').")";
    		}
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

    		dol_fiche_end();

    		print '<br>';
		}

		$head=task_prepare_head($task);
		dol_fiche_head($head, 'time', $langs->trans("Task"),0,'projecttask');

		dol_htmloutput_mesg($mesg);

		if ($action == 'deleteline')
		{
			$ret=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$_GET["id"].'&lineid='.$_GET["lineid"],$langs->trans("DeleteATimeSpent"),$langs->trans("ConfirmDeleteATimeSpent"),"confirm_delete",'','',1);
			if ($ret == 'html') print '<br>';
		}

		print '<table class="border" width="100%">';

		$param=($withproject?'&withproject=1':'');
		$linkback=$withproject?'<a href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$project->id.'">'.$langs->trans("BackToList").'</a>':'';

		// Ref
		print '<tr><td width="30%">';
		print $langs->trans("Ref");
		print '</td><td colspan="3">';
		if (! GETPOST('withproject') || empty($project->id))
		{
		    $projectsListId = $project->getProjectsAuthorizedForUser($user,$mine,1);
		    $task->next_prev_filter=" fk_projet in (".$projectsListId.")";
		}
		else $task->next_prev_filter=" fk_projet = ".$project->id;
	    print $form->showrefnav($task,'id',$linkback,1,'rowid','ref','',$param);
		print '</td></tr>';

		// Label
		print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$task->label.'</td></tr>';

		// Project
		if (empty($withproject))
		{
    		print '<tr><td>'.$langs->trans("Project").'</td><td>';
    		print $project->getNomUrl(1);
    		print '</td></tr>';

    		// Third party
    		print '<td>'.$langs->trans("Company").'</td><td>';
    		if ($project->societe->id) print $project->societe->getNomUrl(1);
    		else print '&nbsp;';
    		print '</td></tr>';
		}

		print '</table>';

		dol_fiche_end();


		/*
		 * Add time spent
		 */
		if ($user->rights->projet->creer)
		{
			print '<br>';

			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$task->id.'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="addtimespent">';
			print '<input type="hidden" name="id" value="'.$task->id.'">';

			print '<table class="noborder" width="100%">';

			print '<tr class="liste_titre">';
			print '<td width="100">'.$langs->trans("Date").'</td>';
			print '<td>'.$langs->trans("By").'</td>';
			print '<td>'.$langs->trans("Note").'</td>';
			print '<td align="right">'.$langs->trans("Duration").'</td>';
			print '<td width="80">&nbsp;</td>';
			print "</tr>\n";

			print '<tr '.$bc[false].'>';

			// Date
			print '<td nowrap="nowrap">';
			$newdate=dol_mktime(12,0,0,$_POST["timemonth"],$_POST["timeday"],$_POST["timeyear"]);
			print $form->select_date($newdate,'time','','','',"timespent_date");
			print '</td>';

			// Contributor
			print '<td nowrap="nowrap">';
			$contactoftask=$task->getListContactId('internal');
			print img_object('','user');
			print $form->select_users($_POST["userid"]?$_POST["userid"]:$user->id,'userid',0,'',0,'',$contactoftask);
			print '</td>';

			// Note
			print '<td nowrap="nowrap">';
			print '<textarea name="timespent_note" cols="80" rows="'.ROWS_3.'">'.($_POST['timespent_note']?$_POST['timespent_note']:'').'</textarea>';
			print '</td>';

			// Duration
			print '<td nowrap="nowrap" align="right">';
			print $form->select_duration('timespent_duration',($_POST['timespent_duration']?$_POST['timespent_duration']:''));
			print '</td>';

			print '<td align="center">';
			print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
			print '</td></tr>';

			print '</table></form>';
		}

		print '<br>';

		/*
		 *  List of time spent
		 */
		$sql = "SELECT t.rowid, t.task_date, t.task_duration, t.fk_user, t.note";
		$sql.= ", u.name, u.firstname";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet_task_time as t";
		$sql .= " , ".MAIN_DB_PREFIX."user as u";
		$sql .= " WHERE t.fk_task =".$task->id;
		$sql .= " AND t.fk_user = u.rowid";
		$sql .= " ORDER BY t.task_date DESC";

		$var=true;
		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			$tasks = array();
			while ($i < $num)
			{
				$row = $db->fetch_object($resql);
				$tasks[$i] = $row;
				$i++;
			}
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}

		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$task->id.'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="updateline">';
		print '<input type="hidden" name="id" value="'.$task->id.'">';

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td width="100">'.$langs->trans("Date").'</td>';
		print '<td>'.$langs->trans("By").'</td>';
		print '<td align="left">'.$langs->trans("Note").'</td>';
		print '<td align="right">'.$langs->trans("Duration").'</td>';
		print '<td>&nbsp;</td>';
		print "</tr>\n";

		$total = 0;
		foreach ($tasks as $task_time)
		{
			$var=!$var;
  		    print "<tr ".$bc[$var].">";

  		    // Date
  		    print '<td>';
  		    if ($_GET['action'] == 'editline' && $_GET['lineid'] == $task_time->rowid)
  		    {
  		    	print $form->select_date($db->jdate($task_time->task_date),'timeline','','','',"timespent_date");
  		    }
  		    else
  		    {
  		    	print dol_print_date($db->jdate($task_time->task_date),'day');
  		    }
  		    print '</td>';

  		    // User
			$user->id = $task_time->fk_user;
			print '<td>';
			if ($_GET['action'] == 'editline' && $_GET['lineid'] == $task_time->rowid)
			{
				print $form->select_users($user->id,'userid_line');
			}
			else
			{
				$user->nom		= $task_time->name;
				$user->prenom 	= $task_time->firstname;
				print $user->getNomUrl(1);
			}
		    print '</td>';

 		    // Note
  		    print '<td align="left">';
  		    if ($_GET['action'] == 'editline' && $_GET['lineid'] == $task_time->rowid)
  		    {
  		    	print '<textarea name="timespent_note_line" cols="80" rows="'.ROWS_3.'">'.$task_time->note.'</textarea>';
  		    }
  		    else
  		    {
  		    	print dol_nl2br($task_time->note);
  		    }
  		    print '</td>';

  		    // Time spent
  		    print '<td align="right">';
  		    if ($_GET['action'] == 'editline' && $_GET['lineid'] == $task_time->rowid)
  		    {
  		    	print '<input type="hidden" name="old_duration" value="'.$task_time->task_duration.'">';
  		    	print $form->select_duration('new_duration',$task_time->task_duration);
  		    }
  		    else
  		    {
				print convertSecondToTime($task_time->task_duration,'all');
  		    }
  		    print '</td>';

			// Edit and delete icon
			print '<td align="center" valign="middle" width="80">';
			if ($_GET['action'] == 'editline' && $_GET['lineid'] == $task_time->rowid)
  		    {
  		    	print '<input type="hidden" name="lineid" value="'.$_GET['lineid'].'">';
  		    	print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
  		    	print '<br>';
  		    	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'">';
  		    }
  		    else if ($user->rights->projet->creer)
			{
				print '&nbsp;';
				print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$task->id.'&amp;action=editline&amp;lineid='.$task_time->rowid.'">';
				print img_edit();
				print '</a>';

				print '&nbsp;';
				print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$task->id.'&amp;action=deleteline&amp;lineid='.$task_time->rowid.'">';
				print img_delete();
				print '</a>';
			}
			print '</td>';

			print "</tr>\n";
			$total += $task_time->task_duration;
		}
		print '<tr class="liste_total"><td colspan="3" class="liste_total">'.$langs->trans("Total").'</td>';
		print '<td align="right" nowrap="nowrap" class="liste_total">'.convertSecondToTime($total).'</td><td>&nbsp;</td>';
		print '</tr>';

		print "</table>";
		print "</form>";
	}
}

$db->close();

llxFooter();
?>
