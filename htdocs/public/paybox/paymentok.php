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
 *		\author	    Laurent Destailleur
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

// Security check
if (empty($conf->paybox->enabled)) accessforbidden('',0,0,1);

$langs->load("main");
$langs->load("other");
$langs->load("dict");
$langs->load("bills");
$langs->load("companies");
$langs->load("paybox");
$langs->load("paypal");

/*$source=GETPOST('source');
$ref=GETPOST('ref');
$PAYBOXTOKEN=GETPOST('TOKEN');
if (empty($PAYBOXTOKEN)) $PAYBOXTOKEN=GETPOST('token');
$PAYBOXPAYERID=GETPOST('PAYERID');
if (empty($PAYBOXPAYERID)) $PAYBOXPAYERID=GETPOST('PayerID');
*/
$PAYBOXFULLTAG=GETPOST('FULLTAG');
if (empty($PAYBOXFULLTAG)) $PAYBOXFULLTAG=GETPOST('fulltag');


/*
 * Actions
 */





/*
 * View
 */

dol_syslog("Callback url when a PayBox payment was done. query_string=".(empty($_SERVER["QUERY_STRING"])?'':$_SERVER["QUERY_STRING"])." script_uri=".(empty($_SERVER["SCRIPT_URI"])?'':$_SERVER["SCRIPT_URI"]), LOG_DEBUG, 0, '_paybox');

$tracepost = "";
foreach($_POST as $k => $v) $tracepost .= "{$k} - {$v}\n";
dol_syslog("POST=".$tracepost, LOG_DEBUG, 0, '_paybox');

llxHeaderPayBox($langs->trans("PaymentForm"));


// Show message
print '<span id="dolpaymentspan"></span>'."\n";
print '<div id="dolpaymentdiv" align="center">'."\n";

// Get on url call
/*
$token              = $PAYBOXTOKEN;
*/
$fulltag            = $PAYBOXFULLTAG;
/*$payerID            = $PAYBOXPAYERID;
// Set by newpayment.php
$paymentType        = $_SESSION['PaymentType'];
$currencyCodeType   = $_SESSION['currencyCodeType'];
$FinalPaymentAmt    = $_SESSION["Payment_Amount"];
// From env
$ipaddress          = $_SESSION['ipaddress'];

dol_syslog("Call newpaymentok with token=".$token." paymentType=".$paymentType." currencyCodeType=".$currencyCodeType." payerID=".$payerID." ipaddress=".$ipaddress." FinalPaymentAmt=".$FinalPaymentAmt." fulltag=".$fulltag);
*/


print $langs->trans("YourPaymentHasBeenRecorded")."<br><br>\n";

if (! empty($conf->global->PAYBOX_MESSAGE_OK)) print $conf->global->PAYBOX_MESSAGE_OK;

// Appel des triggers
include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
$interface=new Interfaces($db);
$result=$interface->run_triggers('PAYBOX_PAYMENT_OK',$object,$user,$langs,$conf);
if ($result < 0) { $error++; $errors=$interface->errors; }
// Fin appel triggers


// Send an email
if (! empty($conf->global->PAYBOX_PAYONLINE_SENDEMAIL))
{
	$sendto=$conf->global->PAYBOX_PAYONLINE_SENDEMAIL;
	$from=$conf->global->MAILING_EMAIL_FROM;
	// Define $urlwithroot
	$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
	$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
	//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

	$urlback=$_SERVER["REQUEST_URI"];
	$topic='['.$conf->global->MAIN_APPLICATION_TITLE.'] '.$langs->transnoentitiesnoconv("NewPayboxPaymentReceived");
	$tmptag=dolExplodeIntoArray($fulltag,'.','=');
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
		$content.=$langs->transnoentitiesnoconv("NewPayboxPaymentReceived")."<br>\n";
	}
	$content.="<br>\n";
	$content.=$langs->transnoentitiesnoconv("TechnicalInformation").":<br>\n";
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

html_print_paybox_footer($mysoc,$langs);


llxFooterPayBox();

$db->close();
