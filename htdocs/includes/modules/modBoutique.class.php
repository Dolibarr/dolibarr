<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**     \defgroup   oscommerce     Module OSCommerce
        \brief      Module pour gerer une boutique et interface avec OSCommerce
		\version	$Id$
*/

/**
        \file       htdocs/includes/modules/modBoutique.class.php
        \ingroup    oscommerce
        \brief      Fichier de description et activation du module OSCommerce
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
		\class 		modBoutique
		\brief      Classe de description et activation du module OSCommerce
*/

class modBoutique extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acces base
	 */
	function modBoutique($DB)
	{
		$this->db = $DB ;
		$this->numero = 800;

		$this->family = "products";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
		$this->description = "Interface de visualisation d'une boutique OSCommerce ou OSCSS";
		$this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 1;

		// Data directories to create when module is enabled
		$this->dirs = array();

		// Config pages
//		$this->config_page_url = array("boutique.php","osc-languages.php");
		$this->config_page_url = array("boutique.php");

		// Dependancies
		$this->depends = array();
		$this->requiredby = array();
	    $this->conflictwith = array("modOSCommerceWS");
	   	$this->langfiles = array("shop");

		// Constants
		$this->const = array();

	    // Boites
	    $this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'boutique';
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
