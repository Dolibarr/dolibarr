<?php
/* Copyright (C) 2018-2021      Thibault FOUCART        <support@ptibogxiv.net>
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

$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/includes/stripe/stripe-php/init.php';
require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';


if (empty($conf->stripe->enabled)) {
	accessforbidden('', 0, 0, 1);
}

// You can find your endpoint's secret in your webhook settings
if (isset($_GET['connect'])) {
	if (isset($_GET['test'])) {
		$service = 'StripeTest';
		$servicestatus = 0;
	} else {
		$service = 'StripeLive';
		$servicestatus = 1;
	}
} else {
	if (isset($_GET['test'])) {
		$service = 'StripeTest';
		$servicestatus = 0;
	} else {
		$service = 'StripeLive';
		$servicestatus = 1;
	}
}

if (empty($endpoint_secret)) {
	//print 'Error: Setup of module Stripe not complete for mode '.$service.'. The WEBHOOK_KEY is not defined.';
	//http_response_code(400); // PHP 5.4 or greater
	//exit();
}

header('Content-Type: application/json');

try {
  // Be sure to authenticate the endpoint for creating connection tokens.
  // Force to use the correct API key
  global $stripearrayofkeysbyenv;
  \Stripe\Stripe::setApiKey($stripearrayofkeysbyenv[$servicestatus]['secret_key']);
  // The ConnectionToken's secret lets you connect to any Stripe Terminal reader
  // and take payments with your Stripe account.
  if (empty($stripeacc)) {				// If the Stripe connect account not set, we use common API usage
	$connectionToken = \Stripe\Terminal\ConnectionToken::create();
} else {
	$connectionToken = \Stripe\Terminal\ConnectionToken::create([
		'location' => ''
	  ], array("stripe_account" => $stripeacc));
}
  echo json_encode(array('secret' => $connectionToken->secret));

} catch (Error $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
