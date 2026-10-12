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
 *	\file			htdocs/core/lib/paymentref.lib.php
 *	\ingroup		invoice
 *	\brief			Generation of the structured payment reference
 */


/**
 * Return the payment reference scheme to use for a country.
 *
 * The scheme decides which country library builds the reference. It is not a
 * user preference, it follows the national banking rules of the creditor.
 *
 * @param	string	$country_code	ISO 3166-1 alpha-2 country code
 * @return	string					'BE', 'FI', 'GR' or 'RF' for plain ISO 11649
 */
function dolPayRefGetSchemeForCountry($country_code)
{
	switch (dol_strtoupper((string) $country_code)) {
		case 'BE':
			// Belgian OGM-VCS structured communication, see functions_be.lib.php
			return 'BE';
		case 'FI':
			// Finnish national reference or finnish RF, see functions_fi.lib.php
			return 'FI';
		case 'GR':
			// DIAS RF with an embedded 5 digit merchant code, see functions_gr.lib.php
			return 'GR';
		default:
			// DE, CH, AT, NL, FR, IT, ES, SE and other SEPA countries use plain
			// ISO 11649 with no national deviation, see functions_creditorref.lib.php
			return 'RF';
	}
}

/**
 * Format a payment reference the way its country prints it.
 *
 * Presentation only. The stored value stays compact, because the QR code, the
 * barcode and the SEPA file all need it unspaced.
 *
 *  - greek references are printed unbroken, all 25 characters
 *  - a belgian structured communication already carries its own separators
 *  - everything else is grouped in fours, the ISO 11649 convention
 *
 * @param	string	$ref			Stored payment reference
 * @param	string	$country_code	Creditor country code
 * @return	string					Reference as it should appear on a document
 */
function dolPayRefFormatForDisplay($ref, $country_code = '')
{
	$ref = (string) $ref;
	if ($ref === '') {
		return '';
	}

	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions_creditorref.lib.php';

	if (dolPayRefDetectScheme($ref) == 'BBA') {
		return $ref;
	}
	if (dolPayRefGetSchemeForCountry($country_code) == 'GR') {
		return dolPayRefStrip($ref);
	}

	return dolPayRefFormat(dolPayRefStrip($ref));
}

/**
 * Return the setup that is still missing before references can be generated.
 *
 * Some schemes need a value that cannot be chosen freely. The greek DIAS code is
 * assigned by the bank, so without it nothing can be produced and the invoice
 * would be validated with an empty reference.
 *
 * @param	string	$country_code	Creditor country code
 * @param	string	$mode			Mode to check. Empty checks the stored one, so a
 *									setup page can test a mode before saving it.
 * @return	string					Translation key of the warning, or '' when the setup is complete
 */
function dolPayRefGetSetupWarning($country_code, $mode = '')
{
	if ($mode == '') {
		$mode = getDolGlobalString('INVOICE_PAYMENT_REF_MODE');
	}
	if (!$mode) {
		return '';
	}

	if (dolPayRefGetSchemeForCountry($country_code) == 'GR') {
		// A company that already has a complete reference issues it as it is, so
		// nothing is generated and the DIAS code is not needed.
		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions_creditorref.lib.php';
		$alreadycomplete = ($mode == 'company'
			&& dolCreditorRefIsValid(getDolGlobalString('MAIN_INFO_SOCIETE_PAYMENT_ID')));

		$diascode = preg_replace('/[^0-9]/', '', getDolGlobalString('MAIN_INFO_SOCIETE_DIAS_CODE'));
		if (!$alreadycomplete && dol_strlen($diascode) != 5) {
			// References already written on a third party keep working without the
			// code, only a third party that has none yet needs one generated.
			if ($mode == 'thirdparty') {
				return 'WarningPaymentRefDIASCodeMissingThirdparty';
			}
			return 'WarningPaymentRefDIASCodeMissing';
		}
	}

	if ($mode == 'company' && !getDolGlobalString('MAIN_INFO_SOCIETE_PAYMENT_ID')) {
		return 'WarningPaymentRefPaymentIdMissing';
	}

	return '';
}

/**
 * Build a structured payment reference from a payment id, for a country.
 *
 * This is the single place that maps a country to its national algorithm.
 *
 * @param	string		$payment_id		Value to encode. Customer code, invoice
 *										number, or a combination, depending on mode.
 * @param	int<0,3>	$statut			Invoice status. Returns '' when lower than 1.
 * @param	string		$country_code	Creditor country code
 * @param	int			$invoice_type	Invoice type, used by the belgian scheme
 * @return	string						Payment reference, or '' on error
 */
function dolPayRefBuild($payment_id, $statut, $country_code, $invoice_type = 0)
{
	if ($statut < 1) {
		return '';
	}

	// A value that is already a complete reference is kept as it is. A company that
	// used a reference before this feature existed can therefore keep issuing the
	// one its customers already pay against.
	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions_creditorref.lib.php';
	if (dolCreditorRefIsValid((string) $payment_id) || strpos((string) $payment_id, '+++') === 0) {
		return dolPayRefStrip((string) $payment_id);
	}

	// A finnish national reference carries its own check digit, so one that is
	// already complete is kept too, instead of being used as a base for a new one.
	if (dolPayRefGetSchemeForCountry($country_code) == 'FI') {
		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions_fi.lib.php';
		if (dolFIIsValidReference((string) $payment_id)) {
			return dolPayRefStrip((string) $payment_id);
		}
	}

	switch (dolPayRefGetSchemeForCountry($country_code)) {
		case 'BE':
			include_once DOL_DOCUMENT_ROOT.'/core/lib/functions_be.lib.php';
			return dolBECalculateStructuredCommunication((string) $payment_id, $invoice_type);

		case 'FI':
			include_once DOL_DOCUMENT_ROOT.'/core/lib/functions_fi.lib.php';
			include_once DOL_DOCUMENT_ROOT.'/core/lib/functions_creditorref.lib.php';
			// Finnish RF is accepted for both domestic and cross border payments.
			// Since 18 november 2024 a plain ISO 11649 reference is also valid in
			// Finland, but the RF form built on the national reference stays the
			// most widely supported, so it is the default here.
			return dolPayRefStrip(dolFICalculatePaymentReference((string) $payment_id, $statut, 1));

		case 'GR':
			include_once DOL_DOCUMENT_ROOT.'/core/lib/functions_gr.lib.php';
			return dolGRCalculateCreditorReference((string) $payment_id, $statut);

		default:
			include_once DOL_DOCUMENT_ROOT.'/core/lib/functions_creditorref.lib.php';
			return dolCreditorRefGenerate((string) $payment_id, $statut);
	}
}

/**
 * Generate and optionally store the payment reference of an invoice.
 *
 * Called from Facture::validate() with $store = 1, and from the invoice card
 * with $store = 0 to show a preview on a draft.
 *
 * Three modes, set with INVOICE_PAYMENT_REF_MODE in Home - Setup - Invoices:
 *
 *	'company'		One fixed reference for the whole company. Every invoice
 *					carries the same value, taken from MAIN_INFO_SOCIETE_PAYMENT_ID.
 *	'thirdparty'	One stable reference per customer, generated once from the
 *					customer code and kept in llx_societe.tp_payment_reference,
 *					then reused on every invoice of that customer.
 *	'invoice'		One reference per invoice, built from the parts listed in
 *					INVOICE_PAYMENT_REF_PARTS.
 *
 * @param	CommonObject	$object		Invoice, or a proposal or order used only to
 *                                      preview the reference the invoice will carry.
 *                                      Needs ->ref, ->status, ->type and ->thirdparty.
 * @param	User			$user		User doing the action, for setValueFrom
 * @param	int<0,1>		$store		1 to write to database, 0 for preview only
 * @return	string						Payment reference, or '' when disabled or on error
 */
function dolPayRefGenerateForInvoice($object, $user, $store = 1)
{
	global $mysoc;

	$mode = getDolGlobalString('INVOICE_PAYMENT_REF_MODE');

	if (empty($mode) || $mode === 'disabled' || !is_object($object)) {
		return '';
	}

	$statut = isset($object->status) ? (int) $object->status : (int) $object->statut;
	$country_code = empty($mysoc->country_code) ? '' : $mysoc->country_code;
	// The invoice type only means something on an invoice, it drives the belgian scheme.
	// A proposal or an order previewing its future reference has none.
	$type = ($object instanceof CommonInvoice) ? (int) $object->type : 0;

	if (!is_object($object->thirdparty)) {
		$object->fetch_thirdparty();
	}

	$ref = '';

	if ($mode === 'company') {
		// One fixed reference for the whole company
		$ref = dolPayRefBuild(getDolGlobalString('MAIN_INFO_SOCIETE_PAYMENT_ID', '0'), $statut, $country_code, $type);
	} elseif ($mode === 'thirdparty') {
		// Reuse the reference already stored on the customer when there is one
		if (!empty($object->thirdparty->tp_payment_reference)) {
			$ref = $object->thirdparty->tp_payment_reference;
		} else {
			$base = '';
			$base = dolPayRefGetThirdpartyBase($object->thirdparty);

			$ref = dolPayRefBuild($base, $statut, $country_code, $type);

			// Keep it on the customer so every later invoice reuses the same value
			if ($ref !== '' && $store) {
				$object->thirdparty->setValueFrom('tp_payment_reference', $ref, '', null, 'text', '', $user, 'COMPANY_MODIFY');
				$object->thirdparty->tp_payment_reference = $ref;
			}
		}
	} elseif ($mode === 'invoice') {
		$parts = dolPayRefCollectParts($object);
		if (empty($parts)) {
			return '';
		}
		if (dolPayRefGetSchemeForCountry($country_code) === 'RF' && count($parts) > 1) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/functions_creditorref.lib.php';
			$ref = dolCreditorRefGenerateFromParts($parts, $statut);
		} else {
			$ref = dolPayRefBuild(implode('', $parts), $statut, $country_code, $type);
		}
	}

	if ($ref === '') {
		dol_syslog('dolPayRefGenerateForInvoice: no reference produced for '.$object->ref.' mode '.$mode, LOG_WARNING);
		return '';
	}

	// llx_facture.payment_reference is varchar(25)
	if (dol_strlen($ref) > 25) {
		dol_syslog('dolPayRefGenerateForInvoice: reference longer than 25 chars, not stored', LOG_WARNING);
		return '';
	}

	if ($store && $statut >= 1) {
		$object->setValueFrom('payment_reference', $ref, '', null, 'text', '', $user, 'BILL_MODIFY');
		$object->payment_reference = $ref;
	}

	return $ref;
}

/**
 * Return the value a third party builds its payment reference from.
 *
 * The field follows INVOICE_PAYMENT_REF_TP_BASE from the setup. A third party that
 * needs a different reference has it written directly on its card instead.
 * The internal id is the last resort so a reference can always be produced.
 *
 * @param	Societe	$thirdparty		Third party
 * @return	string					Value to encode, never empty
 */
function dolPayRefGetThirdpartyBase($thirdparty)
{
	$field = getDolGlobalString('INVOICE_PAYMENT_REF_TP_BASE', 'code_client');

	if ($field == 'code_fournisseur' && !empty($thirdparty->code_fournisseur)) {
		return (string) $thirdparty->code_fournisseur;
	}
	if ($field == 'code_client' && !empty($thirdparty->code_client)) {
		return (string) $thirdparty->code_client;
	}
	if ($field == 'rowid') {
		return (string) $thirdparty->id;
	}

	// The chosen field is empty on this third party, fall back so a reference still exists
	if (!empty($thirdparty->code_client)) {
		return (string) $thirdparty->code_client;
	}
	if (!empty($thirdparty->code_fournisseur)) {
		return (string) $thirdparty->code_fournisseur;
	}

	return (string) $thirdparty->id;
}

/**
 * Collect the ordered parts used to build a per invoice reference.
 *
 * Parts are listed in INVOICE_PAYMENT_REF_PARTS as a comma separated list.
 * Accepted values are 'customer_code', 'contract_ref' and 'invoice_ref'.
 *
 * @param	CommonObject	$object		Invoice, proposal or order
 * @return	string[]					Ordered non empty parts
 */
function dolPayRefCollectParts($object)
{
	$wanted = getDolGlobalString('INVOICE_PAYMENT_REF_PARTS', 'invoice_ref');
	$allowed = array('customer_code', 'contract_ref', 'invoice_ref');
	$parts = array();

	foreach (explode(',', $wanted) as $part) {
		$part = trim($part);
		if (!in_array($part, $allowed)) {
			continue;
		}

		$value = '';
		if ($part === 'customer_code' && is_object($object->thirdparty)) {
			$value = (string) $object->thirdparty->code_client;
		} elseif ($part === 'contract_ref') {
			$value = dolPayRefGetLinkedContractRef($object);
		} elseif ($part === 'invoice_ref') {
			$value = (string) $object->ref;
		}

		if ($value !== '') {
			$parts[] = $value;
		}
	}

	return $parts;
}

/**
 * Return the reference of the first contract linked to an invoice.
 *
 * @param	CommonObject	$object		Invoice, proposal or order, needs ->id and ->db
 * @return	string						Contract reference, or ''
 */
function dolPayRefGetLinkedContractRef($object)
{
	if (empty($object->id) || !is_object($object->db)) {
		return '';
	}

	$sql = "SELECT c.ref";
	$sql .= " FROM ".$object->db->prefix()."contrat as c";
	$sql .= " INNER JOIN ".$object->db->prefix()."element_element as ee ON ee.fk_source = c.rowid";
	$sql .= " WHERE ee.sourcetype = 'contrat'";
	$sql .= " AND ee.targettype = 'facture'";
	$sql .= " AND ee.fk_target = ".((int) $object->id);
	$sql .= " AND c.entity IN (".getEntity('contract').")";
	$sql .= " ORDER BY c.rowid ASC";
	$sql .= $object->db->plimit(1);

	$resql = $object->db->query($sql);
	if ($resql) {
		$obj = $object->db->fetch_object($resql);
		$object->db->free($resql);
		if ($obj) {
			return (string) $obj->ref;
		}
	} else {
		dol_syslog('dolPayRefGetLinkedContractRef: '.$object->db->lasterror(), LOG_ERR);
	}

	return '';
}
