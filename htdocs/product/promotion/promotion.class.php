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

class Promotion {
  var $db ;

  var $id ;
  var $parent_id ;
  var $oscid ;
  var $ref;
  var $titre;
  var $description;
  var $price ;
  var $status ;

  function Promotion($DB, $id=0) {
    $this->db = $DB;
    $this->id   = $id ;
  }  
  /*
   *
   *
   *
   */
  function create($user, $pid, $percent) {

    $sql = "SELECT products_price ";
    $sql .= " FROM ".DB_NAME_OSC.".products as p";
    $sql .= " WHERE p.products_id = $pid";

    $result = $this->db->query($sql) ;

    if ( $result )
      {
	$result = $this->db->fetch_array();
	$this->price_init = $result["products_price"];
      }

    $newprice = 0.95 * $this->price_init;

    $date_exp = "2003-05-01";

    $sql = "INSERT INTO ".DB_NAME_OSC.".specials ";
    $sql .= " (products_id, specials_new_products_price, specials_date_added, specials_last_modified, expires_date, date_status_change, status) ";
    $sql .= " VALUES ($pid, $newprice, now(),NULL,'$date_exp',NULL,1)";

    if ($this->db->query($sql) )
      {
	$id = $this->db->last_insert_id();
	
	return $id;
      }
    else
      {
	print $this->db->error() . ' in ' . $sql;
      }    
  }
  /*
   *
   *
   *
   */
  function update($id, $user)
  {
    $sql = "UPDATE ".MAIN_DB_PREFIX."album ";
    $sql .= " SET title = '" . trim($this->titre) ."'";
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
  function set_active($id)
  {
    $sql = "UPDATE ".DB_NAME_OSC.".specials";
    $sql .= " SET status = 1";

    $sql .= " WHERE products_id = " . $id;

    if ( $this->db->query($sql) ) {
      return 1;
    } else {
      print $this->db->error() . ' in ' . $sql;
    }
  }  
  /*
   *
   */
  function set_inactive($id)
  {
    $sql = "UPDATE ".DB_NAME_OSC.".specials";
    $sql .= " SET status = 0";

    $sql .= " WHERE products_id = " . $id;

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
  function fetch ($id) {
    
    $sql = "SELECT c.categories_id, cd.categories_name, c.parent_id";
    $sql .= " FROM ".DB_NAME_OSC.".categories as c,".DB_NAME_OSC.".categories_description as cd";
    $sql .= " WHERE c.categories_id = cd.categories_id AND cd.language_id = ".OSC_LANGUAGE_ID;
    $sql .= " AND c.categories_id = $id";
    $result = $this->db->query($sql) ;

    if ( $result ) {
      $result = $this->db->fetch_array();

      $this->id          = $result["categories_id"];
      $this->parent_id   = $result["parent_id"];
      $this->name        = $result["categories_name"];
      $this->titre       = $result["title"];
      $this->description = $result["description"];
      $this->oscid       = $result["osc_id"];
    }
    $this->db->free();

    return $result;
  }


  /*
   *
   *
   */
  function delete($user) {

    $sql = "DELETE FROM ".DB_NAME_OSC.".products WHERE products_id = $idosc ";

    $sql = "DELETE FROM ".DB_NAME_OSC.".products_to_categories WHERE products_id = $idosc";

    $sql = "DELETE FROM ".DB_NAME_OSC.".products_description WHERE products_id = $idosc";
	      
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."album WHERE rowid = $id";
	    
    
  }


}
?>
