<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/includes/menus/barre_top/empty.php
 *		\brief      This is an example of an empty top menu handler
 *		\version    $Id$
 */

/**
 *      \class      MenuTop
 *	    \brief      Class for top empty menu
 */
class MenuTop {

    var $require_left=array("empty");   // If this top menu handler must be used with a particular left menu handler
    var $hideifnotallowed=false;		// Put 0 for back office menu, 1 for front office menu

    var $atarget="";               		// To store arget to use in menu links


    /**
     *    \brief      Constructor
     *    \param      db      Dabatase handler
     */
    function MenuTop($db)
    {
        $this->db=$db;
    }


    /**
     *    \brief      Show menu
     */
    function showmenu()
    {
        global $user,$conf,$langs,$dolibarr_main_db_name;;

        print '<table class="tmenu" summary="topmenu"><tr class="tmenu">';

		// Menu Home
	    print '<td class="tmenu">';
	    print '<a href="'.DOL_URL_ROOT.'/index.php?mainmenu=home"'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Home").'</a>';
	    print '</td>';

	    // Put here top menu entries
	    // ***** START *****

		// print '<td class="tmenu">';
		// print '<a '.$class.' href="'.DOL_URL_ROOT.'/thepage1.php>My menu entry 1</a>';
		// print '</td>';
		// print '<td class="tmenu">';
		// print '<a '.$class.' href="'.DOL_URL_ROOT.'/thepage2.php>My menu entry 2</a>';
		// print '</td>';
	    // ...

	    // ***** END *****

	    /*
		// Code to show personalized menus
       	require_once(DOL_DOCUMENT_ROOT."/core/menubase.class.php");

        $menuArbo = new Menubase($this->db,'empty','top');
 		$tabMenu = $menuArbo->menuTopCharger(2,$_SESSION['mainmenu'],'empty');

        for($i=0; $i<count($tabMenu); $i++)
        {
        	if ($tabMenu[$i]['enabled'] == true)
        	{
        		$idsel=(empty($tabMenu[$i]['mainmenu'])?'id="none" ':'id="'.$tabMenu[$i]['mainmenu'].'" ');
	        	if ($tabMenu[$i]['right'] == true)
	        	{
					$url=DOL_URL_ROOT.$tabMenu[$i]['url'];
					if (! eregi('\?',DOL_URL_ROOT.$tabMenu[$i]['url'])) $url.='?';
					else $url.='&';
					$url.='mainmenu='.$tabMenu[$i]['mainmenu'].'&leftmenu=';
					$url.="&idmenu=".$tabMenu[$i]['rowid'];
					if (! empty($_SESSION['idmenu']) && $tabMenu[$i]['rowid'] == $_SESSION['idmenu']) $class='class="tmenusel"';
					else $class='class="tmenu"';
					print '<td class="tmenu">';
					print '<a '.$class.' '.$idsel.'href="'.$url.'"'.($this->atarget?" target=$this->atarget":($this->atarget?" target=$this->atarget":"")).'>';
					print $tabMenu[$i]['titre'];
					print '</a>';
					print '</td>';
	        	}
	        	else
	        	{
	        		if (! $this->hideifnotallowed)
					{
						print '<td class="tmenu">';
						print '<a class="tmenudisabled" '.$idsel.'href="#">'.$tabMenu[$i]['titre'].'</a>';
						print '</td>';
					}
	        	}
			}
        }
        */

        print '</tr></table>';
    }

}

?>