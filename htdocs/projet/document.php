<?php
/* Copyright (C) 2010 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/projet/document.php
 *	\ingroup    project
 *	\brief      Page de gestion des documents attachees a un projet
 */

require('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/images.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");

$langs->load('projects');
$langs->load('other');

$action=GETPOST('action');
$mine = $_REQUEST['mode']=='mine' ? 1 : 0;
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects
$id = GETPOST('id','int');
$ref= GETPOST('ref');

$project = new Project($db);
if (! $project->fetch($id,$ref) > 0)
{
	dol_print_error($db);
	exit;
}
else $id=$project->id;

// Security check
$socid=0;
if ($user->societe_id > 0) $socid=$user->societe_id;
$result=restrictedArea($user,'projet',$id,'');

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



/*
 * Actions
 */

// Envoi fichier
if ($_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	$upload_dir = $conf->projet->dir_output . "/" . dol_sanitizeFileName($project->ref);

	if (dol_mkdir($upload_dir) >= 0)
	{
		$resupload=dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . dol_unescapefile($_FILES['userfile']['name']),0,0,$_FILES['userfile']['error']);
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
if ($action == 'confirm_delete' && $_REQUEST['confirm'] == 'yes' && $user->rights->projet->supprimer)
{
    $langs->load("other");
	$upload_dir = $conf->projet->dir_output . "/" . dol_sanitizeFileName($project->ref);
	$file = $upload_dir . '/' . GETPOST('urlfile');	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
	dol_delete_file($file);
	$mesg = '<div class="ok">'.$langs->trans("FileWasRemoved",GETPOST('urlfile')).'</div>';
}


/*
 * View
 */

llxHeader('',$langs->trans('Project'),'EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes');

$form = new Form($db);

if ($id > 0 || ! empty($ref))
{
	$upload_dir = $conf->projet->dir_output.'/'.dol_sanitizeFileName($project->ref);

	$company = new Societe($db);
	$company->fetch($project->socid);

	if ($project->societe->id > 0)  $result=$project->societe->fetch($project->societe->id);

    // To verify role of users
    //$userAccess = $project->restrictedProjectArea($user,'read');
    $userWrite  = $project->restrictedProjectArea($user,'write');
    //$userDelete = $project->restrictedProjectArea($user,'delete');
    //print "userAccess=".$userAccess." userWrite=".$userWrite." userDelete=".$userDelete;

	$head = project_prepare_head($project);
	dol_fiche_head($head, 'document', $langs->trans("Project"), 0, ($project->public?'projectpub':'project'));

	// Files list constructor
	$filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
	$totalsize=0;
	foreach($filearray as $key => $file)
	{
		$totalsize+=$file['size'];
	}

	if ($action == 'delete')
	{
		$ret=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$_GET["id"]."&urlfile=".urlencode(GETPOST("urlfile")),$langs->trans("DeleteAFile"),$langs->trans("ConfirmDeleteAFile"),"confirm_delete",'','',1);
		if ($ret == 'html') print '<br>';
	}

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td>';
	// Define a complementary filter for search of next/prev ref.
    if (! $user->rights->projet->all->lire)
    {
        $projectsListId = $project->getProjectsAuthorizedForUser($user,$mine,0);
        $project->next_prev_filter=" rowid in (".(count($projectsListId)?join(',',array_keys($projectsListId)):'0').")";
    }
	print $form->showrefnav($project,'ref','',1,'ref','ref');
	print '</td></tr>';

	// Label
	print '<tr><td>'.$langs->trans("Label").'</td><td>'.$project->title.'</td></tr>';

	// Company
	print '<tr><td>'.$langs->trans("Company").'</td><td>';
	if (! empty($project->societe->id)) print $project->societe->getNomUrl(1);
	else print '&nbsp;';
	print '</td></tr>';

	// Visibility
	print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
	if ($project->public) print $langs->trans('SharedProject');
	else print $langs->trans('PrivateProject');
	print '</td></tr>';

	// Statut
	print '<tr><td>'.$langs->trans("Status").'</td><td>'.$project->getLibStatut(4).'</td></tr>';

	// Files infos
	print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

	print "</table>\n";
	print "</div>\n";

	dol_htmloutput_mesg($mesg);


	// Affiche formulaire upload
	$formfile=new FormFile($db);
	$formfile->form_attach_new_file(DOL_URL_ROOT.'/projet/document.php?id='.$project->id,'',0,0,($userWrite>0),50,$object);


	// List of document
	$param='&id='.$project->id;
	$formfile->list_of_documents($filearray,$project,'projet',$param,0,'',($userWrite>0));

}
else
{
	dol_print_error('','NoRecordFound');
}

llxFooter();

$db->close();
?>
