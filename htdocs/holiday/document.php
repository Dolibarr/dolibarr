<?php
/* Copyright (C) 2003-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Marc Barilley / Ocebo   <marc@ocebo.com>
 * Copyright (C) 2005-2009  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2005       Simon TOSSER            <simon@kornog-computing.com>
 * Copyright (C) 2011-2012  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2018-2022  Frédéric France         <frederic.france@netlogic.fr>
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
 *       \file       htdocs/holiday/document.php
 *       \ingroup    fichinter
 *       \brief      Page des documents joints sur les contrats
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/holiday.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array('other', 'holiday', 'companies'));

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');

// Get parameters
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
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
	$sortfield = "position_name";
}


$childids = $user->getAllChildIds(1);

$morefilter = '';
if (getDolGlobalString('HOLIDAY_HIDE_FOR_NON_SALARIES')) {
	$morefilter = 'AND employee = 1';
}

$object = new Holiday($db);

$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

if (($id > 0) || $ref) {
	$object->fetch($id, $ref);

	// Check current user can read this leave request
	$canread = 0;
	if ($user->hasRight('holiday', 'readall')) {
		$canread = 1;
	}
	if ($user->hasRight('holiday', 'read') && in_array($object->fk_user, $childids)) {
		$canread = 1;
	}
	if (!$canread) {
		accessforbidden();
	}
}


$upload_dir = $conf->holiday->dir_output.'/'.get_exdir(0, 0, 0, 1, $object, '');
$modulepart = 'holiday';

// Protection if external user
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'holiday', $object->id, 'holiday');

$permissiontoadd = $user->rights->holiday->write; // Used by the include of actions_setnotes.inc.php


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';


/*
 * View
 */

$form = new Form($db);

$listhalfday = array('morning'=>$langs->trans("Morning"), "afternoon"=>$langs->trans("Afternoon"));
$title = $langs->trans("Leave").' - '.$langs->trans("Files");

llxHeader('', $title);

if ($object->id) {
	$valideur = new User($db);
	$valideur->fetch($object->fk_validator);

	$userRequest = new User($db);
	$userRequest->fetch($object->fk_user);

	$head = holiday_prepare_head($object);

	print dol_get_fiche_head($head, 'documents', $langs->trans("CPTitreMenu"), -1, 'holiday');


	// Build file list
	$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
	$totalsize = 0;
	foreach ($filearray as $key => $file) {
		$totalsize += $file['size'];
	}


	$linkback = '<a href="'.DOL_URL_ROOT.'/holiday/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref');


	print '<div class="fichecenter">';
	//print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield centpercent">';

	print '<tr>';
	print '<td class="titlefield">'.$langs->trans("User").'</td>';
	print '<td>';
	print $userRequest->getNomUrl(-1, 'leave');
	print '</td></tr>';

	// Type
	print '<tr>';
	print '<td>'.$langs->trans("Type").'</td>';
	print '<td>';
	$typeleaves = $object->getTypes(1, -1);
	$labeltoshow = (($typeleaves[$object->fk_type]['code'] && $langs->trans($typeleaves[$object->fk_type]['code']) != $typeleaves[$object->fk_type]['code']) ? $langs->trans($typeleaves[$object->fk_type]['code']) : $typeleaves[$object->fk_type]['label']);
	print empty($labeltoshow) ? $langs->trans("TypeWasDisabledOrRemoved", $object->fk_type) : $labeltoshow;
	print '</td>';
	print '</tr>';

	$starthalfday = ($object->halfday == -1 || $object->halfday == 2) ? 'afternoon' : 'morning';
	$endhalfday = ($object->halfday == 1 || $object->halfday == 2) ? 'morning' : 'afternoon';

	print '<tr>';
	print '<td>';
	print $form->textwithpicto($langs->trans('DateDebCP'), $langs->trans("FirstDayOfHoliday"));
	print '</td>';
	print '<td>'.dol_print_date($object->date_debut, 'day');
	print ' &nbsp; &nbsp; ';
	print '<span class="opacitymedium">'.$langs->trans($listhalfday[$starthalfday]).'</span>';
	print '</td>';
	print '</tr>';

	print '<tr>';
	print '<td>';
	print $form->textwithpicto($langs->trans('DateFinCP'), $langs->trans("LastDayOfHoliday"));
	print '</td>';
	print '<td>'.dol_print_date($object->date_fin, 'day');
	print ' &nbsp; &nbsp; ';
	print '<span class="opacitymedium">'.$langs->trans($listhalfday[$endhalfday]).'</span>';
	print '</td>';
	print '</tr>';

	// Nb days consumed
	print '<tr>';
	print '<td>';
	$htmlhelp = $langs->trans('NbUseDaysCPHelp');
	$includesaturday = (isset($conf->global->MAIN_NON_WORKING_DAYS_INCLUDE_SATURDAY) ? $conf->global->MAIN_NON_WORKING_DAYS_INCLUDE_SATURDAY : 1);
	$includesunday   = (isset($conf->global->MAIN_NON_WORKING_DAYS_INCLUDE_SUNDAY) ? $conf->global->MAIN_NON_WORKING_DAYS_INCLUDE_SUNDAY : 1);
	if ($includesaturday) {
		$htmlhelp .= '<br>'.$langs->trans("DayIsANonWorkingDay", $langs->trans("Saturday"));
	}
	if ($includesunday) {
		$htmlhelp .= '<br>'.$langs->trans("DayIsANonWorkingDay", $langs->trans("Sunday"));
	}
	print $form->textwithpicto($langs->trans('NbUseDaysCP'), $htmlhelp);
	print '</td>';
	print '<td>'.num_open_day($object->date_debut_gmt, $object->date_fin_gmt, 0, 1, $object->halfday).'</td>';
	print '</tr>';

	if ($object->statut == 5) {
		print '<tr>';
		print '<td>'.$langs->trans('DetailRefusCP').'</td>';
		print '<td>'.$object->detail_refuse.'</td>';
		print '</tr>';
	}

	// Description
	print '<tr>';
	print '<td>'.$langs->trans('DescCP').'</td>';
	print '<td>'.nl2br($object->description).'</td>';
	print '</tr>';

	print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.dol_print_size($totalsize, 1, 1).'</td></tr>';

	print '</tbody>';
	print '</table>'."\n";
	/*
	print '</div>';
	print '<div class="fichehalfright">';

	print '<div class="underbanner clearboth"></div>';

	// Info workflow
	print '<table class="border tableforfield centpercent">'."\n";
	print '<tbody>';

	if (!empty($object->fk_user_create)) {
		$userCreate=new User($db);
		$userCreate->fetch($object->fk_user_create);
		print '<tr>';
		print '<td class="titlefield">'.$langs->trans('RequestByCP').'</td>';
		print '<td>'.$userCreate->getNomUrl(-1).'</td>';
		print '</tr>';
	}

	print '<tr>';
	print '<td class="titlefield">'.$langs->trans('ReviewedByCP').'</td>';
	print '<td>'.$valideur->getNomUrl(-1).'</td>';
	print '</tr>';

	print '<tr>';
	print '<td>'.$langs->trans('DateCreation').'</td>';
	print '<td>'.dol_print_date($object->date_create,'dayhour').'</td>';
	print '</tr>';
	if ($object->statut == 3) {
		print '<tr>';
		print '<td>'.$langs->trans('DateValidCP').'</td>';
		print '<td>'.dol_print_date($object->date_valid,'dayhour').'</td>';
		print '</tr>';
	}
	if ($object->statut == 4) {
		print '<tr>';
		print '<td>'.$langs->trans('DateCancelCP').'</td>';
		print '<td>'.dol_print_date($object->date_cancel,'dayhour').'</td>';
		print '</tr>';
	}
	if ($object->statut == 5) {
		print '<tr>';
		print '<td>'.$langs->trans('DateRefusCP').'</td>';
		print '<td>'.dol_print_date($object->date_refuse,'dayhour').'</td>';
		print '</tr>';
	}
	print '</tbody>';
	print '</table>';

	print '</div>'; */
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	$permissiontoadd = $user->rights->holiday->write;
	$permtoedit = $user->rights->holiday->write;
	$param = '&id='.$object->id;
	$relativepathwithnofile = dol_sanitizeFileName($object->ref).'/';
	$savingdocmask = dol_sanitizeFileName($object->ref).'-__file__';

	include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
} else {
	print $langs->trans("ErrorUnknown");
}

// End of page
llxFooter();
$db->close();
