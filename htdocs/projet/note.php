<?php
/* Copyright (C) 2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/projet/note.php
 *	\ingroup    project
 *	\brief      Fiche d'information sur un projet
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';

$langs->load('projects');

$action=GETPOST('action');
$id = GETPOST('id','int');
$ref= GETPOST('ref');

$mine = $_REQUEST['mode']=='mine' ? 1 : 0;
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects

$object = new Project($db);

include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once

// Security check
$socid=0;
if ($user->societe_id > 0) $socid=$user->societe_id;
$result = restrictedArea($user, 'projet', $id);

$permissionnote=$user->rights->projet->creer;	// Used by the include of actions_setnotes.inc.php


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php';	// Must be include, not includ_once


/*
 * View
 */

$title=$langs->trans("Project").' - '.$langs->trans("Note").' - '.$object->ref.' '.$object->name;
if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/projectnameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->ref.' '.$object->name.' - '.$langs->trans("Note");
$help_url="EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";
llxHeader("",$title,$help_url);

$form = new Form($db);
$userstatic=new User($db);

$now=dol_now();

if ($id > 0 || ! empty($ref))
{
	// To verify role of users
	//$userAccess = $object->restrictedProjectArea($user,'read');
	$userWrite  = $object->restrictedProjectArea($user,'write');
	//$userDelete = $object->restrictedProjectArea($user,'delete');
	//print "userAccess=".$userAccess." userWrite=".$userWrite." userDelete=".$userDelete;

	$head = project_prepare_head($object);
	dol_fiche_head($head, 'notes', $langs->trans('Project'), 0, ($object->public?'projectpub':'project'));

	print '<table class="border" width="100%">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php">'.$langs->trans("BackToList").'</a>';

	// Ref
	print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td>';
	// Define a complementary filter for search of next/prev ref.
	if (! $user->rights->projet->all->lire)
	{
		$projectsListId = $object->getProjectsAuthorizedForUser($user,0,0);
		$object->next_prev_filter=" rowid in (".(count($projectsListId)?join(',',array_keys($projectsListId)):'0').")";
	}
	print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
	print '</td></tr>';

	// Label
	print '<tr><td>'.$langs->trans("Label").'</td><td>'.$object->title.'</td></tr>';

	// Third party
	print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
	if ($object->thirdparty->id > 0) print $object->thirdparty->getNomUrl(1);
	else print'&nbsp;';
	print '</td></tr>';

	// Visibility
	print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
	if ($object->public) print $langs->trans('SharedProject');
	else print $langs->trans('PrivateProject');
	print '</td></tr>';

	// Statut
	print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4).'</td></tr>';

	// Date start
	print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
	print dol_print_date($object->date_start,'day');
	print '</td></tr>';

	// Date end
	print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
	print dol_print_date($object->date_end,'day');
	print '</td></tr>';

	print "</table>";

	print '<br>';

	$colwidth=30;
	include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

	dol_fiche_end();
}

llxFooter();

$db->close();
