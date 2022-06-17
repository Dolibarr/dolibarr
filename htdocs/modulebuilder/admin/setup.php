<?php
/* Copyright (C) 2018 Nicolas ZABOURI   <info@inovea-conseil.com>
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
 *  \file       htdocs/modulebuilder/admin/setup.php
 *  \ingroup    modulebuilder
 *  \brief      Page setup for modulebuilder module
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

global $conf, $langs, $user, $db;
$langs->loadLangs(array("admin", "other", "modulebuilder"));

if (!$user->admin || !isModEnabled('modulebuilder')) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');


/*
 * Actions
 */

if ($action == "update") {
	$res1 = dolibarr_set_const($db, 'MODULEBUILDER_SPECIFIC_README', GETPOST('MODULEBUILDER_SPECIFIC_README', 'restricthtml'), 'chaine', 0, '', $conf->entity);
	$res2 = dolibarr_set_const($db, 'MODULEBUILDER_ASCIIDOCTOR', GETPOST('MODULEBUILDER_ASCIIDOCTOR', 'alphanohtml'), 'chaine', 0, '', $conf->entity);
	$res3 = dolibarr_set_const($db, 'MODULEBUILDER_ASCIIDOCTORPDF', GETPOST('MODULEBUILDER_ASCIIDOCTORPDF', 'alphanohtml'), 'chaine', 0, '', $conf->entity);
	$res4 = dolibarr_set_const($db, 'MODULEBUILDER_SPECIFIC_EDITOR_NAME', GETPOST('MODULEBUILDER_SPECIFIC_EDITOR_NAME', 'alphanohtml'), 'chaine', 0, '', $conf->entity);
	$res5 = dolibarr_set_const($db, 'MODULEBUILDER_SPECIFIC_EDITOR_URL', GETPOST('MODULEBUILDER_SPECIFIC_EDITOR_URL', 'alphanohtml'), 'chaine', 0, '', $conf->entity);
	$res6 = dolibarr_set_const($db, 'MODULEBUILDER_SPECIFIC_FAMILY', GETPOST('MODULEBUILDER_SPECIFIC_FAMILY', 'alphanohtml'), 'chaine', 0, '', $conf->entity);
	$res7 = dolibarr_set_const($db, 'MODULEBUILDER_SPECIFIC_AUTHOR', GETPOST('MODULEBUILDER_SPECIFIC_AUTHOR', 'html'), 'chaine', 0, '', $conf->entity);
	$res8 = dolibarr_set_const($db, 'MODULEBUILDER_SPECIFIC_VERSION', GETPOST('MODULEBUILDER_SPECIFIC_VERSION', 'alphanohtml'), 'chaine', 0, '', $conf->entity);
	if ($res1 < 0 || $res2 < 0 || $res3 < 0 || $res4 < 0 || $res5 < 0 || $res6 < 0 || $res7 < 0 || $res8 < 0) {
		setEventMessages('ErrorFailedToSaveDate', null, 'errors');
		$db->rollback();
	} else {
		setEventMessages('RecordModifiedSuccessfully', null, 'mesgs');
		$db->commit();
	}
}

$reg = array();
if (preg_match('/set_(.*)/', $action, $reg)) {
	$code = $reg[1];
	$values = GETPOST($code);
	if (is_array($values)) {
		$values = implode(',', $values);
	}

	if (dolibarr_set_const($db, $code, $values, 'chaine', 0, '', $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
}

if (preg_match('/del_(.*)/', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0) {
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
}


/*
 * 	View
 */

$form = new Form($db);

$help_url = '';
llxHeader('', $langs->trans("ModulebuilderSetup"), $help_url);

$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php').'">'.$langs->trans("BackToModuleList").'</a>';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print load_fiche_titre($langs->trans("ModuleSetup").' '.$langs->trans('Modulebuilder'), $linkback);

if (GETPOST('withtab', 'alpha')) {
	print dol_get_fiche_head($head, 'modulebuilder', '', -1);
}

print '<span class="opacitymedium">'.$langs->trans("ModuleBuilderDesc")."</span><br>\n";

print '<br>';

print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td style="width: 30%">'.$langs->trans("Key").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";


if ($conf->global->MAIN_FEATURES_LEVEL >= 2) {
	// What is use case of this 2 options ?

	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("UseAboutPage").'</td>';
	print '<td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('MODULEBUILDER_USE_ABOUT');
	} else {
		if (empty($conf->global->MODULEBUILDER_USE_ABOUT)) {
			print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_MODULEBUILDER_USE_ABOUT&token='.newToken().'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
		} else {
			print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=del_MODULEBUILDER_USE_ABOUT&token='.newToken().'">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
		}
	}
	print '</td></tr>';

	print '<tr class="oddeven">';
	print '<td class="tdtop">'.$langs->trans("UseSpecificEditorName").'</td>';
	print '<td>';
	print '<input type="text" name="MODULEBUILDER_SPECIFIC_EDITOR_NAME" value="'.$conf->global->MODULEBUILDER_SPECIFIC_EDITOR_NAME.'">';
	print '</td>';
	print '</tr>';

	print '<tr class="oddeven">';
	print '<td class="tdtop">'.$langs->trans("UseSpecificEditorURL").'</td>';
	print '<td>';
	print '<input type="text" name="MODULEBUILDER_SPECIFIC_EDITOR_URL" value="'.$conf->global->MODULEBUILDER_SPECIFIC_EDITOR_URL.'">';
	print '</td>';
	print '</tr>';

	print '<tr class="oddeven">';
	print '<td class="tdtop">'.$langs->trans("UseSpecificFamily").'</td>';
	print '<td>';
	print '<input type="text" name="MODULEBUILDER_SPECIFIC_FAMILY" value="'.$conf->global->MODULEBUILDER_SPECIFIC_FAMILY.'">';
	print '</td>';
	print '</tr>';

	print '<tr class="oddeven">';
	print '<td class="tdtop">'.$langs->trans("UseSpecificAuthor").'</td>';
	print '<td>';
	print '<input type="text" name="MODULEBUILDER_SPECIFIC_AUTHOR" value="'.$conf->global->MODULEBUILDER_SPECIFIC_AUTHOR.'">';
	print '</td>';
	print '</tr>';

	print '<tr class="oddeven">';
	print '<td class="tdtop">'.$langs->trans("UseSpecificVersion").'</td>';
	print '<td>';
	print '<input type="text" name="MODULEBUILDER_SPECIFIC_VERSION" value="'.$conf->global->MODULEBUILDER_SPECIFIC_VERSION.'">';
	print '</td>';
	print '</tr>';
}

print '<tr class="oddeven">';
print '<td class="tdtop">'.$langs->trans("UseSpecificReadme").'</td>';
print '<td>';
print '<textarea class="centpercent" rows="20" name="MODULEBUILDER_SPECIFIC_README">'.$conf->global->MODULEBUILDER_SPECIFIC_README.'</textarea>';
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td class="tdtop">'.$langs->trans("AsciiToHtmlConverter").'</td>';
print '<td>';
print '<input type="text" name="MODULEBUILDER_ASCIIDOCTOR" value="'.$conf->global->MODULEBUILDER_ASCIIDOCTOR.'">';
print ' '.$langs->trans("Example").': asciidoc, asciidoctor';
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td class="tdtop">'.$langs->trans("AsciiToPdfConverter").'</td>';
print '<td>';
print '<input type="text" name="MODULEBUILDER_ASCIIDOCTORPDF" value="'.$conf->global->MODULEBUILDER_ASCIIDOCTORPDF.'">';
print ' '.$langs->trans("Example").': asciidoctor-pdf';
print '</td>';
print '</tr>';

print '</table>';

print $form->buttonsSaveCancel("Save", '');

if (GETPOST('withtab', 'alpha')) {
	print dol_get_fiche_end();
}

print '<br>';

print '</form>';

// End of page
llxFooter();
$db->close();
