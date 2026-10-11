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
 * Greek RF references routed through the DIAS interbank system are always
 * exactly 25 characters and embed the creditor's DIAS code:
 *
 *		RF (2) + check digits (2) + DIAS code (5) + payment id (16) = 25
 *
 * The DIAS code is a 5 digit Merchant Identifier assigned by DIAS when the
 * company registers through its greek bank. It cannot be chosen freely and
 * must be set in Home - Setup - Company as MAIN_INFO_SOCIETE_DIAS_CODE.
 *
 * The company controls only the 16 digit payment id, which is left padded
 * with zeros. Depending on the configured mode this is the customer code,
 * the invoice number, or zero for a single fixed company reference.
 *
 * Example with DIAS code 92192 and payment id 8000000003015873:
 *		payload	= 921928000000003015873
 *		result	= RF20921928000000003015873
 *
 * @param	string		$payment_id		Payment id. Non numeric characters are
 *										removed. Truncated to 16 digits.
 * @param	int<0,3>	$statut			Invoice status. Returns '' when lower than 1.
 * @param	string		$dias_code		5 digit DIAS code. Empty reads the value
 *										from MAIN_INFO_SOCIETE_DIAS_CODE.
 * @return	string						25 character reference, or '' on error
 */
function dolGRCalculateCreditorReference($payment_id, $statut, $dias_code = '')
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

	if (dol_strlen($dias_code) !== 5) {
		dol_syslog('dolGRCalculateCreditorReference: MAIN_INFO_SOCIETE_DIAS_CODE must be 5 digits, got "'.$dias_code.'"', LOG_WARNING);
		return '';
	}

	$id = ltrim(preg_replace('/[^0-9]/', '', (string) $payment_id), '0');

	if (dol_strlen($id) > 16) {
		dol_syslog('dolGRCalculateCreditorReference: payment id "'.$id.'" exceeds 16 digits, truncating', LOG_WARNING);
		$id = dol_substr($id, 0, 16);
	}

	$payload = $dias_code.str_pad($id, 16, '0', STR_PAD_LEFT);

	return 'RF'.dolCreditorRefCheckDigits($payload).$payload;
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
