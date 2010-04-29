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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/projet/activity/list.php
 *	\ingroup    projet
 *	\brief      List activities of tasks
 *	\version    $Id$
 */

require ("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/projet/tasks/task.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/date.lib.php");

$langs->load('projects');

$mode=$_REQUEST["mode"];

$projectid='';
$projectid=isset($_GET["id"])?$_GET["id"]:$_POST["projectid"];

// Security check
$socid=0;
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'projet', $projectid);

/*
 * Actions
 */

if ($_POST["action"] == 'createtask' && $user->rights->projet->creer)
{
	$task = new Task($db);

	$task->fk_task_parent = $_POST["task_parent"]?$_POST["task_parent"]:0;
	$task->label = $_POST["task_name"];

	$result = $task->create($user);

	if ($result == 0)
	{
		Header("Location:fiche.php?id=".$projectid);
		exit;
	}
}

if ($_POST["action"] == 'addtime' && $user->rights->projet->creer)
{
	// TODO probleme si que des minutes
	foreach ($_POST as $key => $time)
	{
		if (substr($key,-4) == 'hour')
  		{
  			if ($time > 0)
		  	{
				$id = str_replace("hour","",$key);

				$task = new Task($db);
				$task->fetch($id);

		  		$task->timespent_fk_user = $user->id;
				$task->timespent_duration = $_POST[$id."hour"]*60*60;	// We store duration in seconds
		  		$task->timespent_duration+= $_POST[$id."min"]*60;		// We store duration in seconds
				$task->timespent_date = dol_mktime(12,0,0,$_POST["$id"."month"],$_POST["$id"."day"],$_POST["$id"."year"]);
	
		  		$task->addTimeSpent($user);
		  	}
		  	else
		  	{
		  		if ($time != '') $mesg='<div class="error">'.$langs->trans("ErrorBadValue").'</div>';
		  	}
		}
	}
}


/*
 * View
 */

$form=new Form($db);

$title=$langs->trans("TimeSpent");
if ($mode == 'mine') $title=$langs->trans("MyTimeSpent");

llxHeader("",$title,"");

$project = new Project($db);
$task = new Task($db);

if ($_GET["id"])
{
	$project->fetch($_GET["id"]);
	$project->societe->fetch($project->societe->id);
}

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $num);

if ($mesg) print $mesg;

$tasksarray=$task->getTasksArray(0,0,$project->id,$socid);
$projectsrole=$task->getUserRolesForProjectsOrTasks($user,0,$project->id,0);
//var_dump($tasksarray);
//var_dump($projectsrole);

print '<form name="addtime" method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$project->id.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="addtime">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Project").'</td>';
print '<td>'.$langs->trans("RefTask").'</td>';
print '<td>'.$langs->trans("LabelTask").'</td>';
print '<td align="right">'.$langs->trans("TimeSpent").'</td>';
print '<td colspan="2">'.$langs->trans("AddDuration").'</td>';
print "</tr>\n";
PLinesb($j, 0, $tasksarray, $level, $projectsrole);
print '</form>';


print "</table>";
print '</div>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
