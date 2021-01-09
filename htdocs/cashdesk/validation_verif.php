<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier    <jeremie.o@laposte.net>
 * Copyright (C) 2008-2009 Laurent Destailleur <eldy@uers.sourceforge.net>
 * Copyright (C) 2011	   Juanjo Menent	   <jmenent@2byte.es>
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
 *	\file       htdocs/cashdesk/validation_verif.php
 *	\ingroup    cashdesk
 *	\brief      validation_verif.php
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/cashdesk/include/environnement.php';
require_once DOL_DOCUMENT_ROOT.'/cashdesk/class/Facturation.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$obj_facturation = unserialize($_SESSION['serObjFacturation']);

$action = GETPOST('action', 'aZ09');
$bankaccountid = GETPOST('cashdeskbank');

switch ($action)
{
	default:
		$redirection = DOL_URL_ROOT.'/cashdesk/affIndex.php?menutpl=validation';
		break;

	case 'validate_sell':
		$thirdpartyid = $_SESSION['CASHDESK_ID_THIRDPARTY'];

		$company = new Societe($db);
		$company->fetch($thirdpartyid);

		$invoice = new Facture($db);
		$invoice->date = dol_now();
		$invoice->type = Facture::TYPE_STANDARD;

		// To use a specific numbering module for POS, reset $conf->global->FACTURE_ADDON and other vars here
		// and restore values just after
		$sav_FACTURE_ADDON = '';
		if (!empty($conf->global->POS_ADDON))
		{
			$sav_FACTURE_ADDON = $conf->global->FACTURE_ADDON;
			$conf->global->FACTURE_ADDON = $conf->global->POS_ADDON;

			// To force prefix only for POS with terre module
			if (!empty($conf->global->POS_NUMBERING_TERRE_FORCE_PREFIX)) $conf->global->INVOICE_NUMBERING_TERRE_FORCE_PREFIX = $conf->global->POS_NUMBERING_TERRE_FORCE_PREFIX;
			// To force prefix only for POS with mars module
			if (!empty($conf->global->POS_NUMBERING_MARS_FORCE_PREFIX)) $conf->global->INVOICE_NUMBERING_MARS_FORCE_PREFIX = $conf->global->POS_NUMBERING_MARS_FORCE_PREFIX;
			// To force rule only for POS with mercure
			//...
		}

		$num = $invoice->getNextNumRef($company);

		// Restore save values
		if (!empty($sav_FACTURE_ADDON))
		{
			$conf->global->FACTURE_ADDON = $sav_FACTURE_ADDON;
		}

		$obj_facturation->numInvoice($num);

		$obj_facturation->getSetPaymentMode($_POST['hdnChoix']);

		// Si paiement autre qu'en especes, montant encaisse = prix total
		$mode_reglement = $obj_facturation->getSetPaymentMode();
		if ($mode_reglement != 'ESP') {
			$montant = $obj_facturation->amountWithTax();
		} else {
			$montant = $_POST['txtEncaisse'];
		}

		if ($mode_reglement != 'DIF') {
			$obj_facturation->amountCollected($montant);

			//Determination de la somme rendue
			$total = $obj_facturation->amountWithTax();
			$encaisse = $obj_facturation->amountCollected();

			$obj_facturation->amountReturned($encaisse - $total);
		} else {
			//$txtDatePaiement=$_POST['txtDatePaiement'];
			$datePaiement = dol_mktime(0, 0, 0, $_POST['txtDatePaiementmonth'], $_POST['txtDatePaiementday'], $_POST['txtDatePaiementyear']);
			$txtDatePaiement = dol_print_date($datePaiement, 'dayrfc');
			$obj_facturation->paiementLe($txtDatePaiement);
		}

		$redirection = 'affIndex.php?menutpl=validation';
		break;


	case 'retour':
		$redirection = 'affIndex.php?menutpl=facturation';
		break;


	case 'validate_invoice':
		$now = dol_now();

		// Recuperation de la date et de l'heure
		$date = dol_print_date($now, 'day');
		$heure = dol_print_date($now, 'hour');

		$note = '';
		if (!is_object($obj_facturation))
		{
			dol_print_error('', 'Empty context');
			exit;
		}

		switch ($obj_facturation->getSetPaymentMode())
		{
			case 'DIF':
				$mode_reglement_id = 0;
				//$cond_reglement_id = dol_getIdFromCode($db,'RECEP','cond_reglement','code','rowid')
				$cond_reglement_id = 0;
				break;
			case 'ESP':
				$mode_reglement_id = dol_getIdFromCode($db, 'LIQ', 'c_paiement', 'code', 'id', 1);
				$cond_reglement_id = 0;
				$note .= $langs->trans("Cash")."\n";
				$note .= $langs->trans("Received").' : '.$obj_facturation->amountCollected()." ".$conf->currency."\n";
				$note .= $langs->trans("Rendu").' : '.$obj_facturation->amountReturned()." ".$conf->currency."\n";
				$note .= "\n";
				$note .= '--------------------------------------'."\n\n";
				break;
			case 'CB':
				$mode_reglement_id = dol_getIdFromCode($db, 'CB', 'c_paiement', 'code', 'id', 1);
				$cond_reglement_id = 0;
				break;
			case 'CHQ':
				$mode_reglement_id = dol_getIdFromCode($db, 'CHQ', 'c_paiement', 'code', 'id', 1);
				$cond_reglement_id = 0;
				break;
		}
		if (empty($mode_reglement_id)) $mode_reglement_id = 0; // If mode_reglement_id not found
		if (empty($cond_reglement_id)) $cond_reglement_id = 0; // If cond_reglement_id not found
		$note .= $_POST['txtaNotes'];
		dol_syslog("obj_facturation->getSetPaymentMode()=".$obj_facturation->getSetPaymentMode()." mode_reglement_id=".$mode_reglement_id." cond_reglement_id=".$cond_reglement_id);

		$error = 0;


		$db->begin();

		$user->fetch($_SESSION['uid']);
		$user->getrights();

		$thirdpartyid = $_SESSION['CASHDESK_ID_THIRDPARTY'];
		$societe = new Societe($db);
		$societe->fetch($thirdpartyid);

		$invoice = new Facture($db);

		// Get content of cart
		$tab_liste = $_SESSION['poscart'];

		// Loop on each line into cart
		$tab_liste_size = count($tab_liste);
		for ($i = 0; $i < $tab_liste_size; $i++)
		{
			$tmp = getTaxesFromId($tab_liste[$i]['fk_tva']);
			$vat_rate = $tmp['rate'];
			$vat_npr = $tmp['npr'];
			$vat_src_code = $tmp['code'];

			$invoiceline = new FactureLigne($db);
			$invoiceline->fk_product = $tab_liste[$i]['fk_article'];
			$invoiceline->desc = $tab_liste[$i]['label'];
			$invoiceline->qty = $tab_liste[$i]['qte'];
			$invoiceline->remise_percent = $tab_liste[$i]['remise_percent'];
			$invoiceline->price = $tab_liste[$i]['price'];
			$invoiceline->subprice = $tab_liste[$i]['price'];

			$invoiceline->tva_tx = empty($vat_rate) ? 0 : $vat_rate; // works even if vat_rate is ''
			$invoiceline->info_bits = empty($vat_npr) ? 0 : $vat_npr;
			$invoiceline->vat_src_code = $vat_src_code;

			$invoiceline->total_ht = $tab_liste[$i]['total_ht'];
			$invoiceline->total_ttc = $tab_liste[$i]['total_ttc'];
			$invoiceline->total_tva = $tab_liste[$i]['total_vat'];
			$invoiceline->total_localtax1 = $tab_liste[$i]['total_localtax1'];
			$invoiceline->total_localtax2 = $tab_liste[$i]['total_localtax2'];

			$invoice->lines[] = $invoiceline;
		}

		$invoice->socid = $conf_fksoc;
		$invoice->date_creation = $now;
		$invoice->date = $now;
		$invoice->date_lim_reglement = 0;
		$invoice->total_ht = $obj_facturation->amountWithoutTax();
		$invoice->total_tva = $obj_facturation->amountVat();
		$invoice->total_ttc = $obj_facturation->amountWithTax();
		$invoice->note_private = $note;
		$invoice->cond_reglement_id = $cond_reglement_id;
		$invoice->mode_reglement_id = $mode_reglement_id;
		$invoice->module_source = 'cashdesk';
		$invoice->pos_source = '0';
		//print "c=".$invoice->cond_reglement_id." m=".$invoice->mode_reglement_id; exit;

		// Si paiement differe ...
		if ($obj_facturation->getSetPaymentMode() == 'DIF')
		{
			$resultcreate = $invoice->create($user, 0, dol_stringtotime($obj_facturation->paiementLe()));
			if ($resultcreate > 0)
			{
				$warehouseidtodecrease = (isset($_SESSION["CASHDESK_ID_WAREHOUSE"]) ? $_SESSION["CASHDESK_ID_WAREHOUSE"] : 0);
				if (!empty($conf->global->CASHDESK_NO_DECREASE_STOCK)) $warehouseidtodecrease = 0; // If a particular stock is defined, we disable choice

				$resultvalid = $invoice->validate($user, $obj_facturation->numInvoice(), 0);

				if ($warehouseidtodecrease > 0)
				{
					// Decrease
					require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
					$langs->load("agenda");
					// Loop on each line
					$cpt = count($invoice->lines);
					for ($i = 0; $i < $cpt; $i++)
					{
						if ($invoice->lines[$i]->fk_product > 0)
						{
							$mouvP = new MouvementStock($db);
							$mouvP->origin = &$invoice;
							// We decrease stock for product
							if ($invoice->type == $invoice::TYPE_CREDIT_NOTE) $result = $mouvP->reception($user, $invoice->lines[$i]->fk_product, $warehouseidtodecrease, $invoice->lines[$i]->qty, $invoice->lines[$i]->subprice, $langs->trans("InvoiceValidatedInDolibarrFromPos", $invoice->newref));
							else $result = $mouvP->livraison($user, $invoice->lines[$i]->fk_product, $warehouseidtodecrease, $invoice->lines[$i]->qty, $invoice->lines[$i]->subprice, $langs->trans("InvoiceValidatedInDolibarrFromPos", $invoice->newref));
							if ($result < 0) {
								$error++;
							}
						}
					}
				}
			} else {
				setEventMessages($invoice->error, $invoice->errors, 'errors');
				$error++;
			}

			$id = $invoice->id;
		} else {
			$resultcreate = $invoice->create($user, 0, 0);
			if ($resultcreate > 0)
			{
				$warehouseidtodecrease = (isset($_SESSION["CASHDESK_ID_WAREHOUSE"]) ? $_SESSION["CASHDESK_ID_WAREHOUSE"] : 0);
				if (!empty($conf->global->CASHDESK_NO_DECREASE_STOCK)) $warehouseidtodecrease = 0; // If a particular stock is defined, we disable choice

				$resultvalid = $invoice->validate($user, $obj_facturation->numInvoice(), 0);

				if ($warehouseidtodecrease > 0)
				{
					// Decrease
					require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
					$langs->load("agenda");
					// Loop on each line
					$cpt = count($invoice->lines);
					for ($i = 0; $i < $cpt; $i++)
					{
						if ($invoice->lines[$i]->fk_product > 0)
						{
							$mouvP = new MouvementStock($db);
							$mouvP->origin = &$invoice;
							// We decrease stock for product
							if ($invoice->type == $invoice::TYPE_CREDIT_NOTE) $result = $mouvP->reception($user, $invoice->lines[$i]->fk_product, $warehouseidtodecrease, $invoice->lines[$i]->qty, $invoice->lines[$i]->subprice, $langs->trans("InvoiceValidatedInDolibarrFromPos", $invoice->newref));
							else $result = $mouvP->livraison($user, $invoice->lines[$i]->fk_product, $warehouseidtodecrease, $invoice->lines[$i]->qty, $invoice->lines[$i]->subprice, $langs->trans("InvoiceValidatedInDolibarrFromPos", $invoice->newref));
							if ($result < 0) {
								setEventMessages($mouvP->error, $mouvP->errors, 'errors');
								$error++;
							}
						}
					}
				}

				$id = $invoice->id;

				// Add the payment
				$payment = new Paiement($db);
				$payment->datepaye = $now;
				$payment->amounts[$invoice->id] = $obj_facturation->amountWithTax();
				$payment->note_public = $langs->trans("Payment").' '.$langs->trans("Invoice").' '.$obj_facturation->numInvoice();
				$payment->paiementid = $invoice->mode_reglement_id;
				$payment->num_paiement = '';
				$payment->num_payment = '';

				$paiement_id = $payment->create($user);
				if ($paiement_id > 0)
				{
					if (!$error)
					{
						$result = $payment->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $bankaccountid, '', '');
						if (!$result > 0)
						{
							$errmsg = $paiement->error;
							$error++;
						}
					}

					if (!$error)
					{
						if ($invoice->total_ttc == $obj_facturation->amountWithTax()
							&& $obj_facturation->getSetPaymentMode() != 'DIFF')
						{
							// We set status to payed
							$result = $invoice->set_paid($user);
				  			//print 'set paid';exit;
						}
					}
				} else {
					setEventMessages($invoice->error, $invoice->errors, 'errors');
					$error++;
				}
			} else {
				setEventMessages($invoice->error, $invoice->errors, 'errors');
				$error++;
			}
		}


		if (!$error)
		{
			$db->commit();
			$redirection = 'affIndex.php?menutpl=validation_ok&facid='.$id; // Ajout de l'id de la facture, pour l'inclure dans un lien pointant directement vers celle-ci dans Dolibarr
		} else {
			$db->rollback();
			$redirection = 'affIndex.php?facid='.$id.'&error=1&mesg=ErrorFailedToCreateInvoice'; // Ajout de l'id de la facture, pour l'inclure dans un lien pointant directement vers celle-ci dans Dolibarr
		}
		break;

		// End of case: validate_invoice
}

unset($_SESSION['serObjFacturation']);

$_SESSION['serObjFacturation'] = serialize($obj_facturation);

header('Location: '.$redirection);
exit;
