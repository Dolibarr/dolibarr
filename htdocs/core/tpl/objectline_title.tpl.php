<?php
/* Copyright (C) 2010-2013	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2017		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2022		OpenDSI				<support@open-dsi.fr>
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
 * Need to have following variables defined:
 * $object (invoice, order, ...)
 * $conf
 * $langs
 * $element     (used to test $user->rights->$element->creer)
 * $permtoedit  (used to replace test $user->rights->$element->creer)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 * $outputalsopricetotalwithtax
 * $usemargins (0 to disable all margins columns, 1 to show according to margin setup)
 *
 * $type, $text, $description, $line
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit;
}
global $hookmanager, $langs;

$arrayofthfields = [];

// Adds a line numbering column
if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
	$arrayofthfields['linecolnum'] = '<th class="linecolnum center">&nbsp;</th>';
}

// Description
$arrayofthfields['linecoldescription'] =  '<th class="linecoldescription">'.$langs->trans('Description').'</th>';

// Supplier ref
if ($this->element == 'supplier_proposal' || $this->element == 'order_supplier' || $this->element == 'invoice_supplier' || $this->element == 'invoice_supplier_rec') {
	$arrayofthfields['linerefsupplier'] = '<th class="linerefsupplier maxwidth125"><span id="title_fourn_ref">'.$langs->trans("SupplierRef").'</span></th>';
}

// VAT
$linecolvat = '<th class="linecolvat right nowraponall">';
if (getDolGlobalString('FACTURE_LOCAL_TAX1_OPTION') || getDolGlobalString('FACTURE_LOCAL_TAX2_OPTION')) {
	$linecolvat .= $langs->trans('Taxes');
} else {
	$linecolvat .= $langs->trans('VAT');
}

if (in_array($object->element, array('propal', 'commande', 'facture', 'supplier_proposal', 'order_supplier', 'invoice_supplier')) && $object->status == $object::STATUS_DRAFT) {
	global $mysoc;

	if (empty($disableedit) && GETPOST('mode', 'aZ09') != 'vatforalllines') {
		$linecolvat .= '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?mode=vatforalllines&id='.$object->id.'">'.img_edit($langs->trans("UpdateForAllLines"), 0, 'class="clickvatforalllines opacitymedium paddingleft cursorpointer"').'</a>';
	}
	// $linecolvat .= '<script>$(document).ready(function() { $(".clickvatforalllines").click(function() { jQuery(".classvatforalllines").toggle(); }); });</script>';
	if (GETPOST('mode', 'aZ09') == 'vatforalllines') {
		$linecolvat .= '<div class="classvatforalllines inline-block nowraponall">';
		$linecolvat .= $form->load_tva('vatforalllines', '', $mysoc, $object->thirdparty, 0, 0, '', false, 1);
		$linecolvat .= '<input class="inline-block button smallpaddingimp" type="submit" name="submitforalllines" value="'.$langs->trans("Update").'">';
		$linecolvat .= '</div>';
	}
}
$linecolvat .= '</th>';
$arrayofthfields['linecolvat'] = $linecolvat;
// Price HT
$arrayofthfields['linecoluht'] = '<th class="linecoluht right nowraponall">'.$langs->trans('PriceUHT').'</th>';

// Multicurrency
if (isModEnabled("multicurrency") && $this->multicurrency_code != $conf->currency) {
	$arrayofthfields['linecoluht_currency'] = '<th class="linecoluht_currency right" style="width: 80px">'.$langs->trans('PriceUHTCurrency', $this->multicurrency_code).'</th>';
}

if (!empty($inputalsopricewithtax) && !getDolGlobalInt('MAIN_NO_INPUT_PRICE_WITH_TAX')) {
	$arrayofthfields['linecoluttc'] = '<th class="right nowraponall">'.$langs->trans('PriceUTTC').'</th>';
}

// Qty
$arrayofthfields['linecolqty'] = '<th class="linecolqty right">'.$langs->trans('Qty').'</th>';

// Unit
if (getDolGlobalString('PRODUCT_USE_UNITS')) {
	$arrayofthfields['linecoluseunit'] = '<th class="linecoluseunit left">'.$langs->trans('Unit').'</th>';
}

// Reduction short
$linecoldiscount = '<th class="linecoldiscount right nowraponall">';
$linecoldiscount .= $langs->trans('ReductionShort');

if (in_array($object->element, array('propal', 'commande', 'facture')) && $object->status == $object::STATUS_DRAFT) {
	global $mysoc;

	if (empty($disableedit) && GETPOST('mode', 'aZ09') != 'remiseforalllines') {
		$linecoldiscount .= '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?mode=remiseforalllines&id='.$object->id.'">'.img_edit($langs->trans("UpdateForAllLines"), 0, 'class="clickvatforalllines opacitymedium paddingleft cursorpointer"').'</a>';
	}
	// $linecoldiscount .= '<script>$(document).ready(function() { $(".clickremiseforalllines").click(function() { jQuery(".classremiseforalllines").toggle(); }); });</script>';
	if (GETPOST('mode', 'aZ09') == 'remiseforalllines') {
		$linecoldiscount .= '<div class="remiseforalllines inline-block nowraponall">';
		$linecoldiscount .= '<input class="inline-block smallpaddingimp width50 right" name="remiseforalllines" value="" placeholder="%">';
		$linecoldiscount .= '<input class="inline-block button smallpaddingimp" type="submit" name="submitforalllines" value="'.$langs->trans("Update").'">';
		$linecoldiscount .= '</div>';
	}
}
$linecoldiscount .= '</th>';
$arrayofthfields['linecoldiscount'] = $linecoldiscount;

// Fields for situation invoice
if (isset($this->situation_cycle_ref) && $this->situation_cycle_ref) {
	$arrayofthfields['linecolcycleref'] = '<th class="linecolcycleref right">'.$langs->trans('Progress').'</th>';
	$arrayofthfields['linecolcycleref2'] = '<th class="linecolcycleref2 right">'.$form->textwithpicto($langs->trans('TotalHT100Short'), $langs->trans('UnitPriceXQtyLessDiscount')).'</th>';
}

// Purchase price
if ($usemargins && isModEnabled('margin') && empty($user->socid)) {
	if ($user->hasRight('margins', 'creer')) {
		if (getDolGlobalString('MARGIN_TYPE') == "1") {
			$arrayofthfields['linecolmargin1'] = '<th class="linecolmargin1 margininfos right width75">'.$langs->trans('BuyingPrice').'</th>';
		} else {
			$arrayofthfields['linecolmargin1'] = '<th class="linecolmargin1 margininfos right width75">'.$langs->trans('CostPrice').'</th>';
		}
	}

	if (getDolGlobalString('DISPLAY_MARGIN_RATES') && $user->hasRight('margins', 'liretous')) {
		$arrayofthfields['linecolmargin2'] = '<th class="linecolmargin2 margininfos right width75">'.$langs->trans('MarginRate');
		if ($user->hasRight("propal", "creer")) {
			$arrayofthfields['linecolmargin2'] .= '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?mode=marginforalllines&id='.$object->id.'">'.img_edit($langs->trans("UpdateForAllLines"), 0, 'class="clickmarginforalllines opacitymedium paddingleft cursorpointer"').'</a>';
			if (GETPOST('mode', 'aZ09') == 'marginforalllines') {
				$arrayofthfields['linecolmargin2'] .= '<div class="classmarginforalllines inline-block nowraponall">';
				$arrayofthfields['linecolmargin2'] .= '<input type="number" name="marginforalllines" min="0" max="999.9" value="20.0" step="0.1" class="width50"><label>%</label>';
				$arrayofthfields['linecolmargin2'] .= '<input class="inline-block button smallpaddingimp" type="submit" name="submitforallmargins" value="'.$langs->trans("Update").'">';
				$arrayofthfields['linecolmargin2'] .= '</div>';
			}
		}
		$arrayofthfields['linecolmargin2'] .= '</th>';
	}
	if (getDolGlobalString('DISPLAY_MARK_RATES') && $user->hasRight('margins', 'liretous')) {
		$arrayofthfields['linecolmargin2'] = '<th class="linecolmargin2 margininfos right width75">'.$langs->trans('MarkRate').'</th>';
	}
}

// Total HT
$arrayofthfields['linecolht'] = '<th class="linecolht right">'.$langs->trans('TotalHTShort').'</th>';

// Multicurrency
if (isModEnabled("multicurrency") && $this->multicurrency_code != $conf->currency) {
	$arrayofthfields['linecoltotalht_currency'] = '<th class="linecoltotalht_currency right">'.$langs->trans('TotalHTShortCurrency', $this->multicurrency_code).'</th>';
}

if ($outputalsopricetotalwithtax) {
	$arrayofthfields['linecoltotalttc'] = '<th class="right" style="width: 80px">'.$langs->trans('TotalTTCShort').'</th>';
}

if (isModEnabled('asset') && $object->element == 'invoice_supplier') {
	$arrayofthfields['linecolasset'] = '<th class="linecolasset"></th>';
}

$arrayofthfields['linecoledit'] = '<th class="linecoledit"></th>'; // No width to allow autodim

$arrayofthfields['linecoldelete'] = '<th class="linecoldelete" style="width: 10px"></th>';

$arrayofthfields['linecolmove'] = '<th class="linecolmove" style="width: 10px"></th>';

if ($action == 'selectlines') {
	$arrayofthfields['linecheckboxtoggle'] = '<th class="linecolcheckall center">';
	$arrayofthfields['linecheckboxtoggle'] .= '<input type="checkbox" class="linecheckboxtoggle" />';
	$arrayofthfields['linecheckboxtoggle'] .= '<script>$(document).ready(function() {$(".linecheckboxtoggle").click(function() {var checkBoxes = $(".linecheckbox");checkBoxes.prop("checked", this.checked);})});</script>';
	$arrayofthfields['linecheckboxtoggle'] .= '</th>';
}


print "<!-- BEGIN PHP TEMPLATE objectline_title.tpl.php -->\n";
// Title line
print "<thead>\n";
print '<tr class="liste_titre nodrag nodrop">';

// th fields
print implode("\n", $arrayofthfields);

print "</tr>\n";
print "</thead>\n";
print "<!-- END PHP TEMPLATE objectline_title.tpl.php -->\n";
