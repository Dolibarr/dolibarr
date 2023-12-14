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
 * $dateSelector
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $senderissupplier (0 by default, 1 or 2 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error: this template page cannot be called directly as an URL";
	exit;
}

global $forcetoshowtitlelines;

// Define colspan for the button 'Add'
$colspan = 3; // Columns: col edit + col delete + move button

// Lines for extrafield
$objectline = null;

print "<!-- BEGIN PHP TEMPLATE productattributevalueline_create.tpl.php -->\n";
$nolinesbefore = (count($this->lines) == 0 || $forcetoshowtitlelines);
?>
<tr class="pair nodrag nodrop nohoverpair<?php echo $nolinesbefore ? '' : ' liste_titre_create'; ?>">
	<?php
	$coldisplay = 0;
	// Adds a line numbering column
	if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
		$coldisplay++;
		echo '<td class="nobottom linecolnum center"></td>';
	}
	$coldisplay++;
	?>
	<td class="nobottom linecolref">
		<?php $coldisplay++; if ($nolinesbefore) {
			echo $langs->trans('Ref') . ': ';
		} ?>
		<input type="text" name="line_ref" id="line_ref" class="flat" value="<?php echo(GETPOSTISSET("line_ref") ? GETPOST("line_ref", 'alpha', 2) : ''); ?>" autofocus>
		<?php
		if (is_object($hookmanager)) {
			$parameters = array();
			$reshook = $hookmanager->executeHooks('formCreateValueOptions', $parameters, $object, $action);
			if (!empty($hookmanager->resPrint)) {
				print $hookmanager->resPrint;
			}
		}
		?>
	</td>

	<td class="nobottom linecolvalue"><?php $coldisplay++; ?>
		<input type="text" name="line_value" id="line_value" class="flat" value="<?php echo(GETPOSTISSET("line_value") ? GETPOST("line_value", 'alpha', 2) : ''); ?>">
	</td>

	<td class="nobottom linecoledit center valignmiddle" colspan="<?php echo $colspan; ?>"><?php $coldisplay += $colspan; ?>
		<input type="submit" class="button reposition small" value="<?php echo $langs->trans('Add'); ?>" name="addline" id="addline">
	</td>
</tr>

<!-- END PHP TEMPLATE productattributevalueline_create.tpl.php -->
