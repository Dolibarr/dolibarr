<?php
/* Copyright (C) 2010 Regis Houssin <regis@dolibarr.fr>
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
 *	\file       htdocs/projet/tasks/document.php
 *	\ingroup    project
 *	\brief      Page de gestion des documents attachees a une tache d'un projet
 */

require('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/task.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");


$langs->load('projects');
$langs->load('other');

$action=GETPOST('action');
$mine = $_REQUEST['mode']=='mine' ? 1 : 0;
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects
$id = GETPOST('id','int');
$ref= GETPOST('ref');
$withproject=GETPOST('withproject');
$project_ref = GETPOST('proj_ref','alfa');

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



$task = new Task($db);

if ($task->fetch($id,$ref) > 0)
{
	$projectstatic = new Project($db);
	$projectstatic->fetch($task->fk_project);

	if (! empty($projectstatic->socid)) $projectstatic->societe->fetch($projectstatic->socid);

	$upload_dir = $conf->projet->dir_output.'/'.dol_sanitizeFileName($projectstatic->ref).'/'.dol_sanitizeFileName($task->ref);
}
else
{
	dol_print_error($db);
}


/*
 * Actions
 */

// Envoi fichier
if ($_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

	if (dol_mkdir($upload_dir) >= 0)
	{
		$resupload=dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name'],0,0,$_FILES['userfile']['error']);
		if (is_numeric($resupload) && $resupload > 0)
		{
			$mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';
		}
		else
		{
			$langs->load("errors");
			if ($resupload < 0)	// Unknown error
			{
				$mesg = '<div class="error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
			}
			else if (preg_match('/ErrorFileIsInfectedWithAVirus/',$resupload))	// Files infected by a virus
			{
				$mesg = '<div class="error">'.$langs->trans("ErrorFileIsInfectedWithAVirus").'</div>';
			}
			else	// Known error
			{
				$mesg = '<div class="error">'.$langs->trans($resupload).'</div>';
			}
		}
	}
}

// Delete
if ($action=='delete')
{
	$file = $upload_dir . '/' . $_GET['urlfile'];	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
	dol_delete_file($file);
	$mesg = '<div class="ok">'.$langs->trans("FileWasRemoved").'</div>';
}

$taskstatic = new Task($db);

// Retreive First Task ID of Project if withprojet is on to allow project prev next to work
if (($project_ref) && ($withproject))
{
	$projectstatic = new Project($db);
	if ($projectstatic->fetch(0,$project_ref) > 0)
	{
		$tasksarray=$taskstatic->getTasksArray(0, 0, $projectstatic->id, $socid, 0);
		if (count($tasksarray) > 0)
		{
			$id=$tasksarray[0]->id;
		}
		else
		{
			Header("Location: ".DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.(empty($mode)?'':'&mode='.$mode));
		}
	}
}

// Determine file directory regarding project ref
if ($taskstatic->fetch($id) > 0)
{
	$projectstatic = new Project($db);
	$projectstatic->fetch($taskstatic->fk_project);

	if (! empty($projectstatic->socid)) $projectstatic->societe->fetch($projectstatic->socid);

	$upload_dir = $conf->projet->dir_output.'/'.dol_sanitizeFileName($projectstatic->ref).'/'.dol_sanitizeFileName($taskstatic->ref);
}
else
{
	dol_print_error($db);
}
/*
 * View
 */

$form = new Form($db);
$project = new Project($db);
$task = new Task($db);

llxHeader('',$langs->trans('Project'));

if ($id > 0 || ! empty($ref))
{
    if ($task->fetch($id) >= 0)
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
    		print $form->showrefnav($project,'proj_ref','',1,'ref','ref','',$param.'&withproject=1');
    		print '</td></tr>';

    		print '<tr><td>'.$langs->trans("Label").'</td><td>'.$project->title.'</td></tr>';

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
    	dol_fiche_head($head, 'task_document', $langs->trans("Task"), 0, 'projecttask');

    	$param=(GETPOST('withproject')?'&withproject=1':'');
    	$linkback=GETPOST('withproject')?'<a href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$project->id.'">'.$langs->trans("BackToList").'</a>':'';

    	// Files list constructor
    	$filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
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
		if (! GETPOST('withproject') || empty($project->id))
		{
		    $projectsListId = $project->getProjectsAuthorizedForUser($user,$mine,1);
		    $task->next_prev_filter=" fk_projet in (".$projectsListId.")";
		}
		else $task->next_prev_filter=" fk_projet = ".$project->id;
	   	print $form->showrefnav($task,'id',$linkback,1,'rowid','ref','',$param);
    	print '</td>';
    	print '</tr>';

    	// Label
    	print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$task->label.'</td></tr>';

    	// Project
    	if (empty($withproject))
    	{
    	    print '<tr><td>'.$langs->trans("Project").'</td><td colspan="3">';
        	print $projectstatic->getNomUrl(1);
        	print '</td></tr>';

        	// Third party
        	print '<td>'.$langs->trans("Company").'</td><td colspan="3">';
        	if ($projectstatic->societe->id) print $projectstatic->societe->getNomUrl(1);
        	else print '&nbsp;';
        	print '</td></tr>';
    	}

    	// Files infos
    	print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
    	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

    	print "</table>\n";

    	dol_fiche_end();

        print '<br>';

    	dol_htmloutput_mesg($mesg);


    	// Affiche formulaire upload
    	$formfile=new FormFile($db);
    	$formfile->form_attach_new_file(DOL_URL_ROOT.'/projet/tasks/document.php?id='.$task->id,'',0,0,$user->rights->projet->creer);


    	// List of document
    	$param='&id='.$task->id;
    	$formfile->list_of_documents($filearray,$task,'projet',$param,0,dol_sanitizeFileName($project->ref).'/'.dol_sanitizeFileName($task->ref).'/');
    }
}
else
{
	Header('Location: index.php');
	exit;
}


llxFooter();

$db->close();
?>
