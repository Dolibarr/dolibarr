<?php
/* Copyright (C) 2005-2012	Regis Houssin	  <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012	Juanjo Menent	  <jmenent@2byte.es>
 * Copyright (C) 2016       Laurent Destailleur <aldy@users.sourceforge.net>
 * Copyright (C) 2013       Florian Henry   <florian.henry@open-concept.pro>
 * Copyright (C) 2016	    Gilles Poirier  <glgpoirier@gmail.com>
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
 *	\file       htdocs/resource/note.php
 *	\ingroup    fichinter
 *	\brief      Fiche d'information sur une resource
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/resource.lib.php';

$langs->load('companies');
$langs->load("interventions");

$id = GETPOST('id','int');
$ref = GETPOST('ref', 'alpha');
$action=GETPOST('action','alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'resource', $id, 'resource');

$object = new DolResource($db);
$object->fetch($id,$ref);

$permissionnote=$user->rights->resource->write;	// Used by the include of actions_setnotes.inc.php


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php';	// Must be include, not includ_once


/*
 * View
 */

llxHeader();

$form = new Form($db);

if ($id > 0 || ! empty($ref))
{
	$head = resource_prepare_head($object);
	dol_fiche_head($head, 'note', $langs->trans('ResourceSingular'), 0, 'resource');

	print '<table class="border" width="100%">';
	print '<tr><td class="titlefield">'.$langs->trans("ResourceFormLabel_ref").'</td><td>';
	$linkback = $objet->ref.' <a href="list.php">'.$langs->trans("BackToList").'</a>';
	print $form->showrefnav($object, 'id', $linkback,1,"rowid");
	print '</td>';
	print '</tr>';

	// Resource type
	print '<tr>';
	print '<td>' . $langs->trans("ResourceType") . '</td>';
	print '<td>';
	print $object->type_label;
	print '</td>';
	print '</tr>';		print "</table>";

	print '<br>';
	$permission=$user->rights->resource->write;
	$cssclass='titlefield';
	include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

	dol_fiche_end();
}

llxFooter();
$db->close();
