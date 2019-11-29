<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       mo_document.php
 *  \ingroup    mrp
 *  \brief      Tab for documents linked to Mo
 */

// Load Dolibarr environment
require '../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
dol_include_once('/mrp/class/mo.class.php');
dol_include_once('/mrp/lib/mrp_mo.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("mrp","companies","other","mails"));


$action=GETPOST('action', 'aZ09');
$confirm=GETPOST('confirm');
$id=(GETPOST('socid', 'int') ? GETPOST('socid', 'int') : GETPOST('id', 'int'));
$ref = GETPOST('ref', 'alpha');

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$result = restrictedArea($user, 'mrp', $id);

// Get parameters
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";
//if (! $sortfield) $sortfield="position_name";

// Initialize technical objects
$object=new Mo($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction=$conf->mrp->dir_output . '/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('modocument','globalcard'));     // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

//if ($id > 0 || ! empty($ref)) $upload_dir = $conf->mrp->multidir_output[$object->entity?$object->entity:$conf->entity] . "/mo/" . dol_sanitizeFileName($object->id);
if ($id > 0 || ! empty($ref)) $upload_dir = $conf->mrp->multidir_output[$object->entity?$object->entity:$conf->entity] . "/mo/" . dol_sanitizeFileName($object->ref);


/*
 * Actions
 */

include_once DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';


/*
 * View
 */

$form = new Form($db);

$title=$langs->trans("Mo").' - '.$langs->trans("Files");
$help_url='';
//$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);

if ($object->id)
{
	/*
	 * Show tabs
	 */
	$head = moPrepareHead($object);

	dol_fiche_head($head, 'document', $langs->trans("MO"), -1, $object->picto);


	// Build file list
	$filearray=dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC), 1);
	$totalsize=0;
	foreach($filearray as $key => $file)
	{
		$totalsize+=$file['size'];
	}

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="' .dol_buildpath('/mrp/mo_list.php', 1) . '?restore_lastsearch_values=1' . (! empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

    print '<div class="fichecenter">';

    print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">';

	// Number of files
	print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';

	// Total size
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

	print '</table>';

	print '</div>';

	dol_fiche_end();

	$modulepart = 'mrp';
	$permission = $user->rights->mrp->write;
	$permtoedit = $user->rights->mrp->write;
	$param = '&id=' . $object->id;

	//$relativepathwithnofile='mo/' . dol_sanitizeFileName($object->id).'/';
	$relativepathwithnofile='mo/' . dol_sanitizeFileName($object->ref).'/';

	include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
}
else
{
	accessforbidden('', 0, 1);
}

// End of page
llxFooter();
$db->close();
