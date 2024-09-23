<?php
/* Copyright (C) 2010-2013	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2017		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2022		OpenDSI				<support@open-dsi.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024 		Alexandre Spangaro 		<alexandre@inovea-conseil.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 * $element     (used to test $user->rights->$element->creer)
 * $permtoedit  (used to replace test $user->rights->$element->creer)
 * $senderissupplier (0 by default, 1 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 * $outputalsopricetotalwithtax
 * $usemargins (0 to disable all margins columns, 1 to show according to margin setup)
 * $disableedit, $disablemove, $disableremove
 *
 * $text, $description, $line
 */
// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

'@phan-var-force CommonObject $this
 @phan-var-force CommonObject $object
 @phan-var-force 0|1 $forceall
 @phan-var-force int $num
';

global $mysoc;
global $forceall, $senderissupplier, $inputalsopricewithtax, $outputalsopricetotalwithtax;

$usemargins = 0;
if (isModEnabled('margin') && !empty($object->element) && in_array($object->element, array('facture', 'facturerec', 'propal', 'commande'))) {
	$usemargins = 1;
}

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
if (empty($outputalsopricetotalwithtax)) {
	$outputalsopricetotalwithtax = 0;
}

// add html5 elements
$domData  = ' data-element="'.$line->element.'"';
$domData .= ' data-id="'.$line->id.'"';
$domData .= ' data-qty="'.$line->qty.'"';
$domData .= ' data-product_type="'.$line->product_type.'"';

$sign = 1;
// @phan-suppress-next-line PhanUndeclaredConstantOfClass
if (getDolGlobalString('INVOICE_POSITIVE_CREDIT_NOTE_SCREEN') && in_array($object->element, array('facture', 'invoice_supplier')) && $object->type == $object::TYPE_CREDIT_NOTE) {
	$sign = -1;
}


$coldisplay = 0;
?>
<!-- BEGIN PHP TEMPLATE objectline_view.tpl.php -->
<tr  id="row-<?php print $line->id?>" class="drag drop oddeven" <?php print $domData; ?> >
<?php if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) { ?>
	<td class="linecolnum center"><span class="opacitymedium"><?php $coldisplay++; ?><?php print($i + 1); ?></span></td>
<?php } ?>
	<td class="linecoldescription minwidth300imp"><?php $coldisplay++; ?><div id="line_<?php print $line->id; ?>"></div>
<?php
if (($line->info_bits & 2) == 2) {
	print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$this->socid.'">';
	$txt = '';
	print img_object($langs->trans("ShowReduc"), 'reduc').' ';
	if ($line->description == '(DEPOSIT)') {
		$txt = $langs->trans("Deposit");
	} elseif ($line->description == '(EXCESS RECEIVED)') {
		$txt = $langs->trans("ExcessReceived");
	} elseif ($line->description == '(EXCESS PAID)') {
		$txt = $langs->trans("ExcessPaid");
	}
	//else $txt=$langs->trans("Discount");
	print $txt;
	print '</a>';
	if ($line->description) {
		if ($line->description == '(CREDIT_NOTE)' && $line->fk_remise_except > 0) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
			$discount = new DiscountAbsolute($this->db);
			$discount->fetch($line->fk_remise_except);
			print($txt ? ' - ' : '').$langs->transnoentities("DiscountFromCreditNote", $discount->getNomUrl(0));
		} elseif ($line->description == '(DEPOSIT)' && $line->fk_remise_except > 0) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
			$discount = new DiscountAbsolute($this->db);
			$discount->fetch($line->fk_remise_except);
			print($txt ? ' - ' : '').$langs->transnoentities("DiscountFromDeposit", $discount->getNomUrl(0));
			// Add date of deposit
			if (getDolGlobalString('INVOICE_ADD_DEPOSIT_DATE')) {
				print ' ('.dol_print_date($discount->datec).')';
			}
		} elseif ($line->description == '(EXCESS RECEIVED)' && $objp->fk_remise_except > 0) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
			$discount = new DiscountAbsolute($this->db);
			$discount->fetch($line->fk_remise_except);
			print($txt ? ' - ' : '').$langs->transnoentities("DiscountFromExcessReceived", $discount->getNomUrl(0));
		} elseif ($line->description == '(EXCESS PAID)' && $objp->fk_remise_except > 0) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
			$discount = new DiscountAbsolute($this->db);
			$discount->fetch($line->fk_remise_except);
			print($txt ? ' - ' : '').$langs->transnoentities("DiscountFromExcessPaid", $discount->getNomUrl(0));
		} else {
			print($txt ? ' - ' : '').dol_htmlentitiesbr($line->description);
		}
	}
} else {
	$format = (getDolGlobalString('MAIN_USE_HOURMIN_IN_DATE_RANGE') ? 'dayhour' : 'day');

	if ($line->fk_product > 0) {
		if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
			print (!empty($line->fk_parent_line) ? img_picto('', 'rightarrow') : '') . $text;
			if (!getDolGlobalInt('PRODUIT_DESC_IN_FORM')) {
				print $form->textwithpicto('', $description);
			}
		} else {
			print $form->textwithtooltip($text, $description, 3, 0, '', $i, 0, (!empty($line->fk_parent_line) ? img_picto('', 'rightarrow') : ''));
		}
	} else {
		$type = (!empty($line->product_type) ? $line->product_type : $line->fk_product_type);
		if ($type == 1) {
			$text = img_object($langs->trans('Service'), 'service');
		} else {
			$text = img_object($langs->trans('Product'), 'product');
		}

		if (!empty($line->label)) {
			$text .= ' <strong>'.$line->label.'</strong>';
			print $form->textwithtooltip($text, dol_htmlentitiesbr($line->description), 3, 0, '', $i, 0, (!empty($line->fk_parent_line) ? img_picto('', 'rightarrow') : ''));
		} else {
			if (!empty($line->fk_parent_line)) {
				print img_picto('', 'rightarrow');
			}
			if (preg_match('/^\(DEPOSIT\)/', $line->description)) {
				$newdesc = preg_replace('/^\(DEPOSIT\)/', $langs->trans("Deposit"), $line->description);
				print $text.' '.dol_htmlentitiesbr($newdesc);
			} else {
				print $text.' '.dol_htmlentitiesbr($line->description);
			}
		}
	}

	// Show date range
	if ($line->element == 'facturedetrec' || $line->element == 'invoice_supplier_det_rec') {
		if ($line->element == 'invoice_supplier_det_rec' && $line->product_type != Product::TYPE_PRODUCT) {
			$line->date_start_fill = $line->date_start;
			$line->date_end_fill = $line->date_end;
		}
		if ($line->date_start_fill || $line->date_end_fill) {
			print '<div class="clearboth nowraponall daterangeofline-facturedetrec">';
		}
		if ($line->date_start_fill) {
			print '<span class="opacitymedium" title="'.dol_escape_htmltag($langs->trans("AutoFillDateFrom")).'">'.$langs->trans('AutoFillDateFromShort').':</span> '.yn($line->date_start_fill);
		}
		if ($line->date_start_fill && $line->date_end_fill) {
			print ' - ';
		}
		if ($line->date_end_fill) {
			print '<span class="opacitymedium" title="'.dol_escape_htmltag($langs->trans("AutoFillDateTo")).'">'.$langs->trans('AutoFillDateToShort').':</span> '.yn($line->date_end_fill);
		}
		if ($line->date_start_fill || $line->date_end_fill) {
			print '</div>';
		}
	} else {
		if ($line->date_start || $line->date_end) {
			print '<div class="clearboth nowraponall opacitymedium daterangeofline">'.get_date_range($line->date_start, $line->date_end, $format).'</div>';
		}

		if (!$line->date_start || !$line->date_end) {
			// show warning under line
			// we need to fetch product associated to line for some test
			if ($object->element == 'propal' || $object->element == 'order' || $object->element == 'facture' || $object->element == 'propal_supplier' || $object->element == 'supplier_proposal' || $object->element == 'commande') {
				$res = $line->fetch_product();
				if ($res  > 0) {
					if ($line->product->isService() && $line->product->isMandatoryPeriod()) {
						print '<div><span class="clearboth nowraponall warning">'.$langs->trans("mandatoryPeriodNeedTobeSet").'</span></div>';
					}
				}
			}
		}

		// If we show the lines in a context to create a recurring sale invoice
		if (basename($_SERVER["PHP_SELF"]) == 'card-rec.php') {
			$default_start_fill = getDolGlobalInt('INVOICEREC_SET_AUTOFILL_DATE_START');
			$default_end_fill = getDolGlobalInt('INVOICEREC_SET_AUTOFILL_DATE_END');
			print '<div class="clearboth nowraponall daterangeofline-facturedetrec">';
			print '<span class="opacitymedium" title="'.dol_escape_htmltag($langs->trans("AutoFillDateFrom")).'">'.$langs->trans('AutoFillDateFromShort').':</span> '.yn($default_start_fill);
			print ' - ';
			print '<span class="opacitymedium" title="'.dol_escape_htmltag($langs->trans("AutoFillDateTo")).'">'.$langs->trans('AutoFillDateToShort').':</span> '.yn($default_end_fill);
			print '</div>';
		}
	}

	// Add description in form
	if ($line->fk_product > 0 && getDolGlobalInt('PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE')) {
		if ($line->element == 'facturedetrec') {
			print (!empty($line->description) && $line->description != $line->product_label) ? (($line->date_start_fill || $line->date_end_fill) ? '' : '<br>').'<br>'.dol_htmlentitiesbr($line->description) : '';
		} elseif ($line->element == 'invoice_supplier_det_rec') {
			print (!empty($line->description) && $line->description != $line->label) ? (($line->date_start || $line->date_end) ? '' : '<br>').'<br>'.dol_htmlentitiesbr($line->description) : '';
		} else {
			print (!empty($line->description) && $line->description != $line->product_label) ? (($line->date_start || $line->date_end) ? '' : '<br>').'<br>'.dol_htmlentitiesbr($line->description) : '';
		}
	}

	// Line extrafield
	if (!empty($extrafields)) {
		$temps = $line->showOptionals($extrafields, 'view', array(), '', '', 1, 'line');
		if (!empty($temps)) {
			print '<div style="padding-top: 10px" id="extrafield_lines_area_'.$line->id.'" name="extrafield_lines_area_'.$line->id.'">';
			print $temps;
			print '</div>';
		}
	}
}

if ($user->hasRight('fournisseur', 'lire') && isset($line->fk_fournprice) && $line->fk_fournprice > 0 && !getDolGlobalString('SUPPLIER_HIDE_SUPPLIER_OBJECTLINES')) {
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
	$productfourn = new ProductFournisseur($this->db);
	$productfourn->fetch_product_fournisseur_price($line->fk_fournprice);
	print '<div class="clearboth"></div>';
	print '<span class="opacitymedium">'.$langs->trans('Supplier').' : </span>'.$productfourn->getSocNomUrl(1, 'supplier').' - <span class="opacitymedium">'.$langs->trans('Ref').' : </span>';
	// Supplier ref
	if ($user->hasRight('produit', 'creer') || $user->hasRight('service', 'creer')) { // change required right here
		print $productfourn->getNomUrl();
	} else {
		print $productfourn->ref_supplier;
	}
}

if (isModEnabled('accounting') && !empty($line->fk_accounting_account) && $line->fk_accounting_account > 0) {
	$accountingaccount = new AccountingAccount($this->db);
	$accountingaccount->fetch($line->fk_accounting_account);
	print '<div class="clearboth"></div><br><span class="opacitymedium">'.$langs->trans('AccountingAffectation').' : </span>'.$accountingaccount->getNomUrl(0, 1, 1);
}

print '</td>';

// Vendor price ref
if ($object->element == 'supplier_proposal' || $object->element == 'order_supplier' || $object->element == 'invoice_supplier' || $object->element == 'invoice_supplier_rec') {	// We must have same test in printObjectLines
	print '<td class="linecolrefsupplier">';
	print($line->ref_fourn ? $line->ref_fourn : $line->ref_supplier);
	print '</td>';
}

$tooltiponprice = '';
$tooltiponpriceend = '';
if (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
	$tooltiponprice = $langs->transcountry("TotalHT", $mysoc->country_code).'='.price($line->total_ht);
	$tooltiponprice .= '<br>'.$langs->transcountry("TotalVAT", ($senderissupplier ? $object->thirdparty->country_code : $mysoc->country_code)).'='.price($line->total_tva);
	if (is_object($object->thirdparty)) {
		if ($senderissupplier) {
			$seller = $object->thirdparty;
			$buyer = $mysoc;
		} else {
			$seller = $mysoc;
			$buyer = $object->thirdparty;
		}

		if ($mysoc->useLocalTax(1)) {
			if (($seller->country_code == $buyer->country_code) || $line->total_localtax1 || $seller->useLocalTax(1)) {
				$tooltiponprice .= '<br>'.$langs->transcountry("TotalLT1", $seller->country_code).'='.price($line->total_localtax1);
			} else {
				$tooltiponprice .= '<br>'.$langs->transcountry("TotalLT1", $seller->country_code).'=<span class="opacitymedium">'.$langs->trans($senderissupplier ? "NotUsedForThisVendor" : "NotUsedForThisCustomer").'</span>';
			}
		}
		if ($mysoc->useLocalTax(2)) {
			if ((isset($seller->country_code) && isset($buyer->thirdparty->country_code) && $seller->country_code == $buyer->thirdparty->country_code) || $line->total_localtax2 || $seller->useLocalTax(2)) {
				$tooltiponprice .= '<br>'.$langs->transcountry("TotalLT2", $seller->country_code).'='.price($line->total_localtax2);
			} else {
				$tooltiponprice .= '<br>'.$langs->transcountry("TotalLT2", $seller->country_code).'=<span class="opacitymedium">'.$langs->trans($senderissupplier ? "NotUsedForThisVendor" : "NotUsedForThisCustomer").'</span>';
			}
		}
	}
	$tooltiponprice .= '<br>'.$langs->transcountry("TotalTTC", $mysoc->country_code).'='.price($line->total_ttc);

	$tooltiponprice = '<span class="classfortooltip" title="'.dol_escape_htmltag($tooltiponprice).'">';
	$tooltiponpriceend = '</span>';
}

// VAT Rate
print '<td class="linecolvat nowrap right">';
$coldisplay++;
$positiverates = '';
if (price2num($line->tva_tx)) {
	$positiverates .= ($positiverates ? '/' : '').price2num($line->tva_tx);
}
if (price2num($line->total_localtax1)) {
	$positiverates .= ($positiverates ? '/' : '').price2num($line->localtax1_tx);
}
if (price2num($line->total_localtax2)) {
	$positiverates .= ($positiverates ? '/' : '').price2num($line->localtax2_tx);
}
if (empty($positiverates)) {
	$positiverates = '0';
}
print $tooltiponprice;
print vatrate($positiverates.($line->vat_src_code ? ' ('.$line->vat_src_code.')' : ''), true, $line->info_bits);
print $tooltiponpriceend;
?></td>

	<td class="linecoluht nowraponall right"><?php $coldisplay++; ?><?php print price($sign * $line->subprice); ?></td>

<?php if (isModEnabled("multicurrency") && $this->multicurrency_code != $conf->currency) { ?>
	<td class="linecoluht_currency nowraponall right"><?php $coldisplay++; ?><?php print price($sign * $line->multicurrency_subprice); ?></td>
<?php }

if (!empty($inputalsopricewithtax) && !getDolGlobalInt('MAIN_NO_INPUT_PRICE_WITH_TAX')) { ?>
	<td class="linecoluttc nowraponall right"><?php $coldisplay++; ?><?php
	$upinctax = isset($line->pu_ttc) ? $line->pu_ttc : null;
	if (getDolGlobalInt('MAIN_UNIT_PRICE_WITH_TAX_IS_FOR_ALL_TAXES')) {
		$upinctax = price2num($line->total_ttc / (float) $line->qty, 'MU');
	}
	print(isset($upinctax) ? price($sign * $upinctax) : price($sign * $line->subprice));
	?></td>
<?php } ?>

	<td class="linecolqty nowraponall right"><?php $coldisplay++; ?>
<?php
if ((($line->info_bits & 2) != 2) && $line->special_code != 3) {
	// I comment this because it shows info even when not required
	// for example always visible on invoice but must be visible only if stock module on and stock decrease option is on invoice validation and status is not validated
	// must also not be output for most entities (proposal, intervention, ...)
	//if($line->qty > $line->stock) print img_picto($langs->trans("StockTooLow"),"warning", 'style="vertical-align: bottom;"')." ";
	print price($line->qty, 0, '', 0, 0); // Yes, it is a quantity, not a price, but we just want the formatting role of function price
} else {
	print '&nbsp;';
}
print '</td>';

if (getDolGlobalString('PRODUCT_USE_UNITS')) {
	print '<td class="linecoluseunit nowrap left">';
	$label = $line->getLabelOfUnit('short');
	if ($label !== '') {
		print $langs->trans($label);
	}
	print '</td>';
}
if (!empty($line->remise_percent) && $line->special_code != 3) {
	print '<td class="linecoldiscount right">';
	$coldisplay++;
	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	print dol_print_reduction($line->remise_percent, $langs);
	print '</td>';
} else {
	print '<td class="linecoldiscount">&nbsp;</td>';
	$coldisplay++;
}

// Fields for situation invoices
if (isset($this->situation_cycle_ref) && $this->situation_cycle_ref) {
	include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';
	$coldisplay++;
	if (getDolGlobalInt('INVOICE_USE_SITUATION') == 2) {
		$previous_progress = $line->get_allprev_progress($object->id);
		$current_progress = $previous_progress + floatval($line->situation_percent);
		print '<td class="linecolcycleref nowrap right">'.$current_progress.'%</td>';
		$coldisplay++;
		print '<td  class="nowrap right">'.$line->situation_percent.'%</td>';
		$coldisplay++;
		$locataxes_array = getLocalTaxesFromRate($line->tva.($line->vat_src_code ? ' ('.$line->vat_src_code.')' : ''), 0, ($senderissupplier ? $mysoc : $object->thirdparty), ($senderissupplier ? $object->thirdparty : $mysoc));
		$tmp = calcul_price_total($line->qty, $line->pu, $line->remise_percent, $line->txtva, -1, -1, 0, 'HT', $line->info_bits, $line->type, ($senderissupplier ? $object->thirdparty : $mysoc), $locataxes_array, 100, $object->multicurrency_tx, $line->multicurrency_subprice);
		print '<td class="linecolcycleref2 right nowrap">'.price($sign * $tmp[0]).'</td>';
	} else {
		print '<td class="linecolcycleref nowrap right">'.$line->situation_percent.'%</td>';
		$coldisplay++;
		$locataxes_array = getLocalTaxesFromRate($line->tva.($line->vat_src_code ? ' ('.$line->vat_src_code.')' : ''), 0, ($senderissupplier ? $mysoc : $object->thirdparty), ($senderissupplier ? $object->thirdparty : $mysoc));
		$tmp = calcul_price_total($line->qty, $line->pu, $line->remise_percent, $line->txtva, -1, -1, 0, 'HT', $line->info_bits, $line->type, ($senderissupplier ? $object->thirdparty : $mysoc), $locataxes_array, 100, $object->multicurrency_tx, $line->multicurrency_subprice);
		print '<td class="linecolcycleref2 right nowrap">'.price($sign * $tmp[0]).'</td>';
	}
}

if ($usemargins && isModEnabled('margin') && empty($user->socid)) {
	if ($user->hasRight('margins', 'creer')) { ?>
		<td class="linecolmargin1 nowrap margininfos right"><?php $coldisplay++; ?><?php print price($line->pa_ht); ?></td>
	<?php }
	if (getDolGlobalString('DISPLAY_MARGIN_RATES') && $user->hasRight('margins', 'liretous')) { ?>
		<td class="linecolmargin2 nowrap margininfos right"><?php $coldisplay++; ?><?php print(($line->pa_ht == 0) ? 'n/a' : price(price2num($line->marge_tx, 'MT')).'%'); ?></td>
	<?php }
	if (getDolGlobalString('DISPLAY_MARK_RATES') && $user->hasRight('margins', 'liretous')) {?>
		<td class="linecolmargin2 nowrap margininfos right"><?php $coldisplay++; ?><?php print price(price2num($line->marque_tx, 'MT')).'%'; ?></td>
	<?php }
}

// Price total without tax
if ($line->special_code == 3) {
	$coldisplay++;
	$colspanOptions	= '';
	if (!empty($conf->multicurrency->enabled) && $object->multicurrency_code != $conf->currency) {
		$coldisplay++;
		$colspanOptions	= ' colspan="2"';
	}
	print '<td class="linecoloption nowrap right"'.$colspanOptions.'>'.$langs->trans('Option').'</td>';
} else {
	print '<td class="linecolht nowrap right">';
	$coldisplay++;
	print $tooltiponprice;
	print price($sign * $line->total_ht);
	print $tooltiponpriceend;
	print '</td>';
	if (isModEnabled("multicurrency") && $this->multicurrency_code != $conf->currency) {
		print '<td class="linecolutotalht_currency nowrap right">'.price($sign * $line->multicurrency_total_ht).'</td>';
		$coldisplay++;
	}
}

// Price inc tax
if ($outputalsopricetotalwithtax) {
	print '<td class="linecolht nowrap right">'.price($sign * $line->total_ttc).'</td>';
	$coldisplay++;
}

// TODO Replace this with $permissiontoedit ?
$objectRights = $this->getRights();
$tmppermtoedit = $objectRights->creer;

if ($this->status == 0 && $tmppermtoedit && $action != 'selectlines') {
	$situationinvoicelinewithparent = 0;
	if (isset($line->fk_prev_id) && in_array($object->element, array('facture', 'facturedet'))) {
		// @phan-suppress-next-line PhanUndeclaredConstantOfClass
		if ($object->type == $object::TYPE_SITUATION) {	// The constant TYPE_SITUATION exists only for object invoice
			// Set constant to disallow editing during a situation cycle
			$situationinvoicelinewithparent = 1;
		}
	}

	// Asset info
	if (isModEnabled('asset') && $object->element == 'invoice_supplier') {
		print '<td class="linecolasset center">';
		$coldisplay++;
		if (!empty($product_static->accountancy_code_buy) ||
			!empty($product_static->accountancy_code_buy_intra) ||
			!empty($product_static->accountancy_code_buy_export)
		) {
			$accountancy_category_asset = getDolGlobalString('ASSET_ACCOUNTANCY_CATEGORY');
			$filters = array();
			if (!empty($product_static->accountancy_code_buy)) {
				$filters[] = "account_number = '" . $this->db->escape($product_static->accountancy_code_buy) . "'";
			}
			if (!empty($product_static->accountancy_code_buy_intra)) {
				$filters[] = "account_number = '" . $this->db->escape($product_static->accountancy_code_buy_intra) . "'";
			}
			if (!empty($product_static->accountancy_code_buy_export)) {
				$filters[] = "account_number = '" . $this->db->escape($product_static->accountancy_code_buy_export) . "'";
			}
			$sql = "SELECT COUNT(*) AS found";
			$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_account";
			$sql .= " WHERE pcg_type = '" . $this->db->escape($conf->global->ASSET_ACCOUNTANCY_CATEGORY) . "'";
			$sql .= " AND (" . implode(' OR ', $filters). ")";
			$resql_asset = $this->db->query($sql);
			if (!$resql_asset) {
				print 'Error SQL: ' . $this->db->lasterror();
			} elseif ($obj = $this->db->fetch_object($resql_asset)) {
				if (!empty($obj->found)) {
					print '<a class="reposition" href="' . DOL_URL_ROOT . '/asset/card.php?action=create&token='.newToken().'&supplier_invoice_id='.$object->id.'">';
					print img_edit_add() . '</a>';
				}
			}
		}
		print '</td>';
	}

	// Edit picto
	print '<td class="linecoledit center">';
	$coldisplay++;
	if (($line->info_bits & 2) == 2 || !empty($disableedit)) {
	} else { ?>
		<a class="editfielda reposition" href="<?php print $_SERVER["PHP_SELF"].'?id='.$this->id.'&action=editline&token='.newToken().'&lineid='.$line->id; ?>">
		<?php print img_edit().'</a>';
	}
	print '</td>';

	// Delete picto
	print '<td class="linecoldelete center">';
	$coldisplay++;
	if (!$situationinvoicelinewithparent && empty($disableremove)) { // For situation invoice, deletion is not possible if there is a parent company.
		print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&action=ask_deleteline&token='.newToken().'&lineid='.$line->id.'">';
		print img_delete();
		print '</a>';
	}
	print '</td>';

	// Move up-down picto
	if ($num > 1 && $conf->browser->layout != 'phone' && ((property_exists($this, 'situation_counter') && $this->situation_counter == 1) || empty($this->situation_cycle_ref)) && empty($disablemove)) {
		print '<td class="linecolmove tdlineupdown center">';
		$coldisplay++;
		if ($i > 0) { ?>
			<a class="lineupdown" href="<?php print $_SERVER["PHP_SELF"].'?id='.$this->id.'&action=up&token='.newToken().'&rowid='.$line->id; ?>">
			<?php print img_up('default', 0, 'imgupforline'); ?>
			</a>
		<?php }
		if ($i < $num - 1) { ?>
			<a class="lineupdown" href="<?php print $_SERVER["PHP_SELF"].'?id='.$this->id.'&action=down&token='.newToken().'&rowid='.$line->id; ?>">
			<?php print img_down('default', 0, 'imgdownforline'); ?>
			</a>
		<?php }
		print '</td>';
	} else {
		print '<td '.(($conf->browser->layout != 'phone' && empty($disablemove)) ? ' class="linecolmove tdlineupdown center"' : ' class="linecolmove center"').'></td>';
		$coldisplay++;
	}
} else {
	$colspan = 3;
	if (isModEnabled('asset') && $object->element == 'invoice_supplier') {
		$colspan++;
	}
	print '<td colspan="'.$colspan.'"></td>';
	$coldisplay += $colspan;
}

if ($action == 'selectlines') { ?>
	<td class="linecolcheck center"><input type="checkbox" class="linecheckbox" name="line_checkbox[<?php print $i + 1; ?>]" value="<?php print $line->id; ?>" ></td>
<?php }

print "</tr>\n";

print "<!-- END PHP TEMPLATE objectline_view.tpl.php -->\n";
