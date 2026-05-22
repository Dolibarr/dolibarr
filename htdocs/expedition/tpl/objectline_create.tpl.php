<?php
/* Copyright (C) 2010-2012	Regis Houssin			    <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2014	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel			<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     		<csalvador@gpcsolutions.fr>
 * Copyright (C) 2014		Florian Henry			    <florian.henry@open-concept.pro>
 * Copyright (C) 2014       Raphaël Doursenaud  		<rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015-2016	Marcos García			    <marcosgdf@gmail.com>
 * Copyright (C) 2018-2024  Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2018		Ferran Marcet			    <fmarcet@2byte.es>
 * Copyright (C) 2024		Vincent Maury			    <vmaury@timgroup.fr>
 * Copyright (C) 2024-2025	MDW						    <mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025		Nick Fragoulis
 * Copyright (C) 2026		Pierre Ardoin				<developpeur@lesmetiersdubatiment.fr>
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
 * $forceall (0 by default, 1 for supplier invoices/orders)
 */

require_once DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php";

/**
 * @var CommonObject $this
 * @var CommonObject $object
 * @var Form $form
 * @var Societe $buyer
 * @var Translate $langs
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error: this template page cannot be called directly as an URL";
	exit;
}

'
@phan-var-force CommonObject $this
@phan-var-force CommonObject $object
@phan-var-force Societe $buyer
';

global $forceall, $forcetoshowtitlelines, $filtertype;

if (empty($forceall)) {
	$forceall = 0;
}

if (empty($filtertype)) {
	$filtertype = 0;
}

$formproduct = new FormProduct($object->db);

print "<!-- BEGIN PHP TEMPLATE expedition/tpl/objectline_create.tpl.php -->\n";

$nolinesbefore = (count($object->lines) == 0 || $forcetoshowtitlelines);

if ($nolinesbefore) {
	print '<tr class="liste_titre nodrag nodrop">';
	if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
		print '<td class="linecolnum center"></td>';
	}
	print '<td class="linecoldescription minwidth500imp">';
	print '<div id="add"></div><span class="hideonsmartphone">'.$langs->trans('AddNewLine').'</span>';
	print '</td>';
	print '<td class="linecolqty right">'.$langs->trans('Qty').'</td>';

	if (getDolGlobalString('PRODUCT_USE_UNITS')) {
		print '<td class="linecoluseunit left">'.$langs->trans('Unit').'</td>';
	}

	if (isModEnabled('stock')) {
		print '<td class="linecolwarehousesource left">'.$langs->trans('WarehouseSource').'</td>';
		print '<td class="linecolstockavailable right">'.$langs->trans('StockAvailable').'</td>';
		print '<td class="linecolstockafter right">'.$langs->trans('StockAfterShipment').'</td>';
	}

	print '<td class="linecoledit"></td>';
	print '<td class="linecoldelete"></td>';
	print '<td class="linecolmove"></td>';

	print '</tr>';
}

print '<tr class="nodrag nodrop nohoverpair'.(($nolinesbefore || $object->element == 'contrat') ? '' : ' liste_titre_create').'">';
$coldisplay = 0;

// Adds a line numbering column
if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
	$coldisplay++;
	echo '<td class="bordertop linecolnum center"></td>';
}

// Product
$coldisplay++;
print '<td class="bordertop linecoldescription line minwidth500imp">';

// Predefined product/service
if (isModEnabled("product") || isModEnabled("service")) {
	$filtertypeforselect = $filtertype;
	if ($filtertypeforselect != 1 && isModEnabled('service') && (getDolGlobalString('STOCK_SUPPORTS_SERVICES') || getDolGlobalString('SHIPMENT_SUPPORTS_SERVICES'))) {
		$filtertypeforselect = isModEnabled('product') ? '' : 1;
	}
	if ($filtertypeforselect == 1) {
		print $langs->trans("Service");
	} elseif ($filtertypeforselect === '') {
		print $langs->trans("ProductOrService");
	} else {
		print $langs->trans("Product");
	}

	echo '<span class="prod_entry_mode_predef nowraponall">';

	$statustoshow = -1;
	$statuswarehouse = '';
	if (getDolGlobalString('ENTREPOT_EXTRA_STATUS')) {
		$statuswarehouse = 'warehouseopen,warehouseinternal';
		if (getDolGlobalString('ENTREPOT_WAREHOUSEINTERNAL_NOT_SELL')) {
			$statuswarehouse = 'warehouseopen';
		}
	}
	print $form->select_produits(GETPOSTINT('idprod'), 'idprod', $filtertypeforselect, getDolGlobalInt('PRODUIT_LIMIT_SIZE'), 0, $statustoshow, 2, '', 1, array(), 0, '1', 0, 'maxwidth500 widthcentpercentminusx', 0, $statuswarehouse, GETPOST('combinations', 'array:alphanohtml'));
	if ($filtertypeforselect == 1 && isModEnabled('service')) {
		$urltocreateproduct = DOL_URL_ROOT.'/product/card.php?action=create&leftmenu=service&type=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?id='.$object->id);
		print '<a href="'.$urltocreateproduct.'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddService").'"></span></a>';
	} else {
		$urltocreateproduct = DOL_URL_ROOT.'/product/card.php?action=create&leftmenu=product&type=0&backtopage='.urlencode($_SERVER["PHP_SELF"].'?id='.$object->id);
		print '<a href="'.$urltocreateproduct.'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddProduct").'"></span></a>';
	}

	echo '</span>';
}

print '</td>';

// Qty
$coldisplay++;
print '<td class="bordertop linecolqty right"><input type="text" size="2" name="qty" id="qty" class="flat right" value="'.(GETPOSTISSET("qty") ? GETPOST("qty", 'alpha', 2) : 1).'">';
print '</td>';

// Unit, kept empty because standalone shipments use the catalog product unit.
if (getDolGlobalString('PRODUCT_USE_UNITS')) {
	$coldisplay++;
	print '<td class="bordertop linecoluseunit left"></td>';
}

// Warehouse source
if (isModEnabled('stock')) {
	$coldisplay++;
	print '<td class="bordertop linecolwarehousesource left">';
	$selectedWarehouse = GETPOSTISSET('entrepot_id') ? GETPOSTINT('entrepot_id') : 'ifone';
	$idprodforwarehouse = GETPOSTINT('idprod');
	$qtyforwarehouse = price2num(GETPOSTISSET('qty') ? GETPOST('qty', 'alpha') : 1, 'MS', 2);
	if ($idprodforwarehouse > 0 && function_exists('expedition_standalone_resolve_product_id')) {
		$idprodforwarehouse = expedition_standalone_resolve_product_id($object->db, $idprodforwarehouse, GETPOST('combinations', 'array:alphanohtml'));
	}
	if (!GETPOSTISSET('entrepot_id') && function_exists('expedition_standalone_get_warehouse_for_product_qty')) {
		$computedWarehouse = expedition_standalone_get_warehouse_for_product_qty($object->db, $object->id, $idprodforwarehouse, (float) $qtyforwarehouse);
		$selectedWarehouse = ($computedWarehouse > 0) ? $computedWarehouse : 'ifone';
	}
	print $formproduct->selectWarehouses($selectedWarehouse, 'entrepot_id', '', 1, 0, 0, '', 0, 0, array(), 'minwidth200', array(), 1, false, 'e.ref');
	print '</td>';
	$stockStatus = array('stock_available' => null, 'stock_after' => null, 'can_validate' => true);
	if (function_exists('expedition_standalone_get_stock_status') && (int) $selectedWarehouse > 0 && $idprodforwarehouse > 0) {
		$stockStatus = expedition_standalone_get_stock_status($object->db, $object->id, $idprodforwarehouse, (int) $selectedWarehouse, (float) $qtyforwarehouse);
	}
	$coldisplay++;
	print '<td class="bordertop linecolstockavailable right"><span id="standalone_stock_available">'.(function_exists('expedition_standalone_format_stock_qty') ? expedition_standalone_format_stock_qty($stockStatus['stock_available']) : '').'</span></td>';
	$coldisplay++;
	print '<td class="bordertop linecolstockafter right"><span id="standalone_stock_after"'.(empty($stockStatus['can_validate']) ? ' class="error"' : '').'>'.(function_exists('expedition_standalone_format_stock_qty') ? expedition_standalone_format_stock_qty($stockStatus['stock_after']) : '').'</span></td>';
}

$coldisplay++;
print '<td class="bordertop linecoledit right valignmiddle">';
print '<input type="submit" class="button button-add small" name="addline" id="addline" value="' . $langs->trans('Add') . '">';
print '</td>';
$coldisplay++;
print '<td class="bordertop linecoldelete"></td>';
$coldisplay++;
print '<td class="bordertop linecolmove"></td>';
print '</tr>';

?>

<script>

jQuery(document).ready(function() {
<?php if (isModEnabled('stock')) { ?>
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
		var idprod = jQuery('#idprod').val();

		if (!$warehouse.length || !idprod || parseInt(idprod, 10) <= 0) {
			updateStandaloneStock(null);
			return;
		}

		var data = {
			action: 'ajaxselectstandalonewarehouse',
			token: '<?php echo currentToken(); ?>',
			id: '<?php echo (int) $object->id; ?>',
			idprod: idprod,
			qty: jQuery('#qty').val(),
			entrepot_id: $warehouse.val(),
			keepwarehouse: keepWarehouse ? 1 : 0
		};

		jQuery('select[name^="combinations["]').each(function() {
			data[jQuery(this).attr('name')] = jQuery(this).val();
		});

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

	jQuery('#idprod').on('change', function() { scheduleStandaloneWarehouseRefresh(false); });
	jQuery('#qty').on('change keyup', function() { scheduleStandaloneWarehouseRefresh(false); });
	jQuery('#entrepot_id').on('change', function() { scheduleStandaloneWarehouseRefresh(true); });
	jQuery(document).on('change', 'select[name^="combinations["]', function() { scheduleStandaloneWarehouseRefresh(false); });
<?php } ?>
});

</script>

<!-- END PHP TEMPLATE objectline_create.tpl.php -->
