<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*!     \defgroup   fournisseur     Module fournisseur
        \brief      Module pour gérer des sociétés et contacts de type fournisseurs
*/

/*!
        \file       htdocs/includes/modules/modFournisseur.class.php
        \ingroup    fournisseur
        \brief      Fichier de description et activation du module Fournisseur
*/


include_once "DolibarrModules.class.php";

/*! \class modFournisseur
		\brief      Classe de description et activation du module Fournisseur
*/

class modFournisseur extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
  function modFournisseur($DB)
  {
    $this->db = $DB ;
    $this->numero = 40 ;

    $this->family = "products";
    $this->name = "Fournisseur";
    $this->description = "Gestion des fournisseurs";
    $this->const_name = "MAIN_MODULE_FOURNISSEUR";
    $this->const_config = MAIN_MODULE_FOURNISSEUR;
    $this->special = 0;

    // Dir
    $this->dirs = array();

    // Dépendances
    $this->depends = array("modSociete");
    $this->requiredby = array();

    // Constantes
    $this->const = array();

    // Boxes
    $this->boxes = array();
    $this->boxes[0][0] = "Derniers founisseurs";
    $this->boxes[0][1] = "box_fournisseurs.php";
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
