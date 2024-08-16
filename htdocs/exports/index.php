<?php
/* Copyright (C) 2005-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/exports/index.php
 *       \ingroup    export
 *       \brief      Home page of export wizard
 */

require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/exports/class/export.class.php';

// Load translation files required by the page
$langs->load("exports");

$export = new Export($db);
$export->load_arrays($user);

// Security check
$result = restrictedArea($user, 'export');


/*
 * View
 */

$form = new Form($db);


$help_url = 'EN:Module_Exports_En|FR:Module_Exports|ES:M&oacute;dulo_Exportaciones|DE:Modul_DatenExporte';

llxHeader('', $langs->trans("ExportsArea"), $help_url, '', 0, 0, '', '', '', 'mod-exports page-index');

print load_fiche_titre($langs->trans("ExportsArea"));

print $langs->trans("FormatedExportDesc1").'<br>';
print '<br>';


print '<div class="center">';
if (count($export->array_export_code)) {
	print dolGetButtonTitle($langs->trans('NewExport'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/exports/export.php?leftmenu=export', '', $user->hasRight('export', 'creer'));
}
print '</div>';
print '<br>';



// List of available export formats

print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("AvailableFormats").'</td>';
print '<td>'.$langs->trans("LibraryShort").'</td>';
print '<td class="right">'.$langs->trans("LibraryVersion").'</td>';
print '</tr>';

include_once DOL_DOCUMENT_ROOT.'/core/modules/export/modules_export.php';
$model = new ModeleExports($db);
$liste = $model->listOfAvailableExportFormat($db); // This is not a static method for exports because method load non static properties

foreach ($liste as $key => $val) {
	if (preg_match('/__\(Disabled\)__/', $liste[$key])) {
		$liste[$key] = preg_replace('/__\(Disabled\)__/', '('.$langs->transnoentitiesnoconv("Disabled").')', $liste[$key]);
	}

	print '<tr class="oddeven">';
	print '<td width="16">'.img_picto_common($model->getDriverLabelForKey($key), $model->getPictoForKey($key)).'</td>';
	$text = $model->getDriverDescForKey($key);
	$label = $liste[$key];
	// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
	print '<td>'.$form->textwithpicto($label, $text).'</td>';
	print '<td>'.$model->getLibLabelForKey($key).'</td>';
	print '<td class="nowrap right">'.$model->getLibVersionForKey($key).'</td>';
	print '</tr>';
}

print '</table>';
print '</div>';

// End of page
llxFooter();
$db->close();
