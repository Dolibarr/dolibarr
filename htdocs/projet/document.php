<?php
/* Copyright (C) 2010 Regis Houssin        <regis.houssin@inodbox.com>
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
 *	\brief      Page to managed related documents linked to a project
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array('projects', 'other'));

$action		= GETPOST('action','alpha');
$confirm	= GETPOST('confirm','alpha');
$id			= GETPOST('id','int');
$ref		= GETPOST('ref','alpha');
$mine 		= (GETPOST('mode','alpha') == 'mine' ? 1 : 0);
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects

// Security check
$socid=0;
//if ($user->societe_id > 0) $socid = $user->societe_id;    // For external user, no check is done on company because readability is managed by public status of project and assignement.
$result=restrictedArea($user,'projet',$id,'projet&project');

$object = new Project($db);

include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once
if(! empty($conf->global->PROJECT_ALLOW_COMMENT_ON_PROJECT) && method_exists($object, 'fetchComments') && empty($object->comments)) $object->fetchComments();

if ($id > 0 || ! empty($ref)) {
    $upload_dir = $conf->projet->dir_output . "/" . dol_sanitizeFileName($object->ref);
}

// Get parameters
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";



/*
 * Actions
 */

include_once DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';


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
	dol_fiche_head($head, 'document', $langs->trans("Project"), -1, ($object->public?'projectpub':'project'));

	// Files list constructor
	$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview.*\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
	$totalsize=0;
	foreach($filearray as $key => $file)
	{
		$totalsize+=$file['size'];
	}


	// Project card

	$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$morehtmlref='<div class="refidno">';
	// Title
	$morehtmlref.=$object->title;
	// Thirdparty
	if ($object->thirdparty->id > 0)
	{
	    $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1, 'project');
	}
	$morehtmlref.='</div>';

	// Define a complementary filter for search of next/prev ref.
	if (! $user->rights->projet->all->lire)
	{
	    $objectsListId = $object->getProjectsAuthorizedForUser($user,0,0);
	    $object->next_prev_filter=" rowid in (".(count($objectsListId)?join(',',array_keys($objectsListId)):'0').")";
	}

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border" width="100%">';

	// Files infos
	print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td>'.count($filearray).'</td></tr>';
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td>'.dol_print_size($totalsize,1,1).'</td></tr>';

	print "</table>\n";

	print '</div>';


	dol_fiche_end();

	$modulepart = 'project';
	$permission = ($userWrite > 0);
	$permtoedit = ($userWrite > 0);
	include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
}
else
{
	dol_print_error('','NoRecordFound');
}

// End of page
llxFooter();
$db->close();
