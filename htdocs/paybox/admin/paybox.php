<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.org>
 * Copyright (C) 2011-2012 Juanjo Menent		<jmenent@2byte.es>
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
 * \file       htdocs/paybox/admin/paybox.php
 * \ingroup    paybox
 * \brief      Page to setup paybox module
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

$servicename='PayBox';

$langs->load("admin");
$langs->load("other");
$langs->load("paybox");

if (!$user->admin)
  accessforbidden();
  
$action = GETPOST('action','alpha');


if ($action == 'setvalue' && $user->admin)
{
	$db->begin();
	//$result=dolibarr_set_const($db, "PAYBOX_IBS_DEVISE",$_POST["PAYBOX_IBS_DEVISE"],'chaine',0,'',$conf->entity);
	$result=dolibarr_set_const($db, "PAYBOX_CGI_URL_V1", GETPOST('PAYBOX_CGI_URL_V1','alpha'),'chaine',0,'',$conf->entity);
	if (! $result > 0) $error++;
	$result=dolibarr_set_const($db, "PAYBOX_CGI_URL_V2",GETPOST('PAYBOX_CGI_URL_V2','alpha'),'chaine',0,'',$conf->entity);
	if (! $result > 0) $error++;
	$result=dolibarr_set_const($db, "PAYBOX_IBS_SITE",GETPOST('PAYBOX_IBS_SITE','alpha'),'chaine',0,'',$conf->entity);
	if (! $result > 0) $error++;
	$result=dolibarr_set_const($db, "PAYBOX_IBS_RANG",GETPOST('PAYBOX_IBS_RANG','alpha'),'chaine',0,'',$conf->entity);
	if (! $result > 0) $error++;
	$result=dolibarr_set_const($db, "PAYBOX_PBX_IDENTIFIANT",GETPOST('PAYBOX_PBX_IDENTIFIANT','alpha'),'chaine',0,'',$conf->entity);
	if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "PAYBOX_CREDITOR",GETPOST('PAYBOX_CREDITOR','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
	$result=dolibarr_set_const($db, "PAYBOX_CSS_URL",GETPOST('PAYBOX_CSS_URL','alpha'),'chaine',0,'',$conf->entity);
	if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "PAYBOX_MESSAGE_OK",GETPOST('PAYBOX_MESSAGE_OK','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "PAYBOX_MESSAGE_KO",GETPOST('PAYBOX_MESSAGE_KO','alpha'),'chaine',0,'',$conf->entity);
	if (! $result > 0) $error++;
	$result=dolibarr_set_const($db, "PAYBOX_PAYONLINE_SENDEMAIL",GETPOST('PAYBOX_PAYONLINE_SENDEMAIL'),'chaine',0,'',$conf->entity);
	if (! $result > 0) $error++;
	
    if (! $error)
  	{
  		$db->commit();
	    setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
  	}
  	else
  	{
  		$db->rollback();
		dol_print_error($db);
    }
}


/*
 *	View
 */

$IBS_SITE="1999888";    // Site test
if (empty($conf->global->PAYBOX_IBS_SITE)) $conf->global->PAYBOX_IBS_SITE=$IBS_SITE;
$IBS_RANG="99";         // Rang test
if (empty($conf->global->PAYBOX_IBS_RANG)) $conf->global->PAYBOX_IBS_RANG=$IBS_RANG;
$IBS_DEVISE="978";      // Euro
if (empty($conf->global->PAYBOX_IBS_DEVISE)) $conf->global->PAYBOX_IBS_DEVISE=$IBS_DEVISE;

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("PayBoxSetup"),$linkback,'title_setup');

print $langs->trans("PayBoxDesc")."<br>\n";

print '<br>';
print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvalue">';

dol_fiche_head(null, 'payboxaccount', '');

$var=true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("AccountParameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print '<span class="fieldrequired">'.$langs->trans("PAYBOX_PBX_SITE").'</span></td><td>';
print '<input size="32" type="text" name="PAYBOX_IBS_SITE" value="'.$conf->global->PAYBOX_IBS_SITE.'">';
print '<br>'.$langs->trans("Example").': 1999888 ('.$langs->trans("Test").')';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print '<span class="fieldrequired">'.$langs->trans("PAYBOX_PBX_RANG").'</span></td><td>';
print '<input size="32" type="text" name="PAYBOX_IBS_RANG" value="'.$conf->global->PAYBOX_IBS_RANG.'">';
print '<br>'.$langs->trans("Example").': 99 ('.$langs->trans("Test").')';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print '<span class="fieldrequired">'.$langs->trans("PAYBOX_PBX_IDENTIFIANT").'</span></td><td>';
print '<input size="32" type="text" name="PAYBOX_PBX_IDENTIFIANT" value="'.$conf->global->PAYBOX_PBX_IDENTIFIANT.'">';
print '<br>'.$langs->trans("Example").': 2 ('.$langs->trans("Test").')';
print '</td></tr>';

$var=true;
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("UsageParameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

/*
$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("PAYBOX_IBS_DEVISE").'</td><td>';
print '<input size="32" type="text" name="PAYBOX_IBS_DEVISE" value="'.$conf->global->PAYBOX_IBS_DEVISE.'">';
print '<br>'.$langs->trans("Example").': 978 (EUR)';
print '</td></tr>';
*/

/*
$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("PAYBOX_CGI_URL_V1").'</td><td>';
print '<input size="64" type="text" name="PAYBOX_CGI_URL_V1" value="'.$conf->global->PAYBOX_CGI_URL_V1.'">';
print '<br>'.$langs->trans("Example").': http://mysite/cgi-bin/module_linux.cgi';
print '</td></tr>';
*/

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print '<span class="fieldrequired">'.$langs->trans("PAYBOX_CGI_URL_V2").'</span></td><td>';
print '<input size="64" type="text" name="PAYBOX_CGI_URL_V2" value="'.$conf->global->PAYBOX_CGI_URL_V2.'">';
print '<br>'.$langs->trans("Example").': http://mysite/cgi-bin/modulev2_redhat72.cgi';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("VendorName").'</td><td>';
print '<input size="64" type="text" name="PAYBOX_CREDITOR" value="'.$conf->global->PAYBOX_CREDITOR.'">';
print '<br>'.$langs->trans("Example").': '.$mysoc->name;
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("CSSUrlForPaymentForm").'</td><td>';
print '<input size="64" type="text" name="PAYBOX_CSS_URL" value="'.$conf->global->PAYBOX_CSS_URL.'">';
print '<br>'.$langs->trans("Example").': http://mysite/mycss.css';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("MessageOK").'</td><td>';
$doleditor=new DolEditor('PAYBOX_MESSAGE_OK',$conf->global->PAYBOX_MESSAGE_OK,'',100,'dolibarr_details','In',false,true,true,ROWS_2,'90%');
$doleditor->Create();
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("MessageKO").'</td><td>';
$doleditor=new DolEditor('PAYBOX_MESSAGE_KO',$conf->global->PAYBOX_MESSAGE_KO,'',100,'dolibarr_details','In',false,true,true,ROWS_2,'90%');
$doleditor->Create();
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("PAYBOX_PAYONLINE_SENDEMAIL").'</td><td>';
print '<input size="32" type="email" name="PAYBOX_PAYONLINE_SENDEMAIL" value="'.$conf->global->PAYBOX_PAYONLINE_SENDEMAIL.'">';
print ' &nbsp; '.$langs->trans("Example").': myemail@myserver.com';
print '</td></tr>';

print '</table>';

dol_fiche_end();

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></div>';

print '</form>';

print '<br><br>';

print '<u>'.$langs->trans("FollowingUrlAreAvailableToMakePayments").':</u><br>';
print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnFreeAmount",$servicename).':<br>';
print '<b>'.DOL_MAIN_URL_ROOT.'/public/paybox/newpayment.php?amount=<i>9.99</i>&tag=<i>your_free_tag</i></b>'."<br>\n";
if (! empty($conf->commande->enabled))
{
	print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnOrder",$servicename).':<br>';
	print '<b>'.DOL_MAIN_URL_ROOT.'/public/paybox/newpayment.php?source=order&ref=<i>order_ref</i></b>'."<br>\n";
}
if (! empty($conf->facture->enabled))
{
	print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnInvoice",$servicename).':<br>';
	print '<b>'.DOL_MAIN_URL_ROOT.'/public/paybox/newpayment.php?source=invoice&ref=<i>invoice_ref</i></b>'."<br>\n";
//	print $langs->trans("SetupPayBoxToHavePaymentCreatedAutomatically",$langs->transnoentitiesnoconv("FeatureNotYetAvailable"))."<br>\n";
}
if (! empty($conf->contrat->enabled))
{
	print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnContractLine",$servicename).':<br>';
	print '<b>'.DOL_MAIN_URL_ROOT.'/public/paybox/newpayment.php?source=contractline&ref=<i>contractline_ref</i></b>'."<br>\n";
}
if (! empty($conf->adherent->enabled))
{
	print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnMemberSubscription",$servicename).':<br>';
	print '<b>'.DOL_MAIN_URL_ROOT.'/public/paybox/newpayment.php?source=membersubscription&ref=<i>member_ref</i></b>'."<br>\n";
}

print "<br>";
print info_admin($langs->trans("YouCanAddTagOnUrl"));

llxFooter();

$db->close();
