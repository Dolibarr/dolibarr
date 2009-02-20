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
require_once(DOL_DOCUMENT_ROOT."/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/html.formother.class.php");

$projetid='';
$projetid=isset($_REQUEST["id"])?$_REQUEST["id"]:$_POST["projetid"];

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
		if (empty($_POST['task_parent']))
		{
			$mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentities("ChildOfTaks"));
			$_GET["action"]='create';
			$error++;
		}

		if (! $error)
		{
			$tmparray=split('_',$_POST['task_parent']);
			$projectid=$tmparray[0];
			$task_parent=$tmparray[1];
			if (empty($task_parent)) $task_parent=0;	// If task_parent is ''

			//print $_POST['task_parent'].'-'.$projectid.'-'.$task_parent;exit;
			$project = new Project($db);
			$result = $project->fetch($projectid);

			$result=$project->CreateTask($user, $_POST["task_name"], $task_parent, $_POST["userid"]);
		}
	}

	if (! $error)
	{
		if (empty($projetid))
		{
			Header("Location: ".DOL_URL_ROOT.'/projet/tasks/index.php'.(empty($_REQUEST["mode"])?'':'?mode='.$_REQUEST["mode"]));
			exit;
		}
		else
		{
			Header("Location: ".DOL_URL_ROOT.'/projet/tasks/fiche.php?id='.$projetid);
			exit;
		}
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

					$date = dol_mktime(12,0,0,$_POST["$id"."month"],$_POST["$id"."day"],$_POST["$id"."year"]);
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
$htmlother=new FormOther($db);

llxHeader("",$langs->trans("Tasks"),"Tasks");

$projet = new Project($db);

$id = $_REQUEST['id'];
$ref= $_GET['ref'];
if ($id > 0 || ! empty($ref))
{
	$projet->fetch($_REQUEST["id"],$_GET["ref"]);
	if ($projet->societe->id > 0)  $result=$projet->societe->fetch($projet->societe->id);
	if ($projet->user_resp_id > 0) $result=$projet->fetch_user($projet->user_resp_id);
}

if ($_GET["action"] == 'create' && $user->rights->projet->creer)
{
	print_fiche_titre($langs->trans("NewTask"));

	if ($mesg) print '<div class="error">'.$mesg.'</div>';

	print '<form action="fiche.php" method="post">';
	print '<input type="hidden" name="action" value="createtask">';
	if ($_GET['id'])   print '<input type="hidden" name="id" value="'.$_GET['id'].'">';
	if ($_GET['mode']) print '<input type="hidden" name="mode" value="'.$_GET['mode'].'">';

	print '<table class="border" width="100%">';

	print '<tr><td>'.$langs->trans("NewTask").'</td><td>';
	print '<input type="text" size="25" name="task_name" class="flat">';
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("ChildOfTaks").'</td><td>';
	print $htmlother->selectProjectTasks($projet->id, 'task_parent', $user->admin?0:1, 0, 1);
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("AffectedTo").'</td><td>';
	print $form->select_users($user->id,'userid',1);
	print '</td></tr>';

	$project=new Project($db);
	$tasksarray=$project->getTasksArray(0, $user, 1);
	print '<tr><td colspan="2" align="center">';
	if (sizeof($tasksarray))
	{
		print '<input type="submit" class="button" name="add" value="'.$langs->trans("Add").'">';
		print ' &nbsp; &nbsp; ';
	}
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
	$tab='tasks';
	if ($_REQUEST["mode"]=='mine') $tab='mytasks';

	$head=project_prepare_head($projet);
	dol_fiche_head($head, $tab, $langs->trans("Project"));

	$param=($_REQUEST["mode"]=='mine'?'&mode=mine':'');

	print '<form name="addtime" method="POST" action="fiche.php?id='.$projet->id.'">';

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td width="30%">';
	print $langs->trans("Ref");
	print '</td><td>';
	print $form->showrefnav($projet,'ref','',1,'ref','ref','',$param);
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("Label").'</td><td>'.$projet->title.'</td></tr>';

	print '<tr><td>'.$langs->trans("Company").'</td><td>';
	if (! empty($projet->societe->id)) print $projet->societe->getNomUrl(1);
	else print '&nbsp;';
	print '</td>';
	print '</tr>';

	// Project leader
	print '<tr><td>'.$langs->trans("OfficerProject").'</td><td>';
	if ($projet->user->id) print $projet->user->getNomUrl(1);
	else print $langs->trans('SharedProject');
	print '</td></tr>';

	print '</table>';

	print '<input type="hidden" name="action" value="addtime">';

	print '</form>';
	print '</div>';


	$tasksarray=$projet->getTasksArray($_REQUEST["mode"]=='mine'?$user:0, 0);


	/*
	 * Actions
	 */
	print '<div class="tabsAction">';

	if ($user->rights->projet->creer)
	{
		if (empty($projet->user_resp_id) || $projet->user_resp_id == -1 || $projet->user_resp_id == $user->id)
		{
			print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$projet->id.'&action=create'.$param.'">'.$langs->trans('AddTask').'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('AddTask').'</a>';
		}
	}

	print '</div>';

	print '<br>';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	if ($projectstatic->id) print '<td>'.$langs->trans("Project").'</td>';
	print '<td>'.$langs->trans("RefTask").'</td>';
	print '<td>'.$langs->trans("LabelTask").'</td>';
	print '<td align="right">'.$langs->trans("TimeSpent").'</td>';
	print "</tr>\n";
	$j=0;
	$nboftaskshown=PLines($j, 0, $tasksarray, $level, true, 0);
	print "</table>";
	print '</div>';

	if ($nboftaskshown < sizeof($tasksarray))
	{
		clean_orphelins($db);
	}
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
