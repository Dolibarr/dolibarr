<?php
/* Copyright (C) 2010-2012	Regis Houssin	<regis.houssin@inodbox.com>
/* Copyright (C) 2017		Charlie Benke	<charlie@patas-monkey.com>
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

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

if (!is_object($form)) $form = new Form($db);

$qtytoconsumeforline = $this->tpl['qty'] / $this->tpl['efficiency'];
/*if ((empty($this->tpl['qty_frozen']) && $this->tpl['qty_bom'] > 1)) {
	$qtytoconsumeforline = $qtytoconsumeforline / $this->tpl['qty_bom'];
}*/
$qtytoconsumeforline = price2num($qtytoconsumeforline, 'MS');

?>

<!-- BEGIN PHP TEMPLATE originproductline.tpl.php -->
<?php
print '<tr class="oddeven'.(empty($this->tpl['strike']) ? '' : ' strikefordisabled').'">';
print '<td>'.$this->tpl['label'].'</td>';
print '<td class="right">'.$this->tpl['qty'].(($this->tpl['efficiency'] > 0 && $this->tpl['efficiency'] < 1) ? ' / '.$form->textwithpicto($this->tpl['efficiency'], $langs->trans("ValueOfMeansLoss")).' = '.$qtytoconsumeforline : '').'</td>';
print '<td class="center">'.($this->tpl['qty_frozen'] ? yn($this->tpl['qty_frozen']) : '').'</td>';
print '<td class="center">'.($this->tpl['disable_stock_change'] ? yn($this->tpl['disable_stock_change']) : '').'</td>';
//print '<td class="right">'.$this->tpl['efficiency'].'</td>';

$selected = 1;
if (!empty($selectedLines) && !in_array($this->tpl['id'], $selectedLines)) $selected = 0;
print '<td class="center">';
//print '<input id="cb'.$this->tpl['id'].'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$this->tpl['id'].'"'.($selected?' checked="checked"':'').'>';
print '</td>';
print '</tr>'."\n";
?>
<!-- END PHP TEMPLATE originproductline.tpl.php -->
