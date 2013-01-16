<?php
/* Copyright (C) 2010		Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@capnetworks.com>
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
 * or see http://www.gnu.org/
 */

/**
 *  \file		htdocs/core/menus/standard/auguria.lib.php
 *  \brief		Library for file auguria menus
 */



/**
 * Core function to output top menu auguria
 *
 * @param 	DoliDB	$db				Database handler
 * @param 	string	$atarget		Target
 * @param 	int		$type_user     	0=Internal,1=External,2=All
 * @return	void
 */
function print_auguria_menu($db,$atarget,$type_user)
{
	require_once DOL_DOCUMENT_ROOT.'/core/class/menubase.class.php';

	global $user,$conf,$langs,$dolibarr_main_db_name;

	// On sauve en session le menu principal choisi
	if (isset($_GET["mainmenu"])) $_SESSION["mainmenu"]=$_GET["mainmenu"];
	if (isset($_GET["idmenu"]))   $_SESSION["idmenu"]=$_GET["idmenu"];
	$_SESSION["leftmenuopened"]="";

	$listofmodulesforexternal=explode(',',$conf->global->MAIN_MODULES_FOR_EXTERNAL);

	$tabMenu=array();
	$menuArbo = new Menubase($db,'auguria','top');
	$newTabMenu = $menuArbo->menuTopCharger('', '', $type_user, 'auguria',$tabMenu);

	print_start_menu_array_auguria();

	$num = count($newTabMenu);
	for($i = 0; $i < $num; $i++)
	{
		$idsel=(empty($newTabMenu[$i]['mainmenu'])?'none':$newTabMenu[$i]['mainmenu']);

		$showmode=dol_auguria_showmenu($type_user,$newTabMenu[$i],$listofmodulesforexternal);

		if ($showmode == 1)
		{
			// Define url
			if (preg_match("/^(http:\/\/|https:\/\/)/i",$newTabMenu[$i]['url']))
			{
				$url = $newTabMenu[$i]['url'];
			}
			else
			{
				$url=dol_buildpath($newTabMenu[$i]['url'],1);
				if (! preg_match('/mainmenu/i',$url) || ! preg_match('/leftmenu/i',$url))
				{
					if (! preg_match('/\?/',$url)) $url.='?';
					else $url.='&';
					$url.='mainmenu='.$newTabMenu[$i]['mainmenu'].'&leftmenu=';
				}
				//$url.="idmenu=".$newTabMenu[$i]['rowid'];    // Already done by menuLoad
			}
			$url=preg_replace('/__LOGIN__/',$user->login,$url);

			// Define the class (top menu selected or not)
			if (! empty($_SESSION['idmenu']) && $newTabMenu[$i]['rowid'] == $_SESSION['idmenu']) $classname='class="tmenusel"';
			else if (! empty($_SESSION["mainmenu"]) && $newTabMenu[$i]['mainmenu'] == $_SESSION["mainmenu"]) $classname='class="tmenusel"';
			else $classname='class="tmenu"';

			print_start_menu_entry_auguria($idsel,$classname);
			print '<div class="mainmenu '.$idsel.'"><span class="mainmenu_'.$idsel.'" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.$url.'"'.($newTabMenu[$i]['target']?' target="'.$newTabMenu[$i]['target'].'"':($atarget?' target="'.$atarget.'"':'')).'>';
			print_text_menu_entry_auguria($newTabMenu[$i]['titre']);
			print '</a>';
			print_end_menu_entry_auguria();
		}
		else if ($showmode == 2)
		{
			print_start_menu_entry_auguria($idsel,'class="tmenu"');
			print '<div class="mainmenu '.$idsel.'"><span class="mainmenu_'.$idsel.'" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '<a class="tmenudisabled" id="mainmenua_'.$idsel.'" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">';
			print_text_menu_entry_auguria($newTabMenu[$i]['titre']);
			print '</a>';
			print_end_menu_entry_auguria();
		}
	}

	print_end_menu_array_auguria();

	print "\n";
}


/**
 * Output start menu array
 *
 * @return	void
 */
function print_start_menu_array_auguria()
{
	global $conf;
	print '<div class="tmenudiv">';
	print '<ul class="tmenu">';
}

/**
 * Output start menu entry
 *
 * @param	string	$idsel		Text
 * @param	string	$classname	String to add a css class
 * @return	void
 */
function print_start_menu_entry_auguria($idsel,$classname)
{
	print '<li '.$classname.' id="mainmenutd_'.$idsel.'">';
	print '<div class="tmenuleft"></div><div class="tmenucenter">';
}

/**
 * Output menu entry
 *
 * @param	string	$text		Text
 * @return	void
 */
function print_text_menu_entry_auguria($text)
{
	print '<span class="mainmenuaspan">';
	print $text;
	print '</span>';
}

/**
 * Output end menu entry
 *
 * @return	void
 */
function print_end_menu_entry_auguria()
{
	print '</div>';
	print '</li>';
	print "\n";
}

/**
 * Output menu array
 *
 * @return	void
 */
function print_end_menu_array_auguria()
{
	print '</ul>';
    print '</div>';
	print "\n";
}



/**
 * Core function to output left menu auguria
 *
 * @param	DoliDB		$db                  Database handler
 * @param 	array		$menu_array_before   Table of menu entries to show before entries of menu handler
 * @param   array		$menu_array_after    Table of menu entries to show after entries of menu handler
 * @return	void
 */
function print_left_auguria_menu($db,$menu_array_before,$menu_array_after)
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
            $urllogo=DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=companylogo&amp;file='.urlencode('thumbs/'.$mysoc->logo_mini);
            print "\n".'<!-- Show logo on menu -->'."\n";
            print '<div class="blockvmenuimpair">'."\n";
            print '<div class="menu_titre" id="menu_titre_logo"></div>';
            print '<div class="menu_top" id="menu_top_logo"></div>';
            print '<div class="menu_contenu" id="menu_contenu_logo">';
            print '<center><img title="" src="'.$urllogo.'"></center>'."\n";
            print '</div>';
            print '<div class="menu_end" id="menu_end_logo"></div>';
            print '</div>'."\n";
        }
    }

	// Modules system tools
	// TODO Find a way to add parent menu only if child menu exists. For the moment, no ther method than hard coded methods.
    if (! empty($conf->product->enabled) || ! empty($conf->service->enabled) || ! empty($conf->global->MAIN_MENU_ENABLE_MODULETOOLS))
    {
    	if (empty($user->societe_id))
    	{
	       	$newmenu->add("/admin/tools/index.php?mainmenu=home&leftmenu=modulesadmintools", $langs->trans("ModulesSystemTools"), 0, 1, '', $mainmenu, 'modulesadmintools');
	       	if ($leftmenu=="modulesadmintools" && $user->admin)
	       	{
	    		$langs->load("products");
	       		$newmenu->add("/product/admin/product_tools.php?mainmenu=home&leftmenu=modulesadmintools", $langs->trans("ProductVatMassChange"), 1, $user->admin);
	      	}
    	}
    }

    /**
     * We update newmenu with entries found into database
     * --------------------------------------------------
     */
    if ($mainmenu)
    {
        require_once DOL_DOCUMENT_ROOT.'/core/class/menubase.class.php';

        $tabMenu=array();
        $menuArbo = new Menubase($db,'auguria','left');
        $newmenu = $menuArbo->menuLeftCharger($newmenu,$mainmenu,$leftmenu,($user->societe_id?1:0),'auguria',$tabMenu);
        //var_dump($newmenu);
    }


    //var_dump($menu_array_before);exit;
    //var_dump($menu_array_after);exit;
    $menu_array=$newmenu->liste;
    if (is_array($menu_array_before)) $menu_array=array_merge($menu_array_before, $menu_array);
    if (is_array($menu_array_after))  $menu_array=array_merge($menu_array, $menu_array_after);
    //var_dump($menu_array);exit;

    // Show menu
    $alt=0;
    if (is_array($menu_array))
    {
        $num=count($menu_array);
    	for ($i = 0; $i < $num; $i++)
        {
            $alt++;
            if (empty($menu_array[$i]['level']))
            {
                if (($alt%2==0))
                {
                	if ($conf->use_javascript_ajax && ! empty($conf->global->MAIN_MENU_USE_JQUERY_ACCORDION))
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
            $url=dol_buildpath($menu_array[$i]['url'],1);

            if (! preg_match('/mainmenu=/i',$menu_array[$i]['url']))
            {
                if (! preg_match('/\?/',$url)) $url.='?';
                else $url.='&';
                $url.='mainmenu='.$mainmenu;
            }

            print '<!-- Add menu entry with mainmenu='.$menu_array[$i]['mainmenu'].', leftmenu='.$menu_array[$i]['leftmenu'].', level='.$menu_array[$i]['mainmenu'].' -->'."\n";

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
                print "\n".'<div id="section_content_'.$i.'">'."\n";
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

    return count($menu_array);
}


/**
 * Function to test if an entry is enabled or not
 *
 * @param	string		$type_user					0=We need backoffice menu, 1=We need frontoffice menu
 * @param	array		&$menuentry					Array for menu entry
 * @param	array		&$listofmodulesforexternal	Array with list of modules allowed to external users
 * @return	int										0=Hide, 1=Show, 2=Show gray
 */
function dol_auguria_showmenu($type_user, &$menuentry, &$listofmodulesforexternal)
{
	//print 'type_user='.$type_user.' module='.$menuentry['module'].' enabled='.$menuentry['enabled'].' perms='.$menuentry['perms'];
	//print 'ok='.in_array($menuentry['module'], $listofmodulesforexternal);

	if (empty($menuentry['enabled'])) return 0;	// Entry disabled by condition
	if ($type_user && $menuentry['module'])
	{
		$tmploops=explode('|',$menuentry['module']);
		$found=0;
		foreach($tmploops as $tmploop)
		{
		 	if (in_array($tmploop, $listofmodulesforexternal)) { $found++; break; }
		}
		if (! $found) return 0;	// Entry is for menus all excluded to external users
	}
	if (! $menuentry['perms'] && $type_user) return 0; 											// No permissions and user is external
	if (! $menuentry['perms'] && ! empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED))	return 0;	// No permissions and option to hide when not allowed, even for internal user, is on
	if (! $menuentry['perms']) return 2;															// No permissions and user is external
	return 1;
}

?>
