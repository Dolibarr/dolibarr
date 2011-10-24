<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");

$langs->load('projects');

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
if (!$user->rights->projet->lire) accessforbidden();


/*
 * Actions
 */
if ($_POST["action"] == 'addtimespent' && $user->rights->projet->creer)
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

if ($_POST["action"] == 'updateline' && ! $_POST["cancel"] && $user->rights->projet->creer)
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

if ($_REQUEST["action"] == 'confirm_delete' && $_REQUEST["confirm"] == "yes" && $user->rights->projet->creer)
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

$project=new Project($db);

llxHeader("",$langs->trans("Task"));

$html = new Form($db);

if ($_GET["id"] > 0)
{
	/*
	 * Fiche projet en mode visu
	 */
	$task = new Task($db);
	$projectstatic = new Project($db);
	$userstatic = new User($db);

	if ($task->fetch($_GET["id"]) >= 0 )
	{
		$result=$projectstatic->fetch($task->fk_project);
		if (! empty($projectstatic->socid)) $projectstatic->societe->fetch($projectstatic->socid);

		// To get role of users
		//$userAccess = $projectstatic->restrictedProjectArea($user); // We allow task affected to user even if a not allowed project
		//$arrayofuseridoftask=$task->getListContactId('internal');

		$head=task_prepare_head($task);

		dol_fiche_head($head, 'time', $langs->trans("Task"),0,'projecttask');

		if ($mesg) print $mesg.'<br>';

		if ($_GET["action"] == 'deleteline')
		{
			$ret=$html->form_confirm($_SERVER["PHP_SELF"]."?id=".$_GET["id"].'&lineid='.$_GET["lineid"],$langs->trans("DeleteATimeSpent"),$langs->trans("ConfirmDeleteATimeSpent"),"confirm_delete",'','',1);
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
		print '</td></tr>';

		// Label
		print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$task->label.'</td></tr>';

		// Project
		print '<tr><td>'.$langs->trans("Project").'</td><td>';
		print $projectstatic->getNomUrl(1);
		print '</td></tr>';

		// Third party
		print '<td>'.$langs->trans("Company").'</td><td>';
		if ($projectstatic->societe->id) print $projectstatic->societe->getNomUrl(1);
		else print '&nbsp;';
		print '</td></tr>';

		print '</table>';

		print '</div>';

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
			print $html->select_date($newdate,'time','','','',"timespent_date");
			print '</td>';

			// Contributor
			print '<td nowrap="nowrap">';
			$contactoftask=$task->getListContactId('internal');
			print img_object('','user');
			print $html->select_users($_POST["userid"]?$_POST["userid"]:$user->id,'userid',0,'',0,'',$contactoftask);
			print '</td>';

			// Note
			print '<td nowrap="nowrap">';
			print '<textarea name="timespent_note" cols="80" rows="'.ROWS_3.'">'.($_POST['timespent_note']?$_POST['timespent_note']:'').'</textarea>';
			print '</td>';

			// Duration
			print '<td nowrap="nowrap" align="right">';
			print $html->select_duration('timespent_duration',($_POST['timespent_duration']?$_POST['timespent_duration']:''));
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
  		    	print $html->select_date($db->jdate($task_time->task_date),'timeline','','','',"timespent_date");
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
				print $html->select_users($user->id,'userid_line');
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
  		    	print $html->select_duration('new_duration',$task_time->task_duration);
  		    }
  		    else
  		    {
				print ConvertSecondToTime($task_time->task_duration,'all');
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
		print '<td align="right" nowrap="nowrap" class="liste_total">'.ConvertSecondToTime($total).'</td><td>&nbsp;</td>';
		print '</tr>';
		
		print "</table>";
		print "</form>";
	}
}

$db->close();

llxFooter();
?>
