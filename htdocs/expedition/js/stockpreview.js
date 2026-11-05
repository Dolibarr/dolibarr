/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/* global jQuery */
jQuery(function ($) {
	var $preview = $('#shipment-stock-preview');
	if (!$preview.length) {
		return;
	}
	var $form = $preview.closest('form');
	var $warehouse = $form.find('[name="entrepot_id"]');
	var request = null;
	var timer = null;
	var version = 0;

	function clearPreview() {
		$form.find('.shipment-stock-available, .shipment-stock-after').text('').removeClass('error');
		$form.find('.shipment-stock-suggestion').text('');
	}

	function refresh() {
		window.clearTimeout(timer);
		version++;
		var currentVersion = version;
		if (request) {
			request.abort();
		}
		clearPreview();
		var keepWarehouse = parseInt($warehouse.val(), 10) > 0;
		$form.find('.shipment-stock-no-warehouse').toggleClass('hideobject', keepWarehouse);
		timer = window.setTimeout(function () {
			if ($form.find('[name="prod_entry_mode"]:checked').val() === 'free' || !(parseInt($form.find('[name="idprod"]').val(), 10) > 0)) {
				return;
			}
			var data = $form.serializeArray().filter(function (field) {
				return ['token', 'id', 'idprod', 'qty', 'entrepot_id'].indexOf(field.name) >= 0 || /^combinations\[/.test(field.name);
			});
			data.push({name: 'keepwarehouse', value: keepWarehouse ? 1 : 0});
			request = $.getJSON($preview.attr('data-url'), data).done(function (result) {
				if (currentVersion !== version) {
					return;
				}
				if (!keepWarehouse && result.selected > 0) {
					$form.find('.shipment-stock-suggestion').text($preview.attr('data-suggestion-label') + ': ' + $warehouse.find('option[value="' + result.selected + '"]').text());
				}
				$form.find('.shipment-stock-available').text(result.stock_available_formatted);
				$form.find('.shipment-stock-after').text(result.stock_after_formatted).toggleClass('error', result.insufficient);
			}).fail(function () {
				if (currentVersion === version) {
					clearPreview();
				}
			});
		}, 250);
	}

	$form.on('change', '[name="idprod"], [name="prod_entry_mode"], select[name^="combinations["]', refresh);
	$form.on('input change', '[name="qty"]', refresh);
	$warehouse.on('change', refresh);
	refresh();
});
