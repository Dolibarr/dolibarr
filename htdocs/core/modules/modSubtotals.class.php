<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011      Dimitri Mouillard 	<dmouillard@teclib.com>
 * Copyright (C) 2013      Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2018      Charlene Benke		<charlie@patas-monkey.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * or see https://www.gnu.org/
 */

/**
 * 	  \defgroup   subtotals 	Module subtotals
 *    \brief      Module for subtotal lines management
 *
 *    \file       htdocs/core/modules/modsubtotal.class.php
 *    \ingroup    subtotals
 *    \brief      Description and activation file for the module subtotals
 */
include_once DOL_DOCUMENT_ROOT."/core/modules/DolibarrModules.class.php";
require_once DOL_DOCUMENT_ROOT.'/subtotals/class/commonsubtotal.class.php';

/**
 *		Description and activation class for module subtotals
 */
class modSubtotals extends DolibarrModules
{
	/**
	 *  Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *  @param	DoliDB	$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = SUBTOTALS_SPECIAL_CODE;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'subtotals';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "technic";
		$this->module_position = '42';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "SubTotalModuleDesc";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto = 'donation';

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/mymodule/temp");
		$this->dirs = array("/subtotals/temp");

		// Config pages
		$this->config_page_url = array("subtotals.php");

		// Dependencies
		$this->hidden = false; // A condition to hide module
		$this->depends = array(); // List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array(); // List of module ids to disable if this one is disabled
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with
		$this->phpmin = array(7, 0); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3, 0); // Minimum version of Dolibarr required by module
		$this->langfiles = array("subtotals");

		// Constants
		// Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',0),
		//                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0) );
		$this->const = array(); // List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 0 or 'allentities')

		// Array to add new pages in new tabs
		//$this->tabs[] = array('data'=>'user:+paidholidays:CPTitreMenu:holiday:$user->rights->holiday->read:/holiday/list.php?mainmenu=hrm&id=__ID__');	// We avoid to get one tab for each module. RH data are already in RH tab.
		$this->tabs[] = array(); // To add a new tab identified by code tabname1

		// Boxes
		$this->boxes = array(); // List of boxes

		// Permissions
		$this->rights = array(); // Permission array used by this module

		// Menus
		//-------
		$this->menu = 1; // This module add menu entries. They are coded into menu manager.

		$this->module_parts = array('substitutions' => 1);
	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
	 *      @param      string	$options    Options when enabling module ('', 'newboxdefonly', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		// Permissions
		$this->remove($options);

		$sql = array(
			//	"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->db->escape($this->const[0][2])."' AND type = 'holiday' AND entity = ".((int) $conf->entity),
			//	"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->db->escape($this->const[0][2])."','holiday',".((int) $conf->entity).")"
		);

		return $this->_init($sql, $options);
	}
}
