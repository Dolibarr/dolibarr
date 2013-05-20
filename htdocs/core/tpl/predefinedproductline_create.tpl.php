<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Christophe Battarel	<christophe.battarel@altairis.fr>
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

<!-- BEGIN PHP TEMPLATE predefinedproductline_create.tpl.php -->

<tr class="liste_titre nodrag nodrop">
	<td<?php echo (! empty($conf->global->MAIN_VIEW_LINE_NUMBER) ? ' colspan="4"' : ' colspan="3"'); ?>>
	<?php
	echo $langs->trans("AddNewLine").' - ';
	if (! empty($conf->product->enabled) && empty($conf->service->enabled)) echo $langs->trans('RecordedProducts');
	else if (empty($conf->product->enabled) && ! empty($conf->service->enabled)) echo $langs->trans('RecordedServices');
	else echo $langs->trans('RecordedProductsAndServices');
	?>
	</td>
	<td align="right"><?php echo $langs->trans('Qty'); ?></td>
	<td align="right"><?php echo $langs->trans('ReductionShort'); ?></td>
<?php
$colspan = 4;
if (! empty($usemargins)) 
{
	if (! empty($conf->global->DISPLAY_MARGIN_RATES)) $colspan++;
	if (! empty($conf->global->DISPLAY_MARK_RATES))   $colspan++;
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
} 
?>
	<td colspan="<?php echo $colspan; ?>">&nbsp;</td>
</tr>

<form name="addpredefinedproduct" id="addpredefinedproduct" action="<?php echo $_SERVER["PHP_SELF"].'?id='.$this->id; ?>#add" method="POST">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="action" value="addline">
<input type="hidden" name="mode" value="predefined">
<input type="hidden" name="id" value="<?php echo $this->id; ?>">

<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery('#idprod').change(function() {
		  if (jQuery('#idprod').val() > 0) jQuery('#np_desc').focus();
	});
});
</script>

<tr <?php echo $bcnd[$var]; ?>>
	<td<?php echo (! empty($conf->global->MAIN_VIEW_LINE_NUMBER) ? ' colspan="4"' : ' colspan="3"'); ?>>
	<?php

	echo '<span>';
	$filtertype='';
	if (! empty($object->element) && $object->element == 'contrat') $filtertype='1';
	$form->select_produits('','idprod',$filtertype,$conf->product->limit_size,$buyer->price_level);
	echo '</span>';

	if (is_object($hookmanager))
	{
        $parameters=array('fk_parent_line'=>GETPOST('fk_parent_line','int'));
	    $reshook=$hookmanager->executeHooks('formCreateProductOptions',$parameters,$object,$action);
	}

	echo '<br>';

	// Editor wysiwyg
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $nbrows=ROWS_2;
    $enabled=(! empty($conf->global->FCKEDITOR_ENABLE_DETAILS)?$conf->global->FCKEDITOR_ENABLE_DETAILS:0);
    if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows=$conf->global->MAIN_INPUT_DESC_HEIGHT;
    $doleditor=new DolEditor('np_desc',GETPOST('np_desc"'),'',100,'dolibarr_details','',false,true,$enabled,$nbrows,70);
	$doleditor->Create();
	?>
	</td>
	<td align="right"><input type="text" size="2" name="qty" value="1"></td>
	<td align="right" nowrap><input type="text" size="1" name="remise_percent" value="<?php echo $buyer->remise_client; ?>">%</td>
	<?php
	$colspan = 4;
	if (! empty($usemargins)) 
	{
		if (! empty($conf->global->DISPLAY_MARGIN_RATES)) $colspan++;
		if (! empty($conf->global->DISPLAY_MARK_RATES))   $colspan++;
		?>
		<td align="right">
			<select id="fournprice" name="fournprice" style="display: none;"></select>
			<input type="text" size="5" id="buying_price" name="buying_price" value="<?php echo (isset($_POST["buying_price"])?$_POST["buying_price"]:''); ?>">
		</td>
		<?php
	}
	?>
	<td align="center" valign="middle" colspan="<?php echo $colspan; ?>">
		<input type="submit" class="button" value="<?php echo $langs->trans("Add"); ?>" name="addline">
	</td>
</tr>

<?php 
if (! empty($conf->service->enabled) && $dateSelector) 
{
	if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) $colspan = 10;
	else $colspan = 9;
	if (! empty($usemargins))
	{
		$colspan++; // For the buying price
		if (! empty($conf->global->DISPLAY_MARGIN_RATES)) $colspan++;
		if (! empty($conf->global->DISPLAY_MARK_RATES))   $colspan++;
	}
?>
<tr <?php echo $bcnd[$var]; ?>>
	<td colspan="<?php echo $colspan; ?>">
	<?php
	if (! empty($object->element) && $object->element == 'contrat')
	{
		print $langs->trans("DateStartPlanned").' ';
		$form->select_date('',"date_start",$usehm,$usehm,1,"addline");
		print ' &nbsp; '.$langs->trans("DateEndPlanned").' ';
		$form->select_date('',"date_end",$usehm,$usehm,1,"addline");		
	}
	else
	{
		echo $langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' ';
		echo $form->select_date('','date_start_predef',$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,1,"addpredefinedproduct");
		echo ' '.$langs->trans('to').' ';
		echo $form->select_date('','date_end_predef',$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,1,"addpredefinedproduct");
	}
	?>
	</td>
</tr>
<?php
} 
?>

</form>

<?php
if (! empty($usemargins)) 
{
?>
	<script type="text/javascript">
	$("#idprod").change(function() {
	  $("#fournprice options").remove();
	  $("#fournprice").hide();
	  $("#buying_price").val("").show();
	  $.post('<?php echo DOL_URL_ROOT; ?>/fourn/ajax/getSupplierPrices.php', {'idprod': $(this).val()}, function(data) {
	    if (data && data.length > 0) {
	      var options = '';
	      var i = 0;
	      $(data).each(function() {
	        i++;
	        options += '<option value="'+this.id+'" price="'+this.price+'"';
	        if (i == 1) {
	          options += ' selected';
	          $("#buying_price").val(this.price);
	        }
	        options += '>'+this.label+'</option>';
	      });
	      options += '<option value=null><?php echo $langs->trans("InputPrice"); ?></option>';
	      $("#buying_price").hide();
	      $("#fournprice").html(options).show();
	      $("#fournprice").change(function() {
	        var selval = $(this).find('option:selected').attr("price");
	        if (selval)
	          $("#buying_price").val(selval).hide();
	        else
	          $('#buying_price').show();
	      });
	    }
	  },
	  'json');
	});
	</script>
<?php
} 
?>
<!-- END PHP TEMPLATE predefinedproductline_create.tpl.php -->
