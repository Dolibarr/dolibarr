<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class Account {
  var $rowid;
  var $bank;
  var $label;
  var $number;
  var $courant;

  Function Account($DB, $rowid=0) {
    global $config;

    $this->db = $DB;
    $this->rowid = $rowid;
    
    return 1;
  }

  Function fetch($id) {
    $this->id = $id; 
    $sql = "SELECT rowid, label, bank, number, courant FROM llx_bank_account";
    $sql .= " WHERE rowid  = ".$id;

    $result = $this->db->query($sql);

    if ($result) {
      if ($this->db->num_rows()) {
	$obj = $this->db->fetch_object($result , 0);

	$this->bank = $obj->bank;
	$this->label = $obj->label;
	$this->number = $obj->number;
	$this->courant = $obj->courant;
      }
      $this->db->free();
    }
  }

  Function solde() {
    $sql = "SELECT sum(amount) FROM llx_bank WHERE fk_account=$this->id AND dateo <=" . $this->db->idate(time() );

    $result = $this->db->query($sql);

    if ($result) {
      if ($this->db->num_rows()) {
	$solde = $this->db->result(0,0);

	return $solde;
      }
      $this->db->free();
    }
  }


}

?>
