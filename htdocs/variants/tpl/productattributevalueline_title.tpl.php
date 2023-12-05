<?php
/* Copyright (C) 2022   Open-Dsi		<support@open-dsi.fr>
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
 * $conf
 * $langs
 * $element     (used to test $user->rights->$element->creer)
 * $permtoedit  (used to replace test $user->rights->$element->creer)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 * $outputalsopricetotalwithtax
 * $usemargins (0 to disable all margins columns, 1 to show according to margin setup)
 *
 * $type, $text, $description, $line
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit;
}

print "<!-- BEGIN PHP TEMPLATE productattributevalueline_title.tpl.php -->\n";

// Title line
print "<thead>\n";

print '<tr class="liste_titre nodrag nodrop">';

// Adds a line numbering column
if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
	print '<td class="linecolnum center">&nbsp;</td>';
}

// Ref
print '<td class="linecolref">'.$langs->trans('Ref').'</td>';

// Value
print '<td class="linecolvalue">'.$langs->trans('Value').'</td>';

print '<td class="linecoledit"></td>'; // No width to allow autodim

print '<td class="linecoldelete" style="width: 10px"></td>';

print '<td class="linecolmove" style="width: 10px"></td>';

if ($action == 'selectlines') {
	print '<td class="linecolcheckall center">';
	print '<input type="checkbox" class="linecheckboxtoggle" />';
	print '<script>$(document).ready(function() {$(".linecheckboxtoggle").click(function() {var checkBoxes = $(".linecheckbox");checkBoxes.prop("checked", this.checked);})});</script>';
	print '</td>';
}

print "</tr>\n";
print "</thead>\n";

print "<!-- END PHP TEMPLATE productattributevalueline_title.tpl.php -->\n";
