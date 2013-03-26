<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *  \defgroup   fckeditor     Module fckeditor
 *  \brief      Module pour mettre en page les zones de saisie de texte
 *  \file       htdocs/core/modules/modFckeditor.class.php
 *  \ingroup    fckeditor
 *  \brief      Fichier de description et activation du module Fckeditor
 */

include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Classe de description et activation du module Fckeditor
 */

class modFckeditor extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
		$this->numero = 2000;

		$this->family = "technic";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Editeur WYSIWYG";
		$this->version = 'dolibarr';    // 'experimental' or 'dolibarr' or version
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 2;
		// Name of png file (without png) used for this module.
		// Png file must be in theme/yourtheme/img directory under name object_pictovalue.png.
		$this->picto='list';

		// Data directories to create when module is enabled
		$this->dirs = array("/fckeditor/temp","/fckeditor/image");

		// Config pages
		$this->config_page_url = array("fckeditor.php");

		// Dependances
		$this->depends = array();
		$this->requiredby = array();

		// Constantes
		$this->const = array();
        $this->const[0]  = array("FCKEDITOR_ENABLE_SOCIETE","yesno","1","WYSIWIG for description and note (except products/services)");
        $this->const[1]  = array("FCKEDITOR_ENABLE_PRODUCTDESC","yesno","1","WYSIWIG for products/services description and note");
        $this->const[2]  = array("FCKEDITOR_ENABLE_MAILING","yesno","1","WYSIWIG for mass emailings");
        $this->const[3]  = array("FCKEDITOR_ENABLE_DETAILS","yesno","1","WYSIWIG for products details lines for all entities");
        $this->const[4]  = array("FCKEDITOR_ENABLE_USERSIGN","yesno","1","WYSIWIG for products details lines for all entities");
        $this->const[5]  = array("FCKEDITOR_ENABLE_MAIL","yesno","1","WYSIWIG for products details lines for all entities");

		// Boites
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'fckeditor';
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

		$sql = array();

		return $this->_init($sql,$options);
	}

    /**
	 *		Function called when module is disabled.
	 *      Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
     */
    function remove($options='')
    {
		$sql = array();

		return $this->_remove($sql,$options);
    }

}
?>
