<?php
/* Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
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
 *	\file			htdocs/core/lib/functions_be.lib.php
 *	\brief			A set of belgium functions for Dolibarr
 *					This file contains rare functions.
 */


/**
 * Calculate Structured Communication / BE Bank payment reference number
 *
 * @param 	string	$invoice_number	    Invoice number to generate payment reference
 * @param 	int     $invoice_type		Invoice type
 * @return 	string                      String structured communication for payment
 */
function dolBECalculateStructuredCommunication($invoice_number, $invoice_type)
{
	$invoice_number = preg_replace('/[^0-9]/', '', $invoice_number); // Keep only numbers

	// We complete with 0 and take the last 8 digits of the number are used to generate the reference base.
	$invoice_number = substr('00000000'.$invoice_number, -8);

	// Prefix with invoice type
	switch ($invoice_type) {
		case '0':
			$invoice_type = '20'; // invoice type standard
			break;
		case '1':
			$invoice_type = '30'; // invoice type replacement
			break;
		case '2':
			$invoice_type = '40'; // invoice type credit note
			break;
		case '3':
			$invoice_type = '21'; // invoice type deposit
			break;
		case '5':
			$invoice_type = '20'; // invoice type situation, force to standard
			break;
		default:
			$invoice_type = '00';
	}

	// Calculate module97
	$invoice_number = $invoice_type.$invoice_number;
	$mod97 = intval($invoice_number) % 97;
	$controlKey = ($mod97 === 0) ? 97 : $mod97;

	// Add the check digit at the end of the reference
	$invoice_number .= $controlKey;

	// Format reference as XXX/XXXX/XXXXX
	$part1 = '+++'.substr($invoice_number, 0, 3);
	$part2 = substr($invoice_number, 3, 4);
	$part3 = substr($invoice_number, 7, 5).'+++'; // Includes last 3 digits + 2 check digits

	$invoice_number = $part1 . '/' . $part2 . '/' . $part3;

	return $invoice_number;
}
