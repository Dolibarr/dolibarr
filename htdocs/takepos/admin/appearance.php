<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2017 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2019-2020 Andreu Bisquerra Gaya		<jove@bisquerra.com>
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
 *	\file       htdocs/takepos/admin/appearance.php
 *	\ingroup    takepos
 *	\brief      Setup page for TakePos module
 */

// Load Dolibarr environment
require '../../main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/takepos.lib.php";

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Security check
if (!$user->admin) {
	accessforbidden();
}

$langs->loadLangs(array("admin", "cashdesk", "commercial"));

/*
 * Actions
 */
$error = 0;

if (GETPOST('action', 'alpha') == 'set') {
	$db->begin();

	$res = dolibarr_set_const($db, "TAKEPOS_COLOR_THEME", GETPOST('TAKEPOS_COLOR_THEME', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_LINES_TO_SHOW", GETPOST('TAKEPOS_LINES_TO_SHOW', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (GETPOSTISSET('TAKEPOS_SHOW_PRODUCT_REFERENCE')) {
		$res = dolibarr_set_const($db, "TAKEPOS_SHOW_PRODUCT_REFERENCE", GETPOST('TAKEPOS_SHOW_PRODUCT_REFERENCE', 'alpha'), 'chaine', 0, '', $conf->entity);
	}

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
}


/*
 * View
 */

$form = new Form($db);
$formproduct = new FormProduct($db);

llxHeader('', $langs->trans("CashDeskSetup"), '', '', 0, 0, '', '', '', 'mod-takepos page-admin_appearance');

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("CashDeskSetup").' (TakePOS)', $linkback, 'title_setup');
$head = takepos_admin_prepare_head();
print dol_get_fiche_head($head, 'appearance', 'TakePOS', -1, 'cash-register');

print '<form action="'.$_SERVER["PHP_SELF"].'?terminal='.(empty($terminal) ? 1 : $terminal).'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set">';

print '<br>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td><td></td>';
print "</tr>\n";

// Color theme
print '<tr class="oddeven"><td>';
print $langs->trans("ColorTheme");
print '</td><td>';
$array = array(0=>"Eldy", 1=>$langs->trans("Colorful"));
print $form->selectarray('TAKEPOS_COLOR_THEME', $array, (!getDolGlobalString('TAKEPOS_COLOR_THEME') ? '0' : $conf->global->TAKEPOS_COLOR_THEME), 0);
print "</td></tr>\n";

// Don't display category section
print '<tr class="oddeven"><td>';
print $langs->trans('HideCategories');
print '</td><td>';
print ajax_constantonoff("TAKEPOS_HIDE_CATEGORIES", array(), $conf->entity, 0, 0, 1, 0);
print "</td></tr>\n";

// Hide category images to speed up
if (!getDolGlobalString('TAKEPOS_HIDE_CATEGORIES')) {
	print '<tr class="oddeven"><td>';
	print $langs->trans('HideCategoryImages');
	print '</td><td>';
	print ajax_constantonoff("TAKEPOS_HIDE_CATEGORY_IMAGES", array(), $conf->entity, 0, 0, 1, 0);
	print "</td></tr>\n";
}

// Hide category images to speed up
print '<tr class="oddeven"><td>';
print $langs->trans('HideProductImages');
print '</td><td>';
print ajax_constantonoff("TAKEPOS_HIDE_PRODUCT_IMAGES", array(), $conf->entity, 0, 0, 1, 0);
print "</td></tr>\n";

// View reference or label of products
print '<tr class="oddeven"><td>';
print $langs->trans('ShowProductReference');
print '</td><td>';
$array = array("0"=>$langs->trans("Label"), 1=>$langs->trans("Ref").'+'.$langs->trans("Label"), 2=>$langs->trans("Ref"));
print $form->selectarray('TAKEPOS_SHOW_PRODUCT_REFERENCE', $array, getDolGlobalInt('TAKEPOS_SHOW_PRODUCT_REFERENCE', 2), 0);
//print ajax_constantonoff("TAKEPOS_SHOW_PRODUCT_REFERENCE", array(), $conf->entity, 0, 0, 1, 0);
print "</td></tr>\n";

// Lines to show
print '<tr class="oddeven"><td>';
print $langs->trans("NumberOfLinesToShow");
print '</td><td>';
$array = array(1=>"1", 2=>"2", 3=>"3", 4=>"4", 5=>"5", 6=>"6");
print $form->selectarray('TAKEPOS_LINES_TO_SHOW', $array, getDolGlobalInt('TAKEPOS_LINES_TO_SHOW', 2), 0);
print "</td></tr>\n";

// Hide stock on line
print '<tr class="oddeven"><td>';
print $langs->trans('HideStockOnLine');
print '</td><td>';
print ajax_constantonoff("TAKEPOS_HIDE_STOCK_ON_LINE", array(), $conf->entity, 0, 0, 1, 0);
print "</td></tr>\n";

// Only the products in stock
print '<tr class="oddeven"><td>';
print $langs->trans('ShowOnlyProductInStock');
print '</td><td>';
print ajax_constantonoff("TAKEPOS_PRODUCT_IN_STOCK", array(), $conf->entity, 0, 0, 1, 0);
print "</td></tr>\n";

// View description of the categories
print '<tr class="oddeven"><td>';
print $langs->trans('ShowCategoryDescription');
print '</td><td>';
print ajax_constantonoff("TAKEPOS_SHOW_CATEGORY_DESCRIPTION", array(), $conf->entity, 0, 0, 1, 0);
print "</td></tr>\n";

print '</table>';

print $form->buttonsSaveCancel("Save", '');

print "</form>\n";

print '<br>';

llxFooter();
$db->close();
