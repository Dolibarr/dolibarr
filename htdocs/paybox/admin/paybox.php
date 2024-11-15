<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010  Laurent Destailleur     <eldy@users.sourceforge.org>
 * Copyright (C) 2011-2012  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 * \file       htdocs/paybox/admin/paybox.php
 * \ingroup    paybox
 * \brief      Page to setup paybox module
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

$servicename = 'PayBox';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Form $form
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('admin', 'other', 'paybox', 'paypal', 'stripe'));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$error = 0;

if ($action == 'setvalue' && $user->admin) {
	$db->begin();
	//$result=dolibarr_set_const($db, "PAYBOX_IBS_DEVISE", GETPOST("PAYBOX_IBS_DEVISE"),'chaine',0,'',$conf->entity);
	$result = dolibarr_set_const($db, "PAYBOX_CGI_URL_V1", GETPOST('PAYBOX_CGI_URL_V1', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}
	$result = dolibarr_set_const($db, "PAYBOX_CGI_URL_V2", GETPOST('PAYBOX_CGI_URL_V2', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}
	$result = dolibarr_set_const($db, "PAYBOX_IBS_SITE", GETPOST('PAYBOX_IBS_SITE', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}
	$result = dolibarr_set_const($db, "PAYBOX_IBS_RANG", GETPOST('PAYBOX_IBS_RANG', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}
	$result = dolibarr_set_const($db, "PAYBOX_PBX_IDENTIFIANT", GETPOST('PAYBOX_PBX_IDENTIFIANT', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_CREDITOR", GETPOST('ONLINE_PAYMENT_CREDITOR', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}
	$result = dolibarr_set_const($db, "PAYBOX_BANK_ACCOUNT_FOR_PAYMENTS", GETPOSTINT('PAYBOX_BANK_ACCOUNT_FOR_PAYMENTS'), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_CSS_URL", GETPOST('ONLINE_PAYMENT_CSS_URL', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_MESSAGE_FORM", GETPOST('ONLINE_PAYMENT_MESSAGE_FORM', 'restricthtml'), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_MESSAGE_OK", GETPOST('ONLINE_PAYMENT_MESSAGE_OK', 'restricthtml'), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_MESSAGE_KO", GETPOST('ONLINE_PAYMENT_MESSAGE_KO', 'restricthtml'), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}
	$result = dolibarr_set_const($db, "ONLINE_PAYMENT_SENDEMAIL", GETPOST('ONLINE_PAYMENT_SENDEMAIL', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}
	// Payment token for URL
	$result = dolibarr_set_const($db, "PAYMENT_SECURITY_TOKEN", GETPOST('PAYMENT_SECURITY_TOKEN', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}
	$result = dolibarr_set_const($db, "PAYMENT_SECURITY_TOKEN_UNIQUE", GETPOST('PAYMENT_SECURITY_TOKEN_UNIQUE', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}
	$result = dolibarr_set_const($db, "PAYBOX_HMAC_KEY", dol_encode(GETPOST('PAYBOX_HMAC_KEY', 'alpha')), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}


	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		$db->rollback();
		dol_print_error($db);
	}
}


/*
 *	View
 */

$IBS_SITE = "1999888"; // Site test
if (!getDolGlobalString('PAYBOX_IBS_SITE')) {
	$conf->global->PAYBOX_IBS_SITE = $IBS_SITE;
}
$IBS_RANG = "99"; // Rang test
if (!getDolGlobalString('PAYBOX_IBS_RANG')) {
	$conf->global->PAYBOX_IBS_RANG = $IBS_RANG;
}
$IBS_DEVISE = "978"; // Euro
if (!getDolGlobalString('PAYBOX_IBS_DEVISE')) {
	$conf->global->PAYBOX_IBS_DEVISE = $IBS_DEVISE;
}

llxHeader();

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("PayBoxSetup"), $linkback, 'title_setup');

$h = 0;
$head = array();

$head[$h][0] = DOL_URL_ROOT."/paybox/admin/paybox.php";
$head[$h][1] = $langs->trans("PayBox");
$head[$h][2] = 'payboxaccount';
$h++;

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="setvalue">';

print dol_get_fiche_head($head, 'payboxaccount', '', -1);

print $langs->trans("PayBoxDesc")."<br>\n";
print '<br>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("AccountParameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";


print '<tr class="oddeven"><td>';
print '<span class="fieldrequired">'.$langs->trans("PAYBOX_PBX_SITE").'</span></td><td>';
print '<input size="32" type="text" name="PAYBOX_IBS_SITE" value="' . getDolGlobalString('PAYBOX_IBS_SITE').'">';
print '<span class="opacitymedium"><br>'.$langs->trans("Example").': 1999888 ('.$langs->trans("Test").')</span>';
print '</td></tr>';


print '<tr class="oddeven"><td>';
print '<span class="fieldrequired">'.$langs->trans("PAYBOX_PBX_RANG").'</span></td><td>';
print '<input size="32" type="text" name="PAYBOX_IBS_RANG" value="' . getDolGlobalString('PAYBOX_IBS_RANG').'">';
print '<span class="opacitymedium"><br>'.$langs->trans("Example").': 99 ('.$langs->trans("Test").')</span>';
print '</td></tr>';


print '<tr class="oddeven"><td>';
print '<span class="fieldrequired">'.$langs->trans("PAYBOX_PBX_IDENTIFIANT").'</span></td><td>';
print '<input size="32" type="text" name="PAYBOX_PBX_IDENTIFIANT" value="' . getDolGlobalString('PAYBOX_PBX_IDENTIFIANT').'">';
print '<span class="opacitymedium"><br>'.$langs->trans("Example").': 2 ('.$langs->trans("Test").')</span>';
print '</td></tr>';

print '<tr class="oddeven"><td>';
print '<span class="fieldrequired">'.$langs->trans("PAYBOX_HMAC_KEY").'</span></td><td>';
print '<input size="100" type="text" name="PAYBOX_HMAC_KEY" value="'.dol_decode($conf->global->PAYBOX_HMAC_KEY).'">';
print '<span class="opacitymedium"><br>'.$langs->trans("Example").': 1A2B3C4D5E6F</span>';
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
print '<input size="64" type="text" name="PAYBOX_CGI_URL_V2" value="' . getDolGlobalString('PAYBOX_CGI_URL_V2').'">';
print '<span class="opacitymedium"><br>'.$langs->trans("Example").' (preprod): https://preprod-tpeweb.paybox.com/php/';
print '<br>'.$langs->trans("Example").' (prod): https://tpeweb.paybox.com/php/</span>';
print '</td></tr>';


print '<tr class="oddeven"><td>';
print $langs->trans("PublicVendorName").'</td><td>';
print '<input type="text" class="minwidth300" name="ONLINE_PAYMENT_CREDITOR" value="' . getDolGlobalString('ONLINE_PAYMENT_CREDITOR').'">';
print '<br><span class="opacitymedium">'.$langs->trans("Example").': '.$mysoc->name.'</span>';
print '</td></tr>';


if (isModEnabled("bank")) {
	print '<tr class="oddeven"><td>';
	print $langs->trans("BankAccount").'</td><td>';
	$form->select_comptes($conf->global->PAYBOX_BANK_ACCOUNT_FOR_PAYMENTS, 'PAYBOX_BANK_ACCOUNT_FOR_PAYMENTS', 0, '', 1);
	print '</td></tr>';
}


print '<tr class="oddeven"><td>';
print $langs->trans("CSSUrlForPaymentForm").'</td><td>';
print '<input size="64" type="text" name="ONLINE_PAYMENT_CSS_URL" value="' . getDolGlobalString('ONLINE_PAYMENT_CSS_URL').'">';
print '<span class="opacitymedium"><br>'.$langs->trans("Example").': http://mysite/mycss.css</span>';
print '</td></tr>';


print '<tr class="oddeven"><td>';
print $langs->trans("MessageForm").'</td><td>';
$doleditor = new DolEditor('ONLINE_PAYMENT_MESSAGE_FORM', $conf->global->ONLINE_PAYMENT_MESSAGE_FORM, '', 100, 'dolibarr_details', 'In', false, true, true, ROWS_2, '90%');
$doleditor->Create();
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("MessageOK").'</td><td>';
$doleditor = new DolEditor('ONLINE_PAYMENT_MESSAGE_OK', $conf->global->ONLINE_PAYMENT_MESSAGE_OK, '', 100, 'dolibarr_details', 'In', false, true, true, ROWS_2, '90%');
$doleditor->Create();
print '</td></tr>';


print '<tr class="oddeven"><td>';
print $langs->trans("MessageKO").'</td><td>';
$doleditor = new DolEditor('ONLINE_PAYMENT_MESSAGE_KO', $conf->global->ONLINE_PAYMENT_MESSAGE_KO, '', 100, 'dolibarr_details', 'In', false, true, true, ROWS_2, '90%');
$doleditor->Create();
print '</td></tr>';


print '<tr class="oddeven"><td class="fieldrequired">';
print $langs->trans("ONLINE_PAYMENT_SENDEMAIL").'</td><td>';
print '<input size="32" type="text" name="ONLINE_PAYMENT_SENDEMAIL" value="' . getDolGlobalString('ONLINE_PAYMENT_SENDEMAIL').'">';
print ' &nbsp; <span class="opacitymedium">'.$langs->trans("Example").': myemail@myserver.com, Payment service &lt;myemail2@myserver2.com&gt;</span>';
print '</td></tr>';

// Payment token for URL
print '<tr class="oddeven"><td>';
print $langs->trans("SecurityToken").'</td><td>';
print '<input size="48" type="text" id="PAYMENT_SECURITY_TOKEN" name="PAYMENT_SECURITY_TOKEN" value="' . getDolGlobalString('PAYMENT_SECURITY_TOKEN').'">';
if (!empty($conf->use_javascript_ajax)) {
	print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_token" class="linkobject"');
}
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("SecurityTokenIsUnique").'</td><td>';
print $form->selectyesno("PAYMENT_SECURITY_TOKEN_UNIQUE", (!getDolGlobalString('PAYMENT_SECURITY_TOKEN') ? 0 : $conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE), 1);
print '</td></tr>';

print '</table>';

print dol_get_fiche_end();

print $form->buttonsSaveCancel("Modify", '');

print '</form>';

print '<br><br>';

include DOL_DOCUMENT_ROOT.'/core/tpl/onlinepaymentlinks.tpl.php';

// End of page
llxFooter();
$db->close();
