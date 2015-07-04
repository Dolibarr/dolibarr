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
 *     	\file       htdocs/public/paybox/paymentko.php
 *		\ingroup    paybox
 *		\brief      File to show page after a failed payment
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




/*
 * Actions
 */





/*
 * View
 */

dol_syslog("Callback url when a PayBox payment was canceled. query_string=".(empty($_SERVER["QUERY_STRING"])?'':$_SERVER["QUERY_STRING"])." script_uri=".(empty($_SERVER["SCRIPT_URI"])?'':$_SERVER["SCRIPT_URI"]), LOG_DEBUG, 0, '_paybox');

$tracepost = "";
foreach($_POST as $k => $v) $tracepost .= "{$k} - {$v}\n";
dol_syslog("POST=".$tracepost, LOG_DEBUG, 0, '_paybox');


// Send an email
if (! empty($conf->global->PAYBOX_PAYONLINE_SENDEMAIL))
{
	$sendto=$conf->global->PAYBOX_PAYONLINE_SENDEMAIL;
	$from=$conf->global->MAILING_EMAIL_FROM;

	$urlback=$_SERVER["REQUEST_URI"];
	$topic='['.$conf->global->MAIN_APPLICATION_TITLE.'] '.$langs->transnoentitiesnoconv("NewPayboxPaymentFailed");
	$content=$langs->transnoentitiesnoconv("NewPayboxPaymentFailed")."\n".$fulltag;
	require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
	$mailfile = new CMailFile($topic, $sendto, $from, $content);

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


llxHeaderPayBox($langs->trans("PaymentForm"));


// Show message
print '<span id="dolpaymentspan"></span>'."\n";
print '<div id="dolpaymentdiv" align="center">'."\n";

print $langs->trans("YourPaymentHasNotBeenRecorded")."<br><br>\n";

if (! empty($conf->global->PAYBOX_MESSAGE_KO)) print $conf->global->PAYBOX_MESSAGE_KO;

print "\n</div>\n";


html_print_paybox_footer($mysoc,$langs);


llxFooterPayBox();

$db->close();
