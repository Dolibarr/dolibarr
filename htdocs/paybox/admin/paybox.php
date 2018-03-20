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
$langs->load("paypal");

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
    $result=dolibarr_set_const($db, "ONLINE_PAYMENT_CREDITOR",GETPOST('ONLINE_PAYMENT_CREDITOR','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
	$result=dolibarr_set_const($db, "ONLINE_PAYMENT_CSS_URL",GETPOST('ONLINE_PAYMENT_CSS_URL','alpha'),'chaine',0,'',$conf->entity);
	if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "ONLINE_PAYMENT_MESSAGE_FORM",GETPOST('ONLINE_PAYMENT_MESSAGE_FORM','alpha'),'chaine',0,'',$conf->entity);
	if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "ONLINE_PAYMENT_MESSAGE_OK",GETPOST('ONLINE_PAYMENT_MESSAGE_OK','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "ONLINE_PAYMENT_MESSAGE_KO",GETPOST('ONLINE_PAYMENT_MESSAGE_KO','alpha'),'chaine',0,'',$conf->entity);
	if (! $result > 0) $error++;
	$result=dolibarr_set_const($db, "ONLINE_PAYMENT_SENDEMAIL",GETPOST('ONLINE_PAYMENT_SENDEMAIL'),'chaine',0,'',$conf->entity);
	if (! $result > 0) $error++;
	// Payment token for URL
	$result=dolibarr_set_const($db, "PAYMENT_SECURITY_TOKEN",GETPOST('PAYMENT_SECURITY_TOKEN','alpha'),'chaine',0,'',$conf->entity);
	if (! $result > 0) $error++;
	$result=dolibarr_set_const($db, "PAYMENT_SECURITY_TOKEN_UNIQUE",GETPOST('PAYMENT_SECURITY_TOKEN_UNIQUE','alpha'),'chaine',0,'',$conf->entity);
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

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("PayBoxSetup"),$linkback,'title_setup');

$h = 0;
$head = array();

$head[$h][0] = DOL_URL_ROOT."/paybox/admin/paybox.php";
$head[$h][1] = $langs->trans("PayBox");
$head[$h][2] = 'payboxaccount';
$h++;

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvalue">';

dol_fiche_head($head, 'payboxaccount', '', -1);

print $langs->trans("PayBoxDesc")."<br>\n";
print '<br>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("AccountParameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";


print '<tr class="oddeven"><td>';
print '<span class="fieldrequired">'.$langs->trans("PAYBOX_PBX_SITE").'</span></td><td>';
print '<input size="32" type="text" name="PAYBOX_IBS_SITE" value="'.$conf->global->PAYBOX_IBS_SITE.'">';
print '<br>'.$langs->trans("Example").': 1999888 ('.$langs->trans("Test").')';
print '</td></tr>';


print '<tr class="oddeven"><td>';
print '<span class="fieldrequired">'.$langs->trans("PAYBOX_PBX_RANG").'</span></td><td>';
print '<input size="32" type="text" name="PAYBOX_IBS_RANG" value="'.$conf->global->PAYBOX_IBS_RANG.'">';
print '<br>'.$langs->trans("Example").': 99 ('.$langs->trans("Test").')';
print '</td></tr>';


print '<tr class="oddeven"><td>';
print '<span class="fieldrequired">'.$langs->trans("PAYBOX_PBX_IDENTIFIANT").'</span></td><td>';
print '<input size="32" type="text" name="PAYBOX_PBX_IDENTIFIANT" value="'.$conf->global->PAYBOX_PBX_IDENTIFIANT.'">';
print '<br>'.$langs->trans("Example").': 2 ('.$langs->trans("Test").')';
print '</td></tr>';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("UsageParameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

/*

print '<tr class="oddeven"><td>';
print $langs->trans("PAYBOX_IBS_DEVISE").'</td><td>';
print '<input size="32" type="text" name="PAYBOX_IBS_DEVISE" value="'.$conf->global->PAYBOX_IBS_DEVISE.'">';
print '<br>'.$langs->trans("Example").': 978 (EUR)';
print '</td></tr>';
*/

/*

print '<tr class="oddeven"><td>';
print $langs->trans("PAYBOX_CGI_URL_V1").'</td><td>';
print '<input size="64" type="text" name="PAYBOX_CGI_URL_V1" value="'.$conf->global->PAYBOX_CGI_URL_V1.'">';
print '<br>'.$langs->trans("Example").': http://mysite/cgi-bin/module_linux.cgi';
print '</td></tr>';
*/


print '<tr class="oddeven"><td>';
print '<span class="fieldrequired">'.$langs->trans("PAYBOX_CGI_URL_V2").'</span></td><td>';
print '<input size="64" type="text" name="PAYBOX_CGI_URL_V2" value="'.$conf->global->PAYBOX_CGI_URL_V2.'">';
print '<br>'.$langs->trans("Example").': http://mysite/cgi-bin/modulev2_redhat72.cgi';
print '</td></tr>';


print '<tr class="oddeven"><td>';
print $langs->trans("VendorName").'</td><td>';
print '<input size="64" type="text" name="ONLINE_PAYMENT_CREDITOR" value="'.$conf->global->ONLINE_PAYMENT_CREDITOR.'">';
print '<br>'.$langs->trans("Example").': '.$mysoc->name;
print '</td></tr>';


print '<tr class="oddeven"><td>';
print $langs->trans("CSSUrlForPaymentForm").'</td><td>';
print '<input size="64" type="text" name="ONLINE_PAYMENT_CSS_URL" value="'.$conf->global->ONLINE_PAYMENT_CSS_URL.'">';
print '<br>'.$langs->trans("Example").': http://mysite/mycss.css';
print '</td></tr>';


print '<tr class="oddeven"><td>';
print $langs->trans("MessageForm").'</td><td>';
$doleditor=new DolEditor('ONLINE_PAYMENT_MESSAGE_FORM',$conf->global->ONLINE_PAYMENT_MESSAGE_FORM,'',100,'dolibarr_details','In',false,true,true,ROWS_2,'90%');
$doleditor->Create();
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("MessageOK").'</td><td>';
$doleditor=new DolEditor('ONLINE_PAYMENT_MESSAGE_OK',$conf->global->ONLINE_PAYMENT_MESSAGE_OK,'',100,'dolibarr_details','In',false,true,true,ROWS_2,'90%');
$doleditor->Create();
print '</td></tr>';


print '<tr class="oddeven"><td>';
print $langs->trans("MessageKO").'</td><td>';
$doleditor=new DolEditor('ONLINE_PAYMENT_MESSAGE_KO',$conf->global->ONLINE_PAYMENT_MESSAGE_KO,'',100,'dolibarr_details','In',false,true,true,ROWS_2,'90%');
$doleditor->Create();
print '</td></tr>';


print '<tr class="oddeven"><td>';
print $langs->trans("ONLINE_PAYMENT_SENDEMAIL").'</td><td>';
print '<input size="32" type="email" name="ONLINE_PAYMENT_SENDEMAIL" value="'.$conf->global->ONLINE_PAYMENT_SENDEMAIL.'">';
print ' &nbsp; '.$langs->trans("Example").': myemail@myserver.com';
print '</td></tr>';

// Payment token for URL
print '<tr class="oddeven"><td>';
print $langs->trans("SecurityToken").'</td><td>';
print '<input size="48" type="text" id="PAYMENT_SECURITY_TOKEN" name="PAYMENT_SECURITY_TOKEN" value="'.$conf->global->PAYMENT_SECURITY_TOKEN.'">';
if (! empty($conf->use_javascript_ajax))
	print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_token" class="linkobject"');
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("SecurityTokenIsUnique").'</td><td>';
print $form->selectyesno("PAYMENT_SECURITY_TOKEN_UNIQUE",(empty($conf->global->PAYMENT_SECURITY_TOKEN)?0:$conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE),1);
print '</td></tr>';

print '</table>';

dol_fiche_end();

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></div>';

print '</form>';

print '<br><br>';

include DOL_DOCUMENT_ROOT.'/core/tpl/onlinepaymentlinks.tpl.php';

llxFooter();
$db->close();
