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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/projet/tasks/document.php
 *	\ingroup    project
 *	\brief      Page de gestion des documents attachees a une tache d'un projet
 *	\version    $Id$
 */

require('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/projet/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/projet/tasks/task.class.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/project.lib.php');
require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");

if (!$user->rights->projet->lire) accessforbidden();

$langs->load('projects');
$langs->load('other');

$id=empty($_GET['id']) ? 0 : intVal($_GET['id']);
$action=empty($_GET['action']) ? (empty($_POST['action']) ? '' : $_POST['action']) : $_GET['action'];

// Security check
$socid=0;
$id = isset($_GET["id"])?$_GET["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
//$result=restrictedArea($user,'projet',$id,'');

// Get parameters
$page=$_GET["page"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];

if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;


$id = $_GET['id'];
$ref= $_GET['ref'];

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
	if (! is_dir($upload_dir)) create_exdir($upload_dir);

	if (is_dir($upload_dir))
	{
		$result = dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name'],0);
    	if ($result > 0)
        {
            $mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';
            //print_r($_FILES);
        }
        else if ($result == -99)
        {
        	// Files infected by a virus
		    $langs->load("errors");
            $mesg = '<div class="error">'.$langs->trans("ErrorFileIsInfectedWithAVirus").'</div>';
        }
		else if ($result < 0)
		{
			// Echec transfert (fichier depassant la limite ?)
			$mesg = '<div class="error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
			// print_r($_FILES);
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


/*
 * View
 */

llxHeader('',$langs->trans('Project'));

$form = new Form($db);

if ($id > 0 || ! empty($ref))
{
	// To verify role of users
	$userAccess = $projectstatic->restrictedProjectArea($user);

	$head = task_prepare_head($task);
	dol_fiche_head($head, 'document', $langs->trans("Task"), 0, 'projecttask');

	// Files list constructor
	$filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
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
	print $form->showrefnav($task,'id','',1,'rowid','ref','','');
	print '</td>';
	print '</tr>';

	// Label
	print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$task->label.'</td></tr>';

	// Project
	print '<tr><td>'.$langs->trans("Project").'</td><td colspan="3">';
	print $projectstatic->getNomUrl(1);
	print '</td></tr>';

	// Third party
	print '<td>'.$langs->trans("Company").'</td><td colspan="3">';
	if ($projectstatic->societe->id) print $projectstatic->societe->getNomUrl(1);
	else print '&nbsp;';
	print '</td></tr>';

	// Files infos
	print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.sizeof($filearray).'</td></tr>';
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

	print "</table>\n";
	print "</div>\n";

	if ($mesg) { print $mesg."<br>"; }


	// Affiche formulaire upload
	$formfile=new FormFile($db);
	$formfile->form_attach_new_file(DOL_URL_ROOT.'/projet/tasks/document.php?id='.$task->id,'',0,0,$user->rights->projet->creer);


	// List of document
	$param='&id='.$task->id;
	$formfile->list_of_documents($filearray,$task,'projet',$param);

}
else
{
	Header('Location: index.php');
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
