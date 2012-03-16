<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2012 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *  @param	string	$option				More parameters on URL request
 *  @param	int		$minLength			Minimum number of chars to trigger that Ajax search
 *  @param	int		$autoselect			Automatic selection if just one value
 *	@return string              		Script
 */
function ajax_autocompleter($selected,$htmlname,$url,$option='',$minLength=2,$autoselect=0)
{
    if (empty($minLength)) $minLength=1;

	$script = '<input type="hidden" name="'.$htmlname.'" id="'.$htmlname.'" value="'.$selected.'" />';

	$script.= '<script type="text/javascript">';
	$script.= 'jQuery(document).ready(function() {
					var autoselect = '.$autoselect.';
					jQuery("input#search_'.$htmlname.'").blur(function() {
    					//console.log(this.value.length);
					    if (this.value.length == 0)
					    {
                            jQuery("#search_'.$htmlname.'").val("");
                            jQuery("#'.$htmlname.'").val("");
					    }
                    });
    				jQuery("input#search_'.$htmlname.'").autocomplete({
    					source: function( request, response ) {
    						jQuery.get("'.$url.($option?'?'.$option:'').'", { '.$htmlname.': request.term }, function(data){
								response( jQuery.map( data, function( item ) {
									if (autoselect == 1 && data.length == 1) {
										jQuery("#search_'.$htmlname.'").val(item.value);
										jQuery("#'.$htmlname.'").val(item.key);
									}
									var label = item.label.toString();
									return { label: label, value: item.value, id: item.key}
								}));
							}, "json");
						},
						dataType: "json",
    					minLength: '.$minLength.',
    					select: function( event, ui ) {
    						jQuery("#'.$htmlname.'").val(ui.item.id);
    					}
					}).data( "autocomplete" )._renderItem = function( ul, item ) {
						return jQuery( "<li></li>" )
						.data( "item.autocomplete", item )
						.append( \'<a href="#"><span class="tag">\' + item.label + "</span></a>" )
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
					jQuery(this ).dialog(\'close\');
				}
	        }
	    });
	});
	</script>';

    $msg.= "\n";

    return $msg;
}

/**
 * 	Convert a select html field into an ajax combobox
 *
 * 	@param	string	$htmlname		Name of html field
 * 	@param	array	$event			Event options
 *  @return	string					Return html string to convert a select field into a combo
 */
function ajax_combobox($htmlname, $event=array())
{
	$msg.= '<script type="text/javascript">
    $(function() {
    	$("#'.$htmlname.'").combobox({
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
    		$.get(url,
					{
						action: method,
						id: id,
						htmlname: htmlname
					},
					function(response) {
						$("select#" + htmlname).html(response);
					});
		}
	});';
    $msg.= "</script>\n";

    return $msg;
}

/**
 * 	On/off button for constant
 *
 * 	@param	string	$code		Name of constant
 * 	@param	array	$input		Input element
 * 	@param	int		$entity		Entity to set
 * 	@return	void
 */
function ajax_constantonoff($code,$input=array(),$entity=false)
{
	global $conf, $langs;

	$entity = ((isset($entity) && is_numeric($entity) && $entity >= 0) ? $entity : $conf->entity);

	$out= '<script type="text/javascript">
		$(function() {
			var input = '.json_encode($input).';

			// Set constant
			$("#set_'.$code.'").click(function() {
				$.get( "'.DOL_URL_ROOT.'/core/ajax/constantonoff.php", {
					action: \'set\',
					name: \''.$code.'\',
					entity: \''.$entity.'\'
				},
				function() {
					$("#set_'.$code.'").hide();
					$("#del_'.$code.'").show();
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
			$("#del_'.$code.'").click(function() {
				$.get( "'.DOL_URL_ROOT.'/core/ajax/constantonoff.php", {
					action: \'del\',
					name: \''.$code.'\',
					entity: \''.$entity.'\'
				},
				function() {
					$("#del_'.$code.'").hide();
					$("#set_'.$code.'").show();
					// Disable another element
					if (input.disabled && input.disabled.length > 0) {
						$.each(input.disabled, function(key,value) {
							$("#" + value).attr("disabled", true);
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

	$out.= '<span id="set_'.$code.'" class="linkobject '.($conf->global->$code?'hideobject':'').'">'.img_picto($langs->trans("Disabled"),'switch_off').'</span>';
	$out.= '<span id="del_'.$code.'" class="linkobject '.($conf->global->$code?'':'hideobject').'">'.img_picto($langs->trans("Enabled"),'switch_on').'</span>';

	return $out;
}

?>