<?php
/* Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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

// TODO Do we really need this page. We alread have a ipn.php page !

define("NOLOGIN", 1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK", 1);	// We accept to go on this page from external web site.

$entity=(! empty($_GET['entity']) ? (int) $_GET['entity'] : (! empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) define("DOLENTITY", $entity);

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/ccountry.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';

require_once DOL_DOCUMENT_ROOT.'/includes/stripe/init.php';
require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';

if (empty($conf->stripe->enabled)) accessforbidden('', 0, 0, 1);


// You can find your endpoint's secret in your webhook settings
if (isset($_GET['connect']))
{
	if (isset($_GET['test']))
	{
		$endpoint_secret =  $conf->global->STRIPE_TEST_WEBHOOK_CONNECT_KEY;
		$service = 'StripeTest';
		$servicestatus = 0;
	}
	else
	{
		$endpoint_secret =  $conf->global->STRIPE_LIVE_WEBHOOK_CONNECT_KEY;
		$service = 'StripeLive';
        $servicestatus = 1;
	}
}
else {
	if (isset($_GET['test']))
	{
		$endpoint_secret =  $conf->global->STRIPE_TEST_WEBHOOK_KEY;
		$service = 'StripeTest';
		$servicestatus = 0;
	}
	else
	{
		$endpoint_secret =  $conf->global->STRIPE_LIVE_WEBHOOK_KEY;
		$service = 'StripeLive';
		$servicestatus = 1;
	}
}



/*
 * Actions
 */

$langs->load("main");

// TODO Do we really need a user in setup just to have an name to fill an email topic when it is a technical system notification email
$user = new User($db);
$user->fetch($conf->global->STRIPE_USER_ACCOUNT_FOR_ACTIONS);
$user->getrights();

// list of  action
$stripe=new Stripe($db);

// Subject
$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $societeName = $conf->global->MAIN_APPLICATION_TITLE;


dol_syslog("Stripe confirm_payment was called");
dol_syslog("GET=".var_export($_GET, true));
dol_syslog("POST=".var_export($_POST, true));


header('Content-Type: application/json');

// retrieve json from POST body
$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str);

$intent = null;
try {
    if (isset($json_obj->payment_method_id)) {
        // Create the PaymentIntent
        $intent = \Stripe\PaymentIntent::create(array(
            'payment_method' => $json_obj->payment_method_id,
            'amount' => 1099,
            'currency' => 'eur',
            'confirmation_method' => 'manual',
            'confirm' => true,
        ));
    }
    if (isset($json_obj->payment_intent_id)) {
        $intent = \Stripe\PaymentIntent::retrieve(
            $json_obj->payment_intent_id
            );
        $intent->confirm();
    }
    generatePaymentResponse($intent);
} catch (\Stripe\Error\Base $e) {
    // Display error on client
    echo json_encode(array(
        'error' => $e->getMessage()
    ));
}

/*
 * generate payment response
 *
 * @param \Stripe\PaymentIntent $intent PaymentIntent
 * @return void
 */
function generatePaymentResponse($intent)
{
    if ($intent->status == 'requires_source_action' &&
        $intent->next_action->type == 'use_stripe_sdk') {
        // Tell the client to handle the action
        echo json_encode(array(
            'requires_action' => true,
            'payment_intent_client_secret' => $intent->client_secret
        ));
    } elseif ($intent->status == 'succeeded') {
        // The payment didn’t need any additional actions and completed!
        // Handle post-payment fulfillment

        // TODO

        echo json_encode(array(
            "success" => true
        ));
    } else {
        // Invalid status
        http_response_code(500);
        echo json_encode(array('error' => 'Invalid PaymentIntent status'));
    }
}
