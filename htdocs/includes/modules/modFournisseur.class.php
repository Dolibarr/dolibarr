<?PHP
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
        \brief      Fichier de description et activation du module Fournisseur
*/


include_once "DolibarrModules.class.php";

class modFournisseur extends DolibarrModules
{

  /** Initialisation de l'objet
   *
   *
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

    // Dépendances
    $this->depends = array("modSociete");
    $this->requiredby = array();

    $this->const = array();
    $this->boxes = array();
    $this->boxes[0][0] = "Derniers founisseurs";
    $this->boxes[0][1] = "box_fournisseurs.php";
  }
  /** 
   * initialisation du module
   *
   *
   */

  function init()
  {
    /*
     * Permissions
     */
    $sql = array();
    
    return $this->_init($sql);
  }
  /** suppression du module
   *
   *
   */
  function remove()
  {
    $sql = array();

    return $this->_remove($sql);
  }
}
?>
