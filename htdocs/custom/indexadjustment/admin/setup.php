<?php
/* Copyright (C) 2025 Florian Hödl <florian@hoedl.co>
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
 * \file       admin/setup.php
 * \ingroup    indexadjustment
 * \brief      Admin page to configure Index Adjustment module
 */

// Load Dolibarr environment
require_once '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/custom/indexadjustment/lib/indexadjustment.lib.php';

// Load translations
$langs->loadLangs(array("admin", "indexadjustment@indexadjustment"));

// Security check
if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');

// Parameters
$arrayofparameters = array(
	'INDEXADJUSTMENT_DEFAULT_THRESHOLD' => array(
		'type' => 'string',
		'css' => 'minwidth200',
		'enabled' => 1,
		'help' => $langs->trans('HelpDefaultThreshold'),
	),
	'INDEXADJUSTMENT_ROUNDING_MODE' => array(
		'type' => 'select',
		'values' => array(
			'standard' => $langs->trans('RoundingStandard'),
			'up' => $langs->trans('RoundingUp'),
			'down' => $langs->trans('RoundingDown'),
		),
		'enabled' => 1,
		'help' => $langs->trans('HelpRoundingMode'),
	),
	'INDEXADJUSTMENT_ROLLBACK_DAYS' => array(
		'type' => 'string',
		'css' => 'minwidth200',
		'enabled' => 1,
		'help' => $langs->trans('HelpRollbackDays'),
	),
	'INDEXADJUSTMENT_VPI_BASE_YEAR' => array(
		'type' => 'string',
		'css' => 'minwidth200',
		'enabled' => 1,
		'help' => $langs->trans('HelpVPIBaseYear'),
	),
);

/*
 * Actions
 */

include DOL_DOCUMENT_ROOT . '/core/actions_setmoduleoptions.inc.php';

/*
 * View
 */

$page_name = "IndexAdjustmentSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = indexadjustmentAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans("ModuleIndexAdjustmentName"), -1, "fa-percent");

// Setup page content
print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Parameter") . '</td>';
print '<td>' . $langs->trans("Value") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '</tr>';

foreach ($arrayofparameters as $constname => $val) {
	if ($val['enabled'] == 1) {
		$setupno498 = 1;
		print '<tr class="oddeven">';

		// Parameter name
		$labelkey = preg_replace('/^INDEXADJUSTMENT_/', '', $constname);
		print '<td>' . $langs->trans($labelkey) . '</td>';

		// Value
		print '<td>';
		if ($val['type'] == 'select') {
			print '<select class="flat ' . ($val['css'] ? $val['css'] : '') . '" name="' . $constname . '">';
			foreach ($val['values'] as $key => $value) {
				$selected = (getDolGlobalString($constname) == $key) ? 'selected' : '';
				print '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
			}
			print '</select>';
		} else {
			print '<input type="text" class="flat ' . ($val['css'] ? $val['css'] : '') . '" name="' . $constname . '" value="' . dol_escape_htmltag(getDolGlobalString($constname)) . '">';
		}
		print '</td>';

		// Description
		print '<td>' . ($val['help'] ? $val['help'] : '') . '</td>';

		print '</tr>';
	}
}

print '</table>';

print '<br>';
print '<div class="center">';
print '<input class="button button-save" type="submit" value="' . $langs->trans("Save") . '">';
print '</div>';

print '</form>';

print dol_get_fiche_end();

// Footer
llxFooter();
$db->close();
