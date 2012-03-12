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

<?php echo $this->object->tpl['title']; ?>

<?php if ($mesg) { ?>
<br><div class="error"><?php echo $mesg; ?></div><br>
<?php } ?>

<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="action" value="update">
<input type="hidden" name="id" value="<?php echo $this->object->tpl['id']; ?>">
<input type="hidden" name="canvas" value="<?php echo $this->object->tpl['canvas']; ?>">


<table class="border allwidth">

<tr>
<td class="fieldrequired" width="20%"><?php echo $langs->trans("Ref"); ?></td>
<td><input name="ref" size="40" maxlength="32" value="<?php echo $this->object->tpl['ref']; ?>">
</td></tr>

<tr>
<td class="fieldrequired"><?php echo $langs->trans("Label"); ?></td>
<td><input name="libelle" size="40" value="<?php echo $this->object->tpl['label']; ?>"></td>
</tr>

<tr>
<td class="fieldrequired"><?php echo $langs->trans("Status"); ?></td>
<td><?php echo $this->object->tpl['status']; ?></td>
</tr>

<tr><td valign="top"><?php echo $langs->trans("Description"); ?></td><td>
<?php if (! $this->object->tpl['textarea_description']) {
$this->object->tpl['doleditor_description']->Create();
}else{
echo $this->object->tpl['textarea_description'];
}?>
</td></tr>

<tr><td><?php echo $langs->trans("Duration"); ?></td>
<td><input name="duration_value" size="6" maxlength="5" value="<?php echo $this->object->tpl['duration_value']; ?>"> &nbsp;
<?php echo $this->object->tpl['duration_unit']; ?>
</td></tr>

<tr><td valign="top"><?php echo $langs->trans("NoteNotVisibleOnBill"); ?></td><td>
<?php echo $this->object->tpl['textarea_note']; ?>
</td></tr>
</table>

<br>

<center><input type="submit" class="button" value="<?php echo $langs->trans("Save"); ?>"> &nbsp; &nbsp;
<input type="submit" class="button" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>"></center>

</form>

<!-- END PHP TEMPLATE -->