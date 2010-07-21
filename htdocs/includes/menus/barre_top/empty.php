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

        print_start_menu_array_empty();

		$idsel='none';
        $classname='class="tmenu"';

		print_start_menu_entry_empty($idsel);
		print '<div class="mainmenu '.$idsel.'"><span class="mainmenu_'.$idsel.'" id="mainmenuspan_'.$idsel.'"></span></div>';
		print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.$url.'"'.($tabMenu[$i]['atarget']?" target='".$tabMenu[$i]['atarget']."'":($atarget?" target=$atarget":"")).'>';
		print_text_menu_entry_empty($langs->trans("Home"));
		print '</a>';
		print_end_menu_entry_empty();

		print_end_menu_array_empty();
    }

}


function print_start_menu_array_empty()
{
	global $conf;
	if (preg_match('/bluelagoon|eldy|freelug|rodolphe|yellow|dev/',$conf->css)) print '<table class="tmenu" summary="topmenu"><tr class="tmenu">';
	else print '<ul class="tmenu">';
}

function print_start_menu_entry_empty($idsel)
{
	global $conf;
	if (preg_match('/bluelagoon|eldy|freelug|rodolphe|yellow|dev/',$conf->css)) print '<td class="tmenu" id="mainmenutd_'.$idsel.'">';
	else print '<li class="tmenu" id="mainmenutd_'.$idsel.'">';
}

function print_text_menu_entry_empty($text)
{
	global $conf;
	print '<span class="mainmenuaspan">';
	print $text;
	print '</span>';
}

function print_end_menu_entry_empty()
{
	global $conf;
	if (preg_match('/bluelagoon|eldy|freelug|rodolphe|yellow|dev/',$conf->css)) print '</td>';
	else print '</li>';
	print "\n";
}

function print_end_menu_array_empty()
{
	global $conf;
	if (preg_match('/bluelagoon|eldy|freelug|rodolphe|yellow|dev/',$conf->css)) print '</tr></table>';
	else print '</ul>';
	print "\n";
}

?>