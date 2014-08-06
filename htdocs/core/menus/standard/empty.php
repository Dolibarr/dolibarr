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

	var $menu;
	var $menu_array_after;


	/**
	 *  Constructor
	 *
	 *  @param	DoliDB		$db     		Database handler
     *  @param	int			$type_user		Type of user
	 */
	function __construct($db, $type_user)
	{
		$this->type_user=$type_user;
		$this->db=$db;
	}


	/**
	 * Load this->tabMenu
	 *
	 * @return	void
	 */
	function loadMenu()
	{

	}


	/**
	 *  Show menu
	 *
     *	@param	string	$mode			'top', 'left', 'jmobile'
     *  @return	void
	 */
	function showmenu($mode)
	{
		global $user,$conf,$langs,$dolibarr_main_db_name;

		$id='mainmenu';

		require_once DOL_DOCUMENT_ROOT.'/core/class/menu.class.php';
		$this->menu=new Menu();

		$res='ErrorBadParameterForMode';

		$noout=0;
		if ($mode == 'jmobile') $noout=1;

		if ($mode == 'top' || $mode == 'jmobile')
		{
			if (empty($noout)) print_start_menu_array_empty();

			// Home
			$showmode=1;
			$idsel='home';
			$classname='class="tmenusel"';

			if (empty($noout)) print_start_menu_entry_empty($idsel, $classname, $showmode);
			if (empty($noout)) print_text_menu_entry_empty($langs->trans("Home"), 1, dol_buildpath('/index.php',1).'?mainmenu=home&amp;leftmenu=', $id, $idsel, $classname, $this->atarget);
			if (empty($noout)) print_end_menu_entry_empty($showmode);
			$this->menu->add(dol_buildpath('/index.php',1), $langs->trans("Home"), 0, $showmode, $this->atarget, 'home', '');

			$showmode=1;
			if (empty($noout)) print_start_menu_entry_empty('','class="tmenuend"',$showmode);
			if (empty($noout)) print_end_menu_entry_empty($showmode);

			if (empty($noout)) print_end_menu_array_empty();

			if ($mode == 'jmobile')
			{
				$this->topmenu=dol_clone($this->menu);
				unset($this->menu->liste);
			}
		}

		if ($mode == 'left' || $mode == 'jmobile')
		{
			// Put here left menu entries
			// ***** START *****

			$langs->load("admin");  // Load translation file admin.lang
			$this->menu->add("/admin/index.php?leftmenu=setup", $langs->trans("Setup"),0);
			$this->menu->add("/admin/company.php", $langs->trans("MenuCompanySetup"),1);
			$this->menu->add("/admin/modules.php", $langs->trans("Modules"),1);
			$this->menu->add("/admin/menus.php", $langs->trans("Menus"),1);
			$this->menu->add("/admin/ihm.php", $langs->trans("GUISetup"),1);
			$this->menu->add("/admin/boxes.php", $langs->trans("Boxes"),1);
			$this->menu->add("/admin/delais.php",$langs->trans("Alerts"),1);
			$this->menu->add("/admin/proxy.php?mainmenu=home", $langs->trans("Security"),1);
			$this->menu->add("/admin/limits.php?mainmenu=home", $langs->trans("MenuLimits"),1);
			$this->menu->add("/admin/pdf.php?mainmenu=home", $langs->trans("PDF"),1);
			$this->menu->add("/admin/mails.php?mainmenu=home", $langs->trans("Emails"),1);
			$this->menu->add("/admin/sms.php?mainmenu=home", $langs->trans("SMS"),1);
			$this->menu->add("/admin/dict.php?mainmenu=home", $langs->trans("DictionnarySetup"),1);
			$this->menu->add("/admin/const.php?mainmenu=home", $langs->trans("OtherSetup"),1);

			// ***** END *****

			// do not change code after this

			if (empty($noout))
			{
				$alt=0;
				$num=count($this->menu->liste);
				for ($i = 0; $i < $num; $i++)
				{
					$alt++;
					if (empty($this->menu->liste[$i]['level']))
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
					$tabul=($this->menu->liste[$i]['level'] - 1);
					if ($tabul > 0)
					{
						for ($j=0; $j < $tabul; $j++)
						{
							$tabstring.='&nbsp; &nbsp;';
						}
					}

					if ($this->menu->liste[$i]['level'] == 0) {
						if ($this->menu->liste[$i]['enabled'])
						{
							print '<div class="menu_titre">'.$tabstring.'<a class="vmenu" href="'.dol_buildpath($this->menu->liste[$i]['url'],1).'"'.($this->menu->liste[$i]['target']?' target="'.$this->menu->liste[$i]['target'].'"':'').'>'.$this->menu->liste[$i]['titre'].'</a></div>'."\n";
						}
						else
						{
							print '<div class="menu_titre">'.$tabstring.'<font class="vmenudisabled">'.$this->menu->liste[$i]['titre'].'</font></div>'."\n";
						}
						print '<div class="menu_top"></div>'."\n";
					}

					if ($this->menu->liste[$i]['level'] > 0) {
						print '<div class="menu_contenu">';

						if ($this->menu->liste[$i]['enabled'])
							print $tabstring.'<a class="vsmenu" href="'.dol_buildpath($this->menu->liste[$i]['url'],1).'">'.$this->menu->liste[$i]['titre'].'</a><br>';
						else
							print $tabstring.'<font class="vsmenudisabled vsmenudisabledmargin">'.$this->menu->liste[$i]['titre'].'</font><br>';

						print '</div>'."\n";
					}

					// If next is a new block or end
					if (empty($this->menu->liste[$i+1]['level']))
					{
						print '<div class="menu_end"></div>'."\n";
						print "</div>\n";
					}
				}
			}

			if ($mode == 'jmobile')
			{
				$this->leftmenu=dol_clone($this->menu);
				unset($this->menu->liste);
			}
		}

		if ($mode == 'jmobile')
		{
			foreach($this->topmenu->liste as $key => $val)		// $val['url','titre','level','enabled'=0|1|2,'target','mainmenu','leftmenu'
			{
				print '<ul class="ulmenu" data-role="listview" data-inset="true">';
				print '<li data-role="list-dividerxxx" class="lilevel0">';
				if ($val['enabled'] == 1)
				{
					$relurl=dol_buildpath($val['url'],1);
					$relurl=preg_replace('/__LOGIN__/',$user->login,$relurl);
					$relurl=preg_replace('/__USERID__/',$user->id,$relurl);

					print '<a href="#">'.$val['titre'].'</a>'."\n";
					// Search submenu fot this entry
					$tmpmainmenu=$val['mainmenu'];
					$tmpleftmenu='all';
					//$submenu=new Menu();
					//$res=print_left_eldy_menu($this->db,$this->menu_array,$this->menu_array_after,$this->tabMenu,$submenu,1,$tmpmainmenu,$tmpleftmenu);
					//$nexturl=dol_buildpath($submenu->liste[0]['url'],1);
					$submenu=$this->leftmenu;

					$canonrelurl=preg_replace('/\?.*$/','',$relurl);
					$canonnexturl=preg_replace('/\?.*$/','',$nexturl);
					//var_dump($canonrelurl);
					//var_dump($canonnexturl);
					print '<ul>';
        			if ($canonrelurl != $canonnexturl && ! in_array($val['mainmenu'],array('home','tools')))
					{
						// We add sub entry
						print '<li data-role="list-dividerxxx"><a href="'.$relurl.'">'.$langs->trans("MainArea").'-'.$val['titre'].'</a></li>'."\n";
					}
					foreach($submenu->liste as $key2 => $val2)		// $val['url','titre','level','enabled'=0|1|2,'target','mainmenu','leftmenu'
					{
						$relurl2=dol_buildpath($val2['url'],1);
						$relurl2=preg_replace('/__LOGIN__/',$user->login,$relurl2);
						$relurl2=preg_replace('/__USERID__/',$user->id,$relurl2);
						//var_dump($val2);
						print '<li'.($val2['level']==0?' data-role="list-dividerxxx"':'').'><a href="'.$relurl2.'">'.$val2['titre'].'</a></li>'."\n";
					}
					//var_dump($submenu);
					print '</ul>';
				}
				if ($val['enabled'] == 2)
				{
					print '<font class="vsmenudisabled">'.$val['titre'].'</font>';
				}
				print '</li>';
				print '</ul>'."\n";

				break;	// Only first menu entry (so home)
			}
		}

		unset($this->menu);

		return $res;
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
 * @param	int		$showmode	0 = hide, 1 = allowed or 2 = not allowed
 * @return	void
 */
function print_start_menu_entry_empty($idsel,$classname,$showmode)
{
	if ($showmode)
	{
		print '<li '.$classname.' id="mainmenutd_'.$idsel.'">';
		print '<div class="tmenuleft"></div><div class="tmenucenter">';
	}
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
 * @return	void
 */
function print_text_menu_entry_empty($text, $showmode, $url, $id, $idsel, $classname, $atarget)
{
	global $conf;

	if ($showmode == 1)
	{
		print '<a class="tmenuimage" href="'.$url.'"'.($atarget?' target="'.$atarget.'"':'').'>';
		print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage" id="mainmenuspan_'.$idsel.'"></span></div>';
		print '</a>';
		print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.$url.'"'.($atarget?' target="'.$atarget.'"':'').'>';
		print '<span class="mainmenuaspan">';
		print $text;
		print '</span>';
		print '</a>';
	}
	if ($showmode == 2)
	{
		print '<div class="'.$id.' '.$idsel.' tmenudisabled"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
		print '<a class="tmenudisabled" id="mainmenua_'.$idsel.'" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">';
	}
}

/**
 * Output end menu entry
 *
 * @param	int		$showmode	0 = hide, 1 = allowed or 2 = not allowed
 * @return	void
 */
function print_end_menu_entry_empty($showmode)
{
	if ($showmode)
	{
		print '</div></li>';
		print "\n";
	}
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
