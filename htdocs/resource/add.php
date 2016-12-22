<?php
/* Copyright (C) 2013	Jean-François Ferry	 <jfefe@aternatik.fr>
 * Copyright (C) 2015	Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015	Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file       resource/add.php
 *		\ingroup    resource
 *		\brief      Page to manage resource object
 *					Initialy built by build_class_from_table on 2013-07-24 16:03
 */

require '../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/resource/class/resource.class.php';
require_once DOL_DOCUMENT_ROOT.'/resource/class/html.formresource.class.php';

// Load traductions files required by page
$langs->load("resource");
$langs->load("companies");
$langs->load("other");
$langs->load("resource");

// Get parameters
$id			= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$cancel		= GETPOST('cancel','alpha');
if (empty($sortorder)) $sortorder="DESC";
if (empty($sortfield)) $sortfield="t.rowid";
if (empty($arch)) $arch = 0;

if ($page == -1) {
	$page = 0 ;
}

$limit = $conf->global->limit;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;


// Protection if external user
if ($user->societe_id > 0)
{
	accessforbidden();
}

$object = new Resource($db);

if ($action == 'confirm_add_resource')
{
	if (! $cancel)
	{
		$error='';

		$ref=GETPOST('ref','alpha');
		$description=GETPOST('description','alpha');
		$fk_code_type_resource=GETPOST('fk_code_type_resource','alpha');

		if (empty($ref))
		{
			$mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Ref"));
			setEventMessages($mesg, null, 'errors');
			$error++;
		}

		if (! $error)
		{
			$object=new Resource($db);
			$object->ref=$ref;
			$object->description=$description;
			$object->fk_code_type_resource=$fk_code_type_resource;

			$result=$object->create($user);
			if ($result > 0)
			{
				// Creation OK
				$db->commit();
				setEventMessages($langs->trans('ResourceCreatedWithSuccess'), null, 'mesgs');
				Header("Location: card.php?id=" . $object->id);
				return;
			}
			else
			{
				// Creation KO
				setEventMessages($object->error, $object->errors, 'errors');
				$action = '';
			}
		}
		else
		{
			$action = '';
		}
	}
	else
	{
		Header("Location: list.php");
	}
}


/*
 * View
 */

$form=new Form($db);
$formresource = new FormResource($db);

if (! $action)
{
	$pagetitle=$langs->trans('AddResource');
	llxHeader('',$pagetitle,'');
	print load_fiche_titre($pagetitle,'','title_generic');

	print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" name="add_resource">';
	print '<input type="hidden" name="action" value="confirm_add_resource" />';

	dol_fiche_head('');

	print '<table class="border" width="100%">';

	// Ref / label
	$field = 'ref';
	print '<tr>';
	print '<td class="fieldrequired">';
	print $langs->trans('ResourceFormLabel_'.$field);
	print '</td>';
	print '<td>';
	print '<input type="text" name="'.$field.'" value="'.$$field.'" />';
	print '</td>';
	print '</tr>';

	// Type
	print '<tr><td width="20%">'.$langs->trans("ResourceType").'</td>';
	print '<td>';
	$ret = $formresource->select_types_resource($object->fk_code_type_resource, 'fk_code_type_resource', '', 2, 1);
	print '</td></tr>';

	// Description
	$field = 'description';
	print '<tr>';
	print '<td class="tdtop">';
	print $langs->trans('ResourceFormLabel_'.$field);
	print '</td>';
	print '<td>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor($field, $$field, 160, '', '', false);
	$doleditor->Create();
	print '</td>';
	print '</tr>';

	print '</table>';

	dol_fiche_end('');

	echo '<div align="center">',
	'<input type="submit" class="button" name="add" value="'.$langs->trans('Save').'" />',
	' &nbsp; ',
	'<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'" />',
	'</div>';

	print '</form>';
}


// End of page
llxFooter();
$db->close();
