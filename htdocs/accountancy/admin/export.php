<?php
/* Copyright (C) 2013-2014 Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2015 Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2014	   Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2014	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 * \file 		htdocs/accountancy/admin/export.php
 * \ingroup 	Advanced accountancy
 * \brief 		Setup page to configure accounting expert module
 */
require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountancyexport.class.php';

$langs->load("compta");
$langs->load("bills");
$langs->load("admin");
$langs->load("accountancy");

// Security check
if (empty($user->admin) || ! empty($user->rights->accountancy->chartofaccount))
{
    accessforbidden();
}

$action = GETPOST('action', 'alpha');

// Parameters ACCOUNTING_EXPORT_*
$main_option = array (
		'ACCOUNTING_EXPORT_PREFIX_SPEC' 
);

$model_option = array (
		'ACCOUNTING_EXPORT_SEPARATORCSV',
		'ACCOUNTING_EXPORT_DATE'
		/*
		'ACCOUNTING_EXPORT_PIECE',
		'ACCOUNTING_EXPORT_GLOBAL_ACCOUNT',
		'ACCOUNTING_EXPORT_LABEL',
		'ACCOUNTING_EXPORT_AMOUNT',
		'ACCOUNTING_EXPORT_DEVISE'
		*/
);

/*
 * Actions
 */
if ($action == 'update') {
	$error = 0;
	
	$format = GETPOST('format', 'alpha');
	$modelcsv = GETPOST('modelcsv', 'int');
	
	if (! empty($format)) {
		if (! dolibarr_set_const($db, 'ACCOUNTING_EXPORT_FORMAT', $format, 'chaine', 0, '', $conf->entity)) {
			$error ++;
		}
	} else {
		$error ++;
	}
	
	if (! empty($modelcsv)) {
		if (! dolibarr_set_const($db, 'ACCOUNTING_EXPORT_MODELCSV', $modelcsv, 'chaine', 0, '', $conf->entity)) {
			$error ++;
		}
		if ($modelcsv==AccountancyExport::$EXPORT_TYPE_QUADRATUS || $modelcsv==AccountancyExport::$EXPORT_TYPE_CIEL) {
			dolibarr_set_const($db, 'ACCOUNTING_EXPORT_FORMAT', 'txt', 'chaine', 0, '', $conf->entity);
		}
	} else {
		$error ++;
	}
	
	foreach ( $main_option as $constname ) {
		$constvalue = GETPOST($constname, 'alpha');
		
		if (! dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
			$error ++;
		}
	}
	
	foreach ( $model_option as $constname ) {
		$constvalue = GETPOST($constname, 'alpha');
		
		if (! dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
			$error ++;
		}
	}
	
	if (! $error) {
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

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans('ConfigAccountingExpert'), $linkback, 'title_setup');

$head = admin_accounting_prepare_head();

print '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="update">';

dol_fiche_head($head, 'export', $langs->trans("Configuration"), 0, 'cron');

$var = true;

/*
 * Main Options
 */

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="3">' . $langs->trans('Options') . '</td>';
print "</tr>\n";

$var = ! $var;

$num = count($main_option);
if ($num) {
	foreach ( $main_option as $key ) {
		$var = ! $var;
		
		print '<tr ' . $bc[$var] . ' class="value">';
		
		// Param
		$label = $langs->trans($key);
		print '<td width="50%">' . $label . '</td>';
		
		// Value
		print '<td>';
		print '<input type="text" size="20" name="' . $key . '" value="' . $conf->global->$key . '">';
		print '</td></tr>';
	}
}

print "</table>\n";

print "<br>\n";

/*
 * Export model
 */
print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td colspan="2">' . $langs->trans("Modelcsv") . '</td>';
print '</tr>';

$var = ! $var;

print '<tr ' . $bc[$var] . '>';
print '<td width="50%">' . $langs->trans("Selectmodelcsv") . '</td>';
if (! $conf->use_javascript_ajax) {
	print '<td class="nowrap">';
	print $langs->trans("NotAvailableWhenAjaxDisabled");
	print "</td>";
} else {
	print '<td>';
	$listmodelcsv = AccountancyExport::getType();
	print $form->selectarray("modelcsv", $listmodelcsv, $conf->global->ACCOUNTING_EXPORT_MODELCSV, 0);
	
	print '</td>';
}
print "</td></tr>";
print "</table>";

print "<br>\n";

/*
 *  Parameters
 */

$num2 = count($model_option);
if ($num2) {
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="3">' . $langs->trans('OtherOptions') . '</td>';
	print "</tr>\n";
	
	if ($conf->global->ACCOUNTING_EXPORT_MODELCSV > 1)
	{
		print '<tr><td colspan="2" bgcolor="red"><b>' . $langs->trans('OptionsDeactivatedForThisExportModel') . '</b></td></tr>';
	}
	
	print '<tr ' . $bc[$var] . '>';
	print '<td width="50%">' . $langs->trans("Selectformat") . '</td>';
	if (! $conf->use_javascript_ajax) {
	    print '<td class="nowrap">';
	    print $langs->trans("NotAvailableWhenAjaxDisabled");
	    print "</td>";
	} else {
	    print '<td>';
	    $listformat = array (
	        'csv' => $langs->trans("csv"),
	        'txt' => $langs->trans("txt")
	    );
	    print $form->selectarray("format", $listformat, $conf->global->ACCOUNTING_EXPORT_FORMAT, 0);
	
	    print '</td>';
	}
	print "</td></tr>";
	
	foreach ( $model_option as $key ) {
		$var = ! $var;
		
		print '<tr ' . $bc[$var] . ' class="value">';
		
		// Param
		$label = $langs->trans($key);
		print '<td width="50%">' . $label . '</td>';
		
		// Value
		print '<td>';
		print '<input type="text" size="20" name="' . $key . '" value="' . $conf->global->$key . '">';
		print '</td></tr>';
	}
	
	print "</table>\n";
}

dol_fiche_end();

print '<div class="center"><input type="submit" class="button" value="' . dol_escape_htmltag($langs->trans('Modify')) . '" name="button"></div>';

print '</form>';

llxFooter();
$db->close();
