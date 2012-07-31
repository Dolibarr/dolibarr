<?php
/* Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/core/menus/standard/empty.php
 *		\brief      This is an example of an empty top menu handler
 */

/**
 *      \class      MenuTop
 *	    \brief      Class for top empty menu
 */
class MenuTop
{
	var $db;
    var $require_left=array("empty");   // If this top menu handler must be used with a particular left menu handler
    var $hideifnotallowed=false;		// Put 0 for back office menu, 1 for front office menu
    var $atarget="";               		// To store arget to use in menu links


    /**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db=$db;
    }


    /**
     *    Show menu
     *
     *    @return	void
     */
    function showmenu()
    {
        global $user,$conf,$langs,$dolibarr_main_db_name;;

        print_start_menu_array_empty();

		$idsel='home';
        $classname='class="tmenu"';

		print_start_menu_entry_empty($idsel);
		print '<a class="tmenuimage" href="'.dol_buildpath('/index.php',1).'?mainmenu=home&amp;leftmenu="'.($this->atarget?' target="'.$this->atarget.'"':'').'>';
		print '<div class="mainmenu '.$idsel.'"><span class="mainmenu_'.$idsel.' tmenuimage" id="mainmenuspan_'.$idsel.'"></span></div>';
		print '</a>';
		print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'"'.($this->atarget?' target="'.$this->atarget.'"':'').'>';
		print_text_menu_entry_empty($langs->trans("Home"));
		print '</a>';
		print_end_menu_entry_empty();

		print_end_menu_array_empty();
    }

}

/**
 * Output menu entry
 *
 * @return	void
 */
function print_start_menu_array_empty()
{
	global $conf;
	if (preg_match('/bluelagoon|eldy|freelug|rodolphe|yellow|dev/',$conf->css)) print '<table class="tmenu" summary="topmenu"><tr class="tmenu">';
	else print '<ul class="tmenu">';
}

/**
 * Output start menu entry
 *
 * @param	string	$idsel		Text
 * @return	void
 */
function print_start_menu_entry_empty($idsel)
{
	global $conf;
	if (preg_match('/bluelagoon|eldy|freelug|rodolphe|yellow|dev/',$conf->css)) print '<td class="tmenu" id="mainmenutd_'.$idsel.'">';
	else print '<li class="tmenu" id="mainmenutd_'.$idsel.'">';
}

/**
 * Output menu entry
 *
 * @param	string	$text		Text
 * @return	void
 */
function print_text_menu_entry_empty($text)
{
	global $conf;
	print '<span class="mainmenuaspan">';
	print $text;
	print '</span>';
}

/**
 * Output end menu entry
 *
 * @return	void
 */
function print_end_menu_entry_empty()
{
	global $conf;
	if (preg_match('/bluelagoon|eldy|freelug|rodolphe|yellow|dev/',$conf->css)) print '</td>';
	else print '</li>';
	print "\n";
}

/**
 * Output menu array
 *
 * @return	void
 */
function print_end_menu_array_empty()
{
	global $conf;
	if (preg_match('/bluelagoon|eldy|freelug|rodolphe|yellow|dev/',$conf->css)) print '</tr></table>';
	else print '</ul>';
	print "\n";
}



/**
 * 	Class for left empty menu
 */
class MenuLeft
{
    var $db;
    var $menu_array;
    var $menu_array_after;


    /**
     *  Constructor
     *
	 *  @param	DoliDB		$db     			Database handler
     *  @param  array		&$menu_array    	Table of menu entries to show before entries of menu handler
     *  @param  array		&$menu_array_after  Table of menu entries to show after entries of menu handler
     */
    function __construct($db,&$menu_array,&$menu_array_after)
    {
        $this->db=$db;
        $this->menu_array=$menu_array;
        $this->menu_array_after=$menu_array_after;
    }


    /**
     *  Show menu
     *
     *  @return	void
     */
    function showmenu()
    {
        global $user,$conf,$langs,$dolibarr_main_db_name;
        $newmenu = new Menu();

        // Put here left menu entries
        // ***** START *****

        $langs->load("admin");  // Load translation file admin.lang
        $newmenu->add("/admin/index.php?leftmenu=setup", $langs->trans("Setup"),0);
        $newmenu->add("/admin/company.php", $langs->trans("MenuCompanySetup"),1);
        $newmenu->add("/admin/modules.php", $langs->trans("Modules"),1);
        $newmenu->add("/admin/menus.php", $langs->trans("Menus"),1);
        $newmenu->add("/admin/ihm.php", $langs->trans("GUISetup"),1);
        $newmenu->add("/admin/boxes.php", $langs->trans("Boxes"),1);
        $newmenu->add("/admin/delais.php",$langs->trans("Alerts"),1);
        $newmenu->add("/admin/perms.php", $langs->trans("Security"),1);
        $newmenu->add("/admin/mails.php", $langs->trans("EMails"),1);
        $newmenu->add("/admin/limits.php", $langs->trans("Limits"),1);
        $newmenu->add("/admin/dict.php", $langs->trans("DictionnarySetup"),1);
        $newmenu->add("/admin/const.php", $langs->trans("OtherSetup"),1);

        // ***** END *****

        // do not change code after this

        // override menu_array by value array in $newmenu
        $this->menu_array=$newmenu->liste;

        $alt=0;
        $num=count($this->menu_array);
        for ($i = 0; $i < $num; $i++)
        {
            $alt++;
            if (empty($this->menu_array[$i]['level']))
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
            $tabul=($this->menu_array[$i]['level'] - 1);
            if ($tabul > 0)
            {
                for ($j=0; $j < $tabul; $j++)
                {
                    $tabstring.='&nbsp; &nbsp;';
                }
            }

            if ($this->menu_array[$i]['level'] == 0) {
                if ($this->menu_array[$i]['enabled'])
                {
                    print '<div class="menu_titre">'.$tabstring.'<a class="vmenu" href="'.dol_buildpath($this->menu_array[$i]['url'],1).'"'.($this->menu_array[$i]['target']?' target="'.$this->menu_array[$i]['target'].'"':'').'>'.$this->menu_array[$i]['titre'].'</a></div>'."\n";
                }
                else
                {
                    print '<div class="menu_titre">'.$tabstring.'<font class="vmenudisabled">'.$this->menu_array[$i]['titre'].'</font></div>'."\n";
                }
                print '<div class="menu_top"></div>'."\n";
            }

            if ($this->menu_array[$i]['level'] > 0) {
                print '<div class="menu_contenu">';

                if ($this->menu_array[$i]['enabled'])
                    print $tabstring.'<a class="vsmenu" href="'.dol_buildpath($this->menu_array[$i]['url'],1).'">'.$this->menu_array[$i]['titre'].'</a><br>';
                else
                    print $tabstring.'<font class="vsmenudisabled">'.$this->menu_array[$i]['titre'].'</font><br>';

                print '</div>'."\n";
            }

            // If next is a new block or end
            if (empty($this->menu_array[$i+1]['level']))
            {
                print '<div class="menu_end"></div>'."\n";
                print "</div>\n";
            }
        }
    }

}

?>