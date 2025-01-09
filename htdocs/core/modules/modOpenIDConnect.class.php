<?php
/* Copyright (C) 2014-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015      Frederic France      <frederic.france@free.fr>
 * Copyright (C) 2023   Maximilien Rozniecki    <mrozniecki@easya.solutions>
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

/** \defgroup   openid_connect     Module OpenID Connect
 *  \brief      Module for activation of OpenID Connect authentication method
 */

/**
 *  \file       htdocs/core/modules/modOpenIDConnect.class.php
 *  \ingroup    openid_connect
 *  \brief      Description and activation file for the module OpenID Connect
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';



/**
 *  Class to describe and activate module OpenID Connect
 */
class modOpenIDConnect extends DolibarrModules
{
	/**
	 *  Constructor
	 *
	 *  @param      DoliDB      $db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->numero = 69000; // ToDo
		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "interface";
		$this->module_position = '32';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Enable OpenID Connect authentication";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or 'dolibarr_deprecated' or version
		$this->version = 'dolibarr';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto = 'technic';

		// Data directories to create when module is enabled.
		$this->dirs = array();

		// Config pages
		$this->config_page_url = array("openid_connect.php");

		// Dependencies
		$this->hidden = false; // A condition to hide module
		$this->depends = array(); // List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array(); // List of module ids to disable if this one is disabled
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with
		$this->phpmin = array(7, 0); // Minimum version of PHP required by module                    // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3, 7, -2); // Minimum version of Dolibarr required by module
		$this->conflictwith = array();
		$this->langfiles = array("openid_connect");

		// Constants
		$this->const = array();

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'openid_connect';

		// List of menus to add
		$this->menu = array();
	}


	/**
	 *      Function called when module is enabled.
	 *      The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *      It also creates data directories
	 *
	 *      @param      string  $options    Options when enabling module ('', 'noboxes')
	 *      @return     int                 1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf;

		// Clean before activation
		$this->remove($options);

		$sql = array();

		return $this->_init($sql, $options);
	}
}
