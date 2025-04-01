<?php
/* Copyright (C) 2010-2012	Regis Houssin	<regis.houssin@inodbox.com>
 * Copyright (C) 2017		Charlie Benke	<charlie@patas-monkey.com>
 * Copyright (C) 2022		Gauthier VERDOL	<gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
/**
 * @var CommonObject $this
 * @var Conf $conf
 */
// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

?>

<!-- BEGIN PHP TEMPLATE originproductline.tpl.php -->
<?php
'@phan-var-force CommonObject $this';
print '<tr data-id="'.$this->tpl['id'].'" class="oddeven'.(empty($this->tpl['strike']) ? '' : ' strikefordisabled').'">';
print '<td class="linecolref">'.$this->tpl['label'].'</td>';
print '<td class="linecoldescription">'.$this->tpl['description'].'</td>';
print '<td class="linecolvat right">'.$this->tpl['vat_rate'].'</td>';
print '<td class="linecoluht right">'.$this->tpl['price'].'</td>';
if (isModEnabled("multicurrency")) {
	print '<td class="linecoluht_currency right">'.$this->tpl['multicurrency_price'].'</td>';
}

print '<td class="linecolqty right">'.$this->tpl['qty'].'</td>';
if (getDolGlobalString('PRODUCT_USE_UNITS')) {
	print '<td class="linecoluseunit left">'.$langs->trans($this->tpl['unit']).'</td>';
}

print '<td class="linecoldiscount right">'.$this->tpl['remise_percent'].'</td>';
print '<td class="linecolht right">'.$this->tpl['total_ht'].'</td>';

$selected = 1;
if (!empty($selectedLines) && !in_array($this->tpl['id'], $selectedLines)) {
	$selected = 0;
}
print '<td class="center">';
print '<input id="cb'.$this->tpl['id'].'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$this->tpl['id'].'"'.($selected ? ' checked="checked"' : '').'>';
print '</td>';
print '</tr>'."\n";
?>
<!-- END PHP TEMPLATE originproductline.tpl.php -->
