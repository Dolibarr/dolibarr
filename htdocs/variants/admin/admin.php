<?php
/* Copyright (C) 2016   	Marcos García   	<marcosgdf@gmail.com>
 * Copyright (C) 2018-2024	Frédéric France 	<frederic.france@free.fr>
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

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/variants/lib/variants.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Form $form
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$langs->loadLangs(array("admin", "products"));

$action = GETPOST('action', 'alphanohtml');

// Security check
if (!$user->admin || !isModEnabled('variants')) {
	accessforbidden();
}

$error = 0;


/*
 * Actions
 */

if ($action) {
	$value = GETPOST('PRODUIT_ATTRIBUTES_HIDECHILD');

	if (!dolibarr_set_const($db, 'PRODUIT_ATTRIBUTES_HIDECHILD', $value, 'chaine', 0, '', $conf->entity)) {
		setEventMessages($langs->trans('CoreErrorMessage'), null, 'errors');
		$error++;
	}

	if (!dolibarr_set_const($db, 'PRODUIT_ATTRIBUTES_SEPARATOR', GETPOST('PRODUIT_ATTRIBUTES_SEPARATOR'), 'chaine', 0, '', $conf->entity)) {
		setEventMessages($langs->trans('CoreErrorMessage'), null, 'errors');
		$error++;
	}

	if (!dolibarr_set_const($db, 'VARIANT_ALLOW_STOCK_MOVEMENT_ON_VARIANT_PARENT', GETPOST('VARIANT_ALLOW_STOCK_MOVEMENT_ON_VARIANT_PARENT'), 'chaine', 0, '', $conf->entity)) {
		setEventMessages($langs->trans('CoreErrorMessage'), null, 'errors');
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
	}
}

$title = $langs->trans('ModuleSetup').' '.$langs->trans('Module610Name');
llxHeader('', $title);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

$head = adminProductAttributePrepareHead();

print dol_get_fiche_head($head, 'admin', $title, -1, 'product');

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<th>'.$langs->trans("Parameters").'</th>'."\n";
print '<th class="right" width="60">'.$langs->trans("Value").'</th>'."\n";
print '</tr>'."\n";

print '<tr class="oddeven"><td>'.$langs->trans('HideProductCombinations').'</td><td>';
print $form->selectyesno("PRODUIT_ATTRIBUTES_HIDECHILD", getDolGlobalString('PRODUIT_ATTRIBUTES_HIDECHILD'), 1).'</td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans('CombinationsSeparator').'</td>';
if (isset($conf->global->PRODUIT_ATTRIBUTES_SEPARATOR)) {
	$separator = getDolGlobalString('PRODUIT_ATTRIBUTES_SEPARATOR');
} else {
	$separator = "_";
}
print '<td class="right"><input size="3" type="text" class="flat" name="PRODUIT_ATTRIBUTES_SEPARATOR" value="'.$separator.'"></td></tr>';

print '<tr class="oddeven"><td>'.$form->textwithpicto($langs->trans('AllowStockMovementVariantParent'), $langs->trans('AllowStockMovementVariantParentHelp')).'</td><td>';
print $form->selectyesno("VARIANT_ALLOW_STOCK_MOVEMENT_ON_VARIANT_PARENT", getDolGlobalString('VARIANT_ALLOW_STOCK_MOVEMENT_ON_VARIANT_PARENT'), 1).'</td></tr>';

print '</table>';

print '<br><div class="center"><input type="submit" value="'.$langs->trans("Save").'" class="button button-save"></div>';

print '</form>';

// End of page
llxFooter();
$db->close();
