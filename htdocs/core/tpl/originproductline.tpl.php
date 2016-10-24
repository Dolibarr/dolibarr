<?php
/* Copyright (C) 2010-2012	Regis Houssin	<regis.houssin@capnetworks.com>
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
<tr <?php echo $bc[$var]; ?>>
	<td><?php echo $this->tpl['label']; ?></td>
	<td><?php echo $this->tpl['description']; ?></td>
	<td align="right"><?php echo $this->tpl['vat_rate']; ?></td>
	<td align="right"><?php echo $this->tpl['price']; ?></td>
	<?php if (!empty($conf->multicurrency->enabled)) { ?><td align="right"><?php echo $this->tpl['multicurrency_price']; ?></td><?php } ?>
	<td align="right"><?php echo $this->tpl['qty']; ?></td>
    <?php
    if($conf->global->PRODUCT_USE_UNITS) echo '<td align="left">'.$langs->trans($this->tpl['unit']).'</td>';
    ?>
	<td align="right"><?php echo $this->tpl['remise_percent']; ?></td>
</tr>
<!-- END PHP TEMPLATE originproductline.tpl.php -->
