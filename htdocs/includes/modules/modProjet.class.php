<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004 Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004 Benoit Mortier			 <benoit.mortier@opensides.be>
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

/*!     \defgroup   projet     Module projet
        \brief      Module pour inclure le detail par projets dans les autres modules
*/

/*!
        \file       htdocs/includes/modules/modProjet.class.php
        \ingroup    projet
        \brief      Fichier de description et activation du module Projet
*/

include_once "DolibarrModules.class.php";

/*!     \class      modProjet
		\brief      Classe de description et activation du module Projet
*/

class modProjet extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
  function modProjet($DB)
  {
    $this->db = $DB ;
    $this->numero = 400 ;

    $this->family = "projects";
    $this->name = "Projets";
    $this->description = "Gestion des projets";
    $this->const_name = "MAIN_MODULE_PROJET";
    $this->const_config = MAIN_MODULE_PROJET;

    // Dépendances
    $this->depends = array();
    $this->requiredby = array();

    // Constantes
    $this->const = array();

    // Boites
    $this->boxes = array();

    // Permissions
    $this->rights = array();
    $this->rights_class = 'projet';
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
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (40,'Tous les droits sur les projets','projet','a',0);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (41,'Lire les projets','projet','r',1);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (42,'Créer modifier les projets','projet','w',0);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (44,'Supprimer les projets','projet','d',0);"
		 );
    
    return $this->_init($sql);
  }

  /**
   *    \brief      Fonction appelée lors de la désactivation d'un module.
   *                Supprime de la base les constantes, boites et permissions du module.
   */
  function remove()
  {
    $sql = array("DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE module = 'projet';");

    return $this->_remove($sql);
  }
}
?>
