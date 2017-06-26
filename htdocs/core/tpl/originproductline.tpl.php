<?php
/* Copyright (C) 2010-2012	Regis Houssin	<regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
?>

<!-- BEGIN PHP TEMPLATE originproductline.tpl.php -->
<?php 
print '<tr'.$bc[$var].'>';
print '<td>'.$this->tpl['label'].'</td>';
print '<td>'.$this->tpl['description'].'</td>';
print '<td align="right">'.$this->tpl['vat_rate'].'</td>';
print '<td align="right">'.$this->tpl['price'].'</td>';
if (!empty($conf->multicurrency->enabled))
	print '<td align="right">'.$this->tpl['multicurrency_price'].'</td>';

print '<td align="right">'.$this->tpl['qty'].'</td>';
if($conf->global->PRODUCT_USE_UNITS) 
	print '<td align="left">'.$langs->trans($this->tpl['unit']).'</td>';

print '<td align="right">'.$this->tpl['remise_percent'].'</td>';
print '</tr>'."\n";
?>
<!-- END PHP TEMPLATE originproductline.tpl.php -->
