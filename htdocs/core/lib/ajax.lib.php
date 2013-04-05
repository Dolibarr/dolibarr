<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *  \file		htdocs/core/lib/ajax.lib.php
 *  \brief		Page called by Ajax request for produts
 */


/**
 *	Get value of an HTML field, do Ajax process and show result
 *
 *  @param	string	$selected           Preselecte value
 *	@param	string	$htmlname           HTML name of input field
 *	@param	string	$url                Url for request: /chemin/fichier.php
 *  @param	string	$urloption			More parameters on URL request
 *  @param	int		$minLength			Minimum number of chars to trigger that Ajax search
 *  @param	int		$autoselect			Automatic selection if just one value
 *  @param	array	$ajaxoptions		Multiple options array
 *	@return string              		Script
 */
function ajax_autocompleter($selected, $htmlname, $url, $urloption='', $minLength=2, $autoselect=0, $ajaxoptions=array())
{
    if (empty($minLength)) $minLength=1;

	$script = '<input type="hidden" name="'.$htmlname.'" id="'.$htmlname.'" value="'.$selected.'" />';

	$script.= '<script type="text/javascript">';
	$script.= '$(document).ready(function() {
					var autoselect = '.$autoselect.';
					var options = '.json_encode($ajaxoptions).';

					// Remove product id before select another product
					// use keyup instead change to avoid loosing the product id
					$("input#search_'.$htmlname.'").keydown(function() {
						//console.log(\'purge_id_after_keydown\');
						$("#'.$htmlname.'").val("");
					});
					$("input#search_'.$htmlname.'").change(function() {
						//console.log(\'keyup\');
						$("#'.$htmlname.'").trigger("change");
					});
					// Check when keyup
					$("input#search_'.$htmlname.'").onDelayedKeyup({ handler: function() {
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
						}
                    });
    				$("input#search_'.$htmlname.'").autocomplete({
    					source: function( request, response ) {
    						$.get("'.$url.($urloption?'?'.$urloption:'').'", { '.$htmlname.': request.term }, function(data){
								response($.map( data, function( item ) {
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
									return { label: label, value: item.value, id: item.key, update: update, textarea: textarea, disabled: item.disabled }
								}));
							}, "json");
						},
						dataType: "json",
    					minLength: '.$minLength.',
    					select: function( event, ui ) {
							//console.log(\'set value of id with \'+ui.item.id);
    						$("#'.$htmlname.'").val(ui.item.id).trigger("change");
    						// Disable an element
    						if (options.option_disabled) {
    							if (ui.item.disabled) {
    								$("#" + options.option_disabled).attr("disabled", "disabled");
    								if (options.error) {
    									$.jnotify(options.error, "error", true);
    								}
    							} else {
    								$("#" + options.option_disabled).removeAttr("disabled");
    							}
    						}
    						if (options.disabled) {
    							$.each(options.disabled, function(key, value) {
    								$("#" + value).attr("disabled", "disabled");
    							});
    						}
    						if (options.show) {
    							$.each(options.show, function(key, value) {
    								$("#" + value).show().trigger("show");
    							});
    						}
    						// Update an input
    						if (ui.item.update) {
    							// clear old data before update
    							$.each(ui.item.update, function(key, value) {
    								$("#" + key).val("");
    							});
    							// update fields
    							$.each(ui.item.update, function(key, value) {
    								$("#" + key).val(value).trigger("change");
    							});
    						}
    						if (ui.item.textarea) {
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
    					}
					}).data( "autocomplete" )._renderItem = function( ul, item ) {
						return $( "<li></li>" )
						.data( "item.autocomplete", item )
						.append( \'<a><span class="tag">\' + item.label + "</span></a>" )
						.appendTo(ul);
					};
  				});';
	$script.= '</script>';

	return $script;
}

/**
 *	Get value of field, do Ajax process and return result
 *
 *	@param	string	$htmlname           Name of field
 *	@param	string	$fields				other fields to autocomplete
 *	@param	string	$url                Chemin du fichier de reponse : /chemin/fichier.php
 *	@param	string	$option				More parameters on URL request
 *	@param	int		$minLength			Minimum number of chars to trigger that Ajax search
 *	@param	int		$autoselect			Automatic selection if just one value
 *	@return string              		Script
 */
function ajax_multiautocompleter($htmlname,$fields,$url,$option='',$minLength=2,$autoselect=0)
{
	$script = '<!-- Autocomplete -->'."\n";
	$script.= '<script type="text/javascript">';
	$script.= 'jQuery(document).ready(function() {
					var fields = '.json_encode($fields).';
					var length = fields.length;
					var autoselect = '.$autoselect.';
					//alert(fields + " " + length);

    				jQuery("input#'.$htmlname.'").autocomplete({
    					dataType: "json",
    					minLength: '.$minLength.',
    					source: function( request, response ) {
    						jQuery.getJSON( "'.$url.($option?'?'.$option:'').'", { '.$htmlname.': request.term }, function(data){
								response( jQuery.map( data, function( item ) {
									if (autoselect == 1 && data.length == 1) {
										jQuery("#'.$htmlname.'").val(item.value);
										// TODO move this to specific request
										if (item.states) {
											jQuery("#departement_id").html(item.states);
										}
										for (i=0;i<length;i++) {
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

    						for (i=0;i<length;i++) {
    							//alert(fields[i] + " = " + ui.item[fields[i]]);
								if (fields[i]=="selectcountry_id")
								{
								    if (ui.item[fields[i]] > 0)     // Do not erase country if unknown
								    {
								        jQuery("#" + fields[i]).val(ui.item[fields[i]]);
								        // If we set new country and new state, we need to set a new list of state to allow change
                                        if (ui.item.states && ui.item["departement_id"] != jQuery("#departement_id").value) {
                                            jQuery("#departement_id").html(ui.item.states);
                                        }
								    }
								}
                                else if (fields[i]=="state_id" || fields[i]=="departement_id")
                                {
                                    if (ui.item[fields[i]] > 0)     // Do not erase state if unknown
                                    {
                                        jQuery("#" + fields[i]).val(ui.item[fields[i]]);    // This may fails if not correct country
                                    }
                                }
								else if (ui.item[fields[i]]) {   // If defined
								    //alert(fields[i]);
								    //alert(ui.item[fields[i]]);
							        jQuery("#" + fields[i]).val(ui.item[fields[i]]);
								}
							}
    					}
					});
  				});';
	$script.= '</script>';

	return $script;
}

/**
 *	Show an ajax dialog
 *
 *	@param	string	$title		Title of dialog box
 *	@param	string	$message	Message of dialog box
 *	@param	int		$w			Width of dialog box
 *	@param	int		$h			height of dialog box
 *	@return	void
 */
function ajax_dialog($title,$message,$w=350,$h=150)
{
	global $langs;

	$msg.= '<div id="dialog-info" title="'.dol_escape_htmltag($title).'">';
	$msg.= $message;
	$msg.= '</div>'."\n";
    $msg.= '<script type="text/javascript">
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

    $msg.= "\n";

    return $msg;
}

/**
 * 	Convert a html select field into an ajax combobox
 *
 * 	@param	string	$htmlname					Name of html select field
 * 	@param	array	$event						Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
 *  @param  int		$minLengthToAutocomplete	Minimum length of input string to start autocomplete
 *  @return	string								Return html string to convert a select field into a combo
 */
function ajax_combobox($htmlname, $event=array(), $minLengthToAutocomplete=0)
{
	global $conf;

	if (! empty($conf->browser->phone)) return '';	// combobox disabled for smartphones (does not works)

	$msg = '<script type="text/javascript">
    $(function() {
    	$("#'.$htmlname.'").combobox({
    		minLengthToAutocomplete : '.$minLengthToAutocomplete.',
    		selected : function(event,ui) {
    			var obj = '.json_encode($event).';
    			$.each(obj, function(key,values) {
    				if (values.method.length) {
    					getMethod(values);
    				}
				});
			}
		});

		function getMethod(obj) {
			var id = $("#'.$htmlname.'").val();
			var method = obj.method;
			var url = obj.url;
			var htmlname = obj.htmlname;
    		$.getJSON(url,
					{
						action: method,
						id: id,
						htmlname: htmlname
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
					});
		}
	});';
    $msg.= "</script>\n";

    return $msg;
}

/**
 * 	On/off button for constant
 *
 * 	@param	string	$code			Name of constant
 * 	@param	array	$input			Array of type->list of CSS element to switch. Example: array('disabled'=>array(0=>'cssid'))
 * 	@param	int		$entity			Entity to set
 *  @param	int		$revertonoff	Revert on/off
 * 	@return	void
 */
function ajax_constantonoff($code, $input=array(), $entity=null, $revertonoff=0)
{
	global $conf, $langs;

	$entity = ((isset($entity) && is_numeric($entity) && $entity >= 0) ? $entity : $conf->entity);

	$out= "\n<!-- Ajax code to switch constant ".$code." -->".'
	<script type="text/javascript">
		$(function() {
			var input = '.json_encode($input).';
			var url = \''.DOL_URL_ROOT.'/core/ajax/constantonoff.php\';
			var code = \''.$code.'\';
			var entity = \''.$entity.'\';
			var yesButton = "'.dol_escape_js($langs->transnoentities("Yes")).'";
			var noButton = "'.dol_escape_js($langs->transnoentities("No")).'";

			// Set constant
			$("#set_" + code).click(function() {
				if (input.alert && input.alert.set) {
					if (input.alert.set.yesButton) yesButton = input.alert.set.yesButton;
					if (input.alert.set.noButton)  noButton = input.alert.set.noButton;
					confirmConstantAction("set", url, code, input, input.alert.set, entity, yesButton, noButton);
				} else {
					setConstant(url, code, input, entity);
				}
			});

			// Del constant
			$("#del_" + code).click(function() {
				if (input.alert && input.alert.del) {
					if (input.alert.del.yesButton) yesButton = input.alert.del.yesButton;
					if (input.alert.del.noButton)  noButton = input.alert.del.noButton;
					confirmConstantAction("del", url, code, input, input.alert.del, entity, yesButton, noButton);
				} else {
					delConstant(url, code, input, entity);
				}
			});
		});
	</script>'."\n";

	$out.= '<div id="confirm_'.$code.'" title="" style="display: none;"></div>';
	$out.= '<span id="set_'.$code.'" class="linkobject '.(! empty($conf->global->$code)?'hideobject':'').'">'.($revertonoff?img_picto($langs->trans("Enabled"),'switch_on'):img_picto($langs->trans("Disabled"),'switch_off')).'</span>';
	$out.= '<span id="del_'.$code.'" class="linkobject '.(! empty($conf->global->$code)?'':'hideobject').'">'.($revertonoff?img_picto($langs->trans("Disabled"),'switch_off'):img_picto($langs->trans("Enabled"),'switch_on')).'</span>';
	$out.="\n";

	return $out;
}

?>
