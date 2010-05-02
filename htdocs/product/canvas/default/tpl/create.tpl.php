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

<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="action" value="add">
<input type="hidden" name="canvas" value="<?php echo $_GET['canvas']; ?>">

<?php echo $product->tpl['title']; ?>

<table class="border" width="100%">

<tr>
<td class="fieldrequired" width="20%"><?php echo $langs->trans("Ref"); ?></td>
<td><input name="ref" size="40" maxlength="32" value="<?php echo $product->tpl['ref']; ?>">
<?php if ($_error == 1) echo $langs->trans("RefAlreadyExists"); ?>
</td></tr>

<tr>
<td class="fieldrequired"><?php echo $langs->trans("Label"); ?></td>
<td><input name="libelle" size="40" value="<?php echo $product->tpl['label']; ?>"></td>
</tr>

<tr>
<td class="fieldrequired"><?php echo $langs->trans("Status"); ?></td>
<td><?php echo $product->tpl['status']; ?></td>
</tr>

<?php if ($conf->stock->enabled) { ?>
<tr><td><?php echo $langs->trans("StockLimit"); ?></td><td>
<input name="seuil_stock_alerte" size="4" value="<?php echo $product->tpl['seuil_stock_alerte']; ?>">
</td></tr>
<?php } else { ?>
<input name="seuil_stock_alerte" type="hidden" value="0">
<?php } ?>

<tr><td valign="top"><?php echo $langs->trans("Description"); ?></td><td>
<?php if (! $product->tpl['textarea_description']) { 
$product->tpl['doleditor_description']->Create();
}else{
echo $product->tpl['textarea_description'];
}?>
</td></tr>

<tr><td><?php echo $langs->trans("Nature"); ?></td><td>
<?php echo $product->tpl['finished']; ?>
</td></tr>

<tr><td><?php echo $langs->trans("Weight"); ?></td><td>
<input name="weight" size="4" value="<?php echo $product->tpl['weight']; ?>">
<?php echo $product->tpl['weight_units']; ?>
</td></tr>

<tr><td><?php echo $langs->trans("Length"); ?></td><td>
<input name="size" size="4" value="<?php echo $product->tpl['length']; ?>">
<?php echo $product->tpl['length_units']; ?>
</td></tr>

<tr><td><?php echo $langs->trans("Surface"); ?></td><td>
<input name="surface" size="4" value="<?php echo $product->tpl['surface']; ?>">
<?php echo $product->tpl['surface_units']; ?>
</td></tr>

<tr><td><?php echo $langs->trans("Volume"); ?></td><td>
<input name="volume" size="4" value="<?php echo $product->tpl['volume']; ?>">
<?php echo $product->tpl['volume_units']; ?>
</td></tr>

<tr><td><?php echo $langs->trans("Hidden"); ?></td>
<td><?php echo $product->tpl['hidden']; ?></td></tr>

<tr><td valign="top"><?php echo $langs->trans("NoteNotVisibleOnBill"); ?></td><td>
<?php if (! $product->tpl['textarea_note']) { 
$product->tpl['doleditor_note']->Create();
}else{
echo $product->tpl['textarea_note'];
}?>
</td></tr>
</table>

<br>

<?php if (! $conf->global->PRODUIT_MULTIPRICES) { ?>

<table class="border" width="100%">

<tr><td><?php echo $langs->trans("SellingPrice"); ?></td>
<td><input name="price" size="10" value="<?php echo $product->tpl['price']; ?>">
<?php echo $product->tpl['price_base_type']; ?>
</td></tr>

<tr><td><?php echo $langs->trans("MinPrice"); ?></td>
<td><input name="price_min" size="10" value="<?php echo $product->tpl['price_min']; ?>">
</td></tr>

<tr><td width="20%"><?php echo $langs->trans("VATRate"); ?></td><td>
<?php echo $product->tpl['tva_tx']; ?>
</td></tr>

</table>

<br>
<?php } ?>

<center><input type="submit" class="button" value="<?php echo $langs->trans("Create"); ?>"></center>

</form>

<!-- END PHP TEMPLATE -->