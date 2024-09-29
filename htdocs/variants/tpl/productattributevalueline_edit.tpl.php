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
 * Need to have the following variables defined:
 * $object (invoice, order, ...)
 * $conf
 * $langs
 * $seller, $buyer
 * $dateSelector
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $senderissupplier (0 by default, 1 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 * $canchangeproduct (0 by default, 1 to allow to change the product if it is a predefined product)
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

// Define colspan for the button 'Add'
$colspan = 3; // Column: col edit + col delete + move button

print "<!-- BEGIN PHP TEMPLATE productattributevalueline_edit.tpl.php -->\n";

$coldisplay = 0;
?>
<tr class="oddeven tredited">
<?php if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) { ?>
		<td class="linecolnum center"><?php $coldisplay++; ?><?php echo($i + 1); ?></td>
<?php }

$coldisplay++;
?>
	<td class="nobottom linecolref">
		<div id="line_<?php echo $line->id; ?>"></div>
		<input type="hidden" name="lineid" value="<?php echo $line->id; ?>">

		<?php $coldisplay++; ?>
		<input type="text" name="line_ref" id="line_ref" class="flat" value="<?php echo(GETPOSTISSET("line_ref") ? GETPOST("line_ref", 'alpha', 2) : $line->ref); ?>">
		<?php
		if (is_object($hookmanager)) {
			$parameters = array('line' => $line);
			$reshook = $hookmanager->executeHooks('formEditProductOptions', $parameters, $object, $action);
			if (!empty($hookmanager->resPrint)) {
				print $hookmanager->resPrint;
			}
		}
		?>
	</td>

	<td class="nobottom linecolvalue"><?php $coldisplay++; ?>
		<input type="text" name="line_value" id="line_value" class="flat" value="<?php echo(GETPOSTISSET("line_value") ? GETPOST("line_value", 'alpha', 2) : $line->value); ?>">
	</td>

	<!-- colspan for this td because it replace td for buttons+... -->
	<td class="center valignmiddle" colspan="<?php echo $colspan; ?>"><?php $coldisplay += $colspan; ?>
		<input type="submit" class="button buttongen marginbottomonly button-save" id="savelinebutton marginbottomonly" name="save" value="<?php echo $langs->trans("Save"); ?>"><br>
		<input type="submit" class="button buttongen marginbottomonly button-cancel" id="cancellinebutton" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>">
	</td>
</tr>

<!-- END PHP TEMPLATE productattributevalueline_edit.tpl.php -->
