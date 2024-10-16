<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015-2016	Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2018-2024	Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2018       Ferran Marcet       <fmarcet@2byte.es>
 * Copyright (C) 2019       Nicolas ZABOURI     <info@inovea-conseil.com>
 * Copyright (C) 2022       OpenDSI             <support@open-dsi.fr>
 * Copyright (C) 2022       Gauthier VERDOL     <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024       Alexandre Spangaro  <alexandre@inovea-conseil.com>
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
 *
 * Need to have the following variables defined:
 * $object (invoice, order, ...)
 * $conf
 * $langs
 * $dateSelector
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $senderissupplier (0 by default, 1 or 2 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error: this template page cannot be called directly as an URL";
	exit;
}

'@phan-var-force CommonObject $this
 @phan-var-force CommonObject $object';

$usemargins = 0;
if (isModEnabled('margin') && !empty($object->element) && in_array($object->element, array('facture', 'facturerec', 'propal', 'commande'))) {
	$usemargins = 1;
}
if (!isset($dateSelector)) {
	global $dateSelector; // Take global var only if not already defined into function calling (for example formAddObjectLine)
}
global $forceall, $forcetoshowtitlelines, $senderissupplier, $inputalsopricewithtax;
global $mysoc;

if (!isset($dateSelector)) {
	$dateSelector = 1; // For backward compatibility
} elseif (empty($dateSelector)) {
	$dateSelector = 0;
}
if (empty($forceall)) {
	$forceall = 0;
}
if (empty($senderissupplier)) {
	$senderissupplier = 0;
}
if (empty($inputalsopricewithtax)) {
	$inputalsopricewithtax = 0;
}
// Define colspan for the button 'Add'
$colspan = 3; // Columns: total ht + col edit + col delete
if (isModEnabled("multicurrency") && $this->multicurrency_code != $conf->currency) {
	$colspan++; //Add column for Total (currency) if required
}
if (in_array($object->element, array('propal', 'commande', 'order', 'facture', 'facturerec', 'invoice', 'supplier_proposal', 'order_supplier', 'invoice_supplier', 'invoice_supplier_rec'))) {
	$colspan++; // With this, there is a column move button
}
if (isModEnabled('asset') && $object->element == 'invoice_supplier') {
	$colspan++;
}

//print $object->element;
// Lines for extrafield
$objectline = null;
if (!empty($extrafields)) {
	if ($this->table_element_line == 'commandedet') {
		$objectline = new OrderLine($this->db);
	} elseif ($this->table_element_line == 'propaldet') {
		$objectline = new PropaleLigne($this->db);
	} elseif ($this->table_element_line == 'supplier_proposaldet') {
		$objectline = new SupplierProposalLine($this->db);
	} elseif ($this->table_element_line == 'facturedet') {
		$objectline = new FactureLigne($this->db);
	} elseif ($this->table_element_line == 'contratdet') {
		$objectline = new ContratLigne($this->db);
	} elseif ($this->table_element_line == 'commande_fournisseurdet') {
		$objectline = new CommandeFournisseurLigne($this->db);
	} elseif ($this->table_element_line == 'facture_fourn_det') {
		$objectline = new SupplierInvoiceLine($this->db);
	} elseif ($this->table_element_line == 'facturedet_rec') {
		$objectline = new FactureLigneRec($this->db);
	} elseif ($this->table_element_line == 'facture_fourn_det_rec') {
		$objectline = new FactureFournisseurLigneRec($this->db);
	}
}
print "<!-- BEGIN PHP TEMPLATE objectline_create.tpl.php -->\n";
$nolinesbefore = (count($this->lines) == 0 || $forcetoshowtitlelines);
if ($nolinesbefore) {
	?>
	<tr class="liste_titre<?php echo(($nolinesbefore || $object->element == 'contrat') ? '' : ' liste_titre_add_') ?> nodrag nodrop">
		<?php if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) { ?>
			<td class="linecolnum center"></td>
		<?php } ?>
		<td class="linecoldescription minwidth400imp">
			<div id="add"></div><span class="hideonsmartphone"><?php echo $langs->trans('AddNewLine'); ?></span>
		</td>
		<?php
		if ($object->element == 'supplier_proposal' || $object->element == 'order_supplier' || $object->element == 'invoice_supplier' || $object->element == 'invoice_supplier_rec') {	// We must have same test in printObjectLines
			?>
			<td class="linecolrefsupplier"><span id="title_fourn_ref"><?php echo $langs->trans('SupplierRef'); ?></span></td>
			<?php
		} ?>
		<td class="linecolvat right"><span id="title_vat"><?php echo $langs->trans('VAT'); ?></span></td>
		<td class="linecoluht right"><span id="title_up_ht"><?php echo $langs->trans('PriceUHT'); ?></span></td>
		<?php if (isModEnabled("multicurrency") && $this->multicurrency_code != $conf->currency) { ?>
			<td class="linecoluht_currency right"><span id="title_up_ht_currency"><?php echo $langs->trans('PriceUHTCurrency'); ?></span></td>
		<?php } ?>
		<?php if (!empty($inputalsopricewithtax) && !getDolGlobalInt('MAIN_NO_INPUT_PRICE_WITH_TAX')) { ?>
			<td class="linecoluttc right"><span id="title_up_ttc"><?php echo $langs->trans('PriceUTTC'); ?></span></td>
		<?php } ?>
		<td class="linecolqty right"><?php echo $langs->trans('Qty'); ?></td>
		<?php
		if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
			print '<td class="linecoluseunit left">';
			print '<span id="title_units">';
			print $langs->trans('Unit');
			print '</span></td>';
		} ?>
		<td class="linecoldiscount right"><?php echo $langs->trans('ReductionShort'); ?></td>
		<?php
		// Fields for situation invoice
		if (isset($this->situation_cycle_ref) && $this->situation_cycle_ref) {
			print '<td class="linecolcycleref right">'.$langs->trans('Progress').'</td>';
			if (getDolGlobalInt('INVOICE_USE_SITUATION') == 2) {
				print '<td class="nobottom nowrap right"></td>';
			}
			print '<td class="linecolcycleref2 right"></td>';
		}
		if (!empty($usemargins)) {
			if (!$user->hasRight('margins', 'creer')) {
				$colspan++;
			} else {
				print '<td class="margininfos linecolmargin1 right">';
				if (getDolGlobalString('MARGIN_TYPE') == "1") {
					echo $langs->trans('BuyingPrice');
				} else {
					echo $langs->trans('CostPrice');
				}
				echo '</td>';
				if (getDolGlobalString('DISPLAY_MARGIN_RATES')) {
					echo '<td class="margininfos linecolmargin2 right"><span class="np_marginRate">'.$langs->trans('MarginRate').'</span></td>';
				}
				if (getDolGlobalString('DISPLAY_MARK_RATES')) {
					echo '<td class="margininfos linecolmargin2 right"><span class="np_markRate">'.$langs->trans('MarkRate').'</span></td>';
				}
			}
		} ?>
		<td class="linecoledit" colspan="<?php echo $colspan; ?>">&nbsp;</td>
	</tr>
	<?php
}
?>
<tr class="pair nodrag nodrop nohoverpair<?php echo ($nolinesbefore || $object->element == 'contrat') ? '' : ' liste_titre_create'; ?>">
	<?php
	$coldisplay = 0;
	// Adds a line numbering column
	if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
		$coldisplay++;
		echo '<td class="nobottom linecolnum center"></td>';
	}
	$coldisplay++;
	?>
	<td class="nobottom linecoldescription minwidth400imp">
		<?php
		$freelines = false;
		if (!getDolGlobalString('MAIN_DISABLE_FREE_LINES')) {
			$freelines = true;
			$forceall = 1; // We always force all type for free lines (module product or service means we use predefined product or service)
			if ($object->element == 'contrat') {
				if (!isModEnabled('product') && !isModEnabled('service') && !getDolGlobalString('CONTRACT_SUPPORT_PRODUCTS')) {
					$forceall = -1; // With contract, by default, no choice at all, except if CONTRACT_SUPPORT_PRODUCTS is set
				} elseif (!getDolGlobalString('CONTRACT_SUPPORT_PRODUCTS')) {
					$forceall = 3;
				}
			}
			// Free line
			echo '<span class="prod_entry_mode_free">';
			// Show radio free line
			if ($forceall >= 0 && (isModEnabled("product") || isModEnabled("service"))) {
				echo '<label for="prod_entry_mode_free">';
				echo '<input type="radio" class="prod_entry_mode_free" name="prod_entry_mode" id="prod_entry_mode_free" value="free"';
				//echo (GETPOST('prod_entry_mode')=='free' ? ' checked' : ((empty($forceall) && (!isModEnabled('product') || !isModEnabled('service')))?' checked':'') );
				echo((GETPOST('prod_entry_mode', 'alpha') == 'free' || getDolGlobalString('MAIN_FREE_PRODUCT_CHECKED_BY_DEFAULT')) ? ' checked' : '');
				echo '> ';
				// Show type selector
				echo '<span class="textradioforitem">'.$langs->trans("FreeLineOfType").'</span>';
				echo '</label>';
				echo ' ';
			} else {
				echo '<input type="hidden" id="prod_entry_mode_free" name="prod_entry_mode" value="free">';
				// Show type selector
				if ($forceall >= 0) {
					if (!isModEnabled('product') || !isModEnabled('service')) {
						echo $langs->trans("Type");
					} else {
						echo $langs->trans("FreeLineOfType");
					}
					echo ' ';
				}
			}
			$form->select_type_of_lines(GETPOSTISSET("type") ? GETPOST("type", 'alpha', 2) : -1, 'type', 1, 1, $forceall, '');
			echo '</span>';
		}
		// Predefined product/service
		if (isModEnabled("product") || isModEnabled("service")) {
			if ($forceall >= 0 && $freelines) {
				echo '<br><span class="prod_entry_mode_predef paddingtop">';
			} else {
				echo '<span class="prod_entry_mode_predef">';
			}
			echo '<label for="prod_entry_mode_predef">';
			echo '<input type="radio" class="prod_entry_mode_predef" name="prod_entry_mode" id="prod_entry_mode_predef" value="predef"'.(GETPOST('prod_entry_mode') == 'predef' ? ' checked' : '').'> ';
			$labelforradio = '';
			if (empty($conf->dol_optimize_smallscreen)) {
				if (isModEnabled("product") && !isModEnabled('service')) {
					$labelforradio = $langs->trans('PredefinedProducts');
				} elseif ((!isModEnabled('product') && isModEnabled('service')) || ($object->element == 'contrat' && !getDolGlobalString('CONTRACT_SUPPORT_PRODUCTS'))) {
					$labelforradio = $langs->trans('PredefinedServices');
				} else {
					$labelforradio = $langs->trans('PredefinedProductsAndServices');
				}
			} else {
				$labelforradio = $langs->trans('PredefinedItem');
			}
			print '<span class="textradioforitem">'.$labelforradio.'</span>';
			echo '</label>';
			echo ' ';
			$filtertype = '';
			if (!empty($object->element) && $object->element == 'contrat' && !getDolGlobalString('CONTRACT_SUPPORT_PRODUCTS')) {
				$filtertype = '1';
			}
			if (empty($senderissupplier)) {
				$statustoshow = 1;
				$statuswarehouse = 'warehouseopen,warehouseinternal';
				if (getDolGlobalString('ENTREPOT_WAREHOUSEINTERNAL_NOT_SELL')) {
					$statuswarehouse = 'warehouseopen';
				}
				if (getDolGlobalString('ENTREPOT_EXTRA_STATUS')) {
					// hide products in closed warehouse, but show products for internal transfer
					$form->select_produits(GETPOST('idprod'), 'idprod', $filtertype, getDolGlobalInt('PRODUIT_LIMIT_SIZE'), $buyer->price_level, $statustoshow, 2, '', 1, array(), $buyer->id, '1', 0, 'maxwidth500 widthcentpercentminusx', 0, $statuswarehouse, GETPOST('combinations', 'array'));
				} else {
					$form->select_produits(GETPOST('idprod'), 'idprod', $filtertype, getDolGlobalInt('PRODUIT_LIMIT_SIZE'), $buyer->price_level, $statustoshow, 2, '', 1, array(), $buyer->id, '1', 0, 'maxwidth500 widthcentpercentminusx', 0, '', GETPOST('combinations', 'array'));
				}
				if (getDolGlobalString('MAIN_AUTO_OPEN_SELECT2_ON_FOCUS_FOR_CUSTOMER_PRODUCTS')) {
					?>
				<script>
					$(document).ready(function(){
						// On first focus on a select2 combo, auto open the menu (this allow to use the keyboard only)
						$(document).on('focus', '.select2-selection.select2-selection--single', function (e) {
							console.log('focus on a select2');
							if ($(this).attr('aria-labelledby') == 'select2-idprod-container')
							{
								console.log('open combo');
								$('#idprod').select2('open');
							}
						});
					});
				</script>
					<?php
				}
			} else {
				// $senderissupplier=2 is the same as 1 but disables test on minimum qty, disable autofill qty with minimum and autofill unit price
				if ($senderissupplier != 2) {
					$ajaxoptions = array(
						'update' => array('qty' => 'qty', 'remise_percent' => 'discount', 'idprod' => 'idprod'), // html id tags that will be edited with each ajax json response key
						'option_disabled' => 'idthatdoesnotexists', // html id to disable once select is done
						'warning' => $langs->trans("NoPriceDefinedForThisSupplier") // translation of an error saved into var 'warning' (for example shown we select a disabled option into combo)
					);
					$alsoproductwithnosupplierprice = 0;
				} else {
					$ajaxoptions = array(
						// Disabled: This is useless because setting discount and price_ht after a selection is already managed
						// by this page itself with a .change on the combolist '#idprodfournprice'
						//'update' => array('remise_percent' => 'discount', 'price_ht' => 'price_ht')			// html id tags that will be edited with each ajax json response key
					);
					$alsoproductwithnosupplierprice = 1;
				}
				$form->select_produits_fournisseurs($object->socid, GETPOST('idprodfournprice'), 'idprodfournprice', '', '', $ajaxoptions, 1, $alsoproductwithnosupplierprice, 'minwidth100 maxwidth500 widthcentpercentminusx');
				if (getDolGlobalString('MAIN_AUTO_OPEN_SELECT2_ON_FOCUS_FOR_SUPPLIER_PRODUCTS')) {
					?>
				<script>
					$(document).ready(function(){
						// On first focus on a select2 combo, auto open the menu (this allow to use the keyboard only)
						$(document).on('focus', '.select2-selection.select2-selection--single', function (e) {
							//console.log('focus on a select2');
							if ($(this).attr('aria-labelledby') == 'select2-idprodfournprice-container')
							{
								$('#idprodfournprice').select2('open');
							}
						});
					});
				</script>
					<?php
				}
			}

			$parentId = GETPOSTINT('parentId');

			$addproducton = (isModEnabled('product') && $user->hasRight('produit', 'creer'));
			$addserviceon = (isModEnabled('service') && $user->hasRight('service', 'creer'));
			if ($addproducton || $addserviceon) {
				if ($addproducton && $addserviceon) {
					echo '<div id="dropdownAddProductAndService" class="dropdown inline-block">';
					echo '<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" id="dropdownAddProductAndServiceLink" aria-haspopup="true" aria-expanded="false">';
					echo '<span class="fa fa-plus-circle valignmiddle paddingleft"></span>';
					echo '</a>';
					echo '<div class="dropdown-menu" aria-labelledby="dropdownAddProductAndServiceLink" style="top:auto; left:auto;">';
					echo '<a class="dropdown-item" href="'.DOL_URL_ROOT.'/product/card.php?action=create&type=0&backtopage='.urlencode($_SERVER["PHP_SELF"].'?id='.$object->id).'"> '.$langs->trans("NewProduct").'</a>';
					echo '<a class="dropdown-item" href="'.DOL_URL_ROOT.'/product/card.php?action=create&type=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?id='.$object->id).'"> '.$langs->trans("NewService").'</a>';
					echo '</div>';
					echo '</div>';
				} else {
					if ($addproducton) {
						$url = '/product/card.php?leftmenu=product&action=create&type=0&backtopage='.urlencode($_SERVER["PHP_SELF"]);
						$newbutton = '<span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("NewProduct").'"></span>';
						if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
							// @FIXME Not working yet
							$tmpbacktopagejsfields = 'addproduct:id,search_id';
							// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
							print dolButtonToOpenUrlInDialogPopup('addproduct', $langs->transnoentitiesnoconv('AddProduct'), $newbutton, $url, '', '', $tmpbacktopagejsfields);
						} else {
							print '<a href="'.DOL_URL_ROOT.'/product/card.php?action=create&type=0&backtopage='.urlencode($_SERVER["PHP_SELF"].'?id='.$object->id).'" title="'.dol_escape_htmltag($langs->trans("NewProduct")).'"><span class="fa fa-plus-circle valignmiddle paddingleft"></span></a>';
						}
					}
					if ($addserviceon) {
						$url = '/product/card.php?leftmenu=product&action=create&type=1&backtopage='.urlencode($_SERVER["PHP_SELF"]);
						$newbutton = '<span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("NewService").'"></span>';
						if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
							// @FIXME Not working yet
							$tmpbacktopagejsfields = 'addproduct:id,search_id';
							// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
							print dolButtonToOpenUrlInDialogPopup('addproduct', $langs->transnoentitiesnoconv('AddService'), $newbutton, $url, '', '', $tmpbacktopagejsfields);
						} else {
							print '<a href="'.DOL_URL_ROOT.'/product/card.php?action=create&type=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?id='.$object->id).'" title="'.dol_escape_htmltag($langs->trans("NewService")).'"><span class="fa fa-plus-circle valignmiddle paddingleft"></span></a>';
						}
					}
				}
			} ?>
			<script>
				$(document).ready(function(){
					$("#dropdownAddProductAndService .dropdown-toggle").on("click", function(event) {
						console.log("toggle addproduct dropdown");
						event.preventDefault();
						$("#dropdownAddProductAndService").toggleClass("open");
					});
				});
			</script>
			<?php

			echo '<input type="hidden" name="pbq" id="pbq" value="">';
			echo '</span>';
		}

		if (getDolGlobalString('MAIN_ADD_LINE_AT_POSITION')) {
			echo '<br>'.$langs->trans('AddLineOnPosition').' : <input type="number" name="rank" step="1" min="0" style="width: 5em;">';
		}

		if (is_object($hookmanager) && empty($senderissupplier)) {
			$parameters = array('fk_parent_line' => GETPOSTINT('fk_parent_line'));
			$reshook = $hookmanager->executeHooks('formCreateProductOptions', $parameters, $object, $action);
			if (!empty($hookmanager->resPrint)) {
				print $hookmanager->resPrint;
			}
		}
		if (is_object($hookmanager) && !empty($senderissupplier)) {
			$parameters = array('htmlname' => 'addproduct');
			$reshook = $hookmanager->executeHooks('formCreateProductSupplierOptions', $parameters, $object, $action);
			if (!empty($hookmanager->resPrint)) {
				print $hookmanager->resPrint;
			}
		}
		if (isModEnabled("product") || isModEnabled("service")) {
			echo '<br>';
			if (isModEnabled('variants')) {
				echo '<div id="attributes_box"></div>';
			}
		}
		// Editor wysiwyg
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$nbrows = ROWS_2;
		$enabled = getDolGlobalString('FCKEDITOR_ENABLE_DETAILS', '0');
		if (getDolGlobalString('MAIN_INPUT_DESC_HEIGHT')) {
			$nbrows = getDolGlobalString('MAIN_INPUT_DESC_HEIGHT');
		}
		$toolbarname = 'dolibarr_details';
		if (getDolGlobalString('FCKEDITOR_ENABLE_DETAILS_FULL')) {
			$toolbarname = 'dolibarr_notes';
		}
		$doleditor = new DolEditor('dp_desc', GETPOST('dp_desc', 'restricthtml'), '', getDolGlobalInt('MAIN_DOLEDITOR_HEIGHT', 100), $toolbarname, '', false, true, $enabled, $nbrows, '98%');
		$doleditor->Create();
		// Show autofill date for recurring invoices
		if (isModEnabled("service") && ($object->element == 'facturerec' || $object->element == 'invoice_supplier_rec')) {
			echo '<div class="divlinefordates"><br>';
			echo $langs->trans('AutoFillDateFrom').' ';
			if (getDolGlobalString('INVOICE_REC_DATE_TO_YES')) {
				$line->date_start_fill = 1;
				$line->date_end_fill = 1;
			}
			echo $form->selectyesno('date_start_fill', $line->date_start_fill, 1);
			echo ' - ';
			echo $langs->trans('AutoFillDateTo').' ';
			echo $form->selectyesno('date_end_fill', $line->date_end_fill, 1);
			echo '</div>';
		}
		if (is_object($objectline)) {
			$temps = $objectline->showOptionals($extrafields, 'create', array(), '', '', 1, 'line');

			if (!empty($temps)) {
				print '<div style="padding-top: 10px" id="extrafield_lines_area_create" name="extrafield_lines_area_create">';
				print $temps;
				print '</div>';
			}
		}
		echo '</td>';
		if ($object->element == 'supplier_proposal' || $object->element == 'order_supplier' || $object->element == 'invoice_supplier' || $object->element == 'invoice_supplier_rec') {	// We must have same test in printObjectLines
			$coldisplay++; ?>
	<td class="nobottom linecolrefsupplier"><input id="fourn_ref" name="fourn_ref" class="flat minwidth50 maxwidth100 maxwidth125onsmartphone" value="<?php echo(GETPOSTISSET("fourn_ref") ? GETPOST("fourn_ref", 'alpha', 2) : ''); ?>"></td>
					<?php
		}
		print '<td class="nobottom linecolvat right">';
		$coldisplay++;
		if ($object->element == 'propal' || $object->element == 'commande' || $object->element == 'facture' || $object->element == 'facturerec') {
			$type_tva = 1;
		} elseif ($object->element == 'supplier_proposal' || $object->element == 'order_supplier' || $object->element == 'invoice_supplier' || $object->element == 'invoice_supplier_rec') {
			$type_tva = 2;
		}
		if ($seller->tva_assuj == "0") {
			echo '<input type="hidden" name="tva_tx" id="tva_tx" value="0">'.vatrate('0', true);
		} else {
			echo $form->load_tva('tva_tx', (GETPOSTISSET("tva_tx") ? GETPOST("tva_tx", 'alpha', 2) : -1), $seller, $buyer, 0, 0, '', false, 1, $type_tva);
		}
		?>
	</td>

	<td class="nobottom linecoluht right"><?php $coldisplay++; ?>
		<input type="text" name="price_ht" id="price_ht" class="flat right width50" value="<?php echo(GETPOSTISSET("price_ht") ? GETPOST("price_ht", 'alpha', 2) : ''); ?>">
	</td>

	<?php
	if (isModEnabled("multicurrency") && $this->multicurrency_code != $conf->currency) {
		$coldisplay++; ?>
		<td class="nobottom linecoluht_currency right">
			<input type="text" name="multicurrency_price_ht" id="multicurrency_price_ht" class="flat right width50" value="<?php echo(GETPOSTISSET("multicurrency_price_ht") ? GETPOST("multicurrency_price_ht", 'alpha', 2) : ''); ?>">
		</td>
		<?php
	}
	if (!empty($inputalsopricewithtax) && !getDolGlobalInt('MAIN_NO_INPUT_PRICE_WITH_TAX')) {
		$coldisplay++; ?>
		<td class="nobottom linecoluttc right">
			<input type="text" name="price_ttc" id="price_ttc" class="flat right width50" value="<?php echo(GETPOSTISSET("price_ttc") ? GETPOST("price_ttc", 'alpha', 2) : ''); ?>">
		</td>
			<?php
	}
	$coldisplay++;
	?>
	<td class="nobottom linecolqty right">
	<?php $default_qty = (!getDolGlobalString('MAIN_OBJECTLINE_CREATE_EMPTY_QTY_BY_DEFAULT') ? 1 : ''); ?>
	<input type="text" name="qty" id="qty" class="flat width40 right" value="<?php echo(GETPOSTISSET("qty") ? GETPOST("qty", 'alpha', 2) : $default_qty); ?>">
	</td>
	<?php
	if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
		$coldisplay++;
		print '<td class="nobottom linecoluseunit left">';
		print $form->selectUnits(empty($line->fk_unit) ? $conf->global->PRODUCT_USE_UNITS : $line->fk_unit, "units");
		print '</td>';
	}
	$remise_percent = $buyer->remise_percent;
	if ($object->element == 'supplier_proposal' || $object->element == 'order_supplier' || $object->element == 'invoice_supplier') {
		$remise_percent = $seller->remise_supplier_percent;
	}
	$coldisplay++;
	?>

	<td class="nobottom nowrap linecoldiscount right"><input type="text" name="remise_percent" id="remise_percent" class="flat width40 right" value="<?php echo(GETPOSTISSET("remise_percent") ? GETPOST("remise_percent", 'alpha', 2) : ($remise_percent ? $remise_percent : '')); ?>"><span class="opacitymedium hideonsmartphone">%</span></td>
	<?php
	if (isset($this->situation_cycle_ref) && $this->situation_cycle_ref) {
		$coldisplay++;
		print '<td class="nobottom nowrap right"><input class="flat right" type="text" size="1" value="" name="progress"><span class="opacitymedium hideonsmartphone">%</span></td>';
		if (getDolGlobalInt('INVOICE_USE_SITUATION') == 2) {
			$coldisplay++;
			print '<td class="nobottom nowrap right"></td>';
		}
		$coldisplay++;
		print '<td></td>';
	}
	if (!empty($usemargins)) {
		if ($user->hasRight('margins', 'creer')) {
			$coldisplay++; ?>
			<td class="nobottom margininfos linecolmargin right">
				<!-- For predef product -->
					<?php if (isModEnabled("product") || isModEnabled("service")) { ?>
					<select id="fournprice_predef" name="fournprice_predef" class="flat minwidth75imp maxwidth150" style="display: none;"></select>
					<?php } ?>
				<!-- For free product -->
				<input type="text" id="buying_price" name="buying_price" class="flat maxwidth75 right" value="<?php echo(GETPOSTISSET("buying_price") ? GETPOST("buying_price", 'alpha', 2) : ''); ?>">
			</td>
				<?php
				if (getDolGlobalString('DISPLAY_MARGIN_RATES')) {
					echo '<td class="nobottom nowraponall margininfos right"><input class="flat right width40" type="text" id="np_marginRate" name="np_marginRate" value="'.(GETPOSTISSET("np_marginRate") ? GETPOST("np_marginRate", 'alpha', 2) : '').'"><span class="np_marginRate opacitymedium hideonsmartphone">%</span></td>';
					$coldisplay++;
				}
				if (getDolGlobalString('DISPLAY_MARK_RATES')) {
					echo '<td class="nobottom nowraponall margininfos right"><input class="flat right width40" type="text" id="np_markRate" name="np_markRate" value="'.(GETPOSTISSET("np_markRate") ? GETPOST("np_markRate", 'alpha', 2) : '').'"><span class="np_markRate opacitymedium hideonsmartphone">%</span></td>';
					$coldisplay++;
				}
		}
	}
	$coldisplay += $colspan;
	?>
	<td class="nobottom linecoledit center valignmiddle" colspan="<?php echo $colspan; ?>">
		<input type="submit" class="button reposition" value="<?php echo $langs->trans('Add'); ?>" name="addline" id="addline">
	</td>
</tr>

<?php
if ((isModEnabled("service") || ($object->element == 'contrat')) && $dateSelector && GETPOST('type') != '0') {	// We show date field if required
	print '<tr id="trlinefordates" class="oddeven">'."\n";
	if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
		print '<td></td>';
	}
	print '<td colspan="'.($coldisplay - (!getDolGlobalString('MAIN_VIEW_LINE_NUMBER') ? 0 : 1)).'">';
	$date_start = dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), 0, GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
	$date_end = dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), 0, GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));

	$prefillDates = false;

	if (getDolGlobalString('MAIN_FILL_SERVICE_DATES_FROM_LAST_SERVICE_LINE') && !empty($object->lines)) {
		for ($i = count($object->lines) - 1; $i >= 0; $i--) {
			$lastline = $object->lines[$i];

			if ($lastline->product_type == Product::TYPE_SERVICE && (!empty($lastline->date_start) || !empty($lastline->date_end))) {
				$date_start_prefill = $lastline->date_start;
				$date_end_prefill = $lastline->date_end;

				$prefillDates = true;
				break;
			}
		}
	}

	if (!empty($object->element) && $object->element == 'contrat') {
		print $langs->trans("DateStartPlanned").' ';
		print $form->selectDate($date_start, "date_start", $usehm, $usehm, 1, "addproduct");
		print ' &nbsp; '.$langs->trans("DateEndPlanned").' ';
		print $form->selectDate($date_end, "date_end", $usehm, $usehm, 1, "addproduct");
	} else {
		print $langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' ';
		print $form->selectDate($date_start, 'date_start', !getDolGlobalString('MAIN_USE_HOURMIN_IN_DATE_RANGE') ? 0 : 1, !getDolGlobalString('MAIN_USE_HOURMIN_IN_DATE_RANGE') ? 0 : 1, 1, "addproduct", 1, 0);
		print ' '.$langs->trans('to').' ';
		print $form->selectDate($date_end, 'date_end', !getDolGlobalString('MAIN_USE_HOURMIN_IN_DATE_RANGE') ? 0 : 1, !getDolGlobalString('MAIN_USE_HOURMIN_IN_DATE_RANGE') ? 0 : 1, 1, "addproduct", 1, 0);
	}

	if ($prefillDates) {
		echo ' <span class="small"><a href="#" id="prefill_service_dates">'.$langs->trans('FillWithLastServiceDates').'</a></span>';
	}

	print '<script>';

	if ($prefillDates) {
		?>
		function prefill_service_dates()
		{
			$('#date_start').val("<?php echo dol_escape_js(dol_print_date($date_start_prefill, 'day')); ?>").trigger('change');
			$('#date_end').val("<?php echo dol_escape_js(dol_print_date($date_end_prefill, 'day')); ?>").trigger('change');

			return false; // Prevent default link behaviour (which is go to href URL)
		}

		$(document).ready(function()
		{
			$('#prefill_service_dates').click(prefill_service_dates);
		});

		<?php
	}

	if (!$date_start) {
		if (isset($conf->global->MAIN_DEFAULT_DATE_START_HOUR)) {
			print 'jQuery("#date_starthour").val("' . getDolGlobalString('MAIN_DEFAULT_DATE_START_HOUR').'");';
		}
		if (isset($conf->global->MAIN_DEFAULT_DATE_START_MIN)) {
			print 'jQuery("#date_startmin").val("' . getDolGlobalString('MAIN_DEFAULT_DATE_START_MIN').'");';
		}
	}
	if (!$date_end) {
		if (isset($conf->global->MAIN_DEFAULT_DATE_END_HOUR)) {
			print 'jQuery("#date_endhour").val("' . getDolGlobalString('MAIN_DEFAULT_DATE_END_HOUR').'");';
		}
		if (isset($conf->global->MAIN_DEFAULT_DATE_END_MIN)) {
			print 'jQuery("#date_endmin").val("' . getDolGlobalString('MAIN_DEFAULT_DATE_END_MIN').'");';
		}
	}
	print '</script>';
	print '</td>';
	print '</tr>'."\n";
}


print "<script>\n";
if (!empty($usemargins) && $user->hasRight('margins', 'creer')) {
	?>
	/* Some js test when we click on button "Add" */
	jQuery(document).ready(function() {
	<?php
	if (getDolGlobalString('DISPLAY_MARGIN_RATES')) { ?>
		$("input[name='np_marginRate']:first").blur(function(e) {
			return checkFreeLine(e, "np_marginRate");
		});
		<?php
	}
	if (getDolGlobalString('DISPLAY_MARK_RATES')) { ?>
		$("input[name='np_markRate']:first").blur(function(e) {
			return checkFreeLine(e, "np_markRate");
		});
		<?php
	} ?>
	});

	/* TODO This does not work for number with thousand separator that is , */
	function checkFreeLine(e, npRate)
	{
		var buying_price = $("input[name='buying_price']:first");
		var remise = $("input[name='remise_percent']:first");

		var rate = $("input[name='"+npRate+"']:first");
		if (rate.val() == '')
			return true;

		if (! $.isNumeric(rate.val().replace(',','.')))
		{
			alert('<?php echo dol_escape_js($langs->trans("rateMustBeNumeric")); ?>');
			e.stopPropagation();
			setTimeout(function () { rate.focus() }, 50);
			return false;
		}
		if (npRate == "np_markRate" && rate.val() >= 100)
		{
			alert('<?php echo dol_escape_js($langs->trans("markRateShouldBeLesserThan100")); ?>');
			e.stopPropagation();
			setTimeout(function () { rate.focus() }, 50);
			return false;
		}

		var price = 0;
		remisejs=price2numjs(remise.val());

		if (remisejs != 100)	// If a discount not 100 or no discount
		{
			if (remisejs == '') remisejs=0;

			bpjs=price2numjs(buying_price.val());
			ratejs=price2numjs(rate.val());

			if (npRate == "np_marginRate")
				price = ((bpjs * (1 + ratejs / 100)) / (1 - remisejs / 100));
			else if (npRate == "np_markRate")
				price = ((bpjs / (1 - ratejs / 100)) / (1 - remisejs / 100));
		}

		$("input[name='price_ht']:first").val(price);	// TODO Must use a function like php price to have here a formatted value

		return true;
	}

	<?php
}
?>

	/* JQuery for product free or predefined select */
	jQuery(document).ready(function() {
		jQuery("#price_ht").keyup(function(event) {
			// console.log(event.which);		// discard event tag and arrows
			if (event.which != 9 && (event.which < 37 ||event.which > 40) && jQuery("#price_ht").val() != '') {
			jQuery("#price_ttc").val('');
			jQuery("#multicurrency_subprice").val('');
			jQuery("#multicurrency_price_ht").val('');
		}
	});
	jQuery("#price_ttc").keyup(function(event) {
		// console.log(event.which);		// discard event tag and arrows
		if (event.which != 9 && (event.which < 37 || event.which > 40) && jQuery("#price_ttc").val() != '') {
			jQuery("#price_ht").val('');
			jQuery("#multicurrency_subprice").val('');
			jQuery("#multicurrency_price_ht").val('');
		}
	});
	jQuery("#multicurrency_subprice").keyup(function(event) {
		// console.log(event.which);		// discard event tag and arrows
		if (event.which != 9 && (event.which < 37 || event.which > 40) && jQuery("#multicurrency_subprice").val() != '') {
			jQuery("#price_ht").val('');
			jQuery("#price_ttc").val('');
		}
	});
	jQuery("#multicurrency_price_ht").keyup(function(event) {
		// console.log(event.which);		// discard event tag and arrows
		if (event.which != 9 && (event.which < 37 || event.which > 40) && jQuery("#multicurrency_price_ht").val() != '') {
			jQuery("#price_ht").val('');
			jQuery("#price_ttc").val('');
		}
	});

	$("#prod_entry_mode_free").on( "click", function() {
		setforfree();
	});
	$("#select_type").change(function()
	{
		setforfree();

		if (jQuery('#select_type').val() >= 0) {
			console.log("Set focus on description field");
			/* this focus code works on a standard textarea but not if field was replaced with CKEDITOR */
			jQuery('#dp_desc').focus();
			/* this focus code works for CKEDITOR */
			if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined") {
				var editor = CKEDITOR.instances['dp_desc'];
				if (editor) {
					editor.focus();
				}
			}
		}

		console.log("Hide/show date according to product type");
		if (jQuery('#select_type').val() == '0')
		{
			jQuery('#trlinefordates').hide();
			jQuery('.divlinefordates').hide();
		}
		else
		{
			jQuery('#trlinefordates').show();
			jQuery('.divlinefordates').show();
		}
	});

	$("#prod_entry_mode_predef").on( "click", function() {
		console.log("click prod_entry_mode_predef");
		setforpredef();
		jQuery('#trlinefordates').show();
	});

	<?php
	if (!$freelines) { ?>
		$("#prod_entry_mode_predef").click();
		<?php
	}

	if (in_array($this->table_element_line, array('propaldet', 'commandedet', 'facturedet'))) { ?>
	$("#date_start, #date_end").focusout(function() {
		let type = $(this).attr('type');
		let mandatoryP = $(this).attr('mandatoryperiod');
		if (type == 1 && mandatoryP == 1) {
			if ($(this).val() == ''  && !$(this).hasClass('inputmandatory')) {
				$(this).addClass('inputmandatory');
			}else{
				$(this).removeClass('inputmandatory');
			}
		}
	});
			<?php
	} ?>
	/* When changing predefined product, we reload list of supplier prices required for margin combo */
	$("#idprod, #idprodfournprice").change(function()
	{
		console.log("objectline_create.tpl Call method change() after change on #idprod or #idprodfournprice (senderissupplier=<?php echo $senderissupplier; ?>). this.val = "+$(this).val());

		setforpredef();		// TODO Keep vat combo visible and set it to first entry into list that match result of get_default_tva(product)

		jQuery('#trlinefordates').show();

		<?php
		if (!getDolGlobalString('MAIN_DISABLE_EDIT_PREDEF_PRICEHT') && empty($senderissupplier)) {
			?>
			var pbq = parseInt($('option:selected', this).attr('data-pbq'));	/* If product was selected with a HTML select */
			if (isNaN(pbq)) { pbq = jQuery('#idprod').attr('data-pbq'); } 		/* If product was selected with a HTML input with autocomplete */

			if ((jQuery('#idprod').val() > 0 || jQuery('#idprodfournprice').val()) && ! isNaN(pbq) && pbq > 0)
			{
				console.log("objectline_create.tpl We are in a price per qty context, we do not call ajax/product, init of fields is done few lines later");
			} else {
				<?php if (getDolGlobalString('PRODUIT_CUSTOMER_PRICES_BY_QTY') || getDolGlobalString('PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES')) { ?>
					if (isNaN(pbq)) { console.log("We use experimental option PRODUIT_CUSTOMER_PRICES_BY_QTY or PRODUIT_CUSTOMER_PRICES_BY_QTY but we could not get the id of pbq from product combo list, so load of price may be 0 if product has different prices"); }
				<?php } ?>
				// Get the price for the product and display it
				console.log("Load unit price and set it into #price_ht or #price_ttc for product id="+$(this).val()+" socid=<?php print $object->socid; ?>");
				$.post('<?php echo DOL_URL_ROOT; ?>/product/ajax/products.php?action=fetch',
					{ 'id': $(this).val(), 'socid': <?php print $object->socid; ?>, 'token': '<?php print currentToken(); ?>', 'addalsovatforthirdpartyid': 1 },
					function(data) {
						console.log("objectline_create.tpl Load unit price ends, we got value ht="+data.price_ht+" ttc="+data.price_ttc+" pricebasetype="+data.pricebasetype);

						$('#date_start').removeAttr('type');
						$('#date_end').removeAttr('type');
						$('#date_start').attr('type', data.type);
						$('#date_end').attr('type', data.type);

						$('#date_start').removeAttr('mandatoryperiod');
						$('#date_end').removeAttr('mandatoryperiod');
						$('#date_start').attr('mandatoryperiod', data.mandatory_period);
						$('#date_end').attr('mandatoryperiod', data.mandatory_period);

						// service and we set mandatory_period to true
						if (data.mandatory_period == 1 && data.type == 1) {
							jQuery('#date_start').addClass('inputmandatory');
							jQuery('#date_end').addClass('inputmandatory');
						} else {
							jQuery('#date_start').removeClass('inputmandatory');
							jQuery('#date_end').removeClass('inputmandatory');
						}

						if (<?php echo (int) $inputalsopricewithtax; ?> == 1 && data.pricebasetype == 'TTC' && <?php print getDolGlobalInt('MAIN_NO_INPUT_PRICE_WITH_TAX') ? 'false' : 'true'; ?>) {
							console.log("objectline_create.tpl set content of price_ttc");
							jQuery("#price_ttc").val(data.price_ttc);
						} else {
							console.log("objectline_create.tpl set content of price_ht");
							jQuery("#price_ht").val(data.price_ht);
						}

						// Set values for any fields in the form options_SOMETHING
						for (var key in data.array_options) {
							if (data.array_options.hasOwnProperty(key)) {
								var field = jQuery("#" + key);
								if(field.length > 0){
									console.log("objectline_create.tpl set content of options_" + key);
									field.val(data.array_options[key]);
								}
							}
						}

						var tva_tx = data.tva_tx;
						var default_vat_code = data.default_vat_code;

						// Now set the VAT
						var stringforvatrateselection = tva_tx;
						if (typeof default_vat_code != 'undefined' && default_vat_code != null && default_vat_code != '') {
							stringforvatrateselection = stringforvatrateselection+' ('+default_vat_code+')';
							<?php
							// Special case for India
							if (getDolGlobalString('MAIN_SALETAX_AUTOSWITCH_I_CS_FOR_INDIA')) {
								?>
								console.log("MAIN_SALETAX_AUTOSWITCH_I_CS_FOR_INDIA is on so we check if we need to autoswith the vat code");
								console.log("mysoc->country_code=<?php echo $mysoc->country_code; ?> thirdparty->country_code=<?php echo $object->thirdparty->country_code; ?>");
								new_default_vat_code = default_vat_code;
								<?php
								if ($mysoc->country_code == 'IN' && !empty($object->thirdparty) && $object->thirdparty->country_code == 'IN' && $mysoc->state_code == $object->thirdparty->state_code) {
									// We are in India and states are same, we revert the vat code "I-x" into "CS-x"
									?>
									console.log("Countries are both IN and states are same, so we revert I into CS in default_vat_code="+default_vat_code);
									new_default_vat_code = default_vat_code.replace(/^I\-/, 'C+S-');
									<?php
								} elseif ($mysoc->country_code == 'IN' && !empty($object->thirdparty) && $object->thirdparty->country_code == 'IN' && $mysoc->state_code != $object->thirdparty->state_code) {
									// We are in India and states differs, we revert the vat code "CS-x" into "I-x"
									?>
									console.log("Countries are both IN and states differs, so we revert CS into I in default_vat_code="+default_vat_code);
									new_default_vat_code = default_vat_code.replace(/^C\+S\-/, 'I-');
									<?php
								} ?>
								if (new_default_vat_code != default_vat_code && jQuery('#tva_tx option:contains("'+new_default_vat_code+'")').val()) {
									console.log("We found en entry into VAT with new default_vat_code, we will use it");
									stringforvatrateselection = jQuery('#tva_tx option:contains("'+new_default_vat_code+'")').val();
								}
								<?php
							} ?>
						}
						// Set vat rate if field is an input box
						$('#tva_tx').val(tva_tx);
						// Set vat rate by selecting the combo
						//$('#tva_tx option').val(tva_tx);	// This is bugged, it replaces the vat key of all options
						$('#tva_tx option').removeAttr('selected');
						console.log("stringforvatrateselection="+stringforvatrateselection+" -> value of option label for this key="+$('#tva_tx option[value="'+stringforvatrateselection+'"]').val());
						$('#tva_tx option[value="'+stringforvatrateselection+'"]').prop('selected', true);

							<?php
							if (getDolGlobalInt('PRODUIT_AUTOFILL_DESC') == 1) {
								if (getDolGlobalInt('MAIN_MULTILANGS') && getDolGlobalString('PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE')) { ?>
						var proddesc = data.desc_trans;
									<?php
								} else { ?>
						var proddesc = data.desc;
									<?php
								} ?>
						console.log("objectline_create.tpl Load description into text area : "+proddesc);
								<?php
								if (getDolGlobalString('FCKEDITOR_ENABLE_DETAILS')) { ?>
						if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined")
						{
							var editor = CKEDITOR.instances['dp_desc'];
							if (editor) {
								editor.setData(proddesc);
							}
						}
									<?php
								} else { ?>
						jQuery('#dp_desc').text(proddesc);
									<?php
								} ?>
								<?php
							} ?>
							<?php
							if (getDolGlobalString('PRODUCT_LOAD_EXTRAFIELD_INTO_OBJECTLINES')) { ?>
							jQuery.each(data.array_options, function( key, value ) {
								jQuery('div[class*="det'+key.replace('options_','_extras_')+'"] > #'+key).val(value);
							});
								<?php
							} ?>
					},
					'json'
				);
			}
				<?php
		}

		if (!empty($usemargins) && $user->hasRight('margins', 'creer')) {
			$langs->load('stocks'); ?>

			/* Code for margin */
			$("#fournprice_predef").find("option").remove();
			$("#fournprice_predef").hide();
			$("#buying_price").val("").show();

			/* Call post to load content of combo list fournprice_predef */
			var token = '<?php echo currentToken(); ?>';		// For AJAX Call we use old 'token' and not 'newtoken'
			$.post('<?php echo DOL_URL_ROOT; ?>/fourn/ajax/getSupplierPrices.php?bestpricefirst=1', { 'idprod': $(this).val(), 'token': token }, function(data) {
				if (data && data.length > 0)
				{
					var options = ''; var defaultkey = ''; var defaultprice = ''; var bestpricefound = 0;

					var bestpriceid = 0; var bestpricevalue = 0;
					var pmppriceid = 0; var pmppricevalue = 0;
					var costpriceid = 0; var costpricevalue = 0;

					/* setup of margin calculation */
					var defaultbuyprice = '<?php
					if (isset($conf->global->MARGIN_TYPE)) {
						if (getDolGlobalString('MARGIN_TYPE') == '1') {
							print 'bestsupplierprice';
						}
						if (getDolGlobalString('MARGIN_TYPE') == 'pmp') {
							print 'pmp';
						}
						if (getDolGlobalString('MARGIN_TYPE') == 'costprice') {
							print 'costprice';
						}
					} ?>';
					console.log("objectline_create.tpl we will set the field for margin. defaultbuyprice="+defaultbuyprice);

					var i = 0;
					$(data).each(function() {
						/* Warning: Lines must be processed in order: best supplier price, then pmpprice line then costprice */
						if (this.id != 'pmpprice' && this.id != 'costprice')
						{
							i++;
							this.price = parseFloat(this.price); // to fix when this.price >0
							// If margin is calculated on best supplier price, we set it by default (but only if value is not 0)
							//console.log("id="+this.id+"-price="+this.price+"-"+(this.price > 0));
							if (bestpricefound == 0 && this.price > 0) { defaultkey = this.id; defaultprice = this.price; bestpriceid = this.id; bestpricevalue = this.price; bestpricefound=1; }	// bestpricefound is used to take the first price > 0
						}
						if (this.id == 'pmpprice')
						{
							// If margin is calculated on PMP, we set it by default (but only if value is not 0)
							console.log("id="+this.id+"-price="+this.price);
							if ('pmp' == defaultbuyprice || 'costprice' == defaultbuyprice)
							{
								if (this.price > 0) {
									defaultkey = this.id; defaultprice = this.price; pmppriceid = this.id; pmppricevalue = this.price;
									//console.log("pmppricevalue="+pmppricevalue);
								}
							}
						}
						if (this.id == 'costprice')
						{
							// If margin is calculated on Cost price, we set it by default (but only if value is not 0)
							console.log("id="+this.id+"-price="+this.price+"-pmppricevalue="+pmppricevalue);
							if ('costprice' == defaultbuyprice)
							{
								if (this.price > 0) { defaultkey = this.id; defaultprice = this.price; costpriceid = this.id; costpricevalue = this.price; }
								else if (pmppricevalue > 0) { defaultkey = 'pmpprice'; defaultprice = pmppricevalue; }
							}
						}
						options += '<option value="'+this.id+'" price="'+this.price+'">'+this.label+'</option>';
					});
					options += '<option value="inputprice" price="'+defaultprice+'"><?php echo dol_escape_js($langs->trans("InputPrice").'...'); ?></option>';

					console.log("finally selected defaultkey="+defaultkey+" defaultprice for buying price="+defaultprice);

					$("#fournprice_predef").html(options).show();
					if (defaultkey != '')
					{
						$("#fournprice_predef").val(defaultkey);
					}

					/* At loading, no product are yet selected, so we hide field of buying_price */
					$("#buying_price").hide();

					/* Define default price at loading */
					var defaultprice = $("#fournprice_predef").find('option:selected').attr("price");
					$("#buying_price").val(defaultprice);

					$("#fournprice_predef").change(function() {
						console.log("change on fournprice_predef");
						/* Hide field buying_price according to choice into list (if 'inputprice' or not) */
						var linevalue=$(this).find('option:selected').val();
						var pricevalue = $(this).find('option:selected').attr("price");
						if (linevalue != 'inputprice' && linevalue != 'pmpprice') {
							$("#buying_price").val(pricevalue).hide();	/* We set value then hide field */
						}
						if (linevalue == 'inputprice') {
							$('#buying_price').show();
						}
						if (linevalue == 'pmpprice') {
							$("#buying_price").val(pricevalue);
							$('#buying_price').hide();
						}
					});
				}
			},
			'json');

					<?php
		}
		?>

		<?php
		if (getDolGlobalString('PRODUIT_CUSTOMER_PRICES_BY_QTY') || getDolGlobalString('PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES')) {
			?>
			/* To process customer price per quantity (PRODUIT_CUSTOMER_PRICES_BY_QTY works only if combo product is not an ajax after x key pressed) */
			var pbq = parseInt($('option:selected', this).attr('data-pbq'));				// When select is done from HTML select
			if (isNaN(pbq)) { pbq = jQuery('#idprod').attr('data-pbq');	}					// When select is done from HTML input with autocomplete
			var pbqup = parseFloat($('option:selected', this).attr('data-pbqup'));
			if (isNaN(pbqup)) { pbqup = jQuery('#idprod').attr('data-pbqup');	}
			var pbqbase = $('option:selected', this).attr('data-pbqbase');
			if (isNaN(pbqbase)) { pbqbase = jQuery('#idprod').attr('data-pbqbase');	}
			var pbqqty = parseFloat($('option:selected', this).attr('data-pbqqty'));
			if (isNaN(pbqqty)) { pbqqty = jQuery('#idprod').attr('data-pbqqty');	}
			var pbqpercent = parseFloat($('option:selected', this).attr('data-pbqpercent'));
			if (isNaN(pbqpercent)) { pbqpercent = jQuery('#idprod').attr('data-pbqpercent');	}

			if ((jQuery('#idprod').val() > 0) && ! isNaN(pbq) && pbq > 0)
			{
				var pbqupht = pbqup;	/* TODO support of price per qty TTC not yet available */

				console.log("We choose a price by quanty price_by_qty id = "+pbq+" price_by_qty upht = "+pbqupht+" price_by_qty qty = "+pbqqty+" price_by_qty percent = "+pbqpercent);
				jQuery("#pbq").val(pbq);
				jQuery("#price_ht").val(pbqupht);
				if (jQuery("#qty").val() < pbqqty)
				{
					jQuery("#qty").val(pbqqty);
				}
				if (jQuery("#remise_percent").val() < pbqpercent)
				{
					jQuery("#remise_percent").val(pbqpercent);
				}
			} else { jQuery("#pbq").val(''); }
			<?php
		}
		$reshook = $hookmanager->executeHooks('objectlineCreateSupplierOrderProductSelected', $parameters, $object, $action);

		?>


		// Deal with supplier ref price (idprodfournprice = int)
		if (jQuery('#idprodfournprice').val() > 0)
		{
			console.log("objectline_create.tpl #idprodfournprice is an ID > 0, so we set some properties into page");

			var up = parseFloat($('option:selected', this).attr('data-up')); 							// When select is done from HTML select
			if (isNaN(up)) { up = parseFloat(jQuery('#idprodfournprice').attr('data-up'));}				// When select is done from HTML input with ajax autocomplete

			var up_locale = $('option:selected', this).attr('data-up-locale');							// When select is done from HTML select
			if (typeof up_locale === 'undefined') { up_locale = jQuery('#idprodfournprice').attr('data-up-locale');}	// When select is done from HTML input with ajax autocomplete

			var qty = parseFloat($('option:selected', this).attr('data-qty'));
			if (isNaN(qty)) { qty = parseFloat(jQuery('#idprodfournprice').attr('data-qty'));}

			var discount = parseFloat($('option:selected', this).attr('data-discount'));
			if (isNaN(discount)) { discount = parseFloat(jQuery('#idprodfournprice').attr('data-discount'));}

			var tva_tx = parseFloat($('option:selected', this).attr('data-tvatx')); 					// When select is done from HTML select
			if (isNaN(tva_tx)) { tva_tx = parseFloat(jQuery('#idprodfournprice').attr('data-tvatx'));}	// When select is done from HTML input with ajax autocomplete

			var default_vat_code = $('option:selected', this).attr('data-default-vat-code');							 					// When select is done from HTML select
			if (typeof default_vat_code === 'undefined') { default_vat_code = jQuery('#idprodfournprice').attr('data-default-vat-code');}	// When select is done from HTML input with ajax autocomplete

			var supplier_ref = $('option:selected', this).attr('data-supplier-ref');											// When select is done from HTML select
			if (typeof supplier_ref === 'undefined') { supplier_ref = jQuery('#idprodfournprice').attr('data-supplier-ref'); }	// When select is done from HTML input with ajax autocomplete

			<?php if (($object->element == 'supplier_proposal' || $object->element == 'order_supplier' || $object->element == 'invoice_supplier' || $object->element == 'invoice_supplier_rec') && !$seller->tva_assuj) { ?>
				if (tva_tx != .0) {
					tva_tx = .0;
					default_vat_code = null;
				}
			<?php } ?>

			var stringforvatrateselection = tva_tx;
			if (typeof default_vat_code != 'undefined' && default_vat_code != null && default_vat_code != '') {
				stringforvatrateselection = stringforvatrateselection+' ('+default_vat_code+')';
			}

			var has_multicurrency_up = false;
			<?php
			if (isModEnabled('multicurrency') && $object->multicurrency_code != $conf->currency) {
				?>
				var object_multicurrency_code = '<?php print dol_escape_js($object->multicurrency_code); ?>';

				var multicurrency_code = $('option:selected', this).attr('data-multicurrency-code');                                			// When select is done from HTML select
				if (multicurrency_code == undefined) { multicurrency_code = jQuery('#idprodfournprice').attr('data-multicurrency-code'); }  	// When select is done from HTML input with ajax autocomplete

				var multicurrency_up = parseFloat($('option:selected', this).attr('data-multicurrency-unitprice'));                                	// When select is done from HTML select
				if (isNaN(multicurrency_up)) { multicurrency_up = parseFloat(jQuery('#idprodfournprice').attr('data-multicurrency-unitprice')); }   // When select is done from HTML input with ajax autocomplete

				if (multicurrency_code == object_multicurrency_code) {
					has_multicurrency_up = true;
					jQuery("#multicurrency_price_ht").val(multicurrency_up);
				}

				console.log("objectline_create.tpl Multicurrency values : object_multicurrency_code = "+object_multicurrency_code+", multicurrency_code = "+multicurrency_code+", multicurrency_up = "+multicurrency_up);
				<?php
			}
			?>

			console.log("objectline_create.tpl We find supplier price : up = "+up+", up_locale = "+up_locale+", has_multicurrency_up = "+has_multicurrency_up+", supplier_ref = "+supplier_ref+" qty = "+qty+", tva_tx = "+tva_tx+", default_vat_code = "+default_vat_code+", stringforvatrateselection="+stringforvatrateselection+", discount = "+discount+" for product supplier ref id = "+jQuery('#idprodfournprice').val());

			if (has_multicurrency_up === false) {
				if (typeof up_locale === 'undefined') {
					jQuery("#price_ht").val(up);
				} else {
					jQuery("#price_ht").val(up_locale);
				}
			}

			// Set supplier_ref
			$('#fourn_ref').val(supplier_ref);
			// Set vat rate if field is an input box
			$('#tva_tx').val(tva_tx);
			// Set vat rate by selecting the combo
			//$('#tva_tx option').val(tva_tx);	// This is bugged, it replaces the vat key of all options
			$('#tva_tx option').removeAttr('selected');
			console.log("stringforvatrateselection="+stringforvatrateselection+" -> value of option label for this key="+$('#tva_tx option[value="'+stringforvatrateselection+'"]').val());
			$('#tva_tx option[value="'+stringforvatrateselection+'"]').prop('selected', true);

			if (jQuery("#qty").val() < qty)	{
				jQuery("#qty").val(qty);
			}
			if (jQuery("#remise_percent").val() < discount) {
				jQuery("#remise_percent").val(discount);
			}

			<?php
			if (getDolGlobalInt('PRODUIT_AUTOFILL_DESC') == 1) {
				?>
			var description = $('option:selected', this).attr('data-description');
			if (typeof description == 'undefined') { description = jQuery('#idprodfournprice').attr('data-description');	}

			console.log("Load description into text area : "+description);
				<?php
				if (getDolGlobalString('FCKEDITOR_ENABLE_DETAILS')) {
					?>
			if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined")
			{
				var editor = CKEDITOR.instances['dp_desc'];
				if (editor) {
					editor.setData(description);
				}
			}
					<?php
				} else {
					?>
			jQuery('#dp_desc').text(description);
					<?php
				}
			}
			?>
		} else if (jQuery('#idprodfournprice').length > 0) {
			console.log("objectline_create.tpl #idprodfournprice is not an int but is a string so we set only few properties into page");

			var tva_tx = parseFloat($('option:selected', this).attr('data-tvatx')); 					// When select is done from HTML select
			if (isNaN(tva_tx)) { tva_tx = parseFloat(jQuery('#idprodfournprice').attr('data-tvatx'));}	// When select is done from HTML input with ajax autocomplete

			var default_vat_code = $('option:selected', this).attr('data-default-vat-code');							 					// When select is done from HTML select
			if (typeof default_vat_code === 'undefined') { default_vat_code = jQuery('#idprodfournprice').attr('data-default-vat-code');}	// When select is done from HTML input with ajax autocomplete

			var supplier_ref = $('option:selected', this).attr('data-supplier-ref');											// When select is done from HTML select
			if (typeof supplier_ref === 'undefined') { supplier_ref = jQuery('#idprodfournprice').attr('data-supplier-ref'); }	// When select is done from HTML input with ajax autocomplete

			var stringforvatrateselection = tva_tx;
			if (typeof default_vat_code != 'undefined' && default_vat_code != null && default_vat_code != '') {
				stringforvatrateselection = stringforvatrateselection+' ('+default_vat_code+')';
			}


			console.log("objectline_create.tpl We find data for price : tva_tx = "+tva_tx+", default_vat_code = "+default_vat_code+", supplier_ref = "+supplier_ref+", stringforvatrateselection="+stringforvatrateselection+" for product id = "+jQuery('#idprodfournprice').val());

			// Set supplier_ref
			$('#fourn_ref').val(supplier_ref);
			// Set vat rate if field is an input box
			$('#tva_tx').val(tva_tx);
			// Set vat rate by selecting the combo
			//$('#tva_tx option').val(tva_tx);	// This is bugged, it replaces the vat key of all options
			$('#tva_tx option').removeAttr('selected');
			console.log("stringforvatrateselection="+stringforvatrateselection+" -> value of option label for this key="+$('#tva_tx option[value="'+stringforvatrateselection+'"]').val());
			$('#tva_tx option[value="'+stringforvatrateselection+'"]').prop('selected', true);
			<?php
			if (getDolGlobalInt('PRODUIT_AUTOFILL_DESC') == 1) {
				if (getDolGlobalString('FCKEDITOR_ENABLE_DETAILS')) {
					?>
			if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined")
			{
				var editor = CKEDITOR.instances['dp_desc'];
				if (editor) {
					editor.setData('');
				}
			}
					<?php
				} else {
					?>
			jQuery('#dp_desc').text('');
					<?php
				}
			}
			?>
		}


		/* To set focus */
		if (jQuery('#idprod').val() > 0 || jQuery('#idprodfournprice').val() > 0)
		{
			/* focus work on a standard textarea but not if field was replaced with CKEDITOR */
			jQuery('#dp_desc').focus();
			/* focus if CKEDITOR */
			if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined")
			{
				var editor = CKEDITOR.instances['dp_desc'];
				if (editor) { editor.focus(); }
			}
		}
	});

		<?php if (GETPOST('prod_entry_mode') == 'predef') { // When we submit with a predef product and it fails we must start with predef?>
		setforpredef();
		<?php } ?>
	});
	<?php
	$reshook = $hookmanager->executeHooks('objectlineCreatesupplierorderlineExtrafieldSelected', $parameters, $object, $action);
	?>
	/* Function to set fields visibility after selecting a free product */
	function setforfree() {
		console.log("objectline_create.tpl::setforfree. We show most fields");
		jQuery("#idprodfournprice").val('0');	// Set cursor on not selected product
		jQuery("#prod_entry_mode_free").prop('checked',true).change();
		jQuery("#prod_entry_mode_predef").prop('checked',false).change();
		jQuery("#search_idprod, #idprod, #search_idprodfournprice, #buying_price").val('');
		jQuery("#price_ht, #multicurrency_price_ht, #price_ttc, #multicurrency_price_ttc, #fourn_ref, #tva_tx, #buying_price, #title_fourn_ref, #title_vat, #title_up_ht, #title_up_ht_currency, #title_up_ttc, #title_up_ttc_currency").show();
		jQuery("#np_marginRate, #np_markRate, .np_marginRate, .np_markRate, #units, #title_units").show();
		jQuery("#fournprice_predef").hide();
	}

	function setforpredef() {
		console.log("objectline_create.tpl::setforpredef We hide some fields, show dates");
		jQuery("#select_type").val(-1);
		jQuery("#prod_entry_mode_free").prop('checked',false).change();
		jQuery("#prod_entry_mode_predef").prop('checked',true).change();
		<?php if (!getDolGlobalString('MAIN_DISABLE_EDIT_PREDEF_PRICEHT')) { ?>
			jQuery("#price_ht").val('').show();
			jQuery("#multicurrency_price_ht").val('').show();
			jQuery("#title_up_ht, #title_up_ht_currency").show();
		<?php } else { ?>
			//jQuery("#price_ht").val('').hide();
			jQuery("#multicurrency_price_ht").val('').hide();
			jQuery("#title_up_ht, #title_up_ht_currency").hide();
		<?php } ?>
		<?php if (!getDolGlobalString('MAIN_DISABLE_EDIT_PREDEF_PRICETTC')) { ?>
			jQuery("#price_ttc").val('').show();
			jQuery("#multicurrency_price_ttc").val('').show();
			jQuery("#title_up_ttc, #title_up_ttc_currency").show();
		<?php } else { ?>
			jQuery("#price_ttc").val('').hide();
			jQuery("#multicurrency_price_ttc").val('').hide();
			jQuery("#title_up_ttc, #title_up_ttc_currency").hide();
		<?php } ?>
		/* jQuery("#tva_tx, #title_vat").hide(); */
		/* jQuery("#title_fourn_ref").hide(); */
		<?php if (!getDolGlobalString('DISPLAY_MARGIN_RATES')) { ?>
			jQuery("#np_marginRate, .np_marginRate").hide();
		<?php } ?>
		<?php if (!getDolGlobalString('DISPLAY_MARK_RATES')) { ?>
			jQuery("#np_markRate, .np_markRate").hide();
		<?php } ?>
		jQuery("#units, #title_units").hide();
		jQuery("#buying_price").show();
		jQuery('#trlinefordates, .divlinefordates').show();
	}

<?php

print '</script>';

print "<!-- END PHP TEMPLATE objectline_create.tpl.php -->\n";
