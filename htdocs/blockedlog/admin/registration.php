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
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'blockedlog', 'other'));

// Get Parameters
$action     = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$withtab    = GETPOSTINT('withtab');

// Access Control
if (!$user->admin) {
	accessforbidden();
}



/*
 * Actions
 */

// TODO


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
if (isRegistrationDataSaved()) {
	$texttop = '';
}

print load_fiche_titre($title.'<br>'.$texttop, $linkback, 'blockedlog', 0, '', '', $morehtmlcenter);

if ($withtab) {
	$head = blockedlogadmin_prepare_head(GETPOST('withtab', 'alpha'));
	print dol_get_fiche_head($head, 'blockedlog', '', -1);
}

//print $texttop;
//print '<br><br>';

if (in_array($mysoc->country_code, array('FR'))) {
	$htmltext = $langs->trans("UnalterableLogToolRegistrationFR").'<br>';
	print info_admin($htmltext, 0, 0, 'warning');
}

print '<br>';


// TODO Form to edit registration fields
// + code to (re)send data when modified and to init module of not initialized yet


if ($withtab) {
	print dol_get_fiche_end();
}

print '<br><br>';

// End of page
llxFooter();
$db->close();
