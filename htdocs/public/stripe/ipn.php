<?php
/* Copyright (C) 2018-2020  Thibault FOUCART            <support@ptibogxiv.net>
 * Copyright (C) 2018-2024  Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2023       Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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

if (!defined('NOLOGIN')) {
	define("NOLOGIN", 1); // This means this output page does not require to be logged.
}
if (!defined('NOCSRFCHECK')) {
	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}

// Because 2 entities can have the same ref.
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

// So log file will have a suffix
if (!defined('USESUFFIXINLOG')) {
	define('USESUFFIXINLOG', '_stripeipn');
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/ccountry.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/includes/stripe/stripe-php/init.php';
require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 */

// You can find your endpoint's secret in your webhook settings
if (GETPOSTISSET('connect')) {
	if (GETPOSTISSET('test')) {
		$endpoint_secret = getDolGlobalString('STRIPE_TEST_WEBHOOK_CONNECT_KEY');
		$service = 'StripeTest';
		$servicestatus = 0;
	} else {
		$endpoint_secret = getDolGlobalString('STRIPE_LIVE_WEBHOOK_CONNECT_KEY');
		$service = 'StripeLive';
		$servicestatus = 1;
	}
} else {
	if (GETPOSTISSET('test')) {
		$endpoint_secret = getDolGlobalString('STRIPE_TEST_WEBHOOK_KEY');
		$service = 'StripeTest';
		$servicestatus = 0;
	} else {
		$endpoint_secret = getDolGlobalString('STRIPE_LIVE_WEBHOOK_KEY');
		$service = 'StripeLive';
		$servicestatus = 1;
	}
}

if (!isModEnabled('stripe')) {
	httponly_accessforbidden('Module Stripe not enabled');
}

if (empty($endpoint_secret)) {
	httponly_accessforbidden('Error: Setup of module Stripe not complete for mode '.dol_escape_htmltag($service).'. The WEBHOOK_KEY is not defined.', 400, 1);
}

if (getDolGlobalString('STRIPE_USER_ACCOUNT_FOR_ACTIONS')) {
	// We set the user to use for all ipn actions in Dolibarr
	$user = new User($db);
	$user->fetch(getDolGlobalString('STRIPE_USER_ACCOUNT_FOR_ACTIONS'));
	$user->loadRights();
} else {
	httponly_accessforbidden('Error: Setup of module Stripe not complete for mode '.dol_escape_htmltag($service).'. The STRIPE_USER_ACCOUNT_FOR_ACTIONS is not defined.', 400, 1);
}

$now = dol_now();

// Security
// The test on security key is done later into constructEvent() method.


/*
 * Actions
 */

$payload = @file_get_contents("php://input");
$sig_header = empty($_SERVER["HTTP_STRIPE_SIGNATURE"]) ? '' : $_SERVER["HTTP_STRIPE_SIGNATURE"];
$event = null;

if (getDolGlobalString('STRIPE_DEBUG')) {
	$fh = fopen(DOL_DATA_ROOT.'/dolibarr_stripeipn_payload.log', 'w+');
	if ($fh) {
		fwrite($fh, dol_print_date(dol_now('gmt'), 'standard').' IPN Called. service='.$service.' HTTP_STRIPE_SIGNATURE='.$sig_header."\n");
		fwrite($fh, $payload);
		fclose($fh);
		dolChmod(DOL_DATA_ROOT.'/dolibarr_stripeipn_payload.log');
	}
}

$error = 0;

try {
	$event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
} catch (UnexpectedValueException $e) {
	// Invalid payload
	httponly_accessforbidden('Invalid payload', 400);
} catch (\Stripe\Exception\SignatureVerificationException $e) {
	httponly_accessforbidden('Invalid signature. May be a hook for an event created by another Stripe env ? Check setup of your keys whsec_...', 400);
} catch (Exception $e) {
	httponly_accessforbidden('Error '.$e->getMessage(), 400);
}

// Do something with $event

$langs->load("main");


if (isModEnabled('multicompany') && !empty($conf->stripeconnect->enabled) && is_object($mc)) {
	$sql = "SELECT entity";
	$sql .= " FROM ".MAIN_DB_PREFIX."oauth_token";
	$sql .= " WHERE service = '".$db->escape($service)."' and tokenstring LIKE '%".$db->escape($db->escapeforlike($event->account))."%'";

	dol_syslog(get_class($db)."::fetch", LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {
		if ($db->num_rows($result)) {
			$obj = $db->fetch_object($result);
			$key = $obj->entity;
		} else {
			$key = 1;
		}
	} else {
		$key = 1;
	}
	$ret = $mc->switchEntity($key);
}

// list of  action
$stripe = new Stripe($db);

// Subject
$societeName = getDolGlobalString('MAIN_INFO_SOCIETE_NOM');
if (getDolGlobalString('MAIN_APPLICATION_TITLE')) {
	$societeName = getDolGlobalString('MAIN_APPLICATION_TITLE');
}

top_httphead();

dol_syslog("***** Stripe IPN was called with event->type=".$event->type." service=".$service);


if ($event->type == 'payout.created') {
	// When a payout is create by Stripe to transfer money to your account
	$error = 0;

	$result = dolibarr_set_const($db, $service."_NEXTPAYOUT", date('Y-m-d H:i:s', $event->data->object->arrival_date), 'chaine', 0, '', $conf->entity);

	if ($result > 0) {
		$subject = $societeName.' - [NOTIFICATION] Stripe payout scheduled';
		if (!empty($user->email)) {
			$sendto = dolGetFirstLastname($user->firstname, $user->lastname)." <".$user->email.">";
		} else {
			$sendto = getDolGlobalString('MAIN_INFO_SOCIETE_MAIL') . '" <' . getDolGlobalString('MAIN_INFO_SOCIETE_MAIL').'>';
		}
		$replyto = $sendto;
		$sendtocc = '';
		if (getDolGlobalString('ONLINE_PAYMENT_SENDEMAIL')) {
			$sendtocc = getDolGlobalString('ONLINE_PAYMENT_SENDEMAIL') . '" <' . getDolGlobalString('ONLINE_PAYMENT_SENDEMAIL').'>';
		}

		$message = "A bank transfer of ".price2num($event->data->object->amount / 100)." ".$event->data->object->currency." should arrive in your account the ".dol_print_date($event->data->object->arrival_date, 'dayhour');

		$mailfile = new CMailFile(
			$subject,
			$sendto,
			$replyto,
			$message,
			array(),
			array(),
			array(),
			$sendtocc,
			'',
			0,
			-1
		);

		$ret = $mailfile->sendfile();

		return 1;
	} else {
		$error++;
		http_response_code(500);
		return -1;
	}
} elseif ($event->type == 'payout.paid') {
	// When a payout to transfer money to your account is completely done
	$error = 0;
	$result = dolibarr_set_const($db, $service."_NEXTPAYOUT", 0, 'chaine', 0, '', $conf->entity);
	if ($result) {
		$langs->load("errors");

		$dateo = dol_now();
		$label = $event->data->object->description;
		$amount = $event->data->object->amount / 100;
		$amount_to = $event->data->object->amount / 100;
		require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

		$accountfrom = new Account($db);
		$accountfrom->fetch(getDolGlobalInt('STRIPE_BANK_ACCOUNT_FOR_PAYMENTS'));

		$accountto = new Account($db);
		$accountto->fetch(getDolGlobalInt('STRIPE_BANK_ACCOUNT_FOR_BANKTRANSFERS'));

		if (($accountto->id != $accountfrom->id) && empty($error)) {
			$bank_line_id_from = 0;
			$bank_line_id_to = 0;
			$result = 0;

			// By default, electronic transfer from bank to bank
			$typefrom = 'PRE';
			$typeto = 'VIR';

			if (!$error) {
				$bank_line_id_from = $accountfrom->addline($dateo, $typefrom, $label, -1 * (float) price2num($amount), '', '', $user);
			}
			if (!($bank_line_id_from > 0)) {
				$error++;
			}
			if (!$error) {
				$bank_line_id_to = $accountto->addline($dateo, $typeto, $label, price2num($amount), '', '', $user);
			}
			if (!($bank_line_id_to > 0)) {
				$error++;
			}

			if (!$error) {
				$result = $accountfrom->add_url_line($bank_line_id_from, $bank_line_id_to, DOL_URL_ROOT.'/compta/bank/line.php?rowid=', '(banktransfert)', 'banktransfert');
			}
			if (!($result > 0)) {
				$error++;
			}
			if (!$error) {
				$result = $accountto->add_url_line($bank_line_id_to, $bank_line_id_from, DOL_URL_ROOT.'/compta/bank/line.php?rowid=', '(banktransfert)', 'banktransfert');
			}
			if (!($result > 0)) {
				$error++;
			}
		}

		$subject = $societeName.' - [NOTIFICATION] Stripe payout done';
		if (!empty($user->email)) {
			$sendto = dolGetFirstLastname($user->firstname, $user->lastname)." <".$user->email.">";
		} else {
			$sendto = getDolGlobalString('MAIN_INFO_SOCIETE_MAIL') . '" <' . getDolGlobalString('MAIN_INFO_SOCIETE_MAIL').'>';
		}
		$replyto = $sendto;
		$sendtocc = '';
		if (getDolGlobalString('ONLINE_PAYMENT_SENDEMAIL')) {
			$sendtocc = getDolGlobalString('ONLINE_PAYMENT_SENDEMAIL') . '" <' . getDolGlobalString('ONLINE_PAYMENT_SENDEMAIL').'>';
		}

		$message = "A bank transfer of ".price2num($event->data->object->amount / 100)." ".$event->data->object->currency." has been done to your account the ".dol_print_date($event->data->object->arrival_date, 'dayhour');

		$mailfile = new CMailFile(
			$subject,
			$sendto,
			$replyto,
			$message,
			array(),
			array(),
			array(),
			$sendtocc,
			'',
			0,
			-1
		);

		$ret = $mailfile->sendfile();

		return 1;
	} else {
		$error++;
		http_response_code(500);
		return -1;
	}
} elseif ($event->type == 'customer.source.created') {
	//TODO: save customer's source
} elseif ($event->type == 'customer.source.updated') {
	//TODO: update customer's source
} elseif ($event->type == 'customer.source.delete') {
	//TODO: delete customer's source
} elseif ($event->type == 'customer.deleted') {
	// When a customer account is delete on Stripe side
	$db->begin();
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."societe_account WHERE key_account = '".$db->escape($event->data->object->id)."' AND site = 'stripe'";
	$db->query($sql);
	$db->commit();
} elseif ($event->type == 'payment_intent.succeeded') {
	// Called when making payment with PaymentIntent method ($conf->global->STRIPE_USE_NEW_CHECKOUT is on).

	//dol_syslog("object = ".var_export($event->data, true));
	include_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
	global $stripearrayofkeysbyenv;
	$error = 0;
	$object = $event->data->object;
	$TRANSACTIONID = $object->id;	// Example pi_123456789...
	$ipaddress = $object->metadata->ipaddress;
	$now = dol_now();
	$currencyCodeType = strtoupper($object->currency);
	$paymentmethodstripeid = $object->payment_method;
	$customer_id = $object->customer;
	$invoice_id = "";
	$paymentTypeCode = "";			// payment type according to Stripe
	$paymentTypeCodeInDolibarr = "";	// payment type according to Dolibarr
	$payment_amount = 0;
	$payment_amountInDolibarr = 0;

	dol_syslog("Try to find a payment in database for the payment_intent id = ".$TRANSACTIONID);

	$sql = "SELECT pi.rowid, pi.fk_facture, pi.fk_prelevement_bons, pi.amount, pi.type, pi.traite";
	$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_demande as pi";
	$sql .= " WHERE pi.ext_payment_id = '".$db->escape($TRANSACTIONID)."'";
	$sql .= " AND pi.ext_payment_site = '".$db->escape($service)."'";

	$result = $db->query($sql);
	if ($result) {
		$obj = $db->fetch_object($result);
		if ($obj) {
			if ($obj->type == 'ban') {
				if ($obj->traite == 1) {
					// This is a direct-debit with an order (llx_bon_prelevement) ALREADY generated, so
					// it means we received here the confirmation that payment request is finished.
					$pdid = $obj->rowid;
					$invoice_id = $obj->fk_facture;
					$directdebitorcreditransfer_id = $obj->fk_prelevement_bons;
					$payment_amountInDolibarr = $obj->amount;
					$paymentTypeCodeInDolibarr = $obj->type;

					dol_syslog("Found a request in database to pay with direct debit generated (pdid = ".$pdid." directdebitorcreditransfer_id=".$directdebitorcreditransfer_id.")");
				} else {
					dol_syslog("Found a request in database not yet generated (pdid = ".$pdid." directdebitorcreditransfer_id=".$directdebitorcreditransfer_id."). Was the order deleted after being sent ?", LOG_WARNING);
				}
			}
			if ($obj->type == 'card' || empty($obj->type)) {
				if ($obj->traite == 0) {
					// This is a card payment not already flagged as sent to Stripe.
					$pdid = $obj->rowid;
					$invoice_id = $obj->fk_facture;
					$payment_amountInDolibarr = $obj->amount;
					$paymentTypeCodeInDolibarr = empty($obj->type) ? 'card' : $obj->type;

					dol_syslog("Found a request in database to pay with card (pdid = ".$pdid."). We should fix status traite to 1");
				} else {
					dol_syslog("Found a request in database to pay with card (pdid = ".$pdid.") already set to traite=1. Nothing to fix.");
				}
			}
		} else {
			dol_syslog("Payment intent ".$TRANSACTIONID." not found into database, so ignored.");
			http_response_code(200);
			print "Payment intent ".$TRANSACTIONID." not found into database, so ignored.";
			return 1;
		}
	} else {
		http_response_code(500);
		print $db->lasterror();
		return -1;
	}

	if ($paymentTypeCodeInDolibarr) {
		// Here, we need to do something. A $invoice_id has been found.

		$stripeacc = $stripearrayofkeysbyenv[$servicestatus]['secret_key'];

		dol_syslog("Get the Stripe payment object for the payment method id = ".json_encode($paymentmethodstripeid));

		$s = new \Stripe\StripeClient($stripeacc);

		$paymentmethodstripe = $s->paymentMethods->retrieve($paymentmethodstripeid);
		$paymentTypeCode =  $paymentmethodstripe->type;
		if ($paymentTypeCode == "ban" || $paymentTypeCode == "sepa_debit") {
			$paymentTypeCode = "PRE";
		} elseif ($paymentTypeCode == "card") {
			$paymentTypeCode = "CB";
		}

		$payment_amount = $payment_amountInDolibarr;
		// TODO Check payment_amount in Stripe (received) is same than the one in Dolibarr

		$postactionmessages = array();

		if ($paymentTypeCode == "CB" && ($paymentTypeCodeInDolibarr == 'card' || empty($paymentTypeCodeInDolibarr))) {
			// Case payment type in Stripe and into prelevement_demande are both CARD.
			// For this case, payment should already have been recorded so we just update flag of payment request if not yet 1

			// TODO Set traite to 1
			dol_syslog("TODO update flag traite to 1");
		} elseif ($paymentTypeCode == "PRE" && $paymentTypeCodeInDolibarr == 'ban') {
			// Case payment type in Stripe and into prelevement_demande are both BAN.
			// For this case, payment on invoice (not yet recorded) must be done and direct debit order must be closed.

			$paiement = new Paiement($db);
			$paiement->datepaye = $now;
			$paiement->date = $now;
			if ($currencyCodeType == $conf->currency) {
				$paiement->amounts = [$invoice_id => $payment_amount];   // Array with all payments dispatching with invoice id
			} else {
				$paiement->multicurrency_amounts = [$invoice_id => $payment_amount];   // Array with all payments dispatching

				$postactionmessages[] = 'Payment was done in a currency ('.$currencyCodeType.') other than the expected currency of company ('.$conf->currency.')';
				$ispostactionok = -1;
				// Not yet supported, so error
				$error++;
			}

			// Get ID of payment PRE
			$paiement->paiementcode = $paymentTypeCode;
			$sql = "SELECT id FROM ".MAIN_DB_PREFIX."c_paiement";
			$sql .= " WHERE code = '".$db->escape($paymentTypeCode)."'";
			$sql .= " AND entity IN (".getEntity('c_paiement').")";
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				$paiement->paiementid = $obj->id;
			} else {
				$error++;
			}

			$paiement->num_payment = '';
			$paiement->note_public = '';
			$paiement->note_private = 'StripeSepa payment received by IPN webhook - ' . dol_print_date($now, 'standard') . ' (TZ server) using servicestatus=' . $servicestatus . ($ipaddress ? ' from ip ' . $ipaddress : '') . ' - Transaction ID = ' . $TRANSACTIONID;
			$paiement->ext_payment_id = $TRANSACTIONID.':'.$customer_id.'@'.$stripearrayofkeysbyenv[$servicestatus]['publishable_key'];		// May be we should store py_... instead of pi_... but we started with pi_... so we continue.
			$paiement->ext_payment_site = $service;

			$ispaymentdone = 0;
			$sql = "SELECT p.rowid FROM ".MAIN_DB_PREFIX."paiement as p";
			$sql .= " WHERE p.ext_payment_id = '".$db->escape($paiement->ext_payment_id)."'";
			$sql .= " AND p.ext_payment_site = '".$db->escape($paiement->ext_payment_site)."'";
			$result = $db->query($sql);
			if ($result) {
				if ($db->num_rows($result)) {
					$ispaymentdone = 1;
					dol_syslog('* Payment for ext_payment_id '.$paiement->ext_payment_id.' already done. We do not recreate the payment');
				}
			}

			$db->begin();

			if (!$error && !$ispaymentdone) {
				dol_syslog('* Record payment type PRE for invoice id ' . $invoice_id . '. It includes closing of invoice and regenerating document.');

				// This include closing invoices to 'paid' (and trigger including unsuspending) and regenerating document
				$paiement_id = $paiement->create($user, 1);
				if ($paiement_id < 0) {
					$postactionmessages[] = $paiement->error . ($paiement->error ? ' ' : '') . implode("<br>\n", $paiement->errors);
					$ispostactionok = -1;
					$error++;

					dol_syslog("Failed to create the payment for invoice id " . $invoice_id);
				} else {
					$postactionmessages[] = 'Payment created';

					dol_syslog("The payment has been created for invoice id " . $invoice_id);
				}
			}

			if (!$error && isModEnabled('bank')) {
				// Search again the payment to see if it is already linked to a bank payment record (We should always find the payment that was created before).
				$ispaymentdone = 0;
				$sql = "SELECT p.rowid, p.fk_bank FROM ".MAIN_DB_PREFIX."paiement as p";
				$sql .= " WHERE p.ext_payment_id = '".$db->escape($paiement->ext_payment_id)."'";
				$sql .= " AND p.ext_payment_site = '".$db->escape($paiement->ext_payment_site)."'";
				$sql .= " AND p.fk_bank <> 0";
				$result = $db->query($sql);
				if ($result) {
					if ($db->num_rows($result)) {
						$ispaymentdone = 1;
						$obj = $db->fetch_object($result);
						dol_syslog('* Payment already linked to bank record '.$obj->fk_bank.' . We do not recreate the link');
					}
				}
				if (!$ispaymentdone) {
					dol_syslog('* Add payment to bank');

					// The bank used is the one defined into Stripe setup
					$paymentmethod = 'stripe';
					$bankaccountid = getDolGlobalInt("STRIPE_BANK_ACCOUNT_FOR_PAYMENTS");

					if ($bankaccountid > 0) {
						$label = '(CustomerInvoicePayment)';
						$result = $paiement->addPaymentToBank($user, 'payment', $label, $bankaccountid, $customer_id, '');
						if ($result < 0) {
							$postactionmessages[] = $paiement->error . ($paiement->error ? ' ' : '') . implode("<br>\n", $paiement->errors);
							$ispostactionok = -1;
							$error++;
						} else {
							$postactionmessages[] = 'Bank transaction of payment created (by ipn.php file)';
						}
					} else {
						$postactionmessages[] = 'Setup of bank account to use in module ' . $paymentmethod . ' was not set. No way to record the payment.';
						$ispostactionok = -1;
						$error++;
					}
				}
			}

			if (!$error && isModEnabled('prelevement')) {
				$bon = new BonPrelevement($db);
				$idbon = 0;
				$sql = "SELECT dp.fk_prelevement_bons as idbon";
				$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_demande as dp";
				$sql .= " JOIN ".MAIN_DB_PREFIX."prelevement_bons as pb"; // Here we join to prevent modification of a prelevement bon already credited
				$sql .= " ON pb.rowid = dp.fk_prelevement_bons";
				$sql .= " WHERE dp.fk_facture = ".((int) $invoice_id);
				$sql .= " AND dp.sourcetype = 'facture'";
				$sql .= " AND dp.ext_payment_id = '".$db->escape($TRANSACTIONID)."'";
				$sql .= " AND dp.traite = 1";
				$sql .= " AND statut = ".((int) $bon::STATUS_TRANSFERED); // To be sure that it's not already credited
				$result = $db->query($sql);
				if ($result) {
					if ($db->num_rows($result)) {
						$obj = $db->fetch_object($result);
						$idbon = $obj->idbon;
						dol_syslog('* Prelevement must be set to credited');
					} else {
						dol_syslog('* Prelevement not found or already credited');
					}
				} else {
					$postactionmessages[] = $db->lasterror();
					$ispostactionok = -1;
					$error++;
				}

				if (!$error && !empty($idbon)) {
					$sql = "UPDATE ".MAIN_DB_PREFIX."prelevement_bons";
					$sql .= " SET fk_user_credit = ".((int) $user->id);
					$sql .= ", statut = ".((int) $bon::STATUS_CREDITED);
					$sql .= ", date_credit = '".$db->idate($now)."'";
					$sql .= ", credite = 1";
					$sql .= " WHERE rowid = ".((int) $idbon);
					$sql .= " AND statut = ".((int) $bon::STATUS_TRANSFERED);

					$result = $db->query($sql);
					if (!$result) {
						$postactionmessages[] = $db->lasterror();
						$ispostactionok = -1;
						$error++;
					}
				}

				if (!$error && !empty($idbon)) {
					$sql = "UPDATE ".MAIN_DB_PREFIX."prelevement_lignes";
					$sql .= " SET statut = 2";
					$sql .= " WHERE fk_prelevement_bons = ".((int) $idbon);
					$result = $db->query($sql);
					if (!$result) {
						$postactionmessages[] = $db->lasterror();
						$ispostactionok = -1;
						$error++;
					}
				}
			}

			if (!$error) {
				$db->commit();
				http_response_code(200);
				return 1;
			} else {
				$db->rollback();
				http_response_code(500);
				return -1;
			}
		} else {
			dol_syslog("The payment mode of this payment is ".$paymentTypeCode." in Stripe and ".$paymentTypeCodeInDolibarr." in Dolibarr. This case is not managed by the IPN");
		}
	} else {
		dol_syslog("Nothing to do in database because we don't know paymentTypeIdInDolibarr");
	}
} elseif ($event->type == 'payment_intent.payment_failed') {
	// When a try to take payment has failed. Useful for asynchronous SEPA payment that fails.
	dol_syslog("A try to make a payment has failed");

	$object = $event->data->object;
	$ipaddress = $object->metadata->ipaddress;
	$currencyCodeType = strtoupper($object->currency);
	$paymentmethodstripeid = $object->payment_method;
	$customer_id = $object->customer;

	$chargesdataarray = array();
	$objpayid = '';
	$objpaydesc = '';
	$objinvoiceid = 0;
	$objerrcode = '';
	$objerrmessage = '';
	$objpaymentmodetype = '';
	if (!empty($object->charges)) {				// Old format
		$chargesdataarray = $object->charges->data;
		foreach ($chargesdataarray as $chargesdata) {
			$objpayid = $chargesdata->id;
			$objpaydesc = $chargesdata->description;
			$objinvoiceid = 0;
			if ($chargesdata->metadata->dol_type == 'facture') {
				$objinvoiceid = $chargesdata->metadata->dol_id;
			}
			$objerrcode = $chargesdata->outcome->reason;
			$objerrmessage = $chargesdata->outcome->seller_message;

			$objpaymentmodetype = $chargesdata->payment_method_details->type;
			break;
		}
	}
	if (!empty($object->last_payment_error)) {	// New format 2023-10-16
		// $object is probably an object of type Stripe\PaymentIntent
		$objpayid = $object->latest_charge;
		$objpaydesc = $object->description;
		$objinvoiceid = 0;
		if ($object->metadata->dol_type == 'facture') {
			$objinvoiceid = $object->metadata->dol_id;
		}
		$objerrcode = empty($object->last_payment_error->code) ? $object->last_payment_error->decline_code : $object->last_payment_error->code;
		$objerrmessage = $object->last_payment_error->message;

		$objpaymentmodetype = $object->last_payment_error->payment_method->type;
	}

	dol_syslog("objpayid=".$objpayid." objpaymentmodetype=".$objpaymentmodetype." objerrcode=".$objerrcode);

	// If this is a differed payment for SEPA, add a line into agenda events
	if ($objpaymentmodetype == 'sepa_debit') {
		$db->begin();

		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		$actioncomm = new ActionComm($db);

		if ($objinvoiceid > 0) {
			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
			$invoice = new Facture($db);
			$invoice->fetch($objinvoiceid);

			$actioncomm->userownerid = 0;
			$actioncomm->percentage = -1;

			$actioncomm->type_code = 'AC_OTH_AUTO'; // Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
			$actioncomm->code = 'AC_IPN';

			$actioncomm->datep = $now;
			$actioncomm->datef = $now;

			$actioncomm->socid = $invoice->socid;
			$actioncomm->fk_project = $invoice->fk_project;
			$actioncomm->fk_element = $invoice->id;
			$actioncomm->elementtype = 'invoice';
			$actioncomm->ip = getUserRemoteIP();
		}

		$actioncomm->note_private = 'Error returned on payment id '.$objpayid.' after SEPA payment request '.$objpaydesc.'<br>Error code is: '.$objerrcode.'<br>Error message is: '.$objerrmessage;
		$actioncomm->label = 'Payment error (SEPA Stripe)';

		$result = $actioncomm->create($user);
		if ($result <= 0) {
			dol_syslog($actioncomm->error, LOG_ERR);
			$error++;
		}

		if (! $error) {
			$db->commit();
		} else {
			$db->rollback();
			http_response_code(500);
			return -1;
		}
	}
} elseif ($event->type == 'checkout.session.completed') {		// Called when making payment with new Checkout method ($conf->global->STRIPE_USE_NEW_CHECKOUT is on).
	// TODO: create fees
} elseif ($event->type == 'payment_method.attached') {
	// When we link a payment method with a customer on Stripe side
	require_once DOL_DOCUMENT_ROOT.'/societe/class/companypaymentmode.class.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societeaccount.class.php';
	$societeaccount = new SocieteAccount($db);

	$companypaymentmode = new CompanyPaymentMode($db);

	$idthirdparty = $societeaccount->getThirdPartyID($db->escape($event->data->object->customer), 'stripe', $servicestatus);
	if ($idthirdparty > 0) {
		// If the payment mode attached is to a stripe account owned by an external customer in societe_account (so a thirdparty that has a Stripe account),
		// we can create the payment mode
		$companypaymentmode->stripe_card_ref = $event->data->object->id;
		$companypaymentmode->fk_soc          = $idthirdparty;
		$companypaymentmode->bank            = null;
		$companypaymentmode->label           = '';
		$companypaymentmode->number          = $event->data->object->id;
		$companypaymentmode->last_four       = $event->data->object->card->last4;
		$companypaymentmode->card_type       = $event->data->object->card->branding;

		$companypaymentmode->owner_name      = $event->data->object->billing_details->name;
		$companypaymentmode->proprio         = $companypaymentmode->owner_name;			// We may still need this formodulebuilder because name of field is "proprio"

		$companypaymentmode->exp_date_month  = (int) $event->data->object->card->exp_month;
		$companypaymentmode->exp_date_year   = (int) $event->data->object->card->exp_year;
		$companypaymentmode->cvn             = null;
		$companypaymentmode->datec           = $event->data->object->created;
		$companypaymentmode->default_rib     = 0;
		$companypaymentmode->type            = $event->data->object->type;
		$companypaymentmode->country_code    = $event->data->object->card->country;
		$companypaymentmode->status          = $servicestatus;

		// TODO Check that a payment mode $companypaymentmode->stripe_card_ref does not exists yet to avoid to create duplicates
		// so we can remove the test on STRIPE_NO_DUPLICATE_CHECK
		if (getDolGlobalString('STRIPE_NO_DUPLICATE_CHECK')) {
			$db->begin();
			$result = $companypaymentmode->create($user);
			if ($result < 0) {
				$error++;
			}
			if (!$error) {
				$db->commit();
			} else {
				$db->rollback();
				http_response_code(500);
				return -1;
			}
		}
	}
} elseif ($event->type == 'payment_method.updated') {
	// When we update a payment method on Stripe side
	require_once DOL_DOCUMENT_ROOT.'/societe/class/companypaymentmode.class.php';
	$companypaymentmode = new CompanyPaymentMode($db);
	$companypaymentmode->fetch(0, '', 0, '', " AND stripe_card_ref = '".$db->escape($event->data->object->id)."'");
	if ($companypaymentmode->id > 0) {
		// If we found a payment mode with the ID
		$companypaymentmode->bank            = null;
		$companypaymentmode->label           = '';
		$companypaymentmode->number          = $db->escape($event->data->object->id);
		$companypaymentmode->last_four       = $db->escape($event->data->object->card->last4);
		$companypaymentmode->proprio         = $db->escape($event->data->object->billing_details->name);
		$companypaymentmode->exp_date_month  = (int) $event->data->object->card->exp_month;
		$companypaymentmode->exp_date_year   = (int) $event->data->object->card->exp_year;
		$companypaymentmode->cvn             = null;
		$companypaymentmode->datec           = (int) $event->data->object->created;
		$companypaymentmode->default_rib     = 0;
		$companypaymentmode->type            = $db->escape($event->data->object->type);
		$companypaymentmode->country_code    = $db->escape($event->data->object->card->country);
		$companypaymentmode->status          = $servicestatus;

		$db->begin();
		if (!$error) {
			$result = $companypaymentmode->update($user);
			if ($result < 0) {
				$error++;
			}
		}
		if (!$error) {
			$db->commit();
		} else {
			$db->rollback();
		}
	}
} elseif ($event->type == 'payment_method.detached') {
	// When we remove a payment method on Stripe side
	$db->begin();
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."societe_rib WHERE number = '".$db->escape($event->data->object->id)."' and status = ".((int) $servicestatus);
	$db->query($sql);
	$db->commit();
} elseif ($event->type == 'charge.succeeded') {
	// Deprecated. TODO: create fees and redirect to paymentok.php
} elseif ($event->type == 'charge.failed') {
	// Deprecated. TODO: Redirect to paymentko.php
} elseif (($event->type == 'source.chargeable') && ($event->data->object->type == 'three_d_secure') && ($event->data->object->three_d_secure->authenticated == true)) {
	// Deprecated.
} elseif ($event->type == 'charge.dispute.closed') {
	// When a dispute to cancel a SEPA payment is finished
	dol_syslog("object = ".var_export($event->data, true));
} elseif ($event->type == 'charge.dispute.funds_withdrawn') {
	// When a dispute/withdraw to cancel a SEPA payment is done
	dol_syslog("object = ".var_export($event->data, true));

	global $stripearrayofkeysbyenv;
	$error = 0;
	$errormsg = '';
	$object = $event->data->object;
	$TRANSACTIONID = $object->payment_intent;
	$ipaddress = $object->metadata->ipaddress;
	$now = dol_now();
	$currencyCodeType = strtoupper($object->currency);
	$paymentmethodstripeid = $object->payment_method;
	$customer_id = $object->customer;
	$reason = $object->reason;
	$amountdisputestripe = $object->amoutndispute;	// In stripe format
	$amountdispute = $amountdisputestripe;			// In real currency format

	$invoice_id = "";
	$paymentTypeCode = "";			// payment type according to Stripe
	$paymentTypeCodeInDolibarr = "";	// payment type according to Dolibarr
	$payment_amount = 0;
	$payment_amountInDolibarr = 0;

	dol_syslog("Try to find the payment in database for the payment_intent id = ".$TRANSACTIONID);

	$sql = "SELECT pi.rowid, pi.fk_facture, pi.fk_prelevement_bons, pi.amount, pi.type, pi.traite";
	$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_demande as pi";
	$sql .= " WHERE pi.ext_payment_id = '".$db->escape($TRANSACTIONID)."'";
	$sql .= " AND pi.ext_payment_site = '".$db->escape($service)."'";

	$result = $db->query($sql);
	if ($result) {
		$obj = $db->fetch_object($result);
		if ($obj) {
			if ($obj->type == 'ban') {
				// This is a direct-debit with an order (llx_bon_prelevement).
				$pdid = $obj->rowid;
				$invoice_id = $obj->fk_facture;
				$directdebitorcreditransfer_id = $obj->fk_prelevement_bons;
				$payment_amountInDolibarr = $obj->amount;
				$paymentTypeCodeInDolibarr = $obj->type;

				dol_syslog("Found the payment intent for ban in database (pdid = ".$pdid." directdebitorcreditransfer_id=".$directdebitorcreditransfer_id.")");
			}
			if ($obj->type == 'card' || empty($obj->type)) {
				// This is a card payment.
				$pdid = $obj->rowid;
				$invoice_id = $obj->fk_facture;
				$payment_amountInDolibarr = $obj->amount;
				$paymentTypeCodeInDolibarr = empty($obj->type) ? 'card' : $obj->type;

				dol_syslog("Found the payment intent for card in database (pdid = ".$pdid." directdebitorcreditransfer_id=".$directdebitorcreditransfer_id.")");
			}
		} else {
			dol_syslog("Payment intent ".$TRANSACTIONID." not found into database, so ignored.");
			http_response_code(200);
			print "Payment intent ".$TRANSACTIONID." not found into database, so ignored.";
			return 1;
		}
	} else {
		http_response_code(500);
		print $db->lasterror();
		return -1;
	}

	dol_syslog("objinvoiceid=".$invoice_id);
	$tmpinvoice = new Facture($db);
	$tmpinvoice->fetch($invoice_id);
	$tmpinvoice->fetch_thirdparty();

	dol_syslog("The payment disputed is ".$amountdispute." and the invoice is ".$payment_amountInDolibarr);

	if ($amountdispute != $payment_amountInDolibarr) {
		http_response_code(500);
		print "The payment disputed is ".$amountdispute." and the invoice is ".$payment_amountInDolibarr.". Amount differs, we don't know what to do.";
		return -1;
	}

	$accountfrom = new Account($db);
	$accountfrom->fetch(getDolGlobalInt('STRIPE_BANK_ACCOUNT_FOR_PAYMENTS'));

	// Now we add a negative payment
	$paiement = new Paiement($db);

	$amounts = array();
	$amounts[$tmpinvoice->id] = -1 * $payment_amountInDolibarr;

	$paiement->datepaye = dol_now();
	$paiement->amounts = $amounts; // Array with all payments dispatching with invoice id
	/*$paiement->multicurrency_amounts = $multicurrency_amounts; // Array with all payments dispatching
	$paiement->multicurrency_code = $multicurrency_code; // Array with all currency of payments dispatching
	$paiement->multicurrency_tx = $multicurrency_tx; // Array with all currency tx of payments dispatching
	*/
	$paiement->paiementid   = dol_getIdFromCode($db, 'PRE', 'c_paiement', 'code', 'id', 1);
	$paiement->num_payment  = $object->id;	// A string like 'du_...'
	$paiement->note_public = 'Fund withdrawn by bank. Reason: '.$reason;
	$paiement->note_private = '';
	$paiement->fk_account   = $accountfrom->id;

	$db->begin();

	$alreadytransferedinaccounting = $tmpinvoice->getVentilExportCompta();

	if ($alreadytransferedinaccounting) {
		// TODO Test if invoice already in accountancy.
		// What to do ?
		$errormsg = 'Error: the invoice '.$tmpinvoice->id.' is already transferred into accounting. Don\'t know what to do.';
		$error++;
	}

	if (! $error && $tmpinvoice->status == Facture::STATUS_CLOSED) {
		// Switch back the invoice to status validated
		$result = $tmpinvoice->setStatut(Facture::STATUS_VALIDATED);
		if ($result < 0) {
			$errormsg = $tmpinvoice->error.implode(', ', $tmpinvoice->errors);
			$error++;
		}
	}

	if (! $error) {
		$paiement_id = $paiement->create($user, 0, $tmpinvoice->thirdparty); // This include regenerating documents
		if ($paiement_id < 0) {
			$errormsg = $paiement->error.implode(', ', $paiement->errors);
			$error++;
		}
	}

	if (!$error) {
		//$db->commit();	// Code not yet enough tested
		$db->rollback();
		http_response_code(500);
		return -1;
	} else {
		$db->rollback();
		http_response_code(500);
		print $errormsg;
		return -1;
	}
}


// End of page. Default return HTTP code will be 200
