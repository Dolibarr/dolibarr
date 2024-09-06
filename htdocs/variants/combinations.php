<?php
/* Copyright (C) 2016      	Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2017      	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2024  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2022   	Open-Dsi			<support@open-dsi.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductAttribute.class.php';
require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductAttributeValue.class.php';
require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination.class.php';
require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination2ValuePair.class.php';

$langs->loadLangs(array("products", "other"));

$id = GETPOSTINT('id');                             // ID of the parent Product
$ref = GETPOST('ref', 'alpha');

$combination_id = GETPOSTINT('combination_id');     // ID of the combination

$reference = GETPOST('reference', 'alpha');         // Reference of the variant Product

$weight_impact = GETPOSTFLOAT('weight_impact', 2);
$price_impact_percent = (bool) GETPOST('price_impact_percent');
$price_impact = $price_impact_percent ? GETPOSTFLOAT('price_impact', 2) : GETPOSTFLOAT('price_impact', 'MU');

// for PRODUIT_MULTIPRICES
$level_price_impact = GETPOST('level_price_impact', 'array');
$level_price_impact = array_map('price2num', $level_price_impact);
$level_price_impact = array_map('floatval', $level_price_impact);
$level_price_impact_percent = GETPOST('level_price_impact_percent', 'array');
$level_price_impact_percent = array_map('boolval', $level_price_impact_percent);

$form = new Form($db);

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOSTINT('show_files');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$cancel = GETPOST('cancel', 'alpha');
$delete_product = GETPOST('delete_product', 'alpha');
$subaction = GETPOST('subaction', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');

// Security check
$fieldvalue = $id ?: $ref;
$fieldtype = !empty($ref) ? 'ref' : 'rowid';

$prodstatic = new Product($db);
$prodattr = new ProductAttribute($db);
$prodattr_val = new ProductAttributeValue($db);

$object = new Product($db);
if ($id > 0 || $ref) {
	$object->fetch($id, $ref);
}

$selectedvariant = isset($_SESSION['addvariant_'.$object->id]) ? $_SESSION['addvariant_'.$object->id] : array();
$selected = '';
// Security check
if (!isModEnabled('variants')) {
	accessforbidden('Module not enabled');
}
if ($user->socid > 0) { // Protection if external user
	accessforbidden();
}

if ($object->id > 0) {
	if ($object->isProduct()) {
		restrictedArea($user, 'produit', $object->id, 'product&product', '', '');
	}
	if ($object->isService()) {
		restrictedArea($user, 'service', $object->id, 'product&product', '', '');
	}
} else {
	restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);
}
$usercanread = (($object->isProduct() && $user->hasRight('produit', 'lire')) || ($object->isService() && $user->hasRight('service', 'lire')));
$usercancreate = (($object->isProduct() && $user->hasRight('produit', 'creer')) || ($object->isService() && $user->hasRight('service', 'creer')));
$usercandelete = (($object->isProduct() && $user->hasRight('produit', 'supprimer')) || ($object->isService() && $user->hasRight('service', 'supprimer')));


/*
 * Actions
 */

if ($cancel) {
	$action = '';
	$massaction = '';
	unset($_SESSION['addvariant_'.$object->id]);
}

if (!$object->isProduct() && !$object->isService()) {
	header('Location: '.dol_buildpath('/product/card.php?id='.$object->id, 2));
	exit();
}
if ($action == 'add') {		// Test on permission not required
	unset($selectedvariant);
	unset($_SESSION['addvariant_'.$object->id]);
}
if ($action == 'create' && GETPOST('selectvariant', 'alpha') && $usercancreate) {	// We click on select combination
	$action = 'add';
	$attribute_id = GETPOSTINT('attribute');
	$attribute_value_id = GETPOSTINT('value');
	if ($attribute_id > 0 && $attribute_value_id > 0) {
		$feature = $attribute_id . '-' . $attribute_value_id;
		$selectedvariant[$feature] = $feature;
		$_SESSION['addvariant_'.$object->id] = $selectedvariant;
	}
}
if ($action == 'create' && $subaction == 'delete' && $usercancreate) {	// We click on select combination
	$action = 'add';
	$feature = GETPOST('feature', 'intcomma');
	if (isset($selectedvariant[$feature])) {
		unset($selectedvariant[$feature]);
		$_SESSION['addvariant_'.$object->id] = $selectedvariant;
	}
}


$prodcomb = new ProductCombination($db);
$prodcomb2val = new ProductCombination2ValuePair($db);

$productCombination2ValuePairs1 = array();

if (($action == 'add' || $action == 'create') && $usercancreate && empty($massaction) && !GETPOST('selectvariant', 'alpha') && empty($subaction)) {	// We click on Create all defined combinations
	//$features = GETPOST('features', 'array');
	$features = !empty($_SESSION['addvariant_'.$object->id]) ? $_SESSION['addvariant_'.$object->id] : array();

	if (!$features) {
		if ($action == 'create') {	// Test on permission already done
			setEventMessages($langs->trans('ErrorFieldsRequired'), null, 'errors');
		}
	} else {
		$reference = trim($reference);
		if (empty($reference)) {
			$reference = false;
		}
		$weight_impact = price2num($weight_impact);
		$price_impact = price2num($price_impact);

		if (!getDolGlobalString('PRODUIT_MULTIPRICES')) {
			$level_price_impact = array(1 => $price_impact);
			$level_price_impact_percent = array(1 => $price_impact_percent);
		}

		$sanit_features = array();

		//First, sanitize
		foreach ($features as $feature) {
			$explode = explode('-', $feature);
			if ($prodattr->fetch($explode[0]) <= 0 || $prodattr_val->fetch($explode[1]) <= 0) {
				continue;
			}

			// Valuepair
			$sanit_features[(int) $explode[0]] = (int) $explode[1];

			$tmp = new ProductCombination2ValuePair($db);
			$tmp->fk_prod_attr = (int) $explode[0];
			$tmp->fk_prod_attr_val = (int) $explode[1];

			$productCombination2ValuePairs1[] = $tmp;
		}

		$db->begin();

		// sanit_feature is an array with 1 (and only 1) value per attribute.
		// For example:  Color->blue, Size->Small, Option->2
		if (!$prodcomb->fetchByProductCombination2ValuePairs($id, $sanit_features)) {
			$result = $prodcomb->createProductCombination($user, $object, $sanit_features, array(), $level_price_impact_percent, $level_price_impact, $weight_impact, $reference);
			if ($result > 0) {
				setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
				unset($_SESSION['addvariant_'.$object->id]);

				$db->commit();
				header('Location: '.dol_buildpath('/variants/combinations.php?id='.$id, 2));
				exit();
			} else {
				$langs->load("errors");
				setEventMessages($prodcomb->error, $prodcomb->errors, 'errors');
			}
		} else {
			setEventMessages($langs->trans('ErrorRecordAlreadyExists'), null, 'errors');
		}

		$db->rollback();
	}
} elseif (!empty($massaction)) {
	$bulkaction = $massaction;
	$error = 0;

	$db->begin();

	foreach ($toselect as $prodid) {
		// need create new of Product to prevent rename dir behavior
		$prodstatic = new Product($db);
		if ($prodstatic->fetch($prodid) < 0) {
			continue;
		}

		if ($bulkaction == 'on_sell') {
			$prodstatic->status = 1;
			$res = $prodstatic->update($prodstatic->id, $user);
			if ($res <= 0) {
				setEventMessages($prodstatic->error, $prodstatic->errors, 'errors');
				$error++;
				break;
			}
		} elseif ($bulkaction == 'on_buy') {
			$prodstatic->status_buy = 1;
			$res = $prodstatic->update($prodstatic->id, $user);
			if ($res <= 0) {
				setEventMessages($prodstatic->error, $prodstatic->errors, 'errors');
				$error++;
				break;
			}
		} elseif ($bulkaction == 'not_sell') {
			$prodstatic->status = 0;
			$res = $prodstatic->update($prodstatic->id, $user);
			if ($res <= 0) {
				setEventMessages($prodstatic->error, $prodstatic->errors, 'errors');
				$error++;
				break;
			}
		} elseif ($bulkaction == 'not_buy') {
			$prodstatic->status_buy = 0;
			$res = $prodstatic->update($prodstatic->id, $user);
			if ($res <= 0) {
				setEventMessages($prodstatic->error, $prodstatic->errors, 'errors');
				$error++;
				break;
			}
		} elseif ($bulkaction == 'delete') {
			$res = $prodstatic->delete($user, $prodstatic->id);
			if ($res <= 0) {
				setEventMessages($prodstatic->error, $prodstatic->errors, 'errors');
				$error++;
				break;
			}
		} else {
			break;
		}
	}

	if ($error) {
		$db->rollback();
		if (empty($prodstatic->error)) {
			setEventMessages($langs->trans('CoreErrorMessage'), null, 'errors');
		}
	} else {
		$db->commit();
		setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
	}
} elseif ($action === 'update' && $combination_id > 0 && $usercancreate) {
	if ($prodcomb->fetch($combination_id) < 0) {
		dol_print_error($db, $langs->trans('ErrorRecordNotFound'));
		exit();
	}

	$prodcomb->variation_weight = (float) price2num($weight_impact);

	// for conf PRODUIT_MULTIPRICES
	if (getDolGlobalString('PRODUIT_MULTIPRICES')) {
		$prodcomb->variation_price = $level_price_impact[1];
		$prodcomb->variation_price_percentage = $level_price_impact_percent[1];
	} else {
		$level_price_impact = array(1 => $price_impact);
		$level_price_impact_percent = array(1 => $price_impact_percent);

		$prodcomb->variation_price = $price_impact;
		$prodcomb->variation_price_percentage = $price_impact_percent;
	}

	if (getDolGlobalString('PRODUIT_MULTIPRICES')) {
		$prodcomb->combination_price_levels = array();
		for ($i = 1; $i <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $i++) {
			$productCombinationLevel = new ProductCombinationLevel($db);
			$productCombinationLevel->fk_product_attribute_combination = $prodcomb->id;
			$productCombinationLevel->fk_price_level = $i;
			$productCombinationLevel->variation_price = (float) $level_price_impact[$i];
			$productCombinationLevel->variation_price_percentage = (bool) $level_price_impact_percent[$i];
			$prodcomb->combination_price_levels[$i] = $productCombinationLevel;
		}
	}

	$error = 0;
	$db->begin();

	// Update product variant ref
	$product_child = new Product($db);
	$product_child->fetch($prodcomb->fk_product_child);
	$product_child->oldcopy = clone $product_child;  // @phan-ignore-current-line PhanTypeMismatchProperty
	$product_child->ref = $reference;

	$result = $product_child->update($product_child->id, $user);
	if ($result < 0) {
		setEventMessages($product_child->error, $product_child->errors, 'errors');
		$error++;
	}

	if (!$error) {
		// Update product variant infos
		$result = $prodcomb->update($user);
		if ($result < 0) {
			setEventMessages($prodcomb->error, $prodcomb->errors, 'errors');
			$error++;
		}
	}

	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
		header('Location: ' . dol_buildpath('/variants/combinations.php?id=' . $id, 2));
		exit();
	} else {
		$db->rollback();
	}
}


// Reload variants
$productCombinations = $prodcomb->fetchAllByFkProductParent($object->id, true);

if ($action === 'confirm_deletecombination' && $usercancreate) {
	if ($prodcomb->fetch($combination_id) > 0) {
		$db->begin();

		if ($prodcomb->delete($user) > 0 && (empty($delete_product) || ($delete_product == 'on' && $prodstatic->fetch($prodcomb->fk_product_child) > 0 && $prodstatic->delete($user) > 0))) {
			$db->commit();
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
			header('Location: '.dol_buildpath('/variants/combinations.php?id='.$object->id, 2));
			exit();
		}

		$db->rollback();
		setEventMessages($langs->trans('ProductCombinationAlreadyUsed'), null, 'errors');
		$action = '';
	}
} elseif ($action === 'edit' && $usercancreate) {
	if ($prodcomb->fetch($combination_id) < 0) {
		dol_print_error($db, $langs->trans('ErrorRecordNotFound'));
		exit();
	}

	$product_child = new Product($db);
	$product_child->fetch($prodcomb->fk_product_child);
	$reference = $product_child->ref;
	$weight_impact = $prodcomb->variation_weight;
	$price_impact = $prodcomb->variation_price;
	$price_impact_percent = $prodcomb->variation_price_percentage;

	$productCombination2ValuePairs1 = $prodcomb2val->fetchByFkCombination($combination_id);
} elseif ($action === 'confirm_copycombination' && $usercancreate) {
	//Check destination product
	$dest_product = GETPOST('dest_product');

	if ($prodstatic->fetch(0, $dest_product) > 0) {
		//To prevent from copying to the same product
		if ($prodstatic->ref != $object->ref) {
			if ($prodcomb->copyAll($user, $object->id, $prodstatic) > 0) {
				header('Location: '.dol_buildpath('/variants/combinations.php?id='.$prodstatic->id, 2));
				exit();
			} else {
				setEventMessages($langs->trans('ErrorCopyProductCombinations'), null, 'errors');
			}
		}
	} else {
		setEventMessages($langs->trans('ErrorDestinationProductNotFound'), null, 'errors');
	}
}



/*
 *	View
 */

$form = new Form($db);

$title = $langs->trans("Variant");

llxHeader("", $title);


if (!empty($id) || !empty($ref)) {
	$showbarcode = isModEnabled('barcode');
	if (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && !$user->hasRight('barcode', 'lire_advance')) {
		$showbarcode = 0;
	}

	$head = product_prepare_head($object);
	$titre = $langs->trans("CardProduct".$object->type);
	$picto = $object->isService() ? 'service' : 'product';

	print dol_get_fiche_head($head, 'combinations', $titre, -1, $picto);

	$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?type='.((int) $object->type).'">'.$langs->trans("BackToList").'</a>';
	$object->next_prev_filter = "fk_product_type = ".((int) $object->type);

	dol_banner_tab($object, 'ref', $linkback, ($user->socid ? 0 : 1), 'ref', '', '', '', 0, '', '');

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	// Type
	if (isModEnabled("product") && isModEnabled("service")) {
		$typeformat = 'select;0:'.$langs->trans("Product").',1:'.$langs->trans("Service");
		print '<tr><td class="titlefieldcreate">';
		print (!getDolGlobalString('PRODUCT_DENY_CHANGE_PRODUCT_TYPE')) ? $form->editfieldkey("Type", 'fk_product_type', $object->type, $object, $usercancreate, $typeformat) : $langs->trans('Type');
		print '</td><td>';
		print $form->editfieldval("Type", 'fk_product_type', $object->type, $object, $usercancreate, $typeformat);
		print '</td></tr>';
	}

	// TVA
	print '<tr><td class="titlefieldcreate">'.$langs->trans("DefaultTaxRate").'</td><td>';

	$positiverates = '';
	if (price2num($object->tva_tx)) {
		$positiverates .= ($positiverates ? '/' : '').price2num($object->tva_tx);
	}
	if (price2num($object->localtax1_type)) {
		$positiverates .= ($positiverates ? '/' : '').price2num($object->localtax1_tx);
	}
	if (price2num($object->localtax2_type)) {
		$positiverates .= ($positiverates ? '/' : '').price2num($object->localtax2_tx);
	}
	if (empty($positiverates)) {
		$positiverates = '0';
	}
	echo vatrate($positiverates.($object->default_vat_code ? ' ('.$object->default_vat_code.')' : ''), '%', $object->tva_npr);
	/*
	if ($object->default_vat_code)
	{
		print vatrate($object->tva_tx, true) . ' ('.$object->default_vat_code.')';
	}
	else print vatrate($object->tva_tx, true, $object->tva_npr, true);*/
	print '</td></tr>';

	// Price
	print '<tr><td>'.$langs->trans("SellingPrice").'</td><td>';
	if ($object->price_base_type == 'TTC') {
		print price($object->price_ttc).' '.$langs->trans($object->price_base_type);
	} else {
		print price($object->price).' '.$langs->trans($object->price_base_type);
	}
	print '</td></tr>';

	// Price minimum
	print '<tr><td>'.$langs->trans("MinPrice").'</td><td>';
	if ($object->price_base_type == 'TTC') {
		print price($object->price_min_ttc).' '.$langs->trans($object->price_base_type);
	} else {
		print price($object->price_min).' '.$langs->trans($object->price_base_type);
	}
	print '</td></tr>';

	// Weight
	print '<tr><td>'.$langs->trans("Weight").'</td><td>';
	if ($object->weight != '') {
		print $object->weight." ".measuringUnitString(0, "weight", $object->weight_units);
	} else {
		print '&nbsp;';
	}
	print "</td></tr>\n";

	print "</table>\n";

	print '</div>';
	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	$listofvariantselected = '';

	// Create or edit a variant
	if ($action == 'add' || ($action == 'edit')) {
		if ($action == 'add') {
			$title = $langs->trans('NewProductCombination');
			// print dol_get_fiche_head();
			$features = !empty($_SESSION['addvariant_'.$object->id]) ? $_SESSION['addvariant_'.$object->id] : array();
			//First, sanitize
			$listofvariantselected = '<div id="parttoaddvariant">';
			if (!empty($features)) {
				$toprint = array();
				foreach ($features as $feature) {
					$explode = explode('-', $feature);
					if ($prodattr->fetch($explode[0]) <= 0 || $prodattr_val->fetch($explode[1]) <= 0) {
						continue;
					}
					$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #ddd;">' . $prodattr->label.' : '.$prodattr_val->value .
						' <a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=create&subaction=delete&feature='.urlencode($feature).'">' . img_delete() . '</a></li>';
				}
				$listofvariantselected .= '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">' . implode(' ', $toprint) . '</ul></div>';
			}
			$listofvariantselected .= '</div>';
			//print dol_get_fiche_end();
		} else {
			$title = $langs->trans('EditProductCombination');
		}

		if ($action == 'add') {
			$prodattr_all = $prodattr->fetchAll();

			if (!$selected) {
				$selected = $prodattr_all[key($prodattr_all)]->id;
			}

			$prodattr_alljson = array();

			foreach ($prodattr_all as $each) {
				$prodattr_alljson[$each->id] = $each;
			} ?>

		<script type="text/javascript">

			variants_available = <?php echo json_encode($prodattr_alljson, JSON_PARTIAL_OUTPUT_ON_ERROR); ?>;
			variants_selected = {
				index: [],
				info: []
			};

			<?php
			foreach ($productCombination2ValuePairs1 as $pc2v) {
				$prodattr_val->fetch($pc2v->fk_prod_attr_val); ?>
				variants_selected.index.push(<?php echo $pc2v->fk_prod_attr ?>);
				variants_selected.info[<?php echo $pc2v->fk_prod_attr ?>] = {
					attribute: variants_available[<?php echo $pc2v->fk_prod_attr ?>],
					value: {
						id: <?php echo $pc2v->fk_prod_attr_val ?>,
						label: '<?php echo $prodattr_val->value ?>'
					}
				};
				<?php
			} ?>

			restoreAttributes = function() {
				jQuery("select[name=attribute]").empty().append('<option value="-1">&nbsp;</option>');

				jQuery.each(variants_available, function (key, val) {
					if (jQuery.inArray(val.id, variants_selected.index) == -1) {
						jQuery("select[name=attribute]").append('<option value="' + val.id + '">' + val.label + '</option>');
					}
				});
			};


			jQuery(document).ready(function() {
				jQuery("select#attribute").change(function () {
					console.log("Change of field variant attribute");
					var select = jQuery("select#value");

					if (!jQuery(this).val().length || jQuery(this).val() == '-1') {
						select.empty();
						select.append('<option value="-1">&nbsp;</option>');
						return;
					}

					select.empty().append('<option value="">Loading...</option>');

					jQuery.getJSON("ajax/get_attribute_values.php", {
						id: jQuery(this).val()
					}, function(data) {
						if (data.error) {
							select.empty();
							select.append('<option value="-1">&nbsp;</option>');
							return alert(data.error);
						}

						select.empty();
						select.append('<option value="-1">&nbsp;</option>');

						jQuery(data).each(function (key, val) {
							keyforoption = val.id
							valforoption = val.value
							select.append('<option value="' + keyforoption + '">' + valforoption + '</option>');
						});
					});
				});
			});
		</script>

			<?php
		}

		print '<br>';

		print load_fiche_titre($title);

		print '<form method="post" id="combinationform" action="'.$_SERVER["PHP_SELF"] .'?id='.$object->id.'">'."\n";
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="'.(($combination_id > 0) ? "update" : "create").'">'."\n";
		if ($combination_id > 0) {
			print '<input type="hidden" name="combination_id" value="'.$combination_id.'">'."\n";
		}

		print dol_get_fiche_head();


		if ($action == 'add') {
			print '<table class="border" style="width: 100%">';
			print "<!--  Variant -->\n";
			print '<tr>';
			print '<td class="titlefieldcreate fieldrequired"><label for="attribute">'.$langs->trans('ProductAttribute').'</label></td>';
			print '<td>';
			if (is_array($prodattr_all)) {
				print '<select class="flat minwidth100" id="attribute" name="attribute">';
				print '<option value="-1">&nbsp;</option>';
				foreach ($prodattr_all as $attr) {
					//print '<option value="'.$attr->id.'"'.($attr->id == GETPOST('attribute', 'int') ? ' selected="selected"' : '').'>'.$attr->label.'</option>';
					print '<option value="'.$attr->id.'">'.$attr->label.'</option>';
				}
				print '</select>';
			}

			$htmltext = $langs->trans("GoOnMenuToCreateVairants", $langs->transnoentities("Product"), $langs->transnoentities("VariantAttributes"));
			print $form->textwithpicto('', $htmltext);
			/*print ' &nbsp; &nbsp; <a href="'.DOL_URL_ROOT.'/variants/create.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=add&token='.newToken().'&id='.$object->id).'">';
			print $langs->trans("Create");
			print '</a>';*/

			print '</td>';
			print '</tr>'; ?>
			<!-- Value -->
			<tr>
				<td class="fieldrequired"><label for="value"><?php echo $langs->trans('Value') ?></label></td>
				<td>
					<select class="flat minwidth100" id="value" name="value">
						<option value="-1">&nbsp;</option>
					</select>
					<?php
					$htmltext = $langs->trans("GoOnMenuToCreateVairants", $langs->transnoentities("Product"), $langs->transnoentities("VariantAttributes"));
					print $form->textwithpicto('', $htmltext);
					/*
					print ' &nbsp; &nbsp; <a href="'.DOL_URL_ROOT.'/variants/create.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=add&token='.newToken().'&id='.$object->id).'">';
					print $langs->trans("Create");
					print '</a>';
					*/ ?>
				</td>
			</tr>
			<tr>
				<td></td><td>
					<input type="submit" class="button" name="selectvariant" id="selectvariant" value="<?php echo dol_escape_htmltag($langs->trans("SelectCombination")); ?>">
				</td>
			</tr>
			<?php
			print '<tr><td></td><td>';
			print $listofvariantselected;
			print '</td>';
			print '</tr>';

			print '</table>';
			print '<hr>';
		}

		if (is_array($productCombination2ValuePairs1)) {
			print '<table class="border" style="width: 100%">';

			// When in edit mode
			if (is_array($productCombination2ValuePairs1) && count($productCombination2ValuePairs1)) {
				?>
				<tr>
					<td class="titlefieldcreate tdtop"><label for="features"><?php echo $langs->trans('Attributes') ?></label></td>
					<td class="tdtop">
						<div class="inline-block valignmiddle quatrevingtpercent">
					<?php
					foreach ($productCombination2ValuePairs1 as $pc2v) {
						$result1 = $prodattr->fetch($pc2v->fk_prod_attr);
						$result2 = $prodattr_val->fetch($pc2v->fk_prod_attr_val);
						if ($result1 > 0 && $result2 > 0) {
							print $prodattr->label.' : '.$prodattr_val->value.'<br>';
							// TODO Add delete link
						}
					} ?>
						</div>
						<!-- <div class="inline-block valignmiddle">
						<a href="#" class="inline-block valignmiddle button" id="delfeature"><?php echo img_edit_remove() ?></a>
						</div>-->
					</td>
					<td>
					</td>
				</tr>
				<?php
			} ?>
			<tr>
				<td><label for="reference"><?php echo $langs->trans('Reference') ?></label></td>
				<td><input type="text" id="reference" name="reference" value="<?php echo trim($reference) ?>"></td>
			</tr>
			<?php
			if (!getDolGlobalString('PRODUIT_MULTIPRICES')) {
				?>
			<tr>
				<td><label for="price_impact"><?php echo $langs->trans('PriceImpact') ?></label></td>
				<td><input type="text" id="price_impact" name="price_impact" value="<?php echo price($price_impact) ?>">
				<input type="checkbox" id="price_impact_percent" name="price_impact_percent" <?php echo $price_impact_percent ? ' checked' : '' ?>> <label for="price_impact_percent"><?php echo $langs->trans('PercentageVariation') ?></label>
				</td>
			</tr>
				<?php
			} else {
				$prodcomb->fetchCombinationPriceLevels();

				for ($i = 1; $i <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $i++) {
					$keyforlabel = 'PRODUIT_MULTIPRICES_LABEL'.$i;
					$text = $langs->trans('ImpactOnPriceLevel', $i).' - '.getDolGlobalString($keyforlabel);
					print '<tr>';
					print '<td><label for="level_price_impact_'.$i.'">'.$text.'</label>';
					if ($i === 1) {
						print '<br/><a id="apply-price-impact-to-all-level" class="classfortooltip" href="#" title="'.$langs->trans('ApplyToAllPriceImpactLevelHelp').'">('.$langs->trans('ApplyToAllPriceImpactLevel').')</a>';
					}
					print '</td>';
					print '<td><input type="text" class="level_price_impact" id="level_price_impact_'.$i.'" name="level_price_impact['.$i.']" value="'.price($prodcomb->combination_price_levels[$i]->variation_price).'">';
					print '<input type="checkbox" class="level_price_impact_percent" id="level_price_impact_percent_'.$i.'" name="level_price_impact_percent['.$i.']" '.(!empty($prodcomb->combination_price_levels[$i]->variation_price_percentage) ? ' checked' : '').'> <label for="level_price_impact_percent_'.$i.'">'.$langs->trans('PercentageVariation').'</label>';

					print '</td>';
					print '</tr>';
				}
			}

			if ($object->isProduct()) {
				print '<tr>';
				print '<td><label for="weight_impact">'.$langs->trans('WeightImpact').'</label></td>';
				print '<td><input type="text" id="weight_impact" name="weight_impact" value="'.price($weight_impact).'"></td>';
				print '</tr>';
			}

			print '</table>';
		}

		if (getDolGlobalString('PRODUIT_MULTIPRICES')) {
			?>
			<script>
				$(document).ready(function() {
					// Apply level 1 impact to all prices impact levels
					$('body').on('click', '#apply-price-impact-to-all-level', function(e) {
						e.preventDefault();
						let priceImpact = $( "#level_price_impact_1" ).val();
						let priceImpactPrecent = $( "#level_price_impact_percent_1" ).prop("checked");

						var multipricelimit = <?php print intval($conf->global->PRODUIT_MULTIPRICES_LIMIT); ?>

						for (let i = 2; i <= multipricelimit; i++) {
							$( "#level_price_impact_" + i ).val(priceImpact);
							$( "#level_price_impact_percent_" + i  ).prop("checked", priceImpactPrecent);
						}
					});
				});
			</script>
			<?php
		}

		print dol_get_fiche_end(); ?>

		<div style="text-align: center">
		<input type="submit" name="create" <?php if (!is_array($productCombination2ValuePairs1)) {
			print ' disabled="disabled"';
										   } ?> value="<?php echo $action == 'add' ? $langs->trans('Create') : $langs->trans("Save") ?>" class="button button-save">
		&nbsp;
		<input type="submit" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>" class="button button-cancel">
		</div>

		<?php

		print '</form>';
	} else {
		if ($action === 'delete') {
			if ($prodcomb->fetch($combination_id) > 0) {
				$prodstatic->fetch($prodcomb->fk_product_child);

				print $form->formconfirm(
					"combinations.php?id=".urlencode((string) ($id))."&combination_id=".urlencode((string) ($combination_id)),
					$langs->trans('Delete'),
					$langs->trans('ProductCombinationDeleteDialog', $prodstatic->ref),
					"confirm_deletecombination",
					array(array('label' => $langs->trans('DeleteLinkedProduct'), 'type' => 'checkbox', 'name' => 'delete_product', 'value' => false)),
					0,
					1
				);
			}
		} elseif ($action === 'copy') {
			print $form->formconfirm('combinations.php?id='.$id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneProductCombinations'), 'confirm_copycombination', array(array('type' => 'text', 'label' => $langs->trans('CloneDestinationReference'), 'name' => 'dest_product')), 0, 1);
		}

		$comb2val = new ProductCombination2ValuePair($db);

		if ($productCombinations) {
			?>

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

			<?php
		}

		// Buttons
		print '<div class="tabsAction">';

		print '	<div class="inline-block divButAction">';

		print '<a href="combinations.php?id='.$object->id.'&action=add&token='.newToken().'" class="butAction">'.$langs->trans('NewProductCombination').'</a>'; // NewVariant

		if ($productCombinations) {
			print '<a href="combinations.php?id='.$object->id.'&action=copy&token='.newToken().'" class="butAction">'.$langs->trans('PropagateVariant').'</a>';
		}

		print '	</div>';

		print '</div>';



		$arrayofselected = is_array($toselect) ? $toselect : array();


		// List of variants
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"] .'?id='.$object->id.'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="massaction">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

		// List of mass actions available

		$aaa = '';
		if (count($productCombinations)) {
			$aaa = '<select id="bulk_action" name="massaction" class="flat">';
			$aaa .= '	<option value="nothing">&nbsp;</option>';
			$aaa .= '	<option value="not_buy" data-html="'.dol_escape_htmltag(img_picto($langs->trans("SetToStatus"), 'stop-circle', 'class="pictofixedwidth"').$langs->trans('SetToStatus', $langs->transnoentitiesnoconv('ProductStatusNotOnBuy'))).'">'.$langs->trans('ProductStatusNotOnBuy').'</option>';
			$aaa .= '	<option value="not_sell" data-html="'.dol_escape_htmltag(img_picto($langs->trans("SetToStatus"), 'stop-circle', 'class="pictofixedwidth"').$langs->trans('SetToStatus', $langs->transnoentitiesnoconv('ProductStatusNotOnSell'))).'">'.$langs->trans('ProductStatusNotOnSell').'</option>';
			$aaa .= '	<option value="on_buy" data-html="'.dol_escape_htmltag(img_picto($langs->trans("SetToStatus"), 'stop-circle', 'class="pictofixedwidth"').$langs->trans('SetToStatus', $langs->transnoentitiesnoconv('ProductStatusOnBuy'))).'">'.$langs->trans('ProductStatusOnBuy').'</option>';
			$aaa .= '	<option value="on_sell" data-html="'.dol_escape_htmltag(img_picto($langs->trans("SetToStatus"), 'stop-circle', 'class="pictofixedwidth"').$langs->trans('SetToStatus', $langs->transnoentitiesnoconv('ProductStatusOnSell'))).'">'.$langs->trans('ProductStatusOnSell').'</option>';
			$aaa .= '	<option value="delete" data-html="'.dol_escape_htmltag(img_picto($langs->trans("Delete"), 'delete', 'class="pictofixedwidth"').$langs->trans('Delete')).'">'.$langs->trans('Delete').'</option>';
			$aaa .= '</select>';
			$aaa .= ajax_combobox("bulk_action");
			$aaa .= '<input type="submit" value="'.dol_escape_htmltag($langs->trans("Apply")).'" class="button small">';
		}
		$massactionbutton = $aaa;

		$title = $langs->trans("ProductCombinations");

		print_barre_liste($title, 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, $aaa, 0);

		print '<div class="div-table-responsive">'; ?>
		<table class="liste">
			<tr class="liste_titre">
				<?php
				// Action column
				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="liste_titre center">';
					$searchpicto = $form->showCheckAddButtons('checkforselect', 1);
					print $searchpicto;
					print '</td>';
				} ?>
				<td class="liste_titre"><?php echo $langs->trans('Product') ?></td>
				<td class="liste_titre"><?php echo $langs->trans('Attributes') ?></td>
				<td class="liste_titre right"><?php echo $langs->trans('PriceImpact') ?></td>
				<?php if ($object->isProduct()) {
					print'<td class="liste_titre right">'.$langs->trans('WeightImpact').'</td>';
				} ?>
				<td class="liste_titre center"><?php echo $langs->trans('OnSell') ?></td>
				<td class="liste_titre center"><?php echo $langs->trans('OnBuy') ?></td>
				<td class="liste_titre"></td>
				<?php
				// Action column
				if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="liste_titre center">';
					$searchpicto = $form->showCheckAddButtons('checkforselect', 1);
					print $searchpicto;
					print '</td>';
				} ?>
			</tr>
		<?php

		if (count($productCombinations)) {
			foreach ($productCombinations as $currcomb) {
				$prodstatic->fetch($currcomb->fk_product_child);
				print '<tr class="oddeven">';

				// Action column
				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="nowrap center">';
					if (!empty($productCombinations) || $massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
						$selected = 0;
						if (in_array($prodstatic->id, $arrayofselected)) {
							$selected = 1;
						}
						print '<input id="cb'.$prodstatic->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$prodstatic->id.'"'.($selected ? ' checked="checked"' : '').'>';
					}
					print '</td>';
				}

				print '<td>'.$prodstatic->getNomUrl(1).'</td>';
				print '<td>';
				foreach ($comb2val->fetchByFkCombination($currcomb->id) as $pc2v) {
					print dol_htmlentities($pc2v).'<br>';
				}
				print '</td>';
				print '<td class="right">'.($currcomb->variation_price >= 0 ? '+' : '').price($currcomb->variation_price).($currcomb->variation_price_percentage ? ' %' : '').'</td>';
				if ($object->isProduct()) {
					print '<td class="right">'.($currcomb->variation_weight >= 0 ? '+' : '').price($currcomb->variation_weight).' '.measuringUnitString(0, 'weight', $prodstatic->weight_units).'</td>';
				}
				print '<td class="center">'.$prodstatic->getLibStatut(2, 0).'</td>';
				print '<td class="center">'.$prodstatic->getLibStatut(2, 1).'</td>';

				print '<td class="right">';
				print '<a class="paddingleft paddingright editfielda" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&action=edit&token='.newToken().'&combination_id='.$currcomb->id.'">'.img_edit().'</a>';
				print '<a class="paddingleft paddingright" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&action=delete&token='.newToken().'&combination_id='.$currcomb->id.'">'.img_delete().'</a>';
				print '</td>';

				// Action column
				if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="nowrap center">';
					if (!empty($productCombinations) || $massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
						$selected = 0;
						if (in_array($prodstatic->id, $arrayofselected)) {
							$selected = 1;
						}
						print '<input id="cb'.$prodstatic->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$prodstatic->id.'"'.($selected ? ' checked="checked"' : '').'>';
					}
					print '</td>';
				}

				print '</tr>';
			}
		} else {
			print '<tr><td colspan="8"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
		}
		print '</table>';
		print '</div>';
		print '</form>';
	}
}

// End of page
llxFooter();
$db->close();
