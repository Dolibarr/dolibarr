// Copyright (C) 2011 Regis Houssin  <regis@dolibarr.fr>
//
// Script javascript that contains functions for edit in place
//
// \file       htdocs/core/js/editinplace.js
// \brief      File that include javascript functions for edit in place


$(document).ready(function() {
	$(document).ready(function() {
		var element = $('#element').html();
		var table_element = $('#table_element').html();
		var fk_element = $('#fk_element').html();
		
		$('.edit_area').editable(urlSaveInPlace, {
			type		: 'textarea',
			rows		: 4,
			id			: 'field',
			tooltip		: tooltipInPlace,
			cancel		: cancelInPlace,
			submit		: submitInPlace,
			indicator	: indicatorInPlace,
			loadurl		: urlLoadInPlace,
			loaddata	: {
				type: 'textarea',
				element: element,
				table_element: table_element,
				fk_element: fk_element
			},
			submitdata	: {
				type: 'textarea',
				element: element,
				table_element: table_element,
				fk_element: fk_element
			}
		});
		$('.edit_text').editable(urlSaveInPlace, {
			type		: 'text',
			id			: 'field',
			width		: 300,
			tooltip		: tooltipInPlace,
			cancel		: cancelInPlace,
			submit		: submitInPlace,
			indicator	: indicatorInPlace,
			submitdata	: {
				type: 'text',
				element: element,
				table_element: table_element,
				fk_element: fk_element
			}
		});
		$('.edit_numeric').editable(urlSaveInPlace, {
			type		: 'text',
			id			: 'field',
			width		: 100,
			tooltip		: tooltipInPlace,
			cancel		: cancelInPlace,
			submit		: submitInPlace,
			indicator	: indicatorInPlace,
			loadurl		: urlLoadInPlace,
			loaddata	: {
				type: 'numeric',
				element: element,
				table_element: table_element,
				fk_element: fk_element
			},
			submitdata	: {
				type: 'numeric',
				element: element,
				table_element: table_element,
				fk_element: fk_element
			}
		});
	});
});