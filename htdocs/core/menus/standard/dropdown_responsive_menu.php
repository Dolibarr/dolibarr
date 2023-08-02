<?php
/* Copyright (C) 2005-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2009 Regis Houssin        <regis.houssin@inodbox.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
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
	/**
     * @var DoliDB Database handler.
     */
    public $db;

    public $type_user;									// Put 0 for internal users, 1 for external users
    public $atarget="";                                // To store default target to use onto links
    public $name="eldy";

    public $menu_array;
    public $menu_array_after;

    public $tabMenu;


    /**
     *  Constructor
     *
	 *  @param	DoliDB		$db     	Database handler
     *  @param	int			$type_user	Type of user
     */
    public function __construct($db, $type_user)
    {
    	$this->type_user=$type_user;
        $this->db=$db;
    }


   	/**
   	 * Load this->tabMenu
   	 *
   	 * @param	string	$forcemainmenu		To force mainmenu to load
   	 * @param	string	$forceleftmenu		To force leftmenu to load
   	 * @return	void
   	 */
   	public function loadMenu($forcemainmenu = '', $forceleftmenu = '')
   	{
    	global $conf, $user, $langs;

   		// On sauve en session le menu principal choisi
    	if (isset($_GET["mainmenu"])) $_SESSION["mainmenu"] = $_GET["mainmenu"];
    	if (isset($_GET["idmenu"]))   $_SESSION["idmenu"] = $_GET["idmenu"];

    	// Read mainmenu and leftmenu that define which menu to show
    	if (isset($_GET["mainmenu"]))
    	{
    		// On sauve en session le menu principal choisi
    		$mainmenu = $_GET["mainmenu"];
    		$_SESSION["mainmenu"] = $mainmenu;
    		$_SESSION["leftmenuopened"] = "";
    	}
    	else
    	{
    		// On va le chercher en session si non defini par le lien
    		$mainmenu = isset($_SESSION["mainmenu"]) ? $_SESSION["mainmenu"] : '';
    	}
		if (!empty($forcemainmenu)) $mainmenu = $forcemainmenu;

    	if (isset($_GET["leftmenu"]))
    	{
    		// On sauve en session le menu principal choisi
    		$leftmenu = $_GET["leftmenu"];
    		$_SESSION["leftmenu"] = $leftmenu;

    		if ($_SESSION["leftmenuopened"] == $leftmenu)	// To collapse
    		{
    			//$leftmenu="";
    			$_SESSION["leftmenuopened"] = "";
    		}
    		else
    		{
    			$_SESSION["leftmenuopened"] = $leftmenu;
    		}
    	} else {
    		// On va le chercher en session si non defini par le lien
    		$leftmenu = isset($_SESSION["leftmenu"]) ? $_SESSION["leftmenu"] : '';
    	}
    	if (!empty($forceleftmenu)) $leftmenu = $forceleftmenu;

    	require_once DOL_DOCUMENT_ROOT.'/core/class/menubase.class.php';
    	$tabMenu = array();
    	$menuArbo = new Menubase($this->db, 'auguria');
    	$menuArbo->menuLoad($mainmenu, $leftmenu, $this->type_user, 'auguria', $tabMenu);
    	$this->tabMenu = $tabMenu;
    	//var_dump($tabMenu);

    	//if ($forcemainmenu == 'all') { var_dump($this->tabMenu); exit; }
   	}


    /**
     *  Show menu.
     *  Menu defined in sql tables were stored into $this->tabMenu BEFORE this is called.
     *
     *	@param	string	$mode			'top', 'topnb', 'left', 'jmobile' (used to get full xml ul/li menu)
     *  @param	array	$moredata		An array with more data to output
     *  @return int                     0 or nb of top menu entries if $mode = 'topnb'
     */
    public function showmenu($mode, $moredata = null)
    {
    	global $conf, $langs, $user, $db, $form;

        require_once DOL_DOCUMENT_ROOT.'/core/menus/standard/dropdown_responsive_menu.lib.php';

        if ($this->type_user == 1)
        {
        	$conf->global->MAIN_SEARCHFORM_SOCIETE_DISABLED=1;
        	$conf->global->MAIN_SEARCHFORM_CONTACT_DISABLED=1;
        }

		require_once DOL_DOCUMENT_ROOT.'/core/class/menu.class.php';
        $this->menu=new Menu();

		if ($mode == 'top') print_ace_menu($this->db, $this->atarget, $this->type_user, $this->tabMenu, $this->menu, 0, $mode, $moredata);

        unset($this->menu);
		
		if (!is_object($form)){
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
			$form = new Form($db);
		}

        //print 'xx'.$mode;
        return 0;
    }
}
