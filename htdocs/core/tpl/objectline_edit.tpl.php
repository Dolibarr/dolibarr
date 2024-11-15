<?php
/* Copyright (C) 2010-2012	Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2022	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2018-2024	Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2022		OpenDSI				<support@open-dsi.fr>
 * Copyright (C) 2024		MDW					<mdeweerd@users.noreply.github.com>
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
 * $seller, $buyer
 * $dateSelector
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $senderissupplier (0 by default, 1 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 * $canchangeproduct (0 by default, 1 to allow to change the product if it is a predefined product)
 */

/**
 * @var CommonObject $this
 * @var CommonObject $object
 * @var CommonObjectLine $line
 * @var Form $form
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

 // Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

'
@phan-var-force Propal|Contrat|Commande|Facture|Expedition|Delivery|FactureFournisseur|FactureFournisseur|SupplierProposal $object
@phan-var-force PropaleLigne|ContratLigne|CommonObjectLine|CommonInvoiceLine|CommonOrderLine|ExpeditionLigne|DeliveryLine|FactureFournisseurLigneRec|SupplierInvoiceLine|SupplierProposalLine $line
@phan-var-force ThirdParty $seller
@phan-var-force ThirdParty $buyer
@phan-var-force string $var
';

$usemargins = 0;
if (isModEnabled('margin') && !empty($object->element) && in_array($object->element, array('facture', 'facturerec', 'propal', 'commande'))) {
	$usemargins = 1;
}

global $forceall, $senderissupplier, $inputalsopricewithtax, $canchangeproduct;
if (empty($dateSelector)) {
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
if (empty($canchangeproduct)) {
	$canchangeproduct = 0;
}

// Define colspan for the button 'Add'
$colspan = 3; // Col total ht + col edit + col delete
if (!empty($inputalsopricewithtax)) {
	$colspan++; // We add 1 if col total ttc
}
if (in_array($object->element, array('propal', 'supplier_proposal', 'facture', 'facturerec', 'invoice', 'commande', 'order', 'order_supplier', 'invoice_supplier', 'invoice_supplier_rec'))) {
	$colspan++; // With this, there is a column move button
}
if (isModEnabled("multicurrency") && $object->multicurrency_code != $conf->currency) {
	$colspan += 2;
}
if (isModEnabled('asset') && $object->element == 'invoice_supplier') {
	$colspan++;
}



print "<!-- BEGIN PHP TEMPLATE objectline_edit.tpl.php -->\n";

$coldisplay = 0;
?>
<tr class="oddeven tredited">
<?php if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) { ?>
		<td class="linecolnum center"><?php $coldisplay++; ?><?php echo($i + 1); ?></td>
<?php }

$coldisplay++;
?>
	<td class="linecoldesc minwidth250onall">
	<div id="line_<?php echo $line->id; ?>"></div>

	<input type="hidden" name="lineid" value="<?php echo $line->id; ?>">
	<input type="hidden" id="product_type" name="type" value="<?php echo $line->product_type; ?>">
	<input type="hidden" id="special_code" name="special_code" value="<?php echo $line->special_code; ?>">
	<input type="hidden" id="fk_parent_line" name="fk_parent_line" value="<?php echo $line->fk_parent_line; ?>">

	<?php if ($line->fk_product > 0) { ?>
		<?php
		if (empty($canchangeproduct)) {
			if ($line->fk_parent_line > 0) {
				echo img_picto('', 'rightarrow');
			} ?>
			<a href="<?php echo DOL_URL_ROOT.'/product/card.php?id='.$line->fk_product; ?>">
			<?php
			if ($line->product_type == 1) {
				echo img_object($langs->trans('ShowService'), 'service');
			} else {
				print img_object($langs->trans('ShowProduct'), 'product');
			}
			echo ' '.$line->ref; ?>
			</a>
			<?php
			echo ' - '.nl2br($line->product_label);
			print '<input type="hidden" id="product_id" name="productid" value="'.(!empty($line->fk_product) ? $line->fk_product : 0).'">';
		} else {
			if ($senderissupplier) {
				$form->select_produits_fournisseurs(!empty($line->fk_product) ? $line->fk_product : 0, 'productid');
			} else {
				print $form->select_produits(!empty($line->fk_product) ? $line->fk_product : 0, 'productid');
			}
		}
		?>
		<br><br>
	<?php }	?>

	<?php
	if (is_object($hookmanager)) {
		$fk_parent_line = (GETPOST('fk_parent_line') ? GETPOSTINT('fk_parent_line') : $line->fk_parent_line);
		$parameters = array('line' => $line, 'fk_parent_line' => $fk_parent_line, 'var' => $var, 'dateSelector' => $dateSelector, 'seller' => $seller, 'buyer' => $buyer);
		$reshook = $hookmanager->executeHooks('formEditProductOptions', $parameters, $this, $action);
	}

	$situationinvoicelinewithparent = 0;
	if ($line->fk_prev_id != null && in_array($object->element, array('facture', 'facturedet'))) {
		/** @var CommonInvoice $object */
		// @phan-suppress-next-line PhanUndeclaredConstantOfClass
		if ($object->type == $object::TYPE_SITUATION) {	// The constant TYPE_SITUATION exists only for object invoice
			// Set constant to disallow editing during a situation cycle
			$situationinvoicelinewithparent = 1;
		}
	}

	// Do not allow editing during a situation cycle
	// but in some situations that is required (update legal information for example)
	if (getDolGlobalString('INVOICE_SITUATION_CAN_FORCE_UPDATE_DESCRIPTION')) {
		$situationinvoicelinewithparent = 0;
	}

	if (!$situationinvoicelinewithparent) {
		// editor wysiwyg
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$nbrows = ROWS_2;
		if (getDolGlobalString('MAIN_INPUT_DESC_HEIGHT')) {
			$nbrows = getDolGlobalString('MAIN_INPUT_DESC_HEIGHT');
		}
		$enable = (isset($conf->global->FCKEDITOR_ENABLE_DETAILS) ? $conf->global->FCKEDITOR_ENABLE_DETAILS : 0);
		$toolbarname = 'dolibarr_details';
		if (getDolGlobalString('FCKEDITOR_ENABLE_DETAILS_FULL')) {
			$toolbarname = 'dolibarr_notes';
		}
		$doleditor = new DolEditor('product_desc', GETPOSTISSET('product_desc') ? GETPOST('product_desc', 'restricthtml') : $line->description, '', (!getDolGlobalString('MAIN_DOLEDITOR_HEIGHT') ? 164 : $conf->global->MAIN_DOLEDITOR_HEIGHT), $toolbarname, '', false, true, $enable, $nbrows, '98%');
		$doleditor->Create();
	} else {
		print '<textarea id="product_desc" class="flat" name="product_desc" readonly style="width: 200px; height:80px;">';
		print GETPOSTISSET('product_desc') ? GETPOST('product_desc', 'restricthtml') : $line->description;
		print '</textarea>';
	}

	//Line extrafield
	if (!empty($extrafields)) {
		$temps = $line->showOptionals($extrafields, 'edit', array('class' => 'tredited'), '', '', 1, 'line');
		if (!empty($temps)) {
			print '<div style="padding-top: 10px" id="extrafield_lines_area_edit" name="extrafield_lines_area_edit">';
			print $temps;
			print '</div>';
		}
	}

	// Show autofill date for recurring invoices
	if (isModEnabled("service") && $line->product_type == 1 && ($line->element == 'facturedetrec' || $line->element == 'invoice_supplier_det_rec')) {
		if ($line->element == 'invoice_supplier_det_rec') {
			$line->date_start_fill = $line->date_start;
			$line->date_end_fill = $line->date_end;
		}
		echo '<br>';
		echo $langs->trans('AutoFillDateFrom').' ';
		echo $form->selectyesno('date_start_fill', GETPOSTISSET('date_start_fill') ? GETPOSTINT('date_start_fill') : $line->date_start_fill, 1);
		echo ' - ';
		echo $langs->trans('AutoFillDateTo').' ';
		echo $form->selectyesno('date_end_fill', GETPOSTISSET('date_end_fill') ? GETPOSTINT('date_end_fill') : $line->date_end_fill, 1);
	}

	?>
	</td>

	<?php
	if ($object->element == 'supplier_proposal' || $object->element == 'order_supplier' || $object->element == 'invoice_supplier' || $object->element == 'invoice_supplier_rec') {	// We must have same test in printObjectLines
		$coldisplay++; ?>
		<td class="right linecolrefsupplier"><input id="fourn_ref" name="fourn_ref" class="flat minwidth50 maxwidth100 maxwidth125onsmartphone" value="<?php echo GETPOSTISSET('fourn_ref') ? GETPOST('fourn_ref') : ($line->ref_supplier ? $line->ref_supplier : $line->ref_fourn); ?>"></td>
					<?php
					print '<input type="hidden" id="fournprice" name="fournprice"  class="" value="'.$line->fk_fournprice.'">';
	}

	// VAT Rate
	$coldisplay++;
	$type_tva = null;
	if ($object->element == 'propal' || $object->element == 'commande' || $object->element == 'facture' || $object->element == 'facturerec') {
		$type_tva = 1;
	} elseif ($object->element == 'supplier_proposal' || $object->element == 'order_supplier' || $object->element == 'invoice_supplier' || $object->element == 'invoice_supplier_rec') {
		$type_tva = 2;
	}
	if (!$situationinvoicelinewithparent) {
		print '<td class="right">';
		print $form->load_tva('tva_tx', GETPOSTISSET('tva_tx') ? GETPOST('tva_tx', 'alpha') : ($line->tva_tx.($line->vat_src_code ? (' ('.$line->vat_src_code.')') : '')), $seller, $buyer, 0, $line->info_bits, $line->product_type, false, 1, $type_tva);
		print '</td>';
	} else {
		print '<td class="right"><input size="1" type="text" class="flat right" name="tva_tx" value="'.price($line->tva_tx).'" readonly />%</td>';
	}

	$coldisplay++;
	print '<td class="right"><input type="text" class="flat right width50" id="price_ht" name="price_ht" value="'.(GETPOSTISSET('price_ht') ? GETPOST('price_ht', 'alpha') : (isset($line->pu_ht) ? price($line->pu_ht, 0, '', 0) : price($line->subprice, 0, '', 0))).'"';
	if ($situationinvoicelinewithparent) {
		print ' readonly';
	}
	print '></td>';

	if (isModEnabled("multicurrency") && $object->multicurrency_code != $conf->currency) {
		$coldisplay++;
		print '<td class="right"><input rel="'.$object->multicurrency_tx.'" type="text" class="flat right width50" id="multicurrency_subprice" name="multicurrency_subprice" value="'.(GETPOSTISSET('multicurrency_subprice') ? GETPOST('multicurrency_subprice', 'alpha') : price($line->multicurrency_subprice)).'" /></td>';
	}

	if (!empty($inputalsopricewithtax) && !getDolGlobalInt('MAIN_NO_INPUT_PRICE_WITH_TAX')) {
		$coldisplay++;
		$upinctax = isset($line->pu_ttc) ? $line->pu_ttc : null;
		if (getDolGlobalInt('MAIN_UNIT_PRICE_WITH_TAX_IS_FOR_ALL_TAXES')) {
			$upinctax = price2num($line->total_ttc / (float) $line->qty, 'MU');
		}
		print '<td class="right"><input type="text" class="flat right width50" id="price_ttc" name="price_ttc" value="'.(GETPOSTISSET('price_ttc') ? GETPOST('price_ttc') : (isset($upinctax) ? price($upinctax, 0, '', 0) : '')).'"';
		if ($situationinvoicelinewithparent) {
			print ' readonly';
		}
		print '></td>';
	}
	?>
	<td class="right">
	<?php $coldisplay++;
	if (($line->info_bits & 2) != 2) {
		// I comment warning of stock because it shows the info even when it should not.
		// for example always visible on invoice but must be visible only if stock module on and stock decrease option is on invoice validation and status is not validated
		// must also not be output for most entities (proposal, intervention, ...)
		//if($line->qty > $line->stock) print img_picto($langs->trans("StockTooLow"),"warning", 'style="vertical-align: bottom;"')." ";
		print '<input size="3" type="text" class="flat right" name="qty" id="qty" value="'.(GETPOSTISSET('qty') ? GETPOST('qty') : $line->qty).'"';
		if ($situationinvoicelinewithparent) {	// Do not allow editing during a situation cycle
			print ' readonly';
		}
		print '>';
	} else { ?>
		&nbsp;
	<?php } ?>
	</td>

	<?php
	if (getDolGlobalString('PRODUCT_USE_UNITS')) {
		$unit_type = false;
		// limit unit select to unit type
		if (!empty($line->fk_unit) && !getDolGlobalString('MAIN_EDIT_LINE_ALLOW_ALL_UNIT_TYPE')) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/cunits.class.php';
			$cUnit = new CUnits($line->db);
			if ($cUnit->fetch($line->fk_unit) > 0) {
				if (!empty($cUnit->unit_type)) {
					$unit_type = $cUnit->unit_type;
				}
			}
		}
		$coldisplay++;
		print '<td class="left">';
		print $form->selectUnits(GETPOSTISSET('units') ? GETPOST('units') : $line->fk_unit, "units", 0, $unit_type);
		print '</td>';
	}
	?>

	<td class="nowraponall right linecoldiscount">
	<?php
	// Discount
	$coldisplay++;
	if (($line->info_bits & 2) != 2) {
		print '<input type="text" class="flat right width40" name="remise_percent" id="remise_percent" value="'.(GETPOSTISSET('remise_percent') ? GETPOST('remise_percent') : ($line->remise_percent ? $line->remise_percent : '')).'"';
		if ($situationinvoicelinewithparent) {
			print ' readonly';
		}
		print '><span class="hideonsmartphone opacitymedium">%</span>';
	} else { ?>
		&nbsp;
	<?php } ?>
	</td>

	<?php
	// Progression for situation invoices
	if ($object->situation_cycle_ref) {
		$coldisplay++;
		if (getDolGlobalInt('INVOICE_USE_SITUATION') == 2) {
			$tmp_fieldv = (GETPOSTISSET('progress') ? GETPOST('progress') : $line->situation_percent);
			$old_fieldv = $line->getAllPrevProgress($line->fk_facture);
			$fieldv = $tmp_fieldv + $old_fieldv;

			print '<td class="nowrap right linecolcycleref"><input class="right" type="text" size="1" value="'.$fieldv.'" name="progress">%</td>';
		} else {
			print '<td class="nowrap right linecolcycleref"><input class="right" type="text" size="1" value="' . (GETPOSTISSET('progress') ? GETPOST('progress') : $line->situation_percent) . '" name="progress">%</td>';
		}
		$coldisplay++;
		print '<td></td>';
	}

	if (!empty($usemargins)) {
		if ($user->hasRight('margins', 'creer')) {
			$coldisplay++; ?>
		<td class="margininfos right">
			<!-- For predef product -->
						<?php if (isModEnabled("product") || isModEnabled("service")) { ?>
			<select id="fournprice_predef" name="fournprice_predef" class="flat minwidth75imp right" style="display: none;"></select>
						<?php } ?>
			<!-- For free product -->
			<input class="flat maxwidth75 right" type="text" id="buying_price" name="buying_price" class="hideobject" value="<?php echo(GETPOSTISSET('buying_price') ? GETPOST('buying_price') : price($line->pa_ht, 0, '', 0)); ?>">
		</td>
						<?php
		}

		if ($user->hasRight('margins', 'creer')) {
			if (getDolGlobalString('DISPLAY_MARGIN_RATES')) {
				$margin_rate = (GETPOSTISSET("np_marginRate") ? GETPOST("np_marginRate", "alpha", 2) : (($line->pa_ht == 0) ? '' : price($line->marge_tx)));
				// if credit note, don't allow to modify margin
				if ($line->subprice < 0) {
					echo '<td class="right nowrap margininfos">'.$margin_rate.'<span class="opacitymedium hideonsmartphone">%</span></td>';
				} else {
					echo '<td class="right nowrap margininfos"><input class="right maxwidth40" type="text" name="np_marginRate" value="'.$margin_rate.'"><span class="opacitymedium hideonsmartphone">%</span></td>';
				}
				$coldisplay++;
			}
			if (getDolGlobalString('DISPLAY_MARK_RATES')) {
				$mark_rate = (GETPOSTISSET("np_markRate") ? GETPOST("np_markRate", 'alpha', 2) : price($line->marque_tx));
				// if credit note, don't allow to modify margin
				if ($line->subprice < 0) {
					echo '<td class="right nowrap margininfos">'.$mark_rate.'<span class="opacitymedium hideonsmartphone">%</span></td>';
				} else {
					echo '<td class="right nowrap margininfos"><input class="right maxwidth40" type="text" name="np_markRate" value="'.$mark_rate.'"><span class="opacitymedium hideonsmartphone">%</span></td>';
				}
				$coldisplay++;
			}
		}
	}
	?>

	<!-- colspan for this td because it replace total_ht+3 td for buttons+... -->
	<td class="center valignmiddle" colspan="<?php echo $colspan; ?>"><?php $coldisplay += $colspan; ?>
		<input type="submit" class="reposition button buttongen marginbottomonly button-save" id="savelinebutton marginbottomonly" name="save" value="<?php echo $langs->trans("Save"); ?>"><br>
		<input type="submit" class="reposition button buttongen marginbottomonly button-cancel" id="cancellinebutton" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>">
	</td>
</tr>

<?php if (isModEnabled("service") && $line->product_type == 1 && $dateSelector) { ?>
<tr id="service_duration_area" class="treditedlinefordate">
	<?php if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) { ?>
		<td class="linecolnum center"></td>
	<?php } ?>
	<td colspan="<?php echo $coldisplay - (!getDolGlobalString('MAIN_VIEW_LINE_NUMBER') ? 0 : 1) ?>"><?php echo $langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' '; ?>
	<?php
	$prefillDates = false;
	$date_start_prefill = 0;
	$date_end_prefill = 0;
	if (getDolGlobalString('MAIN_FILL_SERVICE_DATES_FROM_LAST_SERVICE_LINE') && !empty($object->lines) && $i > 0) {
		for ($j = $i - 1; $j >= 0; $j--) {
			$lastline = $object->lines[$j];
			if ($lastline->product_type == Product::TYPE_SERVICE && (!empty($lastline->date_start) || !empty($lastline->date_end))) {
				$date_start_prefill = $lastline->date_start;
				$date_end_prefill = $lastline->date_end;
				$prefillDates = true;
				break;
			}
		}
	}
	$hourmin = (isset($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE) ? $conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE : '');
	print $form->selectDate($line->date_start, 'date_start', $hourmin, $hourmin, $line->date_start ? 0 : 1, "updateline", 1, 0);
	print ' '.$langs->trans('to').' ';
	print $form->selectDate($line->date_end, 'date_end', $hourmin, $hourmin, $line->date_end ? 0 : 1, "updateline", 1, 0);
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
	if (!$line->date_start) {
		if (isset($conf->global->MAIN_DEFAULT_DATE_START_HOUR)) {
			print 'jQuery("#date_starthour").val("' . getDolGlobalString('MAIN_DEFAULT_DATE_START_HOUR').'");';
		}


		if (isset($conf->global->MAIN_DEFAULT_DATE_START_MIN)) {
			print 'jQuery("#date_startmin").val("' . getDolGlobalString('MAIN_DEFAULT_DATE_START_MIN').'");';
		}

		$res = 1;
		if (!is_object($line->product)) {
			$res = $line->fetch_product();		// fetch product to know its type and allow isMandatoryperiod()
		}
		if ($res > 0) {
			if ($line->product->isMandatoryPeriod() && $line->product->isService()) {
				print  'jQuery("#date_start").addClass("inputmandatory");';	// Do not add tag "required", this block the cancel action when value not set
			}
		}
	}
	if (!$line->date_end) {
		if (isset($conf->global->MAIN_DEFAULT_DATE_END_HOUR)) {
			print 'jQuery("#date_endhour").val("' . getDolGlobalString('MAIN_DEFAULT_DATE_END_HOUR').'");';
		}
		if (isset($conf->global->MAIN_DEFAULT_DATE_END_MIN)) {
			print 'jQuery("#date_endmin").val("' . getDolGlobalString('MAIN_DEFAULT_DATE_END_MIN').'");';
		}

		$res = 1;
		if (!is_object($line->product)) {
			$res = $line->fetch_product();		// fetch product to know its type and allow isMandatoryperiod()
		}
		if ($res  > 0) {
			if ($line->product->isMandatoryperiod() && $line->product->isService()) {
				print  'jQuery("#date_end").addClass("inputmandatory");';	// Do not add tag "required", this block the cancel action when value not set
			}
		}
	}
	print '</script>'
	?>
	</td>
</tr>
<?php }
?>


<script>

<?php
if (!empty($usemargins) && $user->hasRight('margins', 'creer')) {
	?>
	/* Some js test when we click on button "Add" */
	jQuery(document).ready(function() {
	<?php
	if (getDolGlobalString('DISPLAY_MARGIN_RATES')) {
		?>
			$("input[name='np_marginRate']:first").blur(function(e) {
				return checkFreeLine(e, "np_marginRate");
			});
		<?php
	}
	if (getDolGlobalString('DISPLAY_MARK_RATES')) {
		?>
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

		var ratejs = price2numjs(rate.val());
		if (! $.isNumeric(ratejs))
		{
			alert('<?php echo dol_escape_js($langs->transnoentities("rateMustBeNumeric")); ?>');
			e.stopPropagation();
			setTimeout(function () { rate.focus() }, 50);
			return false;
		}
		if (npRate == "np_markRate" && rate.val() >= 100)
		{
			alert('<?php echo dol_escape_js($langs->transnoentities("markRateShouldBeLesserThan100")); ?>');
			e.stopPropagation();
			setTimeout(function () { rate.focus() }, 50);
			return false;
		}

		var price = 0;
		remisejs = price2numjs(remise.val());

		if (remisejs != 100) {	// If a discount not 100 or no discount
			if (remisejs == '') {
				remisejs = 0;
			}

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

jQuery(document).ready(function()
{
	jQuery("#price_ht").keyup(function(event) {
		// console.log(event.which);		// discard event tag and arrows
		if (event.which != 9 && (event.which < 37 ||event.which > 40) && jQuery("#price_ht").val() != '') {
			jQuery("#price_ttc").val('');
			jQuery("#multicurrency_subprice").val('');
		}
	});
	jQuery("#price_ttc").keyup(function(event) {
		// console.log(event.which);		// discard event tag and arrows
		if (event.which != 9 && (event.which < 37 || event.which > 40) && jQuery("#price_ttc").val() != '') {
			jQuery("#price_ht").val('');
			jQuery("#multicurrency_subprice").val('');
		}
	});
	jQuery("#multicurrency_subprice").keyup(function(event) {
		// console.log(event.which);		// discard event tag and arrows
		if (event.which != 9 && (event.which < 37 || event.which > 40) && jQuery("#price_ttc").val() != '') {
			jQuery("#price_ht").val('');
			jQuery("#price_ttc").val('');
		}
	});

	<?php if (in_array($object->table_element_line, array('propaldet', 'commandedet', 'facturedet'))) { ?>
	$("#date_start, #date_end").focusout(function() {
		if ( $(this).val() == ''  && !$(this).hasClass('inputmandatory') ) {
			$(this).addClass('inputmandatory');
		} else {
			$(this).removeClass('inputmandatory');
		}
	});
		<?php
	}

	if (isModEnabled('margin')) {
		?>
		/* Add rule to clear margin when we change some data, so when we change sell or buy price, margin will be recalculated after submitting form */
		jQuery("#tva_tx").click(function() {						/* sometimes field is a text, sometimes a combo */
			jQuery("input[name='np_marginRate']:first").val('');
			jQuery("input[name='np_markRate']:first").val('');
		});
		jQuery("#tva_tx").keyup(function() {						/* sometimes field is a text, sometimes a combo */
			jQuery("input[name='np_marginRate']:first").val('');
			jQuery("input[name='np_markRate']:first").val('');
		});
		jQuery("#price_ht").keyup(function() {
			jQuery("input[name='np_marginRate']:first").val('');
			jQuery("input[name='np_markRate']:first").val('');
		});
		jQuery("#qty").keyup(function() {
			jQuery("input[name='np_marginRate']:first").val('');
			jQuery("input[name='np_markRate']:first").val('');
		});
		jQuery("#remise_percent").keyup(function() {
			jQuery("input[name='np_marginRate']:first").val('');
			jQuery("input[name='np_markRate']:first").val('');
		});
		jQuery("#buying_price").keyup(function() {
			jQuery("input[name='np_marginRate']:first").val('');
			jQuery("input[name='np_markRate']:first").val('');
		});

		/* Init field buying_price and fournprice */
		var token = '<?php echo currentToken(); ?>';		// For AJAX Call we use old 'token' and not 'newtoken'
		$.post('<?php echo DOL_URL_ROOT; ?>/fourn/ajax/getSupplierPrices.php', {'idprod': <?php echo $line->fk_product ? $line->fk_product : 0; ?>, 'token': token }, function(data) {
		  if (data && data.length > 0) {
			var options = '';
			var trouve=false;
			$(data).each(function() {
				options += '<option value="'+this.id+'" price="'+this.price+'"';
				<?php if ($line->fk_fournprice > 0) { ?>
				if (this.id == <?php echo $line->fk_fournprice; ?>) {
					options += ' selected';
					$("#buying_price").val(this.price);
					trouve = true;
				}
				<?php } ?>
				options += '>'+this.label+'</option>';
			});
			options += '<option value=null'+(trouve?'':' selected')+'><?php echo $langs->trans("InputPrice"); ?></option>';
			$("#fournprice").html(options);
			if (trouve) {
				$("#buying_price").hide();
				$("#fournprice").show();
			} else {
				$("#buying_price").show();
			}
			$("#fournprice").change(function() {
				var selval = $(this).find('option:selected').attr("price");
				if (selval)
					$("#buying_price").val(selval).hide();
				else
					$('#buying_price').show();
			});
		} else {
			$("#fournprice").hide();
			$('#buying_price').show();
		}
		}, 'json');
		<?php
	}
	?>
});

</script>
<!-- END PHP TEMPLATE objectline_edit.tpl.php -->
