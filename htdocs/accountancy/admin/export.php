<?php
/* Copyright (C) 2013-2014 Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2014 Alexandre Spangaro	<alexandre.spangaro@gmail.com>
 * Copyright (C) 2014	   Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2014	   Juanjo Menent		<jmenent@2byte.es>
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
 * \file		htdocs/accountancy/admin/export.php
 * \ingroup		Accounting Expert
 * \brief		Setup page to configure accounting expert module
 */

require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';

$langs->load("compta");
$langs->load("bills");
$langs->load("admin");
$langs->load("accountancy");

// Security check
if (!$user->admin)
    accessforbidden();

$action = GETPOST('action', 'alpha');

// Other parameters ACCOUNTING_*
$list = array (
		'ACCOUNTING_SEPARATORCSV'
);

/*
 * Actions
 */
if ($action == 'update') {
	$error = 0;

	$modelcsv = GETPOST('modelcsv', 'int');

	if (! empty($modelcsv)) {

		if (! dolibarr_set_const($db, 'ACCOUNTING_MODELCSV', $modelcsv, 'chaine', 0, '', $conf->entity)) {
			$error ++;
		}
	} else {
		$error ++;
	}

	foreach ( $list as $constname ) {
		$constvalue = GETPOST($constname, 'alpha');

		if (! dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
			$error ++;
		}
	}

	if (! $error) {
		setEventMessage($langs->trans("SetupSaved"));
	} else {
		setEventMessage($langs->trans("Error"), 'errors');
	}
}

/*
 * View
 */

llxHeader();

$form = new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans('ConfigAccountingExpert'),$linkback,'setup');

$head = admin_accounting_prepare_head();

dol_fiche_head($head, 'export', $langs->trans("Configuration"), 0, 'cron');

print '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder" width="100%">';
$var = true;

print '<tr class="liste_titre">';
print '<td colspan="2">' . $langs->trans("Modelcsv") . '</td>';
print '</tr>';

$var = ! $var;

print '<tr ' . $bc[$var] . '>';
print "<td>" . $langs->trans("Selectmodelcsv") . "</td>";
print "<td>";
print '<select class="flat" name="modelcsv" id="modelcsv">';
print '<option value="0"';
if ($conf->global->ACCOUNTING_MODELCSV == 0) {
	print ' selected="selected"';
}
print '>' . $langs->trans("Modelcsv_normal") . '</option>';
print '<option value="1"';
if ($conf->global->ACCOUNTING_MODELCSV == 1) {
	print ' selected="selected"';
}
print '>' . $langs->trans("Modelcsv_CEGID") . '</option>';
print "</select>";
print "</td></tr>";
print "</table>";

print "<br>\n";

/*
 *  Params
 *
 */

$num = count($list);
if ($num) {
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="3">' . $langs->trans('OtherOptions') . '</td>';
	print "</tr>\n";
}

foreach ( $list as $key ) {
	$var = ! $var;

	print '<tr ' . $bc[$var] . ' class="value">';

	// Param
	$label = $langs->trans($key);
	print '<td>' . $label . '</td>';

	// Value
	print '<td>';
	print '<input type="text" size="20" name="' . $key . '" value="' . $conf->global->$key . '">';
	print '</td></tr>';
}

print "</table>\n";

print '<br /><div style="text-align:center"><input type="submit" class="button" value="' . $langs->trans('Modify') . '" name="button"></div>';
print '</form>';

llxFooter();
$db->close();