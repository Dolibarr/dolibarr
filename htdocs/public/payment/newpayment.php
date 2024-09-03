<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2018	    Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2018-2021	Thibault FOUCART	    <support@ptibogxiv.net>
 * Copyright (C) 2021		Waël Almoman	    	<info@almoman.com>
 * Copyright (C) 2021		Dorian Vabre			<dorian.vabre@gmail.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *
 * For Paypal test: https://developer.paypal.com/
 * For Paybox test: ???
 * For Stripe test: Use credit card 4242424242424242 .More example on https://stripe.com/docs/testing
 *
 * Variants:
 * - When option STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION is on, we use the new PaymentIntent API
 * - When option STRIPE_USE_NEW_CHECKOUT is on, we use the new checkout API
 * - If no option set, we use old APIS (charge)
 */

/**
 *     	\file       htdocs/public/payment/newpayment.php
 *		\ingroup    core
 *		\brief      File to offer a way to make a payment for a particular Dolibarr object
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

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and get of entity must be done before including main.inc.php
// Because 2 entities can have the same ref.
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : (!empty($_GET['e']) ? (int) $_GET['e'] : (!empty($_POST['e']) ? (int) $_POST['e'] : 1))));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/eventorganization/class/conferenceorboothattendee.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societeaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

// Hook to be used by external payment modules (ie Payzen, ...)
$hookmanager = new HookManager($db);
$hookmanager->initHooks(array('newpayment'));

// Load translation files
$langs->loadLangs(array("main", "other", "dict", "bills", "companies", "errors", "paybox", "paypal", "stripe")); // File with generic data

// Security check
// No check on module enabled. Done later according to $validpaymentmethod

$action = GETPOST('action', 'aZ09');

// Input are:
// type ('invoice','order','contractline'),
// id (object id),
// amount (required if id is empty),
// tag (a free text, required if type is empty)
// currency (iso code)

$suffix = GETPOST("suffix", 'aZ09');
$amount = price2num(GETPOST("amount", 'alpha'));
if (!GETPOST("currency", 'alpha')) {
	$currency = $conf->currency;
} else {
	$currency = GETPOST("currency", 'aZ09');
}
$source = GETPOST("s", 'aZ09') ? GETPOST("s", 'aZ09') : GETPOST("source", 'aZ09');
$getpostlang = GETPOST('lang', 'aZ09');
$ws = GETPOSTINT("ws"); // Website reference where the newpayment page is embedded

if (!$action) {
	if (!GETPOST("amount", 'alpha') && !$source) {
		print $langs->trans('ErrorBadParameters')." - amount or source";
		exit;
	}
	if (is_numeric($amount) && !GETPOST("tag", 'alpha') && !$source) {
		print $langs->trans('ErrorBadParameters')." - tag or source";
		exit;
	}
	if ($source && !GETPOST("ref", 'alpha')) {
		print $langs->trans('ErrorBadParameters')." - ref";
		exit;
	}
}

if ($source == 'organizedeventregistration') {
	// Finding the Attendee
	$attendee = new ConferenceOrBoothAttendee($db);

	$invoiceid = GETPOSTINT('ref');
	$invoice = new Facture($db);

	$resultinvoice = $invoice->fetch($invoiceid);

	if ($resultinvoice <= 0) {
		setEventMessages(null, $invoice->errors, "errors");
	} else {
		/*
		$attendeeid = 0;

		$invoice->fetchObjectLinked();
		$linkedAttendees = $invoice->linkedObjectsIds['conferenceorboothattendee'];

		if (is_array($linkedAttendees)) {
			$linkedAttendees = array_values($linkedAttendees);
			$attendeeid = $linkedAttendees[0];
		}*/
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."eventorganization_conferenceorboothattendee";
		$sql .= " WHERE fk_invoice = ".((int) $invoiceid);
		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$attendeeid = $obj->rowid;
			}
		}

		if ($attendeeid > 0) {
			$resultattendee = $attendee->fetch($attendeeid);

			if ($resultattendee <= 0) {
				setEventMessages(null, $attendee->errors, "errors");
			} else {
				$attendee->fetch_projet();

				$amount = price2num($invoice->total_ttc);
				// Finding the associated thirdparty
				$thirdparty = new Societe($db);
				$resultthirdparty = $thirdparty->fetch($invoice->socid);
				if ($resultthirdparty <= 0) {
					setEventMessages(null, $thirdparty->errors, "errors");
				}
				$object = $thirdparty;
			}
		}
	}
} elseif ($source == 'boothlocation') {
	// Getting the amount to pay, the invoice, finding the thirdparty
	$invoiceid = GETPOST('ref');
	$invoice = new Facture($db);
	$resultinvoice = $invoice->fetch($invoiceid);
	if ($resultinvoice <= 0) {
		setEventMessages(null, $invoice->errors, "errors");
	} else {
		$amount = price2num($invoice->total_ttc);
		// Finding the associated thirdparty
		$thirdparty = new Societe($db);
		$resultthirdparty = $thirdparty->fetch($invoice->socid);
		if ($resultthirdparty <= 0) {
			setEventMessages(null, $thirdparty->errors, "errors");
		}
		$object = $thirdparty;
	}
}


$paymentmethod = GETPOST('paymentmethod', 'alphanohtml') ? GETPOST('paymentmethod', 'alphanohtml') : ''; // Empty in most cases. Defined when a payment mode is forced
$validpaymentmethod = array();

// Detect $paymentmethod
foreach ($_POST as $key => $val) {
	$reg = array();
	if (preg_match('/^dopayment_(.*)$/', $key, $reg)) {
		$paymentmethod = $reg[1];
		break;
	}
}


// Define $urlwithroot
//$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
//$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
$urlwithroot = DOL_MAIN_URL_ROOT; // This is to use same domain name than current. For Paypal payment, we can use internal URL like localhost.

$urlok = $urlwithroot.'/public/payment/paymentok.php?';
$urlko = $urlwithroot.'/public/payment/paymentko.php?';

// Complete urls for post treatment
$ref = $REF = GETPOST('ref', 'alpha');
$TAG = GETPOST("tag", 'alpha');
$FULLTAG = GETPOST("fulltag", 'alpha'); // fulltag is tag with more information
$SECUREKEY = GETPOST("securekey"); // Secure key

if ($paymentmethod && !preg_match('/'.preg_quote('PM='.$paymentmethod, '/').'/', $FULLTAG)) {
	$FULLTAG .= ($FULLTAG ? '.' : '').'PM='.$paymentmethod;
}

if ($ws) {
	$FULLTAG .= ($FULLTAG ? '.' : '').'WS='.$ws;
}

if (!empty($suffix)) {
	$urlok .= 'suffix='.urlencode($suffix).'&';
	$urlko .= 'suffix='.urlencode($suffix).'&';
}
if ($source) {
	$urlok .= 's='.urlencode($source).'&';
	$urlko .= 's='.urlencode($source).'&';
}
if (!empty($REF)) {
	$urlok .= 'ref='.urlencode($REF).'&';
	$urlko .= 'ref='.urlencode($REF).'&';
}
if (!empty($TAG)) {
	$urlok .= 'tag='.urlencode($TAG).'&';
	$urlko .= 'tag='.urlencode($TAG).'&';
}
if (!empty($FULLTAG)) {
	$urlok .= 'fulltag='.urlencode($FULLTAG).'&';
	$urlko .= 'fulltag='.urlencode($FULLTAG).'&';
}
if (!empty($SECUREKEY)) {
	$urlok .= 'securekey='.urlencode($SECUREKEY).'&';
	$urlko .= 'securekey='.urlencode($SECUREKEY).'&';
}
if (!empty($entity)) {
	$urlok .= 'e='.urlencode((string) ($entity)).'&';
	$urlko .= 'e='.urlencode((string) ($entity)).'&';
}
if (!empty($getpostlang)) {
	$urlok .= 'lang='.urlencode($getpostlang).'&';
	$urlko .= 'lang='.urlencode($getpostlang).'&';
}
$urlok = preg_replace('/&$/', '', $urlok); // Remove last &
$urlko = preg_replace('/&$/', '', $urlko); // Remove last &


// Make special controls

if ((empty($paymentmethod) || $paymentmethod == 'paypal') && isModEnabled('paypal')) {
	require_once DOL_DOCUMENT_ROOT.'/paypal/lib/paypal.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/paypal/lib/paypalfunctions.lib.php';

	// Check parameters
	$PAYPAL_API_OK = "";
	if ($urlok) {
		$PAYPAL_API_OK = $urlok;
	}
	$PAYPAL_API_KO = "";
	if ($urlko) {
		$PAYPAL_API_KO = $urlko;
	}
	if (empty($PAYPAL_API_USER)) {
		print 'Paypal parameter PAYPAL_API_USER is not defined. Please <a href="'.DOL_URL_ROOT.'/paypal/admin/paypal.php">complete the setup of module PayPal first</a>.';
		exit;
	}
	if (empty($PAYPAL_API_PASSWORD)) {
		print 'Paypal parameter PAYPAL_API_PASSWORD is not defined. Please <a href="'.DOL_URL_ROOT.'/paypal/admin/paypal.php">complete the setup of module PayPal first</a>.';
		exit;
	}
	if (empty($PAYPAL_API_SIGNATURE)) {
		print 'Paypal parameter PAYPAL_API_SIGNATURE is not defined. Please <a href="'.DOL_URL_ROOT.'/paypal/admin/paypal.php">complete the setup of module PayPal first</a>.';
		exit;
	}
}
if ((empty($paymentmethod) || $paymentmethod == 'paybox') && isModEnabled('paybox')) {
	// No specific test for the moment
}
if ((empty($paymentmethod) || $paymentmethod == 'stripe') && isModEnabled('stripe')) {
	require_once DOL_DOCUMENT_ROOT.'/stripe/config.php'; // This include also /stripe/lib/stripe.lib.php, /includes/stripe/stripe-php/init.php, ...
}

// Initialize $validpaymentmethod
// The list can be complete by the hook 'doValidatePayment' executed inside getValidOnlinePaymentMethods()
$validpaymentmethod = getValidOnlinePaymentMethods($paymentmethod);

// Check security token
$tmpsource = $source;
if ($tmpsource == 'membersubscription') {
	$tmpsource = 'member';
}
$valid = true;
if (getDolGlobalString('PAYMENT_SECURITY_TOKEN')) {
	$tokenisok = false;
	if (getDolGlobalString('PAYMENT_SECURITY_TOKEN_UNIQUE')) {
		if ($tmpsource && $REF) {
			// Use the source in the hash to avoid duplicates if the references are identical
			$tokenisok = dol_verifyHash(getDolGlobalString('PAYMENT_SECURITY_TOKEN') . $tmpsource.$REF, $SECUREKEY, '2');
			// Do a second test for retro-compatibility (token may have been hashed with membersubscription in external module)
			if ($tmpsource != $source) {
				$tokenisok = dol_verifyHash(getDolGlobalString('PAYMENT_SECURITY_TOKEN') . $source.$REF, $SECUREKEY, '2');
			}
		} else {
			$tokenisok = dol_verifyHash($conf->global->PAYMENT_SECURITY_TOKEN, $SECUREKEY, '2');
		}
	} else {
		$tokenisok = ($conf->global->PAYMENT_SECURITY_TOKEN == $SECUREKEY);
	}

	if (! $tokenisok) {
		if (!getDolGlobalString('PAYMENT_SECURITY_ACCEPT_ANY_TOKEN')) {
			$valid = false; // PAYMENT_SECURITY_ACCEPT_ANY_TOKEN is for backward compatibility
		} else {
			dol_syslog("Warning: PAYMENT_SECURITY_ACCEPT_ANY_TOKEN is on", LOG_WARNING);
		}
	}

	if (!$valid) {
		print '<div class="error">Bad value for key.</div>';
		//print 'SECUREKEY='.$SECUREKEY.' valid='.$valid;
		exit;
	}
}

if (!empty($paymentmethod) && empty($validpaymentmethod[$paymentmethod])) {
	print 'Payment module for payment method '.$paymentmethod.' is not active';
	exit;
}
if (empty($validpaymentmethod)) {
	print 'No active payment module (Paypal, Stripe, Paybox, ...)';
	exit;
}

// Common variables
$creditor = $mysoc->name;
$paramcreditor = 'ONLINE_PAYMENT_CREDITOR';
$paramcreditorlong = 'ONLINE_PAYMENT_CREDITOR_'.$suffix;
if (getDolGlobalString($paramcreditorlong)) {
	$creditor = getDolGlobalString($paramcreditorlong);	// use label long of the seller to show
} elseif (getDolGlobalString($paramcreditor)) {
	$creditor = getDolGlobalString($paramcreditor);		// use label short of the seller to show
}

$mesg = '';


/*
 * Actions
 */

// Action dopayment is called after clicking/choosing the payment mode
if ($action == 'dopayment') {
	dol_syslog("--- newpayment.php Execute action = ".$action." paymentmethod=".$paymentmethod.' amount='.$amount.' newamount='.GETPOST("newamount", 'alpha'), LOG_DEBUG, 0, '_payment');

	if ($paymentmethod == 'paypal') {
		$PAYPAL_API_PRICE = price2num(GETPOST("newamount", 'alpha'), 'MT');
		$PAYPAL_PAYMENT_TYPE = 'Sale';

		// Vars that are used as global var later in print_paypal_redirect()
		$origfulltag = GETPOST("fulltag", 'alpha');
		$shipToName = GETPOST("shipToName", 'alpha');
		$shipToStreet = GETPOST("shipToStreet", 'alpha');
		$shipToCity = GETPOST("shipToCity", 'alpha');
		$shipToState = GETPOST("shipToState", 'alpha');
		$shipToCountryCode = GETPOST("shipToCountryCode", 'alpha');
		$shipToZip = GETPOST("shipToZip", 'alpha');
		$shipToStreet2 = GETPOST("shipToStreet2", 'alpha');
		$phoneNum = GETPOST("phoneNum", 'alpha');
		$email = GETPOST("email", 'alpha');
		$desc = GETPOST("desc", 'alpha');
		$thirdparty_id = GETPOSTINT('thirdparty_id');

		// Special case for Paypal-Indonesia
		if ($shipToCountryCode == 'ID' && !preg_match('/\-/', $shipToState)) {
			$shipToState = 'ID-'.$shipToState;
		}

		if (empty($PAYPAL_API_PRICE) || !is_numeric($PAYPAL_API_PRICE)) {
			$mesg = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Amount"));
			$action = '';
			// } elseif (empty($EMAIL)) { $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("YourEMail"));
			// } elseif (! isValidEmail($EMAIL)) { $mesg=$langs->trans("ErrorBadEMail",$EMAIL);
		} elseif (!$origfulltag) {
			$mesg = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("PaymentCode"));
			$action = '';
		}

		if (empty($mesg)) {
			dol_syslog("newpayment.php call paypal api and do redirect", LOG_DEBUG);

			// Other
			$PAYPAL_API_DEVISE = "USD";
			if (!empty($currency)) {
				$PAYPAL_API_DEVISE = $currency;
			}

			// Show var initialized by inclusion of paypal lib at start of this file
			dol_syslog("Submit Paypal form", LOG_DEBUG);
			dol_syslog("PAYPAL_API_USER: $PAYPAL_API_USER", LOG_DEBUG);
			dol_syslog("PAYPAL_API_PASSWORD: ".preg_replace('/./', '*', $PAYPAL_API_PASSWORD), LOG_DEBUG); // No password into log files
			dol_syslog("PAYPAL_API_SIGNATURE: $PAYPAL_API_SIGNATURE", LOG_DEBUG);
			dol_syslog("PAYPAL_API_SANDBOX: $PAYPAL_API_SANDBOX", LOG_DEBUG);
			dol_syslog("PAYPAL_API_OK: $PAYPAL_API_OK", LOG_DEBUG);
			dol_syslog("PAYPAL_API_KO: $PAYPAL_API_KO", LOG_DEBUG);
			dol_syslog("PAYPAL_API_PRICE: $PAYPAL_API_PRICE", LOG_DEBUG);
			dol_syslog("PAYPAL_API_DEVISE: $PAYPAL_API_DEVISE", LOG_DEBUG);
			// All those fields may be empty when making a payment for a free amount for example
			dol_syslog("shipToName: $shipToName", LOG_DEBUG);
			dol_syslog("shipToStreet: $shipToStreet", LOG_DEBUG);
			dol_syslog("shipToCity: $shipToCity", LOG_DEBUG);
			dol_syslog("shipToState: $shipToState", LOG_DEBUG);
			dol_syslog("shipToCountryCode: $shipToCountryCode", LOG_DEBUG);
			dol_syslog("shipToZip: $shipToZip", LOG_DEBUG);
			dol_syslog("shipToStreet2: $shipToStreet2", LOG_DEBUG);
			dol_syslog("phoneNum: $phoneNum", LOG_DEBUG);
			dol_syslog("email: $email", LOG_DEBUG);
			dol_syslog("desc: $desc", LOG_DEBUG);

			dol_syslog("SCRIPT_URI: ".(empty($_SERVER["SCRIPT_URI"]) ? '' : $_SERVER["SCRIPT_URI"]), LOG_DEBUG); // If defined script uri must match domain of PAYPAL_API_OK and PAYPAL_API_KO

			// A redirect is added if API call successful
			$mesg = print_paypal_redirect($PAYPAL_API_PRICE, $PAYPAL_API_DEVISE, $PAYPAL_PAYMENT_TYPE, $PAYPAL_API_OK, $PAYPAL_API_KO, $FULLTAG);

			// If we are here, it means the Paypal redirect was not done, so we show error message
			$action = '';
		}
	}

	if ($paymentmethod == 'paybox') {
		$PRICE = price2num(GETPOST("newamount"), 'MT');
		$email = getDolGlobalString('ONLINE_PAYMENT_SENDEMAIL');
		$thirdparty_id = GETPOSTINT('thirdparty_id');

		$origfulltag = GETPOST("fulltag", 'alpha');

		// Securekey into back url useless for back url and we need an url lower than 150.
		$urlok = preg_replace('/securekey=[^&]+&?/', '', $urlok);
		$urlko = preg_replace('/securekey=[^&]+&?/', '', $urlko);

		if (empty($PRICE) || !is_numeric($PRICE)) {
			$mesg = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Amount"));
		} elseif (empty($email)) {
			$mesg = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ONLINE_PAYMENT_SENDEMAIL"));
		} elseif (!isValidEmail($email)) {
			$mesg = $langs->trans("ErrorBadEMail", $email);
		} elseif (!$origfulltag) {
			$mesg = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("PaymentCode"));
		} elseif (dol_strlen($urlok) > 150) {
			$mesg = 'Error urlok too long '.$urlok.' (Paybox requires 150, found '.strlen($urlok).')';
		} elseif (dol_strlen($urlko) > 150) {
			$mesg = 'Error urlko too long '.$urlko.' (Paybox requires 150, found '.strlen($urlok).')';
		}

		if (empty($mesg)) {
			dol_syslog("newpayment.php call paybox api and do redirect", LOG_DEBUG);

			include_once DOL_DOCUMENT_ROOT.'/paybox/lib/paybox.lib.php';
			print_paybox_redirect((float) $PRICE, $conf->currency, $email, $urlok, $urlko, $FULLTAG);

			session_destroy();
			exit;
		}
	}

	if ($paymentmethod == 'stripe') {
		if (GETPOST('newamount', 'alpha')) {
			$amount = price2num(GETPOST('newamount', 'alpha'), 'MT');
		} else {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Amount")), null, 'errors');
			$action = '';
		}
	}
}


// Called when choosing Stripe mode.
// When using the old Charge API architecture, this code is called after clicking the 'dopayment' with the Charge API architecture.
// When using the PaymentIntent API architecture, the Stripe customer was already created when creating PaymentIntent when showing payment page, and the payment is already ok when action=charge.
if ($action == 'charge' && isModEnabled('stripe')) {
	$amountstripe = (float) $amount;

	// Correct the amount according to unit of currency
	// See https://support.stripe.com/questions/which-zero-decimal-currencies-does-stripe-support
	$arrayzerounitcurrency = array('BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'VND', 'VUV', 'XAF', 'XOF', 'XPF');
	if (!in_array($currency, $arrayzerounitcurrency)) {
		$amountstripe *= 100;
	}

	dol_syslog("--- newpayment.php Execute action = ".$action." STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION=".getDolGlobalInt('STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION'), LOG_DEBUG, 0, '_payment');
	dol_syslog("GET=".var_export($_GET, true), LOG_DEBUG, 0, '_payment');
	dol_syslog("POST=".var_export($_POST, true), LOG_DEBUG, 0, '_payment');

	$stripeToken = GETPOST("stripeToken", 'alpha');
	$email = GETPOST("email", 'alpha');
	$thirdparty_id = GETPOSTINT('thirdparty_id'); // Note that for payment following online registration for members, this is empty because thirdparty is created once payment is confirmed by paymentok.php
	$dol_type = (GETPOST('s', 'alpha') ? GETPOST('s', 'alpha') : GETPOST('source', 'alpha'));
	$dol_id = GETPOSTINT('dol_id');
	$vatnumber = GETPOST('vatnumber', 'alpha');
	$savesource = GETPOSTISSET('savesource') ? GETPOSTINT('savesource') : 1;

	dol_syslog("POST stripeToken = ".$stripeToken, LOG_DEBUG, 0, '_payment');
	dol_syslog("POST email = ".$email, LOG_DEBUG, 0, '_payment');
	dol_syslog("POST thirdparty_id = ".$thirdparty_id, LOG_DEBUG, 0, '_payment');
	dol_syslog("POST vatnumber = ".$vatnumber, LOG_DEBUG, 0, '_payment');

	$error = 0;
	$errormessage = '';

	// When using the old Charge API architecture
	if (!getDolGlobalInt('STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION')) {
		try {
			$metadata = array(
				'dol_version' => DOL_VERSION,
				'dol_entity'  => $conf->entity,
				'dol_company' => $mysoc->name, // Useful when using multicompany
				'dol_tax_num' => $vatnumber,
				'ipaddress' => getUserRemoteIP()
			);

			if (!empty($thirdparty_id)) {
				$metadata["dol_thirdparty_id"] = $thirdparty_id;
			}

			if ($thirdparty_id > 0) {
				dol_syslog("Search existing Stripe customer profile for thirdparty_id=".$thirdparty_id, LOG_DEBUG, 0, '_payment');

				$service = 'StripeTest';
				$servicestatus = 0;
				if (getDolGlobalString('STRIPE_LIVE') && !GETPOSTINT('forcesandbox')) {
					$service = 'StripeLive';
					$servicestatus = 1;
				}

				$thirdparty = new Societe($db);
				$thirdparty->fetch($thirdparty_id);

				// Create Stripe customer
				include_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';
				$stripe = new Stripe($db);
				$stripeacc = $stripe->getStripeAccount($service);
				$customer = $stripe->customerStripe($thirdparty, $stripeacc, $servicestatus, 1);
				if (empty($customer)) {
					$error++;
					dol_syslog('Failed to get/create stripe customer for thirdparty id = '.$thirdparty_id.' and servicestatus = '.$servicestatus.': '.$stripe->error, LOG_ERR, 0, '_payment');
					setEventMessages('Failed to get/create stripe customer for thirdparty id = '.$thirdparty_id.' and servicestatus = '.$servicestatus.': '.$stripe->error, null, 'errors');
					$action = '';
				}

				// Create Stripe card from Token
				if (!$error) {
					if ($savesource) {
						$card = $customer->sources->create(array("source" => $stripeToken, "metadata" => $metadata));
					} else {
						$card = $stripeToken;
					}

					if (empty($card)) {
						$error++;
						dol_syslog('Failed to create card record', LOG_WARNING, 0, '_payment');
						setEventMessages('Failed to create card record', null, 'errors');
						$action = '';
					} else {
						if (!empty($FULLTAG)) {
							$metadata["FULLTAG"] = $FULLTAG;
						}
						if (!empty($dol_id)) {
							$metadata["dol_id"] = $dol_id;
						}
						if (!empty($dol_type)) {
							$metadata["dol_type"] = $dol_type;
						}

						dol_syslog("Create charge on card ".$card->id, LOG_DEBUG, 0, '_payment');
						$charge = \Stripe\Charge::create(array(
							'amount'   => price2num($amountstripe, 'MU'),
							'currency' => $currency,
							'capture'  => true, // Charge immediately
							'description' => 'Stripe payment: '.$FULLTAG.' ref='.$ref,
							'metadata' => $metadata,
							'customer' => $customer->id,
							'source' => $card,
						  'statement_descriptor_suffix' => dol_trunc($FULLTAG, 10, 'right', 'UTF-8', 1), // 22 chars that appears on bank receipt (company + description)
						), array("idempotency_key" => "$FULLTAG", "stripe_account" => "$stripeacc"));
						// Return $charge = array('id'=>'ch_XXXX', 'status'=>'succeeded|pending|failed', 'failure_code'=>, 'failure_message'=>...)
						if (empty($charge)) {
							$error++;
							dol_syslog('Failed to charge card', LOG_WARNING, 0, '_payment');
							setEventMessages('Failed to charge card', null, 'errors');
							$action = '';
						}
					}
				}
			} else {
				$vatcleaned = $vatnumber ? $vatnumber : null;

				/*$taxinfo = array('type'=>'vat');
				if ($vatcleaned)
				{
					$taxinfo["tax_id"] = $vatcleaned;
				}
				// We force data to "null" if not defined as expected by Stripe
				if (empty($vatcleaned)) $taxinfo=null;
				*/

				dol_syslog("Create anonymous customer card profile", LOG_DEBUG, 0, '_payment');

				$customer = \Stripe\Customer::create(array(
					'email' => $email,
					'description' => ($email ? 'Anonymous customer for '.$email : 'Anonymous customer'),
					'metadata' => $metadata,
					'source'  => $stripeToken           // source can be a token OR array('object'=>'card', 'exp_month'=>xx, 'exp_year'=>xxxx, 'number'=>xxxxxxx, 'cvc'=>xxx, 'name'=>'Cardholder's full name', zip ?)
				));
				// Return $customer = array('id'=>'cus_XXXX', ...)

				// Create the VAT record in Stripe
				/* We don't know country of customer, so we can't create tax
				if (!empty($conf->global->STRIPE_SAVE_TAX_IDS))	// We setup to save Tax info on Stripe side. Warning: This may result in error when saving customer
				{
					if (!empty($vatcleaned))
					{
						$isineec=isInEEC($object);
						if ($object->country_code && $isineec)
						{
							//$taxids = $customer->allTaxIds($customer->id);
							$customer->createTaxId($customer->id, array('type'=>'eu_vat', 'value'=>$vatcleaned));
						}
					}
				}*/

				if (!empty($FULLTAG)) {
					$metadata["FULLTAG"] = $FULLTAG;
				}
				if (!empty($dol_id)) {
					$metadata["dol_id"] = $dol_id;
				}
				if (!empty($dol_type)) {
					$metadata["dol_type"] = $dol_type;
				}

				// The customer was just created with a source, so we can make a charge
				// with no card defined, the source just used for customer creation will be used.
				dol_syslog("Create charge", LOG_DEBUG, 0, '_payment');
				$charge = \Stripe\Charge::create(array(
					'customer' => $customer->id,
					'amount'   => price2num($amountstripe, 'MU'),
					'currency' => $currency,
					'capture'  => true, // Charge immediately
					'description' => 'Stripe payment: '.$FULLTAG.' ref='.$ref,
					'metadata' => $metadata,
					'statement_descriptor' => dol_trunc($FULLTAG, 10, 'right', 'UTF-8', 1), // 22 chars that appears on bank receipt (company + description)
				), array("idempotency_key" => "$FULLTAG", "stripe_account" => "$stripeacc"));
				// Return $charge = array('id'=>'ch_XXXX', 'status'=>'succeeded|pending|failed', 'failure_code'=>, 'failure_message'=>...)
				if (empty($charge)) {
					$error++;
					dol_syslog('Failed to charge card', LOG_WARNING, 0, '_payment');
					setEventMessages('Failed to charge card', null, 'errors');
					$action = '';
				}
			}
		} catch (\Stripe\Exception\CardException $e) {
			// Since it's a decline, \Stripe\Exception\Card will be caught
			$body = $e->getJsonBody();
			$err  = $body['error'];

			print('Status is:'.$e->getHttpStatus()."\n");
			print('Type is:'.$err['type']."\n");
			print('Code is:'.$err['code']."\n");
			// param is '' in this case
			print('Param is:'.$err['param']."\n");
			print('Message is:'.$err['message']."\n");

			$error++;
			$errormessage = "ErrorCard ".$e->getMessage()." err=".var_export($err, true);
			dol_syslog($errormessage, LOG_WARNING, 0, '_payment');
			setEventMessages($e->getMessage(), null, 'errors');
			$action = '';
		} catch (\Stripe\Exception\RateLimitException $e) {
			// Too many requests made to the API too quickly
			$error++;
			$errormessage = "ErrorRateLimit ".$e->getMessage();
			dol_syslog($errormessage, LOG_WARNING, 0, '_payment');
			setEventMessages($e->getMessage(), null, 'errors');
			$action = '';
		} catch (\Stripe\Exception\InvalidRequestException $e) {
			// Invalid parameters were supplied to Stripe's API
			$error++;
			$errormessage = "ErrorInvalidRequest ".$e->getMessage();
			dol_syslog($errormessage, LOG_WARNING, 0, '_payment');
			setEventMessages($e->getMessage(), null, 'errors');
			$action = '';
		} catch (\Stripe\Exception\AuthenticationException $e) {
			// Authentication with Stripe's API failed
			// (maybe you changed API keys recently)
			$error++;
			$errormessage = "ErrorAuthentication ".$e->getMessage();
			dol_syslog($errormessage, LOG_WARNING, 0, '_payment');
			setEventMessages($e->getMessage(), null, 'errors');
			$action = '';
		} catch (\Stripe\Exception\ApiConnectionException $e) {
			// Network communication with Stripe failed
			$error++;
			$errormessage = "ErrorApiConnection ".$e->getMessage();
			dol_syslog($errormessage, LOG_WARNING, 0, '_payment');
			setEventMessages($e->getMessage(), null, 'errors');
			$action = '';
		} catch (\Stripe\Exception\ExceptionInterface $e) {
			// Display a very generic error to the user, and maybe send
			// yourself an email
			$error++;
			$errormessage = "ErrorBase ".$e->getMessage();
			dol_syslog($errormessage, LOG_WARNING, 0, '_payment');
			setEventMessages($e->getMessage(), null, 'errors');
			$action = '';
		} catch (Exception $e) {
			// Something else happened, completely unrelated to Stripe
			$error++;
			$errormessage = "ErrorException ".$e->getMessage();
			dol_syslog($errormessage, LOG_WARNING, 0, '_payment');
			setEventMessages($e->getMessage(), null, 'errors');
			$action = '';
		}
	}

	// When using the PaymentIntent API architecture (mode set on by default into conf.class.php)
	if (getDolGlobalInt('STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION')) {
		$service = 'StripeTest';
		$servicestatus = 0;
		if (getDolGlobalString('STRIPE_LIVE') && !GETPOSTINT('forcesandbox')) {
			$service = 'StripeLive';
			$servicestatus = 1;
		}
		include_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';
		$stripe = new Stripe($db);
		$stripeacc = $stripe->getStripeAccount($service);

		// We go here if $conf->global->STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION is set.
		// In such a case, payment is always ok when we call the "charge" action.
		$paymentintent_id = GETPOST("paymentintent_id", "alpha");

		// Force to use the correct API key
		global $stripearrayofkeysbyenv;
		\Stripe\Stripe::setApiKey($stripearrayofkeysbyenv[$servicestatus]['secret_key']);

		try {
			if (empty($stripeacc)) {				// If the Stripe connect account not set, we use common API usage
				$paymentintent = \Stripe\PaymentIntent::retrieve($paymentintent_id);
			} else {
				$paymentintent = \Stripe\PaymentIntent::retrieve($paymentintent_id, array("stripe_account" => $stripeacc));
			}
		} catch (Exception $e) {
			$error++;
			$errormessage = "CantRetrievePaymentIntent ".$e->getMessage();
			dol_syslog($errormessage, LOG_WARNING, 0, '_payment');
			setEventMessages($e->getMessage(), null, 'errors');
			$action = '';
		}

		if ($paymentintent->status != 'succeeded') {
			$error++;
			$errormessage = "StatusOfRetrievedIntent is not succeeded: ".$paymentintent->status;
			dol_syslog($errormessage, LOG_WARNING, 0, '_payment');
			setEventMessages($paymentintent->status, null, 'errors');
			$action = '';
		} else {
			// TODO We can also record the payment mode into llx_societe_rib with stripe $paymentintent->payment_method
			// Note that with other old Stripe architecture (using Charge API), the payment mode was not recorded, so it is not mandatory to do it here.
			//dol_syslog("Create payment_method for ".$paymentintent->payment_method, LOG_DEBUG, 0, '_payment');

			// Get here amount and currency used for payment and force value into $amount and $currency so the real amount is saved into session instead
			// of the amount and currency retrieved from the POST.
			if (!empty($paymentintent->currency) && !empty($paymentintent->amount)) {
				$currency = strtoupper($paymentintent->currency);
				$amount = $paymentintent->amount;

				// Correct the amount according to unit of currency
				// See https://support.stripe.com/questions/which-zero-decimal-currencies-does-stripe-support
				$arrayzerounitcurrency = array('BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'VND', 'VUV', 'XAF', 'XOF', 'XPF');
				if (!in_array($currency, $arrayzerounitcurrency)) {
					$amount /= 100;
				}
			}
		}
	}


	$remoteip = getUserRemoteIP();

	$_SESSION["onlinetoken"] = $stripeToken;
	$_SESSION["FinalPaymentAmt"] = $amount;			// amount really paid (coming from Stripe). Will be used for check in paymentok.php.
	$_SESSION["currencyCodeType"] = $currency;		// currency really used for payment (coming from Stripe). Will be used for check in paymentok.php.
	$_SESSION["paymentType"] = '';
	$_SESSION['ipaddress'] = ($remoteip ? $remoteip : 'unknown'); // Payer ip
	$_SESSION['payerID'] = is_object($customer) ? $customer->id : '';
	$_SESSION['TRANSACTIONID'] = (is_object($charge) ? $charge->id : (is_object($paymentintent) ? $paymentintent->id : ''));
	$_SESSION['errormessage'] = $errormessage;

	dol_syslog("Action charge stripe STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION=".getDolGlobalInt('STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION')." ip=".$remoteip, LOG_DEBUG, 0, '_payment');
	dol_syslog("onlinetoken=".$_SESSION["onlinetoken"]." FinalPaymentAmt=".$_SESSION["FinalPaymentAmt"]." currencyCodeType=".$_SESSION["currencyCodeType"]." payerID=".$_SESSION['payerID']." TRANSACTIONID=".$_SESSION['TRANSACTIONID'], LOG_DEBUG, 0, '_payment');
	dol_syslog("FULLTAG=".$FULLTAG, LOG_DEBUG, 0, '_payment');
	dol_syslog("error=".$error." errormessage=".$errormessage, LOG_DEBUG, 0, '_payment');
	dol_syslog("_SERVER[SERVER_NAME] = ".(empty($_SERVER["SERVER_NAME"]) ? '' : dol_escape_htmltag($_SERVER["SERVER_NAME"])), LOG_DEBUG, 0, '_payment');
	dol_syslog("_SERVER[SERVER_ADDR] = ".(empty($_SERVER["SERVER_ADDR"]) ? '' : dol_escape_htmltag($_SERVER["SERVER_ADDR"])), LOG_DEBUG, 0, '_payment');
	dol_syslog("Now call the redirect to paymentok or paymentko, URL = ".($error ? $urlko : $urlok), LOG_DEBUG, 0, '_payment');

	if ($error) {
		header("Location: ".$urlko);
		exit;
	} else {
		header("Location: ".$urlok);
		exit;
	}
}

// This hook is used to push to $validpaymentmethod by external payment modules (ie Payzen, ...)
$parameters = array(
	'paymentmethod' => $paymentmethod,
	'validpaymentmethod' => &$validpaymentmethod
);
$reshook = $hookmanager->executeHooks('doPayment', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
} elseif ($reshook > 0) {
	print $hookmanager->resPrint;
}



/*
 * View
 */

$form = new Form($db);

$head = '';
if (getDolGlobalString('ONLINE_PAYMENT_CSS_URL')) {
	$head = '<link rel="stylesheet" type="text/css" href="' . getDolGlobalString('ONLINE_PAYMENT_CSS_URL').'?lang='.(!empty($getpostlang) ? $getpostlang : $langs->defaultlang).'">'."\n";
}

$conf->dol_hide_topmenu = 1;
$conf->dol_hide_leftmenu = 1;

$replacemainarea = (empty($conf->dol_hide_leftmenu) ? '<div>' : '').'<div>';
llxHeader($head, $langs->trans("PaymentForm"), '', '', 0, 0, '', '', '', 'onlinepaymentbody', $replacemainarea);

dol_syslog("--- newpayment.php action = ".$action, LOG_DEBUG, 0, '_payment');
dol_syslog("newpayment.php show page source=".$source." paymentmethod=".$paymentmethod.' amount='.$amount.' newamount='.GETPOST("newamount", 'alpha')." ref=".$ref, LOG_DEBUG, 0, '_payment');
dol_syslog("_SERVER[SERVER_NAME] = ".(empty($_SERVER["SERVER_NAME"]) ? '' : dol_escape_htmltag($_SERVER["SERVER_NAME"])), LOG_DEBUG, 0, '_payment');
dol_syslog("_SERVER[SERVER_ADDR] = ".(empty($_SERVER["SERVER_ADDR"]) ? '' : dol_escape_htmltag($_SERVER["SERVER_ADDR"])), LOG_DEBUG, 0, '_payment');

// Check link validity
if ($source && in_array($ref, array('member_ref', 'contractline_ref', 'invoice_ref', 'order_ref', 'donation_ref', ''))) {
	$langs->load("errors");
	dol_print_error_email('BADREFINPAYMENTFORM', $langs->trans("ErrorBadLinkSourceSetButBadValueForRef", $source, $ref));
	// End of page
	llxFooter();
	$db->close();
	exit;
}


// Show sandbox warning
if ((empty($paymentmethod) || $paymentmethod == 'paypal') && isModEnabled('paypal') && (getDolGlobalString('PAYPAL_API_SANDBOX') || GETPOSTINT('forcesandbox'))) {		// We can force sand box with param 'forcesandbox'
	dol_htmloutput_mesg($langs->trans('YouAreCurrentlyInSandboxMode', 'Paypal'), '', 'warning');
}
if ((empty($paymentmethod) || $paymentmethod == 'stripe') && isModEnabled('stripe') && (!getDolGlobalString('STRIPE_LIVE') || GETPOSTINT('forcesandbox'))) {
	dol_htmloutput_mesg($langs->trans('YouAreCurrentlyInSandboxMode', 'Stripe'), '', 'warning');
}


print '<span id="dolpaymentspan"></span>'."\n";
print '<div class="center">'."\n";
print '<form id="dolpaymentform" class="center" name="paymentform" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">'."\n";
print '<input type="hidden" name="action" value="dopayment">'."\n";
print '<input type="hidden" name="tag" value="'.GETPOST("tag", 'alpha').'">'."\n";
print '<input type="hidden" name="suffix" value="'.dol_escape_htmltag($suffix).'">'."\n";
print '<input type="hidden" name="securekey" value="'.dol_escape_htmltag($SECUREKEY).'">'."\n";
print '<input type="hidden" name="e" value="'.$entity.'" />';
print '<input type="hidden" name="forcesandbox" value="'.GETPOSTINT('forcesandbox').'" />';
print '<input type="hidden" name="lang" value="'.$getpostlang.'">';
print '<input type="hidden" name="ws" value="'.$ws.'">';
print "\n";


// Show logo (search order: logo defined by PAYMENT_LOGO_suffix, then PAYMENT_LOGO, then small company logo, large company logo, theme logo, common logo)
// Define logo and logosmall
$logosmall = $mysoc->logo_small;
$logo = $mysoc->logo;
$paramlogo = 'ONLINE_PAYMENT_LOGO_'.$suffix;
if (getDolGlobalString($paramlogo)) {
	$logosmall = getDolGlobalString($paramlogo);
} elseif (getDolGlobalString('ONLINE_PAYMENT_LOGO')) {
	$logosmall = getDolGlobalString('ONLINE_PAYMENT_LOGO');
}
//print '<!-- Show logo (logosmall='.$logosmall.' logo='.$logo.') -->'."\n";
// Define urllogo
$urllogo = '';
$urllogofull = '';
if (!empty($logosmall) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$logosmall)) {
	$urllogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;entity='.$conf->entity.'&amp;file='.urlencode('logos/thumbs/'.$logosmall);
	$urllogofull = $dolibarr_main_url_root.'/viewimage.php?modulepart=mycompany&entity='.$conf->entity.'&file='.urlencode('logos/thumbs/'.$logosmall);
} elseif (!empty($logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$logo)) {
	$urllogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;entity='.$conf->entity.'&amp;file='.urlencode('logos/'.$logo);
	$urllogofull = $dolibarr_main_url_root.'/viewimage.php?modulepart=mycompany&entity='.$conf->entity.'&file='.urlencode('logos/'.$logo);
}

// Output html code for logo
if ($urllogo && !$ws) {
	print '<div class="backgreypublicpayment">';
	print '<div class="logopublicpayment">';
	print '<img id="dolpaymentlogo" src="'.$urllogo.'"';
	print '>';
	print '</div>';
	if (!getDolGlobalString('MAIN_HIDE_POWERED_BY')) {
		print '<div class="poweredbypublicpayment opacitymedium right"><a class="poweredbyhref" href="https://www.dolibarr.org?utm_medium=website&utm_source=poweredby" target="dolibarr" rel="noopener">'.$langs->trans("PoweredBy").'<br><img class="poweredbyimg" src="'.DOL_URL_ROOT.'/theme/dolibarr_logo.svg" width="80px"></a></div>';
	}
	print '</div>';
} elseif ($creditor && !$ws) {
	print '<div class="backgreypublicpayment">';
	print '<div class="logopublicpayment">';
	print $creditor;
	print '</div>';
	print '</div>';
}
if (getDolGlobalString('MAIN_IMAGE_PUBLIC_PAYMENT')) {
	print '<div class="backimagepublicpayment">';
	print '<img id="idMAIN_IMAGE_PUBLIC_PAYMENT" src="'.getDolGlobalString('MAIN_IMAGE_PUBLIC_PAYMENT').'">';
	print '</div>';
}




print '<!-- Form to send a payment -->'."\n";
print '<!-- creditor = '.dol_escape_htmltag($creditor).' -->'."\n";
// Additional information for each payment system
if (isModEnabled('paypal')) {
	print '<!-- PAYPAL_API_SANDBOX = '.getDolGlobalString('PAYPAL_API_SANDBOX').' -->'."\n";
	print '<!-- PAYPAL_API_INTEGRAL_OR_PAYPALONLY = '.getDolGlobalString('PAYPAL_API_INTEGRAL_OR_PAYPALONLY').' -->'."\n";
}
if (isModEnabled('paybox')) {
	print '<!-- PAYBOX_CGI_URL = '.getDolGlobalString('PAYBOX_CGI_URL_V2').' -->'."\n";
}
if (isModEnabled('stripe')) {
	print '<!-- STRIPE_LIVE = '.getDolGlobalString('STRIPE_LIVE').' -->'."\n";
}
print '<!-- urlok = '.$urlok.' -->'."\n";
print '<!-- urlko = '.$urlko.' -->'."\n";
print "\n";

// Section with payment informationsummary
print '<table id="dolpublictable" summary="Payment form" class="center">'."\n";

// Output introduction text
$text = '';
if (getDolGlobalString('PAYMENT_NEWFORM_TEXT')) {
	$langs->load("members");
	if (preg_match('/^\((.*)\)$/', $conf->global->PAYMENT_NEWFORM_TEXT, $reg)) {
		$text .= $langs->trans($reg[1])."<br>\n";
	} else {
		$text .= getDolGlobalString('PAYMENT_NEWFORM_TEXT') . "<br>\n";
	}
	$text = '<tr><td align="center"><br>'.$text.'<br></td></tr>'."\n";
}
if (empty($text)) {
	$text .= '<tr><td class="textpublicpayment"><br><strong>'.$langs->trans("WelcomeOnPaymentPage").'</strong></td></tr>'."\n";
	$text .= '<tr><td class="textpublicpayment"><span class="opacitymedium">'.$langs->trans("ThisScreenAllowsYouToPay", $creditor).'</span><br><br></td></tr>'."\n";
}
print $text;

// Output payment summary form
print '<tr><td align="center">';	// class=center does not have the payment button centered so we keep align here.
print '<table class="centpercent left" id="tablepublicpayment">';
print '<tr class="hideonsmartphone"><td colspan="2" align="left" class="opacitymedium">'.$langs->trans("ThisIsInformationOnPayment").' :</td></tr>'."\n";

$found = false;
$error = 0;

$object = null;


// Free payment
if (!$source) {
	$found = true;
	$tag = GETPOST("tag", 'alpha');
	if (GETPOST('fulltag', 'alpha')) {
		$fulltag = GETPOST('fulltag', 'alpha');
	} else {
		$fulltag = "TAG=".$tag;
	}

	// Creditor
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Creditor");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>'.$creditor.'</b>';
	print '<input type="hidden" name="creditor" value="'.$creditor.'">';
	print '</td></tr>'."\n";

	// Amount
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Amount");
	if (empty($amount)) {
		print ' ('.$langs->trans("ToComplete").')';
	}
	print '</td><td class="CTableRow2">';
	if (empty($amount) || !is_numeric($amount)) {
		print '<input type="hidden" name="amount" value="'.price2num(GETPOST("amount", 'alpha'), 'MT').'">';
		print '<input class="flat maxwidth75" type="text" name="newamount" value="'.price2num(GETPOST("newamount", "alpha"), 'MT').'">';
		// Currency
		print ' <b>'.$langs->trans("Currency".$currency).'</b>';
	} else {
		print '<b class="amount">'.price($amount, 1, $langs, 1, -1, -1, $currency).'</b>';	// Price with currency
		print '<input type="hidden" name="amount" value="'.$amount.'">';
		print '<input type="hidden" name="newamount" value="'.$amount.'">';
	}
	print '<input type="hidden" name="currency" value="'.$currency.'">';
	print '</td></tr>'."\n";

	// Tag
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow2"><b style="word-break: break-all;">'.$fulltag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="fulltag" value="'.$fulltag.'">';
	print '</td></tr>'."\n";

	// We do not add fields shipToName, shipToStreet, shipToCity, shipToState, shipToCountryCode, shipToZip, shipToStreet2, phoneNum
	// as they don't exists (buyer is unknown, tag is free).
}


// Payment on a Sale Order
if ($source == 'order') {
	$found = true;
	$langs->load("orders");

	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

	$order = new Commande($db);
	$result = $order->fetch('', $ref);
	if ($result <= 0) {
		$mesg = $order->error;
		$error++;
	} else {
		$result = $order->fetch_thirdparty($order->socid);
	}
	$object = $order;

	if ($action != 'dopayment') { // Do not change amount if we just click on first dopayment
		$amount = $order->total_ttc;
		if (GETPOST("amount", 'alpha')) {
			$amount = GETPOST("amount", 'alpha');
		}
		$amount = price2num($amount);
	}

	$tag = '';
	if (GETPOST('fulltag', 'alpha')) {
		$fulltag = GETPOST('fulltag', 'alpha');
	} else {
		$fulltag = 'ORD='.$order->id.'.CUS='.$order->thirdparty->id;
		if (!empty($TAG)) {
			$tag = $TAG;
			$fulltag .= '.TAG='.$TAG;
		}
	}
	$fulltag = dol_string_unaccent($fulltag);

	// Creditor
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Creditor");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>'.$creditor.'</b>';
	print '<input type="hidden" name="creditor" value="'.$creditor.'">';
	print '</td></tr>'."\n";

	// Debitor
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("ThirdParty");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>'.$order->thirdparty->name.'</b>';
	print '</td></tr>'."\n";

	// Object
	$text = '<b>'.$langs->trans("PaymentOrderRef", $order->ref).'</b>';
	if (GETPOST('desc', 'alpha')) {
		$text = '<b>'.$langs->trans(GETPOST('desc', 'alpha')).'</b>';
	}
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Designation");
	print '</td><td class="CTableRow2">'.$text;
	print '<input type="hidden" name="s" value="'.dol_escape_htmltag($source).'">';
	print '<input type="hidden" name="ref" value="'.dol_escape_htmltag($order->ref).'">';
	print '<input type="hidden" name="dol_id" value="'.dol_escape_htmltag($order->id).'">';
	$directdownloadlink = $order->getLastMainDocLink('commande');
	if ($directdownloadlink) {
		print '<br><a href="'.$directdownloadlink.'" rel="nofollow noopener">';
		print img_mime($order->last_main_doc, '');
		print $langs->trans("DownloadDocument").'</a>';
	}
	print '</td></tr>'."\n";

	// Amount
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Amount");
	if (empty($amount)) {
		print ' ('.$langs->trans("ToComplete").')';
	}
	print '</td><td class="CTableRow2">';
	if (empty($amount) || !is_numeric($amount)) {
		print '<input type="hidden" name="amount" value="'.price2num(GETPOST("amount", 'alpha'), 'MT').'">';
		print '<input class="flat maxwidth75" type="text" name="newamount" value="'.price2num(GETPOST("newamount", "alpha"), 'MT').'">';
		// Currency
		print ' <b>'.$langs->trans("Currency".$currency).'</b>';
	} else {
		print '<b class="amount">'.price($amount, 1, $langs, 1, -1, -1, $currency).'</b>';	// Price with currency
		print '<input type="hidden" name="amount" value="'.$amount.'">';
		print '<input type="hidden" name="newamount" value="'.$amount.'">';
	}
	print '<input type="hidden" name="currency" value="'.$currency.'">';
	print '</td></tr>'."\n";

	// Tag
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow2"><b style="word-break: break-all;">'.$fulltag.'</b>';
	print '<input type="hidden" name="tag" value="'.dol_escape_htmltag($tag).'">';
	print '<input type="hidden" name="fulltag" value="'.dol_escape_htmltag($fulltag).'">';
	print '</td></tr>'."\n";

	// Shipping address
	$shipToName = $order->thirdparty->name;
	$shipToStreet = $order->thirdparty->address;
	$shipToCity = $order->thirdparty->town;
	$shipToState = $order->thirdparty->state_code;
	$shipToCountryCode = $order->thirdparty->country_code;
	$shipToZip = $order->thirdparty->zip;
	$shipToStreet2 = '';
	$phoneNum = $order->thirdparty->phone;
	if ($shipToName && $shipToStreet && $shipToCity && $shipToCountryCode && $shipToZip) {
		print '<input type="hidden" name="shipToName" value="'.dol_escape_htmltag($shipToName).'">'."\n";
		print '<input type="hidden" name="shipToStreet" value="'.dol_escape_htmltag($shipToStreet).'">'."\n";
		print '<input type="hidden" name="shipToCity" value="'.dol_escape_htmltag($shipToCity).'">'."\n";
		print '<input type="hidden" name="shipToState" value="'.dol_escape_htmltag($shipToState).'">'."\n";
		print '<input type="hidden" name="shipToCountryCode" value="'.dol_escape_htmltag($shipToCountryCode).'">'."\n";
		print '<input type="hidden" name="shipToZip" value="'.dol_escape_htmltag($shipToZip).'">'."\n";
		print '<input type="hidden" name="shipToStreet2" value="'.dol_escape_htmltag($shipToStreet2).'">'."\n";
		print '<input type="hidden" name="phoneNum" value="'.dol_escape_htmltag($phoneNum).'">'."\n";
	} else {
		print '<!-- Shipping address not complete, so we don t use it -->'."\n";
	}
	if (is_object($order->thirdparty)) {
		print '<input type="hidden" name="thirdparty_id" value="'.$order->thirdparty->id.'">'."\n";
	}
	print '<input type="hidden" name="email" value="'.$order->thirdparty->email.'">'."\n";
	print '<input type="hidden" name="vatnumber" value="'.dol_escape_htmltag($order->thirdparty->tva_intra).'">'."\n";
	$labeldesc = $langs->trans("Order").' '.$order->ref;
	if (GETPOST('desc', 'alpha')) {
		$labeldesc = GETPOST('desc', 'alpha');
	}
	print '<input type="hidden" name="desc" value="'.dol_escape_htmltag($labeldesc).'">'."\n";
}


// Payment on a Customer Invoice
if ($source == 'invoice') {
	$found = true;
	$langs->load("bills");
	$form->load_cache_types_paiements();

	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

	$invoice = new Facture($db);
	$result = $invoice->fetch('', $ref);
	if ($result <= 0) {
		$mesg = $invoice->error;
		$error++;
	} else {
		$result = $invoice->fetch_thirdparty($invoice->socid);
	}
	$object = $invoice;

	if ($action != 'dopayment') { // Do not change amount if we just click on first dopayment
		$amount = price2num($invoice->total_ttc - ($invoice->getSommePaiement() + $invoice->getSumCreditNotesUsed() + $invoice->getSumDepositsUsed()));
		if (GETPOST("amount", 'alpha')) {
			$amount = GETPOST("amount", 'alpha');
		}
		$amount = price2num($amount);
	}

	if (GETPOST('fulltag', 'alpha')) {
		$fulltag = GETPOST('fulltag', 'alpha');
	} else {
		$fulltag = 'INV='.$invoice->id.'.CUS='.$invoice->thirdparty->id;
		if (!empty($TAG)) {
			$tag = $TAG;
			$fulltag .= '.TAG='.$TAG;
		}
	}
	$fulltag = dol_string_unaccent($fulltag);

	// Creditor
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Creditor");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>'.$creditor.'</b>';
	print '<input type="hidden" name="creditor" value="'.dol_escape_htmltag($creditor).'">';
	print '</td></tr>'."\n";

	// Debitor
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("ThirdParty");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>'.$invoice->thirdparty->name.'</b>';
	print '</td></tr>'."\n";

	// Object
	$text = '<b>'.$langs->trans("PaymentInvoiceRef", $invoice->ref).'</b>';
	if (GETPOST('desc', 'alpha')) {
		$text = '<b>'.$langs->trans(GETPOST('desc', 'alpha')).'</b>';
	}
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Designation");
	print '</td><td class="CTableRow2">'.$text;
	print '<input type="hidden" name="s" value="'.dol_escape_htmltag($source).'">';
	print '<input type="hidden" name="ref" value="'.dol_escape_htmltag($invoice->ref).'">';
	print '<input type="hidden" name="dol_id" value="'.dol_escape_htmltag($invoice->id).'">';
	$directdownloadlink = $invoice->getLastMainDocLink('facture');
	if ($directdownloadlink) {
		print '<br><a href="'.$directdownloadlink.'">';
		print img_mime($invoice->last_main_doc, '');
		print $langs->trans("DownloadDocument").'</a>';
	}
	print '</td></tr>'."\n";

	// Amount
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("PaymentAmount");
	if (empty($amount) && empty($object->paye)) {
		print ' ('.$langs->trans("ToComplete").')';
	}
	print '</td><td class="CTableRow2">';
	if ($object->type == $object::TYPE_CREDIT_NOTE) {
		print '<b>'.$langs->trans("CreditNote").'</b>';
	} elseif (empty($object->paye)) {
		if (empty($amount) || !is_numeric($amount)) {
			print '<input type="hidden" name="amount" value="'.price2num(GETPOST("amount", 'alpha'), 'MT').'">';
			print '<input class="flat maxwidth75" type="text" name="newamount" value="'.price2num(GETPOST("newamount", "alpha"), 'MT').'">';
			print ' <b>'.$langs->trans("Currency".$currency).'</b>';
		} else {
			print '<b class="amount">'.price($amount, 1, $langs, 1, -1, -1, $currency).'</b>';	// Price with currency
			print '<input type="hidden" name="amount" value="'.$amount.'">';
			print '<input type="hidden" name="newamount" value="'.$amount.'">';
		}
	} else {
		print '<b class="amount">'.price($object->total_ttc, 1, $langs, 1, -1, -1, $currency).'</b>';	// Price with currency
	}
	print '<input type="hidden" name="currency" value="'.$currency.'">';
	print '</td></tr>'."\n";

	// Tag
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow2"><b style="word-break: break-all;">'.$fulltag.'</b>';
	print '<input type="hidden" name="tag" value="'.(empty($tag) ? '' : $tag).'">';
	print '<input type="hidden" name="fulltag" value="'.$fulltag.'">';
	print '</td></tr>'."\n";

	// Add a warning if we try to pay an invoice set to be paid in credit transfer
	if ($invoice->status == $invoice::STATUS_VALIDATED && $invoice->mode_reglement_id > 0 && $form->cache_types_paiements[$invoice->mode_reglement_id]["code"] == "VIR") {
		print '<tr class="CTableRow2 center"><td class="CTableRow2" colspan="2">';
		print '<div class="warning maxwidth1000">';
		print $langs->trans("PayOfBankTransferInvoice");
		print '</div>';
		print '</td></tr>'."\n";
	}

	// Shipping address
	$shipToName = $invoice->thirdparty->name;
	$shipToStreet = $invoice->thirdparty->address;
	$shipToCity = $invoice->thirdparty->town;
	$shipToState = $invoice->thirdparty->state_code;
	$shipToCountryCode = $invoice->thirdparty->country_code;
	$shipToZip = $invoice->thirdparty->zip;
	$shipToStreet2 = '';
	$phoneNum = $invoice->thirdparty->phone;
	if ($shipToName && $shipToStreet && $shipToCity && $shipToCountryCode && $shipToZip) {
		print '<input type="hidden" name="shipToName" value="'.$shipToName.'">'."\n";
		print '<input type="hidden" name="shipToStreet" value="'.$shipToStreet.'">'."\n";
		print '<input type="hidden" name="shipToCity" value="'.$shipToCity.'">'."\n";
		print '<input type="hidden" name="shipToState" value="'.$shipToState.'">'."\n";
		print '<input type="hidden" name="shipToCountryCode" value="'.$shipToCountryCode.'">'."\n";
		print '<input type="hidden" name="shipToZip" value="'.$shipToZip.'">'."\n";
		print '<input type="hidden" name="shipToStreet2" value="'.$shipToStreet2.'">'."\n";
		print '<input type="hidden" name="phoneNum" value="'.$phoneNum.'">'."\n";
	} else {
		print '<!-- Shipping address not complete, so we don t use it -->'."\n";
	}
	if (is_object($invoice->thirdparty)) {
		print '<input type="hidden" name="thirdparty_id" value="'.$invoice->thirdparty->id.'">'."\n";
	}
	print '<input type="hidden" name="email" value="'.$invoice->thirdparty->email.'">'."\n";
	print '<input type="hidden" name="vatnumber" value="'.$invoice->thirdparty->tva_intra.'">'."\n";
	$labeldesc = $langs->trans("Invoice").' '.$invoice->ref;
	if (GETPOST('desc', 'alpha')) {
		$labeldesc = GETPOST('desc', 'alpha');
	}
	print '<input type="hidden" name="desc" value="'.dol_escape_htmltag($labeldesc).'">'."\n";
}

// Payment on a Contract line
if ($source == 'contractline') {
	$found = true;
	$langs->load("contracts");

	require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';

	$contract = new Contrat($db);
	$contractline = new ContratLigne($db);

	$result = $contractline->fetch('', $ref);
	if ($result <= 0) {
		$mesg = $contractline->error;
		$error++;
	} else {
		if ($contractline->fk_contrat > 0) {
			$result = $contract->fetch($contractline->fk_contrat);
			if ($result > 0) {
				$result = $contract->fetch_thirdparty($contract->socid);
			} else {
				$mesg = $contract->error;
				$error++;
			}
		} else {
			$mesg = 'ErrorRecordNotFound';
			$error++;
		}
	}
	$object = $contractline;

	if ($action != 'dopayment') { // Do not change amount if we just click on first dopayment
		$amount = $contractline->total_ttc;

		if ($contractline->fk_product && getDolGlobalString('PAYMENT_USE_NEW_PRICE_FOR_CONTRACTLINES')) {
			$product = new Product($db);
			$result = $product->fetch($contractline->fk_product);

			// We define price for product (TODO Put this in a method in product class)
			if (getDolGlobalString('PRODUIT_MULTIPRICES')) {
				$pu_ht = $product->multiprices[$contract->thirdparty->price_level];
				$pu_ttc = $product->multiprices_ttc[$contract->thirdparty->price_level];
				$price_base_type = $product->multiprices_base_type[$contract->thirdparty->price_level];
			} else {
				$pu_ht = $product->price;
				$pu_ttc = $product->price_ttc;
				$price_base_type = $product->price_base_type;
			}

			$amount = $pu_ttc;
			if (empty($amount)) {
				dol_print_error(null, 'ErrorNoPriceDefinedForThisProduct');
				exit;
			}
		}

		if (GETPOST("amount", 'alpha')) {
			$amount = GETPOST("amount", 'alpha');
		}
		$amount = price2num($amount);
	}

	if (GETPOST('fulltag', 'alpha')) {
		$fulltag = GETPOST('fulltag', 'alpha');
	} else {
		$fulltag = 'COL='.$contractline->id.'.CON='.$contract->id.'.CUS='.$contract->thirdparty->id.'.DAT='.dol_print_date(dol_now(), '%Y%m%d%H%M%S');
		if (!empty($TAG)) {
			$tag = $TAG;
			$fulltag .= '.TAG='.$TAG;
		}
	}
	$fulltag = dol_string_unaccent($fulltag);

	$qty = 1;
	if (GETPOST('qty')) {
		$qty = price2num(GETPOST('qty', 'alpha'), 'MS');
	}

	// Creditor
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Creditor");
	print '</td><td class="CTableRow2"><b>'.$creditor.'</b>';
	print '<input type="hidden" name="creditor" value="'.$creditor.'">';
	print '</td></tr>'."\n";

	// Debitor
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("ThirdParty");
	print '</td><td class="CTableRow2"><b>'.$contract->thirdparty->name.'</b>';
	print '</td></tr>'."\n";

	// Object
	$text = '<b>'.$langs->trans("PaymentRenewContractId", $contract->ref, $contractline->ref).'</b>';
	if ($contractline->fk_product > 0) {
		$contractline->fetch_product();
		$text .= '<br>'.$contractline->product->ref.($contractline->product->label ? ' - '.$contractline->product->label : '');
	}
	if ($contractline->description) {
		$text .= '<br>'.dol_htmlentitiesbr($contractline->description);
	}
	if ($contractline->date_end) {
		$text .= '<br>'.$langs->trans("ExpiredSince").': '.dol_print_date($contractline->date_end);
	}
	if (GETPOST('desc', 'alpha')) {
		$text = '<b>'.$langs->trans(GETPOST('desc', 'alpha')).'</b>';
	}
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Designation");
	print '</td><td class="CTableRow2">'.$text;
	print '<input type="hidden" name="source" value="'.dol_escape_htmltag($source).'">';
	print '<input type="hidden" name="ref" value="'.dol_escape_htmltag($contractline->ref).'">';
	print '<input type="hidden" name="dol_id" value="'.dol_escape_htmltag($contractline->id).'">';
	$directdownloadlink = $contract->getLastMainDocLink('contract');
	if ($directdownloadlink) {
		print '<br><a href="'.$directdownloadlink.'">';
		print img_mime($contract->last_main_doc, '');
		print $langs->trans("DownloadDocument").'</a>';
	}
	print '</td></tr>'."\n";

	// Quantity
	$label = $langs->trans("Quantity");
	$qty = 1;
	$duration = '';
	if ($contractline->fk_product) {
		if ($contractline->product->isService() && $contractline->product->duration_value > 0) {
			$label = $langs->trans("Duration");

			// TODO Put this in a global method
			if ($contractline->product->duration_value > 1) {
				$dur = array("h" => $langs->trans("Hours"), "d" => $langs->trans("DurationDays"), "w" => $langs->trans("DurationWeeks"), "m" => $langs->trans("DurationMonths"), "y" => $langs->trans("DurationYears"));
			} else {
				$dur = array("h" => $langs->trans("Hour"), "d" => $langs->trans("DurationDay"), "w" => $langs->trans("DurationWeek"), "m" => $langs->trans("DurationMonth"), "y" => $langs->trans("DurationYear"));
			}
			$duration = $contractline->product->duration_value.' '.$dur[$contractline->product->duration_unit];
		}
	}
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$label.'</td>';
	print '<td class="CTableRow2"><b>'.($duration ? $duration : $qty).'</b>';
	print '<input type="hidden" name="newqty" value="'.dol_escape_htmltag($qty).'">';
	print '</b></td></tr>'."\n";

	// Amount
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Amount");
	if (empty($amount)) {
		print ' ('.$langs->trans("ToComplete").')';
	}
	print '</td><td class="CTableRow2">';
	if (empty($amount) || !is_numeric($amount)) {
		print '<input type="hidden" name="amount" value="'.price2num(GETPOST("amount", 'alpha'), 'MT').'">';
		print '<input class="flat maxwidth75" type="text" name="newamount" value="'.price2num(GETPOST("newamount", "alpha"), 'MT').'">';
		// Currency
		print ' <b>'.$langs->trans("Currency".$currency).'</b>';
	} else {
		print '<b class="amount">'.price($amount, 1, $langs, 1, -1, -1, $currency).'</b>';	// Price with currency
		print '<input type="hidden" name="amount" value="'.$amount.'">';
		print '<input type="hidden" name="newamount" value="'.$amount.'">';
	}
	print '<input type="hidden" name="currency" value="'.$currency.'">';
	print '</td></tr>'."\n";

	// Tag
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow2"><b style="word-break: break-all;">'.$fulltag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="fulltag" value="'.$fulltag.'">';
	print '</td></tr>'."\n";

	// Shipping address
	$shipToName = $contract->thirdparty->name;
	$shipToStreet = $contract->thirdparty->address;
	$shipToCity = $contract->thirdparty->town;
	$shipToState = $contract->thirdparty->state_code;
	$shipToCountryCode = $contract->thirdparty->country_code;
	$shipToZip = $contract->thirdparty->zip;
	$shipToStreet2 = '';
	$phoneNum = $contract->thirdparty->phone;
	if ($shipToName && $shipToStreet && $shipToCity && $shipToCountryCode && $shipToZip) {
		print '<input type="hidden" name="shipToName" value="'.$shipToName.'">'."\n";
		print '<input type="hidden" name="shipToStreet" value="'.$shipToStreet.'">'."\n";
		print '<input type="hidden" name="shipToCity" value="'.$shipToCity.'">'."\n";
		print '<input type="hidden" name="shipToState" value="'.$shipToState.'">'."\n";
		print '<input type="hidden" name="shipToCountryCode" value="'.$shipToCountryCode.'">'."\n";
		print '<input type="hidden" name="shipToZip" value="'.$shipToZip.'">'."\n";
		print '<input type="hidden" name="shipToStreet2" value="'.$shipToStreet2.'">'."\n";
		print '<input type="hidden" name="phoneNum" value="'.$phoneNum.'">'."\n";
	} else {
		print '<!-- Shipping address not complete, so we don t use it -->'."\n";
	}
	if (is_object($contract->thirdparty)) {
		print '<input type="hidden" name="thirdparty_id" value="'.$contract->thirdparty->id.'">'."\n";
	}
	print '<input type="hidden" name="email" value="'.$contract->thirdparty->email.'">'."\n";
	print '<input type="hidden" name="vatnumber" value="'.$contract->thirdparty->tva_intra.'">'."\n";
	$labeldesc = $langs->trans("Contract").' '.$contract->ref;
	if (GETPOST('desc', 'alpha')) {
		$labeldesc = GETPOST('desc', 'alpha');
	}
	print '<input type="hidden" name="desc" value="'.dol_escape_htmltag($labeldesc).'">'."\n";
}

// Payment on a Member subscription
if ($source == 'member' || $source == 'membersubscription') {
	$newsource = 'member';

	$tag = "";
	$found = true;
	$langs->load("members");

	require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
	require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
	require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';

	$member = new Adherent($db);
	$adht = new AdherentType($db);
	$subscription = new Subscription($db);

	$result = $member->fetch('', $ref);
	if ($result <= 0) {
		$mesg = $member->error;
		$error++;
	} else {
		$member->fetch_thirdparty();

		$adht->fetch($member->typeid);
	}
	$object = $member;

	if ($action != 'dopayment') { // Do not change amount if we just click on first dopayment
		$amount = $subscription->total_ttc;
		if (GETPOST("amount", 'alpha')) {
			$amount = price2num(GETPOST("amount", 'alpha'), 'MT', 2);
		}
		// If amount still not defined, we take amount of the type of member
		if (empty($amount)) {
			$amount = $adht->amount;
		}

		$amount = max(0, price2num($amount, 'MT'));
	}

	if (GETPOST('fulltag', 'alpha')) {
		$fulltag = GETPOST('fulltag', 'alpha');
	} else {
		$fulltag = 'MEM='.$member->id.'.DAT='.dol_print_date(dol_now(), '%Y%m%d%H%M%S');
		if (!empty($TAG)) {
			$tag = $TAG;
			$fulltag .= '.TAG='.$TAG;
		}
	}
	$fulltag = dol_string_unaccent($fulltag);

	// Creditor
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Creditor");
	print '</td><td class="CTableRow2"><b>'.$creditor.'</b>';
	print '<input type="hidden" name="creditor" value="'.$creditor.'">';
	print '</td></tr>'."\n";

	// Debitor
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Member");
	print '</td><td class="CTableRow2">';
	print '<b>';
	if ($member->morphy == 'mor' && !empty($member->company)) {
		print img_picto('', 'company', 'class="pictofixedwidth"');
		print $member->company;
	} else {
		print img_picto('', 'member', 'class="pictofixedwidth"');
		print $member->getFullName($langs);
	}
	print '</b>';
	print '</td></tr>'."\n";

	// Object
	$text = '<b>'.$langs->trans("PaymentSubscription").'</b>';
	if (GETPOST('desc', 'alpha')) {
		$text = '<b>'.$langs->trans(GETPOST('desc', 'alpha')).'</b>';
	}
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Designation");
	print '</td><td class="CTableRow2">'.$text;
	print '<input type="hidden" name="source" value="'.dol_escape_htmltag($newsource).'">';
	print '<input type="hidden" name="ref" value="'.dol_escape_htmltag($member->ref).'">';
	print '</td></tr>'."\n";

	if ($object->datefin > 0) {
		print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("DateEndSubscription");
		print '</td><td class="CTableRow2">'.dol_print_date($member->datefin, 'day');
		print '</td></tr>'."\n";
	}

	if ($member->last_subscription_date || $member->last_subscription_amount) {
		// Last subscription date

		print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("LastSubscriptionDate");
		print '</td><td class="CTableRow2">'.dol_print_date($member->last_subscription_date, 'day');
		print '</td></tr>'."\n";

		// Last subscription amount

		print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("LastSubscriptionAmount");
		print '</td><td class="CTableRow2">'.price($member->last_subscription_amount);
		print '</td></tr>'."\n";

		if (empty($amount) && !GETPOST('newamount', 'alpha')) {
			$_GET['newamount'] = $member->last_subscription_amount;
			$_GET['amount'] = $member->last_subscription_amount;
		}
		if (!empty($member->last_subscription_amount) && !GETPOSTISSET('newamount') && is_numeric($amount)) {
			$amount = max($member->last_subscription_amount, $amount);
		}
	}

	$amountbytype = $adht->amountByType(1);

	$typeid = $adht->id;
	$caneditamount = $adht->caneditamount;

	if ($member->type) {
		$oldtypeid = $member->typeid;
		$newtypeid = (int) (GETPOSTISSET("typeid") ? GETPOSTINT("typeid") : $member->typeid);
		if (getDolGlobalString('MEMBER_ALLOW_CHANGE_OF_TYPE')) {
			$typeid = $newtypeid;
			$adht->fetch($typeid);	// Reload with the new type id
		}

		$caneditamount = $adht->caneditamount;

		if (getDolGlobalString('MEMBER_ALLOW_CHANGE_OF_TYPE')) {
			// Last member type
			print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("LastMemberType");
			print '</td><td class="CTableRow2">'.dol_escape_htmltag($member->type);
			print "</td></tr>\n";

			// Set the new member type
			$member->typeid = $newtypeid;
			$member->type = (string) dol_getIdFromCode($db, $newtypeid, 'adherent_type', 'rowid', 'libelle');

			// list member type
			if (!$action) {
				// Set amount for the subscription.
				// If we change the type, we use the amount of the new type and not the amount of last subscription.
				$amount = (!empty($amountbytype[$member->typeid])) ? $amountbytype[$member->typeid] : $member->last_subscription_amount;

				print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("NewSubscription");
				print '</td><td class="CTableRow2">';
				print $form->selectarray("typeid", $adht->liste_array(1), $member->typeid, 0, 0, 0, 'onchange="window.location.replace(\''.$urlwithroot.'/public/payment/newpayment.php?source='.urlencode($source).'&ref='.urlencode($ref).'&amount='.urlencode($amount).'&typeid=\' + this.value + \'&securekey='.urlencode($SECUREKEY).'\');"', 0, 0, 0, '', '', 1);
				print "</td></tr>\n";
			} elseif ($action == 'dopayment') {
				print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("NewMemberType");
				print '</td><td class="CTableRow2">'.dol_escape_htmltag($member->type);
				print '<input type="hidden" name="membertypeid" value="'.$member->typeid.'">';
				print "</td></tr>\n";
			}
		} else {
			print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("MemberType");
			print '</td><td class="CTableRow2">'.dol_escape_htmltag($member->type);
			print "</td></tr>\n";
		}
	}

	// Set amount for the subscription from the the type and options:
	// - First check the amount of the member type if not previous payment.
	$amount = ($member->last_subscription_amount ? $member->last_subscription_amount : (empty($amountbytype[$typeid]) ? 0 : $amountbytype[$typeid]));
	// - If not found, take the default amount
	if (empty($amount) && getDolGlobalString('MEMBER_NEWFORM_AMOUNT')) {
		$amount = getDolGlobalString('MEMBER_NEWFORM_AMOUNT');
	}
	// - If not set, we accept to have amount defined as parameter (for backward compatibility).
	//if (empty($amount)) {
	//	$amount = (GETPOST('amount') ? price2num(GETPOST('amount', 'alpha'), 'MT', 2) : '');
	//}
	// - If a min is set, we take it into account
	$amount = max(0, (float) $amount, (float) getDolGlobalInt("MEMBER_MIN_AMOUNT"));

	// Amount
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Amount");
	// This place no longer allows amount edition
	if (getDolGlobalString('MEMBER_EXT_URL_SUBSCRIPTION_INFO')) {
		print ' - <a href="' . getDolGlobalString('MEMBER_EXT_URL_SUBSCRIPTION_INFO').'" rel="external" target="_blank" rel="noopener noreferrer">'.$langs->trans("SeeHere").'</a>';
	}
	print '</td><td class="CTableRow2">';

	$caneditamount = $adht->caneditamount;
	$minimumamount = !getDolGlobalString('MEMBER_MIN_AMOUNT') ? $adht->amount : max(getDolGlobalString('MEMBER_MIN_AMOUNT'), $adht->amount, $amount);

	if ($caneditamount && $action != 'dopayment') {
		if (GETPOSTISSET('newamount')) {
			print '<input type="text" class="width75" name="newamount" value="'.price(price2num(GETPOST('newamount'), '', 2), 1, $langs, 1, -1, -1).'">';
		} else {
			print '<input type="text" class="width75" name="newamount" value="'.price($amount, 1, $langs, 1, -1, -1).'">';
		}
	} else {
		print '<b class="amount">'.price($amount, 1, $langs, 1, -1, -1, $currency).'</b>';	// Price with currency
		if ($minimumamount > $amount) {
			print ' &nbsp; <span class="opacitymedium small">'. $langs->trans("AmountIsLowerToMinimumNotice", price($minimumamount, 1, $langs, 1, -1, -1, $currency)).'</span>';
		}
		print '<input type="hidden" name="newamount" value="'.$amount.'">';
	}
	print '<input type="hidden" name="amount" value="'.$amount.'">';
	print '<input type="hidden" name="currency" value="'.$currency.'">';
	print '</td></tr>'."\n";

	// Tag
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow2"><b style="word-break: break-all;">'.$fulltag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="fulltag" value="'.$fulltag.'">';
	print '</td></tr>'."\n";

	// Shipping address
	$shipToName = $member->getFullName($langs);
	$shipToStreet = $member->address;
	$shipToCity = $member->town;
	$shipToState = $member->state_code;
	$shipToCountryCode = $member->country_code;
	$shipToZip = $member->zip;
	$shipToStreet2 = '';
	$phoneNum = $member->phone;
	if ($shipToName && $shipToStreet && $shipToCity && $shipToCountryCode && $shipToZip) {
		print '<!-- Shipping address information -->';
		print '<input type="hidden" name="shipToName" value="'.$shipToName.'">'."\n";
		print '<input type="hidden" name="shipToStreet" value="'.$shipToStreet.'">'."\n";
		print '<input type="hidden" name="shipToCity" value="'.$shipToCity.'">'."\n";
		print '<input type="hidden" name="shipToState" value="'.$shipToState.'">'."\n";
		print '<input type="hidden" name="shipToCountryCode" value="'.$shipToCountryCode.'">'."\n";
		print '<input type="hidden" name="shipToZip" value="'.$shipToZip.'">'."\n";
		print '<input type="hidden" name="shipToStreet2" value="'.$shipToStreet2.'">'."\n";
		print '<input type="hidden" name="phoneNum" value="'.$phoneNum.'">'."\n";
	} else {
		print '<!-- Shipping address not complete, so we don t use it -->'."\n";
	}
	if (is_object($member->thirdparty)) {
		print '<input type="hidden" name="thirdparty_id" value="'.$member->thirdparty->id.'">'."\n";
	}
	print '<input type="hidden" name="email" value="'.$member->email.'">'."\n";
	$labeldesc = $langs->trans("PaymentSubscription");
	if (GETPOST('desc', 'alpha')) {
		$labeldesc = GETPOST('desc', 'alpha');
	}
	print '<input type="hidden" name="desc" value="'.dol_escape_htmltag($labeldesc).'">'."\n";
}

// Payment on donation
if ($source == 'donation') {
	$found = true;
	$langs->load("don");

	require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';

	$don = new Don($db);
	// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
	$result = $don->fetch($ref);
	if ($result <= 0) {
		$mesg = $don->error;
		$error++;
	} else {
		$don->fetch_thirdparty();
	}
	$object = $don;

	if ($action != 'dopayment') { // Do not change amount if we just click on first dopayment
		if (GETPOST("amount", 'alpha')) {
			$amount = GETPOST("amount", 'alpha');
		} else {
			$amount = $don->getRemainToPay();
		}
		$amount = price2num($amount);
	}

	if (GETPOST('fulltag', 'alpha')) {
		$fulltag = GETPOST('fulltag', 'alpha');
	} else {
		$fulltag = 'DON='.$don->ref.'.DAT='.dol_print_date(dol_now(), '%Y%m%d%H%M%S');
		if (!empty($TAG)) {
			$tag = $TAG;
			$fulltag .= '.TAG='.$TAG;
		}
	}
	$fulltag = dol_string_unaccent($fulltag);

	// Creditor
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Creditor");
	print '</td><td class="CTableRow2"><b>'.$creditor.'</b>';
	print '<input type="hidden" name="creditor" value="'.$creditor.'">';
	print '</td></tr>'."\n";

	// Debitor
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("ThirdParty");
	print '</td><td class="CTableRow2"><b>';
	if ($don->morphy == 'mor' && !empty($don->societe)) {
		print $don->societe;
	} else {
		print $don->getFullName($langs);
	}
	print '</b>';
	print '</td></tr>'."\n";

	// Object
	$text = '<b>'.$langs->trans("PaymentDonation").'</b>';
	if (GETPOST('desc', 'alpha')) {
		$text = '<b>'.$langs->trans(GETPOST('desc', 'alpha')).'</b>';
	}
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Designation");
	print '</td><td class="CTableRow2">'.$text;
	print '<input type="hidden" name="source" value="'.dol_escape_htmltag($source).'">';
	print '<input type="hidden" name="ref" value="'.dol_escape_htmltag($don->ref).'">';
	print '</td></tr>'."\n";

	// Amount
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Amount");
	if (empty($amount)) {
		if (!getDolGlobalString('DONATION_NEWFORM_AMOUNT')) {
			print ' ('.$langs->trans("ToComplete");
		}
		if (getDolGlobalString('DONATION_EXT_URL_SUBSCRIPTION_INFO')) {
			print ' - <a href="' . getDolGlobalString('DONATION_EXT_URL_SUBSCRIPTION_INFO').'" rel="external" target="_blank" rel="noopener noreferrer">'.$langs->trans("SeeHere").'</a>';
		}
		if (!getDolGlobalString('DONATION_NEWFORM_AMOUNT')) {
			print ')';
		}
	}
	print '</td><td class="CTableRow2">';
	$valtoshow = '';
	if (empty($amount) || !is_numeric($amount)) {
		$valtoshow = price2num(GETPOST("newamount", 'alpha'), 'MT');
		// force default subscription amount to value defined into constant...
		if (empty($valtoshow)) {
			if (getDolGlobalString('DONATION_NEWFORM_EDITAMOUNT')) {
				if (getDolGlobalString('DONATION_NEWFORM_AMOUNT')) {
					$valtoshow = getDolGlobalString('DONATION_NEWFORM_AMOUNT');
				}
			} else {
				if (getDolGlobalString('DONATION_NEWFORM_AMOUNT')) {
					$amount = getDolGlobalString('DONATION_NEWFORM_AMOUNT');
				}
			}
		}
	}
	if (empty($amount) || !is_numeric($amount)) {
		//$valtoshow=price2num(GETPOST("newamount",'alpha'),'MT');
		if (getDolGlobalString('DONATION_MIN_AMOUNT') && $valtoshow) {
			$valtoshow = max(getDolGlobalString('DONATION_MIN_AMOUNT'), $valtoshow);
		}
		print '<input type="hidden" name="amount" value="'.price2num(GETPOST("amount", 'alpha'), 'MT').'">';
		print '<input class="flat maxwidth75" type="text" name="newamount" value="'.$valtoshow.'">';
		// Currency
		print ' <b>'.$langs->trans("Currency".$currency).'</b>';
	} else {
		$valtoshow = $amount;
		if (getDolGlobalString('DONATION_MIN_AMOUNT') && $valtoshow) {
			$valtoshow = max(getDolGlobalString('DONATION_MIN_AMOUNT'), $valtoshow);
			$amount = $valtoshow;
		}
		print '<b class="amount">'.price($valtoshow, 1, $langs, 1, -1, -1, $currency).'</b>';	// Price with currency
		print '<input type="hidden" name="amount" value="'.$valtoshow.'">';
		print '<input type="hidden" name="newamount" value="'.$valtoshow.'">';
	}
	print '<input type="hidden" name="currency" value="'.$currency.'">';
	print '</td></tr>'."\n";

	// Tag
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow2"><b style="word-break: break-all;">'.$fulltag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="fulltag" value="'.$fulltag.'">';
	print '</td></tr>'."\n";

	// Shipping address
	$shipToName = $don->getFullName($langs);
	$shipToStreet = $don->address;
	$shipToCity = $don->town;
	$shipToState = $don->state_code;
	$shipToCountryCode = $don->country_code;
	$shipToZip = $don->zip;
	$shipToStreet2 = '';
	$phoneNum = $don->phone;
	if ($shipToName && $shipToStreet && $shipToCity && $shipToCountryCode && $shipToZip) {
		print '<!-- Shipping address information -->';
		print '<input type="hidden" name="shipToName" value="'.$shipToName.'">'."\n";
		print '<input type="hidden" name="shipToStreet" value="'.$shipToStreet.'">'."\n";
		print '<input type="hidden" name="shipToCity" value="'.$shipToCity.'">'."\n";
		print '<input type="hidden" name="shipToState" value="'.$shipToState.'">'."\n";
		print '<input type="hidden" name="shipToCountryCode" value="'.$shipToCountryCode.'">'."\n";
		print '<input type="hidden" name="shipToZip" value="'.$shipToZip.'">'."\n";
		print '<input type="hidden" name="shipToStreet2" value="'.$shipToStreet2.'">'."\n";
		print '<input type="hidden" name="phoneNum" value="'.$phoneNum.'">'."\n";
	} else {
		print '<!-- Shipping address not complete, so we don t use it -->'."\n";
	}
	if (is_object($don->thirdparty)) {
		print '<input type="hidden" name="thirdparty_id" value="'.$don->thirdparty->id.'">'."\n";
	}
	print '<input type="hidden" name="email" value="'.$don->email.'">'."\n";
	$labeldesc = $langs->trans("PaymentSubscription");
	if (GETPOST('desc', 'alpha')) {
		$labeldesc = GETPOST('desc', 'alpha');
	}
	print '<input type="hidden" name="desc" value="'.dol_escape_htmltag($labeldesc).'">'."\n";
}

if ($source == 'organizedeventregistration') {
	$found = true;
	$langs->loadLangs(array("members", "eventorganization"));

	if (GETPOST('fulltag', 'alpha')) {
		$fulltag = GETPOST('fulltag', 'alpha');
	} else {
		$fulltag = 'ATT='.$attendee->id.'.DAT='.dol_print_date(dol_now(), '%Y%m%d%H%M%S');
		if (!empty($TAG)) {
			$tag = $TAG;
			$fulltag .= '.TAG='.$TAG;
		}
	}
	$fulltag = dol_string_unaccent($fulltag);

	// Creditor
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Creditor");
	print '</td><td class="CTableRow2"><b>'.$creditor.'</b>';
	print '<input type="hidden" name="creditor" value="'.$creditor.'">';
	print '</td></tr>'."\n";

	// Debitor
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Attendee");
	print '</td><td class="CTableRow2"><b>';
	print $attendee->email;
	print($thirdparty->name ? ' ('.$thirdparty->name.')' : '');
	print '</b>';
	print '</td></tr>'."\n";

	if (! is_object($attendee->project)) {
		$text = 'ErrorProjectNotFound';
	} else {
		$text = $langs->trans("PaymentEvent").' - '.$attendee->project->title;
	}

	// Object
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Designation");
	print '</td><td class="CTableRow2"><b>'.$text.'</b>';
	print '<input type="hidden" name="source" value="'.dol_escape_htmltag($source).'">';
	print '<input type="hidden" name="ref" value="'.dol_escape_htmltag($invoice->id).'">';
	print '</td></tr>'."\n";

	// Amount
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Amount");
	print '</td><td class="CTableRow2">';
	$valtoshow = $amount;
	print '<b class="amount">'.price($valtoshow, 1, $langs, 1, -1, -1, $currency).'</b>';	// Price with currency
	print '<input type="hidden" name="amount" value="'.$valtoshow.'">';
	print '<input type="hidden" name="newamount" value="'.$valtoshow.'">';
	print '<input type="hidden" name="currency" value="'.$currency.'">';
	print '</td></tr>'."\n";

	// Tag
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow2"><b style="word-break: break-all;">'.$fulltag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="fulltag" value="'.$fulltag.'">';
	print '</td></tr>'."\n";

	// Shipping address
	$shipToName = $thirdparty->getFullName($langs);
	$shipToStreet = $thirdparty->address;
	$shipToCity = $thirdparty->town;
	$shipToState = $thirdparty->state_code;
	$shipToCountryCode = $thirdparty->country_code;
	$shipToZip = $thirdparty->zip;
	$shipToStreet2 = '';
	$phoneNum = $thirdparty->phone;
	if ($shipToName && $shipToStreet && $shipToCity && $shipToCountryCode && $shipToZip) {
		print '<!-- Shipping address information -->';
		print '<input type="hidden" name="shipToName" value="'.$shipToName.'">'."\n";
		print '<input type="hidden" name="shipToStreet" value="'.$shipToStreet.'">'."\n";
		print '<input type="hidden" name="shipToCity" value="'.$shipToCity.'">'."\n";
		print '<input type="hidden" name="shipToState" value="'.$shipToState.'">'."\n";
		print '<input type="hidden" name="shipToCountryCode" value="'.$shipToCountryCode.'">'."\n";
		print '<input type="hidden" name="shipToZip" value="'.$shipToZip.'">'."\n";
		print '<input type="hidden" name="shipToStreet2" value="'.$shipToStreet2.'">'."\n";
		print '<input type="hidden" name="phoneNum" value="'.$phoneNum.'">'."\n";
	} else {
		print '<!-- Shipping address not complete, so we don t use it -->'."\n";
	}
	print '<input type="hidden" name="thirdparty_id" value="'.$thirdparty->id.'">'."\n";
	print '<input type="hidden" name="email" value="'.$thirdparty->email.'">'."\n";
	$labeldesc = $langs->trans("PaymentSubscription");
	if (GETPOST('desc', 'alpha')) {
		$labeldesc = GETPOST('desc', 'alpha');
	}
	print '<input type="hidden" name="desc" value="'.dol_escape_htmltag($labeldesc).'">'."\n";
}

if ($source == 'boothlocation') {
	$found = true;
	$langs->load("members");

	if (GETPOST('fulltag', 'alpha')) {
		$fulltag = GETPOST('fulltag', 'alpha');
	} else {
		$fulltag = 'BOO='.GETPOST("booth").'.DAT='.dol_print_date(dol_now(), '%Y%m%d%H%M%S');
		if (!empty($TAG)) {
			$tag = $TAG;
			$fulltag .= '.TAG='.$TAG;
		}
	}
	$fulltag = dol_string_unaccent($fulltag);

	// Creditor
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Creditor");
	print '</td><td class="CTableRow2"><b>'.$creditor.'</b>';
	print '<input type="hidden" name="creditor" value="'.$creditor.'">';
	print '</td></tr>'."\n";

	// Debitor
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Attendee");
	print '</td><td class="CTableRow2"><b>';
	print $thirdparty->name;
	print '</b>';
	print '</td></tr>'."\n";

	// Object
	$text = '<b>'.$langs->trans("PaymentBoothLocation").'</b>';
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Designation");
	print '</td><td class="CTableRow2">'.$text;
	print '<input type="hidden" name="source" value="'.dol_escape_htmltag($source).'">';
	print '<input type="hidden" name="ref" value="'.dol_escape_htmltag($invoice->id).'">';
	print '</td></tr>'."\n";

	// Amount
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Amount");
	print '</td><td class="CTableRow2">';
	$valtoshow = $amount;
	print '<b class="amount">'.price($valtoshow, 1, $langs, 1, -1, -1, $currency).'</b>';	// Price with currency
	print '<input type="hidden" name="amount" value="'.$valtoshow.'">';
	print '<input type="hidden" name="newamount" value="'.$valtoshow.'">';
	print '<input type="hidden" name="currency" value="'.$currency.'">';
	print '</td></tr>'."\n";

	// Tag
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow2"><b style="word-break: break-all;">'.$fulltag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="fulltag" value="'.$fulltag.'">';
	print '</td></tr>'."\n";

	// Shipping address
	$shipToName = $thirdparty->getFullName($langs);
	$shipToStreet = $thirdparty->address;
	$shipToCity = $thirdparty->town;
	$shipToState = $thirdparty->state_code;
	$shipToCountryCode = $thirdparty->country_code;
	$shipToZip = $thirdparty->zip;
	$shipToStreet2 = '';
	$phoneNum = $thirdparty->phone;
	if ($shipToName && $shipToStreet && $shipToCity && $shipToCountryCode && $shipToZip) {
		print '<!-- Shipping address information -->';
		print '<input type="hidden" name="shipToName" value="'.$shipToName.'">'."\n";
		print '<input type="hidden" name="shipToStreet" value="'.$shipToStreet.'">'."\n";
		print '<input type="hidden" name="shipToCity" value="'.$shipToCity.'">'."\n";
		print '<input type="hidden" name="shipToState" value="'.$shipToState.'">'."\n";
		print '<input type="hidden" name="shipToCountryCode" value="'.$shipToCountryCode.'">'."\n";
		print '<input type="hidden" name="shipToZip" value="'.$shipToZip.'">'."\n";
		print '<input type="hidden" name="shipToStreet2" value="'.$shipToStreet2.'">'."\n";
		print '<input type="hidden" name="phoneNum" value="'.$phoneNum.'">'."\n";
	} else {
		print '<!-- Shipping address not complete, so we don t use it -->'."\n";
	}
	print '<input type="hidden" name="thirdparty_id" value="'.$thirdparty->id.'">'."\n";
	print '<input type="hidden" name="email" value="'.$thirdparty->email.'">'."\n";
	$labeldesc = $langs->trans("PaymentSubscription");
	if (GETPOST('desc', 'alpha')) {
		$labeldesc = GETPOST('desc', 'alpha');
	}
	print '<input type="hidden" name="desc" value="'.dol_escape_htmltag($labeldesc).'">'."\n";
}

if (!$found && !$mesg) {
	$mesg = $langs->trans("ErrorBadParameters");
}

if ($mesg) {
	print '<tr><td align="center" colspan="2"><br><div class="warning">'.dol_escape_htmltag($mesg, 1, 1, 'br').'</div></td></tr>'."\n";
}

print '</table>'."\n";
print "\n";


// Show all payment mode buttons (Stripe, Paypal, ...)
if ($action != 'dopayment') {
	if ($found && !$error) {	// We are in a management option and no error
		// Check status of the object (Invoice) to verify if it is paid by external payment modules (ie Payzen, ...)
		$parameters = [
			'source' => $source,
			'object' => $object
		];
		$reshook = $hookmanager->executeHooks('doCheckStatus', $parameters, $object, $action);
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		} elseif ($reshook > 0) {
			print $hookmanager->resPrint;
		}

		if ($source == 'order' && $object->billed) {
			print '<br><br><span class="amountpaymentcomplete size12x">'.$langs->trans("OrderBilled").'</span>';
		} elseif ($source == 'invoice' && $object->paye) {
			print '<br><br><span class="amountpaymentcomplete size12x">'.$langs->trans("InvoicePaid").'</span>';
		} elseif ($source == 'donation' && $object->paid) {
			print '<br><br><span class="amountpaymentcomplete size12x">'.$langs->trans("DonationPaid").'</span>';
		} else {
			// Membership can be paid and we still allow to make renewal
			if (($source == 'member' || $source == 'membersubscription') && $object->datefin > dol_now()) {
				$langs->load("members");
				print '<br><span class="amountpaymentcomplete size12x">';
				$s = $langs->trans("MembershipPaid", '{s1}');
				print str_replace('{s1}', '<span class="nobold">'.dol_print_date($object->datefin, 'day').'</span>', $s);
				print '</span><br>';
				print '<div class="opacitymedium margintoponly">'.$langs->trans("PaymentWillBeRecordedForNextPeriod").'</div>';
				print '<br>';
			}

			// Buttons for all payments registration methods

			// This hook is used to add Button to newpayment.php for external payment modules (ie Payzen, ...)
			$parameters = [
				'paymentmethod' => $paymentmethod
			];
			$reshook = $hookmanager->executeHooks('doAddButton', $parameters, $object, $action);
			if ($reshook < 0) {
				setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
			} elseif ($reshook > 0) {
				print $hookmanager->resPrint;
			}

			if ((empty($paymentmethod) || $paymentmethod == 'paybox') && isModEnabled('paybox')) {
				print '<div class="button buttonpayment" id="div_dopayment_paybox"><span class="fa fa-credit-card"></span> <input class="" type="submit" id="dopayment_paybox" name="dopayment_paybox" value="'.$langs->trans("PayBoxDoPayment").'">';
				print '<br>';
				print '<span class="buttonpaymentsmall">'.$langs->trans("CreditOrDebitCard").'</span>';
				print '</div>';
				print '<script>
						$( document ).ready(function() {
							$("#div_dopayment_paybox").click(function(){
								$("#dopayment_paybox").click();
							});
							$("#dopayment_paybox").click(function(e){
								$("#div_dopayment_paybox").css( \'cursor\', \'wait\' );
							    e.stopPropagation();
							});
						});
					  </script>
				';
			}

			if ((empty($paymentmethod) || $paymentmethod == 'stripe') && isModEnabled('stripe')) {
				$showbutton = 1;
				if (getDolGlobalString(strtoupper($source).'_FORCE_DISABLE_STRIPE')) {	// Example: MEMBER_FORCE_DISABLE_STRIPE
					$showbutton = 0;
				}

				if ($showbutton) {
					print '<div class="button buttonpayment" id="div_dopayment_stripe"><span class="fa fa-credit-card"></span> <input class="" type="submit" id="dopayment_stripe" name="dopayment_stripe" value="'.$langs->trans("StripeDoPayment").'">';
					print '<input type="hidden" name="noidempotency" value="'.GETPOSTINT('noidempotency').'">';
					print '<br>';
					print '<span class="buttonpaymentsmall">'.$langs->trans("CreditOrDebitCard").'</span>';
					print '</div>';
					print '<script>
							$( document ).ready(function() {
								$("#div_dopayment_stripe").click(function(){
									$("#dopayment_stripe").click();
								});
								$("#dopayment_stripe").click(function(e){
									$("#div_dopayment_stripe").css( \'cursor\', \'wait\' );
								    e.stopPropagation();
									return true;
								});
							});
						  </script>
					';
				}
			}

			if ((empty($paymentmethod) || $paymentmethod == 'paypal') && isModEnabled('paypal')) {
				if (!getDolGlobalString('PAYPAL_API_INTEGRAL_OR_PAYPALONLY')) {
					$conf->global->PAYPAL_API_INTEGRAL_OR_PAYPALONLY = 'integral';
				}

				$showbutton = 1;
				if (getDolGlobalString(strtoupper($source).'_FORCE_DISABLE_PAYPAL')) {	// Example: MEMBER_FORCE_DISABLE_PAYPAL
					$showbutton = 0;
				}

				if ($showbutton) {
					print '<div class="button buttonpayment" id="div_dopayment_paypal">';
					if (getDolGlobalString('PAYPAL_API_INTEGRAL_OR_PAYPALONLY') != 'integral') {
						print '<div style="line-height: 1em">&nbsp;</div>';
					}
					print '<span class="fab fa-paypal"></span> <input class="" type="submit" id="dopayment_paypal" name="dopayment_paypal" value="'.$langs->trans("PaypalDoPayment").'">';
					if (getDolGlobalString('PAYPAL_API_INTEGRAL_OR_PAYPALONLY') == 'integral') {
						print '<br>';
						print '<span class="buttonpaymentsmall">'.$langs->trans("CreditOrDebitCard").'</span><span class="buttonpaymentsmall"> - </span>';
						print '<span class="buttonpaymentsmall">'.$langs->trans("PayPalBalance").'</span>';
					}
					if (getDolGlobalString('PAYPAL_API_INTEGRAL_OR_PAYPALONLY') == 'paypalonly') {
						//print '<br>';
						//print '<span class="buttonpaymentsmall">'.$langs->trans("PayPalBalance").'"></span>';
					}
					print '</div>';
					print '<script>
							$( document ).ready(function() {
								$("#div_dopayment_paypal").click(function(){
									$("#dopayment_paypal").click();
								});
								$("#dopayment_paypal").click(function(e){
									$("#div_dopayment_paypal").css( \'cursor\', \'wait\' );
								    e.stopPropagation();
									return true;
								});
							});
						  </script>
					';
				}
			}
		}
	} else {
		dol_print_error_email('ERRORNEWPAYMENT');
	}
} else {
	// Print
}

print '</td></tr>'."\n";

print '</table>'."\n";

print '</form>'."\n";
print '</div>'."\n";

print '<br>';



// Add more content on page for some services
if (preg_match('/^dopayment/', $action)) {			// If we chose/clicked on the payment mode
	// Save some data for the paymentok
	$remoteip = getUserRemoteIP();
	$_SESSION["currencyCodeType"] = $currency;
	$_SESSION["FinalPaymentAmt"] = $amount;
	$_SESSION['ipaddress'] = ($remoteip ? $remoteip : 'unknown'); // Payer ip
	$_SESSION["paymentType"] = '';

	// For Stripe
	if (GETPOST('dopayment_stripe', 'alpha')) {
		// Personalized checkout
		print '<style>
	    /**
	     * The CSS shown here will not be introduced in the Quickstart guide, but shows
	     * how you can use CSS to style your Element s container.
	     */
	    .StripeElement {
	        background-color: white;
	        padding: 8px 12px;
	        border-radius: 4px;
	        border: 1px solid transparent;
	        box-shadow: 0 1px 3px 0 #e6ebf1;
	        -webkit-transition: box-shadow 150ms ease;
	        transition: box-shadow 150ms ease;
	    }

	    .StripeElement--focus {
	        box-shadow: 0 1px 3px 0 #cfd7df;
	    }

	    .StripeElement--invalid {
	        border-color: #fa755a;
	    }

	    .StripeElement--webkit-autofill {
	        background-color: #fefde5 !important;
	    }
	    </style>';

		//print '<br>';

		print '<!-- Show Stripe form payment-form STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION = ' . getDolGlobalString('STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION').' STRIPE_USE_NEW_CHECKOUT = ' . getDolGlobalString('STRIPE_USE_NEW_CHECKOUT').' -->'."\n";
		print '<form action="'.$_SERVER['REQUEST_URI'].'" method="POST" id="payment-form">'."\n";

		print '<input type="hidden" name="token" value="'.newToken().'">'."\n";
		print '<input type="hidden" name="dopayment_stripe" value="1">'."\n";
		print '<input type="hidden" name="action" value="charge">'."\n";
		print '<input type="hidden" name="tag" value="'.$TAG.'">'."\n";
		print '<input type="hidden" name="s" value="'.$source.'">'."\n";
		print '<input type="hidden" name="ref" value="'.$REF.'">'."\n";
		print '<input type="hidden" name="fulltag" value="'.$FULLTAG.'">'."\n";
		print '<input type="hidden" name="suffix" value="'.$suffix.'">'."\n";
		print '<input type="hidden" name="securekey" value="'.$SECUREKEY.'">'."\n";
		print '<input type="hidden" name="e" value="'.$entity.'" />';
		print '<input type="hidden" name="amount" value="'.$amount.'">'."\n";
		print '<input type="hidden" name="currency" value="'.$currency.'">'."\n";
		print '<input type="hidden" name="forcesandbox" value="'.GETPOSTINT('forcesandbox').'" />';
		print '<input type="hidden" name="email" value="'.GETPOST('email', 'alpha').'" />';
		print '<input type="hidden" name="thirdparty_id" value="'.GETPOSTINT('thirdparty_id').'" />';
		print '<input type="hidden" name="lang" value="'.$getpostlang.'">';

		if (getDolGlobalString('STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION') || getDolGlobalString('STRIPE_USE_NEW_CHECKOUT')) {	// Use a SCA ready method
			require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';

			$service = 'StripeLive';
			$servicestatus = 1;
			if (!getDolGlobalString('STRIPE_LIVE') || GETPOST('forcesandbox', 'alpha')) {
				$service = 'StripeTest';
				$servicestatus = 0;
			}

			$stripe = new Stripe($db);
			$stripeacc = $stripe->getStripeAccount($service);
			$stripecu = null;
			if (is_object($object) && is_object($object->thirdparty)) {
				$stripecu = $stripe->customerStripe($object->thirdparty, $stripeacc, $servicestatus, 1);
			}

			if (getDolGlobalString('STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION')) {
				$noidempotency_key = (GETPOSTISSET('noidempotency') ? GETPOSTINT('noidempotency') : 0); // By default noidempotency is unset, so we must use a different tag/ref for each payment. If set, we can pay several times the same tag/ref.
				$paymentintent = $stripe->getPaymentIntent($amount, $currency, ($tag ? $tag : $fulltag), 'Stripe payment: '.$fulltag.(is_object($object) ? ' ref='.$object->ref : ''), $object, $stripecu, $stripeacc, $servicestatus, 0, 'automatic', false, null, 0, $noidempotency_key);
				// The paymentintnent has status 'requires_payment_method' (even if paymentintent was already paid)
				//var_dump($paymentintent);
				if ($stripe->error) {
					setEventMessages($stripe->error, null, 'errors');
				}
			}
		}

		// Note:
		// $conf->global->STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION = 1 = use intent object (default value, suggest card payment mode only)
		// $conf->global->STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION = 2 = use payment object (suggest both card payment mode but also sepa, ...)

		print '
        <table id="dolpaymenttable" summary="Payment form" class="center centpercent">
        <tbody><tr><td class="textpublicpayment">';

		if (getDolGlobalString('STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION')) {
			print '<div id="payment-request-button"><!-- A Stripe Element will be inserted here. --></div>';
		}

		print '<div class="form-row '.(getDolGlobalInt('STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION') == 2 ? 'center' : 'left').'">';
		if (getDolGlobalInt('STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION') == 1) {
			print '<label for="card-element">'.$langs->trans("CreditOrDebitCard").'</label>';
			print '<br><input id="cardholder-name" class="marginbottomonly" name="cardholder-name" value="" type="text" placeholder="'.$langs->trans("CardOwner").'" autocomplete="off" autofocus required>';
		}

		if (getDolGlobalInt('STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION') == 1) {
			print '<div id="card-element">
	        <!-- a Stripe Element will be inserted here. -->
    	    </div>';
		}
		if (getDolGlobalInt('STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION') == 2) {
			print '<div id="payment-element">
			<!-- a Stripe Element will be inserted here. -->
			</div>';
		}

		print '<!-- Used to display form errors -->
        <div id="card-errors" role="alert"></div>
        </div>';

		print '<br>';
		print '<button class="button buttonpayment" style="text-align: center; padding-left: 0; padding-right: 0;" id="buttontopay" data-secret="'.(is_object($paymentintent) ? $paymentintent->client_secret : '').'">'.$langs->trans("ValidatePayment").'</button>';
		print '<img id="hourglasstopay" class="hidden" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/working.gif">';

		print '</td></tr></tbody>';
		print '</table>';
		//}

		if (getDolGlobalString('STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION')) {
			if (empty($paymentintent)) {
				print '<center>'.$langs->trans("Error").'</center>';
			} else {
				print '<input type="hidden" name="paymentintent_id" value="'.$paymentintent->id.'">';
				//$_SESSION["paymentintent_id"] = $paymentintent->id;
			}
		}

		print '</form>'."\n";


		// JS Code for Stripe
		if (empty($stripearrayofkeys['publishable_key'])) {
			$langs->load("errors");
			print info_admin($langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("Stripe")), 0, 0, 'error');
		} else {
			print '<!-- JS Code for Stripe components -->';
			print '<script src="https://js.stripe.com/v3/"></script>'."\n";
			print '<!-- urllogofull = '.$urllogofull.' -->'."\n";

			// Code to ask the credit card. This use the default "API version". No way to force API version when using JS code.
			print '<script type="text/javascript">'."\n";

			if (getDolGlobalString('STRIPE_USE_NEW_CHECKOUT')) {
				$amountstripe = $amount;

				// Correct the amount according to unit of currency
				// See https://support.stripe.com/questions/which-zero-decimal-currencies-does-stripe-support
				$arrayzerounitcurrency = array('BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'VND', 'VUV', 'XAF', 'XOF', 'XPF');
				if (!in_array($currency, $arrayzerounitcurrency)) {
					$amountstripe *= 100;
				}

				$ipaddress = getUserRemoteIP();
				$metadata = array('dol_version' => DOL_VERSION, 'dol_entity' => $conf->entity, 'ipaddress' => $ipaddress);
				if (is_object($object)) {
					$metadata['dol_type'] = $object->element;
					$metadata['dol_id'] = $object->id;

					$ref = $object->ref;
				}

				try {
					$arrayforpaymentintent = array(
						'description' => 'Stripe payment: '.$FULLTAG.($ref ? ' ref='.$ref : ''),
						"metadata" => $metadata
					);
					if ($TAG) {
						$arrayforpaymentintent["statement_descriptor"] = dol_trunc($TAG, 10, 'right', 'UTF-8', 1); // 22 chars that appears on bank receipt (company + description)
					}

					$arrayforcheckout = array(
						'payment_method_types' => array('card'),
						'line_items' => array(array(
							'price_data' => array(
								'currency' => $currency,
								'unit_amount' => $amountstripe,
								'product_data' => array(
									'name' => $langs->transnoentitiesnoconv("Payment").' '.$TAG, // Label of product line
									'description' => 'Stripe payment: '.$FULLTAG.($ref ? ' ref='.$ref : ''),
									//'images' => array($urllogofull),
								),
							),
							'quantity' => 1,
						)),
						'mode' => 'payment',
						'client_reference_id' => $FULLTAG,
						'success_url' => $urlok,
						'cancel_url' => $urlko,
						'payment_intent_data' => $arrayforpaymentintent
					);
					if ($stripecu) {
						$arrayforcheckout['customer'] = $stripecu;
					} elseif (GETPOST('email', 'alpha') && isValidEmail(GETPOST('email', 'alpha'))) {
						$arrayforcheckout['customer_email'] = GETPOST('email', 'alpha');
					}
					$sessionstripe = \Stripe\Checkout\Session::create($arrayforcheckout);

					$remoteip = getUserRemoteIP();

					// Save some data for the paymentok
					$_SESSION["currencyCodeType"] = $currency;
					$_SESSION["paymentType"] = '';
					$_SESSION["FinalPaymentAmt"] = $amount;
					$_SESSION['ipaddress'] = ($remoteip ? $remoteip : 'unknown'); // Payer ip
					$_SESSION['payerID'] = is_object($stripecu) ? $stripecu->id : '';
					$_SESSION['TRANSACTIONID'] = $sessionstripe->id;
				} catch (Exception $e) {
					print $e->getMessage();
				} ?>
			   // Code for payment with option STRIPE_USE_NEW_CHECKOUT set

			// Create a Stripe client.
				<?php
				if (empty($stripeacc)) {
					?>
			var stripe = Stripe('<?php echo $stripearrayofkeys['publishable_key']; // Defined into config.php?>');
					<?php
				} else {
					?>
			var stripe = Stripe('<?php echo $stripearrayofkeys['publishable_key']; // Defined into config.php?>', { stripeAccount: '<?php echo $stripeacc; ?>' });
					<?php
				} ?>

			// Create an instance of Elements
			var elements = stripe.elements();

			// Custom styling can be passed to options when creating an Element.
			// (Note that this demo uses a wider set of styles than the guide below.)
			var style = {
			  base: {
				color: '#32325d',
				lineHeight: '24px',
				fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
				fontSmoothing: 'antialiased',
				fontSize: '16px',
				'::placeholder': {
				  color: '#aab7c4'
				}
			  },
			  invalid: {
				color: '#fa755a',
				iconColor: '#fa755a'
			  }
			}

			var cardElement = elements.create('card', {style: style});

			// Comment this to avoid the redirect
			stripe.redirectToCheckout({
			  // Make the id field from the Checkout Session creation API response
			  // available to this file, so you can provide it as parameter here
			  // instead of the {{CHECKOUT_SESSION_ID}} placeholder.
			  sessionId: '<?php print $sessionstripe->id; ?>'
			}).then(function (result) {
			  // If `redirectToCheckout` fails due to a browser or network
			  // error, display the localized error message to your customer
			  // using `result.error.message`.
			});


				<?php
			} elseif (getDolGlobalString('STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION')) {
				?>
			// Code for payment with option STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION set to 1 or 2

			// Create a Stripe client.
				<?php
				if (empty($stripeacc)) {
					?>
			var stripe = Stripe('<?php echo $stripearrayofkeys['publishable_key']; // Defined into config.php?>');
					<?php
				} else {
					?>
			var stripe = Stripe('<?php echo $stripearrayofkeys['publishable_key']; // Defined into config.php?>', { stripeAccount: '<?php echo $stripeacc; ?>' });
					<?php
				} ?>

				<?php
				if (getDolGlobalInt('STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION') == 2) {
					?>
			var cardButton = document.getElementById('buttontopay');
			var clientSecret = cardButton.dataset.secret;
			var options = { clientSecret: clientSecret };

			// Create an instance of Elements
			var elements = stripe.elements(options);
					<?php
				} else {
					?>
			// Create an instance of Elements
			var elements = stripe.elements();
					<?php
				} ?>

			// Custom styling can be passed to options when creating an Element.
			// (Note that this demo uses a wider set of styles than the guide below.)
			var style = {
			  base: {
				color: '#32325d',
				lineHeight: '24px',
				fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
				fontSmoothing: 'antialiased',
				fontSize: '16px',
				'::placeholder': {
				  color: '#aab7c4'
				}
			  },
			  invalid: {
				color: '#fa755a',
				iconColor: '#fa755a'
			  }
			}

				<?php
				if (getDolGlobalInt('STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION') == 2) {
					?>
			var paymentElement = elements.create("payment");

			// Add an instance of the card Element into the `card-element` <div>
			paymentElement.mount("#payment-element");

			// Handle form submission
			var cardButton = document.getElementById('buttontopay');

			cardButton.addEventListener('click', function(event) {
				console.log("We click on buttontopay");
				event.preventDefault();

					/* Disable button to pay and show hourglass cursor */
					jQuery('#hourglasstopay').show();
					jQuery('#buttontopay').hide();

					stripe.confirmPayment({
						elements,confirmParams: {
						return_url: '<?php echo $urlok; ?>',
						payment_method_data: {
							billing_details: {
								name: 'test'
								<?php if (GETPOST('email', 'alpha') || (is_object($object) && is_object($object->thirdparty) && !empty($object->thirdparty->email))) {
									?>, email: '<?php echo dol_escape_js(GETPOST('email', 'alpha') ? GETPOST('email', 'alpha') : $object->thirdparty->email); ?>'<?php
								} ?>
								<?php if (is_object($object) && is_object($object->thirdparty) && !empty($object->thirdparty->phone)) {
									?>, phone: '<?php echo dol_escape_js($object->thirdparty->phone); ?>'<?php
								} ?>
								<?php if (is_object($object) && is_object($object->thirdparty)) {
									?>, address: {
									city: '<?php echo dol_escape_js($object->thirdparty->town); ?>',
									<?php if ($object->thirdparty->country_code) {
										?>country: '<?php echo dol_escape_js($object->thirdparty->country_code); ?>',<?php
									} ?>
									line1: '<?php echo dol_escape_js(preg_replace('/\s\s+/', ' ', $object->thirdparty->address)); ?>',
									postal_code: '<?php echo dol_escape_js($object->thirdparty->zip); ?>'
									}
									<?php
								} ?>
							}
							},
							save_payment_method:<?php if ($stripecu) {
								print 'true';
												} else {
													print 'false';
												} ?>	/* true when a customer was provided when creating payment intent. true ask to save the card */
						},
					}
					).then(function(result) {
						console.log(result);
						if (result.error) {
							console.log("Error on result of handleCardPayment");
							jQuery('#buttontopay').show();
							jQuery('#hourglasstopay').hide();
							// Inform the user if there was an error
							var errorElement = document.getElementById('card-errors');
							console.log(result);
							errorElement.textContent = result.error.message;
						} else {
							// The payment has succeeded. Display a success message.
							console.log("No error on result of handleCardPayment, so we submit the form");
							// Submit the form
							jQuery('#buttontopay').hide();
							jQuery('#hourglasstopay').show();
							// Send form (action=charge that will do nothing)
							jQuery('#payment-form').submit();
						}
					});

			});
					<?php
				} else {
					?>
			var cardElement = elements.create('card', {style: style});

			// Add an instance of the card Element into the `card-element` <div>
			cardElement.mount('#card-element');

			// Handle real-time validation errors from the card Element.
			cardElement.addEventListener('change', function(event) {
				var displayError = document.getElementById('card-errors');
				  if (event.error) {
					  console.log("Show event error (like 'Incorrect card number', ...)");
					displayError.textContent = event.error.message;
				  } else {
					  console.log("Reset error message");
					displayError.textContent = '';
				  }
			});

			// Handle form submission
			var cardholderName = document.getElementById('cardholder-name');
			var cardButton = document.getElementById('buttontopay');
			var clientSecret = cardButton.dataset.secret;

			cardButton.addEventListener('click', function(event) {
				console.log("We click on buttontopay");
				event.preventDefault();

				if (cardholderName.value == '')
				{
					console.log("Field Card holder is empty");
					var displayError = document.getElementById('card-errors');
					displayError.textContent = '<?php print dol_escape_js($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CardOwner"))); ?>';
				}
				else
				{
					/* Disable button to pay and show hourglass cursor */
					jQuery('#hourglasstopay').show();
					jQuery('#buttontopay').hide();

					stripe.handleCardPayment(
					clientSecret, cardElement, {
						payment_method_data: {
							billing_details: {
								name: cardholderName.value
								<?php if (GETPOST('email', 'alpha') || (is_object($object) && is_object($object->thirdparty) && !empty($object->thirdparty->email))) {
									?>, email: '<?php echo dol_escape_js(GETPOST('email', 'alpha') ? GETPOST('email', 'alpha') : $object->thirdparty->email); ?>'<?php
								} ?>
								<?php if (is_object($object) && is_object($object->thirdparty) && !empty($object->thirdparty->phone)) {
									?>, phone: '<?php echo dol_escape_js($object->thirdparty->phone); ?>'<?php
								} ?>
								<?php if (is_object($object) && is_object($object->thirdparty)) {
									?>, address: {
									city: '<?php echo dol_escape_js($object->thirdparty->town); ?>',
									<?php if ($object->thirdparty->country_code) {
										?>country: '<?php echo dol_escape_js($object->thirdparty->country_code); ?>',<?php
									} ?>
									line1: '<?php echo dol_escape_js(preg_replace('/\s\s+/', ' ', $object->thirdparty->address)); ?>',
									postal_code: '<?php echo dol_escape_js($object->thirdparty->zip); ?>'
									}
									<?php
								} ?>
							}
							},
							save_payment_method:<?php if ($stripecu) {
								print 'true';
												} else {
													print 'false';
												} ?>	/* true when a customer was provided when creating payment intent. true ask to save the card */
					}
					).then(function(result) {
						console.log(result);
						if (result.error) {
							console.log("Error on result of handleCardPayment");
							jQuery('#buttontopay').show();
							jQuery('#hourglasstopay').hide();
							// Inform the user if there was an error
							var errorElement = document.getElementById('card-errors');
							errorElement.textContent = result.error.message;
						} else {
							// The payment has succeeded. Display a success message.
							console.log("No error on result of handleCardPayment, so we submit the form");
							// Submit the form
							jQuery('#buttontopay').hide();
							jQuery('#hourglasstopay').show();
							// Send form (action=charge that will do nothing)
							jQuery('#payment-form').submit();
						}
					});
				}
			});
					<?php
				} ?>

				<?php
			}

			print '</script>';
		}
	}

	// For any other payment services
	// This hook can be used to show the embedded form to make payments with external payment modules (ie Payzen, ...)
	$parameters = [
		'paymentmethod' => $paymentmethod,
		'amount' => $amount,
		'currency' => $currency,
		'tag' => GETPOST("tag", 'alpha'),
		'dopayment' => GETPOST('dopayment', 'alpha')
	];
	$reshook = $hookmanager->executeHooks('doPayment', $parameters, $object, $action);
	if ($reshook < 0) {
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	} elseif ($reshook > 0) {
		print $hookmanager->resPrint;
	}
}

if (!$ws) {
	htmlPrintOnlineFooter($mysoc, $langs, 1, $suffix, $object);
}

llxFooter('', 'public');

$db->close();
