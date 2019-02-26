<?php
/* Copyright (C) 2016	Marcos García	      <marcosgdf@gmail.com>
 * Copyright (C) 2017	Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
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
$langs->load("other");

$var = false;
$id = GETPOST('id', 'int');
$valueid = GETPOST('valueid', 'int');
$ref = GETPOST('ref', 'alpha');
$weight_impact = GETPOST('weight_impact', 'alpha');
$price_impact = GETPOST('price_impact', 'alpha');
$price_impact_percent = (bool) GETPOST('price_impact_percent');
$form = new Form($db);

$action=GETPOST('action','alpha');
$massaction=GETPOST('massaction','alpha');
$show_files=GETPOST('show_files','int');
$confirm=GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');
$cancel = GETPOST('cancel','alpha');

// Security check
$fieldvalue = (! empty($id) ? $id : $ref);
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
$result=restrictedArea($user,'produit|service',$fieldvalue,'product&product','','',$fieldtype);

$prodstatic = new Product($db);
$prodattr = new ProductAttribute($db);
$prodattr_val = new ProductAttributeValue($db);

$object = new Product($db);
if ($id > 0 || $ref)
{
    $object->fetch($id, $ref);
}

$selectedvariant = $_SESSION['addvariant_'.$object->id];


/*
 * Actions
 */

if ($cancel) {
    $action='';
    $massactions='';
    unset($_SESSION['addvariant_'.$object->id]);
}

if (! $object->isProduct()) {
	header('Location: '.dol_buildpath('/product/card.php?id='.$object->id, 2));
	exit();
}
if ($action == 'add')
{
	unset($selectedvariant);
	unset($_SESSION['addvariant_'.$object->id]);
}
if ($action == 'create' && GETPOST('selectvariant','alpha'))	// We click on select combination
{
    $action = 'add';
    if (GETPOST('attribute') != '-1' && GETPOST('value') != '-1')
    {
        $selectedvariant[GETPOST('attribute').':'.GETPOST('value')]=GETPOST('attribute').':'.GETPOST('value');
        $_SESSION['addvariant_'.$object->id]=$selectedvariant;
    }
}


$prodcomb = new ProductCombination($db);
$prodcomb2val = new ProductCombination2ValuePair($db);

$productCombination2ValuePairs1 = array();

if ($_POST) {

	if (($action == 'add' || $action == 'create') && empty($massaction) && ! GETPOST('selectvariant', 'alpha'))	// We click on Create all defined combinations
	{
		//$features = GETPOST('features', 'array');
        $features = $_SESSION['addvariant_'.$object->id];

		if (!$features) {
			setEventMessage($langs->trans('ErrorFieldsRequired'), 'errors');
		}
		else
		{
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

				// Valuepair
				$sanit_features[$explode[0]] = $explode[1];

				$tmp = new ProductCombination2ValuePair($db);
				$tmp->fk_prod_attr = $explode[0];
				$tmp->fk_prod_attr_val = $explode[1];

				$productCombination2ValuePairs1[] = $tmp;
			}

			$db->begin();

			// sanit_feature is an array with 1 (and only 1) value per attribute.
			// For example:  Color->blue, Size->Small, Option->2
			//var_dump($sanit_features);
			//var_dump($productCombination2ValuePairs1); exit;

			if (! $prodcomb->fetchByProductCombination2ValuePairs($id, $sanit_features))
			{
				$result = $prodcomb->createProductCombination($object, $sanit_features, array(), $price_impact_percent, $price_impact, $weight_impact);
				if ($result > 0)
				{
					setEventMessage($langs->trans('RecordSaved'));
					unset($_SESSION['addvariant_'.$object->id]);

					$db->commit();
					header('Location: '.dol_buildpath('/variants/combinations.php?id='.$id, 2));
					exit();
				} else {
					$langs->load("errors");
					setEventMessages('', $prodcomb->errors, 'errors');
				}
			} else {
				setEventMessages($langs->trans('ErrorRecordAlreadyExists'), null, 'errors');
			}

			$db->rollback();
		}
	}
	elseif (! empty($massaction))
	{
		$bulkaction = $massaction;
		$error = 0;

		$prodstatic = new Product($db);

		$db->begin();

		foreach ($toselect as $prodid) {

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
				$res = $prodstatic->delete($user, $prodstatic->id);
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
				setEventMessages($prodstatic->error, $prodstatic->errors, 'errors');
			} else {
				setEventMessages($langs->trans('CoreErrorMessage'), null, 'errors');
			}

		} else {
			$db->commit();
			setEventMessage($langs->trans('RecordSaved'));
		}

	}
	elseif ($valueid > 0) {

		if ($prodcomb->fetch($valueid) < 0) {
			dol_print_error($db, $langs->trans('ErrorRecordNotFound'));
			exit();
		}

		$prodcomb->variation_price_percentage = $price_impact_percent;
		$prodcomb->variation_price = $price_impact;
		$prodcomb->variation_weight = $weight_impact;

		if ($prodcomb->update($user) > 0) {
			setEventMessage($langs->trans('RecordSaved'));
			header('Location: '.dol_buildpath('/variants/combinations.php?id='.$id, 2));
			exit();
		} else {
			setEventMessages($prodcomb->error, $prodcomb->errors, 'errors');
		}
	}
}

// Reload variants
$productCombinations = $prodcomb->fetchAllByFkProductParent($object->id);

if ($action === 'confirm_deletecombination') {

	if ($prodcomb->fetch($valueid) > 0) {

		$db->begin();

		if ($prodcomb->delete($user) > 0 && $prodstatic->fetch($prodcomb->fk_product_child) > 0 && $prodstatic->delete($user) > 0) {
			$db->commit();
			setEventMessage($langs->trans('RecordSaved'));
			header('Location: '.dol_buildpath('/variants/combinations.php?id='.$object->id, 2));
			exit();
		}

		$db->rollback();
		setEventMessage($langs->trans('ProductCombinationAlreadyUsed'), 'errors');
		$action = '';
	}
} elseif ($action === 'edit') {

	if ($prodcomb->fetch($valueid) < 0) {
		dol_print_error($db, $langs->trans('ErrorRecordNotFound'));
		exit();
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
		if ($prodstatic->ref != $object->ref) {
			if ($prodcomb->copyAll($object->id, $prodstatic) > 0) {
				header('Location: '.dol_buildpath('/variants/combinations.php?id='.$prodstatic->id, 2));
				exit();
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

$form = new Form($db);

if (! empty($id) || ! empty($ref))
{
	llxHeader("", "", $langs->trans("CardProduct".$object->type));

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


	// Create or edit a varian
	if ($action == 'add' || ($action == 'edit')) {

		if ($action == 'add') {
			$title = $langs->trans('NewProductCombination');
		} else {
			$title = $langs->trans('EditProductCombination');
		}

		print '<div id="parttoaddvariant"></div>';
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

		<script type="text/javascript">

			variants_available = <?php echo json_encode($prodattr_alljson); ?>;
			variants_selected = {
				index: [],
				info: []
			};

			<?php
			foreach ($productCombination2ValuePairs1 as $pc2v) {
                $prodattr_val->fetch($pc2v->fk_prod_attr_val);
			?>
    			variants_selected.index.push(<?php echo $pc2v->fk_prod_attr ?>);
    			variants_selected.info[<?php echo $pc2v->fk_prod_attr ?>] = {
    				attribute: variants_available[<?php echo $pc2v->fk_prod_attr ?>],
    				value: {
    					id: <?php echo $pc2v->fk_prod_attr_val ?>,
    					label: '<?php echo $prodattr_val->value ?>'
    				}
    			};
			<?php
		    }
		    ?>

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

		print '<form method="post" id="combinationform" action="'.$_SERVER["PHP_SELF"].'">'."\n";
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="id" value="'.dol_escape_htmltag($id).'">'."\n";
		print '<input type="hidden" name="action" value="' .  (($valueid > 0) ? "update" : "create") .'">'."\n";
                if($valueid > 0) {
                    print '<input type="hidden" name="valueid" value="' . $valueid .'">'."\n";
                }
                    
		print dol_fiche_head();

		?>

		<table class="border" style="width: 100%">
			<?php if ($action == 'add') { ?>
			<!--  Variant -->
			<tr>
				<td class="titlefieldcreate fieldrequired"><label for="attribute"><?php echo $langs->trans('ProductAttribute') ?></label></td>
				<td>
				<?php
				if (is_array($prodattr_all))
				{
					print '<select class="flat minwidth100" id="attribute" name="attribute">';
					print '<option value="-1">&nbsp;</option>';
					foreach ($prodattr_all as $attr)
					{
						print '<option value="'.$attr->id.'">'.$attr->label.'</option>';
					}
					print '</select>';
				}

				$htmltext=$langs->trans("GoOnMenuToCreateVairants", $langs->transnoentities("Product"), $langs->transnoentities("VariantAttributes"));
				print $form->textwithpicto('', $htmltext);
				/*print ' &nbsp; &nbsp; <a href="'.DOL_URL_ROOT.'/variants/create.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=add&id='.$object->id).'">';
				print $langs->trans("Create");
				print '</a>';*/

				?>
				</td>
			</tr>
			<!-- Value -->
			<tr>
				<td class="fieldrequired"><label for="value"><?php echo $langs->trans('Value') ?></label></td>
				<td>
					<select class="flat minwidth100" id="value" name="value">
						<option value="-1">&nbsp;</option>
					</select>
					<?php
					$htmltext=$langs->trans("GoOnMenuToCreateVairants", $langs->transnoentities("Product"), $langs->transnoentities("VariantAttributes"));
					print $form->textwithpicto('', $htmltext);
					/*
						print ' &nbsp; &nbsp; <a href="'.DOL_URL_ROOT.'/variants/create.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=add&id='.$object->id).'">';
						print $langs->trans("Create");
						print '</a>';
					*/
					?>
				</td>
			</tr>
			<tr>
				<td></td><td>
					<input type="submit" class="button" name="selectvariant" id="selectvariant" value="<?php echo dol_escape_htmltag($langs->trans("SelectCombination")); ?>">
				</td>
			</tr>
		</table>
		<?php
		}

		if (is_array($productCombination2ValuePairs1)) {
		?>
		<hr>
		<table class="border" style="width: 100%">
			<tr>
				<td class="titlefieldcreate fieldrequired tdtop"><label for="features"><?php echo $langs->trans('Combination') ?></label></td>
				<td class="tdtop">
					<div class="inline-block valignmiddle quatrevingtpercent">
					<?php
					if (is_array($productCombination2ValuePairs1))
					{
                                            foreach ($productCombination2ValuePairs1 as $key => $val) {
                                            $result1 = $prodattr->fetch($val->fk_prod_attr);
                                            $result2 = $prodattr_val->fetch($val->fk_prod_attr_val);
                                                if ($result1 > 0 && $result2 > 0)
                                                {
                                                       print $prodattr->label . ' - '.$prodattr_val->value.'<br>';
                                                       // TODO Add delete link
                                                }
                                            }
					}
					?>
					</div>
					<!-- <div class="inline-block valignmiddle">
					<a href="#" class="inline-block valignmiddle button" id="delfeature"><?php echo img_edit_remove() ?></a>
					</div>-->
				</td>
				<td>
				</td>
			</tr>
			<tr>
				<td><label for="price_impact"><?php echo $langs->trans('PriceImpact') ?></label></td>
				<td><input type="text" id="price_impact" name="price_impact" value="<?php echo price($price_impact) ?>">
				<input type="checkbox" id="price_impact_percent" name="price_impact_percent" <?php echo $price_impact_percent ? ' checked' : '' ?>> <label for="price_impact_percent"><?php echo $langs->trans('PercentageVariation') ?></label></td>
			</tr>
			<tr>
				<td><label for="weight_impact"><?php echo $langs->trans('WeightImpact') ?></label></td>
				<td><input type="text" id="weight_impact" name="weight_impact" value="<?php echo price($weight_impact) ?>"></td>
			</tr>
		</table>
		<?php
		}

		print dol_fiche_end();
        ?>

		<div style="text-align: center">
		<input type="submit" name="create" <?php if (! is_array($productCombination2ValuePairs1)) print ' disabled="disabled"'; ?> value="<?php echo $action == 'add' ? $langs->trans('Create') : $langs->trans('Save') ?>" class="button">
		&nbsp;
		<input type="submit" name="cancel" value="<?php echo $langs->trans('Cancel'); ?>" class="button">
		</div>

		<?php

        print '</form>';
	}
	else
	{
		if ($action === 'delete') {

			if ($prodcomb->fetch($valueid) > 0) {
				$prodstatic->fetch($prodcomb->fk_product_child);

				print $form->formconfirm(
					"combinations.php?id=".$id."&valueid=".$valueid,
					$langs->trans('Delete'),
					$langs->trans('ProductCombinationDeleteDialog', $prodstatic->ref),
					"confirm_deletecombination",
					'',
					0,
					1
				);
			}
		} elseif ($action === 'copy') {

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

		if ($productCombinations)
		{
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

		<?php }

        // Buttons
		print '<div class="tabsAction">';

		print '	<div class="inline-block divButAction">';
		if ($productCombinations) {
		    print '<a href="combinations.php?id='.$object->id.'&action=copy" class="butAction">'.$langs->trans('PropagateVariant').'</a>';
		}

		print '<a href="combinations.php?id='.$object->id.'&action=add" class="butAction">'.$langs->trans('NewProductCombination').'</a>'; // NewVariant

		// Too much bugged page.
		/*
		print '<a href="generator.php?id='.$object->id.'" class="butAction">'.$langs->trans('ProductCombinationGenerator').'</a>';
		*/

		print '	</div>';

		print '</div>';



		$arrayofselected=is_array($toselect)?$toselect:array();


		// List of variants
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="massaction">';
		print '<input type="hidden" name="id" value="'.$id.'">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

		// List of mass actions available
		/*
		$arrayofmassactions =  array(
		    'presend'=>$langs->trans("SendByMail"),
		    'builddoc'=>$langs->trans("PDFMerge"),
		);
		if ($user->rights->product->supprimer) $arrayofmassactions['predelete']=$langs->trans("Delete");
		if (in_array($massaction, array('presend','predelete'))) $arrayofmassactions=array();
		$massactionbutton=$form->selectMassAction('', $arrayofmassactions);
		*/

		$aaa='';
		if (count($productCombinations))
		{
    		$aaa = '<label for="massaction">'.$langs->trans('BulkActions').'</label>';
    		$aaa .= '<select id="bulk_action" name="massaction" class="flat">';
    		$aaa .= '	<option value="nothing">&nbsp;</option>';
    		$aaa .= '	<option value="not_buy">'.$langs->trans('ProductStatusNotOnBuy').'</option>';
    		$aaa .= '	<option value="not_sell">'.$langs->trans('ProductStatusNotOnSell').'</option>';
    		$aaa .= '	<option value="on_buy">'.$langs->trans('ProductStatusOnBuy').'</option>';
    		$aaa .= '	<option value="on_sell">'.$langs->trans('ProductStatusOnSell').'</option>';
    		$aaa .= '	<option value="delete">'.$langs->trans('Delete').'</option>';
    		$aaa .= '</select>';
    		$aaa .= '<input type="submit" value="'.dol_escape_htmltag($langs->trans("Apply")).'" class="button">';
		}
		$massactionbutton = $aaa;

		$title = $langs->trans("ProductCombinations");

		print_barre_liste($title, 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, $aaa, 0);

		print '<div class="div-table-responsive">';
		?>
		<table class="liste">
			<tr class="liste_titre">
				<td class="liste_titre"><?php echo $langs->trans('Product') ?></td>
				<td class="liste_titre"><?php echo $langs->trans('Combination') ?></td>
				<td class="liste_titre right"><?php echo $langs->trans('PriceImpact') ?></td>
				<td class="liste_titre right"><?php echo $langs->trans('WeightImpact') ?></td>
				<td class="liste_titre center"><?php echo $langs->trans('OnSell') ?></td>
				<td class="liste_titre center"><?php echo $langs->trans('OnBuy') ?></td>
				<td class="liste_titre"></td>
        		<?php
        		print '<td class="liste_titre" align="middle">';
        		$searchpicto=$form->showCheckAddButtons('checkforselect', 1);
        		print $searchpicto;
        		print '</td>';
                ?>
			</tr>
			<?php

			if (count($productCombinations))
			{
    			foreach ($productCombinations as $currcomb)
    			{
    				$prodstatic->fetch($currcomb->fk_product_child);
    				?>
    				<tr class="oddeven">
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
    				<td class="right"><?php echo ($currcomb->variation_price >= 0 ? '+' : '').price($currcomb->variation_price).($currcomb->variation_price_percentage ? ' %' : '') ?></td>
    				<td class="right"><?php echo ($currcomb->variation_weight >= 0 ? '+' : '').price($currcomb->variation_weight).' '.measuring_units_string($prodstatic->weight_units, 'weight') ?></td>
    				<td style="text-align: center;"><?php echo $prodstatic->getLibStatut(2, 0) ?></td>
    				<td style="text-align: center;"><?php echo $prodstatic->getLibStatut(2, 1) ?></td>
    				<td class="right">
    					<a class="paddingleft paddingright" href="<?php echo dol_buildpath('/variants/combinations.php?id='.$id.'&action=edit&valueid='.$currcomb->id, 2) ?>"><?php echo img_edit() ?></a>
    					<a class="paddingleft paddingright" href="<?php echo dol_buildpath('/variants/combinations.php?id='.$id.'&action=delete&valueid='.$currcomb->id, 2) ?>"><?php echo img_delete() ?></a>
    				</td>
    				<?php
    				print '<td class="nowrap" align="center">';
    				if ($productCombinations || $massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
    				{
    				    $selected=0;
    				    if (in_array($prodstatic->id, $arrayofselected)) $selected=1;
    				    print '<input id="cb'.$prodstatic->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$prodstatic->id.'"'.($selected?' checked="checked"':'').'>';
    				}
    				print '</td>';
    				?>
    			</tr>
    			<?php
			    }
			}
			else
			{
			     print '<tr><td colspan="8"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
			}
			?>
		</table>

		<?php
		print '</div>';

		print '</form>';
	}
} else {
	llxHeader();
	// not found
}


llxFooter();

$db->close();
