<?php
/* Copyright (C) 2011      Auguria     			<anthony.poiret@auguria.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *       \file       htdocs/compta/ajaxpayment.php
 *       \brief      File to return Ajax response on payment breakdown process
 *       \version    ajaxpayment.php,v 1.0 
 */

require('../main.inc.php');

// Getting the posted keys=>values, sanitize the ones who are from text inputs
	// from text inputs : total amount
$amountPayment = price2num($_POST['amountPayment']);
$amountPayment = is_numeric($amountPayment)? $amountPayment : 0;					// is a value
	// from text inputs : invoice amount payment
$amounts = $_POST['amounts'];														// is an array (need a foreach)
foreach ($amounts as $key => &$value)
{	
	$value = price2num($value);
	if(!is_numeric($value))unset($amounts[$key]); 
}																	
	// from dolibarr's object (no need to check)
$remains = $_POST['remains'];
	// from DOM elements : imgId (equals invoice id)
$currentInvId = $_POST['imgClicked'];	


// Treatment
$result = $amountPayment - array_sum($amounts);										// Remaining amountPayment
$toJsonArray = 	array();
if($currentInvId)																	// Here to breakdown
{
	// Get the current amount (from form) and the corresponding remainToPay (from invoice)
	$currentAmount = $amounts['amount_'.$currentInvId];
	$currentRemain = $remains['remain_'.$currentInvId];
	
	// Reset the substraction for this amount
	$result += price2num($currentAmount);		
	$currentAmount = 0;								
	if($result >= 0)			// then we need to calculate the amount to breakdown
	{
		$amountToBreakdown = ($result - $currentRemain >= 0 ? 
									$currentRemain : 								// Remain can be fully paid
									$currentRemain + ($result - $currentRemain));	// Remain can only partially be paid
		$currentAmount = $amountToBreakdown;						// In both cases, amount will take breakdown value
		$result -= $amountToBreakdown;								// And canceled substraction has been replaced by breakdown
	}	// else there's no need to calc anything, just reset the field (result is still < 0)
	$toJsonArray['amount_'.$currentInvId] = price2num($currentAmount)."";			// Param will exist only if an img has been clicked
}
// Encode to JSON to return
$toJsonArray['result'] = price2num($result)."";					
echo json_encode($toJsonArray);										// Printing the call's result

?>