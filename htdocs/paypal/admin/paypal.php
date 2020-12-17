<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2013	Laurent Destailleur		<eldy@users.sourceforge.org>
 * Copyright (C) 2011-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2012  Juanjo Menent			<jmenent@2byte.es>
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
 * \file       htdocs/paypal/admin/paypal.php
 * \ingroup    paypal
 * \brief      Page to setup paypal module
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/paypal/lib/paypal.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

$servicename = 'PayPal';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'other', 'paypal', 'paybox'));

if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'aZ09');

if ($action == 'setvalue' && $user->admin)
{
	$db->begin();

	$result = dolibarr_set_const($db, "PAYPAL_API_USER", GETPOST('PAYPAL_API_USER', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!$result > 0) $error++;
	$result = dolibarr_set_const($db, "PAYPAL_API_PASSWORD", GETPOST('PAYPAL_API_PASSWORD', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!$result > 0) $error++;
	$result = dolibarr_set_const($db, "PAYPAL_API_SIGNATURE", GETPOST('PAYPAL_API_SIGNATURE', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!$result > 0) $error++;
	$result = dolibarr_set_const($db, "PAYPAL_SSLVERSION", GETPOST('PAYPAL_SSLVERSION', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!$result > 0) $error++;
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_CREDITOR", GETPOST('ONLINE_PAYMENT_CREDITOR', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!$result > 0) $error++;
	$result = dolibarr_set_const($db, "PAYPAL_BANK_ACCOUNT_FOR_PAYMENTS", GETPOST('PAYPAL_BANK_ACCOUNT_FOR_PAYMENTS', 'int'), 'chaine', 0, '', $conf->entity);
	if (!$result > 0) $error++;
	$result = dolibarr_set_const($db, "PAYPAL_API_INTEGRAL_OR_PAYPALONLY", GETPOST('PAYPAL_API_INTEGRAL_OR_PAYPALONLY', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!$result > 0) $error++;
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_CSS_URL", GETPOST('ONLINE_PAYMENT_CSS_URL', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!$result > 0) $error++;
	$result = dolibarr_set_const($db, "PAYPAL_ADD_PAYMENT_URL", GETPOST('PAYPAL_ADD_PAYMENT_URL', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!$result > 0) $error++;
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_MESSAGE_FORM", GETPOST('ONLINE_PAYMENT_MESSAGE_FORM'), 'chaine', 0, '', $conf->entity);
	if (!$result > 0) $error++;
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_MESSAGE_OK", GETPOST('ONLINE_PAYMENT_MESSAGE_OK'), 'chaine', 0, '', $conf->entity);
	if (!$result > 0) $error++;
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_MESSAGE_KO", GETPOST('ONLINE_PAYMENT_MESSAGE_KO'), 'chaine', 0, '', $conf->entity);
	if (!$result > 0) $error++;
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_SENDEMAIL", GETPOST('ONLINE_PAYMENT_SENDEMAIL'), 'chaine', 0, '', $conf->entity);
	if (!$result > 0) $error++;
	// Payment token for URL
	$result = dolibarr_set_const($db, "PAYMENT_SECURITY_TOKEN", GETPOST('PAYMENT_SECURITY_TOKEN', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!$result > 0) $error++;
	if (empty($conf->use_javascript_ajax)) {
		$result = dolibarr_set_const($db, "PAYMENT_SECURITY_TOKEN_UNIQUE", GETPOST('PAYMENT_SECURITY_TOKEN_UNIQUE', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (!$result > 0) {
			$error++;
		}
	}

	if (!$error) {
  		$db->commit();
  		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
  	} else {
  		$db->rollback();
		dol_print_error($db);
	}
}

if ($action == "setlive")
{
	$liveenable = GETPOST('value', 'int') ? 0 : 1;
	$res = dolibarr_set_const($db, "PAYPAL_API_SANDBOX", $liveenable, 'yesno', 0, '', $conf->entity);
	if (!$res > 0) $error++;
	if (!$error)
	{
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}


/*
 *	View
 */

$form = new Form($db);

llxHeader('', $langs->trans("PaypalSetup"));


$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ModuleSetup").' PayPal', $linkback);

$head = paypaladmin_prepare_head();

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="setvalue">';


print dol_get_fiche_head($head, 'paypalaccount', '', -1);

print '<span class="opacitymedium">'.$langs->trans("PaypalDesc")."</span><br>\n";

// Test if php curl exist
if (!function_exists('curl_version'))
{
	$langs->load("errors");
	setEventMessages($langs->trans("ErrorPhpCurlNotInstalled"), null, 'errors');
}


print '<br>';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

// Account Parameters
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("AccountParameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

print '<tr class="oddeven">';
print '<td>';
print $langs->trans("PaypalLiveEnabled").'</td><td>';
if (empty($conf->global->PAYPAL_API_SANDBOX))
{
	print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setlive&token='.newToken().'&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
} else {
	print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setlive&token='.newToken().'&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
}
print '</td></tr>';

print '<tr class="oddeven"><td class="fieldrequired">';
print $langs->trans("PAYPAL_API_USER").'</td><td>';
print '<input size="32" type="text" name="PAYPAL_API_USER" value="'.$conf->global->PAYPAL_API_USER.'">';
print ' &nbsp; <span class="opacitymedium">'.$langs->trans("Example").': admin-facilitator_api1.example.com, paypal_api1.mywebsite.com</span>';
print '</td></tr>';


print '<tr class="oddeven"><td class="fieldrequired">';
print $langs->trans("PAYPAL_API_PASSWORD").'</td><td>';
print '<input size="32" type="text" name="PAYPAL_API_PASSWORD" value="'.$conf->global->PAYPAL_API_PASSWORD.'">';
print '</td></tr>';


print '<tr class="oddeven"><td class="fieldrequired">';
print $langs->trans("PAYPAL_API_SIGNATURE").'</td><td>';
print '<input size="64" type="text" name="PAYPAL_API_SIGNATURE" value="'.$conf->global->PAYPAL_API_SIGNATURE.'">';
print '<br><span class="opacitymedium">'.$langs->trans("Example").': ASsqXEmw4KzmX-CPChWSVDNCNfd.A3YNR7uz-VncXXAERFDFDFDF</span>';
print '</td></tr>';


print '<tr class="oddeven"><td>';
print $langs->trans("PAYPAL_SSLVERSION").'</td><td>';
print $form->selectarray("PAYPAL_SSLVERSION", array('1'=> $langs->trans('TLSv1'), '6'=> $langs->trans('TLSv1.2')), $conf->global->PAYPAL_SSLVERSION);
print '</td></tr>';

print '</table>';
print '</div>';

print '<br>';


print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

// Usage Parameters
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("UsageParameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";


print '<tr class="oddeven"><td>';
print $langs->trans("PAYPAL_API_INTEGRAL_OR_PAYPALONLY").'</td><td>';
print $form->selectarray("PAYPAL_API_INTEGRAL_OR_PAYPALONLY", array('integral'=> $langs->trans('PaypalModeIntegral'), 'paypalonly'=> $langs->trans('PaypalModeOnlyPaypal')), $conf->global->PAYPAL_API_INTEGRAL_OR_PAYPALONLY);
print '</td></tr>';

/*
print '<tr class="oddeven"><td>';
print '<span class="fieldrequired">'.$langs->trans("PAYPAL_API_EXPRESS").'</span></td><td>';
print $form->selectyesno("PAYPAL_API_EXPRESS",$conf->global->PAYPAL_API_EXPRESS);
print '</td></tr>';
*/


print '<tr class="oddeven"><td>';
print $langs->trans("PublicVendorName").'</td><td>';
print '<input size="64" type="text" name="ONLINE_PAYMENT_CREDITOR" value="'.$conf->global->ONLINE_PAYMENT_CREDITOR.'">';
print ' &nbsp; <span class="opacitymedium">'.$langs->trans("Example").': '.$mysoc->name.'</span>';
print '</td></tr>';

if (!empty($conf->banque->enabled))
{
	print '<tr class="oddeven"><td>';
	print $langs->trans("BankAccount").'</td><td>';
	print img_picto('', 'bank_account').' ';
	$form->select_comptes($conf->global->PAYPAL_BANK_ACCOUNT_FOR_PAYMENTS, 'PAYPAL_BANK_ACCOUNT_FOR_PAYMENTS', 0, '', 1);
	print '</td></tr>';
}

print '<tr class="oddeven"><td>';
print $langs->trans("CSSUrlForPaymentForm").'</td><td>';
print '<input size="64" type="text" name="ONLINE_PAYMENT_CSS_URL" value="'.$conf->global->ONLINE_PAYMENT_CSS_URL.'">';
print ' &nbsp; <span class="opacitymedium">'.$langs->trans("Example").': http://mysite/mycss.css</span>';
print '</td></tr>';


print '<tr class="oddeven"><td>';
print $langs->trans("PAYPAL_ADD_PAYMENT_URL").'</td><td>';
print $form->selectyesno("PAYPAL_ADD_PAYMENT_URL", $conf->global->PAYPAL_ADD_PAYMENT_URL, 1);
print '</td></tr>';


print '<tr class="oddeven"><td>';
print $langs->trans("MessageForm").'</td><td>';
$doleditor = new DolEditor('ONLINE_PAYMENT_MESSAGE_FORM', $conf->global->ONLINE_PAYMENT_MESSAGE_FORM, '', 100, 'dolibarr_details', 'In', false, true, true, ROWS_4, '90%');
$doleditor->Create();
print '</td></tr>';


print '<tr class="oddeven"><td>';
print $langs->trans("MessageOK").'</td><td>';
$doleditor = new DolEditor('ONLINE_PAYMENT_MESSAGE_OK', $conf->global->ONLINE_PAYMENT_MESSAGE_OK, '', 100, 'dolibarr_details', 'In', false, true, true, ROWS_4, '90%');
$doleditor->Create();
print '</td></tr>';


print '<tr class="oddeven"><td>';
print $langs->trans("MessageKO").'</td><td>';
$doleditor = new DolEditor('ONLINE_PAYMENT_MESSAGE_KO', $conf->global->ONLINE_PAYMENT_MESSAGE_KO, '', 100, 'dolibarr_details', 'In', false, true, true, ROWS_4, '90%');
$doleditor->Create();
print '</td></tr>';


print '<tr class="oddeven"><td>';
print $langs->trans("ONLINE_PAYMENT_SENDEMAIL").'</td><td>';
print '<input class="minwidth200" type="text" name="ONLINE_PAYMENT_SENDEMAIL" value="'.$conf->global->ONLINE_PAYMENT_SENDEMAIL.'">';
print ' &nbsp;  <span class="opacitymedium">'.$langs->trans("Example").': myemail@myserver.com, Payment service &lt;myemail2@myserver2.com&gt;</span>';
print '</td></tr>';

print '</table>';
print '</div>';

print '<br>';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("UrlGenerationParameters").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

// Payment token for URL
print '<tr class="oddeven"><td>';
print $langs->trans("SecurityToken").'</td><td>';
print '<input class="minwidth300" type="text" id="PAYMENT_SECURITY_TOKEN" name="PAYMENT_SECURITY_TOKEN" value="'.$conf->global->PAYMENT_SECURITY_TOKEN.'">';
if (!empty($conf->use_javascript_ajax)) {
	print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_token" class="linkobject"');
}
if (!empty($conf->global->PAYMENT_SECURITY_ACCEPT_ANY_TOKEN)) {
	$langs->load("errors");
	print img_warning($langs->trans("WarningTheHiddenOptionIsOn", 'PAYMENT_SECURITY_ACCEPT_ANY_TOKEN'), '', 'pictowarning marginleftonly');
}
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("SecurityTokenIsUnique").'</td><td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('PAYMENT_SECURITY_TOKEN_UNIQUE');
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $form->selectarray("PAYMENT_SECURITY_TOKEN_UNIQUE", $arrval, $conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE);
}
print '</td></tr>';

print '</table>';
print '</div>';

print dol_get_fiche_end();

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></div>';

print '</form>';

print '<br><br>';

// Help doc
print '<u>'.$langs->trans("InformationToFindParameters", "Paypal").'</u>:<br>';
if (!empty($conf->use_javascript_ajax))
	print '<a class="reposition" id="apidoca">'.$langs->trans("ClickHere").'...</a>';

$realpaypalurl = 'www.paypal.com';
$sandboxpaypalurl = 'developer.paypal.com';

print '<div id="apidoc">';
print 'Your API authentication information can be found with following steps. We recommend that you open a separate Web browser session when carrying out this procedure.<br>
1. Log in to your PayPal account (on real paypal <a href="https://'.$realpaypalurl.'" target="_blank">'.$realpaypalurl.'</a> (or sandbox <a href="https://'.$sandboxpaypalurl.'" target="_blank">'.$sandboxpaypalurl.'</a>).<br>
2. Click the "Profile" or "Preferencies" subtab located under the My Account heading.<br>
3. Click the link "API Access".<br>
4. Click the View API Certificate link in the right column.<br>
5. Click the Request API signature radio button on the Request API Credentials page.<br>
6. Complete the Request API Credential Request form by clicking the agreement checkbox and clicking Submit.<br>
7. Save the values for API Username, Password and Signature (make sure this long character signature is copied).<br>
8. Click the "Modify" button after copying your API Username, Password, and Signature.
';
print '</div>';

if (!empty($conf->use_javascript_ajax))
{
	print "\n".'<script type="text/javascript">';
	print '$(document).ready(function () {
	            $("#apidoc").hide();
	            $("#apidoca").click(function() {
					console.log("We click on apidoca show/hide");
	                $("#apidoc").show();
	            	$("#apidoca").hide();
					return false;
	            })
			});';
	print '</script>';
}

print '<br><br>';

$token = '';

include DOL_DOCUMENT_ROOT.'/core/tpl/onlinepaymentlinks.tpl.php';

// End of page
llxFooter();
$db->close();
