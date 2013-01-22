<?php
/* Copyright (C) 2005-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	\file       htdocs/core/menus/standard/eldy_frontoffice.php
 *	\brief      Gestionnaire nomme eldy du menu du haut
 *
 *	\remarks    La construction d'un gestionnaire pour le menu du haut est simple:
 *	\remarks    Toutes les entrees de menu a faire apparaitre dans la barre du haut
 *	\remarks    doivent etre affichees par <a class="tmenu" href="...?mainmenu=...">...</a>
 *	\remarks    ou si menu selectionne <a class="tmenusel" href="...?mainmenu=...">...</a>
 */


/**
 *	Class to manage menu Eldy (for external users)
 */
class MenuManager
{
	var $db;
	var $require_left=array("eldy_frontoffice");    // Si doit etre en phase avec un gestionnaire de menu gauche particulier
	var $type_user=1;								// Put 0 for internal users, 1 for external users
	var $atarget="";                                // Valeur du target a utiliser dans les liens

    var $menu_array;
    var $menu_array_after;


    /**
     *  Constructor
     *
	 *  @param	DoliDB		$db     			Database handler
     */
    function __construct($db)
    {
        $this->db=$db;
    }


    /**
     *  Show menu
     *
     *	@param	string	$mode		'top' or 'left'
     *  @return int     			Number of menu entries shown
     */
    function showmenu($mode)
    {
    	global $conf;

        require_once DOL_DOCUMENT_ROOT.'/core/menus/standard/eldy.lib.php';

        $conf->global->MAIN_SEARCHFORM_SOCIETE=0;
        $conf->global->MAIN_SEARCHFORM_CONTACT=0;

        $res='ErrorBadParameterForMode';
        if ($mode == 'top')  $res=print_eldy_menu($this->db,$this->atarget,$this->type_user);
        if ($mode == 'left') $res=print_left_eldy_menu($this->db,$this->menu_array,$this->menu_array_after);
        
        return $res;
    }

}

?>
