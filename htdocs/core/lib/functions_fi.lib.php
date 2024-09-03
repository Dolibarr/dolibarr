<?php
/* Copyright (C) 2010 Laurent Destailleur         <eldy@users.sourceforge.net>
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
 *	\file			htdocs/core/lib/functions_fi.lib.php
 *	\brief			A set of finish functions for Dolibarr
 *					This file contains rare functions.
 */


/**
 * Calculate Creditor Reference RF / FI Bank payment reference number
 *
 * @param 	string	$invoice_number	    Invoice number to generate payment reference
 * @param 	int     $statut             Invoice status, if draft (0), no reference generating
 * @param   string  $use_rf             false/(0) generate FI Bank payment reference
 *                                      true/(1) Generate European Reference number based on the global
 *                                      Structured Creditor Reference standard (SEPA RF creditor reference)
 * @return 	string                      String Payment reference number or RF creditor reference
 */
function dolFICalculatePaymentReference($invoice_number, $statut, $use_rf)
{
	if ($statut >= 1) {
		$invoice_number = preg_replace('/[^0-9]/', '', $invoice_number); // Keep only numbers
		$invoice_number = ltrim($invoice_number, '0'); //Remove any leading zero or zeros
		$invoice_number = strrev($invoice_number); // Reverse the reference number
		$coefficients = array(7, 3, 1, 7, 3); // Define the coefficient numbers
		$sum = 0;
		$stlen_invoice_number = (int) strlen($invoice_number);
		for ($i = 0; $i < $stlen_invoice_number; $i++) { // Calculate the sum using coefficients
			$sum += (int) $invoice_number[$i] * $coefficients[$i % 5];
		}
		$check_digit = (10 - ($sum % 10)) % 10; // Calculate the check digit
		$bank_reference_fi = strrev($invoice_number) . $check_digit; // Concatenate the Reversed reference number and the check digit
		if ($use_rf) { // SEPA RF creditor reference
			$reference_with_suffix = $bank_reference_fi . "271500"; // Append "271500" to the end of the payment reference number
			$remainder = (int) bcmod($reference_with_suffix, '97'); // Calculate the remainder when dividing by 97
			$check_digit = 98 - $remainder; // Subtract the remainder from 98
			if ($check_digit < 10) { // If below 10 -> add leading zero
				$check_digit = '0' . $check_digit;
			}
			$bank_reference = "RF" . $check_digit . $bank_reference_fi; // Add "RF" and the check digit in front of the payment reference number
		} else { // FI payment reference number
			$bank_reference = $bank_reference_fi;
		}
	} else {
		$bank_reference = '';
	}
	return wordwrap($bank_reference, 4, ' ', true); // Split the string into chunks of 4 characters to improve readability
}

/**
 * Calculate payment Barcode data with FI/RF bank payment reference number
 *
 * @param 	string $recipient_account	Account number for pank payment
 * @param 	string $amount				Amount of invoice payment
 * @param 	string $bank_reference		FI Payment reference number or RF creditor reference
 * @param 	string $due_date			Payments due to date
 * @return 	string String              	String for FI/RF Payment barcode
 */
function dolFIGenerateInvoiceBarcodeData($recipient_account, $amount, $bank_reference, $due_date)
{
	$barcodeData = '0';
	if ($amount >= 0 && !empty($bank_reference)) {
		if (substr($bank_reference, 0, 2) === "RF") {
			$recipient_account = preg_replace('/[^0-9]/', '', $recipient_account); // Remove non-numeric characters from account number
			$recipient_account = str_pad($recipient_account, 16, '0', STR_PAD_LEFT); // Add leading zeros if necessary
			$referencetobarcode = preg_replace('/[^0-9]/', '', $bank_reference); // Remove non-numeric characters (spaces)
			$referencetobarcode = substr($referencetobarcode, 0, 2) . str_pad(substr($referencetobarcode, 2), 21, '0', STR_PAD_LEFT);
			$euros = floor(floatval($amount)); // Separate euros and cents
			$cents = round((floatval($amount) - $euros) * 100);
			$due_date = date('ymd', (int) $due_date); // Format the due date to YYMMDD
			$barcodeData = '5'; // Version number // Construct the string
			$barcodeData .= $recipient_account; // Recipient's account number (IBAN)
			$barcodeData .= sprintf('%06d', (int) $euros); // Euros
			$barcodeData .= sprintf('%02d', (int) $cents); // Cents
			$barcodeData .= $referencetobarcode; // Reference number
			$barcodeData .= (int) $due_date; // Due date YYMMDD
		} elseif (substr($bank_reference, 0, 2) !== "RF") {
			$recipient_account = preg_replace('/[^0-9]/', '', $recipient_account); // Remove non-numeric characters from account number
			$recipient_account = str_pad($recipient_account, 16, '0', STR_PAD_LEFT); // Add leading zeros if necessary
			$referencetobarcode = preg_replace('/[^0-9]/', '', $bank_reference); // Remove non-numeric characters (spaces)
			$euros = floor(floatval($amount)); // Separate euros and cents
			$cents = round((floatval($amount) - $euros) * 100);
			$due_date = date('ymd', (int) $due_date); // Format the due date to YYMMDD
			$barcodeData = '4'; // Version number // Construct the string
			$barcodeData .= $recipient_account; // Recipient's account number (IBAN)
			$barcodeData .= sprintf('%06d', (int) $euros); // Euros
			$barcodeData .= sprintf('%02d', (int) $cents); // Cents
			$barcodeData .= '000'; // Reserved
			$barcodeData .= str_pad($referencetobarcode, 20, '0', STR_PAD_LEFT); // Reference number
			$barcodeData .= (int) $due_date; // Due date YYMMDD
		}
	} else {
		$barcodeData = '';
	}
	return $barcodeData;
}
