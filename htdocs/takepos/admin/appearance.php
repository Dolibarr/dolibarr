<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2017 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2019-2020 Andreu Bisquerra Gaya		<jove@bisquerra.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/takepos/admin/appearance.php
 *	\ingroup    takepos
 *	\brief      Setup page for TakePos module
 */

require '../../main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/takepos.lib.php";

// Security check
if (!$user->admin) {
	accessforbidden();
}

$langs->loadLangs(array("admin", "cashdesk", "commercial"));

/*
 * Actions
 */

if (GETPOST('action', 'alpha') == 'set') {
	$db->begin();

	$res = dolibarr_set_const($db, "TAKEPOS_COLOR_THEME", GETPOST('TAKEPOS_COLOR_THEME', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_LINES_TO_SHOW", GETPOST('TAKEPOS_LINES_TO_SHOW', 'alpha'), 'chaine', 0, '', $conf->entity);

	dol_syslog("admin/cashdesk: level ".GETPOST('level', 'alpha'));

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
} elseif (GETPOST('action', 'alpha') == 'setmethod') {
	dolibarr_set_const($db, "TAKEPOS_PRINT_METHOD", GETPOST('value', 'alpha'), 'chaine', 0, '', $conf->entity);
}


/*
 * View
 */

$form = new Form($db);
$formproduct = new FormProduct($db);

llxHeader('', $langs->trans("CashDeskSetup"));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("CashDeskSetup").' (TakePOS)', $linkback, 'title_setup');
$head = takepos_admin_prepare_head();
print dol_get_fiche_head($head, 'appearance', 'TakePOS', -1, 'cash-register');

print '<form action="'.$_SERVER["PHP_SELF"].'?terminal='.(empty($terminal) ? 1 : $terminal).'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="titlefield">'.$langs->trans("Parameters").'</td><td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

// Color theme
print '<tr class="oddeven"><td>';
print $langs->trans("ColorTheme");
print '<td colspan="2">';
$array = array(0=>"Eldy", 1=>$langs->trans("Colorful"));
print $form->selectarray('TAKEPOS_COLOR_THEME', $array, (empty($conf->global->TAKEPOS_COLOR_THEME) ? '0' : $conf->global->TAKEPOS_COLOR_THEME), 0);
print "</td></tr>\n";

// Hide category images to speed up
print '<tr class="oddeven"><td>';
print $langs->trans('HideCategoryImages');
print '<td colspan="2">';
print ajax_constantonoff("TAKEPOS_HIDE_CATEGORY_IMAGES", array(), $conf->entity, 0, 0, 1, 0);
print "</td></tr>\n";

// Hide category images to speed up
print '<tr class="oddeven"><td>';
print $langs->trans('HideProductImages');
print '<td colspan="2">';
print ajax_constantonoff("TAKEPOS_HIDE_PRODUCT_IMAGES", array(), $conf->entity, 0, 0, 1, 0);
print "</td></tr>\n";

// Lines to show
print '<tr class="oddeven"><td>';
print $langs->trans("NumberOfLinesToShow");
print '<td colspan="2">';
$array = array(1=>"1", 2=>"2", 3=>"3", 4=>"4", 5=>"5", 6=>"6");
print $form->selectarray('TAKEPOS_LINES_TO_SHOW', $array, (empty($conf->global->TAKEPOS_LINES_TO_SHOW) ? '2' : $conf->global->TAKEPOS_LINES_TO_SHOW), 0);
print "</td></tr>\n";

print '</table>';

print $form->buttonsSaveCancel("Save", '');

print "</form>\n";

print '<br>';

llxFooter();
$db->close();
