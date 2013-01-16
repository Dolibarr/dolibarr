<?php
/* Copyright (C) 2012 Laurent Destailleur   <eldy@users.sourceforge.net>
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
 *	\file       htdocs/core/class/html.formsocialcontrib.class.php
 *  \ingroup    core
 *	\brief      File of class with all html predefined components
 */


/**
 *	Class to manage generation of HTML components for social contributions management
 */
class FormSocialContrib
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
     *	Return list of social contributions.
     * 	Use mysoc->country_id or mysoc->country_code so they must be defined.
     *
     *	@param	string	$selected       Preselected type
     *	@param  string	$htmlname       Name of field in form
     * 	@param	int		$useempty		Set to 1 if we want an empty value
     * 	@param	int		$maxlen			Max length of text in combo box
     * 	@param	int		$help			Add or not the admin help picto
     * 	@return	void
     */
    function select_type_socialcontrib($selected='',$htmlname='actioncode', $useempty=0, $maxlen=40, $help=1)
    {
        global $db,$langs,$user,$mysoc;

        if (empty($mysoc->country_id) && empty($mysoc->country_code))
        {
            dol_print_error('','Call to select_type_socialcontrib with mysoc country not yet defined');
            exit;
        }

        if (! empty($mysoc->country_id))
        {
            $sql = "SELECT c.id, c.libelle as type";
            $sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
            $sql.= " WHERE c.active = 1";
            $sql.= " AND c.fk_pays = ".$mysoc->country_id;
            $sql.= " ORDER BY c.libelle ASC";
        }
        else
        {
            $sql = "SELECT c.id, c.libelle as type";
            $sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c, ".MAIN_DB_PREFIX."c_pays as p";
            $sql.= " WHERE c.active = 1 AND c.fk_pays = p.rowid";
            $sql.= " AND p.code = '".$mysoc->country_code."'";
            $sql.= " ORDER BY c.libelle ASC";
        }

        dol_syslog("Form::select_type_socialcontrib sql=".$sql, LOG_DEBUG);
        $resql=$db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            if ($num)
            {
                print '<select class="flat" name="'.$htmlname.'">';
                $i = 0;

                if ($useempty) print '<option value="0">&nbsp;</option>';
                while ($i < $num)
                {
                    $obj = $db->fetch_object($resql);
                    print '<option value="'.$obj->id.'"';
                    if ($obj->id == $selected) print ' selected="selected"';
                    print '>'.dol_trunc($obj->type,$maxlen);
                    $i++;
                }
                print '</select>';
                if ($user->admin && $help) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
            }
            else
            {
                print $langs->trans("ErrorNoSocialContributionForSellerCountry",$mysoc->country_code);
            }
        }
        else
        {
            dol_print_error($db,$db->lasterror());
        }
    }

}

?>
