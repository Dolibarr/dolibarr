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

/*!     \defgroup   boutique     Module boutique
        \brief      Module pour gérer une boutique et interface avec OSC
*/

/*!
        \file       htdocs/includes/modules/modBoutique.class.php
        \brief      Fichier de description et activation du module Boutique
*/

include_once "DolibarrModules.class.php";

class modBoutique extends DolibarrModules
{

  /*
   * Initialisation
   *
   */

  function modBoutique($DB)
  {
    $this->db = $DB ;
    $this->numero = 86 ;

    $this->family = "products";
    $this->name = "Boutique";
    $this->description = "Gestion des boutiques";
    $this->const_name = "MAIN_MODULE_BOUTIQUE";
    $this->const_config = MAIN_MODULE_BOUTIQUE;

    // Config pages
    $this->config_page_url = array("boutique.php","osc-languages.php");

    // Dépendances
    $this->depends = array();
    $this->requiredby = array();

    $this->const = array();
    $this->boxes = array();
    /*
     * Boites
     */
    $this->boxes[0][0] = "Livres";
    $this->boxes[0][1] = "box_boutique_livre.php";
  }
  /*
   *
   *
   *
   */
  function init()
  {
    /*
     *  Activation du module
     */

    $sql = array();
    
    return $this->_init($sql);
  }
  /*
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
