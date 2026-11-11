<?php
/* Copyright (C) 2026		Nick Fragoulis
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
 *	\file			htdocs/core/lib/functions_gr.lib.php
 *	\brief			A set of greek functions for Dolibarr.
 *					DIAS Structured Creditor Reference (RF) for invoice payments.
 */


/**
 * Calculate a greek DIAS RF Structured Creditor Reference.
 *
 * Greek RF references routed through the DIAS interbank system follow the RI18
 * standard of the DIAS DCT Creditor manual (DIAS-ORG02, appendix B). They are
 * always exactly 25 characters:
 *
 *		RF (2) + check digits (2) + DIAS code (5) + code usage (1) + debt code (15) = 25
 *
 * The DIAS code is the 9nnnn creditor code assigned by DIAS when the company
 * registers through its greek bank. It cannot be chosen freely and must be set
 * in Home - Setup - Company as MAIN_INFO_SOCIETE_DIAS_CODE.
 *
 * The code usage digit tells the paying bank how to check the payment:
 *		8	same code for credit transfer and direct debit, no amount check
 *		9	separate codes for credit transfer and direct debit, no amount check
 *		0-7	credit transfer with an amount check digit, not produced here
 * Only 8 and 9 are produced, set by MAIN_INFO_SOCIETE_DIAS_CODE_USAGE, 8 by default.
 * A reference with 0 to 7 there would make the bank check the amount against
 * that digit and refuse most payments.
 *
 * The company controls the 15 digit debt code, left padded with zeros. Depending
 * on the configured mode this is the customer code, the invoice number, or a
 * single fixed company value.
 *
 * Example with DIAS code 92192, code usage 8 and debt code 3015873:
 *		payload	= 921928000000003015873
 *		result	= RF20921928000000003015873
 *
 * @param	string		$payment_id		Debt code. Non numeric characters are removed.
 *										Only the last 15 digits are kept, where the
 *										counter of a reference or a code changes.
 * @param	int<0,3>	$statut			Invoice status. Returns '' when lower than 1.
 * @param	string		$dias_code		DIAS code, 5 digits starting with 9. Empty reads
 *										the value from MAIN_INFO_SOCIETE_DIAS_CODE.
 * @param	string		$usage			Code usage digit, 8 or 9. Empty reads the value
 *										from MAIN_INFO_SOCIETE_DIAS_CODE_USAGE.
 * @return	string						25 character reference, or '' on error
 */
function dolGRCalculateCreditorReference($payment_id, $statut, $dias_code = '', $usage = '')
{
	if ($statut < 1) {
		return '';
	}

	if (!function_exists('dolCreditorRefCheckDigits')) {
		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions_creditorref.lib.php';
	}

	if ($dias_code === '') {
		$dias_code = getDolGlobalString('MAIN_INFO_SOCIETE_DIAS_CODE');
	}
	$dias_code = preg_replace('/[^0-9]/', '', (string) $dias_code);

	if (!dolGRIsValidDiasCode($dias_code)) {
		dol_syslog('dolGRCalculateCreditorReference: MAIN_INFO_SOCIETE_DIAS_CODE must be 5 digits starting with 9, got "'.$dias_code.'"', LOG_WARNING);
		return '';
	}

	if ($usage === '') {
		$usage = getDolGlobalString('MAIN_INFO_SOCIETE_DIAS_CODE_USAGE', '8');
	}
	if (!in_array((string) $usage, array('8', '9'), true)) {
		dol_syslog('dolGRCalculateCreditorReference: code usage must be 8 or 9, got "'.$usage.'", 8 is used', LOG_WARNING);
		$usage = '8';
	}

	$id = ltrim(preg_replace('/[^0-9]/', '', (string) $payment_id), '0');

	if (dol_strlen($id) > 15) {
		dol_syslog('dolGRCalculateCreditorReference: debt code "'.$id.'" exceeds 15 digits, only the last 15 are kept', LOG_WARNING);
		$id = dol_substr($id, -15);
	}

	$payload = $dias_code.$usage.str_pad($id, 15, '0', STR_PAD_LEFT);

	return 'RF'.dolCreditorRefCheckDigits($payload).$payload;
}

/**
 * Check a DIAS creditor code.
 *
 * DIAS assigns codes of the form 9nnnn, see the DIAS DCT Creditor manual.
 *
 * @param	string	$dias_code	Code to check
 * @return	bool				True when the code is 5 digits starting with 9
 */
function dolGRIsValidDiasCode($dias_code)
{
	return (bool) preg_match('/^9[0-9]{4}$/', (string) $dias_code);
}

/**
 * Tell if a greek DIAS reference asks the paying bank for an amount check.
 *
 * Digit 10 of a DIAS reference (code usage) set to 0 to 7 is an amount check digit:
 * the paying bank refuses any other amount than the one it was computed from. Such
 * a reference fits one payment of one amount, never a reference reused on several
 * invoices, see the DIAS DCT Creditor manual, appendix B.
 *
 * @param	string	$ref	Payment reference
 * @return	bool			True for a valid DIAS reference with 0 to 7 at digit 10
 */
function dolGRIsAmountCheckedReference($ref)
{
	if (!function_exists('dolCreditorRefIsValid')) {
		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions_creditorref.lib.php';
	}

	$ref = dolPayRefStrip((string) $ref);

	if (dol_strlen($ref) !== 25 || !dolCreditorRefIsValid($ref) || !dolGRIsValidDiasCode(dol_substr($ref, 4, 5))) {
		return false;
	}

	return (bool) preg_match('/^[0-7]$/', dol_substr($ref, 9, 1));
}

/**
 * Extract the DIAS code from a greek RF reference.
 *
 * @param	string	$ref	Greek RF reference
 * @return	string			5 digit DIAS code, or '' when the reference is not a greek RF
 */
function dolGRGetDiasCodeFromReference($ref)
{
	$ref = str_replace(' ', '', (string) $ref);

	if (dol_substr($ref, 0, 2) !== 'RF' || dol_strlen($ref) !== 25) {
		return '';
	}

	return dol_substr($ref, 4, 5);
}
