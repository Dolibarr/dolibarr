<?php
/* Copyright (C) 2004      Rodolphe Quiedeville 	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2013 Laurent Destailleur  	<eldy@users.sourceforge.org>
 * Copyright (C) 2011-2013 Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 *	    \file       htdocs/admin/mailing.php
 *		\ingroup    mailing
 *		\brief      Page to setup emailing module
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "mails"));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');

$form = new Form($db);


/*
 * Actions
 */

if ($action == 'setvalue') {
	$db->begin();

	$mailfrom = GETPOST('MAILING_EMAIL_FROM', 'alpha');
	$mailerror = GETPOST('MAILING_EMAIL_ERRORSTO', 'alpha');
	$checkread = GETPOST('value', 'alpha');
	$checkread_key = GETPOST('MAILING_EMAIL_UNSUBSCRIBE_KEY', 'alpha');
	$contactbulkdefault = GETPOSTINT('MAILING_CONTACT_DEFAULT_BULK_STATUS');
	if (GETPOST('MAILING_DELAY', 'alpha') != '') {
		$mailingdelay = price2num(GETPOST('MAILING_DELAY', 'alpha'), 3);		// Not less than 1 millisecond.
	} else {
		$mailingdelay = '';
	}
	// Clean data
	if ((float) $mailingdelay > 10) {
		$mailingdelay = 10;
	}
	if (GETPOST('MAILING_DELAY', 'alpha') != '' && GETPOST('MAILING_DELAY', 'alpha') != '0' && (float) $mailingdelay < 0.001) {
		$mailingdelay = 0.001;
	}

	$res = dolibarr_set_const($db, "MAILING_EMAIL_FROM", $mailfrom, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}
	$res = dolibarr_set_const($db, "MAILING_EMAIL_ERRORSTO", $mailerror, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}
	$res = dolibarr_set_const($db, "MAILING_DELAY", $mailingdelay, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}
	$res = dolibarr_set_const($db, "MAILING_CONTACT_DEFAULT_BULK_STATUS", (string) $contactbulkdefault, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	// Create temporary encryption key if needed
	$res = dolibarr_set_const($db, "MAILING_EMAIL_UNSUBSCRIBE_KEY", $checkread_key, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}


	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		$db->rollback();
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}
if ($action == 'setonsearchandlistgooncustomerorsuppliercard') {
	$setonsearchandlistgooncustomerorsuppliercard = GETPOSTINT('value');
	$res = dolibarr_set_const($db, "SOCIETE_ON_SEARCH_AND_LIST_GO_ON_CUSTOMER_OR_SUPPLIER_CARD", (string) $setonsearchandlistgooncustomerorsuppliercard, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}
	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

/*
 *	View
 */

llxHeader('', $langs->trans("MailingSetup"), '', '', 0, 0, '', '', '', 'mod-admin page-mailing');

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("MailingSetup"), $linkback, 'title_setup');

$constname = 'MAILING_EMAIL_UNSUBSCRIBE_KEY';

// Add button to autosuggest a key
include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
print dolJSToSetRandomPassword($constname);

print '<br>';
print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="setvalue">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td class="hideonsmartphone">'.$langs->trans("Example").'</td>';
print "</tr>\n";

print '<tr class="oddeven"><td>';
$help = img_help(1, $langs->trans("EMailHelpMsgSPFDKIM"));
print $langs->trans("MailingEMailFrom").' '.$help.'</td><td>';
print '<input class="minwidth100" type="text" name="MAILING_EMAIL_FROM" value="' . getDolGlobalString('MAILING_EMAIL_FROM').'">';
if (getDolGlobalString('MAILING_EMAIL_FROM') && !isValidEmail($conf->global->MAILING_EMAIL_FROM)) {
	print ' '.img_warning($langs->trans("BadEMail"));
}
print '</td>';
print '<td class="hideonsmartphone"><span class="opacitymedium">'.dol_escape_htmltag(($mysoc->name ? $mysoc->name : 'MyName').' <noreply@example.com>').'</span></td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("MailingEMailError").'</td><td>';
print '<input class="minwidth100" type="text" name="MAILING_EMAIL_ERRORSTO" value="'.getDolGlobalString('MAILING_EMAIL_ERRORSTO').'">';
if (getDolGlobalString('MAILING_EMAIL_ERRORSTO') && !isValidEmail(getDolGlobalString('MAILING_EMAIL_ERRORSTO'))) {
	print ' '.img_warning($langs->trans("BadEMail"));
}
print '</td>';
print '<td class="hideonsmartphone"><span class="opacitymedium">'.dol_escape_htmltag('<webmaster@example.com>').'</span></td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("MailingDelay"), $langs->trans("IfDefinedUseAValueBeetween", '0.001', '10')).'</td><td>';
print '<input class="width75" type="text" name="MAILING_DELAY" value="'.getDolGlobalString('MAILING_DELAY').'">';
print '</td>';
print '<td class="hideonsmartphone"></td>';
print '</tr>';


// Constant to add salt into the unsubscribe and check read tag.
// It is also used as a security key parameter.

print '<tr class="oddeven"><td>';
print $langs->trans("ActivateCheckReadKey").'</td><td>';
print '<input class="minwidth100 maxwdith250 widthcentpercentminusx" type="text" name="MAILING_EMAIL_UNSUBSCRIBE_KEY" id="MAILING_EMAIL_UNSUBSCRIBE_KEY" value="'.getDolGlobalString('MAILING_EMAIL_UNSUBSCRIBE_KEY').'">';
if (!empty($conf->use_javascript_ajax)) {
	print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_token" class="linkobject"');
}
print '</td>';
print '<td class="hideonsmartphone"></td>';
print '</tr>';

// default blacklist from mailing
print '<tr class="oddeven">';
print '<td>' . $langs->trans("DefaultBlacklistMailingStatus", $langs->transnoentitiesnoconv("No_Email")) . '</td>';
print '<td>';
$blacklist_setting=array(0=>$langs->trans('No'), 1=>$langs->trans('Yes'), 2=>$langs->trans('DefaultStatusEmptyMandatory'));
print $form->selectarray("MAILING_CONTACT_DEFAULT_BULK_STATUS", $blacklist_setting, getDolGlobalString('MAILING_CONTACT_DEFAULT_BULK_STATUS'));
print '</td>';
print '<td class="hideonsmartphone"></td>';
print '</tr>';


if (!empty($conf->use_javascript_ajax) && getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 1) {
	print '<tr class="oddeven"><td>';
	print $langs->trans("MailAdvTargetRecipients").'</td><td>';
	print ajax_constantonoff('EMAILING_USE_ADVANCED_SELECTOR');
	print '</td>';
	print '<td class="hideonsmartphone"></td>';
	print '</tr>';
}

print '</table>';

print $form->buttonsSaveCancel("Modify", '');

print '</form>';

// End of page
llxFooter();
$db->close();
