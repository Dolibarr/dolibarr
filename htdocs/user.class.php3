<?PHP
/* Copyright (c) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * 
 * $Id$
 * $Source$
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

class User {
  var $bs;
  var $db;

  var $id;
  var $fullname;
  var $nom;
  var $prenom;
  var $code;
  var $email;
  var $admin;
  var $comm;
  var $compta;

  Function User($DB, $id=0) {

    $this->db = $DB;
    $this->id = $id;
    
    return 1;
  }
  /*
   *
   *
   *
   */

  Function fetch($login) {

    $sql = "SELECT u.rowid, u.name, u.firstname, u.email, u.code, u.admin, u.module_comm, u.module_compta,webcal_login";
    $sql .= " FROM llx_user as u";

    if ($this->id) {
      $sql .= " WHERE u.rowid = $this->id";
    } else {
      $sql .= " WHERE u.login = '$login'";
    }
  
    $result = $this->db->query($sql);

    if ($result) {
      if ($this->db->num_rows()) {
	$obj = $this->db->fetch_object($result , 0);

	$this->id = $obj->rowid;
	$this->nom = $obj->name;
	$this->prenom = $obj->firstname;

	$this->fullname = $this->prenom . ' ' . $this->nom;
	$this->admin = $obj->admin;
	$this->webcal_login = $obj->webcal_login;
	$this->code = $obj->code;
	$this->email = $obj->email;

	$this->comm = $obj->module_comm;
	$this->compta = $obj->module_compta;
      }
      $this->db->free();

    } else {
      print $this->db->error();
    }
  }


}
/*
 * $Id$
 * $Source$
 */
?>
