<?php
/* Copyright (C) 2017 ATM Consulting      <contact@atm-consulting.fr>
 * Copyright (C) 2017 Pierre-Henry Favre  <phf@atm-consulting.fr>
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
 *       \file       htdocs/expensereport/ajax/ajaxik.php
 *       \ingroup    expensereport
 *       \brief      File to return Ajax response on third parties request
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}

$res = 0;
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport_ik.class.php';

// Load translation files required by the page
$langs->loadlangs(array('errors', 'trips'));

$fk_expense = GETPOST('fk_expense', 'int');
$fk_c_exp_tax_cat = GETPOST('fk_c_exp_tax_cat', 'int');
$vatrate = GETPOST('vatrate', 'int');
$qty = GETPOST('qty', 'int');

// Security check
$result = restrictedArea($user, 'expensereport', $fk_expense, 'expensereport');


/*
 * View
 */

top_httphead('application/json');

$rep = new stdClass();
$rep->response_status = 0;
$rep->data = null;
$rep->errorMessage = '';


if (empty($fk_expense) || $fk_expense < 0) {
	$rep->errorMessage =   $langs->transnoentitiesnoconv('ErrorBadValueForParameter', $fk_expense, 'fk_expense');
} elseif (empty($fk_c_exp_tax_cat) || $fk_c_exp_tax_cat < 0) {
	$rep->errorMessage =  $langs->transnoentitiesnoconv('ErrorBadValueForParameter', $fk_c_exp_tax_cat, 'fk_c_exp_tax_cat');

	$rep->response_status = 'error';
} else {
	// @see ndfp.class.php:3576 (method: compute_total_km)
	$expense = new ExpenseReport($db);
	if ($expense->fetch($fk_expense) <= 0) {
		$rep->errorMessage =  $langs->transnoentitiesnoconv('ErrorRecordNotFound');
		$rep->response_status = 'error';
	} else {
		$userauthor = new User($db);
		if ($userauthor->fetch($expense->fk_user_author) <= 0) {
			$rep->errorMessage =  $langs->transnoentitiesnoconv('ErrorRecordNotFound');
			$rep->response_status = 'error';
		} else {
			$expense = new ExpenseReport($db);
			$result = $expense->fetch($fk_expense);
			if ($result) {
				$result = $expense->computeTotalKm($fk_c_exp_tax_cat, $qty, $vatrate);
				if ($result < 0) {
					$rep->errorMessage = $langs->trans('errorComputeTtcOnMileageExpense');
					$rep->response_status = 'error';
				} else {
					$rep->data = $result;
					$rep->response_status = 'success';
				}
			}
		}
	}
}
echo json_encode($rep);
