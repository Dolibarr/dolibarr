<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\defgroup   	prelevement     Module prelevement
 *	\brief      	Module de gestion des prelevements bancaires
 *	\file       	htdocs/core/modules/modPrelevement.class.php
 *	\ingroup    	prelevement
 *	\brief      	Fichier de description et activation du module Prelevement
 */

include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module Prelevement
 */
class modPrelevement extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf;

		$this->db = $db;
		$this->numero = 57;

		$this->family = "financial";
		$this->module_position = 520;
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des Prelevements";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		// Name of png file (without png) used for this module
		$this->picto='payment';

		// Data directories to create when module is enabled
		$this->dirs = array("/prelevement/temp","/prelevement/receipts");

		// Dependancies
		$this->depends = array("modFacture","modBanque");
		$this->requiredby = array();

		// Config pages
		$this->config_page_url = array("prelevement.php");

		// Constants
		$this->const = array();
		$r=0;
		
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
		$r=0;
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

/*        $this->rights[2][0] = 154;
        $this->rights[2][1] = 'Setup withdraw account';
        $this->rights[2][2] = 'w';
        $this->rights[2][3] = 0;
        $this->rights[2][4] = 'bons';
        $this->rights[2][5] = 'configurer';
*/
		
		// Menus
		//-------
		$this->menu = 1;        // This module add menu entries. They are coded into menu manager.
		
	}


	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function init($options='')
	{
		global $conf;

		// Permissions
		$this->remove($options);

		$sql = array(
		    "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->const[0][2]."' AND type = 'bankaccount' AND entity = ".$conf->entity,
		    "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->const[0][2]."','bankaccount',".$conf->entity.")",
		);		

		return $this->_init($sql,$options);
	}
}
