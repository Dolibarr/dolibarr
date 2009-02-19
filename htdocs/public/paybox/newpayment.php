<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *     	\file       htdocs/public/paybox/index.php
 *		\ingroup    core
 *		\brief      File to offer a way to make a payment for a particular Dolibarr entity
 *		\author	    Laurent Destailleur
 *		\version    $Id$
 */

require("../../master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/paybox/paybox.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");

$langcode=(empty($_GET["lang"])?'auto':$_GET["lang"]);
$langs->setDefaultLang($langcode);

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
	dolibarr_print_error('','ErrorBadParameters');
	exit;
}
$amount=$_REQUEST["amount"];
if (is_numeric($amount) && empty($_REQUEST["tag"]))
{
	dolibarr_print_error('','ErrorBadParameters');
	exit;
}
if (! is_numeric($amount) && empty($_REQUEST["ref"]))
{
	dolibarr_print_error('','ErrorBadParameters');
	exit;
}



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
	if (empty($PRICE))            $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Amount"));
	elseif (empty($EMAIL))        $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("YourEMail"));
	elseif (! ValidEMail($EMAIL)) $mesg=$langs->trans("ErrorBadEMail",$EMAIL);
	elseif (empty($TAG))          $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("PaymentCode"));

	if (empty($mesg))
	{
		print_paybox_redirect($PRICE, $conf->monnaie, $EMAIL, $urlok, $urlko, $TAG, $ID);
		exit;
	}
}



/*
 * View
 */

llxHeaderPayBox($langs->trans("PaymentForm"));


// Common variables
$creditor=$mysoc->nom;
if (! empty($conf->global->PAYBOX_CREDITOR)) $creditor=$conf->global->PAYBOX_CREDITOR;


print '<center>';
print '<form name="paymentform" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="action" value="dopayment">';
print '<input type="hidden" name="amount" value="'.$_REQUEST["amount"].'">';
print '<input type="hidden" name="tag" value="'.$_REQUEST["tag"].'">';
print "\n";

print '<table style="font-size:14px;" summary="Logo" width="80%">'."\n";

// Show logo (search in order: small company logo, large company logo, theme logo, common logo)
$width=0;
$urllogo='';
if (! empty($mysoc->logo_small) && is_readable($conf->societe->dir_logos.'/thumbs/'.$mysoc->logo_small))
{
	$urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode('thumbs/'.$mysoc->logo_small);
}
elseif (! empty($mysoc->logo_small) && is_readable($conf->societe->dir_logos.'/'.$mysoc->logo))
{
	$urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode($mysoc->logo);
	$width=96;
}
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
			dolibarr_print_error('','ErrorNoPriceDefinedForThisProduct');
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
	//	$text.=dolibarr_print_date($contractline->date_fin_validite);
	//}
	if ($contractline->date_fin_validite)
	{
		//$dateactend = dol_time_plus_duree ($contractline->date_fin_validite, $product->duration_value, $product->duration_unit);
		//print ', '.$langs->trans("DateEndPlanned").': '.dolibarr_print_date($contractline->date_fin_validite);
		$text.='<br>'.$langs->trans("ExpiredSince").': '.dolibarr_print_date($contractline->date_fin_validite);
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


if (! $found && ! $mesg) $mesg=$langs->trans("ErrorBadParameters");

if ($mesg) print '<tr><td align="center" colspan="2"><br><div class="warning">'.$mesg.'</div></td></tr>';

if ($found)
{
	print '<tr><td align="center" colspan="2"><br><input class="none" type="submit" name="dopayment" value="'.$langs->trans("PayBoxDoPayment").'"></td></tr>';
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
