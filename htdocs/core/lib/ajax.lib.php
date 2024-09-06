<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2015 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *  \file		htdocs/core/lib/ajax.lib.php
 *  \brief		Page called to enhance interface with Javascript and Ajax features.
 */


/**
 * Generic function that return javascript to add to transform a common input text or select field into an autocomplete field by calling an Ajax page (ex: /societe/ajax/ajaxcompanies.php).
 * The HTML field must be an input text with id=search_$htmlname.
 * This use the jQuery "autocomplete" function. If we want to use the select2, we must instead use input select into functions that call this method.
 *
 * @param string	$selected 			Preselected value
 * @param string	$htmlname 			HTML name of input field
 * @param string	$url 				Ajax Url to call for request: /path/page.php. Must return a json array ('key'=>id, 'value'=>String shown into input field once selected, 'label'=>String shown into combo list)
 * @param string	$urloption			More parameters on URL request
 * @param int		$minLength			Minimum number of chars to trigger that Ajax search
 * @param int		$autoselect			Automatic selection if just one value (trigger("change") on field is done if search return only 1 result)
 * @param array<string,string|string[]>	$ajaxoptions	Multiple options array
 *                                                      - Ex: array('update'=>array('field1','field2'...)) will reset field1 and field2 once select done
 *                                                      - Ex: array('disabled'=> )
 *                                                      - Ex: array('show'=> )
 *                                                      - Ex: array('update_textarea'=> )
 *                                                      - Ex: array('option_disabled'=> id to disable and warning to show if we select a disabled value (this is possible when using autocomplete ajax)
 * @param string	$moreparams			More params provided to ajax call
 * @return string   					Script
 */
function ajax_autocompleter($selected, $htmlname, $url, $urloption = '', $minLength = 2, $autoselect = 0, $ajaxoptions = array(), $moreparams = '')
{
	if (empty($minLength)) {
		$minLength = 1;
	}

	$dataforrenderITem = 'ui-autocomplete';
	$dataforitem = 'ui-autocomplete-item';
	// Allow two constant to use other values for backward compatibility
	if (defined('JS_QUERY_AUTOCOMPLETE_RENDERITEM')) {
		$dataforrenderITem = constant('JS_QUERY_AUTOCOMPLETE_RENDERITEM');
	}
	if (defined('JS_QUERY_AUTOCOMPLETE_ITEM')) {
		$dataforitem = constant('JS_QUERY_AUTOCOMPLETE_ITEM');
	}

	$htmlnamejquery = str_replace('.', '\\\\.', $htmlname);

	// Input search_htmlname is original field
	// Input htmlname is a second input field used when using ajax autocomplete.
	$script = '<input type="hidden" name="'.$htmlname.'" id="'.$htmlname.'" value="'.$selected.'" '.($moreparams ? $moreparams : '').' />';

	$script .= '<!-- Javascript code for autocomplete of field '.$htmlname.' -->'."\n";
	$script .= '<script>'."\n";
	$script .= '$(document).ready(function() {
					var autoselect = '.((int) $autoselect).';
					var options = '.json_encode($ajaxoptions).'; /* Option of actions to do after keyup, or after select */

					/* Remove selected id as soon as we type or delete a char (it means old selection is wrong). Use keyup/down instead of change to avoid losing the product id. This is needed only for select of predefined product */
					$("input#search_'.$htmlnamejquery.'").keydown(function(e) {
						if (e.keyCode != 9)		/* If not "Tab" key */
						{
							if (e.keyCode == 13) { return false; } /* disable "ENTER" key useful for barcode readers */
							console.log("Clear id previously selected for field '.$htmlname.'");
							$("#'.$htmlnamejquery.'").val("");
						}
					});

					// Check options for secondary actions when keyup
					$("input#search_'.$htmlnamejquery.'").keyup(function() {
						    if ($(this).val().length == 0)
						    {
	                            $("#search_'.$htmlnamejquery.'").val("");
	                            $("#'.$htmlnamejquery.'").val("").trigger("change");
	                            if (options.option_disabled) {
	    							$("#" + options.option_disabled).removeAttr("disabled");
	    						}
	    						if (options.disabled) {
	    							$.each(options.disabled, function(key, value) {
	    								$("#" + value).removeAttr("disabled");
									});
	    						}
	    						if (options.update) {
	    							$.each(options.update, function(key, value) {
	    								$("#" + key).val("").trigger("change");
									});
								}
								if (options.show) {
	    							$.each(options.show, function(key, value) {
	    								$("#" + value).hide().trigger("hide");
									});
								}
								if (options.update_textarea) {
	    							$.each(options.update_textarea, function(key, value) {
	    								if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined" && CKEDITOR.instances[key] != "undefined") {
	    									CKEDITOR.instances[key].setData("");
	    								} else {
	    									$("#" + key).html("");
										}
	    							});
	    						}
						    }
                    });

					// Activate the autocomplete to execute the GET
    				$("input#search_'.$htmlnamejquery.'").autocomplete({
    					source: function( request, response ) {
    						$.get("'.$url.($urloption ? '?'.$urloption : '').'", { "'.str_replace('.', '_', $htmlname).'": request.term }, function(data){
								if (data != null)
								{
									response($.map( data, function(item) {
										if (autoselect == 1 && data.length == 1) {
											$("#search_'.$htmlnamejquery.'").val(item.value);
											$("#'.$htmlnamejquery.'").val(item.key).trigger("change");
										}
										var label = "";
										if (item.label != null) {
											label = item.label.toString();
										}
										var update = {};
										if (options.update) {
											$.each(options.update, function(key, value) {
												update[key] = item[value];
											});
										}
										var textarea = {};
										if (options.update_textarea) {
											$.each(options.update_textarea, function(key, value) {
												textarea[key] = item[value];
											});
										}

										console.log("Return value from GET to the rest of code");
										return { label: label,
												 value: item.value,
												 id: item.key,
												 disabled: item.disabled,
												 update: update,
												 textarea: textarea,
												 pbq: item.pbq,
												 type: item.type,
												 qty: item.qty,
												 discount: item.discount,
												 pricebasetype: item.pricebasetype,
												 price_ht: item.price_ht,
												 price_ttc: item.price_ttc,
												 price_unit_ht: item.price_unit_ht,
												 price_unit_ht_locale: item.price_unit_ht_locale,
												 multicurrency_code: item.multicurrency_code,
												 multicurrency_unitprice: item.multicurrency_unitprice,
												 description : item.description,
												 ref_customer: item.ref_customer,
												 tva_tx: item.tva_tx,
												 default_vat_code: item.default_vat_code,
												 supplier_ref: item.supplier_ref
										}
									}));
								} else {
									console.error("Error: Ajax url '.$url.($urloption ? '?'.$urloption : '').' has returned an empty page. Should be an empty json array.");
								}
							}, "json");
						},
						dataType: "json",
    					minLength: '.((int) $minLength).',
    					select: function( event, ui ) {		// Function ran once a new value has been selected into the javascript combo
    						console.log("We will trigger change on input '.$htmlname.' because of the select definition of autocomplete code for input#search_'.$htmlname.'");
    					    console.log("Selected id = "+ui.item.id+" - If this value is null, it means you select a record with key that is null so selection is not effective");

							console.log("Before, we propagate some properties, retrieved by the ajax of the get, into the data-xxx properties of the component #'.$htmlnamejquery.'");
							//console.log(ui.item);

							// For supplier price and customer when price by quantity is off
							$("#'.$htmlnamejquery.'").attr("data-up", ui.item.price_ht);
							$("#'.$htmlnamejquery.'").attr("data-up-locale", ui.item.price_unit_ht_locale);
							$("#'.$htmlnamejquery.'").attr("data-base", ui.item.pricebasetype);
							$("#'.$htmlnamejquery.'").attr("data-qty", ui.item.qty);
							$("#'.$htmlnamejquery.'").attr("data-discount", ui.item.discount);
							$("#'.$htmlnamejquery.'").attr("data-description", ui.item.description);
							$("#'.$htmlnamejquery.'").attr("data-ref-customer", ui.item.ref_customer);
							$("#'.$htmlnamejquery.'").attr("data-tvatx", ui.item.tva_tx);
							$("#'.$htmlnamejquery.'").attr("data-default-vat-code", ui.item.default_vat_code);
							$("#'.$htmlnamejquery.'").attr("data-supplier-ref", ui.item.supplier_ref);	// supplier_ref of price

							// For multi-currency values
							$("#'.$htmlnamejquery.'").attr("data-multicurrency-code", ui.item.multicurrency_code);
							$("#'.$htmlnamejquery.'").attr("data-multicurrency-unitprice", ui.item.multicurrency_unitprice);
		';
	if (getDolGlobalString('PRODUIT_CUSTOMER_PRICES_BY_QTY') || getDolGlobalString('PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES')) {
		$script .= '
							// For customer price when PRODUIT_CUSTOMER_PRICES_BY_QTY is on
							console.log("PRODUIT_CUSTOMER_PRICES_BY_QTY is on, so we propagate also prices by quantity into data-pbqxxx properties");
							$("#'.$htmlnamejquery.'").attr("data-pbq", ui.item.pbq);
							$("#'.$htmlnamejquery.'").attr("data-pbqup", ui.item.price_ht);
							$("#'.$htmlnamejquery.'").attr("data-pbqbase", ui.item.pricebasetype);
							$("#'.$htmlnamejquery.'").attr("data-pbqqty", ui.item.qty);
							$("#'.$htmlnamejquery.'").attr("data-pbqpercent", ui.item.discount);
		';
	}
	$script .= '
							// A new value has been selected, we trigger the handlers on #htmlnamejquery
							console.log("Now, we trigger changes on #'.$htmlnamejquery.'");
							$("#'.$htmlnamejquery.'").val(ui.item.id).trigger("change");	// Select new value

							// Complementary actions

    						// Disable an element
    						if (options.option_disabled) {
    							console.log("Make action option_disabled on #"+options.option_disabled+" with disabled="+ui.item.disabled)
    							if (ui.item.disabled) {
									$("#" + options.option_disabled).prop("disabled", true);
    								if (options.error) {
    									$.jnotify(options.error, "error", true);		// Output with jnotify the error message
    								}
    								if (options.warning) {
    									$.jnotify(options.warning, "warning", false);		// Output with jnotify the warning message
    								}
								} else {
    								$("#" + options.option_disabled).removeAttr("disabled");
    							}
    						}

    						if (options.disabled) {
    							console.log("Make action \'disabled\' on each "+options.option_disabled)
    							$.each(options.disabled, function(key, value) {
									$("#" + value).prop("disabled", true);
    							});
    						}
    						if (options.show) {
    							console.log("Make action \'show\' on each "+options.show)
    							$.each(options.show, function(key, value) {
    								$("#" + value).show().trigger("show");
    							});
    						}

    						// Update an input
    						if (ui.item.update) {
    							console.log("Make action \'update\' on each ui.item.update (if there is)")
    							// loop on each "update" fields
    							$.each(ui.item.update, function(key, value) {
									console.log("Set value "+value+" into #"+key);
    								$("#" + key).val(value).trigger("change");
    							});
    						}
    						if (ui.item.textarea) {
    							console.log("Make action \'textarea\' on each ui.item.textarea (if there is)")
    							$.each(ui.item.textarea, function(key, value) {
    								if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined" && CKEDITOR.instances[key] != "undefined") {
    									CKEDITOR.instances[key].setData(value);
    									CKEDITOR.instances[key].focus();
    								} else {
    									$("#" + key).html(value);
    									$("#" + key).focus();
									}
    							});
    						}
    						console.log("ajax_autocompleter new value selected, we trigger change also on original component so on field #search_'.$htmlname.'");

    						$("#search_'.$htmlnamejquery.'").trigger("change");	// We have changed value of the combo select, we must be sure to trigger all js hook binded on this event. This is required to trigger other javascript change method binded on original field by other code.
    					}
    					,delay: 500
					}).data("'.$dataforrenderITem.'")._renderItem = function( ul, item ) {
						return $("<li>")
						.data( "'.$dataforitem.'", item ) // jQuery UI > 1.10.0
						.append( \'<a><span class="tag">\' + item.label + "</span></a>" )
						.appendTo(ul);
					};

  				});';
	$script .= '</script>';

	return $script;
}

/**
 *	Generic function that return javascript to add to a page to transform a common input text field into an autocomplete field by calling an Ajax page (ex: core/ajax/ziptown.php).
 *  The Ajax page can also returns several values (json format) to fill several input fields.
 *  The HTML field must be an input text with id=$htmlname.
 *  This use the jQuery "autocomplete" function.
 *
 *	@param	string	$htmlname           HTML name of input field
 *	@param	string[]	$fields				Array of key of fields to autocomplete
 *	@param	string	$url                URL for ajax request : /chemin/fichier.php
 *	@param	string	$option				More parameters on URL request
 *	@param	int		$minLength			Minimum number of chars to trigger that Ajax search
 *	@param	int		$autoselect			Automatic selection if just one value
 *	@return string              		Script
 */
function ajax_multiautocompleter($htmlname, $fields, $url, $option = '', $minLength = 2, $autoselect = 0)
{
	$script = '<!-- Autocomplete -->'."\n";
	$script .= '<script>';
	$script .= 'jQuery(document).ready(function() {
					var fields = '.json_encode($fields).';
					var nboffields = fields.length;
					var autoselect = '.$autoselect.';
					//alert(fields + " " + nboffields);

					// Activate the autocomplete to execute the GET
					jQuery("input#'.$htmlname.'").autocomplete({
						dataType: "json",
						minLength: '.$minLength.',
						source: function( request, response ) {
							jQuery.getJSON( "'.$url.($option ? '?'.$option : '').'", { '.$htmlname.': request.term }, function(data){
								response( jQuery.map( data, function( item ) {
									if (autoselect == 1 && data.length == 1) {
										jQuery("#'.$htmlname.'").val(item.value);
										// TODO move this to specific request
										if (item.states) {
											jQuery("#state_id").html(item.states);
										}
										for (i=0;i<nboffields;i++) {
											if (item[fields[i]]) {   // If defined
												//alert(item[fields[i]]);
												jQuery("#" + fields[i]).val(item[fields[i]]);
											}
										}
									}
									return item
								}));
							});
						},
						select: function( event, ui ) {
							needtotrigger = "";
							for (i=0;i<nboffields;i++) {
								//alert(fields[i] + " = " + ui.item[fields[i]]);
								if (fields[i]=="selectcountry_id")
								{
									if (ui.item[fields[i]] > 0)	 // Do not erase country if unknown
									{
										oldvalue=jQuery("#" + fields[i]).val();
										newvalue=ui.item[fields[i]];
										//alert(oldvalue+" "+newvalue);
										jQuery("#" + fields[i]).val(ui.item[fields[i]]);
										if (oldvalue != newvalue)	// To force select2 to refresh visible content
										{
											needtotrigger="#" + fields[i];
										}

										// If we set new country and new state, we need to set a new list of state to allow change
										if (ui.item.states && ui.item["state_id"] != jQuery("#state_id").value) {
											jQuery("#state_id").html(ui.item.states);
										}
									}
								}
								else if (fields[i]=="state_id" || fields[i]=="state_id")
								{
									if (ui.item[fields[i]] > 0)	 // Do not erase state if unknown
									{
										oldvalue=jQuery("#" + fields[i]).val();
										newvalue=ui.item[fields[i]];
										//alert(oldvalue+" "+newvalue);
										jQuery("#" + fields[i]).val(ui.item[fields[i]]);	// This may fails if not correct country
										if (oldvalue != newvalue)	// To force select2 to refresh visible content
										{
											needtotrigger="#" + fields[i];
										}
									}
								}
								else if (ui.item[fields[i]]) {   // If defined
									oldvalue=jQuery("#" + fields[i]).val();
									newvalue=ui.item[fields[i]];
									//alert(oldvalue+" "+newvalue);
									jQuery("#" + fields[i]).val(ui.item[fields[i]]);
									if (oldvalue != newvalue)	// To force select2 to refresh visible content
									{
										needtotrigger="#" + fields[i];
									}
								}

								if (needtotrigger != "")	// To force select2 to refresh visible content
								{
									// We introduce a delay so hand is back to js and all other js change can be done before the trigger that may execute a submit is done
									// This is required for example when changing zip with autocomplete that change the country
									jQuery(needtotrigger).delay(500).queue(function() {
										jQuery(this).trigger("change");
									});
								}
							}
						}
					});
  				});';
	$script .= '</script>';

	return $script;
}

/**
 *	Show an ajax dialog
 *
 *	@param	string	$title		Title of dialog box
 *	@param	string	$message	Message of dialog box
 *	@param	int		$w			Width of dialog box
 *	@param	int		$h			height of dialog box
 *	@return	string
 */
function ajax_dialog($title, $message, $w = 350, $h = 150)
{
	$newtitle = dol_textishtml($title) ? dol_string_nohtmltag($title, 1) : $title;
	$msg = '<div id="dialog-info" title="'.dol_escape_htmltag($newtitle).'">';
	$msg .= $message;
	$msg .= '</div>'."\n";
	$msg .= '<script>
    jQuery(function() {
        jQuery("#dialog-info").dialog({
	        resizable: false,
	        height:'.$h.',
	        width:'.$w.',
	        modal: true,
	        buttons: {
	        	Ok: function() {
					jQuery(this).dialog(\'close\');
				}
	        }
	    });
	});
	</script>';

	$msg .= "\n";

	return $msg;
}


/**
 * Convert a html select field into an ajax combobox.
 * Use ajax_combobox() only for small combo list! If not, use instead ajax_autocompleter().
 * TODO: It is used when COMPANY_USE_SEARCH_TO_SELECT and CONTACT_USE_SEARCH_TO_SELECT are set by html.formcompany.class.php. Should use ajax_autocompleter instead like done by html.form.class.php for select_produits.
 *
 * @param	string	$htmlname					Name of html select field ('myid' or '.myclass')
 * @param	array	$events						More events option. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
 * @param  	int		$minLengthToAutocomplete	Minimum length of input string to start autocomplete
 * @param	int		$forcefocus					Force focus on field
 * @param	string	$widthTypeOfAutocomplete	'resolve' or 'off'
 * @param	string	$idforemptyvalue			'-1'
 * @param	string	$morecss					More css
 * @return	string								Return html string to convert a select field into a combo, or '' if feature has been disabled for some reason.
 * @see selectArrayAjax() of html.form.class
 */
function ajax_combobox($htmlname, $events = array(), $minLengthToAutocomplete = 0, $forcefocus = 0, $widthTypeOfAutocomplete = 'resolve', $idforemptyvalue = '-1', $morecss = '')
{
	global $conf;

	// select2 can be disabled for smartphones
	if (!empty($conf->browser->layout) && $conf->browser->layout == 'phone' && getDolGlobalString('MAIN_DISALLOW_SELECT2_WITH_SMARTPHONE')) {
		return '';
	}

	if (getDolGlobalString('MAIN_DISABLE_AJAX_COMBOX')) {
		return '';
	}
	if (empty($conf->use_javascript_ajax)) {
		return '';
	}
	if (!getDolGlobalString('MAIN_USE_JQUERY_MULTISELECT') && !defined('REQUIRE_JQUERY_MULTISELECT')) {
		return '';
	}
	if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
		return '';
	}

	if (empty($minLengthToAutocomplete)) {
		$minLengthToAutocomplete = 0;
	}

	$moreselect2theme = ($morecss ? dol_escape_js(' '.$morecss) : '');
	$moreselect2theme = preg_replace('/widthcentpercentminus[^\s]*/', '', $moreselect2theme);

	$tmpplugin = 'select2';
	$msg = "\n".'<!-- JS CODE TO ENABLE '.$tmpplugin.' for id = '.$htmlname.' -->
		<script>
			$(document).ready(function () {
				$(\''.(preg_match('/^\./', $htmlname) ? $htmlname : '#'.$htmlname).'\').'.$tmpplugin.'({
					dir: \'ltr\',';
	if (preg_match('/onrightofpage/', $morecss)) {	// when $morecss contains 'onrightofpage', the select2 component must also be inside a parent with class="parentonrightofpage"
		$msg .= ' dropdownAutoWidth: true, dropdownParent: $(\'#'.$htmlname.'\').parent(), '."\n";
	}
	$msg .= '		width: \''.dol_escape_js($widthTypeOfAutocomplete).'\',		/* off or resolve */
					minimumInputLength: '.((int) $minLengthToAutocomplete).',
					language: select2arrayoflanguage,
					matcher: function (params, data) {
						if ($.trim(params.term) === "") {
							return data;
						}
						keywords = (params.term).split(" ");
						for (var i = 0; i < keywords.length; i++) {
							if (((data.text).toUpperCase()).indexOf((keywords[i]).toUpperCase()) == -1) {
								return null;
							}
						}
						return data;
					},
					theme: \'default'.$moreselect2theme.'\',		/* to add css on generated html components */
					containerCssClass: \':all:\',					/* Line to add class of origin SELECT propagated to the new <span class="select2-selection...> tag */
					selectionCssClass: \':all:\',					/* Line to add class of origin SELECT propagated to the new <span class="select2-selection...> tag */
					dropdownCssClass: \'ui-dialog\',
					templateResult: function (data, container) {	/* Format visible output into combo list */
	 					/* Code to add class of origin OPTION propagated to the new select2 <li> tag */
						if (data.element) { $(container).addClass($(data.element).attr("class")); }
						//console.log("data html is "+$(data.element).attr("data-html"));
						if (data.id == '.((int) $idforemptyvalue).' && $(data.element).attr("data-html") == undefined) {
							return \'&nbsp;\';
						}
						if ($(data.element).attr("data-html") != undefined) {
							/* If property html set, we decode html entities and use this. */
							/* Note that HTML content must have been sanitized from js with dol_escape_htmltag(xxx, 0, 0, \'\', 0, 1) when building the select option. */
							if (typeof htmlEntityDecodeJs === "function") {
								return htmlEntityDecodeJs($(data.element).attr("data-html"));
							}
						}
						return data.text;
					},
					templateSelection: function (selection) {		/* Format visible output of selected value */
						if (selection.id == '.((int) $idforemptyvalue).') return \'<span class="placeholder">\'+selection.text+\'</span>\';
						return selection.text;
					},
					escapeMarkup: function(markup) {
						return markup;
					}
				})';
	if ($forcefocus) {
		$msg .= '.select2(\'focus\')';
	}
	$msg .= ';'."\n";

	$msg .= '});'."\n";
	$msg .= "</script>\n";

	$msg .= ajax_event($htmlname, $events);

	return $msg;
}


/**
 * Add event management script.
 *
 * @param	string	$htmlname					Name of html select field ('myid' or '.myclass')
 * @param	array	$events						Add some Ajax events option on change of $htmlname component to call ajax to autofill a HTML element (select#htmlname and #inputautocompletehtmlname)
 * 												Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
 * @return	string								Return JS string to manage event
 */
function ajax_event($htmlname, $events)
{
	$out = '';

	if (is_array($events) && count($events)) {   // If an array of js events to do were provided.
		$out = '<!-- JS code to manage event for id = ' . $htmlname . ' -->
	<script>
		$(document).ready(function () {
			jQuery("#'.$htmlname.'").change(function () {
				var obj = '.json_encode($events) . ';
		   		$.each(obj, function(key,values) {
	    			if (values.method.length) {
	    				runJsCodeForEvent'.$htmlname.'(values);
	    			}
				});
			});
			function runJsCodeForEvent'.$htmlname.'(obj) {
				var id = $("#'.$htmlname.'").val();
				var method = obj.method;
				var url = obj.url;
				var htmlname = obj.htmlname;
				var showempty = obj.showempty;
			    console.log("Run runJsCodeForEvent-'.$htmlname.' from ajax_combobox id="+id+" method="+method+" showempty="+showempty+" url="+url+" htmlname="+htmlname);
				$.getJSON(url,
						{
							action: method,
							id: id,
							htmlname: htmlname,
							showempty: showempty
						},
						function(response) {
							$.each(obj.params, function(key,action) {
								if (key.length) {
									var num = response.num;
									if (num > 0) {
										$("#" + key).removeAttr(action);
									} else {
										$("#" + key).attr(action, action);
									}
								}
							});

							console.log("Replace HTML content of select#"+htmlname);
							$("select#" + htmlname).html(response.value);
							if (response.num) {
								var selecthtml_str = response.value;	/* response.value is the HTML string with list of options */
								var selecthtml_dom=$.parseHTML(selecthtml_str);
								if (typeof(selecthtml_dom[0][0]) !== \'undefined\') {
									$("#inputautocomplete"+htmlname).val(selecthtml_dom[0][0].innerHTML);
								}
							} else {
								$("#inputautocomplete"+htmlname).val("");
							}
							$("select#" + htmlname).change();	/* Trigger event change */
						}
				);
			}
		});
	</script>';
	}

	return $out;
}


/**
 * 	On/off button for constant
 *
 * 	@param  string      $code                   Name of constant
 * 	@param  array       $input                  Array of complementary actions to do if success ("disabled"|"enabled'|'set'|'del') => CSS element to switch, 'alert' => message to show, ... Example: array('disabled'=>array(0=>'cssid'))
 * 	@param  int|null    $entity                 Entity. Current entity is used if null.
 *  @param  int         $revertonoff            1=Revert on/off
 *  @param  int	        $strict                 0=Default, 1=Only the complementary actions "disabled and "enabled" (found into $input) are processed. Use only "disabled" with delConstant and "enabled" with setConstant.
 *  @param  int         $forcereload            Force to reload page if we click/change value (this is supported only when there is no 'alert' option in input)
 *  @param  int         $marginleftonlyshort    1 = Add a short left margin on picto, 2 = Add a larger left margin on picto, 0 = No left margin.
 *  @param  int	        $forcenoajax            1 = Force to use a ahref link instead of ajax code.
 *  @param  int         $setzeroinsteadofdel    1 = Set constant to '0' instead of deleting it when $input is empty.
 *  @param  string      $suffix                 Suffix to use on the name of the switch picto when option is on. Example: '', '_red'
 *  @param  string      $mode                   Add parameter &mode= to the href link (Used for href link)
 *  @param  string      $morecss                More CSS
 * 	@return string
 *  @see ajax_object_onoff() to update the status of an object
 */
function ajax_constantonoff($code, $input = array(), $entity = null, $revertonoff = 0, $strict = 0, $forcereload = 0, $marginleftonlyshort = 2, $forcenoajax = 0, $setzeroinsteadofdel = 0, $suffix = '', $mode = '', $morecss = 'inline-block')
{
	global $conf, $langs, $user;

	$entity = ((isset($entity) && is_numeric($entity) && $entity >= 0) ? $entity : $conf->entity);
	if (!isset($input)) {
		$input = array();
	}

	if (empty($conf->use_javascript_ajax) || $forcenoajax) {
		if (empty($conf->global->$code)) {
			$out = '<a '.($morecss ? 'class="'.$morecss.'" ' : '').'href="'.$_SERVER['PHP_SELF'].'?action=set_'.$code.'&token='.newToken().'&entity='.$entity.($mode ? '&mode='.$mode : '').($forcereload ? '&dol_resetcache=1' : '').'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
		} else {
			$out = '<a '.($morecss ? 'class="'.$morecss.'" ' : '').' href="'.$_SERVER['PHP_SELF'].'?action=del_'.$code.'&token='.newToken().'&entity='.$entity.($mode ? '&mode='.$mode : '').($forcereload ? '&dol_resetcache=1' : '').'">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
		}
	} else {
		$out = "\n<!-- Ajax code to switch constant ".$code." -->".'
		<script>
			$(document).ready(function() {
				var input = '.json_encode($input).';
				var url = \''.DOL_URL_ROOT.'/core/ajax/constantonoff.php\';
				var code = \''.dol_escape_js($code).'\';
				var entity = \''.dol_escape_js($entity).'\';
				var strict = \''.dol_escape_js((string) $strict).'\';
				var userid = \''.dol_escape_js((string) $user->id).'\';
				var yesButton = \''.dol_escape_js($langs->transnoentities("Yes")).'\';
				var noButton = \''.dol_escape_js($langs->transnoentities("No")).'\';
				var token = \''.currentToken().'\';

				// Set constant
				$("#set_" + code).click(function() {
					if (input.alert && input.alert.set) {
						if (input.alert.set.yesButton) yesButton = input.alert.set.yesButton;
						if (input.alert.set.noButton)  noButton = input.alert.set.noButton;
						confirmConstantAction("set", url, code, input, input.alert.set, entity, yesButton, noButton, strict, userid, token);
					} else {
						setConstant(url, code, input, entity, 0, '.((int) $forcereload).', userid, token);
					}
				});

				// Del constant
				$("#del_" + code).click(function() {
					if (input.alert && input.alert.del) {
						if (input.alert.del.yesButton) yesButton = input.alert.del.yesButton;
						if (input.alert.del.noButton)  noButton = input.alert.del.noButton;
						confirmConstantAction("del", url, code, input, input.alert.del, entity, yesButton, noButton, strict, userid, token);
					} else {';
		if (empty($setzeroinsteadofdel)) {
			$out .= ' 	delConstant(url, code, input, entity, 0, '.((int) $forcereload).', userid, token);';
		} else {
			$out .= ' 	setConstant(url, code, input, entity, 0, '.((int) $forcereload).', userid, token, 0);';
		}
		$out .= '	}
				});
			});
		</script>'."\n";

		$out .= '<div id="confirm_'.$code.'" title="" style="display: none;"></div>';
		$out .= '<span id="set_'.$code.'" class="valignmiddle inline-block linkobject '.(getDolGlobalString($code) ? 'hideobject' : '').'">'.($revertonoff ? img_picto($langs->trans("Enabled"), 'switch_on', '', false, 0, 0, '', '', $marginleftonlyshort) : img_picto($langs->trans("Disabled"), 'switch_off', '', false, 0, 0, '', '', $marginleftonlyshort)).'</span>';
		$out .= '<span id="del_'.$code.'" class="valignmiddle inline-block linkobject '.(getDolGlobalString($code) ? '' : 'hideobject').'">'.($revertonoff ? img_picto($langs->trans("Disabled"), 'switch_off'.$suffix, '', false, 0, 0, '', '', $marginleftonlyshort) : img_picto($langs->trans("Enabled"), 'switch_on'.$suffix, '', false, 0, 0, '', '', $marginleftonlyshort)).'</span>';
		$out .= "\n";
	}

	return $out;
}

/**
 *  On/off button to change a property status of an object
 *  This uses the ajax service objectonoff.php (May be called when MAIN_DIRECT_STATUS_UPDATE is set for some pages)
 *
 *  @param  Object  $object     Object to set
 *  @param  string  $code       Name of property in object : 'status' or 'status_buy' for product by example
 *  @param  string  $field      Name of database field : 'tosell' or 'tobuy' for product by example
 *  @param  string  $text_on    Text if on ('Text' or 'Text:Picto on:Css picto on')
 *  @param  string  $text_off   Text if off ('Text' or 'Text:Picto off:Css picto off')
 *  @param  array   $input      Array of type->list of CSS element to switch. Example: array('disabled'=>array(0=>'cssid'))
 *  @param	string	$morecss	More CSS
 *  @param	string	$htmlname	Name of HTML component. Keep '' or use a different value if you need to use this component several time on the same page for the same field.
 *  @param	int		$forcenojs	Force the component to work as link post (without javascript) instead of ajax call
 *  @param	string	$moreparam	When $forcenojs=1 then we can add more parameters to the backtopage URL. String must url encoded. Example: 'abc=def&fgh=ijk'
 *  @return string              html for button on/off
 *  @see ajax_constantonoff() to update that value of a constant
 */
function ajax_object_onoff($object, $code, $field, $text_on, $text_off, $input = array(), $morecss = '', $htmlname = '', $forcenojs = 0, $moreparam = '')
{
	global $conf, $langs;

	if (empty($htmlname)) {
		$htmlname = $code;
	}
	//var_dump($object->module); var_dump($object->element);

	$out = '';

	if (!empty($conf->use_javascript_ajax) && empty($forcenojs)) {
		$out .= '<script>
        $(function() {
            var input = '.json_encode($input).';

            // Set constant
            $("#set_'.$htmlname.'_'.$object->id.'").click(function() {
				console.log("Click managed by ajax_object_onoff");
                $.get( "'.DOL_URL_ROOT.'/core/ajax/objectonoff.php", {
                    action: \'set\',
                    field: \''.dol_escape_js($field).'\',
                    value: \'1\',
                    element: \''.dol_escape_js((empty($object->module) || $object->module == $object->element) ? $object->element : $object->element.'@'.$object->module).'\',
                    id: \''.((int) $object->id).'\',
					token: \''.currentToken().'\'
                },
                function() {
                    $("#set_'.$htmlname.'_'.$object->id.'").hide();
                    $("#del_'.$htmlname.'_'.$object->id.'").show();
                    // Enable another element
                    if (input.disabled && input.disabled.length > 0) {
                        $.each(input.disabled, function(key,value) {
                            $("#" + value).removeAttr("disabled");
                            if ($("#" + value).hasClass("butActionRefused") == true) {
                                $("#" + value).removeClass("butActionRefused");
                                $("#" + value).addClass("butAction");
                            }
                        });
                    // Show another element
                    } else if (input.showhide && input.showhide.length > 0) {
                        $.each(input.showhide, function(key,value) {
                            $("#" + value).show();
                        });
                    }
                });
            });

            // Del constant
            $("#del_'.$htmlname.'_'.$object->id.'").click(function() {
				console.log("Click managed by ajax_object_onoff");
                $.get( "'.DOL_URL_ROOT.'/core/ajax/objectonoff.php", {
                    action: \'set\',
                    field: \''.dol_escape_js($field).'\',
                    value: \'0\',
                    element: \''.dol_escape_js((empty($object->module) || $object->module == $object->element) ? $object->element : $object->element.'@'.$object->module).'\',
                    id: \''.((int) $object->id).'\',
					token: \''.currentToken().'\'
                },
                function() {
                    $("#del_'.$htmlname.'_'.$object->id.'").hide();
                    $("#set_'.$htmlname.'_'.$object->id.'").show();
                    // Disable another element
                    if (input.disabled && input.disabled.length > 0) {
                        $.each(input.disabled, function(key,value) {
                            $("#" + value).prop("disabled", true);
                            if ($("#" + value).hasClass("butAction") == true) {
                                $("#" + value).removeClass("butAction");
                                $("#" + value).addClass("butActionRefused");
                            }
                        });
                    // Hide another element
                    } else if (input.showhide && input.showhide.length > 0) {
                        $.each(input.showhide, function(key,value) {
                            $("#" + value).hide();
                        });
                    }
                });
            });
        });
    </script>';
	}

	$switchon = 'switch_on';
	$switchoff = 'switch_off';
	$cssswitchon = '';
	$cssswitchoff = '';
	$tmparray = explode(':', $text_on);
	if (!empty($tmparray[1])) {
		$text_on = $tmparray[0];
		$switchon = $tmparray[1];
		if (!empty($tmparray[2])) {
			$cssswitchon = $tmparray[2];
		}
	}
	$tmparray = explode(':', $text_off);
	if (!empty($tmparray[1])) {
		$text_off = $tmparray[0];
		$switchoff = $tmparray[1];
		if (!empty($tmparray[2])) {
			$cssswitchoff = $tmparray[2];
		}
	}

	if (empty($conf->use_javascript_ajax) || $forcenojs) {
		$out .= '<a id="set_'.$htmlname.'_'.$object->id.'" class="linkobject '.($object->$code == 1 ? 'hideobject' : '').($morecss ? ' '.$morecss : '').'" href="'.DOL_URL_ROOT.'/core/ajax/objectonoff.php?action=set&token='.newToken().'&id='.((int) $object->id).'&element='.urlencode($object->element).'&field='.urlencode($field).'&value=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?id='.$object->id.($moreparam ? '&'.$moreparam : '')).'">'.img_picto($langs->trans($text_off), $switchoff, '', false, 0, 0, '', $cssswitchoff).'</a>';
		$out .= '<a id="del_'.$htmlname.'_'.$object->id.'" class="linkobject '.($object->$code == 1 ? '' : 'hideobject').($morecss ? ' '.$morecss : '').'" href="'.DOL_URL_ROOT.'/core/ajax/objectonoff.php?action=set&token='.newToken().'&id='.((int) $object->id).'&element='.urlencode($object->element).'&field='.urlencode($field).'&value=0&backtopage='.urlencode($_SERVER["PHP_SELF"].'?id='.$object->id.($moreparam ? '&'.$moreparam : '')).'">'.img_picto($langs->trans($text_on), $switchon, '', false, 0, 0, '', $cssswitchon).'</a>';
	} else {
		$out .= '<span id="set_'.$htmlname.'_'.$object->id.'" class="linkobject '.($object->$code == 1 ? 'hideobject' : '').($morecss ? ' '.$morecss : '').'">'.img_picto($langs->trans($text_off), $switchoff, '', false, 0, 0, '', $cssswitchoff).'</span>';
		$out .= '<span id="del_'.$htmlname.'_'.$object->id.'" class="linkobject '.($object->$code == 1 ? '' : 'hideobject').($morecss ? ' '.$morecss : '').'">'.img_picto($langs->trans($text_on), $switchon, '', false, 0, 0, '', $cssswitchon).'</span>';
	}

	return $out;
}
