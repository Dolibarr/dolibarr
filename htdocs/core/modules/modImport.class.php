<?php
/* Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *  \defgroup   import      Module import
 *  \brief      Module to make generic import of data into dolibarr database
 *	\file       htdocs/core/modules/modImport.class.php
 *	\ingroup    import
 *	\brief      Fichier de description et activation du module Import
 */

include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module Import
 */
class modImport extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
		$this->numero = 250;

		$this->family = "technic";
        $this->module_position = 70;
        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Outils d'imports de donnees Dolibarr (via un assistant)";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or 'dolibarr_deprecated' or version
		$this->version = 'dolibarr';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'technic';

		// Data directories to create when module is enabled
		$this->dirs = array("/import/temp");

		// Config pages
		$this->config_page_url = array();

		// Dependencies
		$this->depends = array();
		$this->requiredby = array();
		$this->phpmin = array(4,3,0);	// Need auto_detect_line_endings php option to solve MAC pbs.
		$this->phpmax = array();
		$this->need_dolibarr_version = array(2,7,-1);	// Minimum version of Dolibarr required by module
		$this->need_javascript_ajax = 1;

		// Constants
		$this->const = array();

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'import';
		$r=0;

		$r++;
		$this->rights[$r][0] = 1251;
		$this->rights[$r][1] = 'Run mass imports of external data (data load)';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'run';


		// Menus
		//-------
		$this->menu = 1;        // This module add menu entries. They are coded into menu manager.

	}
}
