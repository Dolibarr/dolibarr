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

<!-- BEGIN PHP TEMPLATE freeproductline_create.tpl.php -->

<?php
// TODO à déplacer
if ($conf->global->PRODUIT_USE_MARKUP) $colspan = 'colspan="2"';
?>

<tr class="liste_titre nodrag nodrop">
	<td <?php echo $colspan; ?>><a name="add"></a><?php echo $langs->trans('AddNewLine').' - '.$langs->trans("FreeZone"); ?></td>
	<td align="right"><?php echo $langs->trans('VAT'); ?></td>
	<td align="right"><?php echo $langs->trans('PriceUHT'); ?></td>
	<td align="right"><?php echo $langs->trans('Qty'); ?></td>
	<td align="right"><?php echo $langs->trans('ReductionShort'); ?></td>
	<td colspan="4">&nbsp;</td>
</tr>

<form name="addproduct" id="addproduct" action="<?php echo $_SERVER["PHP_SELF"].'?id='.$this->id; ?>#add" method="POST">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="action" value="addline">
<input type="hidden" name="id" value="<?php echo $this->id; ?>">

<tr <?php echo $bcnd[$var]; ?>>
	<td <?php echo $colspan; ?>>
	<?php
	echo $html->select_type_of_lines(isset($_POST["type"])?$_POST["type"]:-1,'type',1);
	if ($conf->product->enabled && $conf->service->enabled) echo '<br>';

	// Editor wysiwyg
	require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
    $nbrows=ROWS_2;
    if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows=$conf->global->MAIN_INPUT_DESC_HEIGHT;
	$doleditor=new DolEditor('dp_desc',$_POST["dp_desc"],100,'dolibarr_details','',false,true,$conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS,$nbrows,70);
	$doleditor->Create();
	?>
	</td>

	<td align="right">
	<?php
	if ($soc->tva_assuj == "0") echo '<input type="hidden" name="np_tva_tx" value="0">0';
	else $html->select_tva('np_tva_tx', $conf->defaulttx, $mysoc, $soc);
	?>
	</td>
	<td align="right"><input type="text" size="5" name="np_price"></td>
	<td align="right"><input type="text" size="2" name="qty" value="<?php echo (isset($_POST["qty"])?$_POST["qty"]:1); ?>"></td>
	<td align="right" nowrap><input type="text" size="1" value="<?php echo $soc->remise_client; ?>" name="remise_percent">%</td>
	<td align="center" valign="middle" colspan="4"><input type="submit" class="button" value="<?php echo $langs->trans('Add'); ?>" name="addline"></td>
</tr>

<?php if ($conf->service->enabled && $dateSelector) {?>
<tr <?php echo $bcnd[$var]; ?>>
	<td colspan="9">
	<?php
	echo $langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' ';
	echo $html->select_date('','date_start',$usehm,$usehm,1,"addproduct");
	echo ' '.$langs->trans('to').' ';
	echo $html->select_date('','date_end',$usehm,$usehm,1,"addproduct");
	?>
	</td>
</tr>
<?php } ?>

</form>

<!-- END PHP TEMPLATE freeproductline_create.tpl.php -->
