<?php
/* Copyright (C) 2012		Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2015		Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
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
 *	\file       htdocs/core/class/html.formbank.class.php
 *  \ingroup    core
 *	\brief      File of class with all html predefined components
 */


/**
 *	Class to manage generation of HTML components for bank module
 */
class FormBank
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
     *  Retourne la liste des types de comptes financiers
     *
     *  @param	integer	$selected        Type pre-selectionne
     *  @param  string	$htmlname        Nom champ formulaire
     *  @return	void
     */
    function select_type_comptes_financiers($selected=1,$htmlname='type')
    {
        global $langs;
        $langs->load("banks");

        $type_available=array(0,1,2);

        print '<select id="select'.$htmlname.'" class="flat" name="'.$htmlname.'">';
        $num = count($type_available);
        $i = 0;
        if ($num)
        {
            while ($i < $num)
            {
                if ($selected == $type_available[$i])
                {
                    print '<option value="'.$type_available[$i].'" selected>'.$langs->trans("BankType".$type_available[$i]).'</option>';
                }
                else
                {
                    print '<option value="'.$type_available[$i].'">'.$langs->trans("BankType".$type_available[$i]).'</option>';
                }
                $i++;
            }
        }
        print '</select>';
    }

}

