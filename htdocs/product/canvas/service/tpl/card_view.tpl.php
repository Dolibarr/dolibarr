<?php
/* Copyright (C) 2010 Regis Houssin <regis.houssin@capnetworks.com>
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
 */

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}


$object=$GLOBALS['object'];
?>

<!-- BEGIN PHP TEMPLATE VIEW.TPL -->
<?php echo $langs->trans("Service"); ?>

<?php dol_htmloutput_errors($object->error,$object->errors); ?>

<table class="border allwidth">

<tr>
<td width="15%"><?php echo $langs->trans("Ref"); ?></td>
<td colspan="2"><?php echo $object->ref; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Label") ?></td>
<td><?php echo $object->label; ?></td>

<?php if ($object->photos) { ?>
<td valign="middle" align="center" width="30%" rowspan="<?php echo $object->nblignes; ?>">
<?php echo $object->photos; ?>
</td>
<?php } ?>

</tr>

<tr>
<td><?php echo $langs->trans("Status").' ('.$langs->trans("Sell").')'; ?></td>
<td><?php echo $object->status; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Status").' ('.$langs->trans("Buy").')'; ?></td>
<td><?php echo $object->status_buy; ?></td>
</tr>

<tr>
<td class="tdtop"><?php echo $langs->trans("Description"); ?></td>
<td colspan="2"><?php echo $object->description; ?></td>
</tr>

<tr><td><?php echo $langs->trans("Duration"); ?></td>
<td><?php echo $object->duration_value; ?>&nbsp;
<?php echo $object->duration_unit; ?>&nbsp;
</td></tr>

<tr>
<td class="tdtop"><?php echo $langs->trans("Note"); ?></td>
<td colspan="2"><?php echo $object->note; ?></td>
</tr>

</table>

<!-- END PHP TEMPLATE -->