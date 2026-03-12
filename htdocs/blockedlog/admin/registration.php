<?php
/* Copyright (C) 2017      ATM Consulting      <contact@atm-consulting.fr>
 * Copyright (C) 2017-2018 Laurent Destailleur <eldy@destailleur.fr>
 * Copyright (C) 2024      Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2026		MDW					<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/blockedlog/admin/registration.php
 *  \ingroup    blockedlog
 *  \brief      Page setup for blockedlog module (user registration)
 */


// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/blockedlog/lib/blockedlog.lib.php';
require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/modBlockedLog.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'blockedlog', 'other'));

// Get Parameters
$action     = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$cancel     = GETPOST('cancel');

$withtab    = GETPOSTISSET('withtab') ? GETPOSTINT('withtab') : 1;
$origin     = GETPOST('origin');
$mode       = GETPOST('mode');

// Access Control
if (!$user->admin) {
	accessforbidden();
}


/*
 * Actions
 */

if ($cancel && $origin == 'initmodule') {
	header("Location: ".DOL_URL_ROOT."/admin/modules.php");
	exit(0);
}
if ($cancel) {
	$action = '';
}

if ($action == 'update') {
	$error = 0;
	$db->begin();

	// The mandatory information must be the same than the one defined into isRegistrationDataSaved()
	if (!GETPOST("BLOCKEDLOG_REGISTRATION_NAME")) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->trans("BLOCKEDLOG_REGISTRATION_NAME")), null, 'errors');
		$error++;
	}
	if (!GETPOST("BLOCKEDLOG_REGISTRATION_EMAIL")) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->trans("BLOCKEDLOG_REGISTRATION_EMAIL")), null, 'errors');
		$error++;
	}
	if (!GETPOST("BLOCKEDLOG_REGISTRATION_COUNTRY_CODE")) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->trans("BLOCKEDLOG_REGISTRATION_COUNTRY_CODE")), null, 'errors');
		$error++;
	}
	if (!GETPOST("BLOCKEDLOG_REGISTRATION_IDPROF1")) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->trans("BLOCKEDLOG_REGISTRATION_IDPROF1")), null, 'errors');
		$error++;
	}

	$company_name = GETPOST("BLOCKEDLOG_REGISTRATION_NAME");
	$company_email = GETPOST("BLOCKEDLOG_REGISTRATION_EMAIL");
	$company_country_code = GETPOST("BLOCKEDLOG_REGISTRATION_COUNTRY_CODE");
	$company_idprof1 = GETPOST("BLOCKEDLOG_REGISTRATION_IDPROF1");
	$company_address = GETPOST("BLOCKEDLOG_REGISTRATION_ADDRESS");
	$company_state = GETPOST("BLOCKEDLOG_REGISTRATION_STATE");
	$company_zip = GETPOST("BLOCKEDLOG_REGISTRATION_ZIP");
	$company_town = GETPOST("BLOCKEDLOG_REGISTRATION_TOWN");

	$provider_name = GETPOST("MAIN_INFO_ITPROVIDER_NAME");
	$provider_email = GETPOST("MAIN_INFO_ITPROVIDER_MAIL");
	$provider_country_id = GETPOST("MAIN_INFO_ITPROVIDER_COUNTRY");
	$provider_idprof1 = GETPOST("MAIN_INFO_ITPROVIDER_IDPROF1");
	$provider_address = GETPOST("MAIN_INFO_ITPROVIDER_ADDRESS");
	$provider_state = GETPOST("MAIN_INFO_ITPROVIDER_STATE");
	$provider_zip = GETPOST("MAIN_INFO_ITPROVIDER_ZIP");
	$provider_town = GETPOST("MAIN_INFO_ITPROVIDER_TOWN");

	if (!$error) {
		//Company
		$res = dolibarr_set_const($db, "BLOCKEDLOG_REGISTRATION_NAME", $company_name, 'chaine', 0, '', $conf->entity);
		if ($res <= 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "BLOCKEDLOG_REGISTRATION_EMAIL", $company_email, 'chaine', 0, '', $conf->entity);
		if ($res <= 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "BLOCKEDLOG_REGISTRATION_COUNTRY_CODE", $company_country_code, 'chaine', 0, '', $conf->entity);
		if ($res <= 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "BLOCKEDLOG_REGISTRATION_IDPROF1", $company_idprof1, 'chaine', 0, '', $conf->entity);
		if ($res <= 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "BLOCKEDLOG_REGISTRATION_ADDRESS", $company_address, 'chaine', 0, '', $conf->entity);
		if ($res <= 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "BLOCKEDLOG_REGISTRATION_STATE", $company_state, 'chaine', 0, '', $conf->entity);
		if ($res <= 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "BLOCKEDLOG_REGISTRATION_ZIP", $company_zip, 'chaine', 0, '', $conf->entity);
		if ($res <= 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "BLOCKEDLOG_REGISTRATION_TOWN", $company_town, 'chaine', 0, '', $conf->entity);
		if ($res <= 0) {
			$error++;
		}

		//IT Provider
		$res = dolibarr_set_const($db, "MAIN_INFO_ITPROVIDER_NAME", $provider_name, 'chaine', 0, '', $conf->entity);
		if ($res <= 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "MAIN_INFO_ITPROVIDER_MAIL", $provider_email, 'chaine', 0, '', $conf->entity);
		if ($res <= 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "MAIN_INFO_ITPROVIDER_COUNTRY", $provider_country_id, 'chaine', 0, '', $conf->entity);
		if ($res <= 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "MAIN_INFO_ITPROVIDER_IDPROF1", $provider_idprof1, 'chaine', 0, '', $conf->entity);
		if ($res <= 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "MAIN_INFO_ITPROVIDER_ADDRESS", $provider_address, 'chaine', 0, '', $conf->entity);
		if ($res <= 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "MAIN_INFO_ITPROVIDER_STATE", $provider_state, 'chaine', 0, '', $conf->entity);
		if ($res <= 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "MAIN_INFO_ITPROVIDER_ZIP", $provider_zip, 'chaine', 0, '', $conf->entity);
		if ($res <= 0) {
			$error++;
		}
		$res = dolibarr_set_const($db, "MAIN_INFO_ITPROVIDER_TOWN", $provider_town, 'chaine', 0, '', $conf->entity);
		if ($res <= 0) {
			$error++;
		}
	}
	if (!$error) {
		$db->commit();

		//setEventMessages("SetupSaved", null, 'mesgs');
		$urltouse = $_SERVER["PHP_SELF"]."?mode=forceregistration";
		$urltouse .= (($withtab && GETPOST('origin')) ? '&withtab='.$withtab : '');
		$urltouse .= (GETPOST('origin') ? '&origin='.GETPOST('origin') : '');

		header("Location: ".$urltouse);
		exit;
	} else {
		$db->rollback();
	}
}


/*
 *	View
 */

$formSetup = new FormSetup($db);
$form = new Form($db);
$formcompany = new FormCompany($db);
$block_static = new BlockedLog($db);
$block_static->loadTrackedEvents();

if (GETPOST('withtab', 'alpha')) {
	$title = $langs->trans("ModuleSetup").' '.$langs->trans('BlockedLog');
} else {
	$title = $langs->trans("BrowseBlockedLog");
}

$help_url = "EN:Module_Unalterable_Archives_-_Logs|FR:Module_Archives_-_Logs_Inaltérable";

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-blockedlog page-admin_blockedlog');

if (GETPOST('withtab', 'alpha')) {
	$linkback = '<a href="'.dolBuildUrl($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php', ['restore_lastsearch_values' => 1]).'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';
} else {
	$linkback = '';
}

$morehtmlcenter = '';
$texttop = '';

$registrationnumber = getHashUniqueIdOfRegistration();
if (!userIsTaxAuditor()) {
	$texttop = '<small class="opacitymedium">'.$langs->trans("RegistrationNumber").':</small> <small>'.dol_trunc($registrationnumber, 10).'</small>';
	if ((!isRegistrationDataSavedAndPushed() || !isModEnabled('blockedlog')) && $mode != "forceregistration") {
		$texttop = '';
	}
}

print load_fiche_titre($title.'<br>'.$texttop, $linkback, 'blockedlog', 0, '', '', $morehtmlcenter);

if ($withtab) {
	$head = blockedlogadmin_prepare_head(GETPOST('withtab', 'alpha'));
	print dol_get_fiche_head($head, 'registration', '', -1);
} else {
	print '<br>';
}

print '<span class="opacitymedium">'.$langs->trans("BlockedLogDesc")."</span><br>\n";


// Version
$versionbadge = '<span class="badge-text badge-secondary">'.getBlockedLogVersionToShow().'</span>';


// Special additional message for FR only
$infotoshow = '';
if ($mysoc->country_code == 'FR') {
	$islne = isALNEQualifiedVersion(1, 1);
	if ($islne) {
		if (preg_match('/\-/', DOL_VERSION)) {
			// This is an alpha or beta version
			$infotoshow = $langs->trans("LNECandidateVersionForCertificationFR", $versionbadge);
		} else {
			$infotoshow = $langs->trans("LNECertifiedVersionFR", $versionbadge);
		}
	} else {
		$infotoshow = $langs->trans("NotCertifiedVersionFR", $versionbadge);
	}
}

// Show generic message (for countries that need registration) to explain we need registration to collect data and why
if (in_array($mysoc->country_code, array('FR'))) {
	$organization_for_ping = getDolGlobalString('MAIN_ORGANIZATION_FOR_PING', "Association Dolibarr");
	$dataprivacy_url = getDolGlobalString('MAIN_ORGANIZATION_URL_PRIVACY', "https://www.dolibarr.org/legal-privacy-gdpr.php");

	if (!isRegistrationDataSavedAndPushed() || $origin == 'initmodule') {
		if ($infotoshow) {
			print info_admin($infotoshow, 0, 0, 'info');
		}

		if ((!isRegistrationDataSavedAndPushed() || !isModEnabled('blockedlog')) && $mode != "forceregistration") {
			print '<center><span class="error"><br>'.$langs->trans("RegistrationRequired").'<br><br></span></center>';
		}

		$htmltext = "";
		$htmltext .= $langs->trans("UnalterableLogToolRegistrationFR").'<br>';
		$htmltext .= $langs->trans("InformationWillBePublishedTo");
		$htmltext .= '<br>'.$langs->trans("InformationWillBePublishedTo2", $organization_for_ping, $dataprivacy_url);
		if (!isRegistrationDataSavedAndPushed() || !isModEnabled('blockedlog')) {
			$htmltext .= '<br>'.$langs->trans("InformationWillBePublishedTo3");
			$color = 'warning';
		} else {
			$color = 'info';
		}

		print info_admin($htmltext, 0, 0, $color);

		$htmltext = '';
		// @phpstan-ignore-next-line  Country code is already FR because of in_array('FR') test above
		if ($mysoc->country_code === 'FR') {
			$htmltext .= $langs->trans("UnalterableLogTool1FR").'<br>';
		}

		print info_admin($htmltext, 0, 0, 'warning');

		if (isRegistrationDataSavedAndPushed() && isModEnabled('blockedlog') && $mode != "forceregistration") {
			print '<center><span class="ok"><br>'.$langs->trans("ApplicationHasBeenRegistered").'<br><br></span></center>';
		}
	} else {
		$htmltext = ($infotoshow ? $infotoshow.'<br>' : '');
		$htmltext .= $langs->trans("ApplicationHasBeenRegistered");
		$htmltext .= ' '.$langs->trans("RegistrationNumber").': <span class="badge-text badge-secondary">'.dol_trunc($registrationnumber, 10).'</span>';
		$htmltext .= '<br>';
		$htmltext .= $langs->trans("LastRegistrationDate").' : ';
		//$htmltext .= dol_print_date(getDolGlobalString('MAIN_FIRST_REGISTRATION_OK_DATE'), 'dayhour', 'tzuserrel');
		$htmltext .= getDolGlobalString('MAIN_FIRST_REGISTRATION_OK_DATE');

		print info_admin($htmltext, 0, 0, 'info');

		// Show remind on good practices related to archives
		$htmltext = $langs->trans("UnalterableLogTool1FR").'<br>';
		print info_admin($htmltext, 0, 0, 'warning');
	}
}


print '<br>';


if ($mode == "forceregistration") {
	$company_state = $mysoc->state;
	if (getDolGlobalString('BLOCKEDLOG_REGISTRATION_STATE')) {
		$company_state = getState(getDolGlobalInt('BLOCKEDLOG_REGISTRATION_STATE'));
	}
	$arrayofdata = array(
		'action' => 'dolibarrregistration',

		'company_name' => getDolGlobalString('BLOCKEDLOG_REGISTRATION_NAME', $mysoc->name),
		'company_email' => getDolGlobalString('BLOCKEDLOG_REGISTRATION_EMAIL', $mysoc->email),
		'company_idprof1' => getDolGlobalString('BLOCKEDLOG_REGISTRATION_IDPROF1', $mysoc->idprof1),
		'company_address' => getDolGlobalString('BLOCKEDLOG_REGISTRATION_ADDRESS', $mysoc->address),
		'company_state' => $company_state,
		'company_zip' => getDolGlobalString('BLOCKEDLOG_REGISTRATION_ZIP', $mysoc->zip),
		'company_town' => getDolGlobalString('BLOCKEDLOG_REGISTRATION_TOWN', $mysoc->town),
		'country_code' => getDolGlobalString('BLOCKEDLOG_REGISTRATION_COUNTRY_CODE', $mysoc->country_code),

		'provider_name' => getDolGlobalString('MAIN_INFO_ITPROVIDER_NAME'),
		'provider_email' => getDolGlobalString('MAIN_INFO_ITPROVIDER_MAIL'),
		'provider_phone' => getDolGlobalString('MAIN_INFO_ITPROVIDER_PHONE'),
		'provider_address' => getDolGlobalString('MAIN_INFO_ITPROVIDER_ADDRESS'),
		'provider_state' => getDolGlobalString('MAIN_INFO_ITPROVIDER_STATE'),
		'provider_zip' => getDolGlobalString('MAIN_INFO_ITPROVIDER_ZIP'),
		'provider_town' => getDolGlobalString('MAIN_INFO_ITPROVIDER_TOWN'),
		'provider_country' => getDolGlobalString('MAIN_INFO_ITPROVIDER_COUNTRY'),
		'provider_idprof1' => getDolGlobalString('MAIN_INFO_ITPROVIDER_IDPROF1')
	);

	// Output js code to register data.
	// Note: You can force thereigstration message by calling page /index.php?foreceregistration=1
	printCodeForPing("MAIN_LAST_REGISTRATION_KO_DATE", "MAIN_FIRST_REGISTRATION_OK_DATE", $arrayofdata, 1);

	if (!isModEnabled("blockedlog")) {
		$modblckedlog = new modBlockedLog($db);
		$res = $modblckedlog->init('forceinit');
		//$res = 1;

		if ($res <= 0) {
			setEventMessages($modblckedlog->error, $modblckedlog->errors, 'errors');

			$mode = '';
		}
	}
	if ($mode == "forceregistration") {
		print '<div class="center">';
		print img_picto('', 'tick', 'class="large"');
		print '<br>'.$langs->trans("RegistrationDoneAndModuleEnabled", $langs->transnoentitiesnoconv("BlockedLog"));

		// Go back to setup of module page
		if (GETPOST('origin') == 'initmodule') {
			print '<br><br>';
			print '<br><br>';
			print img_picto('', 'back').' ';
			print '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
		}
		print '</div>';
	}
}
if (empty($mode)) {
	if ($origin != 'initmodule') {
		print '<br>';
		print '<span class="opacitymedium">'.$langs->trans("UseThisFormToUpdate").'</span><br><br>';
	}

	$formSetup->newItem('Company')->setAsTitle();

	//Company name
	$item = $formSetup->newItem('BLOCKEDLOG_REGISTRATION_NAME');
	$item->defaultFieldValue = getDolGlobalString('BLOCKEDLOG_REGISTRATION_NAME', $mysoc->name);
	$item->fieldParams['isMandatory'] = 1;

	//Company email
	$item = $formSetup->newItem('BLOCKEDLOG_REGISTRATION_EMAIL');
	$item->defaultFieldValue = getDolGlobalString('BLOCKEDLOG_REGISTRATION_EMAIL', $mysoc->email);
	$item->setAsEmail();
	$item->fieldParams['isMandatory'] = 1;
	$item->cssClass = "minwidth300 maxwidth500 widthcentpercentminusx";

	//Company IDPROF1
	$item = $formSetup->newItem('BLOCKEDLOG_REGISTRATION_IDPROF1');
	$item->defaultFieldValue = getDolGlobalString('BLOCKEDLOG_REGISTRATION_IDPROF1', $mysoc->idprof1);
	$item->fieldParams['isMandatory'] = 1;

	//Company country code
	$country_code = getDolGlobalString('BLOCKEDLOG_REGISTRATION_COUNTRY_CODE', $mysoc->country_code);
	$item = $formSetup->newItem('BLOCKEDLOG_REGISTRATION_COUNTRY_CODE');
	$item->fieldInputOverride = $form->select_country($country_code, "BLOCKEDLOG_REGISTRATION_COUNTRY_CODE", '', 0, 'minwidth300', 'code2');
	$item->fieldParams['isMandatory'] = 1;

	//Company address
	$item = $formSetup->newItem('BLOCKEDLOG_REGISTRATION_ADDRESS');
	$item->defaultFieldValue = getDolGlobalString('BLOCKEDLOG_REGISTRATION_ADDRESS', $mysoc->address);
	$item->setAsTextarea();

	//Company state
	$item = $formSetup->newItem('BLOCKEDLOG_REGISTRATION_STATE');
	$state_id = 0;
	if (getDolGlobalString('MAIN_INFO_SOCIETE_STATE')) {
		$tmp = explode(':', getDolGlobalString('MAIN_INFO_SOCIETE_STATE'));
		$state_id = $tmp[0];
	}
	$stateid = getDolGlobalInt('BLOCKEDLOG_REGISTRATION_STATE', (int) $state_id);
	$item->fieldInputOverride = $formcompany->select_state($stateid, $country_code, "BLOCKEDLOG_REGISTRATION_STATE");

	//Company zip
	$item = $formSetup->newItem('BLOCKEDLOG_REGISTRATION_ZIP');
	$item->defaultFieldValue = getDolGlobalString('BLOCKEDLOG_REGISTRATION_ZIP', $mysoc->zip);
	$item->cssClass = "width100";

	//Company town
	$item = $formSetup->newItem('BLOCKEDLOG_REGISTRATION_TOWN');
	$item->defaultFieldValue = getDolGlobalString('BLOCKEDLOG_REGISTRATION_TOWN', $mysoc->town);

	$formSetup->newItem('ITProvider')->setAsTitle();

	//IT provider name
	$item = $formSetup->newItem('MAIN_INFO_ITPROVIDER_NAME');
	$item->defaultFieldValue = getDolGlobalString('MAIN_INFO_ITPROVIDER_NAME');

	//IT provider email
	$item = $formSetup->newItem('MAIN_INFO_ITPROVIDER_MAIL');
	$item->defaultFieldValue = getDolGlobalString('MAIN_INFO_ITPROVIDER_MAIL');
	$item->setAsEmail();
	$item->cssClass = "minwidth300 maxwidth500 widthcentpercentminusx";

	//IT provider IDPROF1
	$item = $formSetup->newItem('MAIN_INFO_ITPROVIDER_IDPROF1');
	$item->defaultFieldValue = getDolGlobalString('MAIN_INFO_ITPROVIDER_IDPROF1');

	//IT provider country code
	$item = $formSetup->newItem('MAIN_INFO_ITPROVIDER_COUNTRY');
	$item->defaultFieldValue = getDolGlobalString('MAIN_INFO_ITPROVIDER_COUNTRY');
	$item->fieldInputOverride = $form->select_country(getDolGlobalString('MAIN_INFO_ITPROVIDER_COUNTRY'), 'MAIN_INFO_ITPROVIDER_COUNTRY');

	//IT provider address
	$item = $formSetup->newItem('MAIN_INFO_ITPROVIDER_ADDRESS');
	$item->defaultFieldValue = getDolGlobalString('MAIN_INFO_ITPROVIDER_ADDRESS');
	$item->setAsTextarea();

	//IT provider state
	$item = $formSetup->newItem('MAIN_INFO_ITPROVIDER_STATE');
	$item->fieldInputOverride = $formcompany->select_state(getDolGlobalInt('MAIN_INFO_ITPROVIDER_STATE'), getDolGlobalString('MAIN_INFO_ITPROVIDER_COUNTRY'), "MAIN_INFO_ITPROVIDER_STATE");

	//IT provider zip
	$item = $formSetup->newItem('MAIN_INFO_ITPROVIDER_ZIP');
	$item->defaultFieldValue = getDolGlobalString('MAIN_INFO_ITPROVIDER_ZIP');
	$item->cssClass = "width100";

	//IT provider town
	$item = $formSetup->newItem('MAIN_INFO_ITPROVIDER_TOWN');
	$item->defaultFieldValue = getDolGlobalString('MAIN_INFO_ITPROVIDER_TOWN');

	$formSetup->formHiddenInputs['origin'] = GETPOST('origin');
	$formSetup->formHiddenInputs['withtab'] = $withtab;

	if (isRegistrationDataSavedAndPushed() && $origin != 'initmodule') {
		$formSetup->htmlButtonLabel = 'SaveUpdate';
	} else {
		$formSetup->htmlButtonLabel = 'SaveAndEnableModule';
	}

	print $formSetup->generateOutput(2, true, '', '');
}

if ($withtab) {
	print dol_get_fiche_end();
}

print '<br>';


// End of page
llxFooter();
$db->close();
