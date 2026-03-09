<?php
/* Copyright (C)    2013      Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C)    2013-2014 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C)	2015	  Marcos García		  <marcosgdf@gmail.com>
 * Copyright (C) 	2019	  Nicolas ZABOURI     <info@inovea-conseil.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

// Following var can be set
// $permissiontoadd = permission or not to add a file (can use also $permission) and permission or not to edit file name or crop file (can use also $permtoedit)
// $modulepart  = for download
// $param       = param to add to download links
// $moreparam   = param to add to download link for the form_attach_new_file function
// $upload_dir
// $object
// $filearray
// $savingdocmask = dol_sanitizeFileName($object->ref).'-__file__';

// Protection to avoid direct call of template
if (empty($langs) || !is_object($langs)) {
	print "Error, template page can't be called as URL";
	exit(1);
}


$langs->load("link");
if (empty($relativepathwithnofile)) {
	$relativepathwithnofile = '';
}

// Set $permission from the $permissiontoadd var defined on calling page
if (!isset($permission)) {
	$permission = $permissiontoadd;
}
if (!isset($permtoedit)) {
	$permtoedit = $permissiontoadd;
}
if (!isset($param)) {
	$param = '';
}

// Drag and drop for up and down allowed on product, thirdparty, ...
// The drag and drop call the page core/ajax/row.php
// If you enable the move up/down of files here, check that page that include template set its sortorder on 'position_name' instead of 'name'
// Also the object->fk_element must be defined.
$disablemove = 1;
if (in_array($modulepart, array('product', 'produit', 'societe', 'user', 'ticket', 'holiday', 'expensereport'))) {
	$disablemove = 0;
}



/*
 * Confirm form to delete a file
 */

if ($action == 'deletefile' || $action == 'deletelink') {
	$langs->load("companies"); // Need for string DeleteFile+ConfirmDeleteFiles
	print $form->formconfirm(
		$_SERVER["PHP_SELF"].'?id='.$object->id.'&urlfile='.urlencode(GETPOST("urlfile")).'&linkid='.GETPOSTINT('linkid').(empty($param) ? '' : $param),
		$langs->trans('DeleteFile'),
		$langs->trans('ConfirmDeleteFile'),
		'confirm_deletefile',
		'',
		'',
		1
	);
}

// We define var to enable the feature to add prefix of uploaded files.
// Caller of this include can make
// $savingdocmask=dol_sanitizeFileName($object->ref).'-__file__';
if (!isset($savingdocmask) || getDolGlobalString('MAIN_DISABLE_SUGGEST_REF_AS_PREFIX')) {
	$savingdocmask = '';
	if (!getDolGlobalString('MAIN_DISABLE_SUGGEST_REF_AS_PREFIX')) {
		//var_dump($modulepart);
		if (in_array($modulepart, array(
			'facture_fournisseur',
			'commande_fournisseur',
			'facture',
			'commande',
			'propal',
			'payment',
			'supplier_proposal',
			'ficheinter',
			'contract',
			'expedition',
			'project',
			'project_task',
			'expensereport',
			'tax',
			'tax-vat',
			'produit',
			'product_batch',
			'bom',
			'mrp'
		))) {
			$savingdocmask = dol_sanitizeFileName($object->ref).'-__file__';
		}
		/*if (in_array($modulepart,array('member')))
		{
			$savingdocmask=$object->login.'___file__';
		}*/
	}
}

if (empty($formfile) || !is_object($formfile)) {
	$formfile = new FormFile($db);
}

// Show upload form (document and links)
$formfile->form_attach_new_file(
	$_SERVER["PHP_SELF"].'?id='.$object->id.(empty($withproject) ? '' : '&withproject=1').(empty($moreparam) ? '' : $moreparam),
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

//var_dump($modulepart);var_dump($upload_dir);

// List of document
$formfile->list_of_documents(
	$filearray,
	$object,
	$modulepart,
	$param,
	0,
	$relativepathwithnofile, // relative path with no file. For example "0/1"
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
$formfile->listOfLinks($object, $permission, $action, GETPOSTINT('linkid'), $param);
print "<br>";
