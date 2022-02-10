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
 * \file       htdocs/mrp/js/lib_dispatch.js.php
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
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=10800, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}

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
	if(mode == 'qtymissingconsume') {
		var inputId = 'qtytoconsume';
		var warehouseId = 'idwarehouse';
	}
	else {
		var inputId = 'qtytoproduce';
		var warehouseId = 'idwarehousetoproduce';
	}
	var nbrTrs = $("tr[name^='"+type+"_"+index+"']").length; 				// position of line for batch
	var $row = $("tr[name='"+type+'_'+index+"_1']").clone(true); 				// clone last batch line to jQuery object
	var	qtyOrdered = parseFloat($("#qty_ordered_"+index).val()); 	// Qty ordered is same for all rows
	var	qty = parseFloat($("#"+inputId+"-"+index+"-"+nbrTrs).val());
	var	qtyDispatched;

	if (mode === 'lessone')
	{
		qtyDispatched = parseFloat($("#qty_dispatched_"+index).val()) + 1;
	}
	else
	{
		qtyDispatched = parseFloat($("#qty_dispatched_"+index).val()) + qty;
		console.log($("#qty_dispatched_"+index).val());
		// If user did not reduced the qty to dispatch on old line, we keep only 1 on old line and the rest on new line
		if (qtyDispatched == qtyOrdered && qtyDispatched > 1) {
			qtyDispatched = parseFloat($("#qty_dispatched_"+index).val()) + 1;
			mode = 'lessone';
		}
	}
	console.log("qtyDispatched="+qtyDispatched+" qtyOrdered="+qtyOrdered);

	if (qtyOrdered <= 1) {
		window.alert("Quantity can't be split");
	} else if (qtyDispatched >= qtyOrdered) {
		window.alert("No remain qty to dispatch");
	} else if (qtyDispatched < qtyOrdered) {
		//replace tr suffix nbr
		var re1 = new RegExp('_'+index+'_1', 'g');
		var re2 = new RegExp('-'+index+'-1', 'g');
		$row.html($row.html().replace(re1, '_'+index+'_'+(nbrTrs+1)));
		$row.html($row.html().replace(re2, '-'+index+'-'+(nbrTrs+1)));
		//create new select2 to avoid duplicate id of cloned one
		$row.find("select[name='"+warehouseId+'-'+index+'-'+(nbrTrs+1)+"']").select2();
		// TODO find solution to copy selected option to new select
		// TODO find solution to keep new tr's after page refresh
		//clear value
		$row.find("input[id^='"+inputId+"']").val('');
		//change name of new row
		$row.attr('name',type+'_'+index+'_'+(nbrTrs+1));
		//insert new row before last row
		$("tr[name^='"+type+"_"+index+"_"+nbrTrs+"']:last").after($row);

		//remove cloned select2 with duplicate id.
		$("#s2id_entrepot_"+nbrTrs+'_'+index).detach();			// old way to find duplicated select2 component
		$(".csswarehouse_"+index+"_"+(nbrTrs+1)+":first-child").parent("span.selection").parent(".select2").detach();

		/*  Suffix of lines are:  index _ trs.length */
		$("#"+inputId+"-"+index+"-"+(nbrTrs+1)).focus();
		if ($("#"+inputId+"-"+index+"-"+(nbrTrs)).val() == 0) {
			$("#"+inputId+"-"+index+"-"+(nbrTrs)).val(1);
		}
		var totalonallines = 0;
		for (let i = 1; i <= nbrTrs; i++) {
			console.log(i+" = "+parseFloat($("#"+inputId+"-"+index+"-"+i).val()));
			totalonallines = totalonallines + parseFloat($("#"+inputId+"-"+index+"-"+i).val());
		}
		console.log("totalonallines="+totalonallines);
		if (totalonallines == qtyOrdered && qtyOrdered > 1) {
			var prevouslineqty = $("#"+inputId+"-"+index+"-"+nbrTrs).val();
			$("#"+inputId+"-"+index+"-"+(nbrTrs)).val(1);
			$("#"+inputId+"-"+index+"-"+(nbrTrs+1)).val(prevouslineqty - 1);
		}
		$("#qty_dispatched_"+index).val(qtyDispatched);

		//hide all buttons then show only the last one
		$("tr[name^='"+type+"_'][name$='_"+index+"'] .splitbutton").hide();
		$("tr[name^='"+type+"_'][name$='_"+index+"']:last .splitbutton").show();

		if (mode === 'lessone')
		{
			qty = 1; // keep 1 in old line
			$("#qty_"+(nbrTrs-1)+"_"+index).val(qty);
		}
		// Store arbitrary data for dispatch qty input field change event
		$("#"+inputId+"-"+index+(nbrTrs)).data('qty', qty);
		$("#"+inputId+"-"+index+(nbrTrs)).data('type', type);
		$("#"+inputId+"-"+index+(nbrTrs)).data('index', index);
	}
}

