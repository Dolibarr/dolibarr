<?php
/* Copyright (C) 2010-2017	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin		<regis.houssin@capnetworks.com>
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
print '<td>'.$langs->trans("AttributeCode").'</td>';
print '<td>'.$langs->trans("Type").'</td>';
print '<td align="right">'.$langs->trans("Size").'</td>';
print '<td align="center">'.$langs->trans("Unique").'</td>';
print '<td>'.$langs->trans("ComputedFormula").'</td>';
print '<td align="center">'.$langs->trans("Required").'</td>';
print '<td align="center">'.$langs->trans("AlwaysEditable").'</td>';
if (! empty($conf->global->MAIN_CAN_HIDE_EXTRAFIELDS)) print '<td align="center">'.$langs->trans("Hidden").'</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

if (count($extrafields->attribute_type))
{
    foreach($extrafields->attribute_type as $key => $value)
    {
        
        print '<tr class="oddeven">';
        print "<td>".$extrafields->attribute_pos[$key]."</td>\n";
        print "<td>".$extrafields->attribute_label[$key]."</td>\n";
        print "<td>".$key."</td>\n";
        print "<td>".$type2label[$extrafields->attribute_type[$key]]."</td>\n";
        print '<td align="right">'.$extrafields->attribute_size[$key]."</td>\n";
        print '<td align="center">'.yn($extrafields->attribute_unique[$key])."</td>\n";
        print '<td>'.dol_trunc($extrafields->attribute_computed[$key], 20)."</td>\n";
        print '<td align="center">'.yn($extrafields->attribute_required[$key])."</td>\n";
        print '<td align="center">'.yn($extrafields->attribute_alwayseditable[$key])."</td>\n";
    	if (! empty($conf->global->MAIN_CAN_HIDE_EXTRAFIELDS)) print '<td align="center">'.yn($extrafields->attribute_hidden[$key])."</td>\n";	// Add hidden option on not working feature. Why hide if user can't see it.
        print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=edit&attrname='.$key.'">'.img_edit().'</a>';
        print "&nbsp; <a href=\"".$_SERVER["PHP_SELF"]."?action=delete&attrname=$key\">".img_delete()."</a></td>\n";
        print "</tr>";
    }
}
else
{
    $colspan=9;
    if (! empty($conf->global->MAIN_CAN_HIDE_EXTRAFIELDS)) $colspan++;
    
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
