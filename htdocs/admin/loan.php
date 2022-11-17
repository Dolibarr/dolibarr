<?php
/* Copyright (C) 2014-2017  Alexandre Spangaro	<aspangaro@open-dsi.fr>
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
 *
 */

/**
 * \file		htdocs/admin/loan.php
 * \ingroup		loan
 * \brief		Setup page to configure loan module
 */

require '../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
if (!empty($conf->accounting->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('admin', 'loan'));

// Security check
if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');

// Other parameters LOAN_*
$list = array(
		'LOAN_ACCOUNTING_ACCOUNT_CAPITAL',
		'LOAN_ACCOUNTING_ACCOUNT_INTEREST',
		'LOAN_ACCOUNTING_ACCOUNT_INSURANCE'
);

/*
 * Actions
 */

if ($action == 'update') {
	$error = 0;

	foreach ($list as $constname) {
		$constvalue = GETPOST($constname, 'alpha');

		if (!dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
			$error++;
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

/*
 * View
 */

llxHeader();

$form = new Form($db);
if (!empty($conf->accounting->enabled)) {
	$formaccounting = new FormAccounting($db);
}

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans('ConfigLoan'), $linkback, 'title_setup');

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

/*
 *  Params
 */
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans('Options').'</td>';
print "</tr>\n";

foreach ($list as $key) {
	print '<tr class="oddeven value">';

	// Param
	$label = $langs->trans($key);
	print '<td><label for="'.$key.'">'.$label.'</label></td>';

	// Value
	print '<td>';
	if (!empty($conf->accounting->enabled)) {
		print $formaccounting->select_account(getDolGlobalString($key), $key, 1, '', 1, 1);
	} else {
		print '<input type="text" size="20" id="'.$key.'" name="'.$key.'" value="'.getDolGlobalString($key).'">';
	}
	print '</td></tr>';
}

print '</tr>';

print '</form>';
print "</table>\n";

print '<br><div style="text-align:center"><input type="submit" class="button button-edit" name="button" value="'.$langs->trans('Modify').'"></div>';

// End of page
llxFooter();
$db->close();
