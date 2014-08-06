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
	var $atarget="";                            // To store default target to use onto links
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
   	function loadMenu($forcemainmenu='',$forceleftmenu='')
   	{
    	global $conf, $user, $langs;

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
		if (! empty($forcemainmenu)) $mainmenu=$forcemainmenu;

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
    	if (! empty($forceleftmenu)) $leftmenu=$forceleftmenu;

    	require_once DOL_DOCUMENT_ROOT.'/core/class/menubase.class.php';
    	$tabMenu=array();
    	$menuArbo = new Menubase($this->db,'auguria');
    	$menuArbo->menuLoad($mainmenu, $leftmenu, $this->type_user, 'auguria', $tabMenu);

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
    				$array_menu_product=array(
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
			    	);
			    	array_unshift($tabMenu,$array_menu_product);
    				//$newmenu->add("/product/admin/product_tools.php?mainmenu=home&leftmenu=modulesadmintools", $langs->trans("ProductVatMassChange"), 1, $user->admin);
    			}
    			$array_menu_product=array(
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
				);
    			array_unshift($tabMenu,$array_menu_product);
    		}
    	}

    	$this->tabMenu=$tabMenu;
    }


    /**
     *  Show menu
     *
     *	@param	string	$mode		'top', 'left', 'jmobile'
     *  @return	int     			Number of menu entries shown
	 */
	function showmenu($mode)
	{
    	global $conf, $langs, $user;

        require_once DOL_DOCUMENT_ROOT.'/core/menus/standard/auguria.lib.php';

        if ($this->type_user == 1)
        {
        	$conf->global->MAIN_SEARCHFORM_SOCIETE=0;
	        $conf->global->MAIN_SEARCHFORM_CONTACT=0;
        }

        $res='ErrorBadParameterForMode';

		require_once DOL_DOCUMENT_ROOT.'/core/class/menu.class.php';
        $this->menu=new Menu();

        if ($mode == 'top')  $res=print_auguria_menu($this->db,$this->atarget,$this->type_user,$this->tabMenu,$this->menu,0);
        if ($mode == 'left') $res=print_left_auguria_menu($this->db,$this->menu_array,$this->menu_array_after,$this->tabMenu,$this->menu,0);
        if ($mode == 'jmobile')
        {
        	$res=print_auguria_menu($this->db,$this->atarget,$this->type_user,$this->tabMenu,$this->menu,1);

        	print '<!-- Generate menu list from menu handler '.$this->name.' -->'."\n";
        	foreach($this->menu->liste as $key => $val)		// $val['url','titre','level','enabled'=0|1|2,'target','mainmenu','leftmenu'
        	{
        		print '<ul class="ulmenu" data-role="listview" data-inset="true">';
        		print '<li data-role="list-dividerxxx" class="lilevel0">';
        		if ($val['enabled'] == 1)
        		{
        			$relurl=dol_buildpath($val['url'],1);
        			$relurl=preg_replace('/__LOGIN__/',$user->login,$relurl);
        			$relurl=preg_replace('/__USERID__/',$user->id,$relurl);

        			print '<a class="alilevel0" href="#">'.$val['titre'].'</a>'."\n";
        			// Search submenu fot this entry
        			$tmpmainmenu=$val['mainmenu'];
        			$tmpleftmenu='all';
        			$submenu=new Menu();
        			$res=print_left_auguria_menu($this->db,$this->menu_array,$this->menu_array_after,$this->tabMenu,$submenu,1,$tmpmainmenu,$tmpleftmenu);
        			$nexturl=dol_buildpath($submenu->liste[0]['url'],1);

        			$canonrelurl=preg_replace('/\?.*$/','',$relurl);
        			$canonnexturl=preg_replace('/\?.*$/','',$nexturl);
        			//var_dump($canonrelurl);
        			//var_dump($canonnexturl);
        			print '<ul>'."\n";
        			if (($canonrelurl != $canonnexturl && ! in_array($val['mainmenu'],array('tools')))
        				|| (strpos($canonrelurl,'/product/index.php') !== false || strpos($canonrelurl,'/compta/bank/index.php') !== false))
					{
						// We add sub entry
						print str_pad('',1).'<li data-role="list-dividerxxx" class="lilevel1 ui-btn-icon-right ui-btn">';	 // ui-btn to highlight on clic
						print '<a href="'.$relurl.'">';
						print str_pad('',12,'&nbsp;');
						if ($langs->trans(ucfirst($val['mainmenu'])."Dashboard") == ucfirst($val['mainmenu'])."Dashboard") print $langs->trans("Access");	// No translation
						else print $langs->trans(ucfirst($val['mainmenu'])."Dashboard");
						print '</a>';
						print '</li>'."\n";
        			}
        			foreach($submenu->liste as $key2 => $val2)		// $val['url','titre','level','enabled'=0|1|2,'target','mainmenu','leftmenu'
        			{
        				$relurl2=dol_buildpath($val2['url'],1);
	        			$relurl2=preg_replace('/__LOGIN__/',$user->login,$relurl2);
    	    			$relurl2=preg_replace('/__USERID__/',$user->id,$relurl2);
        				$canonurl2=preg_replace('/\?.*$/','',$val2['url']);
        				//var_dump($val2['url'].' - '.$canonurl2.' - '.$val2['level']);
        				if (in_array($canonurl2,array('/admin/index.php','/admin/tools/index.php','/core/tools.php'))) $relurl2='';
        				if ($val2['level']==0) print str_pad('',$val2['level']+1).'<li'.($val2['level']==0?' data-role="list-dividerxxx"':'').' class="lilevel'.($val2['level']+1).' ui-btn-icon-right ui-btn">';	 // ui-btn to highlight on clic
        				else print str_pad('',$val2['level']+1).'<li class="lilevel'.($val2['level']+1).'">';	 // ui-btn to highlight on clic
        				if ($relurl2) print '<a href="'.$relurl2.'">';
        				print str_pad('',($val2['level']+1)*12,'&nbsp;');
        				print $val2['titre'];
        				if ($relurl2) print '</a>';
        				print '</li>'."\n";
        			}
        			//var_dump($submenu);
        			print '</ul>';
        		}
        		if ($val['enabled'] == 2)
        		{
        			print '<font class="vsmenudisabled">'.$val['titre'].'</font>';
        		}
        		print '</li>';
        		print '</ul>'."\n";
        	}
        }

        unset($this->menu);

        return $res;
    }
}

?>
