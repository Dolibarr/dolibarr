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

/*!     \defgroup   prelevement     Module prelevement
        \brief      Module de gestion des prélèvements bancaires
*/

/*!
        \file       htdocs/includes/modules/modPrelevement.class.php
        \ingroup    prelevement
        \brief      Fichier de description et activation du module Prelevement
*/

include_once "DolibarrModules.class.php";

/*! \class modPrelevement
		\brief      Classe de description et activation du module Prelevement
*/

class modPrelevement extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
  function modPrelevement($DB)
  {
    $this->db = $DB ;
    $this->numero = 57 ;

    $this->family = "technic";
    $this->name = "Prelevement";
    $this->description = "Gestion des Prélèvements (experimental)";
    $this->const_name = "MAIN_MODULE_PRELEVEMENT";
    $this->const_config = MAIN_MODULE_PRELEVEMENT;
    $this->special = 0;

    // Dir
    $this->dirs = array();
    $this->data_directory = DOL_DATA_ROOT . "/prelevement/bon/";

    // Dépendances
    $this->depends = array();
    $this->requiredby = array();

    // Constantes
    $this->const = array();

    // Boites
    $this->boxes = array();

    // Permissions
    $this->rights = array();
    $this->rights_class = 'prelevement';
  }

   /**
    *   \brief      Fonction appelée lors de l'activation du module. Insère en base les constantes, boites, permissions du module.
    *               Définit également les répertoires de données à créer pour ce module.
    */
  function init()
  {
    // Permissions
    $this->remove();

    $this->rights = array();
    $this->rights_class = 'prelevement';

    $this->rights[0][0] = 150; // id de la permission
    $this->rights[0][1] = 'Tous les droits sur les prélèvements'; // libelle de la permission
    $this->rights[0][2] = 'a'; // type de la permission (déprécié à ce jour)
    $this->rights[0][3] = 0; // La permission est-elle une permission par défaut

    $this->rights[1][0] = 151;
    $this->rights[1][1] = 'Consulter les prélèvements';
    $this->rights[1][2] = 'r';
    $this->rights[1][3] = 1;

    $this->rights[2][0] = 152;
    $this->rights[2][1] = 'Configurer les prélèvements';
    $this->rights[2][2] = 'w';
    $this->rights[2][3] = 0;    

    // Dir
    $this->dirs[0] = DOL_DATA_ROOT . "/prelevement/" ;
    $this->dirs[1] = DOL_DATA_ROOT . "/prelevement/bon" ;

    $sql = array();
    
    return $this->_init($sql);
  }

  /**
   *    \brief      Fonction appelée lors de la désactivation d'un module.
   *                Supprime de la base les constantes, boites et permissions du module.
   */
  function remove()
  {
    $sql = array("DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE module = 'prelevement';");

    return $this->_remove($sql);
  }
}
?>
