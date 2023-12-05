<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2009 Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2005      Simon TOSSER          <simon@kornog-computing.com>
 * Copyright (C) 2011-2012 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *       \file       htdocs/expensereport/document.php
 *       \ingroup    expensereport
 *       \brief      Page of linked files onto trip and expens reports
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/expensereport.lib.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';

// Load translation files required by the page
$langs->loadLangs(array("other", "trips", "companies", "interventions"));

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');

$childids = $user->getAllChildIds(1);

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


$object = new ExpenseReport($db);
if (!$object->fetch($id, $ref) > 0) {
	dol_print_error($db);
}

$upload_dir = $conf->expensereport->dir_output.'/'.dol_sanitizeFileName($object->ref);
$modulepart = 'trip';

// Load object
//include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'expensereport', $id, 'expensereport');

if ($object->id > 0) {
	// Check current user can read this expense report
	$canread = 0;
	if ($user->hasRight('expensereport', 'readall')) {
		$canread = 1;
	}
	if ($user->hasRight('expensereport', 'lire') && in_array($object->fk_user_author, $childids)) {
		$canread = 1;
	}
	if (!$canread) {
		accessforbidden();
	}
}

$permissiontoadd = $user->rights->expensereport->creer;	// Used by the include of actions_dellink.inc.php


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("ExpenseReport")." - ".$langs->trans("Documents");
$help_url = "EN:Module_Expense_Reports|FR:Module_Notes_de_frais";

llxHeader("", $title, $help_url);

if ($object->id) {
	$object->fetch_thirdparty();

	$head = expensereport_prepare_head($object);

	print dol_get_fiche_head($head, 'documents', $langs->trans("ExpenseReport"), -1, 'trip');

	$linkback = '<a href="'.DOL_URL_ROOT.'/expensereport/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	// Build file list
	$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
	$totalsize = 0;
	foreach ($filearray as $key => $file) {
		$totalsize += $file['size'];
	}

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield centpercent">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/expensereport/list.php'.(!empty($socid) ? '?socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.dol_print_size($totalsize, 1, 1).'</td></tr>';
	print '</table>';

	print '</div>';

	print dol_get_fiche_end();


	$modulepart = 'expensereport';
	$permissiontoadd = $user->rights->expensereport->creer;
	$permtoedit = $user->rights->expensereport->creer;
	$param = '&id='.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
} else {
	print $langs->trans("ErrorUnknown");
}

// End of page
llxFooter();
$db->close();
