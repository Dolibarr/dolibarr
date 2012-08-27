<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis@dolibarr.fr>
 * Copyright (C) 2010-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Christophe Battarel	<christophe.battarel@altairis.fr>
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

<!-- BEGIN PHP TEMPLATE freeproductline_create.tpl.php -->
<tr class="liste_titre nodrag nodrop">
	<td<?php echo (! empty($conf->global->MAIN_VIEW_LINE_NUMBER) ? ' colspan="2"' : ''); ?>><div id="add"></div><?php echo $langs->trans('AddNewLine'); ?></td>
	<td align="right"><?php echo $langs->trans('VAT'); ?></td>
	<td align="right"><?php echo $langs->trans('PriceUHT'); ?></td>
	<td align="right"><?php echo $langs->trans('PriceUTTC'); ?></td>
	<td align="right"><?php echo $langs->trans('Qty'); ?></td>
	<td align="right"><?php echo $langs->trans('ReductionShort'); ?></td>
<?php
$colspan = 4;
$colspan2 = 12;
if (! empty($conf->margin->enabled)) {
?>
	<td align="right"><?php echo $langs->trans('BuyingPrice'); ?></td>
<?php
  if (! empty($conf->global->DISPLAY_MARGIN_RATES)) {
  	$colspan++;
  	$colspan2++;
  }
  if (! empty($conf->global->DISPLAY_MARK_RATES)) {
  	$colspan++;
  	$colspan2++;
  }
  if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
  	$colspan2++;
  }
}
?>
	<td colspan="<?php echo $colspan; ?>">&nbsp;</td>
</tr>

<form name="addproduct" id="addproduct" action="<?php echo $_SERVER["PHP_SELF"].'?id='.$this->id; ?>#add" method="POST">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
<input type="hidden" name="action" value="addline" />
<input type="hidden" name="id" value="<?php echo $this->id; ?>" />

<?php if (! empty($conf->product->enabled) || ! empty($conf->service->enabled)) { ?>
<tr class="liste_titre nodrag nodrop">
	<td colspan="<?php echo $colspan2; ?>">
		<?php
		if (! empty($conf->product->enabled) || ! empty($conf->service->enabled))
		{
			// show/hide, update elements after select
			$ajaxoptions=array(
					'update' => array(
							'select_type' => 'type',
							'product_ref' => 'value',
							'product_label' => 'label2',
							'origin_label_cache' => 'label2',
							'origin_desc_cache' => 'desc',
							'price_base_type' => 'pricebasetype',
							'price_ht' => 'price_ht',
							'origin_price_ht_cache' => 'price_ht',
							//'price_ttc' => 'price_ttc',
							//'origin_price_ttc_cache' => 'price_ttc'
					),
					'show' => array(
							'update_label_area',
							'update_desc_area',
							'update_price_area'
					),
					'disabled' => array(
							'select_type'
					)
			);
			$form->select_produits('', 'idprod', '', $conf->product->limit_size, $buyer->price_level, 1, 2, '', 3, $ajaxoptions);
		}
		?>
		<span id="add_product_area" class="hideobject"> | <input type="checkbox" id="add_product_checkbox" name="add_product" value="1" />
			<span id="add_product_text" class="hideobject">
				<?php echo $form->textwithtooltip($langs->trans('AddThisProductCard'), $langs->trans('HelpAddThisProductCard'),1,0,'','',3); ?>
			</span>
			<span id="add_service_text" class="hideobject">
				<?php echo $form->textwithtooltip($langs->trans('AddThisServiceCard'), $langs->trans('HelpAddThisServiceCard'),1,0,'','',3); ?>
			</span>
		</span>
		<span id="update_label_area" class="hideobject"> | <input type="checkbox" id="update_label_checkbox" name="update_label" value="1" />
			<?php echo $form->textwithtooltip($langs->trans('UpdateOriginalProductLabel'), $langs->trans('HelpUpdateOriginalProductLabel'),1,0,'','',3); ?>
		</span>
		<span id="update_desc_area" class="hideobject"> | <input type="checkbox" id="update_desc_checkbox" name="update_desc" value="1" />
			<?php echo $form->textwithtooltip($langs->trans('UpdateOriginalProductDescription'), $langs->trans('HelpUpdateOriginalProductDescription'),1,0,'','',3); ?>
		</span>
		<span id="update_price_area" class="hideobject"> | <input type="checkbox" id="update_price_checkbox" name="update_price" value="1" />
			<?php echo $form->textwithtooltip($langs->trans('UpdateOriginalProductPrice'), $langs->trans('HelpUpdateOriginalProductPrice'),1,0,'','',3); ?>
		</span>
	</td>
</tr>
<?php } ?>

<tr <?php echo $bcnd[$var]; ?>>
	<td colspan="<?php echo $colspan2; ?>">
	<?php

	echo $form->select_type_of_lines((GETPOST('type')?GETPOST('type'):-1), 'type', 1);

	echo '<span id="product_ref_area" class="hideobject">&nbsp;<label for="product_ref">'.$langs->trans("Ref").'</label>';
	echo '<input id="product_ref" name="product_ref" size="20" value="'.GETPOST('product_ref').'"></span>';

	echo '&nbsp;<label for="product_label">'.$langs->trans("Label").'</label>';
	echo '<input id="product_label" name="product_label" size="40" value="'.GETPOST('product_label').'">';
	echo '<input type="hidden" id="origin_label_cache" name="origin_label_cache" value="" />';

	if (is_object($hookmanager))
	{
		$parameters=array('fk_parent_line'=>GETPOST('fk_parent_line'));
		$reshook=$hookmanager->executeHooks('formCreateProductOptions',$parameters,$object,$action);
	}
?>
</td>

<tr <?php echo $bcnd[$var]; ?>>
	<td<?php echo (! empty($conf->global->MAIN_VIEW_LINE_NUMBER) ? ' colspan="2"' : ''); ?>>

<?php
	// Editor wysiwyg
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $nbrows=ROWS_2;
    if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows=$conf->global->MAIN_INPUT_DESC_HEIGHT;
	$doleditor=new DolEditor('product_desc', GETPOST('product_desc'), '', 150, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_DETAILS, $nbrows, 70);
	$doleditor->Create();
	?>
	<input type="hidden" id="origin_desc_cache" name="origin_desc_cache" value="" />
	<input type="hidden" id="free_desc_cache" name="free_desc_cache" value="" />
	</td>

	<td align="right">
	<?php
	if ($buyer->tva_assuj == "0") echo '<input type="hidden" name="np_tva_tx" value="0">0';
	else echo $form->load_tva('tva_tx', (GETPOST('tva_tx')?GETPOST('tva_tx'):-1), $seller, $buyer);
	?>
	<input type="hidden" id="price_base_type" name="price_base_type" value="" />
	</td>
	<td align="right">
		<input type="text" size="8" id="price_ht" name="price_ht" value="<?php echo (GETPOST('price_ht')?GETPOST('price_ht'):''); ?>">
		<input type="hidden" id="origin_price_ht_cache" name="origin_price_ht_cache" value="" />
	</td>
	<td align="right">
		<input type="text" size="8" id="price_ttc" name="price_ttc" value="<?php echo (GETPOST('price_ttc')?GETPOST('price_ttc'):''); ?>">
		<input type="hidden" id="origin_price_ttc_cache" name="origin_price_ttc_cache" value="" />
	</td>
	<td align="right"><input type="text" size="3" id="qty" name="qty" value="<?php echo (GETPOST('qty')?GETPOST('qty'):1); ?>"></td>
	<td align="right" nowrap="nowrap"><input type="text" size="1" value="<?php echo $buyer->remise_client; ?>" name="remise_percent">%</td>
<?php
$colspan = 4;
if (! empty($conf->margin->enabled)) {
?>
	<td align="right">
		<select id="fournprice" name="fournprice" style="display: none;"></select>
		<input type="text" size="5" id="buying_price" name="buying_price" value="<?php echo (GETPOST('buying_price')?GETPOST('buying_price'):''); ?>">
	</td>
<?php
  if($conf->global->DISPLAY_MARGIN_RATES)
  	$colspan++;
  if($conf->global->DISPLAY_MARK_RATES)
  	$colspan++;
}
?>
	<td align="center" valign="middle" colspan="<?php echo $colspan; ?>"><input type="submit" class="button"  id="addlinebutton" name="addline" value="<?php echo $langs->trans('Add'); ?>"></td>
</tr>


<?php if (! empty($conf->service->enabled) && ! empty($dateSelector)) {
if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER))
	$colspan = 12;
else
	$colspan = 11;
if (! empty($conf->margin->enabled)) {
	if ($conf->global->DISPLAY_MARGIN_RATES)
		$colspan++;
	if($conf->global->DISPLAY_MARK_RATES)
		$colspan++;
}
?>
<tr id="service_duration_area" <?php echo $bcnd[$var]; ?>>
	<td colspan="<?php echo $colspan; ?>">
	<?php
	echo $langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' ';
	echo $form->select_date('','date_start',$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,1,"addproduct");
	echo ' '.$langs->trans('to').' ';
	echo $form->select_date('','date_end',$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,1,"addproduct");
	?>
	</td>
</tr>
<?php } ?>

</form>
<script type="text/javascript">
$(document).ready(function() {

	$('#service_duration_area').hide();

	$('#idprod').change(function() {
		if ($(this).val().length > 0) {
			if (typeof CKEDITOR == 'object' && typeof CKEDITOR.instances != 'undefined') {
				// We use CKEditor
				CKEDITOR.instances['product_desc'].focus();
			} else {
				// We use a simple textarea
				$('#product_desc').focus();
			}
	    } else {
	    	$('#update_desc_checkbox').removeAttr('checked').trigger('change');
	    	$('#update_price_checkbox').removeAttr('checked').trigger('change');
	    }
	});

	$('#addlinebutton').attr('disabled','disabled');
	$('#select_type').change(function() {
		var type = $(this).val();
		if (type >= 0) {
			if (type == 0) {
				$('#add_product_text').show();
				$('#add_service_text').hide();
				$('#service_duration_area').hide();
				$('#date_start').val('').trigger('change');
				$('#date_end').val('').trigger('change');
			} else if (type == 1) {
				$('#add_product_text').hide();
				$('#add_service_text').show();
				$('#service_duration_area').show();
			}
			//$('#add_product_area').show(); // TODO for add product card
			if (($('#price_ht').val().length > 0) || ($('#price_ttc').val().length > 0)) {
				$('#addlinebutton').removeAttr('disabled');
			} else {
				$('#addlinebutton').attr('disabled','disabled');
			}
		} else {
			//$('#add_product_area').hide(); // TODO for add product card
			$('#add_product_checkbox').removeAttr('checked').trigger('change');
			$('#addlinebutton').attr('disabled','disabled');
			$('#service_duration_area').hide();
			$('#date_start').val('').trigger('change');
			$('#date_end').val('').trigger('change');
		}
	});

	$('#add_product_checkbox').change(function() {
		if ($(this).attr('checked')) {
			$('#product_ref_area').show();
			$('#product_ref').focus();
			$('#product_label').removeAttr('disabled');
			$('#search_idprod').attr('disabled','disabled');
			$('#update_label_area').hide();
			$('#update_label_checkbox').removeAttr('checked');
			$('#update_price_area').hide().trigger('hide');
			$('#update_price_checkbox').removeAttr('checked')
		} else {
			if ($("#idprod").val() > 0) {
				$('#update_label_area').show();
				$('#product_label').attr('disabled', 'disabled');
				$('#update_price_area').show().trigger('show');
			}
			$('#product_ref_area').hide();
			$('#search_idprod').removeAttr('disabled');
		}
	});

	$('#update_price_area').bind('hide', function() {
		$('#price_ht').removeAttr('disabled');
		$('#price_ttc').removeAttr('disabled');
	});

	$('#update_price_area').bind('show', function() {
		$('#price_ht').attr('disabled', 'disabled');
		$('#price_ttc').attr('disabled', 'disabled');
	});

	$('#update_price_checkbox').change(function() {
		if ($(this).attr('checked')) {
			$('#price_ht').removeAttr('disabled').focus();
			if ($('#tva_tx').val() > 0) {
				$('#price_ttc').removeAttr('disabled')
			}
		} else {
			$('#price_ht')
				.attr('disabled','disabled')
				.val($('#origin_price_ht_cache').val())
				.trigger('change');
			$('#price_ttc')
				.attr('disabled','disabled');
				//.val($('#origin_price_ttc_cache').val())
				//.trigger('change');
		}
	});

	$('#update_label_area').bind('hide', function() {
		$('#update_label_checkbox').removeAttr('checked');
		$('#product_label').removeAttr('disabled');
	});

	$('#update_label_area').bind('show', function() {
		$('#product_label').attr('disabled', 'disabled');
	});

	$('#update_label_checkbox').change(function() {
		if ($(this).attr('checked')) {
			$('#product_label').removeAttr('disabled').focus();
		} else {
			$('#product_label')
				.attr('disabled','disabled')
				.val($('#origin_label_cache').val());
			$('#search_idprod').focus();
		}
	});

	$('#update_desc_checkbox').change(function() {

		if ($(this).attr('checked')) {

			var origin_desc = $('#origin_desc_cache').val();

			if (typeof CKEDITOR == 'object' && typeof CKEDITOR.instances != 'undefined') {
				// We use CKEditor
				var freecontent = CKEDITOR.instances['product_desc'].getData();
				if (origin_desc.length > 0)
					var content = origin_desc + '<br />' + freecontent;
				else
					var content = freecontent;
			} else {
				// We use a simple textarea
				var freecontent = $('#product_desc').html();
				if (origin_desc.length > 0)
					var content = origin_desc + '\r\n' + freecontent;
				else
					var content = freecontent;
			}

			$('#free_desc_cache').val(freecontent);

		} else {
			var content = $('#free_desc_cache').val();
		}

		if (typeof CKEDITOR == 'object' && typeof CKEDITOR.instances != 'undefined') {
			// We use CKEditor
			CKEDITOR.instances['product_desc'].setData(content);
		} else {
			// We use a simple textarea
			$('#product_desc').html(content);
		}
	});

	$('#price_ht').focusin(function() {
		$('#price_base_type').val('HT');
	});

	$('#price_ht').bind('change keyup input', function() {
		if ($('#tva_tx').val() > 0 && ($('#idprod').val().length == 0 && $('#price_base_type').val() == 'HT') || $('#idprod').val().length > 0) {
			update_price('price_ht', 'price_ttc');
		}
	});

	$('#price_ttc').focusin(function() {
		$('#price_base_type').val('TTC');
	});

	$('#price_ttc').bind('change keyup input', function() {
		if ($('#price_base_type').val() == 'TTC') {
			update_price('price_ttc', 'price_ht');
		}
	});

	if ($('#tva_tx').val() == 0) {
		$('#price_ttc').attr('disabled','disabled');
	}

	$('#tva_tx').change(function() {
		if ($(this).val() == 0) {
			$('#price_ttc').attr('disabled','disabled');
			$('#price_ttc').val('');
		} else {
			if ($('#idprod').val().length == 0 || ($('#idprod').val().length > 0 && $('#update_price_checkbox').attr('checked') == 'checked')) {
				$('#price_ttc').removeAttr('disabled');
			}
			if ($('#price_base_type').val() == 'HT') {
				update_price('price_ht', 'price_ttc');
			} else if ($('#price_base_type').val() == 'TTC') {
				update_price('price_ttc', 'price_ht');
			}
		}
	});

	function update_price(input, output) {
		$.post('<?php echo DOL_URL_ROOT; ?>/core/ajax/price.php', {
			'amount': $('#' + input).val(),
			'output': output,
			'tva_tx': $('#tva_tx').val()
		},
		function(data) {
			if (typeof data[output] != 'undefined') {
				$('#' + output).val(data[output]);
				if ($('#select_type').val() >= 0) {
					$('#addlinebutton').removeAttr('disabled');
				} else {
					$('#addlinebutton').attr('disabled','disabled');
				}
			} else {
				$('#' + input).val('');
				$('#' + output).val('');
				$('#addlinebutton').attr('disabled','disabled');
			}
		}, 'json');
	}

});
</script>

<?php if (! empty($conf->margin->enabled)) { ?>
<script type="text/javascript">
$("#idprod").change(function() {
	$("#fournprice").empty();
	$("#buying_price").show();
	if ($(this).val() > 0)
    {
		$.post('<?php echo DOL_URL_ROOT; ?>/fourn/ajax/getSupplierPrices.php', {'idprod': $(this).val()}, function(data) {
			if (data && data.length > 0) {
				var options = '';
				var i = 0;
				$(data).each(function() {
					i++;
					options += '<option value="'+this.id+'" title="'+this.title+'" price="'+this.price+'"';
					if (i == 1) {
						options += ' selected';
						$("#buying_price").val(this.price);
					}
					options += '>'+this.label+'</option>';
				});
				options += '<option value=null><?php echo $langs->trans("InputPrice"); ?></option>';
				$("#fournprice").html(options);
				$("#buying_price").hide();
				$("#fournprice").show();
				$("#fournprice").change(function() {
					var selval = $(this).find('option:selected').attr("price");
					if (selval)
						$("#buying_price").val(selval).hide();
					else
						$('#buying_price').show();
				});
			} else {
				$("#fournprice").hide();
				$('#buying_price').val('');
			}
		},
		'json');
    } else {
    	$("#fournprice").hide();
		$('#buying_price').val('');
    }
});
</script>
<?php } ?>
<!-- END PHP TEMPLATE freeproductline_create.tpl.php -->
