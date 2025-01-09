<?php
/* Copyright (C) 2024        Anthony Damhet        <a.damhet@progiseize.fr>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *    \file       htdocs/admint/tools/ui/class/uidoc.class.php
 *    \ingroup    ui
 *    \brief      File of class to manage UI documentation
 */

/**
 *    Class to manage UI documentation
 */
class Documentation
{
	/**
	 * Views
	 *
	 * @var array
	 */
	public $view = array();

	/**
	 * Menu - Set in setMenu in order to use dol_buildpath and called in constructor
	 *
	 * @var array
	 */
	public $menu = array();

	/**
	 * Summary - Set in setSummary and called in constructor
	 *
	 * @var array
	 */
	public $summary = array();

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;


	/**
	 *    Constructor
	 *
	 * @param DoliDB $db Database handler
	 * @return void
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;

		// https://www.figma.com/community/file/1393171578760389765/dolibarr-ui-ux-kit

		// Menu Constructor
		$this->setMenu();
	}

	/**
	 *    Set Documentation Menu
	 *
	 * @return mixed false if error, void if no errors
	 */
	private function setMenu()
	{
		global $hookmanager;


		$hookmanager->initHooks(array('uidocumentation'));

		$baseUrl = 'admin/tools/ui';

		// Go back to Dolibarr
		$this->menu['BackToDolibarr'] = array(
			'url' => DOL_URL_ROOT,
			'icon' => 'fas fa-arrow-left pictofixedwidth',
			'submenu' => array(),
		);

		// Home for Ui documentation
		$this->menu['DocumentationHome'] = array(
			'url' => dol_buildpath($baseUrl.'/index.php', 1),
			'icon' => 'fas fa-book',
			'submenu' => array(),
		);

		// Components
		$this->menu['Components'] = array(
			'url' => dol_buildpath($baseUrl.'/components/index.php', 1),
			'icon' => 'fas fa-th-large',
			'submenu' => array(
				'Badges' => array(
					'url' => dol_buildpath($baseUrl.'/components/badges.php', 1),
					'icon' => 'fas fa-certificate pictofixedwidth',
					'submenu' => array(),
					'summary' => array(
						'DocBasicUsage' => '#badgesection-basicusage',
						'DocBadgeContextualVariations' => '#badgesection-contextvariations',
						'DocBadgeDefaultStatus' => '#badgesection-defaultstatus',
						'DocBadgePillBadges' => '#badgesection-pill',
						'DocBadgeDotBadges' => '#badgesection-dot',
						'DocBadgeLinks' => '#badgesection-links',
						'DocBadgeHelper' => '#badgesection-dolgetbadge'
					),
				),
				'Buttons' => array(
					'url' => dol_buildpath($baseUrl.'/components/buttons.php', 1),
					'icon' => 'fas fa-mouse pictofixedwidth',
					'submenu' => array(),
					'summary' => array(
						'DocBasicUsage' => '#buttonsection-basicusage',
						'DocButtonModal' => '#buttonsection-modals',
						'DocButtonSubmenu' => '#buttonsection-submenu',
					),
				),
				'Progress' => array(
					'url' => dol_buildpath($baseUrl.'/components/progress-bars.php', 1),
					'icon' => 'fas fa-battery-half pictofixedwidth',
					'submenu' => array(),
					'summary' => array(
						'DocBasicUsage' => '#progresse-section-basic-usage',
						'DocColorVariants' => '#progress-section-color',
						'DocStripedVariants' => '#progresse-section-stripped',
					),
				),
				'Event Message' => array(
					'url' => dol_buildpath($baseUrl.'/components/event-message.php', 1),
					'icon' => 'fas fa-comments pictofixedwidth',
					'submenu' => array(),
					'summary' => array(
						'DocBasicUsage' => '#seteventmessagesection-basicusage',
						'DocSetEventMessageContextualVariations' => '#seteventmessagesection-contextvariations',
					)
				),
			),
			'summary' => array(
				'keySum' => '#keySum'
			)
		);

		// Elements
		$this->menu['Content'] = array(
			'url' => dol_buildpath($baseUrl.'/content/index.php', 1),
			'icon' => 'fas fa-th-large',
			'submenu' => array(
				'Tables' => array(
					'url' => dol_buildpath('admin/tools/ui/content/tables.php', 1),
					'icon' => 'fas fa-table pictofixedwidth',
					'submenu' => array(),
					'summary' => array(
						'DocBasicUsage' => '#tablesection-basicusage',
						'DocTableWithFilters' => '#tablesection-withfilters'
					),
				),
			)
		);

		$parameters = array(
			'baseUrl' => $baseUrl,
		);
		$action = '';

		$reshook = $hookmanager->executeHooks('setMenu', $parameters, $this, $action);
		if ($reshook < 0) {
			return false;
		}
	}

	/**
	 *    Output header + body
	 *
	 * @param string $title Title of page
	 * @param 	string[]	$arrayofjs		 Array of complementary js files
	 * @param 	string[]	$arrayofcss		 Array of complementary css files
	 * @return void
	 */
	public function docHeader($title = '', $arrayofjs = [], $arrayofcss = [])
	{
		global $langs;
		$title = (!empty($title)) ? dol_escape_htmltag($title) : $langs->trans('Documentation');

		$arrayofcss[] = 'admin/tools/ui/css/documentation.css';

		top_htmlhead('',  $title, 0, 0, $arrayofjs, $arrayofcss);

		print '<body class="dolibarr-doc">';
	}

	/**
	 *    Output close body + html
	 * @return void
	 */
	public function docFooter()
	{
		global $langs;

		// DIV FOR SCROLL ANIMATION
		print '<div id="documentation-scrollwrapper">';
		print '<div id="documentation-scroll"></div>';
		print '</div>';

		// JS
		print '<script src="'.dol_buildpath('admin/tools/ui/js/documentation.js', 1).'"></script>';
		print '<script src="'.DOL_URL_ROOT.'/core/js/lib_foot.js.php?lang='.$langs->defaultlang.'"></script>';

		print '</body>';
		print '</html>';

		dol_htmloutput_events(0);
	}

	/**
	 * Output sidebar
	 *
	 * @return void
	 */
	public function showSidebar()
	{
		print '<div class="doc-sidebar">';

		// LOGO
		print '<div class="sidebar-logo">';
		if (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.svg')) {
			$urllogo = DOL_URL_ROOT.'/theme/dolibarr_logo.svg';
			print '<img src="'.$urllogo.'" />';
		}
		print '</div>';

		// NAVIGATION
		print '<nav>';
		if (!empty($this->menu)) {
			$this->displayMenu($this->menu);
		}
		print '</nav>';

		print '</div>';
	}

	/**
	 *    Recursive function to set Menu
	 *
	 * @param array $menu  $this->menu or submenus
	 * @param int   $level level of menu
	 * @return void
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
				$this->displayMenu($item['submenu'], $level); // Appel récursif pour afficher les sous-menus
			}
			echo '</li>';
		}
		print '</ul>';
	}

	/**
	 *    Output breadcrumb
	 * @return void
	 */
	public function showBreadcrumb()
	{
		global $langs;

		print '<nav class="doc-breadcrumbs">';
		print '<ul>';
		print '<li class="breadcrumb-item"><a href="'.$this->menu['DocumentationHome']['url'].'"><i class="'.$this->menu['DocumentationHome']['icon'].'" aria-hidden="true"></i></a></li>';
		if (!empty($this->view)) {
			$nb_entries = count($this->view);
			$i = 0;

			$menu_entry = $this->menu;
			foreach ($this->view as $page) {
				$i++;
				if ($i < $nb_entries && isset($menu_entry[$page])) {
					print '<li class="breadcrumb-item"><a href="'.$menu_entry[$page]['url'].'">'.$langs->transnoentities($page).'</a></li>';
					$menu_entry = $menu_entry[$page]['submenu'];
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
	 * Output summary
	 *
	 * @param int $showsubmenu 			Show Sub menus: 0 = No, 1 = Yes
	 * @param int $showsubmenu_summary	Show summary of sub menus: 0 = No, 1 = Yes
	 * @return void
	 */
	public function showSummary($showsubmenu = 1, $showsubmenu_summary = 1)
	{
		$i = 0;
		$menu_entry = [];
		if (!empty($this->view)) :
			// On se place au bon niveau
			foreach ($this->view as $view) {
				$i++;
				if ($i == 1) {
					$menu_entry = $this->menu[$view];
				} else {
					$menu_entry = $menu_entry['submenu'][$view];
				}
			}
		endif;

		if (!empty($menu_entry['summary']) || !empty($menu_entry['submenu'] && $showsubmenu)) {
			print '<div class="summary-wrapper">';
				$this->displaySummary($menu_entry);
			print '</div>';
		}
	}


	/**
	 *    Recursive function for Automatic Summary
	 *
	 * @param array $menu  					$this->menu or submenus
	 * @param int   $level 					level of menu
	 * @param int   $showsubmenu 			Show Sub menus: 0 = No, 1 = Yes
	 * @param int   $showsubmenu_summary 	Show summary of sub menus: 0 = No, 1 = Yes
	 * @return void
	 */
	public function displaySummary($menu, $level = 0, $showsubmenu = 1, $showsubmenu_summary = 1)
	{

		global $langs;

		$level++;
		print '<ul class="documentation-summary level-'.$level.'"">';

		if (!empty($menu['summary'])) :
			foreach ($menu['summary'] as $summary_label => $summary_link) {
				if ($summary_link[0] == '#') {
					$summary_link = $menu['url'].$summary_link;
				}

				print '<li><a href="'.$summary_link.'">'.$langs->trans($summary_label).'</a></li>';
			}
		endif;

		if ($showsubmenu && !empty($menu['submenu'])) {
			foreach ($menu['submenu'] as $key => $item) {
				print '<li class="summary-title ">';
					print '<h3 class="level-'.$level.'">'.$key.'</h3>';
				if ($showsubmenu_summary) {
					$this->displaySummary($item, $level);
				}
				print '</li>';
			}
		}
		print '</ul>';
	}

	/**
	 *    Output a View Code area
	 *
	 * @param array $lines Lines of code to show
	 * @param string $option Source code language ('html', 'php' etc)
	 * @return void
	 */
	public function showCode($lines = array(), $option = 'html')
	{
		require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
		print '<div class="documentation-code">';
		$content = implode("\n", $lines) . "\n";
		$doleditor = new DolEditor(md5($content), $content, '', 0, 'Basic', 'In', true, false, 'ace', 0, '99%', 1);
		print $doleditor->Create(1, '', false, '', $option);
		print '</div>';
	}
}
