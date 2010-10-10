<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.org>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**	    \file       htdocs/paypal/admin/paypal.php
 *		\ingroup    paypal
 *		\brief      Page to setup paypal module
 *		\version    $Id$
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

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

    $result=dolibarr_set_const($db, "PAYPAL_API_INTEGRAL_OR_PAYPALONLY",$_POST["PAYPAL_API_INTEGRAL_OR_PAYPALONLY"],'chaine',0,'',$conf->entity);
    $result=dolibarr_set_const($db, "PAYPAL_CSS_URL",$_POST["PAYPAL_CSS_URL"],'chaine',0,'',$conf->entity);

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

llxHeader();


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("PaypalSetup"),$linkback,'setup');

print $langs->trans("PaypalDesc")."<br>\n";

if ($conf->use_javascript_ajax)
{
    print "\n".'<script type="text/javascript" language="javascript">';
    print 'jQuery(document).ready(function () {
            jQuery("#apidoc").hide();
            jQuery("#apidoca").click(function() {
                jQuery("#apidoca").hide();
                jQuery("#apidoc").show();
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
print '<tr '.$bc[$var].'><td>';
print $langs->trans("PAYPAL_API_SANDBOX").'</td><td>';
print $form->selectyesno("PAYPAL_API_SANDBOX",$conf->global->PAYPAL_API_SANDBOX);
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("PAYPAL_API_USER").'</td><td>';
print '<input size="32" type="text" name="PAYPAL_API_USER" value="'.$conf->global->PAYPAL_API_USER.'">';
print ' &nbsp; '.$langs->trans("Example").': paypal_api1.mywebsite.com';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("PAYPAL_API_PASSWORD").'</td><td>';
print '<input size="32" type="text" name="PAYPAL_API_PASSWORD" value="'.$conf->global->PAYPAL_API_PASSWORD.'">';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
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
print '<tr '.$bc[$var].'><td>';
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
print $langs->trans("PAYPAL_CSS_URL").'</td><td>';
print '<input size="64" type="text" name="PAYPAL_CSS_URL" value="'.$conf->global->PAYPAL_CSS_URL.'">';
print '<br>'.$langs->trans("Example").': http://mysite/mycss.css';
print '</td></tr>';


print '<tr><td colspan="2" align="center"><br><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td></tr>';
print '</table></form>';

print '<br><br>';

// Help doc
print '<u>'.$langs->trans("InformationToFindParameters","Paypal").'</u>:<br>';
if ($conf->use_javascript_ajax) print '<a href="#" id="apidoca">'.$langs->trans("ClickHere").'...</a>';

print '<div id="apidoc">';
print 'Your API authentication information can be found with following steps. We recommend that you open a separate Web browser session when carrying out this procedure.<br>
1. Log in to your PayPal Premier or Business account.<br>
2. Click the Profile subtab located under the My Account heading.<br>
3. Click the API Access link under the Account Information header.<br>
4. Click the View API Certificate link in the right column.<br>
5. Click the Request API signature radio button on the Request API Credentials page.<br>
6. Complete the Request API Credential Request form by clicking the agreement checkbox and clicking Submit.<br>
7. Save the values for API Username, Password and Signature (make sure this long character signature is copied).<br>
8. Click the "Modify" button after copying your API Username, Password, and Signature.
';
print '</div>';


print '<br><br><br>';

// Url list
print '<u>'.$langs->trans("FollowingUrlAreAvailableToMakePayments").':</u><br>';
// Should work with DOL_URL_ROOT='' or DOL_URL_ROOT='/dolibarr'
$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',$dolibarr_main_url_root);
print '<br>';
print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnFreeAmount",$servicename).':<br>';
print '<b>'.$urlwithouturlroot.DOL_URL_ROOT.'/public/paypal/newpayment.php?amount=<i>9.99</i>&tag=<i>your_free_tag</i></b>'."<br>\n";
print '<br>';
if ($conf->commande->enabled)
{
	print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnOrder",$servicename).':<br>';
	print '<b>'.$urlwithouturlroot.DOL_URL_ROOT.'/public/paypal/newpayment.php?source=order&ref=<i>order_ref</i></b>'."<br>\n";
	print '<br>';
}
if ($conf->facture->enabled)
{
	print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnInvoice",$servicename).':<br>';
	print '<b>'.$urlwithouturlroot.DOL_URL_ROOT.'/public/paypal/newpayment.php?source=invoice&ref=<i>invoice_ref</i></b>'."<br>\n";
//	print $langs->trans("SetupPaypalToHavePaymentCreatedAutomatically",$langs->transnoentitiesnoconv("FeatureNotYetAvailable"))."<br>\n";
	print '<br>';
}
if ($conf->contrat->enabled)
{
	print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnContractLine",$servicename).':<br>';
	print '<b>'.$urlwithouturlroot.DOL_URL_ROOT.'/public/paypal/newpayment.php?source=contractline&ref=<i>contractline_ref</i></b>'."<br>\n";
	print '<br>';
}
if ($conf->adherent->enabled)
{
	print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnMemberSubscription",$servicename).':<br>';
	print '<b>'.$urlwithouturlroot.DOL_URL_ROOT.'/public/paypal/newpayment.php?source=membersubscription&ref=<i>member_ref</i></b>'."<br>\n";
	print '<br>';
}
print $langs->trans("YouCanAddTagOnUrl");

$db->close();

llxFooter('$Date$ - $Revision$');
?>
