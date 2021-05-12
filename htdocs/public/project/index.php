<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2018	    Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2018-2019	Thibault FOUCART	    <support@ptibogxiv.net>
 * Copyright (C) 2021		Waël Almoman	    	<info@almoman.com>
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
 *
 * For Paypal test: https://developer.paypal.com/
 * For Paybox test: ???
 * For Stripe test: Use credit card 4242424242424242 .More example on https://stripe.com/docs/testing
 *
 * Variants:
 * - When option STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION is on, we use the new PaymentIntent API
 * - When option STRIPE_USE_NEW_CHECKOUT is on, we use the new checkout API
 * - If no option set, we use old APIS (charge)
 */

/**
 *     	\file       htdocs/public/payment/newpayment.php
 *		\ingroup    core
 *		\brief      File to offer a way to make a payment for a particular Dolibarr object
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
// Do not use GETPOST here, function is not defined and get of entity must be done before including main.inc.php
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : (!empty($_GET['e']) ? (int) $_GET['e'] : (!empty($_POST['e']) ? (int) $_POST['e'] : 1))));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societeaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
// Hook to be used by external payment modules (ie Payzen, ...)
include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
$hookmanager = new HookManager($db);
$hookmanager->initHooks(array('newpayment'));

// For encryption
global $dolibarr_main_instance_unique_id;

// Load translation files
$langs->loadLangs(array("main", "other", "dict", "bills", "companies", "errors", "paybox", "paypal", "stripe")); // File with generic data

// Security check
// No check on module enabled. Done later according to $validpaymentmethod

$action = GETPOST('action', 'aZ09');
$id = GETPOST('id');
$securekeyreceived = GETPOST("securekey");
$securekeytocompare = dol_hash($conf->global->EVENTORGANIZATION_SECUREKEY.'conferenceorbooth'.$id, 2);

if ($securekeytocompare != $securekeyreceived) {
	print $langs->trans('MissingOrBadSecureKey');
	exit;
}

// Define $urlwithroot
//$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
//$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
$urlwithroot = DOL_MAIN_URL_ROOT; // This is to use same domain name than current. For Paypal payment, we can use internal URL like localhost.

$project = new Project($db);
$resultproject = $project->fetch($id);
if ($resultproject < 0) {
	$error++;
	$errmsg .= $project->error;
}

/*
 * Actions
 */


/*
 * View
 */

$head = '';
if (!empty($conf->global->ONLINE_PAYMENT_CSS_URL)) {
	$head = '<link rel="stylesheet" type="text/css" href="'.$conf->global->ONLINE_PAYMENT_CSS_URL.'?lang='.$langs->defaultlang.'">'."\n";
}

$conf->dol_hide_topmenu = 1;
$conf->dol_hide_leftmenu = 1;

$replacemainarea = (empty($conf->dol_hide_leftmenu) ? '<div>' : '').'<div>';
llxHeader($head, $langs->trans("PaymentForm"), '', '', 0, 0, '', '', '', 'onlinepaymentbody', $replacemainarea);


// Show sandbox warning
if ((empty($paymentmethod) || $paymentmethod == 'paypal') && !empty($conf->paypal->enabled) && (!empty($conf->global->PAYPAL_API_SANDBOX) || GETPOST('forcesandbox', 'int'))) {		// We can force sand box with param 'forcesandbox'
	dol_htmloutput_mesg($langs->trans('YouAreCurrentlyInSandboxMode', 'Paypal'), '', 'warning');
}
if ((empty($paymentmethod) || $paymentmethod == 'stripe') && !empty($conf->stripe->enabled) && (empty($conf->global->STRIPE_LIVE) || GETPOST('forcesandbox', 'int'))) {
	dol_htmloutput_mesg($langs->trans('YouAreCurrentlyInSandboxMode', 'Stripe'), '', 'warning');
}


print '<span id="dolpaymentspan"></span>'."\n";
print '<div class="center">'."\n";
print '<form id="dolpaymentform" class="center" name="paymentform" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">'."\n";
print '<input type="hidden" name="action" value="dopayment">'."\n";
print '<input type="hidden" name="tag" value="'.GETPOST("tag", 'alpha').'">'."\n";
print '<input type="hidden" name="suffix" value="'.dol_escape_htmltag($suffix).'">'."\n";
print '<input type="hidden" name="securekey" value="'.dol_escape_htmltag($SECUREKEY).'">'."\n";
print '<input type="hidden" name="e" value="'.$entity.'" />';
print '<input type="hidden" name="forcesandbox" value="'.GETPOST('forcesandbox', 'int').'" />';
print "\n";


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

print '<table id="dolpaymenttable" summary="Payment form" class="center">'."\n";

$text  = '<tr><td class="textpublicpayment"><br><strong>'.$langs->trans("EvntOrgRegistrationWelcomeMessage").'</strong></td></tr>'."\n";
$text .= '<tr><td class="textpublicpayment">'.$langs->trans("EvntOrgRegistrationHelpMessage").' '.$id.'.<br><br></td></tr>'."\n";
$text .= '<tr><td class="textpublicpayment">'.$project->note_public.'<br><br></td></tr>'."\n";;

print $text;

// Output payment summary form
print '<tr><td align="center">';

$found = false;
$error = 0;
$var = false;

$object = null;

print "\n";


// Show all action buttons
print '<br>';
// Output introduction text
if ($project->accept_conference_suggestions) {
	print '<input type="submit" value="'.$langs->trans("SuggestConference").'" id="suggestconference" class="button">';
	print '<br><br>';
}
print '<input type="submit" value="'.$langs->trans("ViewAndVote").'" id="viewandvote" class="button">';
print '<br><br>';
if ($project->accept_booth_suggestions) {
	print '<input type="submit" value="'.$langs->trans("SuggestBooth").'" id="suggestbooth" class="button">';
}



print '</td></tr>'."\n";

print '</table>'."\n";

print '</form>'."\n";
print '</div>'."\n";
print '<br>';


htmlPrintOnlinePaymentFooter($mysoc, $langs, 1, $suffix, $object);

llxFooter('', 'public');

$db->close();
