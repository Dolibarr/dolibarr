// Copyright (C) 2011 Regis Houssin  <regis@dolibarr.fr>
//
// Script javascript that contains functions for datepicker default options
//
// \file       htdocs/core/js/jnotify.js
// \brief      File that include javascript functions for datepicker default options


$(document).ready(function() {
	$.datepicker.setDefaults({
		altField: '#timeStamp',
		altFormat: '@'			// Gives a timestamp dateformat
	});
});