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



/*
 * Actions
 */
if ($_REQUEST["action"] == 'dopayment')
{
	$PRICE=$_REQUEST["newamount"];
	$EMAIL=$_REQUEST["EMAIL"];
	$urlok='';
	$urlko='';
	$DOLSTRING=$_REQUEST["tag"];
	$ID=$_REQUEST["id"];

	$mesg='';
	if (empty($PRICE))     $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Amount"));
	elseif (empty($EMAIL))     $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("YourEMail"));
	elseif (! ValidEMail($EMAIL))     $mesg=$langs->trans("ErrorBadEMail",$EMAIL);
	elseif (empty($DOLSTRING)) $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("PaymentCode"));

	if (empty($mesg))
	{
		print_paybox_redirect($PRICE, $EMAIL, $urlok, $urlko, $DOLSTRING, $ID);
		exit;
	}
}



/*
 * View
 */

llxHeaderPayBox($langs->trans("PaymentForm"));


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

print '<tr><td align="left"><br>'.$langs->trans("ThisScreenAllowsYouToPay",$mysoc->nom).'<br><br></td></tr>'."\n";

print '<tr><td align="center">';
print '<table with="100%">';
print '<tr class="liste_total"><td align="left" colspan="2">'.$langs->trans("ThisIsInformationOnPayment").' :</td></tr>'."\n";

$found=false;
$var=false;
if (is_numeric($_REQUEST["amount"]))
{
	$found=true;

	// Currency
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Creditor");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$mysoc->nom.'</b></td></tr>'."\n";
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
	print '</td></tr>'."\n";
	// Currency
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Currency");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>EUR</b></td></tr>'."\n";
	// Tag
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$_REQUEST["tag"].'</b></td></tr>'."\n";
	// EMail
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("YourEMail");
	print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'"><input class="flat" type="text" name="EMAIL" size="48" value="'.$_REQUEST["EMAIL"].'"></td></tr>'."\n";

}



if (! $found) $mesg=$langs->trans("ErrorBadParameters");

if ($mesg) print '<tr><td align="center" colspan="2"><br><div class="warning">'.$mesg.'</div></td></tr>';

print '<tr><td align="center" colspan="2"><br><input class="none" type="submit" name="dopayment" value="'.$langs->trans("PayBoxDoPayment").'"></td></tr>';
print '<tr><td align="center" colspan="2">'.$langs->trans("YouWillBeRedirectedOnPayBox").'...</td></tr>';


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
