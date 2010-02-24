<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/projet/tasks/task.php
 *	\ingroup    projet
 *	\brief      Fiche taches d'un projet
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");

if (!$user->rights->projet->lire) accessforbidden();

/*
 * Actions
 */
if ($_POST["action"] == 'updateline' && ! $_POST["cancel"] && $user->rights->projet->creer)
{
	
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

llxHeader("",$langs->trans("Task"));

$html = new Form($db);

if ($_GET["id"] > 0)
{
	/*
	 * Fiche projet en mode visu
	 *
	 */
	$task = new Task($db);
	$projectstatic = new Project($db);
	$userstatic = new User($db);

	if ($task->fetch($_GET["id"]) >= 0 )
	{
		$result=$projectstatic->fetch($task->fk_project);
		if (! empty($projectstatic->socid)) $projectstatic->societe->fetch($projectstatic->socid);
		
		// To verify role of users
		$userAccess = $projectstatic->restrictedProjectArea($user);

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
		 * Actions
		 */
		print '<div class="tabsAction">';

		// Add time spent
		if ($user->rights->projet->creer && $userAccess)
		{
			print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$task->id.'&amp;action=addtime">'.$langs->trans('NewTimeSpent').'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('NewTimeSpent').'</a>';
		}

		print '</div>';

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
		
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("By").'</td>';
		print '<td>'.$langs->trans("Date").'</td>';
		print '<td>'.$langs->trans("Note").'</td>';
		print '<td align="right">'.$langs->trans("TimeSpent").'</td>';
		print '<td colspan="2">&nbsp;</td>';
		print "</tr>\n";

		foreach ($tasks as $task_time)
		{
			$var=!$var;
  		    print "<tr ".$bc[$var].">";
  		    
  		    // User
			$user->id		= $task_time->fk_user;
		    $user->nom		= $task_time->name;
		    $user->prenom 	= $task_time->firstname;
		    print '<td>'.$user->getNomUrl(1).'</td>';

  		    // Date
  		    print '<td>'.dol_print_date($db->jdate($task_time->task_date),'%A').' '.dol_print_date($db->jdate($task_time->task_date),'daytext').'</td>';

		    // Note
		    print '<td>'.dol_nl2br($task_time->note).'</td>';
  		    
  		    // Time spent
		    $heure = intval($task_time->task_duration);
			$minutes = round((($task_time->task_duration - $heure) * 60),0);
			$minutes = substr("00"."$minutes", -2);
			print '<td align="right">'.$heure."&nbsp;h&nbsp;".$minutes."</td>\n";
			
			// Edit and delete icon
			print '<td align="right" nowrap>';
			if ($user->rights->projet->creer && $userAccess)
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
		}

		print "</table>";

	}
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
