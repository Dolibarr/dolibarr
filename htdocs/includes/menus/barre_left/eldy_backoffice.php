<?php
/* Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/includes/menus/barre_left/eldy_backoffice.php
 *	\brief      Gestionnaire du menu du gauche Eldy
 *	\version    $Id$
 *
 *	\remarks    La construction d'un gestionnaire pour le menu de gauche est simple:
 *	\remarks    A l'aide d'un objet $newmenu=new Menu() et de la methode add,
 *	\remarks    definir la liste des entrees menu a faire apparaitre.
 *	\remarks    En fin de code, mettre la ligne $menu=$newmenu->liste.
 *	\remarks    Ce qui est defini dans un tel gestionnaire sera alors prioritaire sur
 *	\remarks    les definitions de menu des fichiers pre.inc.php
 */


/**
 *	\class      MenuLeft
 *	\brief      Classe permettant la gestion du menu du gauche Eldy
 */
class MenuLeft {

	var $require_top=array("eldy_backoffice");     // Si doit etre en phase avec un gestionnaire de menu du haut particulier

    var $db;
    var $menu_array;
    var $menu_array_after;


	/**
     *  \brief      Constructor
     *  \param      db                  Database handler
     *  \param      menu_array          Table of menu entries to show before entries of menu handler
     *  \param      menu_array_after    Table of menu entries to show after entries of menu handler
	 */
	function MenuLeft($db,&$menu_array,&$menu_array_after)
	{
		$this->db=$db;
		$this->menu_array=$menu_array;
		$this->menu_array_after=$menu_array_after;
	}


	/**
	 *    	\brief      Show menu
	 * 		\return		int		Number of menu entries shown
	 */
	function showmenu()
	{
		require_once(DOL_DOCUMENT_ROOT.'/includes/menus/barre_left/eldy.lib.php');

		$res=print_left_eldy_menu($this->db,$this->menu_array,$this->menu_array_after);

		return $res;
	}

}

?>
