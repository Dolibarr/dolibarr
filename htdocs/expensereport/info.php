<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 * 	\file       htdocs/expensereport/info.php
 * 	\ingroup    expensereport
 * 	\brief      Page to show a trip information
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/expensereport.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';

// Load translation files required by the page
$langs->load("trips");

// Security check
$id = GETPOST('id', 'int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'expensereport', $id, 'expensereport');


/*
 * View
 */

$form = new Form($db);

$title=$langs->trans("ExpenseReport") . " - " . $langs->trans("Info");
$helpurl="EN:Module_Expense_Reports";
llxHeader("", $title, $helpurl);

if ($id > 0 || ! empty($ref))
{
	$object = new ExpenseReport($db);
	$object->fetch($id, $ref);
	$object->info($object->id);

	$head = expensereport_prepare_head($object);

	dol_fiche_head($head, 'info', $langs->trans("ExpenseReport"), -1, 'trip');

	$linkback = '<a href="'.DOL_URL_ROOT.'/expensereport/list.php?restore_lastsearch_values=1'.(! empty($socid)?'&socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref='<div class="refidno">';
    $morehtmlref.='</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<br>';

	print '<table width="100%"><tr><td>';
    dol_print_object_info($object);
    print '</td></tr></table>';

    print '</div>';

    dol_fiche_end();
}

// End of page
llxFooter();
$db->close();
