<?php
/* Copyright (C) 2005-2012	Regis Houssin	<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012	Juanjo Menent	<jmenent@2byte.es>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
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
 *	\file       htdocs/fichinter/note.php
 *	\ingroup    fichinter
 *	\brief      Fiche d'information sur une fiche d'intervention
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fichinter.lib.php';

$langs->load('companies');
$langs->load("interventions");

$id = GETPOST('id','int');
$ref = GETPOST('ref', 'alpha');
$action=GETPOST('action','alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'ficheinter', $id, 'fichinter');

$object = new Fichinter($db);
$object->fetch($id,$ref);

$permissionnote=$user->rights->ficheinter->creer;	// Used by the include of actions_setnotes.inc.php

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
	$societe = new Societe($db);
	if ($societe->fetch($object->socid))
	{
		$head = fichinter_prepare_head($object);
		dol_fiche_head($head, 'note', $langs->trans('InterventionCard'), 0, 'intervention');

		print '<table class="border" width="100%">';

		$linkback = '<a href="'.DOL_URL_ROOT.'/fichinter/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

		print '<tr><td width="25%">'.$langs->trans('Ref').'</td><td colspan="3">';
		print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
		print '</td></tr>';

		// Company
		print '<tr><td>'.$langs->trans('Company').'</td><td colspan="3">'.$societe->getNomUrl(1).'</td></tr>';

		print "</table>";

		print '<br>';

		include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

		dol_fiche_end();
	}
}

llxFooter();
$db->close();
