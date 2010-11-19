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
 *  \file		htdocs/includes/menus/standard/bureau2crea.lib.php
 *  \brief		Library for file bureau2crea menus
 *  \version	$Id$
 */



/**
 * Core function to output top menu bureau2crea
 *
 * @param $db
 * @param $atarget
 * @param $type_user     0=Internal,1=External,2=All
 */
function print_bureau2crea_menu($db,$atarget,$type_user)
{
	require_once(DOL_DOCUMENT_ROOT."/core/class/menubase.class.php");

	global $user,$conf,$langs,$dolibarr_main_db_name;

	// On sauve en session le menu principal choisi
	if (isset($_GET["mainmenu"])) $_SESSION["mainmenu"]=$_GET["mainmenu"];
	if (isset($_GET["idmenu"]))   $_SESSION["idmenu"]=$_GET["idmenu"];
	$_SESSION["leftmenuopened"]="";

	$menuArbo = new Menubase($db,'bureau2crea','top');
	$tabMenu = $menuArbo->menuTopCharger($type_user,$_SESSION['mainmenu'], 'bureau2crea');

	print_start_menu_array_bureau2crea();

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

				print_start_menu_entry_bureau2crea($idsel);
				print '<div class="mainmenu '.$idsel.'"><span class="mainmenu_'.$idsel.'" id="mainmenuspan_'.$idsel.'"></span></div>';
				print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.$url.'"'.($tabMenu[$i]['atarget']?" target='".$tabMenu[$i]['atarget']."'":($atarget?" target=$atarget":"")).'>';
				print_text_menu_entry_bureau2crea($tabMenu[$i]['titre']);
				print '</a>';
				print_end_menu_entry_bureau2crea();
			}
			else if (empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED))
			{
				if (! $type_user)
				{
					print_start_menu_entry_bureau2crea($idsel);
					print '<div class="mainmenu '.$idsel.'"><span class="mainmenu_'.$idsel.'" id="mainmenuspan_'.$idsel.'"></span></div>';
					print '<a class="tmenudisabled" id="mainmenua_'.$idsel.'" href="#">';
					print_text_menu_entry_bureau2crea($tabMenu[$i]['titre']);
					print '</a>';
					print_end_menu_entry_bureau2crea();
				}
			}
		}
	}

	print_end_menu_array_bureau2crea();

	print "\n";
}



function print_start_menu_array_bureau2crea()
{
	global $conf;
	if (preg_match('/bluelagoon|eldy|freelug|rodolphe|yellow|dev/',$conf->css)) print '<table class="tmenu" summary="topmenu"><tr class="tmenu">';
	else print '<ul class="tmenu">';
}

function print_start_menu_entry_bureau2crea($idsel)
{
	global $conf;
	if (preg_match('/bluelagoon|eldy|freelug|rodolphe|yellow|dev/',$conf->css)) print '<td class="tmenu" id="mainmenutd_'.$idsel.'">';
	else print '<li class="tmenu" id="mainmenutd_'.$idsel.'">';
}

function print_text_menu_entry_bureau2crea($text)
{
	global $conf;
	print '<span class="mainmenuaspan">';
	print $text;
	print '</span>';
}

function print_end_menu_entry_bureau2crea()
{
	global $conf;
	if (preg_match('/bluelagoon|eldy|freelug|rodolphe|yellow|dev/',$conf->css)) print '</td>';
	else print '</li>';
	print "\n";
}

function print_end_menu_array_bureau2crea()
{
	global $conf;
	if (preg_match('/bluelagoon|eldy|freelug|rodolphe|yellow|dev/',$conf->css)) print '</tr></table>';
	else print '</ul>';
	print "\n";
}



/**
 * Core function to output left menu bureau2crea
 *
 * @param      db                  Database handler
 * @param      menu_array_before   Table of menu entries to show before entries of menu handler
 * @param      menu_array_after    Table of menu entries to show after entries of menu handler
 */
function print_left_bureau2crea_menu($db,$menu_array_before,$menu_array_after)
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

        $menuArbo = new Menubase($db,'bureau2crea','left');
        $overwritemenufor = $menuArbo->listeMainmenu();
        $newmenu = $menuArbo->menuLeftCharger($newmenu,$mainmenu,$leftmenu,($user->societe_id?1:0),'bureau2crea');
        //var_dump($newmenu);

        /*
         * Menu AUTRES (Pour les menus du haut qui ne serait pas geres)
         */
        if ($mainmenu && ! in_array($mainmenu,$overwritemenufor)) { $mainmenu=""; }
    }


    /**
     *  Si on est sur un cas gere de surcharge du menu, on ecrase celui par defaut
     */
    //var_dump($menu_array_before);exit;
    //var_dump($menu_array_after);exit;
    $menu_array=$newmenu->liste;
    //if ($mainmenu) {
    if (is_array($menu_array_before)) $menu_array=array_merge($menu_array_before, $menu_array);
    if (is_array($menu_array_after))  $menu_array=array_merge($menu_array, $menu_array_after);
    //}
    //var_dump($menu_array);exit;

    // Affichage du menu
    $alt=0;
    if (is_array($menu_array))
    {
        for ($i = 0 ; $i < sizeof($menu_array) ; $i++)
        {
            $alt++;
            if (empty($menu_array[$i]['level']))
            {
                if (($alt%2==0))
                {
                	if ($conf->use_javascript_ajax && $conf->global->MAIN_MENU_USE_JQUERY_ACCORDION)
                	{
                		print '<div class="blockvmenupair">'."\n";
                	}
                	else
                	{
                		print '<div class="blockvmenuimpair">'."\n";
                	}
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
            if ($menu_array[$i]['level'] == 0)
            {
                if ($menu_array[$i]['enabled'])
                {
                    print '<div class="menu_titre">'.$tabstring.'<a class="vmenu" href="'.$url.'"'.($menu_array[$i]['target']?' target="'.$menu_array[$i]['target'].'"':'').'>'.$menu_array[$i]['titre'].'</a></div>';
                }
                else if (empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED))
                {
                    print '<div class="menu_titre">'.$tabstring.'<font class="vmenudisabled">'.$menu_array[$i]['titre'].'</font></div>';
                }
                print "\n".'<div id="section_content">'."\n";
                print '<div class="menu_top"></div>'."\n";
            }
            // Menu niveau > 0
            if ($menu_array[$i]['level'] > 0)
            {
                if ($menu_array[$i]['enabled'])
                {
                    print '<div class="menu_contenu">'.$tabstring.'<a class="vsmenu" href="'.$url.'"'.($menu_array[$i]['target']?' target="'.$menu_array[$i]['target'].'"':'').'>'.$menu_array[$i]['titre'].'</a></div>';
                }
                else if (empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED))
                {
                    print '<div class="menu_contenu">'.$tabstring.'<font class="vsmenudisabled">'.$menu_array[$i]['titre'].'</font></div>';
                }
            }

            // If next is a new block or end
            if (empty($menu_array[$i+1]['level']))
            {
                print '<div class="menu_end"></div>'."\n";
                print "</div><!-- end section content -->\n";
                print "</div><!-- end blockvmenu  pair/impair -->\n";
            }
        }
    }

    return sizeof($menu_array);
}

?>
