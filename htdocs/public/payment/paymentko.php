<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2013	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@inodbox.com>
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
 *                  This token can be used to get more informations.
 */

if (!defined('NOLOGIN'))		define("NOLOGIN", 1); // This means this output page does not require to be logged.
if (!defined('NOCSRFCHECK'))	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
if (!defined('NOIPCHECK'))		define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
if (!defined('NOBROWSERNOTIF')) define('NOBROWSERNOTIF', '1');

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// TODO This should be useless. Because entity must be retrieve from object ref and not from url.
$entity = (!empty($_GET['e']) ? (int) $_GET['e'] : (!empty($_POST['e']) ? (int) $_POST['e'] : 1));
if (is_numeric($entity)) define("DOLENTITY", $entity);

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

if (!empty($conf->paypal->enabled))
{
	require_once DOL_DOCUMENT_ROOT.'/paypal/lib/paypal.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/paypal/lib/paypalfunctions.lib.php';
}

$langs->loadLangs(array("main", "other", "dict", "bills", "companies", "paybox", "paypal", "stripe"));

if (!empty($conf->paypal->enabled))
{
	$PAYPALTOKEN = GETPOST('TOKEN');
	if (empty($PAYPALTOKEN)) $PAYPALTOKEN = GETPOST('token');
	$PAYPALPAYERID = GETPOST('PAYERID');
	if (empty($PAYPALPAYERID)) $PAYPALPAYERID = GETPOST('PayerID');
}
if (!empty($conf->paybox->enabled))
{
}
if (!empty($conf->stripe->enabled))
{
}

$FULLTAG = GETPOST('FULLTAG');
if (empty($FULLTAG)) $FULLTAG = GETPOST('fulltag');

$suffix = GETPOST("suffix", 'aZ09');


// Detect $paymentmethod
$paymentmethod = '';
$reg = array();
if (preg_match('/PM=([^\.]+)/', $FULLTAG, $reg))
{
	$paymentmethod = $reg[1];
}
if (empty($paymentmethod))
{
	dol_print_error(null, 'The back url does not contains a parameter fulltag that should help us to find the payment method used');
	exit;
} else {
	dol_syslog("paymentmethod=".$paymentmethod);
}


$validpaymentmethod = array();
if (!empty($conf->paypal->enabled)) $validpaymentmethod['paypal'] = 'paypal';
if (!empty($conf->paybox->enabled)) $validpaymentmethod['paybox'] = 'paybox';
if (!empty($conf->stripe->enabled)) $validpaymentmethod['stripe'] = 'stripe';


// Security check
if (empty($validpaymentmethod)) accessforbidden('', 0, 0, 1);


$object = new stdClass(); // For triggers


/*
 * Actions
 */




/*
 * View
 */

dol_syslog("Callback url when an online payment is refused or canceled. query_string=".(empty($_SERVER["QUERY_STRING"]) ? '' : $_SERVER["QUERY_STRING"])." script_uri=".(empty($_SERVER["SCRIPT_URI"]) ? '' : $_SERVER["SCRIPT_URI"]), LOG_DEBUG, 0, '_payment');

$tracepost = "";
foreach ($_POST as $k => $v) $tracepost .= "{$k} - {$v}\n";
dol_syslog("POST=".$tracepost, LOG_DEBUG, 0, '_payment');


if (!empty($_SESSION['ipaddress']))      // To avoid to make action twice
{
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
		// Call trigger
		$result = $object->call_trigger('PAYMENTONLINE_PAYMENT_KO', $user);
		if ($result < 0) $error++;
		// End call triggers
	}

	// Send an email
	$sendemail = '';
   	if (!empty($conf->global->ONLINE_PAYMENT_SENDEMAIL))
   	{
		$sendemail = $conf->global->ONLINE_PAYMENT_SENDEMAIL;
	}

	// Send warning of error to administrator
	if ($sendemail)
	{
		$companylangs = new Translate('', $conf);
		$companylangs->setDefaultLang($mysoc->default_lang);
		$companylangs->loadLangs(array('main', 'members', 'bills', 'paypal', 'paybox'));

		$from = $conf->global->MAILING_EMAIL_FROM;
		$sendto = $sendemail;

		// Define link to login card
		$appli = constant('DOL_APPLICATION_TITLE');
		if (!empty($conf->global->MAIN_APPLICATION_TITLE))
		{
			$appli = $conf->global->MAIN_APPLICATION_TITLE;
			if (preg_match('/\d\.\d/', $appli))
			{
				if (!preg_match('/'.preg_quote(DOL_VERSION).'/', $appli)) $appli .= " (".DOL_VERSION.")"; // If new title contains a version that is different than core
			} else $appli .= " ".DOL_VERSION;
		} else $appli .= " ".DOL_VERSION;

		$urlback = $_SERVER["REQUEST_URI"];
		$topic = '['.$appli.'] '.$companylangs->transnoentitiesnoconv("NewOnlinePaymentFailed");
		$content = "";
		$content .= '<font color="orange">'.$companylangs->transnoentitiesnoconv("ValidationOfOnlinePaymentFailed")."</font>\n";

		$content .= "<br><br>\n";
		$content .= '<u>'.$companylangs->transnoentitiesnoconv("TechnicalInformation").":</u><br>\n";
		$content .= $companylangs->transnoentitiesnoconv("OnlinePaymentSystem").': <strong>'.$paymentmethod."</strong><br>\n";
		$content .= $companylangs->transnoentitiesnoconv("ReturnURLAfterPayment").': '.$urlback."<br>\n";
		$content .= $companylangs->transnoentitiesnoconv("Error").': '.$errormessage."<br>\n";
		$content .= "<br>\n";
		$content .= "tag=".$fulltag." token=".$onlinetoken." paymentType=".$paymentType." currencycodeType=".$currencyCodeType." payerId=".$payerID." ipaddress=".$ipaddress." FinalPaymentAmt=".$FinalPaymentAmt;

		$ishtml = dol_textishtml($content); // May contain urls

		require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
		$mailfile = new CMailFile($topic, $sendto, $from, $content, array(), array(), array(), '', '', 0, $ishtml);

		$result = $mailfile->sendfile();
		if ($result)
		{
			dol_syslog("EMail sent to ".$sendto, LOG_DEBUG, 0, '_payment');
		} else {
			dol_syslog("Failed to send EMail to ".$sendto, LOG_ERR, 0, '_payment');
		}
	}

	unset($_SESSION['ipaddress']);
}

$head = '';
if (!empty($conf->global->ONLINE_PAYMENT_CSS_URL)) $head = '<link rel="stylesheet" type="text/css" href="'.$conf->global->ONLINE_PAYMENT_CSS_URL.'?lang='.$langs->defaultlang.'">'."\n";

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
if (!empty($conf->global->$paramlogo)) $logosmall = $conf->global->$paramlogo;
elseif (!empty($conf->global->ONLINE_PAYMENT_LOGO)) $logosmall = $conf->global->ONLINE_PAYMENT_LOGO;
//print '<!-- Show logo (logosmall='.$logosmall.' logo='.$logo.') -->'."\n";
// Define urllogo
$urllogo = '';
$urllogofull = '';
if (!empty($logosmall) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$logosmall))
{
	$urllogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;entity='.$conf->entity.'&amp;file='.urlencode('logos/thumbs/'.$logosmall);
	$urllogofull = $dolibarr_main_url_root.'/viewimage.php?modulepart=mycompany&entity='.$conf->entity.'&file='.urlencode('logos/thumbs/'.$logosmall);
} elseif (!empty($logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$logo))
{
	$urllogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;entity='.$conf->entity.'&amp;file='.urlencode('logos/'.$logo);
	$urllogofull = $dolibarr_main_url_root.'/viewimage.php?modulepart=mycompany&entity='.$conf->entity.'&file='.urlencode('logos/'.$logo);
}

// Output html code for logo
if ($urllogo)
{
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


print '<br><br>';


print $langs->trans("YourPaymentHasNotBeenRecorded")."<br><br>";

$key = 'ONLINE_PAYMENT_MESSAGE_KO';
if (!empty($conf->global->$key)) print $conf->global->$key;

$type = GETPOST('s', 'alpha');
$ref = GETPOST('ref', 'alphanohtml');
$tag = GETPOST('tag', 'alpha');
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
if ($type || $tag)
{
	$urlsubscription = getOnlinePaymentUrl(0, ($type ? $type : 'free'), $ref, $FinalPaymentAmt, $tag);

	print $langs->trans("ClickHereToTryAgain", $urlsubscription);
}

print "\n</div>\n";


htmlPrintOnlinePaymentFooter($mysoc, $langs, 0, $suffix);


llxFooter('', 'public');

$db->close();
