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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 */

/**
 *	\file			htdocs/lib/admin.lib.php
 *  \brief			Library of admin functions
 *  \version		$Id$
 */


/**
 *		\brief  	Create a redirect form to paybox form
 *		\return 	int				1 if OK, -1 if ERROR
 */
function print_paybox_redirect($PRICE,$EMAIL,$urlok,$urlko="",$ID=0)
{
	global $conf, $langs, $db;

	dol_syslog("Paypal.lib::print_paybox_redirect", LOG_DEBUG);


	$IBS_DEVISE="978";			# Euro

    $ModulePaybox="module_linux.cgi";
    if ($_SERVER["WINDIR"] && eregi("windows",$_SERVER["WINDIR"])) { $ModulePaybox="module_NT_2000.cgi"; }
	$URLPAYBOX=URL_ROOT.'/cgi-bin/'.$ModulePaybox;
	if ($conf->global->PAYBOX_CGI_URL) $URLPAYBOX=$conf->global->PAYBOX_CGI_URL;
	$IBS_SITE=$conf->global->PAYBOX_IBS_SITE;
	$IBS_RANG=$conf->global->PAYBOX_IBS_RANG;

	if (empty($URLPAYBOX))
	{
		dol_print_error('',"Paybox setup param PAYBOX_CGI_URL not defined");
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

	// Value to use for test
	$IBS_SITE="1999888";    # Site test
	$IBS_RANG="99";         # Rang test


    dol_syslog("Paypal.lib::print_paybox_redirect PRICE: ".$PRICE, LOG_DEBUG);

	// Definition des parametres vente produit pour paybox
    $IBS_CMD="DOL:SITE=dolibarr-ID=".$ID;
    $IBS_TOTAL=$PRICE*100;     	# En centimes

    $IBS_MODE=1;            	# Mode formulaire

    $IBS_PORTEUR=$EMAIL;
	$IBS_RETOUR="montant:M;ref:R;auto:A;trans:T";   # Format des paramètres du get de validation en reponse (url a definir sous paybox)
    $IBS_TXT="<center><b>Vous allez être envoyé vers la page de paiement sécurisé Paybox</b><br><i>Merci de patienter quelques secondes...</i><br></center>";
    $IBS_EFFECTUE=$urlok;
    $IBS_ANNULE=$urlko;
    $IBS_REFUSE=$urlko;
    $IBS_BOUTPI="Continuer";
    $IBS_BKGD="#FFFFFF";
    $IBS_WAIT="4000";
    $IBS_LANG="FRA";

    dol_syslog("Soumission Paybox");
    dol_syslog("IBS_MODE: $IBS_MODE");
    dol_syslog("IBS_SITE: $IBS_SITE");
    dol_syslog("IBS_RANG: $IBS_RANG");
    dol_syslog("IBS_TOTAL: $IBS_TOTAL");
    dol_syslog("IBS_DEVISE: $IBS_DEVISE");
    dol_syslog("IBS_CMD: $IBS_CMD");
    dol_syslog("IBS_PORTEUR: $IBS_PORTEUR");
    dol_syslog("IBS_RETOUR: $IBS_RETOUR");
    dol_syslog("IBS_EFFECTUE: $IBS_EFFECTUE");
    dol_syslog("IBS_ANNULE: $IBS_ANNULE");
    dol_syslog("IBS_REFUSE: $IBS_REFUSE");
    dol_syslog("IBS_BKGD: $IBS_BKGD");
    dol_syslog("IBS_WAIT: $IBS_WAIT");
    dol_syslog("IBS_LANG: $IBS_LANG");

    print '<html><body>';
    print "\n";
    print '<form action="'.$URLPAYBOX.'" NAME="Submit" method="POST">';
    print '<input type="hidden" name="IBS_MODE" value="'.$IBS_MODE.'">';
    print '<input type="hidden" name="IBS_SITE" value="'.$IBS_SITE.'">';
    print '<input type="hidden" name="IBS_RANG" value="'.$IBS_RANG.'">';
    print '<input type="hidden" name="IBS_TOTAL" value="'.$IBS_TOTAL.'">';
    print '<input type="hidden" name="IBS_DEVISE" value="'.$IBS_DEVISE.'">';
    print '<input type="hidden" name="IBS_CMD" value="'.$IBS_CMD.'">';
    print '<input type="hidden" name="IBS_PORTEUR" value="'.$IBS_PORTEUR.'">';
    print '<input type="hidden" name="IBS_RETOUR" value="'.$IBS_RETOUR.'">';
    print '<input type="hidden" name="IBS_EFFECTUE" value="'.$IBS_EFFECTUE.'">';
    print '<input type="hidden" name="IBS_ANNULE" value="'.$IBS_ANNULE.'">';
    print '<input type="hidden" name="IBS_REFUSE" value="'.$IBS_REFUSE.'">';
    print '<input type="hidden" name="IBS_TXT" value="'.$IBS_TXT.'">';
    print '<input type="hidden" name="IBS_BKGD" value="'.$IBS_BKGD.'">';
    print '<input type="hidden" name="IBS_WAIT" value="'.$IBS_WAIT.'">';
    print '<input type="hidden" name="IBS_LANG" value="'.$IBS_LANG.'">';
    print '</form>';
    print "\n";
    print '<script type="text/javascript" language="javascript">';
    print '	document.Submit.submit();';
    print '</script>';
    print "\n";
    print '</body></html>';
    print "\n";

	return;
}


?>