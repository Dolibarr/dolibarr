<?PHP
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur       <eldy@users.sourceforge.net>
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

/*!     \defgroup   commercial     Module commercial
        \brief      Module pour gérer les fonctions commerciales
*/

/*!
        \file       htdocs/includes/modules/modCommercial.class.php
        \brief      Fichier de description et activation du module Commercial
*/

include_once "DolibarrModules.class.php";

class modCommercial extends DolibarrModules
{

  /*
   * Initialisation
   *
   */

  function modCommercial($DB)
  {
    $this->db = $DB ;
    $this->numero = 2 ;

    $this->family = "crm";
    $this->name = "Commercial";
    $this->description = "Gestion commercial";
    $this->const_name = "MAIN_MODULE_COMMERCIAL";
    $this->const_config = MAIN_MODULE_COMMERCIAL;

    // Dépendances
    $this->depends = array("modSociete");
    $this->requiredby = array("modPropale","modContrat","modCommande",);

    $this->const = array();
    $this->boxes = array();
    $this->boxes[0][0] = "Derniers clients";
    $this->boxes[0][1] = "box_clients.php";
    $this->boxes[1][0] = "Derniers prospects enregistrés";
    $this->boxes[1][1] = "box_prospect.php";
  }
  /** initialisation du module
   *
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
