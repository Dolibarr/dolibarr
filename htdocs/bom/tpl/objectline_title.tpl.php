<?php
/* Copyright (C) 2010-2013	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2017		Juanjo Menent		<jmenent@2byte.es>
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
 * Need to have the following variables defined:
 * $object (invoice, order, ...)
 * $conf
 * $langs
 * $element     (used to test $user->hasRight($element, 'creer'))
 * $permtoedit  (used to replace test $user->hasRight($element, 'creer'))
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 * $outputalsopricetotalwithtax
 * $usemargins (0 to disable all margins columns, 1 to show according to margin setup)
 *
 * $type, $text, $description, $line
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

'@phan-var-force CommonObject $this
 @phan-var-force CommonObject $object';

global $filtertype;
if (empty($filtertype)) {
	$filtertype = 0;
}

print "<!-- BEGIN PHP TEMPLATE objectline_title.tpl.php -->\n";


// Title line
print "<thead>\n";

print '<tr class="liste_titre nodrag nodrop">';

// Adds a line numbering column
if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
	print '<td class="linecolnum center">&nbsp;</td>';
}

// Product or sub-bom
print '<td class="linecoldescription bomline">'.$langs->trans('Description');
if (getDolGlobalString('BOM_SUB_BOM') && $filtertype != 1) {
	print ' &nbsp; <a id="show_all" href="#">'.img_picto('', 'folder-open', 'class="paddingright"').$langs->trans("ExpandAll").'</a>&nbsp;&nbsp;';
	print '<a id="hide_all" href="#">'.img_picto('', 'folder', 'class="paddingright"').$langs->trans("UndoExpandAll").'</a>&nbsp;';
}
print '</td>';

// Qty
print '<td class="linecolqty width100 right">'.$form->textwithpicto($langs->trans('Qty'), ($filtertype != 1) ? $langs->trans("QtyRequiredIfNoLoss") : '').'</td>';

if ($filtertype != 1) {
	if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
		print '<td class="linecoluseunit"></td>';
	}

	// Qty frozen
	print '<td class="linecolqtyfrozen right">' . $form->textwithpicto($langs->trans('QtyFrozen'), $langs->trans("QuantityConsumedInvariable")) . '</td>';

	// Disable stock change
	print '<td class="linecoldisablestockchange right">' . $form->textwithpicto($langs->trans('DisableStockChange'), $langs->trans('DisableStockChangeHelp')) . '</td>';

	// Efficiency
	print '<td class="linecolefficiency right">' . $form->textwithpicto($langs->trans('ManufacturingEfficiency'), $langs->trans('ValueOfMeansLoss')) . '</td>';

	// Cost
	print '<td class="linecolcost right">'.$form->textwithpicto($langs->trans("TotalCost"), $langs->trans("BOMTotalCost")).'</td>';
} else {
	print '<td class="linecolunit"></td>';

	// Qty frozen
	print '<td class="linecolqtyfrozen right">' .$form->textwithpicto($langs->trans('QtyFrozen'), $langs->trans("QuantityConsumedInvariable")) . '</td>';

	// Workstation
	if (isModEnabled('workstation')) {
		print '<td class="linecolworkstation">' .  $form->textwithpicto($langs->trans('DefaultWorkstation'), '') . '</td>';
	}

	// Cost
	print '<td class="linecolcost right">'.$form->textwithpicto($langs->trans("TotalCost"), $langs->trans("BOMTotalCostService")).'</td>';
}




print '<td class="linecoledit" style="width: 10px"></td>'; // No width to allow autodim

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

print "<!-- END PHP TEMPLATE objectline_title.tpl.php -->\n";
