<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2009 Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2005      Simon TOSSER          <simon@kornog-computing.com>
 * Copyright (C) 2011      Juanjo Menent         <jmenent@2byte.es>
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
 *       \file       htdocs/fichinter/document.php
 *       \ingroup    fichinter
 *       \brief      Page des documents joints sur les contrats
 *       \version    $Id: document.php,v 1.22 2011/07/31 23:50:54 eldy Exp $
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/fichinter.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");

$langs->load("other");
$langs->load("fichinter");
$langs->load("companies");
$langs->load("interventions");

$fichinterid = GETPOST("id");
$action = GETPOST("action");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'ficheinter', $fichinterid, 'fichinter');


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


$object = new Fichinter($db);
$object->fetch($fichinterid);

$upload_dir = $conf->ficheinter->dir_output.'/'.dol_sanitizeFileName($object->ref);
$modulepart='fichinter';


/*
 * Action envoie fichier
 */
if (GETPOST("sendit") && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");

	if (create_exdir($upload_dir) >= 0)
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


/*
 *
 */

$html = new Form($db);

llxHeader("","",$langs->trans("InterventionCard"));


if ($object->id)
{
	$object->fetch_thirdparty();

    $soc = new Societe($db, $object->societe->id);
    $soc->fetch($object->societe->id);

	if ( $error_msg )
	{
		echo '<div class="error">'.$error_msg.'</div><br>';
	}

	if ($action == 'delete')
	{
		$file = $upload_dir . '/' . GETPOST("urlfile");	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
		$result=dol_delete_file($file);
		//if ($result >= 0) $mesg=$langs->trans("FileWasRemoced");
	}

	$head=fichinter_prepare_head($object, $user);

	dol_fiche_head($head, 'documents',  $langs->trans("InterventionCard"), 0, 'intervention');


	// Construit liste des fichiers
	$filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
	$totalsize=0;
	foreach($filearray as $key => $file)
	{
		$totalsize+=$file['size'];
	}


    print '<table class="border" width="100%">';

	// Ref
	print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td>'.$object->ref.'</td></tr>';

	// Societe
	print "<tr><td>".$langs->trans("Company")."</td><td>".$object->client->getNomUrl(1)."</td></tr>";

    print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.sizeof($filearray).'</td></tr>';
    print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
    print '</table>';

    print '</div>';


    // Affiche formulaire upload
   	$formfile=new FormFile($db);
	$formfile->form_attach_new_file(DOL_URL_ROOT.'/fichinter/document.php?id='.$object->id,'',0,0,$user->rights->ficheinter->creer);


	// List of document
	$param='&id='.$object->id;
	$formfile->list_of_documents($filearray,$object,'ficheinter',$param);

}
else
{
	print $langs->trans("UnkownError");
}

$db->close();

llxFooter('$Date: 2011/07/31 23:50:54 $ - $Revision: 1.22 $');
?>
