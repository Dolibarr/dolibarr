<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 */

/*!   \defgroup   telephonie  Module telephonie
      \brief      Module pour gérer la téléphonie
*/

/*!
      \file       htdocs/includes/modules/modTelephonie.class.php
      \ingroup    telephonie
      \brief      Fichier de description et activation du module de Téléphonie
*/

include_once "DolibarrModules.class.php";

/*! \class modTelephonie
    \brief Classe de description et activation du module Telephonie
*/

class modTelephonie extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
  function modTelephonie($DB)
  {
    $this->db = $DB ;
    $this->numero = 56 ;

    $this->family = "technic";
    $this->name = "Telephonie";
    $this->description = "Gestion de la Telephonie (experimental)";
    $this->const_name = "MAIN_MODULE_TELEPHONIE";
    $this->const_config = MAIN_MODULE_TELEPHONIE;
    $this->special = 1;

    // Dir
    $this->dirs = array();

    // Dépendances
    $this->depends = array();
    $this->requiredby = array();

    $this->const = array();
    $this->boxes = array();
  }

   /**
    *   \brief      Fonction appelé lors de l'activation du module. Insère en base les constantes, boites, permissions du module.
    *               Définit également les répertoires de données à créer pour ce module.
    */
  function init()
  {
    /*
     * Permissions
     */    
    $this->remove();
    $sql = array(
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (140,'Tous les droits sur la telephonie','telephonie','a',0);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (141,'Consulter la telephonie','telephonie','r',0);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (142,'Commander les lignes','telephonie','w',0);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (143,'Activer une ligne','telephonie','w',0);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (144,'Configurer la telephonie','telephonie','w',0);");
    
    /*
     * Documents
     *
     */
    $this->dirs[0] = DOL_DATA_ROOT . "/telephonie/" ;
    $this->dirs[1] = DOL_DATA_ROOT . "/telephonie/ligne/" ;	  
    $this->dirs[2] = DOL_DATA_ROOT . "/telephonie/ligne/commande" ;	 
    $this->dirs[3] = DOL_DATA_ROOT . "/telephonie/logs" ;
    $this->dirs[4] = DOL_DATA_ROOT . "/telephonie/client" ;
    $this->dirs[5] = DOL_DATA_ROOT . "/telephonie/rapports" ;
    
    return $this->_init($sql);



  }

  /**
   *    \brief      Fonction appelée lors de la désactivation d'un module.
   *                Supprime de la base les constantes, boites et permissions du module.
   */
  function remove()
  {
    $sql = array("DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE module = 'telephonie';");

    return $this->_remove($sql);
  }
}
?>
