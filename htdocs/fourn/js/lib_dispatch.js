// Copyright (C) 2014 Cedric GROSS		<c.gross@kreiz-it.fr>
// Copyright (C) 2015 Francis Appels	<francis.appels@z-application.com>
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
// along with this program. If not, see <http://www.gnu.org/licenses/>.
// or see http://www.gnu.org/

//
// \file       htdocs/core/js/lib_dispatch.js
// \brief      File that include javascript functions used dispatch.php
//

/**
 * addDispatchLine
 * Adds new table row for dispatching to multiple stock locations
 * 
 * @param	index	int		index of produt line. 0 = first product line
 * @param	type	string	type of dispatch (batch = batch dispatch, dispatch = non batch dispatch)
 */
function addDispatchLine(index,type) 
{
	var $row = $("tr[name='"+type+'_0_'+index+"']").clone(true), // clone first batch line to jQuery object
		nbrTrs = $("tr[name^='"+type+"_'][name$='_"+index+"']").length, // position of line for batch
		qtyOrdered = parseFloat($("#qty_ordered_"+(nbrTrs - 1)+"_"+index).val()),
		qty = parseFloat($("#qty_"+(nbrTrs - 1)+"_"+index).val()),
		qtyDispatched;
			
	if (type === 'batch') 
	{
		qtyDispatched = parseFloat($("#qty_dispatched_"+(nbrTrs - 1)+"_"+index).val()) + 1;
	}
	else
	{
		qtyDispatched = parseFloat($("#qty_dispatched_"+(nbrTrs - 1)+"_"+index).val()) + qty;
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
		$("#s2id_entrepot_"+nbrTrs+'_'+index).detach();		
		/*  Suffix of lines are:  _ trs.length _ index  */
		$("#qty_"+nbrTrs+"_"+index).focus();
		$("#qty_dispatched_"+(nbrTrs)+"_"+index).val(qtyDispatched);
		if (type === 'batch')
		{
			$("#qty_"+(nbrTrs)+"_"+index).val(qty-1);
			$("#qty_"+(nbrTrs-1)+"_"+index).val(1);
		}
		else
		{
			
			$("#qty_"+nbrTrs+"_"+index).val(qtyOrdered - qtyDispatched);	
		}		
	}
}