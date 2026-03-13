<?php
/* Copyright (C) 2017      ATM Consulting      <contact@atm-consulting.fr>
 * Copyright (C) 2017-2018 Laurent Destailleur <eldy@destailleur.fr>
 * Copyright (C) 2024      Frédéric France     <frederic.france@free.fr>
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
 *	\file       htdocs/blockedlog/admin/documentation.php
 *  \ingroup    blockedlog
 *  \brief      Page setup for blockedlog module (user documentation)
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
if (!$user->admin && !userIsTaxAuditor()) {
	accessforbidden();
}


/*
 * Actions
 */

// None


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

$help_url="EN:Module_Unalterable_Archives_-_Logs|FR:Module_Archives_-_Logs_Inaltérable";

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-blockedlog page-admin_blockedlog');

if (GETPOST('withtab', 'alpha')) {
	$linkback = '<a href="'.dolBuildUrl($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php', ['restore_lastsearch_values' => 1]).'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';
} else {
	$linkback='';
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
	print dol_get_fiche_head($head, 'documentation', '', -1);
} else {
	print '<br>';
}

if (!userIsTaxAuditor()) {
	print '<span class="opacitymedium">'.$langs->trans("BlockedLogDesc")."</span><br>\n";
}

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
if (in_array($mysoc->country_code, array('FR')) && !userIsTaxAuditor()) {
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

print '<center><br>';
print $langs->trans("YouMayFindDocumentOn").'<br>';
print '<br>';
print img_picto('', 'url').' <a href="https://www.dolibarr.org/certifications-lf" target="_blank">https://www.dolibarr.org/certifications-lf</a>';
print '<center>';


if ($withtab) {
	print dol_get_fiche_end();
}

print '<br>';


// End of page
llxFooter();
$db->close();
