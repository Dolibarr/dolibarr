<?php
/* Copyright (C) 2017	Laurent Destailleur		<eldy@users.sourceforge.net>
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
 *     	\file       htdocs/public/stripe/paymentok.php
 *		\ingroup    core
 *		\brief      File to show page after a successful payment
 *                  This page is called by payment system with url provided to it completed with parameter FULLTAG=xxx
 *                  More data like token are saved into session. This token can be used to get more informations.
 */

define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// TODO This should be useless. Because entity must be retreive from object ref and not from url.
$entity=(! empty($_GET['entity']) ? (int) $_GET['entity'] : (! empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) define("DOLENTITY", $entity);

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

$langs->load("main");
$langs->load("other");
$langs->load("dict");
$langs->load("bills");
$langs->load("companies");
$langs->load("paybox");
$langs->load("paypal");

$FULLTAG=GETPOST('FULLTAG');
if (empty($FULLTAG)) $FULLTAG=GETPOST('fulltag');
$source=GETPOST('source');
$ref=GETPOST('ref');

// Security check
if (empty($conf->stripe->enabled)) accessforbidden('', 0, 0, 1);


$ispaymentok = false;
// If payment is ok
$PAYMENTSTATUS=$TRANSACTIONID=$TAXAMT=$NOTE='';
// If payment is ko
$ErrorCode=$ErrorShortMsg=$ErrorLongMsg=$ErrorSeverityCode='';


$object = new stdClass();   // For triggers

$paymentmethod='stripe';


/*
 * Actions
 */



/*
 * View
 */

dol_syslog("Callback url when a payment was done. query_string=".(empty($_SERVER["QUERY_STRING"])?'':$_SERVER["QUERY_STRING"])." script_uri=".(empty($_SERVER["SCRIPT_URI"])?'':$_SERVER["SCRIPT_URI"]), LOG_DEBUG, 0, '_stripe');

$tracepost = "";
foreach($_POST as $k => $v) $tracepost .= "{$k} - {$v}\n";
dol_syslog("POST=".$tracepost, LOG_DEBUG, 0, '_stripe');

$head='';
if (! empty($conf->global->ONLINE_PAYMENT_CSS_URL)) $head='<link rel="stylesheet" type="text/css" href="'.$conf->global->ONLINE_PAYMENT_CSS_URL.'?lang='.$langs->defaultlang.'">'."\n";

$conf->dol_hide_topmenu=1;
$conf->dol_hide_leftmenu=1;

llxHeader($head, $langs->trans("PaymentForm"), '', '', 0, 0, '', '', '', 'onlinepaymentbody');



// Show message
print '<span id="dolpaymentspan"></span>'."\n";
print '<div id="dolpaymentdiv" align="center">'."\n";

$ispaymentok = true;    // We call this page if payment is ok
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
    $result=$interface->run_triggers('STRIPE_PAYMENT_OK',$object,$user,$langs,$conf);
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
			$url=$urlwithroot."/adherents/card_subscriptions.php?rowid=".$tmptag['MEM'];
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
			dol_syslog("EMail sent to ".$sendto, LOG_DEBUG, 0, '_stripe');
		}
		else
		{
			dol_syslog("Failed to send EMail to ".$sendto, LOG_ERR, 0, '_stripe');
		}
	}
}


print "\n</div>\n";


htmlPrintOnlinePaymentFooter($mysoc,$langs,0,$suffix);


llxFooter('', 'public');

$db->close();
