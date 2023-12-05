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
 * $element     (used to test $user->rights->$element->creer)
 * $permtoedit  (used to replace test $user->rights->$element->creer)
 * $senderissupplier (0 by default, 1 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 * $outputalsopricetotalwithtax
 * $usemargins (0 to disable all margins columns, 1 to show according to margin setup)
 * $object_rights->creer initialized from = $object->getRights()
 * $disableedit, $disablemove, $disableremove
 *
 * $text, $description, $line
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit;
}

// add html5 elements
$domData  = ' data-element="'.$line->element.'"';
$domData .= ' data-id="'.$line->id.'"';

$coldisplay = 0;
?>
<!-- BEGIN PHP TEMPLATE productattributevalueline_view.tpl.php -->
<tr  id="row-<?php print $line->id?>" class="drag drop oddeven" <?php print $domData; ?> >
<?php if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) { ?>
	<td class="linecolnum center"><span class="opacitymedium"><?php $coldisplay++; ?><?php print($i + 1); ?></span></td>
<?php } ?>
	<td class="linecolref nowrap"><?php $coldisplay++; ?><div id="line_<?php print $line->id; ?>"></div>
		<?php print $line->ref ?>
	</td>

	<td class="linecolvalue nowrap"><?php $coldisplay++; print $line->value ?></td>
<?php
if (!empty($object_rights->write) && $action != 'selectlines') {
	print '<td class="linecoledit center width25">';
	$coldisplay++;
	if (empty($disableedit)) { ?>
		<a class="editfielda reposition" href="<?php print $_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=editline&amp;lineid='.$line->id.'#line_'.$line->id; ?>">
		<?php print img_edit().'</a>';
	}
	print '</td>';

	print '<td class="linecoldelete center width25">';
	$coldisplay++;
	if (empty($disableremove)) { // For situation invoice, deletion is not possible if there is a parent company.
		print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=ask_deleteline&amp;lineid='.$line->id.'">';
		print img_delete();
		print '</a>';
	}
	print '</td>';

	if ($num > 1 && $conf->browser->layout != 'phone' && empty($disablemove)) {
		print '<td class="linecolmove tdlineupdown center width25">';
		$coldisplay++;
		if ($i > 0) { ?>
			<a class="lineupdown reposition" href="<?php print $_SERVER["PHP_SELF"].'?id='.$this->id.'&action=up&token='.newToken().'&rowid='.$line->id; ?>">
			<?php print img_up('default', 0, 'imgupforline'); ?>
			</a>
		<?php }
		if ($i < $num - 1) { ?>
			<a class="lineupdown reposition" href="<?php print $_SERVER["PHP_SELF"].'?id='.$this->id.'&action=down&token='.newToken().'&rowid='.$line->id; ?>">
			<?php print img_down('default', 0, 'imgdownforline'); ?>
			</a>
		<?php }
		print '</td>';
	} else {
		print '<td '.(($conf->browser->layout != 'phone' && empty($disablemove)) ? ' class="linecolmove tdlineupdown center"' : ' class="linecolmove center"').'></td>';
		$coldisplay++;
	}
} else {
	print '<td colspan="3"></td>';
	$coldisplay = $coldisplay + 3;
}

if ($action == 'selectlines') { ?>
	<td class="linecolcheck center"><input type="checkbox" class="linecheckbox" name="line_checkbox[<?php print $i + 1; ?>]" value="<?php print $line->id; ?>" ></td>
<?php }

print "</tr>\n";

print "<!-- END PHP TEMPLATE productattributevalueline_view.tpl.php -->\n";
