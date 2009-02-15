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
 *	\file			htdocs/paybox/paybox.lib.php
 *  \brief			Library for common paybox functions
 *  \version		$Id$
 */


function llxHeaderPaybox($title, $head = "")
{
	global $user, $conf, $langs;

	// Si feuille de style en php existe
	if (file_exists(DOL_DOCUMENT_ROOT.'/'.$conf->css.".php")) $conf->css.=".php";

	header("Content-type: text/html; charset=".$conf->character_set_client);

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
	if ($conf->global->PAYBOX_CSS_URL) print '<link rel="stylesheet" type="text/css" href="'.$conf->global->PAYBOX_CSS_URL.'">'."\n";
	else
	{
		print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/'.$conf->css.'">'."\n";
		print '<style type="text/css">';
		print '.CTableRow1      { margin: 1px; padding: 3px; font: 12px verdana,arial; background: #e6E6eE; color: #000000; -moz-border-radius-topleft:6px; -moz-border-radius-topright:6px; -moz-border-radius-bottomleft:6px; -moz-border-radius-bottomright:6px;}';
		print '.CTableRow2      { margin: 1px; padding: 3px; font: 12px verdana,arial; background: #FFFFFF; color: #000000; -moz-border-radius-topleft:6px; -moz-border-radius-topright:6px; -moz-border-radius-bottomleft:6px; -moz-border-radius-bottomright:6px;}';
		print '</style>';
	}
	print "</head>\n";
	print '<body style="margin: 20px;">'."\n";
}

function llxFooterPayBox()
{
	print "</body>\n";
	print "</html>\n";
}


/**
 *		\brief  	Create a redirect form to paybox form
 *		\return 	int				1 if OK, -1 if ERROR
 */
function print_paybox_redirect($PRICE,$EMAIL,$urlok,$urlko,$TAG,$ID=0)
{
	global $conf, $langs, $db;

	dol_syslog("Paypal.lib::print_paybox_redirect", LOG_DEBUG);

	// Clean parameters
	$IBS_SITE="1999888";    # Site test
	if ($conf->global->PAYBOX_IBS_SITE) $IBS_SITE=$conf->global->PAYBOX_IBS_SITE;
	$IBS_RANG="99";         # Rang test
	if ($conf->global->PAYBOX_IBS_RANG) $IBS_RANG=$conf->global->PAYBOX_IBS_RANG;
	$IBS_DEVISE="978";			# Euro
	if ($conf->global->PAYBOX_IBS_DEVISE) $IBS_DEVISE=$conf->global->PAYBOX_IBS_DEVISE;


	$ModulePaybox="module_linux.cgi";
    if ($_SERVER["WINDIR"] && eregi("windows",$_SERVER["WINDIR"])) { $ModulePaybox="module_NT_2000.cgi"; }
	$URLPAYBOX=URL_ROOT.'/cgi-bin/'.$ModulePaybox;
	if ($conf->global->PAYBOX_CGI_URL) $URLPAYBOX=$conf->global->PAYBOX_CGI_URL;

	if (empty($IBS_DEVISE))
	{
		dol_print_error('',"Paybox setup param PAYBOX_IBS_DEVISE not defined");
		return -1;
	}
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


    dol_syslog("Paypal.lib::print_paybox_redirect PRICE: ".$PRICE, LOG_DEBUG);

    $langsiso=new Translate('',$conf);
    $langsiso=$langs;
    $langsiso->charset_output='ISO-8859-1';

	// Definition des parametres vente produit pour paybox
    $IBS_CMD=$TAG;
    $IBS_TOTAL=$PRICE*100;     	# En centimes
    $IBS_MODE=1;            	# Mode formulaire
    $IBS_PORTEUR=$EMAIL;
	$IBS_RETOUR="montant:M;ref:R;auto:A;trans:T";   # Format des parametres du get de validation en reponse (url a definir sous paybox)
    $IBS_TXT="<center><b>".$langsiso->trans("YouWillBeRedirectedOnPayBox")."</b><br><i>".$langsiso->trans("PleaseBePatient")."...</i><br></center>";
    $IBS_EFFECTUE=$urlok;
    $IBS_ANNULE=$urlko;
    $IBS_REFUSE=$urlko;
    $IBS_BOUTPI=$langsiso->trans("Continue");
    $IBS_BKGD="#FFFFFF";
    $IBS_WAIT="4000";
	$IBS_LANG="ENG"; if (eregi('^FR',$langs->defaultlang)) $IBS_LANG="FRA";

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

    header("Content-type: text/html; charset=".$conf->character_set_client);

    print '<html>'."\n";
    print '<head>'."\n";
    print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$conf->character_set_client."\">\n";
    print '</head>'."\n";
    print '<body>'."\n";
    print "\n";

    // Formulaire pour module Paybox v1 (IBS_xxx)
    print '<form action="'.$URLPAYBOX.'" NAME="Submit" method="POST">'."\n";
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
    print '</form>'."\n";

    // Formulaire pour module Paybox v2 (PBX_xxx)


    print "\n";
    print '<script type="text/javascript" language="javascript">'."\n";
//    print '	document.Submit.submit();'."\n";
    print '</script>'."\n";
    print "\n";
    print '</body></html>'."\n";
    print "\n";

	return;
}


/**
 * Show footer of company in HTML pages
 *
 * @param unknown_type $fromcompany
 * @param unknown_type $langs
 */
function html_print_footer($fromcompany,$langs)
{
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
	if ($fromcompany->profid1 && ($fromcompany->pays_code != 'FR' || ! $fromcompany->profid2))
	{
		$field=$langs->transcountrynoentities("ProfId1",$fromcompany->pays_code);
		if (eregi('\((.*)\)',$field,$reg)) $field=$reg[1];
		$ligne1.=($ligne1?" - ":"").$field.": ".$langs->convToOutputCharset($fromcompany->profid1);
	}
	// Prof Id 2
	if ($fromcompany->profid2)
	{
		$field=$langs->transcountrynoentities("ProfId2",$fromcompany->pays_code);
		if (eregi('\((.*)\)',$field,$reg)) $field=$reg[1];
		$ligne1.=($ligne1?" - ":"").$field.": ".$langs->convToOutputCharset($fromcompany->profid2);
	}

	// Second line of company infos
	$ligne2="";
	// Prof Id 3
	if ($fromcompany->profid3)
	{
		$field=$langs->transcountrynoentities("ProfId3",$fromcompany->pays_code);
		if (eregi('\((.*)\)',$field,$reg)) $field=$reg[1];
		$ligne2.=($ligne2?" - ":"").$field.": ".$langs->convToOutputCharset($fromcompany->profid3);
	}
	// Prof Id 4
	if ($fromcompany->profid4)
	{
		$field=$langs->transcountrynoentities("ProfId4",$fromcompany->pays_code);
		if (eregi('\((.*)\)',$field,$reg)) $field=$reg[1];
		$ligne2.=($ligne2?" - ":"").$field.": ".$langs->convToOutputCharset($fromcompany->profid4);
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