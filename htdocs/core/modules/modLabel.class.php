<?php
/* Copyright (C) 2007-2009 Regis Houssin       <regis.houssin@capnetworks.com>
 * Copyright (C) 2008      Laurent Destailleur <eldy@users.sourceforge.net>
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
 *	\defgroup   label         Module labels
 *	\brief      Module pour gerer les formats d'impression des etiquettes
 *	\file       htdocs/core/modules/modLabel.class.php
 *	\ingroup    other
 *	\brief      Fichier de description et activation du module Label
 */

include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module Label
 */
class modLabel extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
		$this->numero = 60;

		$this->family = "technic";
		$this->module_position = 80;
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des etiquettes";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 2;
		$this->picto='label';

		// Data directories to create when module is enabled
		$this->dirs = array("/label/temp");

		// Dependancies
		$this->depends = array();
		$this->requiredby = array();

		// Config pages
		// $this->config_page_url = array("label.php");

		// Constants
		$this->const = array();

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'label';

		$this->rights[1][0] = 601; // id de la permission
		$this->rights[1][1] = 'Lire les etiquettes'; // libelle de la permission
		$this->rights[1][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[1][4] = 'lire';

		$this->rights[2][0] = 602; // id de la permission
		$this->rights[2][1] = 'Creer/modifier les etiquettes'; // libelle de la permission
		$this->rights[2][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[2][4] = 'creer';

		$this->rights[4][0] = 609; // id de la permission
		$this->rights[4][1] = 'Supprimer les etiquettes'; // libelle de la permission
		$this->rights[4][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[4][4] = 'supprimer';

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
		// Permissions
		$this->remove($options);

		$sql = array();

		return $this->_init($sql,$options);
	}
}
