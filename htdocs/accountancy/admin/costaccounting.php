<?php
/* Copyright (C) 2019      Alexandre Spangaro   <aspangaro@open-dsi.fr>
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
 * \file		htdocs/accountancy/admin/costaccounting.php
 * \ingroup		Advanced accountancy
 * \brief		Setup page to configure cost accounting
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("compta","bills","admin","accountancy"));

// Security access
if (empty($user->rights->accounting->chartofaccount))
{
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');

/*
 * Actions
 */
if ($action == 'setenablecostaccounting') {
	$setenablecostaccounting = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "ACCOUNTING_ENABLE_COST_ACCOUNTING", $setenablecostaccounting, 'yesno', 0, '', $conf->entity);
	if (! $res > 0)
		$error ++;
		if (! $error) {
			setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("Error"), null, 'mesgs');
		}
}

/*
 * View
 */

llxHeader();

$form = new Form($db);

//$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans('ConfigAccountingExpertCost'), $linkback, 'title_setup');

print '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="update">';

// Others params
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">' . $langs->trans('Parameter') . '</td>';
print "</tr>\n";

print '<tr class="oddeven">';
print '<td>' . $langs->trans("ACCOUNTING_ENABLE_COST_ACCOUNTING") . '<br /><i>' . $langs->trans("AccountingCostExplanation") . '</i></td>';
if (! empty($conf->global->ACCOUNTING_ENABLE_COST_ACCOUNTING)) {
    print '<td class="right"><a href="' . $_SERVER['PHP_SELF'] . '?action=setenablecostaccounting&value=0">';
    print img_picto($langs->trans("Activated"), 'switch_on');
    print '</a></td>';
} else {
    print '<td class="right"><a href="' . $_SERVER['PHP_SELF'] . '?action=setenablecostaccounting&value=1">';
    print img_picto($langs->trans("Disabled"), 'switch_off');
    print '</a></td>';
}
print '</tr>';
print '</table>';
print '</form>';

// End of page
llxFooter();
$db->close();
