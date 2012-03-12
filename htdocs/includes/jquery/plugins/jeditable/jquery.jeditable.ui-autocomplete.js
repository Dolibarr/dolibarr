/* Create an inline datepicker which leverages the
   jQuery UI autocomplete 
*/
$.editable.addInputType('autocomplete', {
	element	: $.editable.types.text.element,
	plugin	: function(settings, original) {
		$('input', this).autocomplete(settings.autocomplete);
	}
});