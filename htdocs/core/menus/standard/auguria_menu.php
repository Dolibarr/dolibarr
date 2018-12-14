<?php
/* Copyright (C) 2007      Patrick Raguin       <patrick.raguin@gmail.com>
 * Copyright (C) 2009      Regis Houssin        <regis.houssin@inodbox.com>
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
	/**
     * @var DoliDB Database handler.
     */
    public $db;
    
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

    	$this->tabMenu=$tabMenu;
    }


    /**
     *  Show menu
     *
     *	@param	string	$mode		    'top', 'left', 'jmobile' (used to get full xml ul/li menu)
     *  @param	array	$moredata		An array with more data to output
     *  @return int                     0 or nb of top menu entries if $mode = 'topnb'
	 */
	function showmenu($mode, $moredata=null)
	{
    	global $conf, $langs, $user;

        require_once DOL_DOCUMENT_ROOT.'/core/menus/standard/auguria.lib.php';

        if ($this->type_user == 1)
        {
        	$conf->global->MAIN_SEARCHFORM_SOCIETE_DISABLED=1;
	        $conf->global->MAIN_SEARCHFORM_CONTACT_DISABLED=1;
        }

		require_once DOL_DOCUMENT_ROOT.'/core/class/menu.class.php';
        $this->menu=new Menu();

        if ($mode == 'top')  print_auguria_menu($this->db,$this->atarget,$this->type_user,$this->tabMenu,$this->menu,0,$mode);
        if ($mode == 'left') print_left_auguria_menu($this->db,$this->menu_array,$this->menu_array_after,$this->tabMenu,$this->menu,0,'','',$moredata);

		if ($mode == 'topnb')
		{
		    print_auguria_menu($this->db,$this->atarget,$this->type_user,$this->tabMenu,$this->menu,1,$mode);
		    return $this->menu->getNbOfVisibleMenuEntries();
		}

        if ($mode == 'jmobile')     // Used to get menu in xml ul/li
        {
        	print_auguria_menu($this->db,$this->atarget,$this->type_user,$this->tabMenu,$this->menu,1,$mode);

            // $this->menu->liste is top menu
            //var_dump($this->menu->liste);exit;
        	$lastlevel = array();
        	print '<!-- Generate menu list from menu handler '.$this->name.' -->'."\n";
        	foreach($this->menu->liste as $key => $val)		// $val['url','titre','level','enabled'=0|1|2,'target','mainmenu','leftmenu'
        	{
        		print '<ul class="ulmenu" data-inset="true">';
        		print '<li class="lilevel0">';
        		if ($val['enabled'] == 1)
        		{
        			$substitarray = array('__LOGIN__' => $user->login, '__USER_ID__' => $user->id, '__USER_SUPERVISOR_ID__' => $user->fk_user);
        			$substitarray['__USERID__'] = $user->id;	// For backward compatibility
        			$val['url'] = make_substitutions($val['url'], $substitarray);

        			$relurl=dol_buildpath($val['url'],1);
        			$canonurl=preg_replace('/\?.*$/','',$val['url']);

        			print '<a class="alilevel0" href="#">';

					// Add font-awesome
					if ($val['level'] == 0 && $val['mainmenu'] == 'home') print '<span class="fa fa-home fa-fw paddingright" aria-hidden="true"></span>';

					print $val['titre'];
        			print '</a>'."\n";

        			// Search submenu fot this mainmenu entry
        			$tmpmainmenu=$val['mainmenu'];
        			$tmpleftmenu='all';
        			$submenu=new Menu();
        			print_left_auguria_menu($this->db,$this->menu_array,$this->menu_array_after,$this->tabMenu,$submenu,1,$tmpmainmenu,$tmpleftmenu);
        			$nexturl=dol_buildpath($submenu->liste[0]['url'],1);

        			$canonrelurl=preg_replace('/\?.*$/','',$relurl);
        			$canonnexturl=preg_replace('/\?.*$/','',$nexturl);
        			//var_dump($canonrelurl);
        			//var_dump($canonnexturl);
        			print '<ul>'."\n";
        			if (($canonrelurl != $canonnexturl && ! in_array($val['mainmenu'],array('tools')))
        				|| (strpos($canonrelurl,'/product/index.php') !== false || strpos($canonrelurl,'/compta/bank/list.php') !== false))
					{
						// We add sub entry
						print str_pad('',1).'<li class="lilevel1 ui-btn-icon-right ui-btn">';	 // ui-btn to highlight on clic
						print '<a href="'.$relurl.'">';
					    if ($langs->trans(ucfirst($val['mainmenu'])."Dashboard") == ucfirst($val['mainmenu'])."Dashboard")  // No translation
        				{
        				    if (in_array($val['mainmenu'], array('cashdesk', 'externalsite', 'website', 'collab'))) print $langs->trans("Access");
        				    else print $langs->trans("Dashboard");
        				}
						else print $langs->trans(ucfirst($val['mainmenu'])."Dashboard");
						print '</a>';
						print '</li>'."\n";
        			}

    				if ($val['level']==0)
					{
					    if ($val['enabled'])
					    {
					        $lastlevel[0]='enabled';
					    }
					    else if ($showmenu)                 // Not enabled but visible (so greyed)
					    {
					        $lastlevel[0]='greyed';
					    }
					    else
					    {
					        $lastlevel[0]='hidden';
					    }
					}

					$lastlevel2 = array();
					foreach($submenu->liste as $key2 => $val2)		// $val['url','titre','level','enabled'=0|1|2,'target','mainmenu','leftmenu'
        			{
						$showmenu=true;
						if (! empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED) && empty($val2['enabled'])) $showmenu=false;

        				// If at least one parent is not enabled, we do not show any menu of all children
						if ($val2['level'] > 0)
						{
						    $levelcursor = $val2['level']-1;
						    while ($levelcursor >= 0)
						    {
                                if ($lastlevel2[$levelcursor] != 'enabled') $showmenu=false;
                                $levelcursor--;
						    }
						}

						if ($showmenu)		// Visible (option to hide when not allowed is off or allowed)
       					{
       						$substitarray = array('__LOGIN__' => $user->login, '__USER_ID__' => $user->id, '__USER_SUPERVISOR_ID__' => $user->fk_user);
       						$substitarray['__USERID__'] = $user->id;	// For backward compatibility
       						$val2['url'] = make_substitutions($val2['url'], $substitarray);		// Make also substitution of __(XXX)__ and __[XXX]__

       						if (! preg_match("/^(http:\/\/|https:\/\/)/i", $val2['url']))
       						{
       							$relurl2=dol_buildpath($val2['url'],1);
       						}
       						else
       						{
       							$relurl2=$val2['url'];
       						}
	        				$canonurl2=preg_replace('/\?.*$/','',$val2['url']);
	        				//var_dump($val2['url'].' - '.$canonurl2.' - '.$val2['level']);
	        				if (in_array($canonurl2,array('/admin/index.php','/admin/tools/index.php','/core/tools.php'))) $relurl2='';

	        				$disabled='';
	        				if (! $val2['enabled'])
	        				{
	        				    $disabled=" vsmenudisabled";
	        				}

	        				print str_pad('',$val2['level']+1);
	        				print '<li class="lilevel'.($val2['level']+1);
	        				if ($val2['level']==0) print ' ui-btn-icon-right ui-btn';  // ui-btn to highlight on clic
	        				print $disabled.'">';	 // ui-btn to highlight on clic
	        				if ($relurl2)
	        				{
	        					if ($val2['enabled'])	// Allowed
	        					{
	        						print '<a href="'.$relurl2.'"';
		        					//print ' data-ajax="false"';
		        					print '>';
		        					$lastlevel2[$val2['level']]='enabled';
	        					}
	        					else					// Not allowed but visible (greyed)
	        					{
				        			print '<a href="#" class="vsmenudisabled">';
				        			$lastlevel2[$val2['level']]='greyed';
	        					}
	        				}
	        				else
	        				{
	        				    if ($val2['enabled'])	// Allowed
	        				    {
	        				        $lastlevel2[$val2['level']]='enabled';
	        				    }
	        				    else
	        				    {
	        				        $lastlevel2[$val2['level']]='greyed';
	        				    }
	        				}
	        				print $val2['titre'];
	        				if ($relurl2)
	        				{
	        					if ($val2['enabled'])	// Allowed
	        						print '</a>';
	        					else
	        						print '</a>';
	        				}
	        				print '</li>'."\n";
       					}
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
    }
}

