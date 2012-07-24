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

<!-- BEGIN PHP TEMPLATE predefinedproductline_edit.tpl.php -->
<form action="<?php echo $_SERVER["PHP_SELF"].'?id='.$this->id.'#'.$line->id; ?>" method="POST">
<input type="hidden" name="token" value="<?php  echo $_SESSION['newtoken']; ?>" />
<input type="hidden" name="action" value="updateligne" />
<input type="hidden" name="id" value="<?php echo $this->id; ?>" />
<input type="hidden" name="lineid" value="<?php echo $line->id; ?>" />

<tr <?php echo $bc[$var]; ?>>
	<td<?php echo (! empty($conf->global->MAIN_VIEW_LINE_NUMBER) ? ' colspan="2"' : ''); ?>>
		<div id="<?php echo $line->id; ?>"></div>
		<input type="hidden"	name="productid" value="<?php echo $line->fk_product; ?>" />
		<a href="<?php echo DOL_URL_ROOT.'/product/fiche.php?id='.$line->fk_product; ?>">
		<?php
		if ($line->product_type==1) echo img_object($langs->trans('ShowService'),'service');
		else print img_object($langs->trans('ShowProduct'),'product');
		echo ' '.$line->ref;
		?>
		</a>
		<?php
		echo ' - '.nl2br($line->product_label);
		echo '<br>';

		if (is_object($hookmanager))
		{
		    $fk_parent_line = ($_POST["fk_parent_line"] ? $_POST["fk_parent_line"] : $line->fk_parent_line);
			$parameters=array('fk_parent_line'=>$fk_parent_line);
		    echo $hookmanager->executeHooks('formEditProductOptions',$parameters,$this,$action);
		}

		// editeur wysiwyg
		$nbrows=ROWS_2;
		if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows=$conf->global->MAIN_INPUT_DESC_HEIGHT;
		require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
		$doleditor=new DolEditor('desc',$line->description,'',164,'dolibarr_details','',false,true,$conf->global->FCKEDITOR_ENABLE_DETAILS,$nbrows,70);
		$doleditor->Create();
		?>
	</td>

	<td align="right"><?php echo $form->load_tva('tva_tx',$line->tva_tx,$seller,$buyer,'',$line->info_bits); ?></td>

	<td align="right">
		<input size="6" type="text" class="flat" name="subprice" value="<?php echo price($line->subprice,0,'',0); ?>" />
	</td>

	<td align="right">
	<?php if (($line->info_bits & 2) != 2) { ?>
		<input size="2" type="text" class="flat" name="qty"	value="<?php echo $line->qty; ?>" />
	<?php } else { ?> &nbsp; <?php } ?>
	</td>

	<td align="right" nowrap>
	<?php if (($line->info_bits & 2) != 2) { ?>
		<input size="1" type="text" class="flat" name="remise_percent" value="<?php echo $line->remise_percent; ?>" />%
	<?php } else { ?>
		&nbsp;
	<?php } ?>
	</td>
<?php
if (! empty($conf->margin->enabled)) { 
?>
	<td align="right">
  <select id="fournprice" name="fournprice"></select>
  <input type="text" size="5" id="buying_price" name="buying_price" style="display: none;" value="<?php echo price($line->pa_ht,0,'',0); ?>">
  </td>
<?php
}
?>

	<td align="center" colspan="5" valign="middle">
		<input type="submit" class="button" name="save"	value="<?php echo $langs->trans("Save"); ?>"><br>
		<input type="submit" class="button" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>">
	</td>
</tr>

<?php if ($conf->service->enabled && $dateSelector && $line->product_type == 1)	{ ?>
<tr <?php echo $bc[$var]; ?>>
	<td colspan="9"><?php echo $langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' '; ?>
	<?php
	echo $form->select_date($line->date_start,'date_start',$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$line->date_start?0:1,"updateligne");
	echo ' '.$langs->trans('to').' ';
	echo $form->select_date($line->date_end,'date_end',$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$line->date_end?0:1,"updateligne");
	?>
	</td>
</tr>
<?php } ?></form>
<?php
if (! empty($conf->margin->enabled)) { 
?>
<script type="text/javascript">
$(document).ready(function() {
  $.post('<?php echo DOL_URL_ROOT; ?>/fourn/product/getSupplierPrices.php', {'idprod': <?php echo $line->fk_product; ?>}, function(data) {      
    if (data.length > 0) {
      var options = '';
      var trouve=false;
      $(data).each(function() {
        options += '<option value="'+this.id+'" price="'+this.price+'"';
        <?php
        if ($line->fk_fournprice > 0) {
        ?>
        if (this.id == <?php echo $line->fk_fournprice; ?>) {
          options += ' selected';
          $("#buying_price").val(this.price);
          trouve = true;
        }
        <?php
        }
        ?>
        options += '>'+this.label+'</option>';
      });
      options += '<option value=null'+(trouve?'':' selected')+'><?php echo $langs->trans("InputPrice"); ?></option>';
      $("#fournprice").html(options);
      if (trouve) {
        $("#buying_price").hide();
        $("#fournprice").show();
      }
      else {
        $("#buying_price").show();
      }
      $("#fournprice").change(function() {
        var selval = $(this).find('option:selected').attr("price");
        if (selval) 
          $("#buying_price").val(selval).hide();
        else
          $('#buying_price').show();
      });
    }
    else {
      $("#fournprice").hide();
      $('#buying_price').show();
    }
  },
  'json');
});
</script>
<?php } ?>
<!-- END PHP TEMPLATE predefinedproductline_edit.tpl.php -->

