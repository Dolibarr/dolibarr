<?php
// Copyright (C) 2014 Cedric GROSS		<c.gross@kreiz-it.fr>
// Copyright (C) 2017 Francis Appels	<francis.appels@z-application.com>
// Copyright (C) 2026 Lenin Rivas		<lenin.rivas777@gmail.com>
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program. If not, see <https://www.gnu.org/licenses/>.
// or see https://www.gnu.org/

/**
 * \file       htdocs/fourn/js/lib_dispatch.js.php
 * \brief      File that include javascript functions used for dispatching qty/stock/lot
 */

if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

session_cache_limiter('public');

require_once '../../main.inc.php';

// Define javascript type
top_httphead('text/javascript; charset=UTF-8');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
header('Cache-Control: max-age=10800, public, must-revalidate');

?>
/**
 * addDispatchLine
 * Adds new table row for dispatching to multiple stock locations or multiple lot/serial
 *
 * @param	index	int		index of product line. 0 = first product line
 * @param	type	string	type of dispatch ('batch' = batch dispatch, 'dispatch' = non batch dispatch)
 * @param	mode	string	'qtymissing' will create new line with qty missing, 'lessone' will keep 1 in old line and the rest in new one
 */
function addDispatchLine(index, type, mode) {
	mode = mode || 'qtymissing'

	console.log("fourn/js/lib_dispatch.js.php addDispatchLine Split line type="+type+" index="+index+" mode="+mode);

	var $row0 = $("tr[name='"+type+'_0_'+index+"']");
	var $dpopt = $row0.find('.hasDatepicker').first().datepicker('option', 'all'); // get current datepicker options to apply the same to the cloned datepickers
	var $row = $row0.clone(true); 		// clone first batch line to jQuery object
	var nbrTrs = $("tr[name^='"+type+"_'][name$='_"+index+"']").length; // count nb of tr line with attribute name that starts with 'batch_' or 'dispatch_', and end with _index
	var qtyOrdered = parseFloat($("#qty_ordered_0_"+index).val()); 		// Qty ordered is same for all rows

	var qty = parseFloat($("#qty_"+(nbrTrs - 1)+"_"+index).val());
	if (isNaN(qty)) {
		qty = '';
	}

	console.log("fourn/js/lib_dispatch.js.php addDispatchLine Split line nbrTrs="+nbrTrs+" qtyOrdered="+qtyOrdered+" qty="+qty);

	var	qtyDispatched;

	if (mode === 'lessone') {
		qtyDispatched = parseFloat($("#qty_dispatched_0_" + index).val()) + 1;
	}
	else {
		qtyDispatched = parseFloat($("#qty_dispatched_0_" + index).val()) + qty;
		// If user did not reduced the qty to dispatch on old line, we keep only 1 on old line and the rest on new line
		if (qtyDispatched == qtyOrdered && qtyDispatched > 1) {
			qtyDispatched = parseFloat($("#qty_dispatched_0_" + index).val()) + 1;
			mode = 'lessone';
		}
	}
	console.log("qtyDispatched=" + qtyDispatched + " qtyOrdered=" + qtyOrdered+ " qty=" + qty);

	if (qty <= 1) {
		window.alert("Remain quantity to dispatch is too low to be split");
	} else {
		var oldlineqty = qtyDispatched;
		var newlineqty = qtyOrdered - qtyDispatched;
		if (newlineqty <= 0) {
			newlineqty = qty - 1;
			oldlineqty = 1;
			$("#qty_"+(nbrTrs - 1)+"_"+index).val(oldlineqty);
		}

		//replace tr suffix nbr
		$row.html($row.html().replace(/_0_/g, "_" + nbrTrs + "_"));

		// jquery's deep clone is incompatible with date pickers (the clone shares data with the original)
		// so we destroy and rebuild the new date pickers
		setTimeout(() => {
			$row.find('.hasDatepicker').each((i, dp) => {
				$(dp).removeData()
					.removeClass('hasDatepicker');
				$(dp).next('img.ui-datepicker-trigger').remove();
				$(dp).datepicker($dpopt);
			});
		}, 0);

		//create new select2 to avoid duplicate id of cloned one
		$row.find("select[name='" + 'entrepot_' + nbrTrs + '_' + index + "']").select2();
		// Copy selected option to new select
		let $prevRow = $("tr[name^='" + type + "_'][name$='_" + index + "']:last")
		let $prevEntr = Number($prevRow.find("select[name='" + 'entrepot_' + (nbrTrs-1) + '_' + index + "']").val())
		$row.find("select[name='" + 'entrepot_' + nbrTrs + '_' + index + "']").val($prevEntr)
		// TODO find solution to keep new tr's after page refresh
		//clear value
		$row.find("input[name^='qty']").val('');
		//change name of new row
		$row.attr('name', type + '_' + nbrTrs + '_' + index);
		//insert new row before last row
		$("tr[name^='" + type + "_'][name$='_" + index + "']:last").after($row);

		//remove cloned select2 with duplicate id.
		$("#s2id_entrepot_" + nbrTrs + '_' + index).detach();			// old way to find duplicated select2 component
		$(".csswarehouse_" + nbrTrs + "_" + index + ":first-child").parent("span.selection").parent(".select2").detach();

		/*  Suffix of lines are:  _ trs.length _ index  */
		$("#qty_"+nbrTrs+"_"+index).focus();
		$("#qty_dispatched_0_"+index).val(oldlineqty);

		//hide all buttons then show only the last one
		$("tr[name^='" + type + "_'][name$='_" + index + "'] .splitbutton").hide();
		$("tr[name^='" + type + "_'][name$='_" + index + "']:last .splitbutton").show();

		$("#reset_" + (nbrTrs) + "_" + index).click(function (event) {
			event.preventDefault();
			id = $(this).attr("id");
			id = id.split("reset_");
			idrow = id[1];
			idlast = $("tr[name^='" + type + "_'][name$='_" + index + "']:last .qtydispatchinput").attr("id");
			if (idlast == $("#qty_" + idrow).attr("id")) {
				console.log("Remove trigger for tr name = " + type + "_" + idrow);
				$('tr[name="' + type + '_' + idrow + '"').remove();
				$("tr[name^='" + type + "_'][name$='_" + index + "']:last .splitbutton").show();
			} else {
				console.log("fourn/js/lib_dispatch.js.php Reset trigger for id = qty_" + idrow);
				$("#qty_" + idrow).val("");
			}
		});

		if (mode === 'lessone')
		{
			qty = 1; // keep 1 in old line
			$("#qty_"+(nbrTrs-1)+"_"+index).val(qty);
		}
		$("#qty_"+nbrTrs+"_"+index).val(newlineqty);
		// Store arbitrary data for dispatch qty input field change event
		$("#qty_" + (nbrTrs - 1) + "_" + index).data('qty', qty);
		$("#qty_" + (nbrTrs - 1) + "_" + index).data('type', type);
		$("#qty_" + (nbrTrs - 1) + "_" + index).data('index', index);
		// Update dispatched qty when value dispatch qty input field changed
		//$("#qty_" + (nbrTrs - 1) + "_" + index).change(this.onChangeDispatchLineQty);
		//Clean bad values
		$("tr[name^='" + type + "_'][name$='_" + index + "']:last").data("remove", "remove");
		$("#lot_number_" + (nbrTrs) + "_" + index).val("");
		$("#idline_" + (nbrTrs) + "_" + index).val("-1");
		$("#qty_" + (nbrTrs) + "_" + index).data('expected', "0");
		$("#lot_number_" + (nbrTrs) + "_" + index).removeAttr("disabled");
		//set focus on lot of new line (if it exists)
		if ($("#lot_number_" + (nbrTrs) + "_" + index).length) {
			$("#lot_number_" + (nbrTrs) + "_" + index).focus().select();
		} else {
			$("#qty_" + (nbrTrs) + "_" + index).focus().select();
		}
	}
}

/**
 * onChangeDispatchLineQty
 *
 * Change event handler for dispatch qty input field,
 * recalculate qty dispatched when qty input has changed.
 * If qty is more than qty ordered reset input qty to max qty to dispatch.
 *
 * element requires arbitrary data qty (value before change), type (type of dispatch) and index (index of product line)
 */
function onChangeDispatchLineQty(element) {
	var type = $(element).data('type'),
		qty = parseFloat($(element).data('expected')),
		changedQty, nbrTrs, dispatchingQty, qtyOrdered, qtyDispatched;
		id = $(element).attr("id");
		id = id.split("_");
		index = id[2];

	if (index >= 0 && type && qty >= 0) {
		nbrTrs = $("tr[name^='" + type + "_'][name$='_" + index + "']").length;
		qtyChanged = parseFloat($(element).val()) - qty; // qty changed
		qtyDispatching = parseFloat($(element).val()); // qty currently being dispatched
		qtyOrdered = parseFloat($("#qty_ordered_0_" + index).val()); // qty ordered
		qtyDispatched = parseFloat($("#qty_dispatched_0_" + index).val()); // qty already dispatched

		console.log("onChangeDispatchLineQty qtyChanged: " + qtyChanged + " qtyDispatching: " + qtyDispatching + " qtyOrdered: " + qtyOrdered + " qtyDispatched: " + qtyDispatched);

		if ((qtyChanged) <= (qtyOrdered - (qtyDispatched + qtyDispatching))) {
			$("#qty_dispatched_0_" + index).val(qtyDispatched + qtyChanged);
		} else {
			/*console.log("eee");
			$(element).val($(element).data('expected'));*/
		}
		$(element).data('expected', $(element).val());
	}
}

$(document).ready(function () {
	$(document).on('keydown', '.inputlotnumber', function (e) {
		if (e.key === 'Enter' || e.keyCode === 13 || e.which === 13) {
			e.preventDefault();

			var $input = $(this);
			var $tr = $input.closest('tr');
			var trName = $tr.attr('name');
			if (!trName) {
				return;
			}

			var parts = trName.split('_');
			if (parts.length < 3) {
				return;
			}

			var type = parts[0];
			var rowIdx = parseInt(parts[1], 10);
			var prodIdx = parseInt(parts[2], 10);

			var $rows = $("tr[name^='" + type + "_'][name$='_" + prodIdx + "']");
			var nbrTrs = $rows.length;

			// If user is on the last row of this product line
			if (rowIdx === nbrTrs - 1) {
				var qty = parseFloat($("#qty_" + rowIdx + "_" + prodIdx).val());
				if (qty > 1) {
					addDispatchLine(prodIdx, type);
				} else {
					// Last line for this product reached, try focusing next product line if available
					var nextProdIdx = prodIdx + 1;
					var $nextLot = $("#lot_number_0_" + nextProdIdx);
					if ($nextLot.length && !$nextLot.prop('disabled')) {
						$nextLot.focus().select();
					}
				}
			} else {
				// Move focus to next row's lot input if it exists
				var $nextRowLot = $("#lot_number_" + (rowIdx + 1) + "_" + prodIdx);
				if ($nextRowLot.length) {
					$nextRowLot.focus().select();
				}
			}
		}
	});
});
