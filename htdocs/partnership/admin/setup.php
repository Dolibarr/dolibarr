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
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/partnership.lib.php';
//require_once "../class/myclass.class.php";

// Translations
$langs->loadLangs(array("admin", "partnership"));

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

	$modulemenu = (GETPOST('PARTNERSHIP_IS_MANAGED_FOR', 'alpha') == 'member') ? 'member' : 'thirdparty';
	$res = dolibarr_set_const($db, "PARTNERSHIP_IS_MANAGED_FOR", $modulemenu, 'chaine', 0, '', $conf->entity);

	$partnership = new modPartnership($db);

	$error += $partnership->delete_tabs();
	$error += $partnership->insert_tabs();

	$error += $partnership->delete_menus();
	$error += $partnership->insert_menus();

	if (GETPOSTISSET("PARTNERSHIP_NBDAYS_AFTER_MEMBER_EXPIRATION_BEFORE_CANCEL")) {
		dolibarr_set_const($db, "PARTNERSHIP_NBDAYS_AFTER_MEMBER_EXPIRATION_BEFORE_CANCEL", GETPOST("PARTNERSHIP_NBDAYS_AFTER_MEMBER_EXPIRATION_BEFORE_CANCEL", 'int'), 'chaine', 0, '', $conf->entity);
	}

	dolibarr_set_const($db, "PARTNERSHIP_BACKLINKS_TO_CHECK", GETPOST("PARTNERSHIP_BACKLINKS_TO_CHECK"), 'chaine', 0, '', $conf->entity);
}

if ($action) {
	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("SetupNotError"), null, 'errors');
	}
	header("Location: ".$_SERVER['PHP_SELF']);
	exit;
}

/*
 * View
 */

$title = $langs->trans('PartnershipSetup');

llxHeader('', $title);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

$head = partnershipAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $title, -1, 'partnership');

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

print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Setting").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Examples").'</td>';
print '</tr>';

print '<tr class="oddeven"><td>'.$langs->trans("PARTNERSHIP_IS_MANAGED_FOR").'</td>';
print '<td>';
print '<select class="flat minwidth100" id="select_PARTNERSHIP_IS_MANAGED_FOR" name="PARTNERSHIP_IS_MANAGED_FOR">';
print '<option value="thirdparty" '.((getDolGlobalString('PARTNERSHIP_IS_MANAGED_FOR', 'thirdparty') == 'thirdparty') ? 'selected' : '').'>'.$langs->trans("ThirdParty").'</option>';
print '<option value="member" '.((getDolGlobalString('PARTNERSHIP_IS_MANAGED_FOR', 'thirdparty') == 'member') ? 'selected' : '').'>'.$langs->trans("Members").'</option>';
print '</select>';
print ajax_combobox('select_PARTNERSHIP_IS_MANAGED_FOR');
print '</td>';
print '<td><span class="opacitymedium">'.$langs->trans("partnershipforthirdpartyormember").'</span></td>';
print '</tr>';


//if (getDolGlobalString('PARTNERSHIP_IS_MANAGED_FOR') == 'member') {
print '<tr class="oddeven"><td>'.$langs->trans("PARTNERSHIP_NBDAYS_AFTER_MEMBER_EXPIRATION_BEFORE_CANCEL").'</td>';
print '<td>';
$dnbdays = '30';
$backlinks = (!empty($conf->global->PARTNERSHIP_NBDAYS_AFTER_MEMBER_EXPIRATION_BEFORE_CANCEL)) ? $conf->global->PARTNERSHIP_NBDAYS_AFTER_MEMBER_EXPIRATION_BEFORE_CANCEL : $dnbdays;
print '<input class="maxwidth50" type="text" name="PARTNERSHIP_NBDAYS_AFTER_MEMBER_EXPIRATION_BEFORE_CANCEL" value="'.$backlinks.'">';
print '</td>';
print '<td><span class="opacitymedium">'.$dnbdays.'</span></td>';
print '</tr>';
//}

print '</table>';
print '</div>';

print '<br>';


print load_fiche_titre($langs->trans("ReferingWebsiteCheck"), '', '');

print '<span class="opacitymedium">'.$langs->trans("ReferingWebsiteCheckDesc").'</span><br>';
print '<br>';

print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Setting").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Examples").'</td>';
print '</tr>';

print '<tr class="oddeven"><td>'.$langs->trans("PARTNERSHIP_BACKLINKS_TO_CHECK").'</td>';
print '<td>';
$backlinks = (empty($conf->global->PARTNERSHIP_BACKLINKS_TO_CHECK) ? '' : $conf->global->PARTNERSHIP_BACKLINKS_TO_CHECK);
print '<input class="minwidth400" type="text" name="PARTNERSHIP_BACKLINKS_TO_CHECK" value="'.$backlinks.'">';
print '</td>';
print '<td><span class="opacitymedium">dolibarr.org|dolibarr.fr|dolibarr.es</span></td>';
print '</tr>';

print '</table>';
print '</div>';


print '<div class="center">';
print '<input type="submit" class="button reposition" value="'.$langs->trans("Modify").'">';
print '</div>';

print '</form>';

// End of page
llxFooter();
$db->close();
