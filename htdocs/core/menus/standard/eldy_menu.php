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
 *	\file       htdocs/core/menus/standard/eldy_menu.php
 *	\brief      Menu eldy manager
 */


/**
 *	Class to manage menu Eldy
 */
class MenuManager
{
	var $db;
	var $type_user;									// Put 0 for internal users, 1 for external users
	var $atarget="";                                // To store default target to use onto links
	var $name="eldy";

    var $menu_array;
    var $menu_array_after;

    var $tabMenu;


    /**
     *  Constructor
     *
	 *  @param	DoliDB		$db     	Database handler
     *  @param	int			$type_user	Type of user
     */
    function __construct($db, $type_user)
    {
    	$this->type_user=$type_user;
        $this->db=$db;
    }
    
    
    /**
     * Load this->tabMenu
     * 
     * @return	void
     */
    function loadMenu()
    {
		// On sauve en session le menu principal choisi
		if (isset($_GET["mainmenu"])) $_SESSION["mainmenu"]=$_GET["mainmenu"];
		if (isset($_GET["idmenu"]))   $_SESSION["idmenu"]=$_GET["idmenu"];

		// Read mainmenu and leftmenu that define which menu to show
        if (isset($_GET["mainmenu"]))
        {
        	// On sauve en session le menu principal choisi
        	$mainmenu=$_GET["mainmenu"];
        	$_SESSION["mainmenu"]=$mainmenu;
        	$_SESSION["leftmenuopened"]="";
        }
        else
       {
        	// On va le chercher en session si non defini par le lien
        	$mainmenu=isset($_SESSION["mainmenu"])?$_SESSION["mainmenu"]:'';
        }

        if (isset($_GET["leftmenu"]))
        {
        	// On sauve en session le menu principal choisi
        	$leftmenu=$_GET["leftmenu"];
        	$_SESSION["leftmenu"]=$leftmenu;

        	if ($_SESSION["leftmenuopened"]==$leftmenu)	// To collapse
        	{
        		//$leftmenu="";
        		$_SESSION["leftmenuopened"]="";
        	}
        	else
        	{
        		$_SESSION["leftmenuopened"]=$leftmenu;
        	}
        } else {
        	// On va le chercher en session si non defini par le lien
        	$leftmenu=isset($_SESSION["leftmenu"])?$_SESSION["leftmenu"]:'';
        }

        require_once DOL_DOCUMENT_ROOT.'/core/class/menubase.class.php';
        $tabMenu=array();
        $menuArbo = new Menubase($this->db,'eldy');
        $menuArbo->menuLoad($mainmenu, $leftmenu, $this->type_user, 'eldy', $tabMenu);
        $this->tabMenu=$tabMenu;
    }


    /**
     *  Show menu
     *
     *	@param	string	$mode			'top', 'left', 'jmobile'
     *  @return int     				Number of menu entries shown
     */
    function showmenu($mode)
    {
    	global $conf;

        require_once DOL_DOCUMENT_ROOT.'/core/menus/standard/eldy.lib.php';

        if ($this->type_user == 1)
        {
        	$conf->global->MAIN_SEARCHFORM_SOCIETE=0;
        	$conf->global->MAIN_SEARCHFORM_CONTACT=0;
        }

        $res='ErrorBadParameterForMode';
        
		require_once DOL_DOCUMENT_ROOT.'/core/class/menu.class.php';
        $this->menu=new Menu();
        
        if ($mode == 'top')  $res=print_eldy_menu($this->db,$this->atarget,$this->type_user,$this->tabMenu,$this->menu);
        if ($mode == 'left') $res=print_left_eldy_menu($this->db,$this->menu_array,$this->menu_array_after,$this->tabMenu,$this->menu);
        if ($mode == 'jmobile') $res=print_jmobile_eldy_menu($this->db,$this->menu_array,$this->menu_array_after,$this->tabMenu,$this->menu);

        unset($this->menu);
        
        //print 'xx'.$mode;
        //var_dump($this->menu);
        return $res;
    }

}

?>
