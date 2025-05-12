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
 * @var CommonObjectLine $line
 */
// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit(1);
}
?>

<!-- BEGIN PHP TEMPLATE originproductline.tpl.php -->
<?php

'
@phan-var-force CommonObject $this
@phan-var-force CommonObjectLine|CommonInvoiceLine|CommonOrderLine|ExpeditionLigne|PropaleLigne $line
';

print '<tr data-id="'.$this->tpl['id'].'" class="'.(empty($this->tpl['strike']) ? '' : ' strikefordisabled').'" style="background:#'.$this->getSubtotalColors($line->qty).'">';
print '<td class="linecolref">'.$this->tpl['description'].'</td>';
print '<td class="linecoldescription"></td>';
print '<td class="linecolvat right"></td>';
print '<td class="linecoluht right"></td>';
if (isModEnabled("multicurrency")) {
	print '<td class="linecoluht_currency right"></td>';
}

print '<td class="linecolqty right"></td>';
if (getDolGlobalString('PRODUCT_USE_UNITS')) {
	print '<td class="linecoluseunit left"></td>';
}

print '<td class="linecoldiscount right"></td>';
if ($this->tpl['qty'] < 0) {
	print '<td class="linecolht right">'.$this->getSubtotalLineAmount($line).'</td>';
} else {
	print '<td class="linecolht right"></td>';
}

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
