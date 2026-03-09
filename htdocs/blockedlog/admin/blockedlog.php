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
 *	\file       htdocs/blockedlog/admin/blockedlog.php
 *  \ingroup    blockedlog
 *  \brief      Page setup for blockedlog module
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'blockedlog', 'other'));

// Get Parameters
$action     = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

$withtab    = GETPOSTINT('withtab');
$origin     = GETPOST('origin');

// Access Control
if (!$user->admin || !isModEnabled('blockedlog')) {
	accessforbidden();
}


/*
 * Actions
 */

$reg = array();
if (preg_match('/set_(.*)/', $action, $reg)) {
	$code = $reg[1];
	$values = GETPOST($code);
	if (is_array($values)) {
		$values = implode(',', $values);
	}

	if (dolibarr_set_const($db, $code, $values, 'chaine', 0, '', $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"].($withtab ? '?withtab='.$withtab : ''));
		exit;
	} else {
		dol_print_error($db);
	}
}

if (preg_match('/del_(.*)/', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"].($withtab ? '?withtab='.$withtab : ''));
		exit;
	} else {
		dol_print_error($db);
	}
}


/*
 *	View
 */

$form = new Form($db);
$block_static = new BlockedLog($db);
$block_static->loadTrackedEvents();

$title = $langs->trans("ModuleSetup").' '.$langs->trans('BlockedLog');
$help_url="EN:Module_Unalterable_Archives_-_Logs|FR:Module_Archives_-_Logs_Inaltérable";

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-blockedlog page-admin_blockedlog');

$linkback = '';
if ($withtab) {
	$linkback = '<a href="'.dolBuildUrl($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php', ['restore_lastsearch_values' => 1]).'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';
}

$morehtmlcenter = '';

$registrationnumber = getHashUniqueIdOfRegistration();
$texttop = '<small class="opacitymedium">'.$langs->trans("RegistrationNumber").':</small> <small>'.dol_trunc($registrationnumber, 10).'</small>';
if (!isRegistrationDataSavedAndPushed()) {
	$texttop = '';
}

print load_fiche_titre($title.'<br>'.$texttop, $linkback, 'blockedlog', 0, '', '', $morehtmlcenter);

if ($withtab) {
	$head = blockedlogadmin_prepare_head(GETPOST('withtab', 'alpha'));
	print dol_get_fiche_head($head, 'technicalinfo', '', -1);
}

print '<span class="opacitymedium">'.$langs->trans("BlockedLogDesc")."</span><br>\n";

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
		/*
		$htmltext = $langs->trans("UnalterableLogToolRegistrationFR").'<br>';
		$htmltext .= $langs->trans("InformationWillBePublishedTo");
		$htmltext .= '<br>'.$langs->trans("InformationWillBePublishedTo2", $organization_for_ping, $dataprivacy_url);
		$htmltext .= '<br>'.$langs->trans("InformationWillBePublishedTo3");

		print info_admin($htmltext, 0, 0, 'warning');
		*/
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
		/*
		$htmltext = $langs->trans("UnalterableLogTool1FR").'<br>';
		print info_admin($htmltext, 0, 0, 'warning');
		*/
	}
}


print '<br>';

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>';
print '<td></td>';
print "</tr>\n";

print '<tr class="oddeven">';
print '<td class="titlefieldmiddle">';
print $langs->trans("CompanyInitialKey").'</td><td>';
print $block_static->getOrInitFirstSignature();
print '</td></tr>';

/*
if (getDolGlobalString('BLOCKEDLOG_USE_REMOTE_AUTHORITY')) {
	// Example with a yes / no select
	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("BlockedLogAuthorityUrl").img_info($langs->trans('BlockedLogAuthorityNeededToStoreYouFingerprintsInNonAlterableRemote')).'</td>';
	print '<td class="right" width="300">';

	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="set_BLOCKEDLOG_AUTHORITY_URL">';
	print '<input type="hidden" name="withtab" value="'.$withtab.'">';
	print '<input type="text" name="BLOCKEDLOG_AUTHORITY_URL" value="' . getDolGlobalString('BLOCKEDLOG_AUTHORITY_URL').'" size="40" />';
	print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'">';
	print '</form>';

	print '</td></tr>';
}
*/


// Show the input of countries not allowed for disabling
print '<tr class="oddeven">';
print '<td>';
print $form->textwithpicto($langs->transnoentitiesnoconv("BlockedLogDisableNotAllowedForCountry"), $langs->transnoentitiesnoconv("BlockedLogDisableNotAllowedForCountry2"));
print '</td>';
print '<td>';

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY">';
print '<input type="hidden" name="withtab" value="'.$withtab.'">';

$sql = "SELECT rowid, code as code_iso, code_iso as code_iso3, label, favorite";
$sql .= " FROM ".MAIN_DB_PREFIX."c_country";
$sql .= " WHERE active > 0";

$countryArray = array();
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$countryArray[$obj->code_iso] = ($obj->code_iso && $langs->transnoentitiesnoconv("Country".$obj->code_iso) != "Country".$obj->code_iso ? $langs->transnoentitiesnoconv("Country".$obj->code_iso) : ($obj->label != '-' ? $obj->label : ''));
	}
}

$selected = !getDolGlobalString('BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY') ? array() : explode(',', getDolGlobalString('BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY'));

// Can module be disabled
$canbedisabled = $block_static->canBeDisabled();

print $form->multiselectarray('BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY', $countryArray, $selected, 0, 0, '', 0, 0, $canbedisabled ? '' : 'disabled');
print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'"'.($canbedisabled ? '' : ' disabled').'>';
print '</form>';

print '</td>';


print '<tr class="oddeven">';
print '<td class="">';
print $langs->trans("ListOfTrackedEvents").'</td><td>';
$arrayoftrackedevents = $block_static->trackedevents;
foreach ($arrayoftrackedevents as $key => $val) {
	if (preg_match('/^separator/i', $key)) {
		continue;
	}
	print $key.' - ';
	if (is_array($val)) {
		print $langs->trans($val['labelhtml']).'<br>';
	} else {
		print $langs->trans($val).'<br>';
	}
}

print '</td></tr>';

print '</tr>';

print '</table>';
print '</div>';


print '<br><br>';


print '<!-- Link to pay -->';
print '<span class="fas fa-external-link-alt" style=""></span> <span class="opacitymedium">'.$langs->trans("DebugTools").'</span><br>';
print '<br>';

$urlforceregistration = DOL_MAIN_URL_ROOT.'/index.php?forceregistration=1';
print $langs->trans("URLToForceRegistration").'<br>';
print '<div class="urllink"><input type="text" id="forceregistration" spellcheck="false" class="quatrevingtpercentminusx" value="'.$urlforceregistration.'"><a class="" href="'.$urlforceregistration.'" target="_blank" rel="noopener noreferrer"><span class="fas fa-external-link-alt paddingleft" style=""></span></a></div>';
print ajax_autoselect('forceregistration');

print '<br>';

$urlforcepushcounter = DOL_MAIN_URL_ROOT.'/index.php?forcepushcounter=1';
print $langs->trans("URLToForcePushOfBlockedLogCounter").'<br>';
print '<div class="urllink"><input type="text" id="forcepushcounter" spellcheck="false" class="quatrevingtpercentminusx" value="'.$urlforcepushcounter.'"><a class="" href="'.$urlforcepushcounter.'" target="_blank" rel="noopener noreferrer"><span class="fas fa-external-link-alt paddingleft" style=""></span></a></div>';
print ajax_autoselect('forcepushcounter');



if ($withtab) {
	print dol_get_fiche_end();
}

print '<br><br>';

// End of page
llxFooter();
$db->close();
