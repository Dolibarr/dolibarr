<?php
/* Copyright (C)    2013      Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C)    2013-2014 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C)	2015	  Marcos García		  <marcosgdf@gmail.com>
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
 * or see http://www.gnu.org/
 */

// Following var can be set
// $permission = permission or not to add a file
// $permtoedit = permission or not to edit file name, crop file
// $modulepart = for download
// $param      = param to add to download links

// Protection to avoid direct call of template
if (empty($langs) || ! is_object($langs))
{
	print "Error, template page can't be called as URL";
	exit;
}


$langs->load("link");
if (empty($relativepathwithnofile)) $relativepathwithnofile='';
if (empty($permtoedit)) $permtoedit=-1;

/*
 * Confirm form to delete
 */

if ($action == 'delete')
{
	$langs->load("companies");	// Need for string DeleteFile+ConfirmDeleteFiles
	$ret = $form->form_confirm(
			$_SERVER["PHP_SELF"] . '?id=' . $object->id . '&urlfile=' . urlencode(GETPOST("urlfile")) . '&linkid=' . GETPOST('linkid', 'int') . (empty($param)?'':$param),
			$langs->trans('DeleteFile'),
			$langs->trans('ConfirmDeleteFile'),
			'confirm_deletefile',
			'',
			0,
			1
	);
	if ($ret == 'html') print '<br>';
}

$formfile=new FormFile($db);

// We define var to enable the feature to add prefix of uploaded files
$savingdocmask='';
if (empty($conf->global->MAIN_DISABLE_SUGGEST_REF_AS_PREFIX))
{
	//var_dump($modulepart);
	if (in_array($modulepart,array('facture_fournisseur','commande_fournisseur','facture','commande','propal','supplier_proposal','ficheinter','contract','project','project_task','expensereport','tax')))
	{
		$savingdocmask=dol_sanitizeFileName($object->ref).'-__file__';
	}
	/*if (in_array($modulepart,array('member')))
	{
		$savingdocmask=$object->login.'___file__';
	}*/
}

// Show upload form (document and links)
$formfile->form_attach_new_file(
    $_SERVER["PHP_SELF"].'?id='.$object->id.(empty($withproject)?'':'&withproject=1'),
    '',
    0,
    0,
    $permission,
    $conf->browser->layout == 'phone' ? 40 : 60,
    $object,
	'',
	1,
	$savingdocmask
);

$disablemove=1;
if (in_array($modulepart, array('product', 'produit', 'societe', 'user'))) $disablemove=0;		// Drag and drop for up and down allowed on product, thirdparty, ...

// List of document
$formfile->list_of_documents(
    $filearray,
    $object,
    $modulepart,
    $param,
    0,
    $relativepathwithnofile,		// relative path with no file. For example "0/1"
    $permission,
    0,
    '',
    0,
    '',
    '',
    0,
    $permtoedit,
    $upload_dir,
    $sortfield,
    $sortorder,
    $disablemove
);

print "<br>";
//List of links
$formfile->listOfLinks($object, $permission, $action, GETPOST('linkid', 'int'), $param);
print "<br>";
