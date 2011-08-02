<?php
/* Copyright (C) 2010-2011 Regis Houssin  <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * $Id: originproductline.tpl.php,v 1.5 2011/07/31 23:45:11 eldy Exp $
 */
?>

<!-- BEGIN PHP TEMPLATE originproductline.tpl.php -->
<tr <?php echo $bc[$var]; ?>>
	<td><?php echo $this->tpl['label']; ?></td>
	<td><?php echo $this->tpl['description']; ?></td>
	<td align="right"><?php echo $this->tpl['vat_rate']; ?></td>
	<td align="right"><?php echo $this->tpl['price']; ?></td>
	<td align="right"><?php echo $this->tpl['qty']; ?></td>
	<td align="right"><?php echo $this->tpl['remise_percent']; ?></td>
</tr>
<!-- END PHP TEMPLATE originproductline.tpl.php -->