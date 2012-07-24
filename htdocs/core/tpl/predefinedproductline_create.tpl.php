<?php
/* Copyright (C) 2010-2011 Regis Houssin       <regis@dolibarr.fr>
 * Copyright (C) 2010-2011 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
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
	<td<?php echo (! empty($conf->global->MAIN_VIEW_LINE_NUMBER) ? ' colspan="4"' : ' colspan="3"'); ?>>
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
<?php
$colspan = 4;
if (! empty($conf->margin->enabled)) { 
?>
	<td align="right"><?php echo $langs->trans('BuyingPrice'); ?></td>
<?php
  if($conf->global->DISPLAY_MARGIN_RATES)
    $colspan++;
  if($conf->global->DISPLAY_MARK_RATES)
    $colspan++;
}
?>
	<td colspan="<?php echo $colspan; ?>">&nbsp;</td>
</tr>

<form name="addpredefinedproduct" id="addpredefinedproduct" action="<?php echo $_SERVER["PHP_SELF"].'?id='.$this->id; ?>#add" method="POST">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="action" value="addline">
<input type="hidden" name="id" value="<?php echo $this->id; ?>">

<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery('#idprod').change(function() {
		  jQuery('#np_desc').focus();
	});
}); 
</script>

<tr <?php echo $bcnd[$var]; ?>>
	<td<?php echo (! empty($conf->global->MAIN_VIEW_LINE_NUMBER) ? ' colspan="4"' : ' colspan="3"'); ?>>
	<?php

	$form->select_produits('','idprod','',$conf->product->limit_size,$buyer->price_level);

	if (is_object($hookmanager))
	{
        $parameters=array('fk_parent_line'=>$_POST["fk_parent_line"]);
	    echo $hookmanager->executeHooks('formCreateProductOptions',$parameters,$object,$action);
	}

	// Editor wysiwyg
	require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
    $nbrows=ROWS_2;
    if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows=$conf->global->MAIN_INPUT_DESC_HEIGHT;
    $doleditor=new DolEditor('np_desc',$_POST["np_desc"],'',100,'dolibarr_details','',false,true,$conf->global->FCKEDITOR_ENABLE_DETAILS,$nbrows,70);
	$doleditor->Create();
	?>
	</td>
	<td align="right"><input type="text" size="2" name="qty" value="1"></td>
	<td align="right" nowrap><input type="text" size="1" name="remise_percent" value="<?php echo $buyer->remise_client; ?>">%</td>
<?php
$colspan = 4;
if (! empty($conf->margin->enabled)) { 
?>
	<td align="right">
  <select id="np_fournprice" name="np_fournprice" style="display: none;"></select>
  <input type="text" size="5" id="np_buying_price" name="np_buying_price" value="<?php echo (isset($_POST["np_buying_price"])?$_POST["np_buying_price"]:''); ?>">
  </td>
<?php
  if($conf->global->DISPLAY_MARGIN_RATES)
    $colspan++;
  if($conf->global->DISPLAY_MARK_RATES)
    $colspan++;
}
?>
	<td align="center" valign="middle" colspan="<?php echo $colspan; ?>"><input type="submit" class="button" value="<?php echo $langs->trans("Add"); ?>" name="addline"></td>
</tr>

<?php if ($conf->service->enabled && $dateSelector) {
if(! empty($conf->global->MAIN_VIEW_LINE_NUMBER))
	$colspan = 10;
else
	$colspan = 9;
if (! empty($conf->margin->enabled)) { 
  if($conf->global->DISPLAY_MARGIN_RATES)
    $colspan++;
  if($conf->global->DISPLAY_MARK_RATES)
    $colspan++;
}
?>
<tr <?php echo $bcnd[$var]; ?>>
	<td colspan="<?php echo $colspan; ?>">
	<?php
	echo $langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' ';
	echo $form->select_date('','date_start_predef',$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,1,"addpredefinedproduct");
	echo ' '.$langs->trans('to').' ';
	echo $form->select_date('','date_end_predef',$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,1,"addpredefinedproduct");
	?>
	</td>
</tr>
<?php } ?>

</form>
<?php
if (! empty($conf->margin->enabled)) { 
?>
<script type="text/javascript">
$("#idprod").change(function() {     
  $("#np_fournprice options").remove();
  $("#np_buying_price").show();
  $.post('<?php echo DOL_URL_ROOT; ?>/fourn/product/getSupplierPrices.php', {'idprod': $(this).val()}, function(data) {     
    if (data.length > 0) {
      var options = '';
      var i = 0;
      $(data).each(function() {
        i++;        
        options += '<option value="'+this.id+'" price="'+this.price+'"';
        if (i == 1) {
          options += ' selected';
          $("#np_buying_price").val(this.price);
        }
        options += '>'+this.label+'</option>';
      });
      options += '<option value=null><?php echo $langs->trans("InputPrice"); ?></option>';
      $("#np_fournprice").html(options);
      $("#np_buying_price").hide();
      $("#np_fournprice").show();
      $("#np_fournprice").change(function() {
        var selval = $(this).find('option:selected').attr("price");
        if (selval) 
          $("#np_buying_price").val(selval).hide();
        else
          $('#np_buying_price').show();
      });
    }
  },
  'json');
});
</script>
<?php } ?>
<!-- END PHP TEMPLATE predefinedproductline_create.tpl.php -->
