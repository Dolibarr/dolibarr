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

class ActionComm {
  var $id;
  var $db;

  var $date;
  var $type;
  var $user;
  var $societe;
  var $contact;
  var $note;

  Function ActionComm($db) {
    $this->db = $db;
  }
  /*
   *
   *
   *
   */
  Function add() {
    $sql = "INSERT INTO actioncomm (datea, fk_action, fk_soc, fk_user_author, fk_contact, note) ";
    $sql .= " VALUES ('$this->date',$this->type,$this->societe,$this->user,$this->contact,'$this->note')";

    if ($this->db->query($sql) ) {

    }
  }
  /*
   *
   *
   *
   */
  Function fetch($db, $id) {

    $sql = "SELECT libelle FROM c_actioncomm WHERE id=$id;";

    if ($db->query($sql) ) {
      if ($db->num_rows()) {
	$obj = $db->fetch_object(0);

	$this->id = $rowid;
	$this->datep = $obj->dp;
	$this->ref = $obj->ref;
	$this->price = $obj->price;
	
	$db->free();
      }
    } else {
      print $db->error();
    }    
  }
}    
?>
    
