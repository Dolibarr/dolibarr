// Copyright (C) 2011 Regis Houssin  <regis@dolibarr.fr>
//
// Script javascript that contains functions for edit in place
//
// \file       htdocs/core/js/editinplace.js
// \brief      File that include javascript functions for edit in place


$(document).ready(function() {
	var element = $('#jeditable_element').html();
	var table_element = $('#jeditable_table_element').html();
	var fk_element = $('#jeditable_fk_element').html();
	
	$('.edit_textarea').editable(urlSaveInPlace, {
		type		: 'textarea',
		rows		: 4,
		id			: 'field',
		tooltip		: tooltipInPlace,
		placeholder	: placeholderInPlace,
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
		},
		callback : function(result, settings) {
			var obj = $.parseJSON(result);
			
			if (obj.error) {
				$(this).html(this.revert);
				$.jnotify(obj.error, "error", true);
			} else {
				$(this).html(obj.value);
			}
		}
	});
	$('.edit_ckeditor').editable(urlSaveInPlace, {
		type		: 'ckeditor',
		id			: 'field',
		onblur		: 'ignore',
		tooltip		: tooltipInPlace,
		placeholder	: placeholderInPlace,
		cancel		: cancelInPlace,
		submit		: submitInPlace,
		indicator	: indicatorInPlace,
		ckeditor	: {
			customConfig: ckeditorConfig,
			toolbar: $('#toolbar').val()
		},
		submitdata	: {
			type: 'ckeditor',
			element: element,
			table_element: table_element,
			fk_element: fk_element
		},
		callback : function(result, settings) {
			var obj = $.parseJSON(result);
			
			if (obj.error) {
				$(this).html(this.revert);
				$.jnotify(obj.error, "error", true);
			} else {
				$(this).html(obj.value);
			}
		}
	});
	$('.edit_text').editable(urlSaveInPlace, {
		type		: 'text',
		id			: 'field',
		width		: 300,
		tooltip		: tooltipInPlace,
		placeholder	: placeholderInPlace,
		cancel		: cancelInPlace,
		submit		: submitInPlace,
		indicator	: indicatorInPlace,
		submitdata	: {
			type: 'text',
			element: element,
			table_element: table_element,
			fk_element: fk_element
		},
		callback : function(result, settings) {
			var obj = $.parseJSON(result);
			
			if (obj.error) {
				$(this).html(this.revert);
				$.jnotify(obj.error, "error", true);
			} else {
				$(this).html(obj.value);
			}
		}
	});
	$('.edit_numeric').editable(urlSaveInPlace, {
		type		: 'text',
		id			: 'field',
		width		: 100,
		tooltip		: tooltipInPlace,
		placeholder	: placeholderInPlace,
		cancel		: cancelInPlace,
		submit		: submitInPlace,
		indicator	: indicatorInPlace,
		submitdata	: {
			type: 'numeric',
			element: element,
			table_element: table_element,
			fk_element: fk_element
		},
		callback : function(result, settings) {
			var obj = $.parseJSON(result);
			
			if (obj.error) {
				$(this).html(this.revert);
				$.jnotify(obj.error, "error", true);
			} else {
				$(this).html(obj.value);
			}
		}
	});
	$('.edit_datepicker').editable(urlSaveInPlace, {
		type		: 'datepicker',
		id			: 'field',
		onblur		: 'ignore',
		tooltip		: tooltipInPlace,
		placeholder	: '&nbsp;',
		cancel		: cancelInPlace,
		submit		: submitInPlace,
		indicator	: indicatorInPlace,
		submitdata	: function(value, settings) {
			return {
				type: 'datepicker',
				element: element,
				table_element: table_element,
				fk_element: fk_element,
				timestamp: $('#timeStamp').val()
			};
		},
		callback : function(result, settings) {
			var obj = $.parseJSON(result);
			
			if (obj.error) {
				$(this).html(this.revert);
				$.jnotify(obj.error, "error", true);
			} else {
				$(this).html(obj.value);
			}
		}
	});
	$('.edit_select').editable(urlSaveInPlace, {
		type		: 'select',
		id			: 'field',
		onblur		: 'ignore',
		cssclass	: 'flat',
		tooltip		: tooltipInPlace,
		placeholder	: placeholderInPlace,
		cancel		: cancelInPlace,
		submit		: submitInPlace,
		indicator	: indicatorInPlace,
		loadurl		: urlLoadInPlace,
		loaddata	: {
			type: 'select',
			method: $('#loadmethod').val(),
			element: element,
			table_element: table_element,
			fk_element: fk_element
		},
		submitdata	: {
			type: 'select',
			method: $('#loadmethod').val(),
			element: element,
			table_element: table_element,
			fk_element: fk_element
		},
		callback : function(result, settings) {
			var obj = $.parseJSON(result);
			
			if (obj.error) {
				$(this).html(this.revert);
				$.jnotify(obj.error, "error", true);
			} else {
				$(this).html(obj.value);
			}
		}
	});
	$('.edit_autocomplete').editable(urlSaveInPlace, {
		type		: 'autocomplete',
		id			: 'field',
		onblur		: 'submit',
		tooltip		: tooltipInPlace,
		indicator	: indicatorInPlace,
		autocomplete : {
			data : ["Aberdeen", "Ada", "Adamsville", "Addyston", "Adelphi", "Adena", "Adrian", "Akron"]
		}
	});
});