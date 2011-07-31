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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * $Id: view.tpl.php,v 1.6 2011/07/31 23:19:26 eldy Exp $
 */
?>

<!-- BEGIN PHP TEMPLATE -->

<table class="border" width="100%">

<tr>
<td width="15%"><?php echo $langs->trans("Ref"); ?></td>
<td colspan="2"><?php echo $this->object->tpl['ref']; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Label") ?></td>
<td><?php echo $this->object->tpl['label']; ?></td>

<?php if ($this->object->tpl['photos']) { ?>
<td valign="middle" align="center" width="30%" rowspan="<?php echo $this->object->tpl['nblignes']; ?>">
<?php echo $this->object->tpl['photos']; ?>
</td>
<?php } ?>

</tr>

<tr>
<td><?php echo $this->object->tpl['accountancyBuyCodeKey']; ?></td>
<td><?php echo $this->object->tpl['accountancyBuyCodeVal']; ?></td>
</tr>

<tr>
<td><?php echo $this->object->tpl['accountancySellCodeKey']; ?></td>
<td><?php echo $this->object->tpl['accountancySellCodeVal']; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Status"); ?></td>
<td><?php echo $this->object->tpl['status']; ?></td>
</tr>

<tr>
<td valign="top"><?php echo $langs->trans("Description"); ?></td>
<td colspan="2"><?php echo $this->object->tpl['description']; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Nature"); ?></td>
<td colspan="2"><?php echo $this->object->tpl['finished']; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Weight"); ?></td>
<td colspan="2"><?php echo $this->object->tpl['weight']; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Length"); ?></td>
<td colspan="2"><?php echo $this->object->tpl['length']; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Surface"); ?></td>
<td colspan="2"><?php echo $this->object->tpl['surface']; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Volume"); ?></td>
<td colspan="2"><?php echo $this->object->tpl['volume']; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Hidden"); ?></td>
<td colspan="2"><?php echo $this->object->tpl['hidden']; ?></td>
</tr>

<tr>
<td valign="top"><?php echo $langs->trans("Note"); ?></td>
<td colspan="2"><?php echo $this->object->tpl['note']; ?></td>
</tr>

</table>

<!-- END PHP TEMPLATE -->