<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011 Juanjo Menent 		<jmenent@2byte.es>
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
 *	\defgroup   	prelevement     Module prelevement
 *	\brief      	Module to manage Direct debit orders
 *	\file       	htdocs/core/modules/modPrelevement.class.php
 *	\ingroup    	prelevement
 *	\brief      	Description and activation file for the module Prelevement
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module of payment by Direct Debit
 */
class modPrelevement extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;
		$this->numero = 57;

		$this->family = "financial";
		$this->module_position = '52';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Management of Direct Debit orders";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of png file (without png) used for this module
		$this->picto = 'payment';

		// Data directories to create when module is enabled
		$this->dirs = array("/prelevement/temp", "/prelevement/receipts");

		// Dependencies
		$this->hidden = false; // A condition to hide module
		$this->depends = array("modFacture", "modBanque"); // List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array(); // List of module ids to disable if this one is disabled
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with
		$this->phpmin = array(5, 6); // Minimum version of PHP required by module

		// Config pages
		$this->config_page_url = array("prelevement.php");

		// Constants
		$this->const = array();
		$r = 0;

		$this->const[$r][0] = "BANK_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "sepamandate";
		$this->const[$r][3] = 'Name of manager to generate SEPA mandate';
		$this->const[$r][4] = 0;
		$r++;


		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'prelevement';
		$r = 0;
		$r++;
		$this->rights[$r][0] = 151;
		$this->rights[$r][1] = 'Read direct debit payment orders';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'bons';
		$this->rights[$r][5] = 'lire';

		$r++;
		$this->rights[$r][0] = 152;
		$this->rights[$r][1] = 'Create/modify a direct debit payment order';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'bons';
		$this->rights[$r][5] = 'creer';

		$r++;
		$this->rights[$r][0] = 153;
		$this->rights[$r][1] = 'Send/Transmit direct debit payment orders';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'bons';
		$this->rights[$r][5] = 'send';

		$r++;
		$this->rights[$r][0] = 154;
		$this->rights[$r][1] = 'Record Credits/Rejects of direct debit payment orders';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'bons';
		$this->rights[$r][5] = 'credit';

		// Menus
		//-------
		$this->menu = 1; // This module add menu entries. They are coded into menu manager.
	}


	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf;

		// Permissions
		$this->remove($options);

		$sql = array(
			"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->db->escape($this->const[0][2])."' AND type = 'bankaccount' AND entity = ".$conf->entity,
			"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->db->escape($this->const[0][2])."','bankaccount',".$conf->entity.")",
		);

		return $this->_init($sql, $options);
	}
}
