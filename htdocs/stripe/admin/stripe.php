<?php
/* Copyright (C) 2017		Alexandre Spangaro		<aspangaro@zendsi.com>
 * Copyright (C) 2017		Olivier Geffroy			<jeff@jeffinfo.com>
 * Copyright (C) 2017		Saasprov				<saasprov@gmail.com>
 * Copyright (C) 2018		ptibogxiv				<support@ptibogxiv.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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

$servicename='Stripe';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'other', 'paypal', 'paybox', 'stripe'));

if (! $user->admin) accessforbidden();

$action = GETPOST('action','alpha');


if ($action == 'setvalue' && $user->admin)
{
	$db->begin();

	if (empty($conf->stripeconnect->enabled)) {
		$result = dolibarr_set_const($db, "STRIPE_TEST_PUBLISHABLE_KEY", GETPOST('STRIPE_TEST_PUBLISHABLE_KEY', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "STRIPE_TEST_SECRET_KEY", GETPOST('STRIPE_TEST_SECRET_KEY', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "STRIPE_TEST_WEBHOOK_KEY", GETPOST('STRIPE_TEST_WEBHOOK_KEY', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "STRIPE_LIVE_PUBLISHABLE_KEY", GETPOST('STRIPE_LIVE_PUBLISHABLE_KEY', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "STRIPE_LIVE_SECRET_KEY", GETPOST('STRIPE_LIVE_SECRET_KEY', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
		$result = dolibarr_set_const($db, "STRIPE_LIVE_WEBHOOK_KEY", GETPOST('STRIPE_LIVE_WEBHOOK_KEY', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (! $result > 0)
			$error ++;
	}
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_CREDITOR", GETPOST('ONLINE_PAYMENT_CREDITOR', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (! $result > 0)
		$error ++;
	$result = dolibarr_set_const($db, "STRIPE_BANK_ACCOUNT_FOR_PAYMENTS", GETPOST('STRIPE_BANK_ACCOUNT_FOR_PAYMENTS', 'int'), 'chaine', 0, '', $conf->entity);
	if (! $result > 0)
		$error ++;
    $result = dolibarr_set_const($db, "STRIPE_USER_ACCOUNT_FOR_ACTIONS", GETPOST('STRIPE_USER_ACCOUNT_FOR_ACTIONS', 'int'), 'chaine', 0, '', $conf->entity);
    if (! $result > 0) {
        $error ++;
    }
    $result = dolibarr_set_const($db, "STRIPE_BANK_ACCOUNT_FOR_BANKTRANSFERS", GETPOST('STRIPE_BANK_ACCOUNT_FOR_BANKTRANSFERS', 'int'), 'chaine', 0, '', $conf->entity);
	if (! $result > 0)
		$error ++;
    $result = dolibarr_set_const($db, "STRIPE_MINIMAL_3DSECURE", GETPOST('STRIPE_MINIMAL_3DSECURE', 'int'), 'chaine', 0, '', $conf->entity);
	if (! $result > 0)
		$error ++;
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_CSS_URL", GETPOST('ONLINE_PAYMENT_CSS_URL', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (! $result > 0)
		$error ++;
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_MESSAGE_FORM", GETPOST('ONLINE_PAYMENT_MESSAGE_FORM', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (! $result > 0)
		$error ++;
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_MESSAGE_OK", GETPOST('ONLINE_PAYMENT_MESSAGE_OK', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (! $result > 0)
		$error ++;
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_MESSAGE_KO", GETPOST('ONLINE_PAYMENT_MESSAGE_KO', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (! $result > 0)
		$error ++;
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_SENDEMAIL", GETPOST('ONLINE_PAYMENT_SENDEMAIL'), 'chaine', 0, '', $conf->entity);
	if (! $result > 0)
		$error ++;
	// Stock decrement
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_WAREHOUSE", (GETPOST('ONLINE_PAYMENT_WAREHOUSE', 'alpha') > 0 ? GETPOST('ONLINE_PAYMENT_WAREHOUSE', 'alpha') : ''), 'chaine', 0, '', $conf->entity);
	if (! $result > 0)
		$error ++;

	// Payment token for URL
	$result = dolibarr_set_const($db, "PAYMENT_SECURITY_TOKEN", GETPOST('PAYMENT_SECURITY_TOKEN', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (! $result > 0)
		$error ++;
	$result = dolibarr_set_const($db, "PAYMENT_SECURITY_TOKEN_UNIQUE", GETPOST('PAYMENT_SECURITY_TOKEN_UNIQUE', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (! $result > 0)
		$error ++;

	if (! $error) {
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		$db->rollback();
		dol_print_error($db);
	}
}

if ($action=="setlive")
{
	$liveenable = GETPOST('value','int');
	$res = dolibarr_set_const($db, "STRIPE_LIVE", $liveenable, 'yesno', 0, '', $conf->entity);
	if ($res > 0) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else
	{
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}
//TODO: import script for stripe account saving in alone or connect mode for stripe.class.php


/*
 *	View
 */

$form=new Form($db);
$formproduct=new FormProduct($db);

llxHeader('',$langs->trans("StripeSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ModuleSetup").' Stripe',$linkback);

$head=stripeadmin_prepare_head();

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvalue">';

dol_fiche_head($head, 'stripeaccount', '', -1);

print $langs->trans("StripeDesc")."<br>\n";

print '<br>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("AccountParameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

print '<tr class="oddeven">';
print '<td class="titlefield">';
print $langs->trans("StripeLiveEnabled").'</td><td>';
if (!empty($conf->global->STRIPE_LIVE))
{
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=setlive&value=0">';
	print img_picto($langs->trans("Activated"),'switch_on');
	print '</a>';
}
else
{
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=setlive&value=1">';
	print img_picto($langs->trans("Disabled"),'switch_off');
	print '</a>';
}
print '</td></tr>';

if (empty($conf->stripeconnect->enabled))
{
	print '<tr class="oddeven"><td>';
	print '<span class="fieldrequired">'.$langs->trans("STRIPE_TEST_PUBLISHABLE_KEY").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="STRIPE_TEST_PUBLISHABLE_KEY" value="'.$conf->global->STRIPE_TEST_PUBLISHABLE_KEY.'">';
	print ' &nbsp; '.$langs->trans("Example").': pk_test_xxxxxxxxxxxxxxxxxxxxxxxx';
	print '</td></tr>';

	print '<tr class="oddeven"><td>';
	print '<span class="titlefield fieldrequired">'.$langs->trans("STRIPE_TEST_SECRET_KEY").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="STRIPE_TEST_SECRET_KEY" value="'.$conf->global->STRIPE_TEST_SECRET_KEY.'">';
	print ' &nbsp; '.$langs->trans("Example").': sk_test_xxxxxxxxxxxxxxxxxxxxxxxx';
	print '</td></tr>';

	print '<tr class="oddeven"><td>';
	print '<span>'.$langs->trans("STRIPE_TEST_WEBHOOK_KEY").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="STRIPE_TEST_WEBHOOK_KEY" value="'.$conf->global->STRIPE_TEST_WEBHOOK_KEY.'">';
	print ' &nbsp; '.$langs->trans("Example").': whsec_xxxxxxxxxxxxxxxxxxxxxxxx';
	print '</td></tr>';
} else {
	print '<tr class="oddeven"><td>'.$langs->trans("StripeConnect").'</td>';
	print '<td><b>'.$langs->trans("StripeConnect_Mode").'</b><br/>';
	print $langs->trans("STRIPE_APPLICATION_FEE_PLATFORM").' ';
	print price($conf->global->STRIPE_APPLICATION_FEE_PERCENT);
	print '% + ';
	print price($conf->global->STRIPE_APPLICATION_FEE);
	print ' '.$langs->getCurrencySymbol($conf->currency).' '.$langs->trans("minimum").' '.price($conf->global->STRIPE_APPLICATION_FEE_MINIMAL).' '.$langs->getCurrencySymbol($conf->currency).' </td></tr>';
	print '</td></tr>';
}

if (empty($conf->stripeconnect->enabled))
{
	print '<tr class="oddeven"><td>';
	print '<span class="fieldrequired">'.$langs->trans("STRIPE_LIVE_PUBLISHABLE_KEY").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="STRIPE_LIVE_PUBLISHABLE_KEY" value="'.$conf->global->STRIPE_LIVE_PUBLISHABLE_KEY.'">';
	print ' &nbsp; '.$langs->trans("Example").': pk_live_xxxxxxxxxxxxxxxxxxxxxxxx';
	print '</td></tr>';

	print '<tr class="oddeven"><td>';
	print '<span class="fieldrequired">'.$langs->trans("STRIPE_LIVE_SECRET_KEY").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="STRIPE_LIVE_SECRET_KEY" value="'.$conf->global->STRIPE_LIVE_SECRET_KEY.'">';
	print ' &nbsp; '.$langs->trans("Example").': sk_live_xxxxxxxxxxxxxxxxxxxxxxxx';
	print '</td></tr>';

	print '<tr class="oddeven"><td>';
	print '<span>'.$langs->trans("STRIPE_LIVE_WEBHOOK_KEY").'</span></td><td>';
	print '<input class="minwidth300" type="text" name="STRIPE_LIVE_WEBHOOK_KEY" value="'.$conf->global->STRIPE_LIVE_WEBHOOK_KEY.'">';
	print ' &nbsp; '.$langs->trans("Example").': whsec_xxxxxxxxxxxxxxxxxxxxxxxx';
	print '</td></tr>';
}
else
{
	print '<tr class="oddeven"><td>'.$langs->trans("StripeConnect").'</td>';
	print '<td>'.$langs->trans("StripeConnect_Mode").'</td></tr>';
}


print '</table>';

print '<br>';


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td class="titlefield">'.$langs->trans("UsageParameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

print '<tr class="oddeven"><td>';
print $langs->trans("VendorName").'</td><td>';
print '<input size="64" type="text" name="ONLINE_PAYMENT_CREDITOR" value="'.$conf->global->ONLINE_PAYMENT_CREDITOR.'">';
print ' &nbsp; '.$langs->trans("Example").': '.$mysoc->name;
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("StripeUserAccountForActions").'</td><td>';
print $form->select_dolusers($conf->global->STRIPE_USER_ACCOUNT_FOR_ACTIONS, 'STRIPE_USER_ACCOUNT_FOR_ACTIONS', 0);
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("BankAccount").'</td><td>';
$form->select_comptes($conf->global->STRIPE_BANK_ACCOUNT_FOR_PAYMENTS, 'STRIPE_BANK_ACCOUNT_FOR_PAYMENTS', 0, '', 1);
print '</td></tr>';

if ($conf->global->MAIN_FEATURES_LEVEL >= 2)	// What is this for ?
{
	print '<tr class="oddeven"><td>';
	print $langs->trans("BankAccountForBankTransfer").'</td><td>';
	$form->select_comptes($conf->global->STRIPE_BANK_ACCOUNT_FOR_BANKTRANSFERS, 'STRIPE_BANK_ACCOUNT_FOR_BANKTRANSFERS', 0, '', 1);
	print '</td></tr>';
}

// Minimal amount for force 3Dsecure if it's optionnal
if ($conf->global->MAIN_FEATURES_LEVEL >= 2)	// TODO Not used by current code
{
	print '<tr class="oddeven"><td>';
	print $langs->trans("STRIPE_MINIMAL_3DSECURE").'</td><td>';
	print '<input class="flat" name="STRIPE_MINIMAL_3DSECURE" size="3" value="' .$conf->global->STRIPE_MINIMAL_3DSECURE . '">'.$langs->getCurrencySymbol($conf->currency).'</td></tr>';
}

// Warehouse for automatic decrement
if ($conf->global->MAIN_FEATURES_LEVEL >= 2)	// What is this for ?
{
	print '<tr class="oddeven"><td>';
	print $langs->trans("ONLINE_PAYMENT_WAREHOUSE").'</td><td>';
	print $formproduct->selectWarehouses($conf->global->ONLINE_PAYMENT_WAREHOUSE,'ONLINE_PAYMENT_WAREHOUSE','',1,$disabled);
	print '</td></tr>';
}

print '<tr class="oddeven"><td>';
print $langs->trans("CSSUrlForPaymentForm").'</td><td>';
print '<input size="64" type="text" name="ONLINE_PAYMENT_CSS_URL" value="'.$conf->global->ONLINE_PAYMENT_CSS_URL.'">';
print ' &nbsp; '.$langs->trans("Example").': http://mysite/mycss.css';
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("MessageForm").'</td><td>';
$doleditor=new DolEditor('ONLINE_PAYMENT_MESSAGE_FORM',$conf->global->ONLINE_PAYMENT_MESSAGE_FORM,'',100,'dolibarr_details','In',false,true,true,ROWS_2,'90%');
$doleditor->Create();
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("MessageOK").'</td><td>';
$doleditor=new DolEditor('ONLINE_PAYMENT_MESSAGE_OK',$conf->global->ONLINE_PAYMENT_MESSAGE_OK,'',100,'dolibarr_details','In',false,true,true,ROWS_2,'90%');
$doleditor->Create();
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("MessageKO").'</td><td>';
$doleditor=new DolEditor('ONLINE_PAYMENT_MESSAGE_KO',$conf->global->ONLINE_PAYMENT_MESSAGE_KO,'',100,'dolibarr_details','In',false,true,true,ROWS_2,'90%');
$doleditor->Create();
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("ONLINE_PAYMENT_SENDEMAIL").'</td><td>';
print '<input size="32" type="text" name="ONLINE_PAYMENT_SENDEMAIL" value="'.$conf->global->ONLINE_PAYMENT_SENDEMAIL.'">';
print ' &nbsp; '.$langs->trans("Example").': myemail@myserver.com, Payment service &lt;myemail2@myserver2.com&gt;';
print '</td></tr>';

// Payment token for URL
print '<tr class="oddeven"><td>';
print $langs->trans("SecurityToken").'</td><td>';
print '<input size="48" type="text" id="PAYMENT_SECURITY_TOKEN" name="PAYMENT_SECURITY_TOKEN" value="'.$conf->global->PAYMENT_SECURITY_TOKEN.'">';
if (! empty($conf->use_javascript_ajax))
	print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_token" class="linkobject"');
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("SecurityTokenIsUnique").'</td><td>';
print $form->selectyesno("PAYMENT_SECURITY_TOKEN_UNIQUE",(empty($conf->global->PAYMENT_SECURITY_TOKEN)?0:$conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE),1);
print '</td></tr>';

print '</table>';

dol_fiche_end();

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></div>';

print '</form>';

print '<br><br>';


$token='';

include DOL_DOCUMENT_ROOT.'/core/tpl/onlinepaymentlinks.tpl.php';

print info_admin($langs->trans("ExampleOfTestCreditCard", '4242424242424242', '4000000000000101', '4000000000000069', '4000000000000341'));

if (! empty($conf->use_javascript_ajax))
{
	print "\n".'<script type="text/javascript">';
	print '$(document).ready(function () {
            $("#apidoc").hide();
            $("#apidoca").click(function() {
                $("#apidoc").show();
            	$("#apidoca").hide();
            });
    });';
	print '</script>';
}

// End of page
llxFooter();
$db->close();
