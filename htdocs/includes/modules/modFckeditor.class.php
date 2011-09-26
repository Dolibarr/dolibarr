<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
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
 *  \defgroup   fckeditor     Module fckeditor
 *  \brief      Module pour mettre en page les zones de saisie de texte
 *  \file       htdocs/includes/modules/modFckeditor.class.php
 *  \ingroup    fckeditor
 *  \brief      Fichier de description et activation du module Fckeditor
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
 * 	\class modFckeditor
 *  \brief      Classe de description et activation du module Fckeditor
 */

class modFckeditor extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$DB      Database handler
	 */
	function modFckeditor($DB)
	{
		$this->db = $DB ;
		$this->numero = 2000 ;

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
		$this->const[0]  = array("FCKEDITOR_ENABLE_USER","yesno","1","Activation fckeditor sur notes utilisateurs");
        $this->const[1]  = array("FCKEDITOR_ENABLE_SOCIETE","yesno","1","Activation fckeditor sur notes societe");
        $this->const[2]  = array("FCKEDITOR_ENABLE_PRODUCTDESC","yesno","1","Activation fckeditor sur notes produits");
        $this->const[3]  = array("FCKEDITOR_ENABLE_MEMBER","yesno","1","Activation fckeditor sur notes adherent");
        $this->const[4]  = array("FCKEDITOR_ENABLE_MAILING","yesno","1","Activation fckeditor sur emailing");

		// Boites
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'fckeditor';
	}

	/**
	 *   \brief      Fonction appele lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
	 *               Definit egalement les repertoires de donnees a creer pour ce module.
	 */
	function init()
	{
		global $conf;

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
