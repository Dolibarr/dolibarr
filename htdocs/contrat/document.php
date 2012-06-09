<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005      Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2005      Simon TOSSER         <simon@kornog-computing.com>
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
 *       \file       htdocs/contrat/document.php
 *       \ingroup    contrat
 *       \brief      Page des documents joints sur les contrats
 */

require ("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/contract.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/images.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");

$langs->load("other");
$langs->load("products");


$action		= GETPOST('action','alpha');
$confirm	= GETPOST('confirm','alpha');
$id			= GETPOST('id','int');
$ref		= GETPOST('ref','alpha');

$mesg='';
if (isset($_SESSION['DolMessage']))
{
	$mesg=$_SESSION['DolMessage'];
	unset($_SESSION['DolMessage']);
}

// Security check
if ($user->societe_id > 0)
{
	unset($_GET["action"]);
	$action='';
	$socid = $user->societe_id;
}
$result = restrictedArea($user, 'contrat', $id);

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


$object = new Contrat($db);
$object->fetch($id, $ref);
if ($object->id > 0)
{
	$object->fetch_thirdparty();
}

$upload_dir = $conf->contrat->dir_output.'/'.dol_sanitizeFileName($object->ref);
$modulepart='contract';


/*
 * Action envoie fichier
 */
if ($_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
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
if ($action == 'confirm_deletefile' && $confirm == 'yes')
{
	if ($object->id)
	{
		$langs->load("other");

		$file = $upload_dir . '/' . GETPOST('urlfile');	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
		$ret=dol_delete_file($file,0,0,0,$object);
		$_SESSION['DolMessage'] = '<div class="ok">'.$langs->trans("FileWasRemoved",GETPOST('urlfile')).'</div>';
		Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
		exit;
	}
}


/*
 *
 */

$form = new Form($db);

llxHeader("","",$langs->trans("CardProduct".$product->type));


if ($object->id)
{
	$head=contract_prepare_head($object, $user);

	dol_fiche_head($head, 'documents', $langs->trans("Contract"), 0, 'contract');


	// Construit liste des fichiers
	$filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
	$totalsize=0;
	foreach($filearray as $key => $file)
	{
		$totalsize+=$file['size'];
	}


    print '<table class="border" width="100%">';

    // Reference
	print '<tr><td width="30%">'.$langs->trans('Ref').'</td><td colspan="3">'.$object->ref.'</td></tr>';

    // Societe
    print '<tr><td>'.$langs->trans("Customer").'</td>';
    print '<td colspan="3">'.$object->thirdparty->getNomUrl(1).'</td></tr>';

    print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
    print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
    print '</table>';

    print '</div>';
    
    dol_htmloutput_mesg($mesg,$mesgs);
    
    /*
     * Confirmation suppression fichier
     */
    if ($action == 'delete')
    {
    	$ret=$form->form_confirm($_SERVER["PHP_SELF"].'?id='.$id.'&urlfile='.urlencode(GETPOST("urlfile")), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 1);
    	if ($ret == 'html') print '<br>';
    }


    // Affiche formulaire upload
   	$formfile=new FormFile($db);
	$formfile->form_attach_new_file(DOL_URL_ROOT.'/contrat/document.php?id='.$object->id,'',0,0,$user->rights->contrat->creer,50,$object);


	// List of document
	$param='&id='.$object->id;
	$formfile->list_of_documents($filearray,$object,'contract',$param);

}
else
{
	print $langs->trans("UnkownError");
}

$db->close();

llxFooter();
?>
