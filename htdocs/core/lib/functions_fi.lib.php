<?php
/* Copyright (C) 2010 Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2024 Mikko Virtanen
 * Copyright (C) 2026 Nick Fragoulis
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
 * @param   int     $use_rf             false/(0) generate FI Bank payment reference
 *                                      true/(1) Generate European Reference number based on the global
 *                                      Structured Creditor Reference standard (SEPA RF creditor reference)
 * @return 	string                      String Payment reference number or RF creditor reference
 */
function dolFICalculatePaymentReference($invoice_number, $statut, $use_rf)
{
	if ($statut >= 1) {
		$invoice_number = preg_replace('/[^0-9]/', '', $invoice_number); // Keep only numbers
		// Without any digit every such value would get the same reference 0000,
		// so a bank could no longer tell the payments apart.
		if ($invoice_number === '') {
			dol_syslog('dolFICalculatePaymentReference: no digit in the base, no reference generated', LOG_WARNING);
			return '';
		}
		$invoice_number = ltrim($invoice_number, '0'); // Remove leading zeros
		// A finnish reference is 4 to 20 characters, check digit included, so the base
		// is 3 to 19 digits. Leading zeros do not change the weighted sum, so padding
		// a short base is safe. A longer base is cut to keep the reference valid, and
		// to keep the RF form within the 25 characters of ISO 11649.
		if (dol_strlen($invoice_number) < 3) {
			$invoice_number = str_pad($invoice_number, 3, '0', STR_PAD_LEFT);
		} elseif (dol_strlen($invoice_number) > 19) {
			dol_syslog('dolFICalculatePaymentReference: base "'.$invoice_number.'" is longer than 19 digits, cut to keep the reference valid', LOG_WARNING);
			$invoice_number = dol_substr($invoice_number, 0, 19);
		}
		$coefficients = array(7, 3, 1); // Define the coefficient numbers (rotating 7, 3, 1)
		$sum = 0;
		$stlen_invoice_number = (int) strlen($invoice_number);
		// Calculate the weighted sum from right to left
		for ($i = 0; $i < $stlen_invoice_number; $i++) {
			$sum += (int) $invoice_number[$stlen_invoice_number - $i - 1] * $coefficients[$i % 3];
		}
		$check_digit = (10 - ($sum % 10)) % 10; // Calculate the check digit
		$bank_reference_fi = $invoice_number . $check_digit; // Concatenate the reference number and the check digit
		if ($use_rf) { // SEPA RF creditor reference
			$reference_with_suffix = $bank_reference_fi . "271500"; // Append "271500" to the end of the payment reference number
			// bcmath is not a Dolibarr requirement, so the remainder is computed
			// without it. See dolCreditorRefMod97().
			include_once DOL_DOCUMENT_ROOT.'/core/lib/functions_creditorref.lib.php';
			$remainder = dolCreditorRefMod97($reference_with_suffix); // Calculate the remainder when dividing by 97
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
 * @param 	int    $due_date			Payments due to date, as a timestamp
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
			$euros = floor((float) $amount); // Separate euros and cents
			$cents = round(((float) $amount - $euros) * 100);
			$due_date = date('ymd', (int) $due_date); // Format the due date to YYMMDD
			$barcodeData = '5'; // Version number // Construct the string
			$barcodeData .= $recipient_account; // Recipient's account number (IBAN)
			$barcodeData .= sprintf('%06d', (int) $euros); // Euros
			$barcodeData .= sprintf('%02d', (int) $cents); // Cents
			$barcodeData .= $referencetobarcode; // Reference number
			$barcodeData .= $due_date; // Due date YYMMDD, kept as a string so a leading zero survives
		} elseif (substr($bank_reference, 0, 2) !== "RF") {
			$recipient_account = preg_replace('/[^0-9]/', '', $recipient_account); // Remove non-numeric characters from account number
			$recipient_account = str_pad($recipient_account, 16, '0', STR_PAD_LEFT); // Add leading zeros if necessary
			$referencetobarcode = preg_replace('/[^0-9]/', '', $bank_reference); // Remove non-numeric characters (spaces)
			$euros = floor((float) $amount); // Separate euros and cents
			$cents = round(((float) $amount - $euros) * 100);
			$due_date = date('ymd', (int) $due_date); // Format the due date to YYMMDD
			$barcodeData = '4'; // Version number // Construct the string
			$barcodeData .= $recipient_account; // Recipient's account number (IBAN)
			$barcodeData .= sprintf('%06d', (int) $euros); // Euros
			$barcodeData .= sprintf('%02d', (int) $cents); // Cents
			$barcodeData .= '000'; // Reserved
			$barcodeData .= str_pad($referencetobarcode, 20, '0', STR_PAD_LEFT); // Reference number
			$barcodeData .= $due_date; // Due date YYMMDD, kept as a string so a leading zero survives
		}
	} else {
		$barcodeData = '';
	}
	return $barcodeData;
}

/**
 * Build the finnish payment barcode data of an invoice.
 *
 * Reads the bank account, amount, reference and due date from the invoice so a PDF
 * template or a mail substitution does not have to gather them itself.
 *
 * Returns an empty string unless everything needed is present and valid, because a
 * barcode built on partial data would still be scanned and paid.
 *
 * @param	CommonInvoice	$object		Invoice
 * @return	string						54 character barcode data, or '' when not applicable
 */
function dolFIGetInvoiceBarcodeData($object)
{
	if (!is_object($object) || empty($object->payment_reference) || empty($object->date_lim_reglement)) {
		return '';
	}

	include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

	$idofbankaccount = $object->fk_account;
	if (empty($idofbankaccount)) {
		$idofbankaccount = $object->fk_bank;	// for backward compatibility
	}
	if (empty($idofbankaccount)) {
		$idofbankaccount = getDolGlobalInt('FACTURE_RIB_NUMBER');
	}
	if (empty($idofbankaccount)) {
		return '';
	}

	$bankaccount = new Account($object->db);
	if ($bankaccount->fetch($idofbankaccount) <= 0 || empty($bankaccount->iban)) {
		return '';
	}

	// The account field of the barcode is 16 digits wide, so the barcode is only
	// defined for a finnish IBAN. A foreign one would overflow that field and the
	// barcode would then scan as a different account.
	$iban = dol_strtoupper(str_replace(' ', '', $bankaccount->iban));
	if (strpos($iban, 'FI') !== 0) {
		dol_syslog('dolFIGetInvoiceBarcodeData: barcode needs a finnish IBAN, got '.$iban, LOG_WARNING);
		return '';
	}

	// The reference field of the barcode is numeric, an alphanumeric RF cannot fit
	$reference = str_replace(' ', '', (string) $object->payment_reference);
	if (!ctype_digit(dol_substr($reference, 0, 2) === 'RF' ? dol_substr($reference, 2) : $reference)) {
		dol_syslog('dolFIGetInvoiceBarcodeData: barcode needs a numeric reference, got '.$reference, LOG_WARNING);
		return '';
	}

	$amount = $object->getRemainToPay();
	if ($amount < 0) {
		return '';
	}

	$barcodedata = dolFIGenerateInvoiceBarcodeData($bankaccount->iban, (string) $amount, $object->payment_reference, $object->date_lim_reglement);

	// A barcode of the wrong length would still be printed and scanned, so refuse it
	if (dol_strlen($barcodedata) != 54) {
		dol_syslog('dolFIGetInvoiceBarcodeData: barcode length is '.dol_strlen($barcodedata).', expected 54, discarded', LOG_WARNING);
		return '';
	}

	return $barcodedata;
}

/**
 * Format the finnish payment barcode for reading, as banks print it.
 *
 * The virtual barcode is the same 54 digits shown as text, so a payer can type it
 * into an online bank instead of scanning it. Banks group it in blocks of five.
 *
 * @param	string	$barcodedata	Barcode data from dolFIGenerateInvoiceBarcodeData()
 * @return	string					Grouped string, or '' when the input is empty
 */
function dolFIFormatVirtualBarcode($barcodedata)
{
	if (empty($barcodedata)) {
		return '';
	}

	return trim(chunk_split($barcodedata, 5, ' '));
}

/**
 * Check that a string is a valid finnish national reference.
 *
 * A finnish reference is 4 to 20 digits. The last digit is a check digit over
 * the preceding ones, weighted 7, 3, 1 from the right.
 *
 * @param	string	$reference	Reference to check, spaces are ignored
 * @return	bool				True when the length and the check digit are right
 */
function dolFIIsValidReference($reference)
{
	$reference = str_replace(' ', '', (string) $reference);

	if (!ctype_digit($reference) || dol_strlen($reference) < 4 || dol_strlen($reference) > 20) {
		return false;
	}

	$base = substr($reference, 0, -1);
	$given = (int) substr($reference, -1);

	$coefficients = array(7, 3, 1);
	$sum = 0;
	$i = 0;
	for ($pos = dol_strlen($base) - 1; $pos >= 0; $pos--) {
		$sum += ((int) $base[$pos]) * $coefficients[$i % 3];
		$i++;
	}

	return $given === ((10 - ($sum % 10)) % 10);
}
