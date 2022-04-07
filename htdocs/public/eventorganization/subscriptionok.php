<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2013	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2021		Waël Almoman			<info@almoman.com>
 * Copyright (C) 2021		Dorian Vabre			<dorian.vabre@gmail.com>
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
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// TODO This should be useless. Because entity must be retrieve from object ref and not from url.
$entity = (!empty($_GET['e']) ? (int) $_GET['e'] : (!empty($_POST['e']) ? (int) $_POST['e'] : 1));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

if (!empty($conf->paypal->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/paypal/lib/paypal.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/paypal/lib/paypalfunctions.lib.php';
}

global $dolibarr_main_url_root, $mysoc;

$langs->loadLangs(array("main", "companies", "install", "other", "eventorganization"));

$object = new stdClass(); // For triggers

$error = 0;

// Security check
$id = GETPOST("id", 'int');
$securekeyreceived = GETPOST("securekey");
$securekeytocompare = dol_hash($conf->global->EVENTORGANIZATION_SECUREKEY.'conferenceorbooth'.$id, 2);

if ($securekeyreceived != $securekeytocompare) {
	print $langs->trans('MissingOrBadSecureKey');
	exit;
}

// Security check
if (empty($conf->eventorganization->enabled)) {
	accessforbidden('', 0, 0, 1);
}


/*
 * Actions
 */



/*
 * View
 */

$now = dol_now();

dol_syslog("Callback url when a payment was done. query_string=".(dol_escape_htmltag($_SERVER["QUERY_STRING"]) ?dol_escape_htmltag($_SERVER["QUERY_STRING"]) : '')." script_uri=".(dol_escape_htmltag($_SERVER["SCRIPT_URI"]) ?dol_escape_htmltag($_SERVER["SCRIPT_URI"]) : ''), LOG_DEBUG, 0, '_payment');

$tracepost = "";
foreach ($_POST as $k => $v) {
	$tracepost .= "{$k} - {$v}\n";
}
dol_syslog("POST=".$tracepost, LOG_DEBUG, 0, '_payment');

$head = '';
if (!empty($conf->global->ONLINE_PAYMENT_CSS_URL)) {
	$head = '<link rel="stylesheet" type="text/css" href="'.$conf->global->ONLINE_PAYMENT_CSS_URL.'?lang='.$langs->defaultlang.'">'."\n";
}

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

if (!empty($conf->global->EVENTORGANIZATION_IMAGE_PUBLIC_INTERFACE)) {
	print '<div class="backimagepubliceventorganizationsubscription">';
	print '<img id="idEVENTORGANIZATION_IMAGE_PUBLIC_INTERFACE" src="'.$conf->global->EVENTORGANIZATION_IMAGE_PUBLIC_INTERFACE.'">';
	print '</div>';
}

print '<br><br><br>';

print $langs->trans("SubscriptionOk");

print "\n</div>\n";


htmlPrintOnlinePaymentFooter($mysoc, $langs, 0, $suffix);


// Clean session variables to avoid duplicate actions if post is resent
unset($_SESSION["FinalPaymentAmt"]);
unset($_SESSION["TRANSACTIONID"]);


llxFooter('', 'public');

$db->close();
