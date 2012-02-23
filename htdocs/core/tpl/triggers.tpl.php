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

<table class="noborder" style="width: 100%">
<tr class="liste_titre">
<td colspan="2"><?php echo $langs->trans("File"); ?></td>
<td align="center"><?php echo $langs->trans("Version"); ?></td>
<td align="center"><?php echo $langs->trans("Active"); ?></td>
<td align="center">&nbsp;</td>
</tr>

<?php
$var=True;
foreach ($triggers as $trigger)
{
$var=!$var;
?>

<tr <?php echo $bc[$var]; ?>>

<td valign="top" width="14" align="center"><?php echo $trigger['picto']; ?></td>
<td valign="top"><?php echo $trigger['file']; ?></td>
<td valign="top" align="center"><?php echo $trigger['version']; ?></td>
<td valign="top" align="center"><?php echo $trigger['status']; ?></td>
<td valign="top"><?php echo $form->textwithpicto('', $trigger['info']); ?></td>

</tr>

<?php
}
?>

</table>

<!-- END PHP TEMPLATE -->