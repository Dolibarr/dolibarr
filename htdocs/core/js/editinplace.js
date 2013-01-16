// Copyright (C) 2011-2012	Regis Houssin		<regis.houssin@capnetworks.com>
// Copyright (C) 2011		Laurent Destailleur	<eldy@users.sourceforge.net>
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
// \file       htdocs/core/js/editinplace.js
// \brief      File that include javascript functions for edit in place
//

$(document).ready(function() {
	var element = $('#jeditable_element').html();
	var table_element = $('#jeditable_table_element').html();
	var fk_element = $('#jeditable_fk_element').html();
	
	if ($('.editval_textarea').length > 0) {
		$('.editval_textarea').editable(urlSaveInPlace, {
			type		: 'textarea',
			rows		: $('#textarea_' + $('.editval_textarea').attr('id').substr(8) + '_rows').val(),
			cols		: $('#textarea_' + $('.editval_textarea').attr('id').substr(8) + '_cols').val(),
			id			: 'field',
			tooltip		: tooltipInPlace,
			placeholder	: '&nbsp;',
			cancel		: cancelInPlace,
			submit		: submitInPlace,
			indicator	: indicatorInPlace,
			loadurl		: urlLoadInPlace,
			loaddata	: function(result, settings) {
				return getParameters(this, 'textarea');
			},
			submitdata	: function(result, settings) {
				return getParameters(this, 'textarea');
			},
			callback	: function(result, settings) {
				getResult(this, result);
			},
			onreset		: function(result, settings) {
				getDefault(settings);
			}
		});
		$('.editkey_textarea').hover(
				function () {
					$('#viewval_' + $(this).attr('id')).addClass("viewval_hover");
				},
				function () {
					$('#viewval_' + $(this).attr('id')).removeClass("viewval_hover");
				}
		);
		$('.editkey_textarea').click(function() {
			$('#viewval_' + $(this).attr('id')).click();
		});
		$('.viewval_textarea.active').click(function() {
			$('#viewval_' + $(this).attr('id').substr(8)).hide();
			$('#editval_' + $(this).attr('id').substr(8)).show().click();
		});
		$('.editkey_textarea').click(function() {
			$('#viewval_' + $(this).attr('id')).hide();
			$('#editval_' + $(this).attr('id')).show().click();
		});
	}
	
	if (typeof ckeditorConfig != 'undefined') {
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
				toolbar: $('#ckeditor_toolbar').val(),
				filebrowserBrowseUrl : ckeditorFilebrowserBrowseUrl,
				filebrowserImageBrowseUrl : ckeditorFilebrowserImageBrowseUrl,
				filebrowserWindowWidth : '900',
	            filebrowserWindowHeight : '500',
	            filebrowserImageWindowWidth : '900',
	            filebrowserImageWindowHeight : '500'
			},
			submitdata	: function(result, settings) {
				return getParameters(this, 'ckeditor');
			},
			callback	: function(result, settings) {
				getResult(this, result);
			},
			onreset		: function(result, settings) {
				getDefault(settings);
			}
		});
		$('.editkey_ckeditor').hover(
				function () {
					$('#viewval_' + $(this).attr('id')).addClass("viewval_hover");
				},
				function () {
					$('#viewval_' + $(this).attr('id')).removeClass("viewval_hover");
				}
		);
		$('.editkey_ckeditor').click(function() {
			$( '#viewval_' + $(this).attr('id') ).click();
		});
		$('.viewval_ckeditor.active').click(function() {
			$('#viewval_' + $(this).attr('id').substr(8)).hide();
			$('#editval_' + $(this).attr('id').substr(8)).show().click();
		});
		$('.editkey_ckeditor').click(function() {
			$('#viewval_' + $(this).attr('id')).hide();
			$('#editval_' + $(this).attr('id')).show().click();
		});
	}
	
	if ($('.editval_string').length > 0) {
		$('.editval_string').editable(urlSaveInPlace, {
			type		: 'text',
			id			: 'field',
			width		: 300,
			tooltip		: tooltipInPlace,
			placeholder	: placeholderInPlace,
			cancel		: cancelInPlace,
			submit		: submitInPlace,
			indicator	: indicatorInPlace,
			submitdata	: function(result, settings) {
				return getParameters(this, 'string');
			},
			callback	: function(result, settings) {
				getResult(this, result);
			},
			onreset		: function(result, settings) {
				getDefault(settings);
			}
		});
		$('.editkey_string').hover(
				function () {
					$('#viewval_' + $(this).attr('id')).addClass("viewval_hover");
				},
				function () {
					$('#viewval_' + $(this).attr('id')).removeClass("viewval_hover");
				}
		);
		$('.editkey_string').click(function() {
			$( '#viewval_' + $(this).attr('id') ).click();
		});
		$('.viewval_string.active').click(function() {
			$('#viewval_' + $(this).attr('id').substr(8)).hide();
			$('#editval_' + $(this).attr('id').substr(8)).show().click();
		});
		$('.editkey_string').click(function() {
			$('#viewval_' + $(this).attr('id')).hide();
			$('#editval_' + $(this).attr('id')).show().click();
		});
	}
	
	if ($('.editval_numeric').length > 0) {
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
				return getParameters(this, 'numeric');
			},
			callback	: function(result, settings) {
				getResult(this, result);
			},
			onreset		: function(result, settings) {
				getDefault(settings);
			}
		});
		$('.editkey_numeric').hover(
				function () {
					$( '#viewval_' + $(this).attr('id') ).addClass("viewval_hover");
				},
				function () {
					$( '#viewval_' + $(this).attr('id') ).removeClass("viewval_hover");
				}
		);
		$('.editkey_numeric').click(function() {
			$( '#viewval_' + $(this).attr('id') ).click();
		});
		$('.viewval_numeric.active').click(function() {
			$('#viewval_' + $(this).attr('id').substr(8)).hide();
			$('#editval_' + $(this).attr('id').substr(8)).show().click();
		});
		$('.editkey_numeric').click(function() {
			$('#viewval_' + $(this).attr('id')).hide();
			$('#editval_' + $(this).attr('id')).show().click();
		});
	}
	
	if ($('.editval_datepicker').length > 0) {
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
				return getParameters(this, 'datepicker');
			},
			callback	: function(result, settings) {
				getResult(this, result);
			},
			onreset		: function(result, settings) {
				getDefault(settings);
			}
		});
		$('.editkey_datepicker').hover(
				function () {
					$('#viewval_' + $(this).attr('id')).addClass("viewval_hover");
				},
				function () {
					$('#viewval_' + $(this).attr('id')).removeClass("viewval_hover");
				}
		);
		$('.viewval_datepicker.active').click(function() {
			$('#viewval_' + $(this).attr('id').substr(8)).hide();
			$('#editval_' + $(this).attr('id').substr(8)).show().click();
		});
		$('.editkey_datepicker').click(function() {
			$('#viewval_' + $(this).attr('id')).hide();
			$('#editval_' + $(this).attr('id')).show().click();
		});
	}
	
	if ($('.editval_select').length > 0) {
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
				return getParameters(this, 'select');
			},
			submitdata	: function(result, settings) {
				return getParameters(this, 'select');
			},
			callback	: function(result, settings) {
				getResult(this, result);
			},
			onreset		: function(result, settings) {
				getDefault(settings);
			}
		});
		$('.editkey_select').hover(
				function () {
					$('#viewval_' + $(this).attr('id')).addClass("viewval_hover");
				},
				function () {
					$('#viewval_' + $(this).attr('id')).removeClass("viewval_hover");
				}
		);
		$('.viewval_select.active').click(function() {
			$('#viewval_' + $(this).attr('id').substr(8)).hide();
			$('#editval_' + $(this).attr('id').substr(8)).show().click();
		});
		$('.editkey_select').click(function() {
			$('#viewval_' + $(this).attr('id')).hide();
			$('#editval_' + $(this).attr('id')).show().click();
		});
	}
	
	// for test only (not stable)
	if ($('.editval_autocomplete').length > 0) {
		$('.editval_autocomplete').editable(urlSaveInPlace, {
			type		: 'autocomplete',
			id			: 'field',
			width		: 300,
			onblur		: 'ignore',
			tooltip		: tooltipInPlace,
			placeholder	: '&nbsp;',
			cancel		: cancelInPlace,
			submit		: submitInPlace,
			indicator	: indicatorInPlace,
			autocomplete : {
				source : urlLoadInPlace,
				data : function(result, settings) {
					return getParameters(this, 'select');
				}
			},
			submitdata	: function(result, settings) {
				return getParameters(this, 'select');
			},
			callback	: function(result, settings) {
				getResult(this, result);
			},
			onreset		: function(result, settings) {
				getDefault(settings);
			}
		});
		$('.editkey_autocomplete').hover(
				function () {
					$('#viewval_' + $(this).attr('id')).addClass("viewval_hover");
				},
				function () {
					$('#viewval_' + $(this).attr('id')).removeClass("viewval_hover");
				}
		);
		$('.viewval_autocomplete.active').click(function() {
			$('#viewval_' + $(this).attr('id').substr(8)).hide();
			$('#editval_' + $(this).attr('id').substr(8)).show().click();
		});
		$('.editkey_autocomplete').click(function() {
			$('#viewval_' + $(this).attr('id')).hide();
			$('#editval_' + $(this).attr('id')).show().click();
		});
	}
	
	function getParameters(obj, type) {
		var htmlname		= $(obj).attr('id').substr(8);
		var element			= $('#element_' + htmlname).val();
		var table_element	= $('#table_element_' + htmlname).val();
		var fk_element		= $('#fk_element_' + htmlname).val();
		var loadmethod		= $('#loadmethod_' + htmlname).val();
		var savemethod		= $('#savemethod_' + htmlname).val();
		var ext_element		= $('#ext_element_' + htmlname).val();
		var timestamp		= $('#timestamp').val();
		
		return {
			type: type,
			element: element,
			table_element: table_element,
			fk_element: fk_element,
			loadmethod: loadmethod,
			savemethod: savemethod,
			timestamp: timestamp,
			ext_element: ext_element
		};
	}
	
	function getResult(obj, result) {
		var res = $.parseJSON(result);
		if (res.error) {
			$(obj).html(obj.revert);
			$.jnotify(res.error, "error", true);
		} else {
			var htmlname = $(obj).attr('id').substr(8);
			var success = $( '#success_' + htmlname ).val();
			if (success != undefined) {
				$.jnotify(success, "ok");
			}
			$(obj).html(res.value);
			$(obj).hide();
			$('#viewval_' + htmlname).html(res.view);
			$('#viewval_' + htmlname).show();
		}
	}
	
	function getDefault(settings) {
		var htmlname = $(settings).attr('id').substr(8);
		$('#editval_' + htmlname).hide();
		$('#viewval_' + htmlname).show();
	}
});
