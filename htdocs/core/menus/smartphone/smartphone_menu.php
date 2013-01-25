<?php
/* Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2010 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	\file       htdocs/core/menus/smartphone/smartphone_menu.php
 *	\brief      Menu smartphone manager
 */


/**
 *	Class to manage smartphone menu smartphone
 */
class MenuSmart
{

	var $hideifnotallowed=1;						// Put 0 for back office menu, 1 for front office menu
	var $atarget="";                                // Valeur du target a utiliser dans les liens
	var $name="smartphone";
	

	/**
	 *	Constructor
	 *
	 *  @param	DoliDB		$db      	Database handler
     *  @param	int			$type_user	Type of user
	 */
	function __construct($db, $type_user)
	{
		$this->db=$db;
	}


	/**
	 *  Show menu
	 *
     *	@param	string	$mode			'top' or 'left'
     *  @return int     				Number of menu entries shown
	 */
	function showmenu($mode)
	{
    	global $conf;
		
    	require_once DOL_DOCUMENT_ROOT.'/core/menus/smartphone/smartphone.lib.php';

	    if ($this->type_user == 1)
        {
        	$conf->global->MAIN_SEARCHFORM_SOCIETE=0;
        	$conf->global->MAIN_SEARCHFORM_CONTACT=0;
        }
    	
        print_smartphone_menu($this->db,$this->atarget,$this->hideifnotallowed,$mode);
        
        return 1;
	}

}

?>