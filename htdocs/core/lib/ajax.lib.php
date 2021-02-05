<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2015 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
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
 * Generic function that return javascript to add to a page to transform a common input field into an autocomplete field by calling an Ajax page (ex: /societe/ajaxcompanies.php).
 * The HTML field must be an input text with id=search_$htmlname.
 * This use the jQuery "autocomplete" function. If we want to use the select2, we must also convert the input into select on funcntions that call this method.
 *
 * @param string	$selected 			Preselected value
 * @param string	$htmlname 			HTML name of input field
 * @param string	$url 				Ajax Url to call for request: /path/page.php. Must return a json array ('key'=>id, 'value'=>String shown into input field once selected, 'label'=>String shown into combo list)
 * @param string	$urloption			More parameters on URL request
 * @param int		$minLength			Minimum number of chars to trigger that Ajax search
 * @param int		$autoselect			Automatic selection if just one value (trigger("change") on field is done is search return only 1 result)
 * @param array		$ajaxoptions		Multiple options array
 *                                      - Ex: array('update'=>array('field1','field2'...)) will reset field1 and field2 once select done
 *                                      - Ex: array('disabled'=> )
 *                                      - Ex: array('show'=> )
 *                                      - Ex: array('update_textarea'=> )
 *                                      - Ex: array('option_disabled'=> id to disable and warning to show if we select a disabled value (this is possible when using autocomplete ajax)
 * @param string	$moreparams			More params provided to ajax call
 * @return string   					Script
 */
function ajax_autocompleter($selected, $htmlname, $url, $urloption = '', $minLength = 2, $autoselect = 0, $ajaxoptions = array(), $moreparams = '')
{
	if (empty($minLength)) $minLength = 1;

	$dataforrenderITem = 'ui-autocomplete';
	$dataforitem = 'ui-autocomplete-item';
	// Allow two constant to use other values for backward compatibility
	if (defined('JS_QUERY_AUTOCOMPLETE_RENDERITEM')) $dataforrenderITem = constant('JS_QUERY_AUTOCOMPLETE_RENDERITEM');
	if (defined('JS_QUERY_AUTOCOMPLETE_ITEM'))       $dataforitem = constant('JS_QUERY_AUTOCOMPLETE_ITEM');

	// Input search_htmlname is original field
	// Input htmlname is a second input field used when using ajax autocomplete.
	$script = '<input type="hidden" name="'.$htmlname.'" id="'.$htmlname.'" value="'.$selected.'" '.($moreparams ? $moreparams : '').' />';

	$script .= '<!-- Javascript code for autocomplete of field '.$htmlname.' -->'."\n";
	$script .= '<script>'."\n";
	$script .= '$(document).ready(function() {
					var autoselect = '.$autoselect.';
					var options = '.json_encode($ajaxoptions).'; /* Option of actions to do after keyup, or after select */

					/* Remove selected id as soon as we type or delete a char (it means old selection is wrong). Use keyup/down instead of change to avoid loosing the product id. This is needed only for select of predefined product */
					$("input#search_'.$htmlname.'").keydown(function(e) {
						if (e.keyCode != 9)		/* If not "Tab" key */
						{
							if (e.keyCode == 13) { return false; } /* disable "ENTER" key useful for barcode readers */
							console.log("Clear id previously selected for field '.$htmlname.'");
							$("#'.$htmlname.'").val("");
						}
					});

					// Check options for secondary actions when keyup
					$("input#search_'.$htmlname.'").keyup(function() {
						    if ($(this).val().length == 0)
						    {
	                            $("#search_'.$htmlname.'").val("");
	                            $("#'.$htmlname.'").val("").trigger("change");
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

    				$("input#search_'.$htmlname.'").autocomplete({
    					source: function( request, response ) {
    						$.get("'.$url.($urloption ? '?'.$urloption : '').'", { '.$htmlname.': request.term }, function(data){
								if (data != null)
								{
									response($.map( data, function(item) {
										if (autoselect == 1 && data.length == 1) {
											$("#search_'.$htmlname.'").val(item.value);
											$("#'.$htmlname.'").val(item.key).trigger("change");
										}
										var label = item.label.toString();
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
										return { label: label, value: item.value, id: item.key, disabled: item.disabled,
												 update: update, textarea: textarea,
												 pbq: item.pbq, type: item.type, qty: item.qty, discount: item.discount, pricebasetype: item.pricebasetype, price_ht: item.price_ht, price_ttc: item.price_ttc }
									}));
								}
								else console.error("Error: Ajax url '.$url.($urloption ? '?'.$urloption : '').' has returned an empty page. Should be an empty json array.");
							}, "json");
						},
						dataType: "json",
    					minLength: '.$minLength.',
    					select: function( event, ui ) {		// Function ran once new value has been selected into javascript combo
    						console.log("We will trigger change on input '.$htmlname.' because of the select definition of autocomplete code for input#search_'.$htmlname.'");
    					    console.log("Selected id = "+ui.item.id+" - If this value is null, it means you select a record with key that is null so selection is not effective");

							console.log("Propagate before some properties");
							$("#'.$htmlname.'").attr("data-pbq", ui.item.pbq);
							$("#'.$htmlname.'").attr("data-pbqup", ui.item.price_ht);
							$("#'.$htmlname.'").attr("data-pbqbase", ui.item.pricebasetype);
							$("#'.$htmlname.'").attr("data-pbqqty", ui.item.qty);
							$("#'.$htmlname.'").attr("data-pbqpercent", ui.item.discount);

    						$("#'.$htmlname.'").val(ui.item.id).trigger("change");	// Select new value

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
    							console.log("Make action disabled on each "+options.option_disabled)
    							$.each(options.disabled, function(key, value) {
									$("#" + value).prop("disabled", true);
    							});
    						}
    						if (options.show) {
    							console.log("Make action show on each "+options.show)
    							$.each(options.show, function(key, value) {
    								$("#" + value).show().trigger("show");
    							});
    						}

    						// Update an input
    						if (ui.item.update) {
    							console.log("Make action update on each ui.item.update")
    							// loop on each "update" fields
    							$.each(ui.item.update, function(key, value) {
    								$("#" + key).val(value).trigger("change");
    							});
    						}
    						if (ui.item.textarea) {
    							console.log("Make action textarea on each ui.item.textarea")
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

    						$("#search_'.$htmlname.'").trigger("change");	// We have changed value of the combo select, we must be sure to trigger all js hook binded on this event. This is required to trigger other javascript change method binded on original field by other code.
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
 *	Generic function that return javascript to add to a page to transform a common input field into an autocomplete field by calling an Ajax page (ex: core/ajax/ziptown.php).
 *  The Ajax page can also returns several values (json format) to fill several input fields.
 *  The HTML field must be an input text with id=$htmlname.
 *  This use the jQuery "autocomplete" function.
 *
 *	@param	string	$htmlname           HTML name of input field
 *	@param	array	$fields				Array of key of fields to autocomplete
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
								    if (ui.item[fields[i]] > 0)     // Do not erase country if unknown
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
                                    if (ui.item[fields[i]] > 0)     // Do not erase state if unknown
                                    {
								    	oldvalue=jQuery("#" + fields[i]).val();
								        newvalue=ui.item[fields[i]];
								    	//alert(oldvalue+" "+newvalue);
                                        jQuery("#" + fields[i]).val(ui.item[fields[i]]);    // This may fails if not correct country
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
	global $langs;

	$newtitle = dol_textishtml($title) ?dol_string_nohtmltag($title, 1) : $title;
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
 * @return	string								Return html string to convert a select field into a combo, or '' if feature has been disabled for some reason.
 * @see selectArrayAjax() of html.form.class
 */
function ajax_combobox($htmlname, $events = array(), $minLengthToAutocomplete = 0, $forcefocus = 0, $widthTypeOfAutocomplete = 'resolve')
{
	global $conf;

	// select2 can be disabled for smartphones
	if (!empty($conf->browser->layout) && $conf->browser->layout == 'phone' && !empty($conf->global->MAIN_DISALLOW_SELECT2_WITH_SMARTPHONE)) return '';

	if (!empty($conf->global->MAIN_DISABLE_AJAX_COMBOX)) return '';
	if (empty($conf->use_javascript_ajax)) return '';
	if (empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) && !defined('REQUIRE_JQUERY_MULTISELECT')) return '';
	if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) return '';

	if (empty($minLengthToAutocomplete)) $minLengthToAutocomplete = 0;

	$tmpplugin = 'select2';
	$msg = "\n".'<!-- JS CODE TO ENABLE '.$tmpplugin.' for id = '.$htmlname.' -->
          <script>
        	$(document).ready(function () {
        		$(\''.(preg_match('/^\./', $htmlname) ? $htmlname : '#'.$htmlname).'\').'.$tmpplugin.'({
        		    dir: \'ltr\',
        			width: \''.$widthTypeOfAutocomplete.'\',		/* off or resolve */
					minimumInputLength: '.$minLengthToAutocomplete.',
					language: select2arrayoflanguage,
    				containerCssClass: \':all:\',					/* Line to add class of origin SELECT propagated to the new <span class="select2-selection...> tag */
					templateResult: function (data, container) {	/* Format visible output into combo list */
	 					/* Code to add class of origin OPTION propagated to the new select2 <li> tag */
						if (data.element) { $(container).addClass($(data.element).attr("class")); }
					    //console.log(data.html);
						if ($(data.element).attr("data-html") != undefined) return htmlEntityDecodeJs($(data.element).attr("data-html"));		// If property html set, we decode html entities and use this
					    return data.text;
					},
					templateSelection: function (selection) {		/* Format visible output of selected value */
						return selection.text;
					},
					escapeMarkup: function(markup) {
						return markup;
					},
					dropdownCssClass: \'ui-dialog\'
				})';
	if ($forcefocus) $msg .= '.select2(\'focus\')';
	$msg .= ';'."\n";

	if (is_array($events) && count($events))    // If an array of js events to do were provided.
	{
		$msg .= '
			jQuery("#'.$htmlname.'").change(function () {
				var obj = '.json_encode($events).';
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
							$("select#" + htmlname).html(response.value);
							if (response.num) {
								var selecthtml_str = response.value;
								var selecthtml_dom=$.parseHTML(selecthtml_str);
								$("#inputautocomplete"+htmlname).val(selecthtml_dom[0][0].innerHTML);
							} else {
								$("#inputautocomplete"+htmlname).val("");
							}
							$("select#" + htmlname).change();	/* Trigger event change */
						}
				);
			}';
	}

	$msg .= '});'."\n";
	$msg .= "</script>\n";

	return $msg;
}

/**
 * 	On/off button for constant
 *
 * 	@param	string	$code					Name of constant
 * 	@param	array	$input					Array of options. ("disabled"|"enabled'|'set'|'del') => CSS element to switch, 'alert' => message to show, ... Example: array('disabled'=>array(0=>'cssid'))
 * 	@param	int		$entity					Entity to set. Use current entity if null.
 *  @param	int		$revertonoff			Revert on/off
 *  @param	int		$strict					Use only "disabled" with delConstant and "enabled" with setConstant
 *  @param	int		$forcereload			Force to reload page if we click/change value (this is supported only when there is no 'alert' option in input)
 *  @param	string	$marginleftonlyshort	1 = Add a short left margin on picto, 2 = Add a larger left margin on picto, 0 = No left margin. Works for fontawesome picto only.
 *  @param	int		$forcenoajax			1=Force to use a ahref link instead of ajax code.
 * 	@return	string
 */
function ajax_constantonoff($code, $input = array(), $entity = null, $revertonoff = 0, $strict = 0, $forcereload = 0, $marginleftonlyshort = 2, $forcenoajax = 0)
{
	global $conf, $langs, $user;

	$entity = ((isset($entity) && is_numeric($entity) && $entity >= 0) ? $entity : $conf->entity);
	if (!isset($input)) $input = array();

	if (empty($conf->use_javascript_ajax) || $forcenoajax)
	{
		if (empty($conf->global->$code)) print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_'.$code.'&token='.newToken().'&entity='.$entity.'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
		else print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_'.$code.'&token='.newToken().'&entity='.$entity.'">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
	} else {
		$out = "\n<!-- Ajax code to switch constant ".$code." -->".'
		<script>
			$(document).ready(function() {
				var input = '.json_encode($input).';
				var url = \''.DOL_URL_ROOT.'/core/ajax/constantonoff.php\';
				var code = \''.$code.'\';
				var entity = \''.$entity.'\';
				var strict = \''.$strict.'\';
				var userid = \''.$user->id.'\';
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
						setConstant(url, code, input, entity, 0, '.$forcereload.', userid, token);
					}
				});

				// Del constant
				$("#del_" + code).click(function() {
					if (input.alert && input.alert.del) {
						if (input.alert.del.yesButton) yesButton = input.alert.del.yesButton;
						if (input.alert.del.noButton)  noButton = input.alert.del.noButton;
						confirmConstantAction("del", url, code, input, input.alert.del, entity, yesButton, noButton, strict, userid, token);
					} else {
						delConstant(url, code, input, entity, 0, '.$forcereload.', userid, token);
					}
				});
			});
		</script>'."\n";

		$out .= '<div id="confirm_'.$code.'" title="" style="display: none;"></div>';
		$out .= '<span id="set_'.$code.'" class="valignmiddle linkobject '.(!empty($conf->global->$code) ? 'hideobject' : '').'">'.($revertonoff ?img_picto($langs->trans("Enabled"), 'switch_on', '', false, 0, 0, '', '', $marginleftonlyshort) : img_picto($langs->trans("Disabled"), 'switch_off', '', false, 0, 0, '', '', $marginleftonlyshort)).'</span>';
		$out .= '<span id="del_'.$code.'" class="valignmiddle linkobject '.(!empty($conf->global->$code) ? '' : 'hideobject').'">'.($revertonoff ?img_picto($langs->trans("Disabled"), 'switch_off', '', false, 0, 0, '', '', $marginleftonlyshort) : img_picto($langs->trans("Enabled"), 'switch_on', '', false, 0, 0, '', '', $marginleftonlyshort)).'</span>';
		$out .= "\n";
	}

	return $out;
}

/**
 *  On/off button to change status of an object
 *  This is called when MAIN_DIRECT_STATUS_UPDATE is set and it use tha ajax service objectonoff.php
 *
 *  @param  Object  $object     Object to set
 *  @param  string  $code       Name of constant : status or status_buy for product by example
 *  @param  string  $field      Name of database field : 'tosell' or 'tobuy' for product by example
 *  @param  string  $text_on    Text if on
 *  @param  string  $text_off   Text if off
 *  @param  array   $input      Array of type->list of CSS element to switch. Example: array('disabled'=>array(0=>'cssid'))
 *  @return string              html for button on/off
 */
function ajax_object_onoff($object, $code, $field, $text_on, $text_off, $input = array())
{
	global $langs;

	$out = '<script>
        $(function() {
            var input = '.json_encode($input).';

            // Set constant
            $("#set_'.$code.'_'.$object->id.'").click(function() {
                $.get( "'.DOL_URL_ROOT.'/core/ajax/objectonoff.php", {
                    action: \'set\',
                    field: \''.$field.'\',
                    value: \'1\',
                    element: \''.$object->element.'\',
                    id: \''.$object->id.'\',
					token: \''.newToken().'\'
                },
                function() {
                    $("#set_'.$code.'_'.$object->id.'").hide();
                    $("#del_'.$code.'_'.$object->id.'").show();
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
            $("#del_'.$code.'_'.$object->id.'").click(function() {
                $.get( "'.DOL_URL_ROOT.'/core/ajax/objectonoff.php", {
                    action: \'set\',
                    field: \''.$field.'\',
                    value: \'0\',
                    element: \''.$object->element.'\',
                    id: \''.$object->id.'\',
					token: \''.newToken().'\'
                },
                function() {
                    $("#del_'.$code.'_'.$object->id.'").hide();
                    $("#set_'.$code.'_'.$object->id.'").show();
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
	$out .= '<span id="set_'.$code.'_'.$object->id.'" class="linkobject '.($object->$code == 1 ? 'hideobject' : '').'">'.img_picto($langs->trans($text_off), 'switch_off').'</span>';
	$out .= '<span id="del_'.$code.'_'.$object->id.'" class="linkobject '.($object->$code == 1 ? '' : 'hideobject').'">'.img_picto($langs->trans($text_on), 'switch_on').'</span>';

	return $out;
}
