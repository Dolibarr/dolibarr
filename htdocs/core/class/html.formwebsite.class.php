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

}
