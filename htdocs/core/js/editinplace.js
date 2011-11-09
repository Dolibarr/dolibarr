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
	
	$('.editval_textarea').editable(urlSaveInPlace, {
		type		: 'textarea',
		rows		: 4,
		id			: 'field',
		tooltip		: tooltipInPlace,
		placeholder	: '&nbsp;',
		cancel		: cancelInPlace,
		submit		: submitInPlace,
		indicator	: indicatorInPlace,
		loadurl		: urlLoadInPlace,
		loaddata	: function(result, settings) {
			var htmlname = $(this).attr('id').substr(4);
			return getParameters('textarea', htmlname);
		},
		submitdata	: function(result, settings) {
			var htmlname = $(this).attr('id').substr(4);
			return getParameters('textarea', htmlname);
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
	$('.editkey_textarea').hover(
			function () {
				$( '#val_' + $(this).attr('id') ).addClass("editval_hover");
			},
			function () {
				$( '#val_' + $(this).attr('id') ).removeClass("editval_hover");
			}
	);
	$('.editkey_textarea').click(function() {
		$( '#val_' + $(this).attr('id') ).click();
	});

	$('.editval_ckeditor').editable(urlSaveInPlace, {
		type		: 'ckeditor',
		id			: 'field',
		onblur		: 'ignore',
		tooltip		: tooltipInPlace,
		placeholder	: '&nbsp;',
		cancel		: cancelInPlace,
		submit		: submitInPlace,
		indicator	: indicatorInPlace,
		ckeditor	: {
			customConfig: ckeditorConfig,
			toolbar: $('#ckeditor_toolbar').val()
		},
		submitdata	: function(result, settings) {
			var htmlname = $(this).attr('id').substr(4);
			return getParameters('ckeditor', htmlname);
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
	$('.editkey_ckeditor').hover(
			function () {
				$( '#val_' + $(this).attr('id') ).addClass("editval_hover");
			},
			function () {
				$( '#val_' + $(this).attr('id') ).removeClass("editval_hover");
			}
	);
	$('.editkey_ckeditor').click(function() {
		$( '#val_' + $(this).attr('id') ).click();
	});
	
	$('.editval_text').editable(urlSaveInPlace, {
		type		: 'text',
		id			: 'field',
		width		: 300,
		tooltip		: tooltipInPlace,
		placeholder	: placeholderInPlace,
		cancel		: cancelInPlace,
		submit		: submitInPlace,
		indicator	: indicatorInPlace,
		submitdata	: function(result, settings) {
			var htmlname = $(this).attr('id').substr(4);
			return getParameters('text', htmlname);
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
	$('.editkey_text').hover(
			function () {
				$( '#val_' + $(this).attr('id') ).addClass("editval_hover");
			},
			function () {
				$( '#val_' + $(this).attr('id') ).removeClass("editval_hover");
			}
	);
	$('.editkey_text').click(function() {
		$( '#val_' + $(this).attr('id') ).click();
	});
	
	$('.editval_numeric').editable(urlSaveInPlace, {
		type		: 'text',
		id			: 'field',
		width		: 100,
		tooltip		: tooltipInPlace,
		placeholder	: placeholderInPlace,
		cancel		: cancelInPlace,
		submit		: submitInPlace,
		indicator	: indicatorInPlace,
		submitdata	: function(result, settings) {
			var htmlname = $(this).attr('id').substr(4);
			return getParameters('numeric', htmlname);
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
	$('.editkey_numeric').hover(
			function () {
				$( '#val_' + $(this).attr('id') ).addClass("editval_hover");
			},
			function () {
				$( '#val_' + $(this).attr('id') ).removeClass("editval_hover");
			}
	);
	$('.editkey_numeric').click(function() {
		$( '#val_' + $(this).attr('id') ).click();
	});
	
	$('.editval_datepicker').editable(urlSaveInPlace, {
		type		: 'datepicker',
		id			: 'field',
		onblur		: 'ignore',
		tooltip		: tooltipInPlace,
		placeholder	: '&nbsp;',
		cancel		: cancelInPlace,
		submit		: submitInPlace,
		indicator	: indicatorInPlace,
		submitdata	: function(result, settings) {
			var htmlname = $(this).attr('id').substr(4);
			return getParameters('datepicker', htmlname);
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
	$('.editkey_datepicker').hover(
			function () {
				$( '#val_' + $(this).attr('id') ).addClass("editval_hover");
			},
			function () {
				$( '#val_' + $(this).attr('id') ).removeClass("editval_hover");
			}
	);
	$('.editkey_datepicker').click(function() {
		$( '#val_' + $(this).attr('id') ).click();
	});
	
	$('.editval_select').editable(urlSaveInPlace, {
		type		: 'select',
		id			: 'field',
		onblur		: 'ignore',
		cssclass	: 'flat',
		tooltip		: tooltipInPlace,
		placeholder	: '&nbsp;',
		cancel		: cancelInPlace,
		submit		: submitInPlace,
		indicator	: indicatorInPlace,
		loadurl		: urlLoadInPlace,
		loaddata	: function(result, settings) {
			var htmlname = $(this).attr('id').substr(4);
			return getParameters('select', htmlname);
		},
		submitdata	: function(result, settings) {
			var htmlname = $(this).attr('id').substr(4);
			return getParameters('select', htmlname);
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
	$('.editkey_select').hover(
			function () {
				$( '#val_' + $(this).attr('id') ).addClass("editval_hover");
			},
			function () {
				$( '#val_' + $(this).attr('id') ).removeClass("editval_hover");
			}
	);
	$('.editkey_select').click(function() {
		$( '#val_' + $(this).attr('id') ).click();
	});
	
	function getParameters(type, htmlname) {
		var element = $( '#element_' + htmlname ).val();
		var table_element = $( '#table_element_' + htmlname ).val();
		var fk_element = $( '#fk_element_' + htmlname ).val();
		var loadmethod = $( '#loadmethod_' + htmlname ).val();
		var savemethod = $( '#savemethod_' + htmlname ).val();
		var timestamp = $('#timestamp_' + htmlname ).val();
		var ext_element = $( '#ext_element_' + htmlname ).val();
		//var ext_table_element = $( '#ext_table_element_' + htmlname ).val();
		//var ext_fk_element = $( '#ext_fk_element_' + htmlname ).val();
		
		return {
			type: type,
			element: element,
			table_element: table_element,
			fk_element: fk_element,
			loadmethod: loadmethod,
			savemethod: savemethod,
			timestamp: timestamp,
			ext_element: ext_element,
			//ext_table_element: ext_table_element,
			//ext_fk_element: ext_fk_element
		};
	}
	
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
