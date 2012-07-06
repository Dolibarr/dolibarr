<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2011	Laurent Destailleur		<eldy@users.sourceforge.org>
 * Copyright (C) 2011-2012	Regis Houssin			<regis@dolibarr.fr>
 * Copyright (C) 2011-2012  Juanjo Menent			<jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * \file       htdocs/paypal/admin/paypal.php
 * \ingroup    paypal
 * \brief      Page to setup paypal module
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/paypal/lib/paypal.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");

$servicename='PayPal';

$langs->load("admin");
$langs->load("other");
$langs->load("paypal");
$langs->load("paybox");

if (! $user->admin) accessforbidden();

$action = GETPOST('action','alpha');

if ($action == 'setvalue' && $user->admin)
{
	$db->begin();
    $result=dolibarr_set_const($db, "PAYPAL_API_SANDBOX",GETPOST('PAYPAL_API_SANDBOX','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "PAYPAL_API_USER",GETPOST('PAYPAL_API_USER','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "PAYPAL_API_PASSWORD",GETPOST('PAYPAL_API_PASSWORD','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "PAYPAL_API_SIGNATURE",GETPOST('PAYPAL_API_SIGNATURE','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "PAYPAL_CREDITOR",GETPOST('PAYPAL_CREDITOR','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "PAYPAL_API_INTEGRAL_OR_PAYPALONLY",GETPOST('PAYPAL_API_INTEGRAL_OR_PAYPALONLY','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "PAYPAL_CSS_URL",GETPOST('PAYPAL_CSS_URL','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "PAYPAL_SECURITY_TOKEN",GETPOST('PAYPAL_SECURITY_TOKEN','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "PAYPAL_SECURITY_TOKEN_UNIQUE",GETPOST('PAYPAL_SECURITY_TOKEN_UNIQUE','alpha'),'chaine',0,'',$conf->entity);
	if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "PAYPAL_ADD_PAYMENT_URL",GETPOST('PAYPAL_ADD_PAYMENT_URL','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "PAYPAL_MESSAGE_OK",GETPOST('PAYPAL_MESSAGE_OK','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "PAYPAL_MESSAGE_KO",GETPOST('PAYPAL_MESSAGE_KO','alpha'),'chaine',0,'',$conf->entity);
	if (! $result > 0) $error++;

	if (! $error)
  	{
  		$db->commit();
  		$mesg='<div class="ok">'.$langs->trans("SetupSaved").'</div>';
  	}
  	else
  	{
  		$db->rollback();
		dol_print_error($db);
    }
}


/*
 *	View
 */

$form=new Form($db);

llxHeader('',$langs->trans("PaypalSetup"));


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre(' - '.$langs->trans("ModuleSetup"),$linkback,'paypal_logo@paypal');
print '<br>';

$head=paypaladmin_prepare_head();

dol_fiche_head($head, 'paypalaccount', $langs->trans("ModuleSetup"));

print $langs->trans("PaypalDesc")."<br>\n";

if ($conf->use_javascript_ajax)
{
    print "\n".'<script type="text/javascript" language="javascript">';
    print '$(document).ready(function () {
            $("#apidoc").hide();
            $("#apidoca").click(function() {
                $("#apidoca").hide();
                $("#apidoc").show();
            });

            $("#generate_token").click(function() {
            	$.get( "'.DOL_URL_ROOT.'/core/ajax/security.php", {
            		action: \'getrandompassword\',
            		generic: true
				},
				function(token) {
					$("#PAYPAL_SECURITY_TOKEN").val(token);
				});
            });
    });';
    print '</script>';
}

// Test if php curl exist
if (! function_exists('curl_version'))
{
	$langs->load("errors");
	$mesg='<div class="error">'.$langs->trans("ErrorPhpCurlNotInstalled").'</div>';
}

dol_htmloutput_mesg($mesg);

print '<br>';
print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvalue">';


print '<table class="nobordernopadding" width="100%">';

$var=true;
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("AccountParameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

$var=!$var;
print '<tr '.$bc[$var].'><td class="fieldrequired">';
print $langs->trans("PAYPAL_API_SANDBOX").'</td><td>';
print $form->selectyesno("PAYPAL_API_SANDBOX",$conf->global->PAYPAL_API_SANDBOX,1);
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td class="fieldrequired">';
print $langs->trans("PAYPAL_API_USER").'</td><td>';
print '<input size="32" type="text" name="PAYPAL_API_USER" value="'.$conf->global->PAYPAL_API_USER.'">';
print ' &nbsp; '.$langs->trans("Example").': paypal_api1.mywebsite.com';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td class="fieldrequired">';
print $langs->trans("PAYPAL_API_PASSWORD").'</td><td>';
print '<input size="32" type="text" name="PAYPAL_API_PASSWORD" value="'.$conf->global->PAYPAL_API_PASSWORD.'">';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td class="fieldrequired">';
print $langs->trans("PAYPAL_API_SIGNATURE").'</td><td>';
print '<input size="64" type="text" name="PAYPAL_API_SIGNATURE" value="'.$conf->global->PAYPAL_API_SIGNATURE.'">';
print '<br>'.$langs->trans("Example").': ASsqXEmw4KzmX-CPChWSVDNCNfd.A3YNR7uz-VncXXAERFDFDFDF';
print '</td></tr>';

$var=true;
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("UsageParameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

$var=!$var;
print '<tr '.$bc[$var].'><td class="fieldrequired">';
print $langs->trans("PAYPAL_API_INTEGRAL_OR_PAYPALONLY").'</td><td>';
print $form->selectarray("PAYPAL_API_INTEGRAL_OR_PAYPALONLY",array('integral'=>'Integral','paypalonly'=>'Paypal only'),$conf->global->PAYPAL_API_INTEGRAL_OR_PAYPALONLY);
print '</td></tr>';

/*$var=!$var;
print '<tr '.$bc[$var].'><td>';
print '<span class="fieldrequired">'.$langs->trans("PAYPAL_API_EXPRESS").'</span></td><td>';
print $form->selectyesno("PAYPAL_API_EXPRESS",$conf->global->PAYPAL_API_EXPRESS);
print '</td></tr>';
*/

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("VendorName").'</td><td>';
print '<input size="64" type="text" name="PAYPAL_CREDITOR" value="'.$conf->global->PAYPAL_CREDITOR.'">';
print ' &nbsp; '.$langs->trans("Example").': '.$mysoc->name;
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("CSSUrlForPaymentForm").'</td><td>';
print '<input size="64" type="text" name="PAYPAL_CSS_URL" value="'.$conf->global->PAYPAL_CSS_URL.'">';
print ' &nbsp; '.$langs->trans("Example").': http://mysite/mycss.css';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("PAYPAL_ADD_PAYMENT_URL").'</td><td>';
print $form->selectyesno("PAYPAL_ADD_PAYMENT_URL",$conf->global->PAYPAL_ADD_PAYMENT_URL,1);
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("MessageOK").'</td><td>';
$doleditor=new DolEditor('PAYPAL_MESSAGE_OK',$conf->global->PAYPAL_MESSAGE_OK,'',100,'dolibarr_details','In',false,true,true,ROWS_4,60);
$doleditor->Create();
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("MessageKO").'</td><td>';
$doleditor=new DolEditor('PAYPAL_MESSAGE_KO',$conf->global->PAYPAL_MESSAGE_KO,'',100,'dolibarr_details','In',false,true,true,ROWS_4,60);
$doleditor->Create();
print '</td></tr>';

$var=true;
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("UrlGenerationParameters").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("SecurityToken").'</td><td>';
print '<input size="48" type="text" id="PAYPAL_SECURITY_TOKEN" name="PAYPAL_SECURITY_TOKEN" value="'.$conf->global->PAYPAL_SECURITY_TOKEN.'">';
print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_token" class="linkobject"');
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("SecurityTokenIsUnique").'</td><td>';
print $form->selectyesno("PAYPAL_SECURITY_TOKEN_UNIQUE",(empty($conf->global->PAYPAL_SECURITY_TOKEN)?0:$conf->global->PAYPAL_SECURITY_TOKEN_UNIQUE),1);
print '</td></tr>';

print '<tr><td colspan="2" align="center"><br><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td></tr>';

print '</table>';

print '</form>';

dol_fiche_end();

print '<br><br>';

// Help doc
print '<u>'.$langs->trans("InformationToFindParameters","Paypal").'</u>:<br>';
if ($conf->use_javascript_ajax) print '<a href="#" id="apidoca">'.$langs->trans("ClickHere").'...</a>';

$realpaypalurl='www.paypal.com';
$sandboxpaypalurl='developer.paypal.com';

print '<div id="apidoc">';
print 'Your API authentication information can be found with following steps. We recommend that you open a separate Web browser session when carrying out this procedure.<br>
1. Log in to your PayPal Premier or Business account (on real paypal <a href="https://'.$realpaypalurl.'" target="_blank">'.$realpaypalurl.'</a> (or sandbox <a href="https://'.$sandboxpaypalurl.'" target="_blank">'.$sandboxpaypalurl.'</a>).<br>
2. Click the Profile subtab located under the My Account heading.<br>
3. Click the API Access link under the Account Information header.<br>
4. Click the View API Certificate link in the right column.<br>
5. Click the Request API signature radio button on the Request API Credentials page.<br>
6. Complete the Request API Credential Request form by clicking the agreement checkbox and clicking Submit.<br>
7. Save the values for API Username, Password and Signature (make sure this long character signature is copied).<br>
8. Click the "Modify" button after copying your API Username, Password, and Signature.
';
print '</div>';

print '<br><br>';

$token='';


// Url list
print '<u>'.$langs->trans("FollowingUrlAreAvailableToMakePayments").':</u><br>';
print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnFreeAmount",$servicename).':<br>';
print '<strong>'.getPaypalPaymentUrl(1,'free')."</strong><br><br>\n";
if ($conf->commande->enabled)
{
	print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnOrder",$servicename).':<br>';
	print '<strong>'.getPaypalPaymentUrl(1,'order')."</strong><br>\n";
	if (! empty($conf->global->PAYPAL_SECURITY_TOKEN) && ! empty($conf->global->PAYPAL_SECURITY_TOKEN_UNIQUE))
	{
	    $langs->load("orders");
	    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	    print $langs->trans("EnterRefToBuildUrl",$langs->transnoentitiesnoconv("Order")).': ';
        print '<input type="text class="flat" id="generate_order_ref" name="generate_order_ref" value="'.GETPOST('generate_order_ref','alpha').'" size="10">';
        print '<input type="submit" class="none" value="'.$langs->trans("GetSecuredUrl").'">';
        if (GETPOST('generate_order_ref','alpha'))
        {
            print '<br> -> <strong>';
            $url=getPaypalPaymentUrl(0,'order',GETPOST('generate_order_ref','alpha'));
            print $url;
            print "</strong><br>\n";
        }
        print '</form>';
	}
	print '<br>';
}
if ($conf->facture->enabled)
{
	print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnInvoice",$servicename).':<br>';
	print '<strong>'.getPaypalPaymentUrl(1,'invoice')."</strong><br>\n";
	if (! empty($conf->global->PAYPAL_SECURITY_TOKEN) && ! empty($conf->global->PAYPAL_SECURITY_TOKEN_UNIQUE))
	{
	    $langs->load("bills");
	    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	    print $langs->trans("EnterRefToBuildUrl",$langs->transnoentitiesnoconv("Invoice")).': ';
        print '<input type="text class="flat" id="generate_invoice_ref" name="generate_invoice_ref" value="'.GETPOST('generate_invoice_ref','alpha').'" size="10">';
        print '<input type="submit" class="none" value="'.$langs->trans("GetSecuredUrl").'">';
        if (GETPOST('generate_invoice_ref','alpha'))
        {
            print '<br> -> <strong>';
            $url=getPaypalPaymentUrl(0,'invoice',GETPOST('generate_invoice_ref','alpha'));
            print $url;
            print "</strong><br>\n";
        }
        print '</form>';
	}
	print '<br>';
}
if ($conf->contrat->enabled)
{
	print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnContractLine",$servicename).':<br>';
	print '<strong>'.getPaypalPaymentUrl(1,'contractline')."</strong><br>\n";
	if (! empty($conf->global->PAYPAL_SECURITY_TOKEN) && ! empty($conf->global->PAYPAL_SECURITY_TOKEN_UNIQUE))
	{
	    $langs->load("contract");
	    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	    print $langs->trans("EnterRefToBuildUrl",$langs->transnoentitiesnoconv("Contract")).': ';
        print '<input type="text class="flat" id="generate_contract_ref" name="generate_contract_ref" value="'.GETPOST('generate_contract_ref','alpha').'" size="10">';
        print '<input type="submit" class="none" value="'.$langs->trans("GetSecuredUrl").'">';
        if (GETPOST('generate_contract_ref'))
        {
            print '<br> -> <strong>';
            $url=getPaypalPaymentUrl(0,'contractline',GETPOST('generate_contract_ref','alpha'));
            print $url;
            print "</strong><br>\n";
        }
        print '</form>';
	}
	print '<br>';
}
if ($conf->adherent->enabled)
{
	print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnMemberSubscription",$servicename).':<br>';
	print '<strong>'.getPaypalPaymentUrl(1,'membersubscription')."</strong><br>\n";
	if (! empty($conf->global->PAYPAL_SECURITY_TOKEN) && ! empty($conf->global->PAYPAL_SECURITY_TOKEN_UNIQUE))
	{
	    $langs->load("members");
	    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	    print $langs->trans("EnterRefToBuildUrl",$langs->transnoentitiesnoconv("Member")).': ';
        print '<input type="text class="flat" id="generate_member_ref" name="generate_member_ref" value="'.GETPOST('generate_member_ref','alpha').'" size="10">';
        print '<input type="submit" class="none" value="'.$langs->trans("GetSecuredUrl").'">';
        if (GETPOST('generate_member_ref'))
        {
            print '<br> -> <strong>';
            $url=getPaypalPaymentUrl(0,'membersubscription',GETPOST('generate_member_ref','alpha'));
            print $url;
            print "</strong><br>\n";
        }
        print '</form>';
	}
}

print "<br>";
print info_admin($langs->trans("YouCanAddTagOnUrl"));

llxFooter();

$db->close();
?>
