<?php
/* Copyright (C) 2006-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *	    \file       htdocs/core/menus/standard/empty.php
 *		\brief      This is an example of an empty top menu handler
 */

/**
 *	    Class to manage empty menu
 */
class MenuManager
{
	var $db;
	var $type_user=0;					// Put 0 for internal users, 1 for external users
	var $atarget="";               		// To store default target to use onto links

	var $menu_array;
	var $menu_array_after;


	/**
	 *  Constructor
	 *
	 *  @param	DoliDB		$db     			Database handler
	 */
	function __construct($db)
	{
		$this->db=$db;
	}


	/**
	 *  Show menu
	 *
     *	@param	string	$mode		'top' or 'left'
	 *  @return	void
	 */
	function showmenu($mode)
	{
		global $user,$conf,$langs,$dolibarr_main_db_name;

		$id='mainmenu';

		if ($mode == 'top')
		{
			print_start_menu_array_empty();

			$idsel='home';
			$classname='class="tmenusel"';

			print_start_menu_entry_empty($idsel, $classname);
			print_text_menu_entry_empty($langs->trans("Home"), 1, dol_buildpath('/index.php',1).'?mainmenu=home&amp;leftmenu=', $id, $idsel, $classname, $this->atarget);
			print_end_menu_entry_empty();

			print_end_menu_array_empty();
		}

		if ($mode == 'left')
		{
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
			$newmenu->add("/admin/proxy.php?mainmenu=home", $langs->trans("Security"),1);
			$newmenu->add("/admin/limits.php?mainmenu=home", $langs->trans("MenuLimits"),1);
			$newmenu->add("/admin/pdf.php?mainmenu=home", $langs->trans("PDF"),1);
			$newmenu->add("/admin/mails.php?mainmenu=home", $langs->trans("Emails"),1);
			$newmenu->add("/admin/sms.php?mainmenu=home", $langs->trans("SMS"),1);
			$newmenu->add("/admin/dict.php?mainmenu=home", $langs->trans("DictionnarySetup"),1);
			$newmenu->add("/admin/const.php?mainmenu=home", $langs->trans("OtherSetup"),1);

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
}


/**
 * Output menu entry
 *
 * @return	void
 */
function print_start_menu_array_empty()
{
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
function print_start_menu_entry_empty($idsel,$classname)
{
	print '<li '.$classname.' id="mainmenutd_'.$idsel.'">';
	print '<div class="tmenuleft"></div><div class="tmenucenter">';
}

/**
 * Output menu entry
 *
 * @param	string	$text		Text
 * @param	int		$showmode	1 or 2
 * @param	string	$url		Url
 * @param	string	$id			Id
 * @param	string	$idsel		Id sel
 * @param	string	$classname	Class name
 * @param	string	$atarget	Target
 * @param	string	$menutarget	Menu target (may be empty)
 * @return	void
 */
function print_text_menu_entry_empty($text, $showmode, $url, $id, $idsel, $classname, $atarget, $menutarget='')
{
	global $conf;

	if ($showmode == 1)
	{
		print '<a class="tmenuimage" href="'.$url.'"'.($menutarget?" target='".$menutarget."'":($atarget?' target="'.$atarget.'"':'')).'>';
		print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage" id="mainmenuspan_'.$idsel.'"></span></div>';
		print '</a>';
		print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.$url.'"'.($menutarget?" target='".$menutarget."'":($atarget?' target="'.$atarget.'"':'')).'>';
		print '<span class="mainmenuaspan">';
		print $text;
		print '</span>';
		print '</a>';
	}
	if ($showmode == 2)
	{
		print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
		print '<a class="tmenudisabled" id="mainmenua_'.$idsel.'" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">';
	}
}

/**
 * Output end menu entry
 *
 * @return	void
 */
function print_end_menu_entry_empty()
{
	print '</div></li>';
	print "\n";
}

/**
 * Output menu array
 *
 * @return	void
 */
function print_end_menu_array_empty()
{
	print '</ul>';
	print '</div>';
	print "\n";
}

?>
