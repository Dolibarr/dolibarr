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

class Client {
  var $db ;

  var $id ;
  var $nom;

  Function Client($DB, $id=0) {
    $this->db = $DB;
    $this->id = $id ;
  }  
  /*
   *
   *
   *
   */
  Function fetch ($id) {
    
    $sql = "SELECT customers_id, customers_lastname, customers_firstname FROM ".DB_NAME_OSC.".customers WHERE customers_id = $id";

    $result = $this->db->query($sql) ;

    if ( $result )
      {
	$result = $this->db->fetch_array();

	$this->id      = $result["customers_id"];
	$this->name    = stripslashes($result["customers_firstname"]) . " " . stripslashes($result["customers_lastname"]);
	
	$this->db->free();
      }
    else
      {
	print $this->db->error();
      }
    
    return $result;
  }

}
?>
