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

/*!     \defgroup   postnuke     Module postnuke
        \brief      Module d'interfacage avec Postnuke
*/

/*!
        \file       htdocs/includes/modules/modPostNuke.class.php
        \brief      Fichier de description et activation du module Postnuke
*/

include_once "DolibarrModules.class.php";

class modPostnuke extends DolibarrModules
{

  /*
   * Initialisation
   *
   */

  function modPostnuke($DB)
  {
    $this->db = $DB ;
    $this->numero = 210;

    $this->family = "technic";
    $this->name = "Postnuke";
    $this->description = "Gestion de l'outil Postnuke";
    $this->const_name = "MAIN_MODULE_POSTNUKE";
    $this->const_config = MAIN_MODULE_POSTNUKE;

    // Dépendances
    $this->depends = array();
    $this->requiredby = array();

    $this->const = array();
    $this->boxes = array();
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

    $sql = array(
		 );
    
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
