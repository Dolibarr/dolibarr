<?php
/* Copyright (C) 2017		Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2017		Olivier Geffroy			<jeff@jeffinfo.com>
 * Copyright (C) 2017		Saasprov				<saasprov@gmail.com>
 * Copyright (C) 2018-2022  Thibault FOUCART		<support@ptibogxiv.net>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
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
 * \file       htdocs/stripe/admin/stripe.php
 * \ingroup    stripe
 * \brief      Page to setup stripe module
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/stripe/lib/stripe.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';

$servicename = 'Stripe';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'other', 'paypal', 'paybox', 'stripe'));

if (empty($user->admin)) {
	accessforbidden();
}
if (empty($conf->stripe->enabled)) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');


/*
 * Actions
 */

if ($action == 'setvalue' && $user->admin) {
	$db->begin();

	if (empty($conf->stripeconnect->enabled)) {
		$result = dolibarr_set_const($db, "STRIPE_TEST_PUBLISHABLE_KEY", GETPOST('STRIPE_TEST_PUBLISHABLE_KEY', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (!($result > 0)) {
			$error++;
		}
		$result = dolibarr_set_const($db, "STRIPE_TEST_SECRET_KEY", GETPOST('STRIPE_TEST_SECRET_KEY', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (!($result > 0)) {
			$error++;
		}
		$result = dolibarr_set_const($db, "STRIPE_TEST_WEBHOOK_ID", GETPOST('STRIPE_TEST_WEBHOOK_ID', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (!($result > 0)) {
			$error++;
		}
		$result = dolibarr_set_const($db, "STRIPE_TEST_WEBHOOK_KEY", GETPOST('STRIPE_TEST_WEBHOOK_KEY', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (!($result > 0)) {
			$error++;
		}
		$result = dolibarr_set_const($db, "STRIPE_LIVE_PUBLISHABLE_KEY", GETPOST('STRIPE_LIVE_PUBLISHABLE_KEY', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (!($result > 0)) {
			$error++;
		}
		$result = dolibarr_set_const($db, "STRIPE_LIVE_SECRET_KEY", GETPOST('STRIPE_LIVE_SECRET_KEY', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (!($result > 0)) {
			$error++;
		}
		$result = dolibarr_set_const($db, "STRIPE_LIVE_WEBHOOK_ID", GETPOST('STRIPE_LIVE_WEBHOOK_ID', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (!($result > 0)) {
			$error++;
		}
		$result = dolibarr_set_const($db, "STRIPE_LIVE_WEBHOOK_KEY", GETPOST('STRIPE_LIVE_WEBHOOK_KEY', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (!($result > 0)) {
			$error++;
		}
	}
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_CREDITOR", GETPOST('ONLINE_PAYMENT_CREDITOR', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}
	$result = dolibarr_set_const($db, "STRIPE_BANK_ACCOUNT_FOR_PAYMENTS", GETPOSTINT('STRIPE_BANK_ACCOUNT_FOR_PAYMENTS'), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}
	$result = dolibarr_set_const($db, "STRIPE_USER_ACCOUNT_FOR_ACTIONS", GETPOSTINT('STRIPE_USER_ACCOUNT_FOR_ACTIONS'), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}
	$result = dolibarr_set_const($db, "STRIPE_BANK_ACCOUNT_FOR_BANKTRANSFERS", GETPOSTINT('STRIPE_BANK_ACCOUNT_FOR_BANKTRANSFERS'), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}
	if (GETPOSTISSET('STRIPE_LOCATION')) {
		$result = dolibarr_set_const($db, "STRIPE_LOCATION", GETPOST('STRIPE_LOCATION', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (!$result > 0) {
			$error++;
		}
	}
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_CSS_URL", GETPOST('ONLINE_PAYMENT_CSS_URL', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_MESSAGE_FORM", GETPOST('ONLINE_PAYMENT_MESSAGE_FORM', 'restricthtml'), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_MESSAGE_OK", GETPOST('ONLINE_PAYMENT_MESSAGE_OK', 'restricthtml'), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_MESSAGE_KO", GETPOST('ONLINE_PAYMENT_MESSAGE_KO', 'restricthtml'), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_SENDEMAIL", GETPOST('ONLINE_PAYMENT_SENDEMAIL'), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}
	// Stock decrement
	//$result = dolibarr_set_const($db, "ONLINE_PAYMENT_WAREHOUSE", (GETPOST('ONLINE_PAYMENT_WAREHOUSE', 'alpha') > 0 ? GETPOST('ONLINE_PAYMENT_WAREHOUSE', 'alpha') : ''), 'chaine', 0, '', $conf->entity);
	//if (! $result > 0)
	//	$error ++;

	// Payment token for URL
	$result = dolibarr_set_const($db, "PAYMENT_SECURITY_TOKEN", GETPOST('PAYMENT_SECURITY_TOKEN', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}
	if (empty($conf->use_javascript_ajax)) {
		$result = dolibarr_set_const($db, "PAYMENT_SECURITY_TOKEN_UNIQUE", GETPOST('PAYMENT_SECURITY_TOKEN_UNIQUE', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (!($result > 0)) {
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

if ($action == "setlive") {
	$liveenable = GETPOSTINT('value');
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

if (empty($conf->stripeconnect->enabled)) {
	print '<tr class="oddeven"><td>';
	print '<span class="fieldrequired">'.$langs->trans("STRIPE_TEST_PUBLISHABLE_KEY").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="STRIPE_TEST_PUBLISHABLE_KEY" value="' . getDolGlobalString('STRIPE_TEST_PUBLISHABLE_KEY').'" placeholder="'.$langs->trans("Example").': pk_test_xxxxxxxxxxxxxxxxxxxxxxxx">';
	print '</td><td></td></tr>';

	print '<tr class="oddeven"><td>';
	print '<span class="titlefield fieldrequired">'.$langs->trans("STRIPE_TEST_SECRET_KEY").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="STRIPE_TEST_SECRET_KEY" value="' . getDolGlobalString('STRIPE_TEST_SECRET_KEY').'" placeholder="'.$langs->trans("Example").': sk_test_xxxxxxxxxxxxxxxxxxxxxxxx">';
	print '</td><td></td></tr>';

	print '<tr class="oddeven"><td>';
	print '<span class="titlefield">'.$langs->trans("STRIPE_TEST_WEBHOOK_KEY").'</span></td><td>';
	if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
		print '<input class="minwidth300" type="text" name="STRIPE_TEST_WEBHOOK_ID" value="'.getDolGlobalString('STRIPE_TEST_WEBHOOK_ID').'" placeholder="'.$langs->trans("Example").': we_xxxxxxxxxxxxxxxxxxxxxxxx">';
		print '<br>';
	}
	print '<input class="minwidth300" type="text" name="STRIPE_TEST_WEBHOOK_KEY" value="'.getDolGlobalString('STRIPE_TEST_WEBHOOK_KEY').'" placeholder="'.$langs->trans("Example").': whsec_xxxxxxxxxxxxxxxxxxxxxxxx">';
	$out = img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans("ToOfferALinkForTestWebhook").'</span> ';
	$url = dol_buildpath('/public/stripe/ipn.php', 3);
	$url .= '?test=1';
	//global $dolibarr_main_instance_unique_id;
	//$url .= '&securitykey='.dol_hash('stripeipn-'.$dolibarr_main_instance_unique_id.'-'.$conf->global->STRIPE_TEST_PUBLISHABLE_KEY, 'md5');
	$out .= '<input type="text" id="onlinetestwebhookurl" class="minwidth500" value="'.$url.'" disabled>';
	$out .= ajax_autoselect("onlinetestwebhookurl");
	print '<br>'.$out;
	print '</td><td>';
	if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
		if (getDolGlobalString('STRIPE_TEST_WEBHOOK_KEY') && getDolGlobalString('STRIPE_TEST_SECRET_KEY') && getDolGlobalString('STRIPE_TEST_WEBHOOK_ID')) {
			if (utf8_check($conf->global->STRIPE_TEST_SECRET_KEY)) {
				try {
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
					$endpoint->url = $url;
					// @phan-suppress-next-line PhanDeprecatedFunction
					$endpoint->save();

					if ($endpoint->status == 'enabled') {
						print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=ipn&webhook='.$endpoint->id.'&status=0">';
						print img_picto($langs->trans("Activated"), 'switch_on');
					} else {
						print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=ipn&webhook='.$endpoint->id.'&status=1">';
						print img_picto($langs->trans("Disabled"), 'switch_off');
					}
				} catch (Exception $e) {
					print $e->getMessage();
				}
			} else {
				print 'Bad value for the secret key. Reenter and save it again to fix this.';
			}
		} else {
			print img_picto($langs->trans("Inactive"), 'statut5');
		}
	}
	print'</td></tr>';
} else {
	print '<tr class="oddeven"><td>'.$langs->trans("StripeConnect").'</td>';
	print '<td><b>'.$langs->trans("StripeConnect_Mode").'</b><br>';
	print $langs->trans("STRIPE_APPLICATION_FEE_PLATFORM").' ';
	print price($conf->global->STRIPE_APPLICATION_FEE_PERCENT);
	print '% + ';
	print price($conf->global->STRIPE_APPLICATION_FEE);
	print ' '.$langs->getCurrencySymbol($conf->currency).' '.$langs->trans("minimum").' '.price($conf->global->STRIPE_APPLICATION_FEE_MINIMAL).' '.$langs->getCurrencySymbol($conf->currency);
	print '</td><td></td></tr>';
}

if (empty($conf->stripeconnect->enabled)) {
	print '<tr class="oddeven"><td>';
	print '<span class="fieldrequired">'.$langs->trans("STRIPE_LIVE_PUBLISHABLE_KEY").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="STRIPE_LIVE_PUBLISHABLE_KEY" value="'.getDolGlobalString('STRIPE_LIVE_PUBLISHABLE_KEY').'" placeholder="'.$langs->trans("Example").': pk_live_xxxxxxxxxxxxxxxxxxxxxxxx">';
	print '</td><td></td></tr>';

	print '<tr class="oddeven"><td>';
	print '<span class="fieldrequired">'.$langs->trans("STRIPE_LIVE_SECRET_KEY").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="STRIPE_LIVE_SECRET_KEY" value="'.getDolGlobalString('STRIPE_LIVE_SECRET_KEY').'" placeholder="'.$langs->trans("Example").': sk_live_xxxxxxxxxxxxxxxxxxxxxxxx">';
	print '</td><td></td></tr>';

	print '<tr class="oddeven"><td>';
	print '<span class="titlefield">'.$langs->trans("STRIPE_LIVE_WEBHOOK_KEY").'</span></td><td>';
	if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
		print '<input class="minwidth300" type="text" name="STRIPE_LIVE_WEBHOOK_ID" value="'.getDolGlobalString('STRIPE_LIVE_WEBHOOK_ID').'" placeholder="'.$langs->trans("Example").': we_xxxxxxxxxxxxxxxxxxxxxxxx">';
		print '<br>';
	}
	print '<input class="minwidth300" type="text" name="STRIPE_LIVE_WEBHOOK_KEY" value="'.getDolGlobalString('STRIPE_LIVE_WEBHOOK_KEY').'" placeholder="'.$langs->trans("Example").': whsec_xxxxxxxxxxxxxxxxxxxxxxxx">';
	$out = img_picto('', 'globe', 'class="pictofixedwidth"').' <span class="opacitymedium">'.$langs->trans("ToOfferALinkForLiveWebhook").'</span> ';
	$url = dol_buildpath('/public/stripe/ipn.php', 3);
	//global $dolibarr_main_instance_unique_id;
	//$url .= '?securitykey='.dol_hash('stripeipn-'.$dolibarr_main_instance_unique_id.'-'.$conf->global->STRIPE_LIVE_PUBLISHABLE_KEY, 'md5');
	$out .= '<input type="text" id="onlinelivewebhookurl" class="minwidth500" value="'.$url.'" disabled>';
	$out .= ajax_autoselect("onlinelivewebhookurl", '0');
	print '<br>'.$out;
	print '</td><td>';
	if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
		if (getDolGlobalString('STRIPE_LIVE_WEBHOOK_KEY') && getDolGlobalString('STRIPE_LIVE_SECRET_KEY') && getDolGlobalString('STRIPE_LIVE_WEBHOOK_ID')) {
			if (utf8_check($conf->global->STRIPE_TEST_SECRET_KEY)) {
				try {
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
					$endpoint->url = $url;
					// @phan-suppress-next-line PhanDeprecatedFunction
					$endpoint->save();
					if ($endpoint->status == 'enabled') {
						print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=ipn&webhook='.$endpoint->id.'&status=0">';
						print img_picto($langs->trans("Activated"), 'switch_on');
					} else {
						print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=ipn&webhook='.$endpoint->id.'&status=1">';
						print img_picto($langs->trans("Disabled"), 'switch_off');
					}
				} catch (Exception $e) {
					print $e->getMessage();
				}
			}
		} else {
			print img_picto($langs->trans("Inactive"), 'statut5');
		}
	}
	print '</td></tr>';
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
print '<input class="minwidth300" type="text" name="ONLINE_PAYMENT_CREDITOR" value="'.getDolGlobalString('ONLINE_PAYMENT_CREDITOR').'">';
print ' &nbsp; <span class="opacitymedium">'.$langs->trans("Example").': '.$mysoc->name.'</span>';
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("StripeUserAccountForActions").'</td><td>';
print img_picto('', 'user', 'class="pictofixedwidth"').$form->select_dolusers(getDolGlobalString('STRIPE_USER_ACCOUNT_FOR_ACTIONS'), 'STRIPE_USER_ACCOUNT_FOR_ACTIONS', 0);
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("BankAccount").'</td><td>';
print img_picto('', 'bank_account', 'class="pictofixedwidth"');
$form->select_comptes(getDolGlobalString('STRIPE_BANK_ACCOUNT_FOR_PAYMENTS'), 'STRIPE_BANK_ACCOUNT_FOR_PAYMENTS', 0, '', 1);
print '</td></tr>';

if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {	// What is this for ?
	print '<tr class="oddeven"><td>';
	print $langs->trans("BankAccountForBankTransfer").'</td><td>';
	print img_picto('', 'bank_account', 'class="pictofixedwidth"');
	$form->select_comptes(getDolGlobalString('STRIPE_BANK_ACCOUNT_FOR_BANKTRANSFERS'), 'STRIPE_BANK_ACCOUNT_FOR_BANKTRANSFERS', 0, '', 1);
	print '</td></tr>';
}

// Card Present for Stripe Terminal
if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {	// TODO Not used by current code
	print '<tr class="oddeven"><td>';
	print $langs->trans("STRIPE_CARD_PRESENT").'</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('STRIPE_CARD_PRESENT');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("STRIPE_CARD_PRESENT", $arrval, $conf->global->STRIPE_CARD_PRESENT);
	}
	print '</td></tr>';
}

// Locations for Stripe Terminal
if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {	// TODO Not used by current code
	print '<tr class="oddeven"><td>';
	print $langs->trans("TERMINAL_LOCATION").'</td><td>';
	$service = 'StripeTest';
	$servicestatus = 0;
	if (getDolGlobalString('STRIPE_LIVE') && !GETPOST('forcesandbox', 'alpha')) {
		$service = 'StripeLive';
		$servicestatus = 1;
	}

	try {
		global $stripearrayofkeysbyenv;
		$site_account = $stripearrayofkeysbyenv[$servicestatus]['secret_key'];
		if (!empty($site_account)) {
			\Stripe\Stripe::setApiKey($site_account);
		}
		if (isModEnabled('stripe') && (!getDolGlobalString('STRIPE_LIVE') || GETPOST('forcesandbox', 'alpha'))) {
			$service = 'StripeTest';
			$servicestatus = '0';
			dol_htmloutput_mesg($langs->trans('YouAreCurrentlyInSandboxMode', 'Stripe'), [], 'warning');
		} else {
			$service = 'StripeLive';
			$servicestatus = '1';
		}
		$stripe = new Stripe($db);
		if (!empty($site_account)) {
			// If $site_account not defined, then key not set and no way to call API Location
			$stripeacc = $stripe->getStripeAccount($service);
			if ($stripeacc) {
				$locations = \Stripe\Terminal\Location::all('', array("stripe_account" => $stripeacc));
			} else {
				$locations = \Stripe\Terminal\Location::all();
			}
		}
	} catch (Exception $e) {
		print $e->getMessage().'<br>';
	}

	// Define the array $location
	$location = array();
	$location[""] = $langs->trans("NotDefined");
	if (!empty($locations)) {
		foreach ($locations as $tmplocation) {
			$location[$tmplocation->id] = $tmplocation->display_name;
		}
	}

	print $form->selectarray("STRIPE_LOCATION", $location, getDolGlobalString('STRIPE_LOCATION'));
	print '</td></tr>';
}

print '<tr class="oddeven"><td>';
print $langs->trans("STRIPE_SEPA_DIRECT_DEBIT").'</td><td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('STRIPE_SEPA_DIRECT_DEBIT');
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $form->selectarray("STRIPE_SEPA_DIRECT_DEBIT", $arrval, getDolGlobalString('STRIPE_SEPA_DIRECT_DEBIT'));
}
print '</td></tr>';


// Activate Klarna
if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {	// TODO Not used by current code
	print '<tr class="oddeven"><td>';
	print $langs->trans("STRIPE_KLARNA").'</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('STRIPE_KLARNA');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("STRIPE_KLARNA", $arrval, $conf->global->STRIPE_KLARNA);
	}
	print '</td></tr>';
}

// Activate Bancontact
if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {	// TODO Not used by current code
	print '<tr class="oddeven"><td>';
	print $langs->trans("STRIPE_BANCONTACT").'</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('STRIPE_BANCONTACT');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("STRIPE_BANCONTACT", $arrval, $conf->global->STRIPE_BANCONTACT);
	}
	print ' &nbsp; <span class="opacitymedium">'.$langs->trans("ExampleOnlyForBECustomers").'</span>';
	print '</td></tr>';
}

// Activate iDEAL
if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {	// TODO Not used by current code
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

// Activate Giropay
if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {	// TODO Not used by current code
	print '<tr class="oddeven"><td>';
	print $langs->trans("STRIPE_GIROPAY").'</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('STRIPE_GIROPAY');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("STRIPE_GIROPAY", $arrval, $conf->global->STRIPE_GIROPAY);
	}
	print ' &nbsp; <span class="opacitymedium">'.$langs->trans("ExampleOnlyForDECustomers").'</span>';
	print '</td></tr>';
}

// Activate Sofort
if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {	// TODO Not used by current code
	print '<tr class="oddeven"><td>';
	print $langs->trans("STRIPE_SOFORT").'</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('STRIPE_SOFORT');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("STRIPE_SOFORT", $arrval, $conf->global->STRIPE_SOFORT);
	}
	print ' &nbsp; <span class="opacitymedium">'.$langs->trans("ExampleOnlyForATBEDEITNLESCustomers").'</span>';
	print '</td></tr>';
}

print '<tr class="oddeven"><td>';
print $langs->trans("CSSUrlForPaymentForm").'</td><td>';
print '<input class="width500" type="text" name="ONLINE_PAYMENT_CSS_URL" value="' . getDolGlobalString('ONLINE_PAYMENT_CSS_URL').'">';
print ' &nbsp; <span class="opacitymedium">'.$langs->trans("Example").': http://mysite/mycss.css</span>';
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("MessageForm").'</td><td>';
$doleditor = new DolEditor('ONLINE_PAYMENT_MESSAGE_FORM', getDolGlobalString("ONLINE_PAYMENT_MESSAGE_FORM"), '', 100, 'dolibarr_details', 'In', false, true, true, ROWS_2, '90%');
$doleditor->Create();
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("MessageOK").'</td><td>';
$doleditor = new DolEditor('ONLINE_PAYMENT_MESSAGE_OK', getDolGlobalString("ONLINE_PAYMENT_MESSAGE_OK"), '', 100, 'dolibarr_details', 'In', false, true, true, ROWS_2, '90%');
$doleditor->Create();
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("MessageKO").'</td><td>';
$doleditor = new DolEditor('ONLINE_PAYMENT_MESSAGE_KO', getDolGlobalString("ONLINE_PAYMENT_MESSAGE_KO"), '', 100, 'dolibarr_details', 'In', false, true, true, ROWS_2, '90%');
$doleditor->Create();
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("ONLINE_PAYMENT_SENDEMAIL").'</td><td>';
print img_picto('', 'email', 'class="pictofixedwidth"');
print '<input class="minwidth200" type="text" name="ONLINE_PAYMENT_SENDEMAIL" value="' . getDolGlobalString('ONLINE_PAYMENT_SENDEMAIL').'">';
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
print '<input class="minwidth300"  type="text" id="PAYMENT_SECURITY_TOKEN" name="PAYMENT_SECURITY_TOKEN" value="' . getDolGlobalString('PAYMENT_SECURITY_TOKEN').'">';
if (!empty($conf->use_javascript_ajax)) {
	print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_token" class="linkobject"');
}
if (getDolGlobalString('PAYMENT_SECURITY_ACCEPT_ANY_TOKEN')) {
	$langs->load("errors");
	print img_warning($langs->trans("WarningTheHiddenOptionIsOn", 'PAYMENT_SECURITY_ACCEPT_ANY_TOKEN'), '', 'pictowarning marginleftonly');
}
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("SecurityTokenIsUnique").'</td><td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('PAYMENT_SECURITY_TOKEN_UNIQUE', null, null, 0, 0, 1);
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $form->selectarray("PAYMENT_SECURITY_TOKEN_UNIQUE", $arrval, $conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE);
}
print '</td></tr>';

print '</table>';
print '</div>';

print dol_get_fiche_end();

print $form->buttonsSaveCancel("Save", '');

print '</form>';

print '<br><br>';


$token = '';

include DOL_DOCUMENT_ROOT.'/core/tpl/onlinepaymentlinks.tpl.php';

print info_admin($langs->trans("ExampleOfTestCreditCard", '4242424242424242 (no 3DSecure) or 4000000000003063 (3DSecure required) or 4000002760003184 (3DSecure2 required on all transaction) or 4000003800000446 (3DSecure2 required, the off-session allowed)', '4000000000000101', '4000000000000069', '4000000000000341'));

if (getDolGlobalString('STRIPE_SEPA_DIRECT_DEBIT')) {
	print info_admin($langs->trans("ExampleOfTestBankAcountForSEPA", 'AT611904300234573201 (pending->succeed) or AT861904300235473202 (pending->failed)'));
}



if (!empty($conf->use_javascript_ajax)) {
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
