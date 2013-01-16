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
 *		\file       htdocs/core/menus/smartphone/smartphone_backoffice.php
 *		\brief      Gestionnaire nomme smartphone du menu smartphone
 */


/**
 *	Class to manage smartphone menu smartphone (for internal users)
 */
class MenuSmart
{
	var $db;
	var $require_left=array("smartphone_backoffice");     // Si doit etre en phase avec un gestionnaire de menu particulier
	var $hideifnotallowed=0;						// Put 0 for back office menu, 1 for front office menu
	var $atarget="";                                // Valeur du target a utiliser dans les liens


	/**
     *  Constructor
     *
     *  @param      DoliDb		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db=$db;
	}


	/**
	 *    Show menu
	 *
	 *    @param	string	$limitmenuto		To limit menu to a top or left menu value
	 * 	  @return	void
	 */
	function showmenu($limitmenuto)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/menus/smartphone/smartphone.lib.php';

		print_smartphone_menu($this->db,$this->atarget,$this->hideifnotallowed,$limitmenuto);
	}

}

?>