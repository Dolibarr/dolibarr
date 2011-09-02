<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.org>
 * Copyright (C) 2011      Regis Houssin        <regis@dolibarr.fr>
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

/**	    \file       htdocs/paypal/admin/paypal.php
 *		\ingroup    paypal
 *		\brief      Page to setup paypal module
 *		\version    $Id: paypal.php,v 1.23 2011/07/31 23:24:25 eldy Exp $
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/paypal/lib/paypal.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/security.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");

$servicename='PayPal';

$langs->load("admin");
$langs->load("other");
$langs->load("paypal");
$langs->load("paybox");

if (!$user->admin)
  accessforbidden();


if ($_POST["action"] == 'setvalue' && $user->admin)
{
    $result=dolibarr_set_const($db, "PAYPAL_API_SANDBOX",$_POST["PAYPAL_API_SANDBOX"],'chaine',0,'',$conf->entity);
    $result=dolibarr_set_const($db, "PAYPAL_API_USER",$_POST["PAYPAL_API_USER"],'chaine',0,'',$conf->entity);
    $result=dolibarr_set_const($db, "PAYPAL_API_PASSWORD",$_POST["PAYPAL_API_PASSWORD"],'chaine',0,'',$conf->entity);
    $result=dolibarr_set_const($db, "PAYPAL_API_SIGNATURE",$_POST["PAYPAL_API_SIGNATURE"],'chaine',0,'',$conf->entity);

    $result=dolibarr_set_const($db, "PAYPAL_CREDITOR",$_POST["PAYPAL_CREDITOR"],'chaine',0,'',$conf->entity);
    $result=dolibarr_set_const($db, "PAYPAL_API_INTEGRAL_OR_PAYPALONLY",$_POST["PAYPAL_API_INTEGRAL_OR_PAYPALONLY"],'chaine',0,'',$conf->entity);
    $result=dolibarr_set_const($db, "PAYPAL_CSS_URL",$_POST["PAYPAL_CSS_URL"],'chaine',0,'',$conf->entity);
    $result=dolibarr_set_const($db, "PAYPAL_SECURITY_TOKEN",$_POST["PAYPAL_SECURITY_TOKEN"],'chaine',0,'',$conf->entity);
    $result=dolibarr_set_const($db, "PAYPAL_ADD_PAYMENT_URL",$_POST["PAYPAL_ADD_PAYMENT_URL"],'chaine',0,'',$conf->entity);
    $result=dolibarr_set_const($db, "PAYPAL_MESSAGE_OK",$_POST["PAYPAL_MESSAGE_OK"],'chaine',0,'',$conf->entity);
    $result=dolibarr_set_const($db, "PAYPAL_MESSAGE_KO",$_POST["PAYPAL_MESSAGE_KO"],'chaine',0,'',$conf->entity);

	if ($result >= 0)
  	{
  		$mesg='<div class="ok">'.$langs->trans("SetupSaved").'</div>';
  	}
  	else
  	{
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
print '<br />';

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
            	$.get( "'.DOL_URL_ROOT.'/core/ajaxsecurity.php", {
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

if ($mesg) print '<br>'.$mesg;

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
print $langs->trans("SecurityToken").'</td><td>';
print '<input size="48" type="text" id="PAYPAL_SECURITY_TOKEN" name="PAYPAL_SECURITY_TOKEN" value="'.$conf->global->PAYPAL_SECURITY_TOKEN.'">';
print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_token" class="linkobject"');
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

print '<tr><td colspan="2" align="center"><br><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td></tr>';
print '</table></form>';

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
if (! empty($conf->global->PAYPAL_SECURITY_TOKEN)) $token='&securekey='.$conf->global->PAYPAL_SECURITY_TOKEN;

// Url list
print '<u>'.$langs->trans("FollowingUrlAreAvailableToMakePayments").':</u><br>';
print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnFreeAmount",$servicename).':<br>';
print '<strong>'.DOL_MAIN_URL_ROOT.'/public/paypal/newpayment.php?amount=<em>9.99</em>&tag=<em>your_free_tag'.$token.'</em></strong>'."<br>\n";
if ($conf->commande->enabled)
{
	print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnOrder",$servicename).':<br>';
	print '<strong>'.DOL_MAIN_URL_ROOT.'/public/paypal/newpayment.php?source=order&ref=<em>order_ref'.$token.'</em></strong>'."<br>\n";
}
if ($conf->facture->enabled)
{
	print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnInvoice",$servicename).':<br>';
	print '<strong>'.DOL_MAIN_URL_ROOT.'/public/paypal/newpayment.php?source=invoice&ref=<em>invoice_ref'.$token.'</em></strong>'."<br>\n";
//	print $langs->trans("SetupPaypalToHavePaymentCreatedAutomatically",$langs->transnoentitiesnoconv("FeatureNotYetAvailable"))."<br>\n";
}
if ($conf->contrat->enabled)
{
	print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnContractLine",$servicename).':<br>';
	print '<strong>'.DOL_MAIN_URL_ROOT.'/public/paypal/newpayment.php?source=contractline&ref=<em>contractline_ref'.$token.'</em></strong>'."<br>\n";
}
if ($conf->adherent->enabled)
{
	print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnMemberSubscription",$servicename).':<br>';
	print '<strong>'.DOL_MAIN_URL_ROOT.'/public/paypal/newpayment.php?source=membersubscription&ref=<em>member_ref'.$token.'</em></strong>'."<br>\n";
}

print "<br>";
print info_admin($langs->trans("YouCanAddTagOnUrl"));

$db->close();

llxFooter('$Date: 2011/07/31 23:24:25 $ - $Revision: 1.23 $');
?>
