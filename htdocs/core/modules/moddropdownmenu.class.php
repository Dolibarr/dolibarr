<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2020 Florian Dufourg <florian.dufourg@gnl-solutions.com>
 * Copyright (C) 2021 Anthony Berton <bertonanthony@gmail.com>
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
 * 	\defgroup   menu     Module Menu
 *  \brief      Menu module descriptor.
 *
 *  \file       htdocs/menu/core/modules/modMenu.class.php
 *  \ingroup    menu
 *  \brief      Description and activation file for module Menu
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module Menu
 */
class modDropDownMenu extends DolibarrModules
{
    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs, $conf;
        $this->db = $db;

        $this->numero = 99;
        $this->rights_class = 'menu';
        $this->family = "base";
        $this->module_position = '90';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Drop Down Menu";
        $this->descriptionlong = "Drop Down Menu";
        $this->version = 'experimental';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'dropdownmenu@dropdownmenu';
        $this->module_parts = array(
            'triggers' => 0,
            'login' => 0,
            'substitutions' => 0,
            // Set this to 1 if module has its own menus handler directory (core/menus)
            'menus' => 1,
            'tpl' => 0,
            'barcode' => 0,
            'models' => 0,
            'theme' => 0,
            'css' => array(
                '/dropdownmenu/css/dropdownmenu.css.php',
            ),
            'js' => array(
                '/dropdownmenu/js/dropdownmenu.js.php',
            ),
            'hooks' => array(''),
            'moduleforexternal' => 1,
        );
        $this->dirs = array("/dropdownmenu/temp");
        $this->config_page_url = array("");
        $this->hidden = false;
        $this->depends = array();
        $this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
        $this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)
        $this->langfiles = '';
        $this->phpmin = array(5, 5); // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(10); // Minimum version of Dolibarr required by module
        $this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
        $this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)


        if (!isset($conf->dropdownmenu) || !isset($conf->dropdownmenu->enabled)) {
            $conf->dropdownmenu = new stdClass();
            $conf->dropdownmenu->enabled = 0;
        }

		$this->fileNameMenuHandler = 'dropdown_responsive_menu.php';
		$this->fileNameMenuHandlerLib = 'dropdown_responsive_menu.lib.php';		
		
        $this->const = array(
			1=>array('MAIN_MENU_STANDARD_FORCED','chaine',$this->fileNameMenuHandler,'Force menu handler to this value',1,'current',1),
			2=>array('MAIN_MENUFRONT_STANDARD_FORCED','chaine',$this->fileNameMenuHandler,'Force menu handler to this value',1,'current',1),
			3=>array('MAIN_MENU_SMARTPHONE_FORCED','chaine',$this->fileNameMenuHandler,'Force menu handler to this value',1,'current',1),
			4=>array('MAIN_MENUFRONT_SMARTPHONE_FORCED','chaine',$this->fileNameMenuHandler,'Force menu handler to this value',1,'current',1),
			5=>array('MAIN_HIDE_LEFT_MENU','chaine','1','Hide left menu',1,'current',1),
			6=>array('MAIN_USE_TOP_MENU_SEARCH_DROPDOWN','chaine','1','Show search',1,'current',1),
        );
		
    }

    /**
     *  Function called when module is enabled.
     *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *  It also creates data directories
     *
     *  @param      string  $options    Options when enabling module ('', 'noboxes')
     *  @return     int             	1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        global $conf, $langs;

        $result = $this->_load_tables('/dropdownmenu/sql/');
        if ($result < 0) return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')		

        // Permissions
        $this->remove($options);

        $sql = array();

        return $this->_init($sql, $options);
    }

    /**
     *  Function called when module is disabled.
     *  Remove from database constants, boxes and permissions from Dolibarr database.
     *  Data directories are not deleted
     *
     *  @param      string	$options    Options when enabling module ('', 'noboxes')
     *  @return     int                 1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}