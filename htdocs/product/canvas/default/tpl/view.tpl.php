<?php
/* Copyright (C) 2010 Regis Houssin <regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 */
?>

<!-- BEGIN PHP TEMPLATE -->

<table class="border" width="100%">

<tr>
<td width="15%"><?php echo $langs->trans("Ref"); ?></td>
<td colspan="2"><?php echo $product->tpl['ref']; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Label") ?></td>
<td><?php echo $product->tpl['label']; ?></td>

<?php if ($product->tpl['photos']) { ?>
<td valign="middle" align="center" width="30%" rowspan="<?php echo $product->tpl['nblignes']; ?>">
<?php echo $product->tpl['photos']; ?>
</td>
<?php } ?>

</tr>

<tr>
<td><?php echo $product->tpl['accountancyBuyCodeKey']; ?></td>
<td><?php echo $product->tpl['accountancyBuyCodeVal']; ?></td>
</tr>

<tr>
<td><?php echo $product->tpl['accountancySellCodeKey']; ?></td>
<td><?php echo $product->tpl['accountancySellCodeVal']; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Status"); ?></td>
<td><?php echo $product->tpl['status']; ?></td>
</tr>

<tr>
<td valign="top"><?php echo $langs->trans("Description"); ?></td>
<td colspan="2"><?php echo $product->tpl['description']; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Nature"); ?></td>
<td colspan="2"><?php echo $product->tpl['finished']; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Weight"); ?></td>
<td colspan="2"><?php echo $product->tpl['weight']; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Length"); ?></td>
<td colspan="2"><?php echo $product->tpl['length']; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Surface"); ?></td>
<td colspan="2"><?php echo $product->tpl['surface']; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Volume"); ?></td>
<td colspan="2"><?php echo $product->tpl['volume']; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Hidden"); ?></td>
<td colspan="2"><?php echo $product->tpl['hidden']; ?></td>
</tr>

<tr>
<td valign="top"><?php echo $langs->trans("Note"); ?></td>
<td colspan="2"><?php echo $product->tpl['note']; ?></td>
</tr>

</table>
</div>

<!-- END PHP TEMPLATE -->