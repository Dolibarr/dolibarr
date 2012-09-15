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
 */

$object=$GLOBALS['object'];

$statutarray=array('1' => $langs->trans("OnSell"), '0' => $langs->trans("NotOnSell"));
?>

<!-- BEGIN PHP TEMPLATE -->

<?php
print_fiche_titre($langs->trans("Product"));

dol_htmloutput_errors($object->error,$object->errors);
?>

<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
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
<td><input name="libelle" size="40" value="<?php echo $object->label; ?>"></td>
</tr>

<tr>
<td class="fieldrequired"><?php echo $langs->trans("Status").' ('.$langs->trans("Sell").')'; ?></td>
<td><?php echo $form->selectarray('statut',$statutarray,$object->status); ?></td>
</tr>

<tr>
<td class="fieldrequired"><?php echo $langs->trans("Status").' ('.$langs->trans("Buy").')'; ?></td>
<td><?php echo $form->selectarray('statut_buy',$statutarray,$object->status_tobuy); ?></td>
</tr>

<?php if (! empty($conf->stock->enabled)) { ?>
<tr><td><?php echo $langs->trans("StockLimit"); ?></td><td>
<input name="seuil_stock_alerte" size="4" value="<?php echo $object->seuil_stock_alerte; ?>">
</td></tr>
<?php } else { ?>
<input name="seuil_stock_alerte" type="hidden" value="0">
<?php } ?>

<tr><td><?php echo $langs->trans("Nature"); ?></td><td>
<?php echo $object->finished; ?>
</td></tr>

<tr><td><?php echo $langs->trans("Weight"); ?></td><td>
<input name="weight" size="4" value="<?php echo $object->weight; ?>">
<?php echo $object->weight_units; ?>
</td></tr>

<tr><td><?php echo $langs->trans("Length"); ?></td><td>
<input name="size" size="4" value="<?php echo $object->length; ?>">
<?php echo $object->length_units; ?>
</td></tr>

<tr><td><?php echo $langs->trans("Surface"); ?></td><td>
<input name="surface" size="4" value="<?php echo $object->surface; ?>">
<?php echo $object->surface_units; ?>
</td></tr>

<tr><td><?php echo $langs->trans("Volume"); ?></td><td>
<input name="volume" size="4" value="<?php echo $object->volume; ?>">
<?php echo $object->volume_units; ?>
</td></tr>

<tr><td valign="top"><?php echo $langs->trans("NoteNotVisibleOnBill"); ?></td><td>
<?php echo $object->textarea_note; ?>
</td></tr>
</table>

<br>

<div align="center"><input type="submit" class="button" value="<?php echo $langs->trans("Save"); ?>"> &nbsp; &nbsp;
<input type="submit" class="button" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>"></div>

</form>

<!-- END PHP TEMPLATE -->