<?php
/* Copyright (C) 2010-2018	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2017	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2018-2019  Frédéric France     <frederic.france@netlogic.fr>
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

// Protection to avoid direct call of template
if (empty($langs) || !is_object($langs))
{
	print "Error, template page can't be called as URL";
	exit;
}


$langs->load("modulebuilder");

?>

<!-- BEGIN PHP TEMPLATE admin_extrafields_view.tpl.php -->
<?php

print '<span class="opacitymedium">'.$langs->trans("DefineHereComplementaryAttributes", $textobject).'</span><br>'."\n";
print '<br>';

// Load attribute_label
$extrafields->fetch_name_optionals_label($elementtype);

print '<div class="div-table-responsive">';
print '<table summary="listofattributes" class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td class="left">'.$langs->trans("Position");
print '<span class="nowrap">';
print img_picto('A-Z', '1downarrow.png');
print '</span>';
print '</td>';
print '<td>'.$langs->trans("LabelOrTranslationKey").'</td>';
print '<td>'.$langs->trans("TranslationString").'</td>';
print '<td>'.$langs->trans("AttributeCode").'</td>';
print '<td>'.$langs->trans("Type").'</td>';
print '<td class="right">'.$langs->trans("Size").'</td>';
print '<td>'.$langs->trans("ComputedFormula").'</td>';
print '<td class="center">'.$langs->trans("Unique").'</td>';
print '<td class="center">'.$langs->trans("Required").'</td>';
print '<td class="center">'.$langs->trans("AlwaysEditable").'</td>';
print '<td class="center">'.$form->textwithpicto($langs->trans("Visible"), $langs->trans("VisibleDesc")).'</td>';
print '<td class="center">'.$form->textwithpicto($langs->trans("DisplayOnPdf"), $langs->trans("DisplayOnPdfDesc")).'</td>';
print '<td class="center">'.$form->textwithpicto($langs->trans("Totalizable"), $langs->trans("TotalizableDesc")).'</td>';
if ($conf->multicompany->enabled) {
	print '<td class="center">'.$langs->trans("Entities").'</td>';
}
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

if (is_array($extrafields->attributes[$elementtype]['type']) && count($extrafields->attributes[$elementtype]['type']))
{
	foreach ($extrafields->attributes[$elementtype]['type'] as $key => $value)
	{
		// Load language if required
		if (!empty($extrafields->attributes[$elementtype]['langfile'][$key])) {
			$langs->load($extrafields->attributes[$elementtype]['langfile'][$key]);
		}

		print '<tr class="oddeven">';
		print "<td>".$extrafields->attributes[$elementtype]['pos'][$key]."</td>\n";
		print "<td>".$extrafields->attributes[$elementtype]['label'][$key]."</td>\n"; // We don't translate here, we want admin to know what is the key not translated value
		print "<td>".$langs->trans($extrafields->attributes[$elementtype]['label'][$key])."</td>\n";
		print "<td>".$key."</td>\n";
		print "<td>".$type2label[$extrafields->attributes[$elementtype]['type'][$key]]."</td>\n";
		print '<td class="right">'.$extrafields->attributes[$elementtype]['size'][$key]."</td>\n";
		print '<td>'.dol_trunc($extrafields->attributes[$elementtype]['computed'][$key], 20)."</td>\n";
		print '<td class="center">'.yn($extrafields->attributes[$elementtype]['unique'][$key])."</td>\n";
		print '<td class="center">'.yn($extrafields->attributes[$elementtype]['required'][$key])."</td>\n";
		print '<td class="center">'.yn($extrafields->attributes[$elementtype]['alwayseditable'][$key])."</td>\n";
		print '<td class="center">'.$extrafields->attributes[$elementtype]['list'][$key]."</td>\n";
		print '<td class="center">'.$extrafields->attributes[$elementtype]['printable'][$key]."</td>\n";
		print '<td class="center">'.yn($extrafields->attributes[$elementtype]['totalizable'][$key])."</td>\n";
		if (!empty($conf->multicompany->enabled)) {
			print '<td class="center">';
			if (empty($extrafields->attributes[$elementtype]['entityid'][$key]))
			{
				print $langs->trans("All");
			} else {
				global $multicompanylabel_cache;
				if (!is_array($multicompanylabel_cache)) $multicompanylabel_cache = array();
				if (empty($multicompanylabel_cache[$extrafields->attributes[$elementtype]['entityid'][$key]])) {
					global $mc;
					$mc->getInfo($extrafields->attributes[$elementtype]['entityid'][$key]);
					$multicompanylabel_cache[$extrafields->attributes[$elementtype]['entityid'][$key]] = $mc->label ? $mc->label : $extrafields->attributes[$elementtype]['entityid'][$key];
				}
				print $multicompanylabel_cache[$extrafields->attributes[$elementtype]['entityid'][$key]];
			}
			print '</td>';
		}
		print '<td class="right nowraponall"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'&attrname='.$key.'#formeditextrafield">'.img_edit().'</a>';
		print '&nbsp; <a class="paddingleft" href="'.$_SERVER["PHP_SELF"].'?action=delete&token='.newToken().'&attrname='.$key.'">'.img_delete().'</a></td>'."\n";
		print "</tr>";
	}
} else {
	$colspan = 13;
	if (!empty($conf->multicompany->enabled))  $colspan++;

	print '<tr class="oddeven">';
	print '<td class="opacitymedium" colspan="'.$colspan.'">';
	print $langs->trans("None");
	print '</td>';
	print '</tr>';
}

print "</table>";
print '</div>';
?>
<!-- END PHP TEMPLATE admin_extrafields_view.tpl.php -->
