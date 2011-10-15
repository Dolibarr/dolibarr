<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2011 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *e
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *     	\file       htdocs/public/paypal/newpayment.php
 *		\ingroup    paypal
 *		\brief      File to offer a way to make a payment for a particular Dolibarr entity
 *		\author	    Laurent Destailleur
 *		\version    $Id: newpayment.php,v 1.26 2011/07/31 23:23:20 eldy Exp $
 */

define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/paypal/lib/paypal.lib.php");
require_once(DOL_DOCUMENT_ROOT."/paypal/lib/paypalfunctions.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/security.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");

// Security check
if (empty($conf->paypal->enabled)) accessforbidden('',1,1,1);

$langs->load("main");
$langs->load("other");
$langs->load("dict");
$langs->load("bills");
$langs->load("companies");
$langs->load("errors");
$langs->load("paybox");
$langs->load("paypal");

// Input are:
// type ('invoice','order','contractline'),
// id (object id),
// amount (required if id is empty),
// tag (a free text, required if type is empty)
// currency (iso code)

$suffix=GETPOST("suffix",'alpha');
$amount=price2num(GETPOST("amount"));
if (! GETPOST("currency",'alpha')) $currency=$conf->global->MAIN_MONNAIE;
else $currency=GETPOST("currency",'alpha');

if (! GETPOST("action"))
{
    if (! GETPOST("amount") && ! GETPOST("source"))
    {
    	dol_print_error('',$langs->trans('ErrorBadParameters')." - amount or source");
    	exit;
    }
    if (is_numeric($amount) && ! GETPOST("tag") && ! GETPOST("source"))
    {
    	dol_print_error('',$langs->trans('ErrorBadParameters')." - tag or source");
    	exit;
    }
    if (GETPOST("source") && ! GETPOST("ref"))
    {
    	dol_print_error('',$langs->trans('ErrorBadParameters')." - ref");
    	exit;
    }
}

$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',$dolibarr_main_url_root);
$urlok=$urlwithouturlroot.DOL_URL_ROOT.'/htdocs/public/paypal/paymentok.php?';
$urlko=$urlwithouturlroot.DOL_URL_ROOT.'/htdocs/public/paypal/paymentko.php?';

// Complete urls for post treatment
$SOURCE=GETPOST("source",'alpha');
$ref=$REF=GETPOST('ref','alpha');
$TAG=GETPOST("tag",'alpha');
$FULLTAG=GETPOST("fulltag",'alpha');		// fulltag is tag with more informations
$SECUREKEY=GETPOST("securekey",'alpha');	// Secure key

if (! empty($SOURCE))
{
    $urlok.='source='.urlencode($SOURCE).'&';
    $urlko.='source='.urlencode($SOURCE).'&';
}
if (! empty($REF))
{
    $urlok.='ref='.urlencode($REF).'&';
    $urlko.='ref='.urlencode($REF).'&';
}
if (! empty($TAG))
{
    $urlok.='tag='.urlencode($TAG).'&';
    $urlko.='tag='.urlencode($TAG).'&';
}
if (! empty($FULLTAG))
{
    $urlok.='fulltag='.urlencode($FULLTAG).'&';
    $urlko.='fulltag='.urlencode($FULLTAG).'&';
}
if (! empty($SECUREKEY))
{
    $urlok.='securekey='.urlencode($SECUREKEY).'&';
    $urlko.='securekey='.urlencode($SECUREKEY).'&';
}
$urlok=preg_replace('/&$/','',$urlok);  // Remove last &
$urlko=preg_replace('/&$/','',$urlko);  // Remove last &

// Clean parameters
$PAYPAL_API_OK="";
if ($urlok) $PAYPAL_API_OK=$urlok;
$PAYPAL_API_KO="";
if ($urlko) $PAYPAL_API_KO=$urlko;
if (empty($PAYPAL_API_USER))
{
    dol_print_error('',"Paypal setup param PAYPAL_API_USER not defined");
    return -1;
}
if (empty($PAYPAL_API_PASSWORD))
{
    dol_print_error('',"Paypal setup param PAYPAL_API_PASSWORD not defined");
    return -1;
}
if (empty($PAYPAL_API_SIGNATURE))
{
    dol_print_error('',"Paypal setup param PAYPAL_API_SIGNATURE not defined");
    return -1;
}


/*
 * Actions
 */
if (GETPOST("action") == 'dopayment')
{
	$PAYPAL_API_PRICE=price2num(GETPOST("newamount"),'MT');
	//$EMAIL=GETPOST("EMAIL");
    $PAYPAL_PAYMENT_TYPE='Sale';

    $shipToName=GETPOST("shipToName");
    $shipToStreet=GETPOST("shipToStreet");
    $shipToCity=GETPOST("shipToCity");
    $shipToState=GETPOST("shipToState");
    $shipToCountryCode=GETPOST("shipToCountryCode");
    $shipToZip=GETPOST("shipToZip");
    $shipToStreet2=GETPOST("shipToStreet2");
    $phoneNum=GETPOST("phoneNum");

	$mesg='';
	if (empty($PAYPAL_API_PRICE) || ! is_numeric($PAYPAL_API_PRICE))   $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Amount"));
	//elseif (empty($EMAIL))          $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("YourEMail"));
	//elseif (! isValidEMail($EMAIL)) $mesg=$langs->trans("ErrorBadEMail",$EMAIL);
	elseif (empty($FULLTAG))        $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("PaymentCode"));

    //var_dump($_POST);
	if (empty($mesg))
	{
		dol_syslog("newpayment.php call paypal api and do redirect", LOG_DEBUG);

		// Other
		$PAYPAL_API_DEVISE="EUR";
		if ($CURRENCY == 'EUR') $PAYPAL_API_DEVISE="EUR";
		if ($CURRENCY == 'USD') $PAYPAL_API_DEVISE="USD";

	    dol_syslog("Submit Paypal form", LOG_DEBUG);
	    dol_syslog("PAYPAL_API_USER: $PAYPAL_API_USER", LOG_DEBUG);
	    //dol_syslog("PAYPAL_API_PASSWORD: $PAYPAL_API_PASSWORD", LOG_DEBUG);  // No password into log files
	    dol_syslog("PAYPAL_API_SIGNATURE: $PAYPAL_API_SIGNATURE", LOG_DEBUG);
	    dol_syslog("PAYPAL_API_SANDBOX: $PAYPAL_API_SANDBOX", LOG_DEBUG);
	    dol_syslog("PAYPAL_API_OK: $PAYPAL_API_OK", LOG_DEBUG);
	    dol_syslog("PAYPAL_API_KO: $PAYPAL_API_KO", LOG_DEBUG);
	    dol_syslog("PAYPAL_API_PRICE: $PAYPAL_API_PRICE", LOG_DEBUG);
	    dol_syslog("PAYPAL_API_DEVISE: $PAYPAL_API_DEVISE", LOG_DEBUG);
        dol_syslog("shipToName: $shipToName", LOG_DEBUG);
        dol_syslog("shipToStreet: $shipToStreet", LOG_DEBUG);
        dol_syslog("shipToCity: $shipToCity", LOG_DEBUG);
        dol_syslog("shipToState: $shipToState", LOG_DEBUG);
        dol_syslog("shipToCountryCode: $shipToCountryCode", LOG_DEBUG);
        dol_syslog("shipToZip: $shipToZip", LOG_DEBUG);
        dol_syslog("shipToStreet2: $shipToStreet2", LOG_DEBUG);
        dol_syslog("phoneNum: $phoneNum", LOG_DEBUG);

	    /*header("Content-type: text/html; charset=".$conf->file->character_set_client);

	    print '<html>'."\n";
	    print '<head>'."\n";
	    print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$conf->file->character_set_client."\">\n";
	    print '</head>'."\n";
	    print '<body>'."\n";
	    print "\n";
		*/

	    $_SESSION["Payment_Amount"]=$PAYPAL_API_PRICE;

	    // A redirect is added if API call successfull
        print_paypal_redirect($PAYPAL_API_PRICE,$PAYPAL_API_DEVISE,$PAYPAL_PAYMENT_TYPE,$PAYPAL_API_OK,$PAYPAL_API_KO, $FULLTAG);

	    /*print '</body></html>'."\n";
	    print "\n";
		*/

		exit;
	}
}



/*
 * View
 */

llxHeaderPaypal($langs->trans("PaymentForm"));


// Common variables
$creditor=$mysoc->name;
$paramcreditor='PAYPAL_CREDITOR_'.$suffix;
if (! empty($conf->global->$paramcreditor)) $creditor=$conf->global->$paramcreditor;
else if (! empty($conf->global->PAYPAL_CREDITOR)) $creditor=$conf->global->PAYPAL_CREDITOR;

print '<span id="dolpaymentspan"></span>'."\n";
print '<center>'."\n";
print '<form id="dolpaymentform" name="paymentform" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
print '<input type="hidden" name="action" value="dopayment">'."\n";
print '<input type="hidden" name="amount" value="'.GETPOST("amount",'int').'">'."\n";
print '<input type="hidden" name="tag" value="'.GETPOST("tag",'alpha').'">'."\n";
print '<input type="hidden" name="suffix" value="'.GETPOST("suffix",'alpha').'">'."\n";
print "\n";
print '<!-- Form to send a Paypal payment -->'."\n";
print '<!-- PAYPAL_API_SANDBOX = '.$conf->global->PAYPAL_API_SANDBOX.' -->'."\n";
print '<!-- PAYPAL_API_INTEGRAL_OR_PAYPALONLY = '.$conf->global->PAYPAL_API_INTEGRAL_OR_PAYPALONLY.' -->'."\n";
print '<!-- creditor = '.$creditor.' -->'."\n";
print '<!-- urlok = '.$urlok.' -->'."\n";
print '<!-- urlko = '.$urlko.' -->'."\n";
print "\n";

print '<table id="dolpaymenttable" style="font-size:14px;" summary="Payment form" width="80%">'."\n";

// Show logo (search order: logo defined by PAYBOX_LOGO_suffix, then PAYBOX_LOGO, then small company logo, large company logo, theme logo, common logo)
$width=0;
// Define logo and logosmall
$logosmall=$mysoc->logo_small;
$logo=$mysoc->logo;
$paramlogo='PAYBOX_LOGO_'.$suffix;
if (! empty($conf->global->$paramlogo)) $logosmall=$conf->global->$paramlogo;
else if (! empty($conf->global->PAYBOX_LOGO)) $logosmall=$conf->global->PAYBOX_LOGO;
//print '<!-- Show logo (logosmall='.$logosmall.' logo='.$logo.') -->'."\n";
// Define urllogo
$urllogo='';
if (! empty($logosmall) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$logosmall))
{
	$urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode('thumbs/'.$logosmall);
}
elseif (! empty($logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$logo))
{
	$urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode($logo);
	$width=96;
}
// Output html code for logo
if ($urllogo)
{
	print '<tr>';
	print '<td align="center"><img id="dolpaymentlogo" title="'.$title.'" src="'.$urllogo.'"';
	if ($width) print ' width="'.$width.'"';
	print '></td>';
	print '</tr>'."\n";
}

// Output introduction text
$text='';
if (! empty($conf->global->PAYPAL_NEWFORM_TEXT))
{
    $langs->load("members");
    if (preg_match('/^\((.*)\)$/',$conf->global->PAYPAL_NEWFORM_TEXT,$reg)) $text.=$langs->trans($reg[1])."<br>\n";
    else $text.=$conf->global->PAYPAL_NEWFORM_TEXT."<br>\n";
    $text='<tr><td align="center"><br>'.$text.'<br></td></tr>'."\n";
}
if (empty($text))
{
    $text.='<tr><td align="center"><br>'.$langs->trans("WelcomeOnPaymentPage").'<br></td></tr>'."\n";
    $text.='<tr><td align="center"><br>'.$langs->trans("ThisScreenAllowsYouToPay",$creditor).'<br><br></td></tr>'."\n";
}
print $text;

// Output payment summary form
print '<tr><td align="center">';
print '<table with="100%">';
print '<tr class="liste_total"><td align="left" colspan="2">'.$langs->trans("ThisIsInformationOnPayment").' :</td></tr>'."\n";

$found=false;
$error=0;
$var=false;

// Check security token
$valid=true;
if (! empty($conf->global->PAYPAL_SECURITY_TOKEN) )
{
	$token = $conf->global->PAYPAL_SECURITY_TOKEN;
	if ($SECUREKEY != $token and $SECUREKEY != dol_hash($conf->global->PAYPAL_SECURITY_TOKEN.GETPOST("source").GETPOST("ref"), 2)) $valid=false;
}

// Free payment
if (! GETPOST("source"))
{
	$found=true;
	$tag=GETPOST("tag");
	$fulltag=$tag;

	// Creditor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Creditor");
    print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$creditor.'</b>';
    print '<input type="hidden" name="creditor" value="'.$creditor.'">';
    print '</td></tr>'."\n";

	// Amount
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Amount");
	if (empty($amount)) print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'">';
	if (empty($amount) || ! is_numeric($amount)) print '<input class="flat" size=8 type="text" name="newamount" value="'.GETPOST("newamount",'int').'">';
	else {
		print '<b>'.price($amount).'</b>';
        print '<input type="hidden" name="amount" value="'.$amount.'">';
		print '<input type="hidden" name="newamount" value="'.$amount.'">';
	}
	// Currency
	print ' <b>'.$langs->trans("Currency".$currency).'</b>';
	print '<input type="hidden" name="currency" value="'.$currency.'">';
	print '</td></tr>'."\n";

	// Tag
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$fulltag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="fulltag" value="'.$fulltag.'">';
	print '</td></tr>'."\n";

	// EMail
	/*$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("YourEMail");
	print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'"><input class="flat" type="text" name="EMAIL" size="48" value="'.GETPOST("EMAIL").'"></td></tr>'."\n";
    */

    // We do not add fields shipToName, shipToStreet, shipToCity, shipToState, shipToCountryCode, shipToZip, shipToStreet2, phoneNum
    // as they don't exists (buyer is unknown, tag is free).
}


// Payment on customer order
if (GETPOST("source") == 'order' && $valid)
{
	$found=true;
	$langs->load("orders");

	require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");

	$order=new Commande($db);
	$result=$order->fetch('',$ref);
	if ($result < 0)
	{
		$mesg=$order->error;
		$error++;
	}
	else
	{
		$result=$order->fetch_thirdparty($order->socid);
	}

	$amount=$order->total_ttc;
    if (GETPOST("amount",'int')) $amount=GETPOST("amount",'int');
    $amount=price2num($amount);

	$fulltag='ORD='.$order->ref.'.CUS='.$order->client->id;
	//$fulltag.='.NAM='.strtr($order->client->nom,"-"," ");
	if (! empty($TAG)) { $tag=$TAG; $fulltag.='.TAG='.$TAG; }
	$fulltag=dol_string_unaccent($fulltag);

	// Creditor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Creditor");
    print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$creditor.'</b>';
    print '<input type="hidden" name="creditor" value="'.$creditor.'">';
    print '</td></tr>'."\n";

	// Debitor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("ThirdParty");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$order->client->nom.'</b>';

	// Object
	$var=!$var;
	$text='<b>'.$langs->trans("PaymentOrderRef",$order->ref).'</b>';
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Designation");
	print '</td><td class="CTableRow'.($var?'1':'2').'">'.$text;
	print '<input type="hidden" name="source" value="'.GETPOST("source",'alpha').'">';
	print '<input type="hidden" name="ref" value="'.$order->ref.'">';
	print '</td></tr>'."\n";

	// Amount
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Amount");
	if (empty($amount)) print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'">';
	if (empty($amount) || ! is_numeric($amount)) print '<input class="flat" size=8 type="text" name="newamount" value="'.GETPOST("newamount",'int').'">';
	else {
		print '<b>'.price($amount).'</b>';
        print '<input type="hidden" name="amount" value="'.$amount.'">';
		print '<input type="hidden" name="newamount" value="'.$amount.'">';
	}
	// Currency
	print ' <b>'.$langs->trans("Currency".$currency).'</b>';
	print '<input type="hidden" name="currency" value="'.$currency.'">';
	print '</td></tr>'."\n";

	// Tag
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$fulltag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="fulltag" value="'.$fulltag.'">';
	print '</td></tr>'."\n";

	// EMail
	/*$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("YourEMail");
	print ' ('.$langs->trans("ToComplete").')';
	$email=$order->client->email;
	$email=(GETPOST("EMAIL")?GETPOST("EMAIL"):(isValidEmail($email)?$email:''));
	print '</td><td class="CTableRow'.($var?'1':'2').'"><input class="flat" type="text" name="EMAIL" size="48" value="'.$email.'"></td></tr>'."\n";
    */

	// Shipping address
	$shipToName=$order->client->nom;
    $shipToStreet=$order->client->address;
    $shipToCity=$order->client->ville;
    $shipToState=$order->client->departement_code;
    $shipToCountryCode=$order->client->pays_code;
    $shipToZip=$order->client->cp;
    $shipToStreet2='';
    $phoneNum=$order->client->tel;
    if ($shipToName && $shipToStreet && $shipToCity && $shipToCountryCode && $shipToZip)
    {
        print '<input type="hidden" name="shipToName" value="'.$shipToName.'">'."\n";
        print '<input type="hidden" name="shipToStreet" value="'.$shipToStreet.'">'."\n";
        print '<input type="hidden" name="shipToCity" value="'.$shipToCity.'">'."\n";
        print '<input type="hidden" name="shipToState" value="'.$shipToState.'">'."\n";
        print '<input type="hidden" name="shipToCountryCode" value="'.$shipToCountryCode.'">'."\n";
        print '<input type="hidden" name="shipToZip" value="'.$shipToZip.'">'."\n";
        print '<input type="hidden" name="shipToStreet2" value="'.$shipToStreet2.'">'."\n";
        print '<input type="hidden" name="phoneNum" value="'.$phoneNum.'">'."\n";
    }
    else
    {
        print '<!-- Shipping address not complete, so we don t use it -->'."\n";
    }
}


// Payment on customer invoice
if (GETPOST("source") == 'invoice' && $valid)
{
	$found=true;
	$langs->load("bills");

	require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");

	$invoice=new Facture($db);
	$result=$invoice->fetch('',$ref);
	if ($result < 0)
	{
		$mesg=$invoice->error;
		$error++;
	}
	else
	{
		$result=$invoice->fetch_thirdparty($invoice->socid);
	}

	$amount=price2num($invoice->total_ttc - $invoice->getSommePaiement());
    if (GETPOST("amount",'int')) $amount=GETPOST("amount",'int');
    $amount=price2num($amount);

	$fulltag='INV='.$invoice->ref.'.CUS='.$invoice->client->id;
	//$fulltag.='.NAM='.strtr($invoice->client->nom,"-"," ");
	if (! empty($TAG)) { $tag=$TAG; $fulltag.='.TAG='.$TAG; }
	$fulltag=dol_string_unaccent($fulltag);

	// Creditor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Creditor");
    print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$creditor.'</b>';
    print '<input type="hidden" name="creditor" value="'.$creditor.'">';
    print '</td></tr>'."\n";

	// Debitor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("ThirdParty");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$invoice->client->nom.'</b>';

	// Object
	$var=!$var;
	$text='<b>'.$langs->trans("PaymentInvoiceRef",$invoice->ref).'</b>';
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Designation");
	print '</td><td class="CTableRow'.($var?'1':'2').'">'.$text;
	print '<input type="hidden" name="source" value="'.GETPOST("source",'alpha').'">';
	print '<input type="hidden" name="ref" value="'.$invoice->ref.'">';
	print '</td></tr>'."\n";

	// Amount
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Amount");
	if (empty($amount)) print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'">';
	if (empty($amount) || ! is_numeric($amount)) print '<input class="flat" size=8 type="text" name="newamount" value="'.GETPOST("newamount",'int').'">';
	else {
		print '<b>'.price($amount).'</b>';
        print '<input type="hidden" name="amount" value="'.$amount.'">';
		print '<input type="hidden" name="newamount" value="'.$amount.'">';
	}
	// Currency
	print ' <b>'.$langs->trans("Currency".$currency).'</b>';
	print '<input type="hidden" name="currency" value="'.$currency.'">';
	print '</td></tr>'."\n";

	// Tag
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$fulltag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="fulltag" value="'.$fulltag.'">';
	print '</td></tr>'."\n";

	// EMail
	/*$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("YourEMail");
	print ' ('.$langs->trans("ToComplete").')';
    $email=$invoice->client->email;
    $email=(GETPOST("EMAIL")?GETPOST("EMAIL"):(isValidEmail($email)?$email:''));
	print '</td><td class="CTableRow'.($var?'1':'2').'"><input class="flat" type="text" name="EMAIL" size="48" value="'.$email.'"></td></tr>'."\n";
    */

    // Shipping address
    $shipToName=$invoice->client->nom;
    $shipToStreet=$invoice->client->address;
    $shipToCity=$invoice->client->ville;
    $shipToState=$invoice->client->departement_code;
    $shipToCountryCode=$invoice->client->pays_code;
    $shipToZip=$invoice->client->cp;
    $shipToStreet2='';
    $phoneNum=$invoice->client->tel;
    if ($shipToName && $shipToStreet && $shipToCity && $shipToCountryCode && $shipToZip)
    {
        print '<input type="hidden" name="shipToName" value="'.$shipToName.'">'."\n";
        print '<input type="hidden" name="shipToStreet" value="'.$shipToStreet.'">'."\n";
        print '<input type="hidden" name="shipToCity" value="'.$shipToCity.'">'."\n";
        print '<input type="hidden" name="shipToState" value="'.$shipToState.'">'."\n";
        print '<input type="hidden" name="shipToCountryCode" value="'.$shipToCountryCode.'">'."\n";
        print '<input type="hidden" name="shipToZip" value="'.$shipToZip.'">'."\n";
        print '<input type="hidden" name="shipToStreet2" value="'.$shipToStreet2.'">'."\n";
        print '<input type="hidden" name="phoneNum" value="'.$phoneNum.'">'."\n";
    }
    else
    {
        print '<!-- Shipping address not complete, so we don t use it -->'."\n";
    }
}

// Payment on contract line
if (GETPOST("source") == 'contractline' && $valid)
{
	$found=true;
	$langs->load("contracts");

	require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");

	$contractline=new ContratLigne($db);
	$result=$contractline->fetch('',$ref);
	if ($result < 0)
	{
		$mesg=$contractline->error;
		$error++;
	}
	else
	{
		if ($contractline->fk_contrat > 0)
		{
			$contract=new Contrat($db);
			$result=$contract->fetch($contractline->fk_contrat);
			if ($result > 0)
			{
				$result=$contract->fetch_thirdparty($contract->socid);
			}
			else
			{
				$mesg=$contract->error;
				$error++;
			}
		}
		else
		{
			$mesg='ErrorRecordNotFound';
			$error++;
		}
	}

	$amount=$contractline->total_ttc;
	if ($contractline->fk_product)
	{
		$product=new Product($db);
		$result=$product->fetch($contractline->fk_product);

		// We define price for product (TODO Put this in a method in product class)
		if ($conf->global->PRODUIT_MULTIPRICES)
		{
			$pu_ht = $product->multiprices[$contract->client->price_level];
			$pu_ttc = $product->multiprices_ttc[$contract->client->price_level];
			$price_base_type = $product->multiprices_base_type[$contract->client->price_level];
		}
		else
		{
			$pu_ht = $product->price;
			$pu_ttc = $product->price_ttc;
			$price_base_type = $product->price_base_type;
		}

		$amount=$pu_ttc;
		if (empty($amount))
		{
			dol_print_error('','ErrorNoPriceDefinedForThisProduct');
			exit;
		}
	}
    if (GETPOST("amount",'int')) $amount=GETPOST("amount",'int');
    $amount=price2num($amount);

	$fulltag='COL='.$contractline->ref.'.CON='.$contract->ref.'.CUS='.$contract->client->id;
	//$fulltag.='.NAM='.strtr($contract->client->nom,"-"," ");
	if (! empty($TAG)) { $tag=$TAG; $fulltag.='.TAG='.$TAG; }
	$fulltag=dol_string_unaccent($fulltag);

	$qty=1;
	if (GETPOST('qty')) $qty=GETPOST('qty');

	// Creditor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Creditor");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$creditor.'</b>';
    print '<input type="hidden" name="creditor" value="'.$creditor.'">';
	print '</td></tr>'."\n";

	// Debitor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("ThirdParty");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$contract->client->nom.'</b>';

	// Object
	$var=!$var;
	$text='<b>'.$langs->trans("PaymentRenewContractId",$contract->ref,$contractline->ref).'</b>';
	if ($contractline->fk_product)
	{
		$text.='<br>'.$product->ref.($product->libelle?' - '.$product->libelle:'');
	}
	if ($contractline->description) $text.='<br>'.dol_htmlentitiesbr($contractline->description);
	//if ($contractline->date_fin_validite) {
	//	$text.='<br>'.$langs->trans("DateEndPlanned").': ';
	//	$text.=dol_print_date($contractline->date_fin_validite);
	//}
	if ($contractline->date_fin_validite)
	{
		$text.='<br>'.$langs->trans("ExpiredSince").': '.dol_print_date($contractline->date_fin_validite);
	}

	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Designation");
	print '</td><td class="CTableRow'.($var?'1':'2').'">'.$text;
	print '<input type="hidden" name="source" value="'.GETPOST("source",'alpha').'">';
	print '<input type="hidden" name="ref" value="'.$contractline->ref.'">';
	print '</td></tr>'."\n";

	// Quantity
	$var=!$var;
	$label=$langs->trans("Quantity");
	$qty=1;
	$duration='';
	if ($contractline->fk_product)
	{
		if ($product->isservice() && $product->duration_value > 0)
		{
			$label=$langs->trans("Duration");

			// TODO Put this in a global method
			if ($product->duration_value > 1)
			{
				$dur=array("h"=>$langs->trans("Hours"),"d"=>$langs->trans("DurationDays"),"w"=>$langs->trans("DurationWeeks"),"m"=>$langs->trans("DurationMonths"),"y"=>$langs->trans("DurationYears"));
			}
			else
			{
				$dur=array("h"=>$langs->trans("Hour"),"d"=>$langs->trans("DurationDay"),"w"=>$langs->trans("DurationWeek"),"m"=>$langs->trans("DurationMonth"),"y"=>$langs->trans("DurationYear"));
			}
			$duration=$product->duration_value.' '.$dur[$product->duration_unit];
		}
	}
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$label.'</td>';
	print '<td class="CTableRow'.($var?'1':'2').'"><b>'.($duration?$duration:$qty).'</b>';
	print '<input type="hidden" name="newqty" value="'.dol_escape_htmltag($qty).'">';
	print '</b></td></tr>'."\n";

	// Amount
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Amount");
	if (empty($amount)) print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'">';
	if (empty($amount) || ! is_numeric($amount)) print '<input class="flat" size=8 type="text" name="newamount" value="'.GETPOST("newamount",'int').'">';
	else {
		print '<b>'.price($amount).'</b>';
        print '<input type="hidden" name="amount" value="'.$amount.'">';
		print '<input type="hidden" name="newamount" value="'.$amount.'">';
	}
	// Currency
	print ' <b>'.$langs->trans("Currency".$currency).'</b>';
	print '<input type="hidden" name="currency" value="'.$currency.'">';
	print '</td></tr>'."\n";

	// Tag
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$fulltag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="fulltag" value="'.$fulltag.'">';
	print '</td></tr>'."\n";

	// EMail
	/*$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("YourEMail");
	print ' ('.$langs->trans("ToComplete").')';
    $email=$contract->client->email;
    $email=(GETPOST("EMAIL")?GETPOST("EMAIL"):(isValidEmail($email)?$email:''));
	print '</td><td class="CTableRow'.($var?'1':'2').'"><input class="flat" type="text" name="EMAIL" size="48" value="'.$email.'"></td></tr>'."\n";
    */

    // Shipping address
    $shipToName=$contract->client->nom;
    $shipToStreet=$contract->client->address;
    $shipToCity=$contract->client->ville;
    $shipToState=$contract->client->departement_code;
    $shipToCountryCode=$contract->client->pays_code;
    $shipToZip=$contract->client->cp;
    $shipToStreet2='';
    $phoneNum=$contract->client->tel;
    if ($shipToName && $shipToStreet && $shipToCity && $shipToCountryCode && $shipToZip)
    {
        print '<input type="hidden" name="shipToName" value="'.$shipToName.'">'."\n";
        print '<input type="hidden" name="shipToStreet" value="'.$shipToStreet.'">'."\n";
        print '<input type="hidden" name="shipToCity" value="'.$shipToCity.'">'."\n";
        print '<input type="hidden" name="shipToState" value="'.$shipToState.'">'."\n";
        print '<input type="hidden" name="shipToCountryCode" value="'.$shipToCountryCode.'">'."\n";
        print '<input type="hidden" name="shipToZip" value="'.$shipToZip.'">'."\n";
        print '<input type="hidden" name="shipToStreet2" value="'.$shipToStreet2.'">'."\n";
        print '<input type="hidden" name="phoneNum" value="'.$phoneNum.'">'."\n";
    }
    else
    {
        print '<!-- Shipping address not complete, so we don t use it -->'."\n";
    }
}

// Payment on member subscription
if (GETPOST("source") == 'membersubscription' && $valid)
{
	$found=true;
	$langs->load("members");

	require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");
	require_once(DOL_DOCUMENT_ROOT."/adherents/class/cotisation.class.php");

	$member=new Adherent($db);
	$result=$member->fetch('',$ref);
	if ($result < 0)
	{
		$mesg=$member->error;
		$error++;
	}
	else
	{
		$subscription=new Cotisation($db);
	}

	$amount=$subscription->total_ttc;
    if (GETPOST("amount",'int')) $amount=GETPOST("amount",'int');
    $amount=price2num($amount);

	$fulltag='MEM='.$member->id;
	//$fulltag.='.NAM='.strtr($member->getFullName($langs),"-"," ");
	if (! empty($TAG)) { $tag=$TAG; $fulltag.='.TAG='.$TAG; }
	$fulltag=dol_string_unaccent($fulltag);

	// Creditor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Creditor");
    print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$creditor.'</b>';
    print '<input type="hidden" name="creditor" value="'.$creditor.'">';
    print '</td></tr>'."\n";

	// Debitor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Member");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$member->getFullName($langs).'</b>';

	// Object
	$var=!$var;
	$text='<b>'.$langs->trans("PaymentSubscription").'</b>';
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Designation");
	print '</td><td class="CTableRow'.($var?'1':'2').'">'.$text;
	print '<input type="hidden" name="source" value="'.GETPOST("source",'alpha').'">';
	print '<input type="hidden" name="ref" value="'.$member->ref.'">';
	print '</td></tr>'."\n";

	if ($member->last_subscription_date || $member->last_subscription_amount)
	{
		// Last subscription date
		$var=!$var;
		print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("LastSubscriptionDate");
		print '</td><td class="CTableRow'.($var?'1':'2').'">'.dol_print_date($member->last_subscription_date,'day');
		print '</td></tr>'."\n";

		// Last subscription amount
		$var=!$var;
		print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("LastSubscriptionAmount");
		print '</td><td class="CTableRow'.($var?'1':'2').'">'.price($member->last_subscription_amount);
		print '</td></tr>'."\n";

		if (empty($amount) && ! GETPOST('newamount')) $_GET['newamount']=$member->last_subscription_amount;
	}

	// Amount
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Amount");
	if (empty($amount)) print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'">';
	if (empty($amount) || ! is_numeric($amount)) print '<input class="flat" size=8 type="text" name="newamount" value="'.GETPOST("newamount",'int').'">';
	else {
		print '<b>'.price($amount).'</b>';
        print '<input type="hidden" name="amount" value="'.$amount.'">';
		print '<input type="hidden" name="newamount" value="'.$amount.'">';
	}
	// Currency
	print ' <b>'.$langs->trans("Currency".$currency).'</b>';
	print '<input type="hidden" name="currency" value="'.$currency.'">';
	print '</td></tr>'."\n";

	// Tag
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$fulltag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="fulltag" value="'.$fulltag.'">';
	print '</td></tr>'."\n";

	// EMail
	/*$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("YourEMail");
	print ' ('.$langs->trans("ToComplete").')';
    $email=$member->email;
    $email=(GETPOST("EMAIL")?GETPOST("EMAIL"):(isValidEmail($email)?$email:''));
	print '</td><td class="CTableRow'.($var?'1':'2').'"><input class="flat" type="text" name="EMAIL" size="48" value="'.$email.'"></td></tr>'."\n";
    */

    // Shipping address
    $shipToName=$member->getFullName($langs);
    $shipToStreet=$member->adresse;
    $shipToCity=$member->ville;
    $shipToState=$member->departement_code;
    $shipToCountryCode=$member->pays_code;
    $shipToZip=$member->cp;
    $shipToStreet2='';
    $phoneNum=$member->tel;
    if ($shipToName && $shipToStreet && $shipToCity && $shipToCountryCode && $shipToZip)
    {
        print '<input type="hidden" name="shipToName" value="'.$shipToName.'">'."\n";
        print '<input type="hidden" name="shipToStreet" value="'.$shipToStreet.'">'."\n";
        print '<input type="hidden" name="shipToCity" value="'.$shipToCity.'">'."\n";
        print '<input type="hidden" name="shipToState" value="'.$shipToState.'">'."\n";
        print '<input type="hidden" name="shipToCountryCode" value="'.$shipToCountryCode.'">'."\n";
        print '<input type="hidden" name="shipToZip" value="'.$shipToZip.'">'."\n";
        print '<input type="hidden" name="shipToStreet2" value="'.$shipToStreet2.'">'."\n";
        print '<input type="hidden" name="phoneNum" value="'.$phoneNum.'">'."\n";
    }
    else
    {
        print '<!-- Shipping address not complete, so we don t use it -->'."\n";
    }
}




if (! $found && ! $mesg) $mesg=$langs->trans("ErrorBadParameters");

if ($mesg) print '<tr><td align="center" colspan="2"><br><div class="warning">'.$mesg.'</div></td></tr>'."\n";

print '</table>'."\n";
print "\n";

if ($found && ! $error)	// We are in a management option and no error
{
	if (empty($conf->global->PAYPAL_API_INTEGRAL_OR_PAYPALONLY)) $conf->global->PAYPAL_API_INTEGRAL_OR_PAYPALONLY='integral';

	if ($conf->global->PAYPAL_API_INTEGRAL_OR_PAYPALONLY == 'integral')
	{
		print '<br><input class="button" type="submit" name="dopayment" value="'.$langs->trans("PaypalOrCBDoPayment").'">';
	}
	if ($conf->global->PAYPAL_API_INTEGRAL_OR_PAYPALONLY == 'paypalonly')
	{
		print '<br><input class="button" type="submit" name="dopayment" value="'.$langs->trans("PaypalDoPayment").'">';
	}
}
else
{
	dol_print_error_email();
}

print '</td></tr>'."\n";

print '</table>'."\n";
print '</form>'."\n";
print '</center>'."\n";
print '<br>';


html_print_paypal_footer($mysoc,$langs);

$db->close();

llxFooterPaypal('$Date: 2011/07/31 23:23:20 $ - $Revision: 1.26 $');
?>
