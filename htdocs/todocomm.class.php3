<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class TodoComm {
  var $id;
  var $db;

  var $date;
  var $libelle;

  var $user;
  var $creator;

  var $societe;
  var $contact;
  var $note;

  Function TodoComm($DB) {
    $this->db = $DB;
  }
  /*
   *
   *
   *
   */
  Function add($user) {
    $sqldate = $this->db->idate($this->date);

    $sql = "INSERT INTO llx_todocomm (datea, label, fk_soc, fk_user_author, fk_contact, note) ";
    $sql .= " VALUES ('$sqldate', '$this->libelle', $this->societe, $user->id, $this->contact, '$this->note')";

    if ($this->db->query($sql) ) {


    }
  }
  /*
   *
   *
   *
   */
  Function fetch($db, $id) {

    $sql = "SELECT label FROM llx_todocomm WHERE rowid=$id;";

    if ($db->query($sql) ) {
      if ($db->num_rows()) {
	$obj = $db->fetch_object(0);

	$this->id = $rowid;
	$this->libelle = $obj->label;
	$this->note = $obj->note;
	
	$db->free();
      }
    } else {
      print $db->error();
    }    
  }
}    
?>
    
