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
	/**
     * @var DoliDB Database handler.
     */
    public $db;

    public $type_user=0;					// Put 0 for internal users, 1 for external users
    public $atarget="";               		// To store default target to use onto links

    public $menu;
    public $menu_array_after;


	/**
	 *  Constructor
	 *
	 *  @param	DoliDB		$db     		Database handler
     *  @param	int			$type_user		Type of user
	 */
	public function __construct($db, $type_user)
	{
		$this->type_user=$type_user;
		$this->db=$db;
	}


	/**
	 * Load this->tabMenu
	 *
	 * @return	void
	 */
	public function loadMenu()
	{
	}


	/**
	 *  Show menu
	 *
     *	@param	string	$mode			'top', 'left', 'jmobile'
     *  @param	array	$moredata		An array with more data to output
     *  @return int                     0 or nb of top menu entries if $mode = 'topnb'
	 */
	public function showmenu($mode, $moredata = null)
	{
		global $user,$conf,$langs,$dolibarr_main_db_name;

		$id='mainmenu';

		require_once DOL_DOCUMENT_ROOT.'/core/class/menu.class.php';
		$this->menu=new Menu();

		$res='ErrorBadParameterForMode';

		$noout=0;
		//if ($mode == 'jmobile') $noout=1;

		if ($mode == 'topnb')
		{
		    return 1;
		}

		if ($mode == 'top')
		{
			if (empty($noout)) print_start_menu_array_empty();

            $usemenuhider = 1;

			// Show/Hide vertical menu
			if ($mode != 'jmobile' && $mode != 'topnb' && $usemenuhider &&  empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
			{
			    $showmode=1;
			    $classname = 'class="tmenu menuhider"';
			    $idsel='menu';

			    $this->menu->add('#', '', 0, $showmode, $atarget, "xxx", '', 0, $id, $idsel, $classname);
			}

			// Home
			$showmode=1;
			$classname='class="tmenusel"';
			$idsel='home';

			$this->menu->add('/index.php', $langs->trans("Home"), 0, $showmode, $this->atarget, 'home', '', 10, $id, $idsel, $classname);


			// Sort on position
			$this->menu->liste = dol_sort_array($this->menu->liste, 'position');

			// Output menu entries
			foreach($this->menu->liste as $menkey => $menuval)
			{
			    if (empty($noout)) print_start_menu_entry_empty($menuval['idsel'], $menuval['classname'], $menuval['enabled']);
			    if (empty($noout)) print_text_menu_entry_empty($menuval['titre'], $menuval['enabled'], ($menuval['url']!='#'?DOL_URL_ROOT:'').$menuval['url'], $menuval['id'], $menuval['idsel'], $menuval['classname'], ($menuval['target']?$menuval['target']:$atarget));
			    if (empty($noout)) print_end_menu_entry_empty($menuval['enabled']);
			}

			$showmode=1;
			if (empty($noout)) print_start_menu_entry_empty('', 'class="tmenuend"', $showmode);
			if (empty($noout)) print_end_menu_entry_empty($showmode);

			if (empty($noout)) print_end_menu_array_empty();

			if ($mode == 'jmobile')
			{
				$this->topmenu = clone $this->menu;
				unset($this->menu->liste);
			}
		}

		if ($mode == 'jmobile')     // Used to get menu in xml ul/li
		{
		    // Home
		    $showmode=1;
		    $classname='class="tmenusel"';
		    $idsel='home';

		    $this->menu->add('/index.php', $langs->trans("Home"), 0, $showmode, $this->atarget, 'home', '', 10, $id, $idsel, $classname);


		    // $this->menu->liste is top menu
		    //var_dump($this->menu->liste);exit;
		    $lastlevel = array();
		    print '<!-- Generate menu list from menu handler '.$this->name.' -->'."\n";
		    foreach($this->menu->liste as $key => $val)		// $val['url','titre','level','enabled'=0|1|2,'target','mainmenu','leftmenu'
		    {
		        print '<ul class="ulmenu" data-inset="true">';
		        print '<li class="lilevel0">';

		        $substitarray = array('__LOGIN__' => $user->login, '__USER_ID__' => $user->id, '__USER_SUPERVISOR_ID__' => $user->fk_user);
		        $substitarray['__USERID__'] = $user->id;	// For backward compatibility
		        $val['url'] = make_substitutions($val['url'], $substitarray);

		        if ($val['enabled'] == 1)
		        {
		            $relurl=dol_buildpath($val['url'], 1);
		            $canonurl=preg_replace('/\?.*$/', '', $val['url']);

		            print '<a class="alilevel0" href="#">';

		            // Add font-awesome
		            if ($val['level'] == 0 && $val['mainmenu'] == 'home') print '<span class="fa fa-home fa-fw paddingright" aria-hidden="true"></span>';

		            print $val['titre'];
		            print '</a>'."\n";

		            // Search submenu fot this mainmenu entry
		            $tmpmainmenu=$val['mainmenu'];
		            $tmpleftmenu='all';
		            $submenu=new Menu();

		            $langs->load("admin");  // Load translation file admin.lang
		            $submenu->add("/admin/index.php?leftmenu=setup", $langs->trans("Setup"), 0);
		            $submenu->add("/admin/company.php", $langs->trans("MenuCompanySetup"), 1);
		            $submenu->add("/admin/modules.php", $langs->trans("Modules"), 1);
		            $submenu->add("/admin/menus.php", $langs->trans("Menus"), 1);
		            $submenu->add("/admin/ihm.php", $langs->trans("GUISetup"), 1);
		            $submenu->add("/admin/translation.php?mainmenu=home", $langs->trans("Translation"), 1);
		            $submenu->add("/admin/defaultvalues.php?mainmenu=home", $langs->trans("DefaultValues"), 1);

		            $submenu->add("/admin/boxes.php", $langs->trans("Boxes"), 1);
		            $submenu->add("/admin/delais.php", $langs->trans("Alerts"), 1);
		            $submenu->add("/admin/proxy.php?mainmenu=home", $langs->trans("Security"), 1);
		            $submenu->add("/admin/limits.php?mainmenu=home", $langs->trans("MenuLimits"), 1);
		            $submenu->add("/admin/pdf.php?mainmenu=home", $langs->trans("PDF"), 1);
		            $submenu->add("/admin/mails.php?mainmenu=home", $langs->trans("Emails"), 1);
		            $submenu->add("/admin/sms.php?mainmenu=home", $langs->trans("SMS"), 1);
		            $submenu->add("/admin/dict.php?mainmenu=home", $langs->trans("DictionarySetup"), 1);
		            $submenu->add("/admin/const.php?mainmenu=home", $langs->trans("OtherSetup"), 1);

		            //if ($tmpmainmenu.'-'.$tmpleftmenu == 'home-all') { var_dump($submenu); exit; }
		            //if ($tmpmainmenu=='accountancy') { var_dump($submenu->liste); exit; }
		            $nexturl=dol_buildpath($submenu->liste[0]['url'], 1);

		            $canonrelurl=preg_replace('/\?.*$/', '', $relurl);
		            $canonnexturl=preg_replace('/\?.*$/', '', $nexturl);
		            //var_dump($canonrelurl);
		            //var_dump($canonnexturl);
		            print '<ul>'."\n";
		            if (($canonrelurl != $canonnexturl && ! in_array($val['mainmenu'], array('tools')))
		                || (strpos($canonrelurl, '/product/index.php') !== false || strpos($canonrelurl, '/compta/bank/list.php') !== false))
		            {
		                // We add sub entry
		                print str_pad('', 1).'<li class="lilevel1 ui-btn-icon-right ui-btn">';	 // ui-btn to highlight on clic
		                print '<a href="'.$relurl.'">';
		                if ($langs->trans(ucfirst($val['mainmenu'])."Dashboard") == ucfirst($val['mainmenu'])."Dashboard")  // No translation
		                {
		                    if (in_array($val['mainmenu'], array('cashdesk', 'websites'))) print $langs->trans("Access");
		                    else print $langs->trans("Dashboard");
		                }
		                else print $langs->trans(ucfirst($val['mainmenu'])."Dashboard");
		                print '</a>';
		                print '</li>'."\n";
		            }

		            if ($val['level']==0)
		            {
		                if ($val['enabled'])
		                {
		                    $lastlevel[0]='enabled';
		                }
		                elseif ($showmenu)                 // Not enabled but visible (so greyed)
		                {
		                    $lastlevel[0]='greyed';
		                }
		                else
		                {
		                    $lastlevel[0]='hidden';
		                }
		            }

		            $lastlevel2 = array();
		            foreach($submenu->liste as $key2 => $val2)		// $val['url','titre','level','enabled'=0|1|2,'target','mainmenu','leftmenu'
		            {
		                $showmenu=true;
		                if (! empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED) && empty($val2['enabled'])) $showmenu=false;

		                // If at least one parent is not enabled, we do not show any menu of all children
		                if ($val2['level'] > 0)
		                {
		                    $levelcursor = $val2['level']-1;
		                    while ($levelcursor >= 0)
		                    {
		                        if ($lastlevel2[$levelcursor] != 'enabled') $showmenu=false;
		                        $levelcursor--;
		                    }
		                }

		                if ($showmenu)		// Visible (option to hide when not allowed is off or allowed)
		                {
		                	$substitarray = array('__LOGIN__' => $user->login, '__USER_ID__' => $user->id, '__USER_SUPERVISOR_ID__' => $user->fk_user);
		                	$substitarray['__USERID__'] = $user->id;	// For backward compatibility
		                	$val2['url'] = make_substitutions($val2['url'], $substitarray);

		                    $relurl2=dol_buildpath($val2['url'], 1);
		                    $canonurl2=preg_replace('/\?.*$/', '', $val2['url']);
		                    //var_dump($val2['url'].' - '.$canonurl2.' - '.$val2['level']);
		                    if (in_array($canonurl2, array('/admin/index.php','/admin/tools/index.php','/core/tools.php'))) $relurl2='';

		                    $disabled='';
		                    if (! $val2['enabled'])
		                    {
		                        $disabled=" vsmenudisabled";
		                    }

		                    print str_pad('', $val2['level']+1);
		                    print '<li class="lilevel'.($val2['level']+1);
		                    if ($val2['level']==0) print ' ui-btn-icon-right ui-btn';  // ui-btn to highlight on clic
		                    print $disabled.'">';	 // ui-btn to highlight on clic
		                    if ($relurl2)
		                    {
		                        if ($val2['enabled'])	// Allowed
		                        {
		                            print '<a href="'.$relurl2.'"';
		                            //print ' data-ajax="false"';
		                            print '>';
		                            $lastlevel2[$val2['level']]='enabled';
		                        }
		                        else					// Not allowed but visible (greyed)
		                        {
		                            print '<a href="#" class="vsmenudisabled">';
		                            $lastlevel2[$val2['level']]='greyed';
		                        }
		                    }
		                    else
		                    {
		                        if ($val2['enabled'])	// Allowed
		                        {
		                            $lastlevel2[$val2['level']]='enabled';
		                        }
		                        else
		                        {
		                            $lastlevel2[$val2['level']]='greyed';
		                        }
		                    }
		                    //var_dump($val2['level']);
		                    //var_dump($lastlevel2);
		                    print $val2['titre'];
		                    if ($relurl2)
		                    {
		                        if ($val2['enabled'])	// Allowed
		                            print '</a>';
		                            else
		                                print '</a>';
		                    }
		                    print '</li>'."\n";
		                }
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
		    }
		}

		if ($mode == 'left')
		{
			// Put here left menu entries
			// ***** START *****

			$langs->load("admin");  // Load translation file admin.lang
			$this->menu->add("/admin/index.php?leftmenu=setup", $langs->trans("Setup"), 0);
			$this->menu->add("/admin/company.php", $langs->trans("MenuCompanySetup"), 1);
			$this->menu->add("/admin/modules.php", $langs->trans("Modules"), 1);
			$this->menu->add("/admin/menus.php", $langs->trans("Menus"), 1);
			$this->menu->add("/admin/ihm.php", $langs->trans("GUISetup"), 1);
			$this->menu->add("/admin/translation.php?mainmenu=home", $langs->trans("Translation"), 1);
			$this->menu->add("/admin/defaultvalues.php?mainmenu=home", $langs->trans("DefaultValues"), 1);

		    $this->menu->add("/admin/boxes.php", $langs->trans("Boxes"), 1);
			$this->menu->add("/admin/delais.php", $langs->trans("Alerts"), 1);
			$this->menu->add("/admin/proxy.php?mainmenu=home", $langs->trans("Security"), 1);
			$this->menu->add("/admin/limits.php?mainmenu=home", $langs->trans("MenuLimits"), 1);
			$this->menu->add("/admin/pdf.php?mainmenu=home", $langs->trans("PDF"), 1);
			$this->menu->add("/admin/mails.php?mainmenu=home", $langs->trans("Emails"), 1);
			$this->menu->add("/admin/sms.php?mainmenu=home", $langs->trans("SMS"), 1);
			$this->menu->add("/admin/dict.php?mainmenu=home", $langs->trans("DictionarySetup"), 1);
			$this->menu->add("/admin/const.php?mainmenu=home", $langs->trans("OtherSetup"), 1);

			// ***** END *****

			// do not change code after this

			if (empty($noout))
			{
				$alt=0; $altok=0; $blockvmenuopened=false;
				$num=count($this->menu->liste);
				for ($i = 0; $i < $num; $i++)
				{
					$alt++;
					if (empty($this->menu->liste[$i]['level']))
					{
			    		$altok++;
    					$blockvmenuopened=true;
						$lastopened=true;
        				for($j = ($i + 1); $j < $num; $j++)
        				{
        				    if (empty($menu_array[$j]['level'])) $lastopened=false;
        				}
        				$alt = 0;   // For menu manager "empty", we force to not have blockvmenufirst defined
        				$lastopened = 1; // For menu manager "empty", we force to not have blockvmenulast defined
						if (($alt%2==0))
						{
							print '<div class="blockvmenub lockvmenuimpair blockvmenuunique'.($lastopened?' blockvmenulast':'').($alt == 1 ? ' blockvmenufirst':'').'">'."\n";
						}
						else
						{
							print '<div class="blockvmenu blockvmenupair blockvmenuunique'.($lastopened?' blockvmenulast':'').($alt == 1 ? ' blockvmenufirst':'').'">'."\n";
						}
					}

					// Add tabulation
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
							print '<div class="menu_titre">'.$tabstring.'<a class="vmenu" href="'.dol_buildpath($this->menu->liste[$i]['url'], 1).'"'.($this->menu->liste[$i]['target']?' target="'.$this->menu->liste[$i]['target'].'"':'').'>'.$this->menu->liste[$i]['titre'].'</a></div>'."\n";
						}
						else
						{
							print '<div class="menu_titre">'.$tabstring.'<font class="vmenudisabled">'.$this->menu->liste[$i]['titre'].'</font></div>'."\n";
						}
						print '<div class="menu_top"></div>'."\n";
					}

					if ($this->menu->liste[$i]['level'] > 0)
					{
        				$cssmenu = '';
        				if ($this->menu->liste[$i]['url']) $cssmenu = ' menu_contenu'.dol_string_nospecial(preg_replace('/\.php.*$/', '', $this->menu->liste[$i]['url']));

					    print '<div class="menu_contenu'.$cssmenu.'">';

						if ($this->menu->liste[$i]['enabled'])
						{
							print $tabstring;
							if ($this->menu->liste[$i]['url']) print '<a class="vsmenu" href="'.dol_buildpath($this->menu->liste[$i]['url'], 1).'"'.($this->menu->liste[$i]['target']?' target="'.$this->menu->liste[$i]['target'].'"':'').'>';
							else print '<span class="vsmenu">';
							if ($this->menu->liste[$i]['url']) print $this->menu->liste[$i]['titre'].'</a>';
							else print '</span>';
						}
						else
						{
							print $tabstring.'<font class="vsmenudisabled vsmenudisabledmargin">'.$this->menu->liste[$i]['titre'].'</font>';
						}

						// If title is not pure text and contains a table, no carriage return added
						if (! strstr($this->menu->liste[$i]['titre'], '<table')) print '<br>';
						print '</div>'."\n";
					}

					// If next is a new block or end
					if (empty($this->menu->liste[$i+1]['level']))
					{
						print '<div class="menu_end"></div>'."\n";
						print "</div>\n";
					}
				}

				if ($altok) print '<div class="blockvmenuend"></div>';
			}

			if ($mode == 'jmobile')
			{
				$this->leftmenu = clone $this->menu;
				unset($this->menu->liste);
			}
		}
/*
		if ($mode == 'jmobile')
		{
			foreach($this->menu->liste as $key => $val)		// $val['url','titre','level','enabled'=0|1|2,'target','mainmenu','leftmenu'
			{
				print '<ul class="ulmenu" data-inset="true">';
				print '<li class="lilevel0">';

		        $substitarray = array('__LOGIN__' => $user->login, '__USER_ID__' => $user->id, '__USER_SUPERVISOR_ID__' => $user->fk_user);
		        $substitarray['__USERID__'] = $user->id;	// For backward compatibility
		        $val['url'] = make_substitutions($val['url'], $substitarray);

				if ($val['enabled'] == 1)
				{
					$relurl=dol_buildpath($val['url'],1);

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
						print '<li><a href="'.$relurl.'">'.$langs->trans("MainArea").'-'.$val['titre'].'</a></li>'."\n";
					}
					foreach($submenu->liste as $key2 => $val2)		// $val['url','titre','level','enabled'=0|1|2,'target','mainmenu','leftmenu'
					{
	                	$substitarray = array('__LOGIN__' => $user->login, '__USER_ID__' => $user->id, '__USER_SUPERVISOR_ID__' => $user->fk_user);
	                	$substitarray['__USERID__'] = $user->id;	// For backward compatibility
	                	$val2['url'] = make_substitutions($val2['url'], $substitarray);

						$relurl2=dol_buildpath($val2['url'],1);
						//var_dump($val2);
						print '<li><a href="'.$relurl2.'">'.$val2['titre'].'</a></li>'."\n";
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
*/
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
    global $conf;

	print '<div class="tmenudiv">';
	print '<ul role="navigation" class="tmenu"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)?'':' title="Top menu"').'>';
}

/**
 * Output start menu entry
 *
 * @param	string	$idsel		Text
 * @param	string	$classname	String to add a css class
 * @param	int		$showmode	0 = hide, 1 = allowed or 2 = not allowed
 * @return	void
 */
function print_start_menu_entry_empty($idsel, $classname, $showmode)
{
	if ($showmode)
	{
		print '<li '.$classname.' id="mainmenutd_'.$idsel.'">';
		//print '<div class="tmenuleft tmenusep"></div>';
		print '<div class="tmenucenter">';
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
	global $conf,$langs;

	if ($showmode == 1)
	{
		print '<a class="tmenuimage" tabindex="-1" href="'.$url.'"'.($atarget?' target="'.$atarget.'"':'').'>';
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
