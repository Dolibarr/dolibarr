<?php
/* Copyright (C) 2010-2017	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2017	Regis Houssin		<regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/* To call this template, you must define
 * $textobject
 * $langs
 * $extrafield
 * $elementtype
 */

// Protection to avoid direct call of template
if (empty($langs) || ! is_object($langs))
{
	print "Error, template page can't be called as URL";
	exit;
}


$langs->load("modulebuilder");

?>

<!-- BEGIN PHP TEMPLATE admin_extrafields_view.tpl.php -->
<?php

print $langs->trans("DefineHereComplementaryAttributes",$textobject).'<br>'."\n";
print '<br>';

// Load attribute_label
$extrafields->fetch_name_optionals_label($elementtype);

print '<div class="div-table-responsive">';
print '<table summary="listofattributes" class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td align="left">'.$langs->trans("Position");
print '<span class="nowrap"><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1downarrow.png" alt="" title="A-Z" class="imgdown"></span>';
print '</td>';
print '<td>'.$langs->trans("Label").'</td>';
print '<td>'.$langs->trans("TranslationString").'</td>';
print '<td>'.$langs->trans("AttributeCode").'</td>';
print '<td>'.$langs->trans("Type").'</td>';
print '<td align="right">'.$langs->trans("Size").'</td>';
print '<td align="center">'.$langs->trans("Unique").'</td>';
print '<td>'.$langs->trans("ComputedFormula").'</td>';
print '<td align="center">'.$langs->trans("Required").'</td>';
print '<td align="center">'.$langs->trans("AlwaysEditable").'</td>';
print '<td align="center">'.$form->textwithpicto($langs->trans("Visible"), $langs->trans("VisibleDesc")).'</td>';
if ($conf->multicompany->enabled)  {
	print '<td align="center">'.$langs->trans("Entities").'</td>';
}
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

if (is_array($extrafields->attribute_type) && count($extrafields->attribute_type))
{
	foreach($extrafields->attributes[$elementtype]['type'] as $key => $value)
	{
		// Load language if required
		if (! empty($extrafields->attributes[$elementtype]['langfile'][$key])) {
			$langs->load($extrafields->attributes[$elementtype]['langfile'][$key]);
		}

		print '<tr class="oddeven">';
		print "<td>".$extrafields->attributes[$elementtype]['pos'][$key]."</td>\n";
		print "<td>".$extrafields->attributes[$elementtype]['label'][$key]."</td>\n";	// We don't translate here, we want admin to know what is the key not translated value
		print "<td>".$langs->trans($extrafields->attributes[$elementtype]['label'][$key])."</td>\n";
		print "<td>".$key."</td>\n";
		print "<td>".$type2label[$extrafields->attributes[$elementtype]['type'][$key]]."</td>\n";
		print '<td align="right">'.$extrafields->attributes[$elementtype]['size'][$key]."</td>\n";
		print '<td align="center">'.yn($extrafields->attributes[$elementtype]['unique'][$key])."</td>\n";
		print '<td>'.dol_trunc($extrafields->attributes[$elementtype]['computed'][$key], 20)."</td>\n";
		print '<td align="center">'.yn($extrafields->attributes[$elementtype]['required'][$key])."</td>\n";
		print '<td align="center">'.yn($extrafields->attributes[$elementtype]['alwayseditable'][$key])."</td>\n";
		print '<td align="center">'.$extrafields->attributes[$elementtype]['list'][$key]."</td>\n";
		if (! empty($conf->multicompany->enabled))  {
			print '<td align="center">'.($extrafields->attributes[$elementtype]['entityid'][$key]==0?$langs->trans("All"):$extrafields->attributes[$elementtype]['entitylabel'][$key]).'</td>';
		}
		print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=edit&attrname='.$key.'">'.img_edit().'</a>';
		print "&nbsp; <a href=\"".$_SERVER["PHP_SELF"]."?action=delete&attrname=$key\">".img_delete()."</a></td>\n";
		print "</tr>";
	}
}
else
{
	$colspan=9;

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
