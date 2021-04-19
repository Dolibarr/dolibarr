<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2018	    Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2018-2019	Thibault FOUCART	    <support@ptibogxiv.net>
 * Copyright (C) 2021		Waël Almoman	    	<info@almoman.com>
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
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : (!empty($_GET['e']) ? (int) $_GET['e'] : (!empty($_POST['e']) ? (int) $_POST['e'] : 1))));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societeaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
// Hook to be used by external payment modules (ie Payzen, ...)
include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
$hookmanager = new HookManager($db);
$hookmanager->initHooks(array('newpayment'));

// For encryption
global $dolibarr_main_instance_unique_id;

// Load translation files
$langs->loadLangs(array("main", "other", "dict", "bills", "companies", "errors", "paybox", "paypal", "stripe")); // File with generic data

// Security check
// No check on module enabled. Done later according to $validpaymentmethod

$action = GETPOST('action', 'aZ09');
$id = dol_decode(GETPOST('id'), $dolibarr_main_instance_unique_id);

// Define $urlwithroot
//$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
//$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
$urlwithroot = DOL_MAIN_URL_ROOT; // This is to use same domain name than current. For Paypal payment, we can use internal URL like localhost.


/*
 * Actions
 */


/*
 * View
 */

$head = '';
if (!empty($conf->global->ONLINE_PAYMENT_CSS_URL)) {
	$head = '<link rel="stylesheet" type="text/css" href="'.$conf->global->ONLINE_PAYMENT_CSS_URL.'?lang='.$langs->defaultlang.'">'."\n";
}

$conf->dol_hide_topmenu = 1;
$conf->dol_hide_leftmenu = 1;

$replacemainarea = (empty($conf->dol_hide_leftmenu) ? '<div>' : '').'<div>';
llxHeader($head, $langs->trans("PaymentForm"), '', '', 0, 0, '', '', '', 'onlinepaymentbody', $replacemainarea);


// Show sandbox warning
if ((empty($paymentmethod) || $paymentmethod == 'paypal') && !empty($conf->paypal->enabled) && (!empty($conf->global->PAYPAL_API_SANDBOX) || GETPOST('forcesandbox', 'int'))) {		// We can force sand box with param 'forcesandbox'
	dol_htmloutput_mesg($langs->trans('YouAreCurrentlyInSandboxMode', 'Paypal'), '', 'warning');
}
if ((empty($paymentmethod) || $paymentmethod == 'stripe') && !empty($conf->stripe->enabled) && (empty($conf->global->STRIPE_LIVE) || GETPOST('forcesandbox', 'int'))) {
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
print '<input type="hidden" name="forcesandbox" value="'.GETPOST('forcesandbox', 'int').'" />';
print "\n";


// Show logo (search order: logo defined by PAYMENT_LOGO_suffix, then PAYMENT_LOGO, then small company logo, large company logo, theme logo, common logo)
// Define logo and logosmall
$logosmall = $mysoc->logo_small;
$logo = $mysoc->logo;
$paramlogo = 'ONLINE_PAYMENT_LOGO_'.$suffix;
if (!empty($conf->global->$paramlogo)) {
	$logosmall = $conf->global->$paramlogo;
} elseif (!empty($conf->global->ONLINE_PAYMENT_LOGO)) {
	$logosmall = $conf->global->ONLINE_PAYMENT_LOGO;
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
if ($urllogo) {
	print '<div class="backgreypublicpayment">';
	print '<div class="logopublicpayment">';
	print '<img id="dolpaymentlogo" src="'.$urllogo.'"';
	print '>';
	print '</div>';
	if (empty($conf->global->MAIN_HIDE_POWERED_BY)) {
		print '<div class="poweredbypublicpayment opacitymedium right"><a class="poweredbyhref" href="https://www.dolibarr.org?utm_medium=website&utm_source=poweredby" target="dolibarr" rel="noopener">'.$langs->trans("PoweredBy").'<br><img class="poweredbyimg" src="'.DOL_URL_ROOT.'/theme/dolibarr_logo.svg" width="80px"></a></div>';
	}
	print '</div>';
}

print '<table id="dolpaymenttable" summary="Payment form" class="center">'."\n";

// Output introduction text
$text = '';
if (!empty($conf->global->PAYMENT_NEWFORM_TEXT)) {
	$langs->load("members");
	if (preg_match('/^\((.*)\)$/', $conf->global->PAYMENT_NEWFORM_TEXT, $reg)) {
		$text .= $langs->trans($reg[1])."<br>\n";
	} else {
		$text .= $conf->global->PAYMENT_NEWFORM_TEXT."<br>\n";
	}
	$text = '<tr><td align="center"><br>'.$text.'<br></td></tr>'."\n";
}
if (empty($text)) {
	$text .= '<tr><td class="textpublicpayment"><br><strong>'.$langs->trans("EvntOrgRegistrationWelcomeMessage").'</strong></td></tr>'."\n";
	$text .= '<tr><td class="textpublicpayment">'.$langs->trans("EvntOrgRegistrationHelpMessage").' '.$id.'.<br><br></td></tr>'."\n";
}
print $text;

// Output payment summary form
print '<tr><td align="center">';

$found = false;
$error = 0;
$var = false;

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

}


// Payment on customer order
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

	if (GETPOST('fulltag', 'alpha')) {
		$fulltag = GETPOST('fulltag', 'alpha');
	} else {
		$fulltag = 'ORD='.$order->id.'.CUS='.$order->thirdparty->id;
		if (!empty($TAG)) {
			$tag = $TAG; $fulltag .= '.TAG='.$TAG;
		}
	}
	$fulltag = dol_string_unaccent($fulltag);
}


// Payment on customer invoice
if ($source == 'invoice') {
	$found = true;
	$langs->load("bills");

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
			$tag = $TAG; $fulltag .= '.TAG='.$TAG;
		}
	}
	$fulltag = dol_string_unaccent($fulltag);

	$labeldesc = $langs->trans("Invoice").' '.$invoice->ref;
	if (GETPOST('desc', 'alpha')) {
		$labeldesc = GETPOST('desc', 'alpha');
	}
	print '<input type="hidden" name="desc" value="'.dol_escape_htmltag($labeldesc).'">'."\n";
}

if (!$found && !$mesg) {
	$mesg = $langs->trans("ErrorBadParameters");
}

if ($mesg) {
	print '<tr><td align="center" colspan="2"><br><div class="warning">'.dol_escape_htmltag($mesg).'</div></td></tr>'."\n";
}

print "\n";


// Show all payment mode buttons (Stripe, Paypal, ...)
print '<br>';
print '<input type="submit" value="'.$langs->trans("SuggestConference").'" id="suggestconference" class="button">';
print '<br><br>';
print '<input type="submit" value="'.$langs->trans("ViewAndVote").'" id="viewandvote" class="button">';
print '<br><br>';
print '<input type="submit" value="'.$langs->trans("SuggestBooth").'" id="suggestbooth" class="button">';


print '</td></tr>'."\n";

print '</table>'."\n";

print '</form>'."\n";
print '</div>'."\n";
print '<br>';



// Add more content on page for some services
if (preg_match('/^dopayment/', $action)) {			// If we choosed/click on the payment mode
	// Stripe
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

		print '<br>';

		print '<!-- Form payment-form STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION = '.$conf->global->STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION.' STRIPE_USE_NEW_CHECKOUT = '.$conf->global->STRIPE_USE_NEW_CHECKOUT.' -->'."\n";
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
		print '<input type="hidden" name="forcesandbox" value="'.GETPOST('forcesandbox', 'int').'" />';
		print '<input type="hidden" name="email" value="'.GETPOST('email', 'alpha').'" />';
		print '<input type="hidden" name="thirdparty_id" value="'.GETPOST('thirdparty_id', 'int').'" />';

		if (!empty($conf->global->STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION) || !empty($conf->global->STRIPE_USE_NEW_CHECKOUT)) {	// Use a SCA ready method
			require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';

			$service = 'StripeLive';
			$servicestatus = 1;
			if (empty($conf->global->STRIPE_LIVE) || GETPOST('forcesandbox', 'alpha')) {
				$service = 'StripeTest';
				$servicestatus = 0;
			}

			$stripe = new Stripe($db);
			$stripeacc = $stripe->getStripeAccount($service);
			$stripecu = null;
			if (is_object($object) && is_object($object->thirdparty)) {
				$stripecu = $stripe->customerStripe($object->thirdparty, $stripeacc, $servicestatus, 1);
			}

			if (!empty($conf->global->STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION)) {
				$noidempotency_key = (GETPOSTISSET('noidempotency') ? GETPOST('noidempotency', 'int') : 0); // By default noidempotency is unset, so we must use a different tag/ref for each payment. If set, we can pay several times the same tag/ref.
				$paymentintent = $stripe->getPaymentIntent($amount, $currency, $tag, 'Stripe payment: '.$fulltag.(is_object($object) ? ' ref='.$object->ref : ''), $object, $stripecu, $stripeacc, $servicestatus, 0, 'automatic', false, null, 0, $noidempotency_key);
				// The paymentintnent has status 'requires_payment_method' (even if paymentintent was already paid)
				//var_dump($paymentintent);
				if ($stripe->error) {
					setEventMessages($stripe->error, null, 'errors');
				}
			}
		}

		//if (empty($conf->global->STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION) || ! empty($paymentintent))
		//{
		print '
        <table id="dolpaymenttable" summary="Payment form" class="center">
        <tbody><tr><td class="textpublicpayment">';

		if (!empty($conf->global->STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION)) {
			print '<div id="payment-request-button"><!-- A Stripe Element will be inserted here. --></div>';
		}

		print '<div class="form-row left">';
		print '<label for="card-element">'.$langs->trans("CreditOrDebitCard").'</label>';

		if (!empty($conf->global->STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION)) {
			print '<br><input id="cardholder-name" class="marginbottomonly" name="cardholder-name" value="" type="text" placeholder="'.$langs->trans("CardOwner").'" autocomplete="off" autofocus required>';
		}

		print '<div id="card-element">
        <!-- a Stripe Element will be inserted here. -->
        </div>';

		print '<!-- Used to display form errors -->
        <div id="card-errors" role="alert"></div>
        </div>';

		print '<br>';
		print '<button class="button buttonpayment" style="text-align: center; padding-left: 0; padding-right: 0;" id="buttontopay" data-secret="'.(is_object($paymentintent) ? $paymentintent->client_secret : '').'">'.$langs->trans("ValidatePayment").'</button>';
		print '<img id="hourglasstopay" class="hidden" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/working.gif">';

		print '</td></tr></tbody>';
		print '</table>';
		//}

		if (!empty($conf->global->STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION)) {
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
			print '<script type="text/javascript" language="javascript">'."\n";

			if (!empty($conf->global->STRIPE_USE_NEW_CHECKOUT)) {
				$amountstripe = $amount;

				// Correct the amount according to unit of currency
				// See https://support.stripe.com/questions/which-zero-decimal-currencies-does-stripe-support
				$arrayzerounitcurrency = array('BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'VND', 'VUV', 'XAF', 'XOF', 'XPF');
				if (!in_array($currency, $arrayzerounitcurrency)) {
					$amountstripe = $amountstripe * 100;
				}

				$ipaddress = getUserRemoteIP();
				$metadata = array('dol_version'=>DOL_VERSION, 'dol_entity'=>$conf->entity, 'ipaddress'=>$ipaddress);
				if (is_object($object)) {
					$metadata['dol_type'] = $object->element;
					$metadata['dol_id'] = $object->id;

					$ref = $object->ref;
				}

				try {
					$arrayforpaymentintent = array(
						'description'=>'Stripe payment: '.$FULLTAG.($ref ? ' ref='.$ref : ''),
						"metadata" => $metadata
					);
					if ($TAG) {
						$arrayforpaymentintent["statement_descriptor"] = dol_trunc($TAG, 10, 'right', 'UTF-8', 1); // 22 chars that appears on bank receipt (company + description)
					}

					$arrayforcheckout = array(
						'payment_method_types' => array('card'),
						'line_items' => array(array(
							'name' => $langs->transnoentitiesnoconv("Payment").' '.$TAG, // Label of product line
							'description' => 'Stripe payment: '.$FULLTAG.($ref ? ' ref='.$ref : ''),
							'amount' => $amountstripe,
							'currency' => $currency,
							//'images' => array($urllogofull),
							'quantity' => 1,
						)),
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
				}
				?>
				// Code for payment with option STRIPE_USE_NEW_CHECKOUT set

				// Create a Stripe client.
				var stripe = Stripe('<?php echo $stripearrayofkeys['publishable_key']; // Defined into config.php ?>');

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
				};

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
			} elseif (!empty($conf->global->STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION)) {
				?>
				// Code for payment with option STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION set

				// Create a Stripe client.
				var stripe = Stripe('<?php echo $stripearrayofkeys['publishable_key']; // Defined into config.php ?>');

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
				};

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
				<?php } ?>
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
			} else {
				// Old method (not SCA ready)
				?>
				// Old code for payment with option STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION off and STRIPE_USE_NEW_CHECKOUT off

				// Create a Stripe client.
				var stripe = Stripe('<?php echo $stripearrayofkeys['publishable_key']; // Defined into config.php ?>');

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
				};

				// Create an instance of the card Element
				var card = elements.create('card', {style: style});

				// Add an instance of the card Element into the `card-element` <div>
				card.mount('#card-element');

				// Handle real-time validation errors from the card Element.
				card.addEventListener('change', function(event) {
				var displayError = document.getElementById('card-errors');
				if (event.error) {
				displayError.textContent = event.error.message;
				} else {
				displayError.textContent = '';
				}
				});

				// Handle form submission
				var form = document.getElementById('payment-form');
				console.log(form);
				form.addEventListener('submit', function(event) {
				event.preventDefault();
				<?php
				if (empty($conf->global->STRIPE_USE_3DSECURE)) {	// Ask credit card directly, no 3DS test
					?>
					/* Use token */
					stripe.createToken(card).then(function(result) {
					if (result.error) {
					// Inform the user if there was an error
					var errorElement = document.getElementById('card-errors');
					errorElement.textContent = result.error.message;
					} else {
					// Send the token to your server
					stripeTokenHandler(result.token);
					}
					});
					<?php
				} else // Ask credit card with 3DS test
				{
					?>
					/* Use 3DS source */
					stripe.createSource(card).then(function(result) {
					if (result.error) {
					// Inform the user if there was an error
					var errorElement = document.getElementById('card-errors');
					errorElement.textContent = result.error.message;
					} else {
					// Send the source to your server
					stripeSourceHandler(result.source);
					}
					});
					<?php
				}
				?>
				});


				/* Insert the Token into the form so it gets submitted to the server */
				function stripeTokenHandler(token) {
				// Insert the token ID into the form so it gets submitted to the server
				var form = document.getElementById('payment-form');

				var hiddenInput = document.createElement('input');
				hiddenInput.setAttribute('type', 'hidden');
				hiddenInput.setAttribute('name', 'stripeToken');
				hiddenInput.setAttribute('value', token.id);
				form.appendChild(hiddenInput);

				var hiddenInput2 = document.createElement('input');
				hiddenInput2.setAttribute('type', 'hidden');
				hiddenInput2.setAttribute('name', 'token');
				hiddenInput2.setAttribute('value', '<?php echo newToken(); ?>');
				form.appendChild(hiddenInput2);

				// Submit the form
				jQuery('#buttontopay').hide();
				jQuery('#hourglasstopay').show();
				console.log("submit token");
				form.submit();
				}

				/* Insert the Source into the form so it gets submitted to the server */
				function stripeSourceHandler(source) {
				// Insert the source ID into the form so it gets submitted to the server
				var form = document.getElementById('payment-form');

				var hiddenInput = document.createElement('input');
				hiddenInput.setAttribute('type', 'hidden');
				hiddenInput.setAttribute('name', 'stripeSource');
				hiddenInput.setAttribute('value', source.id);
				form.appendChild(hiddenInput);

				var hiddenInput2 = document.createElement('input');
				hiddenInput2.setAttribute('type', 'hidden');
				hiddenInput2.setAttribute('name', 'token');
				hiddenInput2.setAttribute('value', '<?php echo newToken(); ?>');
				form.appendChild(hiddenInput2);

				// Submit the form
				jQuery('#buttontopay').hide();
				jQuery('#hourglasstopay').show();
				console.log("submit source");
				form.submit();
				}

				<?php
			}

			print '</script>';
		}
	}
	// This hook is used to show the embedded form to make payments with external payment modules (ie Payzen, ...)
	$parameters = [
		'paymentmethod' => $paymentmethod,
		'amount' => price2num(GETPOST("newamount"), 'MT'),
		'tag' => GETPOST("tag", 'alpha'),
		'dopayment' => GETPOST('dopayment', 'alpha')
	];
	$reshook = $hookmanager->executeHooks('doPayment', $parameters, $object, $action);
}


htmlPrintOnlinePaymentFooter($mysoc, $langs, 1, $suffix, $object);

llxFooter('', 'public');

$db->close();
