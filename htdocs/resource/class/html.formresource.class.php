<?php
/* Copyright (C) - 2013	Jean-François FERRY	<jfefe@aternatik.fr>
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

/**
 *       \file       place/class/html.place.class.php
 *       \ingroup    core
 *       \brief      Fichier de la classe permettant la generation du formulaire html d'envoi de mail unitaire
 */
require_once(DOL_DOCUMENT_ROOT ."/core/class/html.form.class.php");


/**
 *
 * Classe permettant la gestion des formulaire du module place
 *
 * @package resource

* \remarks Utilisation: $formresource = new FormResource($db)
* \remarks $formplace->proprietes=1 ou chaine ou tableau de valeurs
*/
class FormResource
{
    var $db;

    var $substit=array();
    var $param=array();

    var $error;


	/**
	* Constructor
	*
	* @param DoliDB $DB Database handler
	*/
    function __construct($db)
    {
        $this->db = $db;

        return 1;
    }


    /**
     *  Output html form to select a location (place)
     *
     *	@param	string	$selected       Preselected type
     *	@param  string	$htmlname       Name of field in form
     *  @param  string	$filter         Optionnal filters criteras (example: 's.rowid <> x')
     *	@param	int		$showempty		Add an empty field
     * 	@param	int		$showtype		Show third party type in combolist (customer, prospect or supplier)
     * 	@param	int		$forcecombo		Force to use combo box
     *  @param	array	$event			Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     *  @param	string	$filterkey		Filter on key value
     *  @param	int		$outputmode		0=HTML select string, 1=Array
     *  @param	int		$limit			Limit number of answers
     * 	@return	string					HTML string with
     */
    function select_resource_list($selected='',$htmlname='fk_resource',$filter='',$showempty=0, $showtype=0, $forcecombo=0, $event=array(), $filterkey='', $outputmode=0, $limit=20)
    {
    	global $conf,$user,$langs;

    	$out='';
    	$outarray=array();

    	$resourcestat = new Resource($this->db);

    	$resources_used = $resourcestat->fetch_all_used('ASC', 't.rowid', $limit, $offset, $filter='');

    	$out = '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
    	$out.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    	//$out.= '<input type="hidden" name="action" value="search">';
    	//$out.= '<input type="hidden" name="id" value="'.$theme->id.'">';

    	if ($resourcestat)
    	{
    		if ($conf->use_javascript_ajax && $conf->global->COMPANY_USE_SEARCH_TO_SELECT && ! $forcecombo)
    		{
    			//$minLength = (is_numeric($conf->global->COMPANY_USE_SEARCH_TO_SELECT)?$conf->global->COMPANY_USE_SEARCH_TO_SELECT:2);
    			$out.= ajax_combobox($htmlname, $event, $conf->global->COMPANY_USE_SEARCH_TO_SELECT);
    		}

    		// Construct $out and $outarray
    		$out.= '<select id="'.$htmlname.'" class="flat" name="'.$htmlname.'">'."\n";
    		if ($showempty) $out.= '<option value="-1"></option>'."\n";
    		$num = count($resourcestat->lines);

    		//var_dump($resourcestat->lines);
    		$i = 0;
    		if ($num)
    		{
    			while ( $i < $num)
    			{
    				$label=$langs->trans(ucfirst($resourcestat->lines[$i]->element)).' : ';
    				$label.=$resourcestat->lines[$i]->ref?$resourcestat->lines[$i]->ref:''.$resourcestat->lines[$i]->label;

    				if ($selected > 0 && $selected == $resourcestat->lines[$i]->id)
    				{
    					$out.= '<option value="'.$resourcestat->lines[$i]->id.'" selected="selected">'.$label.'</option>';
    				}
    				else
    				{
    					$out.= '<option value="'.$resourcestat->lines[$i]->id.'">'.$label.'</option>';
    				}

    				array_push($outarray, array('key'=>$resourcestat->lines[$i]->id, 'value'=>$resourcestat->lines[$i]->label, 'label'=>$resourcestat->lines[$i]->label));

    				$i++;
    				if (($i % 10) == 0) $out.="\n";
    			}
    		}
    		$out.= '</select>'."\n";


    		$out.= '<input type="submit" class="button" value="'.$langs->trans("Search").'"> &nbsp; &nbsp; ';

    		$out.= '</form>';
    	}
    	else
    	{
    		dol_print_error($this->db);
    	}

    	if ($outputmode) return $outarray;
    	return $out;
    }


}

?>
