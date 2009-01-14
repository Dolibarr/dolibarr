<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/projet/tasks/index.php
 *	\ingroup    project
 *	\brief      Fiche tâches d'un projet
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");

$mode=$_REQUEST["mode"];

$langs->load('projects');

// Security check
if (!$user->rights->projet->lire) accessforbidden();
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;


/*
 * Actions
 */

if ($_POST["action"] == 'createtask' && $user->rights->projet->creer)
{
	$project = new Project($db);

	$result = $project->fetch($_GET["id"]);

	if ($result == 0)
	{
		$task_parent = $_POST["task_parent"]?$_POST["task_parent"]:0;
		$project->CreateTask($user, $_POST["task_name"], $task_parent);

		Header("Location:fiche.php?id=".$project->id);
	}
}

/*
 * View
 */

$form=new Form($db);

$title=$langs->trans("Tasks");
if ($mode == 'mine') $title=$langs->trans("MyTasks");

llxHeader("",$title,"Projet");

/*
 * Card
 */

$h=0;
$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/index.php'.($_GET["mode"]?'?mode='.$_GET["mode"]:'');
$head[$h][1] = $title;
$head[$h][2] = 'tasks';
$h++;

dolibarr_fiche_head($head, 'tasks', $title);

$projet = new Project($db);
$tasksarray=$projet->getTasksArray($_GET["mode"]=='mine'?$user:0);


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Project").'</td>';
print '<td>'.$langs->trans("Task").'</td>';
print '<td>'.$langs->trans("Label").'</td>';
print '<td align="right">'.$langs->trans("TimeSpent").'</td>';
print "</tr>\n";

$level=0;
$j=0;
PLines($j, 0, $tasksarray, $level, true);

print "</table>";
print '</div>';


/*
 * Actions
 */
print '<div class="tabsAction">';
print '<a class="butAction" href="'.DOL_URL_ROOT.'/projet/tasks/fiche.php?action=create">'.$langs->trans('AddTask').'</a>';
print '</div>';



$db->close();

llxFooter('$Date$ - $Revision$');
?>
