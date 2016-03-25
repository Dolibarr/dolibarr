<?php
/* Copyright (C) 2003-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Marc Barilley / Ocebo	<marc@ocebo.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2005		Simon TOSSER			<simon@kornog-computing.com>
 * Copyright (C) 2013		Cédric Salvador			<csalvador@gpcsolutions.fr>
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
 *       \file       htdocs/contrat/document.php
 *       \ingroup    contrat
 *       \brief      Page des documents joints sur les contrats
 */

require ("../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$langs->load("other");
$langs->load("products");
$langs->load("contracts");

$action		= GETPOST('action','alpha');
$confirm	= GETPOST('confirm','alpha');
$id			= GETPOST('id','int');
$ref		= GETPOST('ref','alpha');

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
 * Actions
 */
include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_pre_headers.tpl.php';


/*
 *
 */

$form = new Form($db);

llxHeader();


if ($object->id)
{
	$head=contract_prepare_head($object);

	dol_fiche_head($head, 'documents', $langs->trans("Contract"), 0, 'contract');


	// Construit liste des fichiers
	$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
	$totalsize=0;
	foreach($filearray as $key => $file)
	{
		$totalsize+=$file['size'];
	}


    print '<table class="border" width="100%">';

    $linkback = '<a href="'.DOL_URL_ROOT.'/contrat/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

    // Reference
	print '<tr><td width="30%">'.$langs->trans('Ref').'</td><td colspan="3">'.$form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '').'</td></tr>';

    // Societe
    print '<tr><td>'.$langs->trans("Customer").'</td>';
    print '<td colspan="3">'.$object->thirdparty->getNomUrl(1).'</td></tr>';

    print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
    print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
    print '</table>';

    print '</div>';

    $modulepart = 'contract';
    $permission = $user->rights->contrat->creer;
    $param = '&id=' . $object->id;
    include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';

}
else
{
	print $langs->trans("ErrorUnknown");
}


llxFooter();
$db->close();
