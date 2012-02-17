<?php
/* Copyright (C) 2007      Patrick Raguin       <patrick.raguin@gmail.com>
 * Copyright (C) 2009      Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/core/menus/standard/auguria_frontoffice.php
 *	\brief      Gestionnaire nomme Auguria du menu du haut
 *
 *	\remarks    La construction d'un gestionnaire pour le menu du haut est simple:
 *	\remarks    Toutes les entrees de menu a faire apparaitre dans la barre du haut
 *	\remarks    doivent etre affichees par <a class="tmenu" href="...?mainmenu=...">...</a>
 *	\remarks    ou si menu selectionne <a class="tmenusel" href="...?mainmenu=...">...</a>
 */


/**
 *	\class      MenuTop
 *	\brief      Classe permettant la gestion du menu du haut Auguria
 */

class MenuTop {

	var $db;
	var $require_left=array("auguria_backoffice");	// Si doit etre en phase avec un gestionnaire de menu gauche particulier
	var $hideifnotallowed=1;						// Put 0 for back office menu, 1 for front office menu
	var $atarget="";                                // Valeur du target a utiliser dans les liens


	/**
     *  Constructor
     *
     *  @param      DoliDb		$db      Database handler
	 */
	function MenuTop($db)
	{
		$this->db=$db;
	}



	/**
	 *  Show menu
	 * 
	 *  @return void
	 */
	function showmenu()
	{
		require_once(DOL_DOCUMENT_ROOT.'/core/menus/standard/auguria.lib.php');

		print_auguria_menu($this->db,$this->atarget,$this->hideifnotallowed);
	}

}


/**
 * 	Classe permettant la gestion du menu du gauche Auguria
 */
class MenuLeft {

    var $db;
    var $menu_array;
    var $menu_array_after;


    /**
     *  Constructor
     *
     *  @param      db                  Database handler
     *  @param      menu_array          Table of menu entries to show before entries of menu handler
     *  @param      menu_array_after    Table of menu entries to show after entries of menu handler
     */
    function MenuLeft($db,&$menu_array,&$menu_array_after)
    {
        $this->db=$db;
        $this->menu_array=$menu_array;
        $this->menu_array_after=$menu_array_after;
    }


    /**
     *  Show menu
     *
     *  @return     int     Number of menu entries shown
     */
    function showmenu()
    {
        require_once(DOL_DOCUMENT_ROOT.'/core/menus/standard/auguria.lib.php');

        $res=print_left_auguria_menu($this->db,$this->menu_array,$this->menu_array_after);

        $conf->global->MAIN_SEARCHFORM_SOCIETE=0;
        $conf->global->MAIN_SEARCHFORM_CONTACT=0;

        return $res;
    }
}

?>
