<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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

$mode=$_REQUEST["mode"];

$projetid='';
$projetid=isset($_GET["id"])?$_GET["id"]:$_POST["projetid"];

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'projet', $projetid);

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
		exit;
	}
}

if ($_POST["action"] == 'addtime' && $user->rights->projet->creer)
{
	foreach ($_POST as $key => $post)
	{
  		//$pro->CreateTask($user, $_POST["task_name"]);
  		if (substr($key,0,4) == 'task')
  		{
		  	if ($post > 0)
		  	{
				$post=intval($post)+(($post-intval($post))*(1+2/3));
				$post=price2num($post);

				$id = ereg_replace("task","",$key);

				$task=new Task($db);
				$task->fetch($id);

				$project = new Project($db);
				$result = $project->fetch($task->fk_projet);

		  		$date = dolibarr_mktime(12,0,0,$_POST["$id"."month"],$_POST["$id"."day"],$_POST["$id"."year"]);
		  		$project->TaskAddTime($user, $id , $post, $date);
		  	}
		  	else
		  	{
		  		if ($post != '') $mesg='<div class="error">'.$langs->trans("ErrorBadValue").'</div>';
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

$projet = new Project($db);
if ($_GET["id"])
{
	$projet->fetch($_GET["id"]);
	$projet->societe->fetch($projet->societe->id);
}


/*
 * Fiche projet en mode visu
 *
 */

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $num);

if ($mesg) print $mesg;

$tasksrole=$projet->getTasksRoleForUser($user);
$tasksarray=$projet->getTasksArray(0,0);
//var_dump($tasksarray);

print '<form name="addtime" method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$projet->id.'">';
print '<input type="hidden" name="action" value="addtime">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Project").'</td>';
print '<td>'.$langs->trans("RefTask").'</td>';
print '<td>'.$langs->trans("LabelTask").'</td>';
print '<td align="right">'.$langs->trans("TimeSpent").'</td>';
print '<td colspan="2">'.$langs->trans("AddDuration").'</td>';
print "</tr>\n";
PLinesb($j, 0, $tasksarray, $level, $tasksrole);
print '</form>';


print "</table>";
print '</div>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
