<?php
/* Copyright (C) 2010-2012 Regis Houssin  <regis@dolibarr.fr>
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

$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$mine = $_REQUEST['mode']=='mine' ? 1 : 0;
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects
$id = GETPOST('id','int');
$ref= GETPOST('ref', 'alpha');
$withproject=GETPOST('withproject','int');
$project_ref = GETPOST('project_ref','alpha');

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
if (!$user->rights->projet->lire) accessforbidden();
//$result = restrictedArea($user, 'projet', $id, '', 'task'); // TODO ameliorer la verification

$object = new Task($db);
$projectstatic = new Project($db);

if ($id > 0 || ! empty($ref))
{
	if ($object->fetch($id,$ref) > 0)
	{
		$projectstatic->fetch($object->fk_project);
		if (! empty($projectstatic->socid)) $projectstatic->societe->fetch($projectstatic->socid);
	}
	else
	{
		dol_print_error($db);
	}
}


// Retreive First Task ID of Project if withprojet is on to allow project prev next to work
if (! empty($project_ref) && ! empty($withproject))
{
	if ($projectstatic->fetch(0,$project_ref) > 0)
	{
		$tasksarray=$object->getTasksArray(0, 0, $projectstatic->id, $socid, 0);
		if (count($tasksarray) > 0)
		{
			$id=$tasksarray[0]->id;
			$object->fetch($id);
		}
		else
		{
			Header("Location: ".DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.(empty($mode)?'':'&mode='.$mode));
		}
	}
}


/*
 * Actions
 */

if ($action == 'setnote_public' && $user->rights->ficheinter->creer)
{
    $result=$object->update_note_public(dol_html_entity_decode(GETPOST('note_public'), ENT_QUOTES));
    if ($result < 0) dol_print_error($db,$object->error);
}

else if ($action == 'setnote_private' && $user->rights->ficheinter->creer)
{
    $result=$object->update_note(dol_html_entity_decode(GETPOST('note_private'), ENT_QUOTES));
    if ($result < 0) dol_print_error($db,$object->error);
}



/*
 * View
 */

llxHeader();

$form = new Form($db);
$userstatic = new User($db);

$now=dol_now();

if ($object->id > 0)
{
	$userWrite  = $projectstatic->restrictedProjectArea($user,'write');

	if (! empty($withproject))
	{
		// Tabs for project
		$tab='tasks';
		$head=project_prepare_head($projectstatic);
		dol_fiche_head($head, $tab, $langs->trans("Project"),0,($projectstatic->public?'projectpub':'project'));

		$param=($mode=='mine'?'&mode=mine':'');

		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="30%">';
		print $langs->trans("Ref");
		print '</td><td>';
		// Define a complementary filter for search of next/prev ref.
		if (! $user->rights->projet->all->lire)
		{
		    $projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,$mine,0);
		    $projectstatic->next_prev_filter=" rowid in (".(count($projectsListId)?join(',',array_keys($projectsListId)):'0').")";
		}
		print $form->showrefnav($projectstatic,'project_ref','',1,'ref','ref','',$param.'&withproject=1');
		print '</td></tr>';

		// Project
		print '<tr><td>'.$langs->trans("Label").'</td><td>'.$projectstatic->title.'</td></tr>';

		// Company
		print '<tr><td>'.$langs->trans("Company").'</td><td>';
		if (! empty($projectstatic->societe->id)) print $projectstatic->societe->getNomUrl(1);
		else print '&nbsp;';
		print '</td>';
		print '</tr>';

		// Visibility
		print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
		if ($projectstatic->public) print $langs->trans('SharedProject');
		else print $langs->trans('PrivateProject');
		print '</td></tr>';

		// Statut
		print '<tr><td>'.$langs->trans("Status").'</td><td>'.$projectstatic->getLibStatut(4).'</td></tr>';

		print '</table>';

		dol_fiche_end();

		print '<br>';
	}

	$head = task_prepare_head($object);
	dol_fiche_head($head, 'task_notes', $langs->trans('Task'), 0, 'projecttask');

	print '<table class="border" width="100%">';

	$param=(GETPOST('withproject')?'&withproject=1':'');
	$linkback=GETPOST('withproject')?'<a href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.'">'.$langs->trans("BackToList").'</a>':'';

	// Ref
	print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td>';
	if (empty($withproject) || empty($projectstatic->id))
	{
	    $projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,$mine,1);
	    $object->next_prev_filter=" fk_projet in (".$projectsListId.")";
	}
	else $object->next_prev_filter=" fk_projet = ".$projectstatic->id;
	print $form->showrefnav($object,'id',$linkback,1,'rowid','ref','',$param);
	print '</td></tr>';

	// Label
	print '<tr><td>'.$langs->trans("Label").'</td><td>'.$object->label.'</td></tr>';

	// Project
	if (empty($withproject))
	{
		print '<tr><td>'.$langs->trans("Project").'</td><td colspan="3">';
		print $projectstatic->getNomUrl(1);
		print '</td></tr>';

		// Third party
		print '<tr><td>'.$langs->trans("Company").'</td><td>';
		if ($projectstatic->societe->id > 0) print $projectstatic->societe->getNomUrl(1);
		else print'&nbsp;';
		print '</td></tr>';
	}

	print "</table>";

	print '<br>';

	$colwidth=30;
	$permission=($user->rights->projet->creer || $user->rights->projet->all->creer);
    $moreparam=$param;
	include(DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php');

	dol_fiche_end();
}


llxFooter();
$db->close();
?>
