<?php
// Copyright (C) 2014 Cedric GROSS		<c.gross@kreiz-it.fr>
// Copyright (C) 2017 Francis Appels	<francis.appels@z-application.com>
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

if (!defined('NOREQUIRESOC'))    define('NOREQUIRESOC', '1');
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN'))         define('NOLOGIN', 1);
if (!defined('NOREQUIREMENU'))   define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML'))   define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX', '1');

session_cache_limiter('public');

require_once '../../main.inc.php';

// Define javascript type
top_httphead('text/javascript; charset=UTF-8');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=10800, public, must-revalidate');
else header('Cache-Control: no-cache');

?>
/**
 * addDispatchLine
 * Adds new table row for dispatching to multiple stock locations or multiple lot/serial
 *
 * @param	index	int		index of product line. 0 = first product line
 * @param	type	string	type of dispatch (batch = batch dispatch, dispatch = non batch dispatch)
 * @param	mode	string	'qtymissing' will create new line with qty missing, 'lessone' will keep 1 in old line and the rest in new one
 */
function addDispatchLine(index, type, mode)
{
	mode = mode || 'qtymissing'

	console.log("fourn/js/lib_dispatch.js.php Split line type="+type+" index="+index+" mode="+mode);
	var $row = $("tr[name='"+type+'_0_'+index+"']").clone(true); 		// clone first batch line to jQuery object
	var nbrTrs = $("tr[name^='"+type+"_'][name$='_"+index+"']").length; // position of line for batch
	var qtyOrdered = parseFloat($("#qty_ordered_0_"+index).val()); 		// Qty ordered is same for all rows
	var qty = parseFloat($("#qty_"+(nbrTrs - 1)+"_"+index).val());
	var	qtyDispatched;

	if (mode === 'lessone')
	{
		qtyDispatched = parseFloat($("#qty_dispatched_0_"+index).val()) + 1;
	}
	else
	{
		qtyDispatched = parseFloat($("#qty_dispatched_0_"+index).val()) + qty;
		// If user did not reduced the qty to dispatch on old line, we keep only 1 on old line and the rest on new line
		if (qtyDispatched == qtyOrdered && qtyDispatched > 1) {
			qtyDispatched = parseFloat($("#qty_dispatched_0_"+index).val()) + 1;
			mode = 'lessone';
		}
	}
	console.log("qtyDispatched="+qtyDispatched+" qtyOrdered="+qtyOrdered);

	if (qtyOrdered <= 1) {
		window.alert("Quantity can't be split");
	}
	if (qtyDispatched < qtyOrdered)
	{
		//replace tr suffix nbr
		$row.html($row.html().replace(/_0_/g,"_"+nbrTrs+"_"));
		//create new select2 to avoid duplicate id of cloned one
		$row.find("select[name='"+'entrepot_'+nbrTrs+'_'+index+"']").select2();
		// TODO find solution to copy selected option to new select
		// TODO find solution to keep new tr's after page refresh
		//clear value
		$row.find("input[name^='qty']").val('');
		//change name of new row
		$row.attr('name',type+'_'+nbrTrs+'_'+index);
		//insert new row before last row
		$("tr[name^='"+type+"_'][name$='_"+index+"']:last").after($row);

		//remove cloned select2 with duplicate id.
		$("#s2id_entrepot_"+nbrTrs+'_'+index).detach();			// old way to find duplicated select2 component
		$(".csswarehouse_"+nbrTrs+"_"+index+":first-child").parent("span.selection").parent(".select2").detach();

		/*  Suffix of lines are:  _ trs.length _ index  */
		$("#qty_"+nbrTrs+"_"+index).focus();
		$("#qty_dispatched_0_"+index).val(qtyDispatched);

		//hide all buttons then show only the last one
		$("tr[name^='"+type+"_'][name$='_"+index+"'] .splitbutton").hide();
		$("tr[name^='"+type+"_'][name$='_"+index+"']:last .splitbutton").show();

		if (mode === 'lessone')
		{
			qty = 1; // keep 1 in old line
			$("#qty_"+(nbrTrs-1)+"_"+index).val(qty);
		}
		$("#qty_"+nbrTrs+"_"+index).val(qtyOrdered - qtyDispatched);
		// Store arbitrary data for dispatch qty input field change event
		$("#qty_"+(nbrTrs-1)+"_"+index).data('qty', qty);
		$("#qty_"+(nbrTrs-1)+"_"+index).data('type', type);
		$("#qty_"+(nbrTrs-1)+"_"+index).data('index', index);
		// Update dispatched qty when value dispatch qty input field changed
		$("#qty_"+(nbrTrs-1)+"_"+index).change(this.onChangeDispatchLineQty);
		//set focus on lot of new line (if it exists)
		$("#lot_number_"+(nbrTrs)+"_"+index).focus();
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

function onChangeDispatchLineQty() {
	var	index = $(this).data('index'),
		type = $(this).data('type'),
		qty = parseFloat($(this).data('qty')),
		changedQty, nbrTrs, dispatchingQty, qtyOrdered, qtyDispatched;

	if (index >= 0 && type && qty >= 0) {
		nbrTrs = $("tr[name^='"+type+"_'][name$='_"+index+"']").length;
		qtyChanged = parseFloat($(this).val()) - qty; // qty changed
		qtyDispatching = parseFloat($("#qty_"+(nbrTrs-1)+"_"+index).val()); // qty currently being dispatched
		qtyOrdered = parseFloat($("#qty_ordered_0_"+index).val()); // qty ordered
		qtyDispatched = parseFloat($("#qty_dispatched_0_"+index).val()); // qty already dispatched

		console.log("onChangeDispatchLineQty qtyChanged: " + qtyChanged + " qtyDispatching: " + qtyDispatching + " qtyOrdered: " + qtyOrdered + " qtyDispatched: "+ qtyDispatched);

		if ((qtyChanged) <= (qtyOrdered - (qtyDispatched + qtyDispatching))) {
			$("#qty_dispatched_0_"+index).val(qtyDispatched + qtyChanged);
		} else {
			$(this).val($(this).data('qty'));
		}
		$(this).data('qty', $(this).val());
	}
}
