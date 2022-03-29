<?php
/**
 * Copyright (C) 2018    Andreu Bisquerra    <jove@bisquerra.com>
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
 *	\file       htdocs/takepos/invoice.php
 *	\ingroup    takepos
 *	\brief      Page to generate section with list of lines
 */

// if (! defined('NOREQUIREUSER'))    define('NOREQUIREUSER', '1');    // Not disabled cause need to load personalized language
// if (! defined('NOREQUIREDB'))        define('NOREQUIREDB', '1');        // Not disabled cause need to load personalized language
// if (! defined('NOREQUIRESOC'))        define('NOREQUIRESOC', '1');
// if (! defined('NOREQUIRETRAN'))        define('NOREQUIRETRAN', '1');
if (!defined('NOCSRFCHECK')) { define('NOCSRFCHECK', '1'); }
if (!defined('NOTOKENRENEWAL')) { define('NOTOKENRENEWAL', '1'); }
if (!defined('NOREQUIREMENU')) { define('NOREQUIREMENU', '1'); }
if (!defined('NOREQUIREHTML')) { define('NOREQUIREHTML', '1'); }
if (!defined('NOREQUIREAJAX')) { define('NOREQUIREAJAX', '1'); }

if (!defined('INCLUDE_PHONEPAGE_FROM_PUBLIC_PAGE')) require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

global $mysoc;

$langs->loadLangs(array("companies", "commercial", "bills", "cashdesk", "stocks", "banks"));

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');
$idproduct = GETPOST('idproduct', 'int');
$place = (GETPOST('place', 'aZ09') ? GETPOST('place', 'aZ09') : 0); // $place is id of table for Bar or Restaurant
$placeid = 0; // $placeid is ID of invoice
// Terminal is stored into $_SESSION["takeposterminal"];

if (empty($user->rights->takepos->run) && !defined('INCLUDE_PHONEPAGE_FROM_PUBLIC_PAGE')) {
	accessforbidden();
}


/*
 * View
 */

if (($conf->global->TAKEPOS_PHONE_BASIC_LAYOUT == 1 && $conf->browser->layout == 'phone') || defined('INCLUDE_PHONEPAGE_FROM_PUBLIC_PAGE'))
{
	// DIRECT LINK TO THIS PAGE FROM MOBILE AND NO TERMINAL SELECTED
	if ($_SESSION["takeposterminal"] == "")
	{
		if ($conf->global->TAKEPOS_NUM_TERMINALS == "1") $_SESSION["takeposterminal"] = 1;
		else {
			header("Location: ".DOL_URL_ROOT."/takepos/index.php");
			exit;
		}
	}
	$mobilepage = GETPOST('mobilepage', 'alpha');
	$title = 'TakePOS - Dolibarr '.DOL_VERSION;
	if (!empty($conf->global->MAIN_APPLICATION_TITLE)) $title = 'TakePOS - '.$conf->global->MAIN_APPLICATION_TITLE;
	$head = '<meta name="apple-mobile-web-app-title" content="TakePOS"/>
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>';
	$arrayofcss = array(
	'/takepos/css/pos.css.php',
	'/takepos/js/jquery.colorbox-min.js'
	);
	$arrayofjs = array('/takepos/js/jquery.colorbox-min.js');
	top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);
}

/**
 * Abort invoice creationg with a given error message
 *
 * @param   string  $message        Message explaining the error to the user
 * @return	void
 */
function fail($message)
{
	header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error', true, 500);
	die($message);
}



$number = GETPOST('number', 'alpha');
$idline = GETPOST('idline', 'int');
$selectedline = GETPOST('selectedline', 'int');
$desc = GETPOST('desc', 'alphanohtml');
$pay = GETPOST('pay', 'aZ09');
$amountofpayment = price2num(GETPOST('amount', 'alpha'));

$invoiceid = GETPOST('invoiceid', 'int');

$paycode = $pay;
if ($pay == 'cash')   $paycode = 'LIQ'; // For backward compatibility
if ($pay == 'card')   $paycode = 'CB'; // For backward compatibility
if ($pay == 'cheque') $paycode = 'CHQ'; // For backward compatibility

// Retrieve paiementid
$sql = "SELECT id FROM ".MAIN_DB_PREFIX."c_paiement";
$sql .= " WHERE entity IN (".getEntity('c_paiement').")";
$sql .= " AND code = '".$db->escape($paycode)."'";
$resql = $db->query($sql);
$obj = $db->fetch_object($resql);
$paiementid = $obj->id;

$invoice = new Facture($db);
if ($invoiceid > 0)
{
	$ret = $invoice->fetch($invoiceid);
} else {
	$ret = $invoice->fetch('', '(PROV-POS'.$_SESSION["takeposterminal"].'-'.$place.')');
}
if ($ret > 0)
{
	$placeid = $invoice->id;
}

$constforcompanyid = 'CASHDESK_ID_THIRDPARTY'.$_SESSION["takeposterminal"];

$soc = new Societe($db);
if ($invoice->socid > 0) $soc->fetch($invoice->socid);
else $soc->fetch($conf->global->$constforcompanyid);


/*
 * Actions
 */

// Action to record a payment on a TakePOS invoice
if ($action == 'valid' && $user->rights->facture->creer)
{
	$bankaccount = 0;
	$error = 0;

	if (!empty($conf->global->TAKEPOS_CAN_FORCE_BANK_ACCOUNT_DURING_PAYMENT)) {
		$bankaccount = GETPOST('accountid', 'int');
	} else {
		if ($pay == "cash") $bankaccount = $conf->global->{'CASHDESK_ID_BANKACCOUNT_CASH'.$_SESSION["takeposterminal"]};            // For backward compatibility
		elseif ($pay == "card") $bankaccount = $conf->global->{'CASHDESK_ID_BANKACCOUNT_CB'.$_SESSION["takeposterminal"]};          // For backward compatibility
		elseif ($pay == "cheque") $bankaccount = $conf->global->{'CASHDESK_ID_BANKACCOUNT_CHEQUE'.$_SESSION["takeposterminal"]};    // For backward compatibility
		else {
			$accountname = "CASHDESK_ID_BANKACCOUNT_".$pay.$_SESSION["takeposterminal"];
			$bankaccount = $conf->global->$accountname;
		}
	}

	if ($bankaccount <= 0 && $pay != "delayed") {
		$errormsg = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("BankAccount"));
		$error++;
	}

	$now = dol_now();
	$res = 0;

	$invoice = new Facture($db);
	$invoice->fetch($placeid);

	if ($invoice->total_ttc < 0) {
		$invoice->type = $invoice::TYPE_CREDIT_NOTE;

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."facture WHERE";
		$sql .= " fk_soc = ".((int) $invoice->socid);
		$sql .= " AND type <> ".Facture::TYPE_CREDIT_NOTE;
		$sql .= " AND fk_statut >= ".$invoice::STATUS_VALIDATED;
		$sql .= " ORDER BY rowid DESC";

		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			$fk_source = $obj->rowid;
			if ($fk_source == null) {
				fail($langs->transnoentitiesnoconv("NoPreviousBillForCustomer"));
			}
		} else {
			fail($langs->transnoentitiesnoconv("NoPreviousBillForCustomer"));
		}
		$invoice->fk_facture_source = $fk_source;
		$invoice->update($user);
	}

	//$sav_FACTURE_ADDON = '';
	//if (!empty($conf->global->TAKEPOS_ADDON)) {
	//	$sav_FACTURE_ADDON = $conf->global->FACTURE_ADDON;
	//	if ($conf->global->TAKEPOS_ADDON == "terminal") $conf->global->FACTURE_ADDON = $conf->global->{'TAKEPOS_ADDON'.$_SESSION["takeposterminal"]};
	//	else $conf->global->FACTURE_ADDON = $conf->global->TAKEPOS_ADDON;
	//}

	$constantforkey = 'CASHDESK_NO_DECREASE_STOCK'.$_SESSION["takeposterminal"];
	if ($error) {
		dol_htmloutput_errors($errormsg, null, 1);
	} elseif ($invoice->statut != Facture::STATUS_DRAFT) {
		//If invoice is validated but it is not fully paid is not error and make the payment
		if ($invoice->getRemainToPay() > 0) {
			$res = 1;
		} else {
			dol_syslog("Sale already validated");
			dol_htmloutput_errors($langs->trans("InvoiceIsAlreadyValidated", "TakePos"), null, 1);
		}
	} elseif (count($invoice->lines) == 0) {
		$error++;
		dol_syslog('Sale without lines');
		dol_htmloutput_errors($langs->trans("NoLinesToBill", "TakePos"), null, 1);
	} elseif (!empty($conf->stock->enabled) && $conf->global->$constantforkey != "1") {
		$savconst = $conf->global->STOCK_CALCULATE_ON_BILL;
		$conf->global->STOCK_CALCULATE_ON_BILL = 1;

		$constantforkey = 'CASHDESK_ID_WAREHOUSE'.$_SESSION["takeposterminal"];
		dol_syslog("Validate invoice with stock change into warehouse defined into constant ".$constantforkey." = ".$conf->global->$constantforkey);
		$batch_rule = 0;
		if (!empty($conf->productbatch->enabled) && !empty($conf->global->CASHDESK_FORCE_DECREASE_STOCK)) {
			require_once DOL_DOCUMENT_ROOT.'/product/class/productbatch.class.php';
			$batch_rule = Productbatch::BATCH_RULE_SELLBY_EATBY_DATES_FIRST;
		}
		$res = $invoice->validate($user, '', $conf->global->$constantforkey, 0, $batch_rule);

		$conf->global->STOCK_CALCULATE_ON_BILL = $savconst;
	} else {
		$res = $invoice->validate($user);
		if ($res < 0) {
			$error++;
			dol_htmloutput_errors($invoice->error, $invoice->errors, 1);
		}
	}

	// Restore save values
	//if (!empty($sav_FACTURE_ADDON))
	//{
	//	$conf->global->FACTURE_ADDON = $sav_FACTURE_ADDON;
	//}

	// Add the payment
	if (!$error && $res >= 0) {
		$remaintopay = $invoice->getRemainToPay();
		if ($remaintopay > 0) {
			$payment = new Paiement($db);
			$payment->datepaye = $now;
			$payment->fk_account = $bankaccount;
			$payment->amounts[$invoice->id] = $amountofpayment;
			if ($pay == 'cash') $payment->pos_change = price2num(GETPOST('excess', 'alpha'));

			// If user has not used change control, add total invoice payment
			// Or if user has used change control and the amount of payment is higher than remain to pay, add the remain to pay
			if ($amountofpayment == 0 || $amountofpayment > $remaintopay) $payment->amounts[$invoice->id] = $remaintopay;

			$payment->paiementid = $paiementid;
			$payment->num_payment = $invoice->ref;

			if ($pay != "delayed") {
				$payment->create($user);
				$payment->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $bankaccount, '', '');
				$remaintopay = $invoice->getRemainToPay(); // Recalculate remain to pay after the payment is recorded
			}
		}

		if ($remaintopay == 0) {
			dol_syslog("Invoice is paid, so we set it to status Paid");
			$result = $invoice->set_paid($user);
			if ($result > 0) $invoice->paye = 1;
			// set payment method
			$invoice->setPaymentMethods($paiementid);
		} else {
			dol_syslog("Invoice is not paid, remain to pay = ".$remaintopay);
		}
	} else {
		dol_htmloutput_errors($invoice->error, $invoice->errors, 1);
	}
}

if ($action == 'creditnote')
{
	$creditnote = new Facture($db);
	$creditnote->socid = $invoice->socid;
	$creditnote->date = dol_now();
	$creditnote->type = Facture::TYPE_CREDIT_NOTE;
	$creditnote->fk_facture_source = $placeid;
	$creditnote->remise_absolue = $invoice->remise_absolue;
	$creditnote->remise_percent = $invoice->remise_percent;
	$creditnote->create($user);

	foreach ($invoice->lines as $line)
	{
		// Extrafields
		if (method_exists($line, 'fetch_optionals')) {
			// load extrafields
			$line->fetch_optionals();
		}
		// Reset fk_parent_line for no child products and special product
		if (($line->product_type != 9 && empty($line->fk_parent_line)) || $line->product_type == 9) {
			$fk_parent_line = 0;
		}
		if ($invoice->type == Facture::TYPE_SITUATION)
		{
			$source_fk_prev_id = $line->fk_prev_id; // temporary storing situation invoice fk_prev_id
			$line->fk_prev_id  = $line->id; // The new line of the new credit note we are creating must be linked to the situation invoice line it is created from
			if (!empty($invoice->tab_previous_situation_invoice))
			{
				// search the last standard invoice in cycle and the possible credit note between this last and invoice
				// TODO Move this out of loop of $invoice->lines
				$tab_jumped_credit_notes = array();
				$lineIndex = count($invoice->tab_previous_situation_invoice) - 1;
				$searchPreviousInvoice = true;
				while ($searchPreviousInvoice)
				{
					if ($invoice->tab_previous_situation_invoice[$lineIndex]->type == Facture::TYPE_SITUATION || $lineIndex < 1)
					{
						$searchPreviousInvoice = false; // find, exit;
						break;
					} else {
						if ($invoice->tab_previous_situation_invoice[$lineIndex]->type == Facture::TYPE_CREDIT_NOTE) {
							$tab_jumped_credit_notes[$lineIndex] = $invoice->tab_previous_situation_invoice[$lineIndex]->id;
						}
						$lineIndex--; // go to previous invoice in cycle
					}
				}

				$maxPrevSituationPercent = 0;
				foreach ($invoice->tab_previous_situation_invoice[$lineIndex]->lines as $prevLine)
				{
					if ($prevLine->id == $source_fk_prev_id)
					{
						$maxPrevSituationPercent = max($maxPrevSituationPercent, $prevLine->situation_percent);

						//$line->subprice  = $line->subprice - $prevLine->subprice;
						$line->total_ht  = $line->total_ht - $prevLine->total_ht;
						$line->total_tva = $line->total_tva - $prevLine->total_tva;
						$line->total_ttc = $line->total_ttc - $prevLine->total_ttc;
						$line->total_localtax1 = $line->total_localtax1 - $prevLine->total_localtax1;
						$line->total_localtax2 = $line->total_localtax2 - $prevLine->total_localtax2;

						$line->multicurrency_subprice  = $line->multicurrency_subprice - $prevLine->multicurrency_subprice;
						$line->multicurrency_total_ht  = $line->multicurrency_total_ht - $prevLine->multicurrency_total_ht;
						$line->multicurrency_total_tva = $line->multicurrency_total_tva - $prevLine->multicurrency_total_tva;
						$line->multicurrency_total_ttc = $line->multicurrency_total_ttc - $prevLine->multicurrency_total_ttc;
					}
				}

				// prorata
				$line->situation_percent = $maxPrevSituationPercent - $line->situation_percent;

				//print 'New line based on invoice id '.$invoice->tab_previous_situation_invoice[$lineIndex]->id.' fk_prev_id='.$source_fk_prev_id.' will be fk_prev_id='.$line->fk_prev_id.' '.$line->total_ht.' '.$line->situation_percent.'<br>';

				// If there is some credit note between last situation invoice and invoice used for credit note generation (note: credit notes are stored as delta)
				$maxPrevSituationPercent = 0;
				foreach ($tab_jumped_credit_notes as $index => $creditnoteid) {
					foreach ($invoice->tab_previous_situation_invoice[$index]->lines as $prevLine)
					{
						if ($prevLine->fk_prev_id == $source_fk_prev_id)
						{
							$maxPrevSituationPercent = $prevLine->situation_percent;

							$line->total_ht  -= $prevLine->total_ht;
							$line->total_tva -= $prevLine->total_tva;
							$line->total_ttc -= $prevLine->total_ttc;
							$line->total_localtax1 -= $prevLine->total_localtax1;
							$line->total_localtax2 -= $prevLine->total_localtax2;

							$line->multicurrency_subprice  -= $prevLine->multicurrency_subprice;
							$line->multicurrency_total_ht  -= $prevLine->multicurrency_total_ht;
							$line->multicurrency_total_tva -= $prevLine->multicurrency_total_tva;
							$line->multicurrency_total_ttc -= $prevLine->multicurrency_total_ttc;
						}
					}
				}

				// prorata
				$line->situation_percent += $maxPrevSituationPercent;

				//print 'New line based on invoice id '.$invoice->tab_previous_situation_invoice[$lineIndex]->id.' fk_prev_id='.$source_fk_prev_id.' will be fk_prev_id='.$line->fk_prev_id.' '.$line->total_ht.' '.$line->situation_percent.'<br>';
			}
		}

		$line->fk_facture = $creditnote->id;
		$line->fk_parent_line = $fk_parent_line;

		$line->subprice = -$line->subprice; // invert price for object
		$line->pa_ht = $line->pa_ht; // we choosed to have buy/cost price always positive, so no revert of sign here
		$line->total_ht = -$line->total_ht;
		$line->total_tva = -$line->total_tva;
		$line->total_ttc = -$line->total_ttc;
		$line->total_localtax1 = -$line->total_localtax1;
		$line->total_localtax2 = -$line->total_localtax2;

		$line->multicurrency_subprice = -$line->multicurrency_subprice;
		$line->multicurrency_total_ht = -$line->multicurrency_total_ht;
		$line->multicurrency_total_tva = -$line->multicurrency_total_tva;
		$line->multicurrency_total_ttc = -$line->multicurrency_total_ttc;

		$result = $line->insert(0, 1); // When creating credit note with same lines than source, we must ignore error if discount alreayd linked

		$creditnote->lines[] = $line; // insert new line in current object

		// Defined the new fk_parent_line
		if ($result > 0 && $line->product_type == 9) {
			$fk_parent_line = $result;
		}
	}
	$creditnote->update_price(1);

	$constantforkey = 'CASHDESK_NO_DECREASE_STOCK'.$_SESSION["takeposterminal"];
	if (!empty($conf->stock->enabled) && $conf->global->$constantforkey != "1") {
		$savconst = $conf->global->STOCK_CALCULATE_ON_BILL;
		$conf->global->STOCK_CALCULATE_ON_BILL = 1;
		$constantforkey = 'CASHDESK_ID_WAREHOUSE'.$_SESSION["takeposterminal"];
		dol_syslog("Validate invoice with stock change into warehouse defined into constant ".$constantforkey." = ".$conf->global->$constantforkey);
		$batch_rule = 0;
		if (!empty($conf->productbatch->enabled) && !empty($conf->global->CASHDESK_FORCE_DECREASE_STOCK)) {
			require_once DOL_DOCUMENT_ROOT.'/product/class/productbatch.class.php';
			$batch_rule = Productbatch::BATCH_RULE_SELLBY_EATBY_DATES_FIRST;
		}
		$res = $creditnote->validate($user, '', $conf->global->$constantforkey, 0, $batch_rule);
		$conf->global->STOCK_CALCULATE_ON_BILL = $savconst;
	} else {
		$res = $creditnote->validate($user);
	}
}

if ($action == 'history' || $action == 'creditnote')
{
	if ($action == 'creditnote') $placeid = $creditnote->id;
	else $placeid = (int) GETPOST('placeid', 'int');
	$invoice = new Facture($db);
	$invoice->fetch($placeid);
}

if (!empty($conf->multicurrency->enabled) && $_SESSION["takeposcustomercurrency"] != "") {
	$invoice->setMulticurrencyCode($_SESSION["takeposcustomercurrency"]);
}

if (($action == "addline" || $action == "freezone") && $placeid == 0)
{
	$invoice->socid = $conf->global->$constforcompanyid;
	$invoice->date = dol_now();
	$invoice->module_source = 'takepos';
	$invoice->pos_source = $_SESSION["takeposterminal"];
	$invoice->entity = !empty($_SESSION["takeposinvoiceentity"]) ? $_SESSION["takeposinvoiceentity"] : $conf->entity;

	if ($invoice->socid <= 0)
	{
		$langs->load('errors');
		dol_htmloutput_errors($langs->trans("ErrorModuleSetupNotComplete", "TakePos"), null, 1);
	} else {
		$placeid = $invoice->create($user);
		if ($placeid < 0)
		{
			dol_htmloutput_errors($invoice->error, $invoice->errors, 1);
		}
		$sql = "UPDATE ".MAIN_DB_PREFIX."facture set ref='(PROV-POS".$_SESSION["takeposterminal"]."-".$place.")' where rowid=".$placeid;
		$db->query($sql);
	}
}

if ($action == "addline")
{
	$prod = new Product($db);
	$prod->fetch($idproduct);

	$customer = new Societe($db);
	$customer->fetch($invoice->socid);

	$datapriceofproduct = $prod->getSellPrice($mysoc, $customer, 0);

	$price = $datapriceofproduct['pu_ht'];
	$price_ttc = $datapriceofproduct['pu_ttc'];
	//$price_min = $datapriceofproduct['price_min'];
	$price_base_type = $datapriceofproduct['price_base_type'];
	$tva_tx = $datapriceofproduct['tva_tx'];
	$tva_npr = $datapriceofproduct['tva_npr'];

	// Local Taxes
	$localtax1_tx = get_localtax($tva_tx, 1, $customer, $mysoc, $tva_npr);
	$localtax2_tx = get_localtax($tva_tx, 2, $customer, $mysoc, $tva_npr);

	if (!empty($conf->global->TAKEPOS_SUPPLEMENTS))
	{
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$cat = new Categorie($db);
		$categories = $cat->containing($idproduct, 'product');
		$found = (array_search($conf->global->TAKEPOS_SUPPLEMENTS_CATEGORY, array_column($categories, 'id')));
		if ($found !== false) // If this product is a supplement
		{
			$sql = "SELECT fk_parent_line FROM ".MAIN_DB_PREFIX."facturedet where rowid=$selectedline";
			$resql = $db->query($sql);
			$row = $db->fetch_array($resql);
			if ($row[0] == null) $parent_line = $selectedline;
			else $parent_line = $row[0]; //If the parent line is already a supplement, add the supplement to the main  product
		}
	}

	$idoflineadded = 0;
	if (!empty($conf->global->TAKEPOS_GROUP_SAME_PRODUCT)) {
		foreach ($invoice->lines as $line) {
			if ($line->product_ref == $prod->ref) {
				$result = $invoice->updateline($line->id, $line->desc, $line->subprice, $line->qty + 1, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);
				if ($result < 0) {
					dol_htmloutput_errors($invoice->error, $invoice->errors, 1);
				} else {
					$idoflineadded = $line->id;
				}
				break;
			}
		}
	}
	if ($idoflineadded <= 0) {
		$invoice->fetch_thirdparty();
		$idoflineadded = $invoice->addline($prod->description, $price, 1, $tva_tx, $localtax1_tx, $localtax2_tx, $idproduct, $customer->remise_percent, '', 0, 0, 0, '', $price_base_type, $price_ttc, $prod->type, -1, 0, '', 0, $parent_line, null, '', '', 0, 100, '', null, 0);
	}

	$invoice->fetch($placeid);
}

if ($action == "freezone") {
	$customer = new Societe($db);
	$customer->fetch($invoice->socid);

	$tva_tx = GETPOST('tva_tx', 'alpha');
	if ($tva_tx != '') {
		if (!preg_match('/\((.*)\)/', $tva_tx)) {
			$tva_tx = price2num($tva_tx);
		}
	} else {
		$tva_tx = get_default_tva($mysoc, $customer);
	}

	// Local Taxes
	$localtax1_tx = get_localtax($tva_tx, 1, $customer, $mysoc, $tva_npr);
	$localtax2_tx = get_localtax($tva_tx, 2, $customer, $mysoc, $tva_npr);

	$invoice->addline($desc, $number, 1, $tva_tx, $localtax1_tx, $localtax2_tx, 0, 0, '', 0, 0, 0, '', 'TTC', $number, 0, -1, 0, '', 0, 0, null, '', '', 0, 100, '', null, 0);
	$invoice->fetch($placeid);
}

if ($action == "addnote") {
	foreach ($invoice->lines as $line)
	{
		if ($line->id == $number)
		{
			$line->array_options['order_notes'] = $desc;
			$result = $invoice->updateline($line->id, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);
		}
	}
	$invoice->fetch($placeid);
}

if ($action == "deleteline") {
	if ($idline > 0 and $placeid > 0) { // If invoice exists and line selected. To avoid errors if deleted from another device or no line selected.
		$invoice->deleteline($idline);
		$invoice->fetch($placeid);
	} elseif ($placeid > 0) {             // If invoice exists but no line selected, proceed to delete last line.
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."facturedet where fk_facture = ".((int) $placeid)." ORDER BY rowid DESC";
		$resql = $db->query($sql);
		$row = $db->fetch_array($resql);
		$deletelineid = $row[0];
		$invoice->deleteline($deletelineid);
		$invoice->fetch($placeid);
	}
	if (count($invoice->lines) == 0) {
		$invoice->delete($user);
		header("Location: ".DOL_URL_ROOT."/takepos/invoice.php");
		exit;
	}
}

// Action to delete or discard an invoice
if ($action == "delete") {
	// $placeid is the invoice id (it differs from place) and is defined if the place is set and the ref of invoice is '(PROV-POS'.$_SESSION["takeposterminal"].'-'.$place.')', so the fetch at begining of page works.
	if ($placeid > 0) {
		$result = $invoice->fetch($placeid);

		if ($result > 0 && $invoice->statut == Facture::STATUS_DRAFT)
		{
			$db->begin();

			// We delete the lines
			$resdeletelines = 1;
			foreach ($invoice->lines as $line) {
				$tmpres = $invoice->deleteline($line->id);
				if ($tmpres < 0) {
					$resdeletelines = 0;
					break;
				}
			}

			$sql = "UPDATE ".MAIN_DB_PREFIX."facture";
			$sql .= " SET fk_soc=".$conf->global->{'CASHDESK_ID_THIRDPARTY'.$_SESSION["takeposterminal"]}.", ";
			$sql .= " datec = '".$db->idate(dol_now())."'";
			$sql .= " WHERE ref='(PROV-POS".$db->escape($_SESSION["takeposterminal"]."-".$place).")'";
			$resql1 = $db->query($sql);

			if ($resdeletelines && $resql1) {
            	$db->commit();
            } else {
            	$db->rollback();
            }

			$invoice->fetch($placeid);
		}
	}
}

if ($action == "updateqty")
{
	foreach ($invoice->lines as $line)
	{
		if ($line->id == $idline)
		{
			$result = $invoice->updateline($line->id, $line->desc, $line->subprice, $number, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);
		}
	}

	$invoice->fetch($placeid);
}

if ($action == "updateprice")
{
	foreach ($invoice->lines as $line)
	{
		if ($line->id == $idline)
		{
			$prod = new Product($db);
			$prod->fetch($line->fk_product);
			$customer = new Societe($db);
			$customer->fetch($invoice->socid);
			$datapriceofproduct = $prod->getSellPrice($mysoc, $customer, 0);
			$price_min = $datapriceofproduct['price_min'];
			$usercanproductignorepricemin = ((!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->produit->ignore_price_min_advance)) || empty($conf->global->MAIN_USE_ADVANCED_PERMS));
			$pu_ht = price2num($number / (1 + ($line->tva_tx / 100)), 'MU');
			//Check min price
			if ($usercanproductignorepricemin && (!empty($price_min) && (price2num($pu_ht) * (1 - price2num($line->remise_percent) / 100) < price2num($price_min))))
			{
				echo $langs->trans("CantBeLessThanMinPrice");
			} else $result = $invoice->updateline($line->id, $line->desc, $number, $line->qty, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'TTC', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);
		}
	}
	$invoice->fetch($placeid);
}

if ($action == "updatereduction")
{
	foreach ($invoice->lines as $line)
	{
		if ($line->id == $idline)
		{
			$prod = new Product($db);
			$prod->fetch($line->fk_product);
			$customer = new Societe($db);
			$customer->fetch($invoice->socid);
			$datapriceofproduct = $prod->getSellPrice($mysoc, $customer, 0);
			$price_min = $datapriceofproduct['price_min'];
			$usercanproductignorepricemin = ((!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->produit->ignore_price_min_advance)) || empty($conf->global->MAIN_USE_ADVANCED_PERMS));
			$pu_ht = price2num($line->multicurrency_subprice / (1 + ($line->tva_tx / 100)), 'MU');
			//Check min price
			if ($usercanproductignorepricemin && (!empty($price_min) && (price2num($line->multicurrency_subprice) * (1 - price2num($number) / 100) < price2num($price_min))))
			{
				echo $langs->trans("CantBeLessThanMinPrice");
			} else $result = $invoice->updateline($line->id, $line->desc, $line->multicurrency_subprice, $line->qty, $number, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);
		}
	}
	$invoice->fetch($placeid);
} elseif ($action == 'update_reduction_global') {
	foreach ($invoice->lines as $line) {
		$result = $invoice->updateline($line->id, $line->desc, $line->subprice, $line->qty, $number, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);
	}

	$invoice->fetch($placeid);
}

if ($action == "order" and $placeid != 0)
{
	include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	if ($conf->global->TAKEPOS_PRINT_METHOD == "receiptprinter" || $conf->global->TAKEPOS_PRINT_METHOD == "takeposconnector") {
		require_once DOL_DOCUMENT_ROOT.'/core/class/dolreceiptprinter.class.php';
		$printer = new dolReceiptPrinter($db);
	}

	$sql = "SELECT label FROM ".MAIN_DB_PREFIX."takepos_floor_tables where rowid=".((int) $place);
	$resql = $db->query($sql);
	$row = $db->fetch_object($resql);
	$headerorder = '<html><br><b>'.$langs->trans('Place').' '.$row->label.'<br><table width="65%"><thead><tr><th class="left">'.$langs->trans("Label").'</th><th class="right">'.$langs->trans("Qty").'</th></tr></thead><tbody>';
	$footerorder = '</tbody></table>'.dol_print_date(dol_now(), 'dayhour').'<br></html>';
	$order_receipt_printer1 = "";
	$order_receipt_printer2 = "";
	$order_receipt_printer3 = "";
	$catsprinter1 = explode(';', $conf->global->TAKEPOS_PRINTED_CATEGORIES_1);
	$catsprinter2 = explode(';', $conf->global->TAKEPOS_PRINTED_CATEGORIES_2);
	$catsprinter3 = explode(';', $conf->global->TAKEPOS_PRINTED_CATEGORIES_3);
	foreach ($invoice->lines as $line)
	{
		if ($line->special_code == "4") {
			continue;
		}
		$c = new Categorie($db);
		$existing = $c->containing($line->fk_product, Categorie::TYPE_PRODUCT, 'id');
		$result = array_intersect($catsprinter1, $existing);
		$count = count($result);
		if (!$line->fk_product) $count++; // Print Free-text item (Unassigned printer) to Printer 1
		if ($count > 0) {
			$linestoprint++;
			$sql = "UPDATE ".MAIN_DB_PREFIX."facturedet set special_code='1' where rowid=".$line->id; //Set to print on printer 1
			$db->query($sql);
			$order_receipt_printer1 .= '<tr><td class="left">';
			if ($line->fk_product) $order_receipt_printer1 .= $line->product_label;
			else $order_receipt_printer1 .= $line->description;
			$order_receipt_printer1 .= '</td><td class="right">'.$line->qty;
			if (!empty($line->array_options['options_order_notes'])) $order_receipt_printer1 .= "<br>(".$line->array_options['options_order_notes'].")";
			$order_receipt_printer1 .= '</td></tr>';
		}
	}
	if (($conf->global->TAKEPOS_PRINT_METHOD == "receiptprinter" || $conf->global->TAKEPOS_PRINT_METHOD == "takeposconnector") && $linestoprint > 0) {
		$invoice->fetch($placeid); //Reload object before send to printer
		$printer->orderprinter = 1;
		echo "<script>";
		echo "var orderprinter1esc='";
		$ret = $printer->sendToPrinter($invoice, $conf->global->{'TAKEPOS_TEMPLATE_TO_USE_FOR_ORDERS'.$_SESSION["takeposterminal"]}, $conf->global->{'TAKEPOS_ORDER_PRINTER1_TO_USE'.$_SESSION["takeposterminal"]}); // PRINT TO PRINTER 1
		echo "';</script>";
	}
	$sql = "UPDATE ".MAIN_DB_PREFIX."facturedet set special_code='4' where special_code='1' and fk_facture=".$invoice->id; // Set as printed
	$db->query($sql);
	$invoice->fetch($placeid); //Reload object after set lines as printed
	$linestoprint = 0;

	foreach ($invoice->lines as $line)
	{
		if ($line->special_code == "4") {
			continue;
		}
		$c = new Categorie($db);
		$existing = $c->containing($line->fk_product, Categorie::TYPE_PRODUCT, 'id');
		$result = array_intersect($catsprinter2, $existing);
		$count = count($result);
		if ($count > 0) {
			$linestoprint++;
			$sql = "UPDATE ".MAIN_DB_PREFIX."facturedet set special_code='2' where rowid=".$line->id; //Set to print on printer 2
			$db->query($sql);
			$order_receipt_printer2 .= '<tr>'.$line->product_label.'<td class="right">'.$line->qty;
			if (!empty($line->array_options['options_order_notes'])) $order_receipt_printer2 .= "<br>(".$line->array_options['options_order_notes'].")";
			$order_receipt_printer2 .= '</td></tr>';
		}
	}
	if (($conf->global->TAKEPOS_PRINT_METHOD == "receiptprinter" || $conf->global->TAKEPOS_PRINT_METHOD == "takeposconnector") && $linestoprint > 0) {
		$invoice->fetch($placeid); //Reload object before send to printer
		$printer->orderprinter = 2;
		echo "<script>";
		echo "var orderprinter2esc='";
		$ret = $printer->sendToPrinter($invoice, $conf->global->{'TAKEPOS_TEMPLATE_TO_USE_FOR_ORDERS'.$_SESSION["takeposterminal"]}, $conf->global->{'TAKEPOS_ORDER_PRINTER2_TO_USE'.$_SESSION["takeposterminal"]}); // PRINT TO PRINTER 2
		echo "';</script>";
	}
	$sql = "UPDATE ".MAIN_DB_PREFIX."facturedet set special_code='4' where special_code='2' and fk_facture=".$invoice->id; // Set as printed
	$db->query($sql);
	$invoice->fetch($placeid); //Reload object after set lines as printed
	$linestoprint = 0;

	foreach ($invoice->lines as $line)
	{
		if ($line->special_code == "4") {
			continue;
		}
		$c = new Categorie($db);
		$existing = $c->containing($line->fk_product, Categorie::TYPE_PRODUCT, 'id');
		$result = array_intersect($catsprinter3, $existing);
		$count = count($result);
		if ($count > 0) {
			$linestoprint++;
			$sql = "UPDATE ".MAIN_DB_PREFIX."facturedet set special_code='3' where rowid=".$line->id; //Set to print on printer 3
			$db->query($sql);
			$order_receipt_printer3 .= '<tr>'.$line->product_label.'<td class="right">'.$line->qty;
			if (!empty($line->array_options['options_order_notes'])) $order_receipt_printer3 .= "<br>(".$line->array_options['options_order_notes'].")";
			$order_receipt_printer3 .= '</td></tr>';
		}
	}
	if (($conf->global->TAKEPOS_PRINT_METHOD == "receiptprinter" || $conf->global->TAKEPOS_PRINT_METHOD == "takeposconnector") && $linestoprint > 0) {
		$invoice->fetch($placeid); //Reload object before send to printer
		$printer->orderprinter = 3;
		echo "<script>";
		echo "var orderprinter3esc='";
		$ret = $printer->sendToPrinter($invoice, $conf->global->{'TAKEPOS_TEMPLATE_TO_USE_FOR_ORDERS'.$_SESSION["takeposterminal"]}, $conf->global->{'TAKEPOS_ORDER_PRINTER3_TO_USE'.$_SESSION["takeposterminal"]}); // PRINT TO PRINTER 3
		echo "';</script>";
	}
	$sql = "UPDATE ".MAIN_DB_PREFIX."facturedet set special_code='4' where special_code='3' and fk_facture=".$invoice->id; // Set as printed
	$db->query($sql);
	$invoice->fetch($placeid); //Reload object after set lines as printed
}

$sectionwithinvoicelink = '';
if ($action == "valid" || $action == "history" || $action == 'creditnote')
{
	$sectionwithinvoicelink .= '<!-- Section with invoice link -->'."\n";
	$sectionwithinvoicelink .= '<span style="font-size:120%;" class="center">';
	$sectionwithinvoicelink .= $invoice->getNomUrl(1, '', 0, 0, '', 0, 0, -1, '_backoffice')." - ";
	$remaintopay = $invoice->getRemainToPay();
	if ($remaintopay > 0)
	{
		$sectionwithinvoicelink .= $langs->trans('RemainToPay').': <span class="amountremaintopay" style="font-size: unset">'.price($remaintopay, 1, $langs, 1, -1, -1, $conf->currency).'</span>';
	} else {
		if ($invoice->paye) $sectionwithinvoicelink .= '<span class="amountpaymentcomplete" style="font-size: unset">'.$langs->trans("Paid").'</span>';
		else $sectionwithinvoicelink .= $langs->trans('BillShortStatusValidated');
	}
	$sectionwithinvoicelink .= '</span><br>';
	if ($conf->global->TAKEPOS_PRINT_METHOD == "takeposconnector") {
		if (filter_var($conf->global->TAKEPOS_PRINT_SERVER, FILTER_VALIDATE_URL) == true) $sectionwithinvoicelink .= ' <button id="buttonprint" type="button" onclick="TakeposConnector('.$placeid.');">'.$langs->trans('PrintTicket').'</button>';
		else $sectionwithinvoicelink .= ' <button id="buttonprint" type="button" onclick="TakeposPrinting('.$placeid.');">'.$langs->trans('PrintTicket').'</button>';
	} elseif ($conf->global->TAKEPOS_PRINT_METHOD == "receiptprinter") {
		$sectionwithinvoicelink .= ' <button id="buttonprint" type="button" onclick="DolibarrTakeposPrinting('.$placeid.');">'.$langs->trans('PrintTicket').'</button>';
	} else {
		$sectionwithinvoicelink .= ' <button id="buttonprint" type="button" onclick="Print('.$placeid.');">'.$langs->trans('PrintTicket').'</button>';
		if ($conf->global->TAKEPOS_GIFT_RECEIPT) {
			$sectionwithinvoicelink .= ' <button id="buttonprint" type="button" onclick="Print('.$placeid.', 1);">'.$langs->trans('GiftReceipt').'</button>';
		}
	}
	if ($conf->global->TAKEPOS_EMAIL_TEMPLATE_INVOICE > 0)
	{
		$sectionwithinvoicelink .= ' <button id="buttonsend" type="button" onclick="SendTicket('.$placeid.');">'.$langs->trans('SendTicket').'</button>';
	}

	if ($remaintopay <= 0 && $conf->global->TAKEPOS_AUTO_PRINT_TICKETS) $sectionwithinvoicelink .= '<script language="javascript">$("#buttonprint").click();</script>';
}

/*
 * View
 */

$form = new Form($db);

?>
<script language="javascript">
var selectedline=0;
var selectedtext="";
var placeid=<?php echo ($placeid > 0 ? $placeid : 0); ?>;
$(document).ready(function() {
	var idoflineadded = <?php echo ($idoflineadded ? $idoflineadded : 0); ?>;

    $('.posinvoiceline').click(function(){
    	console.log("Click done on "+this.id);
        $('.posinvoiceline').removeClass("selected");
        $(this).addClass("selected");
        if (selectedline==this.id) return; // If is already selected
        else selectedline=this.id;
        selectedtext=$('#'+selectedline).find("td:first").html();
		<?php
		if (defined('INCLUDE_PHONEPAGE_FROM_PUBLIC_PAGE')) {
			print '$("#phonediv1").load("auto_order.php?action=editline&placeid="+placeid+"&selectedline="+selectedline, function() {
			});';
		}
		?>
    });

    /* Autoselect the line */
    if (idoflineadded > 0)
    {
        console.log("Auto select "+idoflineadded);
        $('.posinvoiceline#'+idoflineadded).click();
    }
<?php

if ($action == "order" and $order_receipt_printer1 != "") {
	if (filter_var($conf->global->TAKEPOS_PRINT_SERVER, FILTER_VALIDATE_URL) == true) {
		?>
		$.ajax({
			type: "POST",
			url: '<?php print $conf->global->TAKEPOS_PRINT_SERVER; ?>/printer/index.php',
			data: 'invoice='+orderprinter1esc
		});
		<?php
	}
	else {
		?>
		$.ajax({
			type: "POST",
			url: 'http://<?php print $conf->global->TAKEPOS_PRINT_SERVER; ?>:8111/print',
			data: '<?php
			print $headerorder.$order_receipt_printer1.$footerorder; ?>'
		});
		<?php
	}
}

if ($action == "order" and $order_receipt_printer2 != "") {
	if (filter_var($conf->global->TAKEPOS_PRINT_SERVER, FILTER_VALIDATE_URL) == true) {
		?>
		$.ajax({
			type: "POST",
			url: '<?php print $conf->global->TAKEPOS_PRINT_SERVER; ?>/printer/index.php?printer=2',
			data: 'invoice='+orderprinter2esc
		});
		<?php
	}
	else {
		?>
		$.ajax({
			type: "POST",
			url: 'http://<?php print $conf->global->TAKEPOS_PRINT_SERVER; ?>:8111/print2',
			data: '<?php
			print $headerorder.$order_receipt_printer2.$footerorder; ?>'
		});
    	<?php
	}
}

if ($action == "order" and $order_receipt_printer3 != "") {
	if (filter_var($conf->global->TAKEPOS_PRINT_SERVER, FILTER_VALIDATE_URL) == true) {
		?>
		$.ajax({
			type: "POST",
			url: '<?php print $conf->global->TAKEPOS_PRINT_SERVER; ?>/printer/index.php?printer=3',
			data: 'invoice='+orderprinter3esc
		});
		<?php
	}
}

// Set focus to search field
if ($action == "search" || $action == "valid") {
	?>
	parent.setFocusOnSearchField();
    <?php
}


if ($action == "temp" and $ticket_printer1 != "") {
	?>
    $.ajax({
        type: "POST",
        url: 'http://<?php print $conf->global->TAKEPOS_PRINT_SERVER; ?>:8111/print',
        data: '<?php
		print $header_soc.$header_ticket.$body_ticket.$ticket_printer1.$ticket_total.$footer_ticket; ?>'
    });
    <?php
}

if ($action == "search") {
	?>
    $('#search').focus();
    <?php
}

?>

});

function SendTicket(id)
{
    console.log("Open box to select the Print/Send form");
    $.colorbox({href:"send.php?facid="+id, width:"70%", height:"30%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("SendTicket"); ?>"});
}

function Print(id, gift){
    $.colorbox({href:"receipt.php?facid="+id+"&gift="+gift, width:"40%", height:"90%", transition:"none", iframe:"true", title:"<?php
	echo $langs->trans("PrintTicket"); ?>"});
}

function TakeposPrinting(id){
    var receipt;
	console.log("TakeposPrinting" + id);
    $.get("receipt.php?facid="+id, function(data, status){
        receipt=data.replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '');
        $.ajax({
            type: "POST",
            url: 'http://<?php print $conf->global->TAKEPOS_PRINT_SERVER; ?>:8111/print',
            data: receipt
        });
    });
}

function TakeposConnector(id){
	console.log("TakeposConnector" + id);
	$.get("ajax/ajax.php?action=printinvoiceticket&term=<?php echo $_SESSION["takeposterminal"]; ?>&id="+id, function(data, status){
        $.ajax({
			type: "POST",
			url: '<?php print $conf->global->TAKEPOS_PRINT_SERVER; ?>/printer/index.php',
			data: 'invoice='+data
		});
    });
}

function DolibarrTakeposPrinting(id) {
    console.log("DolibarrTakeposPrinting Printing invoice ticket " + id)
    $.ajax({
        type: "GET",
        url: "<?php print dol_buildpath('/takepos/ajax/ajax.php', 1).'?action=printinvoiceticket&term='.$_SESSION["takeposterminal"].'&id='; ?>" + id,
    });
}

function CreditNote() {
	$("#poslines").load("invoice.php?action=creditnote&invoiceid="+placeid, function() {
	});
}


$( document ).ready(function() {
	console.log("Set customer info and sales in header placeid=<?php echo $placeid; ?> status=<?php echo $invoice->statut; ?>");

    <?php
	$s = $langs->trans("Customer");
	if ($invoice->id > 0 && ($invoice->socid != $conf->global->$constforcompanyid)) {
		$s = $soc->name;
	}
	?>

    $("#customerandsales").html('');

	$("#customerandsales").append('<a class="valignmiddle tdoverflowmax100 minwidth100" id="customer" onclick="Customer();" title="<?php print dol_escape_js($s); ?>"><span class="fas fa-building paddingrightonly"></span><?php print dol_escape_js($s); ?></a>');

	<?php
	$sql = "SELECT rowid, datec, ref FROM ".MAIN_DB_PREFIX."facture";
	if (empty($conf->global->TAKEPOS_CAN_EDIT_IF_ALREADY_VALIDATED)) {
		// By default, only invoices with a ref not already defined can in list of open invoice we can edit.
		$sql .= " WHERE ref LIKE '(PROV-POS".$db->escape($_SESSION["takeposterminal"])."-0%' AND entity IN (".getEntity('invoice').")";
	} else {
		// If TAKEPOS_CAN_EDIT_IF_ALREADY_VALIDATED set, we show also draft invoice that already has a reference defined
		$sql .= " WHERE pos_source = '".$db->escape($_SESSION["takeposterminal"])."'";
		$sql .= " AND module_source = 'takepos'";
		$sql .= " AND entity IN (".getEntity('invoice').")";
	}

	$sql .= $db->order('datec', 'ASC');
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			echo '$("#customerandsales").append(\'';
			echo '<a class="valignmiddle" title="'.dol_escape_js($langs->trans("SaleStartedAt", dol_print_date($db->jdate($obj->datec), '%H:%M', 'tzuser'))).'" onclick="place=\\\'';
			$num_sale = str_replace(")", "", str_replace("(PROV-POS".$_SESSION["takeposterminal"]."-", "", $obj->ref));
			echo $num_sale;
			if (str_replace("-", "", $num_sale) > $max_sale) $max_sale = str_replace("-", "", $num_sale);
			echo '\\\'; invoiceid=\\\'';
			echo $obj->rowid;
			echo '\\\'; Refresh();">';
			if ($placeid == $obj->rowid) echo "<b>";
			echo dol_print_date($db->jdate($obj->datec), '%H:%M', 'tzuser');
			if ($placeid == $obj->rowid) echo "</b>";
			echo '</a>\');';
		}
		echo '$("#customerandsales").append(\'<a onclick="place=\\\'0-';
		echo $max_sale + 1;
		echo '\\\'; invoiceid=0; Refresh();"><span class="fa fa-plus-square" title="'.dol_escape_htmltag($langs->trans("StartAParallelSale")).'"></a>\');';
	} else {
		dol_print_error($db);
	}

	$s = '';

	$constantforkey = 'CASHDESK_NO_DECREASE_STOCK'.$_SESSION["takeposterminal"];
	if (!empty($conf->stock->enabled) && $conf->global->$constantforkey != "1")
	{
		$s = '<span class="small">';
		$constantforkey = 'CASHDESK_ID_WAREHOUSE'.$_SESSION["takeposterminal"];
		$warehouse = new Entrepot($db);
		$warehouse->fetch($conf->global->$constantforkey);
		$s .= $langs->trans("Warehouse").'<br>'.$warehouse->ref;
		$s .= '</span>';
	}
	?>

    $("#infowarehouse").html('<?php print dol_escape_js($s); ?>');

	<?php
	// Module Adherent
	$s = '';
	if (!empty($conf->adherent->enabled) && $invoice->socid > 0 && $invoice->socid != $conf->global->$constforcompanyid)
	{
		$s = '<span class="small">';
		require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
		$langs->load("members");
		$s .= $langs->trans("Member").': ';
		$adh = new Adherent($db);
		$result = $adh->fetch('', '', $invoice->socid);
		if ($result > 0)
		{
			$adh->ref = $adh->getFullName($langs);
			if (empty($adh->statut)) { $s .= "<s>"; }
			$s .= $adh->getFullName($langs);
			$s .= ' - '.$adh->type;
			if ($adh->datefin)
			{
				$s .= '<br>'.$langs->trans("SubscriptionEndDate").': '.dol_print_date($adh->datefin, 'day');
				if ($adh->hasDelay()) {
					$s .= " ".img_warning($langs->trans("Late"));
				}
			} else {
				$s .= '<br>'.$langs->trans("SubscriptionNotReceived");
				if ($adh->statut > 0) $s .= " ".img_warning($langs->trans("Late")); // displays delay Pictogram only if not a draft and not terminated
			}
	  		if (empty($adh->statut)) { $s .= "</s>"; }
		} else {
			$s .= '<br>'.$langs->trans("ThirdpartyNotLinkedToMember");
		}
		$s .= '</span>';
	}
	?>
	$("#moreinfo").html('<?php print dol_escape_js($s); ?>');

});

</script>

<?php
// Add again js for footer because this content is injected into index.php page so all init
// for tooltip and other js beautifiers must be reexecuted too.
if (!empty($conf->use_javascript_ajax))
{
	print "\n".'<!-- Includes JS Footer of Dolibarr -->'."\n";
	print '<script src="'.DOL_URL_ROOT.'/core/js/lib_foot.js.php?lang='.$langs->defaultlang.($ext ? '&'.$ext : '').'"></script>'."\n";
}

print '<!-- invoice.php place='.(int) $place.' invoice='.$invoice->ref.' mobilepage='.$mobilepage.' $_SESSION["basiclayout"]='.$_SESSION["basiclayout"].' conf->global->TAKEPOS_BAR_RESTAURANT='.$conf->global->TAKEPOS_BAR_RESTAURANT.' -->'."\n";
print '<div class="div-table-responsive-no-min invoice">';
print '<table id="tablelines" class="noborder noshadow postablelines" width="100%">';
if ($sectionwithinvoicelink && ($mobilepage == "invoice" || $mobilepage == "")) {
	print '<tr><td colspan="4">'.$sectionwithinvoicelink.'</td></tr>';
}
print '<tr class="liste_titre nodrag nodrop">';
print '<td class="linecoldescription">';
// In phone version only show when it is invoice page
if ($mobilepage == "invoice" || $mobilepage == "") {
	print '<input type="hidden" name="invoiceid" id="invoiceid" value="'.$invoice->id.'">';
}
if ($conf->global->TAKEPOS_BAR_RESTAURANT)
{
	$sql = "SELECT floor, label FROM ".MAIN_DB_PREFIX."takepos_floor_tables where rowid=".((int) $place);
	$resql = $db->query($sql);
	$obj = $db->fetch_object($resql);
	if ($obj)
	{
		$label = $obj->label;
		$floor = $obj->floor;
	}
	// In phone version only show when is invoice page
	if ($mobilepage == "invoice" || $mobilepage == "") {
		print '<span class="opacitymedium">'.$langs->trans('Place')."</span> <b>".$label."</b><br>";
		print '<span class="opacitymedium">'.$langs->trans('Floor')."</span> <b>".$floor."</b>";
	}
	elseif (defined('INCLUDE_PHONEPAGE_FROM_PUBLIC_PAGE')) print $mysoc->name;
	elseif ($mobilepage == "cats") print $langs->trans('Category');
	elseif ($mobilepage == "products") print $langs->trans('Label');
} else {
	print $langs->trans("Products");
}
print '</td>';
if ($_SESSION["basiclayout"] != 1)
{
	print '<td class="linecolqty right">'.$langs->trans('ReductionShort').'</td>';
	print '<td class="linecolqty right">'.$langs->trans('Qty').'</td>';
	print '<td class="linecolht right nowraponall">';
	print '<span class="opacitymedium small">'.$langs->trans('TotalTTCShort').'</span><br>';
	// In phone version only show when it is invoice page
	if ($mobilepage == "invoice" || $mobilepage == "") {
		print '<span id="linecolht-span-total" style="font-size:1.3em; font-weight: bold;">'.price($invoice->total_ttc, 1, '', 1, -1, -1, $conf->currency).'</span>';
		if (!empty($conf->multicurrency->enabled) && $_SESSION["takeposcustomercurrency"] != "" && $conf->currency != $_SESSION["takeposcustomercurrency"]) {
			//Only show customer currency if multicurrency module is enabled, if currency selected and if this currency selected is not the same as main currency
			include_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';
			$multicurrency = new MultiCurrency($db);
			$multicurrency->fetch(0, $_SESSION["takeposcustomercurrency"]);
			print '<br><span id="linecolht-span-total" style="font-size:0.9em; font-style:italic;">('.price($invoice->total_ttc * $multicurrency->rate->rate).' '.$_SESSION["takeposcustomercurrency"].')</span>';
		}
		print '</td>';
	}
	print '</td>';
}
elseif ($mobilepage == "invoice") print '<td class="linecolqty right">'.$langs->trans('Qty').'</td>';
print "</tr>\n";


if ($_SESSION["basiclayout"] == 1)
{
	if ($mobilepage == "cats")
	{
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$categorie = new Categorie($db);
		$categories = $categorie->get_full_arbo('product');
		$htmlforlines = '';
		foreach ($categories as $row) {
			if (defined('INCLUDE_PHONEPAGE_FROM_PUBLIC_PAGE')) $htmlforlines .= '<div class="leftcat';
			else $htmlforlines .= '<tr class="drag drop oddeven posinvoiceline';
			$htmlforlines .= '" onclick="LoadProducts('.$row['id'].');">';
			if (defined('INCLUDE_PHONEPAGE_FROM_PUBLIC_PAGE')) $htmlforlines .= '<img class="imgwrapper" width="33%" src="'.DOL_URL_ROOT.'/takepos/public/auto_order.php?genimg=cat&query=cat&id='.$row['id'].'"><br>';
			else $htmlforlines .= '<td class="left">';
			$htmlforlines .= $row['label'];
			if (defined('INCLUDE_PHONEPAGE_FROM_PUBLIC_PAGE')) $htmlforlines .= '</div>'."\n";
			else $htmlforlines .= '</td></tr>'."\n";
		}
		$htmlforlines .= '</table>';
		$htmlforlines .= '</table>';
		print $htmlforlines;
	}

	if ($mobilepage == "products")
	{
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$object = new Categorie($db);
		$catid = GETPOST('catid', 'int');
		$result = $object->fetch($catid);
		$prods = $object->getObjectsInCateg("product");
		$htmlforlines = '';
		foreach ($prods as $row) {
			if (defined('INCLUDE_PHONEPAGE_FROM_PUBLIC_PAGE')) $htmlforlines .= '<div class="leftcat';
			else $htmlforlines .= '<tr class="drag drop oddeven posinvoiceline';
			$htmlforlines .= '" onclick="AddProduct(\''.$place.'\', '.$row->id.')">';
			if (defined('INCLUDE_PHONEPAGE_FROM_PUBLIC_PAGE')) {
				$htmlforlines .= '<img class="imgwrapper" width="33%" src="'.DOL_URL_ROOT.'/takepos/public/auto_order.php?genimg=pro&query=pro&id='.$row->id.'"><br>';
				$htmlforlines .= $row->label.''.price($row->price_ttc, 1, $langs, 1, -1, -1, $conf->currency);
				$htmlforlines .= '</div>'."\n";
			}
			else {
				$htmlforlines .= '<td class="left">';
				$htmlforlines .= $row->label;
				$htmlforlines .= '<div class="right">'.price($row->price_ttc, 1, $langs, 1, -1, -1, $conf->currency).'</div>';
				$htmlforlines .= '</tr>'."\n";
			}
		}
		$htmlforlines .= '</table>';
		print $htmlforlines;
	}

	if ($mobilepage == "places")
	{
		$sql = "SELECT rowid, entity, label, leftpos, toppos, floor FROM ".MAIN_DB_PREFIX."takepos_floor_tables";
		$resql = $db->query($sql);
		$rows = array();
		$htmlforlines = '';
		while ($row = $db->fetch_array($resql)) {
			$rows[] = $row;
			$htmlforlines .= '<tr class="drag drop oddeven posinvoiceline';
			$htmlforlines .= '" onclick="LoadPlace(\''.$row['label'].'\')">';
			$htmlforlines .= '<td class="left">';
			$htmlforlines .= $row['label'];
			$htmlforlines .= '</td>';
			$htmlforlines .= '</tr>'."\n";
		}
		$htmlforlines .= '</table>';
		print $htmlforlines;
	}
}

if ($placeid > 0)
{
	//In Phone basic layout hide some content depends situation
	if ($_SESSION["basiclayout"] == 1 && $mobilepage != "invoice" && $action != "order") return;

	if (is_array($invoice->lines) && count($invoice->lines))
	{
		print '<!-- invoice.php show lines of invoices -->'."\n";
		$tmplines = array_reverse($invoice->lines);
		foreach ($tmplines as $line)
		{
			if ($line->fk_parent_line != false)
			{
				$htmlsupplements[$line->fk_parent_line] .= '<tr class="drag drop oddeven posinvoiceline';
				if ($line->special_code == "4") $htmlsupplements[$line->fk_parent_line] .= ' order';
				$htmlsupplements[$line->fk_parent_line] .= '" id="'.$line->id.'">';
				$htmlsupplements[$line->fk_parent_line] .= '<td class="left">';
				$htmlsupplements[$line->fk_parent_line] .= img_picto('', 'rightarrow');
				if ($line->product_label) $htmlsupplements[$line->fk_parent_line] .= $line->product_label;
				if ($line->product_label && $line->desc) $htmlsupplements[$line->fk_parent_line] .= '<br>';
				if ($line->product_label != $line->desc)
				{
					$firstline = dolGetFirstLineOfText($line->desc);
					if ($firstline != $line->desc)
					{
						$htmlsupplements[$line->fk_parent_line] .= $form->textwithpicto(dolGetFirstLineOfText($line->desc), $line->desc);
					} else {
						$htmlsupplements[$line->fk_parent_line] .= $line->desc;
					}
				}
				$htmlsupplements[$line->fk_parent_line] .= '</td>';
				if ($_SESSION["basiclayout"] != 1)
				{
					$htmlsupplements[$line->fk_parent_line] .= '<td class="right">'.vatrate($line->remise_percent, true).'</td>';
					$htmlsupplements[$line->fk_parent_line] .= '<td class="right">'.$line->qty.'</td>';
					$htmlsupplements[$line->fk_parent_line] .= '<td class="right">'.price($line->total_ttc).'</td>';
				}
				$htmlsupplements[$line->fk_parent_line] .= '</tr>'."\n";
				continue;
			}
			$htmlforlines = '';

			$htmlforlines .= '<tr class="drag drop oddeven posinvoiceline';
			if ($line->special_code == "4") {
				$htmlforlines .= ' order';
			}
			$htmlforlines .= '" id="'.$line->id.'">';
			$htmlforlines .= '<td class="left">';
			if ($_SESSION["basiclayout"] == 1) $htmlforlines .= '<span class="phoneqty">'.$line->qty."</span> x ";
			if (isset($line->product_type))
			{
				if (empty($line->product_type)) $htmlforlines .= img_object('', 'product').' ';
				else $htmlforlines .= img_object('', 'service').' ';
			}
			if (empty($conf->global->TAKEPOS_SHOW_N_FIRST_LINES)) {
				$tooltiptext = '';
				if ($line->product_ref) {
					$tooltiptext .= '<b>'.$langs->trans("Ref").'</b> : '.$line->product_ref.'<br>';
					$tooltiptext .= '<b>'.$langs->trans("Label").'</b> : '.$line->product_label.'<br>';
					if ($line->product_label != $line->desc) {
						if ($line->desc) $tooltiptext .= '<br>';
						$tooltiptext .= $line->desc;
					}
				}
				$htmlforlines .= $form->textwithpicto($line->product_label ? $line->product_label : ($line->product_ref ? $line->product_ref : dolGetFirstLineOfText($line->desc, 1)), $tooltiptext);
			} else {
				if ($line->product_label) $htmlforlines .= $line->product_label;
				if ($line->product_label != $line->desc)
				{
					if ($line->product_label && $line->desc) $htmlforlines .= '<br>';
					$firstline = dolGetFirstLineOfText($line->desc, $conf->global->TAKEPOS_SHOW_N_FIRST_LINES);
					if ($firstline != $line->desc)
					{
						$htmlforlines .= $form->textwithpicto(dolGetFirstLineOfText($line->desc), $line->desc);
					} else {
						$htmlforlines .= $line->desc;
					}
				}
			}
			if (!empty($line->array_options['options_order_notes'])) $htmlforlines .= "<br>(".$line->array_options['options_order_notes'].")";
			if ($_SESSION["basiclayout"] == 1) {
				$htmlforlines .= '</td><td class="right phonetable"><button type="button" onclick="SetQty(place, '.$line->rowid.', '.($line->qty - 1).');" class="publicphonebutton2 phonered">-</button>&nbsp;&nbsp;<button type="button" onclick="SetQty(place, '.$line->rowid.', '.($line->qty + 1).');" class="publicphonebutton2 phonegreen">+</button>';
			}
			if ($_SESSION["basiclayout"] != 1)
			{
				$moreinfo = '';
				$moreinfo .= $langs->transcountry("TotalHT", $mysoc->country_code).': '.price($line->total_ht);
				if ($line->vat_src_code) $moreinfo .= '<br>'.$langs->trans("VATCode").': '.$line->vat_src_code;
				$moreinfo .= '<br>'.$langs->transcountry("TotalVAT", $mysoc->country_code).': '.price($line->total_tva);
				$moreinfo .= '<br>'.$langs->transcountry("TotalLT1", $mysoc->country_code).': '.price($line->total_localtax1);
				$moreinfo .= '<br>'.$langs->transcountry("TotalLT2", $mysoc->country_code).': '.price($line->total_localtax2);
				$moreinfo .= '<br>'.$langs->transcountry("TotalTTC", $mysoc->country_code).': '.price($line->total_ttc);
				//$moreinfo .= $langs->trans("TotalHT").': '.$line->total_ht;
				if ($line->date_start || $line->date_end) $htmlforlines .= '<br><div class="clearboth nowraponall">'.get_date_range($line->date_start, $line->date_end).'</div>';
				$htmlforlines .= '</td>';
				$htmlforlines .= '<td class="right">'.vatrate($line->remise_percent, true).'</td>';
				$htmlforlines .= '<td class="right">';
				if (!empty($conf->stock->enabled) && !empty($user->rights->stock->mouvement->lire))
				{
					$constantforkey = 'CASHDESK_ID_WAREHOUSE'.$_SESSION["takeposterminal"];
					if (!empty($conf->global->$constantforkey) && $line->fk_product > 0) {
						$sql = "SELECT e.rowid, e.ref, e.lieu, e.fk_parent, e.statut, ps.reel, ps.rowid as product_stock_id, p.pmp";
						$sql .= " FROM ".MAIN_DB_PREFIX."entrepot as e,";
						$sql .= " ".MAIN_DB_PREFIX."product_stock as ps";
						$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = ps.fk_product";
						$sql .= " WHERE ps.reel != 0";
						$sql .= " AND ps.fk_entrepot = ".$conf->global->$constantforkey;
						$sql .= " AND e.entity IN (".getEntity('stock').")";
						$sql .= " AND ps.fk_product = ".$line->fk_product;
						$resql = $db->query($sql);
						if ($resql) {
							$obj = $db->fetch_object($resql);
							$stock_real = price2num($obj->reel, 'MS');
							$htmlforlines .= $line->qty;
							if ($line->qty && $line->qty > $stock_real) $htmlforlines .= '<span style="color: var(--amountremaintopaycolor)">';
							$htmlforlines .= ' <span class="posstocktoolow">('.$langs->trans("Stock").' '.$stock_real.')</span>';
							if ($line->qty && $line->qty > $stock_real) $htmlforlines .= "</span>";
						} else {
							dol_print_error($db);
						}
					} else {
						$htmlforlines .= $line->qty;
					}
				} else {
					$htmlforlines .= $line->qty;
				}

				$htmlforlines .= '</td>';
				$htmlforlines .= '<td class="right classfortooltip" title="'.$moreinfo.'">';
				$htmlforlines .= price($line->total_ttc, 1, '', 1, -1, -1, $conf->currency);
				if (!empty($conf->multicurrency->enabled) && $_SESSION["takeposcustomercurrency"] != "" && $conf->currency != $_SESSION["takeposcustomercurrency"]) {
					//Only show customer currency if multicurrency module is enabled, if currency selected and if this currency selected is not the same as main currency
					include_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';
					$multicurrency = new MultiCurrency($db);
					$multicurrency->fetch(0, $_SESSION["takeposcustomercurrency"]);
					$htmlforlines .= '<br><span id="linecolht-span-total" style="font-size:0.9em; font-style:italic;">('.price($line->total_ttc * $multicurrency->rate->rate).' '.$_SESSION["takeposcustomercurrency"].')</span>';
				}
				$htmlforlines .= '</td>';
			}
			$htmlforlines .= '</tr>'."\n";
			$htmlforlines .= $htmlsupplements[$line->id];

			print $htmlforlines;
		}
	} else {
		print '<tr class="drag drop oddeven"><td class="left"><span class="opacitymedium">'.$langs->trans("Empty").'</span></td><td></td><td></td><td></td></tr>';
	}
} else {      // No invoice generated yet
	print '<tr class="drag drop oddeven"><td class="left"><span class="opacitymedium">'.$langs->trans("Empty").'</span></td><td></td><td></td><td></td></tr>';
}

print '</table>';

if (($action == "valid" || $action == "history") && $invoice->type != Facture::TYPE_CREDIT_NOTE) {
	print '<button id="buttonprint" type="button" onclick="ModalBox(\'ModalCreditNote\')">'.$langs->trans('CreateCreditNote').'</button>';
}


if ($action == "search")
{
	print '<center>
	<input type="text" id="search" name="search" onkeyup="Search2();" name="search" style="width:80%;font-size: 150%;" placeholder=' . $langs->trans('Search').'
	</center>';
}

print '</div>';
