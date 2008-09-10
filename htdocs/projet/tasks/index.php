<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 \file       htdocs/projet/tasks/index.php
 \ingroup    project
 \brief      Fiche tâches d'un projet
 \version    $Id$
 */

require("./pre.inc.php");

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
if ($mode == 'mine') $title=$langs->trans("Mytasks");

llxHeader("",$title,"Projet");

/*
 * Card
 */

$h=0;
$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/index.php';
$head[$h][1] = $title;
$head[$h][2] = 'tasks';
$h++;

dolibarr_fiche_head($head, 'tasks', $title);

/* Liste des tâches */

$sql = "SELECT t.rowid, t.title, t.fk_task_parent, t.duration_effective";
$sql .= " , p.rowid as prowid, p.title as ptitle";
$sql .= " FROM ".MAIN_DB_PREFIX."projet_task as t";
$sql .= " , ".MAIN_DB_PREFIX."projet_task_actors as a";
$sql .= " , ".MAIN_DB_PREFIX."projet as p";
$sql .= " WHERE p.rowid = t.fk_projet";
$sql .= " AND a.fk_projet_task = t.rowid";
if ($mode == 'mine') $sql.= " AND a.fk_user = ".$user->id;
$sql .= " ORDER BY p.rowid, t.fk_task_parent";

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	$tasks = array();
	while ($i < $num)
	{
		$obj = $db->fetch_object($resql);
		$tasks[$i][0] = $obj->title;
		$tasks[$i][1] = $obj->fk_task_parent;
		$tasks[$i][2] = $obj->rowid;
		$tasks[$i][3] = $obj->duration_effective;
		$tasks[$i][4] = $obj->ptitle;
		$tasks[$i][5] = $obj->prowid;
		$i++;
	}
	$db->free();
}
else
{
	dolibarr_print_error($db);
}

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Project").'</td>';
print '<td>'.$langs->trans("Task").'</td>';
print '<td align="right">'.$langs->trans("TimeSpent").'</td>';
print "</tr>\n";
$var=true;

PLines($j, 0, $tasks, $level, $var);

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



Function PLines(&$inc, $parent, $lines, &$level, &$var)
{
	global $db;
	global $bc, $langs;
	
	$projectstatic=new Project($db);
	
	for ($i = 0 ; $i < sizeof($lines) ; $i++)
	{
		if ($parent == 0)
		{
		  $level = 0;
		  $var = !$var;
		}

		if ($lines[$i][1] == $parent)
		{
			  print "<tr ".$bc[$var].">\n<td>";
			  $projectstatic->id=$lines[$i][5];
			  $projectstatic->ref=$lines[$i][4];
			  print $projectstatic->getNomUrl(1);
			  print "</td><td>\n";
		
			  for ($k = 0 ; $k < $level ; $k++)
			  {
			  	print "&nbsp;&nbsp;&nbsp;";
			  }
		
			  print '<a href="task.php?id='.$lines[$i][2].'">'.$lines[$i][0]."</a></td>\n";
		
			  $heure = intval($lines[$i][3]);
			  $minutes = (($lines[$i][3] - $heure) * 60);
			  $minutes = substr("00"."$minutes", -2);
		
			  print '<td align="right">'.$heure."&nbsp;h&nbsp;".$minutes."</td>\n";
			  print "</tr>\n";
			  $inc++;
			  $level++;
			  PLines($inc, $lines[$i][2], $lines, $level, $var);
			  $level--;
		}
		else
		{
	 		//$level--;
		}
	}
}
?>
