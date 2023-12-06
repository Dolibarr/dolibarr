<?php
/* Copyright (C) 2017 Laurent Destailleur <eldy@destailleur.fr>
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

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

print '<!-- BEGIN PHP TEMPLATE ONLINEPAYMENTLINKS -->';

// Url list
print '<u>'.$langs->trans("FollowingUrlAreAvailableToMakePayments").':</u><br><br>';
print img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans("ToOfferALinkForOnlinePaymentOnFreeAmount", $servicename).':</span><br>';
print '<strong class="wordbreak">'.getOnlinePaymentUrl(1, 'free')."</strong><br><br>\n";

if (isModEnabled('commande')) {
	print '<div id="order"></div>';
	print img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans("ToOfferALinkForOnlinePaymentOnOrder", $servicename).':</span><br>';
	print '<strong class="wordbreak">'.getOnlinePaymentUrl(1, 'order')."</strong><br>\n";
	if (getDolGlobalString('PAYMENT_SECURITY_TOKEN') && getDolGlobalString('PAYMENT_SECURITY_TOKEN_UNIQUE')) {
		$langs->load("orders");
		print '<form action="'.$_SERVER["PHP_SELF"].'#order" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';

		print $langs->trans("EnterRefToBuildUrl", $langs->transnoentitiesnoconv("Order")).': ';
		print '<input type="text class="flat" id="generate_order_ref" name="generate_order_ref" value="'.GETPOST('generate_order_ref', 'alpha').'" size="10">';
		print '<input type="submit" class="none reposition button smallpaddingimp" value="'.$langs->trans("GetSecuredUrl").'">';
		if (GETPOST('generate_order_ref', 'alpha')) {
			$url = getOnlinePaymentUrl(0, 'order', GETPOST('generate_order_ref', 'alpha'));
			print '<div class="urllink"><input type="text" class="wordbreak quatrevingtpercent" value="';
			print $url;
			print '"></div>'."\n";
		}
		print '</form>';
	}
	print '<br>';
}
if (isModEnabled('facture')) {
	print '<div id="invoice"></div>';
	print img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans("ToOfferALinkForOnlinePaymentOnInvoice", $servicename).':</span><br>';
	print '<strong class="wordbreak">'.getOnlinePaymentUrl(1, 'invoice')."</strong><br>\n";
	if (getDolGlobalString('PAYMENT_SECURITY_TOKEN') && getDolGlobalString('PAYMENT_SECURITY_TOKEN_UNIQUE')) {
		$langs->load("bills");
		print '<form action="'.$_SERVER["PHP_SELF"].'#invoice" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';

		print $langs->trans("EnterRefToBuildUrl", $langs->transnoentitiesnoconv("Invoice")).': ';
		print '<input type="text class="flat" id="generate_invoice_ref" name="generate_invoice_ref" value="'.GETPOST('generate_invoice_ref', 'alpha').'" size="10">';
		print '<input type="submit" class="none reposition button smallpaddingimp" value="'.$langs->trans("GetSecuredUrl").'">';
		if (GETPOST('generate_invoice_ref', 'alpha')) {
			$url = getOnlinePaymentUrl(0, 'invoice', GETPOST('generate_invoice_ref', 'alpha'));
			print '<div class="urllink"><input type="text" class="wordbreak quatrevingtpercent" value="';
			print $url;
			print '"></div>'."\n";
		}
		print '</form>';
	}
	print '<br>';
}
if (isModEnabled('contrat')) {
	print '<div id="contractline"></div>';
	print img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans("ToOfferALinkForOnlinePaymentOnContractLine", $servicename).':</span><br>';
	print '<strong class="wordbreak">'.getOnlinePaymentUrl(1, 'contractline')."</strong><br>\n";
	if (getDolGlobalString('PAYMENT_SECURITY_TOKEN') && getDolGlobalString('PAYMENT_SECURITY_TOKEN_UNIQUE')) {
		$langs->load("contracts");
		print '<form action="'.$_SERVER["PHP_SELF"].'#contractline" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';

		print $langs->trans("EnterRefToBuildUrl", $langs->transnoentitiesnoconv("ContractLine")).': ';
		print '<input type="text class="flat" id="generate_contract_ref" name="generate_contract_ref" value="'.GETPOST('generate_contract_ref', 'alpha').'" size="10">';
		print '<input type="submit" class="none reposition button smallpaddingimp" value="'.$langs->trans("GetSecuredUrl").'">';
		if (GETPOST('generate_contract_ref')) {
			$url = getOnlinePaymentUrl(0, 'contractline', GETPOST('generate_contract_ref', 'alpha'));
			print '<div class="urllink"><input type="text" class="wordbreak quatrevingtpercent" value="';
			print $url;
			print '"></div>'."\n";
		}
		print '</form>';
	}
	print '<br>';
}
if (isModEnabled('adherent')) {
	print '<div id="membersubscription"></div>';
	print img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans("ToOfferALinkForOnlinePaymentOnMemberSubscription", $servicename).':</span><br>';
	print '<strong class="wordbreak">'.getOnlinePaymentUrl(1, 'membersubscription')."</strong><br>\n";
	if (getDolGlobalString('PAYMENT_SECURITY_TOKEN') && getDolGlobalString('PAYMENT_SECURITY_TOKEN_UNIQUE')) {
		$langs->load("members");
		print '<form action="'.$_SERVER["PHP_SELF"].'#membersubscription" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';

		print $langs->trans("EnterRefToBuildUrl", $langs->transnoentitiesnoconv("Member")).': ';
		print '<input type="text class="flat" id="generate_member_ref" name="generate_member_ref" value="'.GETPOST('generate_member_ref', 'alpha').'" size="10">';
		print '<input type="submit" class="none reposition button smallpaddingimp" value="'.$langs->trans("GetSecuredUrl").'">';
		if (GETPOST('generate_member_ref')) {
			$url = getOnlinePaymentUrl(0, 'membersubscription', GETPOST('generate_member_ref', 'alpha'));
			print '<div class="urllink"><input type="text" class="wordbreak quatrevingtpercent" value="';
			print $url;
			print '"></div>'."\n";
		}
		print '</form>';
	}
	print '<br>';
}
if (isModEnabled('don')) {
	print '<div id="donation"></div>';
	print img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans("ToOfferALinkForOnlinePaymentOnDonation", $servicename).':</span><br>';
	print '<strong class="wordbreak">'.getOnlinePaymentUrl(1, 'donation')."</strong><br>\n";
	if (getDolGlobalString('PAYMENT_SECURITY_TOKEN') && getDolGlobalString('PAYMENT_SECURITY_TOKEN_UNIQUE')) {
		$langs->load("members");
		print '<form action="'.$_SERVER["PHP_SELF"].'#donation" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';

		print $langs->trans("EnterRefToBuildUrl", $langs->transnoentitiesnoconv("Don")).': ';
		print '<input type="text class="flat" id="generate_donation_ref" name="generate_donation_ref" value="'.GETPOST('generate_donation_ref', 'alpha').'" size="10">';
		print '<input type="submit" class="none reposition button smallpaddingimp" value="'.$langs->trans("GetSecuredUrl").'">';
		if (GETPOST('generate_donation_ref')) {
			print '<div class="urllink"><input type="text" class="wordbreak quatrevingtpercent" value="';
			$url = getOnlinePaymentUrl(0, 'donation', GETPOST('generate_donation_ref', 'alpha'));
			print $url;
			print '"></div>'."\n";
		}
		print '</form>';
	}
	print '<br>';
}

$constname = 'PAYMENT_SECURITY_TOKEN';

// Add button to autosuggest a key
include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
print dolJSToSetRandomPassword($constname);

print info_admin($langs->trans("YouCanAddTagOnUrl"));

print '<!-- END PHP TEMPLATE ONLINEPAYMENTLINKS -->';
