<?php
/* Copyright (C) 2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013 Cédric Salvador      <csalvador@gpcsolutions.fr>
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
 *	\file       htdocs/projet/document.php
 *	\ingroup    project
 *	\brief      Page de gestion des documents attachees a un projet
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$langs->load('projects');
$langs->load('other');

$action		= GETPOST('action','alpha');
$confirm	= GETPOST('confirm','alpha');
$id			= GETPOST('id','int');
$ref		= GETPOST('ref','alpha');
$mine 		= (GETPOST('mode','alpha') == 'mine' ? 1 : 0);
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects

// Security check
$socid=0;
if ($user->societe_id > 0) $socid=$user->societe_id;
$result=restrictedArea($user,'projet',$id,'');

$object = new Project($db);

include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once

if ($id > 0 || ! empty($ref)) {
    $upload_dir = $conf->projet->dir_output . "/" . dol_sanitizeFileName($object->ref);
}

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



/*
 * Actions
 */

include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_pre_headers.tpl.php';


/*
 * View
 */

$title=$langs->trans("Project").' - '.$langs->trans("Document").' - '.$object->ref.' '.$object->name;
if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/projectnameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->ref.' '.$object->name.' - '.$langs->trans("Document");
$help_url="EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";

llxHeader('',$title,$help_url);

$form = new Form($db);

if ($object->id > 0)
{
	$upload_dir = $conf->projet->dir_output.'/'.dol_sanitizeFileName($object->ref);

    // To verify role of users
    //$userAccess = $object->restrictedProjectArea($user,'read');
    $userWrite  = $object->restrictedProjectArea($user,'write');
    //$userDelete = $object->restrictedProjectArea($user,'delete');
    //print "userAccess=".$userAccess." userWrite=".$userWrite." userDelete=".$userDelete;

	$head = project_prepare_head($object);
	dol_fiche_head($head, 'document', $langs->trans("Project"), 0, ($object->public?'projectpub':'project'));

	// Files list constructor
	$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
	$totalsize=0;
	foreach($filearray as $key => $file)
	{
		$totalsize+=$file['size'];
	}

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

	// Company
	print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
	if (! empty($object->thirdparty->id)) print $object->thirdparty->getNomUrl(1);
	else print '&nbsp;';
	print '</td></tr>';

	// Visibility
	print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
	if ($object->public) print $langs->trans('SharedProject');
	else print $langs->trans('PrivateProject');
	print '</td></tr>';

	// Statut
	print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4).'</td></tr>';

	// Files infos
	print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

	print "</table>\n";
	print "</div>\n";

	$modulepart = 'project';
	$permission = ($userWrite > 0);
	include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';

}
else
{
	dol_print_error('','NoRecordFound');
}

llxFooter();

$db->close();
