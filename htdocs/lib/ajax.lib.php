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

function ajax_indicator($htmlname,$indicator='working')
{
	$script.='<span id="indicator'.$htmlname.'" style="display: none">'.img_picto('Working...',$indicator.'.gif').'</span>';
	return $script;
}

/**
 *	\brief     Get value of a field, do Ajax process and show result
 *  \param	   htmlname            Name and id of field
 *  \param     keysearch           Optional field to filter
 *  \param	   url                 Full relative URL of page
 *  \param     option              champ supplementaire de recherche dans les parametres
 *  \param     indicator           Nom de l'image gif sans l'extension
 *  \return    string              script complet
 */
function ajax_updater($htmlname,$keysearch,$url,$option='',$indicator='working')
{
	$script = '<input type="hidden" name="'.$htmlname.'" id="'.$htmlname.'" value="">';
	if ($indicator) $script.=ajax_indicator($htmlname,$indicator);
	$script.='<script type="text/javascript">';
	$script.='var myIndicator'.$htmlname.' = {
                     onCreate: function(){
                            if($F("'.$keysearch.$htmlname.'")){
                                  Element.show(\'indicator'.$htmlname.'\');
                            }
                     },

                     onComplete: function() {
                            if(Ajax.activeRequestCount == 0){
                                  Element.hide(\'indicator'.$htmlname.'\');
                            }
                     }
             };';
	$script.='Ajax.Responders.register(myIndicator'.$htmlname.');';
	//print 'param='.$keysearch.'="+$F("'.$keysearch.$htmlname.'")+"&htmlname='.$htmlname.$option; exit;
	$script.='new Form.Element.Observer($("'.$keysearch.$htmlname.'"), 1,
			   function(){
				  var myAjax = new Ajax.Updater( {
					 success: \'ajdynfield'.$htmlname.'\'},
					 \''.$url.'\', {
						method: \'get\',
						parameters: "'.$keysearch.'="+$F("'.$keysearch.$htmlname.'")+"&htmlname='.$htmlname.$option.'"
					 });
				   });';
	$script.='</script>';
	$script.='<div class="nocellnopadd" id="ajdynfield'.$htmlname.'"></div>';

	return $script;
}

/**
 *	\brief     	Get value of field, do Ajax process and return result
 *	\param	    htmlname            nom et id du champ
 *	\param	    url                 chemin du fichier de reponse : /chemin/fichier.php
 *	\return    	string              script complet
 */
function ajax_autocompleter($selected='',$htmlname,$valname,$url)
{
	$script='';

	$script.= '<input type="hidden" name="'.$htmlname.'" id="'.$htmlname.'" value="'.$selected.'" />';

	$script.= '<script type="text/javascript">';
	$script.= 'jQuery(document).ready(function() {
    				jQuery("input#search_'.$htmlname.'").autocomplete({
    					source: function( request, response ) {
    						jQuery.get("'.$url.'", { socid: request.term }, function(data){
								response( jQuery.map( data, function( item ) {
									return { label: item.'.$valname.', value: item.'.$valname.', id: item.'.$htmlname.'}
								}));
							}, "json");
						},
						dataType: "json",		
    					minLength: 2,
    					select: function( event, ui ) {
    						jQuery("#'.$htmlname.'").val(ui.item.id);
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

?>