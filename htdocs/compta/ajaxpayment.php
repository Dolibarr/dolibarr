<?php
/* Copyright (C) 2011 Auguria <anthony.poiret@auguria.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *       \file       htdocs/compta/ajaxpayment.php
 *       \brief      File to return Ajax response on payment breakdown process
 */

if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1'); // If there is no menu to show
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1'); // If we don't need to load the html.form.class.php
}

// Load Dolibarr environment
require '../main.inc.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$langs->load('compta');

// No permission check. This is just a formatting data service.


/*
 * View
 */

//init var
$invoice_type = GETPOSTINT('invoice_type');
$amountPayment = GETPOST('amountPayment');
$amounts = GETPOST('amounts'); // from text inputs : invoice amount payment (check required)
$remains = GETPOST('remains'); // from dolibarr's object (no need to check)
$currentInvId = GETPOST('imgClicked'); // from DOM elements : imgId (equals invoice id)

// Getting the posted keys=>values, sanitize the ones who are from text inputs
$amountPayment = $amountPayment != '' ? (is_numeric(price2num($amountPayment)) ? price2num($amountPayment) : '') : ''; // keep void if not a valid entry

// Clean checkamounts
if (is_array($amounts)) {
	foreach ($amounts as $key => $value) {
		$value = price2num($value);
		$amounts[$key] = $value;
		if (empty($value)) {
			unset($amounts[$key]);
		}
	}
}
// Clean remains
if (is_array($remains)) {
	foreach ($remains as $key => $value) {
		$value = price2num($value);
		$remains[$key] = ($invoice_type == 2 ? -1 : 1) * (float) $value;
		if (empty($value)) {
			unset($remains[$key]);
		}
	}
} elseif ($remains) {
	$remains = array(price2num($remains));
} else {
	$remains = array();
}

// Treatment
$result = ($amountPayment != '') ? ((float) $amountPayment - array_sum($amounts)) : array_sum($amounts); // Remaining amountPayment
$toJsonArray = array();
$totalRemaining = price2num(array_sum($remains));
$toJsonArray['label'] = $amountPayment == '' ? '' : $langs->transnoentities('RemainingAmountPayment');
if ($currentInvId) {																	// Here to breakdown
	// Get the current amount (from form) and the corresponding remainToPay (from invoice)
	$currentAmount = $amounts['amount_'.$currentInvId];
	$currentRemain = $remains['remain_'.$currentInvId];

	// If amountPayment isn't filled, breakdown invoice amount, else breakdown from amountPayment
	if ($amountPayment == '') {
		// Check if current amount exists in amounts
		$amountExists = array_key_exists('amount_'.$currentInvId, $amounts);
		if ($amountExists) {
			$remainAmount = $currentRemain - $currentAmount; // To keep value between curRemain and curAmount
			$result += $remainAmount; // result must be deduced by
			$currentAmount += $remainAmount; // curAmount put to curRemain
		} else {
			$currentAmount = $currentRemain;
			$result += $currentRemain;
		}
	} else {
		// Reset the subtraction for this amount
		$result += price2num($currentAmount);
		$currentAmount = 0;

		if ($result >= 0) {			// then we need to calculate the amount to breakdown
			$amountToBreakdown = ($result - $currentRemain >= 0 ?
										$currentRemain : // Remain can be fully paid
										$currentRemain + ($result - $currentRemain)); // Remain can only partially be paid
			$currentAmount = $amountToBreakdown; // In both cases, amount will take breakdown value
			$result -= $amountToBreakdown; // And canceled subtraction has been replaced by breakdown
		}	// else there's no need to calc anything, just reset the field (result is still < 0)
	}
	$toJsonArray['amount_'.$currentInvId] = price2num($currentAmount); // Param will exist only if an img has been clicked
}

$toJsonArray['makeRed'] = ($totalRemaining < price2num($result) || price2num($result) < 0);
$toJsonArray['result'] = price($result); // Return value to user format
$toJsonArray['resultnum'] = price2num($result); // Return value to numeric format

// Encode to JSON to return
echo json_encode($toJsonArray); // Printing the call's result
