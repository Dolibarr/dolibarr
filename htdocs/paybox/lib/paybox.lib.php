<?php
/* Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file			htdocs/paybox/lib/paybox.lib.php
 *	\ingroup		paybox
 *  \brief			Library for common paybox functions
 */



/**
 * Show header
 *
 * @param 	string	$title		Title of page
 * @param 	string	$head		Head string to add int head section
 * @return	void
 */
function llxHeaderPaybox($title, $head = "")
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
	if (! empty($conf->global->PAYBOX_CSS_URL)) print '<link rel="stylesheet" type="text/css" href="'.$conf->global->PAYBOX_CSS_URL.'?lang='.$langs->defaultlang.'">'."\n";
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

/**
 * Show footer
 *
 * @return	void
 */
function llxFooterPayBox()
{
	print "</body>\n";
	print "</html>\n";
}


/**
 * Create a redirect form to paybox form
 *
 * @param	int   	$PRICE		Price
 * @param   string	$CURRENCY	Currency
 * @param   string	$EMAIL		EMail
 * @param   string	$urlok		Url to go back if payment is OK
 * @param   string	$urlko		Url to go back if payment is KO
 * @param   string	$TAG		Tag
 * @return  int              	1 if OK, -1 if ERROR
 */
function print_paybox_redirect($PRICE,$CURRENCY,$EMAIL,$urlok,$urlko,$TAG)
{
	global $conf, $langs, $db;

	dol_syslog("Paybox.lib::print_paybox_redirect", LOG_DEBUG);

	// Clean parameters
	$PBX_IDENTIFIANT="2";	// Identifiant pour v2 test
	if (! empty($conf->global->PAYBOX_PBX_IDENTIFIANT)) $PBX_IDENTIFIANT=$conf->global->PAYBOX_PBX_IDENTIFIANT;
	$IBS_SITE="1999888";    // Site test
	if (! empty($conf->global->PAYBOX_IBS_SITE)) $IBS_SITE=$conf->global->PAYBOX_IBS_SITE;
	$IBS_RANG="99";         // Rang test
	if (! empty($conf->global->PAYBOX_IBS_RANG)) $IBS_RANG=$conf->global->PAYBOX_IBS_RANG;
	$IBS_DEVISE="840";		// Currency (Dollar US by default)
	if ($CURRENCY == 'EUR') $IBS_DEVISE="978";
	if ($CURRENCY == 'USD') $IBS_DEVISE="840";

	$URLPAYBOX="";
	if ($conf->global->PAYBOX_CGI_URL_V1) $URLPAYBOX=$conf->global->PAYBOX_CGI_URL_V1;
	if ($conf->global->PAYBOX_CGI_URL_V2) $URLPAYBOX=$conf->global->PAYBOX_CGI_URL_V2;

	if (empty($IBS_DEVISE))
	{
		dol_print_error('',"Paybox setup param PAYBOX_IBS_DEVISE not defined");
		return -1;
	}
	if (empty($URLPAYBOX))
	{
		dol_print_error('',"Paybox setup param PAYBOX_CGI_URL_V1 and PAYBOX_CGI_URL_V2 undefined");
		return -1;
	}
	if (empty($IBS_SITE))
	{
		dol_print_error('',"Paybox setup param PAYBOX_IBS_SITE not defined");
		return -1;
	}
	if (empty($IBS_RANG))
	{
		dol_print_error('',"Paybox setup param PAYBOX_IBS_RANG not defined");
		return -1;
	}

	// Definition des parametres vente produit pour paybox
    $IBS_CMD=$TAG;
    $IBS_TOTAL=$PRICE*100;     	// En centimes
    $IBS_MODE=1;            	// Mode formulaire
    $IBS_PORTEUR=$EMAIL;
	$IBS_RETOUR="montant:M;ref:R;auto:A;trans:T";   // Format des parametres du get de validation en reponse (url a definir sous paybox)
    //$IBS_TXT="<center><b>".$langsiso->trans("YouWillBeRedirectedOnPayBox")."</b><br><i>".$langsiso->trans("PleaseBePatient")."...</i><br></center>";
    $IBS_TXT=' ';	// Use a space
    $IBS_BOUTPI=$langs->trans("Wait");
    //$IBS_BOUTPI='';
    $IBS_EFFECTUE=$urlok;
    $IBS_ANNULE=$urlko;
    $IBS_REFUSE=$urlko;
    $IBS_BKGD="#FFFFFF";
    $IBS_WAIT="2000";
	$IBS_LANG="GBR"; 	// By default GBR=english (FRA, GBR, ESP, ITA et DEU...)
	if (preg_match('/^FR/i',$langs->defaultlang)) $IBS_LANG="FRA";
	if (preg_match('/^ES/i',$langs->defaultlang)) $IBS_LANG="ESP";
	if (preg_match('/^IT/i',$langs->defaultlang)) $IBS_LANG="ITA";
	if (preg_match('/^DE/i',$langs->defaultlang)) $IBS_LANG="DEU";
	if (preg_match('/^NL/i',$langs->defaultlang)) $IBS_LANG="NLD";
	if (preg_match('/^SE/i',$langs->defaultlang)) $IBS_LANG="SWE";
	$IBS_OUTPUT='E';
	$PBX_SOURCE='HTML';
	$PBX_TYPEPAIEMENT='CARTE';

    dol_syslog("Soumission Paybox", LOG_DEBUG);
    dol_syslog("IBS_MODE: $IBS_MODE", LOG_DEBUG);
    dol_syslog("IBS_SITE: $IBS_SITE", LOG_DEBUG);
    dol_syslog("IBS_RANG: $IBS_RANG", LOG_DEBUG);
    dol_syslog("IBS_TOTAL: $IBS_TOTAL", LOG_DEBUG);
    dol_syslog("IBS_DEVISE: $IBS_DEVISE", LOG_DEBUG);
    dol_syslog("IBS_CMD: $IBS_CMD", LOG_DEBUG);
    dol_syslog("IBS_PORTEUR: $IBS_PORTEUR", LOG_DEBUG);
    dol_syslog("IBS_RETOUR: $IBS_RETOUR", LOG_DEBUG);
    dol_syslog("IBS_EFFECTUE: $IBS_EFFECTUE", LOG_DEBUG);
    dol_syslog("IBS_ANNULE: $IBS_ANNULE", LOG_DEBUG);
    dol_syslog("IBS_REFUSE: $IBS_REFUSE", LOG_DEBUG);
    dol_syslog("IBS_BKGD: $IBS_BKGD", LOG_DEBUG);
    dol_syslog("IBS_WAIT: $IBS_WAIT", LOG_DEBUG);
    dol_syslog("IBS_LANG: $IBS_LANG", LOG_DEBUG);
    dol_syslog("IBS_OUTPUT: $IBS_OUTPUT", LOG_DEBUG);
    dol_syslog("PBX_IDENTIFIANT: $PBX_IDENTIFIANT", LOG_DEBUG);
    dol_syslog("PBX_SOURCE: $PBX_SOURCE", LOG_DEBUG);
    dol_syslog("PBX_TYPEPAIEMENT: $PBX_TYPEPAIEMENT", LOG_DEBUG);

    header("Content-type: text/html; charset=".$conf->file->character_set_client);

    print '<html>'."\n";
    print '<head>'."\n";
    print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$conf->file->character_set_client."\">\n";
    print '</head>'."\n";
    print '<body>'."\n";
    print "\n";

    // Formulaire pour module Paybox
    print '<form action="'.$URLPAYBOX.'" NAME="Submit" method="POST">'."\n";

    // For Paybox V1 (IBS_xxx)
    /*
    print '<!-- Param for Paybox v1 -->'."\n";
    print '<input type="hidden" name="IBS_MODE" value="'.$IBS_MODE.'">'."\n";
    print '<input type="hidden" name="IBS_SITE" value="'.$IBS_SITE.'">'."\n";
    print '<input type="hidden" name="IBS_RANG" value="'.$IBS_RANG.'">'."\n";
    print '<input type="hidden" name="IBS_TOTAL" value="'.$IBS_TOTAL.'">'."\n";
    print '<input type="hidden" name="IBS_DEVISE" value="'.$IBS_DEVISE.'">'."\n";
    print '<input type="hidden" name="IBS_CMD" value="'.$IBS_CMD.'">'."\n";
    print '<input type="hidden" name="IBS_PORTEUR" value="'.$IBS_PORTEUR.'">'."\n";
    print '<input type="hidden" name="IBS_RETOUR" value="'.$IBS_RETOUR.'">'."\n";
    print '<input type="hidden" name="IBS_EFFECTUE" value="'.$IBS_EFFECTUE.'">'."\n";
    print '<input type="hidden" name="IBS_ANNULE" value="'.$IBS_ANNULE.'">'."\n";
    print '<input type="hidden" name="IBS_REFUSE" value="'.$IBS_REFUSE.'">'."\n";
    print '<input type="hidden" name="IBS_TXT" value="'.$IBS_TXT.'">'."\n";
    print '<input type="hidden" name="IBS_BKGD" value="'.$IBS_BKGD.'">'."\n";
    print '<input type="hidden" name="IBS_WAIT" value="'.$IBS_WAIT.'">'."\n";
    print '<input type="hidden" name="IBS_LANG" value="'.$IBS_LANG.'">'."\n";
    print '<input type="hidden" name="IBS_OUTPUT" value="'.$IBS_OUTPUT.'">'."\n";
	*/

    // For Paybox V2 (PBX_xxx)
    print '<!-- Param for Paybox v2 -->'."\n";
    print '<input type="hidden" name="PBX_IDENTIFIANT" value="'.$PBX_IDENTIFIANT.'">'."\n";
    print '<input type="hidden" name="PBX_MODE" value="'.$IBS_MODE.'">'."\n";
    print '<input type="hidden" name="PBX_SITE" value="'.$IBS_SITE.'">'."\n";
    print '<input type="hidden" name="PBX_RANG" value="'.$IBS_RANG.'">'."\n";
    print '<input type="hidden" name="PBX_TOTAL" value="'.$IBS_TOTAL.'">'."\n";
    print '<input type="hidden" name="PBX_DEVISE" value="'.$IBS_DEVISE.'">'."\n";
    print '<input type="hidden" name="PBX_CMD" value="'.$IBS_CMD.'">'."\n";
    print '<input type="hidden" name="PBX_PORTEUR" value="'.$IBS_PORTEUR.'">'."\n";
    print '<input type="hidden" name="PBX_RETOUR" value="'.$IBS_RETOUR.'">'."\n";
    print '<input type="hidden" name="PBX_EFFECTUE" value="'.$IBS_EFFECTUE.'">'."\n";
    print '<input type="hidden" name="PBX_ANNULE" value="'.$IBS_ANNULE.'">'."\n";
    print '<input type="hidden" name="PBX_REFUSE" value="'.$IBS_REFUSE.'">'."\n";
    print '<input type="hidden" name="PBX_TXT" value="'.$IBS_TXT.'">'."\n";
    print '<input type="hidden" name="PBX_BKGD" value="'.$IBS_BKGD.'">'."\n";
    print '<input type="hidden" name="PBX_WAIT" value="'.$IBS_WAIT.'">'."\n";
    print '<input type="hidden" name="PBX_LANGUE" value="'.$IBS_LANG.'">'."\n";
    print '<input type="hidden" name="PBX_OUTPUT" value="'.$IBS_OUTPUT.'">'."\n";
    print '<input type="hidden" name="PBX_SOURCE" value="'.$PBX_SOURCE.'">'."\n";
    print '<input type="hidden" name="PBX_TYPEPAIEMENT" value="'.$PBX_TYPEPAIEMENT.'">'."\n";

    print '</form>'."\n";

    // Formulaire pour module Paybox v2 (PBX_xxx)


    print "\n";
    print '<script type="text/javascript" language="javascript">'."\n";
    print '	document.Submit.submit();'."\n";
    print '</script>'."\n";
    print "\n";
    print '</body></html>'."\n";
    print "\n";

	return;
}


/**
 * Show footer of company in HTML pages
 *
 * @param   Societe		$fromcompany	Third party
 * @param   Translate	$langs			Output language
 * @return	void
 */
function html_print_paybox_footer($fromcompany,$langs)
{
	global $conf;

	// Juridical status
	$line1="";
	if ($fromcompany->forme_juridique_code)
	{
		$line1.=($line1?" - ":"").getFormeJuridiqueLabel($fromcompany->forme_juridique_code);
	}
	// Capital
	if ($fromcompany->capital)
	{
		$line1.=($line1?" - ":"").$langs->transnoentities("CapitalOf",$fromcompany->capital)." ".$langs->transnoentities("Currency".$conf->currency);
	}
	// Prof Id 1
	if ($fromcompany->idprof1 && ($fromcompany->country_code != 'FR' || ! $fromcompany->idprof2))
	{
		$field=$langs->transcountrynoentities("ProfId1",$fromcompany->country_code);
		if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
		$line1.=($line1?" - ":"").$field.": ".$fromcompany->idprof1;
	}
	// Prof Id 2
	if ($fromcompany->idprof2)
	{
		$field=$langs->transcountrynoentities("ProfId2",$fromcompany->country_code);
		if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
		$line1.=($line1?" - ":"").$field.": ".$fromcompany->idprof2;
	}

	// Second line of company infos
	$line2="";
	// Prof Id 3
	if ($fromcompany->idprof3)
	{
		$field=$langs->transcountrynoentities("ProfId3",$fromcompany->country_code);
		if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
		$line2.=($line2?" - ":"").$field.": ".$fromcompany->idprof3;
	}
	// Prof Id 4
	if ($fromcompany->idprof4)
	{
		$field=$langs->transcountrynoentities("ProfId4",$fromcompany->country_code);
		if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
		$line2.=($line2?" - ":"").$field.": ".$fromcompany->idprof4;
	}
	// IntraCommunautary VAT
	if ($fromcompany->tva_intra != '')
	{
		$line2.=($line2?" - ":"").$langs->transnoentities("VATIntraShort").": ".$fromcompany->tva_intra;
	}

	print '<br><br><hr>'."\n";
	print '<center><font style="font-size: 10px;">'."\n";
	print $fromcompany->nom.'<br>';
	print $line1.'<br>';
	print $line2;
	print '</font></center>'."\n";
}

?>