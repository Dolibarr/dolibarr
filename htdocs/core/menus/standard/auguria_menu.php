<?php
/* Copyright (C) 2007      Patrick Raguin       <patrick.raguin@gmail.com>
 * Copyright (C) 2009      Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2008-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/core/menus/standard/auguria_menu.php
 *	\brief      Menu auguria manager
 */


/**
 *	Class to manage menu Auguria
 */
class MenuManager
{
	var $db;
	var $type_user;								// Put 0 for internal users, 1 for external users
	var $atarget="";                            // Valeur du target a utiliser dans les liens
	var $name="auguria";
	
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
    	global $conf, $user, $langs;
    	
    	$this->type_user=$type_user;
    	$this->db=$db;
    	
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
    	$menuArbo = new Menubase($db,'auguria');
    	$menuArbo->menuLoad($mainmenu, $leftmenu, $type_user, 'auguria', $tabMenu);
    	
    	// Modules system tools
    	// TODO Find a way to add parent menu only if child menu exists. For the moment, no other method than hard coded methods.
    	if (! empty($conf->product->enabled) || ! empty($conf->service->enabled) || ! empty($conf->global->MAIN_MENU_ENABLE_MODULETOOLS))
    	{
    		if (empty($user->societe_id))
    		{
    		    //$newmenu->add("/admin/tools/index.php?mainmenu=home&leftmenu=modulesadmintools", $langs->trans("ModulesSystemTools"), 0, 1, '', 'home', 'modulesadmintools');
    			if ($leftmenu=="modulesadmintools" && $user->admin)
    			{
    				$langs->load("products");
			    	array_unshift($tabMenu,array(
			    			'url'=>"/product/admin/product_tools.php?mainmenu=home&leftmenu=modulesadmintools",
			    			'titre'=>$langs->trans("ProductVatMassChange"),
			    			'enabled'=>($user->admin?true:false),
			    			'perms'=>($user->admin?true:false),
			    			'fk_mainmenu'=>'home',
			    			'fk_leftmenu'=>'modulesadmintools',
			    			'fk_menu'=>-1,
			    			'mainmenu'=>'home',
			    			'leftmenu'=>'modulesadmintools_massvat',
			    			'type'=>'left',
			    			'position'=>20
			    	));
    				//$newmenu->add("/product/admin/product_tools.php?mainmenu=home&leftmenu=modulesadmintools", $langs->trans("ProductVatMassChange"), 1, $user->admin);
    			}
    			array_unshift($tabMenu,array(
		    		'url'=>"/admin/tools/index.php?mainmenu=home&leftmenu=modulesadmintools",
		    		'titre'=>$langs->trans("ModulesSystemTools"),
		    		'enabled'=>($user->admin?true:false),
		    		'perms'=>($user->admin?true:false),
		    		'fk_mainmenu'=>'home',
		    		'fk_menu'=>-1,
		    		'mainmenu'=>'home',
		    		'leftmenu'=>'modulesadmintools',
		    		'type'=>'left',
		    		'position'=>20
				));
    		}
    	}
    	
    	$this->tabMenu=$tabMenu;
    }
	

    /**
     *  Show menu
     *
     *	@param	string	$mode		'top' or 'left'
     *  @return	int     			Number of menu entries shown
	 */
	function showmenu($mode)
	{
    	global $conf;
    	
        require_once DOL_DOCUMENT_ROOT.'/core/menus/standard/auguria.lib.php';

        if ($this->type_user == 1)
        {
        	$conf->global->MAIN_SEARCHFORM_SOCIETE=0;
	        $conf->global->MAIN_SEARCHFORM_CONTACT=0;
        }
            
        $res='ErrorBadParameterForMode';
        if ($mode == 'top')  $res=print_auguria_menu($this->db,$this->atarget,$this->type_user,$this->tabMenu);
        if ($mode == 'left') $res=print_left_auguria_menu($this->db,$this->menu_array,$this->menu_array_after,$this->tabMenu);

        return $res;
    }
}

?>
