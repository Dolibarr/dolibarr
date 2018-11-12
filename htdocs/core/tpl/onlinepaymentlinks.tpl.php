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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

print '<!-- BEGIN PHP TEMPLATE ONLINEPAYMENTLINKS -->';

// Url list
print '<u>'.$langs->trans("FollowingUrlAreAvailableToMakePayments").':</u><br><br>';
print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnFreeAmount",$servicename).':<br>';
print '<strong>'.getOnlinePaymentUrl(1,'free')."</strong><br><br>\n";
if (! empty($conf->commande->enabled))
{
	print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnOrder",$servicename).':<br>';
	print '<strong>'.getOnlinePaymentUrl(1,'order')."</strong><br>\n";
	if (! empty($conf->global->PAYMENT_SECURITY_TOKEN) && ! empty($conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE))
	{
	    $langs->load("orders");
	    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	    print $langs->trans("EnterRefToBuildUrl",$langs->transnoentitiesnoconv("Order")).': ';
        print '<input type="text class="flat" id="generate_order_ref" name="generate_order_ref" value="'.GETPOST('generate_order_ref','alpha').'" size="10">';
        print '<input type="submit" class="none button" value="'.$langs->trans("GetSecuredUrl").'">';
        if (GETPOST('generate_order_ref','alpha'))
        {
            print '<br> -> <strong>';
            $url=getOnlinePaymentUrl(0,'order',GETPOST('generate_order_ref','alpha'));
            print $url;
            print "</strong><br>\n";
        }
        print '</form>';
	}
	print '<br>';
}
if (! empty($conf->facture->enabled))
{
	print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnInvoice",$servicename).':<br>';
	print '<strong>'.getOnlinePaymentUrl(1,'invoice')."</strong><br>\n";
	if (! empty($conf->global->PAYMENT_SECURITY_TOKEN) && ! empty($conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE))
	{
	    $langs->load("bills");
	    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	    print $langs->trans("EnterRefToBuildUrl",$langs->transnoentitiesnoconv("Invoice")).': ';
        print '<input type="text class="flat" id="generate_invoice_ref" name="generate_invoice_ref" value="'.GETPOST('generate_invoice_ref','alpha').'" size="10">';
        print '<input type="submit" class="none button" value="'.$langs->trans("GetSecuredUrl").'">';
        if (GETPOST('generate_invoice_ref','alpha'))
        {
            print '<br> -> <strong>';
            $url=getOnlinePaymentUrl(0,'invoice',GETPOST('generate_invoice_ref','alpha'));
            print $url;
            print "</strong><br>\n";
        }
        print '</form>';
	}
	print '<br>';
}
if (! empty($conf->contrat->enabled))
{
	print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnContractLine",$servicename).':<br>';
	print '<strong>'.getOnlinePaymentUrl(1,'contractline')."</strong><br>\n";
	if (! empty($conf->global->PAYMENT_SECURITY_TOKEN) && ! empty($conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE))
	{
	    $langs->load("contracts");
	    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	    print $langs->trans("EnterRefToBuildUrl",$langs->transnoentitiesnoconv("ContractLine")).': ';
        print '<input type="text class="flat" id="generate_contract_ref" name="generate_contract_ref" value="'.GETPOST('generate_contract_ref','alpha').'" size="10">';
        print '<input type="submit" class="none button" value="'.$langs->trans("GetSecuredUrl").'">';
        if (GETPOST('generate_contract_ref'))
        {
            print '<br> -> <strong>';
            $url=getOnlinePaymentUrl(0,'contractline',GETPOST('generate_contract_ref','alpha'));
            print $url;
            print "</strong><br>\n";
        }
        print '</form>';
	}
	print '<br>';
}
if (! empty($conf->adherent->enabled))
{
	print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnMemberSubscription",$servicename).':<br>';
	print '<strong>'.getOnlinePaymentUrl(1,'membersubscription')."</strong><br>\n";
	if (! empty($conf->global->PAYMENT_SECURITY_TOKEN) && ! empty($conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE))
	{
	    $langs->load("members");
	    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	    print $langs->trans("EnterRefToBuildUrl",$langs->transnoentitiesnoconv("Member")).': ';
        print '<input type="text class="flat" id="generate_member_ref" name="generate_member_ref" value="'.GETPOST('generate_member_ref','alpha').'" size="10">';
        print '<input type="submit" class="none reposition button" value="'.$langs->trans("GetSecuredUrl").'">';
        if (GETPOST('generate_member_ref'))
        {
            print '<br> -> <strong>';
            $url=getOnlinePaymentUrl(0,'membersubscription',GETPOST('generate_member_ref','alpha'));
            print $url;
            print "</strong><br>\n";
        }
        print '</form>';
	}
	print '<br>';
}
if (! empty($conf->don->enabled))
{
	print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnDonation",$servicename).':<br>';
	print '<strong>'.getOnlinePaymentUrl(1,'donation')."</strong><br>\n";
	if (! empty($conf->global->PAYMENT_SECURITY_TOKEN) && ! empty($conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE))
	{
	    $langs->load("members");
	    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	    print $langs->trans("EnterRefToBuildUrl",$langs->transnoentitiesnoconv("Don")).': ';
        print '<input type="text class="flat" id="generate_donation_ref" name="generate_donation_ref" value="'.GETPOST('generate_donation_ref','alpha').'" size="10">';
        print '<input type="submit" class="none reposition button" value="'.$langs->trans("GetSecuredUrl").'">';
        if (GETPOST('generate_donation_ref'))
        {
            print '<br> -> <strong>';
            $url=getOnlinePaymentUrl(0,'donation',GETPOST('generate_donation_ref','alpha'));
            print $url;
            print "</strong><br>\n";
        }
        print '</form>';
	}
	print '<br>';
}

if (! empty($conf->use_javascript_ajax))
{
	print "\n".'<script type="text/javascript">';
	print '$(document).ready(function () {
		$("#generate_token").click(function() {
            	$.get( "'.DOL_URL_ROOT.'/core/ajax/security.php", {
            		action: \'getrandompassword\',
            		generic: true
				},
				function(token) {
					$("#PAYMENT_SECURITY_TOKEN").val(token);
				});
            });
    	});';
	print '</script>';
}

print info_admin($langs->trans("YouCanAddTagOnUrl"));

print '<!-- END PHP TEMPLATE ONLINEPAYMENTLINKS -->';


