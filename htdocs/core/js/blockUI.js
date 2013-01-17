// Copyright (C) 2012	Regis Houssin	<regis.houssin@capnetworks.com>
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

//
// \file       htdocs/core/js/blockUI.js
// \brief      File that include javascript functions for blockUI default options
//

// Examples
$(document).ready(function() {
	// override these in your code to change the default behavior and style
	$.blockUI.events = {

			// styles applied when using $.growlUI
			dolEventValidCSS: {
				width:  	'350px',
				top:		'10px',
				left:   	'',
				right:  	'10px',
				border: 	'none',
				padding:	'5px',
				opacity:	0.8,
				cursor: 	'default',
				color:		'#fff',
				backgroundColor: '#e3f0db',
				'-webkit-border-radius': '10px',
				'-moz-border-radius':	 '10px',
				'border-radius': 		 '10px'
			},
			
			// styles applied when using $.growlUI
			dolEventErrorCSS: {
				width:  	'350px',
				top:		'10px',
				left:   	'',
				right:  	'10px',
				border: 	'none',
				padding:	'5px',
				opacity:	0.8,
				cursor: 	'default',
				color:		'#a72947',
				backgroundColor: '#d79eac',
				'-webkit-border-radius': '10px',
				'-moz-border-radius':	 '10px',
				'border-radius': 		 '10px'
			}

	};
	
	$.dolEventValid = function(title, message, timeout, onClose) {
		var $m = $('<div class="dolEventValid"></div>');
		if (title) $m.append('<h1>'+title+'</h1>');
		if (message) $m.append('<h2>'+message+'</h2>');
		if (timeout == undefined) timeout = 3000;
		$.blockUI({
			message: $m, fadeIn: 200, fadeOut: 700, centerY: false,
			timeout: timeout, showOverlay: false,
			onUnblock: onClose,
			css: $.blockUI.events.dolEventValidCSS
		});
	};
	
	$.dolEventError = function(title, message, timeout, onClose) {
		var $m = $('<div class="dolEventError"></div>');
		if (title) $m.append('<h1>'+title+'</h1>');
		if (message) $m.append('<h2>'+message+'</h2>');
		if (timeout == undefined) timeout = 0;
		$.blockUI({
			message: $m, fadeIn: 200, centerY: false,
			timeout: timeout, showOverlay: false,
			onUnblock: onClose,
			css: $.blockUI.events.dolEventErrorCSS
		});
		$('.dolEventError').click($.unblockUI);
	};
	
	$.pleaseBePatient = function(message) {
		$.blockUI({
			message: message,
			css: {
				border: 'none',
				padding: '15px',
				background: '#000 url(' +  indicatorBlockUI + ') no-repeat 10px center',
				'-webkit-border-radius': '10px',
				'-moz-border-radius': '10px',
				'border-radius': '10px',
				opacity: .5,
				color: '#fff'
			}
		});
	}
});