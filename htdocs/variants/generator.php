<?php

/* Copyright (C) 2016	Marcos García	<marcosgdf@gmail.com>
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
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductAttribute.class.php';
require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductAttributeValue.class.php';
require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination.class.php';
require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination2ValuePair.class.php';

$langs->load("products");
$langs->load('other');

$id = GETPOST('id', 'int');
$ref = GETPOST('ref');
$form = new Form($db);

// Security check
$fieldvalue = (! empty($id) ? $id : $ref);
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
$result=restrictedArea($user,'produit|service',$fieldvalue,'product&product','','',$fieldtype);

$prodattr = new ProductAttribute($db);
$prodattrval = new ProductAttributeValue($db);
$product = new Product($db);

$product->fetch($id);

if (!$product->isProduct()) {
	header('Location: '.dol_buildpath('/product/card.php?id='.$product->id, 2));
	exit();
}

/**
 * Posible combinations. Format.
 * attrval => array(
 * 		valueid => array(
 * 			price => ''
 * 			weight => ''
 * 		)
 * )
 */
$combinations = GETPOST('combinations', 'array');
$price_var_percent = (bool) GETPOST('price_var_percent');
$donotremove = true;

if ($_POST) {

	$donotremove = (bool) GETPOST('donotremove');

	//We must check if all those given combinations actually exist
	$sanitized_values = array();

	foreach ($combinations as $attr => $val) {
		if ($prodattr->fetch($attr) > 0) {
			foreach ($val as $valueid => $content) {
				if ($prodattrval->fetch($valueid) > 0) {
					$sanitized_values[$attr][$valueid] = $content;
				}
			}
		}
	}

	if ($sanitized_values) {

		require DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		$adapted_values = array();

		//Adapt the array to the cartesian function
		foreach ($sanitized_values as $attr => $val) {
			$adapted_values[$attr] = array_keys($val);
		}

		$db->begin();

		$combination = new ProductCombination($db);

		$delete_prev_comb_res = 1;

		if (!$donotremove) {
			$delete_prev_comb_res = $combination->deleteByFkProductParent($user, $id);
		}

		//Current combinations will be deleted
		if ($delete_prev_comb_res > 0) {

			$res = 1;

			foreach (cartesianArray($adapted_values) as $currcomb) 
			{
				$res = $combination->createProductCombination($product, $currcomb, $sanitized_values, $price_var_percent);
				if ($res < 0) {
				    $error++;
				    setEventMessages($combination->error, $combination->errors, 'errors');
				    break;
				}
			}

			if ($res > 0) {
				$db->commit();
				setEventMessage($langs->trans('RecordSaved'));
				header('Location: '.dol_buildpath('/variants/combinations.php?id='.$id, 2));
				exit;
			}
		} else {
			setEventMessage($langs->trans('ErrorDeletingGeneratedProducts'), 'errors');
		}

		$db->rollback();

	} else {
		setEventMessage($langs->trans('ErrorFieldsRequired'), 'errors');
	}
}



/*
 *	View
 */

if (! empty($id) || ! empty($ref)) {
	$object = new Product($db);
	$result = $object->fetch($id, $ref);

	llxHeader("", "", $langs->trans("CardProduct".$object->type));

	if ($result > 0)
	{
		$showbarcode=empty($conf->barcode->enabled)?0:1;
		if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->lire_advance)) $showbarcode=0;
		 
		$head=product_prepare_head($object);
		$titre=$langs->trans("CardProduct".$object->type);
		$picto=($object->type== Product::TYPE_SERVICE?'service':'product');
		dol_fiche_head($head, 'combinations', $titre, 0, $picto);
		 
		$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?type='.$object->type.'">'.$langs->trans("BackToList").'</a>';
		$object->next_prev_filter=" fk_product_type = ".$object->type;
		 
		dol_banner_tab($object, 'ref', $linkback, ($user->societe_id?0:1), 'ref', '', '', '', 0, '', '', 1);
		
		dol_fiche_end();
	}

	print_fiche_titre($langs->trans('ProductCombinationGenerator'));

	$dictionary_attr = array();

	foreach ($prodattr->fetchAll() as $attr) {
		$dictionary_attr[$attr->id] = $attr;
		foreach ($prodattrval->fetchAllByProductAttribute($attr->id) as $attrval) {
			$dictionary_attr[$attr->id]->values[$attrval->id] = $attrval;
		}
	}
	?>

	<script>

		dictionary_attr = <?php echo json_encode($dictionary_attr) ?>;
		weight_units = '<?php echo measuring_units_string($object->weight_units, 'weight') ?>';
		attr_selected = {};
		percentage_variation = jQuery('input#price_var_percent').prop('checked');

		function parseSelectedFeatures(attr, val, inputs) {

			var price = '';
			var weight = '';

			if (typeof(inputs) == 'object') {
				price = inputs.price;
				weight = inputs.weight;
			}

			if (!attr_selected.hasOwnProperty(attr)) {

				var label = dictionary_attr[attr].label;

				var table = jQuery(document.createElement('table'))
					.attr('id', 'combinations_'+attr)
					.css('width', '100%')
					.addClass('liste')
					.append(
						jQuery(document.createElement('thead'))
							.append(jQuery(document.createElement('tr'))
								.addClass('liste_titre')
								.append(
									jQuery(document.createElement('th'))
										.addClass('liste_titre')
										.css('width', '40%')
										.text(label),
									jQuery(document.createElement('th')).addClass('liste_titre').css('text-align', 'center').html('<?php echo $langs->trans('PriceImpact') ?>'),
									jQuery(document.createElement('th')).addClass('liste_titre').css('text-align', 'center').html('<?php echo $langs->trans('WeightImpact') ?>')
								)
							)
					)
				;


				jQuery('form#combinationsform').prepend(table);

				attr_selected[attr] = [];
			} else {
				if (jQuery.inArray(val, attr_selected[attr]) != -1) {
					return;
				}
			}

			var combinations_table = jQuery('table#combinations_' + attr);
			var html = jQuery(document.createElement('tr'));

			if (combinations_table.children('tbody').children('tr').length % 2 === 0) {
				html.addClass('pair');
			} else {
				html.addClass('impair');
			}

			var percent_symbol_html = jQuery(document.createElement('span')).attr('id', 'percentsymbol').html(' %');

			if (!percentage_variation) {
				percent_symbol_html.hide();
			}

			html.append(
				jQuery(document.createElement('td')).text(dictionary_attr[attr].values[val].value),
				jQuery(document.createElement('td')).css('text-align', 'center').append(
					jQuery(document.createElement('input')).attr('type', 'text').css('width', '50px').attr('name', 'combinations[' + attr + '][' + val + '][price]').val(price),
					percent_symbol_html
				),
				jQuery(document.createElement('td')).css('text-align', 'center').append(
					jQuery(document.createElement('input')).attr('type', 'text').css('width', '50px').attr('name', 'combinations[' + attr + '][' + val + '][weight]').val(weight),
					' ' + weight_units
				)
			);

			combinations_table.append(html);

			attr_selected[attr].push(val);
		}

		function showHidePercentageSymbol(checked) {

			percentage_variation = checked;

			if (checked) {
				jQuery('span#percentsymbol').show();
			} else {
				jQuery('span#percentsymbol').hide();
			}
		}

		jQuery(document).ready(function() {

			var input_price_var_percent = jQuery('input#price_var_percent');

			jQuery.each(<?php echo json_encode($combinations) ?>, function(key, val) {
				jQuery.each(val, function(valkey, valcontent) {
					parseSelectedFeatures(key, valkey, valcontent);
				});
			});

			jQuery('#addfeature').click(function() {
				jQuery('#features option:selected').each(function(selector) {
					var explode = jQuery(this).val().split(':');
					parseSelectedFeatures(explode[0], explode[1]);
				});
			});

			jQuery('#delfeature').click(function() {
				jQuery('#features option:selected').each(function(selector) {
					var explode = jQuery(this).val().split(':');

					if (attr_selected.hasOwnProperty(explode[0])) {
						var tr = jQuery('input[name="combinations[' + explode[0] + '][' + explode[1] + '][price]"').parent().parent();

						var index_value = jQuery.inArray(explode[1], attr_selected[explode[0]]);

						attr_selected[explode[0]].splice(index_value, 1);

						if (tr.parent().children('tr').length === 1) {
							tr.parent().parent().detach();
							delete attr_selected[explode[0]]
						} else {
							tr.detach();
						}
					}
				});
			});

			input_price_var_percent.click(function() {
				showHidePercentageSymbol(jQuery(this).prop('checked'));
			});

			jQuery('input#donotremove').click(function() {
				if (jQuery(this).prop('checked')) {
					jQuery('div#info_donotremove').hide();
				} else {
					jQuery('div#info_donotremove').show();
				}
			});
		});

	</script>

	<div style="width: 100%;display:block; height: 300px">

		<div style="float:right; width: 79%; margin-left: 1%">

			<form method="post" id="combinationsform">

					<p><?php echo $langs->trans('TooMuchCombinationsWarning', $langs->trans('DoNotRemovePreviousCombinations')) ?></p>
					<input type="checkbox" name="price_var_percent"
					       id="price_var_percent"<?php echo $price_var_percent ? ' checked' : '' ?>> <label
						for="price_var_percent"><?php echo $langs->trans('UsePercentageVariations') ?></label>
					<br>
					<input type="checkbox" name="donotremove"
					       id="donotremove"<?php echo $donotremove ? ' checked' : '' ?>> <label for="donotremove"><?php echo $langs->trans('DoNotRemovePreviousCombinations') ?></label>

					<br>
					<div id="info_donotremove" class="info" style="<?php echo $donotremove ? 'display: none' : '' ?>">
						<?php echo img_warning() ?>
						<?php echo $langs->trans('ProductCombinationGeneratorWarning') ?>
					</div>
					<br>

				<div style="text-align: center">
					<input type="submit" value="<?php echo $langs->trans('Generate') ?>" class="button" name="submit">
				</div>

			</form>

		</div>
		<div style="float:left; width: 20%">

			<select id="features" multiple style="width: 100%; height: 300px; overflow: auto">
				<?php foreach ($dictionary_attr as $attr): ?>
				<optgroup label="<?php echo $attr->label ?>">
					<?php foreach ($attr->values as $attrval): ?>
						<option value="<?php echo $attr->id.':'.$attrval->id ?>"<?php
						if (isset($combinations[$attr->id][$attrval->id])) {
							echo ' selected';
						}
						?>><?php echo dol_htmlentities($attrval->value) ?></option>
					<?php endforeach ?>
				</optgroup>
				<?php endforeach ?>
			</select>

			<br><br>
			<a class="button" id="delfeature" style="float: right"><?php echo img_edit_remove() ?></a>
			<a class="button" id="addfeature"><?php echo img_edit_add() ?></a>

		</div>

	</div>


	<?php

	llxFooter();
}