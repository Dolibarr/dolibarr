<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file		htdocs/includes/menus/barre_top/auguria.lib.php
 *  \brief		Library for file auguria menus
 *  \version	$Id$
 */



/**
 * Core function to output top menu auguria
 *
 * @param $db
 * @param $atarget
 * @param $type_user     0=Internal,1=External,2=All
 */
function print_auguria_menu($db,$atarget,$type_user)
{
	require_once(DOL_DOCUMENT_ROOT."/core/class/menubase.class.php");

	global $user,$conf,$langs,$dolibarr_main_db_name;

	// On sauve en session le menu principal choisi
	if (isset($_GET["mainmenu"])) $_SESSION["mainmenu"]=$_GET["mainmenu"];
	if (isset($_GET["idmenu"]))   $_SESSION["idmenu"]=$_GET["idmenu"];
	$_SESSION["leftmenuopened"]="";

	$menuArbo = new Menubase($db,'auguria','top');
	$tabMenu = $menuArbo->menuTopCharger($type_user,$_SESSION['mainmenu'], 'auguria');

	print_start_menu_array_auguria();

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
				}

				// Define the class (top menu selected or not)
				if (! empty($_SESSION['idmenu']) && $tabMenu[$i]['rowid'] == $_SESSION['idmenu']) $classname='class="tmenusel"';
				else if (! empty($_SESSION['mainmenu']) && $tabMenu[$i]['mainmenu'] == $_SESSION['mainmenu']) $classname='class="tmenusel"';
				else $classname='class="tmenu"';

				print_start_menu_entry_auguria($idsel);
				print '<div class="mainmenu '.$idsel.'"><span class="mainmenu_'.$idsel.'" id="mainmenuspan_'.$idsel.'"></span></div>';
				print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.$url.'"'.($tabMenu[$i]['atarget']?" target='".$tabMenu[$i]['atarget']."'":($atarget?" target=$atarget":"")).'>';
				print_text_menu_entry_auguria($tabMenu[$i]['titre']);
				print '</a>';
				print_end_menu_entry_auguria();
			}
			else
			{
				if (! $type_user)
				{
					print_start_menu_entry_auguria($idsel);
					print '<div class="mainmenu '.$idsel.'"><span class="mainmenu_'.$idsel.'" id="mainmenuspan_'.$idsel.'"></span></div>';
					print '<a class="tmenudisabled" id="mainmenua_'.$idsel.'" href="#">';
					print_text_menu_entry_auguria($tabMenu[$i]['titre']);
					print '</a>';
					print_end_menu_entry_auguria();
				}
			}
		}
	}

	print_end_menu_array_auguria();

	print "\n";
}



function print_start_menu_array_auguria()
{
	global $conf;
	if (preg_match('/bluelagoon|eldy|freelug|rodolphe|yellow|dev/',$conf->css)) print '<table class="tmenu" summary="topmenu"><tr class="tmenu">';
	else print '<ul class="tmenu">';
}

function print_start_menu_entry_auguria($idsel)
{
	global $conf;
	if (preg_match('/bluelagoon|eldy|freelug|rodolphe|yellow|dev/',$conf->css)) print '<td class="tmenu" id="mainmenutd_'.$idsel.'">';
	else print '<li class="tmenu" id="mainmenutd_'.$idsel.'">';
}

function print_text_menu_entry_auguria($text)
{
	global $conf;
	print '<span class="mainmenuaspan">';
	print $text;
	print '</span>';
}

function print_end_menu_entry_auguria()
{
	global $conf;
	if (preg_match('/bluelagoon|eldy|freelug|rodolphe|yellow|dev/',$conf->css)) print '</td>';
	else print '</li>';
	print "\n";
}

function print_end_menu_array_auguria()
{
	global $conf;
	if (preg_match('/bluelagoon|eldy|freelug|rodolphe|yellow|dev/',$conf->css)) print '</tr></table>';
	else print '</ul>';
	print "\n";
}

?>
