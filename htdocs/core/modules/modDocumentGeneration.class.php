<?php
/* Copyright (C) 2007      Rodolphe Quiedeville <rodolphe@quiedeville.org>
<<<<<<< HEAD
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 * or see http://www.gnu.org/
 */

/**
 *	\defgroup   	document     Module mass mailings
 *	\brief      	Module pour gerer des generations de documents
 *	\file       	htdocs/core/modules/modDocumentGeneration.class.php
 *	\ingroup    	document
 *	\brief      	Fichier de description et activation du module Generation document
 */

include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module Document
 */
class modDocumentGeneration extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
<<<<<<< HEAD
	function __construct($db)
=======
	public function __construct($db)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		$this->db = $db;
		$this->numero = 1520;

		$this->family = "technic";
<<<<<<< HEAD
		$this->module_position = 80;
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
=======
		$this->module_position = '80';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$this->description = "Direct mail document generation";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto='email';

		// Data directories to create when module is enabled
		$this->dirs = array("/documentgeneration/temp");

		// Config pages
		//$this->config_page_url = array("document.php");

		// Dependencies
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array("orders","bills","companies","mails");

		// Constants

		$this->const = array();

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'document';

		$r=0;

		$this->rights[$r][0] = 1521;
		$this->rights[$r][1] = 'Lire les documents';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 1522;
		$this->rights[$r][1] = 'Supprimer les documents clients';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supprimer';
	}


	/**
<<<<<<< HEAD
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function init($options='')
	{
=======
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
     *  @param      string  $options    Options when enabling module ('', 'noboxes')
     *  @return     int                 1 if OK, 0 if KO
     */
    public function init($options = '')
    {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		global $conf;

		// Permissions
		$this->remove($options);

		$sql = array();

<<<<<<< HEAD
		return $this->_init($sql,$options);
=======
		return $this->_init($sql, $options);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}
}
