<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class modExternalRss extends DolibarrModules
{

  /*
   * Initialisation
   *
   */

  Function modExternalRss($DB)
  {
    $this->db = $DB ;

    $this->name = "Syndication";
    $this->description = "Module de gestion de syndication de sites externes";
    $this->const_name = "MAIN_MODULE_EXTERNALRSS";
    $this->const_config = MAIN_MODULE_EXTERNALRSS;

    $this->depends = array();

    $this->boxes = array();
    /*
     * Boites
     */
    $this->boxes[0][0] = "Syndication";
    $this->boxes[0][1] = "box_external_rss.php";

  }
  /*
   *
   *
   *
   */

  Function init()
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
  Function remove()
  {
    $sql = array();

    return $this->_remove($sql);
  }
}
?>
