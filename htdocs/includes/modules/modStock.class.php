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

include_once "DolibarrModules.class.php";

class modStock extends DolibarrModules
{

  /*
   * Initialisation
   *
   */

  Function modStock($DB)
  {
    $this->db = $DB ;
    $this->numero = 52 ;

    $this->family = "products";
    $this->name = "Stock produits";
    $this->description = "Gestion des stocks";
    $this->const_name = "MAIN_MODULE_STOCK";
    $this->const_config = MAIN_MODULE_STOCK;

    // Dépendences
    $this->depends = array("modProduit");
    $this->requiredby = array();

    $this->const = array();
    $this->boxes = array();

  }
  /*
   *
   *
   *
   */

  Function init()
  {
    /*
     * Permissions
     */
    $sql = array(

		 );
    
    return $this->_init($sql);
  }
  /*
   *
   *
   */
  Function remove()
  {
    $sql = array(

		 );

    return $this->_remove($sql);

  }
}
?>
