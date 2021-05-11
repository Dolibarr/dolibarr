<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/core/class/html.formwebsite.class.php
 *  \ingroup    core
 *	\brief      File of class to manage component html for module website
 */


/**
 *	Class to manage component html for module website
 */
class FormWebsite
{
    private $db;
    public $error;


    /**
     *	Constructor
     *
     *	@param	DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;

        return 1;
    }


    /**
     *    Return HTML select list of export models
     *
     *    @param    string	$selected          Id modele pre-selectionne
     *    @param    string	$htmlname          Name of HTML select
     *    @param    int		$useempty          Show empty value or not
     *    @return	string					   Html component
     */
    function selectWebsite($selected='',$htmlname='exportmodelid',$useempty=0)
    {
    	$out='';

        $sql = "SELECT rowid, ref";
        $sql.= " FROM ".MAIN_DB_PREFIX."website";
        $sql.= " WHERE 1 = 1";
        $sql.= " ORDER BY rowid";
        $result = $this->db->query($sql);
        if ($result)
        {
            $out.='<select class="flat minwidth100" name="'.$htmlname.'" id="'.$htmlname.'">';
            if ($useempty)
            {
                $out.='<option value="-1">&nbsp;</option>';
            }

            $num = $this->db->num_rows($result);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($result);
                if ($selected == $obj->rowid)
                {
                    $out.='<option value="'.$obj->rowid.'" selected>';
                }
                else
                {
                    $out.='<option value="'.$obj->rowid.'">';
                }
                $out.=$obj->ref;
                $out.='</option>';
                $i++;
            }
            $out.="</select>";
        }
        else {
            dol_print_error($this->db);
        }

        return $out;
    }


    /**
     *  Return a HTML select list of a dictionary
     *
     *  @param  string	$htmlname          	Name of select zone
     *  @param	string	$selected			Selected value
     *  @param  int		$useempty          	1=Add an empty value in list, 2=Add an empty value in list only if there is more than 2 entries.
     *  @param  string  $moreattrib         More attributes on HTML select tag
     * 	@return	void
     */
    function selectTypeOfContainer($htmlname, $selected='', $useempty=0, $moreattrib='')
    {
    	global $langs, $conf, $user;

    	$langs->load("admin");

    	$sql = "SELECT rowid, code, label, entity";
    	$sql.= " FROM ".MAIN_DB_PREFIX.'c_type_container';
    	$sql.= " WHERE active = 1 AND entity IN (".getEntity('c_type_container').")";
    	$sql.= " ORDER BY label";

    	dol_syslog(get_class($this)."::selectTypeOfContainer", LOG_DEBUG);
    	$result = $this->db->query($sql);
    	if ($result)
    	{
    		$num = $this->db->num_rows($result);
    		$i = 0;
    		if ($num)
    		{
    			print '<select id="select'.$htmlname.'" class="flat selectTypeOfContainer" name="'.$htmlname.'"'.($moreattrib?' '.$moreattrib:'').'>';
    			if ($useempty == 1 || ($useempty == 2 && $num > 1))
    			{
    				print '<option value="-1">&nbsp;</option>';
    			}

    			while ($i < $num)
    			{
    				$obj = $this->db->fetch_object($result);
    				if ($selected == $obj->rowid || $selected == $obj->code)
    				{
    					print '<option value="'.$obj->code.'" selected>';
    				}
    				else
    				{
    					print '<option value="'.$obj->code.'">';
    				}
    				print $obj->label;
    				print '</option>';
    				$i++;
    			}
    			print "</select>";
    			if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
    		}
    		else
    		{
    			print $langs->trans("NoTypeOfPagePleaseEditDictionary");
    		}
    	}
    	else {
    		dol_print_error($this->db);
    	}
    }


    /**
     *  Return a HTML select list of a dictionary
     *
     *  @param  string	$htmlname          	Name of select zone
     *  @param	string	$selected			Selected value
     *  @param  int		$useempty          	1=Add an empty value in list
     *  @param  string  $moreattrib         More attributes on HTML select tag
     * 	@return	void
     */
    function selectSampleOfContainer($htmlname, $selected='', $useempty=0, $moreattrib='')
    {
    	global $langs, $conf, $user;

    	$langs->load("admin");

    	$arrayofsamples=array('empty'=>'EmptyPage', 'corporatehome'=>'CorporateHomePage');

    	$out = '';
    	$out .= '<select id="select'.$htmlname.'" class="flat selectTypeOfContainer" name="'.$htmlname.'"'.($moreattrib?' '.$moreattrib:'').'>';

    	if ($useempty == 1 || $useempty == 2)
    	{
    		$out .= '<option value="-1">&nbsp;</option>';
    	}

    	foreach($arrayofsamples as $key => $val)
    	{
    		if ($selected == $key)
    		{
    			$out .= '<option value="'.$key.'" selected>';
    		}
    		else
    		{
    			$out .= '<option value="'.$key.'">';
    		}
    		$out .= $langs->trans($val);
    		$out .= '</option>';
    	}
    	$out .= "</select>";

    	return $out;
    }

}
