<?php
/* Copyright (C) 2017		Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2017		Olivier Geffroy			<jeff@jeffinfo.com>
 * Copyright (C) 2017		Saasprov				<saasprov@gmail.com>
 * Copyright (C) 2018-2019  Thibault FOUCART		<support@ptibogxiv.net>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 * \file       htdocs/stripe/admin/stripe.php
 * \ingroup    stripe
 * \brief      Page to setup stripe module
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/stripe/lib/stripe.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';

$servicename = 'Stripe';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'other', 'paypal', 'paybox', 'stripe'));

if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'aZ09');


/*
 * Actions
 */

if ($action == 'setvalue' && $user->admin)
{
	$db->begin();

	if (empty($conf->stripeconnect->enabled)) {
		$result = dolibarr_set_const($db, "STRIPE_TEST_PUBLISHABLE_KEY", GETPOST('STRIPE_TEST_PUBLISHABLE_KEY', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (!$result > 0)
			$error++;
		$result = dolibarr_set_const($db, "STRIPE_TEST_SECRET_KEY", GETPOST('STRIPE_TEST_SECRET_KEY', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (!$result > 0)
			$error++;
		$result = dolibarr_set_const($db, "STRIPE_TEST_WEBHOOK_ID", GETPOST('STRIPE_TEST_WEBHOOK_ID', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (!$result > 0)
			$error++;
		$result = dolibarr_set_const($db, "STRIPE_TEST_WEBHOOK_KEY", GETPOST('STRIPE_TEST_WEBHOOK_KEY', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (!$result > 0)
			$error++;
		$result = dolibarr_set_const($db, "STRIPE_LIVE_PUBLISHABLE_KEY", GETPOST('STRIPE_LIVE_PUBLISHABLE_KEY', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (!$result > 0)
			$error++;
		$result = dolibarr_set_const($db, "STRIPE_LIVE_SECRET_KEY", GETPOST('STRIPE_LIVE_SECRET_KEY', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (!$result > 0)
			$error++;
		$result = dolibarr_set_const($db, "STRIPE_LIVE_WEBHOOK_ID", GETPOST('STRIPE_LIVE_WEBHOOK_ID', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (!$result > 0)
			$error++;
		$result = dolibarr_set_const($db, "STRIPE_LIVE_WEBHOOK_KEY", GETPOST('STRIPE_LIVE_WEBHOOK_KEY', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (!$result > 0)
			$error++;
	}
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_CREDITOR", GETPOST('ONLINE_PAYMENT_CREDITOR', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!$result > 0)
		$error++;
	$result = dolibarr_set_const($db, "STRIPE_BANK_ACCOUNT_FOR_PAYMENTS", GETPOST('STRIPE_BANK_ACCOUNT_FOR_PAYMENTS', 'int'), 'chaine', 0, '', $conf->entity);
	if (!$result > 0)
		$error++;
	$result = dolibarr_set_const($db, "STRIPE_USER_ACCOUNT_FOR_ACTIONS", GETPOST('STRIPE_USER_ACCOUNT_FOR_ACTIONS', 'int'), 'chaine', 0, '', $conf->entity);
	if (!$result > 0) {
		$error++;
	}
	$result = dolibarr_set_const($db, "STRIPE_BANK_ACCOUNT_FOR_BANKTRANSFERS", GETPOST('STRIPE_BANK_ACCOUNT_FOR_BANKTRANSFERS', 'int'), 'chaine', 0, '', $conf->entity);
	if (!$result > 0)
		$error++;
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_CSS_URL", GETPOST('ONLINE_PAYMENT_CSS_URL', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!$result > 0)
		$error++;
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_MESSAGE_FORM", GETPOST('ONLINE_PAYMENT_MESSAGE_FORM', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!$result > 0)
		$error++;
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_MESSAGE_OK", GETPOST('ONLINE_PAYMENT_MESSAGE_OK', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!$result > 0)
		$error++;
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_MESSAGE_KO", GETPOST('ONLINE_PAYMENT_MESSAGE_KO', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!$result > 0)
		$error++;
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_SENDEMAIL", GETPOST('ONLINE_PAYMENT_SENDEMAIL'), 'chaine', 0, '', $conf->entity);
	if (!$result > 0)
		$error++;
	// Stock decrement
	//$result = dolibarr_set_const($db, "ONLINE_PAYMENT_WAREHOUSE", (GETPOST('ONLINE_PAYMENT_WAREHOUSE', 'alpha') > 0 ? GETPOST('ONLINE_PAYMENT_WAREHOUSE', 'alpha') : ''), 'chaine', 0, '', $conf->entity);
	//if (! $result > 0)
	//	$error ++;

	// Payment token for URL
	$result = dolibarr_set_const($db, "PAYMENT_SECURITY_TOKEN", GETPOST('PAYMENT_SECURITY_TOKEN', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!$result > 0) {
		$error++;
	}
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
	$liveenable = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "STRIPE_LIVE", $liveenable, 'yesno', 0, '', $conf->entity);
	if ($res > 0) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}
//TODO: import script for stripe account saving in alone or connect mode for stripe.class.php


/*
 *	View
 */

$form = new Form($db);
$formproduct = new FormProduct($db);

llxHeader('', $langs->trans("StripeSetup"));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ModuleSetup").' Stripe', $linkback);

$head = stripeadmin_prepare_head();

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="setvalue">';

print dol_get_fiche_head($head, 'stripeaccount', '', -1);

$stripearrayofwebhookevents = array('account.updated', 'payout.created', 'payout.paid', 'charge.pending', 'charge.refunded', 'charge.succeeded', 'charge.failed', 'payment_intent.succeeded', 'payment_intent.payment_failed', 'payment_method.attached', 'payment_method.updated', 'payment_method.card_automatically_updated', 'payment_method.detached', 'source.chargeable', 'customer.deleted');

print '<span class="opacitymedium">'.$langs->trans("StripeDesc")."</span><br>\n";

print '<br>';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("AccountParameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td></td>';
print "</tr>\n";

print '<tr class="oddeven">';
print '<td>';
print $langs->trans("StripeLiveEnabled").'</td><td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('STRIPE_LIVE');
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $form->selectarray("STRIPE_LIVE", $arrval, $conf->global->STRIPE_LIVE);
}
print '</td><td></td></tr>';

if (empty($conf->stripeconnect->enabled))
{
	print '<tr class="oddeven"><td>';
	print '<span class="fieldrequired">'.$langs->trans("STRIPE_TEST_PUBLISHABLE_KEY").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="STRIPE_TEST_PUBLISHABLE_KEY" value="'.$conf->global->STRIPE_TEST_PUBLISHABLE_KEY.'">';
	print ' &nbsp; <span class="opacitymedium">'.$langs->trans("Example").': pk_test_xxxxxxxxxxxxxxxxxxxxxxxx</span>';
	print '</td><td></td></tr>';

	print '<tr class="oddeven"><td>';
	print '<span class="titlefield fieldrequired">'.$langs->trans("STRIPE_TEST_SECRET_KEY").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="STRIPE_TEST_SECRET_KEY" value="'.$conf->global->STRIPE_TEST_SECRET_KEY.'">';
	print ' &nbsp; <span class="opacitymedium">'.$langs->trans("Example").': sk_test_xxxxxxxxxxxxxxxxxxxxxxxx<</span>';
	print '</td><td></td></tr>';

	print '<tr class="oddeven"><td>';
	print '<span class="titlefield">'.$langs->trans("STRIPE_TEST_WEBHOOK_KEY").'</span></td><td>';
	if ($conf->global->MAIN_FEATURES_LEVEL >= 2) {
		print '<input class="minwidth300" type="text" name="STRIPE_TEST_WEBHOOK_ID" value="'.$conf->global->STRIPE_TEST_WEBHOOK_ID.'">';
		print ' &nbsp; <span class="opacitymedium">'.$langs->trans("Example").': we_xxxxxxxxxxxxxxxxxxxxxxxx</span><br>';
	}
	print '<input class="minwidth300" type="text" name="STRIPE_TEST_WEBHOOK_KEY" value="'.$conf->global->STRIPE_TEST_WEBHOOK_KEY.'">';
	print ' &nbsp; <span class="opacitymedium">'.$langs->trans("Example").': whsec_xxxxxxxxxxxxxxxxxxxxxxxx</span>';
	  $out = img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans("ToOfferALinkForTestWebhook").'</span> ';
	$url = dol_buildpath('/public/stripe/ipn.php?test', 3);
	$out .= '<input type="text" id="onlinetestwebhookurl" class="minwidth500" value="'.$url.'" disabled>';
	$out .= ajax_autoselect("onlinetestwebhookurl", 0);
	print '<br>'.$out;
	print '</td><td>';
	if ($conf->global->MAIN_FEATURES_LEVEL >= 2)
	{
		if (!empty($conf->global->STRIPE_TEST_WEBHOOK_KEY) && !empty($conf->global->STRIPE_TEST_SECRET_KEY) && !empty($conf->global->STRIPE_TEST_WEBHOOK_ID))
		{
			\Stripe\Stripe::setApiKey($conf->global->STRIPE_TEST_SECRET_KEY);
			$endpoint = \Stripe\WebhookEndpoint::retrieve($conf->global->STRIPE_TEST_WEBHOOK_ID);
			$endpoint->enabled_events = $stripearrayofwebhookevents;
			if (GETPOST('webhook', 'alpha') == $conf->global->STRIPE_TEST_WEBHOOK_ID) {
				if (!GETPOST('status', 'alpha')) {
					$endpoint->disabled = true;
				} else {
					$endpoint->disabled = false;
				}
			}
			$endpoint->url = dol_buildpath('/public/stripe/ipn.php?test', 3);
			$endpoint->save();
			if ($endpoint->status == 'enabled')
			{
				print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=ipn&webhook='.$endpoint->id.'&status=0">';
				print img_picto($langs->trans("Activated"), 'switch_on');
			} else {
				print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=ipn&webhook='.$endpoint->id.'&status=1">';
				print img_picto($langs->trans("Disabled"), 'switch_off');
			}
			//print $endpoint;
		} else {
			print img_picto($langs->trans("inactive"), 'statut5');
		}
	}
	print'</td></tr>';
} else {
	print '<tr class="oddeven"><td>'.$langs->trans("StripeConnect").'</td>';
	print '<td><b>'.$langs->trans("StripeConnect_Mode").'</b><br/>';
	print $langs->trans("STRIPE_APPLICATION_FEE_PLATFORM").' ';
	print price($conf->global->STRIPE_APPLICATION_FEE_PERCENT);
	print '% + ';
	print price($conf->global->STRIPE_APPLICATION_FEE);
	print ' '.$langs->getCurrencySymbol($conf->currency).' '.$langs->trans("minimum").' '.price($conf->global->STRIPE_APPLICATION_FEE_MINIMAL).' '.$langs->getCurrencySymbol($conf->currency);
	print '</td><td></td></tr>';
}

if (empty($conf->stripeconnect->enabled))
{
	print '<tr class="oddeven"><td>';
	print '<span class="fieldrequired">'.$langs->trans("STRIPE_LIVE_PUBLISHABLE_KEY").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="STRIPE_LIVE_PUBLISHABLE_KEY" value="'.$conf->global->STRIPE_LIVE_PUBLISHABLE_KEY.'">';
	print ' &nbsp; <span class="opacitymedium">'.$langs->trans("Example").': pk_live_xxxxxxxxxxxxxxxxxxxxxxxx</span>';
	print '</td><td></td></tr>';

	print '<tr class="oddeven"><td>';
	print '<span class="fieldrequired">'.$langs->trans("STRIPE_LIVE_SECRET_KEY").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="STRIPE_LIVE_SECRET_KEY" value="'.$conf->global->STRIPE_LIVE_SECRET_KEY.'">';
	print ' &nbsp; <span class="opacitymedium">'.$langs->trans("Example").': sk_live_xxxxxxxxxxxxxxxxxxxxxxxx</span>';
	print '</td><td></td></tr>';

	print '<tr class="oddeven"><td>';
	print '<span class="titlefield">'.$langs->trans("STRIPE_LIVE_WEBHOOK_KEY").'</span></td><td>';
	if ($conf->global->MAIN_FEATURES_LEVEL >= 2) {
		print '<input class="minwidth300" type="text" name="STRIPE_LIVE_WEBHOOK_ID" value="'.$conf->global->STRIPE_LIVE_WEBHOOK_ID.'">';
		print ' &nbsp; <span class="opacitymedium">'.$langs->trans("Example").': we_xxxxxxxxxxxxxxxxxxxxxxxx</span><br>';
	}
	print '<input class="minwidth300" type="text" name="STRIPE_LIVE_WEBHOOK_KEY" value="'.$conf->global->STRIPE_LIVE_WEBHOOK_KEY.'">';
	print ' &nbsp; <span class="opacitymedium">'.$langs->trans("Example").': whsec_xxxxxxxxxxxxxxxxxxxxxxxx</span>';
	$out = img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans("ToOfferALinkForLiveWebhook").'</span> ';
	$url = dol_buildpath('/public/stripe/ipn.php', 3);
	$out .= '<input type="text" id="onlinelivewebhookurl" class="minwidth500" value="'.$url.'" disabled>';
	$out .= ajax_autoselect("onlinelivewebhookurl", 0);
	print '<br>'.$out;
	print '</td><td>';
	if ($conf->global->MAIN_FEATURES_LEVEL >= 2)
	{
		if (!empty($conf->global->STRIPE_LIVE_WEBHOOK_KEY) && !empty($conf->global->STRIPE_LIVE_SECRET_KEY) && !empty($conf->global->STRIPE_LIVE_WEBHOOK_ID))
		{
			\Stripe\Stripe::setApiKey($conf->global->STRIPE_LIVE_SECRET_KEY);
			$endpoint = \Stripe\WebhookEndpoint::retrieve($conf->global->STRIPE_LIVE_WEBHOOK_ID);
			$endpoint->enabled_events = $stripearrayofwebhookevents;
			if (GETPOST('webhook', 'alpha') == $conf->global->STRIPE_LIVE_WEBHOOK_ID) {
				if (empty(GETPOST('status', 'alpha'))) {
					$endpoint->disabled = true;
				} else {
					$endpoint->disabled = false;
				}
			}
			$endpoint->url = dol_buildpath('/public/stripe/ipn.php', 3);
			$endpoint->save();
			if ($endpoint->status == 'enabled')
			{
				print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=ipn&webhook='.$endpoint->id.'&status=0">';
				print img_picto($langs->trans("Activated"), 'switch_on');
			} else {
				print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=ipn&webhook='.$endpoint->id.'&status=1">';
				print img_picto($langs->trans("Disabled"), 'switch_off');
			}
			//print $endpoint;
		} else {
			print img_picto($langs->trans("inactive"), 'statut5');
		}
	}
	print '</td></tr>';
} else {
	print '<tr class="oddeven"><td>'.$langs->trans("StripeConnect").'</td>';
	print '<td>'.$langs->trans("StripeConnect_Mode").'</td><td></td></tr>';
}


print '</table>';
print '</div>';

print '<br>';


print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("UsageParameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

print '<tr class="oddeven"><td>';
print $langs->trans("PublicVendorName").'</td><td>';
print '<input class="minwidth300" type="text" name="ONLINE_PAYMENT_CREDITOR" value="'.$conf->global->ONLINE_PAYMENT_CREDITOR.'">';
print ' &nbsp; <span class="opacitymedium">'.$langs->trans("Example").': '.$mysoc->name.'</span>';
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("StripeUserAccountForActions").'</td><td>';
print img_picto('', 'user').$form->select_dolusers($conf->global->STRIPE_USER_ACCOUNT_FOR_ACTIONS, 'STRIPE_USER_ACCOUNT_FOR_ACTIONS', 0);
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("BankAccount").'</td><td>';
print img_picto('', 'bank_account').' ';
$form->select_comptes($conf->global->STRIPE_BANK_ACCOUNT_FOR_PAYMENTS, 'STRIPE_BANK_ACCOUNT_FOR_PAYMENTS', 0, '', 1);
print '</td></tr>';

if ($conf->global->MAIN_FEATURES_LEVEL >= 2)	// What is this for ?
{
	print '<tr class="oddeven"><td>';
	print $langs->trans("BankAccountForBankTransfer").'</td><td>';
	$form->select_comptes($conf->global->STRIPE_BANK_ACCOUNT_FOR_BANKTRANSFERS, 'STRIPE_BANK_ACCOUNT_FOR_BANKTRANSFERS', 0, '', 1);
	print '</td></tr>';
}

// Activate Payment Request API
if ($conf->global->MAIN_FEATURES_LEVEL >= 2)	// TODO Not used by current code
{
	print '<tr class="oddeven"><td>';
	print $langs->trans("STRIPE_PAYMENT_REQUEST_API").'</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('STRIPE_PAYMENT_REQUEST_API');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("STRIPE_PAYMENT_REQUEST_API", $arrval, $conf->global->STRIPE_PAYMENT_REQUEST_API);
	}
	print '</td></tr>';
}

// Activate SEPA DIRECT_DEBIT
if ($conf->global->MAIN_FEATURES_LEVEL >= 2)	// TODO Not used by current code
{
	print '<tr class="oddeven"><td>';
	print $langs->trans("STRIPE_SEPA_DIRECT_DEBIT").'</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('STRIPE_SEPA_DIRECT_DEBIT');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("STRIPE_SEPA_DIRECT_DEBIT", $arrval, $conf->global->STRIPE_SEPA_DIRECT_DEBIT);
	}
	print '</td></tr>';
}

// Activate iDEAL
if ($conf->global->MAIN_FEATURES_LEVEL >= 2)	// TODO Not used by current code
{
	print '<tr class="oddeven"><td>';
	print $langs->trans("STRIPE_IDEAL").'</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('STRIPE_IDEAL');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("STRIPE_IDEAL", $arrval, $conf->global->STRIPE_SEPA_DIRECT_DEBIT);
	}
	print ' &nbsp; <span class="opacitymedium">'.$langs->trans("ExampleOnlyForNLCustomers").'</span>';
	print '</td></tr>';
}

// Warehouse for automatic decrement
//if ($conf->global->MAIN_FEATURES_LEVEL >= 2)	// warehouse to reduce stock for online payment
//{
//	print '<tr class="oddeven"><td>';
//	print $langs->trans("ONLINE_PAYMENT_WAREHOUSE").'</td><td>';
//	print $formproduct->selectWarehouses($conf->global->ONLINE_PAYMENT_WAREHOUSE, 'ONLINE_PAYMENT_WAREHOUSE', '', 1, $disabled);
//	print '</td></tr>';
//}

print '<tr class="oddeven"><td>';
print $langs->trans("CSSUrlForPaymentForm").'</td><td>';
print '<input size="64" type="text" name="ONLINE_PAYMENT_CSS_URL" value="'.$conf->global->ONLINE_PAYMENT_CSS_URL.'">';
print ' &nbsp; <span class="opacitymedium">'.$langs->trans("Example").': http://mysite/mycss.css</span>';
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("MessageForm").'</td><td>';
$doleditor = new DolEditor('ONLINE_PAYMENT_MESSAGE_FORM', $conf->global->ONLINE_PAYMENT_MESSAGE_FORM, '', 100, 'dolibarr_details', 'In', false, true, true, ROWS_2, '90%');
$doleditor->Create();
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("MessageOK").'</td><td>';
$doleditor = new DolEditor('ONLINE_PAYMENT_MESSAGE_OK', $conf->global->ONLINE_PAYMENT_MESSAGE_OK, '', 100, 'dolibarr_details', 'In', false, true, true, ROWS_2, '90%');
$doleditor->Create();
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("MessageKO").'</td><td>';
$doleditor = new DolEditor('ONLINE_PAYMENT_MESSAGE_KO', $conf->global->ONLINE_PAYMENT_MESSAGE_KO, '', 100, 'dolibarr_details', 'In', false, true, true, ROWS_2, '90%');
$doleditor->Create();
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("ONLINE_PAYMENT_SENDEMAIL").'</td><td>';
print '<input class="minwidth200" type="text" name="ONLINE_PAYMENT_SENDEMAIL" value="'.$conf->global->ONLINE_PAYMENT_SENDEMAIL.'">';
print ' &nbsp; <span class="opacitymedium">'.$langs->trans("Example").': myemail@myserver.com, Payment service &lt;myemail2@myserver2.com&gt;</span>';
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
print '<input class="minwidth300"  type="text" id="PAYMENT_SECURITY_TOKEN" name="PAYMENT_SECURITY_TOKEN" value="'.$conf->global->PAYMENT_SECURITY_TOKEN.'">';
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

print '<div class="center"><input type="submit" class="button button-save" value="'.$langs->trans("Save").'"></div>';

print '</form>';

print '<br><br>';


$token = '';

include DOL_DOCUMENT_ROOT.'/core/tpl/onlinepaymentlinks.tpl.php';

print info_admin($langs->trans("ExampleOfTestCreditCard", '4242424242424242 (no 3DSecure) or 4000000000003063 (3DSecure required) or 4000002760003184 (3DSecure2 required on all transaction) or 4000003800000446 (3DSecure2 required the off-seesion allowed)', '4000000000000101', '4000000000000069', '4000000000000341'));

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
	            });
		   });';
	print '</script>';
}

// End of page
llxFooter();
$db->close();
