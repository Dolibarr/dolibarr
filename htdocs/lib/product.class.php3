<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Class Product
 * $Id$
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
 */

class Product {
  var $id ;
  var $price ;
  var $db ;

  Function Product($DB, $id=0) {
    $this->db = $DB;

    $this->id   = $id ;
    $this->price = $price ;

  }


  Function fetch () {
    
    $sql = "SELECT rowid, price FROM llx_product WHERE rowid = $this->id";

    $result = $this->db->query($sql) ;

    if ( $result ) {
      $result = $this->db->fetch_array();

      $this->price     = $result["price"];
      $this->id        = $result["rowid"];
    }
    return $result;
  }

}
?>
