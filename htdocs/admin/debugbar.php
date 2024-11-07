<?php
/* Copyright (C) 2005-2019 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2007      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2013	   Juanjo Menent        <jmenent@2byte.es>
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
 *	\file       htdocs/admin/debugbar.php
 *	\ingroup    debugbar
 *	\brief      Setup page for debugbar module
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

if (!$user->admin) {
	accessforbidden();
}

// Load translation files required by the page
$langs->loadLangs(array("admin", "other"));

$error = 0;
$action = GETPOST('action', 'aZ09');


/*
 * Actions
 */

// Set modes
if ($action == 'set') {
	$db->begin();

	$result1 = dolibarr_set_const($db, "DEBUGBAR_LOGS_LINES_NUMBER", GETPOSTINT('DEBUGBAR_LOGS_LINES_NUMBER'), 'chaine', 0, '', 0);
	$result2 = dolibarr_set_const($db, "DEBUGBAR_USE_LOG_FILE", GETPOSTINT('DEBUGBAR_USE_LOG_FILE'), 'chaine', 0, '', 0);
	if ($result1 < 0 || $result2 < 0) {
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


/*
 * View
 */

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-admin page-debugbar');

$form = new Form($db);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("DebugBarSetup"), $linkback, 'title_setup');

if (!function_exists('mb_check_encoding')) {
	$langs->load("errors");
	print info_admin($langs->trans("ErrorPHPNeedModule", 'mbstring'), 0, 0, 'error');
}

print '<br>';

// Level
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td>';
print '<td class="right"><input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'"></td>';
print "</tr>\n";

print '<tr class="oddeven"><td class="nowrap">'.$langs->trans("DEBUGBAR_USE_LOG_FILE").'</td>';
print '<td>';
print $form->selectyesno('DEBUGBAR_USE_LOG_FILE', getDolGlobalString('DEBUGBAR_USE_LOG_FILE'), 1);
print '</td><td>';
print '<span class="opacitymedium"> '.$langs->trans("UsingLogFileShowAllRecordOfSubrequestButIsSlower").'</span>';
print '</td></tr>';

print '<tr class="oddeven"><td class="nowrap">'.$langs->trans("DEBUGBAR_LOGS_LINES_NUMBER").'</td>';
print '<td><input type="text" class="flat width75" name="DEBUGBAR_LOGS_LINES_NUMBER" value="'.(!getDolGlobalString('DEBUGBAR_LOGS_LINES_NUMBER') ? 250 : $conf->global->DEBUGBAR_LOGS_LINES_NUMBER).'">'; // This slow seriously output
print '</td><td>';
print '<span class="opacitymedium">'.$langs->trans("WarningValueHigherSlowsDramaticalyOutput").'</span>';
print '</td></tr>';

print '</table>';
print "</form>\n";

// End of page
llxFooter();
$db->close();
