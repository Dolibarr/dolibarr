<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class Product {
  var $db ;

  var $id ;
  var $ref;
  var $libelle;
  var $description;
  var $price ;

  Function Product($DB, $id=0) {
    $this->db = $DB;
    $this->id   = $id ;
  }  
  /*
   *
   *
   *
   */
  Function create($user) {

    $sql = "INSERT INTO llx_product (fk_user_author) VALUES (".$user->id.")";

    if ($this->db->query($sql) ) {
      $id = $this->db->last_insert_id();

      if ( $this->update($id, $user) ) {
	return $id;
      }
    } else {
      print $this->db->error() . ' in ' . $sql;
    }
  }

  /*
   *
   *
   *
   */
  Function update($id, $user) {

    $sql = "UPDATE llx_product ";
    $sql .= " SET label = '" . trim($this->libelle) ."'";
    $sql .= ",ref = '" . trim($this->ref) ."'";
    $sql .= ",price = " . $this->price ;
    $sql .= ",description = '" . trim($this->description) ."'";

    $sql .= " WHERE rowid = " . $id;

    if ( $this->db->query($sql) ) {
      return 1;
    } else {
      print $this->db->error() . ' in ' . $sql;
    }
  }
  /*
   *
   *
   *
   */
  Function fetch ($id) {
    
    $sql = "SELECT rowid, ref, label, description, price FROM llx_product WHERE rowid = $id";

    $result = $this->db->query($sql) ;

    if ( $result ) {
      $result = $this->db->fetch_array();

      $this->id          = $result["rowid"];
      $this->ref         = $result["ref"];
      $this->label       = $result["label"];
      $this->description = $result["description"];
      $this->price       = $result["price"];
    }
    $this->db->free();
    return $result;
  }

}
?>
