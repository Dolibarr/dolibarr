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


?>
<!-- BEGIN PHP TEMPLATE accountancy_code_view.tpl.php -->
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
	if (isModEnabled('accounting')) {
		require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingaccount.class.php';
	}

	foreach ($assetaccountancycodes->accountancy_codes_fields as $mode_key => $mode_info) {
		//if (empty($object->enabled_modes[$mode_key])) continue;

		print load_fiche_titre($langs->trans($mode_info['label']), '', '');
		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent tableforfield">';
		foreach ($mode_info['fields'] as $field_key => $field_info) {
			print '<tr><td class="titlefieldcreate">' . $langs->trans($field_info['label']) . '</td><td colspan="3">';
			if (!empty($assetaccountancycodes->accountancy_codes[$mode_key][$field_key])) {
				$accountancy_code = $assetaccountancycodes->accountancy_codes[$mode_key][$field_key];
				if (isModEnabled('accounting')) {
					$accountingaccount = new AccountingAccount($db);
					$accountingaccount->fetch('', $accountancy_code, 1);

					print $accountingaccount->getNomUrl(0, 1, 1, '', 1);
				} else {
					print $accountancy_code;
				}
			}
			print '</td></tr>';
		}
		print '</table>';
		print '</div>';
	}
}
?>
<!-- END PHP TEMPLATE accountancy_code_view.tpl.php -->
