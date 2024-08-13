<?php
/* Copyright (C) 2013   Alexandre Spangaro  <aspangaro@open-dsi.fr>
 * Copyright (C) 2014   Laurent Destailleur <eldy@users.sourceforge.net>
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
 * 	\defgroup   SocialNetworks   Module SocialNetworks
 *  \brief      Add a SocialNetworks button.
 *  \file       htdocs/core/modules/modSocialNetworks.class.php
 *  \ingroup    socialnetworks
 *  \brief      Description and activation file for the module SocialNetworks
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *	Class to describe a SocialNetworks module
 */
class modSocialNetworks extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;
		$this->numero = 3400;

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "interface";
		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '20';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Enable Social Networks fields into third parties and addresses (skype, twitter, facebook, ...)";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		$this->picto = 'share-alt';

		// Data directories to create when module is enabled
		$this->dirs = array();

		// Config pages
		$this->config_page_url = array(DOL_URL_ROOT.'/admin/fediverse.php');

		// Dependencies
		$this->hidden = getDolGlobalInt('MODULE_SOCIALNETWORKS_DISABLED'); // A condition to hide module
		$this->depends = array(); // List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array(); // List of module ids to disable if this one is disabled
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with
		$this->phpmin = array(7, 0); // Minimum version of PHP required by module
		$this->langfiles = array();

		// Constants


		// New pages on tabs
		$this->tabs = array();

		// Boxes
		$this->boxes = array();

		// Main menu entries
		$this->menu = array();
	}
}
