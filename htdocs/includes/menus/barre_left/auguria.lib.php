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
 *  \file		htdocs/includes/menus/barre_left/auguria.lib.php
 *  \brief		Library for file auguria menus
 *  \version	$Id$
 */



/**
 * Core function to output left menu auguria
 *
 * @param unknown_type $db
 */
function print_left_auguria_menu($db,$menu_array)
{
	global $user,$conf,$langs,$dolibarr_main_db_name,$mysoc;

	$overwritemenufor = array();
	$newmenu = new Menu();

	// Read mainmenu and leftmenu that define which menu to show
	if (isset($_GET["mainmenu"])) {
		// On sauve en session le menu principal choisi
		$mainmenu=$_GET["mainmenu"];
		$_SESSION["mainmenu"]=$mainmenu;
		$_SESSION["leftmenuopened"]="";
	} else {
		// On va le chercher en session si non defini par le lien
		$mainmenu=$_SESSION["mainmenu"];
	}

	if (isset($_GET["leftmenu"])) {
		// On sauve en session le menu principal choisi
		$leftmenu=$_GET["leftmenu"];
		$_SESSION["leftmenu"]=$leftmenu;
		if ($_SESSION["leftmenuopened"]==$leftmenu) {
			//$leftmenu="";
			$_SESSION["leftmenuopened"]="";
		}
		else {
			$_SESSION["leftmenuopened"]=$leftmenu;
		}
	} else {
		// On va le chercher en session si non defini par le lien
		$leftmenu=isset($_SESSION["leftmenu"])?$_SESSION["leftmenu"]:'';
	}

	//this->menu_array contains menu in pre.inc.php


	// Show logo company
	if (! empty($conf->global->MAIN_SHOW_LOGO))
	{
		$mysoc->logo_mini=$conf->global->MAIN_INFO_SOCIETE_LOGO_MINI;
		if (! empty($mysoc->logo_mini) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_mini))
		{
			$urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode('thumbs/'.$mysoc->logo_mini);
			print "\n".'<!-- Show logo on menu -->'."\n";
			print '<div class="blockvmenuimpair">'."\n";
			print '<center><img title="'.$title.'" src="'.$urllogo.'"></center>'."\n";
			print '</div>'."\n";
		}
	}

	/**
	 * On definit newmenu en fonction de mainmenu et leftmenu
	 * ------------------------------------------------------
	 */
	if ($mainmenu)
	{
		require_once(DOL_DOCUMENT_ROOT."/core/class/menubase.class.php");

		$menuArbo = new Menubase($db,'auguria','left');
		$overwritemenufor = $menuArbo->listeMainmenu();
		$newmenu = $menuArbo->menuLeftCharger($newmenu,$mainmenu,$leftmenu,0,'auguria');

		/*
		 * Menu AUTRES (Pour les menus du haut qui ne serait pas geres)
		 */
		if ($mainmenu && ! in_array($mainmenu,$overwritemenufor)) { $mainmenu=""; }
	}

	//var_dump($newmenu->liste);
	//var_dump($this->menu_array);

	/**
	 *  Si on est sur un cas gere de surcharge du menu, on ecrase celui par defaut
	 */
	if ($mainmenu) {
		if ($leftmenu == 'bank') $menu_array=array_merge($menu_array,$newmenu->liste);
		else $menu_array=$newmenu->liste;
	}


	// Affichage du menu
	$alt=0;
	if (is_array($menu_array))
	{
		$contenu = 0;
		for ($i = 0 ; $i < sizeof($menu_array) ; $i++)
		{
			$alt++;
			if (empty($menu_array[$i]['level']))
			{
				if (($alt%2==0))
				{
					print '<div class="blockvmenuimpair">'."\n";
				}
				else
				{
					print '<div class="blockvmenupair">'."\n";
				}
			}

			// Place tabulation
			$tabstring='';
			$tabul=($menu_array[$i]['level'] - 1);
			if ($tabul > 0)
			{
				for ($j=0; $j < $tabul; $j++)
				{
					$tabstring.='&nbsp; &nbsp;';
				}
			}

			// Add mainmenu in GET url. This make to go back on correct menu even when using Back on browser.
			$url=$menu_array[$i]['url'];
			if (! preg_match('/mainmenu=/i',$menu_array[$i]['url']))
			{
				if (! preg_match('/\?/',$url)) $url.='?';
				else $url.='&';
				$url.='mainmenu='.$mainmenu;
			}

			// Menu niveau 0
			if ($menu_array[$i]['level']==0)
			{
				if ($contenu == 1) print '<div class="menu_end"></div>'."\n";
				if ($menu_array[$i]['enabled'])
				{

					print '<div class="menu_titre">'.$tabstring.'<a class="vmenu" href="'.$url.'"'.($menu_array[$i]['target']?' target="'.$menu_array[$i]['target'].'"':'').'>'.$menu_array[$i]['titre'].'</a></div>';
				}
				else
				{
					print '<div class="menu_titre">'.$tabstring.'<font class="vmenudisabled">'.$menu_array[$i]['titre'].'</font></div>';
				}
				$contenu = 0;
			}
			// Menu niveau > 0
			if ($menu_array[$i]['level'] > 0)
			{
				if ($menu_array[$i]['level']==1) $contenu = 1;

				if ($menu_array[$i]['enabled'])
				{
					print '<div class="menu_contenu">'.$tabstring.'<a class="vsmenu" href="'.$url.'"'.($menu_array[$i]['target']?' target="'.$menu_array[$i]['target'].'"':'').'>'.$menu_array[$i]['titre'].'</a></div>';
				}
				else
				{
					print '<div class="menu_contenu">'.$tabstring.'<font class="vsmenudisabled">'.$menu_array[$i]['titre'].'</font></div>';
				}
			}

			// If next is a new block or end
			if (empty($menu_array[$i+1]['level']))
			{
				print '<div class="menu_end"></div>'."\n";
				print "</div>\n";
			}
		}
	}

	return sizeof($menu_array);
}

?>
