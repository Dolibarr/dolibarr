<?php
/* Copyright (C) 2011	Regis Houssin	<regis.houssin@inodbox.com>
 * Copyright (C) 2022       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	    \file       htdocs/core/lib/expensereport.lib.php
 *		\brief      Functions for module expensereport
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function expensereport_prepare_head($object)
{
	global $db, $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/expensereport/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("ExpenseReport");
	$head[$h][2] = 'card';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'expensereport', 'add', 'core');

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->expensereport->dir_output."/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/expensereport/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'documents';
	$h++;

	if (!getDolGlobalString('MAIN_DISABLE_NOTES_TAB')) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = DOL_URL_ROOT.'/expensereport/note.php?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		}
		$head[$h][2] = 'note';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/expensereport/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'expensereport', 'add', 'external');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'expensereport', 'remove');

	return $head;
}

/**
 * Returns an array with the tabs for the "Expense report payment" section
 * It loads tabs from modules looking for the entity payment
 *
 * @param	PaymentExpenseReport	$object		Current payment object
 * @return	array								Tabs for the payment section
 */
function payment_expensereport_prepare_head(PaymentExpenseReport $object)
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/expensereport/payment/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("ExpenseReportPayment");
	$head[$h][2] = 'payment';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'payment_expensereport');

	$head[$h][0] = DOL_URL_ROOT.'/expensereport/payment/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'payment_expensereport', 'remove');

	return $head;
}

/**
 *  Return array head with list of tabs to view object information.
 *
 *  @return	array   	        head array with tabs
 */
function expensereport_admin_prepare_head()
{
	global $langs, $conf, $user, $db;

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('expensereport');

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/admin/expensereport.php";
	$head[$h][1] = $langs->trans("ExpenseReports");
	$head[$h][2] = 'expensereport';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/admin/expensereport_rules.php";
	$head[$h][1] = $langs->trans("ExpenseReportsRules");
	$head[$h][2] = 'expenserules';
	$h++;

	if (getDolGlobalString('MAIN_USE_EXPENSE_IK')) {
		$head[$h][0] = DOL_URL_ROOT."/admin/expensereport_ik.php";
		$head[$h][1] = $langs->trans("ExpenseReportsIk");
		$head[$h][2] = 'expenseik';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'expensereport_admin');

	$head[$h][0] = DOL_URL_ROOT.'/admin/expensereport_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields");
	$nbExtrafields = $extrafields->attributes['expensereport']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributes';
	$h++;

	/*
	$head[$h][0] = DOL_URL_ROOT.'/fichinter/admin/fichinterdet_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsLines");
	$head[$h][2] = 'attributesdet';
	$h++;
	*/

	complete_head_from_modules($conf, $langs, null, $head, $h, 'expensereport_admin', 'remove');

	return $head;
}
