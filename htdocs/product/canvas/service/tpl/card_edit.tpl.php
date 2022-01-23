<?php
/* Copyright (C) 2010-2018 Regis Houssin <regis.houssin@inodbox.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}


$object = $GLOBALS['object'];

$statutarray = array('1' => $langs->trans("OnSell"), '0' => $langs->trans("NotOnSell"));
?>

<!-- BEGIN PHP TEMPLATE EDIT.TPL -->

<?php
$head = product_prepare_head($object);
$titre = $langs->trans("CardProduct".$object->type);
print dol_get_fiche_head($head, 'card', $titre, 0, 'service');

dol_htmloutput_errors($object->error, $object->errors);
?>

<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post">
<input type="hidden" name="token" value="<?php echo newToken(); ?>">
<input type="hidden" name="action" value="update">
<input type="hidden" name="id" value="<?php echo $object->id; ?>">
<input type="hidden" name="canvas" value="<?php echo $object->canvas; ?>">


<table class="border allwidth">

<tr>
<td class="fieldrequired" width="20%"><?php echo $langs->trans("Ref"); ?></td>
<td><input name="ref" size="40" maxlength="32" value="<?php echo $object->ref; ?>">
</td></tr>

<tr>
<td class="fieldrequired"><?php echo $langs->trans("Label"); ?></td>
<td><input name="label" size="40" value="<?php echo $object->label; ?>"></td>
</tr>

<tr>
<td class="fieldrequired"><?php echo $langs->trans("Status").' ('.$langs->trans("Sell").')'; ?></td>
<td><?php echo $form->selectarray('statut', $statutarray, $object->status); ?></td>
</tr>

<tr>
<td class="fieldrequired"><?php echo $langs->trans("Status").' ('.$langs->trans("Buy").')'; ?></td>
<td><?php echo $form->selectarray('statut_buy', $statutarray, $object->status_buy); ?></td>
</tr>

<tr><td><?php echo $langs->trans("Duration"); ?></td>
<td><input name="duration_value" size="6" maxlength="5" value="<?php echo $object->duration_value; ?>"> &nbsp;
<?php echo $object->duration_unit; ?>
</td></tr>

<tr><td class="tdtop"><?php echo $langs->trans("NoteNotVisibleOnBill"); ?></td><td>
<?php echo $object->textarea_note; ?>
</td></tr>
</table>

<br>

<div align="center"><input type="submit" class="button button-save" value="<?php echo $langs->trans("Save"); ?>"> &nbsp; &nbsp;
<input type="submit" class="button button-cancel" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>"></div>

</form>

<!-- END PHP TEMPLATE -->
