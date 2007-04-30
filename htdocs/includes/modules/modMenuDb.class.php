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
 * $Source$
 */

/**
        \defgroup   menuDb     Module menuDb
        \brief      Module pour administrer les menus par bdd
*/

/**
        \file       htdocs/includes/modules/modMenuBd.class.php
        \ingroup    menuDb
        \brief      Fichier de description et activation du module menuDb
*/

include_once "DolibarrModules.class.php";

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
        $this->id = 'menudb';   // Same value xxx than in file modXxx.class.php file
        $this->numero = 2300 ;
    
        $this->family = "technic";
        $this->name = "Menu Db";
        $this->description = "Administration des menus par base de données";
        $this->version = '1.1-beta';                        // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_MENUDB';
        $this->special = 1;
    
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


    $this->rights[2][0] = 2301;
    $this->rights[2][1] = 'Créer/modifier les menus';
    $this->rights[2][2] = 'w';
    $this->rights[2][3] = 0;
    $this->rights[2][4] = 'creer';

    

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
