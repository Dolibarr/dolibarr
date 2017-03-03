<?php
/* Copyright (C) 2016 		Alexandre Spangaro <aspangaro.dolibarr@gmail.com>
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
class FormAccounting
{
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

