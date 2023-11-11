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

if (!is_object($formadmin)) {
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formadmin.class.php';
	$formadmin = new FormAdmin($db);
}


?>
<!-- BEGIN PHP TEMPLATE depreciation_options_edit.tpl.php -->
<?php

if (!is_array($parameters)) {
	$parameters = array();
}
$enabled_field_info = array();
if (empty($parameters['enabled_field_info'])) {
	$parameters['enabled_field_info'] = &$enabled_field_info;
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
			$enabled_field_info[] = array(
				'mode_key' => $info[0],
				'field_key' => $info[1],
				'value' => $info[2],
				'target' => 'block_' . $mode_key,
			);
		}

		$assetdepreciationoptions->setInfosForMode($mode_key, $class_type, true);
		$prefix_html_name = $mode_key . '_';

		print '<div id="block_' . $mode_key . '">';
		print load_fiche_titre($langs->trans($mode_info['label']), '', '');
		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent tableforfield">' . "\n";
		$mode_info['fields'] = dol_sort_array($mode_info['fields'], 'position');
		foreach ($mode_info['fields'] as $field_key => $field_info) {
			// Discard if extrafield is a hidden field on form
			if (abs($field_info['visible']) != 1 && abs($field_info['visible']) != 3 && abs($field_info['visible']) != 4) {
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

			$html_name = $prefix_html_name . $field_key;
			if (!empty($field_info['enabled_field'])) {
				$info = explode(':', $field_info['enabled_field']);
				$enabled_field_info[] = array(
					'mode_key' => $info[0],
					'field_key' => $info[1],
					'value' => $info[2],
					'target' => 'field_' . $html_name,
				);
			}

			$more_class = '';
			if (!empty($field_info['required']) || (isset($field_info['notnull']) && $field_info['notnull'] > 0)) {
				$more_class .= ' fieldrequired';
			}
			if (preg_match('/^(text|html)/', $val['type'])) {
				$more_class .= ' tdtop';
			}

			print '<tr class="field_' . $html_name . '" id="field_' . $html_name . '"><td';
			print ' class="titlefieldcreate' . $more_class . '">';
			if (!empty($field_info['help'])) {
				print $form->textwithpicto($langs->trans($field_info['label']), $langs->trans($field_info['help']));
			} else {
				print $langs->trans($field_info['label']);
			}
			print '</td>';
			print '<td class="valuefieldcreate">';
			if (!empty($field_info['picto'])) {
				print img_picto('', $field_info['picto'], '', false, 0, 0, '', 'pictofixedwidth');
			}
			if (in_array($field_info['type'], array('int', 'integer'))) {
				$value = GETPOSTISSET($html_name) ?GETPOST($html_name, 'int') : $assetdepreciationoptions->$field_key;
			} elseif ($field_info['type'] == 'double') {
				$value = GETPOSTISSET($html_name) ? price2num(GETPOST($html_name, 'alphanohtml')) : $assetdepreciationoptions->$field_key;
			} elseif (preg_match('/^(text|html)/', $field_info['type'])) {
				$tmparray = explode(':', $field_info['type']);
				if (!empty($tmparray[1])) {
					$check = $tmparray[1];
				} else {
					$check = 'restricthtml';
				}
				$value = GETPOSTISSET($html_name) ? GETPOST($html_name, $check) : $assetdepreciationoptions->$field_key;
			} elseif ($field_info['type'] == 'price') {
				$value = GETPOSTISSET($html_name) ? price2num(GETPOST($html_name)) : ($assetdepreciationoptions->$field_key ? price2num($assetdepreciationoptions->$field_key) : (!empty($field_info['default']) ? dol_eval($field_info['default'], 1) : 0));
			} elseif ($field_key == 'lang') {
				$value = GETPOSTISSET($html_name) ? GETPOST($html_name, 'aZ09') : $assetdepreciationoptions->lang;
			} else {
				$value = GETPOSTISSET($html_name) ? GETPOST($html_name, 'alpha') : $assetdepreciationoptions->$field_key;
			}
			if (!empty($field_info['noteditable'])) {
				print $assetdepreciationoptions->showOutputField($field_info, $field_key, $value, '', '', $prefix_html_name, 0);
			} else {
				if ($field_key == 'lang') {
					print img_picto('', 'language', 'class="pictofixedwidth"');
					print $formadmin->select_language($value, $html_name, 0, null, 1, 0, 0, 'minwidth300', 2);
				} else {
					print $assetdepreciationoptions->showInputField($field_info, $field_key, $value, '', '', $prefix_html_name, 0);
				}
			}
			print '</td>';
			print '</tr>';
		}
		print '</table>';
		print '</div>';
		print '</div>';
		print '<div class="clearboth"></div>';
		print '</div>';
	}
}

if (!empty($enabled_field_info)) {
	$enabled_field_info = json_encode($enabled_field_info);
	print <<<SCRIPT
<script type="text/javascript">
	jQuery(document).ready(function () {
		var enabled_field_info = $enabled_field_info;

		// Init fields
		enabled_field_info.map(function(info) {
			var html_name = info.mode_key + '_' + info.field_key;
			var source = $('#' + html_name);
			if (!(source.length > 0)) source = $('[name="' + html_name + '"]');
			if (source.length > 0) {
				source.attr('data-asset-enabled-field-value', info.value);
				source.attr('data-asset-enabled-field-target', info.target);
				updateEnabledField(source);
				source.on('change click', function() {
					updateEnabledField(jQuery(this));
				});
			}
		});

		function updateEnabledField(_this) {
			var value = _this.attr('data-asset-enabled-field-value');
			var target_name = _this.attr('data-asset-enabled-field-target');

			// for block mode
			var target = $('div#' + target_name);

			// for field
			if (!(target.length > 0)) {
				target = $('#' + target_name);
				if (!(target.length > 0)) target = $('[name="' + target_name + '"]');
				if (target.length > 0) target = target.closest('tr');
			}

			if (target.length > 0) {
				var source_value = _this.attr('type') == 'checkbox' ? (_this.is(':checked') ? 1 : 0) : _this.val();

				if (source_value != value) {
					target.hide();
				} else {
					target.show();
				}
			}
		}
	});
</script>
SCRIPT;
}

?>
<!-- END PHP TEMPLATE depreciation_options_edit.tpl.php -->
