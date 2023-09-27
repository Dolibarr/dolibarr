<?php
/* Copyright (C) 2022-2023  Easya Solutions         <support@easya.solutions>
 * Copyright (C) 2023       Sylvain Legrand         <technique@infras.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/compta/ajax/addpayment.php
 *       \brief      File to return Ajax response on payment process
 */

//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
//if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK', '1');
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1'); // If there is no menu to show
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1'); // If we don't need to load the html.form.class.php

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

$langs->loadLangs(array('banks', 'bills', 'companies', 'multicurrency'));
$error = 0;
$invoice_id = 0;
$errors_msg = array();

/*
 * View
 */
top_httphead();

$facid		= GETPOST('facid', 'int');
$accountid	= GETPOST('accountid', 'int');
$paymentnum	= GETPOST('num_paiement', 'alpha');
$socid      = GETPOST('socid', 'int');

$amounts = array();
$amountsresttopay = array();

$multicurrency_amounts = array();
$multicurrency_amountsresttopay = array();

// Security check
if ($user->societe_id > 0) {
	$socid = $user->societe_id;
}

$object = new Facture($db);

// Load object
if ($facid > 0) {
	$ret = $object->fetch($facid);
}

$usercanissuepayment = !empty($user->rights->facture->paiement);

$fieldid = 'rowid';
$isdraft = (($object->status == Facture::STATUS_DRAFT) ? 1 : 0);
$result = restrictedArea($user, 'facture', $object->id, '', '', 'fk_soc', $fieldid, $isdraft, 1);
if ($result <= 0 || !$usercanissuepayment) {
	$langs->load("errors");
	$errors_msg[] = $langs->trans('ErrorForbidden');
	$error++;
}

if (!$error) {
	$datepaye = dol_mktime(12, 0, 0, GETPOST('remonth', 'int'), GETPOST('reday', 'int'), GETPOST('reyear', 'int'));
	$payment_id = 0;
	$totalpayment = 0;
	$multicurrency_totalpayment = 0;
	$atleastonepaymentnotnull = 0;
	$formquestion = array();
	$i = 0;

	// Generate payment array and check if there is payment higher than invoice and payment date before invoice date
	$tmpinvoice = new Facture($db);
	foreach ($_POST as $key => $value) {
		if (substr($key, 0, 7) == 'amount_' && GETPOST($key) != '') {
			$cursorfacid = substr($key, 7);
			$amounts[$cursorfacid] = price2num(GETPOST($key));
			$totalpayment = $totalpayment + $amounts[$cursorfacid];
			if (!empty($amounts[$cursorfacid])) {
				$atleastonepaymentnotnull++;
			}
			$result = $tmpinvoice->fetch($cursorfacid);
			if ($result <= 0) {
				$errors_msg[] = $db->lasterror();
				$error++;
				break;
			} else {
				$amountsresttopay[$cursorfacid] = price2num($tmpinvoice->total_ttc - $tmpinvoice->getSommePaiement());
				if ($amounts[$cursorfacid]) {
					// Check date
					if ($datepaye && ($datepaye < $tmpinvoice->date)) {
						$langs->load("errors");
						//$error++;
						setEventMessages($langs->transnoentities("WarningPaymentDateLowerThanInvoiceDate", dol_print_date($datepaye, 'day'), dol_print_date($tmpinvoice->date, 'day'), $tmpinvoice->ref), null, 'warnings');
					}
				}
			}
		} elseif (substr($key, 0, 21) == 'multicurrency_amount_') {
			$cursorfacid = substr($key, 21);
			$multicurrency_amounts[$cursorfacid] = price2num(GETPOST($key));
			$multicurrency_totalpayment += floatval($multicurrency_amounts[$cursorfacid]);
			if (!empty($multicurrency_amounts[$cursorfacid])) {
				$atleastonepaymentnotnull++;
			}
			$result = $tmpinvoice->fetch($cursorfacid);
			if ($result <= 0) {
				$errors_msg[] = $db->lasterror();
				$error++;
				break;
			} else {
				$multicurrency_amountsresttopay[$cursorfacid] = price2num($tmpinvoice->multicurrency_total_ttc - $tmpinvoice->getSommePaiement(1));
				if ($multicurrency_amounts[$cursorfacid]) {
					// Check date
					if ($datepaye && ($datepaye < $tmpinvoice->date)) {
						$langs->load("errors");
						//$error++;
						setEventMessages($langs->transnoentities("WarningPaymentDateLowerThanInvoiceDate", dol_print_date($datepaye, 'day'), dol_print_date($tmpinvoice->date, 'day'), $tmpinvoice->ref), null, 'warnings');
					}
				}
			}
		}
	}
}

// Check parameters
if (!GETPOST('paiementcode')) {
	$errors_msg[] = $langs->transnoentities('ErrorFieldRequired', $langs->transnoentities('PaymentMode'));
	$error++;
}

if (!empty($conf->banque->enabled)) {
	// If bank module is on, account is required to enter a payment
	if ($accountid <= 0) {
		$errors_msg[] = $langs->transnoentities('ErrorFieldRequired', $langs->transnoentities('AccountToCredit'));
		$error++;
	}
}

if (empty($totalpayment) && empty($multicurrency_totalpayment) && empty($atleastonepaymentnotnull)) {
	$errors_msg[] = $langs->transnoentities('ErrorFieldRequired', $langs->trans('PaymentAmount'));
	$error++;
}

if (empty($datepaye)) {
	$errors_msg[] = $langs->transnoentities('ErrorFieldRequired', $langs->transnoentities('Date'));
	$error++;
}

// Check if payments in both currency
if ($totalpayment > 0 && $multicurrency_totalpayment > 0) {
	$errors_msg[] = $langs->transnoentities('ErrorPaymentInBothCurrency');
	$error++;
}

if (!$error) {
	$db->begin();

	$thirdparty = new Societe($db);
	if ($socid > 0) {
		$thirdparty->fetch($socid);
	}
	$multicurrency_code = array();
	$multicurrency_tx = array();

	// Clean parameters amount if payment is for a credit note
	foreach ($amounts as $key => $value) {    // How payment is dispatched
		$tmpinvoice = new Facture($db);
		$tmpinvoice->fetch($key);
		if ($tmpinvoice->type == Facture::TYPE_CREDIT_NOTE) {
			$newvalue = price2num($value, 'MT');
			$amounts[$key] = -abs($newvalue);
		}
		$multicurrency_code[$key] = $tmpinvoice->multicurrency_code;
		$multicurrency_tx[$key] = $tmpinvoice->multicurrency_tx;
	}

	foreach ($multicurrency_amounts as $key => $value) {    // How payment is dispatched
		$tmpinvoice = new Facture($db);
		$tmpinvoice->fetch($key);
		$paiement = new Paiement($db);
		$paiement->multicurrency_code = $tmpinvoice->multicurrency_code;
		$paiement->multicurrency_tx = $tmpinvoice->multicurrency_tx;	// TODO the exchange rate may differ from the invoice rate => enter or confirm on the payment entry page
		if ($tmpinvoice->type == Facture::TYPE_CREDIT_NOTE) {
			$newvalue = price2num($value, 'MT');
			$multicurrency_amounts[$key] = -abs($newvalue);
		}
		$multicurrency_code[$key] = $tmpinvoice->multicurrency_code;
		$multicurrency_tx[$key] = $tmpinvoice->multicurrency_tx;
	}

	// Creation of payment line
	$paiement = new Paiement($db);
	$paiement->datepaye = $datepaye;
	$paiement->amounts = $amounts;   // Array with all payments dispatching with invoice id
	$paiement->multicurrency_amounts = $multicurrency_amounts;   // Array with all payments dispatching
	$paiement->multicurrency_code = $multicurrency_code; // Array with all currency of payments dispatching
	$paiement->multicurrency_tx = $multicurrency_tx; // Array with all currency tx of payments dispatching
	$paiement->paiementid = dol_getIdFromCode($db, GETPOST('paiementcode'), 'c_paiement', 'code', 'id', 1);
	$paiement->num_payment = $paymentnum;
	$paiement->note_private = GETPOST('comment', 'alpha');
	$paiement->fk_account   = GETPOST('accountid', 'int');

	if (!$error) {
		// Create payment and update this->multicurrency_amounts if this->amounts filled or
		// this->amounts if this->multicurrency_amounts filled.
		// This also set ->amount and ->multicurrency_amount
		$payment_id = $paiement->create($user, (GETPOST('closepaidinvoices') == 'on' ? 1 : 0), $thirdparty); // This include closing invoices and regenerating documents
		if ($payment_id < 0) {
			$errors_msg[] = $paiement->errorsToString();
			$error++;
		}
	}

	if (!$error) {
		$label = '(CustomerInvoicePayment)';
		if (GETPOST('type') == Facture::TYPE_CREDIT_NOTE) {
			$label = '(CustomerInvoicePaymentBack)'; // Refund of a credit note
		}
		$result = $paiement->addPaymentToBank($user, 'payment', $label, $accountid, GETPOST('chqemetteur'), GETPOST('chqbank'));
		if ($result < 0) {
			$errors_msg[] = $paiement->errorsToString();
			$error++;
		}
	}

	if (!$error) {
		$db->commit();

		// If payment dispatching on more than one invoice, we stay on summary page, otherwise jump on invoice card
		foreach ($paiement->amounts as $key => $amount) {
			$facid = $key;
			if (is_numeric($amount) && $amount <> 0) {
				if ($invoice_id != 0) {
					$invoice_id = -1; // There is more than one invoice payed by this payment
				} else {
					$invoice_id = $facid;
				}
			}
		}
	} else {
		$db->rollback();
	}
}

if ($error) {
	$toJsonArray = array(
		'error' => implode('<br>', $errors_msg),
	);
} elseif ($invoice_id > 0) {
	$toJsonArray = array(
		'invoice_id' => $invoice_id,
	);
} else {
	$toJsonArray = array(
		'payment_id' => $payment_id,
	);
}

// Encode to JSON to return
echo json_encode($toJsonArray);	// Printing the call's result
