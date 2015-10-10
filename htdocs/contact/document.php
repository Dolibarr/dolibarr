<?php
/* Copyright (C) 2014       Alexandre Spangaro  <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015       Frederic France     <frederic.france@free.fr>
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
 *       \file       htdocs/contact/document.php
 *       \ingroup    contact
 *       \brief      Page with attached files on contact
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$langs->load("other");
$langs->load("companies");
$langs->load("contact");

$id = GETPOST('id','int');
$action = GETPOST("action");
$confirm = GETPOST('confirm', 'alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'contact', $id, '','');

// Get parameters
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) {
    $page = 0;
}
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";

$object = new Contact($db);
if ($id > 0) $object->fetch($id);

$upload_dir = $conf->societe->dir_output.'/contact/'.dol_sanitizeFileName($object->ref);
$modulepart='contact';


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_pre_headers.tpl.php';


/*
 * View
 */

$form = new Form($db);

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader("",$langs->trans("Contact"), $helpurl);

if ($object->id)
{
    $head = contact_prepare_head($object);
	$title = (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));

    dol_fiche_head($head, 'documents', $title, 0, 'contact');


    // Construit liste des fichiers
    $filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
    $totalsize=0;
    foreach($filearray as $key => $file)
    {
        $totalsize+=$file['size'];
    }

    print '<table class="border" width="100%">';

    // Ref
    print '<tr><td width="20%">'.$langs->trans("Ref").'</td><td colspan="3">';
    print $form->showrefnav($object, 'id', $linkback);
    print '</td></tr>';

    // Name
    print '<tr><td width="20%">'.$langs->trans("Lastname").' / '.$langs->trans("Label").'</td><td width="30%">'.$object->lastname.'</td>';
    print '<td width="20%">'.$langs->trans("Firstname").'</td><td width="30%">'.$object->firstname.'</td></tr>';

    // Company
    if (empty($conf->global->SOCIETE_DISABLE_CONTACTS))
    {
    	if ($object->socid > 0)
    	{
    		$objsoc = new Societe($db);
    		$objsoc->fetch($object->socid);

    		print '<tr><td>'.$langs->trans("ThirdParty").'</td><td colspan="3">'.$objsoc->getNomUrl(1).'</td></tr>';
    	}

    	else
    	{
    		print '<tr><td>'.$langs->trans("ThirdParty").'</td><td colspan="3">';
    		print $langs->trans("ContactNotLinkedToCompany");
    		print '</td></tr>';
    	}
    }
    
    print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
    print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
    print '</table>';

    print '</div>';

    $modulepart = 'contact';
    $permission = $user->rights->societe->contact->creer;
    $param = '&id=' . $object->id;
    include DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
} else {
    print $langs->trans("ErrorUnknown");
}


llxFooter();

$db->close();
