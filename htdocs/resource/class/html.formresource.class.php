<?php
/* Copyright (C) - 2013-2015 Jean-François FERRY	<jfefe@aternatik.fr>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
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
 *       \file       resource/class/html.formresource.class.php
 *       \ingroup    core
 *       \brief      Class file to manage forms into resource module
 */
require_once DOL_DOCUMENT_ROOT ."/core/class/html.form.class.php";
require_once DOL_DOCUMENT_ROOT ."/resource/class/dolresource.class.php";


/**
 * Classe permettant la gestion des formulaire du module resource
 *
 * \remarks Utilisation: $formresource = new FormResource($db)
 * \remarks $formplace->proprietes=1 ou chaine ou tableau de valeurs
 */
class FormResource
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    public $substit=array();

    public $param=array();

    /**
	 * @var string Error code (or message)
	 */
	public $error='';


	/**
	* Constructor
	*
	* @param DoliDB $db Database handler
	*/
    public function __construct($db)
    {
        $this->db = $db;
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Output html form to select a resource
     *
     *	@param	int   	$selected       Preselected resource id
     *	@param  string	$htmlname       Name of field in form
     *  @param  string	$filter         Optionnal filters criteras (example: 's.rowid <> x')
     *	@param	int		$showempty		Add an empty field
     * 	@param	int		$showtype		Show third party type in combolist (customer, prospect or supplier)
     * 	@param	int		$forcecombo		Force to use combo box
     *  @param	array	$event			Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     *  @param	string	$filterkey		Filter on key value
     *  @param	int		$outputmode		0=HTML select string, 1=Array, 2=without form tag
     *  @param	int		$limit			Limit number of answers
     * 	@return	string					HTML string with
     */
    public function select_resource_list($selected = '', $htmlname = 'fk_resource', $filter = '', $showempty = 0, $showtype = 0, $forcecombo = 0, $event = array(), $filterkey = '', $outputmode = 0, $limit = 20)
    {
        // phpcs:enable
    	global $conf,$user,$langs;

    	$out='';
    	$outarray=array();

    	$resourcestat = new Dolresource($this->db);

    	$resources_used = $resourcestat->fetch_all('ASC', 't.rowid', $limit, 0, $filter);

    	if ($outputmode != 2)
    	{
    	    $out = '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
    	    $out.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    	}

    	if ($resourcestat)
    	{
    		if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->RESOURCE_USE_SEARCH_TO_SELECT) && ! $forcecombo)
    		{
    			//$minLength = (is_numeric($conf->global->RESOURCE_USE_SEARCH_TO_SELECT)?$conf->global->RESOURCE_USE_SEARCH_TO_SELECT:2);
    			$out.= ajax_combobox($htmlname, $event, $conf->global->RESOURCE_USE_SEARCH_TO_SELECT);
    		}

    		// Construct $out and $outarray
    		$out.= '<select id="'.$htmlname.'" class="flat minwidth200" name="'.$htmlname.'">'."\n";
    		if ($showempty) $out.= '<option value="-1">&nbsp;</option>'."\n";

    		$num = 0;
    		if (is_array($resourcestat->lines)) $num = count($resourcestat->lines);

    		//var_dump($resourcestat->lines);
    		$i = 0;
    		if ($num)
    		{
    			while ($i < $num)
    			{
    			    $resourceclass=ucfirst($resourcestat->lines[$i]->element);

    				$label=$resourcestat->lines[$i]->ref?$resourcestat->lines[$i]->ref:''.$resourcestat->lines[$i]->label;
    				if ($resourceclass != 'Dolresource') $label.=' ('.$langs->trans($resourceclass).')';

    				if ($selected > 0 && $selected == $resourcestat->lines[$i]->id)
    				{
    					$out.= '<option value="'.$resourcestat->lines[$i]->id.'" selected>'.$label.'</option>';
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
    		$out.= ajax_combobox($htmlname);

    		if ($outputmode != 2)
    		{

        		$out.= '<input type="submit" class="button" value="'.$langs->trans("Search").'"> &nbsp; &nbsp; ';

        		$out.= '</form>';
    		}
    	}
    	else
    	{
    		dol_print_error($this->db);
    	}

    	if ($outputmode && $outputmode != 2) return $outarray;
    	return $out;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Return html list of tickets type
     *
     *  @param	string	$selected       Id du type pre-selectionne
     *  @param  string	$htmlname       Nom de la zone select
     *  @param  string	$filtertype     To filter on field type in llx_c_ticket_type (array('code'=>xx,'label'=>zz))
     *  @param  int		$format         0=id+libelle, 1=code+code, 2=code+libelle, 3=id+code
     *  @param  int		$empty			1=peut etre vide, 0 sinon
     *  @param	int		$noadmininfo	0=Add admin info, 1=Disable admin info
     *  @param  int		$maxlength      Max length of label
     * 	@return	void
     */
    public function select_types_resource($selected = '', $htmlname = 'type_resource', $filtertype = '', $format = 0, $empty = 0, $noadmininfo = 0, $maxlength = 0)
    {
        // phpcs:enable
    	global $langs,$user;

    	$resourcestat = new Dolresource($this->db);

    	dol_syslog(get_class($this)."::select_types_resource ".$selected.", ".$htmlname.", ".$filtertype.", ".$format, LOG_DEBUG);

    	$filterarray=array();

    	if ($filtertype != '' && $filtertype != '-1') $filterarray=explode(',', $filtertype);

    	$resourcestat->load_cache_code_type_resource();
    	print '<select id="select'.$htmlname.'" class="flat maxwidthonsmartphone select_'.$htmlname.'" name="'.$htmlname.'">';
    	if ($empty) print '<option value="">&nbsp;</option>';
    	if (is_array($resourcestat->cache_code_type_resource) && count($resourcestat->cache_code_type_resource))
    	{
    		foreach($resourcestat->cache_code_type_resource as $id => $arraytypes)
    		{

    			// We discard empty line if showempty is on because an empty line has already been output.
    			if ($empty && empty($arraytypes['code'])) continue;

    			if ($format == 0) print '<option value="'.$id.'"';
    			elseif ($format == 1) print '<option value="'.$arraytypes['code'].'"';
    			elseif ($format == 2) print '<option value="'.$arraytypes['code'].'"';
    			elseif ($format == 3) print '<option value="'.$id.'"';
    			// Si selected est text, on compare avec code, sinon avec id
    			if (preg_match('/[a-z]/i', $selected) && $selected == $arraytypes['code']) print ' selected';
    			elseif ($selected == $id) print ' selected';
    			print '>';
    			if ($format == 0) $value=($maxlength?dol_trunc($arraytypes['label'], $maxlength):$arraytypes['label']);
    			elseif ($format == 1) $value=$arraytypes['code'];
    			elseif ($format == 2) $value=($maxlength?dol_trunc($arraytypes['label'], $maxlength):$arraytypes['label']);
    			elseif ($format == 3) $value=$arraytypes['code'];
    			print $value?$value:'&nbsp;';
    			print '</option>';
    		}
    	}
    	print '</select>';
    	if ($user->admin && ! $noadmininfo) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
    }
}
