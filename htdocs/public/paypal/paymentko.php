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
 *     	\file       htdocs/public/paypal/paymentko.php
 *		\ingroup    paypal
 *		\brief      File to show page after a failed payment.
 *                  This page is called by paypal with url provided to payal competed with parameter TOKEN=xxx
 *                  This token can be used to get more informations.
 */

define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// TODO This should be useless. Because entity must be retreive from object ref and not from url.
$entity=(! empty($_GET['entity']) ? (int) $_GET['entity'] : (! empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) define("DOLENTITY", $entity);

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/paypal/lib/paypal.lib.php';
require_once DOL_DOCUMENT_ROOT.'/paypal/lib/paypalfunctions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

// Security check
if (empty($conf->paypal->enabled)) accessforbidden('',0,0,1);

$langs->load("main");
$langs->load("other");
$langs->load("dict");
$langs->load("bills");
$langs->load("companies");
$langs->load("paybox");
$langs->load("paypal");
$langs->load("stripe");

$PAYPALTOKEN=GETPOST('TOKEN');
if (empty($PAYPALTOKEN)) $PAYPALTOKEN=GETPOST('token');
$PAYPALPAYERID=GETPOST('PAYERID');
if (empty($PAYPALPAYERID)) $PAYPALPAYERID=GETPOST('PayerID');
$FULLTAG=GETPOST('FULLTAG');
if (empty($FULLTAG)) $FULLTAG=GETPOST('fulltag');


$object = new stdClass();   // For triggers

$paymentmethod='paypal';


/*
 * Actions
 */




/*
 * View
 */

dol_syslog("Callback url when a PayPal payment was canceled. query_string=".(dol_escape_htmltag($_SERVER["QUERY_STRING"])?dol_escape_htmltag($_SERVER["QUERY_STRING"]):'')." script_uri=".(dol_escape_htmltag($_SERVER["SCRIPT_URI"])?dol_escape_htmltag($_SERVER["SCRIPT_URI"]):''), LOG_DEBUG, 0, '_paypal');

$tracepost = "";
foreach($_POST as $k => $v) $tracepost .= "{$k} - {$v}\n";
dol_syslog("POST=".$tracepost, LOG_DEBUG, 0, '_paypal');

if (! empty($_SESSION['ipaddress']))      // To avoid to make action twice
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
    $result=$interface->run_triggers('PAYPAL_PAYMENT_KO',$object,$user,$langs,$conf);
    if ($result < 0) { $error++; $errors=$interface->errors; }
    // Fin appel triggers

    // Send an email
    $sendemail = '';
    if (! empty($conf->global->ONLINE_PAYMENT_SENDEMAIL))  $sendemail=$conf->global->ONLINE_PAYMENT_SENDEMAIL;

    if ($sendemail)
    {
    	$sendto=$sendemail;
    	$from=$conf->global->MAILING_EMAIL_FROM;

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
    	$topic='['.$appli.'] '.$langs->transnoentitiesnoconv("NewOnlinePaymentFailed");
    	$content="";
    	$content.=$langs->transnoentitiesnoconv("ValidationOfOnlinePaymentFailed")."\n";
    	$content.="\n";
    	$content.=$langs->transnoentitiesnoconv("TechnicalInformation").":\n";
    	$content.=$langs->transnoentitiesnoconv("OnlinePaymentSystem").': '.$paymentmethod."<br>\n";
    	$content.=$langs->transnoentitiesnoconv("ReturnURLAfterPayment").': '.$urlback."\n";
    	$content.="tag=".$fulltag."\ntoken=".$onlinetoken." paymentType=".$paymentType." currencycodeType=".$currencyCodeType." payerId=".$payerID." ipaddress=".$ipaddress." FinalPaymentAmt=".$FinalPaymentAmt;

    	require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
    	$mailfile = new CMailFile($topic, $sendto, $from, $content);

    	$result=$mailfile->sendfile();
    	if ($result)
    	{
    		dol_syslog("EMail sent to ".$sendto, LOG_DEBUG, 0, '_paypal');
    	}
    	else
    	{
    		dol_syslog("Failed to send EMail to ".$sendto, LOG_ERR, 0, '_paypal');
    	}
    }

    unset($_SESSION['ipaddress']);
}


$head='';
if (! empty($conf->global->ONLINE_PAYMENT_CSS_URL)) $head='<link rel="stylesheet" type="text/css" href="'.$conf->global->ONLINE_PAYMENT_CSS_URL.'?lang='.$langs->defaultlang.'">'."\n";

$conf->dol_hide_topmenu=1;
$conf->dol_hide_leftmenu=1;

llxHeader($head, $langs->trans("PaymentForm"), '', '', 0, 0, '', '', '', 'onlinepaymentbody');


// Show ko message
print '<span id="dolpaymentspan"></span>'."\n";
print '<div id="dolpaymentdiv" align="center">'."\n";
print $langs->trans("YourPaymentHasNotBeenRecorded")."<br><br>";

$key='ONLINE_PAYMENT_MESSAGE_KO';
if (! empty($conf->global->$key)) print $conf->global->$key;

print "\n</div>\n";


htmlPrintOnlinePaymentFooter($mysoc,$langs,0,$suffix);


llxFooter('', 'public');

$db->close();
