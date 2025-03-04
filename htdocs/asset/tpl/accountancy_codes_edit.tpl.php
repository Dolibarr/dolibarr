<?php
/* Copyright (C) 2021  Open-Dsi  <support@open-dsi.fr>
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
 *
 * Show extrafields. It also shows fields from hook formAssetAccountancyCode. Need to have the following variables defined:
 * $object (asset, assetmodel, ...)
 * $assetaccountancycodes
 * $action
 * $conf
 * $langs
 *
 * $parameters
 */

/**
 * @var DoliDB $db
 * @var Form $form
 * @var FormAccounting $formaccounting
 * @var HookManager $hookmanager
 * @var Translate $langs
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

if (!is_object($form)) {
	$form = new Form($db);
}

global $formaccounting;
if (isModEnabled('accounting') && !is_object($formaccounting)) {
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
		$width = ($mode_key == "economic")? "width50p pull-left" : "width50p";
		print '<table class="border '. $width .'" id="block_' . $mode_key . '">';
		print '<tr><td class="info-box-title">'.$langs->trans($mode_info['label']).'</td></tr>';
		foreach ($mode_info['fields'] as $field_key => $field_info) {
			$html_name = $mode_key . '_' . $field_key;
			print '<tr><td class="width40p">' . $langs->trans($field_info['label']) . '</td><td>';
			$accountancy_code = GETPOSTISSET($html_name) ? GETPOST($html_name, 'aZ09') : (!empty($assetaccountancycodes->accountancy_codes[$mode_key][$field_key]) ? $assetaccountancycodes->accountancy_codes[$mode_key][$field_key] : '');
			if (isModEnabled('accounting')) {
				print $formaccounting->select_account($accountancy_code, $html_name, 1, null, 1, 1, 'minwidth100 maxwidth300 maxwidthonsmartphone', 1);
			} else {
				print '<input name="' . $html_name . '" class="maxwidth200 " value="' . dol_escape_htmltag($accountancy_code) . '">';
			}
			print '</td></tr>';
		}
		print '</table>';
	}

	print '<div class="clearboth"></div>';
}
?>
<!-- END PHP TEMPLATE accountancy_code_edit.tpl.php -->
