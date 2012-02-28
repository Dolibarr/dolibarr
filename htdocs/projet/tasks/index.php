<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006-2010 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/projet/tasks/index.php
 *	\ingroup    project
 *	\brief      List all task of a project
 */

require ("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/task.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/project.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");

$langs->load('projects');
$langs->load('users');

$id=GETPOST('id','int');

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
if (!$user->rights->projet->lire) accessforbidden();

$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page = isset($_GET["page"])? $_GET["page"]:$_POST["page"];
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;

$mine = $_REQUEST['mode']=='mine' ? 1 : 0;


/*
 * View
 */

$form=new Form($db);
$projectstatic = new Project($db);
$taskstatic = new Task($db);

$title=$langs->trans("Activities");
if ($mine) $title=$langs->trans("MyActivities");

llxHeader("",$title,"Projet");

if ($id)
{
	$projectstatic->fetch($id);
	$projectstatic->societe->fetch($projectstatic->societe->id);
}

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $num);

// Show description of content
if ($mine) print $langs->trans("MyProjectsDesc").'<br><br>';
else
{
	if ($user->rights->projet->all->lire && ! $socid) print $langs->trans("ProjectsDesc").'<br><br>';
	else print $langs->trans("ProjectsPublicDesc").'<br><br>';
}

// Get list of project id allowed to user
$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,$mine,1,$socid);
// Get list of tasks in tasksarray and taskarrayfiltered
// We need all tasks (even not limited to a user because a task to user
// can have a parent that is not affected to him).
$tasksarray=$taskstatic->getTasksArray(0, 0, $projectstatic->id, $socid, 0);
// We load also tasks limited to a particular user
$tasksrole=($mine ? $taskstatic->getUserRolesForProjectsOrTasks(0,$user,$projectstatic->id,0) : '');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Project").'</td>';
print '<td width="80">'.$langs->trans("RefTask").'</td>';
print '<td>'.$langs->trans("LabelTask").'</td>';
print '<td align="center">'.$langs->trans("DateStart").'</td>';
print '<td align="center">'.$langs->trans("DateEnd").'</td>';
print '<td align="right">'.$langs->trans("Progress").'</td>';
print '<td align="right">'.$langs->trans("TimeSpent").'</td>';
print "</tr>\n";
// Show all lines in taskarray (recursive function to go down on tree)
$j=0; $level=0;
$nboftaskshown=projectLinesa($j, 0, $tasksarray, $level, true, 1, $tasksrole, $projectsListId);
print "</table>";

print '</div>';

/*
 * Actions
 */
if ($user->rights->projet->creer)
{
	print '<div class="tabsAction">';
	print '<a class="butAction" href="'.DOL_URL_ROOT.'/projet/tasks.php?action=create">'.$langs->trans('AddTask').'</a>';
	print '</div>';
}


llxFooter();

$db->close();
?>
