<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
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
?>

<!-- BEGIN PHP TEMPLATE objectline_add.tpl.php -->
<tr class="liste_titre nodrag nodrop">
	<td<?php echo (! empty($conf->global->MAIN_VIEW_LINE_NUMBER) ? ' colspan="2"' : ''); ?>><div id="add"></div><?php echo $langs->trans('AddNewLine'); ?></td>
	<td align="right" width="50"><?php echo $langs->trans('VAT'); ?></td>
	<td align="right" width="80"><?php echo $langs->trans('PriceUHT'); ?></td>
	<td align="right" width="80"><?php echo $langs->trans('PriceUTTC'); ?></td>
	<td align="right" width="50"><?php echo $langs->trans('Qty'); ?></td>
	<td align="right" width="50"><?php echo $langs->trans('ReductionShort'); ?></td>
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
<input type="hidden" name="usenewaddlineform" value="1" />
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
							'price_base_type' => 'pricebasetype',
							'price_ht' => 'price_ht',
							'origin_price_ht_cache' => 'price_ht',
							'origin_tva_tx_cache' => 'tva_tx',
							'origin_price_ttc_cache' => 'price_ttc',
							'qty' => 'qty',
							'remise_percent' => 'discount'
					),
					'update_textarea' => array(
							'product_desc' => 'desc'
					),
					//'show' => array(
							//'price_base_type_area'
					//),
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
	</td>
</tr>
<?php } ?>

<tr <?php echo $bcnd[$var]; ?>>
	<td colspan="<?php echo $colspan2; ?>">
	<?php echo $form->select_type_of_lines((GETPOST('type')?GETPOST('type'):-1), 'type', 1); ?>

	<span id="product_ref_area" class="hideobject">
		&nbsp;<label for="product_ref"><?php echo $langs->trans("Ref"); ?></label>
		<input id="product_ref" name="product_ref" size="20" value="<?php echo GETPOST('product_ref'); ?>">
	</span>
	<span id="product_label_area">
		&nbsp;<label for="product_label"><?php echo $langs->trans("Label"); ?></label>
		<input id="product_label" name="product_label" size="40" value="<?php echo GETPOST('product_label'); ?>">
	</span>
	<span id="price_base_type_area" class="hideobject">
		<input type="hidden" id="price_base_type" name="price_base_type" value="" /> | <?php echo $langs->trans('PriceBase'); ?>:
		<span id="view_price_base_type"></span>
	</span>
<?php
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
    $enable=(isset($conf->global->FCKEDITOR_ENABLE_DETAILS)?$conf->global->FCKEDITOR_ENABLE_DETAILS:0);
	$doleditor=new DolEditor('product_desc', GETPOST('product_desc'), '', 150, 'dolibarr_details', '', false, true, $enable, $nbrows, 70);
	$doleditor->Create();
	?>
	</td>

	<td align="right">
		<?php echo $form->load_tva('tva_tx', (GETPOST('tva_tx')?GETPOST('tva_tx'):-1), $seller, $buyer); ?>
		<input type="hidden" id="origin_tva_tx_cache" name="origin_tva_tx_cache" value="" />
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
	<td align="right" class="nowrap">
		<input type="text" size="1" value="<?php echo $buyer->remise_client; ?>" id="remise_percent" name="remise_percent">%
		<input type="hidden" id="origin_remise_percent" name="origin_remise_percent" value="<?php echo $buyer->remise_client; ?>" />
	</td>
<?php
$colspan = 4;
if (! empty($conf->margin->enabled)) {
?>
	<td align="right">
		<select id="fournprice" name="fournprice" style="display: none;"></select>
		<input type="text" size="5" id="buying_price" name="buying_price" value="<?php echo (GETPOST('buying_price')?GETPOST('buying_price'):''); ?>">
	</td>
<?php
  if (! empty($conf->global->DISPLAY_MARGIN_RATES))
  	$colspan++;
  if (! empty($conf->global->DISPLAY_MARK_RATES))
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
	if (! empty($conf->global->DISPLAY_MARGIN_RATES))
		$colspan++;
	if (! empty($conf->global->DISPLAY_MARK_RATES))
		$colspan++;
}
?>
<tr id="service_duration_area" <?php echo $bcnd[$var]; ?>>
	<td colspan="<?php echo $colspan; ?>">
	<?php
	$hourmin=(isset($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE:'');
	echo $langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' ';
	echo $form->select_date('','date_start',$hourmin,$hourmin,1,"addproduct");
	echo ' '.$langs->trans('to').' ';
	echo $form->select_date('','date_end',$hourmin,$hourmin,1,"addproduct");
	?>
	</td>
</tr>
<?php } ?>

</form>
<script type="text/javascript">
$(document).ready(function() {

	// Add line button disabled by default
	$('#addlinebutton').attr('disabled','disabled');

	// Service duration hide by default
	$('#service_duration_area').hide();

	$('#idprod').change(function() {

		if ($(this).val() > 0) {

			// Update vat rate combobox
			getVATRates('getSellerVATRates', 'tva_tx', $(this).val());

			// For compatibility with combobox
			<?php if (empty($conf->global->PRODUIT_USE_SEARCH_TO_SELECT)) { ?>
			$.post('<?php echo DOL_URL_ROOT; ?>/product/ajax/products.php', {
				'action': 'fetch',
				'id': $(this).val(),
				'price_level': <?php echo empty($buyer->price_level)?1:$buyer->price_level; ?>,
				'pbq': $("option:selected", this).attr('pbq')
				},
			function(data) {
				if (typeof data != 'undefined') {
					$('#product_ref').val(data.ref);
					$('#product_label').val(data.label);
					$('#price_base_type').val(data.pricebasetype).trigger('change');
					$('#price_ht').val(data.price_ht).trigger('change');
					$('#origin_price_ht_cache').val(data.price_ht);
					//$('#origin_price_ttc_cache').val(data.price_ttc);
					$('#origin_tva_tx_cache').val(data.tva_tx);
					$('#select_type').val(data.type).attr('disabled','disabled').trigger('change');
					//$('#price_base_type_area').show();
					$('#qty').val(data.qty);
					if($('#remise_percent').val() < data.discount) $('#remise_percent').val(data.discount);

					if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined" && CKEDITOR.instances['product_desc'] != "undefined") {
						CKEDITOR.instances['product_desc'].setData(data.desc).focus();
					} else {
						$("#product_desc").html(data.desc).focus();
					}
				}
			}, 'json');
			<?php } ?>

	    } else {

	    	$('#price_ttc').val('');

	    	// Restore vat rate combobox
	    	getVATRates('getSellerVATRates', 'tva_tx');

	    	// For compatibility with combobox
			<?php if (empty($conf->global->PRODUIT_USE_SEARCH_TO_SELECT)) { ?>
			$('#select_type').val('').removeAttr('disabled').trigger('change');
			$('#product_ref').val('');
			$('#product_label').val('');
			$('#price_ht').val('').trigger('change');
			$('#origin_price_ht_cache').val('');
			//$('#origin_price_ttc_cache').val('');
			$('#origin_tva_tx_cache').val('');
			$('#price_base_type').val('');
			$('#price_base_type_area').hide();

			if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined" && CKEDITOR.instances['product_desc'] != "undefined") {
				CKEDITOR.instances['product_desc'].setData('');
			} else {
				$("#product_desc").html('');
			}
			<?php } ?>
	    }
	});

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
			var addline=false;
			if ($('#price_ht').val().length > 0) {
				if ($('#idprod').val() == 0) {
					if (typeof CKEDITOR == 'object' && typeof CKEDITOR.instances != 'undefined' && CKEDITOR.instances['product_desc'] != 'undefined') {
						var content = CKEDITOR.instances['product_desc'].getData();
					} else {
						var content = $('#product_desc').val();
					}
					if (content.length > 0) {
						addline=true;
					}
				} else {
					addline=true;
				}
			}
			if (addline) {
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

	// TODO for add product card
	$('#add_product_checkbox').change(function() {
		if ($(this).attr('checked')) {
			$('#product_ref_area').show();
			$('#product_ref').focus();
			$('#product_label').removeAttr('disabled');
			$('#search_idprod').attr('disabled','disabled');
			$('#update_label_area').hide();
			$('#update_label_checkbox').removeAttr('checked');
		} else {
			if ($("#idprod").val() > 0) {
				$('#update_label_area').show();
				$('#product_label').attr('disabled', 'disabled');
			}
			$('#product_ref_area').hide();
			$('#search_idprod').removeAttr('disabled');
		}
	});

	$('#price_ht').focusin(function() {
		$('#price_base_type').val('HT').trigger('change');
	});

	$('#price_ttc').focusin(function() {
		$('#price_base_type').val('TTC').trigger('change');
	});

	$('#price_base_type').change(function() {
		$('#view_price_base_type').html($(this).val());
	});

	$('#price_ht').bind('change keyup input', function() {
		if (($('#idprod').val() == 0 && $('#price_base_type').val() == 'HT') || $('#idprod').val() > 0) {
			update_price('price_ht', 'price_ttc');
		}
	});

	$('#price_ttc').bind('change keyup input', function() {
		if ($('#price_base_type').val() == 'TTC') {
			update_price('price_ttc', 'price_ht');
		}
	});

	if ($('#idprod').val() == 0 && $('#tva_tx').val() == 0) {
		$('#price_ttc').attr('disabled','disabled');
	}

	$('#remise_percent').bind('change', function() {
		if ($(this).val() < $('#origin_remise_percent').val())
			$('#remise_percent').val($('#origin_remise_percent').val());
	});

	$('#tva_tx').change(function() {
		if ($(this).val() == 0) {
			if ($('#idprod').val() == 0) {
				$('#price_ttc').attr('disabled','disabled');
			}
			$('#price_ttc').val('');
		} else {
			// Enable excl.VAT field
			$('#price_ttc').removeAttr('disabled');
			// Update prices fields
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
			var addline=false;
			if (typeof data[output] != 'undefined') {
				// Hide price_ttc if no vat
				//if ($('#tva_tx').val() > 0 || ($('#tva_tx').val() == 0 && output == 'price_ht')) {
					$('#' + output).val(data[output]);
				//}
				if ($('#idprod').val() == 0 && $('#select_type').val() >= 0) {
					if (typeof CKEDITOR == 'object' && typeof CKEDITOR.instances != 'undefined' && CKEDITOR.instances['product_desc'] != 'undefined') {
						var content = CKEDITOR.instances['product_desc'].getData();
					} else {
						var content = $('#product_desc').val();
					}
					if (content.length > 0) {
						addline=true;
					}
				} else {
					addline=true;
				}
			} else {
				$('#' + input).val('');
				$('#' + output).val('');
			}
			if (addline) {
				$('#addlinebutton').removeAttr('disabled');
			} else {
				$('#addlinebutton').attr('disabled','disabled');
			}
		}, 'json');
	}

	function getVATRates(action, htmlname, idprod) {
		var productid = (idprod?idprod:0);
		$.post('<?php echo DOL_URL_ROOT; ?>/core/ajax/vatrates.php', {
			'action': action,
			'id': <?php echo $buyer->id; ?>,
			'productid': productid,
			'htmlname': htmlname },
		function(data) {
			if (typeof data != 'undefined' && data.error == null) {
				$("#" + htmlname).html(data.value).trigger('change');
			}
		}, 'json');
	}

	// Check if decription is not empty for free line
	<?php if (! empty($conf->fckeditor->enabled) && ! empty($conf->global->FCKEDITOR_ENABLE_DETAILS)) { ?>
	CKEDITOR.on('instanceReady', function() {
		CKEDITOR.instances['product_desc'].on('key', function() {
			var addline=false;
			if ($('#idprod').val() == 0 && $('#select_type').val() >= 0 && $('#price_ht').val().length > 0) {
				var content = CKEDITOR.instances['product_desc'].getData();
				if (content.length > 0) {
					addline=true;
				}
			} else if ($('#idprod').val() > 0 && $('#price_ht').val().length > 0) {
				addline=true;
			}
			if (addline) {
				$('#addlinebutton').removeAttr('disabled');
			} else {
				$('#addlinebutton').attr('disabled','disabled');
			}
		});
	});
	<?php } else { ?>
	$('#product_desc').onDelayedKeyup({
		'handler': function() {
			var addline=false;
			if ($('#idprod').val() == 0 && $('#select_type').val() >= 0 && $('#price_ht').val().length > 0) {
				var content = $('#product_desc').val();
				if (content.length > 0) {
					addline=true;
				}
			} else if ($('#idprod').val() > 0 && $('#price_ht').val().length > 0) {
				addline=true;
			}
			if (addline) {
				$('#addlinebutton').removeAttr('disabled');
			} else {
				$('#addlinebutton').attr('disabled','disabled');
			}
		}
	});
	<?php } ?>

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
<!-- END PHP TEMPLATE objectline_add.tpl.php -->
