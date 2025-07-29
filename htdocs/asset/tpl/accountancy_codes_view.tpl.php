<?php
/* Copyright (C) 2021       Open-Dsi                <support@open-dsi.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 * @var Conf $conf
 * @var DoliDB $db
 * @var Form $form
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
		if (empty($assetdepreciationoptions->deprecation_options[$mode_key]) && $mode_key == "accelerated_depreciation") {
			continue;
		}
		$width = "pull-left";
		print '<table class="liste centpercent '. $width .'" id="block_' . $mode_key . '">' . "\n";
		print '<tr class="liste_titre"><td colspan="5">'.$langs->trans($mode_info['label']).'</td></tr>';
		foreach ($mode_info['fields'] as $field_key => $field_info) {
			$key = $mode_key . '_' . $field_key;
			print '<tr class="field_' . $key . '" id="block_' . $mode_key . '"><td class="titlefieldmiddle">' . $langs->trans($field_info['label']) . '</td><td colspan="3">';
			if (!empty($assetaccountancycodes->accountancy_codes[$mode_key][$field_key])) {
				$accountancy_code = $assetaccountancycodes->accountancy_codes[$mode_key][$field_key];
				if (isModEnabled('accounting')) {
					$accountingaccount = new AccountingAccount($db);
					$accountingaccount->fetch(0, $accountancy_code, 1);

					print $accountingaccount->getNomUrl(0, 1, 1, '', 1);
				} else {
					print $accountancy_code;
				}
			}
			print '</td></tr>';
		}
		print '</table>';
		print '<div class="clearboth"></div>';
		print '<br>';
	}
	print '<div class="clearboth"></div>';
}
?>
<!-- END PHP TEMPLATE accountancy_code_view.tpl.php -->
