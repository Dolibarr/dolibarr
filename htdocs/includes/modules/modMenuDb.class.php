<?php
/* Copyright (C) 2007 Patrick Raguin <patrick.raguin@gmail.com>
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

/**
        \defgroup   menudb		Module menu Db
        \brief      Module pour administrer les menus par bdd
*/

/**
        \file       htdocs/includes/modules/modMenuBd.class.php
        \ingroup    menudb
        \brief      Fichier de description et activation du module menuDb
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
        \class      modMenuDb
        \brief      Classe de description et activation du module menuDb
*/

class modMenuDb extends DolibarrModules
{

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      handler d'accès base
     */
    function modMenuDb($DB)
    {
        $this->db = $DB ;
        $this->id = 'menudb';   	// Same value xxx than in file modXxx.class.php file
        $this->numero = 2300 ;
    
        $this->family = "base";		// Family for module (or "base" if core module)
        $this->name = "Menus";
        $this->description = "Administration des menus par base de données";

		$this->revision = explode(' ','$Revision$');
        $this->version = 'experimental';                        // 'experimental' or 'dolibarr' or version

        $this->const_name = 'MAIN_MODULE_MENUDB';
        $this->special = 0;
		$this->picto='group';
    
	    // Dir
	    $this->dirs = array();

	    // Dépendances
	    $this->depends = array();
	    $this->requiredby = array();


	    // Constantes
	    $this->const = array();

	    // Boxes
	    $this->boxes = array();
	    
	    // Permissions
		$this->rights = array();
	    $this->rights_class = 'menudb';
		$this->rights_admin_allowed = 1;	// Admin is always granted of permission (even when module is disabled)
	    $r=0;
    
		$r++;
	    $this->rights[$r][0] = 2301;
	    $this->rights[$r][1] = 'Configurer les menus';
	    $this->rights[$r][2] = 'w';
	    $this->rights[$r][3] = 0;
	    $this->rights[$r][4] = 'creer';
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
