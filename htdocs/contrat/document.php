<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */
 
/**
        \file       htdocs/contrat/document.php
        \ingroup    contrat
        \brief      Page des documents joints sur les contrats
        \version    $Id$
*/

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/lib/contract.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");

$langs->load("other");
$langs->load("products");

if (!$user->rights->contrat->lire)
	accessforbidden();

// Security check
if ($user->societe_id > 0) 
{
	unset($_GET["action"]);
	$action=''; 
	$socid = $user->societe_id;
}

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


$contrat = new Contrat($db);
$contrat->fetch($_GET["id"]);

$upload_dir = $conf->contrat->dir_output.'/'.sanitizeFileName($contrat->ref);
$modulepart='contract';


/*
 * Action envoie fichier
 */
if ($_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
    /*
     * Creation répertoire si n'existe pas
     */
    if (! is_dir($upload_dir)) create_exdir($upload_dir);

    if (is_dir($upload_dir))
    {
        if (dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name'],0) > 0)
        {
            $mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';
            //print_r($_FILES);
        }
        else
        {
            // Echec transfert (fichier dépassant la limite ?)
            $mesg = '<div class="error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
            // print_r($_FILES);
        }
    }
}


/*
 *
 */
 
$html = new Form($db);

llxHeader("","",$langs->trans("CardProduct".$product->type));


if ($contrat->id)
{
    $soc = new Societe($db, $contrat->societe->id);
    $soc->fetch($contrat->societe->id);

	if ( $error_msg )
	{ 
		echo '<div class="error">'.$error_msg.'</div><br>';
	}

	if ($_GET["action"] == 'delete')
	{
		$file = $upload_dir . '/' . urldecode($_GET['urlfile']);
		$result=dol_delete_file($file);
		//if ($result >= 0) $mesg=$langs->trans("FileWasRemoced");
	}

	$head=contract_prepare_head($contrat, $user);

	dolibarr_fiche_head($head, 'documents',  $langs->trans("Contract"));


	// Construit liste des fichiers
	$filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
	$totalsize=0;
	foreach($filearray as $key => $file)
	{
		$totalsize+=$file['size'];
	}
	
    
    print '<table class="border" width="100%">';

    // Reference
	print '<tr><td width="30%">'.$langs->trans('Ref').'</td><td colspan="3">'.$contrat->ref.'</td></tr>';

    // Societe
    print '<tr><td>'.$langs->trans("Customer").'</td>';
    print '<td colspan="3">'.$soc->getNomUrl(1).'</td></tr>';

    print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.sizeof($filearray).'</td></tr>';
    print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
    print '</table>';

    print '</div>';


    // Affiche formulaire upload
   	$formfile=new FormFile($db);
	$formfile->form_attach_new_file(DOL_URL_ROOT.'/contrat/document.php?id='.$contrat->id);

	
	// List of document
	$param='&id='.$contrat->id;	
	$formfile->list_of_documents($filearray,$contrat,'contract',$param);

}
else
{
	print $langs->trans("UnkownError");
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
