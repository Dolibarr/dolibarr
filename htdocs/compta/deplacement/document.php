<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur   <eldy@users.sourceforge.net>
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
 */

/**
 *       \file       htdocs/compta/deplacement/document.php
 *       \ingroup    deplacement
 *       \brief      Page of linked files onto trip and expenses
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/deplacement/class/deplacement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/trip.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array('other', 'trips', 'companies', 'interventions'));

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
	$sortfield = "name";
}


$object = new Deplacement($db);
$object->fetch($id, $ref);

$upload_dir = $conf->deplacement->dir_output.'/'.dol_sanitizeFileName($object->ref);
$modulepart = 'trip';

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'deplacement', $id, '');

$permissiontoadd = $user->rights->deplacement->creer;	// Used by the include of actions_dellink.inc.php


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';


/*
 * View
 */

$form = new Form($db);

llxHeader("", "", $langs->trans("TripCard"));


if ($object->id) {
	$object->fetch_thirdparty();

	$head = trip_prepare_head($object);

	print dol_get_fiche_head($head, 'documents', $langs->trans("TripCard"), 0, 'trip');


	// Build file list
	$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ?SORT_DESC:SORT_ASC), 1);
	$totalsize = 0;
	foreach ($filearray as $key => $file) {
		$totalsize += $file['size'];
	}


	print '<table class="border tableforfield centpercent">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/deplacement/list.php'.(!empty($socid) ? '?socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	// Ref
	print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td>';
	print $form->showrefnav($object, 'id', $linkback, 1, 'rowid', 'ref', '');
	print '</td></tr>';

	// Societe
	//print "<tr><td>".$langs->trans("Company")."</td><td>".$object->client->getNomUrl(1)."</td></tr>";

	print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.dol_print_size($totalsize, 1, 1).'</td></tr>';
	print '</table>';

	print '</div>';

	$modulepart = 'deplacement';
	$permissiontoadd = $user->rights->deplacement->creer;
	$param = '&id='.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
} else {
	print $langs->trans("ErrorUnknown");
}

// End of page
llxFooter();
$db->close();
