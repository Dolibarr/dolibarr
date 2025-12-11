<?php
/* Copyright (C) 2005-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/imports/index.php
 *       \ingroup    import export
 *       \brief      Home page of import and export wizard
 */

require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/imports/class/import.class.php';
require_once DOL_DOCUMENT_ROOT.'/exports/class/export.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->load("exports");

if (!$user->socid == 0) {
	accessforbidden();
}

$export = new Export($db);
$export->load_arrays($user);

$import = new Import($db);
$import->load_arrays($user);

if (isModEnabled('import')) {
	//$usercanimport = restrictedArea($user, 'import', 0, '', 'run');
	$usercanimport = restrictedArea($user, 'import');
}
if (isModEnabled('export')) {
	$usercanexport = restrictedArea($user, 'export');
}


/*
 * View
 */

$form = new Form($db);

$title = "ImportExportArea";
if (isModEnabled('import') && !isModEnabled('export')) {
	$title = "ImportArea";
}
if (!isModEnabled('import') && isModEnabled('export')) {
	$title = "ExportsArea";
}

llxHeader('', $title, 'EN:Module_Imports_En|FR:Module_Imports|ES:M&oacute;dulo_Importaciones');

print load_fiche_titre($langs->trans($title));


// List of available import format
if (isModEnabled('import')) {
	$out = '';
	$out .= '<div class="div-table-responsive-no-min">';
	$out .= '<table class="noborder centpercent nomarginbottom">';
	$out .= '<tr class="liste_titre">';
	$out .= '<td colspan="2">'.$langs->trans("AvailableFormats").'</td>';
	$out .= '<td>'.$langs->trans("LibraryShort").'</td>';
	$out .= '<td class="right">'.$langs->trans("LibraryVersion").'</td>';
	$out .= '</tr>';

	include_once DOL_DOCUMENT_ROOT.'/core/modules/import/modules_import.php';
	$model = new ModeleImports();
	$list = $model->listOfAvailableImportFormat($db);

	foreach ($list as $key) {
		$out .= '<tr class="oddeven">';
		$out .= '<td width="16">'.img_picto_common($model->getDriverLabelForKey($key), $model->getPictoForKey($key)).'</td>';
		$text = $model->getDriverDescForKey($key);
		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		$out .= '<td>'.$form->textwithpicto($model->getDriverLabelForKey($key), $text).'</td>';
		$out .= '<td>'.$model->getLibLabelForKey($key).'</td>';
		$out .= '<td class="nowrap right">'.$model->getLibVersionForKey($key).'</td>';
		$out .= '</tr>';
	}

	$out .= '</table>';
	$out .= '</div>';

	print '<div class="divsection wordwrap center">';
	print '<br>';
	print $form->textwithpicto($langs->trans("FormatedImportDesc1"), $out, 1, 'help', 'valignmiddle', 1, 3, 'ttimport').'<br>';
	print '<br><br>';


	print '<div class="center">';
	if (count($import->array_import_code)) {
		$params = array('forcenohideoftext' => 1);
		print dolGetButtonTitle($langs->trans('NewImport'), '', 'fa fa-plus-circle size4x', DOL_URL_ROOT.'/imports/import.php?leftmenu=import', '', 1, $params);
	}
	print '</div>';
	print '<br>';

	print '</div>';
}


// List of available export formats
if (isModEnabled('export')) {
	$out = '';
	$out .= '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
	$out .= '<table class="noborder centpercent nomarginbottom">';
	$out .= '<tr class="liste_titre">';
	$out .= '<td colspan="2">'.$langs->trans("AvailableFormats").'</td>';
	$out .= '<td>'.$langs->trans("LibraryShort").'</td>';
	$out .= '<td class="right">'.$langs->trans("LibraryVersion").'</td>';
	$out .= '</tr>';

	include_once DOL_DOCUMENT_ROOT.'/core/modules/export/modules_export.php';
	$model = new ModeleExports($db);
	$liste = $model->listOfAvailableExportFormat($db); // This is not a static method for exports because method load non static properties

	foreach ($liste as $key => $val) {
		if (preg_match('/__\(Disabled\)__/', $liste[$key])) {
			$liste[$key] = preg_replace('/__\(Disabled\)__/', '('.$langs->transnoentitiesnoconv("Disabled").')', $liste[$key]);
		}

		$out .= '<tr class="oddeven">';
		$out .= '<td width="16">'.img_picto_common($model->getDriverLabelForKey($key), $model->getPictoForKey($key)).'</td>';
		$text = $model->getDriverDescForKey($key);
		$label = $liste[$key];
		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		$out .= '<td>'.$form->textwithpicto($label, $text).'</td>';
		$out .= '<td>'.$model->getLibLabelForKey($key).'</td>';
		$out .= '<td class="nowrap right">'.$model->getLibVersionForKey($key).'</td>';
		$out .= '</tr>';
	}

	$out .= '</table>';
	$out .= '</div>';


	print '<div class="divsection wordwrap center">';
	print '<br>';
	print $form->textwithpicto($langs->trans("FormatedExportDesc1"), $out, 1, 'help', 'valignmiddle', 1, 3, 'ttexport').'<br>';
	print '<br><br>';

	print '<div class="center">';
	if (count($export->array_export_code)) {
		$params = array('forcenohideoftext' => 1);
		print dolGetButtonTitle($langs->trans('NewExport'), '', 'fa fa-plus-circle size4x', DOL_URL_ROOT.'/exports/export.php?leftmenu=export', '', 1, $params);
	}
	print '</div>';
	print '<br>';

	print '</div>';
}


// End of page
llxFooter();
$db->close();
