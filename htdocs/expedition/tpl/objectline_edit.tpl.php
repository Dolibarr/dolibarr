<?php
/* Copyright (C) 2010-2012	Regis Houssin		        <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2012	Laurent Destailleur	    <eldy@users.sourceforge.net>
 * Copyright (C) 2012		    Christophe Battarel	    <christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013		    Florian Henry		        <florian.henry@open-concept.pro>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		    Vincent Maury		        <vmaury@timgroup.fr>
 * Copyright (C) 2024		    MDW						          <mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025		    Nick Fragoulis
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
 */

require_once DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php";

/**
 * @var CommonObject $this
 * @var CommonObject $object
 * @var HookManager $hookmanager
 * @var CommonObjectLine $line
 * @var Societe $buyer
 * @var Societe $seller
 * @var Translate $langs
 *
 * @var string $action
 * @var int $i
 * @var bool $var
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

'
@phan-var-force expeditionligne $line
@phan-var-force CommonObject $this
@phan-var-force CommonObject $object
@phan-var-force int $i
@phan-var-force bool $var
@phan-var-force Societe $buyer
@phan-var-force Societe $seller
';

global $forceall, $filtertype;

if (empty($forceall)) {
	$forceall = 0;
}

if (empty($filtertype)) {
	$filtertype = 0;
}

$formproduct = new FormProduct($object->db);
$form = new Form($object->db);

// Define colspan for the button 'Add'
$colspan = 3;

// Lines for extrafield
$objectline = new ExpeditionLigne($this->db);

print "<!-- BEGIN PHP TEMPLATE expedition/tpl/objectline_edit.tpl.php -->\n";

$coldisplay = 0;
print '<tr class="oddeven tredited">';
// Adds a line numbering column
if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
	print '<td class="linecolnum center">'.($i + 1).'</td>';
	$coldisplay++;
}

$coldisplay++;
?>
	<td>
	<div id="line_<?php echo $line->id; ?>"></div>

	<input type="hidden" name="lineid" value="<?php echo $line->id; ?>">
	<input type="hidden" id="product_type" name="type" value="<?php echo $line->product_type; ?>">
	<input type="hidden" id="product_id" name="productid" value="<?php echo(!empty($line->fk_product) ? $line->fk_product : 0); ?>" />
	<input type="hidden" id="special_code" name="special_code" value="<?php echo $line->special_code; ?>">

<?php
// Predefined product/service
if ($line->fk_product > 0) {
	$tmpproduct = new Product($object->db);
	$tmpproduct->fetch($line->fk_product);
	print $tmpproduct->getNomUrl(1);
	print ' - '.$tmpproduct->label;
}

//Line extrafield
if (!empty($extrafields)) {
	$temps = $line->showOptionals($extrafields, 'edit', array('class' => 'tredited'), '', '', '1', 'line');
	if (!empty($temps)) {
		print '<div style="padding-top: 10px" id="extrafield_lines_area_edit" name="extrafield_lines_area_edit">';
		print $temps;
		print '</div>';
	}
}

print '</td>';

$coldisplay++;

print '<td class="nobottom linecolqty right">';

if (((int) $line->info_bits & 2) != 2) {
	print '<input size="3" type="text" class="flat right" name="qty" id="qty" value="'.$line->qty.'">';
}
print '</td>';


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

if (isModEnabled('stock')) {
	$coldisplay++;
	print '<td class="nobottom linecolwarehousesource left">';
	if (!empty($line->fk_product)) {
		print $formproduct->selectWarehouses(!empty($line->entrepot_id) ? $line->entrepot_id : 'ifone', 'entrepot_id', '', 1, 0, 0, '', 0, 0, array(), 'minwidth200', array(), 1, false, 'e.ref');
	}
	print '</td>';

	$stockStatus = array('stock_available' => null, 'stock_after' => null, 'can_validate' => true);
	if (function_exists('expedition_standalone_get_stock_status') && !empty($line->fk_product) && !empty($line->entrepot_id)) {
		$stockStatus = expedition_standalone_get_stock_status($object->db, $object->id, (int) $line->fk_product, (int) $line->entrepot_id, (float) $line->qty, (int) $line->id);
	}
	$coldisplay++;
	print '<td class="nobottom linecolstockavailable right"><span id="standalone_stock_available">'.(function_exists('expedition_standalone_format_stock_qty') ? expedition_standalone_format_stock_qty($stockStatus['stock_available']) : '').'</span></td>';
	$coldisplay++;
	print '<td class="nobottom linecolstockafter right"><span id="standalone_stock_after"'.(empty($stockStatus['can_validate']) ? ' class="error"' : '').'>'.(function_exists('expedition_standalone_format_stock_qty') ? expedition_standalone_format_stock_qty($stockStatus['stock_after']) : '').'</span></td>';
}

$coldisplay += $colspan;
print '<td class="nobottom linecoledit center valignmiddle" colspan="'.$colspan.'">';
$coldisplay += $colspan;
print '<input type="submit" class="reposition button buttongen margintoponly marginbottomonly button-save" id="savelinebutton" name="save" value="'.$langs->trans("Save").'">';
print '<input type="submit" class="reposition button buttongen margintoponly marginbottomonly button-cancel" id="cancellinebutton" name="cancel" value="'.$langs->trans("Cancel").'">';
print '</td>';
print '</tr>';

if (isModEnabled('stock')) {
	?>
<script>
jQuery(document).ready(function() {
	var standaloneWarehouseRequest = null;
	var standaloneWarehouseTimer = null;

	function getStandaloneWarehouseFallback($warehouse) {
		var $warehouseOptions = $warehouse.find('option').filter(function() {
			return parseInt(jQuery(this).val(), 10) > 0;
		});

		if ($warehouseOptions.length === 1) {
			return $warehouseOptions.first().val();
		}

		return $warehouse.find('option[value="-1"]').length ? '-1' : '';
	}

	function setStandaloneWarehouse(selectedWarehouse) {
		var $warehouse = jQuery('#entrepot_id');
		if (!$warehouse.length) {
			return;
		}

		var selectedValue = parseInt(selectedWarehouse, 10);
		if (selectedValue > 0 && $warehouse.find('option[value="' + selectedValue + '"]').length) {
			$warehouse.val(selectedValue);
		} else {
			$warehouse.val(getStandaloneWarehouseFallback($warehouse));
		}

		$warehouse.trigger('change');
	}

	function updateStandaloneStock(response) {
		var stockAvailable = response && response.stock_available_formatted ? response.stock_available_formatted : '';
		var stockAfter = response && response.stock_after_formatted ? response.stock_after_formatted : '';

		jQuery('#standalone_stock_available').text(stockAvailable);
		jQuery('#standalone_stock_after').text(stockAfter).toggleClass('error', !!(response && response.can_validate === false));
	}

	function refreshStandaloneWarehouse(keepWarehouse) {
		var $warehouse = jQuery('#entrepot_id');
		var idprod = jQuery('#product_id').val();

		if (!$warehouse.length || !idprod || parseInt(idprod, 10) <= 0) {
			updateStandaloneStock(null);
			return;
		}

		var data = {
			action: 'ajaxselectstandalonewarehouse',
			token: '<?php echo currentToken(); ?>',
			id: '<?php echo (int) $object->id; ?>',
			lineid: '<?php echo (int) $line->id; ?>',
			idprod: idprod,
			qty: jQuery('#qty').val(),
			entrepot_id: $warehouse.val(),
			keepwarehouse: keepWarehouse ? 1 : 0
		};

		if (standaloneWarehouseRequest) {
			standaloneWarehouseRequest.abort();
		}

		standaloneWarehouseRequest = jQuery.getJSON('<?php echo DOL_URL_ROOT; ?>/expedition/card.php', data, function(response) {
			if (!keepWarehouse && response && typeof response.selected !== 'undefined') {
				setStandaloneWarehouse(response.selected);
			}
			updateStandaloneStock(response);
		});
	}

	function scheduleStandaloneWarehouseRefresh(keepWarehouse) {
		window.clearTimeout(standaloneWarehouseTimer);
		standaloneWarehouseTimer = window.setTimeout(function() {
			refreshStandaloneWarehouse(keepWarehouse);
		}, 250);
	}

	jQuery('#qty').on('change keyup', function() { scheduleStandaloneWarehouseRefresh(false); });
	jQuery('#entrepot_id').on('change', function() { scheduleStandaloneWarehouseRefresh(true); });
});
</script>
	<?php
}

print "<!-- END PHP TEMPLATE objectline_edit.tpl.php -->\n";
