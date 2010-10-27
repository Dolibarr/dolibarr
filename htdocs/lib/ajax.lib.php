<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2010 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 */

/**
 *  \file		htdocs/lib/ajax.lib.php
 *  \brief		Page called by Ajax request for produts
 *  \version	$Id$
 */


/**
 *	Get value of field, do Ajax process and return result
 *	@param	    htmlname            nom et id du champ
 *	@param	    url                 chemin du fichier de reponse : /chemin/fichier.php
 *	@return    	string              script complet
 */
function ajax_autocompleter($selected='',$htmlname,$url,$option='',$minLength=2)
{
	$script = '<input type="hidden" name="'.$htmlname.'" id="'.$htmlname.'" value="'.$selected.'" />';

	$script.= '<script type="text/javascript">';
	$script.= 'jQuery(document).ready(function() {
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
									if (data.length == 1) {
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
						.appendTo( ul );
					};
  				});';
	$script.= '</script>';

	return $script;
}

/**
 *	Get value of field, do Ajax process and return result
 *	@param	    htmlname            nom et id du champ
 *	@param		fields				other fields to autocomplete
 *	@param	    url                 chemin du fichier de reponse : /chemin/fichier.php
 *	@return    	string              script complet
 */
function ajax_multiautocompleter($htmlname,$fields,$url,$option='')
{
	$script='';

	$fields = php2js($fields);

	$script.= '<script type="text/javascript">';
	$script.= 'jQuery(document).ready(function() {
					var fields = '.$fields.';
					var length = fields.length;
					//alert(fields + " " + length);

    				jQuery("input#'.$htmlname.'").autocomplete({
    					dataType: "json",
    					minLength: 2,
    					source: function( request, response ) {
    						jQuery.getJSON( "'.$url.($option?'?'.$option:'').'", { '.$htmlname.': request.term }, function(data){
								response( jQuery.map( data, function( item ) {
									if (data.length == 1) {
										jQuery("#'.$htmlname.'").val(item.value);
										// TODO move this to specific request
										if (item.states) {
											jQuery("#departement_id").html(item.states);
										}
										for (i=0;i<length;i++) {
											if (item[fields[i]]) {
												jQuery("#" + fields[i]).val(item[fields[i]]);
											}
										}
									}
									return item
								}));
							});
    					},
    					select: function( event, ui ) {
    						// TODO move this to specific request
    						if (ui.item.states) {
    							jQuery("#departement_id").html(ui.item.states);
    						}
    						for (i=0;i<length;i++) {
    							//alert(fields[i] + " = " + ui.item[fields[i]]);
								if (ui.item[fields[i]]) {
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
 *	@param		title		Title of dialog box
 *	@param		message		Message of dialog box
 *	@param		w			Width of dialog box
 *	@param		h			height of dialog box
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
					jQuery( this ).dialog(\'close\');
				}
	        }
	    });
	});
	</script>';

    $msg.= "\n";

    return $msg;
}

/**
 *
 * Enter description here ...
 * @param unknown_type $var
 */
function php2js($var)
{
    if (is_array($var)) {
        $res = "[";
        $array = array();
        foreach ($var as $a_var) {
            $array[] = php2js($a_var);
        }
        return "[" . join(",", $array) . "]";
    }
    elseif (is_bool($var)) {
        return $var ? "true" : "false";
    }
    elseif (is_int($var) || is_integer($var) || is_double($var) || is_float($var)) {
        return $var;
    }
    elseif (is_string($var)) {
        return "\"" . addslashes(stripslashes($var)) . "\"";
    }
    // autres cas: objets, on ne les gère pas
    return FALSE;
}


?>