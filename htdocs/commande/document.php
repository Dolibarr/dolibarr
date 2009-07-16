<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2009 Regis Houssin         <regis@dolibarr.fr>
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
 \file       htdocs/commande/document.php
 \ingroup    order
 \brief      Page de gestion des documents attachees a une commande
 \version    $Id$
 */

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/lib/order.lib.php');
require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");

if (!$user->rights->commande->lire) accessforbidden();

$langs->load('companies');
//$langs->load("bills");
$langs->load('other');

$id=empty($_GET['id']) ? 0 : intVal($_GET['id']);
$action=empty($_GET['action']) ? (empty($_POST['action']) ? '' : $_POST['action']) : $_GET['action'];

// Security check
$socid=0;
$comid = isset($_GET["id"])?$_GET["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'commande',$comid,'');

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
$commande = new Commande($db);
if (! $commande->fetch($_GET['id'],$_GET['ref']) > 0)
{
	dol_print_error($db);
}


/*
 * Actions
 */

// Envoi fichier
if ($_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	$upload_dir = $conf->commande->dir_output . "/" . dol_sanitizeFileName($commande->ref);
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

// Delete
if ($action=='delete')
{
	$upload_dir = $conf->commande->dir_output . "/" . dol_sanitizeFileName($commande->ref);
	$file = $upload_dir . '/' . $_GET['urlfile'];	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
	dol_delete_file($file);
	$mesg = '<div class="ok">'.$langs->trans("FileWasRemoved").'</div>';
}


/*
 * View
 */

llxHeader();

$html = new Form($db);

if ($id > 0 || ! empty($ref))
{
	$upload_dir = $conf->commande->dir_output.'/'.dol_sanitizeFileName($commande->ref);

	$societe = new Societe($db);
	$societe->fetch($commande->socid);

	$head = commande_prepare_head($commande);
	dol_fiche_head($head, 'documents', $langs->trans('CustomerOrder'));


	// Construit liste des fichiers
	$filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
	$totalsize=0;
	foreach($filearray as $key => $file)
	{
		$totalsize+=$file['size'];
	}


	print '<table class="border"width="100%">';

	// Ref
	print '<tr><td width="30%">'.$langs->trans('Ref').'</td><td colspan="3">';
	print $html->showrefnav($commande,'ref','',1,'ref','ref');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('Company').'</td><td colspan="3">'.$societe->getNomUrl(1).'</td></tr>';
	print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.sizeof($filearray).'</td></tr>';
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
	print "</table>\n";
	print "</div>\n";

	if ($mesg) { print $mesg."<br>"; }


	// Affiche formulaire upload
	$formfile=new FormFile($db);
	$formfile->form_attach_new_file(DOL_URL_ROOT.'/commande/document.php?id='.$commande->id,'',0,0,$user->rights->commande->creer);


	// List of document
	$param='&id='.$commande->id;
	$formfile->list_of_documents($filearray,$commande,'commande',$param);

}
else
{
	Header('Location: index.php');
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
