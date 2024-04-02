<?php
/* Copyright (C) 2005-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
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
 *  \defgroup   export      Module Export
 *  \brief      Module to manage data exports from Dolibarr database
 *
 *  \file       htdocs/core/modules/modExport.class.php
 *  \ingroup    export
 *  \brief      Description and activation file for the module export
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module export
 */
class modExport extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->numero = 240;

		$this->family = "technic";
		$this->module_position = '72';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Outils d'exports de donnees Dolibarr (via un assistant)";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'technic';

		// Data directories to create when module is enabled
		$this->dirs = array("/export/temp");

		// Config pages
		$this->config_page_url = array("export.php");

		// Dependencies
		$this->depends = array();
		$this->requiredby = array();
		$this->phpmin = array(7, 0);
		$this->phpmax = array();
		$this->enabled_bydefault = true; // Will be enabled during install

		// Constants
		$this->const = array();

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'export';
		$r = 0;

		$r++;
		$this->rights[$r][0] = 1201;
		$this->rights[$r][1] = 'Read exports';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 1202;
		$this->rights[$r][1] = 'Creeate/modify export';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'creer';


		// Menus
		//-------
		$this->menu = 1; // This module add menu entries. They are coded into menu manager.
	}
}
