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
	
	$submenu=array();

	// On sauve en session le menu principal choisi
	if (isset($_GET["mainmenu"])) $_SESSION["mainmenu"]=$_GET["mainmenu"];
	if (isset($_GET["idmenu"]))   $_SESSION["idmenu"]=$_GET["idmenu"];
	$_SESSION["leftmenuopened"]="";

	$menutop = new Menubase($db,'iphone','top');
	$menuleft = new Menubase($db,'iphone','left');
	$tabMenu = $menutop->menuTopCharger($type_user,$_SESSION['mainmenu'], 'iphone');
	//var_dump($newmenu);
	
	
	print_start_menu_array($langs->trans('Home'),1);

	for($i=0; $i<count($tabMenu); $i++)
	{
		if ($tabMenu[$i]['enabled'] == true)
		{
			$idsel=(empty($tabMenu[$i]['mainmenu'])?'none':$tabMenu[$i]['mainmenu']);
			if ($tabMenu[$i]['right'] == true)	// Is allowed
			{
				// Define url
				if (preg_match("/^(http:\/\/|https:\/\/)/i",$tabMenu[$i]['url']))
				{
					$url = $tabMenu[$i]['url'];
				}
				else
				{
					$url=DOL_URL_ROOT.$tabMenu[$i]['url'];
					if (! preg_match('/\?/',$url)) $url.='?';
					else $url.='&';
					if (! preg_match('/mainmenu/i',$url) || ! preg_match('/leftmenu/i',$url))
					{
						$url.='mainmenu='.$tabMenu[$i]['mainmenu'].'&leftmenu=&';
					}
					$url.="idmenu=".$tabMenu[$i]['rowid'];
					
					$newmenu = new Menu();
					
					$submenu[$i] = $menuleft->menuLeftCharger($newmenu,$tabMenu[$i]['mainmenu'],'',($user->societe_id?1:0),'iphone');
				}

				print_start_menu_entry($idsel);
				print '<a href="#'.$tabMenu[$i]['titre'].'">';
				print_text_menu_entry($tabMenu[$i]['titre']);
				print '</a>';
				print_end_menu_entry();
			}
		}
	}

	print_end_menu_array();

	print "\n";

	for($i=0; $i<count($submenu); $i++)
	{
		foreach($submenu[$i] as $menu)
		{
			print_start_menu_array($menu[0]['titre']);
			
			for($j=0; $j<count($menu); $j++)
			{
				print_start_menu_entry();
				print '<a href="'.$url.'"'.($menu[$j]['atarget']?" target='".$menu[$j]['atarget']."'":($atarget?" target=$atarget":"")).'>';
				print_text_menu_entry($menu[$j]['titre']);
				print '</a>';
				print_end_menu_entry();
			}
			
			print_end_menu_array();
		}
	}
}



function print_start_menu_array($title,$selected=0)
{
	print '<ul id="'.$title.'" title="'.$title.'" '.($selected?'selected="true"':'').'>';
}

function print_start_menu_entry()
{
	print '<li>';
}

function print_text_menu_entry($text)
{
	print '<span class="name">'.$text.'</span>';
	print '<span class="arrow"></span>';
}

function print_end_menu_entry()
{
	print '</li>';
	print "\n";
}

function print_end_menu_array()
{
	print '</ul>';
	print "\n";
}

?>
