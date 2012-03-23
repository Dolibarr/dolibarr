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
 */
?>

<!-- BEGIN PHP TEMPLATE -->
<?php echo $this->control->tpl['showhead']; ?>

<?php dol_htmloutput_errors($this->control->tpl['error'],$this->control->tpl['errors']); ?>

<table class="border allwidth">

<tr>
<td width="15%"><?php echo $langs->trans("Ref"); ?></td>
<td colspan="2"><?php echo $this->control->tpl['showrefnav']; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Label") ?></td>
<td><?php echo $this->control->tpl['label']; ?></td>

<?php if ($this->control->tpl['photos']) { ?>
<td valign="middle" align="center" width="30%" rowspan="<?php echo $this->control->tpl['nblignes']; ?>">
<?php echo $this->control->tpl['photos']; ?>
</td>
<?php } ?>

</tr>

<tr>
<td><?php echo $langs->trans("Status").' ('.$langs->trans("Sell").')'; ?></td>
<td><?php echo $this->control->tpl['status']; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Status").' ('.$langs->trans("Buy").')'; ?></td>
<td><?php echo $this->control->tpl['status_buy']; ?></td>
</tr>

<tr>
<td valign="top"><?php echo $langs->trans("Description"); ?></td>
<td colspan="2"><?php echo $this->control->tpl['description']; ?></td>
</tr>

<tr><td><?php echo $langs->trans("Duration"); ?></td>
<td><?php echo $this->control->tpl['duration_value']; ?>&nbsp;
<?php echo $this->control->tpl['duration_unit']; ?>&nbsp;
</td></tr>

<tr>
<td><?php echo $langs->trans("Hidden"); ?></td>
<td colspan="2"><?php echo $this->control->tpl['hidden']; ?></td>
</tr>

<tr>
<td valign="top"><?php echo $langs->trans("Note"); ?></td>
<td colspan="2"><?php echo $this->control->tpl['note']; ?></td>
</tr>

</table>

<?php echo $this->control->tpl['showend']; ?>
<!-- END PHP TEMPLATE -->