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

<?php
if ($conf->global->PRODUIT_USE_MARKUP)
$colspan = 'colspan="4"';
else
$colspan = 'colspan="3"';
?>

<tr class="liste_titre">
	<td <?php echo $colspan; ?>>
	<?php
	echo $langs->trans("AddNewLine").' - ';
	if ($conf->service->enabled)
	echo $langs->trans('RecordedProductsAndServices');
	else
	echo $langs->trans('RecordedProducts');
	?>
	</td>
	<td align="right"><?php echo $langs->trans('Qty'); ?></td>
	<td align="right"><?php echo $langs->trans('ReductionShort'); ?></td>
	<td colspan="4">&nbsp;</td>
</tr>

<form id="addpredefinedproduct" action="<?php echo $_SERVER["PHP_SELF"].'?id='.$object->id; ?>#add" method="POST">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="action" value="addline">
<input type="hidden" name="id" value="<?php echo $object->id; ?>">

<tr <?php echo $bc[$var]; ?>>
	<td <?php echo $colspan; ?>>
	<?php
	// multiprix
	if($conf->global->PRODUIT_MULTIPRICES)
	$html->select_produits('','idprod','',$conf->product->limit_size,$societe->price_level);
	else
	$html->select_produits('','idprod','',$conf->product->limit_size);
	
	if (! $conf->global->PRODUIT_USE_SEARCH_TO_SELECT) print '<br>';

	// Editor wysiwyg
	if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS)
	{
		require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
		$doleditor=new DolEditor('np_desc',$_POST["np_desc"],100,'dolibarr_details');
		$doleditor->Create();
	}
	else
	{
		$nbrows=ROWS_2;
		if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows=$conf->global->MAIN_INPUT_DESC_HEIGHT;
		echo '<textarea cols="70" name="np_desc" rows="'.$nbrows.'" class="flat">'.$_POST["np_desc"].'</textarea>';
	}
	?>
	</td>
	<td align="right"><input type="text" size="2" name="qty" value="1"></td>
	<td align="right" nowrap><input type="text" size="1" name="remise_percent" value="<?php echo $societe->remise_client; ?>">%</td>

	<td align="center" valign="middle" colspan="4"><input type="submit" class="button" value="<?php echo $langs->trans("Add"); ?>" name="addline"></td>
</tr>

</form>

<!-- END PHP TEMPLATE -->
