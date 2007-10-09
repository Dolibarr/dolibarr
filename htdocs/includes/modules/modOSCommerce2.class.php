<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 */

/**     \defgroup   oscommerce2     Module OSCommerce 2
        \brief      Module pour gérer une boutique et interface avec OSCommerce via Web Services
*/

/**
        \file       htdocs/includes/modules/modOSCommerce2.class.php
        \ingroup    oscommerce2
        \brief      Fichier de description et activation du module OSCommerce2
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/** 
		\class 		modOSCommerce2
		\brief      Classe de description et activation du module OSCommerce2
*/

class modOSCommerce2 extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'accès base
	 */
	function modOSCommerce2($DB)
	{
		$this->db = $DB ;
		$this->id = 'oscommerce2';   // Same value xxx than in file modXxx.class.php file
		$this->numero = 900;
	
		$this->family = "products";
		$this->name = "OSCommerce 2";
		$this->description = "Interface de visualisation d'une boutique OSCommerce via des Web services.\nCe module requiert d'installer les composants dans /oscommerce_ws/ws_server sur OSCommerce. Voir fichier README dans /oscommerce_ws/ws_server";
		$this->version = 'experimental';	// 'development' or 'experimental' or 'dolibarr' or version
		$this->const_name = 'MAIN_MODULE_OSCOMMERCEWS';
		$this->special = 1;
	
		// Dir
		$this->dirs = array();
	
		// Config pages
		$this->config_page_url = array();
	
		// Dépendances
		$this->depends = array();
		$this->requiredby = array();
	    $this->conflictwith = array("modBoutique");
	   	$this->langfiles = array("shop");
	
		// Constantes
		$this->const = array();
	
	    // Boites
	    $this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'boutique';
	}
  
   /**
    *   \brief      Fonction appelée lors de l'activation du module. Insère en base les constantes, boites, permissions du module.
    *               Définit également les répertoires de données à créer pour ce module.
    */
	function init()
	{
		$sql = array();
		
		return $this->_init($sql);
	}

	/**
	 *    \brief      Fonction appelée lors de la désactivation d'un module.
	 *                Supprime de la base les constantes, boites et permissions du module.
	 */
	function remove()
	{
		$sql = array();
		
		return $this->_remove($sql);
	}
  
}
?>
