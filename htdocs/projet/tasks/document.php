<?php
/* Copyright (C) 2010-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2006-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012      Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2013      Cédric Salvador      <csalvador@gpcsolutions.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *	\file       htdocs/projet/tasks/document.php
 *	\ingroup    project
 *	\brief      Page de gestion des documents attachees a une tache d'un projet
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';


$langs->load('projects');
$langs->load('other');

$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$mine = $_REQUEST['mode']=='mine' ? 1 : 0;
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects
$id = GETPOST('id','int');
$ref= GETPOST('ref','alpha');
$withproject=GETPOST('withproject','int');
$project_ref = GETPOST('project_ref','alpha');

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
//$result=restrictedArea($user,'projet',$id,'');
if (!$user->rights->projet->lire) accessforbidden();

// Get parameters
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";

$object = new Task($db);
$projectstatic = new Project($db);

/*
 * Actions
 */

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
			header("Location: ".DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.($withproject?'&withproject=1':'').(empty($mode)?'':'&mode='.$mode));
			exit;
		}
	}
}

if ($id > 0 || ! empty($ref))
{
	if ($object->fetch($id,$ref) > 0)
	{
		$projectstatic->fetch($object->fk_project);

		if (! empty($projectstatic->socid)) {
			$projectstatic->fetch_thirdparty();
		}

		$object->project = clone $projectstatic;

		$upload_dir = $conf->projet->dir_output.'/'.dol_sanitizeFileName($projectstatic->ref).'/'.dol_sanitizeFileName($object->ref);
	}
	else
	{
		dol_print_error($db);
	}
}

include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_pre_headers.tpl.php';


/*
 * View
 */

$form = new Form($db);

llxHeader('',$langs->trans('Task'));

if ($object->id > 0)
{
	$projectstatic->fetch_thirdparty();

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
			$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,0,0);
			$projectstatic->next_prev_filter=" rowid in (".(count($projectsListId)?join(',',array_keys($projectsListId)):'0').")";
		}
		print $form->showrefnav($projectstatic,'project_ref','',1,'ref','ref','',$param.'&withproject=1');
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("Label").'</td><td>'.$projectstatic->title.'</td></tr>';

		print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
		if (! empty($projectstatic->thirdparty->id)) print $projectstatic->thirdparty->getNomUrl(1);
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

	   	// Date start
		print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
		print dol_print_date($projectstatic->date_start,'day');
		print '</td></tr>';

		// Date end
		print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
		print dol_print_date($projectstatic->date_end,'day');
		print '</td></tr>';

		print '</table>';

		dol_fiche_end();
	}

	$head = task_prepare_head($object);
	dol_fiche_head($head, 'task_document', $langs->trans("Task"), 0, 'projecttask');

	$param=(GETPOST('withproject')?'&withproject=1':'');
	$linkback=GETPOST('withproject')?'<a href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.'">'.$langs->trans("BackToList").'</a>':'';

	// Files list constructor
	$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
	$totalsize=0;
	foreach($filearray as $key => $file)
	{
		$totalsize+=$file['size'];
	}

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td width="30%">';
	print $langs->trans("Ref");
	print '</td><td colspan="3">';
	if (empty($withproject) || empty($projectstatic->id))
	{
		$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,0,1);
		$object->next_prev_filter=" fk_projet in (".$projectsListId.")";
	}
	else $object->next_prev_filter=" fk_projet = ".$projectstatic->id;
	print $form->showrefnav($object,'ref',$linkback,1,'ref','ref','',$param);
	print '</td>';
	print '</tr>';

	// Label
	print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$object->label.'</td></tr>';

	// Project
	if (empty($withproject))
	{
		print '<tr><td>'.$langs->trans("Project").'</td><td colspan="3">';
		print $projectstatic->getNomUrl(1);
		print '</td></tr>';

		// Third party
		print '<td>'.$langs->trans("ThirdParty").'</td><td colspan="3">';
		if ($projectstatic->thirdparty->id) print $projectstatic->thirdparty->getNomUrl(1);
		else print '&nbsp;';
		print '</td></tr>';
	}

	// Files infos
	print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

	print "</table>\n";

	dol_fiche_end();

	print '<br>';

	$param='';
	if ($withproject) $param .= '&withproject=1';
	$modulepart = 'project_task';
	$permission = $user->rights->projet->creer;
	$relativepathwithnofile=dol_sanitizeFileName($projectstatic->ref).'/'.dol_sanitizeFileName($object->ref).'/';
	include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
}
else
{
	header('Location: index.php');
	exit;
}


llxFooter();

$db->close();
