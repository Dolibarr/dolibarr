<?php
/* Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/includes/menus/barre_left/empty.php
		\brief      This is an example of an empty left menu handler
		\version    $Id$
*/

/**
        \class      MenuLeftTop
	    \brief      Class for left empty menu
*/
class MenuLeft {

    var $require_top=array("empty");     // If this top menu handler must be used with a particular left menu handler

    
    /**
     *    \brief      Constructor
     *    \param      db      		Dabatase handler
     *    \param      menu_array    Menu array that you will override in showmenu() function
     */
    function MenuLeft($db,&$menu_array)
    {
        $this->db=$db;
        $this->menu_array=$menu_array;
    }
  
    
    /**
     *    \brief      Show menu
     */
    function showmenu()
    {
        global $user,$conf,$langs,$dolibarr_main_db_name;
        $newmenu = new Menu();
        
	    // Put here left menu entries
	    // ***** START *****
		
		$langs->load("admin");	// Load translation file admin.lang
		$newmenu->add(DOL_URL_ROOT."/admin/index.php?leftmenu=setup", $langs->trans("Setup"));
		$newmenu->add_submenu(DOL_URL_ROOT."/admin/company.php", $langs->trans("MenuCompanySetup"));
		$newmenu->add_submenu(DOL_URL_ROOT."/admin/modules.php", $langs->trans("Modules"));
		$newmenu->add_submenu(DOL_URL_ROOT."/admin/menus.php", $langs->trans("Menus"));
		$newmenu->add_submenu(DOL_URL_ROOT."/admin/ihm.php", $langs->trans("GUISetup"));
		$newmenu->add_submenu(DOL_URL_ROOT."/admin/boxes.php", $langs->trans("Boxes"));
		$newmenu->add_submenu(DOL_URL_ROOT."/admin/delais.php",$langs->trans("Alerts"));
		$newmenu->add_submenu(DOL_URL_ROOT."/admin/perms.php", $langs->trans("Security"));
		$newmenu->add_submenu(DOL_URL_ROOT."/admin/mails.php", $langs->trans("EMails"));
		$newmenu->add_submenu(DOL_URL_ROOT."/admin/limits.php", $langs->trans("Limits"));
		$newmenu->add_submenu(DOL_URL_ROOT."/admin/dict.php", $langs->trans("DictionnarySetup"));
		$newmenu->add_submenu(DOL_URL_ROOT."/admin/const.php", $langs->trans("OtherSetup"));

	    // ***** END *****
		
        // do not change code after this

        // override menu_array by value array in $newmenu
		$this->menu_array=$newmenu->liste;

        $alt=0;
        for ($i = 0 ; $i < sizeof($this->menu_array) ; $i++) 
        {
            $alt++;
            if ($this->menu_array[$i]['level']==0) {
                if (($alt%2==0))
                {
                    print '<div class="blockvmenuimpair">'."\n";
                }
                else
                {
                    print '<div class="blockvmenupair">'."\n";
                }
            }

            if ($this->menu_array[$i]['level']==0) {
                if ($this->menu_array[$i]['enabled'])
                    print '<a class="vmenu" href="'.$this->menu_array[$i]['url'].'">'.$this->menu_array[$i]['titre'].'</a><br>';
                else 
                    print '<font class="vmenudisabled">'.$this->menu_array[$i]['titre'].'</font><br>';
            }
            if ($this->menu_array[$i]['level']==1) {
                if ($this->menu_array[$i]['enabled'])
                    print '<a class="vsmenu" href="'.$this->menu_array[$i]['url'].'">'.$this->menu_array[$i]['titre'].'</a><br>';
                else 
                    print '<font class="vsmenudisabled">'.$this->menu_array[$i]['titre'].'</font><br>';
            }
            if ($this->menu_array[$i]['level']==2) {
                if ($this->menu_array[$i]['enabled'])
                    print '&nbsp; &nbsp; <a class="vsmenu" href="'.$this->menu_array[$i]['url'].'">'.$this->menu_array[$i]['titre'].'</a><br>';
                else 
                    print '&nbsp; &nbsp; <font class="vsmenudisabled">'.$this->menu_array[$i]['titre'].'</font><br>';
            }
            if ($this->menu_array[$i]['level']==3) {
                if ($this->menu_array[$i]['enabled'])
                    print '&nbsp; &nbsp; &nbsp; &nbsp; <a class="vsmenu" href="'.$this->menu_array[$i]['url'].'">'.$this->menu_array[$i]['titre'].'</a><br>';
                else 
                    print '&nbsp; &nbsp; &nbsp; &nbsp; <font class="vsmenudisabled">'.$this->menu_array[$i]['titre'].'</font><br>';
            }
            
            if ($i == (sizeof($this->menu_array)-1) || $this->menu_array[$i+1]['level']==0)  {
                print "</div>\n";
            }
        }

    }
    
}

?>
