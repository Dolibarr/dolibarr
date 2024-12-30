<?php
/* Copyright (C) 2010-2018  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
/**
 * @var Canvas $this
 * @var Conf $conf
 * @var Form $form
 * @var Translate $langs
 * @var User $user
 *
 * @var string $canvas
 * @var int $refalreadyexists
 */
// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit(1);
}


$object = $GLOBALS['object'];
/** @var Product $object */

$statutarray = array('1' => $langs->trans("OnSell"), '0' => $langs->trans("NotOnSell"));
?>

<!-- BEGIN PHP TEMPLATE product/canvas/product/tpl/card_create.tpl.php  -->

<?php
print load_fiche_titre($langs->trans("NewProduct"), '', 'product');
print dol_get_fiche_head([]);
?>

<?php dol_htmloutput_errors((is_numeric($object->error) ? '' : $object->error), $object->errors); ?>

<?php dol_htmloutput_errors($GLOBALS['mesg'], $GLOBALS['mesgs']); ?>

<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post">
<input type="hidden" name="token" value="<?php echo newToken(); ?>">
<input type="hidden" name="action" value="add">
<input type="hidden" name="type" value="0">
<input type="hidden" name="canvas" value="<?php echo $canvas; ?>">
<?php if (!isModEnabled('stock')) { ?>
<input name="seuil_stock_alerte" type="hidden" value="0">
<?php } ?>

<table class="border allwidth">

<tr>
<td class="fieldrequired" width="20%"><?php echo $langs->trans("Ref"); ?></td>
<td><input name="ref" size="40" maxlength="32" value="<?php echo $object->ref; ?>">
<?php if ($refalreadyexists == 1) {
	echo $langs->trans("RefAlreadyExists");
} ?>
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

<?php if (isModEnabled('stock')) { ?>
<tr><td><?php echo $langs->trans("StockLimit"); ?></td><td>
<input name="seuil_stock_alerte" size="4" value="<?php echo $object->seuil_stock_alerte; ?>">
</td></tr>
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

<tr><td class="tdtop"><?php echo $langs->trans("NoteNotVisibleOnBill"); ?></td><td>
<?php echo $object->textarea_note; ?>
</td></tr>
</table>

<br>

<?php if (!$conf->global->PRODUIT_MULTIPRICES) { ?>
<table class="border allwidth">

<tr><td><?php echo $langs->trans("SellingPrice"); ?></td>
<td><input name="price" size="10" value="<?php echo $object->price; ?>">
	<?php echo $object->price_base_type; ?>
</td></tr>

<tr><td><?php echo $langs->trans("MinPrice"); ?></td>
<td><input name="price_min" size="10" value="<?php echo $object->price_min; ?>">
</td></tr>

<tr><td width="20%"><?php echo $langs->trans("VATRate"); ?></td><td>
	<?php echo $object->tva_tx; ?>
</td></tr>

</table>

<br>
<?php } ?>

<div align="center"><input type="submit" class="button" value="<?php echo $langs->trans("Create"); ?>"></div>

</form>

<!-- END PHP TEMPLATE -->
