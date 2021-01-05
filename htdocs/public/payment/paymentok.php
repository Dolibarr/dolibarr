<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2013	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@inodbox.com>
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
 *     	\file       htdocs/public/payment/paymentok.php
 *		\ingroup    core
 *		\brief      File to show page after a successful payment
 *                  This page is called by payment system with url provided to it completed with parameter TOKEN=xxx
 *                  This token can be used to get more informations.
 */

if (!defined('NOLOGIN'))		define("NOLOGIN", 1); // This means this output page does not require to be logged.
if (!defined('NOCSRFCHECK'))	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
if (!defined('NOIPCHECK'))		define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
if (!defined('NOBROWSERNOTIF')) define('NOBROWSERNOTIF', '1');

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// TODO This should be useless. Because entity must be retrieve from object ref and not from url.
$entity = (!empty($_GET['e']) ? (int) $_GET['e'] : (!empty($_POST['e']) ? (int) $_POST['e'] : 1));
if (is_numeric($entity)) define("DOLENTITY", $entity);

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

if (!empty($conf->paypal->enabled))
{
	require_once DOL_DOCUMENT_ROOT.'/paypal/lib/paypal.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/paypal/lib/paypalfunctions.lib.php';
}

$langs->loadLangs(array("main", "other", "dict", "bills", "companies", "paybox", "paypal"));

// Clean parameters
if (!empty($conf->paypal->enabled))
{
	$PAYPAL_API_USER = "";
	if (!empty($conf->global->PAYPAL_API_USER)) $PAYPAL_API_USER = $conf->global->PAYPAL_API_USER;
	$PAYPAL_API_PASSWORD = "";
	if (!empty($conf->global->PAYPAL_API_PASSWORD)) $PAYPAL_API_PASSWORD = $conf->global->PAYPAL_API_PASSWORD;
	$PAYPAL_API_SIGNATURE = "";
	if (!empty($conf->global->PAYPAL_API_SIGNATURE)) $PAYPAL_API_SIGNATURE = $conf->global->PAYPAL_API_SIGNATURE;
	$PAYPAL_API_SANDBOX = "";
	if (!empty($conf->global->PAYPAL_API_SANDBOX)) $PAYPAL_API_SANDBOX = $conf->global->PAYPAL_API_SANDBOX;
	$PAYPAL_API_OK = "";
	if ($urlok) $PAYPAL_API_OK = $urlok;
	$PAYPAL_API_KO = "";
	if ($urlko) $PAYPAL_API_KO = $urlko;

	$PAYPALTOKEN = GETPOST('TOKEN');
	if (empty($PAYPALTOKEN)) $PAYPALTOKEN = GETPOST('token');
	$PAYPALPAYERID = GETPOST('PAYERID');
	if (empty($PAYPALPAYERID)) $PAYPALPAYERID = GETPOST('PayerID');
}

$FULLTAG = GETPOST('FULLTAG');
if (empty($FULLTAG)) $FULLTAG = GETPOST('fulltag');
$source = GETPOST('s', 'alpha') ?GETPOST('s', 'alpha') : GETPOST('source', 'alpha');
$ref = GETPOST('ref');

$suffix = GETPOST("suffix", 'aZ09');


// Detect $paymentmethod
$paymentmethod = '';
$reg = array();
if (preg_match('/PM=([^\.]+)/', $FULLTAG, $reg))
{
	$paymentmethod = $reg[1];
}
if (empty($paymentmethod))
{
	dol_print_error(null, 'The back url does not contains a parameter fulltag that should help us to find the payment method used');
	exit;
}

dol_syslog("***** paymentok.php is called paymentmethod=".$paymentmethod." FULLTAG=".$FULLTAG." REQUEST_URI=".$_SERVER["REQUEST_URI"], LOG_DEBUG, 0, '_payment');


$validpaymentmethod = array();
if (!empty($conf->paypal->enabled)) $validpaymentmethod['paypal'] = 'paypal';
if (!empty($conf->paybox->enabled)) $validpaymentmethod['paybox'] = 'paybox';
if (!empty($conf->stripe->enabled)) $validpaymentmethod['stripe'] = 'stripe';

// Security check
if (empty($validpaymentmethod)) accessforbidden('', 0, 0, 1);


$ispaymentok = false;
// If payment is ok
$PAYMENTSTATUS = $TRANSACTIONID = $TAXAMT = $NOTE = '';
// If payment is ko
$ErrorCode = $ErrorShortMsg = $ErrorLongMsg = $ErrorSeverityCode = '';


$object = new stdClass(); // For triggers

$error = 0;


/*
 * Actions
 */



/*
 * View
 */

$now = dol_now();

dol_syslog("Callback url when a payment was done. query_string=".(dol_escape_htmltag($_SERVER["QUERY_STRING"]) ?dol_escape_htmltag($_SERVER["QUERY_STRING"]) : '')." script_uri=".(dol_escape_htmltag($_SERVER["SCRIPT_URI"]) ?dol_escape_htmltag($_SERVER["SCRIPT_URI"]) : ''), LOG_DEBUG, 0, '_payment');

$tracepost = "";
foreach ($_POST as $k => $v) $tracepost .= "{$k} - {$v}\n";
dol_syslog("POST=".$tracepost, LOG_DEBUG, 0, '_payment');

$head = '';
if (!empty($conf->global->ONLINE_PAYMENT_CSS_URL)) $head = '<link rel="stylesheet" type="text/css" href="'.$conf->global->ONLINE_PAYMENT_CSS_URL.'?lang='.$langs->defaultlang.'">'."\n";

$conf->dol_hide_topmenu = 1;
$conf->dol_hide_leftmenu = 1;

$replacemainarea = (empty($conf->dol_hide_leftmenu) ? '<div>' : '').'<div>';
llxHeader($head, $langs->trans("PaymentForm"), '', '', 0, 0, '', '', '', 'onlinepaymentbody', $replacemainarea);


// Show message
print '<span id="dolpaymentspan"></span>'."\n";
print '<div id="dolpaymentdiv" class="center">'."\n";


// Show logo (search order: logo defined by PAYMENT_LOGO_suffix, then PAYMENT_LOGO, then small company logo, large company logo, theme logo, common logo)
// Define logo and logosmall
$logosmall = $mysoc->logo_small;
$logo = $mysoc->logo;
$paramlogo = 'ONLINE_PAYMENT_LOGO_'.$suffix;
if (!empty($conf->global->$paramlogo)) $logosmall = $conf->global->$paramlogo;
elseif (!empty($conf->global->ONLINE_PAYMENT_LOGO)) $logosmall = $conf->global->ONLINE_PAYMENT_LOGO;
//print '<!-- Show logo (logosmall='.$logosmall.' logo='.$logo.') -->'."\n";
// Define urllogo
$urllogo = '';
$urllogofull = '';
if (!empty($logosmall) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$logosmall))
{
	$urllogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;entity='.$conf->entity.'&amp;file='.urlencode('logos/thumbs/'.$logosmall);
	$urllogofull = $dolibarr_main_url_root.'/viewimage.php?modulepart=mycompany&entity='.$conf->entity.'&file='.urlencode('logos/thumbs/'.$logosmall);
} elseif (!empty($logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$logo))
{
	$urllogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;entity='.$conf->entity.'&amp;file='.urlencode('logos/'.$logo);
	$urllogofull = $dolibarr_main_url_root.'/viewimage.php?modulepart=mycompany&entity='.$conf->entity.'&file='.urlencode('logos/'.$logo);
}

// Output html code for logo
if ($urllogo)
{
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


print '<br><br><br>';


if (!empty($conf->paypal->enabled))
{
	if ($paymentmethod == 'paypal')							// We call this page only if payment is ok on payment system
	{
		if ($PAYPALTOKEN)
		{
			// Get on url call
			$onlinetoken        = $PAYPALTOKEN;
			$fulltag            = $FULLTAG;
			$payerID            = $PAYPALPAYERID;
			// Set by newpayment.php
			$paymentType        = $_SESSION['PaymentType'];
			$currencyCodeType   = $_SESSION['currencyCodeType'];
			$FinalPaymentAmt    = $_SESSION["FinalPaymentAmt"];
			// From env
			$ipaddress          = $_SESSION['ipaddress'];

			dol_syslog("Call paymentok with token=".$onlinetoken." paymentType=".$paymentType." currencyCodeType=".$currencyCodeType." payerID=".$payerID." ipaddress=".$ipaddress." FinalPaymentAmt=".$FinalPaymentAmt." fulltag=".$fulltag, LOG_DEBUG, 0, '_payment');

			// Validate record
			if (!empty($paymentType))
			{
				dol_syslog("We call GetExpressCheckoutDetails", LOG_DEBUG, 0, '_payment');
				$resArray = getDetails($onlinetoken);
				//var_dump($resarray);

				$ack = strtoupper($resArray["ACK"]);
				if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING")
				{
					// Nothing to do
					dol_syslog("Call to GetExpressCheckoutDetails return ".$ack, LOG_DEBUG, 0, '_payment');
				} else {
					dol_syslog("Call to GetExpressCheckoutDetails return error: ".json_encode($resArray), LOG_WARNING, '_payment');
				}

				dol_syslog("We call DoExpressCheckoutPayment token=".$onlinetoken." paymentType=".$paymentType." currencyCodeType=".$currencyCodeType." payerID=".$payerID." ipaddress=".$ipaddress." FinalPaymentAmt=".$FinalPaymentAmt." fulltag=".$fulltag, LOG_DEBUG, 0, '_payment');
				$resArray2 = confirmPayment($onlinetoken, $paymentType, $currencyCodeType, $payerID, $ipaddress, $FinalPaymentAmt, $fulltag);
				//var_dump($resarray);

				$ack = strtoupper($resArray2["ACK"]);
				if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING")
				{
					dol_syslog("Call to GetExpressCheckoutDetails return ".$ack, LOG_DEBUG, 0, '_payment');

					$object->source		= $source;
					$object->ref = $ref;
					$object->payerID	= $payerID;
					$object->fulltag	= $fulltag;
					$object->resArray = $resArray2;

					// resArray was built from a string like that
					// TOKEN=EC%2d1NJ057703V9359028&TIMESTAMP=2010%2d11%2d01T11%3a40%3a13Z&CORRELATIONID=1efa8c6a36bd8&ACK=Success&VERSION=56&BUILD=1553277&TRANSACTIONID=9B994597K9921420R&TRANSACTIONTYPE=expresscheckout&PAYMENTTYPE=instant&ORDERTIME=2010%2d11%2d01T11%3a40%3a12Z&AMT=155%2e57&FEEAMT=5%2e54&TAXAMT=0%2e00&CURRENCYCODE=EUR&PAYMENTSTATUS=Completed&PENDINGREASON=None&REASONCODE=None
					$PAYMENTSTATUS = urldecode($resArray2["PAYMENTSTATUS"]); // Should contains 'Completed'
					$TRANSACTIONID = urldecode($resArray2["TRANSACTIONID"]);
					$TAXAMT = urldecode($resArray2["TAXAMT"]);
					$NOTE = urldecode($resArray2["NOTE"]);

					$ispaymentok = true;
				} else {
					dol_syslog("Call to DoExpressCheckoutPayment return error: ".json_encode($resArray2), LOG_WARNING, 0, '_payment');

					//Display a user friendly Error on the page using any of the following error information returned by PayPal
					$ErrorCode = urldecode($resArray2["L_ERRORCODE0"]);
					$ErrorShortMsg = urldecode($resArray2["L_SHORTMESSAGE0"]);
					$ErrorLongMsg = urldecode($resArray2["L_LONGMESSAGE0"]);
					$ErrorSeverityCode = urldecode($resArray2["L_SEVERITYCODE0"]);
				}
			} else {
				dol_print_error('', 'Session expired');
			}
		} else {
			dol_print_error('', '$PAYPALTOKEN not defined');
		}
	}
}

if (!empty($conf->paybox->enabled))
{
	if ($paymentmethod == 'paybox') $ispaymentok = true; // We call this page only if payment is ok on payment system
}

if (!empty($conf->stripe->enabled))
{
	if ($paymentmethod == 'stripe') $ispaymentok = true; // We call this page only if payment is ok on payment system
}


// If data not provided from back url, search them into the session env
if (empty($ipaddress))       $ipaddress       = $_SESSION['ipaddress'];
if (empty($TRANSACTIONID))   $TRANSACTIONID   = $_SESSION['TRANSACTIONID'];
if (empty($FinalPaymentAmt)) $FinalPaymentAmt = $_SESSION["FinalPaymentAmt"];
if (empty($paymentType))     $paymentType     = $_SESSION["paymentType"];

$fulltag = $FULLTAG;
$tmptag = dolExplodeIntoArray($fulltag, '.', '=');


dol_syslog("ispaymentok=".$ispaymentok." tmptag=".var_export($tmptag, true), LOG_DEBUG, 0, '_payment');


// Make complementary actions
$ispostactionok = 0;
$postactionmessages = array();
if ($ispaymentok)
{
	// Set permission for the anonymous user
	if (empty($user->rights->societe))  $user->rights->societe = new stdClass();
	if (empty($user->rights->facture))  $user->rights->facture = new stdClass();
	if (empty($user->rights->adherent)) { $user->rights->adherent = new stdClass(); $user->rights->adherent->cotisation = new stdClass(); }
	$user->rights->societe->creer = 1;
	$user->rights->facture->creer = 1;
	$user->rights->adherent->cotisation->creer = 1;

	if (array_key_exists('MEM', $tmptag) && $tmptag['MEM'] > 0)
	{
		// Validate member
		// Create subscription
		// Create complementary actions (this include creation of thirdparty)
		// Send confirmation email

		$defaultdelay = 1;
		$defaultdelayunit = 'y';

		// Record subscription
		include_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
		include_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
		include_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';
		$adht = new AdherentType($db);
		$object = new Adherent($db);

		$result1 = $object->fetch($tmptag['MEM']);
		$result2 = $adht->fetch($object->typeid);

		dol_syslog("We have to process member with id=".$tmptag['MEM']." result1=".$result1." result2=".$result2, LOG_DEBUG, 0, '_payment');

		if ($result1 > 0 && $result2 > 0)
		{
			$paymentTypeId = 0;
			if ($paymentmethod == 'paybox') $paymentTypeId = $conf->global->PAYBOX_PAYMENT_MODE_FOR_PAYMENTS;
			if ($paymentmethod == 'paypal') $paymentTypeId = $conf->global->PAYPAL_PAYMENT_MODE_FOR_PAYMENTS;
			if ($paymentmethod == 'stripe') $paymentTypeId = $conf->global->STRIPE_PAYMENT_MODE_FOR_PAYMENTS;
			if (empty($paymentTypeId))
			{
				$paymentType = $_SESSION["paymentType"];
				if (empty($paymentType)) $paymentType = 'CB';
				$paymentTypeId = dol_getIdFromCode($db, $paymentType, 'c_paiement', 'code', 'id', 1);
			}

			$currencyCodeType = $_SESSION['currencyCodeType'];

			dol_syslog("FinalPaymentAmt=".$FinalPaymentAmt." paymentTypeId=".$paymentTypeId, LOG_DEBUG, 0, '_payment');

			// Do action only if $FinalPaymentAmt is set (session variable is cleaned after this page to avoid duplicate actions when page is POST a second time)
			if (!empty($FinalPaymentAmt) && $paymentTypeId > 0)
			{
				$result = $object->validate($user);
				if ($result < 0 || empty($object->datevalid))
				{
					$error++;
					$errmsg = $object->error;
					$postactionmessages[] = $errmsg;
					$postactionmessages = array_merge($postactionmessages, $object->errors);
					$ispostactionok = -1;
					dol_syslog("Failed to validate member: ".$errmsg, LOG_ERR, 0, '_payment');
				}

				// Subscription informations
				$datesubscription = $object->datevalid;
				if ($object->datefin > 0)
				{
					$datesubscription = dol_time_plus_duree($object->datefin, 1, 'd');
				}

				$datesubend = null;
				if ($datesubscription && $defaultdelay && $defaultdelayunit) $datesubend = dol_time_plus_duree(dol_time_plus_duree($datesubscription, $defaultdelay, $defaultdelayunit), -1, 'd');

				$paymentdate = $now;
				$amount = $FinalPaymentAmt;
				$label = 'Online subscription '.dol_print_date($now, 'standard').' using '.$paymentmethod.' from '.$ipaddress.' - Transaction ID = '.$TRANSACTIONID;

				// Payment informations
				$accountid = 0;
				if ($paymentmethod == 'paybox') $accountid = $conf->global->PAYBOX_BANK_ACCOUNT_FOR_PAYMENTS;
				if ($paymentmethod == 'paypal') $accountid = $conf->global->PAYPAL_BANK_ACCOUNT_FOR_PAYMENTS;
				if ($paymentmethod == 'stripe') $accountid = $conf->global->STRIPE_BANK_ACCOUNT_FOR_PAYMENTS;
				if ($accountid < 0)
				{
					$error++;
					$errmsg = 'Setup of bank account to use for payment is not correctly done for payment method '.$paymentmethod;
					$postactionmessages[] = $errmsg;
					$ispostactionok = -1;
					dol_syslog("Failed to get the bank account to record payment: ".$errmsg, LOG_ERR, 0, '_payment');
				}

				$operation = $paymentType; // Payment mode code
				$num_chq = '';
				$emetteur_nom = '';
				$emetteur_banque = '';
				// Define default choice for complementary actions
				$option = '';
				if (!empty($conf->global->ADHERENT_BANK_USE) && $conf->global->ADHERENT_BANK_USE == 'bankviainvoice' && !empty($conf->banque->enabled) && !empty($conf->societe->enabled) && !empty($conf->facture->enabled)) $option = 'bankviainvoice';
				elseif (!empty($conf->global->ADHERENT_BANK_USE) && $conf->global->ADHERENT_BANK_USE == 'bankdirect' && !empty($conf->banque->enabled)) $option = 'bankdirect';
				elseif (!empty($conf->global->ADHERENT_BANK_USE) && $conf->global->ADHERENT_BANK_USE == 'invoiceonly' && !empty($conf->banque->enabled) && !empty($conf->societe->enabled) && !empty($conf->facture->enabled)) $option = 'invoiceonly';
				if (empty($option)) $option = 'none';
				$sendalsoemail = 1;

				// Record the subscription then complementary actions
				$db->begin();

				// Create subscription
				if (!$error)
				{
					dol_syslog("Call ->subscription to create subscription", LOG_DEBUG, 0, '_payment');

					$crowid = $object->subscription($datesubscription, $amount, $accountid, $operation, $label, $num_chq, $emetteur_nom, $emetteur_banque, $datesubend);
					if ($crowid <= 0)
					{
						$error++;
						$errmsg = $object->error;
						$postactionmessages[] = $errmsg;
						$ispostactionok = -1;
					} else {
						$postactionmessages[] = 'Subscription created';
						$ispostactionok = 1;
					}
				}

				if (!$error)
				{
					dol_syslog("Call ->subscriptionComplementaryActions option=".$option, LOG_DEBUG, 0, '_payment');

					$autocreatethirdparty = 1; // will create thirdparty if member not yet linked to a thirdparty

					$result = $object->subscriptionComplementaryActions($crowid, $option, $accountid, $datesubscription, $paymentdate, $operation, $label, $amount, $num_chq, $emetteur_nom, $emetteur_banque, $autocreatethirdparty);
					if ($result < 0)
					{
						dol_syslog("Error ".$object->error." ".join(',', $object->errors), LOG_DEBUG, 0, '_payment');

						$error++;
						$postactionmessages[] = $object->error;
						$postactionmessages = array_merge($postactionmessages, $object->errors);
						$ispostactionok = -1;
					} else {
						if ($option == 'bankviainvoice')
						{
							$postactionmessages[] = 'Invoice, payment and bank record created';
							dol_syslog("Invoice, payment and bank record created", LOG_DEBUG, 0, '_payment');
						}
						if ($option == 'bankdirect')
						{
							$postactionmessages[] = 'Bank record created';
							dol_syslog("Bank record created", LOG_DEBUG, 0, '_payment');
						}
						if ($option == 'invoiceonly')
						{
							$postactionmessages[] = 'Invoice recorded';
							dol_syslog("Invoice recorded", LOG_DEBUG, 0, '_payment');
						}
						$ispostactionok = 1;

						// If an invoice was created, it is into $object->invoice
					}
				}

				if (!$error)
				{
					if ($paymentmethod == 'stripe' && $autocreatethirdparty && $option == 'bankviainvoice')
					{
						$thirdparty_id = $object->fk_soc;

						dol_syslog("Search existing Stripe customer profile for thirdparty_id=".$thirdparty_id, LOG_DEBUG, 0, '_payment');

						$service = 'StripeTest';
						$servicestatus = 0;
						if (!empty($conf->global->STRIPE_LIVE) && !GETPOST('forcesandbox', 'alpha'))
						{
							$service = 'StripeLive';
							$servicestatus = 1;
						}
						$stripeacc = null; // No Oauth/connect use for public pages

						$thirdparty = new Societe($db);
						$thirdparty->fetch($thirdparty_id);

						include_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';	// This also set $stripearrayofkeysbyenv
						$stripe = new Stripe($db);
						//$stripeacc = $stripe->getStripeAccount($service);		Already defined previously

						$customer = $stripe->customerStripe($thirdparty, $stripeacc, $servicestatus, 0);

						if (!$customer && $TRANSACTIONID)	// Not linked to a stripe customer, we make the link
						{
							dol_syslog("No stripe profile found, so we add it for TRANSACTIONID = ".$TRANSACTIONID, LOG_DEBUG, 0, '_payment');

							try {
								global $stripearrayofkeysbyenv;
								\Stripe\Stripe::setApiKey($stripearrayofkeysbyenv[$servicestatus]['secret_key']);

								if (preg_match('/^pi_/', $TRANSACTIONID)) {
									// This may throw an error if not found.
									$chpi = \Stripe\PaymentIntent::retrieve($TRANSACTIONID);	// payment_intent (pi_...)
								} else {
									// This throw an error if not found
									$chpi = \Stripe\Charge::retrieve($TRANSACTIONID); // old method, contains the charge id (ch_...)
								}

								if ($chpi) {
									$stripecu = $chpi->customer; // value 'cus_....'. WARNING: This property may be empty if first payment was recorded before the stripe customer was created.

									if (empty($stripecu)) {
										// This include the INSERT
										$customer = $stripe->customerStripe($thirdparty, $stripeacc, $servicestatus, 1);

										// Link this customer to the payment intent
										if (preg_match('/^pi_/', $TRANSACTIONID) && $customer) {
											\Stripe\PaymentIntent::update($chpi->id, array('customer' => $customer->id));
										}
									} else {
										$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_account (fk_soc, login, key_account, site, site_account, status, entity, date_creation, fk_user_creat)";
										$sql .= " VALUES (".$object->fk_soc.", '', '".$db->escape($stripecu)."', 'stripe', '".$db->escape($stripearrayofkeysbyenv[$servicestatus]['publishable_key'])."', ".$servicestatus.", ".$conf->entity.", '".$db->idate(dol_now())."', 0)";
										$resql = $db->query($sql);
										if (!$resql) {	// should not happen
											$error++;
											$errmsg = 'Failed to insert customer stripe id in database : '.$db->lasterror();
											dol_syslog($errmsg, LOG_ERR, 0, '_payment');
											$postactionmessages[] = $errmsg;
											$ispostactionok = -1;
										}
									}
								} else {	// should not happen
									$error++;
									$errmsg = 'Failed to retreive paymentintent or charge from id';
									dol_syslog($errmsg, LOG_ERR, 0, '_payment');
									$postactionmessages[] = $errmsg;
									$ispostactionok = -1;
								}
							} catch (Exception $e) {	// should not happen
								$error++;
								$errmsg = 'Failed to get or save customer stripe id in database : '.$e->getMessage();
								dol_syslog($errmsg, LOG_ERR, 0, '_payment');
								$postactionmessages[] = $errmsg;
								$ispostactionok = -1;
							}
						}
					}
				}

				if (!$error) {
					$db->commit();
				} else {
					$db->rollback();
				}

				// Send email to member
				if (!$error)
				{
					dol_syslog("Send email to customer to ".$object->email." if we have to (sendalsoemail = ".$sendalsoemail.")", LOG_DEBUG, 0, '_payment');

					// Send confirmation Email
					if ($object->email && $sendalsoemail)
					{
						$subject = '';
						$msg = '';

						// Send subscription email
						include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
						$formmail = new FormMail($db);
						// Set output language
						$outputlangs = new Translate('', $conf);
						$outputlangs->setDefaultLang(empty($object->thirdparty->default_lang) ? $mysoc->default_lang : $object->thirdparty->default_lang);
						// Load traductions files required by page
						$outputlangs->loadLangs(array("main", "members"));
						// Get email content from template
						$arraydefaultmessage = null;
						$labeltouse = $conf->global->ADHERENT_EMAIL_TEMPLATE_SUBSCRIPTION;

						if (!empty($labeltouse)) $arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);

						if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0)
						{
							$subject = $arraydefaultmessage->topic;
							$msg     = $arraydefaultmessage->content;
						}

						$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
						complete_substitutions_array($substitutionarray, $outputlangs, $object);
						$subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
						$texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnSubscription()), $substitutionarray, $outputlangs);

						// Attach a file ?
						$file = '';
						$listofpaths = array();
						$listofnames = array();
						$listofmimes = array();
						if (is_object($object->invoice))
						{
							$invoicediroutput = $conf->facture->dir_output;
							$fileparams = dol_most_recent_file($invoicediroutput.'/'.$object->invoice->ref, preg_quote($object->invoice->ref, '/').'[^\-]+');
							$file = $fileparams['fullname'];

							$listofpaths = array($file);
							$listofnames = array(basename($file));
							$listofmimes = array(dol_mimetype($file));
						}

						$moreinheader = 'X-Dolibarr-Info: send_an_email by public/payment/paymentok.php'."\r\n";

						$result = $object->send_an_email($texttosend, $subjecttosend, $listofpaths, $listofmimes, $listofnames, "", "", 0, -1, "", $moreinheader);

						if ($result < 0)
						{
							$errmsg = $object->error;
							$postactionmessages[] = $errmsg;
							$ispostactionok = -1;
						} else {
							if ($file) $postactionmessages[] = 'Email sent to member (with invoice document attached)';
							else $postactionmessages[] = 'Email sent to member (without any attached document)';

							// TODO Add actioncomm event
						}
					}
				}
			} else {
				$postactionmessages[] = 'Failed to get a valid value for "amount paid" or "payment type" to record the payment of subscription for member '.$tmptag['MEM'].'. May be payment was already recorded.';
				$ispostactionok = -1;
			}
		} else {
			$postactionmessages[] = 'Member '.$tmptag['MEM'].' for subscription payed was not found';
			$ispostactionok = -1;
		}
	} elseif (array_key_exists('INV', $tmptag) && $tmptag['INV'] > 0)
	{
		// Record payment
		include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		$object = new Facture($db);
		$result = $object->fetch($tmptag['INV']);
		if ($result)
		{
			$FinalPaymentAmt = $_SESSION["FinalPaymentAmt"];

			$paymentTypeId = 0;
			if ($paymentmethod == 'paybox') $paymentTypeId = $conf->global->PAYBOX_PAYMENT_MODE_FOR_PAYMENTS;
			if ($paymentmethod == 'paypal') $paymentTypeId = $conf->global->PAYPAL_PAYMENT_MODE_FOR_PAYMENTS;
			if ($paymentmethod == 'stripe') $paymentTypeId = $conf->global->STRIPE_PAYMENT_MODE_FOR_PAYMENTS;
			if (empty($paymentTypeId))
			{
				$paymentType = $_SESSION["paymentType"];
				if (empty($paymentType)) $paymentType = 'CB';
				$paymentTypeId = dol_getIdFromCode($db, $paymentType, 'c_paiement', 'code', 'id', 1);
			}

			$currencyCodeType = $_SESSION['currencyCodeType'];

			// Do action only if $FinalPaymentAmt is set (session variable is cleaned after this page to avoid duplicate actions when page is POST a second time)
			if (!empty($FinalPaymentAmt) && $paymentTypeId > 0)
			{
				$db->begin();

				// Creation of payment line
				include_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
				$paiement = new Paiement($db);
				$paiement->datepaye = $now;
				if ($currencyCodeType == $conf->currency)
				{
					$paiement->amounts = array($object->id => $FinalPaymentAmt); // Array with all payments dispatching with invoice id
				} else {
					$paiement->multicurrency_amounts = array($object->id => $FinalPaymentAmt); // Array with all payments dispatching

					$postactionmessages[] = 'Payment was done in a different currency that currency expected of company';
					$ispostactionok = -1;
					$error++; // Not yet supported
				}
				$paiement->paiementid   = $paymentTypeId;
				$paiement->num_payment = '';
				$paiement->note_public  = 'Online payment '.dol_print_date($now, 'standard').' from '.$ipaddress;
				$paiement->ext_payment_id = $TRANSACTIONID;
				$paiement->ext_payment_site = $service;

				if (!$error)
				{
					$paiement_id = $paiement->create($user, 1); // This include closing invoices and regenerating documents
					if ($paiement_id < 0)
					{
						$postactionmessages[] = $paiement->error.' '.join("<br>\n", $paiement->errors);
						$ispostactionok = -1;
						$error++;
					} else {
						$postactionmessages[] = 'Payment created';
						$ispostactionok = 1;
					}
				}

				if (!$error && !empty($conf->banque->enabled))
				{
					$bankaccountid = 0;
					if ($paymentmethod == 'paybox') $bankaccountid = $conf->global->PAYBOX_BANK_ACCOUNT_FOR_PAYMENTS;
					elseif ($paymentmethod == 'paypal') $bankaccountid = $conf->global->PAYPAL_BANK_ACCOUNT_FOR_PAYMENTS;
					elseif ($paymentmethod == 'stripe') $bankaccountid = $conf->global->STRIPE_BANK_ACCOUNT_FOR_PAYMENTS;

					if ($bankaccountid > 0)
					{
						$label = '(CustomerInvoicePayment)';
						if ($object->type == Facture::TYPE_CREDIT_NOTE) $label = '(CustomerInvoicePaymentBack)'; // Refund of a credit note
						$result = $paiement->addPaymentToBank($user, 'payment', $label, $bankaccountid, '', '');
						if ($result < 0)
						{
							$postactionmessages[] = $paiement->error.' '.join("<br>\n", $paiement->errors);
							$ispostactionok = -1;
							$error++;
						} else {
							$postactionmessages[] = 'Bank transaction of payment created';
							$ispostactionok = 1;
						}
					} else {
						$postactionmessages[] = 'Setup of bank account to use in module '.$paymentmethod.' was not set. No way to record the payment.';
						$ispostactionok = -1;
						$error++;
					}
				}

				if (!$error)
				{
					$db->commit();
				} else {
					$db->rollback();
				}
			} else {
				$postactionmessages[] = 'Failed to get a valid value for "amount paid" ('.$FinalPaymentAmt.') or "payment type" ('.$paymentType.') to record the payment of invoice '.$tmptag['INV'].'. May be payment was already recorded.';
				$ispostactionok = -1;
			}
		} else {
			$postactionmessages[] = 'Invoice payed '.$tmptag['INV'].' was not found';
			$ispostactionok = -1;
		}
	} else {
		// Nothing done
	}
}

if ($ispaymentok)
{
	// Get on url call
	$onlinetoken        = empty($PAYPALTOKEN) ? $_SESSION['onlinetoken'] : $PAYPALTOKEN;
	$payerID            = empty($PAYPALPAYERID) ? $_SESSION['payerID'] : $PAYPALPAYERID;
	// Set by newpayment.php
	$paymentType        = $_SESSION['PaymentType'];
	$currencyCodeType   = $_SESSION['currencyCodeType'];
	$FinalPaymentAmt    = $_SESSION["FinalPaymentAmt"];

	if (is_object($object) && method_exists($object, 'call_trigger')) {
		// Call trigger
		$result = $object->call_trigger('PAYMENTONLINE_PAYMENT_OK', $user);
		if ($result < 0) $error++;
		// End call triggers
	}

	print $langs->trans("YourPaymentHasBeenRecorded")."<br>\n";
	if ($TRANSACTIONID) print $langs->trans("ThisIsTransactionId", $TRANSACTIONID)."<br><br>\n";

	$key = 'ONLINE_PAYMENT_MESSAGE_OK';
	if (!empty($conf->global->$key)) print $conf->global->$key;

	$sendemail = '';
	if (!empty($conf->global->ONLINE_PAYMENT_SENDEMAIL)) $sendemail = $conf->global->ONLINE_PAYMENT_SENDEMAIL;

	$tmptag = dolExplodeIntoArray($fulltag, '.', '=');

	dol_syslog("Send email to admins if we have to (sendemail = ".$sendemail.")", LOG_DEBUG, 0, '_payment');

	// Send an email to admins
	if ($sendemail)
	{
		$companylangs = new Translate('', $conf);
		$companylangs->setDefaultLang($mysoc->default_lang);
		$companylangs->loadLangs(array('main', 'members', 'bills', 'paypal', 'paybox'));

		$sendto = $sendemail;
		$from = $conf->global->MAILING_EMAIL_FROM;
		// Define $urlwithroot
		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
		//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

		// Define link to login card
		$appli = constant('DOL_APPLICATION_TITLE');
		if (!empty($conf->global->MAIN_APPLICATION_TITLE))
		{
			$appli = $conf->global->MAIN_APPLICATION_TITLE;
			if (preg_match('/\d\.\d/', $appli))
			{
				if (!preg_match('/'.preg_quote(DOL_VERSION).'/', $appli)) $appli .= " (".DOL_VERSION.")"; // If new title contains a version that is different than core
			} else $appli .= " ".DOL_VERSION;
		} else $appli .= " ".DOL_VERSION;

		$urlback = $_SERVER["REQUEST_URI"];
		$topic = '['.$appli.'] '.$companylangs->transnoentitiesnoconv("NewOnlinePaymentReceived");
		$content = "";
		if (array_key_exists('MEM', $tmptag))
		{
			$url = $urlwithroot."/adherents/subscription.php?rowid=".$tmptag['MEM'];
			$content .= '<strong>'.$companylangs->trans("PaymentSubscription")."</strong><br><br>\n";
			$content .= $companylangs->trans("MemberId").': <strong>'.$tmptag['MEM']."</strong><br>\n";
			$content .= $companylangs->trans("Link").': <a href="'.$url.'">'.$url.'</a>'."<br>\n";
		} elseif (array_key_exists('INV', $tmptag))
		{
			$url = $urlwithroot."/compta/facture/card.php?id=".$tmptag['INV'];
			$content .= '<strong>'.$companylangs->trans("Payment")."</strong><br><br>\n";
			$content .= $companylangs->trans("InvoiceId").': <strong>'.$tmptag['INV']."</strong><br>\n";
			//$content.=$companylangs->trans("ThirdPartyId").': '.$tmptag['CUS']."<br>\n";
			$content .= $companylangs->trans("Link").': <a href="'.$url.'">'.$url.'</a>'."<br>\n";
		} else {
			$content .= $companylangs->transnoentitiesnoconv("NewOnlinePaymentReceived")."<br>\n";
		}
		$content .= $companylangs->transnoentities("PostActionAfterPayment").' : ';
		if ($ispostactionok > 0)
		{
			//$topic.=' ('.$companylangs->transnoentitiesnoconv("Status").' '.$companylangs->transnoentitiesnoconv("OK").')';
			$content .= '<font color="green">'.$companylangs->transnoentitiesnoconv("OK").'</font>';
		} elseif ($ispostactionok == 0)
		{
			$content .= $companylangs->transnoentitiesnoconv("None");
		} else {
			$topic .= ($ispostactionok ? '' : ' ('.$companylangs->trans("WarningPostActionErrorAfterPayment").')');
			$content .= '<font color="red">'.$companylangs->transnoentitiesnoconv("Error").'</font>';
		}
		$content .= '<br>'."\n";
		foreach ($postactionmessages as $postactionmessage)
		{
			$content .= ' * '.$postactionmessage.'<br>'."\n";
		}
		if ($ispostactionok < 0)
		{
			$content .= $langs->transnoentities("ARollbackWasPerformedOnPostActions");
		}
		$content .= '<br>'."\n";

		$content .= "<br>\n";
		$content .= '<u>'.$companylangs->transnoentitiesnoconv("TechnicalInformation").":</u><br>\n";
		$content .= $companylangs->transnoentitiesnoconv("OnlinePaymentSystem").': <strong>'.$paymentmethod."</strong><br>\n";
		$content .= $companylangs->transnoentitiesnoconv("ThisIsTransactionId").': <strong>'.$TRANSACTIONID."</strong><br>\n";
		$content .= $companylangs->transnoentitiesnoconv("ReturnURLAfterPayment").': '.$urlback."<br>\n";
		$content .= "<br>\n";
		$content .= "tag=".$fulltag."<br>\ntoken=".$onlinetoken."<br>\npaymentType=".$paymentType."<br>\ncurrencycodeType=".$currencyCodeType."<br>\npayerId=".$payerID."<br>\nipaddress=".$ipaddress."<br>\nFinalPaymentAmt=".$FinalPaymentAmt."<br>\n";

		if (!empty($ErrorCode))         $content .= "ErrorCode = ".$ErrorCode."<br>\n";
		if (!empty($ErrorShortMsg))     $content .= "ErrorShortMsg = ".$ErrorShortMsg."<br>\n";
		if (!empty($ErrorLongMsg))      $content .= "ErrorLongMsg = ".$ErrorLongMsg."<br>\n";
		if (!empty($ErrorSeverityCode)) $content .= "ErrorSeverityCode = ".$ErrorSeverityCode."<br>\n";

		$ishtml = dol_textishtml($content); // May contain urls

		require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
		$mailfile = new CMailFile($topic, $sendto, $from, $content, array(), array(), array(), '', '', 0, $ishtml);

		$result = $mailfile->sendfile();
		if ($result)
		{
			dol_syslog("EMail sent to ".$sendto, LOG_DEBUG, 0, '_payment');
			//dol_syslog("EMail sent to ".$sendto, LOG_DEBUG, 0);
		} else {
			dol_syslog("Failed to send EMail to ".$sendto, LOG_ERR, 0, '_payment');
			//dol_syslog("Failed to send EMail to ".$sendto, LOG_ERR, 0);
		}
	}
} else {
	// Get on url call
	$onlinetoken = empty($PAYPALTOKEN) ? $_SESSION['onlinetoken'] : $PAYPALTOKEN;
	$payerID            = empty($PAYPALPAYERID) ? $_SESSION['payerID'] : $PAYPALPAYERID;
	// Set by newpayment.php
	$paymentType        = $_SESSION['PaymentType'];
	$currencyCodeType   = $_SESSION['currencyCodeType'];
	$FinalPaymentAmt    = $_SESSION["FinalPaymentAmt"];

	if (is_object($object) && method_exists($object, 'call_trigger')) {
		// Call trigger
		$result = $object->call_trigger('PAYMENTONLINE_PAYMENT_KO', $user);
		if ($result < 0) $error++;
		// End call triggers
	}

	print $langs->trans('DoExpressCheckoutPaymentAPICallFailed')."<br>\n";
	print $langs->trans('DetailedErrorMessage').": ".$ErrorLongMsg."<br>\n";
	print $langs->trans('ShortErrorMessage').": ".$ErrorShortMsg."<br>\n";
	print $langs->trans('ErrorCode').": ".$ErrorCode."<br>\n";
	print $langs->trans('ErrorSeverityCode').": ".$ErrorSeverityCode."<br>\n";

	if ($mysoc->email) print "\nPlease, send a screenshot of this page to ".$mysoc->email."<br>\n";

	$sendemail = '';
	if (!empty($conf->global->PAYMENTONLINE_SENDEMAIL)) $sendemail = $conf->global->PAYMENTONLINE_SENDEMAIL;
	// TODO Remove local option to keep only the generic one ?
	if ($paymentmethod == 'paypal' && !empty($conf->global->PAYPAL_PAYONLINE_SENDEMAIL)) $sendemail = $conf->global->PAYPAL_PAYONLINE_SENDEMAIL;
	elseif ($paymentmethod == 'paybox' && !empty($conf->global->PAYBOX_PAYONLINE_SENDEMAIL)) $sendemail = $conf->global->PAYBOX_PAYONLINE_SENDEMAIL;
	elseif ($paymentmethod == 'stripe' && !empty($conf->global->STRIPE_PAYONLINE_SENDEMAIL)) $sendemail = $conf->global->STRIPE_PAYONLINE_SENDEMAIL;

	// Send warning of error to administrator
	if ($sendemail)
	{
		$companylangs = new Translate('', $conf);
		$companylangs->setDefaultLang($mysoc->default_lang);
		$companylangs->loadLangs(array('main', 'members', 'bills', 'paypal', 'paybox'));

		$sendto = $sendemail;
		$from = $conf->global->MAILING_EMAIL_FROM;
		// Define $urlwithroot
		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
		//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

		// Define link to login card
		$appli = constant('DOL_APPLICATION_TITLE');
		if (!empty($conf->global->MAIN_APPLICATION_TITLE))
		{
			$appli = $conf->global->MAIN_APPLICATION_TITLE;
			if (preg_match('/\d\.\d/', $appli))
			{
				if (!preg_match('/'.preg_quote(DOL_VERSION).'/', $appli)) $appli .= " (".DOL_VERSION.")"; // If new title contains a version that is different than core
			} else $appli .= " ".DOL_VERSION;
		} else $appli .= " ".DOL_VERSION;

		$urlback = $_SERVER["REQUEST_URI"];
		$topic = '['.$appli.'] '.$companylangs->transnoentitiesnoconv("ValidationOfPaymentFailed");
		$content = "";
		$content .= '<font color="orange">'.$companylangs->transnoentitiesnoconv("PaymentSystemConfirmPaymentPageWasCalledButFailed")."</font>\n";

		$content .= "<br><br>\n";
		$content .= '<u>'.$companylangs->transnoentitiesnoconv("TechnicalInformation").":</u><br>\n";
		$content .= $companylangs->transnoentitiesnoconv("OnlinePaymentSystem").': <strong>'.$paymentmethod."</strong><br>\n";
		$content .= $companylangs->transnoentitiesnoconv("ReturnURLAfterPayment").': '.$urlback."<br>\n";
		$content .= "<br>\n";
		$content .= "tag=".$fulltag."<br>\ntoken=".$onlinetoken."<br>\npaymentType=".$paymentType."<br>\ncurrencycodeType=".$currencyCodeType."<br>\npayerId=".$payerID."<br>\nipaddress=".$ipaddress."<br>\nFinalPaymentAmt=".$FinalPaymentAmt."<br>\n";


		$ishtml = dol_textishtml($content); // May contain urls

		require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
		$mailfile = new CMailFile($topic, $sendto, $from, $content, array(), array(), array(), '', '', 0, $ishtml);

		$result = $mailfile->sendfile();
		if ($result)
		{
			dol_syslog("EMail sent to ".$sendto, LOG_DEBUG, 0, '_payment');
		} else {
			dol_syslog("Failed to send EMail to ".$sendto, LOG_ERR, 0, '_payment');
		}
	}
}


print "\n</div>\n";


htmlPrintOnlinePaymentFooter($mysoc, $langs, 0, $suffix);


// Clean session variables to avoid duplicate actions if post is resent
unset($_SESSION["FinalPaymentAmt"]);
unset($_SESSION["TRANSACTIONID"]);


llxFooter('', 'public');

$db->close();
