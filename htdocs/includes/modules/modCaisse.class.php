<?php
/* Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004 Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004 Benoit Mortier       <benoit.mortier@opensides.be>
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

/*!     \defgroup   caisse     Module caisse
        \brief      Module pour gérer la tenue d'une ou plusieurs caisses liquides
*/

/*!
        \file       htdocs/includes/modules/modCaisse.class.php
        \ingroup    caisse
        \brief      Fichier de description et activation du module Caisse
*/

include_once "DolibarrModules.class.php";

/*! \class modCaisse
		\brief      Classe de description et activation du module Caisse
*/

class modCaisse extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
  function modCaisse($DB)
  {
    $this->db = $DB ;
    $this->numero = 84 ;

    $this->family = "financial";
    $this->name = "Caisse";
    $this->description = "Gestion des comptes financiers de type Caisses liquides (pas encore opérationnel)";
    $this->const_name = "MAIN_MODULE_CAISSE";
    $this->const_config = MAIN_MODULE_CAISSE;
    $this->special = 0;

    // Dir to create
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
    $this->rights_class = 'caisse';
  }

   /**
    *   \brief      Fonction appelée lors de l'activation du module. Insère en base les constantes, boites, permissions du module.
    *               Définit également les répertoires de données à créer pour ce module.
    */
  function init()
  {
    // Permissions
    $this->remove();

    $sql = array(
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (130,'Tous les droits sur les caisses','caisse','a',0);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (131,'Lire les caisses liquide','caisse','r',1);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (132,'Créer, supprimer transactions','caisse','r',1);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (133,'Configurer les caisses (créer, gérer catégories)','caisse','w',0);",
		 );
    
    return $this->_init($sql);
  }

  /**
   *    \brief      Fonction appelée lors de la désactivation d'un module.
   *                Supprime de la base les constantes, boites et permissions du module.
   */
  function remove()
  {
    $sql = array("DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE module = 'caisse';");

    return $this->_remove($sql);
  }
}
?>
