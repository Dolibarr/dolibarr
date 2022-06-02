/* Copyright (C) 2015 Laurent Destailleur <eldy@users.sourceforge.net>
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
 */

/**
 *  \file       htdocs/expedition/js/expeditionline_dispatcher.js
 *  \ingroup    expedition
 *  \brief      Script to manage shipment line dispatch
 *
 * 	@Use jQuery
 */

var ExpeditionLineDispatcher = function(jQuery) {
	/**
	 * Add new row from table for dispatching
	 * (duplicate line and select the max quantity remaining to dispatch)
	 *
	 * @param	{string}		prefix			Dispatcher prefix
	 * @param	{string}		lineId			Dispatcher line id (suffix)
	 * @param	{string}		suffixId		Dispatcher suffix id (suffix)
	 */
	this.addLine = function(prefix, lineId, suffixId) {
		// dispatcher suffix
		var suffix = lineId+'_'+suffixId;

		// nb lines dispatched
		var nbLine = jQuery('tr[name^="'+prefix+suffix+'"]').length;

		// dispatcher suffix with line
		var suffixWithLine = suffix+'_'+nbLine;

		// determine quantity to dispatch
		var qtyToSend = parseFloat(
			jQuery('#'+prefix+'qty_to_send_'+lineId+'_0').val()
		);
		var qtyMultiply = parseFloat(
			jQuery('#'+prefix+suffix+'_0').attr('data-qty-multiply')
		);
		var qtyToDispatch = qtyMultiply * qtyToSend;

		// get all quantities already selected (already dispatched)
		var qtyDispatched = 0;
		for (var i = 0; i < nbLine; i++) {
			qtyDispatched += parseFloat(jQuery('#'+prefix+'qty_'+suffix+'_'+i).val());
		}

		// get the first line (dispatcher line) to clone
		var rowElem = jQuery('tr[name="'+prefix+suffix+'_0"]').clone(false);
		// replace with new names
		var dispatcherRegex = new RegExp('_'+suffix+'_0', 'g');
		rowElem.html(
			rowElem.html().replace(dispatcherRegex, '_'+suffix+'_'+nbLine)
		);
		// remove action
		rowElem.find('td[name="'+prefix+'action_'+suffix+'_'+nbLine+'"]').html('');
		// change name
		rowElem.attr('name', prefix+suffix+'_'+nbLine);
		// insert new row after last cloned row
		jQuery('tr[name="'+prefix+suffix+'_'+(nbLine - 1)+'"]:last').after(rowElem);
		// set remain quantity to dispatch
		var qtyInput = jQuery('#'+prefix+'qty_'+suffixWithLine);
		qtyInput.focus();
		qtyInput.val(qtyToDispatch - qtyDispatched);
		// remove duplicated warehouse got from cloned select2 and reload script
		var warehouseElem = jQuery('#'+prefix+'ent_'+suffixWithLine);
		if (warehouseElem.length > 0) {
			warehouseElem.next('.select2').remove();
			warehouseElem.select2();
		}
	};
};
