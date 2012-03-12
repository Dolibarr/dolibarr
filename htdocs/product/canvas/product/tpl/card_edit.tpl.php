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

<?php
print_fiche_titre($this->control->tpl['title']);

dol_htmloutput_errors($this->control->tpl['error'],$this->control->tpl['errors']);
?>

<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="action" value="update">
<input type="hidden" name="id" value="<?php echo $this->control->tpl['id']; ?>">
<input type="hidden" name="canvas" value="<?php echo $this->control->tpl['canvas']; ?>">


<table class="border allwidth">

<tr>
<td class="fieldrequired" width="20%"><?php echo $langs->trans("Ref"); ?></td>
<td><input name="ref" size="40" maxlength="32" value="<?php echo $this->control->tpl['ref']; ?>">
</td></tr>

<tr>
<td class="fieldrequired"><?php echo $langs->trans("Label"); ?></td>
<td><input name="libelle" size="40" value="<?php echo $this->control->tpl['label']; ?>"></td>
</tr>

<tr>
<td class="fieldrequired"><?php echo $langs->trans("Status"); ?></td>
<td><?php echo $this->control->tpl['status']; ?></td>
</tr>

<?php if ($conf->stock->enabled) { ?>
<tr><td><?php echo $langs->trans("StockLimit"); ?></td><td>
<input name="seuil_stock_alerte" size="4" value="<?php echo $this->control->tpl['seuil_stock_alerte']; ?>">
</td></tr>
<?php } else { ?>
<input name="seuil_stock_alerte" type="hidden" value="0">
<?php } ?>

<tr><td valign="top"><?php echo $langs->trans("Description"); ?></td><td>
<?php echo $this->control->tpl['textarea_description']; ?>
</td></tr>

<tr><td><?php echo $langs->trans("Nature"); ?></td><td>
<?php echo $this->control->tpl['finished']; ?>
</td></tr>

<tr><td><?php echo $langs->trans("Weight"); ?></td><td>
<input name="weight" size="4" value="<?php echo $this->control->tpl['weight']; ?>">
<?php echo $this->control->tpl['weight_units']; ?>
</td></tr>

<tr><td><?php echo $langs->trans("Length"); ?></td><td>
<input name="size" size="4" value="<?php echo $this->control->tpl['length']; ?>">
<?php echo $this->control->tpl['length_units']; ?>
</td></tr>

<tr><td><?php echo $langs->trans("Surface"); ?></td><td>
<input name="surface" size="4" value="<?php echo $this->control->tpl['surface']; ?>">
<?php echo $this->control->tpl['surface_units']; ?>
</td></tr>

<tr><td><?php echo $langs->trans("Volume"); ?></td><td>
<input name="volume" size="4" value="<?php echo $this->control->tpl['volume']; ?>">
<?php echo $this->control->tpl['volume_units']; ?>
</td></tr>

<tr><td valign="top"><?php echo $langs->trans("NoteNotVisibleOnBill"); ?></td><td>
<?php echo $this->control->tpl['textarea_note']; ?>
</td></tr>
</table>

<br>

<center><input type="submit" class="button" value="<?php echo $langs->trans("Save"); ?>"> &nbsp; &nbsp;
<input type="submit" class="button" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>"></center>

</form>

<!-- END PHP TEMPLATE -->