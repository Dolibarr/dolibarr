<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2013	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *     	\file       htdocs/public/payment/paymentko.php
 *		\ingroup    core
 *		\brief      File to show page after a failed payment.
 *                  This page is called by payment system with url provided to it competed with parameter TOKEN=xxx
 *                  This token can be used to get more information.
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
// Do not use GETPOST here, function is not defined and this test must be done before including main.inc.php
// Because 2 entities can have the same ref.
$entity = (!empty($_GET['e']) ? (int) $_GET['e'] : (!empty($_POST['e']) ? (int) $_POST['e'] : 1));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

'@phan-var-force CommonObject $object';

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

if (isModEnabled('paypal')) {
	require_once DOL_DOCUMENT_ROOT.'/paypal/lib/paypal.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/paypal/lib/paypalfunctions.lib.php';
}

$langs->loadLangs(array("main", "other", "dict", "bills", "companies", "paybox", "paypal", "stripe"));

if (isModEnabled('paypal')) {
	$PAYPALTOKEN = GETPOST('TOKEN');
	if (empty($PAYPALTOKEN)) {
		$PAYPALTOKEN = GETPOST('token');
	}
	$PAYPALPAYERID = GETPOST('PAYERID');
	if (empty($PAYPALPAYERID)) {
		$PAYPALPAYERID = GETPOST('PayerID');
	}
}
if (isModEnabled('paybox')) {
}
if (isModEnabled('stripe')) {
}

$FULLTAG = GETPOST('FULLTAG');
if (empty($FULLTAG)) {
	$FULLTAG = GETPOST('fulltag');
}

$suffix = GETPOST("suffix", 'aZ09');


// Detect $paymentmethod
$paymentmethod = '';
$reg = array();
if (preg_match('/PM=([^\.]+)/', $FULLTAG, $reg)) {
	$paymentmethod = $reg[1];
}
if (empty($paymentmethod)) {
	dol_print_error(null, 'The back url does not contain a parameter fulltag that should help us to find the payment method used');
	exit;
} else {
	dol_syslog("paymentmethod=".$paymentmethod);
}

// Detect $ws
$ws = preg_match('/WS=([^\.]+)/', $FULLTAG, $reg_ws) ? $reg_ws[1] : 0;
if ($ws) {
	dol_syslog("Paymentko.php page is invoked from a website with ref ".$ws.". It performs actions and then redirects back to this website. A page with ref paymentko must be created for this website.", LOG_DEBUG, 0, '_payment');
}


$validpaymentmethod = getValidOnlinePaymentMethods($paymentmethod);

// Security check
if (empty($validpaymentmethod)) {
	httponly_accessforbidden('No valid payment mode');
}


$object = new stdClass(); // For triggers


/*
 * Actions
 */

// None



/*
 * View
 */

// Check if we have redirtodomain to do.
if ($ws) {
	$doactionsthenredirect = 1;
}


dol_syslog("Callback url when an online payment is refused or canceled. query_string=".(empty($_SERVER["QUERY_STRING"]) ? '' : $_SERVER["QUERY_STRING"])." script_uri=".(empty($_SERVER["SCRIPT_URI"]) ? '' : $_SERVER["SCRIPT_URI"]), LOG_DEBUG, 0, '_payment');

$tracepost = "";
foreach ($_POST as $k => $v) {
	if (is_scalar($k) && is_scalar($v)) {
		$tracepost .= "$k - $v\n";
	}
}
dol_syslog("POST=".$tracepost, LOG_DEBUG, 0, '_payment');


// Set $appli for emails title
$appli = $mysoc->name;


if (!empty($_SESSION['ipaddress'])) {      // To avoid to make action twice
	// Get on url call
	$fulltag            = $FULLTAG;
	$onlinetoken        = empty($PAYPALTOKEN) ? $_SESSION['onlinetoken'] : $PAYPALTOKEN;
	$payerID            = empty($PAYPALPAYERID) ? $_SESSION['payerID'] : $PAYPALPAYERID;
	// Set by newpayment.php
	$paymentType        = $_SESSION['PaymentType'];
	$currencyCodeType   = $_SESSION['currencyCodeType'];
	$FinalPaymentAmt    = $_SESSION['FinalPaymentAmt'];
	// From env
	$ipaddress          = $_SESSION['ipaddress'];
	$errormessage       = $_SESSION['errormessage'];

	if (is_object($object) && method_exists($object, 'call_trigger')) {
		// Call trigger @phan-suppress-next-line PhanUndeclaredMethod
		$result = $object->call_trigger('PAYMENTONLINE_PAYMENT_KO', $user);
		if ($result < 0) {
			$error++;
		}
		// End call triggers
	}

	// Send an email
	$sendemail = getDolGlobalString('ONLINE_PAYMENT_SENDEMAIL');

	// Send warning of error to administrator
	if ($sendemail) {
		$companylangs = new Translate('', $conf);
		$companylangs->setDefaultLang($mysoc->default_lang);
		$companylangs->loadLangs(array('main', 'members', 'bills', 'paypal', 'paybox', 'stripe'));

		$from = getDolGlobalString('MAILING_EMAIL_FROM', getDolGlobalString("MAIN_MAIL_EMAIL_FROM"));
		$sendto = $sendemail;

		$urlback = $_SERVER["REQUEST_URI"];
		$topic = '['.$appli.'] '.$companylangs->transnoentitiesnoconv("NewOnlinePaymentFailed");
		$content = "";
		$content .= '<span style="color: orange">'.$companylangs->transnoentitiesnoconv("ValidationOfOnlinePaymentFailed")."</span>\n";

		$content .= "<br><br>\n";
		$content .= '<u>'.$companylangs->transnoentitiesnoconv("TechnicalInformation").":</u><br>\n";
		$content .= $companylangs->transnoentitiesnoconv("OnlinePaymentSystem").': <strong>'.$paymentmethod."</strong><br>\n";
		$content .= $companylangs->transnoentitiesnoconv("ReturnURLAfterPayment").': '.$urlback."<br>\n";
		$content .= $companylangs->transnoentitiesnoconv("Error").': '.$errormessage."<br>\n";
		$content .= "<br>\n";
		$content .= "tag=".$fulltag." token=".$onlinetoken." paymentType=".$paymentType." currencycodeType=".$currencyCodeType." payerId=".$payerID." ipaddress=".$ipaddress." FinalPaymentAmt=".$FinalPaymentAmt;

		$ishtml = dol_textishtml($content); // May contain urls

		require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
		$mailfile = new CMailFile($topic, $sendto, $from, $content, array(), array(), array(), '', '', 0, $ishtml ? 1 : 0);

		$result = $mailfile->sendfile();
		if ($result) {
			dol_syslog("EMail sent to ".$sendto, LOG_DEBUG, 0, '_payment');
		} else {
			dol_syslog("Failed to send EMail to ".$sendto, LOG_ERR, 0, '_payment');
		}
	}

	unset($_SESSION['ipaddress']);
}

// Show answer page
if (empty($doactionsthenredirect)) {
	$head = '';
	if (getDolGlobalString('ONLINE_PAYMENT_CSS_URL')) {
		$head = '<link rel="stylesheet" type="text/css" href="' . getDolGlobalString('ONLINE_PAYMENT_CSS_URL').'?lang='.$langs->defaultlang.'">'."\n";
	}

	$conf->dol_hide_topmenu = 1;
	$conf->dol_hide_leftmenu = 1;

	$replacemainarea = (empty($conf->dol_hide_leftmenu) ? '<div>' : '').'<div>';
	llxHeader($head, $langs->trans("PaymentForm"), '', '', 0, 0, '', '', '', 'onlinepaymentbody', $replacemainarea);


	// Show ko message
	print '<span id="dolpaymentspan"></span>'."\n";
	print '<div id="dolpaymentdiv" align="center">'."\n";

	// Show logo (search order: logo defined by PAYMENT_LOGO_suffix, then PAYMENT_LOGO, then small company logo, large company logo, theme logo, common logo)
	// Define logo and logosmall
	$logosmall = $mysoc->logo_small;
	$logo = $mysoc->logo;
	$paramlogo = 'ONLINE_PAYMENT_LOGO_'.$suffix;
	if (getDolGlobalString($paramlogo)) {
		$logosmall = getDolGlobalString($paramlogo);
	} elseif (getDolGlobalString('ONLINE_PAYMENT_LOGO')) {
		$logosmall = getDolGlobalString('ONLINE_PAYMENT_LOGO');
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
		if (!getDolGlobalString('MAIN_HIDE_POWERED_BY')) {
			print '<div class="poweredbypublicpayment opacitymedium right"><a class="poweredbyhref" href="https://www.dolibarr.org?utm_medium=website&utm_source=poweredby" target="dolibarr" rel="noopener">'.$langs->trans("PoweredBy").'<br><img class="poweredbyimg" src="'.DOL_URL_ROOT.'/theme/dolibarr_logo.svg" width="80px"></a></div>';
		}
		print '</div>';
	}
	if (getDolGlobalString('MAIN_IMAGE_PUBLIC_PAYMENT')) {
		print '<div class="backimagepublicpayment">';
		print '<img id="idMAIN_IMAGE_PUBLIC_PAYMENT" src="' . getDolGlobalString('MAIN_IMAGE_PUBLIC_PAYMENT').'">';
		print '</div>';
	}


	print '<br><br>';


	print $langs->trans("YourPaymentHasNotBeenRecorded")."<br><br>";

	$key = 'ONLINE_PAYMENT_MESSAGE_KO';
	if (getDolGlobalString($key)) {
		print $conf->global->$key;
	}

	$type = GETPOST('s', 'alpha');
	$ref = GETPOST('ref', 'alphanohtml');
	$tag = GETPOST('tag', 'alpha');
	require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
	if ($type || $tag) {
		$urlsubscription = getOnlinePaymentUrl(0, ($type ? $type : 'free'), $ref, $FinalPaymentAmt, $tag);

		print $langs->trans("ClickHereToTryAgain", $urlsubscription);
	}

	print "\n</div>\n";


	htmlPrintOnlineFooter($mysoc, $langs, 0, $suffix);

	llxFooter('', 'public');
}


$db->close();


// If option to do a redirect somewhere else is defined.
if (!empty($doactionsthenredirect)) {
	// Redirect to an error page
	// Paymentko page must be created for the specific website
	$ext_urlko = DOL_URL_ROOT.'/public/website/index.php?website='.urlencode($ws).'&pageref=paymentko&fulltag='.$FULLTAG;
	print "<script>window.top.location.href = '".dol_escape_js($ext_urlko)."';</script>";
}
