<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis@dolibarr.fr>
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

/**
 *     	\file       htdocs/public/paybox/newpayment.php
 *		\ingroup    paybox
 *		\brief      File to offer a way to make a payment for a particular Dolibarr entity
 *		\author	    Laurent Destailleur
 *		\version    $Id$
 */

// Init session. Name of session is specific to Dolibarr instance.
$sessionname='DOLSESSID_'.md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"]);
$sessiontimeout='DOLSESSTIMEOUT_'.md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"]);
if (! empty($_COOKIE[$sessiontimeout])) ini_set('session.gc_maxlifetime',$_COOKIE[$sessiontimeout]);
session_name($sessionname);
session_start();

// Creation d'un jeton contre les failles CSRF
$token = md5(uniqid(mt_rand(),TRUE)); // Genere un hash d'un nombre aleatoire
// roulement des jetons car cree a chaque appel
if (isset($_SESSION['newtoken'])) $_SESSION['token'] = $_SESSION['newtoken'];
$_SESSION['newtoken'] = $token;

// Verification de la presence et de la validite du jeton
if (isset($_POST['token']) && isset($_SESSION['token']))
{
	if ($_POST['token'] != $_SESSION['token'])
	{
		unset($_POST);
	}
}

require("../../master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/paybox/paybox.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");

$langcode=(empty($_GET["lang"])?'auto':$_GET["lang"]);
$langs->setDefaultLang($langcode);

// Security check
if (empty($conf->paybox->enabled)) accessforbidden('',1,1,1);

$langs->load("main");
$langs->load("other");
$langs->load("paybox");
$langs->load("dict");
$langs->load("bills");
$langs->load("companies");

// Input are:
// type ('invoice','order','contractline'),
// id (object id),
// amount (required if id is empty),
// tag (a free text, required if type is empty)
// currency (iso code)

if (empty($_REQUEST["currency"])) $currency=$conf->global->MAIN_MONNAIE;
else $currency=$_REQUEST["currency"];
if (empty($_REQUEST["amount"]))
{
	dol_print_error('','ErrorBadParameters');
	session_destroy();
	exit;
}
$amount=$_REQUEST["amount"];
if (is_numeric($amount) && empty($_REQUEST["tag"]))
{
	dol_print_error('','ErrorBadParameters');
	session_destroy();
	exit;
}
if (! is_numeric($amount) && empty($_REQUEST["ref"]))
{
	dol_print_error('','ErrorBadParameters');
	session_destroy();
	exit;
}
$suffix=$_REQUEST["suffix"];



/*
 * Actions
 */
if ($_REQUEST["action"] == 'dopayment')
{
	$PRICE=$_REQUEST["newamount"];
	$EMAIL=$_REQUEST["EMAIL"];
	$urlok='';
	$urlko='';
	$TAG=$_REQUEST["newtag"];
	$ID=$_REQUEST["id"];

	$mesg='';
	if (empty($PRICE))              $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Amount"));
	elseif (empty($EMAIL))          $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("YourEMail"));
	elseif (! isValidEMail($EMAIL)) $mesg=$langs->trans("ErrorBadEMail",$EMAIL);
	elseif (empty($TAG))            $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("PaymentCode"));

	if (empty($mesg))
	{
		print_paybox_redirect($PRICE, $conf->monnaie, $EMAIL, $urlok, $urlko, $TAG, $ID);
		session_destroy();
		exit;
	}
}



/*
 * View
 */

llxHeaderPayBox($langs->trans("PaymentForm"));


// Common variables
$creditor=$mysoc->nom;
$paramcreditor='PAYBOX_CREDITOR_'.$suffix;
if (! empty($conf->global->$paramcreditor)) $creditor=$conf->global->$paramcreditor;
else if (! empty($conf->global->PAYBOX_CREDITOR)) $creditor=$conf->global->PAYBOX_CREDITOR;

print '<center>';
print '<form name="paymentform" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="dopayment">';
print '<input type="hidden" name="amount" value="'.$_REQUEST["amount"].'">';
print '<input type="hidden" name="tag" value="'.$_REQUEST["tag"].'">';
print '<input type="hidden" name="suffix" value="'.$_REQUEST["suffix"].'">';
print "\n";

print '<table style="font-size:14px;" summary="Logo" width="80%">'."\n";

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
	print '<td align="center"><img title="'.$title.'" src="'.$urllogo.'"';
	if ($width) print ' width="'.$width.'"';
	print '></td>';
	print '</tr>'."\n";
}

print '<tr><td align="center"><br>'.$langs->trans("WelcomeOnPaymentPage").'<br></td></tr>'."\n";

print '<tr><td align="center"><br>'.$langs->trans("ThisScreenAllowsYouToPay",$creditor).'<br><br></td></tr>'."\n";

print '<tr><td align="center">';
print '<table with="100%">';
print '<tr class="liste_total"><td align="left" colspan="2">'.$langs->trans("ThisIsInformationOnPayment").' :</td></tr>'."\n";

$found=false;
$var=false;



// Free payment
if (is_numeric($_REQUEST["amount"]))
{
	$found=true;
	$tag=$_REQUEST["tag"];
	$newtag=$tag;

	// Creditor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Creditor");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$creditor.'</b></td></tr>'."\n";

	// Amount
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Amount");
	if (empty($amount)) print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'">';
	if (empty($amount) || ! is_numeric($amount)) print '<input class="flat" size=8 type="text" name="newamount" value="'.$_REQUEST["newamount"].'">';
	else {
		print '<b>'.price($amount).'</b>';
		print '<input type="hidden" name="newamount" value="'.$amount.'">';
	}
	print ' <b>'.$langs->trans("Currency".$conf->monnaie).'</b>';
	print '<input type="hidden" name="currency" value="'.$conf->monnaie.'">';
	print '</td></tr>'."\n";

	// Tag
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$newtag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="newtag" value="'.$newtag.'">';
	print '</td></tr>'."\n";

	// EMail
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("YourEMail");
	print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'"><input class="flat" type="text" name="EMAIL" size="48" value="'.$_REQUEST["EMAIL"].'"></td></tr>'."\n";
}


// Payment on customer order
if ($_REQUEST["amount"] == 'order')
{
	$found=true;
	$langs->load("orders");

	require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");

	$order=new Commande($db);
	$result=$order->fetch('',$_REQUEST["ref"]);
	if ($result < 0)
	{
		$mesg=$order->error;
	}
	else
	{
		$result=$order->fetch_client($order->socid);
	}

	$amount=$order->total_ttc;

	$newtag='IR='.$order->ref.'.TPID='.$order->client->id.'.TP='.strtr($order->client->nom,"-"," ");
	if (! empty($_REQUEST["tag"])) { $tag=$_REQUEST["tag"]; $newtag.='.TAG='.$_REQUEST["tag"]; }
	$newtag=dol_string_unaccent($newtag);

	// Creditor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Creditor");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$creditor.'</b></td></tr>'."\n";

	// Debitor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("ThirdParty");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$order->client->nom.'</b>';

	// Object
	$var=!$var;
	$text='<b>'.$langs->trans("PaymentOrderRef",$order->ref).'</b>';
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Designation");
	print '</td><td class="CTableRow'.($var?'1':'2').'">'.$text;
	print '<input type="hidden" name="ref" value="'.$order->ref.'">';
	print '</td></tr>'."\n";

	// Amount
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Amount");
	if (empty($amount)) print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'">';
	if (empty($amount) || ! is_numeric($amount)) print '<input class="flat" size=8 type="text" name="newamount" value="'.$_REQUEST["newamount"].'">';
	else {
		print '<b>'.price($amount).'</b>';
		print '<input type="hidden" name="newamount" value="'.$amount.'">';
	}
	print ' <b>'.$langs->trans("Currency".$conf->monnaie).'</b>';
	print '<input type="hidden" name="currency" value="'.$conf->monnaie.'">';
	print '</td></tr>'."\n";

	// Tag
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$newtag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="newtag" value="'.$newtag.'">';
	print '</td></tr>'."\n";

	// EMail
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("YourEMail");
	print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'"><input class="flat" type="text" name="EMAIL" size="48" value="'.$_REQUEST["EMAIL"].'"></td></tr>'."\n";
}


// Payment on customer invoice
if ($_REQUEST["amount"] == 'invoice')
{
	$found=true;
	$langs->load("bills");

	require_once(DOL_DOCUMENT_ROOT."/facture.class.php");

	$invoice=new Facture($db);
	$result=$invoice->fetch('',$_REQUEST["ref"]);
	if ($result < 0)
	{
		$mesg=$invoice->error;
	}
	else
	{
		$result=$invoice->fetch_client($invoice->socid);
	}

	$amount=$invoice->total_ttc - $invoice->getSommePaiement();

	$newtag='IR='.$invoice->ref.'.TPID='.$invoice->client->id.'.TP='.strtr($invoice->client->nom,"-"," ");
	if (! empty($_REQUEST["tag"])) { $tag=$_REQUEST["tag"]; $newtag.='.TAG='.$_REQUEST["tag"]; }
	$newtag=dol_string_unaccent($newtag);

	// Creditor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Creditor");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$creditor.'</b></td></tr>'."\n";

	// Debitor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("ThirdParty");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$invoice->client->nom.'</b>';

	// Object
	$var=!$var;
	$text='<b>'.$langs->trans("PaymentInvoiceRef",$invoice->ref).'</b>';
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Designation");
	print '</td><td class="CTableRow'.($var?'1':'2').'">'.$text;
	print '<input type="hidden" name="ref" value="'.$invoice->ref.'">';
	print '</td></tr>'."\n";

	// Amount
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Amount");
	if (empty($amount)) print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'">';
	if (empty($amount) || ! is_numeric($amount)) print '<input class="flat" size=8 type="text" name="newamount" value="'.$_REQUEST["newamount"].'">';
	else {
		print '<b>'.price($amount).'</b>';
		print '<input type="hidden" name="newamount" value="'.$amount.'">';
	}
	print ' <b>'.$langs->trans("Currency".$conf->monnaie).'</b>';
	print '<input type="hidden" name="currency" value="'.$conf->monnaie.'">';
	print '</td></tr>'."\n";

	// Tag
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$newtag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="newtag" value="'.$newtag.'">';
	print '</td></tr>'."\n";

	// EMail
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("YourEMail");
	print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'"><input class="flat" type="text" name="EMAIL" size="48" value="'.$_REQUEST["EMAIL"].'"></td></tr>'."\n";
}

// Payment on contract line
if ($_REQUEST["amount"] == 'contractline')
{
	$found=true;
	$langs->load("contracts");

	require_once(DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");

	$contractline=new ContratLigne($db);
	$result=$contractline->fetch('',$_REQUEST["ref"]);
	if ($result < 0)
	{
		$mesg=$contractline->error;
	}
	else
	{
		if ($contractline->fk_contrat > 0)
		{
			$contract=new Contrat($db);
			$result=$contract->fetch($contractline->fk_contrat);
			if ($result > 0)
			{
				$result=$contract->fetch_client($contract->socid);
			}
			else
			{
				$mesg=$contract->error;
			}
		}
		else
		{
			$mesg='ErrorRecordNotFound';
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

	$newtag='CLR='.$contractline->ref.'.CR='.$contract->ref.'.TPID='.$contract->client->id.'.TP='.strtr($contract->client->nom,"-"," ");
	if (! empty($_REQUEST["tag"])) { $tag=$_REQUEST["tag"]; $newtag.='.TAG='.$_REQUEST["tag"]; }
	$newtag=dol_string_unaccent($newtag);

	$qty=1;
	if (isset($_REQUEST["qty"])) $qty=$_REQUEST["qty"];

	// Creditor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Creditor");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$creditor.'</b></td></tr>'."\n";

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
		//$dateactend = dol_time_plus_duree ($contractline->date_fin_validite, $product->duration_value, $product->duration_unit);
		//print ', '.$langs->trans("DateEndPlanned").': '.dol_print_date($contractline->date_fin_validite);
		$text.='<br>'.$langs->trans("ExpiredSince").': '.dol_print_date($contractline->date_fin_validite);
	}

	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Designation");
	print '</td><td class="CTableRow'.($var?'1':'2').'">'.$text;
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
	print '<input type="hidden" name="newqty" value="'.$qty.'">';
	print '</b></td></tr>'."\n";

	// Amount
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Amount");
	if (empty($amount)) print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'">';
	if (empty($amount) || ! is_numeric($amount)) print '<input class="flat" size=8 type="text" name="newamount" value="'.$_REQUEST["newamount"].'">';
	else {
		print '<b>'.price($amount).'</b>';
		print '<input type="hidden" name="newamount" value="'.$amount.'">';
	}
	print ' <b>'.$langs->trans("Currency".$conf->monnaie).'</b>';
	print '<input type="hidden" name="currency" value="'.$conf->monnaie.'">';
	print '</td></tr>'."\n";

	// Tag
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$newtag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="newtag" value="'.$newtag.'">';
	print '</td></tr>'."\n";

	// EMail
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("YourEMail");
	print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'"><input class="flat" type="text" name="EMAIL" size="48" value="'.$_REQUEST["EMAIL"].'"></td></tr>'."\n";

}

// Payment on member subscription
if ($_REQUEST["amount"] == 'membersubscription')
{
	$found=true;
	$langs->load("members");

	require_once(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
	require_once(DOL_DOCUMENT_ROOT."/adherents/cotisation.class.php");

	$member=new Adherent($db);
	$result=$member->fetch('',$_REQUEST["ref"]);
	if ($result < 0)
	{
		$mesg=$member->error;
	}
	else
	{
		$subscription=new Cotisation($db);
		//$result=$subscription->fetch();
	}

	$amount=$subscription->total_ttc;

	$newtag='MID='.$member->id.'.M='.strtr($member->fullname,"-"," ");
	if (! empty($_REQUEST["tag"])) { $tag=$_REQUEST["tag"]; $newtag.='.TAG='.$_REQUEST["tag"]; }
	$newtag=dol_string_unaccent($newtag);

	// Creditor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Creditor");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$creditor.'</b></td></tr>'."\n";

	// Debitor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Member");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$member->fullname.'</b>';

	// Object
	$var=!$var;
	$text='<b>'.$langs->trans("PaymentSubscription").'</b>';
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Designation");
	print '</td><td class="CTableRow'.($var?'1':'2').'">'.$text;
	print '<input type="hidden" name="ref" value="'.$member->ref.'">';
	print '</td></tr>'."\n";

	// Amount
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Amount");
	if (empty($amount)) print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'">';
	if (empty($amount) || ! is_numeric($amount)) print '<input class="flat" size=8 type="text" name="newamount" value="'.$_REQUEST["newamount"].'">';
	else {
		print '<b>'.price($amount).'</b>';
		print '<input type="hidden" name="newamount" value="'.$amount.'">';
	}
	print ' <b>'.$langs->trans("Currency".$conf->monnaie).'</b>';
	print '<input type="hidden" name="currency" value="'.$conf->monnaie.'">';
	print '</td></tr>'."\n";

	// Tag
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$newtag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="newtag" value="'.$newtag.'">';
	print '</td></tr>'."\n";

	// EMail
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("YourEMail");
	print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'"><input class="flat" type="text" name="EMAIL" size="48" value="'.$_REQUEST["EMAIL"].'"></td></tr>'."\n";
}




if (! $found && ! $mesg) $mesg=$langs->trans("ErrorBadParameters");

if ($mesg) print '<tr><td align="center" colspan="2"><br><div class="warning">'.$mesg.'</div></td></tr>';

if ($found)
{
	print '<tr><td align="center" colspan="2"><br><input class="button" type="submit" name="dopayment" value="'.$langs->trans("PayBoxDoPayment").'"></td></tr>';
	//print '<tr><td align="center" colspan="2">'.$langs->trans("YouWillBeRedirectedOnPayBox").'...</td></tr>';
}

print '</table>';
print '</td></tr>';

print '</table>';
print '</form>';
print '</center>';
print '<br>';


html_print_footer($mysoc,$langs);

$db->close();

llxFooterPayBox('$Date$ - $Revision$');
?>
