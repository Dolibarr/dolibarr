<?php
/* Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2010 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/includes/menus/smartphone/iphone_frontoffice.php
 *	\brief      Gestionnaire nomme iphone du menu smartphone
 *	\version    $Id$
 */


/**
 *      \class      MenuSmart
 *	    \brief      Class to manage smartphone menu Iphone (for external users)
 */
class MenuSmart {

	var $require_left=array("iphone_frontoffice");    // Si doit etre en phase avec un gestionnaire de menu particulier
	var $hideifnotallowed=1;						// Put 0 for back office menu, 1 for front office menu
	var $atarget="";                                // Valeur du target a utiliser dans les liens


	/**
	 *    \brief      Constructeur
	 *    \param      db      Handler d'acces base de donnee
	 */
	function MenuSmart($db)
	{
		$this->db=$db;
	}


	/**
	 *    \brief      Show menu
	 */
	function showmenu()
	{
		require_once(DOL_DOCUMENT_ROOT.'/includes/menus/smartphone/iphone.lib.php');

		print_iphone_menu($this->db,$this->atarget,$this->hideifnotallowed);
	}

}

?>
