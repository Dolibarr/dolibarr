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
	}

	function refresh(keepWarehouse) {
		window.clearTimeout(timer);
		version++;
		var currentVersion = version;
		if (request) {
			request.abort();
		}
		clearPreview();
		timer = window.setTimeout(function () {
			if (!(parseInt($form.find('[name="idprod"]').val(), 10) > 0)) {
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
				if (!keepWarehouse && result.selected > 0 && $warehouse.find('option[value="' + result.selected + '"]').length) {
					$warehouse.val(result.selected).trigger('change.select2');
				} else if (!keepWarehouse && result.selected === 0) {
					$warehouse.val('-1').trigger('change.select2');
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

	$form.on('change', '[name="idprod"], select[name^="combinations["]', function () { refresh(false); });
	$form.on('input change', '[name="qty"]', function () { refresh(false); });
	$warehouse.on('change', function () { refresh(true); });
	refresh($preview.attr('data-keep-warehouse') === '1');
});
