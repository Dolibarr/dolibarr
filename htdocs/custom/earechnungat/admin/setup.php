<?php
/* Copyright (C) 2025 Florian Hoedl <florian@hoedl.co>
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
 * \file       htdocs/custom/earechnungat/admin/setup.php
 * \ingroup    earechnungat
 * \brief      Setup page for EARechnungAT module
 */

// Load Dolibarr environment
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once __DIR__ . '/../lib/earechnungat.lib.php';

// Load translation files
$langs->loadLangs(array("admin", "earechnungat@earechnungat"));

// Security check
if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');

/*
 * Actions
 */

if ($action === 'update') {
	$taxMode = GETPOST('EARECHNUNGAT_TAX_MODE', 'alpha');
	$fiscalStart = GETPOST('EARECHNUNGAT_FISCAL_YEAR_START', 'alpha');

	dolibarr_set_const($db, 'EARECHNUNGAT_TAX_MODE', $taxMode, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'EARECHNUNGAT_FISCAL_YEAR_START', $fiscalStart, 'chaine', 0, '', $conf->entity);

	setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
}

/*
 * View
 */

$title = $langs->trans('EARechnungATSetupPage');

llxHeader('', $title);

$head = earechnungatAdminPrepareHead();

print dol_get_fiche_head($head, 'settings', $langs->trans('EARechnungATMenu'), -1, 'fa-file-invoice');

print '<form method="POST" action="' . dol_escape_htmltag($_SERVER["PHP_SELF"]) . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Parameter") . '</td>';
print '<td>' . $langs->trans("Value") . '</td>';
print '</tr>';

// Tax mode
print '<tr class="oddeven">';
print '<td>' . $langs->trans('TaxMode') . '</td>';
print '<td>';
$currentMode = getDolGlobalString('EARECHNUNGAT_TAX_MODE', 'payment');
print '<select name="EARECHNUNGAT_TAX_MODE" class="flat minwidth200">';
print '<option value="payment"' . ($currentMode === 'payment' ? ' selected' : '') . '>' . $langs->trans('TaxModePayment') . '</option>';
print '<option value="invoice"' . ($currentMode === 'invoice' ? ' selected' : '') . '>' . $langs->trans('TaxModeInvoice') . '</option>';
print '</select>';
print '</td>';
print '</tr>';

// Fiscal year start
print '<tr class="oddeven">';
print '<td>' . $langs->trans('FiscalYearStart') . '</td>';
print '<td>';
$currentStart = getDolGlobalString('EARECHNUNGAT_FISCAL_YEAR_START', '01-01');
print '<input type="text" name="EARECHNUNGAT_FISCAL_YEAR_START" value="' . dol_escape_htmltag($currentStart) . '" size="5" placeholder="MM-DD">';
print ' <span class="opacitymedium">(MM-DD, z.B. 01-01)</span>';
print '</td>';
print '</tr>';

print '</table>';

print '<br>';
print '<div class="center">';
print '<input type="submit" class="button button-save" value="' . $langs->trans("Save") . '">';
print '</div>';

print '</form>';

print dol_get_fiche_end();

llxFooter();
$db->close();
