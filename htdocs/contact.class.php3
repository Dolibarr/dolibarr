<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * 
 * $Id$
 * $Source$
 * Classe Company
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

class Contact {
  var $bs;
  var $db;

  var $id;
  var $fullname;
  var $nom;
  var $prenom;
  var $code;
  var $email;

  Function Contact($DB, $id=0) {

    $this->db = $DB;
    $this->id = $id;
    
    return 1;
  }
  /*
   *
   *
   *
   */

  Function fetch($id) {

    $sql = "SELECT c.idp, c.idp, c.name, c.firstname";
    $sql .= " FROM socpeople as c";
    $sql .= " WHERE c.idp = $id";
  
    $result = $this->db->query($sql);

    if ($result) {
      if ($this->db->num_rows()) {
	$obj = $this->db->fetch_object($result , 0);

	$this->id = $obj->idp;
	$this->nom = $obj->name;
	$this->prenom = $obj->firstname;

	$this->fullname = $this->prenom . ' ' . $this->nom;

	$this->code = $obj->code;
	$this->email = $obj->email;
      }
      $this->db->free();

    } else {
      print $this->db->error();
    }
  }


}

?>
