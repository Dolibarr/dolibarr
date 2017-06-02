<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
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
 * \defgroup   comptabilite     Module comptabilite
 * \brief      Module pour inclure des fonctions de comptabilite (gestion de comptes comptables et rapports)
 * \file       htdocs/core/modules/modComptabilite.class.php
 * \ingroup    comptabilite
 * \brief      Fichier de description et activation du module Comptabilite
 */

include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module Comptabilite
 */
class modComptabilite extends DolibarrModules
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
		$this->numero = 10;

		$this->family = "financial";
		$this->module_position = 600;
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion sommaire de comptabilite";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
        $this->picto='accounting';

		// Config pages
		$this->config_page_url = array("compta.php");

		// Dependencies
		$this->depends = array("modFacture","modBanque");
		$this->requiredby = array();
		$this->conflictwith = array("modAccounting");
		$this->langfiles = array("compta");

		// Constants
		$this->const = array();

		// Data directories to create when module is enabled
		$this->dirs = array("/comptabilite/temp",
		                    "/comptabilite/rapport",
		                    "/comptabilite/export",
		                    "/comptabilite/bordereau"
		                    );

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'compta';
		$r=0;

		$r++;
		$this->rights[$r][0] = 95;
		$this->rights[$r][1] = 'Lire CA, bilans, resultats';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'resultat';
		$this->rights[$r][5] = 'lire';
		
		
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

		// Nettoyage avant activation
		$this->remove($options);

		$sql = array();

		return $this->_init($sql,$options);
	}
}
