<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021 Dorian Laurent <i.merraha@sofimedmaroc.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    partnership/admin/setup.php
 * \ingroup partnership
 * \brief   Partnership setup page.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/partnership.lib.php';
//require_once "../class/myclass.class.php";

// Translations
$langs->loadLangs(array("admin", "partnership@partnership"));

// Security check
if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$value 	= GETPOST('value', 'alpha');


$error = 0;

/*
 * Actions
 */

$nomessageinsetmoduleoptions = 1;
include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';


if ($action == 'setting') {
	require_once DOL_DOCUMENT_ROOT."/core/modules/modPartnership.class.php";
	$partnership = new modPartnership($db);

	$value = GETPOST('managed_for', 'alpha');


	$modulemenu = ($value == 'member') ? 'member' : 'thirdparty';
	$res = dolibarr_set_const($db, "PARTNERSHIP_IS_MANAGED_FOR", $modulemenu, 'chaine', 0, '', $conf->entity);

	$partnership->tabs = array();
	if ($modulemenu == 'member') {
		$partnership->tabs[] = array('data'=>'member:+partnership:Partnership:partnership@partnership:$user->rights->partnership->read:/partnership/partnership.php?socid=__ID__');
		$fk_mainmenu = "members";
	} else {
		$partnership->tabs[] = array('data'=>'thirdparty:+partnership:Partnership:partnership@partnership:$user->rights->partnership->read:/partnership/partnership.php?socid=__ID__');
		$fk_mainmenu = "companies";
	}

	foreach ($partnership->menu as $key => $menu) {
		$partnership->menu[$key]['mainmenu'] = $fk_mainmenu;

		if ($menu['leftmenu'] == 'partnership')
			$partnership->menu[$key]['fk_menu'] = 'fk_mainmenu='.$fk_mainmenu;
		else $partnership->menu[$key]['fk_menu'] = 'fk_mainmenu='.$fk_mainmenu.',fk_leftmenu=partnership';
	}

	$error += $partnership->delete_tabs();
	$error += $partnership->insert_tabs();

	$error += $partnership->delete_menus();
	$error += $partnership->insert_menus();
}

if ($action) {
	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("SetupNotError"), null, 'errors');
	}
}

/*
 * View
 */

$title = $langs->trans('PartnershipSetup');
$tab = $langs->trans("PartnershipSetup");

llxHeader('', $title);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

$head = partnershipAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $tab, -1, 'partnership');

$form = new Form($db);

// Module to manage partnership / services code
$dirpartnership = array('/core/modules/partnership/');
$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);


/*
 * Other conf
 */

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="setting">';
print '<input type="hidden" name="page_y" value="">';

print '<table class="noborder centpercent">';

// Default partnership price base type
print '<tr class="oddeven">';
print '<td>'.$langs->trans("PartnershipManagedFor").'</td>';
print '<td class="right">';
	print '<select class="flat minwidth100" id="select_managed_for" name="managed_for">';
		print '<option value="thirdparty" '.(($conf->global->PARTNERSHIP_IS_MANAGED_FOR == 'thirdparty') ? 'selected' : '').'>'.$langs->trans("ThirdParty").'</option>';
		print '<option value="member" '.(($conf->global->PARTNERSHIP_IS_MANAGED_FOR == 'member') ? 'selected' : '').'>'.$langs->trans("Members").'</option>';
	print '</select>';
print '</td>';
print '</tr>';

print '</table>';

print '<div class="center">';
print '<input type="submit" class="button reposition" value="'.$langs->trans("Modify").'">';
print '</div>';

print '</form>';

// End of page
llxFooter();
$db->close();
