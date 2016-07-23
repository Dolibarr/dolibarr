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
require_once DOL_DOCUMENT_ROOT.'/attributes/class/ProductAttribute.class.php';
require_once DOL_DOCUMENT_ROOT.'/attributes/class/ProductAttributeValue.class.php';
require_once DOL_DOCUMENT_ROOT.'/attributes/class/ProductCombination.class.php';
require_once DOL_DOCUMENT_ROOT.'/attributes/class/ProductCombination2ValuePair.class.php';

$langs->load("products");
$langs->load("other");

$var = false;
$id = GETPOST('id', 'int');
$valueid = GETPOST('valueid', 'int');
$ref = GETPOST('ref');
$weight_impact = (float) GETPOST('weight_impact');
$price_impact = (float) GETPOST('price_impact');
$price_impact_percent = (bool) GETPOST('price_impact_percent');
$form = new Form($db);
$action = GETPOST('action');

// Security check
$fieldvalue = (! empty($id) ? $id : $ref);
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
$result=restrictedArea($user,'produit|service',$fieldvalue,'product&product','','',$fieldtype);

$prodstatic = new Product($db);
$prodattr = new ProductAttribute($db);
$prodattr_val = new ProductAttributeValue($db);

$product = new Product($db);

$product->fetch($id);

if (!$product->isProduct()) {
	header('Location: '.dol_buildpath('/product/card.php?id='.$product->id, 2));
	die;
}

$prodcomb = new ProductCombination($db);
$prodcomb2val = new ProductCombination2ValuePair($db);

$productCombination2ValuePairs1 = array();

if ($_POST) {

	if ($action == 'add') {

		$features = GETPOST('features', 'array');

		if (!$features) {
			setEventMessage($langs->trans('ErrorFieldsRequired'), 'errors');
		} else {
			$weight_impact = price2num($weight_impact);
			$price_impact = price2num($price_impact);
			$sanit_features = array();

			//First, sanitize
			foreach ($features as $feature) {

				$explode = explode(':', $feature);

				if ($prodattr->fetch($explode[0]) < 0) {
					continue;
				}

				if ($prodattr_val->fetch($explode[1]) < 0) {
					continue;
				}

				//Valuepair
				$sanit_features[$explode[0]] = $explode[1];

				$tmp = new ProductCombination2ValuePair($db);
				$tmp->fk_prod_attr = $explode[0];
				$tmp->fk_prod_attr_val = $explode[1];

				$productCombination2ValuePairs1[] = $tmp;
			}

			$db->begin();

			if (!$prodcomb->fetchByProductCombination2ValuePairs($id, $sanit_features)) {
				if (ProductCombination::createProductCombination($product, $sanit_features, array(), $price_impact_percent, $price_impact, $weight_impact)) {
					$db->commit();
					setEventMessage($langs->trans('RecordSaved'));
					header('Location: '.dol_buildpath('/attributes/combinations.php?id='.$id, 2));
					die;
				} else {
					setEventMessage($langs->trans('CoreErrorMessage'), 'errors');
				}
			} else {
				setEventMessage($langs->trans('ErrorRecordAlreadyExists'), 'errors');
			}

			$db->rollback();
		}
	} elseif ($action == 'bulk_actions') {

		$prodarray = array_keys(GETPOST('select', 'array'));
		$bulkaction = GETPOST('bulk_action');
		$error = 0;

		$prodstatic = new Product($db);

		$db->begin();

		foreach ($prodarray as $prodid) {

			if ($prodstatic->fetch($prodid) < 0) {
				continue;
			}

			if ($bulkaction == 'on_sell') {
				$prodstatic->status = 1;
				$res = $prodstatic->update($prodstatic->id, $user);
			} elseif ($bulkaction == 'on_buy') {
				$prodstatic->status_buy = 1;
				$res = $prodstatic->update($prodstatic->id, $user);
			} elseif ($bulkaction == 'not_sell') {
				$prodstatic->status = 0;
				$res = $prodstatic->update($prodstatic->id, $user);
			} elseif ($bulkaction == 'not_buy') {
				$prodstatic->status_buy = 0;
				$res = $prodstatic->update($prodstatic->id, $user);
			} elseif ($bulkaction == 'delete') {
				$res = $prodstatic->delete($prodstatic->id);
			} else {
				break;
			}

			if ($res <= 0) {
				$error++;
				break;
			}
		}

		if ($error) {
			$db->rollback();

			if ($prodstatic->error) {
				setEventMessage($langs->trans($prodstatic->error), 'errors');
			} else {
				setEventMessage($langs->trans('CoreErrorMessage'), 'errors');
			}

		} else {
			$db->commit();
			setEventMessage($langs->trans('RecordSaved'));
		}

	} else {

		if ($prodcomb->fetch($valueid) < 0) {
			dol_print_error($db, $langs->trans('ErrorRecordNotFound'));
			die;
		}

		$prodcomb->variation_price_percentage = $price_impact_percent;
		$prodcomb->variation_price = $price_impact;
		$prodcomb->variation_weight = $weight_impact;

		if ($prodcomb->update() > 0) {
			setEventMessage($langs->trans('RecordSaved'));
			header('Location: '.dol_buildpath('/attributes/combinations.php?id='.$id, 2));
			die;
		} else {
			setEventMessage($langs->trans('CoreErrorMessage'), 'errors');
		}
	}
}

$productCombinations = $prodcomb->fetchAllByFkProductParent($id);

if ($action === 'confirm_deletecombination') {

	if ($prodcomb->fetch($valueid) > 0) {

		$db->begin();

		if ($prodcomb->delete() > 0 && $prodstatic->fetch($prodcomb->fk_product_child) > 0 && $prodstatic->delete() > 0) {
			$db->commit();
			setEventMessage($langs->trans('RecordSaved'));
			header('Location: '.dol_buildpath('/attributes/combinations.php?id='.$product->id, 2));
			die;
		}

		$db->rollback();
		setEventMessage($langs->trans('ProductCombinationAlreadyUsed'), 'errors');
		$action = '';
	}
} elseif ($action === 'edit') {

	if ($prodcomb->fetch($valueid) < 0) {
		dol_print_error($db, $langs->trans('ErrorRecordNotFound'));
		die;
	}

	$weight_impact = $prodcomb->variation_weight;
	$price_impact = $prodcomb->variation_price;
	$price_impact_percent = $prodcomb->variation_price_percentage;

	$productCombination2ValuePairs1 = $prodcomb2val->fetchByFkCombination($valueid);
} elseif ($action === 'confirm_copycombination') {

	//Check destination product
	$dest_product = GETPOST('dest_product');

	if ($prodstatic->fetch('', $dest_product) > 0) {

		//To prevent from copying to the same product
		if ($prodstatic->ref != $product->ref) {
			if ($prodcomb->copyAll($product->id, $prodstatic) > 0) {
				header('Location: '.dol_buildpath('/attributes/combinations.php?id='.$prodstatic->id, 2));
				die;
			} else {
				setEventMessage($langs->trans('ErrorCopyProductCombinations'), 'errors');
			}
		}

	} else {
		setEventMessage($langs->trans('ErrorDestinationProductNotFound'), 'errors');
	}

}

/*
 *	View
 */

if (! empty($id) || ! empty($ref)) {
	$object = new Product($db);
	$result = $object->fetch($id, $ref);

	llxHeader("", "", $langs->trans("CardProduct".$object->type));

	if ($result) {
		$head = product_prepare_head($object);
		$titre = $langs->trans("CardProduct".$object->type);
		$picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');

		dol_fiche_head($head, 'combinations', $titre, 0, $picto);

		print '<table class="border" width="100%">';

		// Reference
		print '<tr>';
		print '<td width="30%">'.$langs->trans("Ref").'</td><td colspan="3">';
		print $form->showrefnav($object, 'id', '', 0);
		print '</td>';
		print '</tr>';

		// Label
		print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$object->label.'</td></tr>';

		// Status (to sell)
		print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td>';
		print $object->getLibStatut(2, 0);
		print '</td></tr>';

		// Status (to buy)
		print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td>';
		print $object->getLibStatut(2, 1);
		print '</td></tr>';

		print '</table>';

		dol_fiche_end();
	}

	if ($action == 'add' || ($action == 'edit')) {

		if ($action == 'add') {
			$title = $langs->trans('NewProductCombination');
		} else {
			$title = $langs->trans('EditProductCombination');
		}

		print_fiche_titre($title);

		if ($action == 'add') {
			$prodattr_all = $prodattr->fetchAll();

			if (!$selected) {
				$selected = $prodattr_all[key($prodattr_all)]->id;
			}

			$prodattr_alljson = array();

			foreach ($prodattr_all as $each) {
				$prodattr_alljson[$each->id] = $each;
			}

		?>

		<script>

			attributes_available = <?php echo json_encode($prodattr_alljson) ?>;
			attributes_selected = {
				index: [],
				info: []
			};

			<?php foreach ($productCombination2ValuePairs1 as $pc2v):
			$prodattr_val->fetch($pc2v->fk_prod_attr_val);
			?>
			attributes_selected.index.push(<?php echo $pc2v->fk_prod_attr ?>);
			attributes_selected.info[<?php echo $pc2v->fk_prod_attr ?>] = {
				attribute: attributes_available[<?php echo $pc2v->fk_prod_attr ?>],
				value: {
					id: <?php echo $pc2v->fk_prod_attr_val ?>,
					label: '<?php echo $prodattr_val->value ?>'
				}
			};
			<?php endforeach ?>

			restoreAttributes = function() {
				jQuery("select[name=attribute]").empty().append('<option value=""></option>');

				jQuery.each(attributes_available, function (key, val) {
					if (jQuery.inArray(val.id, attributes_selected.index) == -1) {
						jQuery("select[name=attribute]").append('<option value="' + val.id + '">' + val.label + '</option>');
					}
				});
			};

			paintAttributes = function() {
				var select = jQuery("select#features");

				select.empty();
				jQuery("form#combinationform input[type=hidden]").detach();

				jQuery.each(attributes_selected.index, function (key, val) {
					var attr_info = attributes_selected.info[val];

					var opt_key = val + ':' + attr_info.value.id;
					var opt_label = attr_info.attribute.label + ': ' + attr_info.value.label;

					//Add combination to the list
					select.append('<option value="' + opt_key + '">' + opt_label + '</option>');
					//Add hidden input to catch the new combination
					jQuery("form#combinationform").append('<input type="hidden" name="features[]" value="' + opt_key + '">');
				});
			};

			jQuery(document).ready(function() {

				jQuery("select#attribute").change(function () {

					var select = jQuery("select#value");

					if (!jQuery(this).val().length) {
						select.empty();
						return;
					}

					select.empty().append('<option value="">Loading...</option>');

					jQuery.getJSON("<?php echo dol_buildpath('/attributes/ajax/get_attribute_values.php', 2) ?>", {
						id: jQuery(this).val()
					}, function(data) {
						if (data.error) {
							jQuery("select#value").empty();
							return alert(data.error);
						}

						select.empty();

						jQuery(data).each(function (key, val) {
							jQuery("select#value").append('<option value="' + val.id + '">' + val.value + '</option>');
						});
					});
				});

				jQuery("#addfeature").click(function () {
					var selectedattr = jQuery("select[name=attribute] option:selected");
					var selectedvalu = jQuery("select[name=value] option:selected");

					if (!selectedattr.val().length || !selectedvalu.val().length) {
						return;
					}

					var selectedattr_val = parseInt(selectedattr.val());

					if (jQuery.inArray(selectedattr_val, attributes_selected.index) != -1) {
						return;
					}

					attributes_selected.index.push(selectedattr_val);
					attributes_selected.info[selectedattr_val] = {
						attribute: attributes_available[selectedattr_val],
						value: {
							id: selectedvalu.val(),
							label: selectedvalu.html()
						}
					};

					paintAttributes();

					selectedattr.detach();
					jQuery("select[name=value] option").detach();
				});

				jQuery("#delfeature").click(function() {
					jQuery("#features option:selected").each(function (key, val) {
						var explode = jQuery(val).val().split(':');
						var indexOf = attributes_selected.index.indexOf(parseInt(explode[0]));

						if (indexOf != -1) {
							attributes_selected.index.splice(indexOf, 1);
							jQuery(attributes_selected.info[parseInt(explode[0])]).detach();
						}

						jQuery(val).detach();
					});

					restoreAttributes();
					paintAttributes();
				});
			});
		</script>
		<?php } ?>

		<form method="post" id="combinationform">
		<table class="border" style="width: 100%">
			<?php if ($action == 'add'): ?>
			<tr>
				<td style="width: 25%"><label for="attribute"><?php echo $langs->trans('ProductAttribute') ?></label></td>
				<td colspan="2"><select id="attribute" name="attribute">
					<option value=""></option>
					<?php foreach ($prodattr_all as $attr): ?>
					<option value="<?php echo $attr->id ?>"><?php echo $attr->label ?></option>
					<?php endforeach ?>
				</select></td>
			</tr>
			<tr>
				<td style="width: 25%"><label for="value"><?php echo $langs->trans('Value') ?></label></td>
				<td colspan="2">
					<select id="value" name="value">
						<option value=""></option>
					</select>
				</td>
			</tr>
			<?php endif; ?>
			<tr>
				<td style="width: 25%" class="fieldrequired"><label for="features"><?php echo $langs->trans('Features') ?></label></td>
				<td><select multiple style="width: 100%" id="features">
						<?php
						foreach ($productCombination2ValuePairs1 as $pc2v): ?>
							<option value="<?php echo $pc2v->fk_prod_attr ?>:<?php echo $pc2v->fk_prod_attr_val ?>"><?php echo dol_htmlentities($pc2v) ?></option>
						<?php endforeach ?>
					</select></td>
				<td>
					<?php if ($action == 'add'): ?>
					<a href="#" class="button" id="addfeature"><?php echo img_edit_add() ?></a><br><br>
					<a href="#" class="button" id="delfeature"><?php echo img_edit_remove() ?></a>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<td style="width: 25%"><label for="price_impact"><?php echo $langs->trans('PriceImpact') ?></label></td>
				<td colspan="2"><input type="text" id="price_impact" name="price_impact" value="<?php echo price($price_impact) ?>">
				<input type="checkbox" id="price_impact_percent" name="price_impact_percent" <?php echo $price_impact_percent ? ' checked' : '' ?>> <label for="price_impact_percent"><?php echo $langs->trans('PercentageVariation') ?></label></td>
			</tr>
			<tr>
				<td style="width: 25%"><label for="weight_impact"><?php echo $langs->trans('WeightImpact') ?></label></td>
				<td colspan="2"><input type="text" id="weight_impact" name="weight_impact" value="<?php echo price($weight_impact) ?>"></td>
			</tr>
		</table>

		<br>
		<div style="text-align: center"><input type="submit" value="<?php echo $action == 'add' ? $langs->trans('Create') : $langs->trans('Save') ?>" class="button"></div>
			<?php foreach ($productCombination2ValuePairs1 as $pc2v): ?>
				<input type="hidden" name="features[]" value="<?php echo $pc2v->fk_prod_attr.':'.$pc2v->fk_prod_attr_val ?>">
			<?php endforeach; ?>
		</form>
<?php

	} else {

		if ($action === 'delete') {

			if ($prodcomb->fetch($valueid) > 0) {
				$form = new Form($db);
				$prodstatic->fetch($prodcomb->fk_product_child);

				print $form->formconfirm(
					"combinations.php?id=".$id."&valueid=".$valueid,
					$langs->trans('Delete'),
					$langs->trans('ProductCombinationDeleteDialog', $prodstatic->getNomUrl(1)),
					"confirm_deletecombination",
					'',
					0,
					1
				);
			}
		} elseif ($action === 'copy') {

			$form = new Form($db);

			print $form->formconfirm(
				'combinations.php?id='.$id,
				$langs->trans('CloneCombinationsProduct'),
				$langs->trans('ConfirmCloneProductCombinations'),
				'confirm_copycombination',
				array(
					array(
						'type' => 'text',
						'label' => $langs->trans('CloneDestinationReference'),
						'name' => 'dest_product'
					)
				),
				0,
				1
			);
		}

		$comb2val = new ProductCombination2ValuePair($db);

		if ($productCombinations): ?>

			<script type="text/javascript">
				jQuery(document).ready(function() {

					jQuery('input[name="select_all"]').click(function() {

						if (jQuery(this).prop('checked')) {
							var checked = true;
						} else {
							var checked = false;
						}

						jQuery('table.liste input[type="checkbox"]').prop('checked', checked);
					});

					jQuery('input[name^="select["]').click(function() {
						jQuery('input[name="select_all"]').prop('checked', false);
					});

				});
			</script>

		<form method="post">
		<label for="bulk_action"><?php echo $langs->trans('BulkActions') ?></label>
		<select id="bulk_action" name="bulk_action" class="flat">
			<option value="not_buy"><?php echo $langs->trans('ProductStatusNotOnBuy') ?></option>
			<option value="not_sell"><?php echo $langs->trans('ProductStatusNotOnSell') ?></option>
			<option value="on_buy"><?php echo $langs->trans('ProductStatusOnBuy') ?></option>
			<option value="on_sell"><?php echo $langs->trans('ProductStatusOnSell') ?></option>
			<option value="delete"><?php echo $langs->trans('Delete') ?></option>
		</select>
		<input type="hidden" name="action" value="bulk_actions">
		<input type="submit" value="Aplicar" class="button">
		<br>
		<br>
		<?php endif; ?>

		<table class="liste">
			<tr class="liste_titre">
				<th class="liste_titre">
					<?php if ($productCombinations): ?>
					<input type="checkbox" name="select_all">
					<?php endif ?>
				</th>
				<th class="liste_titre"><?php echo $langs->trans('Product') ?></th>
				<th class="liste_titre"><?php echo $langs->trans('Combination') ?></th>
				<th class="liste_titre" style="text-align: center"><?php echo $langs->trans('PriceImpact') ?></th>
				<th class="liste_titre" style="text-align: center"><?php echo $langs->trans('WeightImpact') ?></th>
				<th class="liste_titre" style="text-align: center;"><?php echo $langs->trans('OnSell') ?></th>
				<th class="liste_titre" style="text-align: center;"><?php echo $langs->trans('OnBuy') ?></th>
				<th class="liste_titre"></th>
			</tr>
			<?php foreach ($productCombinations as $currcomb):
				$prodstatic->fetch($currcomb->fk_product_child); ?>
			<tr <?php echo $bc[!$var] ?>>
				<td><input type="checkbox" name="select[<?php echo $prodstatic->id ?>]"></td>
				<td><?php echo $prodstatic->getNomUrl(1) ?></td>
				<td>
					<?php

					$productCombination2ValuePairs = $comb2val->fetchByFkCombination($currcomb->id);
					$iMax = count($productCombination2ValuePairs);

					for ($i = 0; $i < $iMax; $i++) {
						echo dol_htmlentities($productCombination2ValuePairs[$i]);

						if ($i !== ($iMax - 1)) {
							echo ', ';
						}
					} ?>
				</td>
				<td style="text-align: right"><?php echo ($currcomb->variation_price >= 0 ? '+' : '').price($currcomb->variation_price).($currcomb->variation_price_percentage ? ' %' : '') ?></td>
				<td style="text-align: right"><?php echo ($currcomb->variation_weight >= 0 ? '+' : '').price($currcomb->variation_weight).' '.measuring_units_string($prodstatic->weight_units, 'weight') ?></td>
				<td style="text-align: center;"><?php echo $prodstatic->getLibStatut(2, 0) ?></td>
				<td style="text-align: center;"><?php echo $prodstatic->getLibStatut(2, 1) ?></td>
				<td style="text-align: right">
					<a href="<?php echo dol_buildpath('/attributes/combinations.php?id='.$id.'&action=edit&valueid='.$currcomb->id, 2) ?>"><?php echo img_edit() ?></a>
					<a href="<?php echo dol_buildpath('/attributes/combinations.php?id='.$id.'&action=delete&valueid='.$currcomb->id, 2) ?>"><?php echo img_delete() ?></a>
				</td>
			</tr>
			<?php $var = !$var; endforeach ?>
		</table>

		<?php if ($productCombinations): ?>
		</form>
		<?php endif ?>

		<?php

		print '<div class="tabsAction">';
		print '	<div class="inline-block divButAction">';
		if ($productCombinations) {
			print '		<a href="combinations.php?id='.$id.'&action=copy" class="butAction">'.$langs->trans('Copy').'</a>';
		}
		print '		<a href="generator.php?id='.$id.'" class="butAction">'.$langs->trans('ProductCombinationGenerator').'</a>
		<a href="combinations.php?id='.$id.'&action=add" class="butAction">'.$langs->trans('NewProductCombination').'</a>';
		print '	</div>';
		print '</div>';

	}
}

llxFooter();
