<?php
/* Copyright (C) 2010-2011 Regis Houssin       <regis@dolibarr.fr>
 * Copyright (C) 2010-2011 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * $Id: predefinedproductline_create.tpl.php,v 1.14 2011/07/31 23:45:12 eldy Exp $
 *
 * Need to have following variables defined:
 * $conf
 * $langs
 * $dateSelector
 * $this (invoice, order, ...)
 * $line defined
 */
?>

<!-- BEGIN PHP TEMPLATE predefinedproductline_create.tpl.php -->

<tr class="liste_titre nodrag nodrop">
	<td colspan="3">
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

<form name="addpredefinedproduct" id="addpredefinedproduct" action="<?php echo $_SERVER["PHP_SELF"].'?id='.$this->id; ?>#add" method="POST">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="action" value="addline">
<input type="hidden" name="id" value="<?php echo $this->id; ?>">

<tr <?php echo $bcnd[$var]; ?>>
	<td colspan="3">
	<?php
	// multiprix
	if($conf->global->PRODUIT_MULTIPRICES)
	$html->select_produits('','idprod','',$conf->product->limit_size,$buyer->price_level);
	else
	$html->select_produits('','idprod','',$conf->product->limit_size);

	if (! $conf->global->PRODUIT_USE_SEARCH_TO_SELECT) echo '<br>';
	
	if (! empty($this->hooks)) {
		foreach($this->hooks as $hook) {
			if (! empty($hook['modules'])) {
				foreach($hook['modules'] as $module) {
					if (method_exists($module,'formCreateProductOptions')) {
						$module->formCreateProductOptions($object);
						echo '<br>';
					}
				}
			}
		}
	}

	// Editor wysiwyg
	require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
    $nbrows=ROWS_2;
    if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows=$conf->global->MAIN_INPUT_DESC_HEIGHT;
    $doleditor=new DolEditor('np_desc',$_POST["np_desc"],'',100,'dolibarr_details','',false,true,$conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS,$nbrows,70);
	$doleditor->Create();
	?>
	</td>
	<td align="right"><input type="text" size="2" name="qty" value="1"></td>
	<td align="right" nowrap><input type="text" size="1" name="remise_percent" value="<?php echo $buyer->remise_client; ?>">%</td>

	<td align="center" valign="middle" colspan="4"><input type="submit" class="button" value="<?php echo $langs->trans("Add"); ?>" name="addline"></td>
</tr>

<?php if ($conf->service->enabled && $dateSelector) {?>
<tr <?php echo $bcnd[$var]; ?>>
	<td colspan="9">
	<?php
	echo $langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' ';
	echo $html->select_date('','date_start_predef',$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,1,"addpredefinedproduct");
	echo ' '.$langs->trans('to').' ';
	echo $html->select_date('','date_end_predef',$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,1,"addpredefinedproduct");
	?>
	</td>
</tr>
<?php } ?>

</form>
<!-- END PHP TEMPLATE predefinedproductline_create.tpl.php -->
