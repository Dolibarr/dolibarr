<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010 Regis Houssin        <regis@dolibarr.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *  \file		htdocs/includes/menus/smartphone/iphone.lib.php
 *  \brief		Library for file iphone menus
 *  \version	$Id$
 */


/**
 * Core function to output top menu iphone
 *
 * @param $db
 * @param $atarget
 * @param $type_user     0=Internal,1=External,2=All
 */
function print_iphone_menu($db,$atarget,$type_user)
{
	require_once(DOL_DOCUMENT_ROOT."/core/class/menubase.class.php");

	global $user,$conf,$langs,$dolibarr_main_db_name;
	
	$submenus='';

	$menutop = new Menubase($db,'iphone','top');
	$menuleft = new Menubase($db,'iphone','left');
	$tabMenu = $menutop->menuTopCharger($type_user, '', 'iphone');
	//var_dump($tabMenu);
	
	$numTabMenu = count($tabMenu);
	
	print_start_menu_array();

	for($i=0; $i<$numTabMenu; $i++)
	{
		if ($tabMenu[$i]['enabled'] == true)
		{
			if ($tabMenu[$i]['right'] == true)	// Is allowed
			{
				// Define url
				if (preg_match("/^(http:\/\/|https:\/\/)/i",$tabMenu[$i]['url']))
				{
					$url = dol_buildpath($tabMenu[$i]['url'],1);
				}
				else
				{
					print_start_menu_entry();
					print_text_menu_entry($tabMenu[$i]['titre']);
					
					$newmenu = new Menu();
					$leftmenu = $menuleft->menuLeftCharger($newmenu,$tabMenu[$i]['mainmenu'],'',($user->societe_id?1:0),'iphone');
					$menus = $leftmenu->liste;
					//var_dump($menus);
					
					print '<ul>';
					
					if (is_array($menus) && !empty($menus))
					{
						$num = count($menus);
						//var_dump($menus);

						for($j=0; $j<$num; $j++)
						{
							if ($menus[$j]['level'] == 0)
							{
								$url=dol_buildpath($menus[$j]['url'],1);
								print_start_menu_entry();
								if (empty($menus[$j+1]['level'])) print '<a href="'.$url.'"'.($menus[$j]['atarget']?" target='".$menus[$j]['atarget']."'":($atarget?" target=$atarget":'')).'>';
								print_text_menu_entry($menus[$j]['titre']);
								if (empty($menus[$j+1]['level'])) print '</a>';
							}
							
							if ($menus[$j]['level'] > 0)
							{
								if ($menus[$j-1]['level'] == 0) print_start_submenu_array();
								
								$url=dol_buildpath($menus[$j]['url'],1);
								print_start_menu_entry();
								print '<a href="'.$url.'"'.($menus[$j]['atarget']?" target='".$menus[$j]['atarget']."'":($atarget?" target=$atarget":'')).'>';
								print_text_menu_entry($menus[$j]['titre']);
								print '</a>';
								print_end_menu_entry();
								
								if (empty($menus[$j+1]['level'])) print_end_menu();
							}
							
							if (empty($menus[$j+1]['level'])) print_end_menu_entry();
						}
					}
					print_end_menu();
				}
			}
		}
	}
	
	print_end_menu();
	print "\n";
}

function print_start_menu_array($theme='c')
{
	print '<ul data-role="listview" data-theme="'.$theme.'">';
	print "\n";
}

function print_start_submenu_array()
{
	print '<ul>';
	print "\n";
}

function print_start_menu_entry()
{
	print '<li>';
}

function print_text_menu_entry($text)
{
	print $text;
}

function print_end_menu_entry()
{
	print '</li>';
	print "\n";
}

function print_end_menu()
{
	print '</ul>';
	print "\n";
}

?>
