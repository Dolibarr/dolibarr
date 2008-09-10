<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	$error=0;

	if (empty($_POST["cancel"]))
	{
		$tmparray=split('_',$_POST['task_parent']);
		$projectid=$tmparray[0];
		$task_parent=$tmparray[1];
		if (empty($task_parent)) $task_parent=0;	// If task_parent is ''
		
		//print $_POST['task_parent'].'-'.$projectid.'-'.$task_parent;exit;
		$project = new Project($db);
		$result = $project->fetch($projectid);
		
		$result=$project->CreateTask($user, $_POST["task_name"], $task_parent);
	}

	if (! $error)
	{
		Header("Location: ".DOL_URL_ROOT.'/projet/tasks/index.php');
		exit;
	}
}

if ($_POST["action"] == 'addtime' && $user->rights->projet->creer)
{
	$project = new Project($db);
	$result = $project->fetch($_GET["id"]);

	if ($result == 0)
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

					$date = dolibarr_mktime(12,0,0,$_POST["$id"."month"],$_POST["$id"."day"],$_POST["$id"."year"]);
					$project->TaskAddTime($user, $id , $post, $date);
				}
			}
		}

		Header("Location:fiche.php?id=".$project->id);
		exit;
	}
}


/*
 * View
 */
$form=new Form($db);

llxHeader("",$langs->trans("Tasks"),"Tasks");

$projet = new Project($db);
if ($_GET["id"])
{
	$projet->fetch($_GET["id"]);
	$projet->societe->fetch($projet->societe->id);
}

if ($_GET["action"] == 'create' && $user->rights->projet->creer)
{
	print_titre($langs->trans("NewTask"));
	print '<br>';

	$tasksarray=$projet->getTasksArray();

	if ($mesg) print $mesg;

	print '<form action="fiche.php" method="post">';
	print '<input type="hidden" name="action" value="createtask">';

	print '<table class="border" width="100%">';

	print '<tr><td>'.$langs->trans("NewTask").'</td><td colspan="3">';
	print '<input type="text" size="25" name="task_name" class="flat">&nbsp;';
	if ($tasksarray)
	{
		print ' &nbsp; '.$langs->trans("ChildOfTaks").' &nbsp; ';

		print '<select class="flat" name="task_parent">';
		print '<option value="0" selected="true">&nbsp;</option>';
		PLineSelect($j, 0, $tasksarray, $level);
		print '</select>';
	}
	print '</td></tr>';

	print '<tr><td colspan="4" align="center">';
	print '<input type="submit" class="button" name="add" value="'.$langs->trans("Add").'">';
	print ' &nbsp; &nbsp; ';
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

	$head=project_prepare_head($projet);
	dolibarr_fiche_head($head, 'tasks', $langs->trans("Project"));


	print '<table class="border" width="100%">';

	print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td>'.$projet->ref.'</td></tr>';
	print '<tr><td>'.$langs->trans("Label").'</td><td>'.$projet->title.'</td></tr>';

	print '<td>'.$langs->trans("Company").'</td><td>';
	if (! empty($projet->societe->id)) print $projet->societe->getNomUrl(1);
	else print '&nbsp;';
	print '</td></tr>';

	$tasksrole=$projet->getTasksRoleForUser($user);

	$tasksarray=$projet->getTasksArray();

	print '</table>';
	print '<br>';

	print '<form name="addtime" method="POST" action="fiche.php?id='.$projet->id.'">';
	print '<input type="hidden" name="action" value="addtime">';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Project").'</td>';
	print '<td>'.$langs->trans("RefTask").'</td>';
	print '<td>'.$langs->trans("LabelTask").'</td>';
	print '<td align="right">'.$langs->trans("TimeSpent").'</td>';
	print "</tr>\n";
	PLines($j, 0, $tasksarray, $level, $tasksrole);
	print '</form>';


	print "</table>";
	print '</div>';


	/*
	 * Actions
	 */
	print '<div class="tabsAction">';
	print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$projet->id.'&amp;action=create">'.$langs->trans('AddTask').'</a>';
	print '</div>';
}

$db->close();

llxFooter('$Date$ - $Revision$');


function PLines(&$inc, $parent, $lines, &$level, $tasksrole)
{
	global $user, $bc, $langs;

	$projectstatic = new Project($db);
	
	$var=true;

	for ($i = 0 ; $i < sizeof($lines) ; $i++)
	{
		if ($parent == 0)
		$level = 0;

		if ($lines[$i]->fk_parent == $parent)
		{
			$var = !$var;
			print "<tr $bc[$var]>\n";

			print "<td>";
			$projectstatic->id=$lines[$i]->projectid;
			$projectstatic->ref=$lines[$i]->projectref;
			print $projectstatic->getNomUrl(1);
			print "</td>";
				
			print "<td>".$lines[$i]->id."</td>";
				
			print "<td>";
			for ($k = 0 ; $k < $level ; $k++)
			{
				print "&nbsp;&nbsp;&nbsp;";
			}

			print '<a href="task.php?id='.$lines[$i]->id.'">'.$lines[$i]->title."</a></td>\n";

			$heure = intval($lines[$i]->duration);
			$minutes = round((($lines[$i]->duration - $heure) * 60),0);
			$minutes = substr("00"."$minutes", -2);

			print '<td align="right">'.$heure."&nbsp;h&nbsp;".$minutes."</td>\n";

			print "</tr>\n";
			$inc++;
			$level++;
			if ($lines[$i]->id) PLines($inc, $lines[$i]->id, $lines, $level, $tasksrole);
			$level--;
		}
		else
		{
			//$level--;
		}
	}
}


function PLineSelect(&$inc, $parent, $lines, &$level)
{
	global $langs;
	
	for ($i = 0 ; $i < sizeof($lines) ; $i++)
	{
		if ($parent == 0) $level = 0;

		if ($lines[$i]->fk_parent == $parent)
		{
			$var = !$var;
			print '<option value="'.$lines[$i]->projectid.'_'.$lines[$i]->id.'">';
			print $langs->trans("Project").' '.$lines[$i]->projectref;
			if ($lines[$i]->id) print ' > ';
			for ($k = 0 ; $k < $level ; $k++)
			{
				print "&nbsp;&nbsp;&nbsp;";
			}

			print $lines[$i]->title."</option>\n";

			$inc++;
			$level++;
			if ($lines[$i]->id) PLineSelect($inc, $lines[$i]->id, $lines, $level);
			$level--;
		}
	}
}
?>
