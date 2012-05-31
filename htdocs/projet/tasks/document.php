<?php
/* Copyright (C) 2010-2012 Regis Houssin <regis@dolibarr.fr>
 * Copyright (C) 2006-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012      Florian Henry
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
require_once(DOL_DOCUMENT_ROOT."/core/lib/images.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");


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

$mesg='';
if (isset($_SESSION['DolMessage']))
{
	$mesg=$_SESSION['DolMessage'];
	unset($_SESSION['DolMessage']);
}

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

// Envoi fichier
if ($_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	if (dol_mkdir($upload_dir) >= 0)
	{
		$resupload=dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . stripslashes($_FILES['userfile']['name']),0,0,$_FILES['userfile']['error']);
		if (is_numeric($resupload) && $resupload > 0)
		{
            if (image_format_supported($upload_dir . "/" . $_FILES['userfile']['name']) == 1)
            {
                // Create small thumbs for image (Ratio is near 16/9)
                // Used on logon for example
                $imgThumbSmall = vignette($upload_dir . "/" . $_FILES['userfile']['name'], $maxwidthsmall, $maxheightsmall, '_small', $quality, "thumbs");
                // Create mini thumbs for image (Ratio is near 16/9)
                // Used on menu or for setup page for example
                $imgThumbMini = vignette($upload_dir . "/" . $_FILES['userfile']['name'], $maxwidthmini, $maxheightmini, '_mini', $quality, "thumbs");
            }
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
    $langs->load("other");
	$file = $upload_dir . '/' . GETPOST('urlfile');	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
	dol_delete_file($file);
	$_SESSION['DolMessage'] = '<div class="ok">'.$langs->trans("FileWasRemoved",GETPOST('urlfile')).'</div>';
    Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
    exit;
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
			Header("Location: ".DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.($withproject?'&withproject=1':'').(empty($mode)?'':'&mode='.$mode));
			exit;
		}
	}
}

// Find upload dir after retreive is task if necessary
if ($id > 0 || ! empty($ref))
{
	if ($object->fetch($id,$ref) > 0)
	{
		$projectstatic->fetch($object->fk_project);

		if (! empty($projectstatic->socid)) $projectstatic->societe->fetch($projectstatic->socid);

		$upload_dir = $conf->projet->dir_output.'/'.dol_sanitizeFileName($projectstatic->ref).'/'.dol_sanitizeFileName($object->ref);
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * View
 */

$form = new Form($db);

llxHeader('',$langs->trans('Project'));

if ($object->id > 0)
{
	if (! empty($projectstatic->socid)) $projectstatic->societe->fetch($projectstatic->socid);

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

		print '<tr><td>'.$langs->trans("Label").'</td><td>'.$projectstatic->title.'</td></tr>';

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
	dol_fiche_head($head, 'task_document', $langs->trans("Task"), 0, 'projecttask');

	$param=(GETPOST('withproject')?'&withproject=1':'');
	$linkback=GETPOST('withproject')?'<a href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.'">'.$langs->trans("BackToList").'</a>':'';

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
	if (empty($withproject) || empty($projectstatic->id))
	{
		$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,$mine,1);
		$object->next_prev_filter=" fk_projet in (".$projectsListId.")";
	}
	else $object->next_prev_filter=" fk_projet = ".$projectstatic->id;
	print $form->showrefnav($object,'id',$linkback,1,'rowid','ref','',$param);
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
	$formfile->form_attach_new_file(DOL_URL_ROOT.'/projet/tasks/document.php?id='.$object->id.($withproject?'&withproject=1':''),'',0,0,$user->rights->projet->creer,50,$object);


	// List of document
	$param='&id='.$object->id;
	$formfile->list_of_documents($filearray,$object,'projet',$param,0,dol_sanitizeFileName($projectstatic->ref).'/'.dol_sanitizeFileName($object->ref).'/');
}
else
{
	Header('Location: index.php');
	exit;
}


llxFooter();

$db->close();
?>
