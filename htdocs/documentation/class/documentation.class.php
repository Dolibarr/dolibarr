<?php
/* Copyright (C) 2024		Anthony Damhet		<a.damhet@progiseize.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/documentation/class/documentation.class.php
 *	\ingroup    documentation
 *	\brief      File of class to manage documentation
 */

/**
 *	Class to manage documentation
 */
class Documentation
{

	/**
	 * Views
	 * @var array
	 */
	public $view = array();

	/**
	 * Menu - Set in constructor in order to use dol_buildpath
	 * @var array
	 */
	public $menu = array();	

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;
	

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;

		$this->menu = array(
			'BackToDolibarr' => array('url' => DOL_URL_ROOT, 'icon' => 'fas fa-arrow-left','submenu' => array()),
			'DocumentationHome' => array('url' => dol_buildpath('documentation/index.php',1), 'icon' => 'fas fa-book','submenu' => array()),			
			'Elements' => array('url' => dol_buildpath('documentation/ux/index.php',1), 'icon' => 'fas fa-th-large','submenu' => array(
				'Badges' => array('url' => dol_buildpath('documentation/ux/badges.php',1), 'icon' => 'fas fa-certificate','submenu' => array()),
				'Buttons' => array('url' => dol_buildpath('documentation/ux/buttons.php',1), 'icon' => 'fas fa-mouse','submenu' => array()),
			)),
		);
	}

	/**
	 *	Output header + body
	 *
	 *  @param	string		$title		Title of page
	 *  @return string		Documentation header
	 */
	public function docHeader($title = '')
	{
		global $langs; 		
		$title = (!empty($title)) ? dol_escape_htmltag($title) : $langs->trans('Documentation');

		print '<!DOCTYPE html>';
		print '<html lang="'.substr($langs->defaultlang, 0, 2).'">';
		print '<head>';
			print '<meta charset="utf-8">';
			print '<meta name="robots" content="noindex,nofollow">';
			print '<meta name="viewport" content="width=device-width, initial-scale=1">';
			print '<meta name="author" content="Dolibarr Development Team">';
			print '<title>'.$title.'</title>';
			print '<link rel="stylesheet" type="text/css" href="'.dol_buildpath('documentation/css/documentation.css',1).'">';
			print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/theme/common/fontawesome-5/css/all.min.css">';
			print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/theme/eldy/style.css.php">';
		print '</head>';
		print '<body class="dolibarr-doc">';
	}

	/**
	 *	Output close body + html
	 *
	 *  @return string		Documentation Footer
	 */
	public function docFooter()
	{
		print '<div id="documentation-scrollwrapper">';
			print '<div id="documentation-scroll"></div>';
		print '</div>';
		print '<script src="'.dol_buildpath('documentation/js/documentation.js',1).'"></script>';
		print '</body>';
		print '</html>';
	}

	/**
	 *	Output sidebar
	 *
	 *  @return string		Documentation Sidebar
	 */
	public function showSidebar()
	{
		print '<div class="doc-sidebar">';

			// LOGO
			print '<div class="sidebar-logo">';
				if (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.svg')){
		     		//print '<img src="'.DOL_URL_ROOT.'/theme/dolibarr_logo.svg'.'" />';
				}
			print '</div>';

			// NAVIGATION
			print '<nav>';
				if(!empty($this->menu)){
					$this->displayMenu($this->menu);
				}
			print '</nav>';

		print '</div>';
	}

	/**
	 *	Recursive function to set Menu
	 * 
	 *  @param		array		$menu      $this->menu or submenus
	 *  @param		int			$level     level of menu
	 *  @return 	string		Documentation Menu
	 */
	private function displayMenu($menu, $level = 0)
	{
		global $langs;
		$level++;

		print '<ul>';
		foreach ($menu as $key => $item) {
			$levelclass = (!empty($item['submenu'])) ? 'li-withsubmenu' : '';
			$levelclass .= (in_array($key, $this->view)) ? ' active' : '';

			print '<li class="'.$levelclass.' level-'.$level.'">';
				print '<a href="'.$item['url'].'" class="'.((!empty($item['submenu'])) ? 'link-withsubmenu' : '').'">';
					print ((!empty($item['icon'])) ? '<i class="menu-icon '.$item['icon'].'" aria-hidden="true"></i>' : '');
					print '<span class="label">'.$langs->transnoentities($key).'</span>';
					print ((!empty($item['submenu'])) ? '<i class="submenu-toggle fas fa-chevron-right" aria-hidden="true"></i>' : '');
				print '</a>';
				if (!empty($item['submenu'])) {
                	$this->displayMenu($item['submenu'],$level); // Appel récursif pour afficher les sous-menus
            	}
            echo '</li>';
        }
		print '</ul>';
	}
	
	/**
	 *	Output breadcrumb
	 *
	 *  @return string		Documentation Breadcrumb
	 */
	public function showBreadcrumb()
	{
		global $langs;

		print '<nav class="doc-breadcrumbs">';
			print '<ul>';
				print '<li class="breadcrumb-item"><a href="'.$this->menu['DocumentationHome']['url'].'"><i class="'.$this->menu['DocumentationHome']['icon'].'" aria-hidden="true"></i></a></li>';
				if(!empty($this->view)){
					$nb_entries = count($this->view);
					$i = 0;
					foreach ($this->view as $page) {
						$i++;
						if($i < $nb_entries && isset($this->menu[$page])){
							print '<li class="breadcrumb-item"><a href="'.$this->menu[$page]['url'].'">'.$langs->transnoentities($page).'</a></li>';
						} else {
							print '<li class="breadcrumb-item">'.$langs->transnoentities($page).'</li>';
						}
						
					}
				} else {
					print '<li class="breadcrumb-item">'.$langs->trans('Documentation').'</li>';
				}
			print '</ul>';
		print '</nav>';
	}

	/**
	 *	Output a View Code area
	 *
	 *  @param	array		$lines 		Lines of code to show
	 *  @return string		Area of code
	 */
	public function showCode($lines = array())
	{
		print '<div class="documentation-code"><pre>';
		foreach($lines as $lineofcode){
			print dol_htmlentities($lineofcode).'<br/>';
		}		
		print '</pre></div>';
	}
}
