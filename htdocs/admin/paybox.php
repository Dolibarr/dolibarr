<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.org>
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

/**	    \file       htdocs/admin/aybox.php
 *		\ingroup    bookmark
 *		\brief      Page to setup bookmark module
 *		\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");
$langs->load("paybox");

if (!$user->admin)
  accessforbidden();


if ($_POST["action"] == 'setvalue' && $user->admin)
{
	$result=dolibarr_set_const($db, "PAYBOX_IBS_DEVISE",$_POST["PAYBOX_IBS_DEVISE"]);
	$result=dolibarr_set_const($db, "PAYBOX_CGI_URL",$_POST["PAYBOX_CGI_URL"]);
	$result=dolibarr_set_const($db, "PAYBOX_CSS_URL",$_POST["PAYBOX_CSS_URL"]);
	$result=dolibarr_set_const($db, "PAYBOX_IBS_SITE",$_POST["PAYBOX_IBS_SITE"]);
	$result=dolibarr_set_const($db, "PAYBOX_IBS_RANG",$_POST["PAYBOX_IBS_RANG"]);

	if ($result >= 0)
  	{
  		$mesg='<div class="ok">'.$langs->trans("SetupSaved").'</div>';
  	}
  	else
  	{
		dolibarr_print_error($db);
    }
}


/*
 *	View
 */

$IBS_SITE="1999888";    # Site test
if (empty($conf->global->PAYBOX_IBS_SITE)) $conf->global->PAYBOX_IBS_SITE=$IBS_SITE;
$IBS_RANG="99";         # Rang test
if (empty($conf->global->PAYBOX_IBS_RANG)) $conf->global->PAYBOX_IBS_RANG=$IBS_RANG;
$IBS_DEVISE="978";      # Euro
if (empty($conf->global->PAYBOX_IBS_DEVISE)) $conf->global->PAYBOX_IBS_DEVISE=$IBS_DEVISE;

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("PayBoxSetup"),$linkback,'setup');

print $langs->trans("PayBoxDesc")."<br>\n";


if ($mesg) print '<br>'.$mesg;

print '<br>';
print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="action" value="setvalue">';

$var=true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("PAYBOX_IBS_DEVISE").'</td><td>';
print '<input size="32" type="text" name="PAYBOX_IBS_DEVISE" value="'.$conf->global->PAYBOX_IBS_DEVISE.'">';
print '<br>'.$langs->trans("Example").': 978 (EUR)';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("PAYBOX_CGI_URL").'</td><td>';
print '<input size="64" type="text" name="PAYBOX_CGI_URL" value="'.$conf->global->PAYBOX_CGI_URL.'">';
print '<br>'.$langs->trans("Example").': http://mysite/cgi-bin/module_linux.cgi';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("PAYBOX_CSS_URL").'</td><td>';
print '<input size="64" type="text" name="PAYBOX_CSS_URL" value="'.$conf->global->PAYBOX_CSS_URL.'">';
print '<br>'.$langs->trans("Example").': http://mysite/mycss.css';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("PAYBOX_IBS_SITE").'</td><td>';
print '<input size="32" type="text" name="PAYBOX_IBS_SITE" value="'.$conf->global->PAYBOX_IBS_SITE.'">';
print '<br>'.$langs->trans("Example").': 1999888 ('.$langs->trans("Test").')';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("PAYBOX_IBS_RANG").'</td><td>';
print '<input size="32" type="text" name="PAYBOX_IBS_RANG" value="'.$conf->global->PAYBOX_IBS_RANG.'">';
print '<br>'.$langs->trans("Example").': 99 ('.$langs->trans("Test").')';
print '</td></tr>';

print '<tr><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td></tr>';
print '</table></form>';

print '<br><br>';

print '<u>'.$langs->trans("FollowingUrlAreAvailableToMakePayments").':</u><br>';
// Should work with DOL_URL_ROOT='' or DOL_URL_ROOT='/dolibarr'
$firstpart=$dolibarr_main_url_root;
$regex=DOL_URL_ROOT.'$';
$firstpart=eregi_replace($regex,'',$firstpart);
//print $firstpart.DOL_URL_ROOT.'/public/paybox/newpayment.php?amount=order&ref=<i>orderref</i>&tag=<i>ATAGOFYOURCHOICE</i>'."<br>\n";
//print $firstpart.DOL_URL_ROOT.'/public/paybox/newpayment.php?amount=invoice&ref=<i>invoiceref</i>&tag=<i>ATAGOFYOURCHOICE</i>'."<br>\n";
//print $firstpart.DOL_URL_ROOT.'/public/paybox/newpayment.php?amount=contractline&ref=<i>contractlineref</i>&tag=<i>ATAGOFYOURCHOICE</i>'."<br>\n";
print $firstpart.DOL_URL_ROOT.'/public/paybox/newpayment.php?amount=<i>9.99</i>&tag=<i>ATAGOFYOURCHOICE</i>'."<br>\n";


$db->close();

llxFooter('$Date$ - $Revision$');
?>
