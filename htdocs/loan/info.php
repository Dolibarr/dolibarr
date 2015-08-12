<?php
/* Copyright (C) 2014		Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
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
 *	\file       htdocs/loan/info.php
 *	\ingroup    loan
 *	\brief      Page with info about loan
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/loan.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

$langs->load("compta");
$langs->load("bills");
$langs->load("loan");

$id=GETPOST('id','int');
$action=GETPOST("action");

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'loan', $id, '','');


/*
 * View
 */

$help_url='EN:Module_Loan|FR:Module_Emprunt';
llxHeader("",$langs->trans("Loan"),$help_url);

if ($id > 0) {
	$loan = new Loan($db);
	$loan->fetch($id);
	$loan->info($id);

	$head = loan_prepare_head($loan);

	dol_fiche_head($head, 'info', $langs->trans("Loan"), 0, 'bill');

	print '<table width="100%"><tr><td>';
	dol_print_object_info($loan);
	print '</td></tr></table>';

	print '</div>';
}
else
{
    // $id ?
}

llxFooter();

$db->close();
