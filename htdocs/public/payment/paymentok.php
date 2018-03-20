<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2013	Laurent Destailleur		<eldy@users.sourceforge.net>
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
 *     	\file       htdocs/public/payment/paymentok.php
 *		\ingroup    core
 *		\brief      File to show page after a successful payment
 *                  This page is called by payment system with url provided to it completed with parameter TOKEN=xxx
 *                  This token can be used to get more informations.
 */

define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// TODO This should be useless. Because entity must be retreive from object ref and not from url.
$entity=(! empty($_GET['e']) ? (int) $_GET['e'] : (! empty($_POST['e']) ? (int) $_POST['e'] : 1));
if (is_numeric($entity)) define("DOLENTITY", $entity);

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

if (! empty($conf->paypal->enabled))
{
	require_once DOL_DOCUMENT_ROOT.'/paypal/lib/paypal.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/paypal/lib/paypalfunctions.lib.php';
}

$langs->load("main");
$langs->load("other");
$langs->load("dict");
$langs->load("bills");
$langs->load("companies");
$langs->load("paybox");
$langs->load("paypal");

// Clean parameters
if (! empty($conf->paypal->enabled))
{
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

    $PAYPALTOKEN=GETPOST('TOKEN');
    if (empty($PAYPALTOKEN)) $PAYPALTOKEN=GETPOST('token');
    $PAYPALPAYERID=GETPOST('PAYERID');
    if (empty($PAYPALPAYERID)) $PAYPALPAYERID=GETPOST('PayerID');
}

$FULLTAG=GETPOST('FULLTAG');
if (empty($FULLTAG)) $FULLTAG=GETPOST('fulltag');
$source=GETPOST('s','alpha')?GETPOST('s','alpha'):GETPOST('source','alpha');
$ref=GETPOST('ref');

$suffix=GETPOST("suffix",'aZ09');


// Detect $paymentmethod
$paymentmethod='';
if (preg_match('/PM=([^\.]+)/', $FULLTAG, $reg))
{
    $paymentmethod=$reg[1];
}
if (empty($paymentmethod))
{
    dol_print_error(null, 'The back url does not contains a parameter fulltag that should help us to find the payment method used');
    exit;
}
else
{
    dol_syslog("paymentmethod=".$paymentmethod);
}


$validpaymentmethod=array();
if (! empty($conf->paypal->enabled)) $validpaymentmethod['paypal']='paypal';
if (! empty($conf->paybox->enabled)) $validpaymentmethod['paybox']='paybox';
if (! empty($conf->stripe->enabled)) $validpaymentmethod['stripe']='stripe';

// Security check
if (empty($validpaymentmethod)) accessforbidden('', 0, 0, 1);


$ispaymentok = false;
// If payment is ok
$PAYMENTSTATUS=$TRANSACTIONID=$TAXAMT=$NOTE='';
// If payment is ko
$ErrorCode=$ErrorShortMsg=$ErrorLongMsg=$ErrorSeverityCode='';


$object = new stdClass();   // For triggers




/*
 * Actions
 */



/*
 * View
 */

dol_syslog("Callback url when a payment was done. query_string=".(dol_escape_htmltag($_SERVER["QUERY_STRING"])?dol_escape_htmltag($_SERVER["QUERY_STRING"]):'')." script_uri=".(dol_escape_htmltag($_SERVER["SCRIPT_URI"])?dol_escape_htmltag($_SERVER["SCRIPT_URI"]):''), LOG_DEBUG, 0, '_payment');

$tracepost = "";
foreach($_POST as $k => $v) $tracepost .= "{$k} - {$v}\n";
dol_syslog("POST=".$tracepost, LOG_DEBUG, 0, '_payment');

$head='';
if (! empty($conf->global->ONLINE_PAYMENT_CSS_URL)) $head='<link rel="stylesheet" type="text/css" href="'.$conf->global->ONLINE_PAYMENT_CSS_URL.'?lang='.$langs->defaultlang.'">'."\n";

$conf->dol_hide_topmenu=1;
$conf->dol_hide_leftmenu=1;

llxHeader($head, $langs->trans("PaymentForm"), '', '', 0, 0, '', '', '', 'onlinepaymentbody');


// Show message
print '<span id="dolpaymentspan"></span>'."\n";
print '<div id="dolpaymentdiv" align="center">'."\n";


// Show logo (search order: logo defined by PAYMENT_LOGO_suffix, then PAYMENT_LOGO, then small company logo, large company logo, theme logo, common logo)
$width=0;
// Define logo and logosmall
$logosmall=$mysoc->logo_small;
$logo=$mysoc->logo;
$paramlogo='ONLINE_PAYMENT_LOGO_'.$suffix;
if (! empty($conf->global->$paramlogo)) $logosmall=$conf->global->$paramlogo;
else if (! empty($conf->global->ONLINE_PAYMENT_LOGO)) $logosmall=$conf->global->ONLINE_PAYMENT_LOGO;
//print '<!-- Show logo (logosmall='.$logosmall.' logo='.$logo.') -->'."\n";
// Define urllogo
$urllogo='';
if (! empty($logosmall) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$logosmall))
{
	$urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('thumbs/'.$logosmall);
	$width=150;
}
elseif (! empty($logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$logo))
{
	$urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode($logo);
	$width=150;
}
// Output html code for logo
if ($urllogo)
{
	print '<center><img id="dolpaymentlogo" title="'.$title.'" src="'.$urllogo.'"';
	if ($width) print ' width="'.$width.'"';
	print '></center>';
	print '<br>';
}


if (! empty($conf->paypal->enabled))
{
	if ($paymentmethod == 'paypal')
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

			dol_syslog("Call paymentok with token=".$onlinetoken." paymentType=".$paymentType." currencyCodeType=".$currencyCodeType." payerID=".$payerID." ipaddress=".$ipaddress." FinalPaymentAmt=".$FinalPaymentAmt." fulltag=".$fulltag, LOG_DEBUG, 0, '_paypal');

			// Validate record
		    if (! empty($paymentType))
		    {
		        dol_syslog("We call GetExpressCheckoutDetails", LOG_DEBUG, 0, '_payment');
		        $resArray=getDetails($onlinetoken);
		        //var_dump($resarray);

		        dol_syslog("We call DoExpressCheckoutPayment token=".$onlinetoken." paymentType=".$paymentType." currencyCodeType=".$currencyCodeType." payerID=".$payerID." ipaddress=".$ipaddress." FinalPaymentAmt=".$FinalPaymentAmt." fulltag=".$fulltag, LOG_DEBUG, 0, '_payment');
		        $resArray=confirmPayment($onlinetoken, $paymentType, $currencyCodeType, $payerID, $ipaddress, $FinalPaymentAmt, $fulltag);

		        $ack = strtoupper($resArray["ACK"]);
		        if ($ack=="SUCCESS" || $ack=="SUCCESSWITHWARNING")
		        {
		        	$object->source		= $source;
		        	$object->ref		= $ref;
		        	$object->payerID	= $payerID;
		        	$object->fulltag	= $fulltag;
		        	$object->resArray	= $resArray;

		            // resArray was built from a string like that
		            // TOKEN=EC%2d1NJ057703V9359028&TIMESTAMP=2010%2d11%2d01T11%3a40%3a13Z&CORRELATIONID=1efa8c6a36bd8&ACK=Success&VERSION=56&BUILD=1553277&TRANSACTIONID=9B994597K9921420R&TRANSACTIONTYPE=expresscheckout&PAYMENTTYPE=instant&ORDERTIME=2010%2d11%2d01T11%3a40%3a12Z&AMT=155%2e57&FEEAMT=5%2e54&TAXAMT=0%2e00&CURRENCYCODE=EUR&PAYMENTSTATUS=Completed&PENDINGREASON=None&REASONCODE=None
		            $PAYMENTSTATUS=urldecode($resArray["PAYMENTSTATUS"]);   // Should contains 'Completed'
		            $TRANSACTIONID=urldecode($resArray["TRANSACTIONID"]);
		            $TAXAMT=urldecode($resArray["TAXAMT"]);
		            $NOTE=urldecode($resArray["NOTE"]);

		            $ispaymentok=True;
		        }
		        else
		        {
		            //Display a user friendly Error on the page using any of the following error information returned by PayPal
		            $ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
		            $ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
		            $ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
		            $ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);
		        }
		    }
		    else
		    {
		        dol_print_error('','Session expired');
		    }
		}
		else
		{
		    dol_print_error('','$PAYPALTOKEN not defined');
		}
	}
}

if (! empty($conf->paybox->enabled))
{
	if ($paymentmethod == 'paybox') $ispaymentok = true;	// We call this page only if payment is ok
}

if (! empty($conf->stripe->enabled))
{
	if ($paymentmethod == 'stripe') $ispaymentok = true;	// We call this page only if payment is ok
}


if ($ispaymentok)
{
    // Get on url call
    $fulltag            = $FULLTAG;
    $onlinetoken        = empty($PAYPALTOKEN)?$_SESSION['onlinetoken']:$PAYPALTOKEN;
    $payerID            = empty($PAYPALPAYERID)?$_SESSION['payerID']:$PAYPALPAYERID;
    // Set by newpayment.php
    $paymentType        = $_SESSION['PaymentType'];
    $currencyCodeType   = $_SESSION['currencyCodeType'];
    $FinalPaymentAmt    = $_SESSION["FinalPaymentAmt"];
    // From env
    $ipaddress          = $_SESSION['ipaddress'];
    $TRANSACTIONID      = $_SESSION['TRANSACTIONID'];

    // Appel des triggers
    include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
    $interface=new Interfaces($db);
    $result=$interface->run_triggers('PAYMENTONLINE_PAYMENT_OK',$object,$user,$langs,$conf);
    if ($result < 0) { $error++; $errors=$interface->errors; }
    // Fin appel triggers


    print $langs->trans("YourPaymentHasBeenRecorded")."<br>\n";
    print $langs->trans("ThisIsTransactionId",$TRANSACTIONID)."<br><br>\n";

    $key='ONLINE_PAYMENT_MESSAGE_OK';
    if (! empty($conf->global->$key)) print $conf->global->$key;

    $sendemail = '';
    if (! empty($conf->global->ONLINE_PAYMENT_SENDEMAIL)) $sendemail=$conf->global->ONLINE_PAYMENT_SENDEMAIL;

    $tmptag=dolExplodeIntoArray($fulltag,'.','=');

	// Send an email
    if ($sendemail)
	{
		$sendto=$sendemail;
		$from=$conf->global->MAILING_EMAIL_FROM;
		// Define $urlwithroot
		$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
		$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
		//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

		// Define link to login card
		$appli=constant('DOL_APPLICATION_TITLE');
		if (! empty($conf->global->MAIN_APPLICATION_TITLE))
		{
		    $appli=$conf->global->MAIN_APPLICATION_TITLE;
		    if (preg_match('/\d\.\d/', $appli))
		    {
		        if (! preg_match('/'.preg_quote(DOL_VERSION).'/', $appli)) $appli.=" (".DOL_VERSION.")";	// If new title contains a version that is different than core
		    }
		    else $appli.=" ".DOL_VERSION;
		}
		else $appli.=" ".DOL_VERSION;

		$urlback=$_SERVER["REQUEST_URI"];
		$topic='['.$appli.'] '.$langs->transnoentitiesnoconv("NewOnlinePaymentReceived");
		$content="";
		if (! empty($tmptag['MEM']))
		{
			$langs->load("members");
			$url=$urlwithroot."/adherents/subscription.php?rowid=".$tmptag['MEM'];
			$content.=$langs->trans("PaymentSubscription")."<br>\n";
			$content.=$langs->trans("MemberId").': '.$tmptag['MEM']."<br>\n";
			$content.=$langs->trans("Link").': <a href="'.$url.'">'.$url.'</a>'."<br>\n";
		}
		else
		{
			$content.=$langs->transnoentitiesnoconv("NewOnlinePaymentReceived")."<br>\n";
		}
		$content.="<br>\n";
		$content.=$langs->transnoentitiesnoconv("TechnicalInformation").":<br>\n";
		$content.=$langs->transnoentitiesnoconv("OnlinePaymentSystem").': '.$paymentmethod."<br>\n";
		$content.=$langs->transnoentitiesnoconv("ReturnURLAfterPayment").': '.$urlback."<br>\n";
		$content.="tag=".$fulltag."\ntoken=".$onlinetoken." paymentType=".$paymentType." currencycodeType=".$currencyCodeType." payerId=".$payerID." ipaddress=".$ipaddress." FinalPaymentAmt=".$FinalPaymentAmt;

		$ishtml=dol_textishtml($content);	// May contain urls

		require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
		$mailfile = new CMailFile($topic, $sendto, $from, $content, array(), array(), array(), '', '', 0, $ishtml);

		$result=$mailfile->sendfile();
		if ($result)
		{
			dol_syslog("EMail sent to ".$sendto, LOG_DEBUG, 0, '_payment');
		}
		else
		{
			dol_syslog("Failed to send EMail to ".$sendto, LOG_ERR, 0, '_payment');
		}
	}
}
else
{
    // Get on url call
    $fulltag            = $FULLTAG;
    $onlinetoken        = empty($PAYPALTOKEN)?$_SESSION['onlinetoken']:$PAYPALTOKEN;
    $payerID            = empty($PAYPALPAYERID)?$_SESSION['payerID']:$PAYPALPAYERID;
    // Set by newpayment.php
    $paymentType        = $_SESSION['PaymentType'];
    $currencyCodeType   = $_SESSION['currencyCodeType'];
    $FinalPaymentAmt    = $_SESSION["FinalPaymentAmt"];
    // From env
    $ipaddress          = $_SESSION['ipaddress'];

    // Appel des triggers
    include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
    $interface=new Interfaces($db);
    $result=$interface->run_triggers('PAYMENTONLINE_PAYMENT_KO',$object,$user,$langs,$conf);
    if ($result < 0) { $error++; $errors=$interface->errors; }
    // Fin appel triggers


    print $langs->trans('DoExpressCheckoutPaymentAPICallFailed') . "<br>\n";
    print $langs->trans('DetailedErrorMessage') . ": " . $ErrorLongMsg."<br>\n";
    print $langs->trans('ShortErrorMessage') . ": " . $ErrorShortMsg."<br>\n";
    print $langs->trans('ErrorCode') . ": " . $ErrorCode."<br>\n";
    print $langs->trans('ErrorSeverityCode') . ": " . $ErrorSeverityCode."<br>\n";

    if ($mysoc->email) print "\nPlease, send a screenshot of this page to ".$mysoc->email."<br>\n";

    $sendemail = '';
    if (! empty($conf->global->PAYMENTONLINE_SENDEMAIL)) $sendemail=$conf->global->PAYMENTONLINE_SENDEMAIL;
    // TODO Remove local option to keep only the generic one ?
    if ($paymentmethod == 'paypal' && ! empty($conf->global->PAYPAL_PAYONLINE_SENDEMAIL)) $sendemail=$conf->global->PAYPAL_PAYONLINE_SENDEMAIL;
    if ($paymentmethod == 'paybox' && ! empty($conf->global->PAYBOX_PAYONLINE_SENDEMAIL)) $sendemail=$conf->global->PAYBOX_PAYONLINE_SENDEMAIL;
    if ($paymentmethod == 'stripe' && ! empty($conf->global->STRIPE_PAYONLINE_SENDEMAIL)) $sendemail=$conf->global->STRIPE_PAYONLINE_SENDEMAIL;

    $tmptag=dolExplodeIntoArray($fulltag,'.','=');

    // Send an email
    if ($sendemail)
    {
        $sendto=$sendemail;
        $from=$conf->global->MAILING_EMAIL_FROM;
        // Define $urlwithroot
        $urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
        $urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
        //$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

        // Define link to login card
        $appli=constant('DOL_APPLICATION_TITLE');
        if (! empty($conf->global->MAIN_APPLICATION_TITLE))
        {
            $appli=$conf->global->MAIN_APPLICATION_TITLE;
            if (preg_match('/\d\.\d/', $appli))
            {
                if (! preg_match('/'.preg_quote(DOL_VERSION).'/', $appli)) $appli.=" (".DOL_VERSION.")";	// If new title contains a version that is different than core
            }
            else $appli.=" ".DOL_VERSION;
        }
        else $appli.=" ".DOL_VERSION;

        $urlback=$_SERVER["REQUEST_URI"];
        $topic='['.$appli.'] '.$langs->transnoentitiesnoconv("ValidationOfPaymentFailed");
        $content="";
        $content.=$langs->transnoentitiesnoconv("PaymentSystemConfirmPaymentPageWasCalledButFailed")."\n";
        $content.="\n";
        $content.=$langs->transnoentitiesnoconv("TechnicalInformation").":\n";
		$content.=$langs->transnoentitiesnoconv("OnlinePaymentSystem").': '.$paymentmethod."\n";
        $content.=$langs->transnoentitiesnoconv("ReturnURLAfterPayment").': '.$urlback."\n";
        $content.="tag=".$fulltag."\ntoken=".$onlinetoken." paymentType=".$paymentType." currencycodeType=".$currencyCodeType." payerId=".$payerID." ipaddress=".$ipaddress." FinalPaymentAmt=".$FinalPaymentAmt;

        $ishtml=dol_textishtml($content);	// May contain urls

        require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
        $mailfile = new CMailFile($topic, $sendto, $from, $content, array(), array(), array(), '', '', 0, $ishtml);

        $result=$mailfile->sendfile();
        if ($result)
        {
            dol_syslog("EMail sent to ".$sendto, LOG_DEBUG, 0, '_payment');
        }
        else
        {
            dol_syslog("Failed to send EMail to ".$sendto, LOG_ERR, 0, '_payment');
        }
    }
}


print "\n</div>\n";


htmlPrintOnlinePaymentFooter($mysoc,$langs,0,$suffix);


llxFooter('', 'public');

$db->close();
