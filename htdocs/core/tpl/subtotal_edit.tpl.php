<?php
/* Copyright (C) 2014-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 * or see https://www.gnu.org/
 */

/**
 * @var CommonObject $this
 * @var CommonObject $object
 * @var CommonObjectLine $line
 * @var Form $form
 * @var Translate $langs
 * @var User $user
 * @var Conf $conf
 * @var int $i
 */

'
@phan-var-force Propal|Contrat|Commande|Facture|Expedition|Delivery|FactureFournisseur|FactureFournisseur|SupplierProposal $object
@phan-var-force CommonObjectLine|CommonInvoiceLine|CommonOrderLine|ExpeditionLigne|PropaleLigne $line
';

// Options for subtotal
$sub_options = $line->extraparams["subtotal"] ?? array();

$titleshowuponpdf = !empty($sub_options['titleshowuponpdf']);
$titleshowtotalexludingvatonpdf = !empty($sub_options['titleshowtotalexludingvatonpdf']);
$titleforcepagebreak = !empty($sub_options['titleforcepagebreak']);
$subtotalshowtotalexludingvatonpdf = !empty($sub_options['subtotalshowtotalexludingvatonpdf']);

$line_options = array(
	'titleshowuponpdf' => array('type' => array('title'), 'value' => 'on', 'checked' => $titleshowuponpdf, 'trans_key' => 'ShowUPOnPDF'),
	'titleshowtotalexludingvatonpdf' => array('type' => array('title'), 'value' => 'on', 'checked' => $titleshowtotalexludingvatonpdf, 'trans_key' => 'ShowTotalExludingVATOnPDF'),
	'titleforcepagebreak' => array('type' => array('title'), 'value' => 'on', 'checked' => $titleforcepagebreak, 'trans_key' => 'ForcePageBreak'),
	'subtotalshowtotalexludingvatonpdf' => array('type' => array('subtotal'), 'value' => 'on', 'checked' => $subtotalshowtotalexludingvatonpdf, 'trans_key' => 'ShowTotalExludingVATOnPDF'),
);

// Line type
$line_type = $line->qty > 0 ? 'title' : 'subtotal';

print "<!-- BEGIN PHP TEMPLATE subtotal_edit.tpl.php -->\n";

echo '<tr class="oddeven tredited">';

if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
	echo '<td class="linecolnum center">' . ($i + 1) . '</td>';
}

// Base colspan if there is no module activated to display line correctly
$colspan = 4;

// Handling colspan if margin module is enabled
if (!empty($object->element) && in_array($object->element, array('facture', 'facturerec', 'propal', 'commande')) && isModEnabled('margin') && empty($user->socid)) {
	if ($user->hasRight('margins', 'creer')) {
		$colspan += 1;
	}
	if (getDolGlobalString('DISPLAY_MARGIN_RATES') && $user->hasRight('margins', 'liretous')) {
		$colspan += 1;
	}
	if (getDolGlobalString('DISPLAY_MARK_RATES') && $user->hasRight('margins', 'liretous')) {
		$colspan += 1;
	}
}

// Handling colspan if multicurrency module is enabled
if (isModEnabled('multicurrency') && $object->multicurrency_code != $conf->currency) {
	$colspan += 1;
}

// Handling colspan if MAIN_NO_INPUT_PRICE_WITH_TAX conf is enabled
if (!getDolGlobalInt('MAIN_NO_INPUT_PRICE_WITH_TAX')) {
	$colspan += 1;
}

// Handling colspan if PRODUCT_USE_UNITS conf is enabled
if (getDolGlobalString('PRODUCT_USE_UNITS')) {
	$colspan += 1;
}

?>

<td class="linecoldesc minwidth250onall">
	<div id="line_<?php echo $line->id; ?>"></div>

	<input type="hidden" name="lineid" value="<?php echo $line->id; ?>">
	<input type="hidden" id="product_type" name="type" value="<?php echo $line->product_type; ?>">
	<input type="hidden" id="special_code" name="special_code" value="<?php echo $line->special_code; ?>">
	<input type="hidden" id="fk_parent_line" name="fk_parent_line" value="<?php echo $line->fk_parent_line; ?>">
	<input type="hidden" name="action" value="update<?php echo $line_type ?>line">

	<?php

	$situationinvoicelinewithparent = 0;
	if ($line->fk_prev_id != null && in_array($object->element, array('facture', 'facturedet'))) {
		/** @var CommonInvoice $object */
		// @phan-suppress-next-line PhanUndeclaredConstantOfClass
		if ($object->type == $object::TYPE_SITUATION) {    // The constant TYPE_SITUATION exists only for object invoice
			// Set constant to disallow editing during a situation cycle
			$situationinvoicelinewithparent = 1;
		}
	}

	// Do not allow editing during a situation cycle
	// but in some situations that is required (update legal information for example)
	if (getDolGlobalString('INVOICE_SITUATION_CAN_FORCE_UPDATE_DESCRIPTION')) {
		$situationinvoicelinewithparent = 0;
	}

	$langs->load('subtotals');


	if (!$situationinvoicelinewithparent) {
		print '<input type="text" name="line_desc" class="marginrightonly" id="line_desc" value="';
		print GETPOSTISSET('product_desc') ? GETPOST('product_desc', 'restricthtml') : $line->description . '"';
		$disabled = 0;
		if ($line_type == 'subtotal') {
			print ' readonly="readonly"';
			$disabled = 1;
		}
		print '>';
		$depth_array = $this->getPossibleLevels($langs);
		print $form->selectarray('line_depth', $depth_array, abs($line->qty), 0, 0, 0, '', 0, 0, $disabled);
		if ($disabled) {
			print '<input type="hidden" name="line_depth" value="' . $line->qty . '">';
		}
		print '<div><ul class="ecmjqft">';
		foreach ($line_options as $key => $value) {
			if (in_array($line_type, $value['type'])) {
				print '<li><label for="' . $key . '">' . $langs->trans($value['trans_key']) . '</label>';
				print '<input style="float: left;" id="' . $key . '" type="checkbox" name="' . $key . '" value="' . $value['value'] . '" ';
				print $value['checked'] ? 'checked' : '';
				print '></li>';
			}
		}
		print '</ul></div></td>';
		print '<td colspan="' . $colspan . '" class="right"></td>';
	} else {
		print '<input type="text" readonly name="line_desc" id="line_desc" value="';
		print GETPOSTISSET('product_desc') ? GETPOST('product_desc', 'restricthtml') : $line->description;
		print '"></td>';
	}
	?>


<td class="center valignmiddle" colspan="4">
	<input type="submit" class="reposition button buttongen button-save" id="savelinebutton marginbottomonly" name="save" value="<?php echo $langs->trans("Save"); ?>"><br>
	<input type="submit" class="reposition button buttongen button-cancel" id="cancellinebutton" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>">
</td>
</tr>

<!-- END PHP TEMPLATE objectline_edit.tpl.php -->
