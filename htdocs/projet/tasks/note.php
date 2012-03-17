<?php
/* Copyright (C) 2010 Regis Houssin  <regis@dolibarr.fr>
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
 *	\file       htdocs/projet/tasks/note.php
 *	\ingroup    project
 *	\brief      Page to show information on a task
 */

require ("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/task.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/project.lib.php");

$langs->load('projects');

$action=GETPOST('action');
$mine = $_REQUEST['mode']=='mine' ? 1 : 0;
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects
$id = GETPOST('id','int');
$ref= GETPOST('ref');
$withproject=GETPOST('withproject');

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
if (!$user->rights->projet->lire) accessforbidden();
//$result = restrictedArea($user, 'projet', $id, '', 'task'); // TODO ameliorer la verification



/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($action == 'update_public' && $user->rights->projet->creer)
{
	$task = new Task($db);
	$task->fetch($id);

	$db->begin();

	$res=$task->update_note_public(dol_html_entity_decode(GETPOST('note_public'), ENT_QUOTES));
	if ($res < 0)
	{
		$mesg='<div class="error">'.$task->error.'</div>';
		$db->rollback();
	}
	else
	{
		$db->commit();
	}
}

if ($action == 'update_private' && $user->rights->projet->creer)
{
	$task = new Task($db);
	$task->fetch($id);

	$db->begin();

	$res=$task->update_note(dol_html_entity_decode(GETPOST('note_private'), ENT_QUOTES));
	if ($res < 0)
	{
		$mesg='<div class="error">'.$task->error.'</div>';
		$db->rollback();
	}
	else
	{
		$db->commit();
	}
}


/*
 * View
 */

llxHeader();

$form = new Form($db);
$project = new Project($db);
$task = new Task($db);
$userstatic = new User($db);

$now=dol_now();

if ($id > 0 || ! empty($ref))
{
	if ($task->fetch($id, $ref) > 0)
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

    		// Project
    		print '<tr><td>'.$langs->trans("Label").'</td><td>'.$project->title.'</td></tr>';

    		// Company
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

		$head = task_prepare_head($task);
		dol_fiche_head($head, 'note', $langs->trans('Task'), 0, 'projecttask');

		print '<table class="border" width="100%">';

		$param=(GETPOST('withproject')?'&withproject=1':'');
		$linkback=GETPOST('withproject')?'<a href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$project->id.'">'.$langs->trans("BackToList").'</a>':'';

		// Ref
		print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td>';
		if (! GETPOST('withproject') || empty($project->id))
		{
		    $projectsListId = $project->getProjectsAuthorizedForUser($user,$mine,1);
		    $task->next_prev_filter=" fk_projet in (".$projectsListId.")";
		}
		else $task->next_prev_filter=" fk_projet = ".$project->id;
		print $form->showrefnav($task,'id',$linkback,1,'rowid','ref','',$param);
		print '</td></tr>';

		// Label
		print '<tr><td>'.$langs->trans("Label").'</td><td>'.$task->label.'</td></tr>';

		// Project
		if (empty($withproject))
		{
    		print '<tr><td>'.$langs->trans("Project").'</td><td colspan="3">';
    		print $project->getNomUrl(1);
    		print '</td></tr>';

    		// Third party
    		print '<tr><td>'.$langs->trans("Company").'</td><td>';
    		if ($project->societe->id > 0) print $project->societe->getNomUrl(1);
    		else print'&nbsp;';
    		print '</td></tr>';
		}

		// Note public
		print '<tr><td valign="top">'.$langs->trans("NotePublic").'</td>';
		print '<td valign="top" colspan="3">';
		if ($action == 'edit')
		{
			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$task->id.'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="update_public">';
			print '<textarea name="note_public" cols="80" rows="8">'.$task->note_public."</textarea><br>";
			print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
			print '</form>';
		}
		else
		{
			print ($task->note_public?nl2br($task->note_public):"&nbsp;");
		}
		print "</td></tr>";

		// Note private
		if (! $user->societe_id)
		{
			print '<tr><td valign="top">'.$langs->trans("NotePrivate").'</td>';
			print '<td valign="top" colspan="3">';
			if ($_GET["action"] == 'edit')
			{
				print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$task->id.'">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="update_private">';
				print '<textarea name="note_private" cols="80" rows="8">'.$task->note_private."</textarea><br>";
				print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
				print '</form>';
			}
			else
			{
				print ($task->note_private?nl2br($task->note_private):"&nbsp;");
			}
			print "</td></tr>";
		}

		print "</table>";

		dol_fiche_end();

		/*
		 * Actions
		 */
		print '<div class="tabsAction">';

		if (($user->rights->projet->creer || $user->rights->projet->all->creer) && $_GET['action'] <> 'edit')
		{
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$task->id.'&amp;action=edit">'.$langs->trans('Modify').'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('Modify').'</a>';
		}

		print '</div>';
	}
}

llxFooter();

$db->close();
?>
