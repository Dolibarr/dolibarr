<?php
/* Copyright (C) 2008-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file			htdocs/paypal/lib/paypal.lib.php
 *  \ingroup		paypal
 *  \brief			Library for common paypal functions
 *  \version		$Id$
 */
function llxHeaderPaypal($title, $head = "")
{
	global $user, $conf, $langs;

	header("Content-type: text/html; charset=".$conf->file->character_set_client);

	print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
	//print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" http://www.w3.org/TR/1999/REC-html401-19991224/strict.dtd>';
	print "\n";
	print "<html>\n";
	print "<head>\n";
	print '<meta name="robots" content="noindex,nofollow">'."\n";
	print '<meta name="keywords" content="dolibarr,payment,online">'."\n";
	print '<meta name="description" content="Welcome on Dolibarr online payment form">'."\n";
	print "<title>".$title."</title>\n";
	if ($head) print $head."\n";
	if ($conf->global->PAYPAL_CSS_URL) print '<link rel="stylesheet" type="text/css" href="'.$conf->global->PAYPAL_CSS_URL.'?lang='.$langs->defaultlang.'">'."\n";
	else
	{
		print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.$conf->css.'?lang='.$langs->defaultlang.'">'."\n";
		print '<style type="text/css">';
		print '.CTableRow1      { margin: 1px; padding: 3px; font: 12px verdana,arial; background: #e6E6eE; color: #000000; -moz-border-radius-topleft:6px; -moz-border-radius-topright:6px; -moz-border-radius-bottomleft:6px; -moz-border-radius-bottomright:6px;}';
		print '.CTableRow2      { margin: 1px; padding: 3px; font: 12px verdana,arial; background: #FFFFFF; color: #000000; -moz-border-radius-topleft:6px; -moz-border-radius-topright:6px; -moz-border-radius-bottomleft:6px; -moz-border-radius-bottomright:6px;}';
		print '</style>';
	}
	print "</head>\n";
	print '<body style="margin: 20px;">'."\n";
}

function llxFooterPaypal()
{
	print "</body>\n";
	print "</html>\n";
}


/**
 *		\brief  	Create a redirect form to paypal form
 *		\return 	int				1 if OK, -1 if ERROR
 */
function print_paypal_redirect($PRICE,$CURRENCY,$EMAIL,$urlok,$urlko,$TAG)
{
	global $conf, $langs, $db;
	global $PAYPAL_API_USER, $PAYPAL_API_PASSWORD, $PAYPAL_API_SIGNATURE;
	global $PAYPAL_API_DEVISE, $PAYPAL_API_OK, $PAYPAL_API_KO;
	global $PAYPAL_API_SANDBOX;

	dol_syslog("Paypal.lib::print_paypal_redirect", LOG_DEBUG);

	// Clean parameters
	$PAYPAL_API_USER="";
	if ($conf->global->PAYPAL_API_USER) $PAYPAL_API_USER=$conf->global->PAYPAL_API_USER;
	$PAYPAL_API_PASSWORD="";
	if ($conf->global->PAYPAL_API_PASSWORD) $PAYPAL_API_PASSWORD=$conf->global->PAYPAL_API_PASSWORD;
	$PAYPAL_API_SIGNATURE="";
	if ($conf->global->PAYPAL_API_SIGNATURE) $PAYPAL_API_SIGNATURE=$conf->global->PAYPAL_API_SIGNATURE;
	$PAYPAL_API_SANDBOX="";
	if ($conf->global->PAYPAL_API_SANDBOX) $PAYPAL_API_SANDBOX=$conf->global->PAYPAL_API_SANDBOX;

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

	// Other
	$PAYPAL_API_DEVISE="EUR";
	if ($CURRENCY == 'EUR') $PAYPAL_API_DEVISE="EUR";
	if ($CURRENCY == 'USD') $PAYPAL_API_DEVISE="USD";
	$PAYPAL_API_OK=$urlok;
	$PAYPAL_API_KO=$urlko;

    dol_syslog("Soumission Paypal", LOG_DEBUG);
    dol_syslog("PAYPAL_API_USER: $PAYPAL_API_USER", LOG_DEBUG);
    dol_syslog("PAYPAL_API_PASSWORD: $PAYPAL_API_PASSWORD", LOG_DEBUG);
    dol_syslog("PAYPAL_API_SIGNATURE: $PAYPAL_API_SIGNATURE", LOG_DEBUG);
    dol_syslog("PAYPAL_API_DEVISE: $PAYPAL_API_DEVISE", LOG_DEBUG);
    dol_syslog("PAYPAL_API_OK: $PAYPAL_API_OK", LOG_DEBUG);
    dol_syslog("PAYPAL_API_KO: $PAYPAL_API_KO", LOG_DEBUG);
    dol_syslog("PAYPAL_API_SANDBOX: $PAYPAL_API_SANDBOX", LOG_DEBUG);

    header("Content-type: text/html; charset=".$conf->file->character_set_client);

    print '<html>'."\n";
    print '<head>'."\n";
    print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$conf->file->character_set_client."\">\n";
    print '</head>'."\n";
    print '<body>'."\n";
    print "\n";

    $_SESSION["Payment_Amount"]=$PRICE;

    // A redirect is added if API call successfull
    require_once(DOL_DOCUMENT_ROOT."/paypal/expresscheckout.php");

    // Formulaire pour module Paybox
//    print '<form action="'.$URLPAYBOX.'" NAME="Submit" method="POST">'."\n";
//print "
//<form action='".DOL_URL_ROOT."/paypal/expresscheckout.php' METHOD='POST' NAME='Submit'>
//<input type='image' name='submit' src='https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif' border='0' align='top' alt='Check out with PayPal'/>
//</form>";
//    print '</form>'."\n";

//    print "\n";
//    print '<script type="text/javascript" language="javascript">'."\n";
//    print '	document.Submit.submit();'."\n";
//    print '</script>'."\n";
//    print "\n";


    print '</body></html>'."\n";
    print "\n";

	return;
}


/**
 * Show footer of company in HTML pages
 *
 * @param   $fromcompany
 * @param   $langs
 */
function html_print_paypal_footer($fromcompany,$langs)
{
	global $conf;

	// Juridical status
	$ligne1="";
	if ($fromcompany->forme_juridique_code)
	{
		$ligne1.=($ligne1?" - ":"").$langs->convToOutputCharset(getFormeJuridiqueLabel($fromcompany->forme_juridique_code));
	}
	// Capital
	if ($fromcompany->capital)
	{
		$ligne1.=($ligne1?" - ":"").$langs->transnoentities("CapitalOf",$fromcompany->capital)." ".$langs->transnoentities("Currency".$conf->monnaie);
	}
	// Prof Id 1
	if ($fromcompany->idprof1 && ($fromcompany->pays_code != 'FR' || ! $fromcompany->idprof2))
	{
		$field=$langs->transcountrynoentities("ProfId1",$fromcompany->pays_code);
		if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
		$ligne1.=($ligne1?" - ":"").$field.": ".$langs->convToOutputCharset($fromcompany->idprof1);
	}
	// Prof Id 2
	if ($fromcompany->idprof2)
	{
		$field=$langs->transcountrynoentities("ProfId2",$fromcompany->pays_code);
		if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
		$ligne1.=($ligne1?" - ":"").$field.": ".$langs->convToOutputCharset($fromcompany->idprof2);
	}

	// Second line of company infos
	$ligne2="";
	// Prof Id 3
	if ($fromcompany->idprof3)
	{
		$field=$langs->transcountrynoentities("ProfId3",$fromcompany->pays_code);
		if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
		$ligne2.=($ligne2?" - ":"").$field.": ".$langs->convToOutputCharset($fromcompany->idprof3);
	}
	// Prof Id 4
	if ($fromcompany->idprof4)
	{
		$field=$langs->transcountrynoentities("ProfId4",$fromcompany->pays_code);
		if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
		$ligne2.=($ligne2?" - ":"").$field.": ".$langs->convToOutputCharset($fromcompany->idprof4);
	}
	// IntraCommunautary VAT
	if ($fromcompany->tva_intra != '')
	{
		$ligne2.=($ligne2?" - ":"").$langs->transnoentities("VATIntraShort").": ".$langs->convToOutputCharset($fromcompany->tva_intra);
	}

	print '<br><br><hr>'."\n";
	print '<center><font style="font-size: 10px;">'."\n";
	print $fromcompany->nom.'<br>';
	print $ligne1.'<br>';
	print $ligne2;
	print '</font></center>'."\n";
}

?>