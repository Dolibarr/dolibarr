<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/**
        \defgroup   user  Module user
        \brief      Module pour gérer les utilisateurs
*/

/**
        \file       htdocs/includes/modules/modUser.class.php
        \ingroup    user
        \brief      Fichier de description et activation du module Utilisateur
*/

include_once "DolibarrModules.class.php";

/**
        \class      modUser
        \brief      Classe de description et activation du module User
*/

class modUser extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
  function modUser($DB)
  {
    $this->db = $DB ;
    $this->numero = 0 ;

    $this->family = "base";
    $this->name = "User";
    $this->description = "Gestion des utilisateurs (requis)";

    $this->revision = explode(" ","$Revision$");
    $this->version = $this->revision[1];

    $this->const_name = "MAIN_MODULE_USER";
    $this->const_config = MAIN_MODULE_USER;
    $this->picto='group';

    // Dir
    $this->dirs = array();

    // Dépendances
    $this->depends = array();
    $this->requiredby = array();

    // Constantes
    $this->const = array();

    // Boites
    $this->boxes = array();

    // Permissions
    $this->rights = array();
    $this->rights_class = 'user';
  }

   /**
    *   \brief      Fonction appelée lors de l'activation du module. Insère en base les constantes, boites, permissions du module.
    *               Définit également les répertoires de données à créer pour ce module.
    */
  function init()
  {
    global $conf;

    // Permissions
    $this->remove();
    $r = 1;

    $this->rights[$r][0] = 251;
    $this->rights[$r][1] = 'Consulter les utilisateurs';
    $this->rights[$r][2] = 'w';
    $this->rights[$r][3] = 1;
    $this->rights[$r][4] = 'user';
    $this->rights[$r][5] = 'lire';
    $r++;

    return $this->_init($sql);
  }

  /**
    \brief      Fonction appelée lors de la désactivation d'un module.
    Supprime de la base les constantes, boites et permissions du module.
   */
  function remove()
  {
    $sql = array();

    return $this->_remove($sql);
  }
}
?>
