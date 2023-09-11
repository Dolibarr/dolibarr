<?php
/* Copyright (C) 2005-2015  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015       Charlie BENKE        <charlie@patas-monkey.com>
 * Copyright (C) 2017-2019  Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2021		Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
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
 *	\file       htdocs/salaries/info.php
 *	\ingroup    salaries
 *	\brief      Page with info about salaries contribution
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/salaries.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("compta", "bills", "users", "salaries", "hrm"));

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

$object = new Salary($db);
$childids = $user->getAllChildIds(1);
if ($id > 0 || !empty($ref)) {
	$object->fetch($id, $ref);

	// Check current user can read this salary
	$canread = 0;
	if (!empty($user->rights->salaries->readall)) {
		$canread = 1;
	} elseif (!empty($user->rights->salaries->read) && $object->fk_user > 0 && in_array($object->fk_user, $childids)) {
		$canread = 1;
	}

	if (!$canread) {
		accessforbidden();
	}
}

// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid) {
	$socid = $user->socid;
}
restrictedArea($user, 'salaries', $object->id, 'salary', '');


/*
 * View
 */

$title = $langs->trans('Salary')." - ".$langs->trans('Info');
$help_url = "";
llxHeader("", $title, $help_url);

$object = new Salary($db);
$object->fetch($id);
$object->info($id);

$head = salaries_prepare_head($object);

print dol_get_fiche_head($head, 'info', $langs->trans("SalaryPayment"), -1, 'salary');

$linkback = '<a href="'.DOL_URL_ROOT.'/salaries/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

$morehtmlref = '<div class="refidno">';

$userstatic = new User($db);
$userstatic->fetch($object->fk_user);

$morehtmlref .= $langs->trans('Employee').' : '.$userstatic->getNomUrl(1);
$morehtmlref .= '</div>';

dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', '');

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<br>';

print '<table width="100%"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';

print '</div>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
