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
 * @var CommonObject $object
 * @var CommonObject $this
 * @var CommonObjectLine $line
 * @var Conf $conf
 * @var Form $form
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 * @var string $action
 *
 * @var int $num
 * @var int $i
 */

'
@phan-var-force CommonObjectLine|CommonInvoiceLine|CommonOrderLine|ExpeditionLigne|PropaleLigne $line
@phan-var-force CommonObject $this
@phan-var-force Propal|Contrat|Commande|Facture|Expedition|Delivery|FactureFournisseur|FactureFournisseur|SupplierProposal $object
@phan-var-force int $num
';

echo "<!-- BEGIN PHP TEMPLATE subtotal_view.tpl.php -->\n";

$langs->load('subtotals');

$line_options = $line->extraparams["subtotal"] ?? array();

$line_color = $this->getSubtotalColors($line->qty);

echo '<tr data-level="' . $line->qty . '" data-desc="' . $line->desc . '" data-rang="' . $line->rang . '" id="row-' . $line->id . '" class="drag drop" style="background:#' . $line_color . '">';

// Showing line number if conf is enabled
if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
	echo '<td class="linecolnum center"><span class="opacitymedium">' . ($i + 1) . '</span></td>';
}

if ($line->qty > 0) { ?>
	<td class="linecollabel" <?php echo !colorIsLight($line_color) ? ' style="color: white"' : ' style="color: black"' ?>><?php echo str_repeat('&nbsp;', (int) ($line->qty - 1) * 8); ?>
		<?php
		echo $line->desc;
		if (array_key_exists('titleshowuponpdf', $line_options)) {
			echo '&nbsp;' . img_picto($langs->trans("ShowUPOnPDF"), 'invoicing');
		}
		if (array_key_exists('titleshowtotalexludingvatonpdf', $line_options)) {
			echo '&nbsp; <span title="' . $langs->trans("ShowTotalExludingVATOnPDF") . '">%</span>';
		}
		if (array_key_exists('titleforcepagebreak', $line_options)) {
			echo '&nbsp;' . img_picto($langs->trans("ForcePageBreak"), 'file');
		}
		?>
	</td>
	<td class="linecolvat nowrap right">
		<?php
		if ($this->status == 0 && $object->element != 'facturerec') {
			if (GETPOST('mode', 'aZ09') == 'vatforblocklines' && GETPOSTINT('lineid') == $line->id) {
				$type_tva = $type_tva ?? 0;
				print '<div class="inline-block nowraponall">';
				print $form->load_tva('vatforblocklines', '', $mysoc, $object->thirdparty, 0, $line->info_bits, $line->product_type, false, 1, $type_tva);
				print '<input type="hidden" name="lineid" value="' . $line->id . '">';
				print '<input class="inline-block button smallpaddingimp" type="submit" name="updateallvatlinesblock" value="' . $langs->trans("Update") . '">';
				print '</div>';
			} else {
				print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&mode=vatforblocklines&lineid=' . $line->id . '">';
				if (!colorIsLight($line_color)) {
					echo img_edit($langs->trans("ApplyVATForBlock"), 0, 'style="color: white"');
				} else {
					echo img_edit($langs->trans("ApplyVATForBlock"), 0, 'style="color: #666"');
				}
				echo '</a>';
			}
		}
		?>
	</td>
	<td class="linecoluht"></td>
	<?php
	if (isModEnabled("multicurrency") && $this->multicurrency_code != $conf->currency) {
		print '<td class="linecoluht_currency"></td>';
	}
	// Handling colspan if MAIN_NO_INPUT_PRICE_WITH_TAX conf is enabled
	if (!getDolGlobalInt('MAIN_NO_INPUT_PRICE_WITH_TAX') && $object->element != 'facturerec') {
		print '<td class="linecoluttc"></td>';
	}

	print '<td class="linecolqty"></td>';

	// Handling colspan if PRODUCT_USE_UNITS conf is enabled
	if (getDolGlobalString('PRODUCT_USE_UNITS')) {
		print '<td class="linecoluseunit"></td>';
	}
	?>
	<td class="linecoldiscount right">
		<?php
		if ($this->status == 0 && $object->element != 'facturerec') {
			if (GETPOST('mode', 'aZ09') == 'discountforblocklines' && GETPOSTINT('lineid') == $line->id) {
				print '<div class="inline-block nowraponall">';
				print '<input type="text" class="flat right width40" name="discountforblocklines" id="discountforblocklines" value="0"><span class="hideonsmartphone"';
				if (!colorIsLight($line_color)) {
					print 'style="color: white"';
				} else {
					print 'style="color: black"';
				}
				print '>%</span>';
				print '<input type="hidden" name="lineid" value="' . $line->id . '">';
				print '<input class="inline-block button smallpaddingimp" type="submit" name="updatealldiscountlinesblock" value="' . $langs->trans("Update") . '">';
				print '</div>';
			} else {
				print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&mode=discountforblocklines&lineid=' . $line->id . '">';
				if (!colorIsLight($line_color)) {
					echo img_edit($langs->trans("ApplyDiscountForBlock"), 0, 'style="color: white"');
				} else {
					echo img_edit($langs->trans("ApplyDiscountForBlock"), 0, 'style="color: #666"');
				}
				echo '</a>';
			}
		}
		?>
	</td>
	<?php
	// Handling if situation invoices conf is enabled
	if (isset($this->situation_cycle_ref) && $this->situation_cycle_ref) {
		print '<td class="linecolcycleref nowrap right"></td>';
		if (getDolGlobalInt('INVOICE_USE_SITUATION') == 2) {
			print '<td  class="nowrap right"></td>';
		}
		print '<td class="linecolcycleref2 right nowrap"></td>';
	}

	// Handling colspan if margin module is enabled
	if (!empty($object->element) && in_array($object->element, array('facture', 'facturerec', 'propal', 'commande')) && isModEnabled('margin') && empty($user->socid)) {
		if ($user->hasRight('margins', 'creer')) {
			print '<td class="linecolmargin1"></td>';
		}
		if (getDolGlobalString('DISPLAY_MARGIN_RATES') && $user->hasRight('margins', 'liretous')) {
			print '<td class="linecolmargin2"></td>';
		}
		if (getDolGlobalString('DISPLAY_MARK_RATES') && $user->hasRight('margins', 'liretous')) {
			print '<td class="linecolmark1"></td>';
		}
	}
	?>
	<td class="linecolht"></td>
	<?php if (isModEnabled("multicurrency") && $this->multicurrency_code != $conf->currency) { ?>
		<td class="linecolutotalht_currency"></td>
	<?php } ?>
<?php } elseif ($line->qty < 0) {
	// Base colspan if there is no module activated to display line correctly
	$colspan = 3;

	if (isset($this->situation_cycle_ref) && $this->situation_cycle_ref) {
		$colspan += 2;
		if (getDolGlobalInt('INVOICE_USE_SITUATION') == 2) {
			$colspan += 1;
		}
	}

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
	if (!getDolGlobalInt('MAIN_NO_INPUT_PRICE_WITH_TAX') && $object->element != 'facturerec') {
		$colspan += 1;
	}

	// Handling colspan if PRODUCT_USE_UNITS conf is enabled
	if (getDolGlobalString('PRODUCT_USE_UNITS')) {
		$colspan += 1;
	}
	?>
	<td class="linecollabel nowrap right" <?php echo !colorIsLight($line_color) ? ' style="color: white"' : ' style="color: black"' ?> colspan="<?php echo $colspan + 2 ?>">
		<?php
		echo $line->desc;
		if (array_key_exists('subtotalshowtotalexludingvatonpdf', $line_options)) {
			echo '&nbsp; <span title="' . $langs->trans("ShowTotalExludingVATOnPDF") . '">%</span>';
		}
		echo ' :';
		?>
	</td>
	<td class="linecolamount nowrap right" <?php echo !colorIsLight($line_color) ? ' style="color: white"' : ' style="color: black"' ?>>
		<?php
		echo $this->getSubtotalLineAmount($line);
		?>
	</td>
	<?php
	if (isModEnabled('multicurrency') && $object->multicurrency_code != $conf->currency) {
		echo '<td class="linecolamount nowrap right"';
		echo !colorIsLight($line_color) ? ' style="color: white"' : ' style="color: black"';
		echo '>';
		echo $this->getSubtotalLineMulticurrencyAmount($line);
		echo '</td>';
	}
	?>
<?php }

if ($this->status == 0) {
	// Edit picto
	echo '<td class="linecoledit center">';
	echo '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?id=' . $this->id . '&action=editline&token=' . newToken() . '&lineid=' . $line->id . '">';
	if (!colorIsLight($line_color)) {
		echo img_edit('default', 0, 'style="color: white"');
	} else {
		echo img_edit('default', 0, 'style="color: #666"');
	}
	echo '</a> </td>';

	// Delete picto
	echo '<td class="linecoldelete center">';
	echo '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?id=' . $this->id . '&action=ask_subtotal_deleteline&token=' . newToken() . '&lineid=' . $line->id;
	if ($line->qty > 0) {
		echo '&type=title';
	}
	echo '">';
	if (!colorIsLight($line_color)) {
		echo img_delete('default', 'class="pictodelete" style="color: white"');
	} else {
		echo img_delete('default', 'class="pictodelete" style="color: #666"');
	}
	echo '</a> </td>';

	// Move up-down picto
	if ($num > 1 && $conf->browser->layout != 'phone' && ((property_exists($this, 'situation_counter') && $this->situation_counter == 1) || empty($this->situation_cycle_ref)) && empty($disablemove)) {
		echo '<td class="linecolmove tdlineupdown center"';
		if (!colorIsLight($line_color)) {
			echo 'data-gripimg="grip_title.png"';
		}
		echo '>';
		if ($i > 0) {
			echo '<a class="lineupdown" href="' . $_SERVER["PHP_SELF"] . '?id=' . $this->id . '&action=up&token=' . newToken() . '&rowid=' . $line->id . '">';
			echo img_up('default', 0, 'imgupforline');
			echo '</a>';
		}
		if ($i < $num - 1) {
			echo '<a class="lineupdown" href="' . $_SERVER["PHP_SELF"] . '?id=' . $this->id . '&action=down&token=' . newToken() . '&rowid=' . $line->id . '">';
			echo img_down('default', 0, 'imgdownforline');
			echo '</a>';
		}
		echo '</td>';
	} else {
		echo '<td ' . (($conf->browser->layout != 'phone' && empty($disablemove)) ? ' class="linecolmove tdlineupdown center"' : ' class="linecolmove center"') . '></td>';
	}
} else {
	$colspan = 3;
	if (isModEnabled('asset') && $object->element == 'invoice_supplier') {
		$colspan++;
	}
	print '<td colspan="' . $colspan . '"></td>';
}

if ($action == 'selectlines') { ?>
	<td class="linecolcheck center"><input type="checkbox" class="linecheckbox" name="line_checkbox[<?php print $i + 1; ?>]" value="<?php print $line->id; ?>" ></td>
<?php }

echo '</tr>';
echo '<!-- END PHP TEMPLATE subtotal_view.tpl.php -->';
?>
