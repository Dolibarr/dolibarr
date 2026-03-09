<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.org>
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
 *	\defgroup   clicktodial      Module clicktodial
 *	\brief      Module to manage a ClickToDial system
 *	\file       htdocs/core/modules/modClickToDial.class.php
 *	\ingroup    clicktodial
 *	\brief      Description and activation file for the module Click to Dial
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module Click to Dial
 */
class modClickToDial extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->numero = 58;

		$this->family = "interface";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Integration of a ClickToDial system (Asterisk, ...)";
		$this->descriptionlong = "Support a Click To Dial feature with a SIP system. When clicking on a phone number, your phone system automatically call the callee.";

		$this->version = 'dolibarr'; // 'development' or 'experimental' or 'dolibarr' or version

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'phoning';

		// Data directories to create when module is enabled
		$this->dirs = array();

		// Dependencies
		$this->depends = array();
		$this->requiredby = array();

		// Config pages
		$this->config_page_url = array("clicktodial.php");

		// Constants
		$this->const = array();

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'clicktodial';
	}
}
