<?php
/* Copyright (C) 2021		Thibault FOUCART	<support@ptibogxiv.net>
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
 *	\file       htdocs/stripe/ajax/ajax.php
 *	\brief      Ajax action for Stipe ie: Terminal. Used when doing payment with Stripe Terminal in TakePOS.
 *
 *  Calling with
 *  action=getConnexionToken return a token of Stripe terminal
 *  action=createPaymentIntent generates a payment intent
 *  action=capturePaymentIntent generates a payment
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}

// Load Dolibarr environment
require '../../main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/includes/stripe/stripe-php/init.php';
require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

$action = GETPOST('action', 'aZ09');
$location = GETPOST('location', 'alphanohtml');
$stripeacc = GETPOST('stripeacc', 'alphanohtml');
$servicestatus = GETPOSTINT('servicestatus');
$amount = GETPOSTINT('amount');

if (!$user->hasRight('takepos', 'run')) {
	accessforbidden('Not allowed to use TakePOS');
}

$usestripeterminals = getDolGlobalString('STRIPE_LOCATION');
if (! $usestripeterminals) {
	accessforbidden('Feature to use Stripe terminals not enabled');
}


/*
 * View
 */

top_httphead('application/json');

if ($action == 'getConnexionToken') {
	try {
		// Be sure to authenticate the endpoint for creating connection tokens.
		// Force to use the correct API key
		global $stripearrayofkeysbyenv;
		\Stripe\Stripe::setApiKey($stripearrayofkeysbyenv[$servicestatus]['secret_key']);
		// The ConnectionToken's secret lets you connect to any Stripe Terminal reader
		// and take payments with your Stripe account.
		$array = array();
		if (isset($location) && !empty($location)) {
			$array['location'] = $location;
		}
		if (empty($stripeacc)) {				// If the Stripe connect account not set, we use common API usage
			$connectionToken = \Stripe\Terminal\ConnectionToken::create($array);
		} else {
			$connectionToken = \Stripe\Terminal\ConnectionToken::create($array, array("stripe_account" => $stripeacc));
		}
		echo json_encode(array('secret' => $connectionToken->secret));
	} catch (Error $e) {
		http_response_code(500);
		echo json_encode(['error' => $e->getMessage()]);
	}
} elseif ($action == 'createPaymentIntent') {
	try {
		$json_str = file_get_contents('php://input');
		$json_obj = json_decode($json_str);

		// For Terminal payments, the 'payment_method_types' parameter must include
		// 'card_present' and the 'capture_method' must be set to 'manual'
		$object = new Facture($db);
		$object->fetch($json_obj->invoiceid);
		$object->fetch_thirdparty();

		$fulltag='INV='.$object->id.'.CUS='.$object->thirdparty->id;
		$tag=null;
		$fulltag=dol_string_unaccent($fulltag);

		$stripe = new Stripe($db);
		$customer = $stripe->customerStripe($object->thirdparty, $stripeacc, $servicestatus, 1);

		$intent = $stripe->getPaymentIntent($json_obj->amount, $object->multicurrency_code, null, 'Stripe payment: '.$fulltag.(is_object($object) ? ' ref='.$object->ref : ''), $object, $customer, $stripeacc, $servicestatus, 1, 'terminal', false, null, 0, 1);

		echo json_encode(array('client_secret' => $intent->client_secret));
	} catch (Error $e) {
		http_response_code(500);
		echo json_encode(['error' => $e->getMessage()]);
	}
} elseif ($action == 'capturePaymentIntent') {
	try {
		// retrieve JSON from POST body
		$json_str = file_get_contents('php://input');
		$json_obj = json_decode($json_str);
		if (empty($stripeacc)) {				// If the Stripe connect account not set, we use common API usage
			$intent = \Stripe\PaymentIntent::retrieve($json_obj->id);
		} else {
			$intent = \Stripe\PaymentIntent::retrieve($json_obj->id, array("stripe_account" => $stripeacc));
		}
		$intent = $intent->capture();

		echo json_encode($intent);
	} catch (Error $e) {
		http_response_code(500);
		echo json_encode(['error' => $e->getMessage()]);
	}
}
