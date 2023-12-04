<?php
/* Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@inodbox.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file			htdocs/paybox/lib/paybox.lib.php
 *	\ingroup		paybox
 *  \brief			Library for common paybox functions
 */




/**
 * Create a redirect form to paybox form
 *
 * @param	int   	$PRICE		Price
 * @param   string	$CURRENCY	Currency
 * @param   string	$EMAIL		EMail
 * @param   string	$urlok		Url to go back if payment is OK
 * @param   string	$urlko		Url to go back if payment is KO
 * @param   string	$TAG		Full tag
 * @return  int              	1 if OK, -1 if ERROR
 */
function print_paybox_redirect($PRICE, $CURRENCY, $EMAIL, $urlok, $urlko, $TAG)
{
	global $conf, $langs, $db;

	dol_syslog("Paybox.lib::print_paybox_redirect", LOG_DEBUG);

	// Clean parameters
	$PBX_IDENTIFIANT = "2"; // Identifiant pour v2 test
	if (getDolGlobalString('PAYBOX_PBX_IDENTIFIANT')) {
		$PBX_IDENTIFIANT = $conf->global->PAYBOX_PBX_IDENTIFIANT;
	}
	$IBS_SITE = "1999888"; // Site test
	if (getDolGlobalString('PAYBOX_IBS_SITE')) {
		$IBS_SITE = $conf->global->PAYBOX_IBS_SITE;
	}
	$IBS_RANG = "99"; // Rang test
	if (getDolGlobalString('PAYBOX_IBS_RANG')) {
		$IBS_RANG = $conf->global->PAYBOX_IBS_RANG;
	}
	$IBS_DEVISE = "840"; // Currency (Dollar US by default)
	if ($CURRENCY == 'EUR') {
		$IBS_DEVISE = "978";
	}
	if ($CURRENCY == 'USD') {
		$IBS_DEVISE = "840";
	}

	$URLPAYBOX = "";
	if ($conf->global->PAYBOX_CGI_URL_V1) {
		$URLPAYBOX = $conf->global->PAYBOX_CGI_URL_V1;
	}
	if ($conf->global->PAYBOX_CGI_URL_V2) {
		$URLPAYBOX = $conf->global->PAYBOX_CGI_URL_V2;
	}

	if (empty($IBS_DEVISE)) {
		dol_print_error('', "Paybox setup param PAYBOX_IBS_DEVISE not defined");
		return -1;
	}
	if (empty($URLPAYBOX)) {
		dol_print_error('', "Paybox setup param PAYBOX_CGI_URL_V1 and PAYBOX_CGI_URL_V2 undefined");
		return -1;
	}
	if (empty($IBS_SITE)) {
		dol_print_error('', "Paybox setup param PAYBOX_IBS_SITE not defined");
		return -1;
	}
	if (empty($IBS_RANG)) {
		dol_print_error('', "Paybox setup param PAYBOX_IBS_RANG not defined");
		return -1;
	}

	$conf->global->PAYBOX_HASH = 'sha512';

	// Definition des parametres vente produit pour paybox
	$IBS_CMD = $TAG;
	$IBS_TOTAL = $PRICE * 100; // En centimes
	$IBS_MODE = 1; // Mode formulaire
	$IBS_PORTEUR = $EMAIL;
	$IBS_RETOUR = "montant:M;ref:R;auto:A;trans:T"; // Format des parametres du get de validation en reponse (url a definir sous paybox)
	$IBS_TXT = ' '; // Use a space
	$IBS_EFFECTUE = $urlok;
	$IBS_ANNULE = $urlko;
	$IBS_REFUSE = $urlko;
	$IBS_BKGD = "#FFFFFF";
	$IBS_WAIT = "2000";
	$IBS_LANG = "GBR"; // By default GBR=english (FRA, GBR, ESP, ITA et DEU...)
	if (preg_match('/^FR/i', $langs->defaultlang)) {
		$IBS_LANG = "FRA";
	}
	if (preg_match('/^ES/i', $langs->defaultlang)) {
		$IBS_LANG = "ESP";
	}
	if (preg_match('/^IT/i', $langs->defaultlang)) {
		$IBS_LANG = "ITA";
	}
	if (preg_match('/^DE/i', $langs->defaultlang)) {
		$IBS_LANG = "DEU";
	}
	if (preg_match('/^NL/i', $langs->defaultlang)) {
		$IBS_LANG = "NLD";
	}
	if (preg_match('/^SE/i', $langs->defaultlang)) {
		$IBS_LANG = "SWE";
	}
	$IBS_OUTPUT = 'E';
	$PBX_SOURCE = 'HTML';
	$PBX_TYPEPAIEMENT = 'CARTE';
	$PBX_HASH = $conf->global->PAYBOX_HASH;
	$PBX_TIME = dol_print_date(dol_now(), 'dayhourrfc', 'gmt');

	$msg = "PBX_IDENTIFIANT=".$PBX_IDENTIFIANT.
		   "&PBX_MODE=".$IBS_MODE.
		   "&PBX_SITE=".$IBS_SITE.
		   "&PBX_RANG=".$IBS_RANG.
		   "&PBX_TOTAL=".$IBS_TOTAL.
		   "&PBX_DEVISE=".$IBS_DEVISE.
		   "&PBX_CMD=".$IBS_CMD.
		   "&PBX_PORTEUR=".$IBS_PORTEUR.
		   "&PBX_RETOUR=".$IBS_RETOUR.
		   "&PBX_EFFECTUE=".$IBS_EFFECTUE.
		   "&PBX_ANNULE=".$IBS_ANNULE.
		   "&PBX_REFUSE=".$IBS_REFUSE.
		   "&PBX_TXT=".$IBS_TXT.
		   "&PBX_BKGD=".$IBS_BKGD.
		   "&PBX_WAIT=".$IBS_WAIT.
		   "&PBX_LANGUE=".$IBS_LANG.
		   "&PBX_OUTPUT=".$IBS_OUTPUT.
		   "&PBX_SOURCE=".$PBX_SOURCE.
		   "&PBX_TYPEPAIEMENT=".$PBX_TYPEPAIEMENT;
	"&PBX_HASH=".$PBX_HASH;
	"&PBX_TIME=".$PBX_TIME;

	$binKey = pack("H*", dol_decode($conf->global->PAYBOX_HMAC_KEY));

	$hmac = strtoupper(hash_hmac($PBX_HASH, $msg, $binKey));


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
	dol_syslog("PBX_HASH: $PBX_HASH", LOG_DEBUG);
	dol_syslog("PBX_TIME: $PBX_TIME", LOG_DEBUG);

	top_httphead();

	print '<html>'."\n";
	print '<head>'."\n";
	print '</head>'."\n";
	print '<body>'."\n";
	print "\n";

	// Formulaire pour module Paybox
	print '<form action="'.$URLPAYBOX.'" NAME="Submit" method="POST">'."\n";

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
	print '<input type="hidden" name="PBX_HASH" value="'.$PBX_HASH.'">'."\n";
	print '<input type="hidden" name="PBX_TIME" value="'.$PBX_TIME.'">'."\n";
	// Footprint of parameters
	print '<input type="hidden" name="PBX_HMAC" value="'.$hmac.'">'."\n";
	print '</form>'."\n";


	print "\n";
	print '<script type="text/javascript">'."\n";
	print '	document.Submit.submit();'."\n";
	print '</script>'."\n";
	print "\n";
	print '</body></html>'."\n";
	print "\n";

	return 1;
}
