<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2009	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@capnetworks.com>
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
 *     	\file       htdocs/public/paypal/paymentok.php
 *		\ingroup    paypal
 *		\brief      File to show page after a successful payment
 *                  This page is called by paypal with url provided to payal competed with parameter TOKEN=xxx
 *                  This token can be used to get more informations.
 *		\author	    Laurent Destailleur
 */

define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.

// For MultiCompany module
$entity=(! empty($_GET['entity']) ? (int) $_GET['entity'] : (! empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_int($entity))
{
	define("DOLENTITY", $entity);
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/paypal/lib/paypal.lib.php';
require_once DOL_DOCUMENT_ROOT.'/paypal/lib/paypalfunctions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

// Security check
if (empty($conf->paypal->enabled)) accessforbidden('',1,1,1);

$langs->load("main");
$langs->load("other");
$langs->load("dict");
$langs->load("bills");
$langs->load("companies");
$langs->load("paybox");
$langs->load("paypal");

// Clean parameters
$PAYPAL_API_USER="";
if (! empty($conf->global->PAYPAL_API_USER)) $PAYPAL_API_USER=$conf->global->PAYPAL_API_USER;
$PAYPAL_API_PASSWORD="";
if (! empty($conf->global->PAYPAL_API_PASSWORD)) $PAYPAL_API_PASSWORD=$conf->global->PAYPAL_API_PASSWORD;
$PAYPAL_API_SIGNATURE="";
if (! empty($conf->global->PAYPAL_API_SIGNATURE)) $PAYPAL_API_SIGNATURE=$conf->global->PAYPAL_API_SIGNATURE;
$PAYPAL_API_SANDBOX="";
if (! empty($conf->global->PAYPAL_API_SANDBOX)) $PAYPAL_API_SANDBOX=$conf->global->PAYPAL_API_SANDBOX;
$PAYPAL_API_OK="";
if ($urlok) $PAYPAL_API_OK=$urlok;
$PAYPAL_API_KO="";
if ($urlko) $PAYPAL_API_KO=$urlko;
if (empty($PAYPAL_API_USER))
{
    dol_print_error('',"Paypal setup param PAYPAL_API_USER not defined");
    return -1;
}
if (empty($PAYPAL_API_PASSWORD))
{
    dol_print_error('',"Paypal setup param PAYPAL_API_PASSWORD not defined");
    return -1;
}
if (empty($PAYPAL_API_SIGNATURE))
{
    dol_print_error('',"Paypal setup param PAYPAL_API_SIGNATURE not defined");
    return -1;
}

$source=GETPOST('source');
$ref=GETPOST('ref');
$PAYPALTOKEN=GETPOST('TOKEN');
if (empty($PAYPALTOKEN)) $PAYPALTOKEN=GETPOST('token');
$PAYPALPAYERID=GETPOST('PAYERID');
if (empty($PAYPALPAYERID)) $PAYPALPAYERID=GETPOST('PayerID');
$PAYPALFULLTAG=GETPOST('FULLTAG');
if (empty($PAYPALFULLTAG)) $PAYPALFULLTAG=GETPOST('fulltag');


/*
 * Actions
 */



/*
 * View
 */

dol_syslog("Callback url when a PayPal payment was done. query_string=".(empty($_SERVER["QUERY_STRING"])?'':$_SERVER["QUERY_STRING"])." script_uri=".(empty($_SERVER["SCRIPT_URI"])?'':$_SERVER["SCRIPT_URI"]), LOG_DEBUG, 0, '_paypal');

$tracepost = "";
foreach($_POST as $k => $v) $tracepost .= "{$k} - {$v}\n";
dol_syslog("POST=".$tracepost, LOG_DEBUG, 0, '_paypal');


llxHeaderPaypal($langs->trans("PaymentForm"));


print '<span id="dolpaymentspan"></span>'."\n";
print '<div id="dolpaymentdiv" align="center">'."\n";

if ($PAYPALTOKEN)
{
    // Get on url call
    $token              = $PAYPALTOKEN;
    $fulltag            = $PAYPALFULLTAG;
    $payerID            = $PAYPALPAYERID;
    // Set by newpayment.php
    $paymentType        = $_SESSION['PaymentType'];
    $currencyCodeType   = $_SESSION['currencyCodeType'];
    $FinalPaymentAmt    = $_SESSION["Payment_Amount"];
    // From env
    $ipaddress          = $_SESSION['ipaddress'];

	dol_syslog("Call newpaymentok with token=".$token." paymentType=".$paymentType." currencyCodeType=".$currencyCodeType." payerID=".$payerID." ipaddress=".$ipaddress." FinalPaymentAmt=".$FinalPaymentAmt." fulltag=".$fulltag);


	// Send an email
	if (! empty($conf->global->MEMBER_PAYONLINE_SENDEMAIL) && preg_match('/MEM=',$fulltag))
	{
		$sendto=$conf->global->MEMBER_PAYONLINE_SENDEMAIL;
		$from=$conf->global->MAILING_EMAIL_FROM;
		require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
		$mailfile = new CMailFile(
			'New subscription payed',
			$sendto,
			$from,
			'New subscription payed '.$fulltag
			);

		$result=$mailfile->sendfile();
		if ($result)
		{
			dol_syslog("EMail sent to ".$sendto);
		}
		else
		{
			dol_syslog("Failed to send EMail to ".$sendto, LOG_ERR);
		}
	}


	// Validate record
    if (! empty($paymentType))
    {
        dol_syslog("We call GetExpressCheckoutDetails");
        $resArray=getDetails($token);
        //var_dump($resarray);

        dol_syslog("We call DoExpressCheckoutPayment token=".$token." paymentType=".$paymentType." currencyCodeType=".$currencyCodeType." payerID=".$payerID." ipaddress=".$ipaddress." FinalPaymentAmt=".$FinalPaymentAmt." fulltag=".$fulltag);
        $resArray=confirmPayment($token, $paymentType, $currencyCodeType, $payerID, $ipaddress, $FinalPaymentAmt, $fulltag);

        $ack = strtoupper($resArray["ACK"]);
        if($ack=="SUCCESS" || $ack=="SUCCESSWITHWARNING")
        {
        	$object = new stdClass();

        	$object->source		= $source;
        	$object->ref		= $ref;
        	$object->payerID	= $payerID;
        	$object->fulltag	= $fulltag;
        	$object->resArray	= $resArray;

            // resArray was built from a string like that
            // TOKEN=EC%2d1NJ057703V9359028&TIMESTAMP=2010%2d11%2d01T11%3a40%3a13Z&CORRELATIONID=1efa8c6a36bd8&ACK=Success&VERSION=56&BUILD=1553277&TRANSACTIONID=9B994597K9921420R&TRANSACTIONTYPE=expresscheckout&PAYMENTTYPE=instant&ORDERTIME=2010%2d11%2d01T11%3a40%3a12Z&AMT=155%2e57&FEEAMT=5%2e54&TAXAMT=0%2e00&CURRENCYCODE=EUR&PAYMENTSTATUS=Completed&PENDINGREASON=None&REASONCODE=None
            $PAYMENTSTATUS=urldecode($resArray["PAYMENTSTATUS"]);   // Should contains 'Completed'
            $TRANSACTIONID=urldecode($resArray["TRANSACTIONID"]);
            $NOTE=urldecode($resArray["NOTE"]);

            print $langs->trans("YourPaymentHasBeenRecorded")."<br>\n";
            print $langs->trans("ThisIsTransactionId",$TRANSACTIONID)."<br>\n";
            if (! empty($conf->global->PAYPAL_MESSAGE_OK)) print $conf->global->PAYPAL_MESSAGE_OK;

            // Appel des triggers
            include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
            $interface=new Interfaces($db);
            $result=$interface->run_triggers('PAYPAL_PAYMENT_OK',$object,$user,$langs,$conf);
            if ($result < 0) { $error++; $errors=$interface->errors; }
            // Fin appel triggers
        }
        else
        {
            //Display a user friendly Error on the page using any of the following error information returned by PayPal
            $ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
            $ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
            $ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
            $ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);

            echo "DoExpressCheckoutPayment API call failed. ";
            echo "Detailed Error Message: " . $ErrorLongMsg;
            echo "Short Error Message: " . $ErrorShortMsg;
            echo "Error Code: " . $ErrorCode;
            echo "Error Severity Code: " . $ErrorSeverityCode;

            if ($mysoc->email) echo "\nPlease, send a screenshot of this page to ".$mysoc->email;
        }
    }
    else
    {
        dol_print_error('','Session expired');
    }
}
else
{
    // No TOKEN parameter in URL
    dol_print_error('','No TOKEN parameter in URL');
}

print "\n</div>\n";

html_print_paypal_footer($mysoc,$langs);


llxFooterPaypal();

$db->close();
?>
