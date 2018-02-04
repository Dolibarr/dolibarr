<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis.houssin@capnetworks.com>
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
 *     	\file       htdocs/public/paybox/newpayment.php
 *		\ingroup    paybox
 *		\brief      File to offer a way to make a payment for a particular Dolibarr entity
 *		\author	    Laurent Destailleur
 */

define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// TODO This should be useless. Because entity must be retreive from object ref and not from url.
$entity=(! empty($_GET['entity']) ? (int) $_GET['entity'] : (! empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) define("DOLENTITY", $entity);

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/paybox/lib/paybox.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

// Security check
if (empty($conf->paybox->enabled)) accessforbidden('',0,0,1);

$langs->load("main");
$langs->load("other");
$langs->load("dict");
$langs->load("bills");
$langs->load("companies");
$langs->load("errors");
$langs->load("paybox");

// Input are:
// type ('invoice','order','contractline'),
// id (object id),
// amount (required if id is empty),
// tag (a free text, required if type is empty)
// currency (iso code)

$suffix=GETPOST("suffix",'alpha');
$amount=price2num(GETPOST("amount"));
if (! GETPOST("currency",'alpha')) $currency=$conf->currency;
else $currency=GETPOST("currency",'alpha');

if (! GETPOST('action','aZ09'))
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

// Define $urlwithroot
$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;			// This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;						// This is to use same domain name than current

$urlok=$urlwithroot.'/public/paybox/paymentok.php?';
$urlko=$urlwithroot.'/public/paybox/paymentko.php?';

// Complete urls
$SOURCE=GETPOST("source",'alpha');
$ref=$REF=GETPOST('ref','alpha');
$TAG=GETPOST("tag",'alpha');
$FULLTAG=GETPOST("fulltag",'alpha');  // fulltag is tag with more informations
$SECUREKEY=GETPOST("securekey");	        // Secure key
$FULLTAG.=($FULLTAG?'.':'').'PM=paybox';

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
if (!empty($TAG))
{
    $urlok.='tag='.urlencode($TAG).'&';
    $urlko.='tag='.urlencode($TAG).'&';
}
if (!empty($FULLTAG))
{
    $urlok.='fulltag='.urlencode($FULLTAG).'&';
    $urlko.='fulltag='.urlencode($FULLTAG).'&';
}
$urlok=preg_replace('/&$/','',$urlok);  // Remove last &
$urlko=preg_replace('/&$/','',$urlko);  // Remove last &

// Check security token
$valid=true;
if (! empty($conf->global->PAYMENT_SECURITY_TOKEN))
{
	if (! empty($conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE))
	{
		if ($SOURCE && $REF) $token = dol_hash($conf->global->PAYMENT_SECURITY_TOKEN . $SOURCE . $REF, 2);    // Use the source in the hash to avoid duplicates if the references are identical
		else $token = dol_hash($conf->global->PAYMENT_SECURITY_TOKEN, 2);
	}
	else
	{
		$token = $conf->global->PAYMENT_SECURITY_TOKEN;
	}
	if ($SECUREKEY != $token)
	{
		if (empty($conf->global->PAYMENT_SECURITY_ACCEPT_ANY_TOKEN)) $valid=false;	// PAYMENT_SECURITY_ACCEPT_ANY_TOKEN is for backward compatibility
		else dol_syslog("Warning: PAYMENT_SECURITY_ACCEPT_ANY_TOKEN is on", LOG_WARNING);
	}

	if (! $valid)
	{
		print '<div class="error">Bad value for key.</div>';
		//print 'SECUREKEY='.$SECUREKEY.' token='.$token.' valid='.$valid;
		exit;
	}
}


/*
 * Actions
 */

if (GETPOST('action','aZ09') == 'dopayment')
{
    $PRICE=price2num(GETPOST("newamount",'alpha'),'MT');
    $email=GETPOST("email");

    $origfulltag=GETPOST("fulltag",'alpha');

	$mesg='';
	if (empty($PRICE) || ! is_numeric($PRICE)) $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Amount"));
	elseif (empty($email))            $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("YourEMail"));
	elseif (! isValidEMail($email))   $mesg=$langs->trans("ErrorBadEMail",$email);
    elseif (! $origfulltag)           $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("PaymentCode"));
	elseif (dol_strlen($urlok) > 150) $mesg='Error urlok too long '.$urlok;
    elseif (dol_strlen($urlko) > 150) $mesg='Error urlko too long '.$urlko;

	if (empty($mesg))
	{
		dol_syslog("newpayment.php call paybox api and do redirect", LOG_DEBUG);

		print_paybox_redirect($PRICE, $conf->currency, $email, $urlok, $urlko, $FULLTAG);

		session_destroy();
		exit;
	}
}



/*
 * View
 */

$head='';
if (! empty($conf->global->ONLINE_PAYMENT_CSS_URL)) $head='<link rel="stylesheet" type="text/css" href="'.$conf->global->ONLINE_PAYMENT_CSS_URL.'?lang='.$langs->defaultlang.'">'."\n";

$conf->dol_hide_topmenu=1;
$conf->dol_hide_leftmenu=1;

llxHeader($head, $langs->trans("PaymentForm"), '', '', 0, 0, '', '', '', 'onlinepaymentbody');

// Common variables
$creditor=$mysoc->name;
$paramcreditor='ONLINE_PAYMENT_CREDITOR_'.$suffix;
if (! empty($conf->global->$paramcreditor)) $creditor=$conf->global->$paramcreditor;
else if (! empty($conf->global->ONLINE_PAYMENT_CREDITOR)) $creditor=$conf->global->ONLINE_PAYMENT_CREDITOR;

// Check link validity
if (! empty($SOURCE) && in_array($ref, array('member_ref', 'contractline_ref', 'invoice_ref', 'order_ref', '')))
{
    $langs->load("errors");
    dol_print_error_email('BADREFINPAYMENTFORM', $langs->trans("ErrorBadLinkSourceSetButBadValueForRef", $SOURCE, $ref));
    llxFooter();
    $db->close();
    exit;
}

print '<span id="dolpaymentspan"></span>'."\n";
print '<div class="center">';
print '<form id="dolpaymentform" class="center" name="paymentform" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="dopayment">';
print '<input type="hidden" name="tag" value="'.GETPOST("tag",'alpha').'">';
print '<input type="hidden" name="suffix" value="'.GETPOST("suffix",'alpha').'">';
print "\n";
print '<!-- Form to send a Paybox payment -->'."\n";
print '<!-- ONLINE_PAYMENT_CREDITOR = '.$conf->global->ONLINE_PAYMENT_CREDITOR.' -->'."\n";
print '<!-- creditor = '.$creditor.' -->'."\n";
print '<!-- urlok = '.$urlok.' -->'."\n";
print '<!-- urlko = '.$urlko.' -->'."\n";
print "\n";

print '<table id="dolpaymenttable" summary="Payment form" class="center">'."\n";

// Show logo (search order: logo defined by PAYMENT_LOGO_suffix, then PAYMENT_LOGO, then small company logo, large company logo, theme logo, common logo)
$width=0;
// Define logo and logosmall
$logosmall=$mysoc->logo_small;
$logo=$mysoc->logo;
$paramlogo='PAYMENT_LOGO_'.$suffix;
if (! empty($conf->global->$paramlogo)) $logosmall=$conf->global->$paramlogo;
else if (! empty($conf->global->PAYMENT_LOGO)) $logosmall=$conf->global->PAYMENT_LOGO;
//print '<!-- Show logo (logosmall='.$logosmall.' logo='.$logo.') -->'."\n";
// Define urllogo
$urllogo='';
if (! empty($logosmall) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$logosmall))
{
	$urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('thumbs/'.$logosmall);
}
elseif (! empty($logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$logo))
{
	$urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode($logo);
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
if (! empty($conf->global->PAYMENT_NEWFORM_TEXT))
{
    $langs->load("members");
    if (preg_match('/^\((.*)\)$/',$conf->global->PAYMENT_NEWFORM_TEXT,$reg)) $text.=$langs->trans($reg[1])."<br>\n";
    else $text.=$conf->global->PAYMENT_NEWFORM_TEXT."<br>\n";
    $text='<tr><td align="center"><br>'.$text.'<br></td></tr>'."\n";
}
if (empty($text))
{
    $text.='<tr><td class="textpublicpayment"><br><strong>'.$langs->trans("WelcomeOnPaymentPage").'</strong></td></tr>'."\n";
    $text.='<tr><td class="textpublicpayment">'.$langs->trans("ThisScreenAllowsYouToPay",$creditor).'<br><br></td></tr>'."\n";
}
print $text;

// Output payment summary form
print '<tr><td align="center">';
print '<table with="100%" id="tablepublicpayment">';
print '<tr><td align="left" colspan="2">'.$langs->trans("ThisIsInformationOnPayment").' :</td></tr>'."\n";

$found=false;
$error=0;
$var=false;



// Free payment
if (! GETPOST("source") && $valid)
{
	$found=true;
    $tag=GETPOST("tag");
    $fulltag=$tag;

	// Creditor

	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Creditor");
    print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$creditor.'</b>';
    print '<input type="hidden" name="creditor" value="'.$creditor.'">';
    print '</td></tr>'."\n";

	// Amount

	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Amount");
	if (empty($amount)) print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'">';
	if (empty($amount) || ! is_numeric($amount))
	{
        print '<input type="hidden" name="amount" value="'.GETPOST("amount",'int').'">';
	    print '<input class="flat maxwidth75" type="text" name="newamount" value="'.price2num(GETPOST("newamount","alpha"),'MT').'">';
	}
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

	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b style="word-break: break-all;">'.$fulltag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="fulltag" value="'.$fulltag.'">';
	print '</td></tr>'."\n";

	// EMail

	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("YourEMail");
	print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'"><input class="flat" type="text" name="email" size="48" value="'.GETPOST("email").'"></td></tr>'."\n";
}


// Payment on customer order
if (GETPOST("source") == 'order' && $valid)
{
	$found=true;
	$langs->load("orders");

	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

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

	$fulltag='IR='.$order->ref.'.TPID='.$order->thirdparty->id;
	//$fulltag.='.TP='.strtr($order->thirdparty->name,"-"," ");    We disable this because url that will contains FULLTAG must be lower than 150
	if (! empty($TAG)) { $tag=$TAG; $fulltag.='.TAG='.$TAG; }
	$fulltag=dol_string_unaccent($fulltag);

	// Creditor

	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Creditor");
    print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$creditor.'</b>';
    print '<input type="hidden" name="creditor" value="'.$creditor.'">';
    print '</td></tr>'."\n";

	// Debitor

	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("ThirdParty");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$order->thirdparty->name.'</b>';

	// Object

	$text='<b>'.$langs->trans("PaymentOrderRef",$order->ref).'</b>';
	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Designation");
	print '</td><td class="CTableRow'.($var?'1':'2').'">'.$text;
	print '<input type="hidden" name="source" value="'.GETPOST("source",'alpha').'">';
	print '<input type="hidden" name="ref" value="'.$order->ref.'">';
	print '</td></tr>'."\n";

	// Amount

	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Amount");
	if (empty($amount)) print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'">';
	if (empty($amount) || ! is_numeric($amount))
	{
        print '<input type="hidden" name="amount" value="'.GETPOST("amount",'int').'">';
	    print '<input class="flat maxwidth75" type="text" name="newamount" value="'.price2num(GETPOST("newamount","alpha"),'MT').'">';
	}
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

	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b style="word-break: break-all;">'.$fulltag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="fulltag" value="'.$fulltag.'">';
	print '</td></tr>'."\n";

	// EMail

	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("YourEMail");
	print ' ('.$langs->trans("ToComplete").')';
	$email=$order->thirdparty->email;
	$email=(GETPOST("email")?GETPOST("email"):(isValidEmail($email)?$email:''));
	print '</td><td class="CTableRow'.($var?'1':'2').'"><input class="flat" type="text" name="email" size="48" value="'.$email.'"></td></tr>'."\n";
}


// Payment on customer invoice
if (GETPOST("source") == 'invoice' && $valid)
{
	$found=true;
	$langs->load("bills");

	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

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

	$fulltag='IR='.$invoice->ref.'.TPID='.$invoice->thirdparty->id;
	//$fulltag.='.TP='.strtr($invoice->thirdparty->name,"-"," ");        We disable this because url that will contains FULLTAG must be lower than 150
	if (! empty($TAG)) { $tag=$TAG; $fulltag.='.TAG='.$TAG; }
	$fulltag=dol_string_unaccent($fulltag);

	// Creditor

	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Creditor");
    print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$creditor.'</b>';
    print '<input type="hidden" name="creditor" value="'.$creditor.'">';
    print '</td></tr>'."\n";

	// Debitor

	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("ThirdParty");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$invoice->thirdparty->name.'</b>';

	// Object

	$text='<b>'.$langs->trans("PaymentInvoiceRef",$invoice->ref).'</b>';
	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Designation");
	print '</td><td class="CTableRow'.($var?'1':'2').'">'.$text;
	print '<input type="hidden" name="source" value="'.GETPOST("source",'alpha').'">';
	print '<input type="hidden" name="ref" value="'.$invoice->ref.'">';
	print '</td></tr>'."\n";

	// Amount

	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Amount");
	if (empty($amount)) print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'">';
	if (empty($amount) || ! is_numeric($amount))
	{
        print '<input type="hidden" name="amount" value="'.GETPOST("amount",'int').'">';
	    print '<input class="flat maxwidth75" type="text" name="newamount" value="'.price2num(GETPOST("newamount","alpha"),'MT').'">';
	}
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

	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b style="word-break: break-all;">'.$fulltag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="fulltag" value="'.$fulltag.'">';
	print '</td></tr>'."\n";

	// EMail

	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("YourEMail");
	print ' ('.$langs->trans("ToComplete").')';
    $email=$invoice->thirdparty->email;
    $email=(GETPOST("email")?GETPOST("email"):(isValidEmail($email)?$email:''));
	print '</td><td class="CTableRow'.($var?'1':'2').'"><input class="flat" type="text" name="email" size="48" value="'.$email.'"></td></tr>'."\n";
}

// Payment on contract line
if (GETPOST("source") == 'contractline' && $valid)
{
	$found=true;
	$langs->load("contracts");

	require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';

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
		if (! empty($conf->global->PRODUIT_MULTIPRICES))
		{
			$pu_ht = $product->multiprices[$contract->thirdparty->price_level];
			$pu_ttc = $product->multiprices_ttc[$contract->thirdparty->price_level];
			$price_base_type = $product->multiprices_base_type[$contract->thirdparty->price_level];
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

	$fulltag='CLR='.$contractline->ref.'.CR='.$contract->ref.'.TPID='.$contract->thirdparty->id;
	//$fulltag.='.TP='.strtr($contract->thirdparty->name,"-"," ");    We disable this because url that will contains FULLTAG must be lower than 150
	if (! empty($TAG)) { $tag=$TAG; $fulltag.='.TAG='.$TAG; }
	$fulltag=dol_string_unaccent($fulltag);

	$qty=1;
	if (GETPOST('qty')) $qty=GETPOST('qty');

	// Creditor

	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Creditor");
    print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$creditor.'</b>';
    print '<input type="hidden" name="creditor" value="'.$creditor.'">';
    print '</td></tr>'."\n";

	// Debitor

	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("ThirdParty");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$contract->thirdparty->name.'</b>';

	// Object

	$text='<b>'.$langs->trans("PaymentRenewContractId",$contract->ref,$contractline->ref).'</b>';
	if ($contractline->fk_product)
	{
		$text.='<br>'.$product->ref.($product->label?' - '.$product->label:'');
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

	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Designation");
	print '</td><td class="CTableRow'.($var?'1':'2').'">'.$text;
	print '<input type="hidden" name="source" value="'.GETPOST("source",'alpha').'">';
	print '<input type="hidden" name="ref" value="'.$contractline->ref.'">';
	print '</td></tr>'."\n";

	// Quantity

	$label=$langs->trans("Quantity");
	$qty=1;
	$duration='';
	if ($contractline->fk_product)
	{
		if ($product->isService() && $product->duration_value > 0)
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
	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$label.'</td>';
	print '<td class="CTableRow'.($var?'1':'2').'"><b>'.($duration?$duration:$qty).'</b>';
	print '<input type="hidden" name="newqty" value="'.dol_escape_htmltag($qty).'">';
	print '</b></td></tr>'."\n";

	// Amount

	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Amount");
	if (empty($amount)) print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'">';
	if (empty($amount) || ! is_numeric($amount))
	{
        print '<input type="hidden" name="amount" value="'.GETPOST("amount",'int').'">';
	    print '<input class="flat maxwidth75" type="text" name="newamount" value="'.price2num(GETPOST("newamount","alpha"),'MT').'">';
	}
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

	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b style="word-break: break-all;">'.$fulltag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="fulltag" value="'.$fulltag.'">';
	print '</td></tr>'."\n";

	// EMail

	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("YourEMail");
	print ' ('.$langs->trans("ToComplete").')';
    $email=$contract->thirdparty->email;
    $email=(GETPOST("email")?GETPOST("email"):(isValidEmail($email)?$email:''));
	print '</td><td class="CTableRow'.($var?'1':'2').'"><input class="flat" type="text" name="email" size="48" value="'.$email.'"></td></tr>'."\n";

}

// Payment on member subscription
if (GETPOST("source") == 'membersubscription' && $valid)
{
	$found=true;
	$langs->load("members");

	require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
	require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';

	$member=new Adherent($db);
	$result=$member->fetch('',$ref);
	if ($result < 0)
	{
		$mesg=$member->error;
		$error++;
	}
	else
	{
		$subscription=new Subscription($db);
	}

	$amount=$subscription->total_ttc;
    if (GETPOST("amount",'int')) $amount=GETPOST("amount",'int');
    $amount=price2num($amount);

	$fulltag='MID='.$member->id;
	//$fulltag.='.M='.dol_trunc(strtr($member->getFullName($langs),"-"," "),12);        We disable this because url that will contains FULLTAG must be lower than 150
	if (! empty($TAG)) { $tag=$TAG; $fulltag.='.TAG='.$TAG; }
	$fulltag=dol_string_unaccent($fulltag);

	// Creditor

	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Creditor");
    print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$creditor.'</b>';
    print '<input type="hidden" name="creditor" value="'.$creditor.'">';
    print '</td></tr>'."\n";

	// Debitor

	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Member");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>';
	if ($member->morphy == 'mor' && ! empty($member->societe)) print $member->societe;
	else print $member->getFullName($langs);
	print '</b>';

	// Object

	$text='<b>'.$langs->trans("PaymentSubscription").'</b>';
	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Designation");
	print '</td><td class="CTableRow'.($var?'1':'2').'">'.$text;
	print '<input type="hidden" name="source" value="'.GETPOST("source",'alpha').'">';
	print '<input type="hidden" name="ref" value="'.$member->ref.'">';
	print '</td></tr>'."\n";

	if ($member->last_subscription_date || $member->last_subscription_amount)
	{
		// Last subscription date

		print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("LastSubscriptionDate");
		print '</td><td class="CTableRow'.($var?'1':'2').'">'.dol_print_date($member->last_subscription_date,'day');
		print '</td></tr>'."\n";

		// Last subscription amount

		print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("LastSubscriptionAmount");
		print '</td><td class="CTableRow'.($var?'1':'2').'">'.price($member->last_subscription_amount);
		print '</td></tr>'."\n";

		if (empty($amount) && ! GETPOST('newamount','alpha')) $_GET['newamount']=$member->last_subscription_amount;
	}

	// Amount

	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Amount");
	if (empty($amount)) print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'">';
	$valtoshow='';
	if (empty($amount) || ! is_numeric($amount))
	{
		$valtoshow=price2num(GETPOST("newamount",'alpha'),'MT');
		// force default subscription amount to value defined into constant...
		if (empty($valtoshow))
		{
			if (! empty($conf->global->MEMBER_NEWFORM_EDITAMOUNT)) {
				if (! empty($conf->global->MEMBER_NEWFORM_AMOUNT)) {
					$valtoshow = $conf->global->MEMBER_NEWFORM_AMOUNT;
				}
			}
			else {
				if (! empty($conf->global->MEMBER_NEWFORM_AMOUNT)) {
					$amount = $conf->global->MEMBER_NEWFORM_AMOUNT;
				}
			}
		}
	}
	if (empty($amount) || ! is_numeric($amount))
	{
	    //$valtoshow=price2num(GETPOST("newamount",'alpha'),'MT');
	    if (! empty($conf->global->MEMBER_MIN_AMOUNT) && $valtoshow) $valtoshow=max($conf->global->MEMBER_MIN_AMOUNT,$valtoshow);
        print '<input type="hidden" name="amount" value="'.GETPOST("amount",'int').'">';
	    print '<input class="flat maxwidth75" type="text" name="newamount" value="'.$valtoshow.'">';
	}
	else {
	    $valtoshow=$amount;
	    if (! empty($conf->global->MEMBER_MIN_AMOUNT) && $valtoshow) $valtoshow=max($conf->global->MEMBER_MIN_AMOUNT,$valtoshow);
	    print '<b>'.price($valtoshow).'</b>';
	    print '<input type="hidden" name="amount" value="'.$valtoshow.'">';
	    print '<input type="hidden" name="newamount" value="'.$valtoshow.'">';
	}
	// Currency
	print ' <b>'.$langs->trans("Currency".$currency).'</b>';
	print '<input type="hidden" name="currency" value="'.$currency.'">';
	print '</td></tr>'."\n";

	// Tag

	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b style="word-break: break-all;">'.$fulltag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="fulltag" value="'.$fulltag.'">';
	print '</td></tr>'."\n";

	// EMail

	print '<tr class="CTableRow'.($var?'1':'2').'"><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("YourEMail");
    $email=$member->email;
    $email=(GETPOST("email")?GETPOST("email"):(isValidEmail($email)?$email:''));
	if (empty($email)) print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'"><input class="flat" type="text" name="email" size="48" value="'.$email.'"></td></tr>'."\n";
}




if (! $found && ! $mesg) $mesg=$langs->trans("ErrorBadParameters");

if ($mesg) print '<tr><td align="center" colspan="2"><br><div class="warning">'.$mesg.'</div></td></tr>'."\n";

print '</table>'."\n";
print "\n";

if ($found && ! $error)	// We are in a management option and no error
{
	print '<br><input class="button" type="submit" name="dopayment" value="'.$langs->trans("PayBoxDoPayment").'">';
	//print '<tr><td align="center" colspan="2">'.$langs->trans("YouWillBeRedirectedOnPayBox").'...</td></tr>';
}
else
{
	dol_print_error_email('ERRORNEWPAYMENTPAYBOX');
}

print '</td></tr>'."\n";

print '</table>'."\n";
print '</form>'."\n";
print '</div>'."\n";
print '<br>';


htmlPrintOnlinePaymentFooter($mysoc,$langs,1,$suffix);


llxFooter('', 'public');

$db->close();
