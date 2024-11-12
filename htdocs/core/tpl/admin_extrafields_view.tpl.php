<?php
/* Copyright (C) 2010-2018	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2021	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2018-2024  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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

/* To call this template, you must define
 * $textobject
 * $langs
 * $extrafield
 * $elementtype
 */

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var ExtraFields $extrafields
 * @var Form $form
 * @var Translate $langs
 *
 * @var string $action
 * @var string $elementtype
 * @var string $textobject
 */
// Protection to avoid direct call of template
if (empty($langs) || !is_object($langs)) {
	print "Error, template page can't be called as URL";
	exit(1);
}
global $action, $form, $langs;

$langs->load("modulebuilder");

if ($action == 'delete') {
	$attributekey = GETPOST('attrname', 'aZ09');
	print $form->formconfirm($_SERVER['PHP_SELF']."?attrname=$attributekey", $langs->trans("DeleteExtrafield"), $langs->trans("ConfirmDeleteExtrafield", $attributekey), "confirm_delete", '', 0, 1);
}

?>

<!-- BEGIN PHP TEMPLATE admin_extrafields_view.tpl.php -->
<?php

$title = '<span class="opacitymedium">'.$langs->trans("DefineHereComplementaryAttributes", empty($textobject) ? '' : $textobject).'</span><br>'."\n";
//if ($action != 'create' && $action != 'edit') {
$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('NewAttribute'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?action=create', '', 1);
/*} else {
	$newcardbutton = '';
}*/

print '<div class="centpercent tagtable marginbottomonly">';
print '<div class="tagtr">';
print '<div class="tagtd inline-block valignmiddle hideonsmartphoneimp">'.$title.'</div>';
print '<div class="tagtd right inline-block valignmiddle"">'.$newcardbutton.'</div>';
print '</div>';
print '</div>';

// Load $extrafields->attributes
$extrafields->fetch_name_optionals_label($elementtype);

print '<div class="div-table-responsive">';
print '<table summary="listofattributes" class="noborder centpercent small">';

print '<tr class="liste_titre">';
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td width="80">&nbsp;</td>';
}
print '<td class="left">'.$langs->trans("Position");
print '<span class="nowrap">';
print img_picto('A-Z', '1downarrow.png');
print '</span>';
print '</td>';
print '<td>'.$langs->trans("LabelOrTranslationKey").'</td>';
//print '<td>'.$langs->trans("TranslationString").'</td>';
print '<td>'.$langs->trans("AttributeCode").'</td>';
print '<td>'.$langs->trans("Type").'</td>';
print '<td class="right">'.$langs->trans("Size").'</td>';
print '<td>'.$langs->trans("ComputedFormula").'</td>';
print '<td class="center">'.$langs->trans("Unique").'</td>';
print '<td class="center">'.$langs->trans("Mandatory").'</td>';
print '<td class="center">'.$form->textwithpicto($langs->trans("AlwaysEditable"), $langs->trans("EditableWhenDraftOnly")).'</td>';
print '<td class="center">'.$form->textwithpicto($langs->trans("Visibility"), $langs->trans("VisibleDesc").'<br><br>'.$langs->trans("ItCanBeAnExpression")).'</td>';
print '<td class="center">'.$form->textwithpicto($langs->trans("DisplayOnPdf"), $langs->trans("DisplayOnPdfDesc")).'</td>';
print '<td class="center">'.$form->textwithpicto($langs->trans("Totalizable"), $langs->trans("TotalizableDesc")).'</td>';
print '<td class="center">'.$form->textwithpicto($langs->trans("CssOnEdit"), $langs->trans("HelpCssOnEditDesc")).'</td>';
print '<td class="center">'.$form->textwithpicto($langs->trans("CssOnView"), $langs->trans("HelpCssOnViewDesc")).'</td>';
print '<td class="center">'.$form->textwithpicto($langs->trans("CssOnList"), $langs->trans("HelpCssOnListDesc")).'</td>';
if (isModEnabled('multicompany')) {
	print '<td class="center">'.$langs->trans("Entity").'</td>';
}
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td width="80">&nbsp;</td>';
}
print "</tr>\n";

if (isset($extrafields->attributes[$elementtype]['type']) && is_array($extrafields->attributes[$elementtype]['type']) && count($extrafields->attributes[$elementtype]['type'])) {
	foreach ($extrafields->attributes[$elementtype]['type'] as $key => $value) {
		/*if (! (int) dol_eval($extrafields->attributes[$elementtype]['enabled'][$key], 1, 1, '1')) {
			// TODO Uncomment this to exclude extrafields of modules not enabled. Add a link to "Show extrafields disabled"
			// continue;
		}*/

		// Load language if required
		if (!empty($extrafields->attributes[$elementtype]['langfile'][$key])) {
			$langs->load($extrafields->attributes[$elementtype]['langfile'][$key]);
		}

		print '<tr class="oddeven">';
		// Actions
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="center nowraponall">';
			print '<a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'&attrname='.urlencode($key).'#formeditextrafield">'.img_edit().'</a>';
			print '&nbsp; <a class="paddingleft" href="'.$_SERVER["PHP_SELF"].'?action=delete&token='.newToken().'&attrname='.urlencode($key).'">'.img_delete().'</a>';
			if ($extrafields->attributes[$elementtype]['type'][$key] == 'password' && !empty($extrafields->attributes[$elementtype]['param'][$key]['options']) && array_key_exists('dolcrypt', $extrafields->attributes[$elementtype]['param'][$key]['options'])) {
				print '&nbsp; <a class="aaa" href="'.$_SERVER["PHP_SELF"].'?action=encrypt&token='.newToken().'&attrname='.urlencode($key).'" title="'.dol_escape_htmltag($langs->trans("ReEncryptDesc")).'">'.img_picto('', 'refresh').'</a>';
			}
			print '</td>'."\n";
		}
		// Position
		print "<td>".dol_escape_htmltag((string) $extrafields->attributes[$elementtype]['pos'][$key])."</td>\n";
		// Label and label translated
		print '<td title="'.dol_escape_htmltag($extrafields->attributes[$elementtype]['label'][$key]).'" class="tdoverflowmax150 subtitle">';
		print dol_escape_htmltag($extrafields->attributes[$elementtype]['label'][$key]);
		if ($langs->transnoentitiesnoconv($extrafields->attributes[$elementtype]['label'][$key]) != $extrafields->attributes[$elementtype]['label'][$key]) {
			print '<br><span class="subtitle small opacitymedium inline-block" title="'.dolPrintHTMLForAttribute($langs->trans("LabelTranslatedInCurrentLanguage")).'">';
			print $langs->transnoentitiesnoconv($extrafields->attributes[$elementtype]['label'][$key]);
		}
		print '</span>';
		print "</td>\n"; // We don't translate here, we want admin to know what is the key not translated value
		// Label translated
		//print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv($extrafields->attributes[$elementtype]['label'][$key])).'">'.dol_escape_htmltag($langs->transnoentitiesnoconv($extrafields->attributes[$elementtype]['label'][$key]))."</td>\n";
		// Key
		print '<td title="'.dol_escape_htmltag($key).'" class="tdoverflowmax100">'.dol_escape_htmltag($key)."</td>\n";
		// Type
		$typetoshow = $type2label[$extrafields->attributes[$elementtype]['type'][$key]];
		print '<td title="'.dol_escape_htmltag($typetoshow).'" class="tdoverflowmax100">';
		print getPictoForType($extrafields->attributes[$elementtype]['type'][$key]);
		print dol_escape_htmltag($typetoshow);
		print "</td>\n";
		// Size
		print '<td class="right">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['size'][$key])."</td>\n";
		// Computed field
		print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($extrafields->attributes[$elementtype]['computed'][$key]).'">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['computed'][$key])."</td>\n";
		// Is unique ?
		print '<td class="center">'.yn($extrafields->attributes[$elementtype]['unique'][$key])."</td>\n";
		// Is mandatory ?
		print '<td class="center">'.yn($extrafields->attributes[$elementtype]['required'][$key])."</td>\n";
		// Can always be editable ?
		print '<td class="center">'.yn($extrafields->attributes[$elementtype]['alwayseditable'][$key])."</td>\n";
		// Visible
		print '<td class="center tdoverflowmax100" title="'.dol_escape_htmltag($extrafields->attributes[$elementtype]['list'][$key]).'">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['list'][$key])."</td>\n";
		// Print on PDF
		print '<td class="center tdoverflowmax100" title="'.dol_escape_htmltag((string) $extrafields->attributes[$elementtype]['printable'][$key]).'">'.dol_escape_htmltag((string) $extrafields->attributes[$elementtype]['printable'][$key])."</td>\n";
		// Summable
		print '<td class="center">'.yn($extrafields->attributes[$elementtype]['totalizable'][$key])."</td>\n";
		// CSS
		print '<td class="center tdoverflowmax100" title="'.dol_escape_htmltag($extrafields->attributes[$elementtype]['css'][$key]).'">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['css'][$key])."</td>\n";
		// CSS view
		print '<td class="center tdoverflowmax100" title="'.dol_escape_htmltag($extrafields->attributes[$elementtype]['cssview'][$key]).'">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['cssview'][$key])."</td>\n";
		// CSS list
		print '<td class="center tdoverflowmax100" title="'.dol_escape_htmltag($extrafields->attributes[$elementtype]['csslist'][$key]).'">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['csslist'][$key])."</td>\n";
		// Multicompany
		if (isModEnabled('multicompany')) {
			print '<td class="center">';
			if (empty($extrafields->attributes[$elementtype]['entityid'][$key])) {
				print $langs->trans("All");
			} else {
				global $multicompanylabel_cache;
				if (!is_array($multicompanylabel_cache)) {
					$multicompanylabel_cache = array();
				}
				if (empty($multicompanylabel_cache[$extrafields->attributes[$elementtype]['entityid'][$key]])) {
					global $mc;
					if (is_object($mc) && method_exists($mc, 'getInfo')) {
						$mc->getInfo($extrafields->attributes[$elementtype]['entityid'][$key]);
						$multicompanylabel_cache[$extrafields->attributes[$elementtype]['entityid'][$key]] = $mc->label ? $mc->label : $extrafields->attributes[$elementtype]['entityid'][$key];
					}
				}
				print $multicompanylabel_cache[$extrafields->attributes[$elementtype]['entityid'][$key]];
			}
			print '</td>';
		}
		// Actions
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="right nowraponall">';
			print '<a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'&attrname='.urlencode($key).'#formeditextrafield">'.img_edit().'</a>';
			print '&nbsp; <a class="paddingleft" href="'.$_SERVER["PHP_SELF"].'?action=delete&token='.newToken().'&attrname='.urlencode($key).'">'.img_delete().'</a>';
			if ($extrafields->attributes[$elementtype]['type'][$key] == 'password' && !empty($extrafields->attributes[$elementtype]['param'][$key]['options']) && array_key_exists('dolcrypt', $extrafields->attributes[$elementtype]['param'][$key]['options'])) {
				print '&nbsp; <a class="aaa" href="'.$_SERVER["PHP_SELF"].'?action=encrypt&token='.newToken().'&attrname='.urlencode($key).'" title="'.dol_escape_htmltag($langs->trans("ReEncryptDesc")).'">'.img_picto('', 'refresh').'</a>';
			}
			print '</td>'."\n";
		}
		print "</tr>";
	}
} else {
	$colspan = 17;
	if (isModEnabled('multicompany')) {
		$colspan++;
	}

	print '<tr class="oddeven">';
	print '<td colspan="'.$colspan.'"><span class="opacitymedium">';
	print $langs->trans("None");
	print '</span></td>';
	print '</tr>';
}

print "</table>";
print '</div>';
?>
<!-- END PHP TEMPLATE admin_extrafields_view.tpl.php -->
