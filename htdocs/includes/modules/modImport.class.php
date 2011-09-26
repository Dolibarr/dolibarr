<?php
/* Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *	\file       htdocs/includes/modules/modImport.class.php
 *	\ingroup    import
 *	\brief      Fichier de description et activation du module Import
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**     \class      modImport
 *		\brief      Classe de description et activation du module Import
 */
class modImport extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$DB      Database handler
	 */
	function modImport($DB)
	{
		$this->db = $DB ;
		$this->numero = 250;

		$this->family = "technic";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Outils d'imports de donnees Dolibarr (via un assistant)";
		// Possible values for version are: 'experimental' or 'dolibarr' or version
		$this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto = 'technic';

		// Data directories to create when module is enabled
		$this->dirs = array("/import/temp");

		// Config pages
		$this->config_page_url = array();

		// D�pendances
		$this->depends = array();
		$this->requiredby = array();
		$this->phpmin = array(4,3,0);	// Need auto_detect_line_endings php option to solve MAC pbs.
		$this->phpmax = array();
		$this->need_dolibarr_version = array(2,7,-1);	// Minimum version of Dolibarr required by module
		$this->need_javascript_ajax = 1;

		// Constantes
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
	}

	/**
	 *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
	 *               Definit egalement les repertoires de donnees a creer pour ce module.
	 */
	function init()
	{
		$sql = array();

		return $this->_init($sql);
	}

	/**
	 *    \brief      Fonction appelee lors de la desactivation d'un module.
	 *                Supprime de la base les constantes, boites et permissions du module.
	 */
	function remove()
	{
		$sql = array();

		return $this->_remove($sql);
	}
}
?>
