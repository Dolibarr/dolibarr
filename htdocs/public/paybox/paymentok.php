<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *     	\file       htdocs/public/paybox/paymentok.php
 *		\ingroup    paybox
 *		\brief      File to show page after a successful payment
 */

define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// TODO This should be useless. Because entity must be retreive from object ref and not from url.
$entity=(! empty($_GET['entity']) ? (int) $_GET['entity'] : (! empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) define("DOLENTITY", $entity);

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/paybox/lib/paybox.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

// Security check
if (empty($conf->paybox->enabled)) accessforbidden('',0,0,1);

$langs->loadLangs(array("main", "other", "dict", "bills", "companies", "paybox", "paypal", "stripe"));

/*$source=GETPOST('source');
$ref=GETPOST('ref');
$PAYBOXTOKEN=GETPOST('TOKEN');
if (empty($PAYBOXTOKEN)) $PAYBOXTOKEN=GETPOST('token');
$PAYBOXPAYERID=GETPOST('PAYERID');
if (empty($PAYBOXPAYERID)) $PAYBOXPAYERID=GETPOST('PayerID');
*/
$FULLTAG=GETPOST('FULLTAG');
if (empty($FULLTAG)) $FULLTAG=GETPOST('fulltag');


$object = new stdClass();   // For triggers

$paymentmethod='paybox';


/*
 * Actions
 */





/*
 * View
 */

dol_syslog("Callback url when a PayBox payment was done. query_string=".(dol_escape_htmltag($_SERVER["QUERY_STRING"])?dol_escape_htmltag($_SERVER["QUERY_STRING"]):'')." script_uri=".(dol_escape_htmltag($_SERVER["SCRIPT_URI"])?dol_escape_htmltag($_SERVER["SCRIPT_URI"]):''), LOG_DEBUG, 0, '_paybox');

$tracepost = "";
foreach($_POST as $k => $v) $tracepost .= "{$k} - {$v}\n";
dol_syslog("POST=".$tracepost, LOG_DEBUG, 0, '_paybox');


$head='';
if (! empty($conf->global->ONLINE_PAYMENT_CSS_URL)) $head='<link rel="stylesheet" type="text/css" href="'.$conf->global->ONLINE_PAYMENT_CSS_URL.'?lang='.$langs->defaultlang.'">'."\n";

$conf->dol_hide_topmenu=1;
$conf->dol_hide_leftmenu=1;

llxHeader($head, $langs->trans("PaymentForm"), '', '', 0, 0, '', '', '', 'onlinepaymentbody');


// Show message
print '<span id="dolpaymentspan"></span>'."\n";
print '<div id="dolpaymentdiv" align="center">'."\n";

// Get on url call
/*
$onlinetoken              = $PAYBOXTOKEN;
*/
$fulltag            = $FULLTAG;
/*$payerID            = $PAYBOXPAYERID;
// Set by newpayment.php
$paymentType        = $_SESSION['PaymentType'];
$currencyCodeType   = $_SESSION['currencyCodeType'];
$FinalPaymentAmt    = $_SESSION["FinalPaymentAmt"];
// From env
$ipaddress          = $_SESSION['ipaddress'];

dol_syslog("Call newpaymentok with token=".$onlinetoken." paymentType=".$paymentType." currencyCodeType=".$currencyCodeType." payerID=".$payerID." ipaddress=".$ipaddress." FinalPaymentAmt=".$FinalPaymentAmt." fulltag=".$fulltag);
*/


print $langs->trans("YourPaymentHasBeenRecorded")."<br><br>\n";

$key='ONLINE_PAYMENT_MESSAGE_OK';
if (! empty($conf->global->$key)) print $conf->global->$key;


// Appel des triggers
include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
$interface=new Interfaces($db);
$result=$interface->run_triggers('PAYBOX_PAYMENT_OK',$object,$user,$langs,$conf);
if ($result < 0) { $error++; $errors=$interface->errors; }
// Fin appel triggers

$tmptag=dolExplodeIntoArray($fulltag,'.','=');

// Send an email
if (! empty($conf->global->ONLINE_PAYMENT_SENDEMAIL))
{
	$sendto=$conf->global->ONLINE_PAYMENT_SENDEMAIL;
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
	$content.="tag=".$fulltag."<br>\n";

	$ishtml=dol_textishtml($content);	// May contain urls

	require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
	$mailfile = new CMailFile($topic, $sendto, $from, $content, array(), array(), array(), '', '', 0, $ishtml);

	// Send an email
	$result=$mailfile->sendfile();
	if ($result)
	{
		dol_syslog("EMail sent to ".$sendto, LOG_DEBUG, 0, '_paybox');
	}
	else
	{
		dol_syslog("Failed to send EMail to ".$sendto, LOG_ERR, 0, '_paybox');
	}
}



print "\n</div>\n";

htmlPrintOnlinePaymentFooter($mysoc,$langs,0,$suffix);


llxFooter('', 'public');

$db->close();
