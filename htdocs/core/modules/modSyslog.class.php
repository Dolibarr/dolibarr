<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\defgroup   syslog  Module syslog
 *	\brief      Module pour gerer les messages d'erreur dans syslog
 *	\file       htdocs/core/modules/modSyslog.class.php
 *	\ingroup    syslog
 *	\brief      Fichier de description et activation du module de syslog
 */

include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

/**
 *	Class to enable/disable module Logs
 */
class modSyslog extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
		$this->numero = 42;

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "base";
		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '50';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Activate debug logs (syslog)";
		// Can be enabled / disabled only in the main company
		$this->core_enabled = 1;
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		$this->picto='technic';

		// Data directories to create when module is enabled
		$this->dirs = array();

		// Config pages
		$this->config_page_url = array("syslog.php");

		// Dependencies
		$this->hidden = false;			// A condition to hide module
		$this->depends = array();		// List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array();	// List of module ids to disable if this one is disabled
		$this->conflictwith = array();	// List of module class names as string this module is in conflict with
		$this->phpmin = array(5,4);		// Minimum version of PHP required by module

		// Constants
		$this->const = array();

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'syslog';

		// Cronjobs
		$this->cronjobs = array(
			0 => array(
				'label' => 'CompressSyslogs',
				'jobtype' => 'method',
				'class' => 'core/class/utils.class.php',
				'objectname' => 'Utils',
				'method' => 'compressSyslogs',
				'parameters' => '',
				'comment' => 'Compress and archive log files. Warning: batch must be run with same account than your web server to avoid to get log files with different owner than required by web server. Another solution is to set web server Operating System group as the group of directory documents and set GROUP permission "rws" on this directory so log files will always have the group and permissions of the web server Operating System group',
				'frequency' => 1,
				'unitfrequency' => 3600 * 24,
				'priority' => 50,
				'status' => 0,
				'test' => true
			)
		);
	}
}
