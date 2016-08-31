<?php
/* Copyright (C) 2015 Alexandre Spangaro <aspangaro.dolibarr@gmail.com>
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
 * \file    htdocs/core/modules/modHRM.class.php
 * \ingroup HRM
 * \brief   Description and activation file for module HRM
 */
include_once (DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php");

/**
 * Class to describe and activate the HRM module
 */
class modHRM extends DolibarrModules
{
	/**
	 * Constructor.
	 * Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db        	
	 */
	public function __construct($db)
	{
		global $langs, $conf;
		
		$this->db = $db;

		$this->numero = 4000;
		$this->rights_class = 'hrm';
		
		$this->family = "hr";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace( '/^mod/i', '', get_class($this));
		$this->description = "Management of employees carrier and feelings";
		
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
		$this->special = 0;
		// $this->picto = '';
		
		// define triggers
		$this->module_parts = array();
		
		// Data directories to create when module is enabled
		$this->dirs = array();
		
		// Config pages
		$this->config_page_url = array('admin_hrm.php@hrm');
		
		// Dependencies
		$this->depends = array();
		$this->requiredby = array(/*"
			modSalaries,
			modExpenseReport,
			modHoliday
		"*/);
		$this->conflictwith = array();
		$this->phpmin = array (
			5,
			3 
		); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array (
			3,
			9 
		); // Minimum version of Dolibarr required by module
		$this->langfiles = array (
			"hrm" 
		);

		// Dictionnaries
		$this->dictionnaries=array();

		// Constantes
		$this->const = array ();
		$r = 0;

		// Boxes
		$this->boxes = array ();
		
		// Permissions
		$this->rights = array(); // Permission array used by this module
		$r = 0;

		$this->rights[$r][0] = 4001;
		$this->rights[$r][1] = 'See employees';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'employee';
		$this->rights[$r][5] = 'read';
		$r ++;
		
		$this->rights[$r][0] = 4002;
		$this->rights[$r][1] = 'Create employees';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'employee';
		$this->rights[$r][5] = 'write';
		$r ++;
		
		$this->rights[$r][0] = 4003;
		$this->rights[$r][1] = 'Delete employees';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'employee';
		$this->rights[$r][5] = 'delete';
		$r ++;
		
		$this->rights[$r][0] = 4004;
		$this->rights[$r][1] = 'Export employees';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'employee';
		$this->rights[$r][5] = 'export';
		$r ++;

		// Main menu entries
		$this->menus = array (); // List of menus to add
		$r = 0;
	}
	
	/**
	 * Function called when module is enabled.
	 * The init function add constants, boxes, permissions and menus
	 * (defined in constructor) into Dolibarr database.
	 * It also creates data directories
	 *
	 * @param string $options Enabling module ('', 'noboxes')
	 * @return int if OK, 0 if KO
	 */
	function init($options='')
	{
		// Permissions
		$this->remove($options);

		$sql = array();

		return $this->_init($sql,$options);
	}
}
