<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2013	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * Need to have following variables defined:
 * $conf
 * $langs
 * $dateSelector
 * $this (invoice, order, ...)
 * $line defined
 */

$usemargins=0;
if (! empty($conf->margin->enabled) && ! empty($object->element) && in_array($object->element,array('facture','propal','commande'))) $usemargins=1;

?>

<!-- BEGIN PHP TEMPLATE freeproductline_create.tpl.php -->

<form name="addproduct" id="addproduct"	action="<?php echo $_SERVER["PHP_SELF"].'?id='.$this->id; ?>#add" method="POST">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="action" value="addline">
<input type="hidden" name="mode" value="libre">
<input type="hidden" name="id" value="<?php echo $this->id; ?>">

<tr class="liste_titre nodrag nodrop">
	<td
	<?php echo (! empty($conf->global->MAIN_VIEW_LINE_NUMBER) ? ' colspan="2"' : ''); ?>><div
			id="add"></div> <span class="hideonsmartphone"><?php echo $langs->trans('AddNewLine').' - ' ?></span><?php echo $langs->trans("FreeZone"); ?>
	</td>
	<td align="right"><?php echo $langs->trans('VAT'); ?></td>
	<td align="right"><?php echo $langs->trans('PriceUHT'); ?></td>
	<td align="right"><?php echo $langs->trans('Qty'); ?></td>
	<td align="right"><?php echo $langs->trans('ReductionShort'); ?></td>
	<?php
	$colspan = 4;
	if (! empty($usemargins))
	{
		?>
		<td align="right">
		<?php
		if ($conf->global->MARGIN_TYPE == "1")
			echo $langs->trans('BuyingPrice');
		else
			echo $langs->trans('CostPrice');
		?>
		</td>
		<?php
		if (! empty($conf->global->DISPLAY_MARGIN_RATES)) $colspan++;
		if (! empty($conf->global->DISPLAY_MARK_RATES))   $colspan++;
	}
	?>
	<td colspan="<?php echo $colspan; ?>">&nbsp;</td>
</tr>

<tr <?php echo $bcnd[$var]; ?>>
	<?php 
	if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
			$coldisplay=2; }
	else {
			$coldisplay=0; }
	?>
	
	<td<?php echo (! empty($conf->global->MAIN_VIEW_LINE_NUMBER) ? ' colspan="2"' : ''); ?>>
		<?php

			echo '<span>';
			echo $form->select_type_of_lines(isset($_POST["type"])?$_POST["type"]:-1,'type',1);
			echo '</span>';

			if (is_object($hookmanager))
			{
				$parameters=array();
				$reshook=$hookmanager->executeHooks('formCreateProductOptions',$parameters,$object,$action);
			}

			if ((! empty($conf->product->enabled) && ! empty($conf->service->enabled)) || (empty($conf->product->enabled) && empty($conf->service->enabled))) echo '<br>';

			// Editor wysiwyg
			require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
			$nbrows=ROWS_2;
			$enabled=(! empty($conf->global->FCKEDITOR_ENABLE_DETAILS)?$conf->global->FCKEDITOR_ENABLE_DETAILS:0);
			if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows=$conf->global->MAIN_INPUT_DESC_HEIGHT;
			$doleditor=new DolEditor('dp_desc',GETPOST('dp_desc'),'',100,'dolibarr_details','',false,true,$enabled,$nbrows,70);
			$doleditor->Create();
			?>
	</td>

	<td align="right"><?php
	if ($seller->tva_assuj == "0") echo '<input type="hidden" name="np_tva_tx" value="0">0';
	else echo $form->load_tva('tva_tx', (isset($_POST["tva_tx"])?$_POST["tva_tx"]:-1), $seller, $buyer);
	?>
	</td>
	<td align="right"><input type="text" size="5" name="price_ht" class="flat" value="<?php echo (isset($_POST["price_ht"])?$_POST["price_ht"]:''); ?>">
	</td>
	<td align="right"><input type="text" size="2" name="qty" class="flat" value="<?php echo (isset($_POST["qty"])?$_POST["qty"]:1); ?>"></td>
	<td align="right" class="nowrap"><input type="text" size="1" class="flat" value="<?php echo $buyer->remise_client; ?>" name="remise_percent"><span class="hideonsmartphone">%</span></td>
	<?php
	$colspan = 4;
	if (! empty($usemargins))
	{
		?>
		<td align="right"><input type="text" size="5" name="buying_price" class="flat"
			value="<?php echo (isset($_POST["buying_price"])?$_POST["buying_price"]:''); ?>">
		</td>
		<?php
			if (! empty($conf->global->DISPLAY_MARGIN_RATES)) {
				$colspan++;
				$coldisplay++;
			}
			if (! empty($conf->global->DISPLAY_MARK_RATES)) {
				$colspan++;
				$coldisplay++;
			}
	}
	?>
	<td align="center" valign="middle" colspan="<?php echo $colspan; ?>"><input type="submit" class="button" value="<?php echo $langs->trans('Add'); ?>" name="addline"></td>
		<?php
		//Line extrafield
		if (!empty($extrafieldsline)) {
			if ($this->table_element_line=='commandedet') {
				$newline = new OrderLine($this->db);
			}elseif ($this->table_element_line=='propaldet') {
				$newline = new PropaleLigne($this->db);
			}elseif ($this->table_element_line=='facturedet') {
				$newline = new FactureLigne($this->db);
			}
			if (is_object($newline)) {
			print $newline->showOptionals($extrafieldsline,'edit',array('style'=>$bcnd[$var],'colspan'=>$coldisplay+8));
			}
		}
		?>
</tr>

<?php
if (! empty($conf->service->enabled) && $dateSelector)
{
	if(! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) $colspan = 10;
	else $colspan = 9;

	if (! empty($usemargins))
	{
		$colspan++; // For the buying price
		if($conf->global->DISPLAY_MARGIN_RATES)	$colspan++;
		if($conf->global->DISPLAY_MARK_RATES)	$colspan++;
	}
?>

<tr <?php echo $bcnd[$var]; ?>>
	<td colspan="<?php echo $colspan; ?>"><?php
	if (! empty($object->element) && $object->element == 'contrat')
	{
		print $langs->trans("DateStartPlanned").' ';
		$form->select_date('',"date_start_sl",$usehm,$usehm,1,"addline_sl");
		print ' &nbsp; '.$langs->trans("DateEndPlanned").' ';
		$form->select_date('',"date_end_sl",$usehm,$usehm,1,"addline_sl");
	}
	else
	{
		echo $langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' ';
		echo $form->select_date('','date_start',empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?0:1,empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?0:1,1,"addproduct");
		echo ' '.$langs->trans('to').' ';
		echo $form->select_date('','date_end',empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?0:1,empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?0:1,1,"addproduct");
	}
	?>
	</td>
</tr>
<?php
}
?>

</form>
<!-- END PHP TEMPLATE freeproductline_create.tpl.php -->
