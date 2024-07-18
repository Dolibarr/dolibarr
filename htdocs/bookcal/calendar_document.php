<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alice Adminson <aadminson@example.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 */

/**
 *  \file       htdocs/bookcal/calendar_document.php
 *  \ingroup    bookcal
 *  \brief      Tab for documents linked to Calendar
 */

// Load Dolibarr environment
require '../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/bookcal/class/calendar.class.php';
require_once DOL_DOCUMENT_ROOT.'/bookcal/lib/bookcal_calendar.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("agenda", "companies", "other", "mails"));


$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm');
$id = (GETPOSTINT('socid') ? GETPOSTINT('socid') : GETPOSTINT('id'));
$ref = GETPOST('ref', 'alpha');

// Get parameters
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "name";
}
//if (! $sortfield) $sortfield="position_name";

// Initialize a technical objects
$object = new Calendar($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->bookcal->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('calendardocument', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals

if ($id > 0 || !empty($ref)) {
	$upload_dir = $conf->bookcal->multidir_output[$object->entity ? $object->entity : $conf->entity]."/calendar/".get_exdir(0, 0, 0, 1, $object);
}

// There is several ways to check permission.
// Set $enablepermissioncheck to 1 to enable a minimum low level of checks
$enablepermissioncheck = 0;
if ($enablepermissioncheck) {
	$permissiontoread = $user->hasRight('bookcal', 'calendar', 'read');
	$permissiontoadd = $user->hasRight('bookcal', 'calendar', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_linkedfiles.inc.php
} else {
	$permissiontoread = 1;
	$permissiontoadd = 1;
}

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->module, $object->id, $object->table_element, $object->element, 'fk_soc', 'rowid', $isdraft);
if (!isModEnabled("bookcal")) {
	accessforbidden();
}
if (!$permissiontoread) {
	accessforbidden();
}
if (empty($object->id)) {
	accessforbidden();
}



/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("Calendar").' - '.$langs->trans("Files");
$help_url = '';
//$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);

// Show tabs
$head = calendarPrepareHead($object);

print dol_get_fiche_head($head, 'document', $langs->trans("Calendar"), -1, $object->picto);


// Build file list
$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
$totalsize = 0;
foreach ($filearray as $key => $file) {
	$totalsize += $file['size'];
}

// Object card
// ------------------------------------------------------------
$linkback = '<a href="'.dol_buildpath('/bookcal/calendar_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

$morehtmlref = '<div class="refidno">';
/*
 // Ref customer
 $morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
 $morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
 // Thirdparty
 $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . (is_object($object->thirdparty) ? $object->thirdparty->getNomUrl(1) : '');
 // Project
 if (isModEnabled('project')) {
 $langs->load("projects");
 $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
 if ($permissiontoadd)
 {
 if ($action != 'classify')
 //$morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&token='.newToken().'&id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
 $morehtmlref.=' : ';
 if ($action == 'classify') {
 //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
 $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
 $morehtmlref.='<input type="hidden" name="action" value="classin">';
 $morehtmlref.='<input type="hidden" name="token" value="'.newToken().'">';
 $morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
 $morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
 $morehtmlref.='</form>';
 } else {
 $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
 }
 } else {
 if (!empty($object->fk_project)) {
 $proj = new Project($db);
 $proj->fetch($object->fk_project);
 $morehtmlref .= ': '.$proj->getNomUrl();
 } else {
 $morehtmlref .= '';
 }
 }
 }*/
$morehtmlref .= '</div>';

dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';

// Number of files
print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';

// Total size
print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

print '</table>';

print '</div>';

print dol_get_fiche_end();

$modulepart = 'bookcal';
$param = '&id='.$object->id;

//$relativepathwithnofile='calendar/' . dol_sanitizeFileName($object->id).'/';
$relativepathwithnofile = 'calendar/'.dol_sanitizeFileName($object->ref).'/';

include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';

// End of page
llxFooter();
$db->close();
