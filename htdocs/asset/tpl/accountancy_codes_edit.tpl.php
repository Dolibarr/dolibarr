<?php
/* Copyright (C) 2021  Open-Dsi  <support@open-dsi.fr>
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
 * Show extrafields. It also show fields from hook formAssetAccountancyCode. Need to have following variables defined:
 * $object (asset, assetmodel, ...)
 * $assetaccountancycodes
 * $action
 * $conf
 * $langs
 *
 * $parameters
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit;
}

if (!is_object($form)) {
	$form = new Form($db);
}

if (!empty($conf->accounting->enabled) && !is_object($formaccounting)) {
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';
	$formaccounting = new FormAccounting($db);
}


?>
<!-- BEGIN PHP TEMPLATE accountancy_code_edit.tpl.php -->
<?php

if (!is_array($parameters)) {
	$parameters = array();
}
$parameters['assetaccountancycodes'] = &$assetaccountancycodes;
$reshook = $hookmanager->executeHooks('formAssetAccountancyCodes', $parameters, $object, $action);
print $hookmanager->resPrint;
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	foreach ($assetaccountancycodes->accountancy_codes_fields as $mode_key => $mode_info) {
		//if (empty($object->enabled_modes[$mode_key])) continue;

		print load_fiche_titre($langs->trans($mode_info['label']), '', '');
		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent tableforfield">';
		foreach ($mode_info['fields'] as $field_key => $field_info) {
			$html_name = $mode_key . '_' . $field_key;
			print '<tr><td class="titlefieldcreate">' . $langs->trans($field_info['label']) . '</td><td colspan="3">';
			$accountancy_code = GETPOSTISSET($html_name) ? GETPOST($html_name, 'aZ09') : (!empty($assetaccountancycodes->accountancy_codes[$mode_key][$field_key]) ? $assetaccountancycodes->accountancy_codes[$mode_key][$field_key] : '');
			if (!empty($conf->accounting->enabled)) {
				print $formaccounting->select_account($accountancy_code, $html_name, 1, null, 1, 1, 'minwidth150 maxwidth300', 1);
			} else {
				print '<input name="' . $html_name . '" class="maxwidth200" value="' . dol_escape_htmltag($accountancy_code) . '">';
			}
			print '</td></tr>';
		}
		print '</table>';
		print '</div>';
	}
}
?>
<!-- END PHP TEMPLATE accountancy_code_edit.tpl.php -->
