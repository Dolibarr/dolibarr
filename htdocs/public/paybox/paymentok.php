<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/paybox/lib/paybox.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

// Security check
if (empty($conf->paybox->enabled)) accessforbidden('',1,1,1);

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


print $langs->trans("YourPaymentHasBeenRecorded")."<br>\n";

if (! empty($conf->global->PAYBOX_MESSAGE_OK)) print $conf->global->PAYBOX_MESSAGE_OK;

print "\n</div>\n";


html_print_paybox_footer($mysoc,$langs);


llxFooterPayBox();

$db->close();
?>
