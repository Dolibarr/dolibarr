<?php
/* Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013 	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2020      Frédéric France      <frederic.france@netlogic.fr>
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
 *       \file       htdocs/admin/sms.php
 *       \brief      Page to setup emails sending
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "admin", "products", "sms", "other", "errors"));

$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'aZ09');

if (!$user->admin) {
	accessforbidden();
}

$substitutionarrayfortest = array(
	'__ID__' => 'TESTIdRecord',
	'__PHONEFROM__' => 'TESTPhoneFrom',
	'__PHONETO__' => 'TESTPhoneTo',
	'__LASTNAME__' => 'TESTLastname',
	'__FIRSTNAME__' => 'TESTFirstname'
);


/*
 * Actions
 */

if ($action == 'update' && !$cancel) {
	dolibarr_set_const($db, "MAIN_DISABLE_ALL_SMS", GETPOST("MAIN_DISABLE_ALL_SMS", 'alphanohtml'), 'chaine', 0, '', $conf->entity);

	dolibarr_set_const($db, "MAIN_SMS_SENDMODE", GETPOST("MAIN_SMS_SENDMODE", 'alphanohtml'), 'chaine', 0, '', $conf->entity);

	dolibarr_set_const($db, "MAIN_SMS_FROM", GETPOST("MAIN_SMS_FROM", 'alphanohtml'), 'chaine', 0, '', $conf->entity);

	header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
	exit;
}


// Send sms
if ($action == 'send' && !$cancel) {
	$error = 0;

	$smsfrom = '';
	if (GETPOST("fromsms", 'alphanohtml')) {
		$smsfrom = GETPOST("fromsms", 'alphanohtml');
	}
	if (empty($smsfrom)) {
		$smsfrom = GETPOST("fromname", 'alphanohtml');
	}
	$sendto     = GETPOST("sendto", 'alphanohtml');
	$body       = GETPOST('message', 'alphanohtml');
	$deliveryreceipt = GETPOST("deliveryreceipt", 'alphanohtml');
	$deferred   = GETPOST('deferred', 'alphanohtml');
	$priority   = GETPOST('priority', 'alphanohtml');
	$class      = GETPOST('class', 'alphanohtml');
	$errors_to  = GETPOST("errorstosms", 'alphanohtml');

	// Create form object
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formsms.class.php';
	$formsms = new FormSms($db);

	if (!empty($formsms->error)) {
		setEventMessages($formsms->error, $formsms->errors, 'errors');
		$action = 'test';
		$error++;
	}
	if (empty($body)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Message")), null, 'errors');
		$action = 'test';
		$error++;
	}
	if (empty($smsfrom) || !str_replace('+', '', $smsfrom)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("SmsFrom")), null, 'errors');
		$action = 'test';
		$error++;
	}
	if (empty($sendto) || !str_replace('+', '', $sendto)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("SmsTo")), null, 'errors');
		$action = 'test';
		$error++;
	}
	if (!$error) {
		// Make substitutions into message
		complete_substitutions_array($substitutionarrayfortest, $langs);
		$body = make_substitutions($body, $substitutionarrayfortest);

		require_once DOL_DOCUMENT_ROOT.'/core/class/CSMSFile.class.php';
		try {
			$smsfile = new CSMSFile($sendto, $smsfrom, $body, $deliveryreceipt, $deferred, $priority, $class); // This define OvhSms->login, pass, session and account
		} catch (Exception $e) {
			setEventMessages($e->getMessage(), null, 'error');
		}
		$result = $smsfile->sendfile(); // This send SMS

		if ($result) {
			setEventMessages($langs->trans("SmsSuccessfulySent", $smsfrom, $sendto), null, 'mesgs');
			setEventMessages($smsfile->error, $smsfile->errors, 'mesgs');
		} else {
			setEventMessages($langs->trans("ResultKo"), null, 'errors');
			setEventMessages($smsfile->error, $smsfile->errors, 'errors');
		}

		$action = '';
	}
}



/*
 * View
 */

$form = new Form($db);

$linuxlike = 1;
if (preg_match('/^win/i', PHP_OS)) {
	$linuxlike = 0;
}
if (preg_match('/^mac/i', PHP_OS)) {
	$linuxlike = 0;
}

$wikihelp = 'EN:Setup Sms|FR:Paramétrage Sms|ES:Configuración Sms';
llxHeader('', $langs->trans("Setup"), $wikihelp, '', 0, 0, '', '', '', 'mod-admin page-sms');

print load_fiche_titre($langs->trans("SmsSetup"), '', 'title_setup');

print '<span class="opacitymedium">'.$langs->trans("SmsDesc")."</span><br>\n";
print "<br>\n";
print "<br>\n";

// List of sending methods
$listofmethods = (is_array($conf->modules_parts['sms']) ? $conf->modules_parts['sms'] : array());
asort($listofmethods);

if (!count($listofmethods)) {
	$descnosms = $langs->trans("NoSmsEngine", '{Dolistore}');
	$descnosms = str_replace('{Dolistore}', '<a href="https://www.dolistore.com/search.php?orderby=position&orderway=desc&search_query=smsmanager">DoliStore</a>', $descnosms);
	print '<div class="warning">'.$descnosms.'</div>';
}

if ($action == 'edit') {
	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';

	clearstatcache();

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	// Disable
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_DISABLE_ALL_SMS").'</td><td>';
	print $form->selectyesno('MAIN_DISABLE_ALL_SMS', getDolGlobalString('MAIN_DISABLE_ALL_SMS'), 1);
	print '</td></tr>';

	// Separator
	print '<tr class="oddeven"><td colspan="2">&nbsp;</td></tr>';

	// Method
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_SMS_SENDMODE").'</td><td>';
	if (count($listofmethods)) {
		print $form->selectarray('MAIN_SMS_SENDMODE', $listofmethods, getDolGlobalString('MAIN_SMS_SENDMODE'), 1);
	} else {
		print '<span class="error">'.$langs->trans("None").'</span>';
	}
	print '</td></tr>';

	// From
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_SMS_FROM", $langs->transnoentities("Undefined")).'</td>';
	print '<td><input class="flat" name="MAIN_SMS_FROM" size="32" value="'.getDolGlobalString('MAIN_SMS_FROM');
	print '"></td></tr>';

	print '</table>';

	print '<div class="center">';
	print '<input class="button button-save" type="submit" name="save" value="'.$langs->trans("Save").'"'.(!count($listofmethods) ? ' disabled' : '').'>';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input class="button button-cancel" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
	print '<br>';
} else {
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	// Disable
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_DISABLE_ALL_SMS").'</td><td>'.yn(getDolGlobalString('MAIN_DISABLE_ALL_SMS')).'</td></tr>';

	// Separator
	print '<tr class="oddeven"><td colspan="2">&nbsp;</td></tr>';

	// Method
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_SMS_SENDMODE").'</td><td>';
	$text = getDolGlobalString('MAIN_SMS_SENDMODE') ? $listofmethods[getDolGlobalString('MAIN_SMS_SENDMODE')] : '';
	if (empty($text)) {
		$text = $langs->trans("Undefined").' '.img_warning();
	}
	print $text;
	print '</td></tr>';

	// From
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_SMS_FROM", $langs->transnoentities("Undefined")).'</td>';
	print '<td>'.getDolGlobalString('MAIN_SMS_FROM');
	if (getDolGlobalString('MAIN_SMS_FROM') && !isValidPhone(getDolGlobalString('MAIN_SMS_FROM'))) {
		print ' '.img_warning($langs->trans("ErrorBadPhone"));
	}
	print '</td></tr>';

	print '</table>';


	// Buttons for actions

	print '<div class="tabsAction">';

	if ($action != 'test') {
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';

		if (count($listofmethods) && getDolGlobalString('MAIN_SMS_SENDMODE')) {
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=test&mode=init&token='.newToken().'">'.$langs->trans("DoTestSend").'</a>';
		} else {
			print '<a class="butActionRefused classfortooltip" href="#">'.$langs->trans("DoTestSend").'</a>';
		}
	}

	print '</div>';


	// Affichage formulaire de TEST simple
	if ($action == 'test') {
		print '<br>';
		print load_fiche_titre($langs->trans("DoTestSend"));

		// Cree l'objet formulaire mail
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formsms.class.php';
		$formsms = new FormSms($db);
		$formsms->fromtype = 'user';
		$formsms->fromid = $user->id;
		$formsms->fromsms = (GETPOSTISSET('fromsms') ? GETPOST('fromsms') : getDolGlobalString('MAIN_SMS_FROM', $user->user_mobile));
		$formsms->withfromreadonly = 0;
		$formsms->withsubstit = 0;
		$formsms->withfrom = 1;
		$formsms->withto = (GETPOSTISSET('sendto') ? GETPOST('sendto') : ($user->user_mobile ? $user->user_mobile : 1));
		$formsms->withbody = (GETPOSTISSET('message') ? (!GETPOST('message') ? 1 : GETPOST('message')) : $langs->trans("ThisIsATestMessage"));
		$formsms->withbodyreadonly = 0;
		$formsms->withcancel = 1;
		// Tableau des substitutions
		$formsms->substit = $substitutionarrayfortest;
		// Tableau des parameters complementaires du post
		$formsms->param["action"] = "send";
		$formsms->param["models"] = "body";
		$formsms->param["smsid"] = 0;
		$formsms->param["returnurl"] = $_SERVER["PHP_SELF"];

		$formsms->show_form();

		print '<br>';
	}
}

// End of page
llxFooter();
$db->close();
