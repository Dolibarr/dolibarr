<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *   	\file       htdocs/admin/index.php
 *		\brief      Home page of setup area
 */

// Load Dolibarr environment
require '../main.inc.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'companies'));

$action = '';

if (!$user->admin) {
	accessforbidden();
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('homesetup'));


/*
 * View
 */

$form = new Form($db);

$wikihelp = 'EN:First_setup|FR:Premiers_paramétrages|ES:Primeras_configuraciones';
llxHeader('', $langs->trans("Setup"), $wikihelp, '', 0, 0, '', '', '', 'mod-admin page-index');


print load_fiche_titre($langs->trans("SetupArea"), '', 'tools');


if (getDolGlobalString('MAIN_MOTD_SETUPPAGE')) {
	$conf->global->MAIN_MOTD_SETUPPAGE = preg_replace('/<br(\s[\sa-zA-Z_="]*)?\/?>/i', '<br>', $conf->global->MAIN_MOTD_SETUPPAGE);
	if (getDolGlobalString('MAIN_MOTD_SETUPPAGE')) {
		$i = 0;
		$reg = array();
		while (preg_match('/__\(([a-zA-Z|@]+)\)__/i', $conf->global->MAIN_MOTD_SETUPPAGE, $reg) && $i < 100) {
			$tmp = explode('|', $reg[1]);
			if (!empty($tmp[1])) {
				$langs->load($tmp[1]);
			}
			$conf->global->MAIN_MOTD_SETUPPAGE = preg_replace('/__\('.preg_quote($reg[1]).'\)__/i', $langs->trans($tmp[0]), $conf->global->MAIN_MOTD_SETUPPAGE);
			$i++;
		}

		print "\n<!-- Start of welcome text for setup page -->\n";
		print '<table width="100%" class="notopnoleftnoright"><tr><td>';
		print dol_htmlentitiesbr($conf->global->MAIN_MOTD_SETUPPAGE);
		print '</td></tr></table><br>';
		print "\n<!-- End of welcome text for setup page -->\n";
	}
}

print '<span class="opacitymedium hideonsmartphone">';
print $langs->trans("SetupDescription1").' ';
print $langs->trans("AreaForAdminOnly").' ';
print $langs->trans("SetupDescription2", $langs->transnoentities("MenuCompanySetup"), $langs->transnoentities("Modules"));
print "<br><br>";
print '</span>';

print '<br>';

// Show info setup company
if (!getDolGlobalString('MAIN_INFO_SOCIETE_NOM') || !getDolGlobalString('MAIN_INFO_SOCIETE_COUNTRY') || getDolGlobalString('MAIN_INFO_SOCIETE_SETUP_TODO_WARNING')) {
	$setupcompanynotcomplete = 1;
}

print '<section class="setupsection">';

print img_picto('', 'company', 'class="paddingright valignmiddle double"').' '.$langs->trans("SetupDescriptionLink", DOL_URL_ROOT.'/admin/company.php?mainmenu=home'.(empty($setupcompanynotcomplete) ? '' : '&action=edit&token='.newToken()), $langs->transnoentities("Setup"), $langs->transnoentities("MenuCompanySetup"));
print '<br><br>';
print $langs->trans("SetupDescription3b");
if (!empty($setupcompanynotcomplete)) {
	$langs->load("errors");
	$warnpicto = img_warning($langs->trans("WarningMandatorySetupNotComplete"), 'style="padding-right: 6px;"');
	print '<br><div class="warning"><a href="'.DOL_URL_ROOT.'/admin/company.php?mainmenu=home'.(empty($setupcompanynotcomplete) ? '' : '&action=edit').'">'.$warnpicto.$langs->trans("WarningMandatorySetupNotComplete").'</a></div>';
}

print '</section>';

print '<br>';
print '<br>';

print '<section class="setupsection">';

// Define $nbmodulesnotautoenabled - TODO This code is at different places
$nbmodulesnotautoenabled = count($conf->modules);
$listofmodulesautoenabled = array('agenda', 'fckeditor', 'export', 'import');
foreach ($listofmodulesautoenabled as $moduleautoenable) {
	if (in_array($moduleautoenable, $conf->modules)) {
		$nbmodulesnotautoenabled--;
	}
}

// Show info setup module
print img_picto('', 'cog', 'class="paddingright valignmiddle double"').' '.$langs->trans("SetupDescriptionLink", DOL_URL_ROOT.'/admin/modules.php?mainmenu=home', $langs->transnoentities("Setup"), $langs->transnoentities("Modules"));
print '<br><br>'.$langs->trans("SetupDescription4b");
if ($nbmodulesnotautoenabled <= getDolGlobalInt('MAIN_MIN_NB_ENABLED_MODULE_FOR_WARNING', 1)) {	// If only minimal initial modules enabled
	$langs->load("errors");
	$warnpicto = img_warning($langs->trans("WarningEnableYourModulesApplications"), 'style="padding-right: 6px;"');
	print '<br><div class="warning"><a href="'.DOL_URL_ROOT.'/admin/modules.php?mainmenu=home">'.$warnpicto.$langs->trans("WarningEnableYourModulesApplications").'</a></div>';
}

print '</section>';

print '<br>';
print '<br>';
print '<br>';

// Add hook to add information
$parameters = array();
$object = new stdClass();
$reshook = $hookmanager->executeHooks('addHomeSetup', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
print $hookmanager->resPrint;
if (empty($reshook)) {
	// Show into other
	//print '<span class="opacitymedium hideonsmartphone">'.$langs->trans("SetupDescription5")."</span><br>";
	print '<br class="hideonsmartphone">';

	// Show logo
	print '<div class="center"><div class="logo_setup"></div></div>';
}

// End of page
llxFooter();
$db->close();
