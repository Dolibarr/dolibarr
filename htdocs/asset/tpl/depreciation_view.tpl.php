<?php
/* Copyright (C) 2021       Open-Dsi                <support@open-dsi.fr>
 * Copyright (C) 2024		MDW			            <mdeweerd@users.noreply.github.com>
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
 * @var AssetDepreciationOptions $assetdepreciationoptions
 * @var DoliDB $db
 * @var Form $form
 * @var HookManager $hookmanager
 * @var Translate $langs
 *
 * @var string $action
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
<!-- BEGIN PHP TEMPLATE depreciation_options_view.tpl.php -->
<?php

if (!is_array($parameters)) {
	$parameters = array();
}
if (empty($parameters['assetdepreciationoptions'])) {
	$parameters['assetdepreciationoptions'] = &$assetdepreciationoptions;
}
$reshook = $hookmanager->executeHooks('formAssetDeprecationOptions', $parameters, $object, $action);
print $hookmanager->resPrint;
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$class_type = get_class($object) == 'Asset' ? 0 : 1;
	foreach ($assetdepreciationoptions->deprecation_options_fields as $mode_key => $mode_info) {
		if (!empty($mode_info['enabled_field'])) {
			$info = explode(':', $mode_info['enabled_field']);
			if ($assetdepreciationoptions->deprecation_options[$info[0]][$info[1]] != $info[2]) {
				continue;
			}
		}

		$assetdepreciationoptions->setInfosForMode($mode_key, $class_type, true);

		print load_fiche_titre($langs->trans($mode_info['label']), '', '');
		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent tableforfield">' . "\n";
		$mode_info['fields'] = dol_sort_array($mode_info['fields'], 'position');
		foreach ($mode_info['fields'] as $field_key => $field_info) {
			if (!empty($field_info['enabled_field'])) {
				$info = explode(':', $field_info['enabled_field']);
				if ($assetdepreciationoptions->deprecation_options[$info[0]][$info[1]] != $info[2]) {
					continue;
				}
			}
			// Discard if extrafield is a hidden field on form
			$isVisibleAbs = array_key_exists('visible', $field_info) ? abs((int) $field_info['visible']) : 0;
			if (!in_array($isVisibleAbs, array(1, 3, 4, 5))) {
				continue;
			}
			if (array_key_exists('enabled', $field_info) && isset($field_info['enabled']) && !verifCond($field_info['enabled'])) {
				continue; // We don't want this field
			}
			if (!empty($field_info['column_break'])) {
				print '</table>';

				// We close div and reopen for second column
				print '</div>';
				print '<div class="fichehalfright">';

				print '<div class="underbanner clearboth"></div>';
				print '<table class="border centpercent tableforfield">';
			}

			$key = $mode_key . '_' . $field_key;
			$value = $assetdepreciationoptions->deprecation_options[$mode_key][$field_key];

			print '<tr class="field_' . $key . '"><td';
			print ' class="' . (empty($field_info['tdcss']) ? 'titlefield' : $field_info['tdcss']) . ' fieldname_' . $key;
			if ($field_info['type'] == 'text' || $field_info['type'] == 'html') {
				print ' tdtop';
			}
			print '">';
			if (!empty($field_info['help'])) {
				print $form->textwithpicto($langs->trans($field_info['label']), $langs->trans($field_info['help']));
			} else {
				if (isset($field_info['copytoclipboard']) && $field_info['copytoclipboard'] == 1) {  // @phan-suppress-current-line PhanTypeInvalidDimOffset
					print showValueWithClipboardCPButton($value, 0, $langs->transnoentitiesnoconv($field_info['label']));
				} else {
					print $langs->trans($field_info['label']);
				}
			}
			print '</td>';
			print '<td class="valuefield fieldname_' . $key;
			if ($field_info['type'] == 'text') {
				print ' wordbreak';
			}
			if (!empty($field_info['cssview'])) {
				print ' ' . $field_info['cssview'];
			}
			print '">';
			if (in_array($field_info['type'], array('text', 'html'))) {
				print '<div class="longmessagecut">';
			}
			if ($field_key == 'lang') {
				$langs->load("languages");
				$labellang = ($value ? $langs->trans('Language_' . $value) : '');
				print picto_from_langcode($value, 'class="paddingrightonly saturatemedium opacitylow"');
				print $labellang;
			} else {
				if (isset($field_info['copytoclipboard']) && $field_info['copytoclipboard'] == 2) {
					$out = $assetdepreciationoptions->showOutputField($field_info, $field_key, $value, '', '', $mode_key . '_', 0);
					print showValueWithClipboardCPButton($out, 0, $out);
				} else {
					print $assetdepreciationoptions->showOutputField($field_info, $field_key, $value, '', '', $mode_key . '_', 0);
				}
			}
			if (in_array($field_info['type'], array('text', 'html'))) {
				print '</div>';
			}
			print '</td>';
			print '</tr>';
		}
		print '</table>';
		print '</div>';
		print '</div>';
		print '<div class="clearboth"></div>';
	}
}

?>
<!-- END PHP TEMPLATE depreciation_options_view.tpl.php -->
