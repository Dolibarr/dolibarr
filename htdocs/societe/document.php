<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
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
 *  \file       htdocs/societe/document.php
 *  \brief      Tab for documents linked to third party
 *  \ingroup    societe
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$langs->load("companies");
$langs->load('other');


$action=GETPOST('action');
$confirm=GETPOST('confirm');
$id=(GETPOST('socid','int') ? GETPOST('socid','int') : GETPOST('id','int'));
$ref = GETPOST('ref', 'alpha');

// Security check
if ($user->societe_id > 0)
{
	unset($action);
	$socid = $user->societe_id;
}
$result = restrictedArea($user, 'societe', $id, '&societe');

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

$object = new Societe($db);
if ($id > 0 || ! empty($ref))
{
	$result = $object->fetch($id, $ref);

	$upload_dir = $conf->societe->multidir_output[$object->entity] . "/" . $object->id ;
	$courrier_dir = $conf->societe->multidir_output[$object->entity] . "/courrier/" . get_exdir($object->id);
}


/*
 * Actions
 */

// TODO Use an include to mutualize this code for action sendit and confirm_deletefile

// Post file
if (GETPOST('sendit') && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	if ($object->id)
	{
		dol_add_file_process($upload_dir,0,1,'userfile');
	}
}

// Delete file
if ($action == 'confirm_deletefile' && $confirm == 'yes')
{
	if ($object->id)
	{
		$file = $upload_dir . "/" . GETPOST('urlfile');	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).

		$ret=dol_delete_file($file,0,0,0,$object);
		if ($ret) setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile')));
		else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), 'errors');
    	header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
    	exit;
	}
}


/*
 * View
 */

$form = new Form($db);

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$langs->trans("ThirdParty").' - '.$langs->trans("Files"),$help_url);

if ($object->id)
{
	/*
	 * Affichage onglets
	 */
	if (! empty($conf->notification->enabled)) $langs->load("mails");
	$head = societe_prepare_head($object);

	$form=new Form($db);

	dol_fiche_head($head, 'document', $langs->trans("ThirdParty"),0,'company');


	// Construit liste des fichiers
	$filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
	$totalsize=0;
	foreach($filearray as $key => $file)
	{
		$totalsize+=$file['size'];
	}


	print '<table class="border"width="100%">';

	// Ref
	print '<tr><td width="30%">'.$langs->trans("ThirdPartyName").'</td>';
	print '<td colspan="3">';
	print $form->showrefnav($object,'socid','',($user->societe_id?0:1),'rowid','nom');
	print '</td></tr>';

	// Prefix
	if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
	{
		print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
	}

	if ($object->client)
	{
		print '<tr><td>';
		print $langs->trans('CustomerCode').'</td><td colspan="3">';
		print $object->code_client;
		if ($object->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
		print '</td></tr>';
	}

	if ($object->fournisseur)
	{
		print '<tr><td>';
		print $langs->trans('SupplierCode').'</td><td colspan="3">';
		print $object->code_fournisseur;
		if ($object->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
		print '</td></tr>';
	}

	// Nbre fichiers
	print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';

	//Total taille
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

	print '</table>';

	print '</div>';

	/*
	 * Confirmation suppression fichier
	 */
	if ($action == 'delete')
	{
		$ret=$form->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&urlfile='.urlencode(GETPOST("urlfile")), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 1);
		if ($ret == 'html') print '<br>';
	}

	$formfile=new FormFile($db);

	// Show upload form
	$formfile->form_attach_new_file($_SERVER["PHP_SELF"].'?id='.$object->id,'',0,0,$user->rights->societe->creer,50,$object);

	// List of document
	$formfile->list_of_documents($filearray,$object,'societe');

	print "<br><br>";
}
else
{
	accessforbidden('',0,0);
}


llxFooter();
$db->close();
?>
