<?php
/* Copyright (C) 2007-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Regis Houssin        <regis@dolibarr.fr>
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
 *	\param     	indicator           nom de l'image gif sans l'extension
 *	\return    	string              script complet
 */
function ajax_autocompleter($selected='',$htmlname,$url,$indicator='working')
{
	$script='';

	$script.= '<input type="hidden" name="'.$htmlname.'_id" id="'.$htmlname.'_id" value="'.$selected.'" />';

	$script.= '<div id="result'.$htmlname.'" class="autocomplete"></div>';
	$script.= '<script type="text/javascript">';
	$script.= 'new Ajax.Autocompleter(\''.$htmlname.'\',\'result'.$htmlname.'\',\''.$url.'\',{
	           method: \'post\',
	           paramName: \'socid\',
	           minChars: \'1\',
	           indicator: \'indicator'.$htmlname.'\',
	           afterUpdateElement: ac_return
	         });';	// paramName must be 'socid', as it is the name of POST parameter to send value in htmlname field.
					// and it is name of parameter read by ajaxcompanies.php
		// Note: The ac_return will fill value inside field htmlname (param of Autocompleter constructor)
		// and will also fill value inside field htmlname_id (using function ac_return)
	$script.= '</script>';

	return $script;
}

/**
 *	\brief     	Get value of field, do Ajax process and return result. Use jQuery.
 *  \param      selected            Preselected value
 *	\param	    htmlname            Name of html field
 *	\param	    url                 Path of source file to get values: /path/ajaxpage.php
 *  \param      option              Not used
 *	\return    	string              script complet
 */
function ajax_autocompleter2($selected='', $htmlname, $url, $option='')
{
	$script = '<input type="hidden" name="'.$htmlname.'" id="'.$htmlname.'" value="'.$selected.'" />';

	$script.= '<script type="text/javascript">';
	$script.= 'jQuery(function() {
					jQuery("input#'.$htmlname.'free").autocomplete({
                        source: "'.$url.($option?'?'.$option:'').'",
					    delay: 300,
						minLength: 1,
                        select: function(event, ui) {
                            /* jQuery("#'.$htmlname.'free") is automatically affected with ui.item.key */
                            /* jQuery("#'.$htmlname.'free").val(ui.item.fulltext); */
                            jQuery("#'.$htmlname.'").val(ui.item.key);
                            /* alert(ui.item.key); */
                        }
					});
				});';
	$script.= '</script>';

	return $script;
}

?>