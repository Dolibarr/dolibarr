<?php
/* Copyright (C)    2013    Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C)    2013    Laurent Destailleur	<eldy@users.sourceforge.net>
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

$langs->load("link");

/*
 * Confirm suppression
 */

if ($action == 'delete')
{
	$langs->load("companies");	// Need for string DeleteFile+ConfirmDeleteFiles
	$ret = $form->form_confirm(
			$_SERVER["PHP_SELF"] . '?id=' . $object->id . '&urlfile=' . urlencode(GETPOST("urlfile")) . '&linkid=' . GETPOST('linkid', 'int'),
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

// Show upload form (document and links)
$formfile->form_attach_new_file(
    $_SERVER["PHP_SELF"].'?id='.$object->id.(empty($withproject)?'':'&withproject=1'),
    '',
    0,
    0,
    $permission,
    50,
    $object
);

// List of document
$formfile->list_of_documents(
    $filearray,
    $object,
    $modulepart,
    $param,
    0,
    '',
    $permission
);

print "<br>";
//List of links
$formfile->listOfLinks($object, $permission, $action, GETPOST('linkid', 'int'));
print "<br>";
