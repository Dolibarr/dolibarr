// Copyright (C) 2014 Cedric GROSS	<c.gross@kreiz-it.fr>
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
// \file       htdocs/core/js/lib_batch.js
// \brief      File that include javascript functions used when dispatching batch-enabled product
//

/**
 * addLineBatch
 * @deprecated replaced by addDispatchLine and moved to module folder and file fourn/js/lib_dispatch.js
 *
 * @param	index	int		number of produt. 0 = first product line
 */
function addLineBatch(index) 
{
	var nme = 'dluo_0_'+index;
	$row=$("tr[name='"+nme+"']").clone(true);
	$row.find("input[name^='qty']").val('');
	var trs = $("tr[name^='dluo_'][name$='_"+index+"']");			/* trs.length = position of line for batch */
	var newrow=$row.html().replace(/_0_/g,"_"+(trs.length)+"_");
	$row.html(newrow);
	//clear value
	$row.find("input[name^='qty']").val('');
	//change name of row
	$row.attr('name','dluo_'+trs.length+'_'+index);
	$("tr[name^='dluo_'][name$='_"+index+"']:last").after($row);
	
	/*  Suffix of lines are:  _ trs.length _ index  */
	jQuery("#lot_number_"+trs.length+"_"+index).focus();
	nb = jQuery("#qty_"+(trs.length - 1)+"_"+index).val();
	if (nb > 0)
	{
		jQuery("#qty_"+(trs.length - 1)+"_"+index).val(1);
		jQuery("#qty_"+trs.length+"_"+index).val(nb - 1);
	}
}
