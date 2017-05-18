<?php
/* Copyright (C) 2016-2017	Alexandre Spangaro	<aspangaro@zendsi.com>
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
 */

/**
 *	\file       htdocs/core/class/html.formaccounting.class.php
 *  \ingroup    Advanced accountancy
 *	\brief      File of class with all html predefined components
 */


/**
 *	Class to manage generation of HTML components for accounting management
 */
class FormAccounting extends Form
{

    private $options_cache = array();

	var $db;
	var $error;

	/**
	* Constructor
	*
	* @param		DoliDB		$db      Database handler
	*/
	public function __construct($db)
	{
	    $this->db = $db;
	}

	/**
	 * Return list of journals with label by nature
	 *
	 * @param	string	$selectid	Preselected pcg_type
	 * @param	string	$htmlname	Name of field in html form
	 * @param	int		$nature		Limit the list to a particular type of journals (1:various operations / 2:sale / 3:purchase / 4:bank / 9: has-new)
	 * @param	int		$showempty	Add an empty field
	 * @param	array	$event		Event options
	 * @param	int		$select_in	0=selectid value is the journal rowid (default) or 1=selectid is journal code
	 * @param	int		$select_out	Set value returned by select. 0=rowid (default), 1=code
	 * @param	string	$morecss	More css non HTML object
	 * @param	string	$usecache	Key to use to store result into a cache. Next call with same key will reuse the cache.
	 *
	 * @return	string				String with HTML select
	 */
	function select_journal($selectid, $htmlname = 'journal', $nature=0, $showempty = 0, $event = array(), $select_in = 0, $select_out = 0, $morecss='maxwidth300 maxwidthonsmartphone', $usecache='')
	{
		global $conf;

		$out = '';

    	$options = array();
		if ($usecache && ! empty($this->options_cache[$usecache]))
		{
		    $options = $this->options_cache[$usecache];
		    $selected=$selectid;
		}
		else
		{
			$sql = "SELECT rowid, code, label, nature, entity, active";
			$sql.= " FROM " . MAIN_DB_PREFIX . "accounting_journal";
			$sql.= " WHERE active = 1";
			$sql.= " AND entity = ".$conf->entity;
			//if ($nature && is_numeric($nature))   $sql .= " AND nature = ".$nature;
			$sql.= " ORDER BY code";

			dol_syslog(get_class($this) . "::select_journal", LOG_DEBUG);
			$resql = $this->db->query($sql);

			if (!$resql) {
				$this->error = "Error ".$this->db->lasterror();
				dol_syslog(get_class($this)."::select_journal ".$this->error, LOG_ERR);
				return -1;
			}

			$out = ajax_combobox($htmlname, $event);

    		$selected = 0;
			while ($obj = $this->db->fetch_object($resql))
			{
				$label = $obj->code . ' - ' . $obj->label;

    			$select_value_in = $obj->rowid;
				$select_value_out = $obj->rowid;

				// Try to guess if we have found default value
    			if ($select_in == 1) {
    				$select_value_in = $obj->code;
    			}
    			if ($select_out == 1) {
    				$select_value_out = $obj->code;
    			}
    			// Remember guy's we store in database llx_accounting_bookkeeping the code of accounting_journal and not the rowid
    			if ($selectid != '' && $selectid == $select_value_in) {
    			    //var_dump("Found ".$selectid." ".$select_value_in);
    				$selected = $select_value_out;
    			}

				$options[$select_value_out] = $label;
			}
			$this->db->free($resql);

			if ($usecache)
			{
				$this->options_cache[$usecache] = $options;
			}
		}

		$out .= Form::selectarray($htmlname, $options, $selected, $showempty, 0, 0, '', 0, 0, 0, '', $morecss, 1);

		return $out;
	}

    /**
     *	Return list of accounting category.
     * 	Use mysoc->country_id or mysoc->country_code so they must be defined.
     *
     *	@param	string	$selected       Preselected type
     *	@param  string	$htmlname       Name of field in form
     * 	@param	int		$useempty		Set to 1 if we want an empty value
     * 	@param	int		$maxlen			Max length of text in combo box
     * 	@param	int		$help			Add or not the admin help picto
     *  @param  int     $allcountries   All countries
     * 	@return	void
     */
    function select_accounting_category($selected='',$htmlname='account_category', $useempty=0, $maxlen=0, $help=1, $allcountries=0)
    {
        global $db,$langs,$user,$mysoc;

        if (empty($mysoc->country_id) && empty($mysoc->country_code) && empty($allcountries))
        {
            dol_print_error('','Call to select_accounting_account with mysoc country not yet defined');
            exit;
        }

        if (! empty($mysoc->country_id))
        {
            $sql = "SELECT c.rowid, c.label as type, c.range_account";
            $sql.= " FROM ".MAIN_DB_PREFIX."c_accounting_category as c";
            $sql.= " WHERE c.active = 1";
			$sql.= " AND c.category_type = 0";
            if (empty($allcountries)) $sql.= " AND c.fk_country = ".$mysoc->country_id;
            $sql.= " ORDER BY c.label ASC";
        }
        else
        {
            $sql = "SELECT c.rowid, c.label as type, c.range_account";
            $sql.= " FROM ".MAIN_DB_PREFIX."c_accounting_category as c, ".MAIN_DB_PREFIX."c_country as co";
            $sql.= " WHERE c.active = 1";
			$sql.= " AND c.category_type = 0";
			$sql.= " AND c.fk_country = co.rowid";
            if (empty($allcountries)) $sql.= " AND co.code = '".$mysoc->country_code."'";
            $sql.= " ORDER BY c.label ASC";
        }

        dol_syslog(get_class($this).'::'.__METHOD__, LOG_DEBUG);
        $resql=$db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            if ($num)
            {
                $out = '<select class="flat minwidth200" id="'.$htmlname.'" name="'.$htmlname.'">';
                $i = 0;

                if ($useempty) $out.= '<option value="0">&nbsp;</option>';
                while ($i < $num)
                {
                    $obj = $db->fetch_object($resql);
                    $out .= '<option value="'.$obj->rowid.'"';
                    if ($obj->rowid == $selected) $out .= ' selected';
                    $out .= '>'.($maxlen ? dol_trunc($obj->type,$maxlen) : $obj->type);
					$out .= ' ('.$obj->range_account.')';
                    $i++;
                }
                $out .=  '</select>';
                //if ($user->admin && $help) $out .= info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
            }
            else
            {
                $out .= $langs->trans("ErrorNoAccountingCategoryForThisCountry",$mysoc->country_code);
            }
        }
        else
        {
            dol_print_error($db,$db->lasterror());
        }
        
        $out .= ajax_combobox($htmlname, $event);
        
        print $out;
    }
}

