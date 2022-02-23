<?php
/* Copyright (C) 2017  Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * Need to have following variables defined:
 * $object (invoice, order, ...)
 * $action
 * $conf
 * $langs
 *
 * $keyforbreak may be defined to key to switch on second column
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}
if (!is_object($form)) {
	$form = new Form($db);
}

?>
<!-- BEGIN PHP TEMPLATE commonfields_view.tpl.php -->
<?php

$object->fields = dol_sort_array($object->fields, 'position');

foreach ($object->fields as $key => $val) {
	if (!empty($keyforbreak) && $key == $keyforbreak) {
		break; // key used for break on second column
	}

	// Discard if extrafield is a hidden field on form
	if (abs($val['visible']) != 1 && abs($val['visible']) != 3 && abs($val['visible']) != 4 && abs($val['visible']) != 5) {
		continue;
	}

	if (array_key_exists('enabled', $val) && isset($val['enabled']) && !verifCond($val['enabled'])) {
		continue; // We don't want this field
	}
	if (in_array($key, array('ref', 'status'))) {
		continue; // Ref and status are already in dol_banner
	}

	$value = $object->$key;

	print '<tr class="field_'.$key.'"><td';
	print ' class="'.(empty($val['tdcss']) ? 'titlefield' : $val['tdcss']).' fieldname_'.$key;
	//if ($val['notnull'] > 0) print ' fieldrequired';     // No fieldrequired on the view output
	if ($val['type'] == 'text' || $val['type'] == 'html') {
		print ' tdtop';
	}
	print '">';
	if (!empty($val['help'])) {
		print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
	} else {
		if (isset($val['copytoclipboard']) && $val['copytoclipboard'] == 1) {
			print showValueWithClipboardCPButton($value, 0, $langs->transnoentitiesnoconv($val['label']));
		} else {
			print $langs->trans($val['label']);
		}
	}
	print '</td>';
	print '<td class="valuefield fieldname_'.$key;
	if ($val['type'] == 'text') {
		print ' wordbreak';
	}
	if (!empty($val['cssview'])) {
		print ' '.$val['cssview'];
	}
	print '">';
	if (in_array($val['type'], array('text', 'html'))) {
		print '<div class="longmessagecut">';
	}
	if ($key == 'lang') {
		$langs->load("languages");
		$labellang = ($value ? $langs->trans('Language_'.$value) : '');
		print picto_from_langcode($value, 'class="paddingrightonly saturatemedium opacitylow"');
		print $labellang;
	} else {
		if (isset($val['copytoclipboard']) && $val['copytoclipboard'] == 2) {
			$out = $object->showOutputField($val, $key, $value, '', '', '', 0);
			print showValueWithClipboardCPButton($out, 0, $out);
		} else {
			print $object->showOutputField($val, $key, $value, '', '', '', 0);
		}
	}
	//print dol_escape_htmltag($object->$key, 1, 1);
	if (in_array($val['type'], array('text', 'html'))) {
		print '</div>';
	}
	print '</td>';
	print '</tr>';
}

print '</table>';

// We close div and reopen for second column
print '</div>';


$rightpart = '';
$alreadyoutput = 1;
foreach ($object->fields as $key => $val) {
	if ($alreadyoutput) {
		if (!empty($keyforbreak) && $key == $keyforbreak) {
			$alreadyoutput = 0; // key used for break on second column
		} else {
			continue;
		}
	}

	// Discard if extrafield is a hidden field on form
	if (abs($val['visible']) != 1 && abs($val['visible']) != 3 && abs($val['visible']) != 4 && abs($val['visible']) != 5) {
		continue;
	}

	if (array_key_exists('enabled', $val) && isset($val['enabled']) && !$val['enabled']) {
		continue; // We don't want this field
	}
	if (in_array($key, array('ref', 'status'))) {
		continue; // Ref and status are already in dol_banner
	}

	$value = $object->$key;

	$rightpart .= '<tr><td';
	$rightpart .= ' class="'.(empty($val['tdcss']) ? 'titlefield' : $val['tdcss']).'  fieldname_'.$key;
	//if ($val['notnull'] > 0) $rightpart .= ' fieldrequired';		// No fieldrequired inthe view output
	if ($val['type'] == 'text' || $val['type'] == 'html') {
		$rightpart .= ' tdtop';
	}
	$rightpart.= '">';
	if (!empty($val['help'])) {
		$rightpart .= $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
	} else {
		$rightpart .= $langs->trans($val['label']);
	}
	$rightpart .= '</td>';
	$rightpart .= '<td class="valuefield fieldname_'.$key;
	if ($val['type'] == 'text') {
		$rightpart .= ' wordbreak';
	}
	if (!empty($val['cssview'])) {
		$rightpart .= ' '.$val['cssview'];
	}
	$rightpart .= '">';
	if (in_array($val['type'], array('text', 'html'))) {
		$rightpart .= '<div class="longmessagecut">';
	}
	$rightpart .= $object->showOutputField($val, $key, $value, '', '', '', 0);
	//$rightpart .= dol_escape_htmltag($object->$key, 1, 1);
	if (in_array($val['type'], array('text', 'html'))) {
		$rightpart .= '</div>';
	}
	$rightpart .= '</td>';
	$rightpart .= '</tr>';
}


print '<div class="fichehalfright">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border centpercent tableforfield">';

print $rightpart;

?>
<!-- END PHP TEMPLATE commonfields_view.tpl.php -->
